<?php
declare(strict_types=1);

/**
 * Build markdown documentation for discovered hooks.
 */
class Hook_Directory_Docs {

	public function build_markdown(): string {
		global $wpdb;
		$table = $wpdb->prefix . 'hook_explorer_cache';
		$rows = $wpdb->get_results( "SELECT hook_name, hook_type, source_type, source_name, file_path FROM {$table} ORDER BY hook_name ASC", ARRAY_A );

		$lines = array();
		$lines[] = '# Site Hooks';
		$lines[] = '';
		$lines[] = '*Generated: ' . gmdate( 'Y-m-d H:i:s' ) . ' UTC*';
		$lines[] = '';
		$lines[] = '## Table of Contents';
		$lines[] = '';
		$seen = array();
		foreach ( $rows as $row ) {
			$name = $row['hook_name'];
			if ( isset( $seen[ $name ] ) ) {
				continue;
			}
			$seen[ $name ] = true;
			$lines[] = '- [' . $name . '](#' . sanitize_title( $name ) . ')';
		}
		$lines[] = '';

		$current = '';
		foreach ( $rows as $row ) {
			$name = $row['hook_name'];
			if ( $name !== $current ) {
				if ( $current !== '' ) {
					$lines[] = '';
				}
				$current = $name;
				$lines[] = '---';
				$lines[] = '### ' . $name;
				$lines[] = '';
			}
			$src = trim( ($row['source_type'] ?? '') . ' ' . ($row['source_name'] ?? '') );
			$lines[] = '- ' . ($row['hook_type'] ?? '') . ' — ' . ($src !== '' ? $src : '');
			if ( ! empty( $row['file_path'] ) ) {
				$lines[] = '  - ' . $row['file_path'];
			}
		}

		return implode( "\n", $lines ) . "\n";
	}
}


