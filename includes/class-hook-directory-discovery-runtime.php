<?php
declare(strict_types=1);

/**
 * Runtime discovery engine: listens to all hooks and records occurrences with sampling.
 */
class Hook_Directory_Discovery_Runtime {

	private bool $enabled = false;
	private int $sampleEvery = 0; // 0 = every request
	private bool $inHandler = false; // reentrancy guard

	public function __construct() {
		$settings = get_option( 'hook_explorer_settings', array() );
		$this->enabled = ! empty( $settings['runtime_capture'] );
		$this->sampleEvery = isset( $settings['capture_sample'] ) ? max( 0, (int) $settings['capture_sample'] ) : 0;
	}

	/**
 	 * Begin listening if enabled.
 	 */
	public function start(): void {
		if ( ! $this->enabled ) {
			return;
		}
		add_filter( 'all', array( $this, 'on_all' ), 1, 1 );
	}

	/**
 	 * Handler for all hooks.
 	 *
 	 * @param string $hook_name
 	 */
	public function on_all( string $hook_name ): void {
		// Prevent recursion and skip extremely chatty/internal hooks
		if ( $this->inHandler || $hook_name === 'all' || $hook_name === 'query' ) {
			return;
		}
		if ( ! $this->should_sample() ) {
			return;
		}

		$this->inHandler = true;
		try {
			list( $file, $line ) = $this->find_first_non_wp_frame();

			$this->insert_event( array(
				'hook_name'   => $hook_name,
				'hook_type'   => $this->infer_type( $hook_name ),
				'file_path'   => $file,
				'line'        => $line,
				'source_type' => $this->infer_source_type( $file ),
				'source_name' => $this->infer_source_name( $file ),
			) );
		} finally {
			$this->inHandler = false;
		}
	}

	private function should_sample(): bool {
		if ( $this->sampleEvery <= 1 ) {
			return true; // 0 or 1 means always
		}
		try {
			return random_int(1, $this->sampleEvery) === 1;
		} catch (\Throwable $e) {
			return true; // fallback to capture
		}
	}

	private function find_first_non_wp_frame(): array {
		$trace = debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS, 10 );
		$base = wp_normalize_path( ABSPATH );
		$plugin_php = wp_normalize_path( ABSPATH . 'wp-includes/plugin.php' );
		foreach ( $trace as $frame ) {
			$file = isset( $frame['file'] ) ? wp_normalize_path( (string) $frame['file'] ) : '';
			if ( $file === '' ) {
				continue;
			}
			if ( $file === $plugin_php ) {
				continue; // skip core dispatcher
			}
			$relative = $this->relative_path( $file );
			return array( $relative, isset( $frame['line'] ) ? (int) $frame['line'] : 0 );
		}
		return array( '', 0 );
	}

	private function relative_path( string $abs ): string {
		$abs = wp_normalize_path( $abs );
		$base = wp_normalize_path( ABSPATH );
		if ( str_starts_with( $abs, $base ) ) {
			return ltrim( substr( $abs, strlen( $base ) ), '/' );
		}
		return $abs;
	}

	private function infer_type( string $hook_name ): string {
		// Without parsing callsite, we can't know for sure; leave as unknown.
		return 'unknown';
	}

	private function infer_source_type( string $relative_path ): string {
		if ( $relative_path === '' ) {
			return '';
		}
		$path = wp_normalize_path( $relative_path );
		if ( str_starts_with( $path, 'wp-includes/' ) || str_starts_with( $path, 'wp-admin/' ) ) {
			return 'core';
		}
		$plugins = wp_normalize_path( WP_PLUGIN_DIR );
		$themes = wp_normalize_path( get_theme_root() );
		$abs = wp_normalize_path( ABSPATH . $path );
		if ( str_starts_with( $abs, rtrim( $plugins, '/' ) . '/' ) ) {
			return 'plugin';
		}
		if ( str_starts_with( $abs, rtrim( $themes, '/' ) . '/' ) ) {
			return 'theme';
		}
		return '';
	}

	private function infer_source_name( string $relative_path ): string {
		if ( $relative_path === '' ) {
			return '';
		}
		$abs = wp_normalize_path( ABSPATH . $relative_path );
		$plugins_root = rtrim( wp_normalize_path( WP_PLUGIN_DIR ), '/' ) . '/';
		$themes_root  = rtrim( wp_normalize_path( get_theme_root() ), '/' ) . '/';
		if ( str_starts_with( $abs, $plugins_root ) ) {
			$rest = substr( $abs, strlen( $plugins_root ) );
			return explode( '/', $rest )[0] ?? '';
		}
		if ( str_starts_with( $abs, $themes_root ) ) {
			$rest = substr( $abs, strlen( $themes_root ) );
			return explode( '/', $rest )[0] ?? '';
		}
		return '';
	}

	private function insert_event( array $e ): void {
		global $wpdb;
		$table = $wpdb->prefix . 'hook_explorer_cache';
		$wpdb->insert(
			$table,
			array(
				'hook_name'        => $e['hook_name'],
				'hook_type'        => $e['hook_type'],
				'file_path'        => $e['file_path'],
				'line'             => (int) $e['line'],
				'source_type'      => $e['source_type'],
				'source_name'      => $e['source_name'],
				'detection_method' => 'runtime',
				'first_seen'       => current_time( 'mysql', true ),
				'last_seen'        => current_time( 'mysql', true ),
			),
			array( '%s','%s','%s','%d','%s','%s','%s','%s','%s' )
		);
	}
}


