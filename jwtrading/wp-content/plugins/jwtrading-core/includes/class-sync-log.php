<?php
defined( 'ABSPATH' ) || exit;

/**
 * Logs every sync attempt to {prefix}jwt_sync_log.
 * Debug lifesaver: "why isn't order #123 in the sheet?" → check this table.
 */
class JWT_Sync_Log {

	const MAX_ATTEMPTS = 5;

	public static function table() {
		global $wpdb;
		return $wpdb->prefix . 'jwt_sync_log';
	}

	public static function create_table() {
		global $wpdb;
		$charset = $wpdb->get_charset_collate();
		$table   = self::table();

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		dbDelta( "CREATE TABLE {$table} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			order_id BIGINT UNSIGNED NOT NULL,
			target VARCHAR(20) NOT NULL,
			status VARCHAR(10) NOT NULL DEFAULT 'pending',
			attempts TINYINT UNSIGNED NOT NULL DEFAULT 0,
			payload LONGTEXT NULL,
			response TEXT NULL,
			created_at DATETIME NOT NULL,
			updated_at DATETIME NOT NULL,
			PRIMARY KEY  (id),
			KEY order_target (order_id, target),
			KEY status (status)
		) {$charset};" );
	}

	/**
	 * Record an attempt. Returns row id.
	 *
	 * @param int    $order_id Order ID.
	 * @param string $target   'kit' | 'sheets'.
	 * @param string $status   'success' | 'failed'.
	 * @param array  $payload  Data sent.
	 * @param string $response Response body / error message.
	 */
	public static function log( $order_id, $target, $status, $payload = array(), $response = '' ) {
		global $wpdb;

		// Update existing row for this order+target if present (retries), else insert.
		$existing = $wpdb->get_row( $wpdb->prepare(
			'SELECT id, attempts FROM ' . self::table() . ' WHERE order_id = %d AND target = %s',
			$order_id,
			$target
		) );

		$now = current_time( 'mysql' );

		if ( $existing ) {
			$wpdb->update(
				self::table(),
				array(
					'status'     => $status,
					'attempts'   => (int) $existing->attempts + 1,
					'response'   => mb_substr( (string) $response, 0, 5000 ),
					'updated_at' => $now,
				),
				array( 'id' => $existing->id )
			);
			return (int) $existing->id;
		}

		$wpdb->insert(
			self::table(),
			array(
				'order_id'   => $order_id,
				'target'     => $target,
				'status'     => $status,
				'attempts'   => 1,
				'payload'    => wp_json_encode( $payload ),
				'response'   => mb_substr( (string) $response, 0, 5000 ),
				'created_at' => $now,
				'updated_at' => $now,
			)
		);
		return (int) $wpdb->insert_id;
	}

	/**
	 * Failed rows still eligible for retry.
	 */
	public static function get_failed() {
		global $wpdb;
		return $wpdb->get_results( $wpdb->prepare(
			'SELECT * FROM ' . self::table() . ' WHERE status = %s AND attempts < %d ORDER BY id ASC LIMIT 20',
			'failed',
			self::MAX_ATTEMPTS
		) );
	}
}
