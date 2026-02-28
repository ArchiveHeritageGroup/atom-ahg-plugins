-- ============================================================
-- Phase 3: ENUM → VARCHAR + ahg_dropdown migration
-- Part A: Heritage/Linked Data tables
-- Part B: Workflow V2.0 tables
--
-- Run: mysql -u root archive < enum_to_dropdown_migration_phase3.sql
-- ============================================================

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;

-- ============================================================
-- PART A: Heritage / Linked Data dropdown seeds
-- ============================================================

-- entity_type already exists but missing 'concept' value
INSERT IGNORE INTO `ahg_dropdown` (`taxonomy`, `taxonomy_label`, `code`, `label`, `sort_order`, `taxonomy_section`)
VALUES ('entity_type', 'Entity Type', 'concept', 'Concept', 80, 'heritage_monuments');

-- graph_relationship_type (NEW)
INSERT IGNORE INTO `ahg_dropdown` (`taxonomy`, `taxonomy_label`, `code`, `label`, `sort_order`, `taxonomy_section`) VALUES
('graph_relationship_type', 'Graph Relationship Type', 'co_occurrence', 'Co-occurrence', 10, 'heritage_monuments'),
('graph_relationship_type', 'Graph Relationship Type', 'mentioned_with', 'Mentioned With', 20, 'heritage_monuments'),
('graph_relationship_type', 'Graph Relationship Type', 'associated_with', 'Associated With', 30, 'heritage_monuments'),
('graph_relationship_type', 'Graph Relationship Type', 'employed_by', 'Employed By', 40, 'heritage_monuments'),
('graph_relationship_type', 'Graph Relationship Type', 'located_in', 'Located In', 50, 'heritage_monuments'),
('graph_relationship_type', 'Graph Relationship Type', 'occurred_at', 'Occurred At', 60, 'heritage_monuments'),
('graph_relationship_type', 'Graph Relationship Type', 'related_to', 'Related To', 70, 'heritage_monuments'),
('graph_relationship_type', 'Graph Relationship Type', 'same_as', 'Same As', 80, 'heritage_monuments'),
('graph_relationship_type', 'Graph Relationship Type', 'child_of', 'Child Of', 90, 'heritage_monuments'),
('graph_relationship_type', 'Graph Relationship Type', 'preceded_by', 'Preceded By', 100, 'heritage_monuments'),
('graph_relationship_type', 'Graph Relationship Type', 'followed_by', 'Followed By', 110, 'heritage_monuments');

-- getty_link_status (NEW)
INSERT IGNORE INTO `ahg_dropdown` (`taxonomy`, `taxonomy_label`, `code`, `label`, `color`, `sort_order`, `taxonomy_section`) VALUES
('getty_link_status', 'Getty Link Status', 'pending', 'Pending', '#6c757d', 10, 'heritage_monuments'),
('getty_link_status', 'Getty Link Status', 'suggested', 'Suggested', '#ffc107', 20, 'heritage_monuments'),
('getty_link_status', 'Getty Link Status', 'confirmed', 'Confirmed', '#28a745', 30, 'heritage_monuments'),
('getty_link_status', 'Getty Link Status', 'rejected', 'Rejected', '#dc3545', 40, 'heritage_monuments');

-- ============================================================
-- PART A: ALTER ENUM → VARCHAR on heritage tables
-- ============================================================

-- heritage_entity_graph_node.entity_type
ALTER TABLE `heritage_entity_graph_node`
    MODIFY COLUMN `entity_type` VARCHAR(50) NOT NULL;

-- heritage_entity_graph_edge.relationship_type
ALTER TABLE `heritage_entity_graph_edge`
    MODIFY COLUMN `relationship_type` VARCHAR(50) NOT NULL DEFAULT 'co_occurrence';

-- heritage_entity_graph_object.extraction_method
ALTER TABLE `heritage_entity_graph_object`
    MODIFY COLUMN `extraction_method` VARCHAR(50) DEFAULT 'ner';

