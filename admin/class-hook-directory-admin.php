<?php
declare(strict_types=1);

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://kiunyearaya.dev
 * @since      1.0.0
 *
 * @package    Hook_Directory
 * @subpackage Hook_Directory/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Hook_Directory
 * @subpackage Hook_Directory/admin
 * @author     Kiunye Araya <kiunyearaya@gmail.com>
 */
class Hook_Directory_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;

	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		// Attempt to enqueue Vite-built CSS if manifest exists
		$asset = $this->resolve_manifest_asset( 'admin.js' );
		if ( $asset && ! empty( $asset['css'] ) && is_array( $asset['css'] ) ) {
			foreach ( $asset['css'] as $idx => $cssRel ) {
				wp_enqueue_style( $this->plugin_name . '-vite-' . $idx, plugins_url( 'admin/build/' . ltrim( $cssRel, '/' ), dirname( __FILE__ ) ), array(), $this->version );
			}
			return;
		}

		// Fallback stylesheet
		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/hook-directory-admin.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		// Attempt to enqueue Vite-built JS if manifest exists
		$asset = $this->resolve_manifest_asset( 'admin.js' );
		if ( $asset && ! empty( $asset['file'] ) ) {
			wp_enqueue_script( $this->plugin_name . '-vite', plugins_url( 'admin/build/' . ltrim( $asset['file'], '/' ), dirname( __FILE__ ) ), array(), $this->version, true );
			wp_localize_script( $this->plugin_name . '-vite', 'HookExplorer', array(
				'restUrl' => esc_url_raw( get_rest_url( null, '/hook-explorer/v1' ) ),
				'nonce'   => wp_create_nonce( 'wp_rest' ),
			) );
			return;
		}

		// Fallback script
		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/hook-directory-admin.js', array( 'jquery' ), $this->version, false );
		wp_localize_script( $this->plugin_name, 'HookExplorer', array(
			'restUrl' => esc_url_raw( get_rest_url( null, '/hook-explorer/v1' ) ),
			'nonce'   => wp_create_nonce( 'wp_rest' ),
		) );

	}

	/**
	 * Resolve a Vite manifest asset by input name.
	 */
	private function resolve_manifest_asset( $input ) {
		$manifestPath = plugin_dir_path( dirname( __FILE__ ) ) . 'admin/build/manifest.json';
		if ( ! file_exists( $manifestPath ) ) {
			return null;
		}
		$contents = file_get_contents( $manifestPath );
		if ( $contents === false ) {
			return null;
		}
		$manifest = json_decode( $contents, true );
		if ( ! is_array( $manifest ) ) {
			return null;
		}
		foreach ( $manifest as $key => $data ) {
			if ( isset( $data['file'] ) && str_ends_with( (string)$key, $input ) ) {
				return $data;
			}
		}
		return null;
	}
}
