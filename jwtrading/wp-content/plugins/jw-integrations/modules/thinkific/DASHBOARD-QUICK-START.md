# Dashboard Quick Start Guide

## 🚀 Get Your Dashboard Running in 5 Minutes

### Step 1: Create Dashboard Page (2 minutes)

1. Go to **Pages → Add New**
2. **Title**: "My Learning Dashboard"
3. **Content**: Add this shortcode:
   ```
   [thinkific_dashboard show_orders="yes"]
   ```
4. **Template**: Select "Full Width" (if your theme has it)
5. Click **Publish**

### Step 2: Add to Menu (1 minute)

1. Go to **Appearance → Menus**
2. Find your dashboard page in the Pages list
3. Add it to your Primary Menu
4. Position it where users can easily find it (top-level recommended)
5. **Save Menu**

### Step 3: Test It! (30 seconds)

1. **Log Out** of WordPress
2. Visit your dashboard page
3. You should see the modern login screen
4. **Log back in** - you'll see your dashboard tabs!

---

## 📱 Dashboard Features at a Glance

### For Students (What They See):

| Tab | What's Inside | Why It Matters |
|-----|---------------|----------------|
| **Overview** | Quick stats, recent courses, account info | Fast access to what matters |
| **My Courses** | All enrolled courses with status badges | Track learning progress |
| **Orders** | Purchase history and order details | Review past transactions |

### Login:

- Standard WordPress login - users click "Log In" to sign in

---

## 🎨 Visual Overview

### Login Prompt (when not logged in)
```
┌─────────────────────────────────────┐
│     [Book Icon]                     │
│   Welcome Back!                     │
│   Sign in to access your courses    │
│                                     │
│   ┌──────────────────────────────┐ │
│   │  👤  Log In                  │ │
│   └──────────────────────────────┘ │
│                                     │
│   Don't have an account? Sign up   │
└─────────────────────────────────────┘
```

### Dashboard Header
```
┌────────────────────────────────────────────────────┐
│  Welcome back, John! 👤                           │
│  Continue your learning journey                    │
└────────────────────────────────────────────────────┘
```

### Tab Navigation
```
┌────────────────────────────────────────────────────┐
│ 📊 Overview  📚 My Courses [3]  🛒 Orders  │
│ ═══════════                                        │
└────────────────────────────────────────────────────┘
```

### Overview Tab
```
┌─────────────┬─────────────┬─────────────┬─────────────┐
│📚 Enrolled  │ ✅ Completed│ 🛒 Orders   │ 🔗 Thinkific│
│     3       │      0      │      2      │     ✓       │
└─────────────┴─────────────┴─────────────┴─────────────┘

┌─────────────────────────────────────┬───────────────┐
│ Continue Learning                    │ Account Info  │
│ ┌─────────────────────────────────┐ │ Email: ...    │
│ │ Course Name              [Go →] │ │ Member: ...   │
│ └─────────────────────────────────┘ │ Username: ... │
│ ┌─────────────────────────────────┐ │               │
│ │ Another Course           [Go →] │ │               │
│ └─────────────────────────────────┘ │               │
└─────────────────────────────────────┴───────────────┘
```

### My Courses Tab
```
┌──────────────────┬──────────────────┬──────────────────┐
│ ✅ ENROLLED      │ ⏱ PENDING       │ ✅ ENROLLED      │
│ Course Title     │ Course Title     │ Course Title     │
│ Enrolled: Jan 15 │ Pending...       │ Enrolled: Jan 10 │
│ [Continue →]     │ [Continue →]     │ [Continue →]     │
└──────────────────┴──────────────────┴──────────────────┘
```

### Orders Tab
```
┌────────────────────────────────────────────────────┐
│ Order #1234              [Completed]               │
│ January 15, 2026                                   │
│ ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ │
│ 📚 Course Name × 1                                 │
│ Total: $99.00                    [View Details]    │
└────────────────────────────────────────────────────┘
```

---

## 🎯 Common Customizations

### Hide Orders Tab
```
[thinkific_dashboard show_orders="no"]
```

### Courses Only Dashboard
```
[thinkific_dashboard show_orders="no"]
```

### Full Dashboard
```
[thinkific_dashboard show_orders="yes"]
```

---

## 🔗 Quick Links for Users

Add these to your website:

### Header Menu
- My Dashboard
- My Courses (link to `?tab=courses`)
- Community (link to `?tab=community`)

### Footer Links
- Student Dashboard
- Order History (link to `?tab=orders`)
- Help & Support

### Email Templates
```html
<a href="https://yoursite.com/dashboard/">Access Your Dashboard</a>
<a href="https://yoursite.com/dashboard/?tab=courses">View Your Courses</a>
```

---

## ✅ Success Checklist

Before going live, verify:

- [ ] Dashboard page is published
- [ ] Shortcode is correctly placed
- [ ] Page is added to menu
- [ ] Test as logged-out user (see login page)
- [ ] Test as logged-in user (see dashboard tabs)
- [ ] All tabs work (click each one)
- [ ] Courses display correctly
- [ ] Orders show up (if you have test orders)
- [ ] Mobile view looks good (test on phone)
- [ ] Discord URL is set (if using community tab)
- [ ] Google login works (if plugin installed)

---

## 🚨 Troubleshooting Quick Fixes

### "Tabs not working"
→ Go to **Settings → Permalinks** → Click **Save Changes**

### "Courses not showing"
→ Check **Thinkific → Course Mapping** - ensure products are mapped

### "Styling looks wrong"
→ Clear cache (WordPress cache + browser cache)

### "Enrolled courses show as Pending"
→ User needs to login to Thinkific once with their checkout email

---

## 📞 Need Help?

1. **Check Logs**: Admin → Thinkific → Logs
2. **Test Connection**: Admin → Thinkific → Settings → Test Connection
3. **Read Docs**: Review `DASHBOARD-FEATURES.md` for detailed info
4. **View All Docs**: Check `INDEX.md` for complete documentation

---

## 🎨 Brand Colors

Want to match your brand? Add to **Appearance → Customize → Additional CSS**:

```css
/* Change primary color */
.thinkific-dashboard-header,
.thinkific-course-btn-launch {
    background: linear-gradient(135deg, #YOUR-COLOR-1 0%, #YOUR-COLOR-2 100%) !important;
}

/* Change active tab color */
.thinkific-tab-link.active {
    color: #YOUR-BRAND-COLOR !important;
    border-bottom-color: #YOUR-BRAND-COLOR !important;
}
```

---

**That's it! Your modern dashboard is ready to go! 🎉**

For more advanced features and customization options, see **[DASHBOARD-FEATURES.md](DASHBOARD-FEATURES.md)**
