# Technical Notes - Thinkific WooCommerce Integration

## Architecture Overview

This plugin bridges WooCommerce and Thinkific using the Thinkific Public API v1. It's specifically designed for the **Growth plan**, which has important limitations compared to higher tiers.

## Growth Plan Constraints

### 1. No Native SSO (CRITICAL)

**Problem**: Growth plan does NOT support:
- OIDC/OpenID Connect SSO
- JWT/SAML SSO
- Custom authentication backends

**Solution**: This plugin implements a "seamless-feeling" approach without true SSO:

#### How It Works

1. **User Creation/Enrollment** (API-based)
   - When WooCommerce order is paid, plugin calls Thinkific API
   - Creates user in Thinkific (or finds existing by email)
   - Enrolls user in purchased courses
   - User receives Thinkific welcome email with password setup link

2. **Course Access** (Guided Login)
   - WordPress dashboard shows "My Courses"
   - "Continue Course" button links directly to Thinkific course URL
   - First-time users see: "Use the same email as checkout"
   - After first login, Thinkific remembers user (cookies)
   - Subsequent visits are instant

3. **UX Optimization**
   - Same-tab navigation (not popup)
   - Tracking of "first launch" to hide helper text later
   - Clear, non-technical language
   - Email consistency emphasized

#### Why This Works

- **Email as Primary Key**: Users understand "use the same email"
- **One-Time Setup**: Thinkific's own session management handles future visits
- **Low Friction**: After first login, experience is smooth
- **No Technical Debt**: When/if client upgrades, can add real SSO

#### What It's NOT

- ❌ True Single Sign-On
- ❌ Password synchronization
- ❌ Automatic login to Thinkific
- ❌ Session sharing between platforms

#### What It IS

- ✅ Automatic enrollment
- ✅ Unified course dashboard
- ✅ Helpful user guidance
- ✅ Best possible UX without SSO
- ✅ Production-ready for Growth plan

### 2. New Course Builder API Limitations

**Problem**: Thinkific's New Course Builder may return incomplete or empty results from:
- `GET /api/public/v1/courses`
- `GET /api/public/v1/courses/{id}`

**Root Cause**: New Course Builder uses a different internal architecture, and the REST API hasn't been fully updated.

**Solution**: Plugin does NOT rely on course listing as core functionality.

#### Manual Mapping Approach

1. **Course URLs Are Sufficient**
   - Admin manually provides course URL
   - URL is used for "Continue Course" button
   - No API listing required for this

2. **Course IDs Are Optional**
   - If admin has course ID, great (better API enrollment)
   - If not, email-based enrollment still works
   - Enrollment endpoints are more stable than listing

3. **Sync Is Convenience Only**
   - "Sync Courses" button is a helper tool
   - If it works, admin can copy IDs easily
   - If it fails, plugin still functions perfectly

#### Implementation Details

```php
// This is optional and allowed to fail
public function get_courses() {
    $courses = $this->client->get_courses();
    
    if (is_wp_error($courses) || empty($courses)) {
        // Show admin warning
        // But don't break functionality
        return array();
    }
    
    return $courses;
}

// This is required and must work
public function enroll_user($course_id, $user_id) {
    // Enrollment API is more stable
    // And can use email if ID unavailable
}
```

### 3. Rate Limiting

**Constraint**: 120 requests per minute

**Strategy**:

1. **Aggressive Caching**
   - Course data: 24 hours (rarely changes)
   - Enrollments: 10 minutes (balance freshness/calls)
   - User data: 10 minutes

2. **Batch Operations**
   - Process all enrollments for an order in one pass
   - Don't call API on every dashboard page load

3. **Rate Limit Detection**
   - If API returns 429, set transient lock
   - Block all API calls for 60 seconds
   - Log the incident
   - Inform admin

4. **Backoff Strategy**
   ```php
   if ($status_code === 429) {
       set_transient('thinkific_wp_rate_limit_lock', true, 60);
       return new WP_Error('rate_limit', 'Exceeded rate limit');
   }
   ```

