# Modern Tabbed Dashboard Features

## Overview

The Thinkific WP Integration plugin features a professional, tabbed dashboard interface modeled after modern learning platforms like Udemy and LinkedIn Learning. The dashboard provides a clean, organized view of user information, courses, orders, and community features with intuitive tab-based navigation.

## Dashboard Layout

### Modern Tab-Based Interface

The dashboard features a professional navigation system with the following tabs:

1. **Overview** - Dashboard home with quick stats and recent activity
2. **My Courses** - Complete course catalog with enrollment status
3. **Orders** - Full purchase history with order details
4. **Community** - Discord and social integration

### Login Page

**Modern, Centered Login Design**

For non-logged-in users, displays a beautiful login card with:
- Welcome message and branding
- Multiple login options:
  - **Email Login** - Standard WordPress authentication
  - **Google Login** - Continue with Google (if configured)
  - **Discord Login** - Continue with Discord (if enabled)
- Register link for new users
- Clean, professional design matching modern SaaS applications

### Dashboard Header

- **Personalized Welcome** - Greets user by name
- **User Avatar** - Profile picture with elegant border
- **Gradient Background** - Eye-catching purple gradient
- **Subtitle** - Motivational message

### Tab Navigation

- **Sticky Navigation Bar** - Stays visible while scrolling
- **Icon + Text Labels** - Clear, intuitive navigation
- **Active Tab Indicator** - Visual highlight for current section
- **Course Count Badge** - Shows number of enrolled courses
- **Responsive Design** - Collapses to icons only on mobile

## Tab Details

### 1. Overview Tab

**Quick Stats Cards** (4 cards)
- **Enrolled Courses** - Total number of active enrollments
- **Completed** - Courses finished
- **Total Orders** - Purchase count
- **Thinkific Status** - Connection status indicator

**Continue Learning Section**
- Shows up to 3 most recent courses
- Compact card design with "Continue" buttons
- "View All" link to courses tab
- Empty state with "Browse Courses" CTA

**Account Info Sidebar**
- Email address
- Member since date
- Username
- Clean, organized layout

### 2. My Courses Tab

**Course Grid Display**
- Modern card design with hover effects
- Large, readable course cards
- Visual enrollment status badges:
  - ✓ **Enrolled** (Green) - Successfully enrolled
  - ⏱ **Pending** (Yellow) - Awaiting enrollment
- Course metadata:
  - Enrollment date
  - Course description
- Prominent "Continue Learning" button
- First-time login helper text (when needed)
- Empty state with shop link

### 3. Orders Tab

**Order History List**
- Chronological order display
- Each order card shows:
  - Order number (clickable link to WooCommerce details)
  - Order date
  - Status badge (Completed/Processing/Pending)
  - List of purchased items with icons
  - Order total in formatted currency
  - "View Details" button
- Empty state with shopping CTA

### 4. Community Tab

**Discord Integration Card**
- Large Discord icon
- Connection status badge
- Server description
- Join/Connect buttons
- Link to open Discord server
- Support for Discord OAuth plugins

## Tab Navigation

### How Tabs Work

The dashboard uses URL parameters to switch between tabs:
- `?tab=overview` - Overview tab (default)
- `?tab=courses` - My Courses tab
- `?tab=orders` - Orders tab
- `?tab=community` - Community tab

**Example URLs:**
```
https://yoursite.com/dashboard/
https://yoursite.com/dashboard/?tab=courses
https://yoursite.com/dashboard/?tab=orders
```

### Direct Links to Tabs

Create menu items or buttons that link directly to specific tabs:

```html
<!-- Link to courses -->
<a href="/dashboard/?tab=courses">View My Courses</a>

<!-- Link to orders -->
<a href="/dashboard/?tab=orders">Order History</a>

<!-- Link to community -->
<a href="/dashboard/?tab=community">Join Community</a>
```

### Tab Visibility Control

Control which tabs appear using shortcode parameters:

```php
<!-- Hide Orders tab -->
[thinkific_dashboard show_orders="no"]

<!-- Hide Community tab -->
[thinkific_dashboard show_discord="no"]

<!-- Show only Overview and Courses -->
[thinkific_dashboard show_orders="no" show_discord="no"]
```

## Shortcode Usage

### Basic Usage

```php
[thinkific_dashboard]
```

