-- ahgCorePlugin Installation SQL
-- This plugin doesn't require its own tables, but we need to register it

-- Register plugin in atom_plugin table (if not exists)
INSERT IGNORE INTO atom_plugin (name, class_name, version, description, category, is_enabled, is_core, is_locked, load_order, created_at)
VALUES (
    'ahgCorePlugin',
    'ahgCorePluginConfiguration',
    '1.0.0',
    'Core utilities and shared services for AHG plugins',
    'core',
    1,
    1,
    1,
    1,
    NOW()
);

-- Update if already exists
UPDATE atom_plugin SET
    version = '1.0.0',
    description = 'Core utilities and shared services for AHG plugins',
    category = 'core',
    is_enabled = 1,
    is_core = 1,
    is_locked = 1,
    load_order = 1,
    updated_at = NOW()
WHERE name = 'ahgCorePlugin';

-- ============================================================
-- AHG Dropdown Table
-- Plugin-specific controlled vocabulary system
-- Replaces hardcoded dropdown values with database-driven terms
-- ============================================================

CREATE TABLE IF NOT EXISTS `ahg_dropdown` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `taxonomy` VARCHAR(100) NOT NULL COMMENT 'Taxonomy code e.g. loan_status',
    `taxonomy_label` VARCHAR(255) NOT NULL COMMENT 'Display name e.g. Loan Status',
    `code` VARCHAR(100) NOT NULL COMMENT 'Term code e.g. draft',
    `label` VARCHAR(255) NOT NULL COMMENT 'Term display name',
    `color` VARCHAR(7) NULL COMMENT 'Hex color e.g. #4caf50',
    `icon` VARCHAR(50) NULL COMMENT 'Icon class e.g. fa-check',
    `sort_order` INT DEFAULT 0,
    `is_default` TINYINT(1) DEFAULT 0,
    `is_active` TINYINT(1) DEFAULT 1,
    `metadata` JSON NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_taxonomy_code` (`taxonomy`, `code`),
    INDEX `idx_taxonomy` (`taxonomy`),
    INDEX `idx_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- SEED DATA: Exhibition Types
-- ============================================================
INSERT IGNORE INTO `ahg_dropdown` (`taxonomy`, `taxonomy_label`, `code`, `label`, `sort_order`) VALUES
('exhibition_type', 'Exhibition Type', 'permanent', 'Permanent Exhibition', 10),
('exhibition_type', 'Exhibition Type', 'temporary', 'Temporary Exhibition', 20),
('exhibition_type', 'Exhibition Type', 'traveling', 'Traveling Exhibition', 30),
('exhibition_type', 'Exhibition Type', 'online', 'Online/Virtual Exhibition', 40),
('exhibition_type', 'Exhibition Type', 'pop_up', 'Pop-up Exhibition', 50);

-- ============================================================
-- SEED DATA: Exhibition Status (with colors)
-- ============================================================
INSERT IGNORE INTO `ahg_dropdown` (`taxonomy`, `taxonomy_label`, `code`, `label`, `color`, `sort_order`, `is_default`) VALUES
('exhibition_status', 'Exhibition Status', 'concept', 'Concept', '#9e9e9e', 10, 1),
('exhibition_status', 'Exhibition Status', 'planning', 'Planning', '#2196f3', 20, 0),
('exhibition_status', 'Exhibition Status', 'preparation', 'Preparation', '#ff9800', 30, 0),
('exhibition_status', 'Exhibition Status', 'installation', 'Installation', '#9c27b0', 40, 0),
('exhibition_status', 'Exhibition Status', 'open', 'Open', '#4caf50', 50, 0),
('exhibition_status', 'Exhibition Status', 'closing', 'Closing', '#ff5722', 60, 0),
('exhibition_status', 'Exhibition Status', 'closed', 'Closed', '#795548', 70, 0),
('exhibition_status', 'Exhibition Status', 'archived', 'Archived', '#607d8b', 80, 0),
('exhibition_status', 'Exhibition Status', 'canceled', 'Canceled', '#f44336', 90, 0);

