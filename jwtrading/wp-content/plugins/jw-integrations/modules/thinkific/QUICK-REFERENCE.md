# Quick Reference Card - Thinkific WooCommerce Integration

## Admin Quick Start (2 Minutes)

### 1. Configure API
```
WordPress Admin → Thinkific → Settings
- Subdomain: yourschool
- API Key: [from Thinkific]
- Click "Test Connection"
- Save Settings
```

### 2. Add Mapping
```
Thinkific → Course Mapping → Add New Mapping
- Product: Select WooCommerce product
- Course Name: Display name
- Course URL: https://yourschool.thinkific.com/courses/slug
- Course ID: (optional)
- Save
```

### 3. Create Dashboard
```
Pages → Add New
- Title: "My Courses"
- Content: [thinkific_dashboard]
- Publish
```

## Shortcode

```
[thinkific_dashboard]
```

**Attributes:**
- `title` - Dashboard heading
- `show_description` - yes/no

**Example:**
```
[thinkific_dashboard title="My Learning Path" show_description="no"]
```

## Common Admin Tasks

### Test API Connection
```
Thinkific → Settings → Test Connection button
```

### View Logs
```
Thinkific → Logs
```

### Clear Logs
```
Thinkific → Logs → Clear Logs button
```

### Retry Failed Enrollment
```
WooCommerce → Orders → [Order] → Thinkific Enrollments meta box → Retry button
```

### Clear Cache Manually
```php
// Add to functions.php temporarily
thinkific_wp_clear_all_caches();
```

### Check User's Courses
```php
$courses = thinkific_wp_get_user_courses($user_id);
```

## Troubleshooting Commands

### Check if Product is Mapped
```php
$is_mapped = thinkific_wp_is_product_mapped($product_id);
```

### Get Mapping for Product
```php
$mapping = thinkific_wp_get_product_course_mapping($product_id);
```

### Check if User Has Course Access
```php
$has_access = thinkific_wp_user_has_course_access($user_id, $course_id);
```

## File Locations

### Plugin Files
```
/wp-content/plugins/thinkific-wp-integration/
```

### Main Bootstrap
```
thinkific-wp-integration.php
```

### Admin Pages
```
includes/class-admin.php
includes/class-settings.php
includes/class-mappings.php
```

### API Client
```
includes/class-thinkific-client.php
```

### Dashboard
```
includes/class-dashboard.php
```

## Database Tables

### Course Mappings
```sql
SELECT * FROM wp_thinkific_course_mappings;
```

### Enrollments
```sql
SELECT * FROM wp_thinkific_enrollments WHERE status = 'failed';
```

### Clear Failed Enrollments (Old)
```sql
DELETE FROM wp_thinkific_enrollments 
WHERE status = 'failed' 
AND updated_at < DATE_SUB(NOW(), INTERVAL 90 DAY);
```

## WordPress Admin Paths

- **Settings**: `Thinkific → Settings`
- **Course Mapping**: `Thinkific → Course Mapping`
- **Logs**: `Thinkific → Logs`
- **Order Enrollments**: `WooCommerce → Orders → [Order] → Thinkific Enrollments box`

## Common Issues & Quick Fixes

### "Connection failed"
```
1. Check subdomain (no .thinkific.com)
2. Verify API key in Thinkific
3. Test Connection button
4. Check logs
```

### "Course sync returns empty"
```
Normal for New Course Builder
Use manual mapping instead
```

### "Enrollment failed"
```
1. Check logs for error
2. Verify course ID
3. Click Retry in order meta box
4. Check customer email is valid
```

### "Rate limit exceeded"
```
Wait 60 seconds (auto-locked)
Check cache settings
Reduce manual retries
```

## Default Settings

### Cache Durations
- **Courses**: 86400 seconds (24 hours)
- **Enrollments**: 600 seconds (10 minutes)

### Enrollment Triggers
- **Processing** ✓
- **Completed** ✓

### API Base URL
```
https://api.thinkific.com/api/public/v1
```

## API Rate Limits

- **Limit**: 120 requests/minute
- **Handling**: Auto-backoff for 60 seconds on 429
- **Caching**: Reduces API calls

## Security Notes

- API key stored in database (not code)
- All AJAX requests nonce-protected
- Capability checks on admin actions
- Prepared SQL statements
- Input sanitization

## Useful Filters

### Modify Dashboard Courses
```php
add_filter('thinkific_wp_dashboard_courses', function($courses, $user_id) {
    // Modify $courses
    return $courses;
}, 10, 2);
```

### Modify Course Data
```php
add_filter('thinkific_wp_course_data', function($course) {
    // Modify $course
    return $course;
});
```

## Useful Actions

### Before Enrollment
```php
add_action('thinkific_wp_before_enrollment', function($order_id, $course_id) {
    // Custom actions
}, 10, 2);
```

### After Successful Enrollment
```php
add_action('thinkific_wp_enrollment_success', function($order_id, $course_id, $user_id) {
    // Custom actions
}, 10, 3);
```

## Support Checklist

When asking for help, provide:
- [ ] Plugin version
- [ ] WordPress version
- [ ] WooCommerce version
- [ ] PHP version
- [ ] Thinkific plan (Growth)
- [ ] Error message from logs
- [ ] Steps to reproduce

## Emergency Procedures

### Disable Plugin Temporarily
```
Plugins → Deactivate "Thinkific WooCommerce Integration"
```

### Force Reinstall Database Tables
```php
// In functions.php temporarily
require_once(WP_PLUGIN_DIR . '/thinkific-wp-integration/includes/class-db.php');
Thinkific_WP_DB::create_tables();
```

### Clear All Plugin Data (CAUTION)
```sql
-- Backup first!
DELETE FROM wp_options WHERE option_name LIKE 'thinkific_wp_%';
DROP TABLE wp_thinkific_course_mappings;
DROP TABLE wp_thinkific_enrollments;
```

## Performance Tips

1. Use object caching (Redis/Memcached)
2. Keep default cache durations
3. Enable "Skip Cart" for course products
4. Monitor logs weekly
5. Clear old logs monthly

## Best Practices

1. ✅ Test API connection after setup
2. ✅ Start with one mapping to test
3. ✅ Keep logging enabled
4. ✅ Monitor first few orders
5. ✅ Add dashboard to user menu
6. ✅ Educate customers about email matching

## Quick Links

- [Full Documentation](README.md)
- [Setup Guide](SETUP.md)
- [Technical Notes](TECHNICAL-NOTES.md)
- [Database Schema](schema.sql)

---

**Keep this card handy for quick reference!**
