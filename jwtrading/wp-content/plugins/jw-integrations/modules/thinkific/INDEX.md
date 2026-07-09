# Documentation Index

Welcome to the Thinkific WooCommerce Integration plugin! This index will help you find the right documentation for your needs.

## 📚 Documentation Files

### For Users & Administrators

| Document | Purpose | When to Read |
|----------|---------|--------------|
| **[README.md](README.md)** | Complete user documentation | First read - covers all features and usage |
| **[SETUP.md](SETUP.md)** | Step-by-step setup guide | During initial installation and configuration |
| **[QUICK-REFERENCE.md](QUICK-REFERENCE.md)** | Quick reference card | Keep handy for common tasks and troubleshooting |
| **[API-KEY-IMPORTANT.md](API-KEY-IMPORTANT.md)** | ⚠️ **START HERE - Correct API Key** | **MUST READ: Use Private API Key, NOT JWT!** |
| **[QUICK-FIX-401.md](QUICK-FIX-401.md)** | ⚠️ Fix 401 authentication errors | If you're getting "Authentication Error" |
| **[HOW-TO-GET-API-KEY.md](HOW-TO-GET-API-KEY.md)** | Get your Private API Key | Wrong token type - need the right key |
| **[AUTHENTICATION-GUIDE.md](AUTHENTICATION-GUIDE.md)** | Understanding JWT vs API Key | Learn about different auth methods |
| **[DASHBOARD-QUICK-START.md](DASHBOARD-QUICK-START.md)** | ⚡ 5-minute dashboard setup | Quick setup for tabbed dashboard |
| **[DASHBOARD-FEATURES.md](DASHBOARD-FEATURES.md)** | Enhanced dashboard customization | Detailed dashboard features and customization |
| **[CHANGELOG.md](CHANGELOG.md)** | Version history and updates | When updating or checking what's new |

### For Developers & Technical Staff

| Document | Purpose | When to Read |
|----------|---------|--------------|
| **[TECHNICAL-NOTES.md](TECHNICAL-NOTES.md)** | Deep technical architecture | Understanding how plugin works internally |
| **[PLUGIN-SUMMARY.md](PLUGIN-SUMMARY.md)** | High-level overview | Getting oriented with plugin structure |
| **[schema.sql](schema.sql)** | Database schema reference | Database operations or troubleshooting |
| **[composer.json](composer.json)** | PHP dependencies | Development setup |

### Legal & Licensing

| Document | Purpose |
|----------|---------|
| **[LICENSE](LICENSE)** | GPL v2 license terms |

## 🚀 Getting Started Path

### New to the Plugin?

1. **Start here**: [README.md](README.md) - Understand what the plugin does
2. **Then read**: [SETUP.md](SETUP.md) - Follow step-by-step setup
3. **Keep handy**: [QUICK-REFERENCE.md](QUICK-REFERENCE.md) - For daily tasks

### Already Set Up?