-- ============================================================
-- SEED DATA: Exhibition Object Status
-- ============================================================
INSERT IGNORE INTO `ahg_dropdown` (`taxonomy`, `taxonomy_label`, `code`, `label`, `sort_order`, `is_default`) VALUES
('exhibition_object_status', 'Exhibition Object Status', 'proposed', 'Proposed', 10, 1),
('exhibition_object_status', 'Exhibition Object Status', 'confirmed', 'Confirmed', 20, 0),
('exhibition_object_status', 'Exhibition Object Status', 'on_loan_request', 'Loan Requested', 30, 0),
('exhibition_object_status', 'Exhibition Object Status', 'installed', 'Installed', 40, 0),
('exhibition_object_status', 'Exhibition Object Status', 'removed', 'Removed', 50, 0),
('exhibition_object_status', 'Exhibition Object Status', 'returned', 'Returned', 60, 0);

-- ============================================================
-- SEED DATA: Request to Publish Status
-- ============================================================
INSERT IGNORE INTO `ahg_dropdown` (`taxonomy`, `taxonomy_label`, `code`, `label`, `color`, `sort_order`, `is_default`) VALUES
('rtp_status', 'Request to Publish Status', 'in_review', 'In Review', '#ff9800', 10, 1),
('rtp_status', 'Request to Publish Status', 'rejected', 'Rejected', '#f44336', 20, 0),
('rtp_status', 'Request to Publish Status', 'approved', 'Approved', '#4caf50', 30, 0);

-- ============================================================
-- SEED DATA: Workflow Status (with colors)
-- ============================================================
INSERT IGNORE INTO `ahg_dropdown` (`taxonomy`, `taxonomy_label`, `code`, `label`, `color`, `sort_order`, `is_default`) VALUES
('workflow_status', 'Workflow Status', 'not_started', 'Not Started', '#9e9e9e', 10, 1),
('workflow_status', 'Workflow Status', 'in_progress', 'In Progress', '#2196f3', 20, 0),
('workflow_status', 'Workflow Status', 'pending_review', 'Pending Review', '#ff9800', 30, 0),
('workflow_status', 'Workflow Status', 'pending_approval', 'Pending Approval', '#ff9800', 35, 0),
('workflow_status', 'Workflow Status', 'approved', 'Approved', '#8bc34a', 40, 0),
('workflow_status', 'Workflow Status', 'completed', 'Completed', '#4caf50', 50, 0),
('workflow_status', 'Workflow Status', 'on_hold', 'On Hold', '#607d8b', 60, 0),
('workflow_status', 'Workflow Status', 'cancelled', 'Cancelled', '#f44336', 70, 0),
('workflow_status', 'Workflow Status', 'overdue', 'Overdue', '#e91e63', 80, 0);

-- ============================================================
-- SEED DATA: Link Status (Getty/vocabulary links)
-- ============================================================
INSERT IGNORE INTO `ahg_dropdown` (`taxonomy`, `taxonomy_label`, `code`, `label`, `sort_order`, `is_default`) VALUES
('link_status', 'Link Status', 'pending', 'Pending', 10, 1),
('link_status', 'Link Status', 'suggested', 'Suggested', 20, 0),
('link_status', 'Link Status', 'confirmed', 'Confirmed', 30, 0),
('link_status', 'Link Status', 'rejected', 'Rejected', 40, 0);

-- ============================================================
-- SEED DATA: Loan Status (with colors)
-- ============================================================
INSERT IGNORE INTO `ahg_dropdown` (`taxonomy`, `taxonomy_label`, `code`, `label`, `color`, `sort_order`, `is_default`) VALUES
('loan_status', 'Loan Status', 'draft', 'Draft', '#9e9e9e', 10, 1),
('loan_status', 'Loan Status', 'pending_approval', 'Pending Approval', '#ff9800', 20, 0),
('loan_status', 'Loan Status', 'approved', 'Approved', '#8bc34a', 30, 0),
('loan_status', 'Loan Status', 'active', 'Active', '#4caf50', 40, 0),
('loan_status', 'Loan Status', 'in_transit', 'In Transit', '#2196f3', 50, 0),
('loan_status', 'Loan Status', 'overdue', 'Overdue', '#e91e63', 60, 0),
('loan_status', 'Loan Status', 'returned', 'Returned', '#607d8b', 70, 0),
('loan_status', 'Loan Status', 'cancelled', 'Cancelled', '#f44336', 80, 0);

