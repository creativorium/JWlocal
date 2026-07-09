<?php
/**
 * Uninstall Script
 *
 * Fired when the plugin is uninstalled.
 *
 * @package Thinkific_WP
 */

// If uninstall not called from WordPress, exit
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

/**
 * Remove plugin data
 */
function thinkific_wp_uninstall() {
    global $wpdb;
    
    // Only proceed if user wants to delete all data
    // You can add an admin option to control this behavior
    
    // Delete options
    $options = array(
        'thinkific_wp_version',
        'thinkific_wp_api_key',
        'thinkific_wp_subdomain',
        'thinkific_wp_api_base_url',
        'thinkific_wp_course_cache_duration',
        'thinkific_wp_enrollment_cache_duration',
        'thinkific_wp_order_statuses',
        'thinkific_wp_force_single_quantity',
        'thinkific_wp_skip_cart',
        'thinkific_wp_enable_logging',
        'thinkific_wp_logs',
    );
    
    foreach ($options as $option) {
        delete_option($option);
    }
    
    // Delete transients
    $wpdb->query(
        "DELETE FROM {$wpdb->options} 
         WHERE option_name LIKE '_transient_thinkific_%' 
         OR option_name LIKE '_transient_timeout_thinkific_%'"
    );
    
    // Delete custom tables
    $table_mappings = $wpdb->prefix . 'thinkific_course_mappings';
    $table_enrollments = $wpdb->prefix . 'thinkific_enrollments';
    
    $wpdb->query("DROP TABLE IF EXISTS $table_mappings");
    $wpdb->query("DROP TABLE IF EXISTS $table_enrollments");
    
    // Delete user meta
    $wpdb->query(
        "DELETE FROM {$wpdb->usermeta} 
         WHERE meta_key LIKE '_thinkific_%'"
    );
    
    // Delete post meta
    $wpdb->query(
        "DELETE FROM {$wpdb->postmeta} 
         WHERE meta_key LIKE '_thinkific_%'"
    );
    
    // Clear any remaining caches
    wp_cache_flush();
}

// Run uninstall
thinkific_wp_uninstall();