-- heritage_entity_cache.entity_type
ALTER TABLE `heritage_entity_cache`
    MODIFY COLUMN `entity_type` VARCHAR(50) NOT NULL;

-- heritage_entity_cache.extraction_method
ALTER TABLE `heritage_entity_cache`
    MODIFY COLUMN `extraction_method` VARCHAR(50) DEFAULT 'taxonomy';

-- getty_vocabulary_link.vocabulary
ALTER TABLE `getty_vocabulary_link`
    MODIFY COLUMN `vocabulary` VARCHAR(20) NOT NULL;

-- getty_vocabulary_link.status
ALTER TABLE `getty_vocabulary_link`
    MODIFY COLUMN `status` VARCHAR(50) NOT NULL DEFAULT 'pending';

-- ============================================================
-- PART B: Workflow V2.0 dropdown seeds
-- ============================================================

-- workflow_history_action (NEW — extends workflow_action with event types)
INSERT IGNORE INTO `ahg_dropdown` (`taxonomy`, `taxonomy_label`, `code`, `label`, `color`, `icon`, `sort_order`, `taxonomy_section`) VALUES
-- Existing actions (from ahg_workflow_history ENUM)
('workflow_history_action', 'Workflow History Action', 'started', 'Started', '#17a2b8', 'fa-play', 10, 'reporting_workflow'),
('workflow_history_action', 'Workflow History Action', 'claimed', 'Claimed', '#007bff', 'fa-hand-paper', 20, 'reporting_workflow'),
('workflow_history_action', 'Workflow History Action', 'released', 'Released', '#6c757d', 'fa-hand-rock', 30, 'reporting_workflow'),
('workflow_history_action', 'Workflow History Action', 'approved', 'Approved', '#28a745', 'fa-check', 40, 'reporting_workflow'),
('workflow_history_action', 'Workflow History Action', 'rejected', 'Rejected', '#dc3545', 'fa-times', 50, 'reporting_workflow'),
('workflow_history_action', 'Workflow History Action', 'returned', 'Returned', '#fd7e14', 'fa-undo', 60, 'reporting_workflow'),
('workflow_history_action', 'Workflow History Action', 'escalated', 'Escalated', '#e83e8c', 'fa-arrow-up', 70, 'reporting_workflow'),
('workflow_history_action', 'Workflow History Action', 'cancelled', 'Cancelled', '#6c757d', 'fa-ban', 80, 'reporting_workflow'),
('workflow_history_action', 'Workflow History Action', 'completed', 'Completed', '#28a745', 'fa-flag-checkered', 90, 'reporting_workflow'),
('workflow_history_action', 'Workflow History Action', 'comment', 'Comment', '#6c757d', 'fa-comment', 100, 'reporting_workflow'),
('workflow_history_action', 'Workflow History Action', 'reassigned', 'Reassigned', '#007bff', 'fa-user-friends', 110, 'reporting_workflow'),
-- V2.0 new action types
('workflow_history_action', 'Workflow History Action', 'note_added', 'Note Added', '#17a2b8', 'fa-sticky-note', 120, 'reporting_workflow'),
('workflow_history_action', 'Workflow History Action', 'attachment_added', 'Attachment Added', '#17a2b8', 'fa-paperclip', 130, 'reporting_workflow'),
('workflow_history_action', 'Workflow History Action', 'attachment_removed', 'Attachment Removed', '#fd7e14', 'fa-unlink', 140, 'reporting_workflow'),
('workflow_history_action', 'Workflow History Action', 'rights_decision', 'Rights Decision', '#6f42c1', 'fa-balance-scale', 150, 'reporting_workflow'),
('workflow_history_action', 'Workflow History Action', 'publish', 'Published', '#28a745', 'fa-globe', 160, 'reporting_workflow'),
('workflow_history_action', 'Workflow History Action', 'unpublish', 'Unpublished', '#dc3545', 'fa-eye-slash', 170, 'reporting_workflow'),
('workflow_history_action', 'Workflow History Action', 'priority_changed', 'Priority Changed', '#ffc107', 'fa-exclamation-triangle', 180, 'reporting_workflow'),
('workflow_history_action', 'Workflow History Action', 'due_date_changed', 'Due Date Changed', '#17a2b8', 'fa-calendar-alt', 190, 'reporting_workflow'),
('workflow_history_action', 'Workflow History Action', 'queue_changed', 'Queue Changed', '#007bff', 'fa-inbox', 200, 'reporting_workflow'),
('workflow_history_action', 'Workflow History Action', 'sla_warning', 'SLA Warning', '#ffc107', 'fa-clock', 210, 'reporting_workflow'),
('workflow_history_action', 'Workflow History Action', 'sla_breached', 'SLA Breached', '#dc3545', 'fa-exclamation-circle', 220, 'reporting_workflow');