-- ============================================================
-- SEED DATA: Loan Type
-- ============================================================
INSERT IGNORE INTO `ahg_dropdown` (`taxonomy`, `taxonomy_label`, `code`, `label`, `sort_order`) VALUES
('loan_type', 'Loan Type', 'incoming', 'Incoming Loan', 10),
('loan_type', 'Loan Type', 'outgoing', 'Outgoing Loan', 20),
('loan_type', 'Loan Type', 'exhibition', 'Exhibition Loan', 30),
('loan_type', 'Loan Type', 'research', 'Research Loan', 40),
('loan_type', 'Loan Type', 'conservation', 'Conservation Loan', 50);

-- ============================================================
-- SEED DATA: Spectrum Procedure Status
-- ============================================================
INSERT IGNORE INTO `ahg_dropdown` (`taxonomy`, `taxonomy_label`, `code`, `label`, `color`, `sort_order`, `is_default`) VALUES
('spectrum_procedure_status', 'Spectrum Procedure Status', 'not_started', 'Not Started', '#9e9e9e', 10, 1),
('spectrum_procedure_status', 'Spectrum Procedure Status', 'in_progress', 'In Progress', '#2196f3', 20, 0),
('spectrum_procedure_status', 'Spectrum Procedure Status', 'completed', 'Completed', '#4caf50', 30, 0),
('spectrum_procedure_status', 'Spectrum Procedure Status', 'on_hold', 'On Hold', '#ff9800', 40, 0);

-- ============================================================
-- SEED DATA: Rights Basis
-- ============================================================
INSERT IGNORE INTO `ahg_dropdown` (`taxonomy`, `taxonomy_label`, `code`, `label`, `sort_order`, `is_default`) VALUES
('rights_basis', 'Rights Basis', 'copyright', 'Copyright', 10, 1),
('rights_basis', 'Rights Basis', 'license', 'License', 20, 0),
('rights_basis', 'Rights Basis', 'statute', 'Statute', 30, 0),
('rights_basis', 'Rights Basis', 'donor', 'Donor Agreement', 40, 0),
('rights_basis', 'Rights Basis', 'policy', 'Institutional Policy', 50, 0),
('rights_basis', 'Rights Basis', 'other', 'Other', 60, 0);

-- ============================================================
-- SEED DATA: Copyright Status
-- ============================================================
INSERT IGNORE INTO `ahg_dropdown` (`taxonomy`, `taxonomy_label`, `code`, `label`, `color`, `sort_order`, `is_default`) VALUES
('copyright_status', 'Copyright Status', 'copyrighted', 'In Copyright', '#f44336', 10, 0),
('copyright_status', 'Copyright Status', 'public_domain', 'Public Domain', '#4caf50', 20, 0),
('copyright_status', 'Copyright Status', 'unknown', 'Unknown', '#9e9e9e', 30, 1);

-- ============================================================
-- SEED DATA: Act Type (Rights Actions)
-- ============================================================
INSERT IGNORE INTO `ahg_dropdown` (`taxonomy`, `taxonomy_label`, `code`, `label`, `sort_order`) VALUES
('act_type', 'Act Type', 'render', 'Render / Display', 10),
('act_type', 'Act Type', 'disseminate', 'Disseminate / Distribute', 20),
('act_type', 'Act Type', 'replicate', 'Replicate / Copy', 30),
('act_type', 'Act Type', 'migrate', 'Migrate / Transform', 40),
('act_type', 'Act Type', 'modify', 'Modify / Edit', 50),
('act_type', 'Act Type', 'delete', 'Delete', 60),
('act_type', 'Act Type', 'print', 'Print', 70),
('act_type', 'Act Type', 'publish', 'Publish', 80),
('act_type', 'Act Type', 'use', 'Use', 90),
('act_type', 'Act Type', 'excerpt', 'Excerpt', 100),
('act_type', 'Act Type', 'annotate', 'Annotate', 110);

