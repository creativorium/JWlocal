<?php
/**
 * Plugin Name: JW WooCommerce Google Sheet Sync
 * Plugin URI: https://creativorium.com
 * Description: Sends successful WooCommerce order data to a Google Apps Script webhook for recording in Google Sheets.
 * Version: 1.0.1
 * Author: Abetnego
 * Author URI: https://creativorium.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: jw-gsheet-sync
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * WC requires at least: 6.0
 * WC tested up to: 8.0
 */

defined( 'ABSPATH' ) || exit;

// Declare HPOS (High-Performance Order Storage) compatibility
add_action( 'before_woocommerce_init', function() {
	if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
	}
} );

// Plugin constants
define( 'JW_GSHEET_SYNC_VERSION', '1.0.1' );
define( 'JW_GSHEET_SYNC_PLUGIN_FILE', __FILE__ );
define( 'JW_GSHEET_SYNC_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'JW_GSHEET_SYNC_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'JW_GSHEET_SYNC_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Check if WooCommerce is active before loading the plugin.
 */
function jw_gsheet_sync_check_woocommerce() {
	if ( ! class_exists( 'WooCommerce' ) ) {
		add_action( 'admin_notices', 'jw_gsheet_sync_woocommerce_missing_notice' );
		return false;
	}
	return true;
}

/**
 * Admin notice when WooCommerce is inactive.
 */
function jw_gsheet_sync_woocommerce_missing_notice() {
	$message = sprintf(
		/* translators: %s: WooCommerce plugin name */
		__( 'JW WooCommerce Google Sheet Sync requires %s to be installed and active.', 'jw-gsheet-sync' ),
		'<strong>WooCommerce</strong>'
	);
	printf(
		'<div class="notice notice-error"><p>%s</p></div>',
		wp_kses_post( $message )
	);
}

/**
 * Bootstrap the plugin.
 */
function jw_gsheet_sync_init() {
	if ( ! jw_gsheet_sync_check_woocommerce() ) {
		return;
	}

	load_plugin_textdomain( 'jw-gsheet-sync', false, dirname( JW_GSHEET_SYNC_PLUGIN_BASENAME ) . '/languages' );

	require_once JW_GSHEET_SYNC_PLUGIN_DIR . 'includes/class-jw-gsheet-sync.php';
	JW_GSheet_Sync::instance();
}

add_action( 'plugins_loaded', 'jw_gsheet_sync_init' );
