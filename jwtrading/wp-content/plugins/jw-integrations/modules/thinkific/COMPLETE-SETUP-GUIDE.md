# Complete Setup Guide - Seamless Course Access Experience

## 🎯 Goal: What Your Customers Will Experience

1. Customer buys a course on your WordPress/WooCommerce site
2. Order is automatically processed
3. Customer is automatically enrolled in Thinkific
4. Customer logs into WordPress
5. Customer sees "My Courses" dashboard
6. Customer clicks "Continue Course" → Opens Thinkific
7. If first time: Uses same email to login to Thinkific
8. After first login: Seamless access every time

---

## 📋 Complete Setup Checklist

- [x] ✅ API Connection successful
- [ ] Create WooCommerce products for courses
- [ ] Map products to Thinkific courses
- [ ] Create "My Courses" dashboard page
- [ ] Test the complete flow
- [ ] Configure order statuses (optional)
- [ ] Setup email notifications (optional)

---

# Step 1: Create WooCommerce Products

**First, create WooCommerce products that represent your Thinkific courses.**

## 1.1 Go to WooCommerce Products

```
WordPress Admin → Products → Add New
```

## 1.2 Create a Product for Each Course

**Example: "WordPress Fundamentals Course"**

### Product Details:
```
Product Name: WordPress Fundamentals Course
Price: $99 (or your price)
Product Type: Simple Product (or Virtual Product)
SKU: course-wp-fundamentals (optional)
Description: Learn WordPress from scratch...
```

### Important Settings:
```
✅ Virtual: YES (no shipping needed)
✅ Downloadable: NO (it's not a download, it's course access)
✅ Sold Individually: YES (recommended - prevents buying multiple)
```

### Save the Product