-- workflow_priority (NEW)
INSERT IGNORE INTO `ahg_dropdown` (`taxonomy`, `taxonomy_label`, `code`, `label`, `color`, `icon`, `sort_order`, `taxonomy_section`) VALUES
('workflow_priority', 'Workflow Priority', 'low', 'Low', '#6c757d', 'fa-arrow-down', 10, 'reporting_workflow'),
('workflow_priority', 'Workflow Priority', 'normal', 'Normal', '#007bff', 'fa-minus', 20, 'reporting_workflow'),
('workflow_priority', 'Workflow Priority', 'high', 'High', '#fd7e14', 'fa-arrow-up', 30, 'reporting_workflow'),
('workflow_priority', 'Workflow Priority', 'urgent', 'Urgent', '#dc3545', 'fa-exclamation-circle', 40, 'reporting_workflow');

-- workflow_decision (NEW)
INSERT IGNORE INTO `ahg_dropdown` (`taxonomy`, `taxonomy_label`, `code`, `label`, `color`, `sort_order`, `taxonomy_section`) VALUES
('workflow_decision', 'Workflow Decision', 'pending', 'Pending', '#6c757d', 10, 'reporting_workflow'),
('workflow_decision', 'Workflow Decision', 'approved', 'Approved', '#28a745', 20, 'reporting_workflow'),
('workflow_decision', 'Workflow Decision', 'rejected', 'Rejected', '#dc3545', 30, 'reporting_workflow'),
('workflow_decision', 'Workflow Decision', 'returned', 'Returned', '#fd7e14', 40, 'reporting_workflow');

-- workflow_notification_type (NEW)
INSERT IGNORE INTO `ahg_dropdown` (`taxonomy`, `taxonomy_label`, `code`, `label`, `icon`, `sort_order`, `taxonomy_section`) VALUES
('workflow_notification_type', 'Workflow Notification Type', 'task_assigned', 'Task Assigned', 'fa-user-plus', 10, 'reporting_workflow'),
('workflow_notification_type', 'Workflow Notification Type', 'task_claimed', 'Task Claimed', 'fa-hand-paper', 20, 'reporting_workflow'),
('workflow_notification_type', 'Workflow Notification Type', 'task_approved', 'Task Approved', 'fa-check-circle', 30, 'reporting_workflow'),
('workflow_notification_type', 'Workflow Notification Type', 'task_rejected', 'Task Rejected', 'fa-times-circle', 40, 'reporting_workflow'),
('workflow_notification_type', 'Workflow Notification Type', 'task_returned', 'Task Returned', 'fa-undo', 50, 'reporting_workflow'),
('workflow_notification_type', 'Workflow Notification Type', 'task_escalated', 'Task Escalated', 'fa-arrow-up', 60, 'reporting_workflow'),
('workflow_notification_type', 'Workflow Notification Type', 'task_due_soon', 'Task Due Soon', 'fa-clock', 70, 'reporting_workflow'),
('workflow_notification_type', 'Workflow Notification Type', 'task_overdue', 'Task Overdue', 'fa-exclamation-triangle', 80, 'reporting_workflow'),
('workflow_notification_type', 'Workflow Notification Type', 'workflow_completed', 'Workflow Completed', 'fa-flag-checkered', 90, 'reporting_workflow'),
-- V2.0 additions
('workflow_notification_type', 'Workflow Notification Type', 'sla_warning', 'SLA Warning', 'fa-clock', 100, 'reporting_workflow'),
('workflow_notification_type', 'Workflow Notification Type', 'sla_breached', 'SLA Breached', 'fa-exclamation-circle', 110, 'reporting_workflow'),
('workflow_notification_type', 'Workflow Notification Type', 'bulk_complete', 'Bulk Operation Complete', 'fa-tasks', 120, 'reporting_workflow');

