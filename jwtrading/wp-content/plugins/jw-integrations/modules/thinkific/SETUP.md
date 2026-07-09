# Setup Guide - Thinkific WooCommerce Integration

## Quick Start (5 Minutes)

### Step 1: Install Plugin
1. Upload plugin folder to `/wp-content/plugins/`
2. Activate in WordPress admin
3. Ensure WooCommerce is active

### Step 2: Get Thinkific API Credentials

#### Finding Your API Key
1. Log into your Thinkific admin
2. Go to **Settings** > **Code & Analytics**
3. Click on **API & Webhooks** tab
4. Click **Create New API Key**
5. Give it a name (e.g., "WordPress Integration")
6. Copy the API key (save it securely!)

#### Finding Your Subdomain
Your subdomain is the part before `.thinkific.com` in your school URL.

Example: If your school is `awesomeschool.thinkific.com`, your subdomain is `awesomeschool`

### Step 3: Configure Plugin

1. In WordPress admin, go to **Thinkific** > **Settings**
2. Enter your **Subdomain**
3. Enter your **API Key**
4. Click **Test Connection**
5. If successful, click **Save Settings**

### Step 4: Create Your First Mapping

#### Option A: Try Syncing (May Not Work with New Course Builder)
1. Go to **Thinkific** > **Course Mapping**
2. Click **Sync Courses from Thinkific**
3. If successful, note the course IDs

#### Option B: Manual Mapping (Always Works)
1. Go to **Thinkific** > **Course Mapping**
2. Click **Add New Mapping**
3. Fill in the form:
   - **Product**: Select your WooCommerce product
   - **Course Name**: "WordPress Basics" (or your course name)
   - **Course URL**: `https://yourschool.thinkific.com/courses/wordpress-basics`
   - **Course ID**: (Optional) The numeric ID from Thinkific
4. Click **Save Mapping**

#### Getting Course URL and ID

**Course URL:**
1. Log into Thinkific admin
2. Go to **Manage Learning Content**
3. Click on your course
4. Look at the browser address bar
5. The URL format is: `https://yourschool.thinkific.com/courses/course-slug`

**Course ID:**
1. In Thinkific admin, click on your course
2. Look at the browser URL: `https://yourschool.thinkific.com/manage/courses/123456/...`
3. The number `123456` is your course ID

### Step 5: Create Dashboard Page

1. Go to **Pages** > **Add New**
2. Title: "My Courses" (or whatever you prefer)
3. Add this shortcode to the page:
   ```
   [thinkific_dashboard]
   ```
4. Publish the page
5. Note the page URL (you may want to add this to your menu)

### Step 6: Test the Flow

1. **Create a test order**:
   - Use WooCommerce > Orders > Add Order
   - Add your mapped product
   - Use a real email address
   - Set status to "Processing"

2. **Check enrollment**:
   - Scroll down to **Thinkific Enrollments** meta box
   - Should show "Enrolled" status
   - If failed, click **Retry** and check **Logs**

3. **Test student view**:
   - Log in as a customer (or the user from the test order)
   - Visit your "My Courses" page
   - Course should appear with **Continue Course** button
   - Click it to launch Thinkific

## Advanced Configuration

### Enrollment Trigger Statuses

By default, enrollment happens when order status is:
- Processing
- Completed

To change this:
1. Go to **Thinkific** > **Settings**
2. Scroll to **WooCommerce Settings**
3. Select desired statuses (hold Ctrl/Cmd for multiple)
4. Save settings

**Recommended**: Keep "Processing" and "Completed" for paid orders.

### Cache Configuration

**Default settings work well for most sites**, but you can adjust:

**Course Cache (24 hours)**:
- Longer = fewer API calls
- Shorter = more up-to-date course data
- Recommended: 86400 (24 hours)

**Enrollment Cache (10 minutes)**:
- Longer = fewer API calls when viewing dashboard
- Shorter = more accurate enrollment status
- Recommended: 600 (10 minutes)

### Cart Optimization

**Force Single Quantity**:
- Prevents buying multiple of the same course
- Enable for course products

**Skip Cart**:
- Redirects directly to checkout
- Good for single-course purchases
- May confuse customers buying multiple items

### Logging

**Enable Logging**: Keep this ON during initial setup for troubleshooting.

You can disable later if everything works perfectly, but it's recommended to keep it enabled for monitoring.

## Testing Checklist

### ✅ Pre-Launch Tests

