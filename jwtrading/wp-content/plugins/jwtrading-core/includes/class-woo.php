<?php
defined( 'ABSPATH' ) || exit;

/**
 * Central dispatcher: order events → Kit + Sheets sync.
 * Rule #1: never block checkout. Everything is try/catch'd and retried by cron.
 */
class JWT_Woo {

	public static function init() {
		// Order → Kit + Sheets sync is handled by the JW Integrations plugin now
		// (consolidated Kit Auto Tagger + Google Sheet Sync). Core's own dispatch is
		// left OFF to avoid a duplicate/second sync. Flip this filter to true only if
		// you remove JW Integrations and want Core to take over order syncing again.
		if ( ! apply_filters( 'jwt/enable_core_order_sync', false ) ) {
			return;
		}

		// Duitku confirms payment → order moves to processing. Completed also covered as safety net.
		add_action( 'woocommerce_order_status_processing', array( __CLASS__, 'dispatch_order_sync' ), 10, 1 );
		add_action( 'woocommerce_order_status_completed', array( __CLASS__, 'dispatch_order_sync' ), 10, 1 );
	}

	/**
	 * Run both syncs for an order. Idempotent: skips targets already synced.
	 *
	 * @param int $order_id Order ID.
	 */
	public static function dispatch_order_sync( $order_id ) {
		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return;
		}

		if ( 'yes' !== $order->get_meta( '_jwt_synced_kit' ) && get_option( 'jwt_kit_enabled', 'yes' ) === 'yes' ) {
			try {
				$result = JWT_Kit_Sync::sync_order( $order );
				if ( true === $result ) {
					$order->update_meta_data( '_jwt_synced_kit', 'yes' );
				}
			} catch ( Throwable $e ) {
				JWT_Sync_Log::log( $order_id, 'kit', 'failed', array(), $e->getMessage() );
			}
		}

		if ( 'yes' !== $order->get_meta( '_jwt_synced_sheets' ) && get_option( 'jwt_sheets_enabled', 'yes' ) === 'yes' ) {
			try {
				$result = JWT_Sheets_Sync::sync_order( $order );
				if ( true === $result ) {
					$order->update_meta_data( '_jwt_synced_sheets', 'yes' );
				}
			} catch ( Throwable $e ) {
				JWT_Sync_Log::log( $order_id, 'sheets', 'failed', array(), $e->getMessage() );
			}
		}

		$order->save();
	}

	/**
	 * Cron: retry failed syncs (max attempts enforced by JWT_Sync_Log).
	 */
	public static function retry_failed_syncs() {
		foreach ( JWT_Sync_Log::get_failed() as $row ) {
			$order = wc_get_order( (int) $row->order_id );
			if ( ! $order ) {
				continue;
			}

			try {
				if ( 'kit' === $row->target ) {
					$result = JWT_Kit_Sync::sync_order( $order );
					if ( true === $result ) {
						$order->update_meta_data( '_jwt_synced_kit', 'yes' );
						$order->save();
					}
				} elseif ( 'sheets' === $row->target ) {
					$result = JWT_Sheets_Sync::sync_order( $order );
					if ( true === $result ) {
						$order->update_meta_data( '_jwt_synced_sheets', 'yes' );
						$order->save();
					}
				}
			} catch ( Throwable $e ) {
				JWT_Sync_Log::log( (int) $row->order_id, $row->target, 'failed', array(), 'Retry: ' . $e->getMessage() );
			}
		}
	}
}