## Data Flow

### Enrollment Flow

```
[Order Created]
      ↓
[Status → Processing/Completed]
      ↓
[Hook: woocommerce_order_status_changed]
      ↓
[Get Mappings for Products]
      ↓
[Get/Create Thinkific User] ← API Call (cached)
      ↓
[For Each Course]
      ↓
[Enroll via API] ← API Call
      ↓
[Log to Database]
      ↓
[Update Order Meta]
```

### Dashboard Flow

```
[User Visits Dashboard]
      ↓
[Check Login]
      ↓
[Get User's Orders]
      ↓
[Get Mappings for Products]
      ↓
[Optional: Verify via API] ← API Call (cached)
      ↓
[Render Course Cards]
      ↓
[User Clicks "Continue Course"]
      ↓
[Navigate to Thinkific URL]
      ↓
[Thinkific Handles Login]
```

## Database Schema

### Course Mappings Table

```sql
CREATE TABLE wp_thinkific_course_mappings (
    id bigint UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    woo_product_id bigint UNSIGNED NOT NULL,    -- Foreign key to posts
    course_name varchar(255) NOT NULL,          -- Display name
    course_url varchar(500) NOT NULL,           -- Launch URL (REQUIRED)
    course_id varchar(100) NULL,                -- Thinkific course ID (optional)
    course_description text NULL,               -- For dashboard display
    created_at datetime DEFAULT CURRENT_TIMESTAMP,
    updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX(woo_product_id),
    INDEX(course_id)
);
```

**Key Design Decisions**:
- `course_url` is NOT NULL (core requirement)
- `course_id` is NULL (optional for API, not needed for UX)
- No foreign key constraints (performance, WooCommerce compatibility)

### Enrollments Table

```sql
CREATE TABLE wp_thinkific_enrollments (
    id bigint UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id bigint UNSIGNED NOT NULL,
    user_id bigint UNSIGNED NOT NULL,
    product_id bigint UNSIGNED NOT NULL,
    course_id varchar(100) NOT NULL,
    thinkific_user_id varchar(100) NULL,
    status varchar(50) NOT NULL DEFAULT 'pending',  -- pending, enrolled, failed
    error_message text NULL,
    retry_count int DEFAULT 0,
    enrolled_at datetime NULL,
    created_at datetime DEFAULT CURRENT_TIMESTAMP,
    updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX(order_id),
    INDEX(user_id),
    INDEX(status),
    INDEX(course_id),
    UNIQUE KEY unique_enrollment (user_id, course_id)
);
```

**Key Design Decisions**:
- `UNIQUE(user_id, course_id)` prevents duplicate enrollments
- `retry_count` enables retry logic
- `error_message` for debugging
- `status` for workflow management

## API Client Design

### Caching Strategy

```php
function get_user_enrollments($user_id) {
    $cache_key = 'thinkific_enrollments_' . $user_id;
    $cached = get_transient($cache_key);
    
    if ($cached !== false) {
        return $cached; // Cache hit
    }
    
    // API call
    $enrollments = $this->api_request(...);
    
    // Cache for 10 minutes
    set_transient($cache_key, $enrollments, 600);
    
    return $enrollments;
}
```

### Error Handling

```php
function request($method, $endpoint, $args) {
    // Check if rate limited
    if ($this->is_rate_limited()) {
        return new WP_Error('rate_limited', 'Please wait');
    }
    
    // Make request
    $response = wp_remote_request($url, $request_args);
    
    // Handle WP_Error
    if (is_wp_error($response)) {
        $this->logger->error('API request failed', [...]);
        return $response;
    }
    
    // Handle HTTP errors
    $status = wp_remote_retrieve_response_code($response);
    
    if ($status === 429) {
        $this->set_rate_limit_lock();
        return new WP_Error('rate_limit', '...');
    }
    
    if ($status >= 400) {
        return new WP_Error('api_error', '...');
    }
    
    return json_decode($body, true);
}
```

## Security Considerations

### API Key Storage