-- ============================================================
-- SEED DATA: Restriction Type
-- ============================================================
INSERT IGNORE INTO `ahg_dropdown` (`taxonomy`, `taxonomy_label`, `code`, `label`, `color`, `sort_order`, `is_default`) VALUES
('restriction_type', 'Restriction Type', 'allow', 'Allow', '#4caf50', 10, 1),
('restriction_type', 'Restriction Type', 'disallow', 'Disallow', '#f44336', 20, 0),
('restriction_type', 'Restriction Type', 'conditional', 'Conditional', '#ff9800', 30, 0);

-- ============================================================
-- SEED DATA: Embargo Type
-- ============================================================
INSERT IGNORE INTO `ahg_dropdown` (`taxonomy`, `taxonomy_label`, `code`, `label`, `color`, `sort_order`, `is_default`) VALUES
('embargo_type', 'Embargo Type', 'full', 'Full Embargo', '#f44336', 10, 0),
('embargo_type', 'Embargo Type', 'metadata_only', 'Metadata Only (No Digital)', '#ff9800', 20, 0),
('embargo_type', 'Embargo Type', 'digital_only', 'Digital Only (Metadata Visible)', '#2196f3', 30, 0),
('embargo_type', 'Embargo Type', 'partial', 'Partial', '#9c27b0', 40, 0);

-- ============================================================
-- SEED DATA: Embargo Reason
-- ============================================================
INSERT IGNORE INTO `ahg_dropdown` (`taxonomy`, `taxonomy_label`, `code`, `label`, `sort_order`, `is_default`) VALUES
('embargo_reason', 'Embargo Reason', 'donor_restriction', 'Donor Restriction', 10, 1),
('embargo_reason', 'Embargo Reason', 'copyright', 'Copyright', 20, 0),
('embargo_reason', 'Embargo Reason', 'privacy', 'Privacy', 30, 0),
('embargo_reason', 'Embargo Reason', 'legal', 'Legal Hold', 40, 0),
('embargo_reason', 'Embargo Reason', 'commercial', 'Commercial Sensitivity', 50, 0),
('embargo_reason', 'Embargo Reason', 'research', 'Research Embargo', 60, 0),
('embargo_reason', 'Embargo Reason', 'cultural', 'Cultural Sensitivity', 70, 0),
('embargo_reason', 'Embargo Reason', 'security', 'Security Classification', 80, 0),
('embargo_reason', 'Embargo Reason', 'other', 'Other', 90, 0);

-- ============================================================
-- SEED DATA: Work Type (Copyright)
-- ============================================================
INSERT IGNORE INTO `ahg_dropdown` (`taxonomy`, `taxonomy_label`, `code`, `label`, `sort_order`) VALUES
('work_type', 'Work Type', 'literary', 'Literary Work', 10),
('work_type', 'Work Type', 'dramatic', 'Dramatic Work', 20),
('work_type', 'Work Type', 'musical', 'Musical Work', 30),
('work_type', 'Work Type', 'artistic', 'Artistic Work', 40),
('work_type', 'Work Type', 'film', 'Film', 50),
('work_type', 'Work Type', 'sound_recording', 'Sound Recording', 60),
('work_type', 'Work Type', 'broadcast', 'Broadcast', 70),
('work_type', 'Work Type', 'photograph', 'Photograph', 80),
('work_type', 'Work Type', 'database', 'Database', 90),
('work_type', 'Work Type', 'other', 'Other', 100);

-- ============================================================
-- SEED DATA: Source Type (Rights Research)
-- ============================================================
INSERT IGNORE INTO `ahg_dropdown` (`taxonomy`, `taxonomy_label`, `code`, `label`, `sort_order`) VALUES
('source_type', 'Source Type', 'database', 'Database/Registry', 10),
('source_type', 'Source Type', 'registry', 'Copyright Registry', 20),
('source_type', 'Source Type', 'publisher', 'Publisher', 30),
('source_type', 'Source Type', 'author_society', 'Author/Rights Society', 40),
('source_type', 'Source Type', 'archive', 'Archive/Library', 50),
('source_type', 'Source Type', 'library', 'Library Catalog', 60),
('source_type', 'Source Type', 'internet', 'Internet Search', 70),
('source_type', 'Source Type', 'newspaper', 'Newspaper/Publication', 80),
('source_type', 'Source Type', 'other', 'Other', 90);