-- workflow_escalation_action (NEW — for SLA policy)
INSERT IGNORE INTO `ahg_dropdown` (`taxonomy`, `taxonomy_label`, `code`, `label`, `sort_order`, `taxonomy_section`) VALUES
('workflow_escalation_action', 'Workflow Escalation Action', 'notify_lead', 'Notify Team Lead', 10, 'reporting_workflow'),
('workflow_escalation_action', 'Workflow Escalation Action', 'notify_admin', 'Notify Administrator', 20, 'reporting_workflow'),
('workflow_escalation_action', 'Workflow Escalation Action', 'auto_reassign', 'Auto-Reassign', 30, 'reporting_workflow');

-- ============================================================
-- PART B: ALTER ENUM → VARCHAR on workflow tables
-- ============================================================

-- ahg_workflow.scope_type
ALTER TABLE `ahg_workflow`
    MODIFY COLUMN `scope_type` VARCHAR(50) NOT NULL DEFAULT 'global';

-- ahg_workflow.trigger_event
ALTER TABLE `ahg_workflow`
    MODIFY COLUMN `trigger_event` VARCHAR(50) NOT NULL DEFAULT 'submit';

-- ahg_workflow.applies_to
ALTER TABLE `ahg_workflow`
    MODIFY COLUMN `applies_to` VARCHAR(50) NOT NULL DEFAULT 'information_object';

-- ahg_workflow_step.step_type
ALTER TABLE `ahg_workflow_step`
    MODIFY COLUMN `step_type` VARCHAR(50) NOT NULL DEFAULT 'review';

-- ahg_workflow_step.action_required
ALTER TABLE `ahg_workflow_step`
    MODIFY COLUMN `action_required` VARCHAR(50) NOT NULL DEFAULT 'approve_reject';

-- ahg_workflow_task.status
ALTER TABLE `ahg_workflow_task`
    MODIFY COLUMN `status` VARCHAR(50) NOT NULL DEFAULT 'pending';

-- ahg_workflow_task.priority
ALTER TABLE `ahg_workflow_task`
    MODIFY COLUMN `priority` VARCHAR(50) NOT NULL DEFAULT 'normal';

-- ahg_workflow_task.decision
ALTER TABLE `ahg_workflow_task`
    MODIFY COLUMN `decision` VARCHAR(50) DEFAULT 'pending';

-- ahg_workflow_history.action
ALTER TABLE `ahg_workflow_history`
    MODIFY COLUMN `action` VARCHAR(50) NOT NULL;

-- ahg_workflow_notification.notification_type
ALTER TABLE `ahg_workflow_notification`
    MODIFY COLUMN `notification_type` VARCHAR(50) NOT NULL;

-- ahg_workflow_notification.status (also ENUM)
ALTER TABLE `ahg_workflow_notification`
    MODIFY COLUMN `status` VARCHAR(50) NOT NULL DEFAULT 'pending';

-- ============================================================
-- PART B: V2.0 schema additions
-- ============================================================

