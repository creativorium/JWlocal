<?php
/**
 * Database Management Class
 *
 * @package Thinkific_WP
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Handles custom database tables
 */
class Thinkific_WP_DB {
    
    /**
     * Table name for course mappings
     */
    const TABLE_MAPPINGS = 'thinkific_course_mappings';
    
    /**
     * Table name for enrollment logs
     */
    const TABLE_ENROLLMENTS = 'thinkific_enrollments';
    
    /**
     * Create custom tables
     */
    public static function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Course mappings table
        $table_mappings = $wpdb->prefix . self::TABLE_MAPPINGS;
        $sql_mappings = "CREATE TABLE IF NOT EXISTS $table_mappings (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            woo_product_id bigint(20) UNSIGNED NOT NULL,
            course_name varchar(255) NOT NULL,
            course_url varchar(500) NOT NULL,
            course_id varchar(100) DEFAULT NULL,
            course_description text DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY woo_product_id (woo_product_id),
            KEY course_id (course_id)
        ) $charset_collate;";
        
        // Enrollment logs table
        $table_enrollments = $wpdb->prefix . self::TABLE_ENROLLMENTS;
        $sql_enrollments = "CREATE TABLE IF NOT EXISTS $table_enrollments (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            order_id bigint(20) UNSIGNED NOT NULL,
            user_id bigint(20) UNSIGNED NOT NULL,
            product_id bigint(20) UNSIGNED NOT NULL,
            course_id varchar(100) NOT NULL,
            thinkific_user_id varchar(100) DEFAULT NULL,
            status varchar(50) NOT NULL DEFAULT 'pending',
            error_message text DEFAULT NULL,
            retry_count int(11) DEFAULT 0,
            enrolled_at datetime DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY order_id (order_id),
            KEY user_id (user_id),
            KEY status (status),
            KEY course_id (course_id),
            KEY user_course (user_id, course_id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql_mappings);
        dbDelta($sql_enrollments);
        
        self::maybe_drop_unique_enrollment_constraint();
    }
    
    /**
     * Drop unique_enrollment constraint for existing installs (allows multiple records per user/course for order tracking)
     */
    public static function maybe_drop_unique_enrollment_constraint() {
        global $wpdb;
        $table = $wpdb->prefix . self::TABLE_ENROLLMENTS;
        $index = $wpdb->get_row("SHOW INDEX FROM $table WHERE Key_name = 'unique_enrollment'");
        if ($index) {
            $wpdb->query("ALTER TABLE $table DROP INDEX unique_enrollment");
        }
    }
    
    /**
     * Get table name with prefix
     */
    public static function get_table_name($table) {
        global $wpdb;
        return $wpdb->prefix . $table;
    }
    
    /**
     * Drop tables (for uninstall)
     */
    public static function drop_tables() {
        global $wpdb;
        
        $table_mappings = $wpdb->prefix . self::TABLE_MAPPINGS;
        $table_enrollments = $wpdb->prefix . self::TABLE_ENROLLMENTS;
        
        $wpdb->query("DROP TABLE IF EXISTS $table_mappings");
        $wpdb->query("DROP TABLE IF EXISTS $table_enrollments");
    }
}
