# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.0] - 2026-02-17

### Added
- Initial release
- Automatic enrollment system for WooCommerce orders
- Course mapping interface (manual and optional sync)
- Student dashboard with `[thinkific_dashboard]` shortcode
- Thinkific API client with caching and rate limiting
- Comprehensive logging system
- Order meta box showing enrollment status
- Retry mechanism for failed enrollments
- Connection testing tool
- Admin settings page with API configuration
- Cache management (courses and enrollments)
- WooCommerce integration hooks
- Custom database tables for mappings and enrollments
- Responsive dashboard design
- Growth plan compatibility (no SSO required)
- New Course Builder compatibility
- Helper text for first-time Thinkific login
- Security: nonce protection, prepared statements, input sanitization
- Performance: intelligent caching, rate limit handling
- Uninstall cleanup script

### Features
- **No SSO Required**: Works perfectly on Growth plan without native SSO
- **Manual Mapping**: Functions even if course listing API doesn't work
- **Smart Caching**: Reduces API calls to stay within 120/min limit
- **Seamless UX**: Guides users through first Thinkific login
- **Retry System**: Admins can retry failed enrollments
- **Comprehensive Logs**: Full activity tracking for debugging
- **Flexible Settings**: Customizable trigger statuses and cache durations
- **Cart Optimization**: Optional single-quantity and skip-cart features

### Technical
- WordPress 5.8+ compatibility
- PHP 7.4+ compatibility
- WooCommerce 5.0+ compatibility
- PSR-4 class structure
- WordPress Coding Standards
- AJAX-powered admin interface
- Transient-based caching
- Custom database schema
- WP_Error handling throughout

## [Unreleased]

### Planned
- Bulk enrollment processor
- Course progress tracking (if API supports)
- Email notifications for enrollment status
- Admin dashboard widget with stats
- Import/export mappings
- Webhook support for real-time updates
- REST API endpoints for external integrations
- WP-CLI commands for management
- Multi-language support (i18n ready)

---

**Legend:**
- `Added` for new features
- `Changed` for changes in existing functionality
- `Deprecated` for soon-to-be removed features
- `Removed` for removed features
- `Fixed` for bug fixes
- `Security` for vulnerability fixes
