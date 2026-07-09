<?php
/**
 * Core Plugin Class
 *
 * @package Thinkific_WP
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Main plugin class
 */
class Thinkific_WP_Plugin {
    
    /**
     * Single instance
     */
    private static $instance = null;
    
    /**
     * Logger instance
     */
    public $logger;
    
    /**
     * Settings instance
     */
    public $settings;
    
    /**
     * Mappings instance
     */
    public $mappings;
    
    /**
     * Thinkific client instance
     */
    public $client;
    
    /**
     * Admin instance
     */
    public $admin;
    
    /**
     * Dashboard instance
     */
    public $dashboard;
    
    /**
     * WooCommerce integration instance
     */
    public $woocommerce;
    
    /**
     * Get singleton instance
     */
    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        $this->init_hooks();
        $this->init_classes();
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        add_action('init', array($this, 'load_textdomain'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
    }
    
    /**
     * Initialize classes
     */
    private function init_classes() {
        $this->logger = new Thinkific_WP_Logger();
        $this->settings = new Thinkific_WP_Settings();
        $this->mappings = new Thinkific_WP_Mappings();
        $this->client = new Thinkific_WP_Client();
        $this->dashboard = new Thinkific_WP_Dashboard();
        $this->woocommerce = new Thinkific_WP_WooCommerce();
        
        if (is_admin()) {
            $this->admin = new Thinkific_WP_Admin();
        }
    }
    
    /**
     * Load text domain for translations
     */
    public function load_textdomain() {
        load_plugin_textdomain(
            'thinkific-wp',
            false,
            dirname(THINKIFIC_WP_PLUGIN_BASENAME) . '/languages'
        );
    }
    
    /**
     * Enqueue frontend assets
     */
    public function enqueue_frontend_assets() {
        if (is_singular() || has_shortcode(get_post()->post_content ?? '', 'thinkific_dashboard')) {
            wp_enqueue_style(
                'thinkific-wp-dashboard',
                THINKIFIC_WP_PLUGIN_URL . 'assets/dashboard.css',
                array(),
                THINKIFIC_WP_VERSION
            );
        }
    }
    
    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets($hook) {
        // Only load on our admin pages
        if (strpos($hook, 'thinkific') === false) {
            return;
        }
        
        wp_enqueue_style(
            'thinkific-wp-admin',
            THINKIFIC_WP_PLUGIN_URL . 'assets/admin.css',
            array(),
            THINKIFIC_WP_VERSION
        );
        
        wp_enqueue_script(
            'thinkific-wp-admin',
            THINKIFIC_WP_PLUGIN_URL . 'assets/admin.js',
            array('jquery'),
            THINKIFIC_WP_VERSION,
            true
        );
        
        wp_localize_script('thinkific-wp-admin', 'thinkificWP', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('thinkific_wp_admin'),
            'strings' => array(
                'confirmDelete' => __('Are you sure you want to delete this mapping?', 'thinkific-wp'),
                'testing' => __('Testing connection...', 'thinkific-wp'),
                'success' => __('Success!', 'thinkific-wp'),
                'error' => __('Error', 'thinkific-wp'),
            )
        ));
    }
}
