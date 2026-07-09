<?php
/**
 * WooCommerce Integration Class
 *
 * @package Thinkific_WP
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Handles WooCommerce integration
 */
class Thinkific_WP_WooCommerce {
    
    /**
     * Logger
     */
    private $logger;
    
    /**
     * Client
     */
    private $client;
    
    /**
     * Mappings
     */
    private $mappings;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->logger = new Thinkific_WP_Logger();
        $this->client = new Thinkific_WP_Client();
        $this->mappings = new Thinkific_WP_Mappings();
        
        // Order status hooks
        add_action('woocommerce_order_status_changed', array($this, 'handle_order_status_change'), 10, 4);
        
        // Cart and product hooks
        add_filter('woocommerce_add_to_cart_redirect', array($this, 'maybe_redirect_to_checkout'));
        add_filter('woocommerce_is_sold_individually', array($this, 'force_sold_individually'), 10, 2);
        
        // Order meta box
        add_action('add_meta_boxes', array($this, 'add_order_meta_box'));
        
        // AJAX handlers
        add_action('wp_ajax_thinkific_retry_enrollment', array($this, 'ajax_retry_enrollment'));
        add_action('wp_ajax_thinkific_process_order', array($this, 'ajax_process_order'));
        
        // Account welcome email when new Thinkific user is created via API
        add_action('thinkific_wp_user_created', array($this, 'send_account_welcome_email'), 10, 4);
    }
    
    /**
     * Handle order status change
     */
    public function handle_order_status_change($order_id, $old_status, $new_status, $order) {
        $trigger_statuses = get_option('thinkific_wp_order_statuses', array('processing', 'completed'));
        
        // Check if new status should trigger enrollment
        if (!in_array($new_status, $trigger_statuses)) {
            return;
        }
        
        // Use order object for meta (HPOS compatible)
        $order_obj = is_object($order) ? $order : wc_get_order($order_id);
        if (!$order_obj) {
            return;
        }
        
        // Check if already processed
        if ($order_obj->get_meta('_thinkific_processed', true)) {
            return;
        }
        
        $this->logger->info('Processing order for enrollment', array(
            'order_id' => $order_id,
            'status' => $new_status
        ));
        
        $this->process_order_enrollments($order_id);
        
        // Mark as processed (HPOS compatible)
        $order_obj->update_meta_data('_thinkific_processed', time());
        $order_obj->save();
    }
    
    /**
     * Process enrollments for an order
     */
    public function process_order_enrollments($order_id) {
        $order = wc_get_order($order_id);
        
        if (!$order) {
            $this->logger->error('Order not found', array('order_id' => $order_id));
            return false;
        }
        
        // Get customer info
        $customer_email = $order->get_billing_email();
        $first_name = $order->get_billing_first_name();
        $last_name = $order->get_billing_last_name();
        $user_id = $order->get_user_id();
        
        if (empty($customer_email)) {
            $this->logger->error('No customer email found', array('order_id' => $order_id));
            return false;
        }
        
        // Get or create Thinkific user
        $thinkific_user = $this->client->get_or_create_user($customer_email, $first_name, $last_name);
        
        if (is_wp_error($thinkific_user)) {
            $this->logger->error('Failed to get/create Thinkific user', array(
                'order_id' => $order_id,
                'email' => $customer_email,
                'error' => $thinkific_user->get_error_message()
            ));
            
            $order_obj = wc_get_order($order_id);
            if ($order_obj) {
                $order_obj->update_meta_data('_thinkific_error', $thinkific_user->get_error_message());
                $order_obj->save();
            }
            return false;
        }
        
        $thinkific_user_id = $thinkific_user['id'];
        
        // Get mappings for order
        $mappings = $this->mappings->get_mappings_for_order($order_id);
        
        if (empty($mappings)) {
            // Log product IDs in order to help debug mapping mismatches
            $order_product_ids = array();
            foreach ($order->get_items() as $item) {
                if (is_callable(array($item, 'get_product_id'))) {
                    $order_product_ids[] = array(
                        'product_id' => $item->get_product_id(),
                        'variation_id' => is_callable(array($item, 'get_variation_id')) ? $item->get_variation_id() : 0,
                    );
                }
            }
            $this->logger->info('No course mappings found for order', array(
                'order_id' => $order_id,
                'order_products' => $order_product_ids,
                'hint' => 'Ensure product IDs in Course Mapping match the products in this order (check parent vs variation for variable products)',
            ));
            return true; // Not an error, just no courses to enroll
        }
        
        $success_count = 0;
        $error_count = 0;
        
        foreach ($mappings as $mapping) {
            $product_id = isset($mapping['_resolved_product_id']) ? $mapping['_resolved_product_id'] : $mapping['woo_product_id'];
            $result = $this->enroll_user_in_course(
                $order_id,
                $user_id,
                $product_id,
                $mapping['course_id'],
                $thinkific_user_id
            );
            
            if ($result) {
                $success_count++;
                $this->maybe_send_welcome_email($customer_email, $first_name, $mapping);
            } else {
                $error_count++;
            }
        }
        
        $this->logger->info('Order enrollment processing complete', array(
            'order_id' => $order_id,
            'success' => $success_count,
            'errors' => $error_count
        ));
        
        // Store summary in order meta (HPOS compatible)
        $order_obj = wc_get_order($order_id);
        if ($order_obj) {
            $order_obj->update_meta_data('_thinkific_enrollment_summary', array(
                'success' => $success_count,
                'errors' => $error_count,
                'processed_at' => current_time('mysql')
            ));
            $order_obj->save();
        }
        
        return $error_count === 0;
    }
    
    /**
     * Enroll user in a specific course
     */
    private function enroll_user_in_course($order_id, $wp_user_id, $product_id, $course_id, $thinkific_user_id) {
        global $wpdb;
        $table = Thinkific_WP_DB::get_table_name(Thinkific_WP_DB::TABLE_ENROLLMENTS);
        
        // Check if THIS Thinkific user is already enrolled (use thinkific_user_id, not wp user_id - guests all have user_id=0!)
        $existing = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE thinkific_user_id = %s AND course_id = %s AND status = 'enrolled' ORDER BY id DESC LIMIT 1",
            $thinkific_user_id,
            $course_id
        ), ARRAY_A);
        
        if ($existing) {
            $this->logger->info('User already enrolled in course (skipping API call)', array(
                'thinkific_user_id' => $thinkific_user_id,
                'course_id' => $course_id
            ));
            // Still insert record for THIS order so meta box shows correctly
            $wpdb->insert($table, array(
                'order_id' => $order_id,
                'user_id' => $wp_user_id,
                'product_id' => $product_id,
                'course_id' => $course_id,
                'thinkific_user_id' => $thinkific_user_id,
                'status' => 'enrolled',
                'enrolled_at' => $existing['enrolled_at'] ?: current_time('mysql'),
            ));
            return true;
        }
        
        // Enroll via API
        $result = $this->client->enroll_user($course_id, $thinkific_user_id);
        
        $enrollment_data = array(
            'order_id' => $order_id,
            'user_id' => $wp_user_id,
            'product_id' => $product_id,
            'course_id' => $course_id,
            'thinkific_user_id' => $thinkific_user_id,
        );
        
        if (is_wp_error($result)) {
            // Log enrollment failure
            $enrollment_data['status'] = 'failed';
            $enrollment_data['error_message'] = $result->get_error_message();
            $enrollment_data['retry_count'] = $existing ? ($existing['retry_count'] + 1) : 1;
            
            $this->logger->error('Enrollment failed', array(
                'order_id' => $order_id,
                'course_id' => $course_id,
                'error' => $result->get_error_message()
            ));
            
            if ($existing) {
                $wpdb->update($table, $enrollment_data, array('id' => $existing['id']));
            } else {
                $wpdb->insert($table, $enrollment_data);
            }
            
            return false;
        } else {
            // Log enrollment success
            $enrollment_data['status'] = 'enrolled';
            $enrollment_data['enrolled_at'] = current_time('mysql');
            $enrollment_data['error_message'] = null;
            
            $this->logger->info('Enrollment successful', array(
                'order_id' => $order_id,
                'course_id' => $course_id,
                'user_id' => $wp_user_id
            ));
            
            if ($existing) {
                $wpdb->update($table, $enrollment_data, array('id' => $existing['id']));
            } else {
                $wpdb->insert($table, $enrollment_data);
            }
            
            return true;
        }
    }
    
    /**
     * Send welcome email after enrollment (Thinkific may not send for API enrollments)
     */
    private function maybe_send_welcome_email($email, $first_name, $mapping) {
        if (!get_option('thinkific_wp_send_welcome_email', false)) {
            return;
        }
        
        $course_name = isset($mapping['course_name']) ? $mapping['course_name'] : __('Your Course', 'thinkific-wp');
        $course_url = isset($mapping['course_url']) ? $mapping['course_url'] : '';
        
        $subject = sprintf(
            /* translators: %s: site name */
            __('[%s] Welcome to your course!', 'thinkific-wp'),
            get_bloginfo('name')
        );
        
        $greeting = !empty($first_name) ? sprintf(__('Hi %s,', 'thinkific-wp'), $first_name) : __('Hi,', 'thinkific-wp');
        
        $message = $greeting . "\n\n"
            . __('You have been enrolled in the following course:', 'thinkific-wp') . "\n\n"
            . sprintf(__('Course: %s', 'thinkific-wp'), $course_name) . "\n\n";
        
        if (!empty($course_url)) {
            $message .= __('Access your course here:', 'thinkific-wp') . "\n" . $course_url . "\n\n";
        }
        
        $message .= __('Happy learning!', 'thinkific-wp') . "\n\n"
            . sprintf(__('— %s', 'thinkific-wp'), get_bloginfo('name'));
        
        $headers = array('Content-Type: text/plain; charset=UTF-8');
        
        wp_mail($email, $subject, $message, $headers);
    }
    
    /**
     * Send account/site welcome email when new Thinkific user is created via API.
     * Thinkific does not send its "Send site welcome email" for API-created users.
     */
    public function send_account_welcome_email($thinkific_user, $email, $first_name, $last_name) {
        if (!get_option('thinkific_wp_send_account_welcome_email', false)) {
            return;
        }
        
        $subdomain = trim(get_option('thinkific_wp_subdomain', ''));
        $subdomain = str_replace('.thinkific.com', '', $subdomain);
        
        if (empty($subdomain)) {
            $this->logger->warning('Cannot send account welcome email: subdomain not configured');
            return;
        }
        
        $school_url = 'https://' . $subdomain . '.thinkific.com';
        $sign_in_url = $school_url . '/sign_in';
        
        $subject = sprintf(
            /* translators: %s: site name */
            __('[%s] Welcome! Your learning account is ready', 'thinkific-wp'),
            get_bloginfo('name')
        );
        
        $greeting = !empty($first_name) ? sprintf(__('Hi %s,', 'thinkific-wp'), $first_name) : __('Hi,', 'thinkific-wp');
        
        $message = $greeting . "\n\n"
            . __('Your account has been created on our learning platform.', 'thinkific-wp') . "\n\n"
            . __('Sign in here to access your courses:', 'thinkific-wp') . "\n" . $sign_in_url . "\n\n"
            . __('If you need to set or reset your password, use the "Forgot password" link on the sign-in page.', 'thinkific-wp') . "\n\n"
            . __('Welcome aboard!', 'thinkific-wp') . "\n\n"
            . sprintf(__('— %s', 'thinkific-wp'), get_bloginfo('name'));
        
        $headers = array('Content-Type: text/plain; charset=UTF-8');
        
        wp_mail($email, $subject, $message, $headers);
    }
    
    /**
     * Maybe redirect to checkout
     */
    public function maybe_redirect_to_checkout($url) {
        if (!get_option('thinkific_wp_skip_cart', false)) {
            return $url;
        }
        
        // Check if added product is mapped
        $product_id = isset($_REQUEST['add-to-cart']) ? absint($_REQUEST['add-to-cart']) : 0;
        
        if (!$product_id) {
            return $url;
        }
        
        $mapping = $this->mappings->get_mapping_by_product_id($product_id);
        
        if ($mapping) {
            return wc_get_checkout_url();
        }
        
        return $url;
    }
    
    /**
     * Force sold individually
     */
    public function force_sold_individually($sold_individually, $product) {
        if (!get_option('thinkific_wp_force_single_quantity', false)) {
            return $sold_individually;
        }
        
        $mapping = $this->mappings->get_mapping_by_product_id($product->get_id());
        
        if ($mapping) {
            return true;
        }
        
        return $sold_individually;
    }
    
    /**
     * Add order meta box
     */
    public function add_order_meta_box() {
        add_meta_box(
            'thinkific_order_enrollments',
            __('Thinkific Enrollments', 'thinkific-wp'),
            array($this, 'render_order_meta_box'),
            'shop_order',
            'side',
            'default'
        );
        
        // WooCommerce HPOS compatibility
        add_meta_box(
            'thinkific_order_enrollments',
            __('Thinkific Enrollments', 'thinkific-wp'),
            array($this, 'render_order_meta_box'),
            'woocommerce_page_wc-orders',
            'side',
            'default'
        );
    }
    
    /**
     * Render order meta box
     */
    public function render_order_meta_box($post_or_order) {
        $order = $post_or_order instanceof WP_Post ? wc_get_order($post_or_order->ID) : $post_or_order;
        $order_id = $order->get_id();
        
        global $wpdb;
        $table = Thinkific_WP_DB::get_table_name(Thinkific_WP_DB::TABLE_ENROLLMENTS);
        
        $enrollments = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table WHERE order_id = %d",
            $order_id
        ), ARRAY_A);
        
        if (empty($enrollments)) {
            $processed = $order->get_meta('_thinkific_processed', true);
            if ($processed) {
                echo '<p>' . esc_html__('No courses mapped for this order.', 'thinkific-wp') . '</p>';
                echo '<p class="description">' . esc_html__('Check that the product in this order is mapped in Thinkific → Course Mapping. For variable products, map the parent product ID.', 'thinkific-wp') . '</p>';
                echo '<button type="button" class="button" onclick="thinkificForceProcessOrder(' . esc_js($order_id) . ')">' . esc_html__('Force Reprocess', 'thinkific-wp') . '</button>';
            } else {
                echo '<p>' . esc_html__('Order not yet processed for enrollment.', 'thinkific-wp') . '</p>';
                echo '<button type="button" class="button" onclick="thinkificProcessOrder(' . esc_js($order_id) . ')">' . esc_html__('Process Now', 'thinkific-wp') . '</button>';
            }
            return;
        }
        
        echo '<table class="widefat" style="margin-bottom: 10px;">';
        echo '<thead><tr><th>' . esc_html__('Course', 'thinkific-wp') . '</th><th>' . esc_html__('Status', 'thinkific-wp') . '</th></tr></thead>';
        echo '<tbody>';
        
        foreach ($enrollments as $enrollment) {
            $mapping = $this->mappings->get_mapping_by_course_id($enrollment['course_id']);
            $course_name = $mapping ? $mapping['course_name'] : $enrollment['course_id'];
            
            $status_class = $enrollment['status'] === 'enrolled' ? 'success' : 'error';
            $status_label = $enrollment['status'] === 'enrolled' 
                ? __('Enrolled', 'thinkific-wp') 
                : __('Failed', 'thinkific-wp');
            
            echo '<tr>';
            echo '<td>' . esc_html($course_name) . '</td>';
            echo '<td><span class="dashicons dashicons-' . ($enrollment['status'] === 'enrolled' ? 'yes' : 'no') . '"></span> ' . esc_html($status_label) . '</td>';
            echo '</tr>';
            
            if ($enrollment['status'] === 'failed' && !empty($enrollment['error_message'])) {
                echo '<tr><td colspan="2"><small style="color: #a00;">' . esc_html($enrollment['error_message']) . '</small></td></tr>';
                echo '<tr><td colspan="2"><button type="button" class="button button-small" onclick="thinkificRetryEnrollment(' . esc_js($enrollment['id']) . ', ' . esc_js($order_id) . ')">' . esc_html__('Retry', 'thinkific-wp') . '</button></td></tr>';
            }
        }
        
        echo '</tbody></table>';
        
        $summary = $order->get_meta('_thinkific_enrollment_summary', true);
        if ($summary) {
            echo '<p><small>' . sprintf(
                __('Last processed: %s', 'thinkific-wp'),
                $summary['processed_at']
            ) . '</small></p>';
        }
        
        ?>
        <script>
        function thinkificRetryEnrollment(enrollmentId, orderId) {
            if (!confirm('<?php esc_html_e('Retry this enrollment?', 'thinkific-wp'); ?>')) {
                return;
            }
            
            jQuery.post(ajaxurl, {
                action: 'thinkific_retry_enrollment',
                enrollment_id: enrollmentId,
                order_id: orderId,
                nonce: '<?php echo wp_create_nonce('thinkific_wp_admin'); ?>'
            }, function(response) {
                if (response.success) {
                    alert(response.data.message);
                    location.reload();
                } else {
                    alert(response.data.message);
                }
            });
        }
        
        function thinkificProcessOrder(orderId) {
            if (!confirm('<?php esc_html_e('Process enrollments for this order?', 'thinkific-wp'); ?>')) {
                return;
            }
            thinkificDoProcessOrder(orderId, false);
        }
        
        function thinkificForceProcessOrder(orderId) {
            if (!confirm('<?php esc_html_e('Clear processed flag and reprocess? Use this after fixing course mappings.', 'thinkific-wp'); ?>')) {
                return;
            }
            thinkificDoProcessOrder(orderId, true);
        }
        
        function thinkificDoProcessOrder(orderId, force) {
            jQuery.post(ajaxurl, {
                action: 'thinkific_process_order',
                order_id: orderId,
                force: force ? 1 : 0,
                nonce: '<?php echo wp_create_nonce('thinkific_wp_admin'); ?>'
            }, function(response) {
                if (response.success) {
                    alert(response.data.message);
                    location.reload();
                } else {
                    alert(response.data.message || '<?php esc_html_e('Processing failed.', 'thinkific-wp'); ?>');
                }
            });
        }
        </script>
        <?php
    }
    
    /**
     * AJAX: Retry enrollment
     */
    public function ajax_retry_enrollment() {
        check_ajax_referer('thinkific_wp_admin', 'nonce');
        
        if (!current_user_can('edit_shop_orders')) {
            wp_send_json_error(array('message' => __('Permission denied', 'thinkific-wp')));
        }
        
        $enrollment_id = isset($_POST['enrollment_id']) ? absint($_POST['enrollment_id']) : 0;
        $order_id = isset($_POST['order_id']) ? absint($_POST['order_id']) : 0;
        
        if (!$enrollment_id || !$order_id) {
            wp_send_json_error(array('message' => __('Invalid parameters', 'thinkific-wp')));
        }
        
        global $wpdb;
        $table = Thinkific_WP_DB::get_table_name(Thinkific_WP_DB::TABLE_ENROLLMENTS);
        
        $enrollment = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE id = %d",
            $enrollment_id
        ), ARRAY_A);
        
        if (!$enrollment) {
            wp_send_json_error(array('message' => __('Enrollment not found', 'thinkific-wp')));
        }
        
        // Retry enrollment
        $result = $this->enroll_user_in_course(
            $enrollment['order_id'],
            $enrollment['user_id'],
            $enrollment['product_id'],
            $enrollment['course_id'],
            $enrollment['thinkific_user_id']
        );
        
        if ($result) {
            wp_send_json_success(array('message' => __('Enrollment successful!', 'thinkific-wp')));
        } else {
            wp_send_json_error(array('message' => __('Enrollment failed. Check logs for details.', 'thinkific-wp')));
        }
    }
    
    /**
     * AJAX: Process order for enrollment (manual trigger)
     */
    public function ajax_process_order() {
        check_ajax_referer('thinkific_wp_admin', 'nonce');
        
        if (!current_user_can('edit_shop_orders')) {
            wp_send_json_error(array('message' => __('Permission denied', 'thinkific-wp')));
        }
        
        $order_id = isset($_POST['order_id']) ? absint($_POST['order_id']) : 0;
        $force = !empty($_POST['force']);
        
        if (!$order_id) {
            wp_send_json_error(array('message' => __('Invalid order ID', 'thinkific-wp')));
        }
        
        $order = wc_get_order($order_id);
        if (!$order) {
            wp_send_json_error(array('message' => __('Order not found', 'thinkific-wp')));
        }
        
        if ($force) {
            $order->delete_meta_data('_thinkific_processed');
            $order->delete_meta_data('_thinkific_enrollment_summary');
            $order->delete_meta_data('_thinkific_error');
            $order->save();
        }
        
        $this->process_order_enrollments($order_id);
        
        $order = wc_get_order($order_id);
        $order->update_meta_data('_thinkific_processed', time());
        $order->save();
        
        $mappings = $this->mappings->get_mappings_for_order($order_id);
        if (empty($mappings)) {
            wp_send_json_error(array('message' => __('No courses mapped for products in this order. Check Thinkific → Course Mapping.', 'thinkific-wp')));
        }
        
        wp_send_json_success(array('message' => __('Order processed. Check enrollment status above.', 'thinkific-wp')));
    }
}
