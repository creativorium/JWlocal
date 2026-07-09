<?php
/**
 * Logger Class
 *
 * @package Thinkific_WP
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Handles logging for the plugin
 */
class Thinkific_WP_Logger {
    
    /**
     * Log levels
     */
    const LEVEL_ERROR = 'error';
    const LEVEL_WARNING = 'warning';
    const LEVEL_INFO = 'info';
    const LEVEL_DEBUG = 'debug';
    
    /**
     * Option name for logs
     */
    const OPTION_NAME = 'thinkific_wp_logs';
    
    /**
     * Maximum log entries to keep
     */
    const MAX_LOGS = 500;
    
    /**
     * Check if logging is enabled
     */
    private function is_enabled() {
        return (bool) get_option('thinkific_wp_enable_logging', 1);
    }
    
    /**
     * Log a message
     */
    public function log($message, $level = self::LEVEL_INFO, $context = array()) {
        if (!$this->is_enabled()) {
            return;
        }
        
        $entry = array(
            'timestamp' => current_time('mysql'),
            'level' => $level,
            'message' => $message,
            'context' => $context,
        );
        
        $logs = get_option(self::OPTION_NAME, array());
        
        // Add new entry
        array_unshift($logs, $entry);
        
        // Trim to max size
        if (count($logs) > self::MAX_LOGS) {
            $logs = array_slice($logs, 0, self::MAX_LOGS);
        }
        
        update_option(self::OPTION_NAME, $logs, false);
        
        // Also log to error_log for critical errors
        if ($level === self::LEVEL_ERROR) {
            error_log(sprintf(
                '[Thinkific WP] %s: %s | Context: %s',
                strtoupper($level),
                $message,
                json_encode($context)
            ));
        }
    }
    
    /**
     * Log error
     */
    public function error($message, $context = array()) {
        $this->log($message, self::LEVEL_ERROR, $context);
    }
    
    /**
     * Log warning
     */
    public function warning($message, $context = array()) {
        $this->log($message, self::LEVEL_WARNING, $context);
    }
    
    /**
     * Log info
     */
    public function info($message, $context = array()) {
        $this->log($message, self::LEVEL_INFO, $context);
    }
    
    /**
     * Log debug
     */
    public function debug($message, $context = array()) {
        $this->log($message, self::LEVEL_DEBUG, $context);
    }
    
    /**
     * Get all logs
     */
    public function get_logs($limit = 100) {
        $logs = get_option(self::OPTION_NAME, array());
        return array_slice($logs, 0, $limit);
    }
    
    /**
     * Clear all logs
     */
    public function clear_logs() {
        delete_option(self::OPTION_NAME);
    }
    
    /**
     * Get logs by level
     */
    public function get_logs_by_level($level, $limit = 100) {
        $logs = $this->get_logs(self::MAX_LOGS);
        $filtered = array_filter($logs, function($log) use ($level) {
            return $log['level'] === $level;
        });
        return array_slice($filtered, 0, $limit);
    }
}