- **Common tasks**: [QUICK-REFERENCE.md](QUICK-REFERENCE.md)
- **Troubleshooting**: [README.md#troubleshooting](README.md#troubleshooting)
- **View logs**: WordPress Admin → Thinkific → Logs

### Developer/Technical?

1. **Architecture**: [TECHNICAL-NOTES.md](TECHNICAL-NOTES.md)
2. **Overview**: [PLUGIN-SUMMARY.md](PLUGIN-SUMMARY.md)
3. **Database**: [schema.sql](schema.sql)

## 🔍 Find Information By Topic

### Installation & Setup
- Initial installation: [README.md#installation](README.md#installation)
- Complete setup guide: [SETUP.md](SETUP.md)
- Requirements: [README.md#requirements](README.md#requirements)

### Configuration
- API settings: [SETUP.md#step-2-get-thinkific-api-credentials](SETUP.md#step-2-get-thinkific-api-credentials)
- Course mapping: [README.md#2-course-mapping](README.md#2-course-mapping)
- WooCommerce settings: [README.md#3-woocommerce-settings](README.md#3-woocommerce-settings)
- Cache settings: [README.md#4-cache-settings](README.md#4-cache-settings)

### Features & Usage
- Auto-enrollment: [README.md#purchase-flow](README.md#purchase-flow)
- Student dashboard: [README.md#4-student-dashboard](README.md#4-student-dashboard)
- Enhanced dashboard features: [DASHBOARD-FEATURES.md](DASHBOARD-FEATURES.md)
- Course mapping: [SETUP.md#step-4-create-your-first-mapping](SETUP.md#step-4-create-your-first-mapping)
- Shortcode usage: [README.md#student-dashboard-shortcode](README.md#student-dashboard-shortcode)

### Troubleshooting
- Common issues: [README.md#troubleshooting](README.md#troubleshooting)
- View logs: [README.md#view-logs](README.md#view-logs)
- Retry enrollments: [README.md#retry-failed-enrollments](README.md#retry-failed-enrollments)
- Quick fixes: [QUICK-REFERENCE.md#common-issues--quick-fixes](QUICK-REFERENCE.md#common-issues--quick-fixes)

### Technical Details
- Architecture: [TECHNICAL-NOTES.md#architecture-overview](TECHNICAL-NOTES.md#architecture-overview)
- No SSO approach: [TECHNICAL-NOTES.md#1-no-native-sso-critical](TECHNICAL-NOTES.md#1-no-native-sso-critical)
- API integration: [TECHNICAL-NOTES.md#api-client-design](TECHNICAL-NOTES.md#api-client-design)
- Database schema: [schema.sql](schema.sql)
- Security: [TECHNICAL-NOTES.md#security-considerations](TECHNICAL-NOTES.md#security-considerations)

### Customization
- Shortcode attributes: [README.md#student-dashboard](README.md#student-dashboard)
- Hooks and filters: [README.md#hooks-and-filters](README.md#hooks-and-filters)
- Styling: [README.md#styling-the-dashboard](README.md#styling-the-dashboard)
- Helper functions: [QUICK-REFERENCE.md#troubleshooting-commands](QUICK-REFERENCE.md#troubleshooting-commands)

### Growth Plan Specifics
- No SSO explanation: [TECHNICAL-NOTES.md#1-no-native-sso-critical](TECHNICAL-NOTES.md#1-no-native-sso-critical)
- New Course Builder: [TECHNICAL-NOTES.md#2-new-course-builder-api-limitations](TECHNICAL-NOTES.md#2-new-course-builder-api-limitations)
- Rate limiting: [TECHNICAL-NOTES.md#3-rate-limiting](TECHNICAL-NOTES.md#3-rate-limiting)

## 📖 Documentation by Role

### Site Administrator
**Your Essential Docs:**
1. [SETUP.md](SETUP.md) - How to set it up
2. [QUICK-REFERENCE.md](QUICK-REFERENCE.md) - Daily operations
3. [README.md](README.md) - Complete reference

**Common Tasks:**
- Add course mapping: [SETUP.md#step-4](SETUP.md#step-4)
- View logs: [QUICK-REFERENCE.md#view-logs](QUICK-REFERENCE.md#view-logs)
- Retry enrollments: [QUICK-REFERENCE.md#retry-failed-enrollment](QUICK-REFERENCE.md#retry-failed-enrollment)

### WordPress Developer
**Your Essential Docs:**
1. [TECHNICAL-NOTES.md](TECHNICAL-NOTES.md) - Architecture
2. [PLUGIN-SUMMARY.md](PLUGIN-SUMMARY.md) - Overview
3. [schema.sql](schema.sql) - Database

**Common Tasks:**
- Extend plugin: [TECHNICAL-NOTES.md#extensibility](TECHNICAL-NOTES.md#extensibility)
- Custom hooks: [README.md#hooks-and-filters](README.md#hooks-and-filters)
- Database queries: [schema.sql](schema.sql)

### Support Staff
**Your Essential Docs:**
1. [QUICK-REFERENCE.md](QUICK-REFERENCE.md) - Quick fixes
2. [README.md#troubleshooting](README.md#troubleshooting) - Common issues
3. [SETUP.md#testing-checklist](SETUP.md#testing-checklist) - Testing

**Common Tasks:**
- Diagnose issues: [README.md#troubleshooting](README.md#troubleshooting)
- Check logs: [QUICK-REFERENCE.md#view-logs](QUICK-REFERENCE.md#view-logs)
- Test setup: [SETUP.md#testing-checklist](SETUP.md#testing-checklist)

### End User (Student)
**How to Access Courses:**
1. Log into your WordPress account
2. Visit the "My Courses" page
3. Click "Continue Course" button
4. If first time: Use the same email you used at checkout

**Need Help?**
- Contact your site administrator
- Check if you're logged into WordPress

## 🔧 File Structure Reference

```
thinkific-wp-integration/
│
├── 📄 README.md                    → User documentation
├── 📄 SETUP.md                     → Setup guide
├── 📄 TECHNICAL-NOTES.md           → Technical deep dive
├── 📄 PLUGIN-SUMMARY.md            → Overview
├── 📄 QUICK-REFERENCE.md           → Quick reference
├── 📄 CHANGELOG.md                 → Version history
├── 📄 INDEX.md                     → This file
├── 📄 LICENSE                      → GPL v2 license
├── 📄 composer.json                → PHP dependencies
├── 📄 schema.sql                   → Database schema
├── 📄 thinkific-wp-integration.php → Main plugin file
├── 📄 uninstall.php                → Cleanup script
│
├── 📁 includes/                    → Core classes
│   ├── class-plugin.php            → Main controller
│   ├── class-admin.php             → Admin UI
│   ├── class-settings.php          → Settings
│   ├── class-db.php                → Database
│   ├── class-mappings.php          → Course mapping
│   ├── class-dashboard.php         → Student dashboard
│   ├── class-woocommerce.php       → WooCommerce hooks
│   ├── class-thinkific-client.php  → API client
│   ├── class-logger.php            → Logging
│   └── helpers.php                 → Utilities
│
└── 📁 assets/                      → Frontend files
    ├── admin.css                   → Admin styles
    ├── admin.js                    → Admin scripts
    └── dashboard.css               → Dashboard styles
```

## ❓ FAQ: Which Doc Should I Read?

**Q: I just installed the plugin. Where do I start?**  
A: [SETUP.md](SETUP.md) - Step-by-step setup guide

**Q: How do I add a course mapping?**  
A: [SETUP.md#step-4](SETUP.md#step-4) or [README.md#course-mapping](README.md#course-mapping)

**Q: What's the shortcode for the dashboard?**  
A: [QUICK-REFERENCE.md#shortcode](QUICK-REFERENCE.md#shortcode) → `[thinkific_dashboard]`

**Q: Enrollment failed, what do I do?**  
A: [README.md#retry-failed-enrollments](README.md#retry-failed-enrollments)

**Q: How does it work without SSO?**  
A: [TECHNICAL-NOTES.md#1-no-native-sso-critical](TECHNICAL-NOTES.md#1-no-native-sso-critical)

**Q: Getting 401 Authentication Error?**  
A: You likely have the wrong token type! → [QUICK-FIX-401.md](QUICK-FIX-401.md)

**Q: Course sync returns empty, is this broken?**  
A: No! [TECHNICAL-NOTES.md#2-new-course-builder-api-limitations](TECHNICAL-NOTES.md#2-new-course-builder-api-limitations)

**Q: How do I customize the dashboard?**  
A: [README.md#styling-the-dashboard](README.md#styling-the-dashboard)

**Q: Where are the database tables defined?**  
A: [schema.sql](schema.sql)

**Q: How do I extend the plugin?**  
A: [TECHNICAL-NOTES.md#extensibility](TECHNICAL-NOTES.md#extensibility)

**Q: What's the plugin architecture?**  
A: [PLUGIN-SUMMARY.md](PLUGIN-SUMMARY.md) for overview, [TECHNICAL-NOTES.md](TECHNICAL-NOTES.md) for details

## 🆘 Need Help?

1. **Check logs**: WordPress Admin → Thinkific → Logs
2. **Common issues**: [README.md#troubleshooting](README.md#troubleshooting)
3. **Quick fixes**: [QUICK-REFERENCE.md#common-issues--quick-fixes](QUICK-REFERENCE.md#common-issues--quick-fixes)
4. **Support checklist**: [QUICK-REFERENCE.md#support-checklist](QUICK-REFERENCE.md#support-checklist)

## 📝 Contributing

If you find issues or have improvements:
1. Check [TECHNICAL-NOTES.md](TECHNICAL-NOTES.md) for architecture
2. Review [PLUGIN-SUMMARY.md](PLUGIN-SUMMARY.md) for structure
3. Follow WordPress Coding Standards
4. Update [CHANGELOG.md](CHANGELOG.md)

---

**Version**: 1.0.0  
**Last Updated**: 2026-02-17  

**Quick Start**: [SETUP.md](SETUP.md) | **Reference**: [QUICK-REFERENCE.md](QUICK-REFERENCE.md) | **Troubleshooting**: [README.md#troubleshooting](README.md#troubleshooting)
