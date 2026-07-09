# Thinkific WooCommerce Integration Plugin

A production-ready WordPress plugin that integrates Thinkific (Growth plan) with WooCommerce, providing seamless course access management and automatic enrollment.

## 🚀 Features

### Core Functionality
- **Automatic Enrollment**: Customers are automatically enrolled in Thinkific courses when they purchase mapped WooCommerce products
- **Course Mapping**: Flexible product-to-course mapping system that works without relying on Thinkific's course listing API
- **Student Dashboard**: Beautiful, responsive dashboard showing purchased courses with one-click access
- **Smart Caching**: Intelligent API caching to stay within rate limits (120 requests/min)
- **Comprehensive Logging**: Built-in logging system for troubleshooting and monitoring
- **Retry System**: Failed enrollments can be retried directly from order admin

### Growth Plan Compatibility
This plugin is specifically designed for Thinkific's **Growth plan** with the following considerations:

1. **No SSO Required**: Growth plan doesn't support native SSO. The plugin creates a seamless experience by:
   - Auto-creating Thinkific users via API
   - Providing helpful login guidance
   - Tracking first-time launches to reduce friction

2. **New Course Builder Compatible**: Works even if the REST API can't list courses due to the New Course Builder. Manual mapping ensures full functionality.

3. **Rate Limit Aware**: Implements caching and backoff to respect the 120 requests/min limit.

## 📋 Requirements

- WordPress 5.8+
- PHP 7.4+
- WooCommerce 5.0+
- Thinkific account (Growth plan or higher)
- Thinkific API key

## 🔧 Installation

1. Upload the `thinkific-wp-integration` folder to `/wp-content/plugins/`
2. Activate the plugin through WordPress admin
3. Navigate to **Thinkific > Settings**
4. Configure your API credentials

## ⚙️ Configuration

### 1. API Settings

Go to **Thinkific > Settings**:

- **Subdomain**: Your Thinkific subdomain (e.g., `yourschool` from `yourschool.thinkific.com`)
- **API Key**: Found in Thinkific Admin > Settings > API & Webhooks
- **API Base URL**: Leave as default unless instructed otherwise

Click **Test Connection** to verify your credentials.

### 2. Course Mapping

Go to **Thinkific > Course Mapping**:

#### Option A: Sync from Thinkific (if available)
1. Click **Sync Courses from Thinkific**
2. If successful, course IDs will be displayed
3. Use these IDs when creating mappings

#### Option B: Manual Mapping (always works)
1. Click **Add New Mapping**
2. Fill in the required fields:
   - **WooCommerce Product**: Select the product to map
   - **Course Name**: Display name (e.g., "WordPress Fundamentals")
   - **Course URL**: Full URL to the course (e.g., `https://yourschool.thinkific.com/courses/wordpress-101`)
   - **Course ID**: (Optional) Thinkific course ID for API enrollment
   - **Description**: (Optional) Shown in student dashboard

3. Click **Save Mapping**

### 3. WooCommerce Settings

Configure enrollment behavior:

- **Enrollment Trigger Statuses**: Which order statuses trigger enrollment (default: Processing, Completed)
- **Force Single Quantity**: Prevent customers from buying multiple quantities of mapped products
- **Skip Cart**: Redirect mapped products directly to checkout

### 4. Cache Settings

Configure API caching:

- **Course Cache Duration**: How long to cache course data (default: 24 hours)
- **Enrollment Cache Duration**: How long to cache enrollment data (default: 10 minutes)

### 5. Student Dashboard

Add the student dashboard to any page using the shortcode:

```
[thinkific_dashboard]
```

**Optional attributes:**
- `title`: Dashboard heading (default: "My Courses")
- `show_description`: Show course descriptions (default: "yes")

Example:
```
[thinkific_dashboard title="My Learning Path" show_description="no"]
```

## 🔄 How It Works

### Purchase Flow

1. **Customer purchases** a WooCommerce product
2. **Order status changes** to a trigger status (e.g., "Processing")
3. **Plugin checks** for course mappings
4. **Thinkific user** is created/found via API
5. **Enrollment** happens automatically via API
6. **Status is logged** in the database and order meta
7. **Customer sees** course in their WordPress dashboard

### Student Experience

1. Customer logs into WordPress
2. Visits the dashboard page (with `[thinkific_dashboard]` shortcode)
3. Sees all purchased courses
4. Clicks **Continue Course** button
5. Opens Thinkific in the same tab
6. If first time, sees helpful note: "Use the same email you used at checkout"
7. After first login, subsequent launches are instant

### No SSO Workaround

Since Growth plan doesn't support SSO, this plugin creates a "seamless-feeling" experience:

- Users are enrolled automatically in Thinkific
- They receive the same welcome email from Thinkific
- First login requires them to use the same email
- After first login, Thinkific remembers them
- The dashboard provides helpful reminders

## 🛠️ Troubleshooting