-- ============================================================
-- SEED DATA: Agreement Status (Donor Agreements)
-- ============================================================
INSERT IGNORE INTO `ahg_dropdown` (`taxonomy`, `taxonomy_label`, `code`, `label`, `color`, `sort_order`, `is_default`) VALUES
('agreement_status', 'Agreement Status', 'draft', 'Draft', '#9e9e9e', 10, 1),
('agreement_status', 'Agreement Status', 'pending_review', 'Pending Review', '#ff9800', 20, 0),
('agreement_status', 'Agreement Status', 'pending_signature', 'Pending Signature', '#2196f3', 30, 0),
('agreement_status', 'Agreement Status', 'pending_approval', 'Pending Approval', '#ff9800', 35, 0),
('agreement_status', 'Agreement Status', 'active', 'Active', '#4caf50', 40, 0),
('agreement_status', 'Agreement Status', 'suspended', 'Suspended', '#9c27b0', 50, 0),
('agreement_status', 'Agreement Status', 'expired', 'Expired', '#795548', 60, 0),
('agreement_status', 'Agreement Status', 'terminated', 'Terminated', '#f44336', 70, 0),
('agreement_status', 'Agreement Status', 'renewed', 'Renewed', '#8bc34a', 80, 0);

-- ============================================================
-- SEED DATA: Condition Grade
-- ============================================================
INSERT IGNORE INTO `ahg_dropdown` (`taxonomy`, `taxonomy_label`, `code`, `label`, `color`, `sort_order`, `is_default`) VALUES
('condition_grade', 'Condition Grade', 'excellent', 'Excellent', '#4caf50', 10, 0),
('condition_grade', 'Condition Grade', 'good', 'Good', '#8bc34a', 20, 1),
('condition_grade', 'Condition Grade', 'fair', 'Fair', '#ff9800', 30, 0),
('condition_grade', 'Condition Grade', 'poor', 'Poor', '#ff5722', 40, 0),
('condition_grade', 'Condition Grade', 'unacceptable', 'Unacceptable', '#f44336', 50, 0);

-- ============================================================
-- SEED DATA: Damage Type
-- ============================================================
INSERT IGNORE INTO `ahg_dropdown` (`taxonomy`, `taxonomy_label`, `code`, `label`, `sort_order`, `metadata`) VALUES
('damage_type', 'Damage Type', 'abrasion', 'Abrasion/Scratches', 10, '{"category": "physical"}'),
('damage_type', 'Damage Type', 'crack', 'Crack', 20, '{"category": "physical"}'),
('damage_type', 'Damage Type', 'break', 'Break/Fracture', 30, '{"category": "physical"}'),
('damage_type', 'Damage Type', 'chip', 'Chip/Loss', 40, '{"category": "physical"}'),
('damage_type', 'Damage Type', 'dent', 'Dent/Deformation', 50, '{"category": "physical"}'),
('damage_type', 'Damage Type', 'tear', 'Tear', 60, '{"category": "physical"}'),
('damage_type', 'Damage Type', 'hole', 'Hole/Puncture', 70, '{"category": "physical"}'),
('damage_type', 'Damage Type', 'missing_part', 'Missing Part', 80, '{"category": "physical"}'),
('damage_type', 'Damage Type', 'stain', 'Stain', 90, '{"category": "surface"}'),
('damage_type', 'Damage Type', 'discoloration', 'Discoloration', 100, '{"category": "surface"}'),
('damage_type', 'Damage Type', 'fading', 'Fading', 110, '{"category": "surface"}'),
('damage_type', 'Damage Type', 'foxing', 'Foxing', 120, '{"category": "surface"}'),
('damage_type', 'Damage Type', 'accretion', 'Accretion/Deposit', 130, '{"category": "surface"}'),
('damage_type', 'Damage Type', 'corrosion', 'Corrosion/Rust', 140, '{"category": "surface"}'),
('damage_type', 'Damage Type', 'tarnish', 'Tarnish', 150, '{"category": "surface"}'),
('damage_type', 'Damage Type', 'delamination', 'Delamination', 160, '{"category": "structural"}'),
('damage_type', 'Damage Type', 'flaking', 'Flaking/Lifting', 170, '{"category": "structural"}'),
('damage_type', 'Damage Type', 'warping', 'Warping', 180, '{"category": "structural"}'),
('damage_type', 'Damage Type', 'cupping', 'Cupping', 190, '{"category": "structural"}'),
('damage_type', 'Damage Type', 'splitting', 'Splitting', 200, '{"category": "structural"}'),
('damage_type', 'Damage Type', 'loose_joint', 'Loose Joint', 210, '{"category": "structural"}');