Displays full dashboard with all features enabled (Overview, Courses, Orders tabs visible).

### Show All Features

```php
[thinkific_dashboard show_orders="yes" show_discord="yes"]
```

Shows all 4 tabs: Overview, Courses, Orders, and Community.

### Courses Only

```php
[thinkific_dashboard show_orders="no" show_discord="no"]
```

Displays only Overview and My Courses tabs.

### Custom Configuration

```php
[thinkific_dashboard 
    show_description="yes" 
    show_orders="yes" 
    show_discord="yes"]
```

## Shortcode Parameters

| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `show_description` | yes/no | yes | Show course descriptions in course cards |
| `show_orders` | yes/no | yes | Show Orders tab in navigation |
| `show_discord` | yes/no | no | Show Community tab in navigation |
| `show_user_info` | yes/no | yes | Show user account info in overview (deprecated, always shown in Overview tab) |
| `title` | string | - | Not used in tabbed dashboard (deprecated) |

**Note:** The `title` and `show_user_info` parameters are deprecated in the new tabbed design but remain for backward compatibility.

## Setup Instructions

### Step 1: Add Discord Invite URL (Optional)

1. Go to **WordPress Admin → Thinkific → Settings**
2. Scroll to **Dashboard Settings** section
3. Enter your Discord server invite URL
4. Save changes

### Step 2: Create Dashboard Page

1. Create a new page: **Pages → Add New**
2. Title: "My Courses" or "Dashboard"
3. Add the shortcode:
   ```
   [thinkific_dashboard show_discord="yes"]
   ```
4. Publish the page

### Step 3: Add to Menu

1. Go to **Appearance → Menus**
2. Add your new dashboard page to the menu
3. Recommended: Place it in the primary navigation or user account menu

### Step 4: Customize Display

You can customize what information is displayed using shortcode parameters:

**Minimal Display** (courses only):
```
[thinkific_dashboard show_user_info="no" show_orders="no"]
```

**Full Dashboard** (everything):
```
[thinkific_dashboard show_user_info="yes" show_orders="yes" show_discord="yes"]
```

**Without Descriptions** (compact view):
```
[thinkific_dashboard show_description="no"]
```

## Social Login Integration

### Google Login Support

The dashboard automatically detects and integrates with Google login plugins.

**Automatic Detection:**
- Checks for `google_login_button()` function
- Uses `thinkific_google_login_url` filter hook
- Displays "Continue with Google" button with official branding

**To Enable Google Login:**

1. **Install a Google OAuth Plugin:**
   - **Nextend Social Login** (Recommended)
   - **Super Socializer**
   - **WP Social Login**
   - **Loginizer**

2. **Configure the Plugin:**
   - Get Google OAuth credentials from Google Cloud Console
   - Enter credentials in plugin settings
   - Enable Google login method
   - Test login functionality

3. **The Dashboard Will Automatically:**
   - Detect the integration
   - Show "Continue with Google" button
   - Use official Google colors and icon
   - Handle authentication redirects

**For Developers:**
To integrate your custom Google login, add this filter:

```php
add_filter('thinkific_google_login_url', function($url) {
    return 'your-google-oauth-url';
});
```

### Discord Integration

**Automatic Detection:**
- Checks for Discord plugin functions
- Uses `discord_login_button()` and `discord_connect()` functions
- Stores connection status in `_discord_user_id` user meta
- Displays Discord invite link from settings

**To Enable Discord Login:**

1. **Install a Discord Plugin:**
   - **WP Discord** (Recommended)
   - **Discord OAuth**
   - **Community by Discord**

2. **Configure Plugin:**
   - Create Discord Application
   - Get OAuth credentials
   - Configure redirect URLs
   - Enable login/connect features

3. **Configure Thinkific Plugin:**
   - Go to **WordPress Admin → Thinkific → Settings**
   - Find **Dashboard Settings** section
   - Enter Discord invite URL
   - Save changes

4. **Use Discord in Shortcode:**
   ```
   [thinkific_dashboard show_discord="yes"]
   ```

**For Developers:**
To integrate custom Discord functionality:

```php
add_filter('thinkific_discord_login_url', function($url) {
    return 'your-discord-oauth-url';
});
```

### Recommended Social Login Plugins

#### For Google:
- **Nextend Social Login** - Full featured, supports multiple providers
- **Super Socializer** - Lightweight, easy setup
- **WP Social Login** - Comprehensive social authentication

