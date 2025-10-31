<?php
declare(strict_types=1);

/**
 * Simple cache/background writer for Hook Explorer entries.
 */
class Hook_Directory_Cache {

	const QUEUE_OPTION = 'hook_explorer_queue';
	const CRON_HOOK    = 'hook_explorer_process_queue';

	/**
	 * Replace all static-detected entries. Uses background processing when large.
	 *
	 * @param array $entries
	 * @return int number of rows handled (queued or inserted)
	 */
	public function replace_static_entries( array $entries ): int {
		global $wpdb;
		$table = $wpdb->prefix . 'hook_explorer_cache';

		// Verify table exists
		$table_exists = $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $table ) );
		if ( $table_exists !== $table ) {
			error_log( 'Hook Explorer: Table does not exist: ' . $table );
			return 0;
		}

		// Clear previous static detections first.
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$table} WHERE detection_method = %s", 'static' ) );

		$count = count( $entries );
		if ( $count === 0 ) {
			return 0;
		}

		// For small sets, write synchronously.
		if ( $count <= 500 ) {
			$this->insert_batch( $entries );
			return $count;
		}

		// For large sets, process first chunk immediately, queue rest for background.
		$firstChunk = array_slice( $entries, 0, 500 );
		$remaining = array_slice( $entries, 500 );
		$this->insert_batch( $firstChunk );
		if ( ! empty( $remaining ) ) {
			$this->queue_entries( $remaining, 500 );
			$this->schedule_processing();
		}
		return $count;
	}

	/**
	 * Insert a batch of entries (array of associative arrays with required keys).
	 */
	public function insert_batch( array $entries ): void {
		global $wpdb;
		$table = $wpdb->prefix . 'hook_explorer_cache';
		$now = current_time( 'mysql', true );
		$inserted = 0;
		$errors = 0;
		foreach ( $entries as $e ) {
			$result = $wpdb->insert(
				$table,
				array(
					'hook_name'        => $e['hook_name'] ?? '',
					'hook_type'        => $e['hook_type'] ?? '',
					'file_path'        => $e['file_path'] ?? null,
					'line'             => isset( $e['line'] ) ? (int) $e['line'] : null,
					'source_type'      => $e['source_type'] ?? null,
					'source_name'      => $e['source_name'] ?? null,
					'detection_method' => $e['detection_method'] ?? 'static',
					'first_seen'       => $e['first_seen'] ?? $now,
					'last_seen'        => $e['last_seen'] ?? $now,
				),
				array( '%s','%s','%s','%d','%s','%s','%s','%s','%s' )
			);
			if ( $result === false ) {
				$errors++;
				if ( $errors <= 3 ) {
					error_log( 'Hook Explorer insert failed: ' . $wpdb->last_error . ' | Hook: ' . ( $e['hook_name'] ?? 'unknown' ) );
				}
			} else {
				$inserted++;
			}
		}
		if ( $errors > 0 ) {
			error_log( "Hook Explorer: Inserted {$inserted}, Failed {$errors} out of " . count( $entries ) );
		}
	}

	/**
	 * Queue entries split into chunks for background processing.
	 */
	public function queue_entries( array $entries, int $chunkSize = 500 ): void {
		$queue = get_option( self::QUEUE_OPTION, array() );
		$queue = is_array( $queue ) ? $queue : array();
		$chunks = array_chunk( $entries, max( 1, $chunkSize ) );
		$queue = array_merge( $queue, $chunks );
		update_option( self::QUEUE_OPTION, $queue, false );
	}

	/**
	 * Process one queued chunk; reschedule if more remain.
	 */
	public function process_queue_chunk(): void {
		$queue = get_option( self::QUEUE_OPTION, array() );
		if ( empty( $queue ) || ! is_array( $queue ) ) {
			return;
		}
		$chunk = array_shift( $queue );
		update_option( self::QUEUE_OPTION, $queue, false );
		if ( is_array( $chunk ) && ! empty( $chunk ) ) {
			$this->insert_batch( $chunk );
		}
		// If more remain, schedule next run soon.
		if ( ! empty( $queue ) ) {
			$this->schedule_processing( 15 );
		}
	}

	/**
	 * Ensure a cron is scheduled to process the queue.
	 *
	 * @param int $delaySeconds
	 */
	public function schedule_processing( int $delaySeconds = 0 ): void {
		$timestamp = time() + max( 0, $delaySeconds );
		if ( ! wp_next_scheduled( self::CRON_HOOK ) ) {
			wp_schedule_single_event( $timestamp, self::CRON_HOOK );
		}
	}
}


