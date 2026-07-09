<?php
/**
 * Course Mappings Management Class
 *
 * @package Thinkific_WP
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Manages WooCommerce product to Thinkific course mappings
 */
class Thinkific_WP_Mappings {
    
    /**
     * Constructor
     */
    public function __construct() {
        // AJAX handlers
        add_action('wp_ajax_thinkific_add_mapping', array($this, 'ajax_add_mapping'));
        add_action('wp_ajax_thinkific_delete_mapping', array($this, 'ajax_delete_mapping'));
        add_action('wp_ajax_thinkific_sync_courses', array($this, 'ajax_sync_courses'));
    }
    
    /**
     * Get all mappings
     */
    public function get_all_mappings() {
        global $wpdb;
        $table = Thinkific_WP_DB::get_table_name(Thinkific_WP_DB::TABLE_MAPPINGS);
        
        $results = $wpdb->get_results(
            "SELECT * FROM $table ORDER BY created_at DESC",
            ARRAY_A
        );
        
        return $results;
    }
    
    /**
     * Get mapping by product ID
     */
    public function get_mapping_by_product_id($product_id) {
        global $wpdb;
        $table = Thinkific_WP_DB::get_table_name(Thinkific_WP_DB::TABLE_MAPPINGS);
        
        $result = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM $table WHERE woo_product_id = %d", $product_id),
            ARRAY_A
        );
        
