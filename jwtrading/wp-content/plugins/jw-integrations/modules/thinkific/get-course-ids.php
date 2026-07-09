<?php
/**
 * Get Thinkific Course IDs Tool
 * 
 * USAGE:
 * 1. Upload to WordPress root
 * 2. Visit: yoursite.com/get-course-ids.php
 * 3. Copy the course IDs
 * 4. DELETE this file
 */

require_once('wp-load.php');

if (!is_user_logged_in() || !current_user_can('manage_options')) {
    wp_die('Access denied.');
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Get Thinkific Course IDs</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 900px; margin: 50px auto; padding: 20px; }
        .success { background: #d4edda; padding: 15px; margin: 10px 0; border-radius: 4px; }
        .error { background: #f8d7da; padding: 15px; margin: 10px 0; border-radius: 4px; }
        .warning { background: #fff3cd; padding: 15px; margin: 10px 0; border-radius: 4px; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        table td, table th { padding: 12px; border: 1px solid #ddd; text-align: left; }
        table th { background: #f4f4f4; font-weight: bold; }
        code { background: #f4f4f4; padding: 2px 8px; border-radius: 3px; font-size: 14px; }
        .copy-btn { background: #0073aa; color: white; border: none; padding: 5px 10px; cursor: pointer; border-radius: 3px; }
        .copy-btn:hover { background: #005177; }
    </style>
</head>
<body>
    <h1>🎓 Get Your Thinkific Course IDs</h1>
    
    <div class="warning">
        <strong>⚠️ DELETE THIS FILE after use:</strong> <code>get-course-ids.php</code>
    </div>

    <?php
    require_once(plugin_dir_path(__FILE__) . 'wp-content/plugins/thinkific-wp-integration/includes/class-thinkific-client.php');
    require_once(plugin_dir_path(__FILE__) . 'wp-content/plugins/thinkific-wp-integration/includes/class-logger.php');
    
    $client = new Thinkific_WP_Client();
    
    if (!$client->is_configured()) {
        echo '<div class="error">❌ Thinkific API is not configured. Please set up your API credentials first.</div>';
    } else {
        echo '<div class="success">✅ API configured. Fetching courses...</div>';
        
        // Clear cache to get fresh data
        delete_transient('thinkific_courses');
        
        $courses = $client->get_courses();
        
        if (is_wp_error($courses)) {
            echo '<div class="error">';
            echo '<h3>❌ Failed to fetch courses</h3>';
            echo '<p><strong>Error:</strong> ' . esc_html($courses->get_error_message()) . '</p>';
            echo '<p>This might be due to:</p>';
            echo '<ul>';
            echo '<li>New Course Builder (API doesn\'t return courses)</li>';
            echo '<li>API permissions issue</li>';
            echo '<li>Network connectivity</li>';
            echo '</ul>';
            echo '<p><strong>Solution:</strong> Get course ID manually from Thinkific admin URL (see instructions below)</p>';
            echo '</div>';
        } elseif (empty($courses)) {
            echo '<div class="warning">';
            echo '<h3>⚠️ No courses returned from API</h3>';
            echo '<p>This is expected if you\'re using the <strong>New Course Builder</strong>.</p>';
            echo '<p><strong>Get Course ID manually:</strong></p>';
            echo '<ol>';
            echo '<li>Log into Thinkific admin</li>';
            echo '<li>Go to: Manage Learning Content → Courses</li>';
            echo '<li>Click on your course</li>';
            echo '<li>Look at the URL: <code>https://jw-ict-bootcamp.thinkific.com/manage/courses/<strong>1234567</strong>/...</code></li>';
            echo '<li>The number is your Course ID!</li>';
            echo '</ol>';
            echo '</div>';
        } else {
            echo '<div class="success">';
            echo '<h3>✅ Found ' . count($courses) . ' course(s)!</h3>';
            echo '</div>';
            
            echo '<h2>Your Courses:</h2>';
            echo '<table>';
            echo '<thead>';
            echo '<tr>';
            echo '<th>Course ID</th>';
            echo '<th>Course Name</th>';
            echo '<th>Slug</th>';
            echo '<th>Published</th>';
            echo '<th>Action</th>';
            echo '</tr>';
            echo '</thead>';
            echo '<tbody>';
            
            foreach ($courses as $course) {
                $course_id = isset($course['id']) ? $course['id'] : 'N/A';
                $course_name = isset($course['name']) ? $course['name'] : 'Untitled';
                $course_slug = isset($course['slug']) ? $course['slug'] : '';
                $published = isset($course['published']) ? ($course['published'] ? 'Yes' : 'No') : 'Unknown';
                
                echo '<tr>';
                echo '<td><code style="font-size: 16px; font-weight: bold; color: #d63638;">' . esc_html($course_id) . '</code></td>';
                echo '<td><strong>' . esc_html($course_name) . '</strong></td>';
                echo '<td>' . esc_html($course_slug) . '</td>';
                echo '<td>' . esc_html($published) . '</td>';
                echo '<td><button class="copy-btn" onclick="copyToClipboard(\'' . esc_js($course_id) . '\')">Copy ID</button></td>';
                echo '</tr>';
            }
            
            echo '</tbody>';
            echo '</table>';
            
            echo '<div class="success">';
            echo '<h3>📋 Next Steps:</h3>';
            echo '<ol>';
            echo '<li>Copy the Course ID from the table above</li>';
            echo '<li>Go to: WordPress Admin → Thinkific → Course Mapping</li>';
            echo '<li>Edit your mapping</li>';
            echo '<li>Paste the Course ID in the "Course ID" field</li>';
            echo '<li>Save</li>';
            echo '<li><strong>DELETE THIS FILE!</strong></li>';
            echo '</ol>';
            echo '</div>';
        }
    }
    ?>
    
    <hr style="margin: 40px 0;">
    
    <h2>📖 Manual Method (Always Works)</h2>
    
    <div class="success">
        <h3>Get Course ID from Thinkific Admin URL:</h3>
        <ol>
            <li><strong>Log into Thinkific Admin:</strong><br>
                <code>https://jw-ict-bootcamp.thinkific.com/manage</code>
            </li>
            <li><strong>Go to:</strong> Manage Learning Content → Courses</li>
            <li><strong>Click on your course name</strong></li>
            <li><strong>Look at the browser URL bar:</strong><br>
                <code>https://jw-ict-bootcamp.thinkific.com/manage/courses/<span style="background: yellow; padding: 2px 5px;">2494845</span>/curriculum/chapters</code><br>
                <small>The highlighted number is your Course ID!</small>
            </li>
            <li><strong>Copy that number</strong> and use it in your mapping</li>
        </ol>
    </div>
    
    <div class="warning" style="margin-top: 40px;">
        <h3>🔥 Important: Delete This File!</h3>
        <p>This tool can expose course information. Delete <code>get-course-ids.php</code> immediately after use!</p>
    </div>
    
    <script>
    function copyToClipboard(text) {
        navigator.clipboard.writeText(text).then(function() {
            alert('Course ID copied: ' + text);
        }, function(err) {
            prompt('Copy this Course ID:', text);
        });
    }
    </script>
    
</body>
</html>
