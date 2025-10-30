<?php
declare(strict_types=1);

/**
 * REST API for Hook Explorer
 */
class Hook_Directory_REST {

	const NAMESPACE = 'hook-explorer/v1';

	public function register_routes(): void {
		register_rest_route( self::NAMESPACE, '/hooks', array(
			'methods'             => WP_REST_Server::READABLE,
			'permission_callback' => array( $this, 'can_view' ),
			'args'                => array(
				'q'        => array( 'type' => 'string', 'sanitize_callback' => 'sanitize_text_field' ),
				'type'     => array( 'type' => 'string', 'sanitize_callback' => 'sanitize_text_field' ),
				'source'   => array( 'type' => 'string', 'sanitize_callback' => 'sanitize_text_field' ),
				'page'     => array( 'type' => 'integer', 'default' => 1 ),
				'per_page' => array( 'type' => 'integer', 'default' => 50 ),
			),
			'callback'            => array( $this, 'list_hooks' ),
		) );

		register_rest_route( self::NAMESPACE, '/scan', array(
			'methods'             => WP_REST_Server::CREATABLE,
			'permission_callback' => array( $this, 'can_manage' ),
			'callback'            => array( $this, 'scan' ),
		) );

		register_rest_route( self::NAMESPACE, '/stats', array(
			'methods'             => WP_REST_Server::READABLE,
			'permission_callback' => array( $this, 'can_view' ),
			'callback'            => array( $this, 'stats' ),
		) );
	}

	public function can_view(): bool {
		return current_user_can( 'manage_options' ); // TODO: replace with custom cap `view_hook_explorer`
	}

	public function can_manage(): bool {
		return current_user_can( 'manage_options' ); // TODO: replace with custom cap `manage_hook_explorer`
	}

	public function list_hooks( WP_REST_Request $request ): WP_REST_Response {
		global $wpdb;
		$table = $wpdb->prefix . 'hook_explorer_cache';
		$q = trim( (string) $request->get_param( 'q' ) );
		$type = trim( (string) $request->get_param( 'type' ) );
		$source = trim( (string) $request->get_param( 'source' ) );
		$page = max( 1, (int) $request->get_param( 'page' ) );
		$per = min( 200, max( 1, (int) $request->get_param( 'per_page' ) ) );
		$offset = ( $page - 1 ) * $per;

		$where = array();
		$params = array();
		if ( $q !== '' ) {
			$where[] = 'hook_name LIKE %s';
			$params[] = '%' . $wpdb->esc_like( $q ) . '%';
		}
		if ( $type !== '' ) {
			$where[] = 'hook_type = %s';
			$params[] = $type;
		}
		if ( $source !== '' ) {
			$where[] = 'source_type = %s';
			$params[] = $source;
		}
		$whereSql = $where ? ( 'WHERE ' . implode( ' AND ', $where ) ) : '';

		$total = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$table} {$whereSql}", $params ) );
		$rows = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table} {$whereSql} ORDER BY hook_name ASC LIMIT %d OFFSET %d", array_merge( $params, array( $per, $offset ) ) ), ARRAY_A );

		return new WP_REST_Response( array(
			'total'   => $total,
			'page'    => $page,
			'perPage' => $per,
			'items'   => $rows,
		) );
	}

	public function scan( WP_REST_Request $request ): WP_REST_Response {
		$scanner = new Hook_Directory_Discovery_Static();
		$count = $scanner->scan();
		return new WP_REST_Response( array( 'scanned' => $count ), 200 );
	}

	public function stats( WP_REST_Request $request ): WP_REST_Response {
		global $wpdb;
		$table = $wpdb->prefix . 'hook_explorer_cache';
		$totals = array(
			'total' => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" ),
			'by_type' => array(),
			'by_source' => array(),
		);
		$by_type = $wpdb->get_results( "SELECT hook_type, COUNT(*) c FROM {$table} GROUP BY hook_type", ARRAY_A );
		foreach ( $by_type as $row ) {
			$totals['by_type'][ $row['hook_type'] ] = (int) $row['c'];
		}
		$by_source = $wpdb->get_results( "SELECT source_type, COUNT(*) c FROM {$table} GROUP BY source_type", ARRAY_A );
		foreach ( $by_source as $row ) {
			$totals['by_source'][ $row['source_type'] ] = (int) $row['c'];
		}
		$totals['last_scan'] = (int) get_option( 'hook_explorer_last_scan', 0 );
		return new WP_REST_Response( $totals, 200 );
	}
}


