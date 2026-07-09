<?php
/**
 * Thinkific API Client
 *
 * @package Thinkific_WP
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Handles all communication with Thinkific API
 */
class Thinkific_WP_Client {
    
    /**
     * API base URL
     */
    private $api_base_url;
    
    /**
     * API key
     */
    private $api_key;
    
    /**
     * Subdomain
     */
    private $subdomain;
    
    /**
     * Logger
     */
    private $logger;
    
    /**
     * Rate limit lock option name
     */
    const RATE_LIMIT_LOCK = 'thinkific_wp_rate_limit_lock';
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->api_base_url = get_option('thinkific_wp_api_base_url', 'https://api.thinkific.com/api/public/v1');
        $this->api_key = trim(get_option('thinkific_wp_api_key', ''));
        $this->subdomain = trim(get_option('thinkific_wp_subdomain', ''));
        
        $this->logger = new Thinkific_WP_Logger();
        
        // Warn if JWT is detected (wrong token type for Admin API)
        if ($this->is_bearer_token($this->api_key)) {
            $this->logger->error('JWT token detected in API Key field', array(
                'message' => 'You are using an API Access Token (JWT) which is for SSO only.',
                'required' => 'Please use your Private API Key instead.',
                'where_to_find' => 'Thinkific Admin > Settings > Code & Analytics > API Keys section',
                'guide' => 'See HOW-TO-GET-API-KEY.md for detailed instructions'
            ));
        }
    }
    
    /**
     * Detect if API key is a JWT Bearer token
     */
    private function is_bearer_token($api_key) {
        // JWT tokens have 3 parts separated by dots
        return substr_count($api_key, '.') === 2;
    }
    
    /**
     * Extract subdomain from JWT token
     */
    private function extract_subdomain_from_jwt($token) {
        try {
            $parts = explode('.', $token);
            if (count($parts) !== 3) {
                return null;
            }
            
            // Decode the payload (second part)
            $payload = json_decode(base64_decode(strtr($parts[1], '-_', '+/')), true);
            
            if (isset($payload['subdomain'])) {
                return $payload['subdomain'];
            }
        } catch (Exception $e) {
            $this->logger->warning('Failed to extract subdomain from JWT', array(
                'error' => $e->getMessage()
            ));
        }
        
        return null;
    }
    
    /**
     * Check if API is configured
     */
    public function is_configured() {
        return !empty($this->api_key) && !empty($this->subdomain);
    }
    
    /**
     * Check if rate limited
     */
    private function is_rate_limited() {
        return get_transient(self::RATE_LIMIT_LOCK) !== false;
    }
    
    /**
     * Set rate limit lock
     */
    private function set_rate_limit_lock() {
        set_transient(self::RATE_LIMIT_LOCK, true, 60); // 60 seconds
        $this->logger->warning('Rate limit hit, locking API requests for 60 seconds');
    }
    
    /**
     * Make API request
     */
    public function request($method, $endpoint, $args = array()) {
        if (!$this->is_configured()) {
            return new WP_Error('not_configured', __('Thinkific API is not configured', 'thinkific-wp'));
        }
        
        if ($this->is_rate_limited()) {
            return new WP_Error('rate_limited', __('Rate limit active, please wait', 'thinkific-wp'));
        }
        
        $url = trailingslashit($this->api_base_url) . ltrim($endpoint, '/');
        
        // Clean subdomain - remove .thinkific.com if present
        $clean_subdomain = str_replace('.thinkific.com', '', $this->subdomain);
        $clean_subdomain = trim($clean_subdomain);
        
        // Clean API key - remove any whitespace
        $clean_api_key = trim($this->api_key);
        
        // IMPORTANT: Always use Private API Key authentication for Admin API
        // JWT tokens are for SSO only, not for Admin API calls
        $is_jwt = $this->is_bearer_token($clean_api_key);
        
        if ($is_jwt) {
            // Log warning if JWT is being used (should use Private API Key instead)
            $this->logger->warning('JWT token detected - Admin API requires Private API Key', array(
                'message' => 'JWT tokens (API Access Tokens) are for SSO only. Please use your Private API Key from Thinkific Settings > Code & Analytics.',
                'token_format' => 'JWT (3 parts with dots)',
                'required_format' => 'Private API Key (long alphanumeric string)',
                'guide' => 'See HOW-TO-GET-API-KEY.md for instructions'
            ));
        }
        
        // Always use X-Auth headers for Admin API (Private API Key method)
        $headers = array(
            'X-Auth-API-Key' => $clean_api_key,
            'X-Auth-Subdomain' => $clean_subdomain,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        );
        
        $auth_method = $is_jwt ? 'JWT (Wrong - Should be Private API Key!)' : 'Private API Key (Correct)';
        
        $request_args = array(
            'method' => strtoupper($method),
            'headers' => $headers,
            'timeout' => 30,
            'sslverify' => true,
        );
        
        if (!empty($args)) {
            if (strtoupper($method) === 'GET') {
                $url = add_query_arg($args, $url);
            } else {
                $request_args['body'] = json_encode($args);
            }
        }
        
        // Log request with sanitized details
        $this->logger->debug("API Request: $method $url", array(
            'auth_method' => $auth_method,
            'subdomain' => $clean_subdomain,
            'token_first_10' => substr($clean_api_key, 0, 10) . '...',
            'token_last_10' => '...' . substr($clean_api_key, -10),
            'token_length' => strlen($clean_api_key),
            'endpoint' => $endpoint,
            'args' => $args
        ));
        
        $response = wp_remote_request($url, $request_args);
        
        if (is_wp_error($response)) {
            $this->logger->error('API request failed', array(
                'error' => $response->get_error_message(),
                'url' => $url
            ));
            return $response;
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        $response_headers = wp_remote_retrieve_headers($response);
        $data = json_decode($body, true);
        
        // Handle rate limiting
        if ($status_code === 429) {
            $this->set_rate_limit_lock();
            return new WP_Error('rate_limit', __('Thinkific API rate limit exceeded', 'thinkific-wp'));
        }
        
        // Handle errors with detailed logging
        if ($status_code >= 400) {
            $error_message = isset($data['error']) ? $data['error'] : (isset($data['errors']) ? json_encode($data['errors']) : $body);
            
            // Log detailed error for 401
            if ($status_code === 401) {
                $error_context = array(
                    'url' => $url,
                    'method' => $method,
                    'auth_method' => $auth_method,
                    'subdomain' => $clean_subdomain,
                    'token_length' => strlen($clean_api_key),
                    'token_first_10' => substr($clean_api_key, 0, 10) . '...',
                    'token_last_10' => '...' . substr($clean_api_key, -10),
                    'is_bearer_token' => $is_bearer,
                    'response_body' => $body
                );
                
                // Add helpful hint if using JWT
                if ($is_bearer) {
                    $error_context['IMPORTANT'] = 'JWT tokens are typically for SSO, not Admin API. You may need a Private API Key instead. See HOW-TO-GET-API-KEY.md';
                }
                
                $this->logger->error("API Authentication Failed (401)", $error_context);
            } else {
                $this->logger->error("API error: $status_code", array(
                    'url' => $url,
                    'response' => $error_message
                ));
            }
            
            return new WP_Error('api_error', $error_message, array('status' => $status_code, 'body' => $body));
        }
        
        $this->logger->debug("API Response: $status_code", array('data' => $data));
        
        return $data;
    }
    
    /**
     * Get user by email
     */
    public function get_user_by_email($email) {
        $cache_key = 'thinkific_user_' . md5($email);
        $cached = get_transient($cache_key);
        
        if ($cached !== false) {
            return $cached;
        }
        
        $response = $this->request('GET', 'users', array(
            'query[email]' => $email
        ));
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        $user = isset($response['items'][0]) ? $response['items'][0] : null;
        
        if ($user) {
            set_transient($cache_key, $user, 600); // Cache 10 minutes
        }
        
        return $user;
    }
    
    /**
     * Create user
     */
    public function create_user($email, $first_name = '', $last_name = '') {
        $data = array(
            'email' => $email,
            'first_name' => $first_name,
            'last_name' => $last_name,
        );
        
        $response = $this->request('POST', 'users', $data);
        
        if (!is_wp_error($response)) {
            // Clear cache
            $cache_key = 'thinkific_user_' . md5($email);
            delete_transient($cache_key);
            
            $this->logger->info('Created Thinkific user', array('email' => $email));
            
            // Allow plugins to send account welcome email (Thinkific doesn't send for API-created users)
            do_action('thinkific_wp_user_created', $response, $email, $first_name, $last_name);
        }
        
        return $response;
    }
    
    /**
     * Get or create user
     */
    public function get_or_create_user($email, $first_name = '', $last_name = '') {
        $user = $this->get_user_by_email($email);
        
        if (is_wp_error($user)) {
            return $user;
        }
        
        if (!$user) {
            $user = $this->create_user($email, $first_name, $last_name);
        }
        
        return $user;
    }
    
    /**
     * Enroll user in course
     */
    public function enroll_user($course_id, $user_id) {
        $data = array(
            'course_id' => (int) $course_id,
            'user_id' => (int) $user_id,
            'activated_at' => current_time('c'),
        );
        
        $response = $this->request('POST', "enrollments", $data);
        
        if (!is_wp_error($response)) {
            // Clear enrollment cache for this user
            $this->clear_user_enrollment_cache($user_id);
            
            $this->logger->info('Enrolled user in course', array(
                'user_id' => $user_id,
                'course_id' => $course_id
            ));
        }
        
        return $response;
    }
    
    /**
     * Get user enrollments
     */
    public function get_user_enrollments($user_id_or_email) {
        // Determine if we have user ID or email
        $is_email = is_string($user_id_or_email) && strpos($user_id_or_email, '@') !== false;
        
        if ($is_email) {
            $user = $this->get_user_by_email($user_id_or_email);
            if (is_wp_error($user) || !$user) {
                return array();
            }
            $user_id = $user['id'];
        } else {
            $user_id = $user_id_or_email;
        }
        
        $cache_key = 'thinkific_enrollments_' . $user_id;
        $cached = get_transient($cache_key);
        
        if ($cached !== false) {
            return $cached;
        }
        
        $enrollments = array();
        $page = 1;
        $per_page = 25;
        
        do {
            $response = $this->request('GET', 'enrollments', array(
                'query[user_id]' => $user_id,
                'page' => $page,
                'limit' => $per_page
            ));
            
            if (is_wp_error($response)) {
                $this->logger->error('Failed to fetch enrollments', array(
                    'user_id' => $user_id,
                    'error' => $response->get_error_message()
                ));
                break;
            }
            
            $items = isset($response['items']) ? $response['items'] : array();
            $enrollments = array_merge($enrollments, $items);
            
            $has_more = count($items) === $per_page;
            $page++;
            
        } while ($has_more && $page <= 10); // Safety limit
        
        // Cache for configured duration
        $cache_duration = get_option('thinkific_wp_enrollment_cache_duration', 600);
        set_transient($cache_key, $enrollments, $cache_duration);
        
        return $enrollments;
    }
    
    /**
     * Clear user enrollment cache
     */
    private function clear_user_enrollment_cache($user_id) {
        $cache_key = 'thinkific_enrollments_' . $user_id;
        delete_transient($cache_key);
    }
    
    /**
     * Get courses (optional, may not work with New Course Builder)
     */
    public function get_courses() {
        $cache_key = 'thinkific_courses';
        $cached = get_transient($cache_key);
        
        if ($cached !== false) {
            return $cached;
        }
        
        $courses = array();
        $page = 1;
        $per_page = 25;
        
        do {
            $response = $this->request('GET', 'courses', array(
                'page' => $page,
                'limit' => $per_page
            ));
            
            if (is_wp_error($response)) {
                $this->logger->warning('Failed to fetch courses - may be due to New Course Builder', array(
                    'error' => $response->get_error_message()
                ));
                break;
            }
            
            $items = isset($response['items']) ? $response['items'] : array();
            $courses = array_merge($courses, $items);
            
            $has_more = count($items) === $per_page;
            $page++;
            
        } while ($has_more && $page <= 10); // Safety limit
        
        // Cache for configured duration
        $cache_duration = get_option('thinkific_wp_course_cache_duration', 86400);
        set_transient($cache_key, $courses, $cache_duration);
        
        return $courses;
    }
    
    /**
     * Test connection
     */
    public function test_connection() {
        $response = $this->request('GET', 'users', array('limit' => 1));
        
        if (is_wp_error($response)) {
            return array(
                'success' => false,
                'message' => $response->get_error_message()
            );
        }
        
        return array(
            'success' => true,
            'message' => __('Connection successful!', 'thinkific-wp')
        );
    }
}
