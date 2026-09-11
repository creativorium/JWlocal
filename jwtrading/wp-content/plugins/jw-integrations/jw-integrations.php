<?php
/**
 * Plugin Name: JW Integrations
 * Plugin URI: https://creativorium.com
 * Description: Consolidated JW integrations — Kit Auto Tagger and WooCommerce Google Sheet Sync bundled as one plugin. Behaves exactly like the original plugins (same classes, hooks, options, admin pages); it just loads them from one place. The Thinkific module was retired when the courses moved to Yapp.
 * Version: 1.0.0
 * Author: Abetnego
 * Author URI: https://creativorium.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Requires at least: 5.8
 * Requires PHP: 7.4
 *
 * @package JW_Integrations
 */

defined( 'ABSPATH' ) || exit;

define( 'JW_INTEGRATIONS_VERSION', '1.0.0' );
define( 'JW_INTEGRATIONS_FILE', __FILE__ );
define( 'JW_INTEGRATIONS_DIR', plugin_dir_path( __FILE__ ) );
define( 'JW_INTEGRATIONS_MODULES', JW_INTEGRATIONS_DIR . 'modules/' );

/**
 * Load the bundled modules.
 *
 * Each module is the ORIGINAL plugin's code, unchanged. Its main file uses
 * __FILE__-relative paths, so every include/asset/textdomain still resolves
 * from its own modules/<name>/ folder — behavior is identical.
 *
 * Each require is guarded on a symbol the legacy standalone plugin defines, so
 * if you happen to have the old plugin still active during the switch-over this
 * module is simply skipped instead of fataling with a "cannot redeclare" error.
 * (Recommended flow: deactivate the old plugins, THEN activate this one.)
 *
 * RETIRED: the Thinkific module is no longer loaded. Yapp hosts the courses and
 * grants access itself, so the enrollment API call it made on order completion
 * has nothing left to talk to. Its files stay under modules/thinkific/ (and its
 * DB table and logs stay in the database) purely so the enrollment history is
 * still readable if it is ever needed — nothing runs.
 */
if ( ! class_exists( 'JW_Kit_Auto_Tagger' ) ) {
	require_once JW_INTEGRATIONS_MODULES . 'kit-tagger/jw-kit-auto-tagger.php';
}
if ( ! function_exists( 'jw_gsheet_sync_init' ) ) {
	require_once JW_INTEGRATIONS_MODULES . 'sheet-sync/jw-woocommerce-google-sheet-sync.php';
}

/**
 * Declare WooCommerce HPOS compatibility for THIS plugin file (the active one).
 * The modules also declare it for their own paths; this is the declaration WP
 * checks for jw-integrations.
 */
add_action( 'before_woocommerce_init', function () {
	if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
	}
} );
