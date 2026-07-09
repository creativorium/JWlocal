<?php
/**
 * Thinkific API Credentials Test Script
 * 
 * USAGE:
 * 1. Copy this file to your WordPress root directory
 * 2. Visit: yoursite.com/test-credentials.php?test=1
 * 3. Check the output
 * 4. DELETE THIS FILE after testing for security
 * 
 * @package Thinkific_WP
 */

// Require WordPress
require_once('wp-load.php');

// Security: Only allow logged-in admins
if (!is_user_logged_in() || !current_user_can('manage_options')) {
    wp_die('Access denied. You must be logged in as an administrator.');
}

// Only run if ?test=1 is in URL
if (!isset($_GET['test']) || $_GET['test'] !== '1') {
    wp_die('Add ?test=1 to the URL to run the test.');
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Thinkific API Test</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 900px; margin: 50px auto; padding: 20px; }
        .success { background: #d4edda; border: 1px solid #c3e6cb; padding: 15px; margin: 10px 0; border-radius: 4px; }
        .error { background: #f8d7da; border: 1px solid #f5c6cb; padding: 15px; margin: 10px 0; border-radius: 4px; }
        .info { background: #d1ecf1; border: 1px solid #bee5eb; padding: 15px; margin: 10px 0; border-radius: 4px; }
        .warning { background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; margin: 10px 0; border-radius: 4px; }
        code { background: #f4f4f4; padding: 2px 6px; border-radius: 3px; }
        pre { background: #f4f4f4; padding: 15px; border-radius: 4px; overflow-x: auto; }
        h1 { color: #333; }
        h2 { color: #555; border-bottom: 2px solid #ddd; padding-bottom: 10px; }
        .test-section { margin: 30px 0; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        table td, table th { padding: 10px; border: 1px solid #ddd; text-align: left; }
        table th { background: #f4f4f4; font-weight: bold; }
        .delete-warning { background: #f8d7da; border: 2px solid #dc3545; padding: 20px; margin: 30px 0; border-radius: 4px; }
    </style>
</head>
<body>
    <h1>🔍 Thinkific API Credentials Test</h1>
    
    <div class="delete-warning">
        <strong>⚠️ SECURITY WARNING:</strong> Delete this file after testing! It exposes sensitive information.
    </div>

    <?php
    // Get settings
    $api_key = get_option('thinkific_wp_api_key', '');
    $subdomain = get_option('thinkific_wp_subdomain', '');
    $api_base_url = get_option('thinkific_wp_api_base_url', 'https://api.thinkific.com/api/public/v1');
    
    // Clean subdomain
    $clean_subdomain = str_replace('.thinkific.com', '', trim($subdomain));
    $clean_api_key = trim($api_key);
    
    // Detect authentication type
    $is_bearer = substr_count($clean_api_key, '.') === 2;
    $auth_type = $is_bearer ? 'API Access Token (JWT Bearer)' : 'Private API Key';
    
    // Extract subdomain from JWT if present
    $jwt_subdomain = '';
    if ($is_bearer) {
        $parts = explode('.', $clean_api_key);
        if (isset($parts[1])) {
            $payload = json_decode(base64_decode(strtr($parts[1], '-_', '+/')), true);
            if (isset($payload['subdomain'])) {
                $jwt_subdomain = $payload['subdomain'];
            }
        }
    }
    
    // Use JWT subdomain if field is empty
    $display_subdomain = !empty($clean_subdomain) ? $clean_subdomain : $jwt_subdomain;
    
    ?>
    
    <div class="test-section">
        <h2>1. Configuration Check</h2>
        
        <table>
            <tr>
                <th width="30%">Setting</th>
                <th width="50%">Value</th>
                <th width="20%">Status</th>
            </tr>
            <tr>
                <td><strong>Authentication Type</strong></td>
                <td><code><?php echo esc_html($auth_type); ?></code></td>
                <td><?php echo $is_bearer ? '✅ Modern' : '✅ Legacy'; ?></td>
            </tr>
            <tr>
                <td><strong>Subdomain (from field)</strong></td>
                <td><code><?php echo esc_html($clean_subdomain ?: '(empty)'); ?></code></td>
                <td><?php echo !empty($clean_subdomain) ? '✅' : ($is_bearer ? '⚠️ Using JWT' : '❌ Missing'); ?></td>
            </tr>
            <?php if ($is_bearer && $jwt_subdomain) : ?>
            <tr>
                <td><strong>Subdomain (from JWT)</strong></td>
                <td><code><?php echo esc_html($jwt_subdomain); ?></code></td>
                <td>✅ Extracted</td>
            </tr>
            <?php endif; ?>
            <tr>
                <td><strong>Subdomain Being Used</strong></td>
                <td><code><?php echo esc_html($display_subdomain); ?></code></td>
                <td><?php echo !empty($display_subdomain) ? '✅' : '❌'; ?></td>
            </tr>
            <tr>
                <td><strong>API Token</strong></td>
                <td><code><?php echo esc_html(substr($clean_api_key, 0, 10)); ?>...<?php echo esc_html(substr($clean_api_key, -10)); ?></code></td>
                <td><?php echo !empty($clean_api_key) ? '✅' : '❌ Missing'; ?></td>
            </tr>
            <tr>
                <td><strong>Token Length</strong></td>
                <td><?php echo strlen($clean_api_key); ?> characters</td>
                <td>
                    <?php 
                    if ($is_bearer) {
                        echo (strlen($clean_api_key) >= 300) ? '✅' : '⚠️ Seems short for JWT';
                    } else {
                        echo (strlen($clean_api_key) >= 32) ? '✅' : '⚠️ Too short';
                    }
                    ?>
                </td>
            </tr>
            <tr>
                <td><strong>API Base URL</strong></td>
                <td><code><?php echo esc_html($api_base_url); ?></code></td>
                <td><?php echo !empty($api_base_url) ? '✅' : '❌'; ?></td>
            </tr>
            <tr>
                <td><strong>School URL</strong></td>
                <td>
                    <a href="https://<?php echo esc_attr($display_subdomain); ?>.thinkific.com" target="_blank">
                        https://<?php echo esc_html($display_subdomain); ?>.thinkific.com
                    </a>
                </td>
                <td>Click to test</td>
            </tr>
        </table>
    </div>
    
    <div class="test-section">
        <h2>2. API Test (WordPress HTTP)</h2>
        
        <?php
        if (empty($clean_api_key) || empty($display_subdomain)) {
            echo '<div class="error">❌ Cannot test: API key or subdomain is missing</div>';
        } else {
            $test_url = trailingslashit($api_base_url) . 'users?limit=1';
            
            // Build headers based on authentication type
            if ($is_bearer) {
                $headers = array(
                    'Authorization' => 'Bearer ' . $clean_api_key,
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                );
            } else {
                $headers = array(
                    'X-Auth-API-Key' => $clean_api_key,
                    'X-Auth-Subdomain' => $display_subdomain,
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                );
            }
            
            $response = wp_remote_get($test_url, array(
                'headers' => $headers,
                'timeout' => 30,
            ));
            
            if (is_wp_error($response)) {
                echo '<div class="error"><strong>❌ WordPress HTTP Error:</strong><br>';
                echo esc_html($response->get_error_message());
                echo '</div>';
            } else {
                $status_code = wp_remote_retrieve_response_code($response);
                $body = wp_remote_retrieve_body($response);
                $headers = wp_remote_retrieve_headers($response);
                
                if ($status_code === 200) {
                    echo '<div class="success"><strong>✅ SUCCESS!</strong> API authentication is working.</div>';
                    echo '<div class="info"><strong>Response Preview:</strong><br>';
                    $data = json_decode($body, true);
                    if (isset($data['items'])) {
                        echo 'Found ' . count($data['items']) . ' user(s)';
                    }
                    echo '</div>';
                } elseif ($status_code === 401) {
                    echo '<div class="error"><strong>❌ Authentication Failed (401)</strong></div>';
                    echo '<div class="warning">';
                    echo '<strong>Response:</strong><br>';
                    echo '<pre>' . esc_html($body) . '</pre>';
                    echo '</div>';
                    
                    echo '<div class="info">';
                    echo '<strong>Possible Issues:</strong><br>';
                    echo '<ul>';
                    echo '<li>API key is incorrect or expired</li>';
                    echo '<li>Subdomain doesn\'t match the API key</li>';
                    echo '<li>API key doesn\'t have required permissions</li>';
                    echo '<li>API key was deleted in Thinkific admin</li>';
                    echo '</ul>';
                    echo '</div>';
                } else {
                    echo '<div class="error"><strong>❌ HTTP Error ' . esc_html($status_code) . '</strong></div>';
                    echo '<pre>' . esc_html($body) . '</pre>';
                }
                
                echo '<div class="info"><strong>Request Details:</strong><br>';
                echo '<table>';
                echo '<tr><td><strong>URL:</strong></td><td><code>' . esc_html($test_url) . '</code></td></tr>';
                echo '<tr><td><strong>Method:</strong></td><td>GET</td></tr>';
                echo '<tr><td><strong>Auth Type:</strong></td><td>' . esc_html($auth_type) . '</td></tr>';
                echo '<tr><td><strong>Status:</strong></td><td>' . esc_html($status_code) . '</td></tr>';
                echo '<tr><td><strong>Headers Sent:</strong></td><td>';
                if ($is_bearer) {
                    echo 'Authorization: Bearer ' . esc_html(substr($clean_api_key, 0, 10)) . '...' . esc_html(substr($clean_api_key, -10)) . '<br>';
                } else {
                    echo 'X-Auth-API-Key: ' . esc_html(substr($clean_api_key, 0, 10)) . '...' . esc_html(substr($clean_api_key, -10)) . '<br>';
                    echo 'X-Auth-Subdomain: ' . esc_html($display_subdomain);
                }
                echo '</td></tr>';
                echo '</table>';
                echo '</div>';
            }
        }
        ?>
    </div>
    
    <div class="test-section">
        <h2>3. Direct cURL Test</h2>
        
        <div class="info">
            <p>Copy and run this command in your terminal to test outside WordPress:</p>
            <?php if ($is_bearer) : ?>
            <pre>curl -X GET "<?php echo esc_html($api_base_url); ?>/users?limit=1" \
  -H "Authorization: Bearer <?php echo esc_html($clean_api_key); ?>" \
  -H "Content-Type: application/json"</pre>
            <?php else : ?>
            <pre>curl -X GET "<?php echo esc_html($api_base_url); ?>/users?limit=1" \
  -H "X-Auth-API-Key: <?php echo esc_html($clean_api_key); ?>" \
  -H "X-Auth-Subdomain: <?php echo esc_html($display_subdomain); ?>" \
  -H "Content-Type: application/json"</pre>
            <?php endif; ?>
            
            <p><strong>Expected result:</strong></p>
            <ul>
                <li>✅ Status 200 = Credentials work</li>
                <li>❌ Status 401 = Credentials are wrong</li>
            </ul>
        </div>
    </div>
    
    <div class="test-section">
        <h2>4. Recommendations</h2>
        
        <?php if (empty($clean_api_key) || empty($clean_subdomain)) : ?>
            <div class="error">
                <strong>❌ Setup Required</strong>
                <ol>
                    <li>Go to WordPress Admin → Thinkific → Settings</li>
                    <li>Enter your subdomain and API key</li>
                    <li>Click "Test Connection"</li>
                    <li>Then come back here and refresh</li>
                </ol>
            </div>
        <?php else : ?>
            <div class="info">
                <strong>Next Steps:</strong>
                <ol>
                    <li>If test passed: <strong>Delete this file now!</strong> (test-credentials.php)</li>
                    <li>If test failed with 401:
                        <ul>
                            <li>Get a fresh API key from Thinkific admin</li>
                            <li>Verify subdomain is exactly correct (no spaces, no https://)</li>
                            <li>Check API key has Users and Enrollments permissions</li>
                            <li>Try the cURL command above in terminal</li>
                        </ul>
                    </li>
                    <li>Check logs: WordPress Admin → Thinkific → Logs</li>
                </ol>
            </div>
        <?php endif; ?>
    </div>
    
    <div class="delete-warning">
        <h3>🔥 DELETE THIS FILE NOW!</h3>
        <p>This file exposes your API credentials. Delete <code>test-credentials.php</code> from your WordPress root immediately after testing.</p>
        <p><strong>Command:</strong> <code>rm test-credentials.php</code></p>
    </div>
    
    <p style="text-align: center; color: #999; margin-top: 50px;">
        <small>Thinkific WooCommerce Integration v1.0.0 | Diagnostic Tool</small>
    </p>

</body>
</html>