#### For Discord:
- **WP Discord** - Full Discord integration with roles
- **Discord OAuth** - Simple Discord login
- **Community by Discord** - Advanced community features

### Email Login

Always available by default. Uses WordPress core authentication:
- Standard login form
- Password recovery
- Remember me option
- Redirects back to dashboard after login

## Design & Styling

### Modern Design Principles

The dashboard follows contemporary web design standards:

- **Clean Typography** - System font stack for optimal readability
- **Generous Whitespace** - Comfortable spacing between elements
- **Subtle Shadows** - Depth without distraction
- **Smooth Transitions** - Professional hover and click effects
- **Gradient Accents** - Eye-catching color combinations
- **Consistent Iconography** - WordPress Dashicons throughout
- **Card-Based Layout** - Organized content containers
- **Status Colors** - Color-coded feedback (green=success, yellow=warning, etc.)

### Color Palette

**Primary Colors:**
- **Purple Gradient**: `#667eea` → `#764ba2` (headers, buttons, accents)
- **White**: `#ffffff` (cards, backgrounds)
- **Gray Scale**: `#f5f7fa` (page background), `#e1e4e8` (borders), `#586069` (text)

**Status Colors:**
- **Success**: `#d4edda` background, `#155724` text (enrolled, completed)
- **Warning**: `#fff3cd` background, `#856404` text (pending, alerts)
- **Info**: `#d1ecf1` background, `#0c5460` text (verified, information)
- **Error**: `#f8d7da` background, `#721c24` text (errors, disconnected)

**Gradient Presets:**
- **Primary**: `linear-gradient(135deg, #667eea 0%, #764ba2 100%)`
- **Success**: `linear-gradient(135deg, #11998e 0%, #38ef7d 100%)`
- **Danger**: `linear-gradient(135deg, #eb3349 0%, #f45c43 100%)`
- **Warm**: `linear-gradient(135deg, #ffd89b 0%, #19547b 100%)`

### Customization Options

#### Change Brand Colors

Add to your theme's `style.css` or custom CSS:

```css
/* Override primary gradient */
.thinkific-dashboard-header,
.thinkific-login-icon,
.thinkific-stat-icon-courses,
.thinkific-course-btn-launch {
    background: linear-gradient(135deg, #your-color-1 0%, #your-color-2 100%) !important;
}

/* Override tab active state */
.thinkific-tab-link.active {
    color: #your-brand-color !important;
    border-bottom-color: #your-brand-color !important;
}

/* Override stat card icons */
.thinkific-stat-card-icon {
    background: #your-brand-color !important;
}
```

#### Adjust Layout Spacing

```css
/* Wider dashboard */
.thinkific-dashboard-wrapper {
    max-width: 1400px !important;
}

/* Tighter card spacing */
.thinkific-stats-cards,
.thinkific-courses-grid-modern {
    gap: 16px !important;
}

/* Larger header */
.thinkific-dashboard-header {
    padding: 60px 40px !important;
}
```

#### Custom Typography

```css
/* Change font family */
.thinkific-dashboard-wrapper {
    font-family: 'Your Font', -apple-system, sans-serif !important;
}

/* Adjust heading sizes */
.thinkific-dashboard-user-welcome h1 {
    font-size: 36px !important;
}

.thinkific-tab-header h2 {
    font-size: 32px !important;
}
```

#### Border Radius

```css
/* More rounded corners */
.thinkific-card,
.thinkific-course-card-modern,
.thinkific-order-card,
.thinkific-stat-card {
    border-radius: 16px !important;
}

/* Square corners */
.thinkific-card,
.thinkific-course-card-modern {
    border-radius: 0 !important;
}
```

### Dark Mode Support

Add dark mode styles for users who prefer it:

```css
@media (prefers-color-scheme: dark) {
    .thinkific-dashboard-wrapper {
        background: #1a1a1a !important;
    }
    
    .thinkific-card,
    .thinkific-course-card-modern {
        background: #2d2d2d !important;
        color: #e1e1e1 !important;
    }
    
    .thinkific-course-card-title,
    .thinkific-tab-header h2 {
        color: #ffffff !important;
    }
}
```

## Responsive Design

The dashboard is built mobile-first and fully responsive across all devices:

