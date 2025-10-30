<?php
declare(strict_types=1);

/**
 * Static discovery engine: scans filesystem for do_action/apply_filters occurrences.
 */
class Hook_Directory_Discovery_Static {

	/**
	 * Run a full static scan based on settings. Returns number of hooks recorded.
	 */
	public function scan(): int {
		$roots = $this->get_scan_roots();
		$entries = array();
		foreach ( $roots as $root ) {
			$entries = array_merge( $entries, $this->scan_root( $root['path'], $root['source_type'], $root['source_name'] ) );
		}
		return $this->write_results( $entries );
	}

	/**
	 * Determine which roots to scan from settings.
	 */
	private function get_scan_roots(): array {
		$settings = get_option( 'hook_explorer_settings', array() );
		$roots = array();

		$abs = wp_normalize_path( ABSPATH );
		$wp_includes = wp_normalize_path( ABSPATH . 'wp-includes' );
		$wp_admin    = wp_normalize_path( ABSPATH . 'wp-admin' );
		$wp_content  = wp_normalize_path( WP_CONTENT_DIR );

		if ( ! empty( $settings['scan_core'] ) ) {
			$roots[] = array( 'path' => $wp_includes, 'source_type' => 'core', 'source_name' => 'core' );
			$roots[] = array( 'path' => $wp_admin, 'source_type' => 'core', 'source_name' => 'core' );
		}

		if ( ! empty( $settings['scan_plugins'] ) ) {
			$plugins_dir = wp_normalize_path( WP_PLUGIN_DIR );
			$roots[] = array( 'path' => $plugins_dir, 'source_type' => 'plugin', 'source_name' => '' );
		}

		if ( ! empty( $settings['scan_themes'] ) ) {
			$themes_dir = wp_normalize_path( get_theme_root() );
			$roots[] = array( 'path' => $themes_dir, 'source_type' => 'theme', 'source_name' => '' );
		}

		return $roots;
	}

	/**
	 * Scan a directory tree, returning discovered hook entries.
	 */
	private function scan_root( string $root, string $source_type, string $source_name ): array {
		$results = array();
		if ( ! is_dir( $root ) ) {
			return $results;
		}
		$iterator = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator( $root, FilesystemIterator::SKIP_DOTS )
		);
		foreach ( $iterator as $file ) {
			if ( $file instanceof SplFileInfo && $file->isFile() ) {
				$path = wp_normalize_path( $file->getPathname() );
				if ( substr( $path, -4 ) !== '.php' ) {
					continue;
				}
				$relative = $this->relative_path( $path );
				$inferred_source_name = $source_name;
				if ( $source_type === 'plugin' ) {
					$inferred_source_name = $this->first_dir_after( $path, wp_normalize_path( WP_PLUGIN_DIR ) );
				} elseif ( $source_type === 'theme' ) {
					$inferred_source_name = $this->first_dir_after( $path, wp_normalize_path( get_theme_root() ) );
				}
				$results = array_merge( $results, $this->scan_file( $path, $relative, $source_type, $inferred_source_name ) );
			}
		}
		return $results;
	}

	/**
	 * Parse a PHP file and extract do_action/apply_filters calls.
	 */
	private function scan_file( string $absolute_path, string $relative_path, string $source_type, string $source_name ): array {
		$code = @file_get_contents( $absolute_path );
		if ( $code === false ) {
			return array();
		}
		$tokens = token_get_all( $code );
		$results = array();
		$fn_name = '';
		for ( $i = 0, $n = count( $tokens ); $i < $n; $i++ ) {
			$tok = $tokens[ $i ];
			if ( is_array( $tok ) ) {
				[$id, $text, $line] = $tok;
				if ( $id === T_STRING && ( $text === 'do_action' || $text === 'do_action_ref_array' || $text === 'apply_filters' || $text === 'apply_filters_ref_array' ) ) {
					$fn_name = $text;
					// Next non-whitespace should be '('
					$hook_name = $this->extract_first_arg_string( $tokens, $i + 1 );
					if ( $hook_name !== null ) {
						$results[] = array(
							'hook_name'       => $hook_name,
							'hook_type'       => str_starts_with( $fn_name, 'do_action' ) ? 'action' : 'filter',
							'file_path'       => $relative_path,
							'line'            => $line,
							'source_type'     => $source_type,
							'source_name'     => $source_name,
							'detection_method'=> 'static',
						);
					}
				}
			}
		}
		return $results;
	}

	/**
	 * Extract first argument string literal from a function call.
	 */
	private function extract_first_arg_string( array $tokens, int $start_index ): ?string {
		// advance to '('
		for ( $j = $start_index; $j < count( $tokens ); $j++ ) {
			$t = $tokens[ $j ];
			if ( is_string( $t ) ) {
				if ( $t === '(' ) {
					// next non-whitespace token should be a string literal
					for ( $k = $j + 1; $k < count( $tokens ); $k++ ) {
						$tk = $tokens[ $k ];
						if ( is_array( $tk ) ) {
							if ( $tk[0] === T_CONSTANT_ENCAPSED_STRING ) {
								return stripcslashes( substr( $tk[1], 1, -1 ) );
							}
							if ( $tk[0] === T_WHITESPACE || $tk[0] === T_COMMENT || $tk[0] === T_DOC_COMMENT ) {
								continue;
							}
							return null; // not a simple string literal
						} elseif ( is_string( $tk ) && $tk === ')' ) {
							return null;
						}
					}
				}
			}
		}
		return null;
	}

	private function relative_path( string $abs ): string {
		$abs = wp_normalize_path( $abs );
		$base = wp_normalize_path( ABSPATH );
		if ( str_starts_with( $abs, $base ) ) {
			return ltrim( substr( $abs, strlen( $base ) ), '/' );
		}
		return $abs;
	}

	private function first_dir_after( string $path, string $root ): string {
		$path = wp_normalize_path( $path );
		$root = rtrim( wp_normalize_path( $root ), '/' ) . '/';
		if ( str_starts_with( $path, $root ) ) {
			$rest = substr( $path, strlen( $root ) );
			$parts = explode( '/', $rest );
			return $parts[0] ?? '';
		}
		return '';
	}

	/**
	 * Persist results: replace existing static entries.
	 */
	private function write_results( array $entries ): int {
		global $wpdb;
		$table = $wpdb->prefix . 'hook_explorer_cache';
		// Clear previous static detections
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$table} WHERE detection_method = %s", 'static' ) );
		$count = 0;
		foreach ( $entries as $e ) {
			$wpdb->insert(
				$table,
				array(
					'hook_name'        => $e['hook_name'],
					'hook_type'        => $e['hook_type'],
					'file_path'        => $e['file_path'],
					'line'             => $e['line'],
					'source_type'      => $e['source_type'],
					'source_name'      => $e['source_name'],
					'detection_method' => 'static',
					'first_seen'       => current_time( 'mysql', true ),
					'last_seen'        => current_time( 'mysql', true ),
				),
				array( '%s','%s','%s','%d','%s','%s','%s','%s','%s' )
			);
			$count++;
		}
		update_option( 'hook_explorer_last_scan', time() );
		return $count;
	}
}