### View Logs

Go to **Thinkific > Logs** to see:
- API requests and responses
- Enrollment successes/failures
- Error messages with context

### Retry Failed Enrollments

1. Go to the WooCommerce order
2. Find the **Thinkific Enrollments** meta box
3. Click **Retry** on any failed enrollment

### Clear Caches

To force fresh data from Thinkific:

```php
// Add this to functions.php temporarily, then remove
thinkific_wp_clear_all_caches();
```

### Common Issues

**"Connection failed"**
- Verify API key and subdomain in settings
- Check that API key has correct permissions in Thinkific
- Test connection using the Test Connection button

**"Course sync returns empty"**
- This is expected if using New Course Builder
- Use manual mapping instead
- Plugin will work perfectly with manual mappings

**"Enrollment failed"**
- Check logs for specific error
- Verify course ID is correct
- Ensure customer email is valid
- Use retry button in order meta box

**"Rate limit exceeded"**
- Plugin will automatically pause for 60 seconds
- Reduce cache duration if needed
- Check logs for excessive API calls

## 📊 Database Tables

The plugin creates two custom tables:

### `wp_thinkific_course_mappings`
Stores WooCommerce product → Thinkific course mappings.

### `wp_thinkific_enrollments`
Tracks enrollment attempts with status, errors, and retry count.

## 🎨 Customization

### Styling the Dashboard

Override styles by adding CSS to your theme:

```css
/* Change button color */
.thinkific-launch-button {
    background: #your-color;
}

/* Customize card appearance */
.thinkific-course-card {
    border-radius: 12px;
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}
```

### Hooks and Filters

```php
// Modify course data before display
add_filter('thinkific_wp_course_data', function($course) {
    // Customize course data
    return $course;
});

// After successful enrollment
add_action('thinkific_wp_enrollment_success', function($order_id, $course_id, $user_id) {
    // Custom actions after enrollment
}, 10, 3);

// Before enrollment attempt
add_action('thinkific_wp_before_enrollment', function($order_id, $course_id) {
    // Custom actions before enrollment
}, 10, 2);
```

## 🔒 Security

- All API keys are stored securely in WordPress options
- AJAX requests use WordPress nonces
- API credentials are never exposed to frontend
- Input sanitization on all user inputs
- Prepared SQL statements to prevent injection

## 📝 Development

### File Structure

```
thinkific-wp-integration/
├── thinkific-wp-integration.php    # Bootstrap
├── includes/
│   ├── class-plugin.php            # Core plugin class
│   ├── class-admin.php             # Admin interface
│   ├── class-settings.php          # Settings management
│   ├── class-db.php                # Database schema
│   ├── class-mappings.php          # Course mappings
│   ├── class-dashboard.php         # Student dashboard
│   ├── class-woocommerce.php       # WooCommerce integration
│   ├── class-thinkific-client.php  # API client
│   ├── class-logger.php            # Logging system
│   └── helpers.php                 # Helper functions
├── assets/
│   ├── admin.css                   # Admin styles
│   ├── admin.js                    # Admin scripts
│   └── dashboard.css               # Dashboard styles
└── README.md
```

### Code Standards

- Follows WordPress Coding Standards
- PSR-4 autoloading structure
- Comprehensive inline documentation
- Type hints where applicable (PHP 7.4+)

## 🚨 Important Notes

### Growth Plan Limitations

**No Native SSO**: The Growth plan does not support OIDC/JWT SSO. This plugin provides the best possible experience without true SSO by:
- Auto-enrolling users
- Providing clear login instructions
- Tracking login status
- Creating a unified dashboard

**Course Listing**: The New Course Builder may make REST API course listing incomplete or unavailable. The plugin is designed to work with manual mapping, so this limitation doesn't affect core functionality.

### Rate Limiting

Thinkific Growth plan limits API requests to **120 per minute**. This plugin:
- Caches API responses
- Implements backoff on 429 errors
- Avoids unnecessary API calls
- Provides admin warnings if rate limited

### Best Practices

1. **Always test** API connection after setup
2. **Start with one mapping** to verify workflow
3. **Monitor logs** during initial rollout
4. **Set appropriate cache durations** based on your needs
5. **Educate customers** about using the same email

## 📞 Support

For issues, questions, or contributions:
- Check the **Logs** page for diagnostic information
- Review this README for troubleshooting tips
- Ensure you're using compatible versions

## 📄 License

GPL v2 or later

## 🎯 Roadmap

Potential future enhancements:
- Bulk enrollment processor
- Course progress tracking (if API supports it)
- Email notifications for enrollment status
- Admin dashboard widget with enrollment stats
- Import/export mappings
- Webhook support for real-time updates

---

**Version**: 1.0.0  
**Author**: Your Name  
**Requires WordPress**: 5.8+  
**Requires PHP**: 7.4+  
**Tested up to WordPress**: 6.4  
**WooCommerce**: 5.0+