        return $result;
    }
    
    /**
     * Get mapping by course ID
     */
    public function get_mapping_by_course_id($course_id) {
        global $wpdb;
        $table = Thinkific_WP_DB::get_table_name(Thinkific_WP_DB::TABLE_MAPPINGS);
        
        $result = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM $table WHERE course_id = %s", $course_id),
            ARRAY_A
        );
        
        return $result;
    }
    
    /**
     * Get mapping by ID
     */
    public function get_mapping($id) {
        global $wpdb;
        $table = Thinkific_WP_DB::get_table_name(Thinkific_WP_DB::TABLE_MAPPINGS);
        
        $result = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM $table WHERE id = %d", $id),
            ARRAY_A
        );
        
        return $result;
    }
    
    /**
     * Add or update mapping
     */
    public function save_mapping($data) {
        global $wpdb;
        $table = Thinkific_WP_DB::get_table_name(Thinkific_WP_DB::TABLE_MAPPINGS);
        
        // Check if table exists
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table'") === $table;
        if (!$table_exists) {
            // Try to create table
            Thinkific_WP_DB::create_tables();
        }
        
        $mapping_data = array(
            'woo_product_id' => absint($data['woo_product_id']),
            'course_name' => sanitize_text_field($data['course_name']),
            'course_url' => esc_url_raw($data['course_url']),
            'course_id' => !empty($data['course_id']) ? sanitize_text_field($data['course_id']) : null,
            'course_description' => !empty($data['course_description']) ? wp_kses_post($data['course_description']) : null,
        );
        
        // Check if mapping exists
        $existing = $this->get_mapping_by_product_id($mapping_data['woo_product_id']);
        
        if ($existing) {
            // Update
            $result = $wpdb->update(
                $table,
                $mapping_data,
                array('id' => $existing['id']),
                array('%d', '%s', '%s', '%s', '%s'),
                array('%d')
            );
            
            if ($result === false) {
                return false; // Update failed
            }
            return $existing['id'];
        } else {
            // Insert
            $result = $wpdb->insert(
                $table,
                $mapping_data,
                array('%d', '%s', '%s', '%s', '%s')
            );
            
            if ($result === false) {
                return false; // Insert failed
            }
            return $wpdb->insert_id;
        }
    }
    
    /**
     * Delete mapping
     */
    public function delete_mapping($id) {
        global $wpdb;
        $table = Thinkific_WP_DB::get_table_name(Thinkific_WP_DB::TABLE_MAPPINGS);
        
        return $wpdb->delete(
            $table,
            array('id' => $id),
            array('%d')
        );
    }
    
    /**
     * Get mappings for order
     * Checks both parent product ID and variation ID (for variable products)
     */
    public function get_mappings_for_order($order_id) {
        $order = wc_get_order($order_id);
        if (!$order) {
            return array();
        }
        
        $mappings = array();
        $added_course_ids = array(); // Avoid duplicates if parent + variation both match
        
        foreach ($order->get_items() as $item) {
            if (!is_callable(array($item, 'get_product_id'))) {
                continue;
            }
            
            $product_id = $item->get_product_id();
            $variation_id = is_callable(array($item, 'get_variation_id')) ? $item->get_variation_id() : 0;
            
            // Check parent product ID first
            $mapping = $this->get_mapping_by_product_id($product_id);
            
            // If no mapping and item has a variation, check variation ID
            if (!$mapping && $variation_id > 0) {
                $mapping = $this->get_mapping_by_product_id($variation_id);
                if ($mapping) {
                    $product_id = $variation_id; // Use variation for enrollment record
                }
            }
            
            if ($mapping && !in_array($mapping['course_id'], $added_course_ids)) {
                $mappings[] = array_merge($mapping, array('_resolved_product_id' => $product_id));
                $added_course_ids[] = $mapping['course_id'];
            }
        }
        
        return $mappings;
    }
    
    /**
     * AJAX: Add mapping
     */
    public function ajax_add_mapping() {
        check_ajax_referer('thinkific_wp_admin', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permission denied', 'thinkific-wp')));
        }
        
        $data = array(
            'woo_product_id' => isset($_POST['product_id']) ? absint($_POST['product_id']) : 0,
            'course_name' => isset($_POST['course_name']) ? sanitize_text_field($_POST['course_name']) : '',
            'course_url' => isset($_POST['course_url']) ? esc_url_raw($_POST['course_url']) : '',
            'course_id' => isset($_POST['course_id']) ? sanitize_text_field($_POST['course_id']) : '',
            'course_description' => isset($_POST['course_description']) ? wp_kses_post($_POST['course_description']) : '',
        );
        
        // Validate
        if (empty($data['woo_product_id']) || empty($data['course_name']) || empty($data['course_url'])) {
            wp_send_json_error(array('message' => __('Product ID, course name, and course URL are required', 'thinkific-wp')));
        }
        
        // Verify product exists
        $product = wc_get_product($data['woo_product_id']);
        if (!$product) {
            wp_send_json_error(array('message' => __('Product not found', 'thinkific-wp')));
        }
        
        $mapping_id = $this->save_mapping($data);
        
        if ($mapping_id) {
            $logger = new Thinkific_WP_Logger();
            $logger->info('Mapping saved', array(
                'mapping_id' => $mapping_id,
                'product_id' => $data['woo_product_id'],
                'course_name' => $data['course_name']
            ));
            
            wp_send_json_success(array(
                'message' => __('Mapping saved successfully', 'thinkific-wp'),
                'mapping_id' => $mapping_id
            ));
        } else {
            global $wpdb;
            $logger = new Thinkific_WP_Logger();
            $logger->error('Failed to save mapping', array(
                'data' => $data,
                'wpdb_error' => $wpdb->last_error,
                'wpdb_query' => $wpdb->last_query
            ));
            
            $error_message = __('Failed to save mapping', 'thinkific-wp');
            if ($wpdb->last_error) {
                $error_message .= ': ' . $wpdb->last_error;
            }
            
            wp_send_json_error(array('message' => $error_message));
        }
    }
    
    /**
     * AJAX: Delete mapping
     */
    public function ajax_delete_mapping() {
        check_ajax_referer('thinkific_wp_admin', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permission denied', 'thinkific-wp')));
        }
        
        $mapping_id = isset($_POST['mapping_id']) ? absint($_POST['mapping_id']) : 0;
        
        if (!$mapping_id) {
            wp_send_json_error(array('message' => __('Invalid mapping ID', 'thinkific-wp')));
        }
        
        $result = $this->delete_mapping($mapping_id);
        
        if ($result) {
            $logger = new Thinkific_WP_Logger();
            $logger->info('Mapping deleted', array('mapping_id' => $mapping_id));
            
            wp_send_json_success(array('message' => __('Mapping deleted successfully', 'thinkific-wp')));
        } else {
            wp_send_json_error(array('message' => __('Failed to delete mapping', 'thinkific-wp')));
        }
    }
    
    /**
     * AJAX: Sync courses from Thinkific
     */
    public function ajax_sync_courses() {
        check_ajax_referer('thinkific_wp_admin', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permission denied', 'thinkific-wp')));
        }
        
        $client = new Thinkific_WP_Client();
        
        if (!$client->is_configured()) {
            wp_send_json_error(array('message' => __('Thinkific API is not configured', 'thinkific-wp')));
        }
        
        $courses = $client->get_courses();
        
        if (is_wp_error($courses)) {
            wp_send_json_error(array(
                'message' => sprintf(
                    __('Failed to sync courses: %s', 'thinkific-wp'),
                    $courses->get_error_message()
                )
            ));
        }
        
        if (empty($courses)) {
            wp_send_json_error(array(
                'message' => __('No courses found. This may be due to the New Course Builder. Please add mappings manually.', 'thinkific-wp'),
                'is_empty' => true
            ));
        }
        
        wp_send_json_success(array(
            'message' => sprintf(__('Found %d courses', 'thinkific-wp'), count($courses)),
            'courses' => $courses
        ));
    }
}