```php
// GOOD: Stored in wp_options (database)
update_option('thinkific_wp_api_key', $key);

// BAD: Never do this
define('THINKIFIC_API_KEY', 'secret'); // In file, can be committed to git
```

### Nonce Protection

```php
// All AJAX handlers
check_ajax_referer('thinkific_wp_admin', 'nonce');

// All forms
wp_nonce_field('thinkific_wp_admin');
```

### SQL Injection Prevention

```php
// GOOD: Prepared statements
$wpdb->prepare("SELECT * FROM $table WHERE id = %d", $id);

// BAD: Direct concatenation
$wpdb->query("SELECT * FROM $table WHERE id = $id");
```

### Input Sanitization

```php
$product_id = absint($_POST['product_id']);           // Integer
$course_name = sanitize_text_field($_POST['name']);   // Text
$course_url = esc_url_raw($_POST['url']);             // URL
$description = wp_kses_post($_POST['desc']);          // HTML
```

### Capability Checks

```php
if (!current_user_can('manage_options')) {
    wp_die('Permission denied');
}
```

## Performance Optimizations

### 1. Transient Caching

- Reduces database queries
- Reduces API calls
- Automatic expiration
- Easy to clear

### 2. Conditional Loading

```php
// Only load admin assets on plugin pages
if (strpos($hook, 'thinkific') === false) {
    return;
}
```

### 3. Lazy Instantiation

```php
// Classes only created when needed
if (is_admin()) {
    $this->admin = new Thinkific_WP_Admin();
}
```

### 4. Efficient Queries

```php
// Get all mappings in one query
$mappings = $this->get_mappings_for_order($order_id);

// Instead of looping and querying each product
```

## Extensibility

### Hooks for Developers

```php
// Before enrollment
do_action('thinkific_wp_before_enrollment', $order_id, $course_id);

// After successful enrollment
do_action('thinkific_wp_enrollment_success', $order_id, $course_id, $user_id);

// Modify course data
apply_filters('thinkific_wp_course_data', $course);

// Modify dashboard courses
apply_filters('thinkific_wp_dashboard_courses', $courses, $user_id);
```

### Helper Functions

```php
// Check if user has access
thinkific_wp_user_has_course_access($user_id, $course_id);

// Get user's courses
thinkific_wp_get_user_courses($user_id);

// Clear all caches
thinkific_wp_clear_all_caches();
```

## Testing Recommendations

### Unit Testing

- Mock API responses
- Test error handling paths
- Test cache expiration
- Test rate limiting logic

### Integration Testing

- Test full enrollment flow
- Test with real Thinkific sandbox
- Test dashboard rendering
- Test admin interface

### Load Testing

- Simulate multiple concurrent orders
- Verify rate limiting works
- Check cache effectiveness
- Monitor database performance

## Maintenance Considerations

### Logs

- Keep logs for debugging
- Monitor for API errors
- Watch for rate limit hits
- Clear old logs periodically

### Updates

- Test API changes from Thinkific
- Watch for WooCommerce breaking changes
- Monitor WordPress core updates
- Keep PHP version requirements updated

### Support

- Encourage logging during troubleshooting
- Provide clear error messages
- Document common issues
- Offer retry mechanisms

## Future Enhancements

### When Upgrading to Higher Thinkific Plan

If client upgrades to a plan with SSO:

1. Keep current enrollment logic (still works)
2. Add SSO layer on top
3. Implement OIDC/JWT authentication
4. Automatically log into Thinkific from WordPress
5. Remove helper text about "same email"

### Additional Features

- Webhook support for real-time updates
- Course progress tracking
- Certificate management
- Bulk enrollment tools
- Analytics dashboard

## Conclusion

This plugin prioritizes:
1. **Reliability**: Works even when APIs are limited
2. **User Experience**: Seamless despite no SSO
3. **Performance**: Caching and rate limit awareness
4. **Maintainability**: Clean code, good documentation
5. **Extensibility**: Hooks and filters for customization

It's production-ready for Thinkific Growth plan customers who want to sell courses through WooCommerce.
