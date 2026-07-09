-- Thinkific WooCommerce Integration
-- Database Schema
-- Version: 1.0.0

-- Course Mappings Table
-- Stores relationships between WooCommerce products and Thinkific courses
CREATE TABLE IF NOT EXISTS `wp_thinkific_course_mappings` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `woo_product_id` bigint(20) UNSIGNED NOT NULL COMMENT 'WooCommerce product ID',
  `course_name` varchar(255) NOT NULL COMMENT 'Display name for the course',
  `course_url` varchar(500) NOT NULL COMMENT 'Full URL to Thinkific course (required)',
  `course_id` varchar(100) DEFAULT NULL COMMENT 'Thinkific course ID (optional)',
  `course_description` text DEFAULT NULL COMMENT 'Course description for dashboard',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `woo_product_id` (`woo_product_id`),
  KEY `course_id` (`course_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Maps WooCommerce products to Thinkific courses';

-- Enrollments Table
-- Tracks enrollment attempts and status
CREATE TABLE IF NOT EXISTS `wp_thinkific_enrollments` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_id` bigint(20) UNSIGNED NOT NULL COMMENT 'WooCommerce order ID',
  `user_id` bigint(20) UNSIGNED NOT NULL COMMENT 'WordPress user ID',
  `product_id` bigint(20) UNSIGNED NOT NULL COMMENT 'WooCommerce product ID',
  `course_id` varchar(100) NOT NULL COMMENT 'Thinkific course ID',
  `thinkific_user_id` varchar(100) DEFAULT NULL COMMENT 'Thinkific user ID',
  `status` varchar(50) NOT NULL DEFAULT 'pending' COMMENT 'Enrollment status: pending, enrolled, failed',
  `error_message` text DEFAULT NULL COMMENT 'Error message if enrollment failed',
  `retry_count` int(11) DEFAULT 0 COMMENT 'Number of retry attempts',
  `enrolled_at` datetime DEFAULT NULL COMMENT 'Timestamp of successful enrollment',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `order_id` (`order_id`),
  KEY `user_id` (`user_id`),
  KEY `status` (`status`),
  KEY `course_id` (`course_id`),
  UNIQUE KEY `unique_enrollment` (`user_id`,`course_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Tracks Thinkific enrollment status per user/course';

-- Sample Queries

-- Get all mappings
-- SELECT * FROM wp_thinkific_course_mappings ORDER BY created_at DESC;

-- Get mapping for a product
-- SELECT * FROM wp_thinkific_course_mappings WHERE woo_product_id = 123;

-- Get enrollments for an order
-- SELECT * FROM wp_thinkific_enrollments WHERE order_id = 456;

-- Get failed enrollments
-- SELECT * FROM wp_thinkific_enrollments WHERE status = 'failed' ORDER BY updated_at DESC;

-- Get enrollment statistics
-- SELECT 
--   status,
--   COUNT(*) as count,
--   AVG(retry_count) as avg_retries
-- FROM wp_thinkific_enrollments 
-- GROUP BY status;

-- Get user's enrolled courses
-- SELECT 
--   e.*,
--   m.course_name,
--   m.course_url
-- FROM wp_thinkific_enrollments e
-- JOIN wp_thinkific_course_mappings m ON e.course_id = m.course_id
-- WHERE e.user_id = 789 AND e.status = 'enrolled';

-- Clean up old failed enrollments (older than 90 days)
-- DELETE FROM wp_thinkific_enrollments 
-- WHERE status = 'failed' 
-- AND updated_at < DATE_SUB(NOW(), INTERVAL 90 DAY);
