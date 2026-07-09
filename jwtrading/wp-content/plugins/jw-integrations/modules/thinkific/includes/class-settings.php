<?php
/**
 * Settings Management Class
 *
 * @package Thinkific_WP
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Manages plugin settings
 */
class Thinkific_WP_Settings {
    
    /**
     * Settings page slug
     */
    const PAGE_SLUG = 'thinkific-settings';
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('admin_init', array($this, 'register_settings'));
    }
    
    /**
     * Register settings
     */
    public function register_settings() {
        // API Settings Section
        add_settings_section(
            'thinkific_api_settings',
            __('API Configuration', 'thinkific-wp'),
            array($this, 'render_api_section'),
            self::PAGE_SLUG
        );
        
        register_setting('thinkific_settings', 'thinkific_wp_subdomain', array(
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => ''
        ));
        
        register_setting('thinkific_settings', 'thinkific_wp_api_key', array(
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => ''
        ));
        
        add_settings_field(
            'thinkific_wp_subdomain',
            __('Thinkific Subdomain', 'thinkific-wp'),
            array($this, 'render_subdomain_field'),
            self::PAGE_SLUG,
            'thinkific_api_settings'
        );
        
        add_settings_field(
            'thinkific_wp_api_key',
            __('API Key', 'thinkific-wp'),
            array($this, 'render_api_key_field'),
            self::PAGE_SLUG,
            'thinkific_api_settings'
        );
        
        // Cache Settings Section
        add_settings_section(
            'thinkific_cache_settings',
            __('Cache Settings', 'thinkific-wp'),
            array($this, 'render_cache_section'),
            self::PAGE_SLUG
        );
        
        register_setting('thinkific_settings', 'thinkific_wp_course_cache_duration', array(
            'type' => 'integer',
            'sanitize_callback' => 'absint',
            'default' => 86400
        ));
        
        register_setting('thinkific_settings', 'thinkific_wp_enrollment_cache_duration', array(
            'type' => 'integer',
            'sanitize_callback' => 'absint',
            'default' => 600
        ));
        
        add_settings_field(
            'thinkific_wp_course_cache_duration',
            __('Course Cache Duration', 'thinkific-wp'),
            array($this, 'render_course_cache_field'),
            self::PAGE_SLUG,
            'thinkific_cache_settings'
        );
        
        add_settings_field(
            'thinkific_wp_enrollment_cache_duration',
            __('Enrollment Cache Duration', 'thinkific-wp'),
            array($this, 'render_enrollment_cache_field'),
            self::PAGE_SLUG,
            'thinkific_cache_settings'
        );
        
        // WooCommerce Settings Section
        add_settings_section(
            'thinkific_woo_settings',
            __('WooCommerce Settings', 'thinkific-wp'),
            array($this, 'render_woo_section'),
            self::PAGE_SLUG
        );
        
        register_setting('thinkific_settings', 'thinkific_wp_order_statuses', array(
            'type' => 'array',
            'sanitize_callback' => array($this, 'sanitize_order_statuses'),
            'default' => array('processing', 'completed')
        ));
        
        register_setting('thinkific_settings', 'thinkific_wp_force_single_quantity', array(
            'type' => 'boolean',
            'sanitize_callback' => 'rest_sanitize_boolean',
            'default' => false
        ));
        
        register_setting('thinkific_settings', 'thinkific_wp_skip_cart', array(
            'type' => 'boolean',
            'sanitize_callback' => 'rest_sanitize_boolean',
            'default' => false
        ));
        
        add_settings_field(
            'thinkific_wp_order_statuses',
            __('Enrollment Trigger Statuses', 'thinkific-wp'),
            array($this, 'render_order_statuses_field'),
            self::PAGE_SLUG,
            'thinkific_woo_settings'
        );
        
        add_settings_field(
            'thinkific_wp_force_single_quantity',
            __('Force Single Quantity', 'thinkific-wp'),
            array($this, 'render_force_single_field'),
            self::PAGE_SLUG,
            'thinkific_woo_settings'
        );
        
        add_settings_field(
            'thinkific_wp_skip_cart',
            __('Skip Cart', 'thinkific-wp'),
            array($this, 'render_skip_cart_field'),
            self::PAGE_SLUG,
            'thinkific_woo_settings'
        );
        
        register_setting('thinkific_settings', 'thinkific_wp_send_welcome_email', array(
            'type' => 'boolean',
            'sanitize_callback' => 'rest_sanitize_boolean',
            'default' => false
        ));
        
        add_settings_field(
            'thinkific_wp_send_welcome_email',
            __('Send Course Welcome Email', 'thinkific-wp'),
            array($this, 'render_welcome_email_field'),
            self::PAGE_SLUG,
            'thinkific_woo_settings'
        );
        
        register_setting('thinkific_settings', 'thinkific_wp_send_account_welcome_email', array(
            'type' => 'boolean',
            'sanitize_callback' => 'rest_sanitize_boolean',
            'default' => false
        ));
        
        add_settings_field(
            'thinkific_wp_send_account_welcome_email',
            __('Send Account Welcome Email', 'thinkific-wp'),
            array($this, 'render_account_welcome_email_field'),
            self::PAGE_SLUG,
            'thinkific_woo_settings'
        );
        
        // Logging Settings
        register_setting('thinkific_settings', 'thinkific_wp_enable_logging', array(
            'type' => 'boolean',
            'sanitize_callback' => 'rest_sanitize_boolean',
            'default' => true
        ));
        
        add_settings_section(
            'thinkific_logging_settings',
            __('Logging Settings', 'thinkific-wp'),
            array($this, 'render_logging_section'),
            self::PAGE_SLUG
        );
        
        add_settings_field(
            'thinkific_wp_enable_logging',
            __('Enable Logging', 'thinkific-wp'),
            array($this, 'render_logging_field'),
            self::PAGE_SLUG,
            'thinkific_logging_settings'
        );
        
    }
    
    /**
     * Render sections
     */
    public function render_api_section() {
        echo '<p>' . esc_html__('Configure your Thinkific API credentials. You can find these in your Thinkific admin under Settings > API & Webhooks.', 'thinkific-wp') . '</p>';
        echo '<p><strong>' . esc_html__('Note:', 'thinkific-wp') . '</strong> ' . esc_html__('Growth plan does not support native SSO. This plugin provides a seamless experience without true SSO.', 'thinkific-wp') . '</p>';
    }
    
    public function render_cache_section() {
        echo '<p>' . esc_html__('Configure caching to reduce API calls and improve performance.', 'thinkific-wp') . '</p>';
    }
    
    public function render_woo_section() {
        echo '<p>' . esc_html__('Configure how the plugin integrates with WooCommerce orders.', 'thinkific-wp') . '</p>';
    }
    
    public function render_logging_section() {
        echo '<p>' . esc_html__('Enable logging to troubleshoot issues with API calls and enrollments.', 'thinkific-wp') . '</p>';
    }
    
    /**
     * Render fields
     */
    public function render_subdomain_field() {
        $value = get_option('thinkific_wp_subdomain', '');
        $api_key = get_option('thinkific_wp_api_key', '');
        $is_jwt = substr_count($api_key, '.') === 2;
        
        ?>
        <input type="text" 
               name="thinkific_wp_subdomain" 
               value="<?php echo esc_attr($value); ?>" 
               class="regular-text"
               placeholder="yourschool"
               required />
        
        <?php if ($is_jwt) : ?>
            <p class="description" style="color: #d63638;">
                <strong>⚠️ <?php esc_html_e('Important:', 'thinkific-wp'); ?></strong>
                <?php esc_html_e('Subdomain is REQUIRED when using Private API Key.', 'thinkific-wp'); ?><br>
                <?php esc_html_e('Enter your subdomain from yourschool.thinkific.com (just the "yourschool" part)', 'thinkific-wp'); ?>
            </p>
        <?php else : ?>
            <p class="description">
                <?php esc_html_e('Your Thinkific subdomain (e.g., "yourschool" from yourschool.thinkific.com)', 'thinkific-wp'); ?><br>
                <strong><?php esc_html_e('Required:', 'thinkific-wp'); ?></strong> 
                <?php esc_html_e('This must be entered when using Private API Key.', 'thinkific-wp'); ?>
            </p>
        <?php endif; ?>
        <?php
    }
    
    public function render_api_key_field() {
        $value = get_option('thinkific_wp_api_key', '');
        
        // Detect if it's a Bearer token (JWT)
        $is_bearer = substr_count($value, '.') === 2;
        
        ?>
        <textarea name="thinkific_wp_api_key" 
                  class="large-text code" 
                  rows="3"
                  autocomplete="off"
                  placeholder="eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9..."><?php echo esc_textarea($value); ?></textarea>
        
        <?php if ($is_bearer) : ?>
            <div class="notice notice-error inline" style="margin: 10px 0; border-left-color: #d63638;">
                <p>
                    <strong>❌ <?php esc_html_e('WRONG TOKEN TYPE - JWT/SSO Token Detected', 'thinkific-wp'); ?></strong><br>
                    <span style="color: #d63638; font-weight: bold;">
                        <?php esc_html_e('This is an "API Access Token" (JWT) which is for SSO ONLY, not for Admin API calls!', 'thinkific-wp'); ?>
                    </span><br><br>
                    <strong><?php esc_html_e('Required:', 'thinkific-wp'); ?></strong> 
                    <?php esc_html_e('You need your "Private API Key" (long alphanumeric string) instead.', 'thinkific-wp'); ?><br><br>
                    <strong><?php esc_html_e('Where to find it:', 'thinkific-wp'); ?></strong><br>
                    1. <?php esc_html_e('Go to Thinkific Admin → Settings → Code & Analytics', 'thinkific-wp'); ?><br>
                    2. <?php esc_html_e('Scroll to "API Keys" section', 'thinkific-wp'); ?><br>
                    3. <?php esc_html_e('Copy the PRIVATE API KEY (NOT the API Access Token)', 'thinkific-wp'); ?><br><br>
                    <a href="<?php echo esc_url(plugin_dir_url(THINKIFIC_WP_PLUGIN_FILE) . '../HOW-TO-GET-API-KEY.md'); ?>" 
                       target="_blank" 
                       class="button button-primary">
                        <?php esc_html_e('View Detailed Guide →', 'thinkific-wp'); ?>
                    </a>
                </p>
            </div>
        <?php else : ?>
            <div class="notice notice-success inline" style="margin: 10px 0;">
                <p>
                    <strong>✅ <?php esc_html_e('Correct Format - Private API Key Detected', 'thinkific-wp'); ?></strong><br>
                    <?php esc_html_e('This is the correct token type for Admin API access.', 'thinkific-wp'); ?>
                </p>
            </div>
        <?php endif; ?>
        <p class="description">
            <?php esc_html_e('Found in Thinkific Admin > Settings > Code & Analytics > API & Webhooks', 'thinkific-wp'); ?>
        </p>
        <?php
    }
    
    public function render_course_cache_field() {
        $value = get_option('thinkific_wp_course_cache_duration', 86400);
        ?>
        <input type="number" 
               name="thinkific_wp_course_cache_duration" 
               value="<?php echo esc_attr($value); ?>" 
               class="small-text" 
               min="0" /> <?php esc_html_e('seconds', 'thinkific-wp'); ?>
        <p class="description">
            <?php esc_html_e('How long to cache course data (default: 86400 = 24 hours)', 'thinkific-wp'); ?>
        </p>
        <?php
    }
    
    public function render_enrollment_cache_field() {
        $value = get_option('thinkific_wp_enrollment_cache_duration', 600);
        ?>
        <input type="number" 
               name="thinkific_wp_enrollment_cache_duration" 
               value="<?php echo esc_attr($value); ?>" 
               class="small-text" 
               min="0" /> <?php esc_html_e('seconds', 'thinkific-wp'); ?>
        <p class="description">
            <?php esc_html_e('How long to cache enrollment data (default: 600 = 10 minutes)', 'thinkific-wp'); ?>
        </p>
        <?php
    }
    
    public function render_order_statuses_field() {
        $selected = get_option('thinkific_wp_order_statuses', array('processing', 'completed'));
        $statuses = wc_get_order_statuses();
        ?>
        <select name="thinkific_wp_order_statuses[]" multiple class="regular-text" style="height: 120px;">
            <?php foreach ($statuses as $status_key => $status_label) : 
                $status_key = str_replace('wc-', '', $status_key);
            ?>
                <option value="<?php echo esc_attr($status_key); ?>" 
                        <?php selected(in_array($status_key, $selected)); ?>>
                    <?php echo esc_html($status_label); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <p class="description">
            <?php esc_html_e('Order statuses that trigger Thinkific enrollment (hold Ctrl/Cmd to select multiple)', 'thinkific-wp'); ?>
        </p>
        <?php
    }
    
    public function render_force_single_field() {
        $value = get_option('thinkific_wp_force_single_quantity', false);
        ?>
        <label>
            <input type="checkbox" 
                   name="thinkific_wp_force_single_quantity" 
                   value="1" 
                   <?php checked($value, true); ?> />
            <?php esc_html_e('Force mapped products to sell as single quantity only', 'thinkific-wp'); ?>
        </label>
        <?php
    }
    
    public function render_skip_cart_field() {
        $value = get_option('thinkific_wp_skip_cart', false);
        ?>
        <label>
            <input type="checkbox" 
                   name="thinkific_wp_skip_cart" 
                   value="1" 
                   <?php checked($value, true); ?> />
            <?php esc_html_e('Skip cart and redirect directly to checkout for mapped products', 'thinkific-wp'); ?>
        </label>
        <?php
    }
    
    public function render_welcome_email_field() {
        $value = get_option('thinkific_wp_send_welcome_email', false);
        ?>
        <label>
            <input type="checkbox" 
                   name="thinkific_wp_send_welcome_email" 
                   value="1" 
                   <?php checked($value, true); ?> />
            <?php esc_html_e('Send WordPress welcome email after enrollment', 'thinkific-wp'); ?>
        </label>
        <p class="description">
            <?php esc_html_e('Thinkific may not send welcome emails for API enrollments. Enable this to send a custom welcome email from WordPress with course access link.', 'thinkific-wp'); ?>
        </p>
        <?php
    }
    
    public function render_account_welcome_email_field() {
        $value = get_option('thinkific_wp_send_account_welcome_email', false);
        ?>
        <label>
            <input type="checkbox" 
                   name="thinkific_wp_send_account_welcome_email" 
                   value="1" 
                   <?php checked($value, true); ?> />
            <?php esc_html_e('Send account/site welcome email when creating new Thinkific user', 'thinkific-wp'); ?>
        </label>
        <p class="description">
            <?php esc_html_e('Users created via API bypass Thinkific\'s normal "Send site welcome email". Enable this to send a WordPress welcome email with login link when a new Thinkific account is created.', 'thinkific-wp'); ?>
        </p>
        <?php
    }
    
    public function render_logging_field() {
        $value = get_option('thinkific_wp_enable_logging', true);
        ?>
        <label>
            <input type="checkbox" 
                   name="thinkific_wp_enable_logging" 
                   value="1" 
                   <?php checked($value, true); ?> />
            <?php esc_html_e('Enable logging for debugging and troubleshooting', 'thinkific-wp'); ?>
        </label>
        <?php
    }
    
    /**
     * Sanitize order statuses
     */
    public function sanitize_order_statuses($value) {
        if (!is_array($value)) {
            return array('processing', 'completed');
        }
        
        return array_map('sanitize_text_field', $value);
    }
}