-- Correlation ID for bulk operations (#172)
SET @col_exists = (SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'ahg_workflow_history' AND COLUMN_NAME = 'correlation_id');
SET @sql = IF(@col_exists = 0,
    'ALTER TABLE `ahg_workflow_history` ADD COLUMN `correlation_id` VARCHAR(36) DEFAULT NULL',
    'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Index on correlation_id
SET @idx_exists = (SELECT COUNT(*) FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'ahg_workflow_history' AND INDEX_NAME = 'idx_wh_correlation');
SET @sql = IF(@idx_exists = 0,
    'CREATE INDEX `idx_wh_correlation` ON `ahg_workflow_history` (`correlation_id`)',
    'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Queue ID on tasks (#173)
SET @col_exists = (SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'ahg_workflow_task' AND COLUMN_NAME = 'queue_id');
SET @sql = IF(@col_exists = 0,
    'ALTER TABLE `ahg_workflow_task` ADD COLUMN `queue_id` INT UNSIGNED DEFAULT NULL',
    'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Index on queue_id
SET @idx_exists = (SELECT COUNT(*) FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'ahg_workflow_task' AND INDEX_NAME = 'idx_wt_queue');
SET @sql = IF(@idx_exists = 0,
    'CREATE INDEX `idx_wt_queue` ON `ahg_workflow_task` (`queue_id`)',
    'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ============================================================
-- Queue table (#173)
-- ============================================================
CREATE TABLE IF NOT EXISTS `ahg_workflow_queue` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `slug` VARCHAR(100) NOT NULL UNIQUE,
    `description` TEXT,
    `sort_order` INT DEFAULT 0,
    `is_active` TINYINT(1) DEFAULT 1,
    `sla_days` INT DEFAULT NULL,
    `icon` VARCHAR(50) DEFAULT 'fa-inbox',
    `color` VARCHAR(7) DEFAULT '#6c757d',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Default queues
INSERT IGNORE INTO `ahg_workflow_queue` (`name`, `slug`, `description`, `sort_order`, `icon`, `color`) VALUES
('Intake', 'intake', 'New submissions awaiting initial review', 10, 'fa-inbox', '#007bff'),
('Quality Control', 'qc', 'Items requiring quality control checks', 20, 'fa-check-double', '#17a2b8'),
('Description', 'description', 'Items requiring descriptive metadata', 30, 'fa-file-alt', '#6f42c1'),
('Rights', 'rights', 'Items requiring rights assessment', 40, 'fa-balance-scale', '#fd7e14'),
('Publish', 'publish', 'Items ready for publication review', 50, 'fa-globe', '#28a745'),
('Requests', 'requests', 'Access and reproduction requests', 60, 'fa-envelope-open', '#e83e8c'),
('Movement', 'movement', 'Physical object movement tracking', 70, 'fa-truck', '#6c757d'),
('Preservation', 'preservation', 'Digital preservation tasks', 80, 'fa-shield-alt', '#dc3545');

-- ============================================================
-- SLA Policy table (#174)
-- ============================================================
CREATE TABLE IF NOT EXISTS `ahg_workflow_sla_policy` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `queue_id` INT UNSIGNED DEFAULT NULL,
    `workflow_id` INT DEFAULT NULL,
    `warning_days` INT DEFAULT 3,
    `due_days` INT DEFAULT 5,
    `escalation_days` INT DEFAULT 7,
    `escalation_user_id` INT DEFAULT NULL,
    `escalation_action` VARCHAR(50) DEFAULT 'notify_lead',
    `is_active` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_queue` (`queue_id`),
    INDEX `idx_workflow` (`workflow_id`),
    INDEX `idx_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Default SLA policies
INSERT IGNORE INTO `ahg_workflow_sla_policy` (`name`, `queue_id`, `warning_days`, `due_days`, `escalation_days`, `escalation_action`) VALUES
('Standard Queue SLA', NULL, 3, 5, 7, 'notify_lead'),
('Urgent Queue SLA', NULL, 1, 2, 3, 'notify_admin');

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
