<?php
/**
 * Student Dashboard Class
 *
 * @package Thinkific_WP
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Handles student dashboard shortcode
 */
class Thinkific_WP_Dashboard {
    
    /**
     * Mappings
     */
    private $mappings;
    
    /**
     * Client
     */
    private $client;
    
    /**
     * Logger
     */
    private $logger;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->mappings = new Thinkific_WP_Mappings();
        $this->client = new Thinkific_WP_Client();
        $this->logger = new Thinkific_WP_Logger();
        
        add_shortcode('thinkific_dashboard', array($this, 'render_dashboard'));
        
        // Handle redirect back from Thinkific
        add_action('template_redirect', array($this, 'handle_thinkific_return'));
    }
    
    /**
     * Render dashboard shortcode
     */
    public function render_dashboard($atts) {
        $atts = shortcode_atts(array(
            'title' => __('My Courses', 'thinkific-wp'),
            'show_description' => 'yes',
            'show_user_info' => 'yes',
            'show_orders' => 'yes',
        ), $atts);
        
        ob_start();
        
        if (!is_user_logged_in()) {
            $this->render_login_prompt($atts);
        } else {
            $this->render_full_dashboard($atts);
        }
        
        return ob_get_clean();
    }
    
    /**
     * Render login prompt
     */
    private function render_login_prompt($atts) {
        ?>
        <div class="thinkific-dashboard-wrapper">
            <div class="thinkific-login-container">
                <div class="thinkific-login-card">
                    <div class="thinkific-login-header">
                        <div class="thinkific-login-icon">
                            <span class="dashicons dashicons-welcome-learn-more"></span>
                        </div>
                        <h2><?php esc_html_e('Welcome Back!', 'thinkific-wp'); ?></h2>
                        <p><?php esc_html_e('Sign in to access your courses and dashboard', 'thinkific-wp'); ?></p>
                    </div>
                    
                    <div class="thinkific-login-body">
                        <a href="<?php echo esc_url(wp_login_url(get_permalink())); ?>" class="thinkific-login-btn thinkific-login-email">
                            <span class="dashicons dashicons-admin-users"></span>
                            <span class="thinkific-login-btn-text"><?php esc_html_e('Log In', 'thinkific-wp'); ?></span>
                        </a>
                        
                        <p class="thinkific-register-text">
                            <?php esc_html_e('Don\'t have an account?', 'thinkific-wp'); ?>
                            <a href="<?php echo esc_url(wp_registration_url()); ?>" class="thinkific-register-link">
                                <?php esc_html_e('Sign up', 'thinkific-wp'); ?>
                            </a>
                        </p>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render full dashboard with tabs
     */
    private function render_full_dashboard($atts) {
        $user_id = get_current_user_id();
        $user = wp_get_current_user();
        $courses = $this->get_user_courses($user_id, $user->user_email);
        $active_tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'overview';
        
        ?>
        <div class="thinkific-dashboard-wrapper">
            <!-- Dashboard Header -->
            <div class="thinkific-dashboard-header">
                <div class="thinkific-dashboard-header-content">
                    <div class="thinkific-dashboard-user-welcome">
                        <h1><?php printf(esc_html__('Welcome back, %s!', 'thinkific-wp'), esc_html($user->display_name)); ?></h1>
                        <p class="thinkific-dashboard-subtitle"><?php esc_html_e('Continue your learning journey', 'thinkific-wp'); ?></p>
                    </div>
                    <div class="thinkific-dashboard-user-avatar">
                        <?php echo get_avatar($user->ID, 64, '', '', array('class' => 'thinkific-avatar')); ?>
                    </div>
                </div>
            </div>
            
            <!-- Dashboard Navigation Tabs -->
            <div class="thinkific-dashboard-nav">
                <nav class="thinkific-tabs-nav">
                    <a href="<?php echo esc_url(add_query_arg('tab', 'overview', get_permalink())); ?>" 
                       class="thinkific-tab-link <?php echo $active_tab === 'overview' ? 'active' : ''; ?>">
                        <span class="dashicons dashicons-dashboard"></span>
                        <span class="thinkific-tab-text"><?php esc_html_e('Overview', 'thinkific-wp'); ?></span>
                    </a>
                    <a href="<?php echo esc_url(add_query_arg('tab', 'courses', get_permalink())); ?>" 
                       class="thinkific-tab-link <?php echo $active_tab === 'courses' ? 'active' : ''; ?>">
                        <span class="dashicons dashicons-book"></span>
                        <span class="thinkific-tab-text"><?php esc_html_e('My Courses', 'thinkific-wp'); ?></span>
                        <span class="thinkific-tab-badge"><?php echo count($courses); ?></span>
                    </a>
                    <?php if ($atts['show_orders'] === 'yes') : ?>
                        <a href="<?php echo esc_url(add_query_arg('tab', 'orders', get_permalink())); ?>" 
                           class="thinkific-tab-link <?php echo $active_tab === 'orders' ? 'active' : ''; ?>">
                            <span class="dashicons dashicons-cart"></span>
                            <span class="thinkific-tab-text"><?php esc_html_e('Orders', 'thinkific-wp'); ?></span>
                        </a>
                    <?php endif; ?>
                </nav>
            </div>
            
            <!-- Dashboard Content Area -->
            <div class="thinkific-dashboard-content">
                <?php
                switch ($active_tab) {
                    case 'overview':
                        $this->render_overview_tab($user, $user_id, $courses, $atts);
                        break;
                    case 'courses':
                        $this->render_courses_tab($courses, $user_id, $atts);
                        break;
                    case 'orders':
                        $this->render_orders_tab($user_id);
                        break;
                    default:
                        $this->render_overview_tab($user, $user_id, $courses, $atts);
                }
                ?>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render Overview Tab
     */
    private function render_overview_tab($user, $user_id, $courses, $atts) {
        $orders = wc_get_orders(array('customer_id' => $user_id, 'limit' => -1));
        $completed_courses = 0;
        $in_progress_courses = count($courses);
        
        ?>
        <div class="thinkific-tab-content thinkific-overview-tab">
            <!-- Quick Stats -->
            <div class="thinkific-stats-cards">
                <div class="thinkific-stat-card">
                    <div class="thinkific-stat-card-icon thinkific-stat-icon-courses">
                        <span class="dashicons dashicons-book-alt"></span>
                    </div>
                    <div class="thinkific-stat-card-content">
                        <div class="thinkific-stat-card-value"><?php echo esc_html($in_progress_courses); ?></div>
                        <div class="thinkific-stat-card-label"><?php esc_html_e('Enrolled Courses', 'thinkific-wp'); ?></div>
                    </div>
                </div>
                
                <div class="thinkific-stat-card">
                    <div class="thinkific-stat-card-icon thinkific-stat-icon-completed">
                        <span class="dashicons dashicons-yes-alt"></span>
                    </div>
                    <div class="thinkific-stat-card-content">
                        <div class="thinkific-stat-card-value"><?php echo esc_html($completed_courses); ?></div>
                        <div class="thinkific-stat-card-label"><?php esc_html_e('Completed', 'thinkific-wp'); ?></div>
                    </div>
                </div>
                
                <div class="thinkific-stat-card">
                    <div class="thinkific-stat-card-icon thinkific-stat-icon-orders">
                        <span class="dashicons dashicons-cart"></span>
                    </div>
                    <div class="thinkific-stat-card-content">
                        <div class="thinkific-stat-card-value"><?php echo count($orders); ?></div>
                        <div class="thinkific-stat-card-label"><?php esc_html_e('Total Orders', 'thinkific-wp'); ?></div>
                    </div>
                </div>
                
                <div class="thinkific-stat-card">
                    <div class="thinkific-stat-card-icon thinkific-stat-icon-thinkific">
                        <span class="dashicons dashicons-admin-links"></span>
                    </div>
                    <div class="thinkific-stat-card-content">
                        <?php $thinkific_logged_in = get_user_meta($user_id, '_thinkific_has_logged_in', true); ?>
                        <div class="thinkific-stat-card-value">
                            <?php if ($thinkific_logged_in) : ?>
                                <span class="dashicons dashicons-yes" style="color: #46b450;"></span>
                            <?php else : ?>
                                <span class="dashicons dashicons-clock" style="color: #ffb900;"></span>
                            <?php endif; ?>
                        </div>
                        <div class="thinkific-stat-card-label"><?php esc_html_e('Thinkific Status', 'thinkific-wp'); ?></div>
                    </div>
                </div>
            </div>
            
            <!-- Recent Activity -->
            <div class="thinkific-dashboard-row">
                <!-- Continue Learning -->
                <div class="thinkific-dashboard-col thinkific-col-8">
                    <div class="thinkific-card">
                        <div class="thinkific-card-header-simple">
                            <h3><?php esc_html_e('Continue Learning', 'thinkific-wp'); ?></h3>
                            <a href="<?php echo esc_url(add_query_arg('tab', 'courses', get_permalink())); ?>" class="thinkific-link-view-all">
                                <?php esc_html_e('View All', 'thinkific-wp'); ?> →
                            </a>
                        </div>
                        <div class="thinkific-card-body">
                            <?php if (empty($courses)) : ?>
                                <div class="thinkific-empty-state">
                                    <span class="dashicons dashicons-book"></span>
                                    <p><?php esc_html_e('No courses yet. Start your learning journey today!', 'thinkific-wp'); ?></p>
                                    <a href="<?php echo esc_url(get_permalink(wc_get_page_id('shop'))); ?>" class="button button-primary">
                                        <?php esc_html_e('Browse Courses', 'thinkific-wp'); ?>
                                    </a>
                                </div>
                            <?php else : ?>
                                <div class="thinkific-recent-courses">
                                    <?php foreach (array_slice($courses, 0, 3) as $course) : ?>
                                        <?php $this->render_course_card_compact($course, $user_id); ?>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Account Info -->
                <div class="thinkific-dashboard-col thinkific-col-4">
                    <div class="thinkific-card">
                        <div class="thinkific-card-header-simple">
                            <h3><?php esc_html_e('Account Info', 'thinkific-wp'); ?></h3>
                        </div>
                        <div class="thinkific-card-body">
                            <div class="thinkific-account-info">
                                <div class="thinkific-account-info-item">
                                    <span class="thinkific-account-info-label"><?php esc_html_e('Email:', 'thinkific-wp'); ?></span>
                                    <span class="thinkific-account-info-value"><?php echo esc_html($user->user_email); ?></span>
                                </div>
                                <div class="thinkific-account-info-item">
                                    <span class="thinkific-account-info-label"><?php esc_html_e('Member Since:', 'thinkific-wp'); ?></span>
                                    <span class="thinkific-account-info-value">
                                        <?php echo esc_html(date_i18n(get_option('date_format'), strtotime($user->user_registered))); ?>
                                    </span>
                                </div>
                                <div class="thinkific-account-info-item">
                                    <span class="thinkific-account-info-label"><?php esc_html_e('Username:', 'thinkific-wp'); ?></span>
                                    <span class="thinkific-account-info-value"><?php echo esc_html($user->user_login); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render Courses Tab
     */
    private function render_courses_tab($courses, $user_id, $atts) {
        ?>
        <div class="thinkific-tab-content thinkific-courses-tab">
            <div class="thinkific-tab-header">
                <h2><?php esc_html_e('My Courses', 'thinkific-wp'); ?></h2>
                <p class="thinkific-tab-description"><?php esc_html_e('Access all your enrolled courses', 'thinkific-wp'); ?></p>
            </div>
            
            <?php if (empty($courses)) : ?>
                <div class="thinkific-empty-state-large">
                    <div class="thinkific-empty-state-icon">
                        <span class="dashicons dashicons-book"></span>
                    </div>
                    <h3><?php esc_html_e('No Courses Yet', 'thinkific-wp'); ?></h3>
                    <p><?php esc_html_e('You haven\'t enrolled in any courses yet. Browse our course catalog to get started!', 'thinkific-wp'); ?></p>
                    <a href="<?php echo esc_url(get_permalink(wc_get_page_id('shop'))); ?>" class="button button-primary button-large">
                        <span class="dashicons dashicons-cart"></span>
                        <?php esc_html_e('Browse Courses', 'thinkific-wp'); ?>
                    </a>
                </div>
            <?php else : ?>
                <div class="thinkific-courses-grid-modern">
                    <?php foreach ($courses as $course) : ?>
                        <?php $this->render_course_card_modern($course, $atts, $user_id); ?>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }
    
    /**
     * Render Orders Tab
     */
    private function render_orders_tab($user_id) {
        $orders = wc_get_orders(array(
            'customer_id' => $user_id,
            'limit' => -1,
            'orderby' => 'date',
            'order' => 'DESC',
        ));
        
        ?>
        <div class="thinkific-tab-content thinkific-orders-tab">
            <div class="thinkific-tab-header">
                <h2><?php esc_html_e('Order History', 'thinkific-wp'); ?></h2>
                <p class="thinkific-tab-description"><?php esc_html_e('View your purchase history and order details', 'thinkific-wp'); ?></p>
            </div>
            
            <?php if (empty($orders)) : ?>
                <div class="thinkific-empty-state-large">
                    <div class="thinkific-empty-state-icon">
                        <span class="dashicons dashicons-cart"></span>
                    </div>
                    <h3><?php esc_html_e('No Orders Yet', 'thinkific-wp'); ?></h3>
                    <p><?php esc_html_e('You haven\'t placed any orders yet.', 'thinkific-wp'); ?></p>
                    <a href="<?php echo esc_url(get_permalink(wc_get_page_id('shop'))); ?>" class="button button-primary button-large">
                        <?php esc_html_e('Start Shopping', 'thinkific-wp'); ?>
                    </a>
                </div>
            <?php else : ?>
                <div class="thinkific-orders-list">
                    <?php foreach ($orders as $order) : ?>
                        <div class="thinkific-order-card">
                            <div class="thinkific-order-header">
                                <div class="thinkific-order-number">
                                    <strong><?php esc_html_e('Order', 'thinkific-wp'); ?> #<?php echo esc_html($order->get_order_number()); ?></strong>
                                    <span class="thinkific-order-date">
                                        <?php echo esc_html($order->get_date_created()->date_i18n(get_option('date_format'))); ?>
                                    </span>
                                </div>
                                <div class="thinkific-order-status-badge thinkific-status-<?php echo esc_attr($order->get_status()); ?>">
                                    <?php echo esc_html(wc_get_order_status_name($order->get_status())); ?>
                                </div>
                            </div>
                            <div class="thinkific-order-body">
                                <div class="thinkific-order-items">
                                    <?php foreach ($order->get_items() as $item) : ?>
                                        <div class="thinkific-order-item">
                                            <span class="dashicons dashicons-book-alt"></span>
                                            <span><?php echo esc_html($item->get_name()); ?></span>
                                            <span class="thinkific-order-item-qty">× <?php echo esc_html($item->get_quantity()); ?></span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <div class="thinkific-order-footer">
                                    <div class="thinkific-order-total">
                                        <strong><?php esc_html_e('Total:', 'thinkific-wp'); ?></strong>
                                        <?php echo wp_kses_post($order->get_formatted_order_total()); ?>
                                    </div>
                                    <a href="<?php echo esc_url($order->get_view_order_url()); ?>" class="button button-small">
                                        <?php esc_html_e('View Details', 'thinkific-wp'); ?>
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }
    
    /**
     * Render compact course card for overview
     */
    private function render_course_card_compact($course, $user_id) {
        ?>
        <div class="thinkific-course-compact">
            <div class="thinkific-course-compact-info">
                <h4><?php echo esc_html($course['course_name']); ?></h4>
                <?php if (!empty($course['course_description'])) : ?>
                    <p><?php echo esc_html(wp_trim_words($course['course_description'], 15)); ?></p>
                <?php endif; ?>
            </div>
            <a href="<?php echo esc_url($this->get_course_launch_url($course['course_url'], $user_id)); ?>" 
               class="button button-primary button-small">
                <?php esc_html_e('Continue', 'thinkific-wp'); ?> →
            </a>
        </div>
        <?php
    }
    
    /**
     * Render modern course card
     */
    private function render_course_card_modern($course, $atts, $user_id) {
        $show_description = $atts['show_description'] === 'yes';
        $has_launched = get_user_meta($user_id, '_thinkific_launched_' . md5($course['course_url']), true);
        
        // Check enrollment status
        global $wpdb;
        $enrollments_table = Thinkific_WP_DB::get_table_name(Thinkific_WP_DB::TABLE_ENROLLMENTS);
        $enrollment_status = $wpdb->get_row($wpdb->prepare(
            "SELECT status, enrolled_at FROM $enrollments_table WHERE user_id = %d AND course_id = %s ORDER BY updated_at DESC LIMIT 1",
            $user_id,
            $course['course_id']
        ));
        
        $is_enrolled = ($enrollment_status && $enrollment_status->status === 'enrolled');
        
        ?>
        <div class="thinkific-course-card-modern <?php echo $is_enrolled ? 'enrolled' : 'pending'; ?>">
            <div class="thinkific-course-card-status">
                <?php if ($is_enrolled) : ?>
                    <span class="thinkific-status-badge-modern success">
                        <span class="dashicons dashicons-yes-alt"></span>
                        <?php esc_html_e('Enrolled', 'thinkific-wp'); ?>
                    </span>
                <?php else : ?>
                    <span class="thinkific-status-badge-modern pending">
                        <span class="dashicons dashicons-clock"></span>
                        <?php esc_html_e('Pending', 'thinkific-wp'); ?>
                    </span>
                <?php endif; ?>
            </div>
            
            <div class="thinkific-course-card-content">
                <h3 class="thinkific-course-card-title"><?php echo esc_html($course['course_name']); ?></h3>
                
                <?php if ($show_description && !empty($course['course_description'])) : ?>
                    <p class="thinkific-course-card-description"><?php echo esc_html($course['course_description']); ?></p>
                <?php endif; ?>
                
                <?php if ($enrollment_status && $enrollment_status->enrolled_at) : ?>
                    <div class="thinkific-course-card-meta">
                        <span class="dashicons dashicons-calendar"></span>
                        <?php printf(
                            esc_html__('Enrolled: %s', 'thinkific-wp'),
                            date_i18n(get_option('date_format'), strtotime($enrollment_status->enrolled_at))
                        ); ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="thinkific-course-card-actions">
                <a href="<?php echo esc_url($this->get_course_launch_url($course['course_url'], $user_id)); ?>" 
                   class="button button-primary thinkific-course-btn-launch">
                    <?php esc_html_e('Continue Learning', 'thinkific-wp'); ?>
                    <span class="dashicons dashicons-arrow-right-alt2"></span>
                </a>
                
                <?php if (!$has_launched && !$is_enrolled) : ?>
                    <p class="thinkific-course-card-help">
                        <span class="dashicons dashicons-info"></span>
                        <?php esc_html_e('Use your checkout email to login', 'thinkific-wp'); ?>
                    </p>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render user info card
     */
    private function render_user_info_card($user) {
        $thinkific_logged_in = get_user_meta($user->ID, '_thinkific_has_logged_in', true);
        $member_since = get_userdata($user->ID)->user_registered;
        
        ?>
        <div class="thinkific-info-card thinkific-user-card">
            <div class="thinkific-card-header">
                <span class="dashicons dashicons-admin-users"></span>
                <h3><?php esc_html_e('User Information', 'thinkific-wp'); ?></h3>
            </div>
            <div class="thinkific-card-body">
                <div class="thinkific-info-row">
                    <span class="thinkific-info-label"><?php esc_html_e('Name:', 'thinkific-wp'); ?></span>
                    <span class="thinkific-info-value"><?php echo esc_html($user->display_name); ?></span>
                </div>
                <div class="thinkific-info-row">
                    <span class="thinkific-info-label"><?php esc_html_e('Email:', 'thinkific-wp'); ?></span>
                    <span class="thinkific-info-value"><?php echo esc_html($user->user_email); ?></span>
                </div>
                <div class="thinkific-info-row">
                    <span class="thinkific-info-label"><?php esc_html_e('Member Since:', 'thinkific-wp'); ?></span>
                    <span class="thinkific-info-value"><?php echo esc_html(date_i18n(get_option('date_format'), strtotime($member_since))); ?></span>
                </div>
                <div class="thinkific-info-row">
                    <span class="thinkific-info-label"><?php esc_html_e('Thinkific Status:', 'thinkific-wp'); ?></span>
                    <span class="thinkific-info-value">
                        <?php if ($thinkific_logged_in) : ?>
                            <span class="thinkific-status-badge thinkific-status-active">
                                <span class="dashicons dashicons-yes"></span>
                                <?php esc_html_e('Connected', 'thinkific-wp'); ?>
                            </span>
                        <?php else : ?>
                            <span class="thinkific-status-badge thinkific-status-pending">
                                <span class="dashicons dashicons-clock"></span>
                                <?php esc_html_e('Not Yet Connected', 'thinkific-wp'); ?>
                            </span>
                        <?php endif; ?>
                    </span>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render order status card
     */
    private function render_order_status_card($user_id) {
        $orders = wc_get_orders(array(
            'customer_id' => $user_id,
            'limit' => 5,
            'orderby' => 'date',
            'order' => 'DESC',
        ));
        
        $total_spent = 0;
        $active_courses = 0;
        
        foreach ($orders as $order) {
            if (in_array($order->get_status(), array('processing', 'completed'))) {
                $total_spent += $order->get_total();
                
                $mappings = $this->mappings->get_mappings_for_order($order->get_id());
                $active_courses += count($mappings);
            }
        }
        
        ?>
        <div class="thinkific-info-card thinkific-orders-card">
            <div class="thinkific-card-header">
                <span class="dashicons dashicons-cart"></span>
                <h3><?php esc_html_e('Order Summary', 'thinkific-wp'); ?></h3>
            </div>
            <div class="thinkific-card-body">
                <div class="thinkific-stats-grid">
                    <div class="thinkific-stat-item">
                        <div class="thinkific-stat-value"><?php echo count($orders); ?></div>
                        <div class="thinkific-stat-label"><?php esc_html_e('Total Orders', 'thinkific-wp'); ?></div>
                    </div>
                    <div class="thinkific-stat-item">
                        <div class="thinkific-stat-value"><?php echo wc_price($total_spent); ?></div>
                        <div class="thinkific-stat-label"><?php esc_html_e('Total Spent', 'thinkific-wp'); ?></div>
                    </div>
                    <div class="thinkific-stat-item">
                        <div class="thinkific-stat-value"><?php echo esc_html($active_courses); ?></div>
                        <div class="thinkific-stat-label"><?php esc_html_e('Enrolled Courses', 'thinkific-wp'); ?></div>
                    </div>
                </div>
                
                <?php if (!empty($orders)) : ?>
                    <div class="thinkific-recent-orders">
                        <h4><?php esc_html_e('Recent Orders', 'thinkific-wp'); ?></h4>
                        <table class="thinkific-orders-table">
                            <thead>
                                <tr>
                                    <th><?php esc_html_e('Order', 'thinkific-wp'); ?></th>
                                    <th><?php esc_html_e('Date', 'thinkific-wp'); ?></th>
                                    <th><?php esc_html_e('Status', 'thinkific-wp'); ?></th>
                                    <th><?php esc_html_e('Total', 'thinkific-wp'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach (array_slice($orders, 0, 3) as $order) : ?>
                                    <tr>
                                        <td>
                                            <a href="<?php echo esc_url($order->get_view_order_url()); ?>">
                                                #<?php echo esc_html($order->get_order_number()); ?>
                                            </a>
                                        </td>
                                        <td><?php echo esc_html($order->get_date_created()->date_i18n(get_option('date_format'))); ?></td>
                                        <td>
                                            <span class="thinkific-order-status thinkific-order-status-<?php echo esc_attr($order->get_status()); ?>">
                                                <?php echo esc_html(wc_get_order_status_name($order->get_status())); ?>
                                            </span>
                                        </td>
                                        <td><?php echo wp_kses_post($order->get_formatted_order_total()); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <p class="thinkific-view-all">
                            <a href="<?php echo esc_url(wc_get_account_endpoint_url('orders')); ?>">
                                <?php esc_html_e('View All Orders →', 'thinkific-wp'); ?>
                            </a>
                        </p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render course card
     */
    private function render_course_card($course, $atts, $user_id) {
        $show_description = $atts['show_description'] === 'yes';
        $has_launched = get_user_meta($user_id, '_thinkific_launched_' . md5($course['course_url']), true);
        
        // Check enrollment status from database
        global $wpdb;
        $enrollments_table = Thinkific_WP_DB::get_table_name(Thinkific_WP_DB::TABLE_ENROLLMENTS);
        $enrollment_status = $wpdb->get_row($wpdb->prepare(
            "SELECT status, enrolled_at FROM $enrollments_table WHERE user_id = %d AND course_id = %s ORDER BY updated_at DESC LIMIT 1",
            $user_id,
            $course['course_id']
        ));
        
        $is_enrolled = ($enrollment_status && $enrollment_status->status === 'enrolled');
        $enrollment_date = $enrollment_status ? $enrollment_status->enrolled_at : null;
        
        ?>
        <div class="thinkific-course-card <?php echo $is_enrolled ? 'thinkific-course-enrolled' : 'thinkific-course-pending'; ?>">
            <div class="thinkific-course-card-header">
                <h3 class="thinkific-course-title"><?php echo esc_html($course['course_name']); ?></h3>
                
                <!-- Enrollment Status Badge -->
                <div class="thinkific-enrollment-badge">
                    <?php if ($is_enrolled) : ?>
                        <span class="thinkific-badge thinkific-badge-success">
                            <span class="dashicons dashicons-yes-alt"></span>
                            <?php esc_html_e('Enrolled', 'thinkific-wp'); ?>
                        </span>
                    <?php elseif ($course['enrolled_via_api']) : ?>
                        <span class="thinkific-badge thinkific-badge-info">
                            <span class="dashicons dashicons-update"></span>
                            <?php esc_html_e('Verified', 'thinkific-wp'); ?>
                        </span>
                    <?php else : ?>
                        <span class="thinkific-badge thinkific-badge-warning">
                            <span class="dashicons dashicons-clock"></span>
                            <?php esc_html_e('Pending', 'thinkific-wp'); ?>
                        </span>
                    <?php endif; ?>
                </div>
            </div>
            
            <?php if ($show_description && !empty($course['course_description'])) : ?>
                <div class="thinkific-course-description">
                    <?php echo wp_kses_post($course['course_description']); ?>
                </div>
            <?php endif; ?>
            
            <!-- Course Meta Info -->
            <div class="thinkific-course-meta-info">
                <?php if ($enrollment_date) : ?>
                    <div class="thinkific-meta-item">
                        <span class="dashicons dashicons-calendar-alt"></span>
                        <span><?php printf(esc_html__('Enrolled: %s', 'thinkific-wp'), date_i18n(get_option('date_format'), strtotime($enrollment_date))); ?></span>
                    </div>
                <?php endif; ?>
                
                <?php if ($course['source']) : ?>
                    <div class="thinkific-meta-item">
                        <span class="dashicons dashicons-info"></span>
                        <span><?php esc_html_e('Source:', 'thinkific-wp'); ?> <?php echo esc_html(ucfirst($course['source'])); ?></span>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="thinkific-course-actions">
                <a href="<?php echo esc_url($this->get_course_launch_url($course['course_url'], $user_id)); ?>" 
                   class="button button-primary thinkific-launch-button"
                   target="_self">
                    <span class="dashicons dashicons-arrow-right-alt"></span>
                    <?php esc_html_e('Continue Course', 'thinkific-wp'); ?>
                </a>
                
                <?php if (!$has_launched && !$is_enrolled) : ?>
                    <div class="thinkific-helper-text">
                        <span class="dashicons dashicons-info"></span>
                        <span><?php esc_html_e('First time? Use the same email you used at checkout to login to Thinkific.', 'thinkific-wp'); ?></span>
                    </div>
                <?php endif; ?>
                
                <?php if ($is_enrolled) : ?>
                    <div class="thinkific-success-text">
                        <span class="dashicons dashicons-yes"></span>
                        <span><?php esc_html_e('You\'re enrolled! Click to start learning.', 'thinkific-wp'); ?></span>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }
    
    /**
     * Get user courses
     */
    private function get_user_courses($user_id, $user_email) {
        $courses = array();
        $course_ids = array();
        
        // 1. Get courses from WooCommerce purchases (primary source)
        $orders = wc_get_orders(array(
            'customer_id' => $user_id,
            'status' => array('processing', 'completed'),
            'limit' => -1,
        ));
        
        foreach ($orders as $order) {
            $mappings = $this->mappings->get_mappings_for_order($order->get_id());
            
            foreach ($mappings as $mapping) {
                if (!in_array($mapping['course_id'], $course_ids)) {
                    $courses[] = array(
                        'course_id' => $mapping['course_id'],
                        'course_name' => $mapping['course_name'],
                        'course_url' => $mapping['course_url'],
                        'course_description' => $mapping['course_description'],
                        'source' => 'woocommerce',
                        'enrolled_via_api' => false,
                    );
                    $course_ids[] = $mapping['course_id'];
                }
            }
        }
        
        // 2. Optionally verify against Thinkific API
        if ($this->client->is_configured()) {
            try {
                $api_enrollments = $this->client->get_user_enrollments($user_email);
                
                if (!is_wp_error($api_enrollments) && !empty($api_enrollments)) {
                    foreach ($api_enrollments as $enrollment) {
                        $course_id_str = (string) $enrollment['course_id'];
                        
                        // Find in our mappings
                        $mapping = $this->mappings->get_mapping_by_course_id($course_id_str);
                        
                        if ($mapping) {
                            // Check if already in our list
                            $found = false;
                            foreach ($courses as &$course) {
                                if ($course['course_id'] === $course_id_str) {
                                    $course['enrolled_via_api'] = true;
                                    $found = true;
                                    break;
                                }
                            }
                            
                            // Add if not found
                            if (!$found) {
                                $courses[] = array(
                                    'course_id' => $mapping['course_id'],
                                    'course_name' => $mapping['course_name'],
                                    'course_url' => $mapping['course_url'],
                                    'course_description' => $mapping['course_description'],
                                    'source' => 'thinkific_api',
                                    'enrolled_via_api' => true,
                                );
                            }
                        }
                    }
                }
            } catch (Exception $e) {
                $this->logger->warning('Failed to fetch API enrollments for dashboard', array(
                    'user_id' => $user_id,
                    'error' => $e->getMessage()
                ));
            }
        }
        
        return $courses;
    }
    
    /**
     * Get course launch URL with tracking
     */
    private function get_course_launch_url($course_url, $user_id) {
        $return_url = get_permalink();
        
        // Add return URL parameter for tracking
        $launch_url = add_query_arg(array(
            'thinkific_return' => '1',
            'wp_user' => $user_id,
            'return_to' => urlencode($return_url),
        ), $course_url);
        
        return $launch_url;
    }
    
    /**
     * Handle return from Thinkific
     */
    public function handle_thinkific_return() {
        if (!isset($_GET['thinkific_return']) || !is_user_logged_in()) {
            return;
        }
        
        $user_id = get_current_user_id();
        $wp_user_param = isset($_GET['wp_user']) ? absint($_GET['wp_user']) : 0;
        
        // Verify user matches
        if ($wp_user_param !== $user_id) {
            return;
        }
        
        // Mark that user has launched Thinkific
        $referer = wp_get_referer();
        if ($referer && strpos($referer, 'thinkific.com') !== false) {
            update_user_meta($user_id, '_thinkific_has_logged_in', time());
            
            // Try to determine which course
            if (isset($_GET['return_to'])) {
                $course_url_hash = md5($referer);
                update_user_meta($user_id, '_thinkific_launched_' . $course_url_hash, time());
            }
        }
        
        // Redirect to clean URL
        if (isset($_GET['return_to'])) {
            wp_safe_redirect(urldecode($_GET['return_to']));
            exit;
        }
    }
    
}
