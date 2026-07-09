<?php
/**
 * Database Check & Repair Tool
 * 
 * USAGE:
 * 1. Upload to WordPress root
 * 2. Visit: yoursite.com/check-database.php
 * 3. Check results
 * 4. DELETE this file after fixing
 */

// Load WordPress
require_once('wp-load.php');

// Security: Admins only
if (!is_user_logged_in() || !current_user_can('manage_options')) {
    wp_die('Access denied. Must be admin.');
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Thinkific Database Check</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 900px; margin: 50px auto; padding: 20px; }
        .success { background: #d4edda; padding: 15px; margin: 10px 0; border-radius: 4px; color: #155724; }
        .error { background: #f8d7da; padding: 15px; margin: 10px 0; border-radius: 4px; color: #721c24; }
        .warning { background: #fff3cd; padding: 15px; margin: 10px 0; border-radius: 4px; color: #856404; }
        .info { background: #d1ecf1; padding: 15px; margin: 10px 0; border-radius: 4px; color: #0c5460; }
        code { background: #f4f4f4; padding: 2px 6px; border-radius: 3px; }
        pre { background: #f4f4f4; padding: 15px; border-radius: 4px; overflow-x: auto; }
        h2 { color: #555; border-bottom: 2px solid #ddd; padding-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        table td, table th { padding: 10px; border: 1px solid #ddd; text-align: left; }
        table th { background: #f4f4f4; }
    </style>
</head>
<body>
    <h1>🔍 Thinkific Database Check</h1>
    
    <div class="warning">
        <strong>⚠️ DELETE THIS FILE after fixing! </strong> <code>check-database.php</code>
    </div>

    <?php
    global $wpdb;
    
    $tables = array(
        'mappings' => $wpdb->prefix . 'thinkific_course_mappings',
        'enrollments' => $wpdb->prefix . 'thinkific_enrollments'
    );
    
    echo '<h2>1. Check Tables Exist</h2>';
    
    $all_exist = true;
    foreach ($tables as $name => $table) {
        $exists = $wpdb->get_var("SHOW TABLES LIKE '$table'") === $table;
        
        if ($exists) {
            echo '<div class="success">✅ <strong>' . esc_html($name) . '</strong>: Table exists → <code>' . esc_html($table) . '</code></div>';
        } else {
            echo '<div class="error">❌ <strong>' . esc_html($name) . '</strong>: Table MISSING! → <code>' . esc_html($table) . '</code></div>';
            $all_exist = false;
        }
    }
    
    // If tables don't exist, try to create them
    if (!$all_exist) {
        echo '<h2>2. Create Missing Tables</h2>';
        
        require_once(plugin_dir_path(__FILE__) . 'wp-content/plugins/thinkific-wp-integration/includes/class-db.php');
        
        echo '<div class="info">Attempting to create tables...</div>';
        
        Thinkific_WP_DB::create_tables();
        
        // Check again
        $all_exist_now = true;
        foreach ($tables as $name => $table) {
            $exists = $wpdb->get_var("SHOW TABLES LIKE '$table'") === $table;
            
            if ($exists) {
                echo '<div class="success">✅ <strong>' . esc_html($name) . '</strong>: Created successfully!</div>';
            } else {
                echo '<div class="error">❌ <strong>' . esc_html($name) . '</strong>: Still missing!</div>';
                $all_exist_now = false;
            }
        }
        
        if ($all_exist_now) {
            echo '<div class="success"><strong>✅ All tables created successfully!</strong></div>';
        } else {
            echo '<div class="error"><strong>❌ Some tables could not be created. Check database permissions.</strong></div>';
        }
    }
    
    // Check table structure
    echo '<h2>3. Table Structure</h2>';
    
    foreach ($tables as $name => $table) {
        $exists = $wpdb->get_var("SHOW TABLES LIKE '$table'") === $table;
        
        if ($exists) {
            echo '<h3>' . esc_html(ucfirst($name)) . ' Table</h3>';
            
            $columns = $wpdb->get_results("DESCRIBE $table");
            
            if ($columns) {
                echo '<table>';
                echo '<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>';
                foreach ($columns as $column) {
                    echo '<tr>';
                    echo '<td><code>' . esc_html($column->Field) . '</code></td>';
                    echo '<td>' . esc_html($column->Type) . '</td>';
                    echo '<td>' . esc_html($column->Null) . '</td>';
                    echo '<td>' . esc_html($column->Key) . '</td>';
                    echo '<td>' . esc_html($column->Default) . '</td>';
                    echo '</tr>';
                }
                echo '</table>';
            }
            
            // Count rows
            $count = $wpdb->get_var("SELECT COUNT(*) FROM $table");
            echo '<div class="info">📊 <strong>Rows:</strong> ' . esc_html($count) . '</div>';
        }
    }
    
    // Test insert capability
    echo '<h2>4. Test Database Write</h2>';
    
    $test_table = $tables['mappings'];
    
    // Try to insert test data
    $test_result = $wpdb->insert(
        $test_table,
        array(
            'woo_product_id' => 99999,
            'course_name' => 'TEST - DELETE ME',
            'course_url' => 'https://test.com/test',
            'course_id' => 'test123'
        ),
        array('%d', '%s', '%s', '%s')
    );
    
    if ($test_result !== false) {
        $test_id = $wpdb->insert_id;
        echo '<div class="success">✅ <strong>Write Test PASSED!</strong> Test record inserted (ID: ' . esc_html($test_id) . ')</div>';
        
        // Clean up test record
        $wpdb->delete($test_table, array('id' => $test_id), array('%d'));
        echo '<div class="info">🧹 Test record cleaned up</div>';
    } else {
        echo '<div class="error">❌ <strong>Write Test FAILED!</strong></div>';
        echo '<div class="error"><strong>Error:</strong> ' . esc_html($wpdb->last_error) . '</div>';
        echo '<div class="warning"><strong>Query:</strong> <code>' . esc_html($wpdb->last_query) . '</code></div>';
    }
    
    // Check database permissions
    echo '<h2>5. Database Permissions</h2>';
    
    $db_user = DB_USER;
    $db_name = DB_NAME;
    
    echo '<table>';
    echo '<tr><th>Setting</th><th>Value</th></tr>';
    echo '<tr><td>Database Name</td><td><code>' . esc_html($db_name) . '</code></td></tr>';
    echo '<tr><td>Database User</td><td><code>' . esc_html($db_user) . '</code></td></tr>';
    echo '<tr><td>Table Prefix</td><td><code>' . esc_html($wpdb->prefix) . '</code></td></tr>';
    echo '<tr><td>WordPress Version</td><td><code>' . esc_html(get_bloginfo('version')) . '</code></td></tr>';
    echo '<tr><td>PHP Version</td><td><code>' . esc_html(phpversion()) . '</code></td></tr>';
    echo '</table>';
    
    // Recommendations
    echo '<h2>6. Recommendations</h2>';
    
    if ($all_exist && $test_result !== false) {
        echo '<div class="success">';
        echo '<h3>✅ Everything looks good!</h3>';
        echo '<p>Your database is set up correctly. You should be able to create mappings now.</p>';
        echo '<p><strong>Next steps:</strong></p>';
        echo '<ol>';
        echo '<li>DELETE this file (<code>check-database.php</code>)</li>';
        echo '<li>Go back to WordPress Admin → Thinkific → Course Mapping</li>';
        echo '<li>Try creating a mapping again</li>';
        echo '</ol>';
        echo '</div>';
    } else {
        echo '<div class="error">';
        echo '<h3>❌ Issues Found</h3>';
        echo '<p><strong>Try these fixes:</strong></p>';
        echo '<ol>';
        echo '<li><strong>Deactivate and Reactivate Plugin:</strong> WordPress Admin → Plugins → Deactivate "Thinkific WooCommerce Integration" → Activate it again</li>';
        echo '<li><strong>Check Database Permissions:</strong> Make sure your database user has CREATE, INSERT, UPDATE, DELETE permissions</li>';
        echo '<li><strong>Contact Host:</strong> If still not working, contact your hosting provider about database permissions</li>';
        echo '</ol>';
        echo '</div>';
    }
    
    // Show recent WordPress errors
    echo '<h2>7. Recent WordPress Errors</h2>';
    
    if (defined('WP_DEBUG') && WP_DEBUG && defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
        $debug_file = WP_CONTENT_DIR . '/debug.log';
        if (file_exists($debug_file)) {
            $log_lines = file($debug_file);
            $recent_lines = array_slice($log_lines, -20);
            
            echo '<div class="info">';
            echo '<strong>Last 20 lines from debug.log:</strong>';
            echo '<pre>' . esc_html(implode('', $recent_lines)) . '</pre>';
            echo '</div>';
        } else {
            echo '<div class="info">Debug log file not found.</div>';
        }
    } else {
        echo '<div class="warning">WordPress debug mode is not enabled. Enable it to see errors.</div>';
    }
    
    ?>
    
    <div class="warning" style="margin-top: 40px;">
        <h3>🔥 Important: Delete This File!</h3>
        <p>This diagnostic file exposes database information. Delete <code>check-database.php</code> now!</p>
    </div>
    
</body>
</html>
