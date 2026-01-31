-- ============================================================
-- ahgWorkflowPlugin - Database Schema
-- Configurable Approval Workflow System
-- DO NOT include INSERT INTO atom_plugin
-- ============================================================

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;

-- ============================================================
-- Table: ahg_workflow
-- Main workflow definition table
-- Workflows can be scoped to repository or collection level
-- ============================================================
CREATE TABLE IF NOT EXISTS `ahg_workflow` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(255) NOT NULL,
    `description` TEXT,
    `scope_type` ENUM('global', 'repository', 'collection') NOT NULL DEFAULT 'global',
    `scope_id` INT DEFAULT NULL COMMENT 'repository_id or information_object_id depending on scope_type',
    `trigger_event` ENUM('create', 'update', 'submit', 'publish', 'manual') NOT NULL DEFAULT 'submit',
    `applies_to` ENUM('information_object', 'actor', 'accession', 'digital_object') NOT NULL DEFAULT 'information_object',
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `is_default` TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Default workflow for scope',
    `require_all_steps` TINYINT(1) NOT NULL DEFAULT 1 COMMENT 'Must complete all steps in order',
    `allow_parallel` TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Allow parallel step execution',
    `auto_archive_days` INT DEFAULT NULL COMMENT 'Auto-archive completed tasks after N days',
    `notification_enabled` TINYINT(1) NOT NULL DEFAULT 1,
    `created_by` INT DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_scope` (`scope_type`, `scope_id`),
    KEY `idx_trigger` (`trigger_event`),
    KEY `idx_active` (`is_active`),
    KEY `idx_default` (`is_default`, `scope_type`, `scope_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Table: ahg_workflow_step
-- Individual steps within a workflow
-- Each step can require specific roles (integration with security clearance)
-- ============================================================
CREATE TABLE IF NOT EXISTS `ahg_workflow_step` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `workflow_id` INT NOT NULL,
    `name` VARCHAR(255) NOT NULL,
    `description` TEXT,
    `step_order` INT NOT NULL DEFAULT 1,
    `step_type` ENUM('review', 'approve', 'edit', 'verify', 'sign_off', 'custom') NOT NULL DEFAULT 'review',
    `action_required` ENUM('approve', 'reject', 'approve_reject', 'complete', 'submit') NOT NULL DEFAULT 'approve_reject',
    `required_role_id` INT DEFAULT NULL COMMENT 'AtoM role_id or null for any authenticated user',
    `required_clearance_level` INT DEFAULT NULL COMMENT 'Security clearance level from ahgSecurityClearancePlugin',
    `allowed_group_ids` TEXT COMMENT 'JSON array of allowed group IDs',
    `allowed_user_ids` TEXT COMMENT 'JSON array of specific user IDs',
    `pool_enabled` TINYINT(1) NOT NULL DEFAULT 1 COMMENT 'Allow task claiming from pool',
    `auto_assign_user_id` INT DEFAULT NULL COMMENT 'Auto-assign to specific user',
    `escalation_days` INT DEFAULT NULL COMMENT 'Days before escalation',
    `escalation_user_id` INT DEFAULT NULL COMMENT 'User to escalate to',
    `notification_template` VARCHAR(100) DEFAULT 'default' COMMENT 'Email template name',
    `instructions` TEXT COMMENT 'Instructions shown to reviewer',
    `checklist` TEXT COMMENT 'JSON array of checklist items',
    `is_optional` TINYINT(1) NOT NULL DEFAULT 0,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_workflow` (`workflow_id`),
    KEY `idx_order` (`workflow_id`, `step_order`),
    KEY `idx_role` (`required_role_id`),
    KEY `idx_clearance` (`required_clearance_level`),
    CONSTRAINT `fk_step_workflow` FOREIGN KEY (`workflow_id`)
        REFERENCES `ahg_workflow` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Table: ahg_workflow_task
-- Active workflow tasks (items currently in workflow)
-- ============================================================
CREATE TABLE IF NOT EXISTS `ahg_workflow_task` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `workflow_id` INT NOT NULL,
    `workflow_step_id` INT NOT NULL,
    `object_id` INT NOT NULL COMMENT 'information_object.id or other entity',
    `object_type` VARCHAR(50) NOT NULL DEFAULT 'information_object',
    `status` ENUM('pending', 'claimed', 'in_progress', 'approved', 'rejected', 'returned', 'escalated', 'cancelled') NOT NULL DEFAULT 'pending',
    `priority` ENUM('low', 'normal', 'high', 'urgent') NOT NULL DEFAULT 'normal',
    `submitted_by` INT NOT NULL COMMENT 'User who submitted item to workflow',
    `assigned_to` INT DEFAULT NULL COMMENT 'User who claimed/was assigned the task',
    `claimed_at` DATETIME DEFAULT NULL,
    `due_date` DATE DEFAULT NULL,
    `decision` ENUM('pending', 'approved', 'rejected', 'returned') DEFAULT 'pending',
    `decision_comment` TEXT,
    `decision_at` DATETIME DEFAULT NULL,
    `decision_by` INT DEFAULT NULL,
    `checklist_completed` TEXT COMMENT 'JSON object of completed checklist items',
    `metadata` TEXT COMMENT 'Additional JSON metadata',
    `previous_task_id` INT DEFAULT NULL COMMENT 'Link to previous step task',
    `retry_count` INT NOT NULL DEFAULT 0,
    `escalated_at` DATETIME DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_workflow` (`workflow_id`),
    KEY `idx_step` (`workflow_step_id`),
    KEY `idx_object` (`object_id`, `object_type`),
    KEY `idx_status` (`status`),
    KEY `idx_assigned` (`assigned_to`),
    KEY `idx_submitted` (`submitted_by`),
    KEY `idx_due` (`due_date`),
    KEY `idx_priority` (`priority`),
    KEY `idx_pending_pool` (`status`, `assigned_to`),
    CONSTRAINT `fk_task_workflow` FOREIGN KEY (`workflow_id`)
        REFERENCES `ahg_workflow` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_task_step` FOREIGN KEY (`workflow_step_id`)
        REFERENCES `ahg_workflow_step` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Table: ahg_workflow_history
-- Complete audit trail of all workflow actions
-- ============================================================
CREATE TABLE IF NOT EXISTS `ahg_workflow_history` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `task_id` INT DEFAULT NULL COMMENT 'Link to task (null if task deleted)',
    `workflow_id` INT NOT NULL,
    `workflow_step_id` INT DEFAULT NULL,
    `object_id` INT NOT NULL,
    `object_type` VARCHAR(50) NOT NULL DEFAULT 'information_object',
    `action` ENUM('started', 'claimed', 'released', 'approved', 'rejected', 'returned', 'escalated', 'cancelled', 'completed', 'comment', 'reassigned') NOT NULL,
    `from_status` VARCHAR(50) DEFAULT NULL,
    `to_status` VARCHAR(50) DEFAULT NULL,
    `performed_by` INT NOT NULL,
    `performed_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `comment` TEXT,
    `metadata` TEXT COMMENT 'JSON additional data',
    `ip_address` VARCHAR(45) DEFAULT NULL,
    `user_agent` VARCHAR(500) DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_task` (`task_id`),
    KEY `idx_workflow` (`workflow_id`),
    KEY `idx_object` (`object_id`, `object_type`),
    KEY `idx_performer` (`performed_by`),
    KEY `idx_action` (`action`),
    KEY `idx_date` (`performed_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Table: ahg_workflow_notification
