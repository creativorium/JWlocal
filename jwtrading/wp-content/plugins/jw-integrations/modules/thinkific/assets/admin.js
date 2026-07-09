/**
 * Admin JavaScript
 *
 * @package Thinkific_WP
 */

(function($) {
    'use strict';
    
    // Initialize on document ready
    $(document).ready(function() {
        initializeAdmin();
    });
    
    /**
     * Initialize admin functionality
     */
    function initializeAdmin() {
        // Any additional admin JS can go here
        // Most functionality is inline in the admin pages for simplicity
        
        // Show/hide password field
        togglePasswordVisibility();
    }
    
    /**
     * Toggle password visibility
     */
    function togglePasswordVisibility() {
        // Add a toggle button to password fields if needed
        $('input[name="thinkific_wp_api_key"]').each(function() {
            var $input = $(this);
            var $wrapper = $('<div class="thinkific-password-wrapper"></div>');
            
            $input.wrap($wrapper);
            
            var $toggle = $('<button type="button" class="button thinkific-toggle-password" style="margin-left: 10px;">Show</button>');
            
            $toggle.on('click', function() {
                if ($input.attr('type') === 'password') {
                    $input.attr('type', 'text');
                    $toggle.text('Hide');
                } else {
                    $input.attr('type', 'password');
                    $toggle.text('Show');
                }
            });
            
            $input.after($toggle);
        });
    }
    
    /**
     * Show notification
     */
    window.thinkificShowNotice = function(message, type) {
        type = type || 'info';
        
        var $notice = $('<div class="notice notice-' + type + ' is-dismissible"><p>' + message + '</p></div>');
        
        $('.wrap > h1').after($notice);
        
        // Auto-dismiss after 5 seconds
        setTimeout(function() {
            $notice.fadeOut(function() {
                $(this).remove();
            });
        }, 5000);
    };
    
    /**
     * Confirm action
     */
    window.thinkificConfirm = function(message, callback) {
        if (confirm(message)) {
            callback();
        }
    };
    
})(jQuery);