Click **"Publish"** and note the **Product ID** (you'll see it in the URL):
```
Example URL: /wp-admin/post.php?post=123&action=edit
Product ID: 123
```

## 1.3 Repeat for Each Course

Create one WooCommerce product for each Thinkific course you want to sell.

---

# Step 2: Get Thinkific Course Information

**You need the course URL from Thinkific for each course.**

## 2.1 Log into Thinkific Admin

```
https://jw-ict-bootcamp.thinkific.com/manage
```

## 2.2 Go to Manage Learning Content

```
Manage Learning Content → Courses
```

## 2.3 For Each Course, Get:

### A) Course Name
```
Example: "WordPress Fundamentals"
```

### B) Course URL (REQUIRED)

**IMPORTANT:** Use the course PLAYER URL, not the product/sales URL!

**Option 1: Direct Course Player URL** (Recommended)
```
Format: https://[subdomain].thinkific.com/courses/take/[course-slug]

Example: https://jw-ict-bootcamp.thinkific.com/courses/take/smart-money-trading-bootcamp
```

**Option 2: Enrollments Dashboard** (Universal)
```
Format: https://[subdomain].thinkific.com/enrollments

Example: https://jw-ict-bootcamp.thinkific.com/enrollments
```
This shows all enrolled courses. Use this URL for ALL mappings if you want a single dashboard.

**Option 3: Test as Enrolled User**
1. Log into Thinkific as enrolled student
2. Go to your enrollments: `https://jw-ict-bootcamp.thinkific.com/enrollments`
3. Click on the course
4. Copy the URL from browser (should be `/courses/...` or `/courses/take/...`)

**❌ AVOID:** URLs with `/products/courses/...` - These are sales pages and will show "Buy" button even for enrolled users!

### C) Course ID (Optional but Helpful)

**From the Admin URL:**
When you're editing a course in Thinkific admin, look at the URL:
```
Example: https://jw-ict-bootcamp.thinkific.com/manage/courses/123456/...
Course ID: 123456
```

### Write Down Your Course Info:

```
Course 1:
  Name: WordPress Fundamentals
  URL: https://jw-ict-bootcamp.thinkific.com/courses/wordpress-fundamentals
  ID: 123456
  WooCommerce Product ID: 123

Course 2:
  Name: Advanced WordPress Development
  URL: https://jw-ict-bootcamp.thinkific.com/courses/advanced-wp
  ID: 123457
  WooCommerce Product ID: 124
```

---

# Step 3: Map Products to Courses

**Now connect your WooCommerce products to Thinkific courses.**

## 3.1 Go to Course Mapping

```
WordPress Admin → Thinkific → Course Mapping
```

## 3.2 Try "Sync Courses" (Optional - May Not Work)

Click **"Sync Courses from Thinkific"**

### If It Works:
- ✅ You'll see a list of courses with IDs
- Copy the course IDs to use in mappings

### If It Doesn't Work:
- ⚠️ You'll see: "No courses found"
- This is normal with New Course Builder
- Don't worry - manual mapping still works perfectly!

## 3.3 Add Manual Mapping

Click **"Add New Mapping"** button

### Fill in the Form:

```
┌─────────────────────────────────────────────────────┐
│ Add/Edit Course Mapping                             │
├─────────────────────────────────────────────────────┤
│                                                     │
│ WooCommerce Product: *                              │
│ [Dropdown: Select a product...]                     │
│ → Select: "WordPress Fundamentals Course"           │
│                                                     │
│ Course Name: *                                      │
│ [WordPress Fundamentals                 ]           │
│                                                     │
│ Course URL: *                                       │
│ [https://jw-ict-bootcamp.thinkific.com/courses/...] │
│                                                     │
│ Course ID (Optional):                               │
│ [123456                                 ]           │
│                                                     │
│ Description (Optional):                             │
│ [Learn WordPress from scratch. Perfect for          │
│  beginners who want to build websites.  ]           │
│                                                     │
│ [Save Mapping]  [Cancel]                            │
└─────────────────────────────────────────────────────┘
```

### Field Explanations:

**WooCommerce Product:** (REQUIRED)
- Select the product you created
- This is what customers will buy

**Course Name:** (REQUIRED)
- Display name shown in dashboard
- Can be different from product name
- Example: "WordPress Fundamentals"

**Course URL:** (REQUIRED)
- The full URL to access the course on Thinkific
- This is where "Continue Course" button goes
- Must start with https://
- Example: `https://jw-ict-bootcamp.thinkific.com/courses/wordpress-fundamentals`

**Course ID:** (Optional)
- Thinkific's numeric course ID
- Used for API enrollment
- If you don't have it, enrollment still works via email
- Example: `123456`

**Description:** (Optional)
- Shown on the student dashboard
- Brief course description
- Example: "Learn WordPress from scratch..."

## 3.4 Save Mapping

Click **"Save Mapping"**

You should see success message and the mapping appears in the table below.

## 3.5 Repeat for Each Course

Create one mapping for each course you want to sell.

### Your Mapping Table Will Look Like:

```
┌──────────────────┬─────────────────────┬────────────────────────┬──────────┬─────────┐
│ Product          │ Course Name         │ Course URL             │ Course ID│ Actions │
├──────────────────┼─────────────────────┼────────────────────────┼──────────┼─────────┤
│ WordPress Fund...│ WordPress Fund...   │ https://jw-ict-boo...  │ 123456   │ Delete  │
│ (ID: 123)        │                     │                        │          │         │
├──────────────────┼─────────────────────┼────────────────────────┼──────────┼─────────┤
│ Advanced WP (124)│ Advanced WordPress  │ https://jw-ict-boo...  │ 123457   │ Delete  │
└──────────────────┴─────────────────────┴────────────────────────┴──────────┴─────────┘
```

---

# Step 4: Create "My Courses" Dashboard Page

**Create a page where students can access their courses.**

## 4.1 Create New Page

```
WordPress Admin → Pages → Add New
```

## 4.2 Page Setup

### Page Details:
```
Title: My Courses
Permalink: /my-courses/ (or /dashboard/ or /student-portal/)
```

### Page Content:
Add the shortcode:
```
[thinkific_dashboard]
```

That's it! Just the shortcode.

### Optional: Add Custom Title
```
[thinkific_dashboard title="My Learning Dashboard"]
```

### Optional: Hide Descriptions
```
[thinkific_dashboard show_description="no"]
```

## 4.3 Page Settings

### Template:
- Use default template or "Full Width" if available

### Parent Page (Optional):
- Can be under "My Account" if you want

## 4.4 Publish Page

Click **"Publish"**

Copy the page URL:
```
Example: https://yoursite.com/my-courses/
```

## 4.5 Add to Menu (Recommended)

```
Appearance → Menus
```

Add "My Courses" page to your:
- Main menu (for all users)
- My Account menu (for logged-in users only)

### Make it Show Only When Logged In:
If using a menu plugin like Max Mega Menu or custom code:
```php
// Only show for logged-in users
if (is_user_logged_in()) {
    // Show menu item
}
```

---

# Step 5: Configure Order Processing (Optional)

**Control when enrollment happens.**

## 5.1 Go to Settings

```
WordPress Admin → Thinkific → Settings
```

## 5.2 Scroll to "WooCommerce Settings"

### Enrollment Trigger Statuses

**Default:** Processing + Completed

This means enrollment happens when order status is:
- ✅ Processing (after payment is received)
- ✅ Completed (when order is fulfilled)

### Change if Needed:

**Option A: Only Completed**
- Select only "Completed"
- Enrollment happens only after you manually complete order
- Good if you want manual control

**Option B: Processing + Completed** (Recommended)
- Keep default
- Enrollment happens immediately after payment
- Best for automatic workflow

## 5.3 Other WooCommerce Options

### Force Single Quantity
```
☑ Force mapped products to sell as single quantity only
```
- Prevents buying multiple of same course
- Recommended: ON

### Skip Cart
```
☐ Skip cart and redirect directly to checkout
```
- Sends customer straight to checkout
- Good for single-course sales
- Optional: Your choice

---

# Step 6: Test Complete Flow

**Test everything before going live!**

## 6.1 Create Test Order

### Option A: Manual Order (Easier)
```
WooCommerce → Orders → Add Order
```

1. Click "Add Order"
2. Add customer (yourself or test email)
3. Add your course product
4. Set status to "Processing"
5. Save order

### Option B: Real Purchase (Complete Test)
1. Open your site in incognito/private window
2. Add course to cart
3. Go through checkout
4. Use test payment (if test mode) or real payment

## 6.2 Check Enrollment Happened

### A) Check Order Meta Box
```
WooCommerce → Orders → [Your Test Order]
```

Scroll down to **"Thinkific Enrollments"** meta box:

```
┌─────────────────────────────────────────┐
│ Thinkific Enrollments                   │
├─────────────────────────────────────────┤
│ Course              │ Status            │
├─────────────────────┼───────────────────┤
│ WordPress Fund...   │ ✓ Enrolled        │
└─────────────────────┴───────────────────┘
│ Last processed: 2026-02-17 01:50:00     │
└─────────────────────────────────────────┘
```

### B) Check Logs
```
Thinkific → Logs
```

Look for entries like:
```
[INFO] Enrollment successful
Context:
  - order_id: 123
  - course_id: 123456
  - user_id: 1
```

### C) Check Thinkific Admin
```
Thinkific Admin → Users → [Customer Email]
```

Should show enrollment in the course.

## 6.3 Test Customer Dashboard

### As the Customer:
1. Log into WordPress with customer account
2. Go to the "My Courses" page
3. Should see the course card

```
┌─────────────────────────────────────────┐
│ WordPress Fundamentals                  │
│                                         │
│ Learn WordPress from scratch. Perfect   │
│ for beginners who want to build sites.  │
│                                         │
│ [Continue Course]                       │
│                                         │
│ ℹ️ If prompted to login to Thinkific,  │
│    use the same email you used at       │
│    checkout.                            │
└─────────────────────────────────────────┘
```

## 6.4 Test Thinkific Access

1. Click **"Continue Course"**
2. Opens Thinkific course page
3. If first time: Thinkific asks for login
4. Customer logs in with **same email** used at checkout
5. Customer can set Thinkific password
6. Course loads!

---

# Step 7: Customer Experience (Detailed)

**What your customers will actually experience.**

## 7.1 Purchase Flow

### Step 1: Customer Browses Your Site
```
Customer visits: https://yoursite.com
Sees course: "WordPress Fundamentals - $99"
Clicks: "Add to Cart" or "Buy Now"
```

### Step 2: Checkout
```
Customer goes to checkout
Fills in:
  - Email: customer@example.com ← IMPORTANT: Remember this!
  - Billing info
  - Payment details
Completes purchase
```

### Step 3: Order Confirmation
```
Customer sees: "Thank you for your order!"
Email received: "Order Confirmation"
```

### Behind the Scenes (Automatic):
```
✅ Order status → Processing
✅ Plugin detects order
✅ Plugin calls Thinkific API
✅ Creates/finds user in Thinkific (email: customer@example.com)
✅ Enrolls user in course
✅ Logs success
```

## 7.2 First Time Accessing Course

### Step 1: Customer Logs into WordPress
```
Customer goes to: https://yoursite.com/my-account/
Logs in with WordPress credentials
```

### Step 2: Customer Visits Dashboard
```
Customer goes to: https://yoursite.com/my-courses/
or clicks "My Courses" in menu
```

### Step 3: Sees Course Dashboard
```
┌──────────────────────────────────────────────┐
│ My Courses                                   │
├──────────────────────────────────────────────┤
│                                              │
│ ┌──────────────────────────────────────┐    │
│ │ WordPress Fundamentals               │    │
│ │                                      │    │
│ │ Learn WordPress from scratch...      │    │
│ │                                      │    │
│ │ [Continue Course]                    │    │
│ │                                      │    │
│ │ ℹ️ If prompted to login to Thinkific,│    │
│ │    use the same email you used at    │    │
│ │    checkout.                         │    │
│ └──────────────────────────────────────┘    │
│                                              │
└──────────────────────────────────────────────┘
```

### Step 4: Clicks "Continue Course"
```
Button opens: https://jw-ict-bootcamp.thinkific.com/courses/wordpress-fundamentals
Opens in same tab (seamless feeling)
```

### Step 5: Thinkific Login (First Time Only)
```
Thinkific shows:
┌─────────────────────────────────────┐
│ Welcome! Please log in to continue  │
│                                     │
│ Email: [customer@example.com    ]   │
│ Password: [                    ]    │
│                                     │
│ [Log In] or [Create Account]       │
└─────────────────────────────────────┘

Customer enters:
- Email: customer@example.com (same as checkout!)
- Creates password (if first time)
- Logs in
```

### Step 6: Course Loads!
```
✅ Customer is in the course
✅ Can start learning
✅ Progress is tracked
```

## 7.3 Subsequent Visits (Seamless!)

### Next Time Customer Visits:
```
1. Logs into WordPress
2. Goes to "My Courses"
3. Clicks "Continue Course"
4. Thinkific opens
5. Already logged in (cookies) - NO LOGIN PROMPT!
6. Course loads immediately
```

**This is the "seamless feeling" - after first login, it's instant!**

---

# Step 8: Make It More Seamless (Optional Enhancements)

## 8.1 Custom Dashboard Page Design

Add custom content above/below shortcode:

```html
<h1>Welcome to Your Learning Dashboard</h1>
<p>Access all your courses below. Click "Continue Course" to start learning!</p>

[thinkific_dashboard]

<h2>Need Help?</h2>
<p>Contact us at support@yoursite.com</p>
```

## 8.2 Add to My Account Page

If using WooCommerce My Account:

```php
// Add to functions.php
add_filter('woocommerce_account_menu_items', 'add_my_courses_menu_item');
function add_my_courses_menu_item($items) {
    $items['my-courses'] = 'My Courses';
    return $items;
}

add_action('woocommerce_account_my-courses_endpoint', 'my_courses_content');
function my_courses_content() {
    echo do_shortcode('[thinkific_dashboard]');
}
```

## 8.3 Email Notifications

Customize WooCommerce order emails to mention course access:

```
WooCommerce → Settings → Emails
```

Edit "Processing Order" email template:

Add text like:
```
Your course access has been set up!

Visit your dashboard to start learning:
https://yoursite.com/my-courses/

Important: When accessing the course for the first time, 
use the same email address: {customer_email}
```

## 8.4 Welcome Email from Thinkific

Customers will receive a Thinkific welcome email automatically:
- Subject: "Welcome to [Your School]"
- Contains: Login link, password setup
- This is normal and expected

You can customize this in:
```
Thinkific Admin → Settings → Email Notifications
```

---

# Step 9: Troubleshooting Common Issues

## Issue 1: Enrollment Not Happening

**Check:**
```
1. Order status is "Processing" or "Completed"
2. Product has a mapping (Thinkific → Course Mapping)
3. Check Thinkific → Logs for errors
4. Check order meta box for enrollment status
```

**Fix:**
```
Order → Thinkific Enrollments box → Click "Retry"
```

## Issue 2: Customer Can't Log into Thinkific

**Why:**
- Used different email at checkout vs Thinkific login

**Solution:**
- Customer must use same email as checkout
- Add clear instructions on dashboard
- Send reminder email

## Issue 3: Course Not Showing on Dashboard

**Check:**
```
1. Customer has a paid order (Processing or Completed)
2. Order contains the mapped product
3. Customer is logged into WordPress
4. Dashboard page has [thinkific_dashboard] shortcode
```

## Issue 4: Dashboard Shows Login Prompt

**Why:**
- Customer is not logged into WordPress

**Solution:**
- Customer needs to log into WordPress first
- Add login link on dashboard page

---

# Step 10: Going Live Checklist

Before you launch to real customers:

## Pre-Launch:
- [ ] ✅ API connection working
- [ ] ✅ All course products created in WooCommerce
- [ ] ✅ All courses mapped (Thinkific → Course Mapping)
- [ ] ✅ "My Courses" dashboard page published
- [ ] ✅ Dashboard page added to menu
- [ ] ✅ Test purchase completed successfully
- [ ] ✅ Test enrollment verified in Thinkific
- [ ] ✅ Test customer can access dashboard
- [ ] ✅ Test customer can open course in Thinkific
- [ ] ✅ Order emails reviewed/customized
- [ ] ✅ Thinkific welcome email reviewed

## Post-Launch:
- [ ] Monitor first few orders (Thinkific → Logs)
- [ ] Check enrollments happening automatically
- [ ] Respond to customer questions quickly
- [ ] Gather feedback on UX

---

# Visual Summary

## The Complete Flow

```
STEP 1: PURCHASE
┌─────────────────────────┐
│  Customer buys course   │
│  on WordPress/WooCommerce│
└──────────┬──────────────┘
           │
           ▼
STEP 2: AUTO-ENROLLMENT (Behind the Scenes)
┌─────────────────────────┐
│  Order status changes   │
│  Plugin detects order   │
│  Calls Thinkific API    │
│  Creates user           │
│  Enrolls in course      │
└──────────┬──────────────┘
           │
           ▼
STEP 3: CUSTOMER DASHBOARD
┌─────────────────────────┐
│  Customer logs into WP  │
│  Visits "My Courses"    │
│  Sees course card       │
└──────────┬──────────────┘
           │
           ▼
STEP 4: COURSE ACCESS
┌─────────────────────────┐
│  Clicks "Continue Course"│
│  Opens Thinkific        │
│  Logs in (first time)   │
│  Learns! 🎓             │
└─────────────────────────┘
```

---

# Quick Reference Card

## Add New Course to System

1. **Create WooCommerce Product**
   - Products → Add New
   - Set price, make it Virtual
   - Publish, note Product ID

2. **Get Thinkific Course Info**
   - Course Name
   - Course URL (full)
   - Course ID (optional)

3. **Create Mapping**
   - Thinkific → Course Mapping
   - Add New Mapping
   - Fill in all fields
   - Save

4. **Test**
   - Create test order
   - Check enrollment
   - Verify dashboard shows course

## Customer Support Quick Answers

**Q: Where are my courses?**
→ Visit: https://yoursite.com/my-courses/ (logged in)

**Q: Can't access course in Thinkific**
→ Use the same email you used at checkout

**Q: Forgot Thinkific password**
→ Click "Forgot Password" on Thinkific login

**Q: Course not showing**
→ Make sure you're logged into WordPress
→ Order must be Processing or Completed status

---

**You're all set! 🎉 Your seamless course delivery system is ready!**
