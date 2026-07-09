<?php
/**
 * Plugin Name: Thinkific WooCommerce Integration
 * Plugin URI: https://creativorium.com
 * Description: Production-ready integration between Thinkific (Growth plan) and WooCommerce. Auto-enrolls customers and provides a seamless course access dashboard.
 * Version: 1.1.5
 * Author: Abetnego
 * Author URI: https://creativorium.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: thinkific-wp
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * WC requires at least: 5.0
 * WC tested up to: 8.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Plugin constants
define('THINKIFIC_WP_VERSION', '1.1.5');
define('THINKIFIC_WP_PLUGIN_FILE', __FILE__);
define('THINKIFIC_WP_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('THINKIFIC_WP_PLUGIN_URL', plugin_dir_url(__FILE__));
define('THINKIFIC_WP_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Check if WooCommerce is active
 */
function thinkific_wp_check_woocommerce() {
    if (!class_exists('WooCommerce')) {
        add_action('admin_notices', function() {
            ?>
            <div class="notice notice-error">
                <p><?php esc_html_e('Thinkific WooCommerce Integration requires WooCommerce to be installed and activated.', 'thinkific-wp'); ?></p>
            </div>
            <?php
        });
        return false;
    }
    return true;
}

/**
 * Initialize the plugin
 */
function thinkific_wp_init() {
    if (!thinkific_wp_check_woocommerce()) {
        return;
    }

    // Load dependencies
    require_once THINKIFIC_WP_PLUGIN_DIR . 'includes/helpers.php';
    require_once THINKIFIC_WP_PLUGIN_DIR . 'includes/class-logger.php';
    require_once THINKIFIC_WP_PLUGIN_DIR . 'includes/class-db.php';
    Thinkific_WP_DB::maybe_drop_unique_enrollment_constraint();
    require_once THINKIFIC_WP_PLUGIN_DIR . 'includes/class-thinkific-client.php';
    require_once THINKIFIC_WP_PLUGIN_DIR . 'includes/class-settings.php';
    require_once THINKIFIC_WP_PLUGIN_DIR . 'includes/class-mappings.php';
    require_once THINKIFIC_WP_PLUGIN_DIR . 'includes/class-admin.php';
    require_once THINKIFIC_WP_PLUGIN_DIR . 'includes/class-dashboard.php';
    require_once THINKIFIC_WP_PLUGIN_DIR . 'includes/class-woocommerce.php';
    require_once THINKIFIC_WP_PLUGIN_DIR . 'includes/class-plugin.php';

    // Initialize plugin
    Thinkific_WP_Plugin::instance();
}
add_action('plugins_loaded', 'thinkific_wp_init');

/**
 * Declare WooCommerce HPOS compatibility
 */
add_action('before_woocommerce_init', function() {
    if (class_exists(\Automattic\WooCommerce\Utilities\FeaturesUtil::class)) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
    }
});

/**
 * Activation hook
 */
function thinkific_wp_activate() {
    if (!class_exists('WooCommerce')) {
        deactivate_plugins(plugin_basename(__FILE__));
        wp_die(
            esc_html__('Thinkific WooCommerce Integration requires WooCommerce to be installed and activated.', 'thinkific-wp'),
            esc_html__('Plugin Activation Error', 'thinkific-wp'),
            array('back_link' => true)
        );
    }

    require_once THINKIFIC_WP_PLUGIN_DIR . 'includes/class-db.php';
    Thinkific_WP_DB::create_tables();
    
    // Set default options
    add_option('thinkific_wp_version', THINKIFIC_WP_VERSION);
    add_option('thinkific_wp_api_base_url', 'https://api.thinkific.com/api/public/v1');
    add_option('thinkific_wp_course_cache_duration', 86400); // 24 hours
    add_option('thinkific_wp_enrollment_cache_duration', 600); // 10 minutes
    add_option('thinkific_wp_order_statuses', array('processing', 'completed'));
    add_option('thinkific_wp_enable_logging', 1);
    
    flush_rewrite_rules();
}
register_activation_hook(__FILE__, 'thinkific_wp_activate');

/**
 * Deactivation hook
 */
function thinkific_wp_deactivate() {
    flush_rewrite_rules();
}
register_deactivation_hook(__FILE__, 'thinkific_wp_deactivate');
