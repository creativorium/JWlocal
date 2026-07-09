<?php
/**
 * Admin Interface Class
 *
 * @package Thinkific_WP
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Handles admin interface
 */
class Thinkific_WP_Admin {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'handle_admin_actions'));
        
        // AJAX handlers
        add_action('wp_ajax_thinkific_test_connection', array($this, 'ajax_test_connection'));
        add_action('wp_ajax_thinkific_clear_logs', array($this, 'ajax_clear_logs'));
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_menu_page(
            __('Thinkific', 'thinkific-wp'),
            __('Thinkific', 'thinkific-wp'),
            'manage_options',
            'thinkific',
            array($this, 'render_settings_page'),
            'dashicons-welcome-learn-more',
            56
        );
        
        add_submenu_page(
            'thinkific',
            __('Settings', 'thinkific-wp'),
            __('Settings', 'thinkific-wp'),
            'manage_options',
            'thinkific',
            array($this, 'render_settings_page')
        );
        
        add_submenu_page(
            'thinkific',
            __('Course Mapping', 'thinkific-wp'),
            __('Course Mapping', 'thinkific-wp'),
            'manage_options',
            'thinkific-mappings',
            array($this, 'render_mappings_page')
        );
        
        add_submenu_page(
            'thinkific',
            __('Logs', 'thinkific-wp'),
            __('Logs', 'thinkific-wp'),
            'manage_options',
            'thinkific-logs',
            array($this, 'render_logs_page')
        );
    }
    
    /**
     * Handle admin actions
     */
    public function handle_admin_actions() {
        // Handle actions here if needed
    }
    
    /**
     * Render settings page
     */
    public function render_settings_page() {
        // Show credential diagnostic if configured
        $api_key = get_option('thinkific_wp_api_key', '');
        $subdomain = get_option('thinkific_wp_subdomain', '');
        
        $show_diagnostic = !empty($api_key) && !empty($subdomain);
        
        ?>
        <div class="wrap thinkific-admin">
            <h1><?php esc_html_e('Thinkific Integration Settings', 'thinkific-wp'); ?></h1>
            
            <div class="thinkific-admin-header">
                <p class="description">
                    <?php esc_html_e('Configure your Thinkific API settings and integration options.', 'thinkific-wp'); ?>
                </p>
            </div>
            
            <?php if ($show_diagnostic) : 
                $clean_subdomain = str_replace('.thinkific.com', '', trim($subdomain));
                $is_bearer = substr_count($api_key, '.') === 2;
                $auth_type = $is_bearer ? 'API Access Token (JWT Bearer)' : 'Private API Key';
                
                // Try to extract subdomain from JWT if empty
                $display_subdomain = $clean_subdomain;
                if (empty($display_subdomain) && $is_bearer) {
                    $parts = explode('.', $api_key);
                    if (isset($parts[1])) {
                        $payload = json_decode(base64_decode(strtr($parts[1], '-_', '+/')), true);
                        if (isset($payload['subdomain'])) {
                            $display_subdomain = $payload['subdomain'];
                        }
                    }
                }
            ?>
            <div class="notice notice-info inline" style="margin-bottom: 20px;">
                <h3><?php esc_html_e('Current Configuration', 'thinkific-wp'); ?></h3>
                <table class="widefat" style="max-width: 700px;">
                    <tr>
                        <th style="width: 180px;"><?php esc_html_e('Authentication Type', 'thinkific-wp'); ?></th>
                        <td>
                            <strong style="color: <?php echo $is_bearer ? '#0073aa' : '#2271b1'; ?>;">
                                <?php echo esc_html($auth_type); ?>
                            </strong>
                        </td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e('Subdomain', 'thinkific-wp'); ?></th>
                        <td>
                            <code><?php echo esc_html($display_subdomain); ?></code>
                            <?php if (empty($clean_subdomain) && $is_bearer) : ?>
                                <small style="color: #0073aa;">(<?php esc_html_e('auto-detected from token', 'thinkific-wp'); ?>)</small>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e('API Token', 'thinkific-wp'); ?></th>
                        <td>
                            <code><?php echo esc_html(substr($api_key, 0, 10)); ?>...<?php echo esc_html(substr($api_key, -10)); ?></code>
                            <small>(<?php echo esc_html(strlen($api_key)); ?> characters)</small>
                        </td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e('API URL', 'thinkific-wp'); ?></th>
                        <td><code><?php echo esc_html(get_option('thinkific_wp_api_base_url', 'https://api.thinkific.com/api/public/v1')); ?></code></td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e('School URL', 'thinkific-wp'); ?></th>
                        <td>
                            <a href="https://<?php echo esc_attr($display_subdomain); ?>.thinkific.com" target="_blank">
                                https://<?php echo esc_html($display_subdomain); ?>.thinkific.com
                            </a>
                        </td>
                    </tr>
                </table>
                <p style="margin-top: 10px;">
                    <strong><?php esc_html_e('Verification Steps:', 'thinkific-wp'); ?></strong><br>
                    1. Visit your school URL above - should load your Thinkific site<br>
                    2. <?php echo $is_bearer ? 'JWT token should be 400-600 characters' : 'API key should be 32-64 characters'; ?><br>
                    3. Click "Test Connection" below to verify authentication
                </p>
            </div>
            <?php endif; ?>
            
            <div class="thinkific-admin-notices">
                <?php settings_errors(); ?>
            </div>
            
            <form method="post" action="options.php">
                <?php
                settings_fields('thinkific_settings');
                do_settings_sections(Thinkific_WP_Settings::PAGE_SLUG);
                ?>
                
                <div class="thinkific-admin-actions">
                    <?php submit_button(__('Save Settings', 'thinkific-wp'), 'primary', 'submit', false); ?>
                    
                    <button type="button" 
                            class="button button-secondary" 
                            id="thinkific-test-connection">
                        <?php esc_html_e('Test Connection', 'thinkific-wp'); ?>
                    </button>
                </div>
            </form>
            
            <div id="thinkific-test-result" style="margin-top: 20px;"></div>
            
            <div class="thinkific-admin-info" style="margin-top: 40px;">
                <h2><?php esc_html_e('Important Notes', 'thinkific-wp'); ?></h2>
                
                <div class="notice notice-info inline">
                    <h3><?php esc_html_e('Growth Plan Limitations', 'thinkific-wp'); ?></h3>
                    <p>
                        <?php esc_html_e('The Thinkific Growth plan does not support native SSO (Single Sign-On). This plugin provides a seamless experience by:', 'thinkific-wp'); ?>
                    </p>
                    <ul style="list-style: disc; margin-left: 20px;">
                        <li><?php esc_html_e('Automatically creating/enrolling users via API', 'thinkific-wp'); ?></li>
                        <li><?php esc_html_e('Providing a "My Courses" dashboard in WordPress', 'thinkific-wp'); ?></li>
                        <li><?php esc_html_e('Guiding users to use the same email for Thinkific login', 'thinkific-wp'); ?></li>
                    </ul>
                </div>
                
                <div class="notice notice-warning inline">
                    <h3><?php esc_html_e('New Course Builder Compatibility', 'thinkific-wp'); ?></h3>
                    <p>
                        <?php esc_html_e('If you\'re using the New Course Builder, the course listing sync may not work. The plugin is designed to work with manual course mapping, so this won\'t prevent functionality.', 'thinkific-wp'); ?>
                    </p>
                </div>
                
                <div class="thinkific-shortcode-info" style="background: #f5f5f5; padding: 15px; border-left: 4px solid #2271b1; margin-top: 20px;">
                    <h3><?php esc_html_e('Student Dashboard Shortcode', 'thinkific-wp'); ?></h3>
                    <p><?php esc_html_e('Use this shortcode to display the course dashboard on any page:', 'thinkific-wp'); ?></p>
                    <code style="background: #fff; padding: 5px 10px; display: inline-block; font-size: 14px;">[thinkific_dashboard]</code>
                    <p style="margin-top: 10px;">
                        <strong><?php esc_html_e('Optional attributes:', 'thinkific-wp'); ?></strong><br>
                        <code>title</code> - <?php esc_html_e('Dashboard title (default: "My Courses")', 'thinkific-wp'); ?><br>
                        <code>show_description</code> - <?php esc_html_e('Show course descriptions (default: "yes")', 'thinkific-wp'); ?>
                    </p>
                </div>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            $('#thinkific-test-connection').on('click', function() {
                var $button = $(this);
                var $result = $('#thinkific-test-result');
                
                $button.prop('disabled', true).text('<?php esc_html_e('Testing...', 'thinkific-wp'); ?>');
                $result.html('');
                
                $.post(ajaxurl, {
                    action: 'thinkific_test_connection',
                    nonce: '<?php echo wp_create_nonce('thinkific_wp_admin'); ?>'
                }, function(response) {
                    $button.prop('disabled', false).text('<?php esc_html_e('Test Connection', 'thinkific-wp'); ?>');
                    
                    if (response.success) {
                        $result.html('<div class="notice notice-success inline"><p>' + response.data.message + '</p></div>');
                    } else {
                        $result.html('<div class="notice notice-error inline"><p>' + response.data.message + '</p></div>');
                    }
                });
            });
        });
        </script>
        <?php
    }
    
    /**
     * Render mappings page
     */
    public function render_mappings_page() {
        $mappings_obj = new Thinkific_WP_Mappings();
        $mappings = $mappings_obj->get_all_mappings();
        
        // Get all products
        $products = wc_get_products(array(
            'limit' => -1,
            'status' => 'publish',
        ));
        
        ?>
        <div class="wrap thinkific-admin">
            <h1><?php esc_html_e('Course Mapping', 'thinkific-wp'); ?></h1>
            
            <p class="description">
                <?php esc_html_e('Map WooCommerce products to Thinkific courses. When a customer purchases a mapped product, they will automatically be enrolled in the corresponding course.', 'thinkific-wp'); ?>
            </p>
            
            <div class="thinkific-mappings-actions" style="margin: 20px 0;">
                <button type="button" class="button button-primary" id="thinkific-add-mapping-btn">
                    <?php esc_html_e('Add New Mapping', 'thinkific-wp'); ?>
                </button>
                
                <button type="button" class="button button-secondary" id="thinkific-sync-courses-btn">
                    <?php esc_html_e('Sync Courses from Thinkific', 'thinkific-wp'); ?>
                </button>
            </div>
            
            <div id="thinkific-mapping-form" style="display: none; background: #f5f5f5; padding: 20px; margin: 20px 0; border: 1px solid #ccc;">
                <h2><?php esc_html_e('Add/Edit Course Mapping', 'thinkific-wp'); ?></h2>
                
                <table class="form-table">
                    <tr>
                        <th><?php esc_html_e('WooCommerce Product', 'thinkific-wp'); ?></th>
                        <td>
                            <select id="mapping-product-id" class="regular-text">
                                <option value=""><?php esc_html_e('Select a product...', 'thinkific-wp'); ?></option>
                                <?php foreach ($products as $product) : ?>
                                    <option value="<?php echo esc_attr($product->get_id()); ?>">
                                        <?php echo esc_html($product->get_name()); ?> (ID: <?php echo $product->get_id(); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e('Course Name', 'thinkific-wp'); ?></th>
                        <td>
                            <input type="text" id="mapping-course-name" class="regular-text" placeholder="<?php esc_attr_e('e.g., Introduction to WordPress', 'thinkific-wp'); ?>" />
                            <p class="description"><?php esc_html_e('Display name for the course in WordPress', 'thinkific-wp'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e('Course URL', 'thinkific-wp'); ?></th>
                        <td>
                            <input type="url" id="mapping-course-url" class="regular-text" placeholder="https://yourschool.thinkific.com/courses/your-course" />
                            <p class="description"><?php esc_html_e('The full URL to the course on Thinkific (this is what "Continue Course" will link to)', 'thinkific-wp'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e('Course ID (Optional)', 'thinkific-wp'); ?></th>
                        <td>
                            <input type="text" id="mapping-course-id" class="regular-text" placeholder="123456" />
                            <p class="description"><?php esc_html_e('Thinkific course ID (if known). Used for API enrollment.', 'thinkific-wp'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e('Description (Optional)', 'thinkific-wp'); ?></th>
                        <td>
                            <textarea id="mapping-course-description" class="large-text" rows="3"></textarea>
                            <p class="description"><?php esc_html_e('Course description shown in the student dashboard', 'thinkific-wp'); ?></p>
                        </td>
                    </tr>
                </table>
                
                <p>
                    <button type="button" class="button button-primary" id="thinkific-save-mapping">
                        <?php esc_html_e('Save Mapping', 'thinkific-wp'); ?>
                    </button>
                    <button type="button" class="button" id="thinkific-cancel-mapping">
                        <?php esc_html_e('Cancel', 'thinkific-wp'); ?>
                    </button>
                </p>
            </div>
            
            <div id="thinkific-sync-result"></div>
            
            <table class="wp-list-table widefat fixed striped" style="margin-top: 20px;">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Product', 'thinkific-wp'); ?></th>
                        <th><?php esc_html_e('Course Name', 'thinkific-wp'); ?></th>
                        <th><?php esc_html_e('Course URL', 'thinkific-wp'); ?></th>
                        <th><?php esc_html_e('Course ID', 'thinkific-wp'); ?></th>
                        <th><?php esc_html_e('Actions', 'thinkific-wp'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($mappings)) : ?>
                        <tr>
                            <td colspan="5" style="text-align: center; padding: 20px;">
                                <?php esc_html_e('No mappings yet. Click "Add New Mapping" to get started.', 'thinkific-wp'); ?>
                            </td>
                        </tr>
                    <?php else : ?>
                        <?php foreach ($mappings as $mapping) : 
                            $product = wc_get_product($mapping['woo_product_id']);
                            $product_name = $product ? $product->get_name() : __('Product not found', 'thinkific-wp');
                        ?>
                            <tr>
                                <td>
                                    <?php echo esc_html($product_name); ?>
                                    <small>(ID: <?php echo esc_html($mapping['woo_product_id']); ?>)</small>
                                </td>
                                <td><?php echo esc_html($mapping['course_name']); ?></td>
                                <td>
                                    <a href="<?php echo esc_url($mapping['course_url']); ?>" target="_blank">
                                        <?php echo esc_html(wp_trim_words($mapping['course_url'], 6, '...')); ?>
                                    </a>
                                </td>
                                <td><?php echo esc_html($mapping['course_id'] ?: '—'); ?></td>
                                <td>
                                    <button type="button" 
                                            class="button button-small thinkific-delete-mapping" 
                                            data-mapping-id="<?php echo esc_attr($mapping['id']); ?>">
                                        <?php esc_html_e('Delete', 'thinkific-wp'); ?>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            // Show add mapping form
            $('#thinkific-add-mapping-btn').on('click', function() {
                $('#thinkific-mapping-form').slideDown();
            });
            
            // Cancel mapping
            $('#thinkific-cancel-mapping').on('click', function() {
                $('#thinkific-mapping-form').slideUp();
                $('#thinkific-mapping-form input, #thinkific-mapping-form textarea').val('');
            });
            
            // Save mapping
            $('#thinkific-save-mapping').on('click', function() {
                var $button = $(this);
                var data = {
                    action: 'thinkific_add_mapping',
                    nonce: '<?php echo wp_create_nonce('thinkific_wp_admin'); ?>',
                    product_id: $('#mapping-product-id').val(),
                    course_name: $('#mapping-course-name').val(),
                    course_url: $('#mapping-course-url').val(),
                    course_id: $('#mapping-course-id').val(),
                    course_description: $('#mapping-course-description').val()
                };
                
                if (!data.product_id || !data.course_name || !data.course_url) {
                    alert('<?php esc_html_e('Please fill in all required fields', 'thinkific-wp'); ?>');
                    return;
                }
                
                $button.prop('disabled', true).text('<?php esc_html_e('Saving...', 'thinkific-wp'); ?>');
                
                $.post(ajaxurl, data, function(response) {
                    $button.prop('disabled', false).text('<?php esc_html_e('Save Mapping', 'thinkific-wp'); ?>');
                    
                    if (response.success) {
                        alert(response.data.message);
                        location.reload();
                    } else {
                        alert(response.data.message);
                    }
                });
            });
            
            // Delete mapping
            $('.thinkific-delete-mapping').on('click', function() {
                if (!confirm('<?php esc_html_e('Are you sure you want to delete this mapping?', 'thinkific-wp'); ?>')) {
                    return;
                }
                
                var $button = $(this);
                var mappingId = $button.data('mapping-id');
                
                $button.prop('disabled', true).text('<?php esc_html_e('Deleting...', 'thinkific-wp'); ?>');
                
                $.post(ajaxurl, {
                    action: 'thinkific_delete_mapping',
                    nonce: '<?php echo wp_create_nonce('thinkific_wp_admin'); ?>',
                    mapping_id: mappingId
                }, function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert(response.data.message);
                        $button.prop('disabled', false).text('<?php esc_html_e('Delete', 'thinkific-wp'); ?>');
                    }
                });
            });
            
            // Sync courses
            $('#thinkific-sync-courses-btn').on('click', function() {
                var $button = $(this);
                var $result = $('#thinkific-sync-result');
                
                $button.prop('disabled', true).text('<?php esc_html_e('Syncing...', 'thinkific-wp'); ?>');
                $result.html('');
                
                $.post(ajaxurl, {
                    action: 'thinkific_sync_courses',
                    nonce: '<?php echo wp_create_nonce('thinkific_wp_admin'); ?>'
                }, function(response) {
                    $button.prop('disabled', false).text('<?php esc_html_e('Sync Courses from Thinkific', 'thinkific-wp'); ?>');
                    
                    if (response.success) {
                        var html = '<div class="notice notice-success inline"><p>' + response.data.message + '</p>';
                        
                        if (response.data.courses && response.data.courses.length > 0) {
                            html += '<p><?php esc_html_e('Available courses:', 'thinkific-wp'); ?></p><ul style="list-style: disc; margin-left: 20px;">';
                            response.data.courses.forEach(function(course) {
                                html += '<li><strong>' + course.name + '</strong> (ID: ' + course.id + ')</li>';
                            });
                            html += '</ul><p><small><?php esc_html_e('Use these IDs when creating mappings above.', 'thinkific-wp'); ?></small></p>';
                        }
                        
                        html += '</div>';
                        $result.html(html);
                    } else {
                        var noticeType = response.data.is_empty ? 'warning' : 'error';
                        $result.html('<div class="notice notice-' + noticeType + ' inline"><p>' + response.data.message + '</p></div>');
                    }
                });
            });
        });
        </script>
        <?php
    }
    
    /**
     * Render logs page
     */
    public function render_logs_page() {
        $logger = new Thinkific_WP_Logger();
        $logs = $logger->get_logs(100);
        
        ?>
        <div class="wrap thinkific-admin">
            <h1><?php esc_html_e('Activity Logs', 'thinkific-wp'); ?></h1>
            
            <p class="description">
                <?php esc_html_e('View plugin activity and troubleshoot issues.', 'thinkific-wp'); ?>
            </p>
            
            <p>
                <button type="button" class="button" id="thinkific-clear-logs">
                    <?php esc_html_e('Clear Logs', 'thinkific-wp'); ?>
                </button>
                <button type="button" class="button" onclick="location.reload()">
                    <?php esc_html_e('Refresh', 'thinkific-wp'); ?>
                </button>
            </p>
            
            <?php if (empty($logs)) : ?>
                <div class="notice notice-info inline">
                    <p><?php esc_html_e('No logs yet.', 'thinkific-wp'); ?></p>
                </div>
            <?php else : ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th width="15%"><?php esc_html_e('Time', 'thinkific-wp'); ?></th>
                            <th width="10%"><?php esc_html_e('Level', 'thinkific-wp'); ?></th>
                            <th width="50%"><?php esc_html_e('Message', 'thinkific-wp'); ?></th>
                            <th width="25%"><?php esc_html_e('Context', 'thinkific-wp'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($logs as $log) : 
                            $level_class = 'thinkific-log-' . esc_attr($log['level']);
                        ?>
                            <tr class="<?php echo esc_attr($level_class); ?>">
                                <td><?php echo esc_html($log['timestamp']); ?></td>
                                <td>
                                    <span class="thinkific-log-level thinkific-log-level-<?php echo esc_attr($log['level']); ?>">
                                        <?php echo esc_html(strtoupper($log['level'])); ?>
                                    </span>
                                </td>
                                <td><?php echo esc_html($log['message']); ?></td>
                                <td>
                                    <?php if (!empty($log['context'])) : ?>
                                        <details>
                                            <summary><?php esc_html_e('View context', 'thinkific-wp'); ?></summary>
                                            <pre style="font-size: 11px; background: #f5f5f5; padding: 10px; overflow: auto;"><?php echo esc_html(print_r($log['context'], true)); ?></pre>
                                        </details>
                                    <?php else : ?>
                                        —
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            $('#thinkific-clear-logs').on('click', function() {
                if (!confirm('<?php esc_html_e('Are you sure you want to clear all logs?', 'thinkific-wp'); ?>')) {
                    return;
                }
                
                var $button = $(this);
                $button.prop('disabled', true).text('<?php esc_html_e('Clearing...', 'thinkific-wp'); ?>');
                
                $.post(ajaxurl, {
                    action: 'thinkific_clear_logs',
                    nonce: '<?php echo wp_create_nonce('thinkific_wp_admin'); ?>'
                }, function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert(response.data.message);
                        $button.prop('disabled', false).text('<?php esc_html_e('Clear Logs', 'thinkific-wp'); ?>');
                    }
                });
            });
        });
        </script>
        <?php
    }
    
    /**
     * AJAX: Test connection
     */
    public function ajax_test_connection() {
        check_ajax_referer('thinkific_wp_admin', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permission denied', 'thinkific-wp')));
        }
        
        $client = new Thinkific_WP_Client();
        $result = $client->test_connection();
        
        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result);
        }
    }
    
    /**
     * AJAX: Clear logs
     */
    public function ajax_clear_logs() {
        check_ajax_referer('thinkific_wp_admin', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permission denied', 'thinkific-wp')));
        }
        
        $logger = new Thinkific_WP_Logger();
        $logger->clear_logs();
        
        wp_send_json_success(array('message' => __('Logs cleared successfully', 'thinkific-wp')));
    }
}