### Desktop (>768px)
- **Full Tab Labels** - Text + icons in navigation
- **Multi-Column Layouts** - Grid displays for stats and courses
- **Horizontal Card Layouts** - Side-by-side information
- **Large Typography** - 32px headings, 16px body text
- **Hover Effects** - Interactive feedback on cards and buttons

### Tablet (768px)
- **Adaptive Grids** - Adjusts to 2-column or single-column layouts
- **Touch-Friendly** - Larger tap targets (44px minimum)
- **Readable Text** - Optimized font sizes
- **Stacked Content** - Vertical layouts for cards
- **Collapsed Sidebars** - Full-width content areas

### Mobile (<480px)
- **Icon-Only Tabs** - Compact navigation with icons and badges
- **Single Column** - All content stacks vertically
- **Touch Optimized** - Extra padding for fingers
- **Smaller Typography** - 20-24px headings
- **Simplified Cards** - Compact information display
- **Bottom Fixed Nav** - Optional sticky tab bar

### Optimization Features
- **Fast Loading** - Minimal CSS and JavaScript
- **No Page Reload** - Tab switching uses URL parameters only
- **Lazy Loading Ready** - Prepared for image lazy loading
- **Progressive Enhancement** - Works without JavaScript
- **Print Friendly** - Clean print styles included

## User Experience Flow

### For Non-Logged In Users

1. User visits dashboard page
2. Sees login prompt with:
   - Email login button
   - Discord login option (if enabled)
   - Registration link

### For Logged In Users

1. User sees personalized dashboard with their name
2. Views order summary and spending stats
3. Browses enrolled courses with clear status indicators
4. Clicks "Continue Course" to access Thinkific
5. Receives appropriate login guidance on first visit

### For First-Time Thinkific Users

1. User purchases course via WooCommerce
2. Sees course in dashboard with "Pending" or "Enrolled" status
3. Clicks "Continue Course"
4. Sees helper text: "Use the same email you used at checkout"
5. Logs into Thinkific with purchase email
6. Access granted automatically
7. Future visits: Direct access without login prompt

## Enrollment Status Explained

### ✓ Enrolled (Green)

- User is confirmed enrolled via Thinkific API
- Enrollment record exists in database
- Full course access granted

### ⟳ Verified (Blue)

- Enrollment verified through API
- Course access confirmed
- Enrollment in good standing

### ⏱ Pending (Yellow)

- Purchase completed in WooCommerce
- Enrollment request sent to Thinkific
- May require user to complete Thinkific login
- Status will update to "Enrolled" after first login

## Best Practices

### Dashboard Page Setup

1. **Create Dedicated Page:**
   - Title: "My Learning Dashboard" or "Student Dashboard"
   - Slug: `/dashboard/` or `/my-courses/`
   - Template: Full Width (no sidebar)

2. **Add to Main Menu:**
   - Place in primary navigation
   - Use custom icon if theme supports it
   - Make it visible only to logged-in users (optional)

3. **Set as Default After Login:**
   ```php
   add_filter('login_redirect', function($redirect_to, $request, $user) {
       return get_permalink(get_page_by_path('dashboard'));
   }, 10, 3);
   ```

### User Experience Tips

1. **Welcome Email** - Send new users an email with dashboard link
2. **First Purchase** - Redirect to dashboard after first course purchase
3. **Email Signature** - Include dashboard link in order confirmation emails
4. **Account Menu** - Add dashboard to user account dropdown
5. **Progress Tracking** - Consider adding completion tracking

### Performance Optimization

1. **Enable Caching:**
   - Use object caching (Redis/Memcached)
   - Enable transient caching for API calls
   - Set appropriate cache durations

2. **Optimize Images:**
   - Use WebP format for avatars
   - Lazy load course images
   - Compress profile pictures

3. **Database Optimization:**
   - Regular database maintenance
   - Index optimization
   - Query monitoring

## Troubleshooting

### Tabs Not Working

**Symptom:** Clicking tabs doesn't change content

**Causes & Solutions:**
1. **Permalink Conflict:**
   - Go to Settings → Permalinks
   - Click "Save Changes" to flush rewrite rules
   
2. **JavaScript Conflict:**
   - Disable other plugins temporarily
   - Check browser console for errors
   
3. **Theme Compatibility:**
   - Switch to a default theme temporarily
   - Contact theme developer

### Courses Not Showing