- [ ] API connection test passes
- [ ] At least one course mapping exists
- [ ] Test order with mapped product enrolls successfully
- [ ] Dashboard page displays correctly
- [ ] Logged-out users see login prompt
- [ ] "Continue Course" button links correctly
- [ ] First-time helper text displays
- [ ] Enrollment appears in order meta box

### ✅ Live Test with Real Customer

- [ ] Customer completes real purchase
- [ ] Order status triggers enrollment
- [ ] Customer receives Thinkific welcome email
- [ ] Customer can access dashboard
- [ ] Customer can launch course
- [ ] No error messages in logs

## Common Setup Issues

### "Connection failed"

**Problem**: API credentials incorrect or insufficient permissions

**Solution**:
1. Double-check subdomain (no .thinkific.com)
2. Regenerate API key in Thinkific
3. Ensure API key has required permissions
4. Check for typos in subdomain/key

### "Course sync returns empty"

**Problem**: New Course Builder limitation

**Solution**:
- This is expected and normal
- Use manual mapping instead
- Plugin works perfectly without sync

### "Product not enrolling"

**Problem**: Mapping missing or order status not triggering

**Solution**:
1. Verify mapping exists for the product
2. Check WooCommerce settings for trigger statuses
3. Look at order meta box for enrollment status
4. Check logs for specific error
5. Try "Retry" button in order meta box

### "Rate limit errors"

**Problem**: Too many API calls too quickly

**Solution**:
1. Increase cache durations
2. Wait 60 seconds (plugin auto-pauses)
3. Avoid manual enrollment retries in rapid succession
4. Check for webhook loops or duplicate processing

## Database Schema

The plugin creates two tables on activation:

### `wp_thinkific_course_mappings`
```sql
CREATE TABLE wp_thinkific_course_mappings (
  id bigint(20) UNSIGNED AUTO_INCREMENT,
  woo_product_id bigint(20) UNSIGNED NOT NULL,
  course_name varchar(255) NOT NULL,
  course_url varchar(500) NOT NULL,
  course_id varchar(100) DEFAULT NULL,
  course_description text DEFAULT NULL,
  created_at datetime DEFAULT CURRENT_TIMESTAMP,
  updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY woo_product_id (woo_product_id),
  KEY course_id (course_id)
);
```

### `wp_thinkific_enrollments`
```sql
CREATE TABLE wp_thinkific_enrollments (
  id bigint(20) UNSIGNED AUTO_INCREMENT,
  order_id bigint(20) UNSIGNED NOT NULL,
  user_id bigint(20) UNSIGNED NOT NULL,
  product_id bigint(20) UNSIGNED NOT NULL,
  course_id varchar(100) NOT NULL,
  thinkific_user_id varchar(100) DEFAULT NULL,
  status varchar(50) NOT NULL DEFAULT 'pending',
  error_message text DEFAULT NULL,
  retry_count int(11) DEFAULT 0,
  enrolled_at datetime DEFAULT NULL,
  created_at datetime DEFAULT CURRENT_TIMESTAMP,
  updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY order_id (order_id),
  KEY user_id (user_id),
  KEY status (status),
  KEY course_id (course_id),
  UNIQUE KEY unique_enrollment (user_id, course_id)
);
```

## Security Checklist

- [ ] API key stored securely (not in version control)
- [ ] Test with non-admin user to verify permissions
- [ ] Dashboard only shows user's own courses
- [ ] AJAX requests properly nonce-protected
- [ ] No sensitive data in frontend JavaScript
- [ ] SQL queries use prepared statements
- [ ] Input sanitization on all form fields

## Performance Tips

1. **Use Object Caching**: If you have Redis/Memcached, WordPress will use it automatically
2. **Set Appropriate Cache Durations**: Balance freshness vs. API calls
3. **Avoid Cart Page Overhead**: Enable "Skip Cart" for course products
4. **Monitor Logs**: Watch for excessive API calls

## Next Steps

1. **Set up email notifications** (optional): Customize WooCommerce order emails to mention course access
2. **Add to navigation**: Add your dashboard page to the user account menu
3. **Create landing pages**: Build marketing pages for your courses
4. **Monitor logs**: Check weekly for any issues
5. **Gather feedback**: Ask first customers about their experience

## Support Resources

- **Plugin Logs**: Thinkific > Logs
- **Order Meta Box**: View enrollment status per order
- **WooCommerce Logs**: WooCommerce > Status > Logs
- **PHP Error Log**: Check server error logs for critical issues

---

**Setup Complete!** 🎉

Your plugin is now configured and ready to automatically enroll customers in Thinkific courses when they purchase from your WooCommerce store.
