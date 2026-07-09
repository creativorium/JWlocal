<?php
/**
 * Helper Functions
 *
 * @package Thinkific_WP
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Get plugin instance
 */
function thinkific_wp() {
    return Thinkific_WP_Plugin::instance();
}

/**
 * Log a message
 */
function thinkific_wp_log($message, $level = 'info', $context = array()) {
    $logger = thinkific_wp()->logger;
    $logger->log($message, $level, $context);
}

/**
 * Check if user has access to a course
 */
function thinkific_wp_user_has_course_access($user_id, $course_id) {
    // Check WooCommerce orders
    $orders = wc_get_orders(array(
        'customer_id' => $user_id,
        'status' => array('processing', 'completed'),
        'limit' => -1,
    ));
    
    $mappings = new Thinkific_WP_Mappings();
    
    foreach ($orders as $order) {
        $order_mappings = $mappings->get_mappings_for_order($order->get_id());
        
        foreach ($order_mappings as $mapping) {
            if ($mapping['course_id'] === $course_id) {
                return true;
            }
        }
    }
    
    return false;
}

/**
 * Get user's enrolled courses
 */
function thinkific_wp_get_user_courses($user_id) {
    $dashboard = new Thinkific_WP_Dashboard();
    $user = get_userdata($user_id);
    
    if (!$user) {
        return array();
    }
    
    return $dashboard->get_user_courses($user_id, $user->user_email);
}

/**
 * Format Thinkific course URL
 */
function thinkific_wp_format_course_url($course_url) {
    // Ensure URL has protocol
    if (!preg_match('~^(?:f|ht)tps?://~i', $course_url)) {
        $course_url = 'https://' . $course_url;
    }
    
    return esc_url($course_url);
}

/**
 * Get enrollment status for order
 */
function thinkific_wp_get_order_enrollment_status($order_id) {
    global $wpdb;
    $table = Thinkific_WP_DB::get_table_name(Thinkific_WP_DB::TABLE_ENROLLMENTS);
    
    $enrollments = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM $table WHERE order_id = %d",
        $order_id
    ), ARRAY_A);
    
    if (empty($enrollments)) {
        return array(
            'status' => 'not_processed',
            'total' => 0,
            'enrolled' => 0,
            'failed' => 0,
        );
    }
    
    $stats = array(
        'status' => 'partial',
        'total' => count($enrollments),
        'enrolled' => 0,
        'failed' => 0,
    );
    
    foreach ($enrollments as $enrollment) {
        if ($enrollment['status'] === 'enrolled') {
            $stats['enrolled']++;
        } elseif ($enrollment['status'] === 'failed') {
            $stats['failed']++;
        }
    }
    
    if ($stats['enrolled'] === $stats['total']) {
        $stats['status'] = 'completed';
    } elseif ($stats['failed'] === $stats['total']) {
        $stats['status'] = 'failed';
    }
    
    return $stats;
}

/**
 * Clear all Thinkific caches
 */
function thinkific_wp_clear_all_caches() {
    global $wpdb;
    
    // Clear transients
    $wpdb->query(
        "DELETE FROM {$wpdb->options} 
         WHERE option_name LIKE '_transient_thinkific_%' 
         OR option_name LIKE '_transient_timeout_thinkific_%'"
    );
    
    thinkific_wp_log('All caches cleared', 'info');
    
    return true;
}

/**
 * Get plugin version
 */
function thinkific_wp_get_version() {
    return THINKIFIC_WP_VERSION;
}

/**
 * Check if API is configured
 */
function thinkific_wp_is_api_configured() {
    $api_key = get_option('thinkific_wp_api_key', '');
    $subdomain = get_option('thinkific_wp_subdomain', '');
    
    return !empty($api_key) && !empty($subdomain);
}

/**
 * Get mapped product IDs
 */
function thinkific_wp_get_mapped_product_ids() {
    $mappings = new Thinkific_WP_Mappings();
    $all_mappings = $mappings->get_all_mappings();
    
    return array_column($all_mappings, 'woo_product_id');
}

/**
 * Check if product is mapped
 */
function thinkific_wp_is_product_mapped($product_id) {
    $mappings = new Thinkific_WP_Mappings();
    $mapping = $mappings->get_mapping_by_product_id($product_id);
    
    return !empty($mapping);
}

/**
 * Get course mapping for product
 */
function thinkific_wp_get_product_course_mapping($product_id) {
    $mappings = new Thinkific_WP_Mappings();
    return $mappings->get_mapping_by_product_id($product_id);
}

/**
 * Sanitize course ID
 */
function thinkific_wp_sanitize_course_id($course_id) {
    return preg_replace('/[^a-zA-Z0-9_-]/', '', $course_id);
}

/**
 * Get Thinkific subdomain URL
 */
function thinkific_wp_get_subdomain_url() {
    $subdomain = get_option('thinkific_wp_subdomain', '');
    
    if (empty($subdomain)) {
        return '';
    }
    
    // Remove .thinkific.com if included
    $subdomain = str_replace('.thinkific.com', '', $subdomain);
    
    return 'https://' . $subdomain . '.thinkific.com';
}

/**
 * Format error message for display
 */
function thinkific_wp_format_error($error) {
    if (is_wp_error($error)) {
        return $error->get_error_message();
    }
    
    if (is_string($error)) {
        return $error;
    }
    
    if (is_array($error)) {
        return implode(', ', $error);
    }
    
    return __('Unknown error', 'thinkific-wp');
}