**Check:**
1. User has completed WooCommerce purchase
2. Order status is "Processing" or "Completed"
3. Product is properly mapped to Thinkific course
4. Course mapping table exists in database
5. Go to **My Courses tab** specifically (not just Overview)

### Enrollment Status Stuck on Pending

**Possible Causes:**
1. Thinkific API call failed
2. User hasn't logged into Thinkific yet
3. Email mismatch between WordPress and Thinkific
4. Network connectivity issues

**Solution Steps:**
1. Check **Admin → Thinkific → Logs** for error messages
2. Verify API credentials in Settings
3. Test API connection with "Test Connection" button
4. Re-trigger enrollment from WooCommerce order page
5. Verify enrollment directly in Thinkific admin

### Social Login Not Appearing

**Google Login:**
1. Install and activate a Google OAuth plugin
2. Configure with proper credentials
3. Test Google login works on wp-login.php
4. Clear browser cache and revisit dashboard

**Discord Login:**
1. Install Discord OAuth plugin
2. Set Discord invite URL in Thinkific settings
3. Use `show_discord="yes"` in shortcode
4. Verify plugin functions are available

### Orders Tab Empty

**Check:**
1. User has placed orders
2. Orders are assigned to correct user ID
3. WooCommerce is active and functioning
4. HPOS compatibility is enabled (WooCommerce 8.0+)

### Styling Issues

**If Dashboard Looks Broken:**
1. Clear all caches (WordPress, CDN, browser)
2. Verify `dashboard.css` is loading (check page source)
3. Check for CSS conflicts with theme
4. Increase CSS specificity if needed:
   ```css
   .thinkific-dashboard-wrapper .thinkific-card {
       /* your styles */
   }
   ```

### Mobile Display Problems

1. **Viewport Meta Tag:** Ensure theme has proper viewport meta tag
2. **Theme Override:** Check if theme CSS is overriding dashboard styles
3. **Testing:** Test on actual devices, not just browser resizing
4. **Touch Targets:** Ensure buttons are at least 44x44px

## Performance Optimization

The dashboard is optimized for performance:

- **Caching**: API responses cached per settings
- **Efficient Queries**: Minimal database calls
- **Lazy Loading**: Only loads data when needed
- **Pagination**: Limits recent orders to prevent slowdown

## Security Features

- **Nonce Protection**: All AJAX requests protected
- **User Verification**: Content only shown to logged-in users
- **Data Sanitization**: All output properly escaped
- **Access Control**: Users only see their own data

## Advanced Customization

### Adding Custom Tabs

Developers can add custom tabs using filters:

```php
// Add custom tab to navigation
add_filter('thinkific_dashboard_tabs', function($tabs) {
    $tabs['certificates'] = array(
        'label' => 'Certificates',
        'icon' => 'dashicons-awards',
        'callback' => 'render_certificates_tab'
    );
    return $tabs;
});

// Render custom tab content
function render_certificates_tab() {
    echo '<div class="thinkific-tab-content">';
    echo '<h2>My Certificates</h2>';
    // Your custom content here
    echo '</div>';
}
```

### Modifying Stat Cards

```php
// Customize overview stats
add_filter('thinkific_overview_stats', function($stats, $user_id) {
    $stats['custom'] = array(
        'label' => 'Study Hours',
        'value' => get_user_meta($user_id, 'study_hours', true),
        'icon' => 'dashicons-clock',
        'gradient' => 'linear-gradient(135deg, #f093fb 0%, #f5576c 100%)'
    );
    return $stats;
}, 10, 2);
```

### Custom Empty States

```php
// Modify "no courses" message
add_filter('thinkific_empty_courses_message', function($message) {
    return 'Start your journey today! Browse our curated courses.';
});
```

## Accessibility Features

The dashboard is built with accessibility in mind:

- **Keyboard Navigation** - All interactive elements accessible via keyboard
- **Focus Indicators** - Clear visual focus states
- **ARIA Labels** - Proper labeling for screen readers
- **Color Contrast** - WCAG AA compliant color ratios
- **Semantic HTML** - Proper heading hierarchy and landmarks
- **Alt Text** - Images include descriptive alt attributes
- **Skip Links** - Jump to main content option (theme dependent)

### Testing Accessibility

