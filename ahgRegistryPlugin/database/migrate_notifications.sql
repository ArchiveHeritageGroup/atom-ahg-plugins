-- Registry notifications: in-app notifications for admins and users
-- Recipients: admins (all users in administrator group) + targeted single users

CREATE TABLE IF NOT EXISTS `registry_notification` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL COMMENT 'recipient user.id',
  `type` VARCHAR(64) NOT NULL COMMENT 'user_registered, institution_claimed, vendor_registered, software_added, review_submitted, etc.',
  `title` VARCHAR(255) NOT NULL,
  `message` TEXT NULL,
  `link` VARCHAR(500) NULL COMMENT 'destination URL when notification is clicked',
  `related_type` VARCHAR(64) NULL COMMENT 'user, institution, vendor, software, review, ...',
  `related_id` BIGINT UNSIGNED NULL,
  `actor_user_id` INT UNSIGNED NULL COMMENT 'user.id who triggered the event (null for anonymous)',
  `actor_name` VARCHAR(255) NULL COMMENT 'display name of triggering actor (snapshot)',
  `is_read` TINYINT(1) NOT NULL DEFAULT 0,
  `is_dismissed` TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'hidden from top bar (still appears in dropdown until read)',
  `created_at` DATETIME NOT NULL,
  `read_at` DATETIME NULL,
  PRIMARY KEY (`id`),
  KEY `idx_user_unread` (`user_id`, `is_read`, `created_at`),
  KEY `idx_user_bar` (`user_id`, `is_dismissed`, `is_read`, `created_at`),
  KEY `idx_type_related` (`type`, `related_type`, `related_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
