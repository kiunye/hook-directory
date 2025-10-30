<?php
declare(strict_types=1);

/**
 * Settings registration and admin page for Hook Explorer
 *
 * @package Hook_Directory
 */

class Hook_Directory_Settings {

	/**
	 * Register settings, sections, and fields.
	 */
	public function register_settings(): void {
		register_setting(
			'hook_explorer',
			'hook_explorer_settings',
			array(
				'type'              => 'array',
				'sanitize_callback' => array( $this, 'sanitize_settings' ),
				'show_in_rest'      => false,
				'default'           => $this->get_default_settings(),
			)
		);

		add_settings_section(
			'hook_explorer_scan_section',
			__( 'Scan Settings', 'hook-directory' ),
			'__return_false',
			'hook_explorer'
		);

		add_settings_field(
			'scan_core',
			__( 'Scan WordPress Core', 'hook-directory' ),
			array( $this, 'render_checkbox' ),
			'hook_explorer',
			'hook_explorer_scan_section',
			array( 'key' => 'scan_core' )
		);

		add_settings_field(
			'scan_plugins',
			__( 'Scan Plugins', 'hook-directory' ),
			array( $this, 'render_checkbox' ),
			'hook_explorer',
			'hook_explorer_scan_section',
			array( 'key' => 'scan_plugins' )
		);

		add_settings_field(
			'scan_themes',
			__( 'Scan Themes', 'hook-directory' ),
			array( $this, 'render_checkbox' ),
			'hook_explorer',
			'hook_explorer_scan_section',
			array( 'key' => 'scan_themes' )
		);

		add_settings_section(
			'hook_explorer_runtime_section',
			__( 'Runtime Capture', 'hook-directory' ),
			'__return_false',
			'hook_explorer'
		);

		add_settings_field(
			'runtime_capture',
			__( 'Enable runtime capture', 'hook-directory' ),
			array( $this, 'render_checkbox' ),
			'hook_explorer',
			'hook_explorer_runtime_section',
			array( 'key' => 'runtime_capture' )
		);

		add_settings_field(
			'capture_sample',
			__( 'Sampling (0 = every request)', 'hook-directory' ),
			array( $this, 'render_number' ),
			'hook_explorer',
			'hook_explorer_runtime_section',
			array( 'key' => 'capture_sample', 'min' => 0, 'step' => 1 )
		);

		add_settings_section(
			'hook_explorer_cache_section',
			__( 'Cache', 'hook-directory' ),
			'__return_false',
			'hook_explorer'
		);

		add_settings_field(
			'cache_expiry_days',
			__( 'Cache expiry (days)', 'hook-directory' ),
			array( $this, 'render_number' ),
			'hook_explorer',
			'hook_explorer_cache_section',
			array( 'key' => 'cache_expiry_days', 'min' => 1, 'step' => 1 )
		);
	}

	/**
	 * Add settings page under Settings > Hook Explorer.
	 */
	public function add_admin_menu(): void {
		add_options_page(
			__( 'Hook Explorer', 'hook-directory' ),
			__( 'Hook Explorer', 'hook-directory' ),
			'manage_options',
			'hook_explorer',
			array( $this, 'render_settings_page' )
		);
	}

	/**
	 * Render the settings page.
	 */
	public function render_settings_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$settings = get_option( 'hook_explorer_settings', $this->get_default_settings() );
		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
			<form action="options.php" method="post">
				<?php
				settings_fields( 'hook_explorer' );
				do_settings_sections( 'hook_explorer' );
				submit_button();
				?>
			</form>
		</div>
		<?php
	}

	/**
	 * Checkbox field renderer.
	 */
	public function render_checkbox( array $args ): void {
		$key = $args['key'] ?? '';
		$settings = get_option( 'hook_explorer_settings', $this->get_default_settings() );
		$value = ! empty( $settings[ $key ] );
		printf(
			'<input type="checkbox" name="hook_explorer_settings[%1$s]" value="1" %2$s />',
			esc_attr( $key ),
			checked( $value, true, false )
		);
	}

	/**
	 * Number field renderer.
	 */
	public function render_number( array $args ): void {
		$key = $args['key'] ?? '';
		$min = isset( $args['min'] ) ? (int) $args['min'] : 0;
		$step = isset( $args['step'] ) ? (int) $args['step'] : 1;
		$settings = get_option( 'hook_explorer_settings', $this->get_default_settings() );
		$value = isset( $settings[ $key ] ) ? (int) $settings[ $key ] : 0;
		printf(
			'<input type="number" name="hook_explorer_settings[%1$s]" value="%2$d" min="%3$d" step="%4$d" />',
			esc_attr( $key ),
			(int) $value,
			$min,
			$step
		);
	}

	/**
	 * Sanitize settings before saving.
	 */
	public function sanitize_settings( $raw ) {
		$defaults = $this->get_default_settings();
		$raw = is_array( $raw ) ? $raw : array();
		$sanitized = array();

		$sanitized['scan_core'] = ! empty( $raw['scan_core'] );
		$sanitized['scan_plugins'] = ! empty( $raw['scan_plugins'] );
		$sanitized['scan_themes'] = ! empty( $raw['scan_themes'] );
		$sanitized['runtime_capture'] = ! empty( $raw['runtime_capture'] );
		$sanitized['capture_sample'] = max( 0, (int) ( $raw['capture_sample'] ?? $defaults['capture_sample'] ) );
		$sanitized['cache_expiry_days'] = max( 1, (int) ( $raw['cache_expiry_days'] ?? $defaults['cache_expiry_days'] ) );

		return $sanitized;
	}

	/**
	 * Default settings.
	 */
	private function get_default_settings(): array {
		return array(
			'scan_core'         => true,
			'scan_plugins'      => true,
			'scan_themes'       => true,
			'runtime_capture'   => false,
			'capture_sample'    => 0,
			'cache_expiry_days' => 7,
		);
	}
}