1. **Keyboard Test:** Navigate using only Tab, Enter, and Arrow keys
2. **Screen Reader:** Test with NVDA, JAWS, or VoiceOver
3. **Color Contrast:** Use browser DevTools to check contrast ratios
4. **Zoom Test:** Test at 200% zoom level

## Integration Examples

### WooCommerce My Account Integration

Add dashboard link to WooCommerce account menu:

```php
add_filter('woocommerce_account_menu_items', function($items) {
    $new_items = array();
    foreach ($items as $key => $item) {
        $new_items[$key] = $item;
        if ($key === 'dashboard') {
            $new_items['thinkific-dashboard'] = 'My Courses';
        }
    }
    return $new_items;
});

add_filter('woocommerce_get_endpoint_url', function($url, $endpoint) {
    if ($endpoint === 'thinkific-dashboard') {
        return get_permalink(get_page_by_path('dashboard'));
    }
    return $url;
}, 10, 2);
```

### BuddyPress/BuddyBoss Profile Tab

```php
// Add to bp-custom.php
function add_thinkific_profile_tab() {
    bp_core_new_nav_item(array(
        'name' => 'My Courses',
        'slug' => 'courses',
        'screen_function' => 'thinkific_profile_courses_screen',
        'position' => 50,
        'default_subnav_slug' => 'courses'
    ));
}
add_action('bp_setup_nav', 'add_thinkific_profile_tab');

function thinkific_profile_courses_screen() {
    add_action('bp_template_content', function() {
        echo do_shortcode('[thinkific_dashboard]');
    });
    bp_core_load_template('members/single/plugins');
}
```

### MemberPress Integration

Show courses based on membership level:

```php
add_filter('thinkific_user_courses', function($courses, $user_id) {
    $membership = get_user_meta($user_id, 'mepr-membership', true);
    // Filter courses based on membership
    return $courses;
}, 10, 2);
```

## Future Enhancements

Planned features for upcoming releases:

### Version 2.0
- **Course Progress Tracking** - Visual progress bars and completion percentages
- **Lesson Bookmarks** - Save your place in each course
- **Study Timer** - Track time spent learning
- **Course Notes** - Take notes while watching lessons

### Version 2.5
- **Certificates Display** - Showcase earned certificates
- **Learning Paths** - Guided course sequences
- **Achievements System** - Gamification badges and rewards
- **Study Streaks** - Daily login tracking

### Version 3.0
- **Mobile App** - Native iOS/Android apps
- **Live Chat Support** - Integrated help chat
- **AI Recommendations** - Personalized course suggestions
- **Social Features** - Follow other learners, share progress

## Support & Resources

### Getting Help

1. **Documentation:**
   - Read all `.md` files in plugin folder
   - Check INDEX.md for quick navigation
   - Review COMPLETE-SETUP-GUIDE.md

2. **Troubleshooting:**
   - Check plugin logs: **Admin → Thinkific → Logs**
   - Test API connection: **Admin → Thinkific → Settings → Test Connection**
   - Review WordPress debug.log file

3. **Community:**
   - Join Discord community (if configured)
   - WordPress support forums
   - GitHub issues (if open source)

### Reporting Bugs

When reporting issues, include:
- WordPress version
- PHP version
- WooCommerce version
- Plugin version
- Theme name and version
- List of active plugins
- Error messages from logs
- Steps to reproduce
- Screenshots if applicable

### Feature Requests

Submit feature requests with:
- Clear description of desired feature
- Use case / why it's needed
- Expected behavior
- Priority level (nice-to-have vs. critical)

---

## Version Information

**Current Version**: 2.0.0  
**Release Date**: February 2026  
**Minimum Requirements:**
- WordPress 5.8+
- WooCommerce 7.0+
- PHP 7.4+

**Compatibility:**
- WordPress 6.4+ ✓
- WooCommerce 8.0+ (with HPOS) ✓
- PHP 8.0+ ✓
- Gutenberg Block Editor ✓
- Classic Editor ✓
- Multisite ⚠️ (Limited testing)

**Browser Support:**
- Chrome/Edge (Chromium) - Last 2 versions
- Firefox - Last 2 versions
- Safari - Last 2 versions
- Mobile Safari (iOS) - Last 2 versions
- Chrome Mobile (Android) - Last 2 versions

---

**Documentation Last Updated**: February 17, 2026  
**Author**: Thinkific WP Integration Team  
**License**: GPL v2 or later