-- ============================================================
-- SEED DATA: Shipment Type
-- ============================================================
INSERT IGNORE INTO `ahg_dropdown` (`taxonomy`, `taxonomy_label`, `code`, `label`, `sort_order`, `is_default`) VALUES
('shipment_type', 'Shipment Type', 'outbound', 'Outbound (To Borrower)', 10, 1),
('shipment_type', 'Shipment Type', 'return', 'Return (To Lender)', 20, 0);

-- ============================================================
-- SEED DATA: Shipment Status
-- ============================================================
INSERT IGNORE INTO `ahg_dropdown` (`taxonomy`, `taxonomy_label`, `code`, `label`, `color`, `sort_order`, `is_default`) VALUES
('shipment_status', 'Shipment Status', 'planned', 'Planned', '#9e9e9e', 10, 1),
('shipment_status', 'Shipment Status', 'picked_up', 'Picked Up', '#2196f3', 20, 0),
('shipment_status', 'Shipment Status', 'in_transit', 'In Transit', '#ff9800', 30, 0),
('shipment_status', 'Shipment Status', 'customs', 'In Customs', '#9c27b0', 40, 0),
('shipment_status', 'Shipment Status', 'out_for_delivery', 'Out for Delivery', '#00bcd4', 50, 0),
('shipment_status', 'Shipment Status', 'delivered', 'Delivered', '#4caf50', 60, 0),
('shipment_status', 'Shipment Status', 'failed', 'Delivery Failed', '#f44336', 70, 0),
('shipment_status', 'Shipment Status', 'returned', 'Returned to Sender', '#795548', 80, 0);

-- ============================================================
-- SEED DATA: Cost Type (Loan Costs)
-- ============================================================
INSERT IGNORE INTO `ahg_dropdown` (`taxonomy`, `taxonomy_label`, `code`, `label`, `sort_order`) VALUES
('cost_type', 'Cost Type', 'transport', 'Transport/Shipping', 10),
('cost_type', 'Cost Type', 'insurance', 'Insurance', 20),
('cost_type', 'Cost Type', 'conservation', 'Conservation', 30),
('cost_type', 'Cost Type', 'framing', 'Framing/Mounting', 40),
('cost_type', 'Cost Type', 'crating', 'Crating/Packing', 50),
('cost_type', 'Cost Type', 'customs', 'Customs/Duties', 60),
('cost_type', 'Cost Type', 'courier_fee', 'Courier Fee', 70),
('cost_type', 'Cost Type', 'handling', 'Handling', 80),
('cost_type', 'Cost Type', 'photography', 'Photography', 90),
('cost_type', 'Cost Type', 'other', 'Other', 100);

-- ============================================================
-- SEED DATA: Report Type (Condition Reports)
-- ============================================================
INSERT IGNORE INTO `ahg_dropdown` (`taxonomy`, `taxonomy_label`, `code`, `label`, `sort_order`, `is_default`) VALUES
('report_type', 'Report Type', 'incoming', 'Incoming', 10, 1),
('report_type', 'Report Type', 'outgoing', 'Outgoing', 20, 0),
('report_type', 'Report Type', 'periodic', 'Periodic', 30, 0),
('report_type', 'Report Type', 'damage', 'Damage', 40, 0);

-- ============================================================
-- SEED DATA: Image Type (Condition Photos)
-- ============================================================
INSERT IGNORE INTO `ahg_dropdown` (`taxonomy`, `taxonomy_label`, `code`, `label`, `sort_order`, `is_default`) VALUES
('image_type', 'Image Type', 'overall', 'Overall', 10, 1),
('image_type', 'Image Type', 'detail', 'Detail', 20, 0),
('image_type', 'Image Type', 'damage', 'Damage', 30, 0),
('image_type', 'Image Type', 'before', 'Before', 40, 0),
('image_type', 'Image Type', 'after', 'After', 50, 0);