-- Email notification queue and log
-- ============================================================
CREATE TABLE IF NOT EXISTS `ahg_workflow_notification` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `task_id` INT DEFAULT NULL,
    `user_id` INT NOT NULL,
    `notification_type` ENUM('task_assigned', 'task_claimed', 'task_approved', 'task_rejected', 'task_returned', 'task_escalated', 'task_due_soon', 'task_overdue', 'workflow_completed') NOT NULL,
    `subject` VARCHAR(500) NOT NULL,
    `body` TEXT NOT NULL,
    `status` ENUM('pending', 'sent', 'failed', 'cancelled') NOT NULL DEFAULT 'pending',
    `sent_at` DATETIME DEFAULT NULL,
    `error_message` TEXT,
    `retry_count` INT NOT NULL DEFAULT 0,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_task` (`task_id`),
    KEY `idx_user` (`user_id`),
    KEY `idx_status` (`status`),
    KEY `idx_type` (`notification_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Insert default workflow (optional - can be customized)
-- ============================================================
INSERT IGNORE INTO `ahg_workflow` (`id`, `name`, `description`, `scope_type`, `trigger_event`, `applies_to`, `is_active`, `is_default`) VALUES
(1, 'Standard Review Workflow', 'Default two-step review and approval workflow for archival descriptions', 'global', 'submit', 'information_object', 1, 1);

INSERT IGNORE INTO `ahg_workflow_step` (`id`, `workflow_id`, `name`, `description`, `step_order`, `step_type`, `action_required`, `pool_enabled`, `instructions`) VALUES
(1, 1, 'Initial Review', 'Review submission for completeness and accuracy', 1, 'review', 'approve_reject', 1, 'Check that all required fields are completed and the description follows standards.'),
(2, 1, 'Final Approval', 'Final approval before publication', 2, 'approve', 'approve_reject', 1, 'Verify the description is ready for public access.');

/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
