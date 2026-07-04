<?php
/**
 * Plugin Name:       JWTrading Core
 * Description:       Business logic for jwtrading — Woo order hooks, Kit sync, Google Sheets sync, logging.
 * Version:           1.0.0
 * Requires PHP:      8.0
 * Author:            Nego
 * Text Domain:       jwtrading
 */

defined( 'ABSPATH' ) || exit;

define( 'JWT_CORE_VERSION', '1.0.0' );
define( 'JWT_CORE_PATH', plugin_dir_path( __FILE__ ) );
define( 'JWT_CORE_URL', plugin_dir_url( __FILE__ ) );

require_once JWT_CORE_PATH . 'includes/class-sync-log.php';
require_once JWT_CORE_PATH . 'includes/class-kit-sync.php';
require_once JWT_CORE_PATH . 'includes/class-sheets-sync.php';
require_once JWT_CORE_PATH . 'includes/class-woo.php';
require_once JWT_CORE_PATH . 'includes/class-admin.php';
require_once JWT_CORE_PATH . 'includes/class-plugin-guard.php';
require_once JWT_CORE_PATH . 'includes/class-checkout.php';
require_once JWT_CORE_PATH . 'includes/class-thankyou.php';
require_once JWT_CORE_PATH . 'includes/class-emails.php';
require_once JWT_CORE_PATH . 'includes/class-preview-gate.php';
require_once JWT_CORE_PATH . 'includes/class-tracking.php';

// WooCommerce-independent features — boot immediately.
JWT_Plugin_Guard::init();
JWT_Preview_Gate::init();
JWT_Tracking::init();

/**
 * Activation: create log table + schedule retry cron.
 */
register_activation_hook( __FILE__, function () {
	JWT_Sync_Log::create_table();

	if ( ! wp_next_scheduled( 'jwt_retry_failed_syncs' ) ) {
		wp_schedule_event( time() + 300, 'jwt_15min', 'jwt_retry_failed_syncs' );
	}
} );

register_deactivation_hook( __FILE__, function () {
	wp_clear_scheduled_hook( 'jwt_retry_failed_syncs' );
} );

/**
 * Custom 15-minute cron interval.
 */
add_filter( 'cron_schedules', function ( $schedules ) {
	$schedules['jwt_15min'] = array(
		'interval' => 15 * MINUTE_IN_SECONDS,
		'display'  => __( 'Every 15 minutes', 'jwtrading' ),
	);
	return $schedules;
} );

/**
 * Boot.
 */
add_action( 'plugins_loaded', function () {
	// Bail gracefully if WooCommerce is missing.
	if ( ! class_exists( 'WooCommerce' ) ) {
		add_action( 'admin_notices', function () {
			echo '<div class="notice notice-error"><p>JWTrading Core requires WooCommerce to be active.</p></div>';
		} );
		return;
	}

	JWT_Woo::init();
	JWT_Admin::init();
	JWT_Checkout::init();
	JWT_Thankyou::init();
	JWT_Emails::init();

	// Retry cron handler.
	add_action( 'jwt_retry_failed_syncs', array( 'JWT_Woo', 'retry_failed_syncs' ) );
} );
