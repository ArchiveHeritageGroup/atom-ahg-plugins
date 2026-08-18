-- ---------------------------------------------------------------------------
-- Moved from atom-framework/database/install.sql.
-- These tables belong to ahgCorePlugin and are created when this plugin is installed,
-- rather than for every installation regardless of need. Ordered by dependency;
-- each table is followed by its own seed data.
-- ---------------------------------------------------------------------------


-- NOTE: this file used to INSERT/UPDATE a row in `atom_plugin` here.
-- Two reasons it is gone. `atom_plugin` is created by the AHG framework and does
-- not exist on a stock AtoM, so the statement aborted the whole script and none
-- of the tables below were created. And a plugin registering itself is forbidden
-- outright (see atom-framework/CLAUDE.md): enablement belongs to the operator,
-- through the `plugins` setting that AtoM's own plugin admin maintains.
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
    `taxonomy_section` VARCHAR(50) NULL COMMENT 'UI section grouping',
    `metadata` JSON NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_taxonomy_code` (`taxonomy`, `code`),
    INDEX `idx_taxonomy` (`taxonomy`),
    INDEX `idx_active` (`is_active`),
    INDEX `idx_section` (`taxonomy_section`)
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
('spectrum_procedure_status', 'Collections Procedure Status', 'not_started', 'Not Started', '#9e9e9e', 10, 1),
('spectrum_procedure_status', 'Collections Procedure Status', 'in_progress', 'In Progress', '#2196f3', 20, 0),
('spectrum_procedure_status', 'Collections Procedure Status', 'completed', 'Completed', '#4caf50', 30, 0),
('spectrum_procedure_status', 'Collections Procedure Status', 'on_hold', 'On Hold', '#ff9800', 40, 0);

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

-- ============================================================
-- SEED DATA: Embargo Status
-- ============================================================
INSERT IGNORE INTO `ahg_dropdown` (`taxonomy`, `taxonomy_label`, `code`, `label`, `color`, `sort_order`, `is_default`) VALUES
('embargo_status', 'Embargo Status', 'active', 'Active', '#f44336', 10, 1),
('embargo_status', 'Embargo Status', 'pending', 'Pending', '#ff9800', 20, 0),
('embargo_status', 'Embargo Status', 'extended', 'Extended', '#9c27b0', 30, 0),
('embargo_status', 'Embargo Status', 'expired', 'Expired', '#9e9e9e', 40, 0),
('embargo_status', 'Embargo Status', 'lifted', 'Lifted', '#4caf50', 50, 0);

-- ============================================================
-- SEED DATA: ID Type (Research/Visitor Registration)
-- ============================================================
INSERT IGNORE INTO `ahg_dropdown` (`taxonomy`, `taxonomy_label`, `code`, `label`, `sort_order`, `is_default`) VALUES
('id_type', 'ID Type', 'passport', 'Passport', 10, 0),
('id_type', 'ID Type', 'national_id', 'National ID', 20, 1),
('id_type', 'ID Type', 'drivers_license', 'Driver''s License', 30, 0),
('id_type', 'ID Type', 'student_card', 'Student Card', 40, 0),
('id_type', 'ID Type', 'employee_id', 'Employee ID', 50, 0),
('id_type', 'ID Type', 'other', 'Other', 90, 0);

-- ============================================================
-- SEED DATA: Organization Type (Research/Visitor)
-- ============================================================
INSERT IGNORE INTO `ahg_dropdown` (`taxonomy`, `taxonomy_label`, `code`, `label`, `sort_order`, `is_default`) VALUES
('organization_type', 'Organization Type', 'independent', 'Independent Researcher', 10, 0),
('organization_type', 'Organization Type', 'academic', 'Academic Institution', 20, 1),
('organization_type', 'Organization Type', 'government', 'Government', 30, 0),
('organization_type', 'Organization Type', 'private', 'Private Company', 40, 0),
('organization_type', 'Organization Type', 'nonprofit', 'Non-Profit Organization', 50, 0),
('organization_type', 'Organization Type', 'student', 'Student', 60, 0),
('organization_type', 'Organization Type', 'media', 'Media/Press', 70, 0),
('organization_type', 'Organization Type', 'other', 'Other', 90, 0);

-- ============================================================
-- SEED DATA: Equipment Type (Reading Room)
-- ============================================================
INSERT IGNORE INTO `ahg_dropdown` (`taxonomy`, `taxonomy_label`, `code`, `label`, `sort_order`) VALUES
('equipment_type', 'Equipment Type', 'microfilm_reader', 'Microfilm Reader', 10),
('equipment_type', 'Equipment Type', 'microfiche_reader', 'Microfiche Reader', 20),
('equipment_type', 'Equipment Type', 'scanner', 'Scanner', 30),
('equipment_type', 'Equipment Type', 'computer', 'Computer Workstation', 40),
('equipment_type', 'Equipment Type', 'laptop', 'Laptop', 50),
('equipment_type', 'Equipment Type', 'magnifier', 'Magnifier/Loupe', 60),
('equipment_type', 'Equipment Type', 'light_box', 'Light Box', 70),
('equipment_type', 'Equipment Type', 'camera_stand', 'Camera Stand', 80),
('equipment_type', 'Equipment Type', 'projector', 'Projector', 90),
('equipment_type', 'Equipment Type', 'audio_player', 'Audio Player', 100),
('equipment_type', 'Equipment Type', 'video_player', 'Video Player', 110),
('equipment_type', 'Equipment Type', 'other', 'Other', 200);

-- ============================================================
-- SEED DATA: Equipment Condition
-- ============================================================
INSERT IGNORE INTO `ahg_dropdown` (`taxonomy`, `taxonomy_label`, `code`, `label`, `color`, `sort_order`, `is_default`) VALUES
('equipment_condition', 'Equipment Condition', 'excellent', 'Excellent', '#4caf50', 10, 0),
('equipment_condition', 'Equipment Condition', 'good', 'Good', '#8bc34a', 20, 1),
('equipment_condition', 'Equipment Condition', 'fair', 'Fair', '#ff9800', 30, 0),
('equipment_condition', 'Equipment Condition', 'needs_repair', 'Needs Repair', '#ff5722', 40, 0),
('equipment_condition', 'Equipment Condition', 'out_of_service', 'Out of Service', '#f44336', 50, 0);

-- ============================================================
-- SEED DATA: Workspace Privacy (Research Workspaces)
-- ============================================================
INSERT IGNORE INTO `ahg_dropdown` (`taxonomy`, `taxonomy_label`, `code`, `label`, `sort_order`, `is_default`) VALUES
('workspace_privacy', 'Workspace Privacy', 'private', 'Private (Only Me)', 10, 1),
('workspace_privacy', 'Workspace Privacy', 'members', 'Members Only', 20, 0),
('workspace_privacy', 'Workspace Privacy', 'public', 'Public', 30, 0);

-- ============================================================
-- SEED DATA: Creator Role (Library/Bibliographic)
-- ============================================================
INSERT IGNORE INTO `ahg_dropdown` (`taxonomy`, `taxonomy_label`, `code`, `label`, `sort_order`, `is_default`) VALUES
('creator_role', 'Creator Role', 'author', 'Author', 10, 1),
('creator_role', 'Creator Role', 'editor', 'Editor', 20, 0),
('creator_role', 'Creator Role', 'translator', 'Translator', 30, 0),
('creator_role', 'Creator Role', 'illustrator', 'Illustrator', 40, 0),
('creator_role', 'Creator Role', 'compiler', 'Compiler', 50, 0),
('creator_role', 'Creator Role', 'contributor', 'Contributor', 60, 0),
('creator_role', 'Creator Role', 'photographer', 'Photographer', 70, 0),
('creator_role', 'Creator Role', 'composer', 'Composer', 80, 0),
('creator_role', 'Creator Role', 'director', 'Director', 90, 0),
('creator_role', 'Creator Role', 'producer', 'Producer', 100, 0),
('creator_role', 'Creator Role', 'narrator', 'Narrator', 110, 0),
('creator_role', 'Creator Role', 'other', 'Other', 200, 0);

-- ============================================================
-- SEED DATA: Document Type (Donor Agreements)
-- ============================================================
INSERT IGNORE INTO `ahg_dropdown` (`taxonomy`, `taxonomy_label`, `code`, `label`, `sort_order`, `is_default`) VALUES
('document_type', 'Document Type', 'signed_agreement', 'Signed Agreement', 10, 1),
('document_type', 'Document Type', 'draft', 'Draft', 20, 0),
('document_type', 'Document Type', 'amendment', 'Amendment', 30, 0),
('document_type', 'Document Type', 'addendum', 'Addendum', 40, 0),
('document_type', 'Document Type', 'correspondence', 'Correspondence', 50, 0),
('document_type', 'Document Type', 'inventory', 'Inventory List', 60, 0),
('document_type', 'Document Type', 'provenance_evidence', 'Provenance Evidence', 70, 0),
('document_type', 'Document Type', 'appraisal', 'Appraisal Report', 80, 0),
('document_type', 'Document Type', 'receipt', 'Receipt', 90, 0),
('document_type', 'Document Type', 'other', 'Other', 200, 0);

-- ============================================================
-- SEED DATA: Reminder Type (Agreements/Loans)
-- ============================================================
INSERT IGNORE INTO `ahg_dropdown` (`taxonomy`, `taxonomy_label`, `code`, `label`, `sort_order`, `is_default`) VALUES
('reminder_type', 'Reminder Type', 'review_due', 'Review Due', 10, 1),
('reminder_type', 'Reminder Type', 'expiry_warning', 'Expiry Warning', 20, 0),
('reminder_type', 'Reminder Type', 'renewal_required', 'Renewal Required', 30, 0),
('reminder_type', 'Reminder Type', 'donor_contact', 'Donor Contact', 40, 0),
('reminder_type', 'Reminder Type', 'follow_up', 'Follow Up', 50, 0),
('reminder_type', 'Reminder Type', 'custom', 'Custom', 90, 0);

-- ============================================================
-- SEED DATA: RDF Export Format
-- ============================================================
INSERT IGNORE INTO `ahg_dropdown` (`taxonomy`, `taxonomy_label`, `code`, `label`, `sort_order`, `is_default`) VALUES
('rdf_format', 'RDF Export Format', 'jsonld', 'JSON-LD', 10, 1),
('rdf_format', 'RDF Export Format', 'turtle', 'Turtle (.ttl)', 20, 0),
('rdf_format', 'RDF Export Format', 'rdfxml', 'RDF/XML', 30, 0),
('rdf_format', 'RDF Export Format', 'ntriples', 'N-Triples', 40, 0),
('rdf_format', 'RDF Export Format', 'n3', 'Notation3 (N3)', 50, 0);

-- ============================================================
-- SEED DATA: Federation Sync Direction
-- ============================================================
INSERT IGNORE INTO `ahg_dropdown` (`taxonomy`, `taxonomy_label`, `code`, `label`, `sort_order`, `is_default`) VALUES
('federation_sync_direction', 'Federation Sync Direction', 'pull', 'Pull (from remote)', 10, 1),
('federation_sync_direction', 'Federation Sync Direction', 'push', 'Push (to remote)', 20, 0),
('federation_sync_direction', 'Federation Sync Direction', 'bidirectional', 'Bidirectional', 30, 0);

-- ============================================================
-- SEED DATA: Federation Conflict Resolution
-- ============================================================
INSERT IGNORE INTO `ahg_dropdown` (`taxonomy`, `taxonomy_label`, `code`, `label`, `sort_order`, `is_default`) VALUES
('federation_conflict_resolution', 'Federation Conflict Resolution', 'prefer_local', 'Prefer Local', 10, 0),
('federation_conflict_resolution', 'Federation Conflict Resolution', 'prefer_remote', 'Prefer Remote', 20, 0),
('federation_conflict_resolution', 'Federation Conflict Resolution', 'skip', 'Skip Conflicts', 30, 1),
('federation_conflict_resolution', 'Federation Conflict Resolution', 'merge', 'Merge', 40, 0);

-- ============================================================
-- SEED DATA: Federation Harvest Action
-- ============================================================
INSERT IGNORE INTO `ahg_dropdown` (`taxonomy`, `taxonomy_label`, `code`, `label`, `color`, `sort_order`) VALUES
('federation_harvest_action', 'Federation Harvest Action', 'created', 'Created', '#4caf50', 10),
('federation_harvest_action', 'Federation Harvest Action', 'updated', 'Updated', '#2196f3', 20),
('federation_harvest_action', 'Federation Harvest Action', 'deleted', 'Deleted', '#f44336', 30);

-- ============================================================
-- SEED DATA: Federation Session Status
-- ============================================================
INSERT IGNORE INTO `ahg_dropdown` (`taxonomy`, `taxonomy_label`, `code`, `label`, `color`, `sort_order`, `is_default`) VALUES
('federation_session_status', 'Federation Session Status', 'running', 'Running', '#2196f3', 10, 1),
('federation_session_status', 'Federation Session Status', 'completed', 'Completed', '#4caf50', 20, 0),
('federation_session_status', 'Federation Session Status', 'failed', 'Failed', '#f44336', 30, 0),
('federation_session_status', 'Federation Session Status', 'cancelled', 'Cancelled', '#9e9e9e', 40, 0);

-- ============================================================
-- SEED DATA: Federation Mapping Status
-- ============================================================
INSERT IGNORE INTO `ahg_dropdown` (`taxonomy`, `taxonomy_label`, `code`, `label`, `color`, `sort_order`, `is_default`) VALUES
('federation_mapping_status', 'Federation Mapping Status', 'matched', 'Matched', '#4caf50', 10, 1),
('federation_mapping_status', 'Federation Mapping Status', 'created', 'Created', '#2196f3', 20, 0),
('federation_mapping_status', 'Federation Mapping Status', 'conflict', 'Conflict', '#ff9800', 30, 0),
('federation_mapping_status', 'Federation Mapping Status', 'skipped', 'Skipped', '#9e9e9e', 40, 0);

-- ============================================================
-- SEED DATA: Federation Change Type
-- ============================================================
INSERT IGNORE INTO `ahg_dropdown` (`taxonomy`, `taxonomy_label`, `code`, `label`, `sort_order`) VALUES
('federation_change_type', 'Federation Change Type', 'term_added', 'Term Added', 10),
('federation_change_type', 'Federation Change Type', 'term_updated', 'Term Updated', 20),
('federation_change_type', 'Federation Change Type', 'term_deleted', 'Term Deleted', 30),
('federation_change_type', 'Federation Change Type', 'term_moved', 'Term Moved', 40),
('federation_change_type', 'Federation Change Type', 'relation_added', 'Relation Added', 50),
('federation_change_type', 'Federation Change Type', 'relation_removed', 'Relation Removed', 60);

-- ============================================================
-- SEED DATA: Federation Search Status
-- ============================================================
INSERT IGNORE INTO `ahg_dropdown` (`taxonomy`, `taxonomy_label`, `code`, `label`, `color`, `sort_order`, `is_default`) VALUES
('federation_search_status', 'Federation Search Status', 'success', 'Success', '#4caf50', 10, 1),
('federation_search_status', 'Federation Search Status', 'timeout', 'Timeout', '#ff9800', 20, 0),
('federation_search_status', 'Federation Search Status', 'error', 'Error', '#f44336', 30, 0);

-- ============================================================
-- SEED DATA: Restriction Code (Access Restrictions)
-- Base set of access restriction vocabularies for any institution.
-- Institutions add their own codes via Admin > Dropdown Manager.
-- ============================================================
INSERT IGNORE INTO `ahg_dropdown` (`taxonomy`, `taxonomy_label`, `code`, `label`, `color`, `sort_order`, `is_default`) VALUES
('restriction_code', 'Access Restriction', 'open', 'Open / Unrestricted', '#4caf50', 10, 1),
('restriction_code', 'Access Restriction', 'closed', 'Closed', '#f44336', 20, 0),
('restriction_code', 'Access Restriction', 'restricted_time', 'Time-based Restriction', '#ff9800', 30, 0),
('restriction_code', 'Access Restriction', 'restricted_permission', 'Permission Required', '#2196f3', 40, 0),
('restriction_code', 'Access Restriction', 'restricted_privacy', 'Privacy Restriction', '#9c27b0', 50, 0),
('restriction_code', 'Access Restriction', 'restricted_legal', 'Legal Hold', '#795548', 60, 0),
('restriction_code', 'Access Restriction', 'restricted_cultural', 'Cultural Protocol', '#607d8b', 70, 0),
('restriction_code', 'Access Restriction', 'restricted_security', 'Security Classification', '#e91e63', 80, 0),
('restriction_code', 'Access Restriction', 'restricted_donor', 'Donor Restriction', '#ff5722', 90, 0);

-- ============================================================
-- ENUM TO DROPDOWN MIGRATION DATA
-- These values replace hardcoded ENUM columns across all AHG plugins
-- Additional migrations: enum_to_dropdown_migration_phase2*.sql
-- ============================================================

-- ---------------------------------------------------------------------------
-- JOB/TASK STATUS (used by: ahg_ai_batch, ahg_ai_job, ahg_dedupe_scan, etc.)
-- ---------------------------------------------------------------------------
INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('job_status', 'Job Status', 'pending', 'Pending', '#6c757d', 10, 1, NOW()),
('job_status', 'Job Status', 'queued', 'Queued', '#17a2b8', 20, 1, NOW()),
('job_status', 'Job Status', 'running', 'Running', '#007bff', 30, 1, NOW()),
('job_status', 'Job Status', 'paused', 'Paused', '#ffc107', 40, 1, NOW()),
('job_status', 'Job Status', 'completed', 'Completed', '#28a745', 50, 1, NOW()),
('job_status', 'Job Status', 'failed', 'Failed', '#dc3545', 60, 1, NOW()),
('job_status', 'Job Status', 'cancelled', 'Cancelled', '#6c757d', 70, 1, NOW()),
('job_status', 'Job Status', 'skipped', 'Skipped', '#868e96', 80, 1, NOW());

-- ---------------------------------------------------------------------------
-- APPROVAL STATUS
-- ---------------------------------------------------------------------------
INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('approval_status', 'Approval Status', 'pending', 'Pending', '#ffc107', 10, 1, NOW()),
('approval_status', 'Approval Status', 'approved', 'Approved', '#28a745', 20, 1, NOW()),
('approval_status', 'Approval Status', 'rejected', 'Rejected', '#dc3545', 30, 1, NOW()),
('approval_status', 'Approval Status', 'returned', 'Returned', '#fd7e14', 40, 1, NOW()),
('approval_status', 'Approval Status', 'escalated', 'Escalated', '#e83e8c', 50, 1, NOW()),
('approval_status', 'Approval Status', 'edited', 'Edited', '#17a2b8', 60, 1, NOW());

-- ---------------------------------------------------------------------------
-- PRIORITY LEVELS
-- ---------------------------------------------------------------------------
INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('priority_level', 'Priority Level', 'low', 'Low', '#28a745', 10, 1, NOW()),
('priority_level', 'Priority Level', 'normal', 'Normal', '#007bff', 20, 1, NOW()),
('priority_level', 'Priority Level', 'high', 'High', '#fd7e14', 30, 1, NOW()),
('priority_level', 'Priority Level', 'urgent', 'Urgent', '#dc3545', 40, 1, NOW());

-- ---------------------------------------------------------------------------
-- RISK/SEVERITY LEVELS
-- ---------------------------------------------------------------------------
INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('risk_level', 'Risk Level', 'low', 'Low', '#28a745', 10, 1, NOW()),
('risk_level', 'Risk Level', 'medium', 'Medium', '#ffc107', 20, 1, NOW()),
('risk_level', 'Risk Level', 'high', 'High', '#fd7e14', 30, 1, NOW()),
('risk_level', 'Risk Level', 'critical', 'Critical', '#dc3545', 40, 1, NOW());

-- ---------------------------------------------------------------------------
-- VENDOR STATUS
-- ---------------------------------------------------------------------------
INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('vendor_status', 'Vendor Status', 'active', 'Active', '#28a745', 10, 1, NOW()),
('vendor_status', 'Vendor Status', 'inactive', 'Inactive', '#6c757d', 20, 1, NOW()),
('vendor_status', 'Vendor Status', 'suspended', 'Suspended', '#dc3545', 30, 1, NOW()),
('vendor_status', 'Vendor Status', 'pending_approval', 'Pending Approval', '#ffc107', 40, 1, NOW());

-- ---------------------------------------------------------------------------
-- VENDOR TYPE
-- ---------------------------------------------------------------------------
INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('vendor_type', 'Vendor Type', 'company', 'Company', '#007bff', 10, 1, NOW()),
('vendor_type', 'Vendor Type', 'individual', 'Individual', '#28a745', 20, 1, NOW()),
('vendor_type', 'Vendor Type', 'institution', 'Institution', '#6f42c1', 30, 1, NOW()),
('vendor_type', 'Vendor Type', 'government', 'Government', '#fd7e14', 40, 1, NOW());

-- ---------------------------------------------------------------------------
-- CONTRACT STATUS
-- ---------------------------------------------------------------------------
INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('contract_status', 'Contract Status', 'draft', 'Draft', '#6c757d', 10, 1, NOW()),
('contract_status', 'Contract Status', 'pending_review', 'Pending Review', '#ffc107', 20, 1, NOW()),
('contract_status', 'Contract Status', 'pending_signature', 'Pending Signature', '#17a2b8', 30, 1, NOW()),
('contract_status', 'Contract Status', 'active', 'Active', '#28a745', 40, 1, NOW()),
('contract_status', 'Contract Status', 'suspended', 'Suspended', '#fd7e14', 50, 1, NOW()),
('contract_status', 'Contract Status', 'expired', 'Expired', '#dc3545', 60, 1, NOW()),
('contract_status', 'Contract Status', 'terminated', 'Terminated', '#343a40', 70, 1, NOW()),
('contract_status', 'Contract Status', 'renewed', 'Renewed', '#007bff', 80, 1, NOW()),
('contract_status', 'Contract Status', 'superseded', 'Superseded', '#868e96', 90, 1, NOW());

-- ---------------------------------------------------------------------------
-- COUNTERPARTY TYPE
-- ---------------------------------------------------------------------------
INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('counterparty_type', 'Counterparty Type', 'vendor', 'Vendor/Supplier', '#007bff', 10, 1, NOW()),
('counterparty_type', 'Counterparty Type', 'institution', 'Institution', '#6f42c1', 20, 1, NOW()),
('counterparty_type', 'Counterparty Type', 'individual', 'Individual', '#28a745', 30, 1, NOW()),
('counterparty_type', 'Counterparty Type', 'government', 'Government', '#fd7e14', 40, 1, NOW()),
('counterparty_type', 'Counterparty Type', 'other', 'Other', '#6c757d', 50, 1, NOW());

-- ---------------------------------------------------------------------------
-- PAYMENT FREQUENCY
-- ---------------------------------------------------------------------------
INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('payment_frequency', 'Payment Frequency', 'once', 'Once', '#6c757d', 10, 1, NOW()),
('payment_frequency', 'Payment Frequency', 'monthly', 'Monthly', '#007bff', 20, 1, NOW()),
('payment_frequency', 'Payment Frequency', 'quarterly', 'Quarterly', '#17a2b8', 30, 1, NOW()),
('payment_frequency', 'Payment Frequency', 'annually', 'Annually', '#28a745', 40, 1, NOW()),
('payment_frequency', 'Payment Frequency', 'on_delivery', 'On Delivery', '#fd7e14', 50, 1, NOW());

-- ---------------------------------------------------------------------------
-- RECURRENCE PATTERN
-- ---------------------------------------------------------------------------
INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('recurrence_pattern', 'Recurrence Pattern', 'daily', 'Daily', '#dc3545', 10, 1, NOW()),
('recurrence_pattern', 'Recurrence Pattern', 'weekly', 'Weekly', '#fd7e14', 20, 1, NOW()),
('recurrence_pattern', 'Recurrence Pattern', 'monthly', 'Monthly', '#ffc107', 30, 1, NOW()),
('recurrence_pattern', 'Recurrence Pattern', 'quarterly', 'Quarterly', '#28a745', 40, 1, NOW()),
('recurrence_pattern', 'Recurrence Pattern', 'yearly', 'Yearly', '#007bff', 50, 1, NOW());

-- ---------------------------------------------------------------------------
-- GLAM SECTOR
-- ---------------------------------------------------------------------------
INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('glam_sector', 'GLAM Sector', 'archive', 'Archive', '#007bff', 10, 1, NOW()),
('glam_sector', 'GLAM Sector', 'library', 'Library', '#28a745', 20, 1, NOW()),
('glam_sector', 'GLAM Sector', 'museum', 'Museum', '#6f42c1', 30, 1, NOW()),
('glam_sector', 'GLAM Sector', 'gallery', 'Gallery', '#fd7e14', 40, 1, NOW()),
('glam_sector', 'GLAM Sector', 'dam', 'Digital Asset Management', '#17a2b8', 50, 1, NOW());

-- ---------------------------------------------------------------------------
-- NOTIFICATION STATUS
-- ---------------------------------------------------------------------------
INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('notification_status', 'Notification Status', 'pending', 'Pending', '#ffc107', 10, 1, NOW()),
('notification_status', 'Notification Status', 'sent', 'Sent', '#28a745', 20, 1, NOW()),
('notification_status', 'Notification Status', 'failed', 'Failed', '#dc3545', 30, 1, NOW()),
('notification_status', 'Notification Status', 'bounced', 'Bounced', '#fd7e14', 40, 1, NOW()),
('notification_status', 'Notification Status', 'cancelled', 'Cancelled', '#6c757d', 50, 1, NOW());

-- ---------------------------------------------------------------------------
-- SETTING TYPE
-- ---------------------------------------------------------------------------
INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('setting_type', 'Setting Type', 'string', 'String', '#007bff', 10, 1, NOW()),
('setting_type', 'Setting Type', 'integer', 'Integer', '#28a745', 20, 1, NOW()),
('setting_type', 'Setting Type', 'float', 'Float', '#17a2b8', 30, 1, NOW()),
('setting_type', 'Setting Type', 'boolean', 'Boolean', '#ffc107', 40, 1, NOW()),
('setting_type', 'Setting Type', 'json', 'JSON', '#6f42c1', 50, 1, NOW()),
('setting_type', 'Setting Type', 'array', 'Array', '#fd7e14', 60, 1, NOW());

-- ---------------------------------------------------------------------------
-- DUPLICATE DETECTION STATUS
-- ---------------------------------------------------------------------------
INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('duplicate_status', 'Duplicate Status', 'pending', 'Pending Review', '#ffc107', 10, 1, NOW()),
('duplicate_status', 'Duplicate Status', 'confirmed', 'Confirmed', '#dc3545', 20, 1, NOW()),
('duplicate_status', 'Duplicate Status', 'dismissed', 'Dismissed', '#6c757d', 30, 1, NOW()),
('duplicate_status', 'Duplicate Status', 'merged', 'Merged', '#28a745', 40, 1, NOW());

-- ---------------------------------------------------------------------------
-- DOI STATUS
-- ---------------------------------------------------------------------------
INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('doi_status', 'DOI Status', 'draft', 'Draft', '#6c757d', 10, 1, NOW()),
('doi_status', 'DOI Status', 'registered', 'Registered', '#17a2b8', 20, 1, NOW()),
('doi_status', 'DOI Status', 'findable', 'Findable', '#28a745', 30, 1, NOW()),
('doi_status', 'DOI Status', 'failed', 'Failed', '#dc3545', 40, 1, NOW()),
('doi_status', 'DOI Status', 'deleted', 'Deleted', '#343a40', 50, 1, NOW());

-- ---------------------------------------------------------------------------
-- WEBHOOK STATUS
-- ---------------------------------------------------------------------------
INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('webhook_status', 'Webhook Status', 'pending', 'Pending', '#ffc107', 10, 1, NOW()),
('webhook_status', 'Webhook Status', 'success', 'Success', '#28a745', 20, 1, NOW()),
('webhook_status', 'Webhook Status', 'failed', 'Failed', '#dc3545', 30, 1, NOW()),
('webhook_status', 'Webhook Status', 'retrying', 'Retrying', '#fd7e14', 40, 1, NOW());

-- ---------------------------------------------------------------------------
-- NER ENTITY TYPES
-- ---------------------------------------------------------------------------
INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('ner_correction_type', 'NER Correction Type', 'none', 'None', '#6c757d', 10, 1, NOW()),
('ner_correction_type', 'NER Correction Type', 'value_edit', 'Value Edited', '#17a2b8', 20, 1, NOW()),
('ner_correction_type', 'NER Correction Type', 'type_change', 'Type Changed', '#fd7e14', 30, 1, NOW()),
('ner_correction_type', 'NER Correction Type', 'both', 'Both Changed', '#6f42c1', 40, 1, NOW()),
('ner_correction_type', 'NER Correction Type', 'rejected', 'Rejected', '#dc3545', 50, 1, NOW()),
('ner_correction_type', 'NER Correction Type', 'approved', 'Approved', '#28a745', 60, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('ner_link_type', 'NER Link Type', 'exact', 'Exact Match', '#28a745', 10, 1, NOW()),
('ner_link_type', 'NER Link Type', 'fuzzy', 'Fuzzy Match', '#ffc107', 20, 1, NOW()),
('ner_link_type', 'NER Link Type', 'manual', 'Manual', '#007bff', 30, 1, NOW());

-- ---------------------------------------------------------------------------
-- SPELLCHECK/TRANSLATION STATUS
-- ---------------------------------------------------------------------------
INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('spellcheck_status', 'Spellcheck Status', 'pending', 'Pending', '#ffc107', 10, 1, NOW()),
('spellcheck_status', 'Spellcheck Status', 'reviewed', 'Reviewed', '#28a745', 20, 1, NOW()),
('spellcheck_status', 'Spellcheck Status', 'ignored', 'Ignored', '#6c757d', 30, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('translation_status', 'Translation Status', 'draft', 'Draft', '#6c757d', 10, 1, NOW()),
('translation_status', 'Translation Status', 'applied', 'Applied', '#28a745', 20, 1, NOW()),
('translation_status', 'Translation Status', 'rejected', 'Rejected', '#dc3545', 30, 1, NOW());

-- ---------------------------------------------------------------------------
-- ORDER/PAYMENT STATUS
-- ---------------------------------------------------------------------------
INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('order_status', 'Order Status', 'pending', 'Pending', '#ffc107', 10, 1, NOW()),
('order_status', 'Order Status', 'paid', 'Paid', '#28a745', 20, 1, NOW()),
('order_status', 'Order Status', 'processing', 'Processing', '#007bff', 30, 1, NOW()),
('order_status', 'Order Status', 'completed', 'Completed', '#20c997', 40, 1, NOW()),
('order_status', 'Order Status', 'cancelled', 'Cancelled', '#6c757d', 50, 1, NOW()),
('order_status', 'Order Status', 'refunded', 'Refunded', '#dc3545', 60, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('payment_status', 'Payment Status', 'pending', 'Pending', '#ffc107', 10, 1, NOW()),
('payment_status', 'Payment Status', 'processing', 'Processing', '#17a2b8', 20, 1, NOW()),
('payment_status', 'Payment Status', 'completed', 'Completed', '#28a745', 30, 1, NOW()),
('payment_status', 'Payment Status', 'failed', 'Failed', '#dc3545', 40, 1, NOW()),
('payment_status', 'Payment Status', 'refunded', 'Refunded', '#fd7e14', 50, 1, NOW()),
('payment_status', 'Payment Status', 'not_invoiced', 'Not Invoiced', '#6c757d', 60, 1, NOW()),
('payment_status', 'Payment Status', 'invoiced', 'Invoiced', '#007bff', 70, 1, NOW()),
('payment_status', 'Payment Status', 'disputed', 'Disputed', '#e83e8c', 80, 1, NOW());

-- ---------------------------------------------------------------------------
-- WORKFLOW TYPES
-- ---------------------------------------------------------------------------
INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('workflow_task_status', 'Workflow Task Status', 'pending', 'Pending', '#6c757d', 10, 1, NOW()),
('workflow_task_status', 'Workflow Task Status', 'claimed', 'Claimed', '#17a2b8', 20, 1, NOW()),
('workflow_task_status', 'Workflow Task Status', 'in_progress', 'In Progress', '#007bff', 30, 1, NOW()),
('workflow_task_status', 'Workflow Task Status', 'approved', 'Approved', '#28a745', 40, 1, NOW()),
('workflow_task_status', 'Workflow Task Status', 'rejected', 'Rejected', '#dc3545', 50, 1, NOW()),
('workflow_task_status', 'Workflow Task Status', 'returned', 'Returned', '#fd7e14', 60, 1, NOW()),
('workflow_task_status', 'Workflow Task Status', 'escalated', 'Escalated', '#e83e8c', 70, 1, NOW()),
('workflow_task_status', 'Workflow Task Status', 'cancelled', 'Cancelled', '#6c757d', 80, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('workflow_trigger', 'Workflow Trigger', 'create', 'On Create', '#28a745', 10, 1, NOW()),
('workflow_trigger', 'Workflow Trigger', 'update', 'On Update', '#007bff', 20, 1, NOW()),
('workflow_trigger', 'Workflow Trigger', 'submit', 'On Submit', '#17a2b8', 30, 1, NOW()),
('workflow_trigger', 'Workflow Trigger', 'publish', 'On Publish', '#6f42c1', 40, 1, NOW()),
('workflow_trigger', 'Workflow Trigger', 'manual', 'Manual', '#6c757d', 50, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('workflow_scope', 'Workflow Scope', 'global', 'Global', '#dc3545', 10, 1, NOW()),
('workflow_scope', 'Workflow Scope', 'repository', 'Repository', '#007bff', 20, 1, NOW()),
('workflow_scope', 'Workflow Scope', 'collection', 'Collection', '#28a745', 30, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('workflow_applies_to', 'Workflow Applies To', 'information_object', 'Information Object', '#007bff', 10, 1, NOW()),
('workflow_applies_to', 'Workflow Applies To', 'actor', 'Actor', '#28a745', 20, 1, NOW()),
('workflow_applies_to', 'Workflow Applies To', 'accession', 'Accession', '#6f42c1', 30, 1, NOW()),
('workflow_applies_to', 'Workflow Applies To', 'digital_object', 'Digital Object', '#fd7e14', 40, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('workflow_step_type', 'Workflow Step Type', 'review', 'Review', '#007bff', 10, 1, NOW()),
('workflow_step_type', 'Workflow Step Type', 'approve', 'Approve', '#28a745', 20, 1, NOW()),
('workflow_step_type', 'Workflow Step Type', 'edit', 'Edit', '#ffc107', 30, 1, NOW()),
('workflow_step_type', 'Workflow Step Type', 'verify', 'Verify', '#17a2b8', 40, 1, NOW()),
('workflow_step_type', 'Workflow Step Type', 'sign_off', 'Sign Off', '#6f42c1', 50, 1, NOW()),
('workflow_step_type', 'Workflow Step Type', 'custom', 'Custom', '#6c757d', 60, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('workflow_action', 'Workflow Action', 'approve', 'Approve', '#28a745', 10, 1, NOW()),
('workflow_action', 'Workflow Action', 'reject', 'Reject', '#dc3545', 20, 1, NOW()),
('workflow_action', 'Workflow Action', 'approve_reject', 'Approve/Reject', '#ffc107', 30, 1, NOW()),
('workflow_action', 'Workflow Action', 'complete', 'Complete', '#007bff', 40, 1, NOW()),
('workflow_action', 'Workflow Action', 'submit', 'Submit', '#17a2b8', 50, 1, NOW());

-- ---------------------------------------------------------------------------
-- FORM FIELD TYPES
-- ---------------------------------------------------------------------------
INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('form_field_type', 'Form Field Type', 'text', 'Text', '#007bff', 10, 1, NOW()),
('form_field_type', 'Form Field Type', 'textarea', 'Textarea', '#28a745', 20, 1, NOW()),
('form_field_type', 'Form Field Type', 'richtext', 'Rich Text', '#6f42c1', 30, 1, NOW()),
('form_field_type', 'Form Field Type', 'date', 'Date', '#fd7e14', 40, 1, NOW()),
('form_field_type', 'Form Field Type', 'daterange', 'Date Range', '#ffc107', 50, 1, NOW()),
('form_field_type', 'Form Field Type', 'select', 'Select', '#17a2b8', 60, 1, NOW()),
('form_field_type', 'Form Field Type', 'multiselect', 'Multi-select', '#20c997', 70, 1, NOW()),
('form_field_type', 'Form Field Type', 'autocomplete', 'Autocomplete', '#e83e8c', 80, 1, NOW()),
('form_field_type', 'Form Field Type', 'checkbox', 'Checkbox', '#343a40', 90, 1, NOW()),
('form_field_type', 'Form Field Type', 'radio', 'Radio', '#6c757d', 100, 1, NOW()),
('form_field_type', 'Form Field Type', 'file', 'File Upload', '#dc3545', 110, 1, NOW()),
('form_field_type', 'Form Field Type', 'hidden', 'Hidden', '#868e96', 120, 1, NOW()),
('form_field_type', 'Form Field Type', 'heading', 'Heading', '#495057', 130, 1, NOW()),
('form_field_type', 'Form Field Type', 'divider', 'Divider', '#adb5bd', 140, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('form_field_width', 'Form Field Width', 'full', 'Full Width', '#007bff', 10, 1, NOW()),
('form_field_width', 'Form Field Width', 'half', 'Half Width', '#28a745', 20, 1, NOW()),
('form_field_width', 'Form Field Width', 'third', 'One Third', '#ffc107', 30, 1, NOW()),
('form_field_width', 'Form Field Width', 'quarter', 'One Quarter', '#fd7e14', 40, 1, NOW());

-- ---------------------------------------------------------------------------
-- LOAN OBJECT STATUS
-- ---------------------------------------------------------------------------
INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('loan_object_status', 'Loan Object Status', 'pending', 'Pending', '#6c757d', 10, 1, NOW()),
('loan_object_status', 'Loan Object Status', 'approved', 'Approved', '#28a745', 20, 1, NOW()),
('loan_object_status', 'Loan Object Status', 'prepared', 'Prepared', '#17a2b8', 30, 1, NOW()),
('loan_object_status', 'Loan Object Status', 'dispatched', 'Dispatched', '#007bff', 40, 1, NOW()),
('loan_object_status', 'Loan Object Status', 'received', 'Received', '#20c997', 50, 1, NOW()),
('loan_object_status', 'Loan Object Status', 'on_display', 'On Display', '#6f42c1', 60, 1, NOW()),
('loan_object_status', 'Loan Object Status', 'packed', 'Packed', '#fd7e14', 70, 1, NOW()),
('loan_object_status', 'Loan Object Status', 'returned', 'Returned', '#343a40', 80, 1, NOW());

-- ---------------------------------------------------------------------------
-- LOAN INSURANCE TYPE
-- ---------------------------------------------------------------------------
INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('loan_insurance_type', 'Loan Insurance Type', 'borrower', 'Borrower', '#007bff', 10, 1, NOW()),
('loan_insurance_type', 'Loan Insurance Type', 'lender', 'Lender', '#28a745', 20, 1, NOW()),
('loan_insurance_type', 'Loan Insurance Type', 'shared', 'Shared', '#6f42c1', 30, 1, NOW()),
('loan_insurance_type', 'Loan Insurance Type', 'government', 'Government', '#fd7e14', 40, 1, NOW()),
('loan_insurance_type', 'Loan Insurance Type', 'self', 'Self-Insured', '#ffc107', 50, 1, NOW()),
('loan_insurance_type', 'Loan Insurance Type', 'none', 'None', '#dc3545', 60, 1, NOW());

-- ---------------------------------------------------------------------------
-- VENDOR TRANSACTION STATUS
-- ---------------------------------------------------------------------------
INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('vendor_transaction_status', 'Vendor Transaction Status', 'pending_approval', 'Pending Approval', '#ffc107', 10, 1, NOW()),
('vendor_transaction_status', 'Vendor Transaction Status', 'approved', 'Approved', '#28a745', 20, 1, NOW()),
('vendor_transaction_status', 'Vendor Transaction Status', 'dispatched', 'Dispatched', '#007bff', 30, 1, NOW()),
('vendor_transaction_status', 'Vendor Transaction Status', 'received_by_vendor', 'Received by Vendor', '#17a2b8', 40, 1, NOW()),
('vendor_transaction_status', 'Vendor Transaction Status', 'in_progress', 'In Progress', '#6f42c1', 50, 1, NOW()),
('vendor_transaction_status', 'Vendor Transaction Status', 'completed', 'Completed', '#20c997', 60, 1, NOW()),
('vendor_transaction_status', 'Vendor Transaction Status', 'ready_for_collection', 'Ready for Collection', '#fd7e14', 70, 1, NOW()),
('vendor_transaction_status', 'Vendor Transaction Status', 'returned', 'Returned', '#343a40', 80, 1, NOW()),
('vendor_transaction_status', 'Vendor Transaction Status', 'cancelled', 'Cancelled', '#dc3545', 90, 1, NOW());

-- ============================================================
-- NOTE: Additional ENUM migrations are in separate files:
-- - enum_to_dropdown_migration_phase2.sql (Access, DAM, Display, Donor, Exhibition, Gallery types)
-- - enum_to_dropdown_migration_phase2b.sql (Heritage, ICIP, IIIF types)
-- - enum_to_dropdown_migration_phase2c.sql (NAZ, NMMZ, OAIS, Preservation types)
-- - enum_to_dropdown_migration_phase2d.sql (Privacy, Provenance, RIC, Rights types)
-- - enum_to_dropdown_migration_phase2e.sql (Research plugin types)
-- Run these separately for full ENUM coverage.
-- ============================================================

-- ============================================================
-- TTS (Text-to-Speech) Settings
-- ============================================================
CREATE TABLE IF NOT EXISTS `ahg_tts_settings` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `sector` VARCHAR(50) NOT NULL DEFAULT 'all' COMMENT 'all, archive, library, museum, gallery, dam',
    `setting_key` VARCHAR(100) NOT NULL,
    `setting_value` TEXT,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_sector_key` (`sector`, `setting_key`),
    INDEX `idx_sector` (`sector`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Default TTS settings
INSERT IGNORE INTO `ahg_tts_settings` (`sector`, `setting_key`, `setting_value`) VALUES
('all', 'enabled', '1'),
('all', 'default_rate', '1.0'),
('all', 'read_labels', '1'),
('all', 'keyboard_shortcuts', '1'),
('archive', 'fields_to_read', '["title","scopeAndContent","arrangement"]'),
('library', 'fields_to_read', '["title","scopeAndContent","abstract"]'),
('museum', 'fields_to_read', '["title","scopeAndContent","physicalDescription"]'),
('gallery', 'fields_to_read', '["title","scopeAndContent","medium"]'),
('dam', 'fields_to_read', '["title","scopeAndContent","technicalNotes"]');

-- ============================================================================
-- EMAIL SETTINGS
-- ============================================================================

CREATE TABLE IF NOT EXISTS `email_setting` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `setting_key` VARCHAR(100) NOT NULL,
    `setting_value` TEXT,
    `setting_type` VARCHAR(20) DEFAULT 'text',
    `setting_group` VARCHAR(50) DEFAULT 'general',
    `description` VARCHAR(255) DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `setting_key` (`setting_key`),
    KEY `idx_key` (`setting_key`),
    KEY `idx_group` (`setting_group`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- SMTP settings (values configured per instance via Admin > Settings > Email)
INSERT IGNORE INTO `email_setting` (`setting_key`, `setting_value`, `setting_type`, `setting_group`, `description`) VALUES
('smtp_enabled', '0', 'boolean', 'smtp', 'Enable email sending'),
('smtp_host', '', 'text', 'smtp', 'SMTP server hostname'),
('smtp_port', '587', 'number', 'smtp', 'SMTP server port'),
('smtp_encryption', 'tls', 'text', 'smtp', 'Encryption type (tls, ssl, or empty)'),
('smtp_username', '', 'text', 'smtp', 'SMTP username'),
('smtp_password', '', 'password', 'smtp', 'SMTP password'),
('smtp_from_email', '', 'email', 'smtp', 'From email address'),
('smtp_from_name', 'AtoM Archive', 'text', 'smtp', 'From name'),
('notify_new_researcher', '', 'email', 'notifications', 'Email to notify of new researcher registrations'),
('notify_new_booking', '', 'email', 'notifications', 'Email to notify of new booking requests'),
('notify_errors', '', 'email', 'notifications', 'Admin email address to receive system error alerts'),
('email_researcher_pending_subject', 'Registration Received - Pending Approval', 'text', 'templates', 'Subject for pending registration email'),
('email_researcher_pending_body', 'Dear {name},\n\nThank you for registering as a researcher. Your application is being reviewed.\n\nYou will receive an email once your account has been approved.\n\nRegards,\nThe Archive Team', 'textarea', 'templates', 'Body for pending registration email'),
('email_researcher_approved_subject', 'Registration Approved', 'text', 'templates', 'Subject for approved registration email'),
('email_researcher_approved_body', 'Dear {name},\n\nYour researcher registration has been approved!\n\nYou can now log in and book reading room visits at:\n{login_url}\n\nRegards,\nThe Archive Team', 'textarea', 'templates', 'Body for approved registration email'),
('email_researcher_rejected_subject', 'Registration Not Approved', 'text', 'templates', 'Subject for rejected registration email'),
('email_researcher_rejected_body', 'Dear {name},\n\nUnfortunately, your researcher registration was not approved.\n\nReason: {reason}\n\nIf you have questions, please contact us.\n\nRegards,\nThe Archive Team', 'textarea', 'templates', 'Body for rejected registration email'),
('email_password_reset_subject', 'Password Reset Request', 'text', 'templates', 'Subject for password reset email'),
('email_password_reset_body', 'Dear {name},\n\nA password reset was requested for your account.\n\nClick the link below to reset your password:\n{reset_url}\n\nThis link expires in 2 hours.\n\nIf you did not request this, please ignore this email.\n\nRegards,\nThe Archive Team', 'textarea', 'templates', 'Body for password reset email'),
('email_booking_confirmed_subject', 'Booking Confirmed', 'text', 'templates', 'Subject for booking confirmation email'),
('email_booking_confirmed_body', 'Dear {name},\n\nYour reading room booking has been confirmed:\n\nDate: {date}\nTime: {time}\nRoom: {room}\n\nPlease bring valid identification.\n\nRegards,\nThe Archive Team', 'textarea', 'templates', 'Body for booking confirmation email'),
('email_admin_new_researcher_subject', 'New Researcher Registration', 'text', 'templates', 'Subject for admin notification of new researcher'),
('email_admin_new_researcher_body', 'A new researcher has registered:\n\nName: {name}\nEmail: {email}\nInstitution: {institution}\n\nReview at: {review_url}', 'textarea', 'templates', 'Body for admin notification of new researcher'),
('email_error_alert_subject', 'System Error Alert - {hostname}', 'text', 'templates', 'Subject line for error notification emails'),
('email_error_alert_body', 'System Error Alert\n==================\n\nTime: {timestamp}\nHost: {hostname}\nURL: {url}\n\nError: {message}\nFile: {file}\nLine: {line}\n\nStack Trace:\n{trace}', 'textarea', 'templates', 'Body template for error notification emails'),
('email_booking_cancelled_subject', 'Booking Cancelled', 'text', 'templates', 'Subject for booking cancellation email'),
('email_booking_cancelled_body', 'Dear {name},\n\nYour reading room booking for {date} ({time}) in {room} has been cancelled.\n\nIf you have questions, please contact us.\n\nBest regards,\nThe Archive Team', 'textarea', 'templates', 'Body for booking cancellation email. Placeholders: {name}, {date}, {time}, {room}'),
('email_search_alert_subject', 'New results for your saved search: {search_name}', 'text', 'templates', 'Subject for search alert email'),
('email_search_alert_body', 'Dear {name},\n\nYour saved search \"{search_name}\" has {result_count} new result(s).\n\nSearch query: {search_query}\n\nView your saved searches at: {saved_searches_url}\n\nYou can manage your alert settings from your researcher workspace.\n\nBest regards,\nThe Archive Team', 'textarea', 'templates', 'Body for search alert email. Placeholders: {name}, {search_name}, {result_count}, {search_query}, {saved_searches_url}'),
('email_collaborator_invite_subject', 'You have been invited to collaborate on a research project', 'text', 'templates', 'Subject for collaborator invitation email'),
('email_collaborator_invite_body', 'Dear {name},\n\n{inviter_name} has invited you to collaborate on the research project \"{project_title}\" as a {role}.\n\nView the project and accept the invitation:\n{project_url}\n\nBest regards,\nThe Archive Team', 'textarea', 'templates', 'Body for collaborator invitation email. Placeholders: {name}, {inviter_name}, {project_title}, {role}, {project_url}'),
('email_collaborator_external_subject', 'You have been invited to collaborate on a research project', 'text', 'templates', 'Subject for external collaborator invitation email'),
('email_collaborator_external_body', 'Dear Colleague,\n\n{inviter_name} has invited you to collaborate on the research project \"{project_title}\" as a {role}.\n\nTo accept this invitation, you first need to register as a researcher:\n{register_url}\n\nAfter registration and approval, you will be able to join the project.\n\nBest regards,\nThe Archive Team', 'textarea', 'templates', 'Body for external collaborator invitation email. Placeholders: {inviter_name}, {project_title}, {role}, {register_url}'),
('email_peer_review_request_subject', 'Peer Review Request: {report_title}', 'text', 'templates', 'Subject for peer review request email'),
('email_peer_review_request_body', 'Dear {name},\n\n{requester_name} has requested your peer review of the report \"{report_title}\".\n\nPlease review the report at:\n{review_url}\n\nBest regards,\nThe Archive Team', 'textarea', 'templates', 'Body for peer review request email. Placeholders: {name}, {requester_name}, {report_title}, {review_url}');

-- ---------------------------------------------------------------------------
-- Merged in from database/enum_to_dropdown_migration_phase2b.sql on 2026-08-17.
--
-- It sat beside install.sql and was never run by install-plugin-schema.php,
-- so a clean install silently lacked whatever it defines. Our own instances
-- had it because someone applied the file by hand. A plugin's schema is
-- install.sql; there is no second file.
-- ---------------------------------------------------------------------------

-- ============================================================================
-- ENUM to ahg_dropdown Migration Script - PHASE 2B
-- Generated: 2026-02-04
--
-- Continuation of Phase 2 - Heritage, ICIP, IIIF, IPSAS, NAZ, NMMZ,
-- OAIS, Preservation, Privacy, Provenance, Research, Rights types
-- ============================================================================

-- ============================================================================
-- HERITAGE / GRAP TYPES
-- ============================================================================

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('acquisition_method', 'Acquisition Method', 'purchase', 'Purchase', '#28a745', 10, 1, NOW()),
('acquisition_method', 'Acquisition Method', 'donation', 'Donation', '#007bff', 20, 1, NOW()),
('acquisition_method', 'Acquisition Method', 'bequest', 'Bequest', '#6f42c1', 30, 1, NOW()),
('acquisition_method', 'Acquisition Method', 'transfer', 'Transfer', '#17a2b8', 40, 1, NOW()),
('acquisition_method', 'Acquisition Method', 'found', 'Found', '#fd7e14', 50, 1, NOW()),
('acquisition_method', 'Acquisition Method', 'exchange', 'Exchange', '#ffc107', 60, 1, NOW()),
('acquisition_method', 'Acquisition Method', 'excavation', 'Excavation', '#e83e8c', 70, 1, NOW()),
('acquisition_method', 'Acquisition Method', 'confiscation', 'Confiscation', '#dc3545', 80, 1, NOW()),
('acquisition_method', 'Acquisition Method', 'unknown', 'Unknown', '#6c757d', 90, 1, NOW()),
('acquisition_method', 'Acquisition Method', 'other', 'Other', '#868e96', 100, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('measurement_basis', 'Measurement Basis', 'cost', 'Cost', '#007bff', 10, 1, NOW()),
('measurement_basis', 'Measurement Basis', 'fair_value', 'Fair Value', '#28a745', 20, 1, NOW()),
('measurement_basis', 'Measurement Basis', 'nominal', 'Nominal', '#ffc107', 30, 1, NOW()),
('measurement_basis', 'Measurement Basis', 'not_practicable', 'Not Practicable', '#6c757d', 40, 1, NOW()),
('measurement_basis', 'Measurement Basis', 'historical_cost', 'Historical Cost', '#6f42c1', 50, 1, NOW()),
('measurement_basis', 'Measurement Basis', 'replacement_cost', 'Replacement Cost', '#17a2b8', 60, 1, NOW()),
('measurement_basis', 'Measurement Basis', 'not_recognized', 'Not Recognized', '#dc3545', 70, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('valuation_method', 'Valuation Method', 'market', 'Market', '#28a745', 10, 1, NOW()),
('valuation_method', 'Valuation Method', 'cost', 'Cost', '#007bff', 20, 1, NOW()),
('valuation_method', 'Valuation Method', 'income', 'Income', '#6f42c1', 30, 1, NOW()),
('valuation_method', 'Valuation Method', 'expert', 'Expert', '#fd7e14', 40, 1, NOW()),
('valuation_method', 'Valuation Method', 'insurance', 'Insurance', '#dc3545', 50, 1, NOW()),
('valuation_method', 'Valuation Method', 'other', 'Other', '#6c757d', 60, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('depreciation_policy', 'Depreciation Policy', 'not_depreciated', 'Not Depreciated', '#28a745', 10, 1, NOW()),
('depreciation_policy', 'Depreciation Policy', 'straight_line', 'Straight Line', '#007bff', 20, 1, NOW()),
('depreciation_policy', 'Depreciation Policy', 'reducing_balance', 'Reducing Balance', '#6f42c1', 30, 1, NOW()),
('depreciation_policy', 'Depreciation Policy', 'units_of_production', 'Units of Production', '#fd7e14', 40, 1, NOW()),
('depreciation_policy', 'Depreciation Policy', 'none', 'None', '#6c757d', 50, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('derecognition_reason', 'Derecognition Reason', 'disposal', 'Disposal', '#dc3545', 10, 1, NOW()),
('derecognition_reason', 'Derecognition Reason', 'destruction', 'Destruction', '#343a40', 20, 1, NOW()),
('derecognition_reason', 'Derecognition Reason', 'loss', 'Loss', '#fd7e14', 30, 1, NOW()),
('derecognition_reason', 'Derecognition Reason', 'transfer', 'Transfer', '#007bff', 40, 1, NOW()),
('derecognition_reason', 'Derecognition Reason', 'write_off', 'Write Off', '#6c757d', 50, 1, NOW()),
('derecognition_reason', 'Derecognition Reason', 'theft', 'Theft', '#dc3545', 60, 1, NOW()),
('derecognition_reason', 'Derecognition Reason', 'deaccession', 'Deaccession', '#ffc107', 70, 1, NOW()),
('derecognition_reason', 'Derecognition Reason', 'sale', 'Sale', '#28a745', 80, 1, NOW()),
('derecognition_reason', 'Derecognition Reason', 'donation', 'Donation', '#17a2b8', 90, 1, NOW()),
('derecognition_reason', 'Derecognition Reason', 'other', 'Other', '#868e96', 100, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('heritage_significance', 'Heritage Significance', 'exceptional', 'Exceptional', '#dc3545', 10, 1, NOW()),
('heritage_significance', 'Heritage Significance', 'high', 'High', '#fd7e14', 20, 1, NOW()),
('heritage_significance', 'Heritage Significance', 'medium', 'Medium', '#ffc107', 30, 1, NOW()),
('heritage_significance', 'Heritage Significance', 'low', 'Low', '#28a745', 40, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('recognition_status', 'Recognition Status', 'recognised', 'Recognised', '#28a745', 10, 1, NOW()),
('recognition_status', 'Recognition Status', 'not_recognised', 'Not Recognised', '#dc3545', 20, 1, NOW()),
('recognition_status', 'Recognition Status', 'pending', 'Pending', '#ffc107', 30, 1, NOW()),
('recognition_status', 'Recognition Status', 'derecognised', 'Derecognised', '#6c757d', 40, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('revaluation_frequency', 'Revaluation Frequency', 'annual', 'Annual', '#dc3545', 10, 1, NOW()),
('revaluation_frequency', 'Revaluation Frequency', 'triennial', 'Triennial', '#fd7e14', 20, 1, NOW()),
('revaluation_frequency', 'Revaluation Frequency', 'quinquennial', 'Quinquennial', '#ffc107', 30, 1, NOW()),
('revaluation_frequency', 'Revaluation Frequency', 'as_needed', 'As Needed', '#007bff', 40, 1, NOW()),
('revaluation_frequency', 'Revaluation Frequency', 'not_applicable', 'Not Applicable', '#6c757d', 50, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('heritage_audit_action', 'Heritage Audit Action', 'create', 'Create', '#28a745', 10, 1, NOW()),
('heritage_audit_action', 'Heritage Audit Action', 'update', 'Update', '#007bff', 20, 1, NOW()),
('heritage_audit_action', 'Heritage Audit Action', 'delete', 'Delete', '#dc3545', 30, 1, NOW()),
('heritage_audit_action', 'Heritage Audit Action', 'view', 'View', '#6c757d', 40, 1, NOW()),
('heritage_audit_action', 'Heritage Audit Action', 'export', 'Export', '#6f42c1', 50, 1, NOW()),
('heritage_audit_action', 'Heritage Audit Action', 'import', 'Import', '#17a2b8', 60, 1, NOW()),
('heritage_audit_action', 'Heritage Audit Action', 'batch', 'Batch', '#fd7e14', 70, 1, NOW()),
('heritage_audit_action', 'Heritage Audit Action', 'access', 'Access', '#ffc107', 80, 1, NOW()),
('heritage_audit_action', 'Heritage Audit Action', 'system', 'System', '#343a40', 90, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('heritage_batch_status', 'Heritage Batch Status', 'pending', 'Pending', '#ffc107', 10, 1, NOW()),
('heritage_batch_status', 'Heritage Batch Status', 'queued', 'Queued', '#17a2b8', 20, 1, NOW()),
('heritage_batch_status', 'Heritage Batch Status', 'processing', 'Processing', '#007bff', 30, 1, NOW()),
('heritage_batch_status', 'Heritage Batch Status', 'completed', 'Completed', '#28a745', 40, 1, NOW()),
('heritage_batch_status', 'Heritage Batch Status', 'failed', 'Failed', '#dc3545', 50, 1, NOW()),
('heritage_batch_status', 'Heritage Batch Status', 'cancelled', 'Cancelled', '#6c757d', 60, 1, NOW()),
('heritage_batch_status', 'Heritage Batch Status', 'paused', 'Paused', '#fd7e14', 70, 1, NOW()),
('heritage_batch_status', 'Heritage Batch Status', 'success', 'Success', '#28a745', 80, 1, NOW()),
('heritage_batch_status', 'Heritage Batch Status', 'skipped', 'Skipped', '#868e96', 90, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('compliance_check_type', 'Compliance Check Type', 'required_field', 'Required Field', '#dc3545', 10, 1, NOW()),
('compliance_check_type', 'Compliance Check Type', 'value_check', 'Value Check', '#ffc107', 20, 1, NOW()),
('compliance_check_type', 'Compliance Check Type', 'date_check', 'Date Check', '#17a2b8', 30, 1, NOW()),
('compliance_check_type', 'Compliance Check Type', 'custom', 'Custom', '#6c757d', 40, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('compliance_category', 'Compliance Category', 'recognition', 'Recognition', '#007bff', 10, 1, NOW()),
('compliance_category', 'Compliance Category', 'measurement', 'Measurement', '#28a745', 20, 1, NOW()),
('compliance_category', 'Compliance Category', 'disclosure', 'Disclosure', '#6f42c1', 30, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('contribution_status', 'Contribution Status', 'pending', 'Pending', '#ffc107', 10, 1, NOW()),
('contribution_status', 'Contribution Status', 'approved', 'Approved', '#28a745', 20, 1, NOW()),
('contribution_status', 'Contribution Status', 'rejected', 'Rejected', '#dc3545', 30, 1, NOW()),
('contribution_status', 'Contribution Status', 'superseded', 'Superseded', '#6c757d', 40, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('trust_level', 'Trust Level', 'new', 'New', '#6c757d', 10, 1, NOW()),
('trust_level', 'Trust Level', 'contributor', 'Contributor', '#007bff', 20, 1, NOW()),
('trust_level', 'Trust Level', 'trusted', 'Trusted', '#28a745', 30, 1, NOW()),
('trust_level', 'Trust Level', 'expert', 'Expert', '#6f42c1', 40, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('badge_criteria_type', 'Badge Criteria Type', 'contribution_count', 'Contribution Count', '#007bff', 10, 1, NOW()),
('badge_criteria_type', 'Badge Criteria Type', 'approval_rate', 'Approval Rate', '#28a745', 20, 1, NOW()),
('badge_criteria_type', 'Badge Criteria Type', 'points', 'Points', '#ffc107', 30, 1, NOW()),
('badge_criteria_type', 'Badge Criteria Type', 'type_specific', 'Type Specific', '#6f42c1', 40, 1, NOW()),
('badge_criteria_type', 'Badge Criteria Type', 'manual', 'Manual', '#6c757d', 50, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('curated_link_type', 'Curated Link Type', 'collection', 'Collection', '#007bff', 10, 1, NOW()),
('curated_link_type', 'Curated Link Type', 'search', 'Search', '#28a745', 20, 1, NOW()),
('curated_link_type', 'Curated Link Type', 'external', 'External', '#fd7e14', 30, 1, NOW()),
('curated_link_type', 'Curated Link Type', 'page', 'Page', '#6f42c1', 40, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('entity_type', 'Entity Type', 'person', 'Person', '#007bff', 10, 1, NOW()),
('entity_type', 'Entity Type', 'organization', 'Organization', '#28a745', 20, 1, NOW()),
('entity_type', 'Entity Type', 'place', 'Place', '#fd7e14', 30, 1, NOW()),
('entity_type', 'Entity Type', 'date', 'Date', '#17a2b8', 40, 1, NOW()),
('entity_type', 'Entity Type', 'event', 'Event', '#6f42c1', 50, 1, NOW()),
('entity_type', 'Entity Type', 'work', 'Work', '#e83e8c', 60, 1, NOW()),
('entity_type', 'Entity Type', 'family', 'Family', '#ffc107', 70, 1, NOW()),
('entity_type', 'Entity Type', 'unknown', 'Unknown', '#6c757d', 80, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('extraction_method', 'Extraction Method', 'taxonomy', 'Taxonomy', '#007bff', 10, 1, NOW()),
('extraction_method', 'Extraction Method', 'ner', 'NER', '#28a745', 20, 1, NOW()),
('extraction_method', 'Extraction Method', 'pattern', 'Pattern', '#6f42c1', 30, 1, NOW()),
('extraction_method', 'Extraction Method', 'manual', 'Manual', '#fd7e14', 40, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('display_style', 'Display Style', 'grid', 'Grid', '#007bff', 10, 1, NOW()),
('display_style', 'Display Style', 'list', 'List', '#28a745', 20, 1, NOW()),
('display_style', 'Display Style', 'timeline', 'Timeline', '#6f42c1', 30, 1, NOW()),
('display_style', 'Display Style', 'map', 'Map', '#fd7e14', 40, 1, NOW()),
('display_style', 'Display Style', 'carousel', 'Carousel', '#17a2b8', 50, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('source_type', 'Source Type', 'taxonomy', 'Taxonomy', '#007bff', 10, 1, NOW()),
('source_type', 'Source Type', 'authority', 'Authority', '#28a745', 20, 1, NOW()),
('source_type', 'Source Type', 'field', 'Field', '#6f42c1', 30, 1, NOW()),
('source_type', 'Source Type', 'facet', 'Facet', '#fd7e14', 40, 1, NOW()),
('source_type', 'Source Type', 'custom', 'Custom', '#6c757d', 50, 1, NOW()),
('source_type', 'Source Type', 'iiif', 'IIIF', '#17a2b8', 60, 1, NOW()),
('source_type', 'Source Type', 'archival', 'Archival', '#e83e8c', 70, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('cta_style', 'CTA Style', 'primary', 'Primary', '#007bff', 10, 1, NOW()),
('cta_style', 'CTA Style', 'secondary', 'Secondary', '#6c757d', 20, 1, NOW()),
('cta_style', 'CTA Style', 'outline', 'Outline', '#ffc107', 30, 1, NOW()),
('cta_style', 'CTA Style', 'light', 'Light', '#28a745', 40, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('hero_media_type', 'Hero Media Type', 'image', 'Image', '#28a745', 10, 1, NOW()),
('hero_media_type', 'Hero Media Type', 'video', 'Video', '#6f42c1', 20, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('overlay_type', 'Overlay Type', 'none', 'None', '#6c757d', 10, 1, NOW()),
('overlay_type', 'Overlay Type', 'gradient', 'Gradient', '#007bff', 20, 1, NOW()),
('overlay_type', 'Overlay Type', 'solid', 'Solid', '#343a40', 30, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('text_position', 'Text Position', 'left', 'Left', '#007bff', 10, 1, NOW()),
('text_position', 'Text Position', 'center', 'Center', '#28a745', 20, 1, NOW()),
('text_position', 'Text Position', 'right', 'Right', '#6f42c1', 30, 1, NOW()),
('text_position', 'Text Position', 'bottom-left', 'Bottom Left', '#fd7e14', 40, 1, NOW()),
('text_position', 'Text Position', 'bottom-right', 'Bottom Right', '#17a2b8', 50, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('journal_type', 'Journal Type', 'recognition', 'Recognition', '#28a745', 10, 1, NOW()),
('journal_type', 'Journal Type', 'revaluation', 'Revaluation', '#007bff', 20, 1, NOW()),
('journal_type', 'Journal Type', 'depreciation', 'Depreciation', '#fd7e14', 30, 1, NOW()),
('journal_type', 'Journal Type', 'impairment', 'Impairment', '#dc3545', 40, 1, NOW()),
('journal_type', 'Journal Type', 'impairment_reversal', 'Impairment Reversal', '#28a745', 50, 1, NOW()),
('journal_type', 'Journal Type', 'derecognition', 'Derecognition', '#343a40', 60, 1, NOW()),
('journal_type', 'Journal Type', 'adjustment', 'Adjustment', '#ffc107', 70, 1, NOW()),
('journal_type', 'Journal Type', 'transfer', 'Transfer', '#17a2b8', 80, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('hero_effect', 'Hero Effect', 'kenburns', 'Ken Burns', '#007bff', 10, 1, NOW()),
('hero_effect', 'Hero Effect', 'fade', 'Fade', '#28a745', 20, 1, NOW()),
('hero_effect', 'Hero Effect', 'none', 'None', '#6c757d', 30, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('term_relationship', 'Term Relationship', 'synonym', 'Synonym', '#28a745', 10, 1, NOW()),
('term_relationship', 'Term Relationship', 'broader', 'Broader', '#007bff', 20, 1, NOW()),
('term_relationship', 'Term Relationship', 'narrower', 'Narrower', '#6f42c1', 30, 1, NOW()),
('term_relationship', 'Term Relationship', 'related', 'Related', '#fd7e14', 40, 1, NOW()),
('term_relationship', 'Term Relationship', 'spelling', 'Spelling', '#17a2b8', 50, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('learned_term_source', 'Learned Term Source', 'user_behavior', 'User Behavior', '#007bff', 10, 1, NOW()),
('learned_term_source', 'Learned Term Source', 'admin', 'Admin', '#dc3545', 20, 1, NOW()),
('learned_term_source', 'Learned Term Source', 'taxonomy', 'Taxonomy', '#28a745', 30, 1, NOW()),
('learned_term_source', 'Learned Term Source', 'external', 'External', '#6f42c1', 40, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('movement_type', 'Movement Type', 'loan_out', 'Loan Out', '#fd7e14', 10, 1, NOW()),
('movement_type', 'Movement Type', 'loan_return', 'Loan Return', '#28a745', 20, 1, NOW()),
('movement_type', 'Movement Type', 'transfer', 'Transfer', '#007bff', 30, 1, NOW()),
('movement_type', 'Movement Type', 'exhibition', 'Exhibition', '#6f42c1', 40, 1, NOW()),
('movement_type', 'Movement Type', 'conservation', 'Conservation', '#17a2b8', 50, 1, NOW()),
('movement_type', 'Movement Type', 'storage_change', 'Storage Change', '#ffc107', 60, 1, NOW()),
('movement_type', 'Movement Type', 'other', 'Other', '#6c757d', 70, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('popia_flag_type', 'POPIA Flag Type', 'personal_info', 'Personal Info', '#ffc107', 10, 1, NOW()),
('popia_flag_type', 'POPIA Flag Type', 'sensitive', 'Sensitive', '#dc3545', 20, 1, NOW()),
('popia_flag_type', 'POPIA Flag Type', 'children', 'Children', '#e83e8c', 30, 1, NOW()),
('popia_flag_type', 'POPIA Flag Type', 'health', 'Health', '#fd7e14', 40, 1, NOW()),
('popia_flag_type', 'POPIA Flag Type', 'biometric', 'Biometric', '#6f42c1', 50, 1, NOW()),
('popia_flag_type', 'POPIA Flag Type', 'criminal', 'Criminal', '#343a40', 60, 1, NOW()),
('popia_flag_type', 'POPIA Flag Type', 'financial', 'Financial', '#28a745', 70, 1, NOW()),
('popia_flag_type', 'POPIA Flag Type', 'political', 'Political', '#007bff', 80, 1, NOW()),
('popia_flag_type', 'POPIA Flag Type', 'religious', 'Religious', '#17a2b8', 90, 1, NOW()),
('popia_flag_type', 'POPIA Flag Type', 'sexual', 'Sexual', '#e83e8c', 100, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('detected_by', 'Detected By', 'automatic', 'Automatic', '#007bff', 10, 1, NOW()),
('detected_by', 'Detected By', 'manual', 'Manual', '#28a745', 20, 1, NOW()),
('detected_by', 'Detected By', 'review', 'Review', '#ffc107', 30, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('suggestion_type', 'Suggestion Type', 'query', 'Query', '#007bff', 10, 1, NOW()),
('suggestion_type', 'Suggestion Type', 'title', 'Title', '#28a745', 20, 1, NOW()),
('suggestion_type', 'Suggestion Type', 'subject', 'Subject', '#6f42c1', 30, 1, NOW()),
('suggestion_type', 'Suggestion Type', 'creator', 'Creator', '#fd7e14', 40, 1, NOW()),
('suggestion_type', 'Suggestion Type', 'place', 'Place', '#17a2b8', 50, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('tenant_status', 'Tenant Status', 'active', 'Active', '#28a745', 10, 1, NOW()),
('tenant_status', 'Tenant Status', 'suspended', 'Suspended', '#dc3545', 20, 1, NOW()),
('tenant_status', 'Tenant Status', 'trial', 'Trial', '#ffc107', 30, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('tenant_role', 'Tenant Role', 'owner', 'Owner', '#dc3545', 10, 1, NOW()),
('tenant_role', 'Tenant Role', 'super_user', 'Super User', '#fd7e14', 20, 1, NOW()),
('tenant_role', 'Tenant Role', 'editor', 'Editor', '#ffc107', 30, 1, NOW()),
('tenant_role', 'Tenant Role', 'contributor', 'Contributor', '#28a745', 40, 1, NOW()),
('tenant_role', 'Tenant Role', 'viewer', 'Viewer', '#6c757d', 50, 1, NOW());

-- ============================================================================
-- ICIP (Indigenous Cultural Intellectual Property) TYPES
-- ============================================================================

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('icip_restriction_type', 'ICIP Restriction Type', 'community_permission_required', 'Community Permission Required', '#dc3545', 10, 1, NOW()),
('icip_restriction_type', 'ICIP Restriction Type', 'gender_restricted_male', 'Gender Restricted (Male)', '#007bff', 20, 1, NOW()),
('icip_restriction_type', 'ICIP Restriction Type', 'gender_restricted_female', 'Gender Restricted (Female)', '#e83e8c', 30, 1, NOW()),
('icip_restriction_type', 'ICIP Restriction Type', 'initiated_only', 'Initiated Only', '#6f42c1', 40, 1, NOW()),
('icip_restriction_type', 'ICIP Restriction Type', 'seasonal', 'Seasonal', '#28a745', 50, 1, NOW()),
('icip_restriction_type', 'ICIP Restriction Type', 'mourning_period', 'Mourning Period', '#343a40', 60, 1, NOW()),
('icip_restriction_type', 'ICIP Restriction Type', 'repatriation_pending', 'Repatriation Pending', '#fd7e14', 70, 1, NOW()),
('icip_restriction_type', 'ICIP Restriction Type', 'under_consultation', 'Under Consultation', '#ffc107', 80, 1, NOW()),
('icip_restriction_type', 'ICIP Restriction Type', 'elder_approval_required', 'Elder Approval Required', '#17a2b8', 90, 1, NOW()),
('icip_restriction_type', 'ICIP Restriction Type', 'custom', 'Custom', '#6c757d', 100, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('australian_state', 'Australian State/Territory', 'NSW', 'New South Wales', '#007bff', 10, 1, NOW()),
('australian_state', 'Australian State/Territory', 'VIC', 'Victoria', '#28a745', 20, 1, NOW()),
('australian_state', 'Australian State/Territory', 'QLD', 'Queensland', '#6f42c1', 30, 1, NOW()),
('australian_state', 'Australian State/Territory', 'WA', 'Western Australia', '#fd7e14', 40, 1, NOW()),
('australian_state', 'Australian State/Territory', 'SA', 'South Australia', '#dc3545', 50, 1, NOW()),
('australian_state', 'Australian State/Territory', 'TAS', 'Tasmania', '#17a2b8', 60, 1, NOW()),
('australian_state', 'Australian State/Territory', 'NT', 'Northern Territory', '#ffc107', 70, 1, NOW()),
('australian_state', 'Australian State/Territory', 'ACT', 'Australian Capital Territory', '#e83e8c', 80, 1, NOW()),
('australian_state', 'Australian State/Territory', 'External', 'External Territory', '#6c757d', 90, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('consent_status', 'Consent Status', 'not_required', 'Not Required', '#6c757d', 10, 1, NOW()),
('consent_status', 'Consent Status', 'pending_consultation', 'Pending Consultation', '#ffc107', 20, 1, NOW()),
('consent_status', 'Consent Status', 'consultation_in_progress', 'Consultation In Progress', '#17a2b8', 30, 1, NOW()),
('consent_status', 'Consent Status', 'conditional_consent', 'Conditional Consent', '#fd7e14', 40, 1, NOW()),
('consent_status', 'Consent Status', 'full_consent', 'Full Consent', '#28a745', 50, 1, NOW()),
('consent_status', 'Consent Status', 'restricted_consent', 'Restricted Consent', '#ffc107', 60, 1, NOW()),
('consent_status', 'Consent Status', 'denied', 'Denied', '#dc3545', 70, 1, NOW()),
('consent_status', 'Consent Status', 'unknown', 'Unknown', '#6c757d', 80, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('consultation_method', 'Consultation Method', 'in_person', 'In Person', '#28a745', 10, 1, NOW()),
('consultation_method', 'Consultation Method', 'phone', 'Phone', '#007bff', 20, 1, NOW()),
('consultation_method', 'Consultation Method', 'video', 'Video', '#6f42c1', 30, 1, NOW()),
('consultation_method', 'Consultation Method', 'email', 'Email', '#17a2b8', 40, 1, NOW()),
('consultation_method', 'Consultation Method', 'letter', 'Letter', '#fd7e14', 50, 1, NOW()),
('consultation_method', 'Consultation Method', 'other', 'Other', '#6c757d', 60, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('consultation_type', 'Consultation Type', 'initial_contact', 'Initial Contact', '#007bff', 10, 1, NOW()),
('consultation_type', 'Consultation Type', 'consent_request', 'Consent Request', '#28a745', 20, 1, NOW()),
('consultation_type', 'Consultation Type', 'access_request', 'Access Request', '#6f42c1', 30, 1, NOW()),
('consultation_type', 'Consultation Type', 'repatriation', 'Repatriation', '#dc3545', 40, 1, NOW()),
('consultation_type', 'Consultation Type', 'digitisation', 'Digitisation', '#17a2b8', 50, 1, NOW()),
('consultation_type', 'Consultation Type', 'exhibition', 'Exhibition', '#fd7e14', 60, 1, NOW()),
('consultation_type', 'Consultation Type', 'publication', 'Publication', '#ffc107', 70, 1, NOW()),
('consultation_type', 'Consultation Type', 'research', 'Research', '#e83e8c', 80, 1, NOW()),
('consultation_type', 'Consultation Type', 'general', 'General', '#6c757d', 90, 1, NOW()),
('consultation_type', 'Consultation Type', 'follow_up', 'Follow Up', '#20c997', 100, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('consultation_status', 'Consultation Status', 'scheduled', 'Scheduled', '#ffc107', 10, 1, NOW()),
('consultation_status', 'Consultation Status', 'completed', 'Completed', '#28a745', 20, 1, NOW()),
('consultation_status', 'Consultation Status', 'cancelled', 'Cancelled', '#dc3545', 30, 1, NOW()),
('consultation_status', 'Consultation Status', 'follow_up_required', 'Follow Up Required', '#17a2b8', 40, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('tk_label_applied_by', 'TK Label Applied By', 'community', 'Community', '#28a745', 10, 1, NOW()),
('tk_label_applied_by', 'TK Label Applied By', 'institution', 'Institution', '#007bff', 20, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('tk_label_category', 'TK Label Category', 'TK', 'Traditional Knowledge', '#dc3545', 10, 1, NOW()),
('tk_label_category', 'TK Label Category', 'BC', 'Biocultural', '#28a745', 20, 1, NOW());

-- ============================================================================
-- IIIF TYPES
-- ============================================================================

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('iiif_motivation', 'IIIF Motivation', 'commenting', 'Commenting', '#007bff', 10, 1, NOW()),
('iiif_motivation', 'IIIF Motivation', 'tagging', 'Tagging', '#28a745', 20, 1, NOW()),
('iiif_motivation', 'IIIF Motivation', 'describing', 'Describing', '#6f42c1', 30, 1, NOW()),
('iiif_motivation', 'IIIF Motivation', 'linking', 'Linking', '#fd7e14', 40, 1, NOW()),
('iiif_motivation', 'IIIF Motivation', 'transcribing', 'Transcribing', '#17a2b8', 50, 1, NOW()),
('iiif_motivation', 'IIIF Motivation', 'identifying', 'Identifying', '#ffc107', 60, 1, NOW()),
('iiif_motivation', 'IIIF Motivation', 'supplementing', 'Supplementing', '#e83e8c', 70, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('iiif_viewing_hint', 'IIIF Viewing Hint', 'individuals', 'Individuals', '#007bff', 10, 1, NOW()),
('iiif_viewing_hint', 'IIIF Viewing Hint', 'paged', 'Paged', '#28a745', 20, 1, NOW()),
('iiif_viewing_hint', 'IIIF Viewing Hint', 'continuous', 'Continuous', '#6f42c1', 30, 1, NOW()),
('iiif_viewing_hint', 'IIIF Viewing Hint', 'multi-part', 'Multi-part', '#fd7e14', 40, 1, NOW()),
('iiif_viewing_hint', 'IIIF Viewing Hint', 'top', 'Top', '#17a2b8', 50, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('iiif_item_type', 'IIIF Item Type', 'manifest', 'Manifest', '#007bff', 10, 1, NOW()),
('iiif_item_type', 'IIIF Item Type', 'collection', 'Collection', '#28a745', 20, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('ocr_block_type', 'OCR Block Type', 'word', 'Word', '#007bff', 10, 1, NOW()),
('ocr_block_type', 'OCR Block Type', 'line', 'Line', '#28a745', 20, 1, NOW()),
('ocr_block_type', 'OCR Block Type', 'paragraph', 'Paragraph', '#6f42c1', 30, 1, NOW()),
('ocr_block_type', 'OCR Block Type', 'region', 'Region', '#fd7e14', 40, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('ocr_format', 'OCR Format', 'plain', 'Plain Text', '#007bff', 10, 1, NOW()),
('ocr_format', 'OCR Format', 'alto', 'ALTO XML', '#28a745', 20, 1, NOW()),
('ocr_format', 'OCR Format', 'hocr', 'hOCR', '#6f42c1', 30, 1, NOW());

-- ============================================================================
-- PHYSICAL LOCATION TYPES
-- ============================================================================

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('physical_access_status', 'Physical Access Status', 'available', 'Available', '#28a745', 10, 1, NOW()),
('physical_access_status', 'Physical Access Status', 'in_use', 'In Use', '#007bff', 20, 1, NOW()),
('physical_access_status', 'Physical Access Status', 'restricted', 'Restricted', '#dc3545', 30, 1, NOW()),
('physical_access_status', 'Physical Access Status', 'offsite', 'Offsite', '#fd7e14', 40, 1, NOW()),
('physical_access_status', 'Physical Access Status', 'missing', 'Missing', '#343a40', 50, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('physical_object_status', 'Physical Object Status', 'active', 'Active', '#28a745', 10, 1, NOW()),
('physical_object_status', 'Physical Object Status', 'full', 'Full', '#ffc107', 20, 1, NOW()),
('physical_object_status', 'Physical Object Status', 'maintenance', 'Maintenance', '#17a2b8', 30, 1, NOW()),
('physical_object_status', 'Physical Object Status', 'decommissioned', 'Decommissioned', '#dc3545', 40, 1, NOW());

-- ============================================================================
-- IPSAS TYPES (Heritage Asset Accounting)
-- ============================================================================

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('ipsas_asset_type', 'IPSAS Asset Type', 'heritage', 'Heritage', '#6f42c1', 10, 1, NOW()),
('ipsas_asset_type', 'IPSAS Asset Type', 'operational', 'Operational', '#007bff', 20, 1, NOW()),
('ipsas_asset_type', 'IPSAS Asset Type', 'mixed', 'Mixed', '#ffc107', 30, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('ipsas_calculation_method', 'IPSAS Calculation Method', 'straight_line', 'Straight Line', '#007bff', 10, 1, NOW()),
('ipsas_calculation_method', 'IPSAS Calculation Method', 'reducing_balance', 'Reducing Balance', '#28a745', 20, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('ipsas_fy_status', 'IPSAS FY Status', 'open', 'Open', '#28a745', 10, 1, NOW()),
('ipsas_fy_status', 'IPSAS FY Status', 'closed', 'Closed', '#dc3545', 20, 1, NOW()),
('ipsas_fy_status', 'IPSAS FY Status', 'audited', 'Audited', '#6f42c1', 30, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('heritage_asset_status', 'Heritage Asset Status', 'active', 'Active', '#28a745', 10, 1, NOW()),
('heritage_asset_status', 'Heritage Asset Status', 'on_loan', 'On Loan', '#007bff', 20, 1, NOW()),
('heritage_asset_status', 'Heritage Asset Status', 'in_storage', 'In Storage', '#6c757d', 30, 1, NOW()),
('heritage_asset_status', 'Heritage Asset Status', 'under_conservation', 'Under Conservation', '#17a2b8', 40, 1, NOW()),
('heritage_asset_status', 'Heritage Asset Status', 'disposed', 'Disposed', '#dc3545', 50, 1, NOW()),
('heritage_asset_status', 'Heritage Asset Status', 'lost', 'Lost', '#343a40', 60, 1, NOW()),
('heritage_asset_status', 'Heritage Asset Status', 'destroyed', 'Destroyed', '#343a40', 70, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('ipsas_insurance_status', 'IPSAS Insurance Status', 'active', 'Active', '#28a745', 10, 1, NOW()),
('ipsas_insurance_status', 'IPSAS Insurance Status', 'expired', 'Expired', '#dc3545', 20, 1, NOW()),
('ipsas_insurance_status', 'IPSAS Insurance Status', 'cancelled', 'Cancelled', '#6c757d', 30, 1, NOW()),
('ipsas_insurance_status', 'IPSAS Insurance Status', 'pending_renewal', 'Pending Renewal', '#ffc107', 40, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('ipsas_valuation_type', 'IPSAS Valuation Type', 'initial', 'Initial', '#28a745', 10, 1, NOW()),
('ipsas_valuation_type', 'IPSAS Valuation Type', 'revaluation', 'Revaluation', '#007bff', 20, 1, NOW()),
('ipsas_valuation_type', 'IPSAS Valuation Type', 'impairment', 'Impairment', '#dc3545', 30, 1, NOW()),
('ipsas_valuation_type', 'IPSAS Valuation Type', 'reversal', 'Reversal', '#ffc107', 40, 1, NOW()),
('ipsas_valuation_type', 'IPSAS Valuation Type', 'disposal', 'Disposal', '#6c757d', 50, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('valuer_type', 'Valuer Type', 'internal', 'Internal', '#007bff', 10, 1, NOW()),
('valuer_type', 'Valuer Type', 'external', 'External', '#28a745', 20, 1, NOW()),
('valuer_type', 'Valuer Type', 'government', 'Government', '#6f42c1', 30, 1, NOW());

-- ============================================================================
-- LOAN TYPES (General)
-- ============================================================================

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('loan_purpose', 'Loan Purpose', 'exhibition', 'Exhibition', '#6f42c1', 10, 1, NOW()),
('loan_purpose', 'Loan Purpose', 'research', 'Research', '#007bff', 20, 1, NOW()),
('loan_purpose', 'Loan Purpose', 'conservation', 'Conservation', '#17a2b8', 30, 1, NOW()),
('loan_purpose', 'Loan Purpose', 'photography', 'Photography', '#fd7e14', 40, 1, NOW()),
('loan_purpose', 'Loan Purpose', 'education', 'Education', '#ffc107', 50, 1, NOW()),
('loan_purpose', 'Loan Purpose', 'filming', 'Filming', '#e83e8c', 60, 1, NOW()),
('loan_purpose', 'Loan Purpose', 'long_term', 'Long Term', '#28a745', 70, 1, NOW()),
('loan_purpose', 'Loan Purpose', 'other', 'Other', '#6c757d', 80, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('loan_document_type', 'Loan Document Type', 'agreement', 'Agreement', '#28a745', 10, 1, NOW()),
('loan_document_type', 'Loan Document Type', 'facilities_report', 'Facilities Report', '#007bff', 20, 1, NOW()),
('loan_document_type', 'Loan Document Type', 'condition_report', 'Condition Report', '#17a2b8', 30, 1, NOW()),
('loan_document_type', 'Loan Document Type', 'insurance_certificate', 'Insurance Certificate', '#dc3545', 40, 1, NOW()),
('loan_document_type', 'Loan Document Type', 'receipt', 'Receipt', '#ffc107', 50, 1, NOW()),
('loan_document_type', 'Loan Document Type', 'correspondence', 'Correspondence', '#6f42c1', 60, 1, NOW()),
('loan_document_type', 'Loan Document Type', 'photograph', 'Photograph', '#fd7e14', 70, 1, NOW()),
('loan_document_type', 'Loan Document Type', 'other', 'Other', '#6c757d', 80, 1, NOW());

-- ============================================================================
-- MEDIA TYPES
-- ============================================================================

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('derivative_type', 'Derivative Type', 'thumbnail', 'Thumbnail', '#007bff', 10, 1, NOW()),
('derivative_type', 'Derivative Type', 'poster', 'Poster', '#28a745', 20, 1, NOW()),
('derivative_type', 'Derivative Type', 'preview', 'Preview', '#6f42c1', 30, 1, NOW()),
('derivative_type', 'Derivative Type', 'waveform', 'Waveform', '#fd7e14', 40, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('media_type', 'Media Type', 'audio', 'Audio', '#fd7e14', 10, 1, NOW()),
('media_type', 'Media Type', 'video', 'Video', '#6f42c1', 20, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('media_task_type', 'Media Task Type', 'metadata_extraction', 'Metadata Extraction', '#007bff', 10, 1, NOW()),
('media_task_type', 'Media Task Type', 'transcription', 'Transcription', '#28a745', 20, 1, NOW()),
('media_task_type', 'Media Task Type', 'waveform', 'Waveform', '#fd7e14', 30, 1, NOW()),
('media_task_type', 'Media Task Type', 'thumbnail', 'Thumbnail', '#6f42c1', 40, 1, NOW());

-- ============================================================================
-- METADATA TYPES
-- ============================================================================

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('export_status', 'Export Status', 'success', 'Success', '#28a745', 10, 1, NOW()),
('export_status', 'Export Status', 'failed', 'Failed', '#dc3545', 20, 1, NOW()),
('export_status', 'Export Status', 'partial', 'Partial', '#ffc107', 30, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('extraction_operation', 'Extraction Operation', 'extract', 'Extract', '#007bff', 10, 1, NOW()),
('extraction_operation', 'Extraction Operation', 'face_detect', 'Face Detect', '#28a745', 20, 1, NOW()),
('extraction_operation', 'Extraction Operation', 'face_match', 'Face Match', '#6f42c1', 30, 1, NOW()),
('extraction_operation', 'Extraction Operation', 'index_face', 'Index Face', '#fd7e14', 40, 1, NOW()),
('extraction_operation', 'Extraction Operation', 'bulk', 'Bulk', '#17a2b8', 50, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('triggered_by', 'Triggered By', 'upload', 'Upload', '#28a745', 10, 1, NOW()),
('triggered_by', 'Triggered By', 'job', 'Job', '#007bff', 20, 1, NOW()),
('triggered_by', 'Triggered By', 'manual', 'Manual', '#6f42c1', 30, 1, NOW()),
('triggered_by', 'Triggered By', 'api', 'API', '#fd7e14', 40, 1, NOW()),
('triggered_by', 'Triggered By', 'scheduler', 'Scheduler', '#17a2b8', 50, 1, NOW()),
('triggered_by', 'Triggered By', 'user', 'User', '#ffc107', 60, 1, NOW()),
('triggered_by', 'Triggered By', 'system', 'System', '#6c757d', 70, 1, NOW()),
('triggered_by', 'Triggered By', 'cron', 'Cron', '#e83e8c', 80, 1, NOW()),
('triggered_by', 'Triggered By', 'cli', 'CLI', '#343a40', 90, 1, NOW());

-- ============================================================================
-- Show Phase 2B statistics
-- ============================================================================

SELECT 'Phase 2B Migration Complete' as status;

-- ---------------------------------------------------------------------------
-- Merged in from database/enum_to_dropdown_migration_phase2c.sql on 2026-08-17.
--
-- It sat beside install.sql and was never run by install-plugin-schema.php,
-- so a clean install silently lacked whatever it defines. Our own instances
-- had it because someone applied the file by hand. A plugin's schema is
-- install.sql; there is no second file.
-- ---------------------------------------------------------------------------

-- ============================================================================
-- ENUM to ahg_dropdown Migration Script - PHASE 2C
-- Generated: 2026-02-04
--
-- Final part: NAZ, NMMZ, OAIS, Preservation, Privacy, Provenance,
-- Research, RIC, Rights, Object 3D, Numbering, and Backup types
-- ============================================================================

-- ============================================================================
-- NAZ (National Archives of Zimbabwe) TYPES
-- ============================================================================

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('naz_closure_type', 'NAZ Closure Type', 'standard', 'Standard', '#007bff', 10, 1, NOW()),
('naz_closure_type', 'NAZ Closure Type', 'extended', 'Extended', '#fd7e14', 20, 1, NOW()),
('naz_closure_type', 'NAZ Closure Type', 'indefinite', 'Indefinite', '#dc3545', 30, 1, NOW()),
('naz_closure_type', 'NAZ Closure Type', 'ministerial', 'Ministerial', '#6f42c1', 40, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('naz_closure_status', 'NAZ Closure Status', 'active', 'Active', '#dc3545', 10, 1, NOW()),
('naz_closure_status', 'NAZ Closure Status', 'expired', 'Expired', '#6c757d', 20, 1, NOW()),
('naz_closure_status', 'NAZ Closure Status', 'extended', 'Extended', '#fd7e14', 30, 1, NOW()),
('naz_closure_status', 'NAZ Closure Status', 'released', 'Released', '#28a745', 40, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('naz_protection_type', 'NAZ Protection Type', 'cabinet', 'Cabinet', '#dc3545', 10, 1, NOW()),
('naz_protection_type', 'NAZ Protection Type', 'security', 'Security', '#fd7e14', 20, 1, NOW()),
('naz_protection_type', 'NAZ Protection Type', 'personal', 'Personal', '#ffc107', 30, 1, NOW()),
('naz_protection_type', 'NAZ Protection Type', 'legal', 'Legal', '#6f42c1', 40, 1, NOW()),
('naz_protection_type', 'NAZ Protection Type', 'commercial', 'Commercial', '#17a2b8', 50, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('naz_access_restriction', 'NAZ Access Restriction', 'open', 'Open', '#28a745', 10, 1, NOW()),
('naz_access_restriction', 'NAZ Access Restriction', 'restricted', 'Restricted', '#ffc107', 20, 1, NOW()),
('naz_access_restriction', 'NAZ Access Restriction', 'confidential', 'Confidential', '#fd7e14', 30, 1, NOW()),
('naz_access_restriction', 'NAZ Access Restriction', 'secret', 'Secret', '#dc3545', 40, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('naz_classification', 'NAZ Classification', 'vital', 'Vital', '#dc3545', 10, 1, NOW()),
('naz_classification', 'NAZ Classification', 'important', 'Important', '#fd7e14', 20, 1, NOW()),
('naz_classification', 'NAZ Classification', 'useful', 'Useful', '#ffc107', 30, 1, NOW()),
('naz_classification', 'NAZ Classification', 'non-essential', 'Non-Essential', '#6c757d', 40, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('disposal_action', 'Disposal Action', 'destroy', 'Destroy', '#dc3545', 10, 1, NOW()),
('disposal_action', 'Disposal Action', 'transfer', 'Transfer', '#007bff', 20, 1, NOW()),
('disposal_action', 'Disposal Action', 'review', 'Review', '#ffc107', 30, 1, NOW()),
('disposal_action', 'Disposal Action', 'permanent', 'Permanent', '#28a745', 40, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('naz_schedule_status', 'NAZ Schedule Status', 'draft', 'Draft', '#6c757d', 10, 1, NOW()),
('naz_schedule_status', 'NAZ Schedule Status', 'pending', 'Pending', '#ffc107', 20, 1, NOW()),
('naz_schedule_status', 'NAZ Schedule Status', 'approved', 'Approved', '#28a745', 30, 1, NOW()),
('naz_schedule_status', 'NAZ Schedule Status', 'superseded', 'Superseded', '#fd7e14', 40, 1, NOW()),
('naz_schedule_status', 'NAZ Schedule Status', 'archived', 'Archived', '#343a40', 50, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('naz_permit_type', 'NAZ Permit Type', 'general', 'General', '#28a745', 10, 1, NOW()),
('naz_permit_type', 'NAZ Permit Type', 'restricted', 'Restricted', '#ffc107', 20, 1, NOW()),
('naz_permit_type', 'NAZ Permit Type', 'special', 'Special', '#6f42c1', 30, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('naz_permit_status', 'NAZ Permit Status', 'pending', 'Pending', '#ffc107', 10, 1, NOW()),
('naz_permit_status', 'NAZ Permit Status', 'approved', 'Approved', '#28a745', 20, 1, NOW()),
('naz_permit_status', 'NAZ Permit Status', 'rejected', 'Rejected', '#dc3545', 30, 1, NOW()),
('naz_permit_status', 'NAZ Permit Status', 'active', 'Active', '#007bff', 40, 1, NOW()),
('naz_permit_status', 'NAZ Permit Status', 'expired', 'Expired', '#6c757d', 50, 1, NOW()),
('naz_permit_status', 'NAZ Permit Status', 'revoked', 'Revoked', '#343a40', 60, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('naz_researcher_type', 'NAZ Researcher Type', 'local', 'Local', '#28a745', 10, 1, NOW()),
('naz_researcher_type', 'NAZ Researcher Type', 'foreign', 'Foreign', '#007bff', 20, 1, NOW()),
('naz_researcher_type', 'NAZ Researcher Type', 'institutional', 'Institutional', '#6f42c1', 30, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('naz_researcher_status', 'NAZ Researcher Status', 'active', 'Active', '#28a745', 10, 1, NOW()),
('naz_researcher_status', 'NAZ Researcher Status', 'inactive', 'Inactive', '#6c757d', 20, 1, NOW()),
('naz_researcher_status', 'NAZ Researcher Status', 'suspended', 'Suspended', '#ffc107', 30, 1, NOW()),
('naz_researcher_status', 'NAZ Researcher Status', 'blacklisted', 'Blacklisted', '#dc3545', 40, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('naz_transfer_status', 'NAZ Transfer Status', 'proposed', 'Proposed', '#6c757d', 10, 1, NOW()),
('naz_transfer_status', 'NAZ Transfer Status', 'scheduled', 'Scheduled', '#ffc107', 20, 1, NOW()),
('naz_transfer_status', 'NAZ Transfer Status', 'in_transit', 'In Transit', '#17a2b8', 30, 1, NOW()),
('naz_transfer_status', 'NAZ Transfer Status', 'received', 'Received', '#28a745', 40, 1, NOW()),
('naz_transfer_status', 'NAZ Transfer Status', 'accessioned', 'Accessioned', '#007bff', 50, 1, NOW()),
('naz_transfer_status', 'NAZ Transfer Status', 'rejected', 'Rejected', '#dc3545', 60, 1, NOW()),
('naz_transfer_status', 'NAZ Transfer Status', 'cancelled', 'Cancelled', '#343a40', 70, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('naz_transfer_type', 'NAZ Transfer Type', 'scheduled', 'Scheduled', '#007bff', 10, 1, NOW()),
('naz_transfer_type', 'NAZ Transfer Type', 'voluntary', 'Voluntary', '#28a745', 20, 1, NOW()),
('naz_transfer_type', 'NAZ Transfer Type', 'rescue', 'Rescue', '#dc3545', 30, 1, NOW()),
('naz_transfer_type', 'NAZ Transfer Type', 'donation', 'Donation', '#6f42c1', 40, 1, NOW());

-- ============================================================================
-- NMMZ (National Museums and Monuments of Zimbabwe) TYPES
-- ============================================================================

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('nmmz_condition_rating', 'NMMZ Condition Rating', 'excellent', 'Excellent', '#28a745', 10, 1, NOW()),
('nmmz_condition_rating', 'NMMZ Condition Rating', 'good', 'Good', '#20c997', 20, 1, NOW()),
('nmmz_condition_rating', 'NMMZ Condition Rating', 'fair', 'Fair', '#ffc107', 30, 1, NOW()),
('nmmz_condition_rating', 'NMMZ Condition Rating', 'poor', 'Poor', '#fd7e14', 40, 1, NOW()),
('nmmz_condition_rating', 'NMMZ Condition Rating', 'fragmentary', 'Fragmentary', '#dc3545', 50, 1, NOW()),
('nmmz_condition_rating', 'NMMZ Condition Rating', 'critical', 'Critical', '#dc3545', 60, 1, NOW()),
('nmmz_condition_rating', 'NMMZ Condition Rating', 'destroyed', 'Destroyed', '#343a40', 70, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('ownership_type', 'Ownership Type', 'state', 'State', '#007bff', 10, 1, NOW()),
('ownership_type', 'Ownership Type', 'museum', 'Museum', '#28a745', 20, 1, NOW()),
('ownership_type', 'Ownership Type', 'private', 'Private', '#fd7e14', 30, 1, NOW()),
('ownership_type', 'Ownership Type', 'communal', 'Communal', '#6f42c1', 40, 1, NOW()),
('ownership_type', 'Ownership Type', 'mixed', 'Mixed', '#ffc107', 50, 1, NOW()),
('ownership_type', 'Ownership Type', 'unknown', 'Unknown', '#6c757d', 60, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('antiquity_status', 'Antiquity Status', 'in_collection', 'In Collection', '#28a745', 10, 1, NOW()),
('antiquity_status', 'Antiquity Status', 'on_loan', 'On Loan', '#007bff', 20, 1, NOW()),
('antiquity_status', 'Antiquity Status', 'missing', 'Missing', '#dc3545', 30, 1, NOW()),
('antiquity_status', 'Antiquity Status', 'repatriated', 'Repatriated', '#6f42c1', 40, 1, NOW()),
('antiquity_status', 'Antiquity Status', 'destroyed', 'Destroyed', '#343a40', 50, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('site_protection_status', 'Site Protection Status', 'protected', 'Protected', '#28a745', 10, 1, NOW()),
('site_protection_status', 'Site Protection Status', 'unprotected', 'Unprotected', '#ffc107', 20, 1, NOW()),
('site_protection_status', 'Site Protection Status', 'at_risk', 'At Risk', '#dc3545', 30, 1, NOW()),
('site_protection_status', 'Site Protection Status', 'destroyed', 'Destroyed', '#343a40', 40, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('research_potential', 'Research Potential', 'high', 'High', '#28a745', 10, 1, NOW()),
('research_potential', 'Research Potential', 'medium', 'Medium', '#ffc107', 20, 1, NOW()),
('research_potential', 'Research Potential', 'low', 'Low', '#fd7e14', 30, 1, NOW()),
('research_potential', 'Research Potential', 'exhausted', 'Exhausted', '#6c757d', 40, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('site_status', 'Site Status', 'active', 'Active', '#28a745', 10, 1, NOW()),
('site_status', 'Site Status', 'destroyed', 'Destroyed', '#dc3545', 20, 1, NOW()),
('site_status', 'Site Status', 'submerged', 'Submerged', '#17a2b8', 30, 1, NOW()),
('site_status', 'Site Status', 'built_over', 'Built Over', '#6c757d', 40, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('applicant_type', 'Applicant Type', 'individual', 'Individual', '#007bff', 10, 1, NOW()),
('applicant_type', 'Applicant Type', 'institution', 'Institution', '#28a745', 20, 1, NOW()),
('applicant_type', 'Applicant Type', 'dealer', 'Dealer', '#fd7e14', 30, 1, NOW()),
('applicant_type', 'Applicant Type', 'researcher', 'Researcher', '#6f42c1', 40, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('export_purpose', 'Export Purpose', 'exhibition', 'Exhibition', '#6f42c1', 10, 1, NOW()),
('export_purpose', 'Export Purpose', 'research', 'Research', '#007bff', 20, 1, NOW()),
('export_purpose', 'Export Purpose', 'conservation', 'Conservation', '#17a2b8', 30, 1, NOW()),
('export_purpose', 'Export Purpose', 'sale', 'Sale', '#ffc107', 40, 1, NOW()),
('export_purpose', 'Export Purpose', 'personal', 'Personal', '#28a745', 50, 1, NOW()),
('export_purpose', 'Export Purpose', 'return', 'Return', '#fd7e14', 60, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('export_permit_status', 'Export Permit Status', 'pending', 'Pending', '#ffc107', 10, 1, NOW()),
('export_permit_status', 'Export Permit Status', 'approved', 'Approved', '#28a745', 20, 1, NOW()),
('export_permit_status', 'Export Permit Status', 'rejected', 'Rejected', '#dc3545', 30, 1, NOW()),
('export_permit_status', 'Export Permit Status', 'issued', 'Issued', '#007bff', 40, 1, NOW()),
('export_permit_status', 'Export Permit Status', 'used', 'Used', '#6c757d', 50, 1, NOW()),
('export_permit_status', 'Export Permit Status', 'expired', 'Expired', '#343a40', 60, 1, NOW()),
('export_permit_status', 'Export Permit Status', 'cancelled', 'Cancelled', '#dc3545', 70, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('impact_level', 'Impact Level', 'none', 'None', '#28a745', 10, 1, NOW()),
('impact_level', 'Impact Level', 'low', 'Low', '#ffc107', 20, 1, NOW()),
('impact_level', 'Impact Level', 'moderate', 'Moderate', '#fd7e14', 30, 1, NOW()),
('impact_level', 'Impact Level', 'high', 'High', '#dc3545', 40, 1, NOW()),
('impact_level', 'Impact Level', 'severe', 'Severe', '#343a40', 50, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('hia_recommendation', 'HIA Recommendation', 'approve', 'Approve', '#28a745', 10, 1, NOW()),
('hia_recommendation', 'HIA Recommendation', 'approve_with_conditions', 'Approve with Conditions', '#ffc107', 20, 1, NOW()),
('hia_recommendation', 'HIA Recommendation', 'reject', 'Reject', '#dc3545', 30, 1, NOW()),
('hia_recommendation', 'HIA Recommendation', 'further_study', 'Further Study Required', '#17a2b8', 40, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('hia_status', 'HIA Status', 'submitted', 'Submitted', '#6c757d', 10, 1, NOW()),
('hia_status', 'HIA Status', 'under_review', 'Under Review', '#17a2b8', 20, 1, NOW()),
('hia_status', 'HIA Status', 'approved', 'Approved', '#28a745', 30, 1, NOW()),
('hia_status', 'HIA Status', 'rejected', 'Rejected', '#dc3545', 40, 1, NOW()),
('hia_status', 'HIA Status', 'expired', 'Expired', '#343a40', 50, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('monument_legal_status', 'Monument Legal Status', 'gazetted', 'Gazetted', '#28a745', 10, 1, NOW()),
('monument_legal_status', 'Monument Legal Status', 'provisional', 'Provisional', '#ffc107', 20, 1, NOW()),
('monument_legal_status', 'Monument Legal Status', 'proposed', 'Proposed', '#17a2b8', 30, 1, NOW()),
('monument_legal_status', 'Monument Legal Status', 'delisted', 'Delisted', '#dc3545', 40, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('monument_protection_level', 'Monument Protection Level', 'national', 'National', '#dc3545', 10, 1, NOW()),
('monument_protection_level', 'Monument Protection Level', 'provincial', 'Provincial', '#fd7e14', 20, 1, NOW()),
('monument_protection_level', 'Monument Protection Level', 'local', 'Local', '#ffc107', 30, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('monument_status', 'Monument Status', 'active', 'Active', '#28a745', 10, 1, NOW()),
('monument_status', 'Monument Status', 'at_risk', 'At Risk', '#dc3545', 20, 1, NOW()),
('monument_status', 'Monument Status', 'destroyed', 'Destroyed', '#343a40', 30, 1, NOW()),
('monument_status', 'Monument Status', 'delisted', 'Delisted', '#6c757d', 40, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('world_heritage_status', 'World Heritage Status', 'inscribed', 'Inscribed', '#28a745', 10, 1, NOW()),
('world_heritage_status', 'World Heritage Status', 'tentative', 'Tentative', '#ffc107', 20, 1, NOW()),
('world_heritage_status', 'World Heritage Status', 'none', 'None', '#6c757d', 30, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('conservation_priority', 'Conservation Priority', 'high', 'High', '#dc3545', 10, 1, NOW()),
('conservation_priority', 'Conservation Priority', 'medium', 'Medium', '#ffc107', 20, 1, NOW()),
('conservation_priority', 'Conservation Priority', 'low', 'Low', '#28a745', 30, 1, NOW());

-- ============================================================================
-- NUMBERING SCHEME TYPES
-- ============================================================================

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('numbering_sector', 'Numbering Sector', 'archive', 'Archive', '#007bff', 10, 1, NOW()),
('numbering_sector', 'Numbering Sector', 'library', 'Library', '#28a745', 20, 1, NOW()),
('numbering_sector', 'Numbering Sector', 'museum', 'Museum', '#6f42c1', 30, 1, NOW()),
('numbering_sector', 'Numbering Sector', 'gallery', 'Gallery', '#fd7e14', 40, 1, NOW()),
('numbering_sector', 'Numbering Sector', 'dam', 'DAM', '#17a2b8', 50, 1, NOW()),
('numbering_sector', 'Numbering Sector', 'all', 'All', '#6c757d', 60, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('sequence_reset', 'Sequence Reset', 'never', 'Never', '#28a745', 10, 1, NOW()),
('sequence_reset', 'Sequence Reset', 'yearly', 'Yearly', '#007bff', 20, 1, NOW()),
('sequence_reset', 'Sequence Reset', 'monthly', 'Monthly', '#6f42c1', 30, 1, NOW());

-- ============================================================================
-- OAIS (Open Archival Information System) TYPES
-- ============================================================================

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('checksum_type', 'Checksum Type', 'md5', 'MD5', '#6c757d', 10, 1, NOW()),
('checksum_type', 'Checksum Type', 'sha1', 'SHA-1', '#ffc107', 20, 1, NOW()),
('checksum_type', 'Checksum Type', 'sha256', 'SHA-256', '#28a745', 30, 1, NOW()),
('checksum_type', 'Checksum Type', 'sha512', 'SHA-512', '#007bff', 40, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('package_type', 'Package Type', 'SIP', 'Submission Information Package', '#007bff', 10, 1, NOW()),
('package_type', 'Package Type', 'AIP', 'Archival Information Package', '#28a745', 20, 1, NOW()),
('package_type', 'Package Type', 'DIP', 'Dissemination Information Package', '#6f42c1', 30, 1, NOW()),
('package_type', 'Package Type', 'sip', 'SIP', '#007bff', 40, 1, NOW()),
('package_type', 'Package Type', 'aip', 'AIP', '#28a745', 50, 1, NOW()),
('package_type', 'Package Type', 'dip', 'DIP', '#6f42c1', 60, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('preservation_level', 'Preservation Level', 'bit', 'Bit', '#6c757d', 10, 1, NOW()),
('preservation_level', 'Preservation Level', 'logical', 'Logical', '#007bff', 20, 1, NOW()),
('preservation_level', 'Preservation Level', 'semantic', 'Semantic', '#28a745', 30, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('oais_package_status', 'OAIS Package Status', 'pending', 'Pending', '#ffc107', 10, 1, NOW()),
('oais_package_status', 'OAIS Package Status', 'ingesting', 'Ingesting', '#17a2b8', 20, 1, NOW()),
('oais_package_status', 'OAIS Package Status', 'stored', 'Stored', '#28a745', 30, 1, NOW()),
('oais_package_status', 'OAIS Package Status', 'preserved', 'Preserved', '#007bff', 40, 1, NOW()),
('oais_package_status', 'OAIS Package Status', 'disseminated', 'Disseminated', '#6f42c1', 50, 1, NOW()),
('oais_package_status', 'OAIS Package Status', 'error', 'Error', '#dc3545', 60, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('package_content_type', 'Package Content Type', 'content', 'Content', '#007bff', 10, 1, NOW()),
('package_content_type', 'Package Content Type', 'metadata', 'Metadata', '#28a745', 20, 1, NOW()),
('package_content_type', 'Package Content Type', 'manifest', 'Manifest', '#6f42c1', 30, 1, NOW()),
('package_content_type', 'Package Content Type', 'signature', 'Signature', '#fd7e14', 40, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('premis_event_outcome', 'PREMIS Event Outcome', 'success', 'Success', '#28a745', 10, 1, NOW()),
('premis_event_outcome', 'PREMIS Event Outcome', 'failure', 'Failure', '#dc3545', 20, 1, NOW()),
('premis_event_outcome', 'PREMIS Event Outcome', 'warning', 'Warning', '#ffc107', 30, 1, NOW()),
('premis_event_outcome', 'PREMIS Event Outcome', 'unknown', 'Unknown', '#6c757d', 40, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('premis_event_type', 'PREMIS Event Type', 'capture', 'Capture', '#007bff', 10, 1, NOW()),
('premis_event_type', 'PREMIS Event Type', 'compression', 'Compression', '#28a745', 20, 1, NOW()),
('premis_event_type', 'PREMIS Event Type', 'creation', 'Creation', '#6f42c1', 30, 1, NOW()),
('premis_event_type', 'PREMIS Event Type', 'deaccession', 'Deaccession', '#dc3545', 40, 1, NOW()),
('premis_event_type', 'PREMIS Event Type', 'decompression', 'Decompression', '#17a2b8', 50, 1, NOW()),
('premis_event_type', 'PREMIS Event Type', 'decryption', 'Decryption', '#fd7e14', 60, 1, NOW()),
('premis_event_type', 'PREMIS Event Type', 'deletion', 'Deletion', '#343a40', 70, 1, NOW()),
('premis_event_type', 'PREMIS Event Type', 'digital_signature_validation', 'Digital Signature Validation', '#ffc107', 80, 1, NOW()),
('premis_event_type', 'PREMIS Event Type', 'dissemination', 'Dissemination', '#e83e8c', 90, 1, NOW()),
('premis_event_type', 'PREMIS Event Type', 'encryption', 'Encryption', '#20c997', 100, 1, NOW()),
('premis_event_type', 'PREMIS Event Type', 'fixity_check', 'Fixity Check', '#28a745', 110, 1, NOW()),
('premis_event_type', 'PREMIS Event Type', 'format_identification', 'Format Identification', '#007bff', 120, 1, NOW()),
('premis_event_type', 'PREMIS Event Type', 'ingestion', 'Ingestion', '#6f42c1', 130, 1, NOW()),
('premis_event_type', 'PREMIS Event Type', 'message_digest_calculation', 'Message Digest Calculation', '#17a2b8', 140, 1, NOW()),
('premis_event_type', 'PREMIS Event Type', 'migration', 'Migration', '#fd7e14', 150, 1, NOW()),
('premis_event_type', 'PREMIS Event Type', 'normalization', 'Normalization', '#ffc107', 160, 1, NOW()),
('premis_event_type', 'PREMIS Event Type', 'replication', 'Replication', '#28a745', 170, 1, NOW()),
('premis_event_type', 'PREMIS Event Type', 'validation', 'Validation', '#007bff', 180, 1, NOW()),
('premis_event_type', 'PREMIS Event Type', 'virus_check', 'Virus Check', '#dc3545', 190, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('preservation_action_type', 'Preservation Action Type', 'migrate', 'Migrate', '#007bff', 10, 1, NOW()),
('preservation_action_type', 'Preservation Action Type', 'normalize', 'Normalize', '#28a745', 20, 1, NOW()),
('preservation_action_type', 'Preservation Action Type', 'emulate', 'Emulate', '#6f42c1', 30, 1, NOW()),
('preservation_action_type', 'Preservation Action Type', 'preserve', 'Preserve', '#fd7e14', 40, 1, NOW()),
('preservation_action_type', 'Preservation Action Type', 'none', 'None', '#6c757d', 50, 1, NOW()),
('preservation_action_type', 'Preservation Action Type', 'monitor', 'Monitor', '#ffc107', 60, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('pronom_risk_level', 'PRONOM Risk Level', 'low', 'Low', '#28a745', 10, 1, NOW()),
('pronom_risk_level', 'PRONOM Risk Level', 'medium', 'Medium', '#ffc107', 20, 1, NOW()),
('pronom_risk_level', 'PRONOM Risk Level', 'high', 'High', '#fd7e14', 30, 1, NOW()),
('pronom_risk_level', 'PRONOM Risk Level', 'critical', 'Critical', '#dc3545', 40, 1, NOW());

-- ============================================================================
-- OBJECT 3D TYPES
-- ============================================================================

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('object_3d_audit_action', 'Object 3D Audit Action', 'upload', 'Upload', '#28a745', 10, 1, NOW()),
('object_3d_audit_action', 'Object 3D Audit Action', 'update', 'Update', '#007bff', 20, 1, NOW()),
('object_3d_audit_action', 'Object 3D Audit Action', 'delete', 'Delete', '#dc3545', 30, 1, NOW()),
('object_3d_audit_action', 'Object 3D Audit Action', 'view', 'View', '#6c757d', 40, 1, NOW()),
('object_3d_audit_action', 'Object 3D Audit Action', 'ar_view', 'AR View', '#6f42c1', 50, 1, NOW()),
('object_3d_audit_action', 'Object 3D Audit Action', 'download', 'Download', '#17a2b8', 60, 1, NOW()),
('object_3d_audit_action', 'Object 3D Audit Action', 'hotspot_add', 'Hotspot Add', '#ffc107', 70, 1, NOW()),
('object_3d_audit_action', 'Object 3D Audit Action', 'hotspot_delete', 'Hotspot Delete', '#fd7e14', 80, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('hotspot_type', 'Hotspot Type', 'annotation', 'Annotation', '#007bff', 10, 1, NOW()),
('hotspot_type', 'Hotspot Type', 'info', 'Information', '#28a745', 20, 1, NOW()),
('hotspot_type', 'Hotspot Type', 'link', 'Link', '#6f42c1', 30, 1, NOW()),
('hotspot_type', 'Hotspot Type', 'damage', 'Damage', '#dc3545', 40, 1, NOW()),
('hotspot_type', 'Hotspot Type', 'detail', 'Detail', '#fd7e14', 50, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('link_target', 'Link Target', '_self', 'Same Window', '#007bff', 10, 1, NOW()),
('link_target', 'Link Target', '_blank', 'New Window', '#28a745', 20, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('ar_placement', 'AR Placement', 'floor', 'Floor', '#007bff', 10, 1, NOW()),
('ar_placement', 'AR Placement', 'wall', 'Wall', '#28a745', 20, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('model_format', 'Model Format', 'glb', 'GLB', '#28a745', 10, 1, NOW()),
('model_format', 'Model Format', 'gltf', 'GLTF', '#007bff', 20, 1, NOW()),
('model_format', 'Model Format', 'obj', 'OBJ', '#6f42c1', 30, 1, NOW()),
('model_format', 'Model Format', 'fbx', 'FBX', '#fd7e14', 40, 1, NOW()),
('model_format', 'Model Format', 'stl', 'STL', '#17a2b8', 50, 1, NOW()),
('model_format', 'Model Format', 'ply', 'PLY', '#ffc107', 60, 1, NOW()),
('model_format', 'Model Format', 'usdz', 'USDZ', '#e83e8c', 70, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('texture_type', 'Texture Type', 'diffuse', 'Diffuse', '#007bff', 10, 1, NOW()),
('texture_type', 'Texture Type', 'normal', 'Normal', '#28a745', 20, 1, NOW()),
('texture_type', 'Texture Type', 'roughness', 'Roughness', '#6f42c1', 30, 1, NOW()),
('texture_type', 'Texture Type', 'metallic', 'Metallic', '#fd7e14', 40, 1, NOW()),
('texture_type', 'Texture Type', 'ao', 'Ambient Occlusion', '#17a2b8', 50, 1, NOW()),
('texture_type', 'Texture Type', 'emissive', 'Emissive', '#ffc107', 60, 1, NOW()),
('texture_type', 'Texture Type', 'environment', 'Environment', '#e83e8c', 70, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('access_level', 'Access Level', 'view', 'View', '#28a745', 10, 1, NOW()),
('access_level', 'Access Level', 'download', 'Download', '#007bff', 20, 1, NOW()),
('access_level', 'Access Level', 'edit', 'Edit', '#6f42c1', 30, 1, NOW());

-- ============================================================================
-- PRESERVATION TYPES
-- ============================================================================

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('backup_type', 'Backup Type', 'database', 'Database', '#007bff', 10, 1, NOW()),
('backup_type', 'Backup Type', 'files', 'Files', '#28a745', 20, 1, NOW()),
('backup_type', 'Backup Type', 'full', 'Full', '#6f42c1', 30, 1, NOW()),
('backup_type', 'Backup Type', 'incremental', 'Incremental', '#fd7e14', 40, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('backup_verification_status', 'Backup Verification Status', 'valid', 'Valid', '#28a745', 10, 1, NOW()),
('backup_verification_status', 'Backup Verification Status', 'invalid', 'Invalid', '#dc3545', 20, 1, NOW()),
('backup_verification_status', 'Backup Verification Status', 'missing', 'Missing', '#343a40', 30, 1, NOW()),
('backup_verification_status', 'Backup Verification Status', 'error', 'Error', '#fd7e14', 40, 1, NOW()),
('backup_verification_status', 'Backup Verification Status', 'corrupted', 'Corrupted', '#dc3545', 50, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('verification_status', 'Verification Status', 'pending', 'Pending', '#ffc107', 10, 1, NOW()),
('verification_status', 'Verification Status', 'valid', 'Valid', '#28a745', 20, 1, NOW()),
('verification_status', 'Verification Status', 'invalid', 'Invalid', '#dc3545', 30, 1, NOW()),
('verification_status', 'Verification Status', 'error', 'Error', '#fd7e14', 40, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('fixity_status', 'Fixity Status', 'pass', 'Pass', '#28a745', 10, 1, NOW()),
('fixity_status', 'Fixity Status', 'fail', 'Fail', '#dc3545', 20, 1, NOW()),
('fixity_status', 'Fixity Status', 'error', 'Error', '#fd7e14', 30, 1, NOW()),
('fixity_status', 'Fixity Status', 'missing', 'Missing', '#343a40', 40, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('migration_quality', 'Migration Quality', 'lossless', 'Lossless', '#28a745', 10, 1, NOW()),
('migration_quality', 'Migration Quality', 'minimal', 'Minimal Loss', '#ffc107', 20, 1, NOW()),
('migration_quality', 'Migration Quality', 'moderate', 'Moderate Loss', '#fd7e14', 30, 1, NOW()),
('migration_quality', 'Migration Quality', 'significant', 'Significant Loss', '#dc3545', 40, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('migration_urgency', 'Migration Urgency', 'none', 'None', '#28a745', 10, 1, NOW()),
('migration_urgency', 'Migration Urgency', 'low', 'Low', '#ffc107', 20, 1, NOW()),
('migration_urgency', 'Migration Urgency', 'medium', 'Medium', '#fd7e14', 30, 1, NOW()),
('migration_urgency', 'Migration Urgency', 'high', 'High', '#dc3545', 40, 1, NOW()),
('migration_urgency', 'Migration Urgency', 'critical', 'Critical', '#343a40', 50, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('migration_scope', 'Migration Scope', 'all', 'All', '#6f42c1', 10, 1, NOW()),
('migration_scope', 'Migration Scope', 'repository', 'Repository', '#007bff', 20, 1, NOW()),
('migration_scope', 'Migration Scope', 'collection', 'Collection', '#28a745', 30, 1, NOW()),
('migration_scope', 'Migration Scope', 'custom', 'Custom', '#6c757d', 40, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('migration_plan_status', 'Migration Plan Status', 'draft', 'Draft', '#6c757d', 10, 1, NOW()),
('migration_plan_status', 'Migration Plan Status', 'approved', 'Approved', '#28a745', 20, 1, NOW()),
('migration_plan_status', 'Migration Plan Status', 'in_progress', 'In Progress', '#007bff', 30, 1, NOW()),
('migration_plan_status', 'Migration Plan Status', 'completed', 'Completed', '#28a745', 40, 1, NOW()),
('migration_plan_status', 'Migration Plan Status', 'cancelled', 'Cancelled', '#6c757d', 50, 1, NOW()),
('migration_plan_status', 'Migration Plan Status', 'failed', 'Failed', '#dc3545', 60, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('format_confidence', 'Format Confidence', 'low', 'Low', '#dc3545', 10, 1, NOW()),
('format_confidence', 'Format Confidence', 'medium', 'Medium', '#ffc107', 20, 1, NOW()),
('format_confidence', 'Format Confidence', 'high', 'High', '#28a745', 30, 1, NOW()),
('format_confidence', 'Format Confidence', 'certain', 'Certain', '#007bff', 40, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('package_format', 'Package Format', 'bagit', 'BagIt', '#007bff', 10, 1, NOW()),
('package_format', 'Package Format', 'zip', 'ZIP', '#28a745', 20, 1, NOW()),
('package_format', 'Package Format', 'tar', 'TAR', '#6f42c1', 30, 1, NOW()),
('package_format', 'Package Format', 'directory', 'Directory', '#6c757d', 40, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('policy_type', 'Policy Type', 'fixity', 'Fixity', '#007bff', 10, 1, NOW()),
('policy_type', 'Policy Type', 'format', 'Format', '#28a745', 20, 1, NOW()),
('policy_type', 'Policy Type', 'retention', 'Retention', '#6f42c1', 30, 1, NOW()),
('policy_type', 'Policy Type', 'replication', 'Replication', '#fd7e14', 40, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('replication_operation', 'Replication Operation', 'sync', 'Sync', '#28a745', 10, 1, NOW()),
('replication_operation', 'Replication Operation', 'verify', 'Verify', '#007bff', 20, 1, NOW()),
('replication_operation', 'Replication Operation', 'restore', 'Restore', '#6f42c1', 30, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('replication_target_type', 'Replication Target Type', 'local', 'Local', '#28a745', 10, 1, NOW()),
('replication_target_type', 'Replication Target Type', 'sftp', 'SFTP', '#007bff', 20, 1, NOW()),
('replication_target_type', 'Replication Target Type', 's3', 'AWS S3', '#fd7e14', 30, 1, NOW()),
('replication_target_type', 'Replication Target Type', 'azure', 'Azure Blob', '#17a2b8', 40, 1, NOW()),
('replication_target_type', 'Replication Target Type', 'gcs', 'Google Cloud Storage', '#dc3545', 50, 1, NOW()),
('replication_target_type', 'Replication Target Type', 'rsync', 'Rsync', '#6c757d', 60, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('virus_scan_status', 'Virus Scan Status', 'clean', 'Clean', '#28a745', 10, 1, NOW()),
('virus_scan_status', 'Virus Scan Status', 'infected', 'Infected', '#dc3545', 20, 1, NOW()),
('virus_scan_status', 'Virus Scan Status', 'error', 'Error', '#fd7e14', 30, 1, NOW()),
('virus_scan_status', 'Virus Scan Status', 'skipped', 'Skipped', '#6c757d', 40, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('workflow_schedule_type', 'Workflow Schedule Type', 'cron', 'Cron', '#007bff', 10, 1, NOW()),
('workflow_schedule_type', 'Workflow Schedule Type', 'interval', 'Interval', '#28a745', 20, 1, NOW()),
('workflow_schedule_type', 'Workflow Schedule Type', 'manual', 'Manual', '#6c757d', 30, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('preservation_workflow_type', 'Preservation Workflow Type', 'format_identification', 'Format Identification', '#007bff', 10, 1, NOW()),
('preservation_workflow_type', 'Preservation Workflow Type', 'fixity_check', 'Fixity Check', '#28a745', 20, 1, NOW()),
('preservation_workflow_type', 'Preservation Workflow Type', 'virus_scan', 'Virus Scan', '#dc3545', 30, 1, NOW()),
('preservation_workflow_type', 'Preservation Workflow Type', 'format_conversion', 'Format Conversion', '#6f42c1', 40, 1, NOW()),
('preservation_workflow_type', 'Preservation Workflow Type', 'backup_verification', 'Backup Verification', '#fd7e14', 50, 1, NOW()),
('preservation_workflow_type', 'Preservation Workflow Type', 'replication', 'Replication', '#17a2b8', 60, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('preservation_object_role', 'Preservation Object Role', 'payload', 'Payload', '#007bff', 10, 1, NOW()),
('preservation_object_role', 'Preservation Object Role', 'metadata', 'Metadata', '#28a745', 20, 1, NOW()),
('preservation_object_role', 'Preservation Object Role', 'manifest', 'Manifest', '#6f42c1', 30, 1, NOW()),
('preservation_object_role', 'Preservation Object Role', 'tagfile', 'Tag File', '#fd7e14', 40, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('linking_agent_type', 'Linking Agent Type', 'user', 'User', '#007bff', 10, 1, NOW()),
('linking_agent_type', 'Linking Agent Type', 'system', 'System', '#6c757d', 20, 1, NOW()),
('linking_agent_type', 'Linking Agent Type', 'software', 'Software', '#28a745', 30, 1, NOW()),
('linking_agent_type', 'Linking Agent Type', 'organization', 'Organization', '#6f42c1', 40, 1, NOW());

-- ============================================================================
-- BACKUP TYPES
-- ============================================================================

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('backup_frequency', 'Backup Frequency', 'hourly', 'Hourly', '#dc3545', 10, 1, NOW()),
('backup_frequency', 'Backup Frequency', 'daily', 'Daily', '#fd7e14', 20, 1, NOW()),
('backup_frequency', 'Backup Frequency', 'weekly', 'Weekly', '#ffc107', 30, 1, NOW()),
('backup_frequency', 'Backup Frequency', 'monthly', 'Monthly', '#28a745', 40, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('backup_status', 'Backup Status', 'pending', 'Pending', '#ffc107', 10, 1, NOW()),
('backup_status', 'Backup Status', 'in_progress', 'In Progress', '#007bff', 20, 1, NOW()),
('backup_status', 'Backup Status', 'completed', 'Completed', '#28a745', 30, 1, NOW()),
('backup_status', 'Backup Status', 'failed', 'Failed', '#dc3545', 40, 1, NOW());

-- ============================================================================
-- Show Phase 2C statistics
-- ============================================================================

SELECT 'Phase 2C Migration Complete' as status;

-- ---------------------------------------------------------------------------
-- Merged in from database/enum_to_dropdown_migration_phase2d.sql on 2026-08-17.
--
-- It sat beside install.sql and was never run by install-plugin-schema.php,
-- so a clean install silently lacked whatever it defines. Our own instances
-- had it because someone applied the file by hand. A plugin's schema is
-- install.sql; there is no second file.
-- ---------------------------------------------------------------------------

-- ============================================================================
-- ENUM to ahg_dropdown Migration Script - PHASE 2D (Final)
-- Generated: 2026-02-04
--
-- Final part: Privacy, Provenance, Research, RIC, Rights types
-- ============================================================================

-- ============================================================================
-- PRIVACY TYPES
-- ============================================================================

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('breach_notification_method', 'Breach Notification Method', 'email', 'Email', '#007bff', 10, 1, NOW()),
('breach_notification_method', 'Breach Notification Method', 'letter', 'Letter', '#28a745', 20, 1, NOW()),
('breach_notification_method', 'Breach Notification Method', 'portal', 'Portal', '#6f42c1', 30, 1, NOW()),
('breach_notification_method', 'Breach Notification Method', 'phone', 'Phone', '#fd7e14', 40, 1, NOW()),
('breach_notification_method', 'Breach Notification Method', 'in_person', 'In Person', '#17a2b8', 50, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('breach_notification_type', 'Breach Notification Type', 'regulator', 'Regulator', '#dc3545', 10, 1, NOW()),
('breach_notification_type', 'Breach Notification Type', 'data_subject', 'Data Subject', '#007bff', 20, 1, NOW()),
('breach_notification_type', 'Breach Notification Type', 'internal', 'Internal', '#6c757d', 30, 1, NOW()),
('breach_notification_type', 'Breach Notification Type', 'third_party', 'Third Party', '#fd7e14', 40, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('risk_to_rights', 'Risk to Rights', 'unlikely', 'Unlikely', '#28a745', 10, 1, NOW()),
('risk_to_rights', 'Risk to Rights', 'possible', 'Possible', '#ffc107', 20, 1, NOW()),
('risk_to_rights', 'Risk to Rights', 'likely', 'Likely', '#fd7e14', 30, 1, NOW()),
('risk_to_rights', 'Risk to Rights', 'high', 'High', '#dc3545', 40, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('complaint_status', 'Complaint Status', 'received', 'Received', '#6c757d', 10, 1, NOW()),
('complaint_status', 'Complaint Status', 'investigating', 'Investigating', '#17a2b8', 20, 1, NOW()),
('complaint_status', 'Complaint Status', 'resolved', 'Resolved', '#28a745', 30, 1, NOW()),
('complaint_status', 'Complaint Status', 'escalated', 'Escalated', '#dc3545', 40, 1, NOW()),
('complaint_status', 'Complaint Status', 'closed', 'Closed', '#343a40', 50, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('consent_type', 'Consent Type', 'processing', 'Processing', '#007bff', 10, 1, NOW()),
('consent_type', 'Consent Type', 'marketing', 'Marketing', '#28a745', 20, 1, NOW()),
('consent_type', 'Consent Type', 'profiling', 'Profiling', '#6f42c1', 30, 1, NOW()),
('consent_type', 'Consent Type', 'third_party', 'Third Party', '#fd7e14', 40, 1, NOW()),
('consent_type', 'Consent Type', 'cookies', 'Cookies', '#17a2b8', 50, 1, NOW()),
('consent_type', 'Consent Type', 'research', 'Research', '#ffc107', 60, 1, NOW()),
('consent_type', 'Consent Type', 'special_category', 'Special Category', '#dc3545', 70, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('consent_log_action', 'Consent Log Action', 'granted', 'Granted', '#28a745', 10, 1, NOW()),
('consent_log_action', 'Consent Log Action', 'withdrawn', 'Withdrawn', '#dc3545', 20, 1, NOW()),
('consent_log_action', 'Consent Log Action', 'expired', 'Expired', '#6c757d', 30, 1, NOW()),
('consent_log_action', 'Consent Log Action', 'renewed', 'Renewed', '#007bff', 40, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('data_type', 'Data Type', 'personal', 'Personal', '#ffc107', 10, 1, NOW()),
('data_type', 'Data Type', 'special_category', 'Special Category', '#dc3545', 20, 1, NOW()),
('data_type', 'Data Type', 'children', 'Children', '#e83e8c', 30, 1, NOW()),
('data_type', 'Data Type', 'criminal', 'Criminal', '#343a40', 40, 1, NOW()),
('data_type', 'Data Type', 'financial', 'Financial', '#28a745', 50, 1, NOW()),
('data_type', 'Data Type', 'health', 'Health', '#fd7e14', 60, 1, NOW()),
('data_type', 'Data Type', 'biometric', 'Biometric', '#6f42c1', 70, 1, NOW()),
('data_type', 'Data Type', 'genetic', 'Genetic', '#17a2b8', 80, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('storage_format', 'Storage Format', 'electronic', 'Electronic', '#007bff', 10, 1, NOW()),
('storage_format', 'Storage Format', 'paper', 'Paper', '#28a745', 20, 1, NOW()),
('storage_format', 'Storage Format', 'both', 'Both', '#6f42c1', 30, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('dsar_outcome', 'DSAR Outcome', 'granted', 'Granted', '#28a745', 10, 1, NOW()),
('dsar_outcome', 'DSAR Outcome', 'partially_granted', 'Partially Granted', '#ffc107', 20, 1, NOW()),
('dsar_outcome', 'DSAR Outcome', 'refused', 'Refused', '#dc3545', 30, 1, NOW()),
('dsar_outcome', 'DSAR Outcome', 'not_applicable', 'Not Applicable', '#6c757d', 40, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('paia_access_form', 'PAIA Access Form', 'inspect', 'Inspect', '#007bff', 10, 1, NOW()),
('paia_access_form', 'PAIA Access Form', 'copy', 'Copy', '#28a745', 20, 1, NOW()),
('paia_access_form', 'PAIA Access Form', 'both', 'Both', '#6f42c1', 30, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('paia_section', 'PAIA Section', 'section_18', 'Section 18', '#007bff', 10, 1, NOW()),
('paia_section', 'PAIA Section', 'section_22', 'Section 22', '#28a745', 20, 1, NOW()),
('paia_section', 'PAIA Section', 'section_23', 'Section 23', '#6f42c1', 30, 1, NOW()),
('paia_section', 'PAIA Section', 'section_50', 'Section 50', '#fd7e14', 40, 1, NOW()),
('paia_section', 'PAIA Section', 'section_77', 'Section 77', '#17a2b8', 50, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('paia_status', 'PAIA Status', 'received', 'Received', '#6c757d', 10, 1, NOW()),
('paia_status', 'PAIA Status', 'processing', 'Processing', '#17a2b8', 20, 1, NOW()),
('paia_status', 'PAIA Status', 'granted', 'Granted', '#28a745', 30, 1, NOW()),
('paia_status', 'PAIA Status', 'partially_granted', 'Partially Granted', '#ffc107', 40, 1, NOW()),
('paia_status', 'PAIA Status', 'refused', 'Refused', '#dc3545', 50, 1, NOW()),
('paia_status', 'PAIA Status', 'transferred', 'Transferred', '#6f42c1', 60, 1, NOW()),
('paia_status', 'PAIA Status', 'appealed', 'Appealed', '#fd7e14', 70, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('redaction_file_type', 'Redaction File Type', 'pdf', 'PDF', '#dc3545', 10, 1, NOW()),
('redaction_file_type', 'Redaction File Type', 'image', 'Image', '#28a745', 20, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('retention_disposal_action', 'Retention Disposal Action', 'destroy', 'Destroy', '#dc3545', 10, 1, NOW()),
('retention_disposal_action', 'Retention Disposal Action', 'archive', 'Archive', '#28a745', 20, 1, NOW()),
('retention_disposal_action', 'Retention Disposal Action', 'anonymize', 'Anonymize', '#6f42c1', 30, 1, NOW()),
('retention_disposal_action', 'Retention Disposal Action', 'review', 'Review', '#ffc107', 40, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('redaction_region_type', 'Redaction Region Type', 'rectangle', 'Rectangle', '#007bff', 10, 1, NOW()),
('redaction_region_type', 'Redaction Region Type', 'polygon', 'Polygon', '#28a745', 20, 1, NOW()),
('redaction_region_type', 'Redaction Region Type', 'freehand', 'Freehand', '#6f42c1', 30, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('redaction_source', 'Redaction Source', 'manual', 'Manual', '#28a745', 10, 1, NOW()),
('redaction_source', 'Redaction Source', 'auto_ner', 'Auto NER', '#007bff', 20, 1, NOW()),
('redaction_source', 'Redaction Source', 'auto_pii', 'Auto PII', '#6f42c1', 30, 1, NOW()),
('redaction_source', 'Redaction Source', 'imported', 'Imported', '#fd7e14', 40, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('redaction_status', 'Redaction Status', 'pending', 'Pending', '#ffc107', 10, 1, NOW()),
('redaction_status', 'Redaction Status', 'approved', 'Approved', '#28a745', 20, 1, NOW()),
('redaction_status', 'Redaction Status', 'applied', 'Applied', '#007bff', 30, 1, NOW()),
('redaction_status', 'Redaction Status', 'rejected', 'Rejected', '#dc3545', 40, 1, NOW());

-- ============================================================================
-- PROVENANCE TYPES
-- ============================================================================

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('provenance_agent_type', 'Provenance Agent Type', 'person', 'Person', '#007bff', 10, 1, NOW()),
('provenance_agent_type', 'Provenance Agent Type', 'organization', 'Organization', '#28a745', 20, 1, NOW()),
('provenance_agent_type', 'Provenance Agent Type', 'family', 'Family', '#6f42c1', 30, 1, NOW()),
('provenance_agent_type', 'Provenance Agent Type', 'unknown', 'Unknown', '#6c757d', 40, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('provenance_document_type', 'Provenance Document Type', 'deed_of_gift', 'Deed of Gift', '#28a745', 10, 1, NOW()),
('provenance_document_type', 'Provenance Document Type', 'bill_of_sale', 'Bill of Sale', '#007bff', 20, 1, NOW()),
('provenance_document_type', 'Provenance Document Type', 'invoice', 'Invoice', '#17a2b8', 30, 1, NOW()),
('provenance_document_type', 'Provenance Document Type', 'receipt', 'Receipt', '#ffc107', 40, 1, NOW()),
('provenance_document_type', 'Provenance Document Type', 'auction_catalog', 'Auction Catalog', '#6f42c1', 50, 1, NOW()),
('provenance_document_type', 'Provenance Document Type', 'exhibition_catalog', 'Exhibition Catalog', '#e83e8c', 60, 1, NOW()),
('provenance_document_type', 'Provenance Document Type', 'inventory', 'Inventory', '#20c997', 70, 1, NOW()),
('provenance_document_type', 'Provenance Document Type', 'insurance_record', 'Insurance Record', '#dc3545', 80, 1, NOW()),
('provenance_document_type', 'Provenance Document Type', 'photograph', 'Photograph', '#fd7e14', 90, 1, NOW()),
('provenance_document_type', 'Provenance Document Type', 'correspondence', 'Correspondence', '#6c757d', 100, 1, NOW()),
('provenance_document_type', 'Provenance Document Type', 'certificate', 'Certificate', '#28a745', 110, 1, NOW()),
('provenance_document_type', 'Provenance Document Type', 'customs_document', 'Customs Document', '#007bff', 120, 1, NOW()),
('provenance_document_type', 'Provenance Document Type', 'export_license', 'Export License', '#17a2b8', 130, 1, NOW()),
('provenance_document_type', 'Provenance Document Type', 'import_permit', 'Import Permit', '#6f42c1', 140, 1, NOW()),
('provenance_document_type', 'Provenance Document Type', 'appraisal', 'Appraisal', '#ffc107', 150, 1, NOW()),
('provenance_document_type', 'Provenance Document Type', 'condition_report', 'Condition Report', '#fd7e14', 160, 1, NOW()),
('provenance_document_type', 'Provenance Document Type', 'newspaper_clipping', 'Newspaper Clipping', '#868e96', 170, 1, NOW()),
('provenance_document_type', 'Provenance Document Type', 'publication', 'Publication', '#343a40', 180, 1, NOW()),
('provenance_document_type', 'Provenance Document Type', 'oral_history', 'Oral History', '#e83e8c', 190, 1, NOW()),
('provenance_document_type', 'Provenance Document Type', 'affidavit', 'Affidavit', '#dc3545', 200, 1, NOW()),
('provenance_document_type', 'Provenance Document Type', 'legal_document', 'Legal Document', '#343a40', 210, 1, NOW()),
('provenance_document_type', 'Provenance Document Type', 'other', 'Other', '#6c757d', 220, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('provenance_certainty', 'Provenance Certainty', 'certain', 'Certain', '#28a745', 10, 1, NOW()),
('provenance_certainty', 'Provenance Certainty', 'probable', 'Probable', '#007bff', 20, 1, NOW()),
('provenance_certainty', 'Provenance Certainty', 'possible', 'Possible', '#ffc107', 30, 1, NOW()),
('provenance_certainty', 'Provenance Certainty', 'uncertain', 'Uncertain', '#fd7e14', 40, 1, NOW()),
('provenance_certainty', 'Provenance Certainty', 'unknown', 'Unknown', '#6c757d', 50, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('date_qualifier', 'Date Qualifier', 'circa', 'Circa', '#6c757d', 10, 1, NOW()),
('date_qualifier', 'Date Qualifier', 'before', 'Before', '#007bff', 20, 1, NOW()),
('date_qualifier', 'Date Qualifier', 'after', 'After', '#28a745', 30, 1, NOW()),
('date_qualifier', 'Date Qualifier', 'by', 'By', '#ffc107', 40, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('provenance_owner_type', 'Provenance Owner Type', 'person', 'Person', '#007bff', 10, 1, NOW()),
('provenance_owner_type', 'Provenance Owner Type', 'family', 'Family', '#28a745', 20, 1, NOW()),
('provenance_owner_type', 'Provenance Owner Type', 'dealer', 'Dealer', '#fd7e14', 30, 1, NOW()),
('provenance_owner_type', 'Provenance Owner Type', 'auction_house', 'Auction House', '#ffc107', 40, 1, NOW()),
('provenance_owner_type', 'Provenance Owner Type', 'museum', 'Museum', '#6f42c1', 50, 1, NOW()),
('provenance_owner_type', 'Provenance Owner Type', 'corporate', 'Corporate', '#17a2b8', 60, 1, NOW()),
('provenance_owner_type', 'Provenance Owner Type', 'government', 'Government', '#dc3545', 70, 1, NOW()),
('provenance_owner_type', 'Provenance Owner Type', 'religious', 'Religious', '#e83e8c', 80, 1, NOW()),
('provenance_owner_type', 'Provenance Owner Type', 'artist', 'Artist', '#20c997', 90, 1, NOW()),
('provenance_owner_type', 'Provenance Owner Type', 'unknown', 'Unknown', '#6c757d', 100, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('provenance_transfer_type', 'Provenance Transfer Type', 'sale', 'Sale', '#28a745', 10, 1, NOW()),
('provenance_transfer_type', 'Provenance Transfer Type', 'auction', 'Auction', '#ffc107', 20, 1, NOW()),
('provenance_transfer_type', 'Provenance Transfer Type', 'gift', 'Gift', '#007bff', 30, 1, NOW()),
('provenance_transfer_type', 'Provenance Transfer Type', 'bequest', 'Bequest', '#6f42c1', 40, 1, NOW()),
('provenance_transfer_type', 'Provenance Transfer Type', 'inheritance', 'Inheritance', '#17a2b8', 50, 1, NOW()),
('provenance_transfer_type', 'Provenance Transfer Type', 'commission', 'Commission', '#fd7e14', 60, 1, NOW()),
('provenance_transfer_type', 'Provenance Transfer Type', 'exchange', 'Exchange', '#e83e8c', 70, 1, NOW()),
('provenance_transfer_type', 'Provenance Transfer Type', 'seizure', 'Seizure', '#dc3545', 80, 1, NOW()),
('provenance_transfer_type', 'Provenance Transfer Type', 'restitution', 'Restitution', '#343a40', 90, 1, NOW()),
('provenance_transfer_type', 'Provenance Transfer Type', 'transfer', 'Transfer', '#20c997', 100, 1, NOW()),
('provenance_transfer_type', 'Provenance Transfer Type', 'loan', 'Loan', '#868e96', 110, 1, NOW()),
('provenance_transfer_type', 'Provenance Transfer Type', 'found', 'Found', '#6c757d', 120, 1, NOW()),
('provenance_transfer_type', 'Provenance Transfer Type', 'created', 'Created', '#28a745', 130, 1, NOW()),
('provenance_transfer_type', 'Provenance Transfer Type', 'unknown', 'Unknown', '#6c757d', 140, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('provenance_event_type', 'Provenance Event Type', 'creation', 'Creation', '#28a745', 10, 1, NOW()),
('provenance_event_type', 'Provenance Event Type', 'commission', 'Commission', '#007bff', 20, 1, NOW()),
('provenance_event_type', 'Provenance Event Type', 'sale', 'Sale', '#ffc107', 30, 1, NOW()),
('provenance_event_type', 'Provenance Event Type', 'purchase', 'Purchase', '#28a745', 40, 1, NOW()),
('provenance_event_type', 'Provenance Event Type', 'auction', 'Auction', '#fd7e14', 50, 1, NOW()),
('provenance_event_type', 'Provenance Event Type', 'gift', 'Gift', '#6f42c1', 60, 1, NOW()),
('provenance_event_type', 'Provenance Event Type', 'donation', 'Donation', '#17a2b8', 70, 1, NOW()),
('provenance_event_type', 'Provenance Event Type', 'bequest', 'Bequest', '#e83e8c', 80, 1, NOW()),
('provenance_event_type', 'Provenance Event Type', 'inheritance', 'Inheritance', '#20c997', 90, 1, NOW()),
('provenance_event_type', 'Provenance Event Type', 'descent', 'Descent', '#343a40', 100, 1, NOW()),
('provenance_event_type', 'Provenance Event Type', 'loan_out', 'Loan Out', '#dc3545', 110, 1, NOW()),
('provenance_event_type', 'Provenance Event Type', 'loan_return', 'Loan Return', '#28a745', 120, 1, NOW()),
('provenance_event_type', 'Provenance Event Type', 'deposit', 'Deposit', '#007bff', 130, 1, NOW()),
('provenance_event_type', 'Provenance Event Type', 'withdrawal', 'Withdrawal', '#fd7e14', 140, 1, NOW()),
('provenance_event_type', 'Provenance Event Type', 'transfer', 'Transfer', '#6f42c1', 150, 1, NOW()),
('provenance_event_type', 'Provenance Event Type', 'exchange', 'Exchange', '#17a2b8', 160, 1, NOW()),
('provenance_event_type', 'Provenance Event Type', 'theft', 'Theft', '#dc3545', 170, 1, NOW()),
('provenance_event_type', 'Provenance Event Type', 'recovery', 'Recovery', '#28a745', 180, 1, NOW()),
('provenance_event_type', 'Provenance Event Type', 'confiscation', 'Confiscation', '#343a40', 190, 1, NOW()),
('provenance_event_type', 'Provenance Event Type', 'restitution', 'Restitution', '#6f42c1', 200, 1, NOW()),
('provenance_event_type', 'Provenance Event Type', 'repatriation', 'Repatriation', '#e83e8c', 210, 1, NOW()),
('provenance_event_type', 'Provenance Event Type', 'discovery', 'Discovery', '#ffc107', 220, 1, NOW()),
('provenance_event_type', 'Provenance Event Type', 'excavation', 'Excavation', '#fd7e14', 230, 1, NOW()),
('provenance_event_type', 'Provenance Event Type', 'import', 'Import', '#17a2b8', 240, 1, NOW()),
('provenance_event_type', 'Provenance Event Type', 'export', 'Export', '#20c997', 250, 1, NOW()),
('provenance_event_type', 'Provenance Event Type', 'authentication', 'Authentication', '#007bff', 260, 1, NOW()),
('provenance_event_type', 'Provenance Event Type', 'appraisal', 'Appraisal', '#28a745', 270, 1, NOW()),
('provenance_event_type', 'Provenance Event Type', 'conservation', 'Conservation', '#6f42c1', 280, 1, NOW()),
('provenance_event_type', 'Provenance Event Type', 'restoration', 'Restoration', '#fd7e14', 290, 1, NOW()),
('provenance_event_type', 'Provenance Event Type', 'accessioning', 'Accessioning', '#ffc107', 300, 1, NOW()),
('provenance_event_type', 'Provenance Event Type', 'deaccessioning', 'Deaccessioning', '#dc3545', 310, 1, NOW()),
('provenance_event_type', 'Provenance Event Type', 'unknown', 'Unknown', '#6c757d', 320, 1, NOW()),
('provenance_event_type', 'Provenance Event Type', 'other', 'Other', '#868e96', 330, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('evidence_type', 'Evidence Type', 'documentary', 'Documentary', '#28a745', 10, 1, NOW()),
('evidence_type', 'Evidence Type', 'physical', 'Physical', '#007bff', 20, 1, NOW()),
('evidence_type', 'Evidence Type', 'oral', 'Oral', '#6f42c1', 30, 1, NOW()),
('evidence_type', 'Evidence Type', 'circumstantial', 'Circumstantial', '#ffc107', 40, 1, NOW()),
('evidence_type', 'Evidence Type', 'none', 'None', '#6c757d', 50, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('provenance_acquisition_type', 'Provenance Acquisition Type', 'donation', 'Donation', '#28a745', 10, 1, NOW()),
('provenance_acquisition_type', 'Provenance Acquisition Type', 'purchase', 'Purchase', '#007bff', 20, 1, NOW()),
('provenance_acquisition_type', 'Provenance Acquisition Type', 'bequest', 'Bequest', '#6f42c1', 30, 1, NOW()),
('provenance_acquisition_type', 'Provenance Acquisition Type', 'transfer', 'Transfer', '#17a2b8', 40, 1, NOW()),
('provenance_acquisition_type', 'Provenance Acquisition Type', 'loan', 'Loan', '#ffc107', 50, 1, NOW()),
('provenance_acquisition_type', 'Provenance Acquisition Type', 'deposit', 'Deposit', '#fd7e14', 60, 1, NOW()),
('provenance_acquisition_type', 'Provenance Acquisition Type', 'exchange', 'Exchange', '#e83e8c', 70, 1, NOW()),
('provenance_acquisition_type', 'Provenance Acquisition Type', 'field_collection', 'Field Collection', '#20c997', 80, 1, NOW()),
('provenance_acquisition_type', 'Provenance Acquisition Type', 'unknown', 'Unknown', '#6c757d', 90, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('cultural_property_status', 'Cultural Property Status', 'none', 'None', '#6c757d', 10, 1, NOW()),
('cultural_property_status', 'Cultural Property Status', 'claimed', 'Claimed', '#ffc107', 20, 1, NOW()),
('cultural_property_status', 'Cultural Property Status', 'disputed', 'Disputed', '#dc3545', 30, 1, NOW()),
('cultural_property_status', 'Cultural Property Status', 'repatriated', 'Repatriated', '#6f42c1', 40, 1, NOW()),
('cultural_property_status', 'Cultural Property Status', 'cleared', 'Cleared', '#28a745', 50, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('custody_type', 'Custody Type', 'permanent', 'Permanent', '#28a745', 10, 1, NOW()),
('custody_type', 'Custody Type', 'temporary', 'Temporary', '#ffc107', 20, 1, NOW()),
('custody_type', 'Custody Type', 'loan', 'Loan', '#007bff', 30, 1, NOW()),
('custody_type', 'Custody Type', 'deposit', 'Deposit', '#6f42c1', 40, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('research_status', 'Research Status', 'not_started', 'Not Started', '#6c757d', 10, 1, NOW()),
('research_status', 'Research Status', 'in_progress', 'In Progress', '#007bff', 20, 1, NOW()),
('research_status', 'Research Status', 'complete', 'Complete', '#28a745', 30, 1, NOW()),
('research_status', 'Research Status', 'inconclusive', 'Inconclusive', '#ffc107', 40, 1, NOW());

-- ============================================================================
-- REPORT TYPES
-- ============================================================================

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('report_category', 'Report Category', 'collection', 'Collection', '#007bff', 10, 1, NOW()),
('report_category', 'Report Category', 'acquisition', 'Acquisition', '#28a745', 20, 1, NOW()),
('report_category', 'Report Category', 'access', 'Access', '#6f42c1', 30, 1, NOW()),
('report_category', 'Report Category', 'preservation', 'Preservation', '#fd7e14', 40, 1, NOW()),
('report_category', 'Report Category', 'researcher', 'Researcher', '#17a2b8', 50, 1, NOW()),
('report_category', 'Report Category', 'compliance', 'Compliance', '#dc3545', 60, 1, NOW()),
('report_category', 'Report Category', 'statistics', 'Statistics', '#ffc107', 70, 1, NOW()),
('report_category', 'Report Category', 'custom', 'Custom', '#6c757d', 80, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('report_frequency', 'Report Frequency', 'daily', 'Daily', '#dc3545', 10, 1, NOW()),
('report_frequency', 'Report Frequency', 'weekly', 'Weekly', '#fd7e14', 20, 1, NOW()),
('report_frequency', 'Report Frequency', 'monthly', 'Monthly', '#ffc107', 30, 1, NOW()),
('report_frequency', 'Report Frequency', 'quarterly', 'Quarterly', '#28a745', 40, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('report_format', 'Report Format', 'pdf', 'PDF', '#dc3545', 10, 1, NOW()),
('report_format', 'Report Format', 'xlsx', 'Excel', '#28a745', 20, 1, NOW()),
('report_format', 'Report Format', 'csv', 'CSV', '#007bff', 30, 1, NOW());

-- ============================================================================
-- RIC (Records in Context) TYPES
-- ============================================================================

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('orphan_detection_method', 'Orphan Detection Method', 'integrity_check', 'Integrity Check', '#007bff', 10, 1, NOW()),
('orphan_detection_method', 'Orphan Detection Method', 'sync_failure', 'Sync Failure', '#dc3545', 20, 1, NOW()),
('orphan_detection_method', 'Orphan Detection Method', 'manual', 'Manual', '#28a745', 30, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('orphan_status', 'Orphan Status', 'detected', 'Detected', '#ffc107', 10, 1, NOW()),
('orphan_status', 'Orphan Status', 'reviewed', 'Reviewed', '#17a2b8', 20, 1, NOW()),
('orphan_status', 'Orphan Status', 'cleaned', 'Cleaned', '#28a745', 30, 1, NOW()),
('orphan_status', 'Orphan Status', 'retained', 'Retained', '#6f42c1', 40, 1, NOW()),
('orphan_status', 'Orphan Status', 'restored', 'Restored', '#007bff', 50, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('ric_operation', 'RIC Operation', 'create', 'Create', '#28a745', 10, 1, NOW()),
('ric_operation', 'RIC Operation', 'update', 'Update', '#007bff', 20, 1, NOW()),
('ric_operation', 'RIC Operation', 'delete', 'Delete', '#dc3545', 30, 1, NOW()),
('ric_operation', 'RIC Operation', 'move', 'Move', '#6f42c1', 40, 1, NOW()),
('ric_operation', 'RIC Operation', 'resync', 'Resync', '#17a2b8', 50, 1, NOW()),
('ric_operation', 'RIC Operation', 'cleanup', 'Cleanup', '#ffc107', 60, 1, NOW()),
('ric_operation', 'RIC Operation', 'integrity_check', 'Integrity Check', '#fd7e14', 70, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('sync_status', 'Sync Status', 'synced', 'Synced', '#28a745', 10, 1, NOW()),
('sync_status', 'Sync Status', 'pending', 'Pending', '#ffc107', 20, 1, NOW()),
('sync_status', 'Sync Status', 'failed', 'Failed', '#dc3545', 30, 1, NOW()),
('sync_status', 'Sync Status', 'deleted', 'Deleted', '#343a40', 40, 1, NOW()),
('sync_status', 'Sync Status', 'orphaned', 'Orphaned', '#6c757d', 50, 1, NOW());

-- ============================================================================
-- RIGHTS TYPES
-- ============================================================================

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('derivative_rule_type', 'Derivative Rule Type', 'watermark', 'Watermark', '#007bff', 10, 1, NOW()),
('derivative_rule_type', 'Derivative Rule Type', 'redaction', 'Redaction', '#dc3545', 20, 1, NOW()),
('derivative_rule_type', 'Derivative Rule Type', 'resize', 'Resize', '#28a745', 30, 1, NOW()),
('derivative_rule_type', 'Derivative Rule Type', 'format_conversion', 'Format Conversion', '#6f42c1', 40, 1, NOW()),
('derivative_rule_type', 'Derivative Rule Type', 'metadata_strip', 'Metadata Strip', '#ffc107', 50, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('watermark_position', 'Watermark Position', 'center', 'Center', '#007bff', 10, 1, NOW()),
('watermark_position', 'Watermark Position', 'top_left', 'Top Left', '#28a745', 20, 1, NOW()),
('watermark_position', 'Watermark Position', 'top_right', 'Top Right', '#6f42c1', 30, 1, NOW()),
('watermark_position', 'Watermark Position', 'bottom_left', 'Bottom Left', '#fd7e14', 40, 1, NOW()),
('watermark_position', 'Watermark Position', 'bottom_right', 'Bottom Right', '#17a2b8', 50, 1, NOW()),
('watermark_position', 'Watermark Position', 'tile', 'Tile', '#ffc107', 60, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('rights_grant_act', 'Rights Grant Act', 'render', 'Render', '#007bff', 10, 1, NOW()),
('rights_grant_act', 'Rights Grant Act', 'disseminate', 'Disseminate', '#28a745', 20, 1, NOW()),
('rights_grant_act', 'Rights Grant Act', 'replicate', 'Replicate', '#6f42c1', 30, 1, NOW()),
('rights_grant_act', 'Rights Grant Act', 'migrate', 'Migrate', '#fd7e14', 40, 1, NOW()),
('rights_grant_act', 'Rights Grant Act', 'modify', 'Modify', '#17a2b8', 50, 1, NOW()),
('rights_grant_act', 'Rights Grant Act', 'delete', 'Delete', '#dc3545', 60, 1, NOW()),
('rights_grant_act', 'Rights Grant Act', 'print', 'Print', '#ffc107', 70, 1, NOW()),
('rights_grant_act', 'Rights Grant Act', 'use', 'Use', '#e83e8c', 80, 1, NOW()),
('rights_grant_act', 'Rights Grant Act', 'publish', 'Publish', '#20c997', 90, 1, NOW()),
('rights_grant_act', 'Rights Grant Act', 'excerpt', 'Excerpt', '#343a40', 100, 1, NOW()),
('rights_grant_act', 'Rights Grant Act', 'annotate', 'Annotate', '#6c757d', 110, 1, NOW()),
('rights_grant_act', 'Rights Grant Act', 'move', 'Move', '#868e96', 120, 1, NOW()),
('rights_grant_act', 'Rights Grant Act', 'sell', 'Sell', '#28a745', 130, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('rights_restriction', 'Rights Restriction', 'allow', 'Allow', '#28a745', 10, 1, NOW()),
('rights_restriction', 'Rights Restriction', 'disallow', 'Disallow', '#dc3545', 20, 1, NOW()),
('rights_restriction', 'Rights Restriction', 'conditional', 'Conditional', '#ffc107', 30, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('orphan_search_source', 'Orphan Search Source', 'database', 'Database', '#007bff', 10, 1, NOW()),
('orphan_search_source', 'Orphan Search Source', 'registry', 'Registry', '#28a745', 20, 1, NOW()),
('orphan_search_source', 'Orphan Search Source', 'publisher', 'Publisher', '#6f42c1', 30, 1, NOW()),
('orphan_search_source', 'Orphan Search Source', 'author_society', 'Author Society', '#fd7e14', 40, 1, NOW()),
('orphan_search_source', 'Orphan Search Source', 'archive', 'Archive', '#17a2b8', 50, 1, NOW()),
('orphan_search_source', 'Orphan Search Source', 'library', 'Library', '#ffc107', 60, 1, NOW()),
('orphan_search_source', 'Orphan Search Source', 'internet', 'Internet', '#e83e8c', 70, 1, NOW()),
('orphan_search_source', 'Orphan Search Source', 'newspaper', 'Newspaper', '#20c997', 80, 1, NOW()),
('orphan_search_source', 'Orphan Search Source', 'other', 'Other', '#6c757d', 90, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('orphan_work_status', 'Orphan Work Status', 'in_progress', 'In Progress', '#007bff', 10, 1, NOW()),
('orphan_work_status', 'Orphan Work Status', 'completed', 'Completed', '#28a745', 20, 1, NOW()),
('orphan_work_status', 'Orphan Work Status', 'rights_holder_found', 'Rights Holder Found', '#6f42c1', 30, 1, NOW()),
('orphan_work_status', 'Orphan Work Status', 'abandoned', 'Abandoned', '#dc3545', 40, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('work_type', 'Work Type', 'literary', 'Literary', '#007bff', 10, 1, NOW()),
('work_type', 'Work Type', 'dramatic', 'Dramatic', '#28a745', 20, 1, NOW()),
('work_type', 'Work Type', 'musical', 'Musical', '#6f42c1', 30, 1, NOW()),
('work_type', 'Work Type', 'artistic', 'Artistic', '#fd7e14', 40, 1, NOW()),
('work_type', 'Work Type', 'film', 'Film', '#17a2b8', 50, 1, NOW()),
('work_type', 'Work Type', 'sound_recording', 'Sound Recording', '#ffc107', 60, 1, NOW()),
('work_type', 'Work Type', 'broadcast', 'Broadcast', '#e83e8c', 70, 1, NOW()),
('work_type', 'Work Type', 'typographical', 'Typographical', '#20c997', 80, 1, NOW()),
('work_type', 'Work Type', 'database', 'Database', '#343a40', 90, 1, NOW()),
('work_type', 'Work Type', 'photograph', 'Photograph', '#dc3545', 100, 1, NOW()),
('work_type', 'Work Type', 'other', 'Other', '#6c757d', 110, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('rights_basis', 'Rights Basis', 'copyright', 'Copyright', '#007bff', 10, 1, NOW()),
('rights_basis', 'Rights Basis', 'license', 'License', '#28a745', 20, 1, NOW()),
('rights_basis', 'Rights Basis', 'statute', 'Statute', '#6f42c1', 30, 1, NOW()),
('rights_basis', 'Rights Basis', 'donor', 'Donor', '#fd7e14', 40, 1, NOW()),
('rights_basis', 'Rights Basis', 'policy', 'Policy', '#17a2b8', 50, 1, NOW()),
('rights_basis', 'Rights Basis', 'other', 'Other', '#6c757d', 60, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('copyright_status', 'Copyright Status', 'copyrighted', 'Copyrighted', '#dc3545', 10, 1, NOW()),
('copyright_status', 'Copyright Status', 'public_domain', 'Public Domain', '#28a745', 20, 1, NOW()),
('copyright_status', 'Copyright Status', 'unknown', 'Unknown', '#6c757d', 30, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('rights_statement_category', 'Rights Statement Category', 'in-copyright', 'In Copyright', '#dc3545', 10, 1, NOW()),
('rights_statement_category', 'Rights Statement Category', 'no-copyright', 'No Copyright', '#28a745', 20, 1, NOW()),
('rights_statement_category', 'Rights Statement Category', 'other', 'Other', '#6c757d', 30, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('territory_type', 'Territory Type', 'include', 'Include', '#28a745', 10, 1, NOW()),
('territory_type', 'Territory Type', 'exclude', 'Exclude', '#dc3545', 20, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('tk_rights_category', 'TK Rights Category', 'tk', 'Traditional Knowledge', '#dc3545', 10, 1, NOW()),
('tk_rights_category', 'TK Rights Category', 'bc', 'Biocultural', '#28a745', 20, 1, NOW()),
('tk_rights_category', 'TK Rights Category', 'attribution', 'Attribution', '#007bff', 30, 1, NOW());

-- ============================================================================
-- HERITAGE ACCESS TYPES
-- ============================================================================

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('heritage_access_action', 'Heritage Access Action', 'view', 'View', '#6c757d', 10, 1, NOW()),
('heritage_access_action', 'Heritage Access Action', 'view_metadata', 'View Metadata', '#007bff', 20, 1, NOW()),
('heritage_access_action', 'Heritage Access Action', 'download', 'Download', '#28a745', 30, 1, NOW()),
('heritage_access_action', 'Heritage Access Action', 'download_master', 'Download Master', '#6f42c1', 40, 1, NOW()),
('heritage_access_action', 'Heritage Access Action', 'print', 'Print', '#fd7e14', 50, 1, NOW()),
('heritage_access_action', 'Heritage Access Action', 'all', 'All', '#dc3545', 60, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('heritage_access_applies_to', 'Heritage Access Applies To', 'all', 'All', '#28a745', 10, 1, NOW()),
('heritage_access_applies_to', 'Heritage Access Applies To', 'anonymous', 'Anonymous', '#6c757d', 20, 1, NOW()),
('heritage_access_applies_to', 'Heritage Access Applies To', 'authenticated', 'Authenticated', '#007bff', 30, 1, NOW()),
('heritage_access_applies_to', 'Heritage Access Applies To', 'trust_level', 'Trust Level', '#6f42c1', 40, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('heritage_rule_type', 'Heritage Rule Type', 'allow', 'Allow', '#28a745', 10, 1, NOW()),
('heritage_rule_type', 'Heritage Rule Type', 'deny', 'Deny', '#dc3545', 20, 1, NOW()),
('heritage_rule_type', 'Heritage Rule Type', 'require_approval', 'Require Approval', '#ffc107', 30, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('analytics_category', 'Analytics Category', 'content', 'Content', '#007bff', 10, 1, NOW()),
('analytics_category', 'Analytics Category', 'search', 'Search', '#28a745', 20, 1, NOW()),
('analytics_category', 'Analytics Category', 'access', 'Access', '#6f42c1', 30, 1, NOW()),
('analytics_category', 'Analytics Category', 'quality', 'Quality', '#fd7e14', 40, 1, NOW()),
('analytics_category', 'Analytics Category', 'system', 'System', '#17a2b8', 50, 1, NOW()),
('analytics_category', 'Analytics Category', 'opportunity', 'Opportunity', '#ffc107', 60, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('analytics_severity', 'Analytics Severity', 'info', 'Info', '#17a2b8', 10, 1, NOW()),
('analytics_severity', 'Analytics Severity', 'warning', 'Warning', '#ffc107', 20, 1, NOW()),
('analytics_severity', 'Analytics Severity', 'critical', 'Critical', '#dc3545', 30, 1, NOW()),
('analytics_severity', 'Analytics Severity', 'success', 'Success', '#28a745', 40, 1, NOW());

-- ============================================================================
-- EMAIL/NOTIFICATION TYPES
-- ============================================================================

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('email_setting_type', 'Email Setting Type', 'text', 'Text', '#007bff', 10, 1, NOW()),
('email_setting_type', 'Email Setting Type', 'email', 'Email', '#28a745', 20, 1, NOW()),
('email_setting_type', 'Email Setting Type', 'number', 'Number', '#6f42c1', 30, 1, NOW()),
('email_setting_type', 'Email Setting Type', 'boolean', 'Boolean', '#fd7e14', 40, 1, NOW()),
('email_setting_type', 'Email Setting Type', 'textarea', 'Textarea', '#17a2b8', 50, 1, NOW()),
('email_setting_type', 'Email Setting Type', 'password', 'Password', '#dc3545', 60, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('event_type', 'Event Type', 'view', 'View', '#6c757d', 10, 1, NOW()),
('event_type', 'Event Type', 'download', 'Download', '#28a745', 20, 1, NOW()),
('event_type', 'Event Type', 'search', 'Search', '#007bff', 30, 1, NOW()),
('event_type', 'Event Type', 'login', 'Login', '#6f42c1', 40, 1, NOW()),
('event_type', 'Event Type', 'api', 'API', '#fd7e14', 50, 1, NOW());

-- ============================================================================
-- Final statistics
-- ============================================================================

SELECT 'Phase 2D Migration Complete - ALL ENUM TYPES MIGRATED' as status;
SELECT COUNT(DISTINCT taxonomy) as total_taxonomies, COUNT(*) as total_terms FROM ahg_dropdown;

-- ---------------------------------------------------------------------------
-- Merged in from database/enum_to_dropdown_migration_phase2e.sql on 2026-08-17.
--
-- It sat beside install.sql and was never run by install-plugin-schema.php,
-- so a clean install silently lacked whatever it defines. Our own instances
-- had it because someone applied the file by hand. A plugin's schema is
-- install.sql; there is no second file.
-- ---------------------------------------------------------------------------

-- ============================================================================
-- ENUM to ahg_dropdown Migration Script - PHASE 2E
-- Generated: 2026-02-04
--
-- Research Plugin Types
-- ============================================================================

-- ============================================================================
-- RESEARCH ACTIVITY TYPES
-- ============================================================================

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('research_activity_type', 'Research Activity Type', 'class', 'Class', '#007bff', 10, 1, NOW()),
('research_activity_type', 'Research Activity Type', 'tour', 'Tour', '#28a745', 20, 1, NOW()),
('research_activity_type', 'Research Activity Type', 'exhibit', 'Exhibition', '#6f42c1', 30, 1, NOW()),
('research_activity_type', 'Research Activity Type', 'loan', 'Loan', '#fd7e14', 40, 1, NOW()),
('research_activity_type', 'Research Activity Type', 'conservation', 'Conservation', '#17a2b8', 50, 1, NOW()),
('research_activity_type', 'Research Activity Type', 'photography', 'Photography', '#ffc107', 60, 1, NOW()),
('research_activity_type', 'Research Activity Type', 'filming', 'Filming', '#e83e8c', 70, 1, NOW()),
('research_activity_type', 'Research Activity Type', 'event', 'Event', '#20c997', 80, 1, NOW()),
('research_activity_type', 'Research Activity Type', 'meeting', 'Meeting', '#343a40', 90, 1, NOW()),
('research_activity_type', 'Research Activity Type', 'other', 'Other', '#6c757d', 100, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('research_activity_status', 'Research Activity Status', 'requested', 'Requested', '#6c757d', 10, 1, NOW()),
('research_activity_status', 'Research Activity Status', 'tentative', 'Tentative', '#ffc107', 20, 1, NOW()),
('research_activity_status', 'Research Activity Status', 'confirmed', 'Confirmed', '#28a745', 30, 1, NOW()),
('research_activity_status', 'Research Activity Status', 'in_progress', 'In Progress', '#007bff', 40, 1, NOW()),
('research_activity_status', 'Research Activity Status', 'completed', 'Completed', '#28a745', 50, 1, NOW()),
('research_activity_status', 'Research Activity Status', 'cancelled', 'Cancelled', '#dc3545', 60, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('research_log_type', 'Research Log Type', 'view', 'View', '#6c757d', 10, 1, NOW()),
('research_log_type', 'Research Log Type', 'search', 'Search', '#007bff', 20, 1, NOW()),
('research_log_type', 'Research Log Type', 'download', 'Download', '#28a745', 30, 1, NOW()),
('research_log_type', 'Research Log Type', 'cite', 'Cite', '#6f42c1', 40, 1, NOW()),
('research_log_type', 'Research Log Type', 'annotate', 'Annotate', '#fd7e14', 50, 1, NOW()),
('research_log_type', 'Research Log Type', 'collect', 'Collect', '#17a2b8', 60, 1, NOW()),
('research_log_type', 'Research Log Type', 'book', 'Book', '#ffc107', 70, 1, NOW()),
('research_log_type', 'Research Log Type', 'request', 'Request', '#e83e8c', 80, 1, NOW()),
('research_log_type', 'Research Log Type', 'export', 'Export', '#20c997', 90, 1, NOW()),
('research_log_type', 'Research Log Type', 'share', 'Share', '#343a40', 100, 1, NOW()),
('research_log_type', 'Research Log Type', 'login', 'Login', '#6c757d', 110, 1, NOW()),
('research_log_type', 'Research Log Type', 'logout', 'Logout', '#868e96', 120, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('research_material_status', 'Research Material Status', 'requested', 'Requested', '#ffc107', 10, 1, NOW()),
('research_material_status', 'Research Material Status', 'approved', 'Approved', '#28a745', 20, 1, NOW()),
('research_material_status', 'Research Material Status', 'rejected', 'Rejected', '#dc3545', 30, 1, NOW()),
('research_material_status', 'Research Material Status', 'retrieved', 'Retrieved', '#17a2b8', 40, 1, NOW()),
('research_material_status', 'Research Material Status', 'in_use', 'In Use', '#007bff', 50, 1, NOW()),
('research_material_status', 'Research Material Status', 'returned', 'Returned', '#6c757d', 60, 1, NOW()),
('research_material_status', 'Research Material Status', 'damaged', 'Damaged', '#dc3545', 70, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('registration_status', 'Registration Status', 'pending', 'Pending', '#ffc107', 10, 1, NOW()),
('registration_status', 'Registration Status', 'confirmed', 'Confirmed', '#28a745', 20, 1, NOW()),
('registration_status', 'Registration Status', 'waitlist', 'Waitlist', '#17a2b8', 30, 1, NOW()),
('registration_status', 'Registration Status', 'cancelled', 'Cancelled', '#dc3545', 40, 1, NOW()),
('registration_status', 'Registration Status', 'attended', 'Attended', '#007bff', 50, 1, NOW()),
('registration_status', 'Registration Status', 'no_show', 'No Show', '#6c757d', 60, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('participant_role', 'Participant Role', 'organizer', 'Organizer', '#dc3545', 10, 1, NOW()),
('participant_role', 'Participant Role', 'instructor', 'Instructor', '#fd7e14', 20, 1, NOW()),
('participant_role', 'Participant Role', 'presenter', 'Presenter', '#6f42c1', 30, 1, NOW()),
('participant_role', 'Participant Role', 'student', 'Student', '#007bff', 40, 1, NOW()),
('participant_role', 'Participant Role', 'visitor', 'Visitor', '#28a745', 50, 1, NOW()),
('participant_role', 'Participant Role', 'assistant', 'Assistant', '#17a2b8', 60, 1, NOW()),
('participant_role', 'Participant Role', 'staff', 'Staff', '#ffc107', 70, 1, NOW()),
('participant_role', 'Participant Role', 'other', 'Other', '#6c757d', 80, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('annotation_type', 'Annotation Type', 'note', 'Note', '#007bff', 10, 1, NOW()),
('annotation_type', 'Annotation Type', 'highlight', 'Highlight', '#ffc107', 20, 1, NOW()),
('annotation_type', 'Annotation Type', 'bookmark', 'Bookmark', '#dc3545', 30, 1, NOW()),
('annotation_type', 'Annotation Type', 'tag', 'Tag', '#28a745', 40, 1, NOW()),
('annotation_type', 'Annotation Type', 'transcription', 'Transcription', '#6f42c1', 50, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('bibliography_type', 'Bibliography Type', 'archival', 'Archival', '#007bff', 10, 1, NOW()),
('bibliography_type', 'Bibliography Type', 'book', 'Book', '#28a745', 20, 1, NOW()),
('bibliography_type', 'Bibliography Type', 'article', 'Article', '#6f42c1', 30, 1, NOW()),
('bibliography_type', 'Bibliography Type', 'chapter', 'Chapter', '#fd7e14', 40, 1, NOW()),
('bibliography_type', 'Bibliography Type', 'thesis', 'Thesis', '#17a2b8', 50, 1, NOW()),
('bibliography_type', 'Bibliography Type', 'website', 'Website', '#ffc107', 60, 1, NOW()),
('bibliography_type', 'Bibliography Type', 'other', 'Other', '#6c757d', 70, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('booking_status', 'Booking Status', 'pending', 'Pending', '#ffc107', 10, 1, NOW()),
('booking_status', 'Booking Status', 'confirmed', 'Confirmed', '#28a745', 20, 1, NOW()),
('booking_status', 'Booking Status', 'cancelled', 'Cancelled', '#dc3545', 30, 1, NOW()),
('booking_status', 'Booking Status', 'completed', 'Completed', '#6c757d', 40, 1, NOW()),
('booking_status', 'Booking Status', 'no_show', 'No Show', '#343a40', 50, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('equipment_type', 'Equipment Type', 'microfilm_reader', 'Microfilm Reader', '#007bff', 10, 1, NOW()),
('equipment_type', 'Equipment Type', 'microfiche_reader', 'Microfiche Reader', '#28a745', 20, 1, NOW()),
('equipment_type', 'Equipment Type', 'scanner', 'Scanner', '#6f42c1', 30, 1, NOW()),
('equipment_type', 'Equipment Type', 'computer', 'Computer', '#fd7e14', 40, 1, NOW()),
('equipment_type', 'Equipment Type', 'magnifier', 'Magnifier', '#17a2b8', 50, 1, NOW()),
('equipment_type', 'Equipment Type', 'book_cradle', 'Book Cradle', '#ffc107', 60, 1, NOW()),
('equipment_type', 'Equipment Type', 'light_box', 'Light Box', '#e83e8c', 70, 1, NOW()),
('equipment_type', 'Equipment Type', 'camera_stand', 'Camera Stand', '#20c997', 80, 1, NOW()),
('equipment_type', 'Equipment Type', 'gloves', 'Gloves', '#343a40', 90, 1, NOW()),
('equipment_type', 'Equipment Type', 'weights', 'Weights', '#6c757d', 100, 1, NOW()),
('equipment_type', 'Equipment Type', 'other', 'Other', '#868e96', 110, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('equipment_booking_status', 'Equipment Booking Status', 'reserved', 'Reserved', '#ffc107', 10, 1, NOW()),
('equipment_booking_status', 'Equipment Booking Status', 'in_use', 'In Use', '#007bff', 20, 1, NOW()),
('equipment_booking_status', 'Equipment Booking Status', 'returned', 'Returned', '#28a745', 30, 1, NOW()),
('equipment_booking_status', 'Equipment Booking Status', 'cancelled', 'Cancelled', '#dc3545', 40, 1, NOW()),
('equipment_booking_status', 'Equipment Booking Status', 'no_show', 'No Show', '#6c757d', 50, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('material_request_type', 'Material Request Type', 'reading_room', 'Reading Room', '#007bff', 10, 1, NOW()),
('material_request_type', 'Material Request Type', 'reproduction', 'Reproduction', '#28a745', 20, 1, NOW()),
('material_request_type', 'Material Request Type', 'loan', 'Loan', '#6f42c1', 30, 1, NOW()),
('material_request_type', 'Material Request Type', 'remote_access', 'Remote Access', '#fd7e14', 40, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('material_request_status', 'Material Request Status', 'requested', 'Requested', '#ffc107', 10, 1, NOW()),
('material_request_status', 'Material Request Status', 'retrieved', 'Retrieved', '#17a2b8', 20, 1, NOW()),
('material_request_status', 'Material Request Status', 'delivered', 'Delivered', '#007bff', 30, 1, NOW()),
('material_request_status', 'Material Request Status', 'in_use', 'In Use', '#6f42c1', 40, 1, NOW()),
('material_request_status', 'Material Request Status', 'returned', 'Returned', '#28a745', 50, 1, NOW()),
('material_request_status', 'Material Request Status', 'unavailable', 'Unavailable', '#dc3545', 60, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('print_template_type', 'Print Template Type', 'call_slip', 'Call Slip', '#007bff', 10, 1, NOW()),
('print_template_type', 'Print Template Type', 'paging_slip', 'Paging Slip', '#28a745', 20, 1, NOW()),
('print_template_type', 'Print Template Type', 'receipt', 'Receipt', '#6f42c1', 30, 1, NOW()),
('print_template_type', 'Print Template Type', 'badge', 'Badge', '#fd7e14', 40, 1, NOW()),
('print_template_type', 'Print Template Type', 'label', 'Label', '#17a2b8', 50, 1, NOW()),
('print_template_type', 'Print Template Type', 'report', 'Report', '#ffc107', 60, 1, NOW()),
('print_template_type', 'Print Template Type', 'letter', 'Letter', '#e83e8c', 70, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('page_orientation', 'Page Orientation', 'portrait', 'Portrait', '#007bff', 10, 1, NOW()),
('page_orientation', 'Page Orientation', 'landscape', 'Landscape', '#28a745', 20, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('page_size', 'Page Size', 'a4', 'A4', '#007bff', 10, 1, NOW()),
('page_size', 'Page Size', 'a5', 'A5', '#28a745', 20, 1, NOW()),
('page_size', 'Page Size', 'letter', 'Letter', '#6f42c1', 30, 1, NOW()),
('page_size', 'Page Size', 'label_4x6', 'Label 4x6', '#fd7e14', 40, 1, NOW()),
('page_size', 'Page Size', 'label_2x4', 'Label 2x4', '#17a2b8', 50, 1, NOW()),
('page_size', 'Page Size', 'badge', 'Badge', '#ffc107', 60, 1, NOW()),
('page_size', 'Page Size', 'custom', 'Custom', '#6c757d', 70, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('project_type', 'Project Type', 'thesis', 'Thesis', '#007bff', 10, 1, NOW()),
('project_type', 'Project Type', 'dissertation', 'Dissertation', '#28a745', 20, 1, NOW()),
('project_type', 'Project Type', 'publication', 'Publication', '#6f42c1', 30, 1, NOW()),
('project_type', 'Project Type', 'exhibition', 'Exhibition', '#fd7e14', 40, 1, NOW()),
('project_type', 'Project Type', 'documentary', 'Documentary', '#17a2b8', 50, 1, NOW()),
('project_type', 'Project Type', 'genealogy', 'Genealogy', '#ffc107', 60, 1, NOW()),
('project_type', 'Project Type', 'institutional', 'Institutional', '#e83e8c', 70, 1, NOW()),
('project_type', 'Project Type', 'personal', 'Personal', '#20c997', 80, 1, NOW()),
('project_type', 'Project Type', 'other', 'Other', '#6c757d', 90, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('project_status', 'Project Status', 'planning', 'Planning', '#6c757d', 10, 1, NOW()),
('project_status', 'Project Status', 'active', 'Active', '#28a745', 20, 1, NOW()),
('project_status', 'Project Status', 'on_hold', 'On Hold', '#ffc107', 30, 1, NOW()),
('project_status', 'Project Status', 'completed', 'Completed', '#007bff', 40, 1, NOW()),
('project_status', 'Project Status', 'archived', 'Archived', '#343a40', 50, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('project_visibility', 'Project Visibility', 'private', 'Private', '#dc3545', 10, 1, NOW()),
('project_visibility', 'Project Visibility', 'collaborators', 'Collaborators', '#ffc107', 20, 1, NOW()),
('project_visibility', 'Project Visibility', 'public', 'Public', '#28a745', 30, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('collaborator_role', 'Collaborator Role', 'owner', 'Owner', '#dc3545', 10, 1, NOW()),
('collaborator_role', 'Collaborator Role', 'editor', 'Editor', '#fd7e14', 20, 1, NOW()),
('collaborator_role', 'Collaborator Role', 'contributor', 'Contributor', '#ffc107', 30, 1, NOW()),
('collaborator_role', 'Collaborator Role', 'viewer', 'Viewer', '#6c757d', 40, 1, NOW()),
('collaborator_role', 'Collaborator Role', 'admin', 'Admin', '#dc3545', 50, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('collaborator_status', 'Collaborator Status', 'pending', 'Pending', '#ffc107', 10, 1, NOW()),
('collaborator_status', 'Collaborator Status', 'accepted', 'Accepted', '#28a745', 20, 1, NOW()),
('collaborator_status', 'Collaborator Status', 'declined', 'Declined', '#dc3545', 30, 1, NOW()),
('collaborator_status', 'Collaborator Status', 'removed', 'Removed', '#6c757d', 40, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('milestone_status', 'Milestone Status', 'pending', 'Pending', '#ffc107', 10, 1, NOW()),
('milestone_status', 'Milestone Status', 'in_progress', 'In Progress', '#007bff', 20, 1, NOW()),
('milestone_status', 'Milestone Status', 'completed', 'Completed', '#28a745', 30, 1, NOW()),
('milestone_status', 'Milestone Status', 'cancelled', 'Cancelled', '#dc3545', 40, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('project_resource_type', 'Project Resource Type', 'collection', 'Collection', '#007bff', 10, 1, NOW()),
('project_resource_type', 'Project Resource Type', 'saved_search', 'Saved Search', '#28a745', 20, 1, NOW()),
('project_resource_type', 'Project Resource Type', 'annotation', 'Annotation', '#6f42c1', 30, 1, NOW()),
('project_resource_type', 'Project Resource Type', 'bibliography', 'Bibliography', '#fd7e14', 40, 1, NOW()),
('project_resource_type', 'Project Resource Type', 'object', 'Object', '#17a2b8', 50, 1, NOW()),
('project_resource_type', 'Project Resource Type', 'external_link', 'External Link', '#ffc107', 60, 1, NOW()),
('project_resource_type', 'Project Resource Type', 'document', 'Document', '#e83e8c', 70, 1, NOW()),
('project_resource_type', 'Project Resource Type', 'note', 'Note', '#20c997', 80, 1, NOW()),
('project_resource_type', 'Project Resource Type', 'link', 'Link', '#343a40', 90, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('seat_type', 'Seat Type', 'standard', 'Standard', '#007bff', 10, 1, NOW()),
('seat_type', 'Seat Type', 'accessible', 'Accessible', '#28a745', 20, 1, NOW()),
('seat_type', 'Seat Type', 'computer', 'Computer', '#6f42c1', 30, 1, NOW()),
('seat_type', 'Seat Type', 'microfilm', 'Microfilm', '#fd7e14', 40, 1, NOW()),
('seat_type', 'Seat Type', 'oversize', 'Oversize', '#17a2b8', 50, 1, NOW()),
('seat_type', 'Seat Type', 'quiet', 'Quiet', '#ffc107', 60, 1, NOW()),
('seat_type', 'Seat Type', 'group', 'Group', '#e83e8c', 70, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('reproduction_type', 'Reproduction Type', 'photocopy', 'Photocopy', '#007bff', 10, 1, NOW()),
('reproduction_type', 'Reproduction Type', 'scan', 'Scan', '#28a745', 20, 1, NOW()),
('reproduction_type', 'Reproduction Type', 'photograph', 'Photograph', '#6f42c1', 30, 1, NOW()),
('reproduction_type', 'Reproduction Type', 'digital_copy', 'Digital Copy', '#fd7e14', 40, 1, NOW()),
('reproduction_type', 'Reproduction Type', 'transcription', 'Transcription', '#17a2b8', 50, 1, NOW()),
('reproduction_type', 'Reproduction Type', 'certification', 'Certification', '#ffc107', 60, 1, NOW()),
('reproduction_type', 'Reproduction Type', 'other', 'Other', '#6c757d', 70, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('color_mode', 'Color Mode', 'color', 'Color', '#28a745', 10, 1, NOW()),
('color_mode', 'Color Mode', 'grayscale', 'Grayscale', '#6c757d', 20, 1, NOW()),
('color_mode', 'Color Mode', 'bw', 'Black & White', '#343a40', 30, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('reproduction_item_status', 'Reproduction Item Status', 'pending', 'Pending', '#ffc107', 10, 1, NOW()),
('reproduction_item_status', 'Reproduction Item Status', 'in_progress', 'In Progress', '#007bff', 20, 1, NOW()),
('reproduction_item_status', 'Reproduction Item Status', 'completed', 'Completed', '#28a745', 30, 1, NOW()),
('reproduction_item_status', 'Reproduction Item Status', 'cancelled', 'Cancelled', '#dc3545', 40, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('delivery_method', 'Delivery Method', 'email', 'Email', '#007bff', 10, 1, NOW()),
('delivery_method', 'Delivery Method', 'download', 'Download', '#28a745', 20, 1, NOW()),
('delivery_method', 'Delivery Method', 'post', 'Post', '#6f42c1', 30, 1, NOW()),
('delivery_method', 'Delivery Method', 'collect', 'Collect', '#fd7e14', 40, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('intended_use', 'Intended Use', 'personal', 'Personal', '#007bff', 10, 1, NOW()),
('intended_use', 'Intended Use', 'academic', 'Academic', '#28a745', 20, 1, NOW()),
('intended_use', 'Intended Use', 'publication', 'Publication', '#6f42c1', 30, 1, NOW()),
('intended_use', 'Intended Use', 'exhibition', 'Exhibition', '#fd7e14', 40, 1, NOW()),
('intended_use', 'Intended Use', 'commercial', 'Commercial', '#dc3545', 50, 1, NOW()),
('intended_use', 'Intended Use', 'other', 'Other', '#6c757d', 60, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('reproduction_request_status', 'Reproduction Request Status', 'draft', 'Draft', '#6c757d', 10, 1, NOW()),
('reproduction_request_status', 'Reproduction Request Status', 'submitted', 'Submitted', '#ffc107', 20, 1, NOW()),
('reproduction_request_status', 'Reproduction Request Status', 'processing', 'Processing', '#17a2b8', 30, 1, NOW()),
('reproduction_request_status', 'Reproduction Request Status', 'awaiting_payment', 'Awaiting Payment', '#fd7e14', 40, 1, NOW()),
('reproduction_request_status', 'Reproduction Request Status', 'in_production', 'In Production', '#007bff', 50, 1, NOW()),
('reproduction_request_status', 'Reproduction Request Status', 'completed', 'Completed', '#28a745', 60, 1, NOW()),
('reproduction_request_status', 'Reproduction Request Status', 'cancelled', 'Cancelled', '#dc3545', 70, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('queue_type', 'Queue Type', 'retrieval', 'Retrieval', '#007bff', 10, 1, NOW()),
('queue_type', 'Queue Type', 'paging', 'Paging', '#28a745', 20, 1, NOW()),
('queue_type', 'Queue Type', 'return', 'Return', '#6f42c1', 30, 1, NOW()),
('queue_type', 'Queue Type', 'curatorial', 'Curatorial', '#fd7e14', 40, 1, NOW()),
('queue_type', 'Queue Type', 'reproduction', 'Reproduction', '#17a2b8', 50, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('affiliation_type', 'Affiliation Type', 'academic', 'Academic', '#007bff', 10, 1, NOW()),
('affiliation_type', 'Affiliation Type', 'government', 'Government', '#28a745', 20, 1, NOW()),
('affiliation_type', 'Affiliation Type', 'private', 'Private', '#6f42c1', 30, 1, NOW()),
('affiliation_type', 'Affiliation Type', 'independent', 'Independent', '#fd7e14', 40, 1, NOW()),
('affiliation_type', 'Affiliation Type', 'student', 'Student', '#17a2b8', 50, 1, NOW()),
('affiliation_type', 'Affiliation Type', 'other', 'Other', '#6c757d', 60, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('id_type', 'ID Type', 'passport', 'Passport', '#007bff', 10, 1, NOW()),
('id_type', 'ID Type', 'national_id', 'National ID', '#28a745', 20, 1, NOW()),
('id_type', 'ID Type', 'drivers_license', 'Driver\'s License', '#6f42c1', 30, 1, NOW()),
('id_type', 'ID Type', 'student_card', 'Student Card', '#fd7e14', 40, 1, NOW()),
('id_type', 'ID Type', 'other', 'Other', '#6c757d', 50, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('researcher_status', 'Researcher Status', 'pending', 'Pending', '#ffc107', 10, 1, NOW()),
('researcher_status', 'Researcher Status', 'approved', 'Approved', '#28a745', 20, 1, NOW()),
('researcher_status', 'Researcher Status', 'suspended', 'Suspended', '#dc3545', 30, 1, NOW()),
('researcher_status', 'Researcher Status', 'expired', 'Expired', '#6c757d', 40, 1, NOW()),
('researcher_status', 'Researcher Status', 'rejected', 'Rejected', '#343a40', 50, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('alert_frequency', 'Alert Frequency', 'daily', 'Daily', '#dc3545', 10, 1, NOW()),
('alert_frequency', 'Alert Frequency', 'weekly', 'Weekly', '#ffc107', 20, 1, NOW()),
('alert_frequency', 'Alert Frequency', 'monthly', 'Monthly', '#28a745', 30, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('seat_assignment_status', 'Seat Assignment Status', 'assigned', 'Assigned', '#007bff', 10, 1, NOW()),
('seat_assignment_status', 'Seat Assignment Status', 'occupied', 'Occupied', '#28a745', 20, 1, NOW()),
('seat_assignment_status', 'Seat Assignment Status', 'released', 'Released', '#6c757d', 30, 1, NOW()),
('seat_assignment_status', 'Seat Assignment Status', 'no_show', 'No Show', '#dc3545', 40, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('verification_type', 'Verification Type', 'id_document', 'ID Document', '#007bff', 10, 1, NOW()),
('verification_type', 'Verification Type', 'institutional_letter', 'Institutional Letter', '#28a745', 20, 1, NOW()),
('verification_type', 'Verification Type', 'institutional_email', 'Institutional Email', '#6f42c1', 30, 1, NOW()),
('verification_type', 'Verification Type', 'orcid', 'ORCID', '#fd7e14', 40, 1, NOW()),
('verification_type', 'Verification Type', 'staff_approval', 'Staff Approval', '#17a2b8', 50, 1, NOW()),
('verification_type', 'Verification Type', 'professional_membership', 'Professional Membership', '#ffc107', 60, 1, NOW()),
('verification_type', 'Verification Type', 'other', 'Other', '#6c757d', 70, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('verification_status', 'Verification Status', 'pending', 'Pending', '#ffc107', 10, 1, NOW()),
('verification_status', 'Verification Status', 'verified', 'Verified', '#28a745', 20, 1, NOW()),
('verification_status', 'Verification Status', 'rejected', 'Rejected', '#dc3545', 30, 1, NOW()),
('verification_status', 'Verification Status', 'expired', 'Expired', '#6c757d', 40, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('workspace_visibility', 'Workspace Visibility', 'private', 'Private', '#dc3545', 10, 1, NOW()),
('workspace_visibility', 'Workspace Visibility', 'members', 'Members', '#ffc107', 20, 1, NOW()),
('workspace_visibility', 'Workspace Visibility', 'public', 'Public', '#28a745', 30, 1, NOW());

-- ============================================================================
-- Final statistics
-- ============================================================================

SELECT 'Phase 2E (Research Types) Migration Complete' as status;

-- ---------------------------------------------------------------------------
-- Merged in from database/enum_to_dropdown_migration_phase2.sql on 2026-08-17.
--
-- It sat beside install.sql and was never run by install-plugin-schema.php,
-- so a clean install silently lacked whatever it defines. Our own instances
-- had it because someone applied the file by hand. A plugin's schema is
-- install.sql; there is no second file.
-- ---------------------------------------------------------------------------

-- ============================================================================
-- ENUM to ahg_dropdown Migration Script - PHASE 2
-- Generated: 2026-02-04
--
-- This script migrates remaining ENUM values not covered in Phase 1
-- to the ahg_dropdown system for centralized vocabulary management.
--
-- Run this AFTER Phase 1 migration completes.
-- ============================================================================

-- ============================================================================
-- ACCESS REQUEST TYPES
-- ============================================================================

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('access_request_type', 'Access Request Type', 'clearance', 'Security Clearance', '#dc3545', 10, 1, NOW()),
('access_request_type', 'Access Request Type', 'object', 'Object Access', '#007bff', 20, 1, NOW()),
('access_request_type', 'Access Request Type', 'repository', 'Repository Access', '#28a745', 30, 1, NOW()),
('access_request_type', 'Access Request Type', 'authority', 'Authority Record Access', '#6f42c1', 40, 1, NOW()),
('access_request_type', 'Access Request Type', 'researcher', 'Researcher Registration', '#fd7e14', 50, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('access_request_scope', 'Access Request Scope', 'single', 'Single Item', '#007bff', 10, 1, NOW()),
('access_request_scope', 'Access Request Scope', 'with_children', 'With Children', '#28a745', 20, 1, NOW()),
('access_request_scope', 'Access Request Scope', 'collection', 'Entire Collection', '#6f42c1', 30, 1, NOW()),
('access_request_scope', 'Access Request Scope', 'repository_all', 'All Repository Items', '#fd7e14', 40, 1, NOW()),
('access_request_scope', 'Access Request Scope', 'renewal', 'Renewal', '#17a2b8', 50, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('access_request_status', 'Access Request Status', 'pending', 'Pending', '#ffc107', 10, 1, NOW()),
('access_request_status', 'Access Request Status', 'approved', 'Approved', '#28a745', 20, 1, NOW()),
('access_request_status', 'Access Request Status', 'denied', 'Denied', '#dc3545', 30, 1, NOW()),
('access_request_status', 'Access Request Status', 'cancelled', 'Cancelled', '#6c757d', 40, 1, NOW()),
('access_request_status', 'Access Request Status', 'expired', 'Expired', '#343a40', 50, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('access_request_action', 'Access Request Action', 'created', 'Created', '#28a745', 10, 1, NOW()),
('access_request_action', 'Access Request Action', 'updated', 'Updated', '#007bff', 20, 1, NOW()),
('access_request_action', 'Access Request Action', 'approved', 'Approved', '#28a745', 30, 1, NOW()),
('access_request_action', 'Access Request Action', 'denied', 'Denied', '#dc3545', 40, 1, NOW()),
('access_request_action', 'Access Request Action', 'cancelled', 'Cancelled', '#6c757d', 50, 1, NOW()),
('access_request_action', 'Access Request Action', 'expired', 'Expired', '#343a40', 60, 1, NOW()),
('access_request_action', 'Access Request Action', 'escalated', 'Escalated', '#e83e8c', 70, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('access_object_type', 'Access Object Type', 'information_object', 'Information Object', '#007bff', 10, 1, NOW()),
('access_object_type', 'Access Object Type', 'repository', 'Repository', '#28a745', 20, 1, NOW()),
('access_object_type', 'Access Object Type', 'actor', 'Actor', '#6f42c1', 30, 1, NOW());

-- ============================================================================
-- AGREEMENT RIGHTS VOCABULARY
-- ============================================================================

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('agreement_rights_category', 'Agreement Rights Category', 'usage', 'Usage Rights', '#007bff', 10, 1, NOW()),
('agreement_rights_category', 'Agreement Rights Category', 'restriction', 'Restriction', '#dc3545', 20, 1, NOW()),
('agreement_rights_category', 'Agreement Rights Category', 'condition', 'Condition', '#ffc107', 30, 1, NOW()),
('agreement_rights_category', 'Agreement Rights Category', 'license', 'License', '#28a745', 40, 1, NOW());

-- ============================================================================
-- BOT LIST CATEGORIES
-- ============================================================================

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('bot_category', 'Bot Category', 'search_engine', 'Search Engine', '#28a745', 10, 1, NOW()),
('bot_category', 'Bot Category', 'social', 'Social Media', '#007bff', 20, 1, NOW()),
('bot_category', 'Bot Category', 'monitoring', 'Monitoring', '#17a2b8', 30, 1, NOW()),
('bot_category', 'Bot Category', 'crawler', 'Crawler', '#6f42c1', 40, 1, NOW()),
('bot_category', 'Bot Category', 'spam', 'Spam', '#dc3545', 50, 1, NOW()),
('bot_category', 'Bot Category', 'other', 'Other', '#6c757d', 60, 1, NOW());

-- ============================================================================
-- EXTENSION SYSTEM TYPES
-- ============================================================================

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('extension_protection_level', 'Extension Protection Level', 'core', 'Core', '#dc3545', 10, 1, NOW()),
('extension_protection_level', 'Extension Protection Level', 'system', 'System', '#fd7e14', 20, 1, NOW()),
('extension_protection_level', 'Extension Protection Level', 'theme', 'Theme', '#6f42c1', 30, 1, NOW()),
('extension_protection_level', 'Extension Protection Level', 'extension', 'Extension', '#28a745', 40, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('extension_status', 'Extension Status', 'installed', 'Installed', '#17a2b8', 10, 1, NOW()),
('extension_status', 'Extension Status', 'enabled', 'Enabled', '#28a745', 20, 1, NOW()),
('extension_status', 'Extension Status', 'disabled', 'Disabled', '#6c757d', 30, 1, NOW()),
('extension_status', 'Extension Status', 'pending_removal', 'Pending Removal', '#dc3545', 40, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('extension_audit_action', 'Extension Audit Action', 'discovered', 'Discovered', '#17a2b8', 10, 1, NOW()),
('extension_audit_action', 'Extension Audit Action', 'installed', 'Installed', '#28a745', 20, 1, NOW()),
('extension_audit_action', 'Extension Audit Action', 'enabled', 'Enabled', '#007bff', 30, 1, NOW()),
('extension_audit_action', 'Extension Audit Action', 'disabled', 'Disabled', '#6c757d', 40, 1, NOW()),
('extension_audit_action', 'Extension Audit Action', 'uninstalled', 'Uninstalled', '#dc3545', 50, 1, NOW()),
('extension_audit_action', 'Extension Audit Action', 'upgraded', 'Upgraded', '#28a745', 60, 1, NOW()),
('extension_audit_action', 'Extension Audit Action', 'downgraded', 'Downgraded', '#fd7e14', 70, 1, NOW()),
('extension_audit_action', 'Extension Audit Action', 'backup_created', 'Backup Created', '#6f42c1', 80, 1, NOW()),
('extension_audit_action', 'Extension Audit Action', 'backup_restored', 'Backup Restored', '#20c997', 90, 1, NOW()),
('extension_audit_action', 'Extension Audit Action', 'data_deleted', 'Data Deleted', '#343a40', 100, 1, NOW()),
('extension_audit_action', 'Extension Audit Action', 'config_changed', 'Config Changed', '#ffc107', 110, 1, NOW()),
('extension_audit_action', 'Extension Audit Action', 'error', 'Error', '#dc3545', 120, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('menu_location', 'Menu Location', 'main', 'Main Menu', '#007bff', 10, 1, NOW()),
('menu_location', 'Menu Location', 'admin', 'Admin Menu', '#dc3545', 20, 1, NOW()),
('menu_location', 'Menu Location', 'user', 'User Menu', '#28a745', 30, 1, NOW()),
('menu_location', 'Menu Location', 'footer', 'Footer Menu', '#6c757d', 40, 1, NOW()),
('menu_location', 'Menu Location', 'mobile', 'Mobile Menu', '#17a2b8', 50, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('widget_type', 'Widget Type', 'stat_card', 'Stat Card', '#007bff', 10, 1, NOW()),
('widget_type', 'Widget Type', 'chart', 'Chart', '#28a745', 20, 1, NOW()),
('widget_type', 'Widget Type', 'list', 'List', '#6f42c1', 30, 1, NOW()),
('widget_type', 'Widget Type', 'table', 'Table', '#fd7e14', 40, 1, NOW()),
('widget_type', 'Widget Type', 'html', 'HTML', '#17a2b8', 50, 1, NOW()),
('widget_type', 'Widget Type', 'custom', 'Custom', '#6c757d', 60, 1, NOW()),
('widget_type', 'Widget Type', 'count', 'Count', '#ffc107', 70, 1, NOW()),
('widget_type', 'Widget Type', 'stat', 'Stat', '#e83e8c', 80, 1, NOW());

-- ============================================================================
-- ISBN/LIBRARY TYPES
-- ============================================================================

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('isbn_response_format', 'ISBN Response Format', 'json', 'JSON', '#007bff', 10, 1, NOW()),
('isbn_response_format', 'ISBN Response Format', 'xml', 'XML', '#28a745', 20, 1, NOW()),
('isbn_response_format', 'ISBN Response Format', 'marcxml', 'MARCXML', '#6f42c1', 30, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('landing_page_status', 'Landing Page Status', 'draft', 'Draft', '#6c757d', 10, 1, NOW()),
('landing_page_status', 'Landing Page Status', 'published', 'Published', '#28a745', 20, 1, NOW()),
('landing_page_status', 'Landing Page Status', 'archived', 'Archived', '#343a40', 30, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('library_heading_type', 'Library Heading Type', 'topical', 'Topical', '#007bff', 10, 1, NOW()),
('library_heading_type', 'Library Heading Type', 'personal', 'Personal', '#28a745', 20, 1, NOW()),
('library_heading_type', 'Library Heading Type', 'corporate', 'Corporate', '#6f42c1', 30, 1, NOW()),
('library_heading_type', 'Library Heading Type', 'geographic', 'Geographic', '#fd7e14', 40, 1, NOW()),
('library_heading_type', 'Library Heading Type', 'genre', 'Genre/Form', '#17a2b8', 50, 1, NOW()),
('library_heading_type', 'Library Heading Type', 'meeting', 'Meeting', '#e83e8c', 60, 1, NOW());

-- ============================================================================
-- CDPA/PRIVACY COMPLIANCE TYPES (Zimbabwe)
-- ============================================================================

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('breach_type', 'Breach Type', 'unauthorized_access', 'Unauthorized Access', '#dc3545', 10, 1, NOW()),
('breach_type', 'Breach Type', 'data_loss', 'Data Loss', '#fd7e14', 20, 1, NOW()),
('breach_type', 'Breach Type', 'data_theft', 'Data Theft', '#dc3545', 30, 1, NOW()),
('breach_type', 'Breach Type', 'accidental_disclosure', 'Accidental Disclosure', '#ffc107', 40, 1, NOW()),
('breach_type', 'Breach Type', 'system_breach', 'System Breach', '#dc3545', 50, 1, NOW()),
('breach_type', 'Breach Type', 'confidentiality', 'Confidentiality Breach', '#e83e8c', 60, 1, NOW()),
('breach_type', 'Breach Type', 'integrity', 'Integrity Breach', '#6f42c1', 70, 1, NOW()),
('breach_type', 'Breach Type', 'availability', 'Availability Breach', '#17a2b8', 80, 1, NOW()),
('breach_type', 'Breach Type', 'other', 'Other', '#6c757d', 90, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('breach_status', 'Breach Status', 'investigating', 'Investigating', '#ffc107', 10, 1, NOW()),
('breach_status', 'Breach Status', 'contained', 'Contained', '#17a2b8', 20, 1, NOW()),
('breach_status', 'Breach Status', 'resolved', 'Resolved', '#28a745', 30, 1, NOW()),
('breach_status', 'Breach Status', 'ongoing', 'Ongoing', '#dc3545', 40, 1, NOW()),
('breach_status', 'Breach Status', 'detected', 'Detected', '#fd7e14', 50, 1, NOW()),
('breach_status', 'Breach Status', 'closed', 'Closed', '#6c757d', 60, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('consent_method', 'Consent Method', 'written', 'Written', '#28a745', 10, 1, NOW()),
('consent_method', 'Consent Method', 'electronic', 'Electronic', '#007bff', 20, 1, NOW()),
('consent_method', 'Consent Method', 'verbal', 'Verbal', '#ffc107', 30, 1, NOW()),
('consent_method', 'Consent Method', 'opt_in', 'Opt-in', '#17a2b8', 40, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('controller_tier', 'Controller Tier', 'tier1', 'Tier 1', '#dc3545', 10, 1, NOW()),
('controller_tier', 'Controller Tier', 'tier2', 'Tier 2', '#fd7e14', 20, 1, NOW()),
('controller_tier', 'Controller Tier', 'tier3', 'Tier 3', '#ffc107', 30, 1, NOW()),
('controller_tier', 'Controller Tier', 'tier4', 'Tier 4', '#28a745', 40, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('dsar_request_type', 'DSAR Request Type', 'access', 'Access Request', '#007bff', 10, 1, NOW()),
('dsar_request_type', 'DSAR Request Type', 'rectification', 'Rectification', '#28a745', 20, 1, NOW()),
('dsar_request_type', 'DSAR Request Type', 'erasure', 'Erasure', '#dc3545', 30, 1, NOW()),
('dsar_request_type', 'DSAR Request Type', 'object', 'Object to Processing', '#fd7e14', 40, 1, NOW()),
('dsar_request_type', 'DSAR Request Type', 'portability', 'Data Portability', '#17a2b8', 50, 1, NOW()),
('dsar_request_type', 'DSAR Request Type', 'restriction', 'Restriction', '#6f42c1', 60, 1, NOW()),
('dsar_request_type', 'DSAR Request Type', 'withdraw_consent', 'Withdraw Consent', '#e83e8c', 70, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('dsar_status', 'DSAR Status', 'pending', 'Pending', '#ffc107', 10, 1, NOW()),
('dsar_status', 'DSAR Status', 'in_progress', 'In Progress', '#007bff', 20, 1, NOW()),
('dsar_status', 'DSAR Status', 'completed', 'Completed', '#28a745', 30, 1, NOW()),
('dsar_status', 'DSAR Status', 'rejected', 'Rejected', '#dc3545', 40, 1, NOW()),
('dsar_status', 'DSAR Status', 'extended', 'Extended', '#17a2b8', 50, 1, NOW()),
('dsar_status', 'DSAR Status', 'received', 'Received', '#6c757d', 60, 1, NOW()),
('dsar_status', 'DSAR Status', 'verified', 'Verified', '#28a745', 70, 1, NOW()),
('dsar_status', 'DSAR Status', 'pending_info', 'Pending Info', '#fd7e14', 80, 1, NOW()),
('dsar_status', 'DSAR Status', 'withdrawn', 'Withdrawn', '#343a40', 90, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('dpia_status', 'DPIA Status', 'draft', 'Draft', '#6c757d', 10, 1, NOW()),
('dpia_status', 'DPIA Status', 'in_progress', 'In Progress', '#007bff', 20, 1, NOW()),
('dpia_status', 'DPIA Status', 'completed', 'Completed', '#28a745', 30, 1, NOW()),
('dpia_status', 'DPIA Status', 'approved', 'Approved', '#28a745', 40, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('legal_basis', 'Legal Basis', 'consent', 'Consent', '#28a745', 10, 1, NOW()),
('legal_basis', 'Legal Basis', 'contract', 'Contract', '#007bff', 20, 1, NOW()),
('legal_basis', 'Legal Basis', 'legal_obligation', 'Legal Obligation', '#dc3545', 30, 1, NOW()),
('legal_basis', 'Legal Basis', 'vital_interest', 'Vital Interest', '#fd7e14', 40, 1, NOW()),
('legal_basis', 'Legal Basis', 'public_interest', 'Public Interest', '#6f42c1', 50, 1, NOW()),
('legal_basis', 'Legal Basis', 'legitimate_interest', 'Legitimate Interest', '#17a2b8', 60, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('storage_location', 'Storage Location', 'zimbabwe', 'Zimbabwe', '#28a745', 10, 1, NOW()),
('storage_location', 'Storage Location', 'international', 'International', '#007bff', 20, 1, NOW()),
('storage_location', 'Storage Location', 'both', 'Both', '#ffc107', 30, 1, NOW());

-- ============================================================================
-- CONDITION REPORT TYPES
-- ============================================================================

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('damage_severity', 'Damage Severity', 'minor', 'Minor', '#28a745', 10, 1, NOW()),
('damage_severity', 'Damage Severity', 'moderate', 'Moderate', '#ffc107', 20, 1, NOW()),
('damage_severity', 'Damage Severity', 'severe', 'Severe', '#dc3545', 30, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('condition_image_type', 'Condition Image Type', 'general', 'General', '#007bff', 10, 1, NOW()),
('condition_image_type', 'Condition Image Type', 'detail', 'Detail', '#28a745', 20, 1, NOW()),
('condition_image_type', 'Condition Image Type', 'damage', 'Damage', '#dc3545', 30, 1, NOW()),
('condition_image_type', 'Condition Image Type', 'before', 'Before', '#6c757d', 40, 1, NOW()),
('condition_image_type', 'Condition Image Type', 'after', 'After', '#28a745', 50, 1, NOW()),
('condition_image_type', 'Condition Image Type', 'raking', 'Raking Light', '#ffc107', 60, 1, NOW()),
('condition_image_type', 'Condition Image Type', 'uv', 'UV', '#6f42c1', 70, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('condition_report_context', 'Condition Report Context', 'acquisition', 'Acquisition', '#28a745', 10, 1, NOW()),
('condition_report_context', 'Condition Report Context', 'loan_out', 'Loan Out', '#007bff', 20, 1, NOW()),
('condition_report_context', 'Condition Report Context', 'loan_in', 'Loan In', '#17a2b8', 30, 1, NOW()),
('condition_report_context', 'Condition Report Context', 'loan_return', 'Loan Return', '#20c997', 40, 1, NOW()),
('condition_report_context', 'Condition Report Context', 'exhibition', 'Exhibition', '#6f42c1', 50, 1, NOW()),
('condition_report_context', 'Condition Report Context', 'storage', 'Storage', '#6c757d', 60, 1, NOW()),
('condition_report_context', 'Condition Report Context', 'conservation', 'Conservation', '#fd7e14', 70, 1, NOW()),
('condition_report_context', 'Condition Report Context', 'routine', 'Routine', '#ffc107', 80, 1, NOW()),
('condition_report_context', 'Condition Report Context', 'incident', 'Incident', '#dc3545', 90, 1, NOW()),
('condition_report_context', 'Condition Report Context', 'insurance', 'Insurance', '#e83e8c', 100, 1, NOW()),
('condition_report_context', 'Condition Report Context', 'deaccession', 'Deaccession', '#343a40', 110, 1, NOW()),
('condition_report_context', 'Condition Report Context', 'pre_loan', 'Pre-Loan', '#007bff', 120, 1, NOW()),
('condition_report_context', 'Condition Report Context', 'post_loan', 'Post-Loan', '#28a745', 130, 1, NOW()),
('condition_report_context', 'Condition Report Context', 'in_transit', 'In Transit', '#17a2b8', 140, 1, NOW()),
('condition_report_context', 'Condition Report Context', 'periodic', 'Periodic', '#6c757d', 150, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('condition_vocabulary_type', 'Condition Vocabulary Type', 'damage_type', 'Damage Type', '#dc3545', 10, 1, NOW()),
('condition_vocabulary_type', 'Condition Vocabulary Type', 'severity', 'Severity', '#fd7e14', 20, 1, NOW()),
('condition_vocabulary_type', 'Condition Vocabulary Type', 'condition', 'Condition', '#28a745', 30, 1, NOW()),
('condition_vocabulary_type', 'Condition Vocabulary Type', 'priority', 'Priority', '#ffc107', 40, 1, NOW()),
('condition_vocabulary_type', 'Condition Vocabulary Type', 'material', 'Material', '#007bff', 50, 1, NOW()),
('condition_vocabulary_type', 'Condition Vocabulary Type', 'location_zone', 'Location Zone', '#6f42c1', 60, 1, NOW());

-- ============================================================================
-- CONTACT INFORMATION TYPES
-- ============================================================================

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('preferred_contact_method', 'Preferred Contact Method', 'email', 'Email', '#007bff', 10, 1, NOW()),
('preferred_contact_method', 'Preferred Contact Method', 'phone', 'Phone', '#28a745', 20, 1, NOW()),
('preferred_contact_method', 'Preferred Contact Method', 'cell', 'Cell/Mobile', '#17a2b8', 30, 1, NOW()),
('preferred_contact_method', 'Preferred Contact Method', 'fax', 'Fax', '#6c757d', 40, 1, NOW()),
('preferred_contact_method', 'Preferred Contact Method', 'mail', 'Post/Mail', '#fd7e14', 50, 1, NOW());

-- ============================================================================
-- DAM (DIGITAL ASSET MANAGEMENT) TYPES
-- ============================================================================

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('dam_link_type', 'DAM Link Type', 'ESAT', 'ESAT', '#007bff', 10, 1, NOW()),
('dam_link_type', 'DAM Link Type', 'IMDb', 'IMDb', '#ffc107', 20, 1, NOW()),
('dam_link_type', 'DAM Link Type', 'SAFILM', 'SA Film', '#28a745', 30, 1, NOW()),
('dam_link_type', 'DAM Link Type', 'NFVSA', 'NFVSA', '#6f42c1', 40, 1, NOW()),
('dam_link_type', 'DAM Link Type', 'Wikipedia', 'Wikipedia', '#6c757d', 50, 1, NOW()),
('dam_link_type', 'DAM Link Type', 'Wikidata', 'Wikidata', '#dc3545', 60, 1, NOW()),
('dam_link_type', 'DAM Link Type', 'VIAF', 'VIAF', '#17a2b8', 70, 1, NOW()),
('dam_link_type', 'DAM Link Type', 'YouTube', 'YouTube', '#dc3545', 80, 1, NOW()),
('dam_link_type', 'DAM Link Type', 'Vimeo', 'Vimeo', '#17a2b8', 90, 1, NOW()),
('dam_link_type', 'DAM Link Type', 'Archive_org', 'Archive.org', '#28a745', 100, 1, NOW()),
('dam_link_type', 'DAM Link Type', 'BFI', 'BFI', '#007bff', 110, 1, NOW()),
('dam_link_type', 'DAM Link Type', 'AFI', 'AFI', '#fd7e14', 120, 1, NOW()),
('dam_link_type', 'DAM Link Type', 'Letterboxd', 'Letterboxd', '#fd7e14', 130, 1, NOW()),
('dam_link_type', 'DAM Link Type', 'MUBI', 'MUBI', '#e83e8c', 140, 1, NOW()),
('dam_link_type', 'DAM Link Type', 'Filmography', 'Filmography', '#6f42c1', 150, 1, NOW()),
('dam_link_type', 'DAM Link Type', 'Review', 'Review', '#ffc107', 160, 1, NOW()),
('dam_link_type', 'DAM Link Type', 'Academic', 'Academic', '#28a745', 170, 1, NOW()),
('dam_link_type', 'DAM Link Type', 'Press', 'Press', '#17a2b8', 180, 1, NOW()),
('dam_link_type', 'DAM Link Type', 'Other', 'Other', '#6c757d', 190, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('dam_access_status', 'DAM Access Status', 'available', 'Available', '#28a745', 10, 1, NOW()),
('dam_access_status', 'DAM Access Status', 'restricted', 'Restricted', '#dc3545', 20, 1, NOW()),
('dam_access_status', 'DAM Access Status', 'preservation_only', 'Preservation Only', '#6f42c1', 30, 1, NOW()),
('dam_access_status', 'DAM Access Status', 'digitized_available', 'Digitized Available', '#007bff', 40, 1, NOW()),
('dam_access_status', 'DAM Access Status', 'on_request', 'On Request', '#ffc107', 50, 1, NOW()),
('dam_access_status', 'DAM Access Status', 'staff_only', 'Staff Only', '#fd7e14', 60, 1, NOW()),
('dam_access_status', 'DAM Access Status', 'unknown', 'Unknown', '#6c757d', 70, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('dam_format_type', 'DAM Format Type', '35mm', '35mm Film', '#007bff', 10, 1, NOW()),
('dam_format_type', 'DAM Format Type', '16mm', '16mm Film', '#28a745', 20, 1, NOW()),
('dam_format_type', 'DAM Format Type', '8mm', '8mm Film', '#17a2b8', 30, 1, NOW()),
('dam_format_type', 'DAM Format Type', 'Super8', 'Super 8', '#20c997', 40, 1, NOW()),
('dam_format_type', 'DAM Format Type', 'VHS', 'VHS', '#6c757d', 50, 1, NOW()),
('dam_format_type', 'DAM Format Type', 'Betacam', 'Betacam', '#6f42c1', 60, 1, NOW()),
('dam_format_type', 'DAM Format Type', 'U-matic', 'U-matic', '#fd7e14', 70, 1, NOW()),
('dam_format_type', 'DAM Format Type', 'DV', 'DV', '#ffc107', 80, 1, NOW()),
('dam_format_type', 'DAM Format Type', 'DVD', 'DVD', '#dc3545', 90, 1, NOW()),
('dam_format_type', 'DAM Format Type', 'Blu-ray', 'Blu-ray', '#007bff', 100, 1, NOW()),
('dam_format_type', 'DAM Format Type', 'LaserDisc', 'LaserDisc', '#343a40', 110, 1, NOW()),
('dam_format_type', 'DAM Format Type', 'Digital_File', 'Digital File', '#28a745', 120, 1, NOW()),
('dam_format_type', 'DAM Format Type', 'DCP', 'DCP', '#e83e8c', 130, 1, NOW()),
('dam_format_type', 'DAM Format Type', 'ProRes', 'ProRes', '#6f42c1', 140, 1, NOW()),
('dam_format_type', 'DAM Format Type', 'Nitrate', 'Nitrate', '#dc3545', 150, 1, NOW()),
('dam_format_type', 'DAM Format Type', 'Safety', 'Safety Film', '#28a745', 160, 1, NOW()),
('dam_format_type', 'DAM Format Type', 'Polyester', 'Polyester', '#17a2b8', 170, 1, NOW()),
('dam_format_type', 'DAM Format Type', 'Audio_Reel', 'Audio Reel', '#fd7e14', 180, 1, NOW()),
('dam_format_type', 'DAM Format Type', 'Audio_Cassette', 'Audio Cassette', '#ffc107', 190, 1, NOW()),
('dam_format_type', 'DAM Format Type', 'Vinyl', 'Vinyl', '#343a40', 200, 1, NOW()),
('dam_format_type', 'DAM Format Type', 'CD', 'CD', '#6c757d', 210, 1, NOW()),
('dam_format_type', 'DAM Format Type', 'Other', 'Other', '#868e96', 220, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('color_type', 'Color Type', 'color', 'Color', '#28a745', 10, 1, NOW()),
('color_type', 'Color Type', 'black_and_white', 'Black & White', '#343a40', 20, 1, NOW()),
('color_type', 'Color Type', 'mixed', 'Mixed', '#6f42c1', 30, 1, NOW()),
('color_type', 'Color Type', 'colorized', 'Colorized', '#ffc107', 40, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('license_type', 'License Type', 'rights_managed', 'Rights Managed', '#dc3545', 10, 1, NOW()),
('license_type', 'License Type', 'royalty_free', 'Royalty Free', '#28a745', 20, 1, NOW()),
('license_type', 'License Type', 'creative_commons', 'Creative Commons', '#007bff', 30, 1, NOW()),
('license_type', 'License Type', 'public_domain', 'Public Domain', '#6c757d', 40, 1, NOW()),
('license_type', 'License Type', 'editorial', 'Editorial Use Only', '#ffc107', 50, 1, NOW()),
('license_type', 'License Type', 'other', 'Other', '#868e96', 60, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('model_release_status', 'Model Release Status', 'none', 'None', '#dc3545', 10, 1, NOW()),
('model_release_status', 'Model Release Status', 'not_applicable', 'Not Applicable', '#6c757d', 20, 1, NOW()),
('model_release_status', 'Model Release Status', 'unlimited', 'Unlimited', '#28a745', 30, 1, NOW()),
('model_release_status', 'Model Release Status', 'limited', 'Limited', '#ffc107', 40, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('property_release_status', 'Property Release Status', 'none', 'None', '#dc3545', 10, 1, NOW()),
('property_release_status', 'Property Release Status', 'not_applicable', 'Not Applicable', '#6c757d', 20, 1, NOW()),
('property_release_status', 'Property Release Status', 'unlimited', 'Unlimited', '#28a745', 30, 1, NOW()),
('property_release_status', 'Property Release Status', 'limited', 'Limited', '#ffc107', 40, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('version_link_type', 'Version Link Type', 'language', 'Language Version', '#007bff', 10, 1, NOW()),
('version_link_type', 'Version Link Type', 'format', 'Format Version', '#28a745', 20, 1, NOW()),
('version_link_type', 'Version Link Type', 'restoration', 'Restoration', '#6f42c1', 30, 1, NOW()),
('version_link_type', 'Version Link Type', 'directors_cut', 'Director\'s Cut', '#fd7e14', 40, 1, NOW()),
('version_link_type', 'Version Link Type', 'censored', 'Censored', '#dc3545', 50, 1, NOW()),
('version_link_type', 'Version Link Type', 'other', 'Other', '#6c757d', 60, 1, NOW());

-- ============================================================================
-- DIGITAL OBJECT TYPES
-- ============================================================================

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('identification_source', 'Identification Source', 'auto', 'Automatic', '#007bff', 10, 1, NOW()),
('identification_source', 'Identification Source', 'manual', 'Manual', '#28a745', 20, 1, NOW()),
('identification_source', 'Identification Source', 'verified', 'Verified', '#28a745', 30, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('file_type', 'File Type', 'image', 'Image', '#28a745', 10, 1, NOW()),
('file_type', 'File Type', 'pdf', 'PDF', '#dc3545', 20, 1, NOW()),
('file_type', 'File Type', 'office', 'Office Document', '#007bff', 30, 1, NOW()),
('file_type', 'File Type', 'video', 'Video', '#6f42c1', 40, 1, NOW()),
('file_type', 'File Type', 'audio', 'Audio', '#fd7e14', 50, 1, NOW()),
('file_type', 'File Type', 'other', 'Other', '#6c757d', 60, 1, NOW());

-- ============================================================================
-- DISPLAY PROFILE TYPES
-- ============================================================================

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('display_data_type', 'Display Data Type', 'text', 'Text', '#007bff', 10, 1, NOW()),
('display_data_type', 'Display Data Type', 'textarea', 'Textarea', '#28a745', 20, 1, NOW()),
('display_data_type', 'Display Data Type', 'date', 'Date', '#fd7e14', 30, 1, NOW()),
('display_data_type', 'Display Data Type', 'daterange', 'Date Range', '#ffc107', 40, 1, NOW()),
('display_data_type', 'Display Data Type', 'number', 'Number', '#17a2b8', 50, 1, NOW()),
('display_data_type', 'Display Data Type', 'select', 'Select', '#6f42c1', 60, 1, NOW()),
('display_data_type', 'Display Data Type', 'multiselect', 'Multi-select', '#e83e8c', 70, 1, NOW()),
('display_data_type', 'Display Data Type', 'relation', 'Relation', '#20c997', 80, 1, NOW()),
('display_data_type', 'Display Data Type', 'file', 'File', '#343a40', 90, 1, NOW()),
('display_data_type', 'Display Data Type', 'actor', 'Actor', '#6c757d', 100, 1, NOW()),
('display_data_type', 'Display Data Type', 'term', 'Term', '#868e96', 110, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('display_field_group', 'Display Field Group', 'identity', 'Identity', '#007bff', 10, 1, NOW()),
('display_field_group', 'Display Field Group', 'description', 'Description', '#28a745', 20, 1, NOW()),
('display_field_group', 'Display Field Group', 'context', 'Context', '#6f42c1', 30, 1, NOW()),
('display_field_group', 'Display Field Group', 'access', 'Access', '#fd7e14', 40, 1, NOW()),
('display_field_group', 'Display Field Group', 'technical', 'Technical', '#17a2b8', 50, 1, NOW()),
('display_field_group', 'Display Field Group', 'admin', 'Admin', '#dc3545', 60, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('card_size', 'Card Size', 'small', 'Small', '#28a745', 10, 1, NOW()),
('card_size', 'Card Size', 'medium', 'Medium', '#007bff', 20, 1, NOW()),
('card_size', 'Card Size', 'large', 'Large', '#6f42c1', 30, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('sort_direction', 'Sort Direction', 'asc', 'Ascending', '#28a745', 10, 1, NOW()),
('sort_direction', 'Sort Direction', 'desc', 'Descending', '#dc3545', 20, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('layout_mode', 'Layout Mode', 'detail', 'Detail', '#007bff', 10, 1, NOW()),
('layout_mode', 'Layout Mode', 'hierarchy', 'Hierarchy', '#28a745', 20, 1, NOW()),
('layout_mode', 'Layout Mode', 'grid', 'Grid', '#6f42c1', 30, 1, NOW()),
('layout_mode', 'Layout Mode', 'gallery', 'Gallery', '#fd7e14', 40, 1, NOW()),
('layout_mode', 'Layout Mode', 'list', 'List', '#17a2b8', 50, 1, NOW()),
('layout_mode', 'Layout Mode', 'card', 'Card', '#ffc107', 60, 1, NOW()),
('layout_mode', 'Layout Mode', 'masonry', 'Masonry', '#e83e8c', 70, 1, NOW()),
('layout_mode', 'Layout Mode', 'catalog', 'Catalog', '#20c997', 80, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('thumbnail_position', 'Thumbnail Position', 'left', 'Left', '#007bff', 10, 1, NOW()),
('thumbnail_position', 'Thumbnail Position', 'right', 'Right', '#28a745', 20, 1, NOW()),
('thumbnail_position', 'Thumbnail Position', 'top', 'Top', '#6f42c1', 30, 1, NOW()),
('thumbnail_position', 'Thumbnail Position', 'background', 'Background', '#fd7e14', 40, 1, NOW()),
('thumbnail_position', 'Thumbnail Position', 'inline', 'Inline', '#17a2b8', 50, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('thumbnail_size', 'Thumbnail Size', 'none', 'None', '#6c757d', 10, 1, NOW()),
('thumbnail_size', 'Thumbnail Size', 'small', 'Small', '#28a745', 20, 1, NOW()),
('thumbnail_size', 'Thumbnail Size', 'medium', 'Medium', '#007bff', 30, 1, NOW()),
('thumbnail_size', 'Thumbnail Size', 'large', 'Large', '#6f42c1', 40, 1, NOW()),
('thumbnail_size', 'Thumbnail Size', 'hero', 'Hero', '#fd7e14', 50, 1, NOW()),
('thumbnail_size', 'Thumbnail Size', 'full', 'Full', '#dc3545', 60, 1, NOW());

-- ============================================================================
-- DONOR AGREEMENT SPECIFIC TYPES
-- ============================================================================

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('donor_agreement_status', 'Donor Agreement Status', 'draft', 'Draft', '#6c757d', 10, 1, NOW()),
('donor_agreement_status', 'Donor Agreement Status', 'pending_review', 'Pending Review', '#ffc107', 20, 1, NOW()),
('donor_agreement_status', 'Donor Agreement Status', 'pending_signature', 'Pending Signature', '#17a2b8', 30, 1, NOW()),
('donor_agreement_status', 'Donor Agreement Status', 'active', 'Active', '#28a745', 40, 1, NOW()),
('donor_agreement_status', 'Donor Agreement Status', 'expired', 'Expired', '#dc3545', 50, 1, NOW()),
('donor_agreement_status', 'Donor Agreement Status', 'terminated', 'Terminated', '#343a40', 60, 1, NOW()),
('donor_agreement_status', 'Donor Agreement Status', 'superseded', 'Superseded', '#6c757d', 70, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('donor_document_type', 'Donor Document Type', 'signed_agreement', 'Signed Agreement', '#28a745', 10, 1, NOW()),
('donor_document_type', 'Donor Document Type', 'draft', 'Draft', '#6c757d', 20, 1, NOW()),
('donor_document_type', 'Donor Document Type', 'amendment', 'Amendment', '#007bff', 30, 1, NOW()),
('donor_document_type', 'Donor Document Type', 'addendum', 'Addendum', '#17a2b8', 40, 1, NOW()),
('donor_document_type', 'Donor Document Type', 'schedule', 'Schedule', '#6f42c1', 50, 1, NOW()),
('donor_document_type', 'Donor Document Type', 'correspondence', 'Correspondence', '#ffc107', 60, 1, NOW()),
('donor_document_type', 'Donor Document Type', 'appraisal_report', 'Appraisal Report', '#fd7e14', 70, 1, NOW()),
('donor_document_type', 'Donor Document Type', 'inventory', 'Inventory', '#20c997', 80, 1, NOW()),
('donor_document_type', 'Donor Document Type', 'deed_of_gift', 'Deed of Gift', '#28a745', 90, 1, NOW()),
('donor_document_type', 'Donor Document Type', 'transfer_form', 'Transfer Form', '#007bff', 100, 1, NOW()),
('donor_document_type', 'Donor Document Type', 'receipt', 'Receipt', '#17a2b8', 110, 1, NOW()),
('donor_document_type', 'Donor Document Type', 'payment_record', 'Payment Record', '#e83e8c', 120, 1, NOW()),
('donor_document_type', 'Donor Document Type', 'legal_opinion', 'Legal Opinion', '#dc3545', 130, 1, NOW()),
('donor_document_type', 'Donor Document Type', 'board_resolution', 'Board Resolution', '#343a40', 140, 1, NOW()),
('donor_document_type', 'Donor Document Type', 'donor_id', 'Donor ID', '#6c757d', 150, 1, NOW()),
('donor_document_type', 'Donor Document Type', 'provenance_evidence', 'Provenance Evidence', '#6f42c1', 160, 1, NOW()),
('donor_document_type', 'Donor Document Type', 'valuation', 'Valuation', '#ffc107', 170, 1, NOW()),
('donor_document_type', 'Donor Document Type', 'insurance', 'Insurance', '#fd7e14', 180, 1, NOW()),
('donor_document_type', 'Donor Document Type', 'photo', 'Photo', '#28a745', 190, 1, NOW()),
('donor_document_type', 'Donor Document Type', 'other', 'Other', '#868e96', 200, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('donor_relationship_type', 'Donor Relationship Type', 'covers', 'Covers', '#28a745', 10, 1, NOW()),
('donor_relationship_type', 'Donor Relationship Type', 'partially_covers', 'Partially Covers', '#ffc107', 20, 1, NOW()),
('donor_relationship_type', 'Donor Relationship Type', 'references', 'References', '#007bff', 30, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('donor_reminder_type', 'Donor Reminder Type', 'expiry_warning', 'Expiry Warning', '#dc3545', 10, 1, NOW()),
('donor_reminder_type', 'Donor Reminder Type', 'review_due', 'Review Due', '#ffc107', 20, 1, NOW()),
('donor_reminder_type', 'Donor Reminder Type', 'renewal_required', 'Renewal Required', '#fd7e14', 30, 1, NOW()),
('donor_reminder_type', 'Donor Reminder Type', 'restriction_ending', 'Restriction Ending', '#17a2b8', 40, 1, NOW()),
('donor_reminder_type', 'Donor Reminder Type', 'payment_due', 'Payment Due', '#007bff', 50, 1, NOW()),
('donor_reminder_type', 'Donor Reminder Type', 'donor_contact', 'Donor Contact', '#28a745', 60, 1, NOW()),
('donor_reminder_type', 'Donor Reminder Type', 'anniversary', 'Anniversary', '#6f42c1', 70, 1, NOW()),
('donor_reminder_type', 'Donor Reminder Type', 'audit', 'Audit', '#e83e8c', 80, 1, NOW()),
('donor_reminder_type', 'Donor Reminder Type', 'preservation_check', 'Preservation Check', '#20c997', 90, 1, NOW()),
('donor_reminder_type', 'Donor Reminder Type', 'custom', 'Custom', '#6c757d', 100, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('popia_category', 'POPIA Category', 'special_personal', 'Special Personal', '#dc3545', 10, 1, NOW()),
('popia_category', 'POPIA Category', 'personal', 'Personal', '#fd7e14', 20, 1, NOW()),
('popia_category', 'POPIA Category', 'children', 'Children', '#e83e8c', 30, 1, NOW()),
('popia_category', 'POPIA Category', 'criminal', 'Criminal', '#343a40', 40, 1, NOW()),
('popia_category', 'POPIA Category', 'biometric', 'Biometric', '#6f42c1', 50, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('donor_restriction_type', 'Donor Restriction Type', 'closure', 'Closure', '#dc3545', 10, 1, NOW()),
('donor_restriction_type', 'Donor Restriction Type', 'partial_closure', 'Partial Closure', '#fd7e14', 20, 1, NOW()),
('donor_restriction_type', 'Donor Restriction Type', 'redaction', 'Redaction', '#ffc107', 30, 1, NOW()),
('donor_restriction_type', 'Donor Restriction Type', 'permission_only', 'Permission Only', '#17a2b8', 40, 1, NOW()),
('donor_restriction_type', 'Donor Restriction Type', 'researcher_only', 'Researcher Only', '#007bff', 50, 1, NOW()),
('donor_restriction_type', 'Donor Restriction Type', 'onsite_only', 'Onsite Only', '#28a745', 60, 1, NOW()),
('donor_restriction_type', 'Donor Restriction Type', 'no_copying', 'No Copying', '#6f42c1', 70, 1, NOW()),
('donor_restriction_type', 'Donor Restriction Type', 'no_publication', 'No Publication', '#e83e8c', 80, 1, NOW()),
('donor_restriction_type', 'Donor Restriction Type', 'anonymization', 'Anonymization', '#20c997', 90, 1, NOW()),
('donor_restriction_type', 'Donor Restriction Type', 'time_embargo', 'Time Embargo', '#343a40', 100, 1, NOW()),
('donor_restriction_type', 'Donor Restriction Type', 'review_required', 'Review Required', '#ffc107', 110, 1, NOW()),
('donor_restriction_type', 'Donor Restriction Type', 'security_clearance', 'Security Clearance', '#dc3545', 120, 1, NOW()),
('donor_restriction_type', 'Donor Restriction Type', 'popia_restricted', 'POPIA Restricted', '#e83e8c', 130, 1, NOW()),
('donor_restriction_type', 'Donor Restriction Type', 'legal_hold', 'Legal Hold', '#343a40', 140, 1, NOW()),
('donor_restriction_type', 'Donor Restriction Type', 'cultural_protocol', 'Cultural Protocol', '#6f42c1', 150, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('donor_right_permission', 'Donor Right Permission', 'granted', 'Granted', '#28a745', 10, 1, NOW()),
('donor_right_permission', 'Donor Right Permission', 'restricted', 'Restricted', '#ffc107', 20, 1, NOW()),
('donor_right_permission', 'Donor Right Permission', 'prohibited', 'Prohibited', '#dc3545', 30, 1, NOW()),
('donor_right_permission', 'Donor Right Permission', 'conditional', 'Conditional', '#17a2b8', 40, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('donor_right_type', 'Donor Right Type', 'replicate', 'Replicate', '#007bff', 10, 1, NOW()),
('donor_right_type', 'Donor Right Type', 'migrate', 'Migrate', '#28a745', 20, 1, NOW()),
('donor_right_type', 'Donor Right Type', 'modify', 'Modify', '#6f42c1', 30, 1, NOW()),
('donor_right_type', 'Donor Right Type', 'use', 'Use', '#ffc107', 40, 1, NOW()),
('donor_right_type', 'Donor Right Type', 'disseminate', 'Disseminate', '#17a2b8', 50, 1, NOW()),
('donor_right_type', 'Donor Right Type', 'delete', 'Delete', '#dc3545', 60, 1, NOW()),
('donor_right_type', 'Donor Right Type', 'display', 'Display', '#20c997', 70, 1, NOW()),
('donor_right_type', 'Donor Right Type', 'publish', 'Publish', '#fd7e14', 80, 1, NOW()),
('donor_right_type', 'Donor Right Type', 'digitize', 'Digitize', '#e83e8c', 90, 1, NOW()),
('donor_right_type', 'Donor Right Type', 'reproduce', 'Reproduce', '#343a40', 100, 1, NOW()),
('donor_right_type', 'Donor Right Type', 'loan', 'Loan', '#6c757d', 110, 1, NOW()),
('donor_right_type', 'Donor Right Type', 'exhibit', 'Exhibit', '#28a745', 120, 1, NOW()),
('donor_right_type', 'Donor Right Type', 'broadcast', 'Broadcast', '#007bff', 130, 1, NOW()),
('donor_right_type', 'Donor Right Type', 'commercial_use', 'Commercial Use', '#dc3545', 140, 1, NOW()),
('donor_right_type', 'Donor Right Type', 'derivative_works', 'Derivative Works', '#6f42c1', 150, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('donor_applies_to', 'Donor Rights Applies To', 'all_items', 'All Items', '#28a745', 10, 1, NOW()),
('donor_applies_to', 'Donor Rights Applies To', 'specific_items', 'Specific Items', '#007bff', 20, 1, NOW()),
('donor_applies_to', 'Donor Rights Applies To', 'digital_only', 'Digital Only', '#6f42c1', 30, 1, NOW()),
('donor_applies_to', 'Donor Rights Applies To', 'physical_only', 'Physical Only', '#fd7e14', 40, 1, NOW()),
('donor_applies_to', 'Donor Rights Applies To', 'metadata_only', 'Metadata Only', '#17a2b8', 50, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('provenance_relationship', 'Provenance Relationship', 'donated', 'Donated', '#28a745', 10, 1, NOW()),
('provenance_relationship', 'Provenance Relationship', 'deposited', 'Deposited', '#007bff', 20, 1, NOW()),
('provenance_relationship', 'Provenance Relationship', 'loaned', 'Loaned', '#17a2b8', 30, 1, NOW()),
('provenance_relationship', 'Provenance Relationship', 'purchased', 'Purchased', '#ffc107', 40, 1, NOW()),
('provenance_relationship', 'Provenance Relationship', 'transferred', 'Transferred', '#6f42c1', 50, 1, NOW()),
('provenance_relationship', 'Provenance Relationship', 'bequeathed', 'Bequeathed', '#fd7e14', 60, 1, NOW()),
('provenance_relationship', 'Provenance Relationship', 'gifted', 'Gifted', '#20c997', 70, 1, NOW());

-- ============================================================================
-- EMBARGO TYPES
-- ============================================================================

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('embargo_type', 'Embargo Type', 'full', 'Full Embargo', '#dc3545', 10, 1, NOW()),
('embargo_type', 'Embargo Type', 'metadata_only', 'Metadata Only', '#fd7e14', 20, 1, NOW()),
('embargo_type', 'Embargo Type', 'digital_object', 'Digital Object Only', '#ffc107', 30, 1, NOW()),
('embargo_type', 'Embargo Type', 'custom', 'Custom', '#6c757d', 40, 1, NOW()),
('embargo_type', 'Embargo Type', 'digital_only', 'Digital Only', '#17a2b8', 50, 1, NOW()),
('embargo_type', 'Embargo Type', 'metadata_hidden', 'Metadata Hidden', '#6f42c1', 60, 1, NOW()),
('embargo_type', 'Embargo Type', 'partial', 'Partial', '#ffc107', 70, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('embargo_status', 'Embargo Status', 'active', 'Active', '#dc3545', 10, 1, NOW()),
('embargo_status', 'Embargo Status', 'expired', 'Expired', '#6c757d', 20, 1, NOW()),
('embargo_status', 'Embargo Status', 'lifted', 'Lifted', '#28a745', 30, 1, NOW()),
('embargo_status', 'Embargo Status', 'pending', 'Pending', '#ffc107', 40, 1, NOW()),
('embargo_status', 'Embargo Status', 'extended', 'Extended', '#17a2b8', 50, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('embargo_audit_action', 'Embargo Audit Action', 'created', 'Created', '#28a745', 10, 1, NOW()),
('embargo_audit_action', 'Embargo Audit Action', 'modified', 'Modified', '#007bff', 20, 1, NOW()),
('embargo_audit_action', 'Embargo Audit Action', 'lifted', 'Lifted', '#28a745', 30, 1, NOW()),
('embargo_audit_action', 'Embargo Audit Action', 'extended', 'Extended', '#ffc107', 40, 1, NOW()),
('embargo_audit_action', 'Embargo Audit Action', 'exception_added', 'Exception Added', '#17a2b8', 50, 1, NOW()),
('embargo_audit_action', 'Embargo Audit Action', 'exception_removed', 'Exception Removed', '#dc3545', 60, 1, NOW()),
('embargo_audit_action', 'Embargo Audit Action', 'reviewed', 'Reviewed', '#6f42c1', 70, 1, NOW()),
('embargo_audit_action', 'Embargo Audit Action', 'notification_sent', 'Notification Sent', '#20c997', 80, 1, NOW()),
('embargo_audit_action', 'Embargo Audit Action', 'auto_released', 'Auto Released', '#343a40', 90, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('embargo_exception_type', 'Embargo Exception Type', 'user', 'User', '#007bff', 10, 1, NOW()),
('embargo_exception_type', 'Embargo Exception Type', 'group', 'Group', '#28a745', 20, 1, NOW()),
('embargo_exception_type', 'Embargo Exception Type', 'ip_range', 'IP Range', '#6f42c1', 30, 1, NOW()),
('embargo_exception_type', 'Embargo Exception Type', 'repository', 'Repository', '#fd7e14', 40, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('embargo_reason', 'Embargo Reason', 'donor_restriction', 'Donor Restriction', '#dc3545', 10, 1, NOW()),
('embargo_reason', 'Embargo Reason', 'copyright', 'Copyright', '#fd7e14', 20, 1, NOW()),
('embargo_reason', 'Embargo Reason', 'privacy', 'Privacy', '#e83e8c', 30, 1, NOW()),
('embargo_reason', 'Embargo Reason', 'legal', 'Legal', '#343a40', 40, 1, NOW()),
('embargo_reason', 'Embargo Reason', 'commercial', 'Commercial', '#ffc107', 50, 1, NOW()),
('embargo_reason', 'Embargo Reason', 'research', 'Research', '#007bff', 60, 1, NOW()),
('embargo_reason', 'Embargo Reason', 'cultural', 'Cultural', '#6f42c1', 70, 1, NOW()),
('embargo_reason', 'Embargo Reason', 'security', 'Security', '#dc3545', 80, 1, NOW()),
('embargo_reason', 'Embargo Reason', 'other', 'Other', '#6c757d', 90, 1, NOW());

-- ============================================================================
-- EXHIBITION TYPES
-- ============================================================================

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('exhibition_type', 'Exhibition Type', 'permanent', 'Permanent', '#28a745', 10, 1, NOW()),
('exhibition_type', 'Exhibition Type', 'temporary', 'Temporary', '#007bff', 20, 1, NOW()),
('exhibition_type', 'Exhibition Type', 'traveling', 'Traveling', '#6f42c1', 30, 1, NOW()),
('exhibition_type', 'Exhibition Type', 'online', 'Online', '#17a2b8', 40, 1, NOW()),
('exhibition_type', 'Exhibition Type', 'pop_up', 'Pop-up', '#fd7e14', 50, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('exhibition_status', 'Exhibition Status', 'concept', 'Concept', '#6c757d', 10, 1, NOW()),
('exhibition_status', 'Exhibition Status', 'planning', 'Planning', '#ffc107', 20, 1, NOW()),
('exhibition_status', 'Exhibition Status', 'preparation', 'Preparation', '#17a2b8', 30, 1, NOW()),
('exhibition_status', 'Exhibition Status', 'installation', 'Installation', '#007bff', 40, 1, NOW()),
('exhibition_status', 'Exhibition Status', 'open', 'Open', '#28a745', 50, 1, NOW()),
('exhibition_status', 'Exhibition Status', 'closing', 'Closing', '#fd7e14', 60, 1, NOW()),
('exhibition_status', 'Exhibition Status', 'closed', 'Closed', '#343a40', 70, 1, NOW()),
('exhibition_status', 'Exhibition Status', 'archived', 'Archived', '#6c757d', 80, 1, NOW()),
('exhibition_status', 'Exhibition Status', 'canceled', 'Canceled', '#dc3545', 90, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('exhibition_checklist_type', 'Exhibition Checklist Type', 'planning', 'Planning', '#ffc107', 10, 1, NOW()),
('exhibition_checklist_type', 'Exhibition Checklist Type', 'preparation', 'Preparation', '#17a2b8', 20, 1, NOW()),
('exhibition_checklist_type', 'Exhibition Checklist Type', 'installation', 'Installation', '#007bff', 30, 1, NOW()),
('exhibition_checklist_type', 'Exhibition Checklist Type', 'opening', 'Opening', '#28a745', 40, 1, NOW()),
('exhibition_checklist_type', 'Exhibition Checklist Type', 'during', 'During', '#6f42c1', 50, 1, NOW()),
('exhibition_checklist_type', 'Exhibition Checklist Type', 'closing', 'Closing', '#fd7e14', 60, 1, NOW()),
('exhibition_checklist_type', 'Exhibition Checklist Type', 'deinstallation', 'Deinstallation', '#343a40', 70, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('exhibition_checklist_status', 'Exhibition Checklist Status', 'not_started', 'Not Started', '#6c757d', 10, 1, NOW()),
('exhibition_checklist_status', 'Exhibition Checklist Status', 'in_progress', 'In Progress', '#007bff', 20, 1, NOW()),
('exhibition_checklist_status', 'Exhibition Checklist Status', 'completed', 'Completed', '#28a745', 30, 1, NOW()),
('exhibition_checklist_status', 'Exhibition Checklist Status', 'overdue', 'Overdue', '#dc3545', 40, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('exhibition_event_type', 'Exhibition Event Type', 'opening', 'Opening', '#28a745', 10, 1, NOW()),
('exhibition_event_type', 'Exhibition Event Type', 'closing', 'Closing', '#dc3545', 20, 1, NOW()),
('exhibition_event_type', 'Exhibition Event Type', 'tour', 'Tour', '#007bff', 30, 1, NOW()),
('exhibition_event_type', 'Exhibition Event Type', 'lecture', 'Lecture', '#6f42c1', 40, 1, NOW()),
('exhibition_event_type', 'Exhibition Event Type', 'workshop', 'Workshop', '#fd7e14', 50, 1, NOW()),
('exhibition_event_type', 'Exhibition Event Type', 'performance', 'Performance', '#e83e8c', 60, 1, NOW()),
('exhibition_event_type', 'Exhibition Event Type', 'family', 'Family Event', '#20c997', 70, 1, NOW()),
('exhibition_event_type', 'Exhibition Event Type', 'school', 'School Event', '#ffc107', 80, 1, NOW()),
('exhibition_event_type', 'Exhibition Event Type', 'vip', 'VIP Event', '#343a40', 90, 1, NOW()),
('exhibition_event_type', 'Exhibition Event Type', 'press', 'Press Event', '#17a2b8', 100, 1, NOW()),
('exhibition_event_type', 'Exhibition Event Type', 'other', 'Other', '#6c757d', 110, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('exhibition_event_status', 'Exhibition Event Status', 'scheduled', 'Scheduled', '#ffc107', 10, 1, NOW()),
('exhibition_event_status', 'Exhibition Event Status', 'confirmed', 'Confirmed', '#28a745', 20, 1, NOW()),
('exhibition_event_status', 'Exhibition Event Status', 'canceled', 'Canceled', '#dc3545', 30, 1, NOW()),
('exhibition_event_status', 'Exhibition Event Status', 'completed', 'Completed', '#6c757d', 40, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('exhibition_gallery_type', 'Exhibition Gallery Type', 'gallery', 'Gallery', '#007bff', 10, 1, NOW()),
('exhibition_gallery_type', 'Exhibition Gallery Type', 'hall', 'Hall', '#28a745', 20, 1, NOW()),
('exhibition_gallery_type', 'Exhibition Gallery Type', 'room', 'Room', '#6f42c1', 30, 1, NOW()),
('exhibition_gallery_type', 'Exhibition Gallery Type', 'corridor', 'Corridor', '#fd7e14', 40, 1, NOW()),
('exhibition_gallery_type', 'Exhibition Gallery Type', 'outdoor', 'Outdoor', '#28a745', 50, 1, NOW()),
('exhibition_gallery_type', 'Exhibition Gallery Type', 'foyer', 'Foyer', '#17a2b8', 60, 1, NOW()),
('exhibition_gallery_type', 'Exhibition Gallery Type', 'stairwell', 'Stairwell', '#6c757d', 70, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('exhibition_media_type', 'Exhibition Media Type', 'image', 'Image', '#28a745', 10, 1, NOW()),
('exhibition_media_type', 'Exhibition Media Type', 'video', 'Video', '#6f42c1', 20, 1, NOW()),
('exhibition_media_type', 'Exhibition Media Type', 'audio', 'Audio', '#fd7e14', 30, 1, NOW()),
('exhibition_media_type', 'Exhibition Media Type', 'document', 'Document', '#007bff', 40, 1, NOW()),
('exhibition_media_type', 'Exhibition Media Type', 'floorplan', 'Floorplan', '#17a2b8', 50, 1, NOW()),
('exhibition_media_type', 'Exhibition Media Type', 'poster', 'Poster', '#e83e8c', 60, 1, NOW()),
('exhibition_media_type', 'Exhibition Media Type', 'press', 'Press', '#20c997', 70, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('exhibition_media_usage', 'Exhibition Media Usage', 'promotional', 'Promotional', '#28a745', 10, 1, NOW()),
('exhibition_media_usage', 'Exhibition Media Usage', 'installation', 'Installation', '#007bff', 20, 1, NOW()),
('exhibition_media_usage', 'Exhibition Media Usage', 'documentation', 'Documentation', '#6f42c1', 30, 1, NOW()),
('exhibition_media_usage', 'Exhibition Media Usage', 'press', 'Press', '#17a2b8', 40, 1, NOW()),
('exhibition_media_usage', 'Exhibition Media Usage', 'catalog', 'Catalog', '#fd7e14', 50, 1, NOW()),
('exhibition_media_usage', 'Exhibition Media Usage', 'internal', 'Internal', '#6c757d', 60, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('exhibition_object_status', 'Exhibition Object Status', 'proposed', 'Proposed', '#ffc107', 10, 1, NOW()),
('exhibition_object_status', 'Exhibition Object Status', 'confirmed', 'Confirmed', '#28a745', 20, 1, NOW()),
('exhibition_object_status', 'Exhibition Object Status', 'on_loan_request', 'On Loan Request', '#17a2b8', 30, 1, NOW()),
('exhibition_object_status', 'Exhibition Object Status', 'installed', 'Installed', '#007bff', 40, 1, NOW()),
('exhibition_object_status', 'Exhibition Object Status', 'removed', 'Removed', '#dc3545', 50, 1, NOW()),
('exhibition_object_status', 'Exhibition Object Status', 'returned', 'Returned', '#6c757d', 60, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('security_level', 'Security Level', 'standard', 'Standard', '#28a745', 10, 1, NOW()),
('security_level', 'Security Level', 'enhanced', 'Enhanced', '#ffc107', 20, 1, NOW()),
('security_level', 'Security Level', 'maximum', 'Maximum', '#dc3545', 30, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('exhibition_section_type', 'Exhibition Section Type', 'gallery', 'Gallery', '#007bff', 10, 1, NOW()),
('exhibition_section_type', 'Exhibition Section Type', 'room', 'Room', '#28a745', 20, 1, NOW()),
('exhibition_section_type', 'Exhibition Section Type', 'alcove', 'Alcove', '#6f42c1', 30, 1, NOW()),
('exhibition_section_type', 'Exhibition Section Type', 'corridor', 'Corridor', '#fd7e14', 40, 1, NOW()),
('exhibition_section_type', 'Exhibition Section Type', 'outdoor', 'Outdoor', '#28a745', 50, 1, NOW()),
('exhibition_section_type', 'Exhibition Section Type', 'virtual', 'Virtual', '#17a2b8', 60, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('exhibition_narrative_type', 'Exhibition Narrative Type', 'thematic', 'Thematic', '#007bff', 10, 1, NOW()),
('exhibition_narrative_type', 'Exhibition Narrative Type', 'chronological', 'Chronological', '#28a745', 20, 1, NOW()),
('exhibition_narrative_type', 'Exhibition Narrative Type', 'biographical', 'Biographical', '#6f42c1', 30, 1, NOW()),
('exhibition_narrative_type', 'Exhibition Narrative Type', 'geographical', 'Geographical', '#fd7e14', 40, 1, NOW()),
('exhibition_narrative_type', 'Exhibition Narrative Type', 'technique', 'Technique', '#17a2b8', 50, 1, NOW()),
('exhibition_narrative_type', 'Exhibition Narrative Type', 'custom', 'Custom', '#6c757d', 60, 1, NOW()),
('exhibition_narrative_type', 'Exhibition Narrative Type', 'general', 'General', '#ffc107', 70, 1, NOW()),
('exhibition_narrative_type', 'Exhibition Narrative Type', 'guided_tour', 'Guided Tour', '#e83e8c', 80, 1, NOW()),
('exhibition_narrative_type', 'Exhibition Narrative Type', 'self_guided', 'Self-Guided', '#20c997', 90, 1, NOW()),
('exhibition_narrative_type', 'Exhibition Narrative Type', 'educational', 'Educational', '#343a40', 100, 1, NOW()),
('exhibition_narrative_type', 'Exhibition Narrative Type', 'accessible', 'Accessible', '#28a745', 110, 1, NOW()),
('exhibition_narrative_type', 'Exhibition Narrative Type', 'highlights', 'Highlights', '#dc3545', 120, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('reading_level', 'Reading Level', 'basic', 'Basic', '#28a745', 10, 1, NOW()),
('reading_level', 'Reading Level', 'intermediate', 'Intermediate', '#007bff', 20, 1, NOW()),
('reading_level', 'Reading Level', 'advanced', 'Advanced', '#6f42c1', 30, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('target_audience', 'Target Audience', 'general', 'General', '#007bff', 10, 1, NOW()),
('target_audience', 'Target Audience', 'children', 'Children', '#ffc107', 20, 1, NOW()),
('target_audience', 'Target Audience', 'students', 'Students', '#17a2b8', 30, 1, NOW()),
('target_audience', 'Target Audience', 'specialists', 'Specialists', '#6f42c1', 40, 1, NOW()),
('target_audience', 'Target Audience', 'all', 'All', '#28a745', 50, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('venue_type', 'Venue Type', 'internal', 'Internal', '#007bff', 10, 1, NOW()),
('venue_type', 'Venue Type', 'partner', 'Partner', '#28a745', 20, 1, NOW()),
('venue_type', 'Venue Type', 'external', 'External', '#fd7e14', 30, 1, NOW()),
('venue_type', 'Venue Type', 'online', 'Online', '#17a2b8', 40, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('accessibility_rating', 'Accessibility Rating', 'none', 'None', '#dc3545', 10, 1, NOW()),
('accessibility_rating', 'Accessibility Rating', 'partial', 'Partial', '#ffc107', 20, 1, NOW()),
('accessibility_rating', 'Accessibility Rating', 'full', 'Full', '#28a745', 30, 1, NOW());

-- ============================================================================
-- GALLERY TYPES
-- ============================================================================

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('artist_type', 'Artist Type', 'individual', 'Individual', '#007bff', 10, 1, NOW()),
('artist_type', 'Artist Type', 'collective', 'Collective', '#28a745', 20, 1, NOW()),
('artist_type', 'Artist Type', 'studio', 'Studio', '#6f42c1', 30, 1, NOW()),
('artist_type', 'Artist Type', 'anonymous', 'Anonymous', '#6c757d', 40, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('bibliography_entry_type', 'Bibliography Entry Type', 'book', 'Book', '#007bff', 10, 1, NOW()),
('bibliography_entry_type', 'Bibliography Entry Type', 'catalog', 'Catalog', '#28a745', 20, 1, NOW()),
('bibliography_entry_type', 'Bibliography Entry Type', 'article', 'Article', '#6f42c1', 30, 1, NOW()),
('bibliography_entry_type', 'Bibliography Entry Type', 'review', 'Review', '#fd7e14', 40, 1, NOW()),
('bibliography_entry_type', 'Bibliography Entry Type', 'interview', 'Interview', '#17a2b8', 50, 1, NOW()),
('bibliography_entry_type', 'Bibliography Entry Type', 'thesis', 'Thesis', '#e83e8c', 60, 1, NOW()),
('bibliography_entry_type', 'Bibliography Entry Type', 'website', 'Website', '#20c997', 70, 1, NOW()),
('bibliography_entry_type', 'Bibliography Entry Type', 'video', 'Video', '#ffc107', 80, 1, NOW()),
('bibliography_entry_type', 'Bibliography Entry Type', 'other', 'Other', '#6c757d', 90, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('gallery_exhibition_type', 'Gallery Exhibition Type', 'solo', 'Solo', '#007bff', 10, 1, NOW()),
('gallery_exhibition_type', 'Gallery Exhibition Type', 'group', 'Group', '#28a745', 20, 1, NOW()),
('gallery_exhibition_type', 'Gallery Exhibition Type', 'duo', 'Duo', '#6f42c1', 30, 1, NOW()),
('gallery_exhibition_type', 'Gallery Exhibition Type', 'retrospective', 'Retrospective', '#fd7e14', 40, 1, NOW()),
('gallery_exhibition_type', 'Gallery Exhibition Type', 'survey', 'Survey', '#17a2b8', 50, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('facility_report_type', 'Facility Report Type', 'incoming', 'Incoming', '#28a745', 10, 1, NOW()),
('facility_report_type', 'Facility Report Type', 'outgoing', 'Outgoing', '#fd7e14', 20, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('insurance_policy_type', 'Insurance Policy Type', 'all_risk', 'All Risk', '#28a745', 10, 1, NOW()),
('insurance_policy_type', 'Insurance Policy Type', 'named_perils', 'Named Perils', '#007bff', 20, 1, NOW()),
('insurance_policy_type', 'Insurance Policy Type', 'transit', 'Transit', '#6f42c1', 30, 1, NOW()),
('insurance_policy_type', 'Insurance Policy Type', 'exhibition', 'Exhibition', '#fd7e14', 40, 1, NOW()),
('insurance_policy_type', 'Insurance Policy Type', 'permanent_collection', 'Permanent Collection', '#17a2b8', 50, 1, NOW()),
('insurance_policy_type', 'Insurance Policy Type', 'all_risks', 'All Risks', '#28a745', 60, 1, NOW()),
('insurance_policy_type', 'Insurance Policy Type', 'blanket', 'Blanket', '#ffc107', 70, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('gallery_loan_type', 'Gallery Loan Type', 'incoming', 'Incoming', '#28a745', 10, 1, NOW()),
('gallery_loan_type', 'Gallery Loan Type', 'outgoing', 'Outgoing', '#fd7e14', 20, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('gallery_loan_status', 'Gallery Loan Status', 'inquiry', 'Inquiry', '#6c757d', 10, 1, NOW()),
('gallery_loan_status', 'Gallery Loan Status', 'requested', 'Requested', '#ffc107', 20, 1, NOW()),
('gallery_loan_status', 'Gallery Loan Status', 'approved', 'Approved', '#28a745', 30, 1, NOW()),
('gallery_loan_status', 'Gallery Loan Status', 'agreed', 'Agreed', '#007bff', 40, 1, NOW()),
('gallery_loan_status', 'Gallery Loan Status', 'in_transit_out', 'In Transit Out', '#17a2b8', 50, 1, NOW()),
('gallery_loan_status', 'Gallery Loan Status', 'on_loan', 'On Loan', '#6f42c1', 60, 1, NOW()),
('gallery_loan_status', 'Gallery Loan Status', 'in_transit_return', 'In Transit Return', '#fd7e14', 70, 1, NOW()),
('gallery_loan_status', 'Gallery Loan Status', 'returned', 'Returned', '#343a40', 80, 1, NOW()),
('gallery_loan_status', 'Gallery Loan Status', 'cancelled', 'Cancelled', '#dc3545', 90, 1, NOW()),
('gallery_loan_status', 'Gallery Loan Status', 'declined', 'Declined', '#dc3545', 100, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('valuation_type', 'Valuation Type', 'insurance', 'Insurance', '#dc3545', 10, 1, NOW()),
('valuation_type', 'Valuation Type', 'market', 'Market', '#28a745', 20, 1, NOW()),
('valuation_type', 'Valuation Type', 'replacement', 'Replacement', '#007bff', 30, 1, NOW()),
('valuation_type', 'Valuation Type', 'auction_estimate', 'Auction Estimate', '#ffc107', 40, 1, NOW()),
('valuation_type', 'Valuation Type', 'probate', 'Probate', '#6f42c1', 50, 1, NOW()),
('valuation_type', 'Valuation Type', 'donation', 'Donation', '#17a2b8', 60, 1, NOW());

-- ============================================================================
-- GETTY VOCABULARY TYPES
-- ============================================================================

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('getty_vocabulary', 'Getty Vocabulary', 'aat', 'Art & Architecture Thesaurus', '#007bff', 10, 1, NOW()),
('getty_vocabulary', 'Getty Vocabulary', 'tgn', 'Thesaurus of Geographic Names', '#28a745', 20, 1, NOW()),
('getty_vocabulary', 'Getty Vocabulary', 'ulan', 'Union List of Artist Names', '#6f42c1', 30, 1, NOW());

INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('getty_link_status', 'Getty Link Status', 'confirmed', 'Confirmed', '#28a745', 10, 1, NOW()),
('getty_link_status', 'Getty Link Status', 'suggested', 'Suggested', '#ffc107', 20, 1, NOW()),
('getty_link_status', 'Getty Link Status', 'rejected', 'Rejected', '#dc3545', 30, 1, NOW()),
('getty_link_status', 'Getty Link Status', 'pending', 'Pending', '#6c757d', 40, 1, NOW());

-- ============================================================================
-- Show migration statistics
-- ============================================================================

SELECT 'Phase 2 Migration Complete' as status;
SELECT taxonomy, taxonomy_label, COUNT(*) as term_count
FROM ahg_dropdown
GROUP BY taxonomy, taxonomy_label
ORDER BY taxonomy_label;

-- ---------------------------------------------------------------------------
-- Merged in from database/enum_to_dropdown_migration_phase3.sql on 2026-08-17.
--
-- It sat beside install.sql and was never run by install-plugin-schema.php,
-- so a clean install silently lacked whatever it defines. Our own instances
-- had it because someone applied the file by hand. A plugin's schema is
-- install.sql; there is no second file.
-- ---------------------------------------------------------------------------

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

-- ---------------------------------------------------------------------------
-- Merged in from database/enum_to_dropdown_migration.sql on 2026-08-17.
--
-- It sat beside install.sql and was never run by install-plugin-schema.php,
-- so a clean install silently lacked whatever it defines. Our own instances
-- had it because someone applied the file by hand. A plugin's schema is
-- install.sql; there is no second file.
-- ---------------------------------------------------------------------------

-- ============================================================================
-- ENUM to ahg_dropdown Migration Script
-- Generated: 2026-02-04
--
-- This script migrates hardcoded ENUM values to the ahg_dropdown system
-- for centralized vocabulary management.
--
-- Run this AFTER the ahg_dropdown table exists.
-- ============================================================================

-- ============================================================================
-- STEP 1: INSERT TAXONOMIES INTO ahg_dropdown
-- ============================================================================

-- ---------------------------------------------------------------------------
-- JOB/TASK STATUS (used by: ahg_ai_batch, ahg_ai_job, ahg_dedupe_scan, etc.)
-- ---------------------------------------------------------------------------
INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('job_status', 'Job Status', 'pending', 'Pending', '#6c757d', 10, 1, NOW()),
('job_status', 'Job Status', 'queued', 'Queued', '#17a2b8', 20, 1, NOW()),
('job_status', 'Job Status', 'running', 'Running', '#007bff', 30, 1, NOW()),
('job_status', 'Job Status', 'paused', 'Paused', '#ffc107', 40, 1, NOW()),
('job_status', 'Job Status', 'completed', 'Completed', '#28a745', 50, 1, NOW()),
('job_status', 'Job Status', 'failed', 'Failed', '#dc3545', 60, 1, NOW()),
('job_status', 'Job Status', 'cancelled', 'Cancelled', '#6c757d', 70, 1, NOW()),
('job_status', 'Job Status', 'skipped', 'Skipped', '#868e96', 80, 1, NOW());

-- ---------------------------------------------------------------------------
-- APPROVAL STATUS (used by: workflow tasks, requests, etc.)
-- ---------------------------------------------------------------------------
INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('approval_status', 'Approval Status', 'pending', 'Pending', '#ffc107', 10, 1, NOW()),
('approval_status', 'Approval Status', 'approved', 'Approved', '#28a745', 20, 1, NOW()),
('approval_status', 'Approval Status', 'rejected', 'Rejected', '#dc3545', 30, 1, NOW()),
('approval_status', 'Approval Status', 'returned', 'Returned', '#fd7e14', 40, 1, NOW()),
('approval_status', 'Approval Status', 'escalated', 'Escalated', '#e83e8c', 50, 1, NOW()),
('approval_status', 'Approval Status', 'edited', 'Edited', '#17a2b8', 60, 1, NOW());

-- ---------------------------------------------------------------------------
-- CONTRACT/AGREEMENT STATUS
-- ---------------------------------------------------------------------------
INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('contract_status', 'Contract Status', 'draft', 'Draft', '#6c757d', 10, 1, NOW()),
('contract_status', 'Contract Status', 'pending_review', 'Pending Review', '#ffc107', 20, 1, NOW()),
('contract_status', 'Contract Status', 'pending_signature', 'Pending Signature', '#17a2b8', 30, 1, NOW()),
('contract_status', 'Contract Status', 'active', 'Active', '#28a745', 40, 1, NOW()),
('contract_status', 'Contract Status', 'suspended', 'Suspended', '#fd7e14', 50, 1, NOW()),
('contract_status', 'Contract Status', 'expired', 'Expired', '#dc3545', 60, 1, NOW()),
('contract_status', 'Contract Status', 'terminated', 'Terminated', '#343a40', 70, 1, NOW()),
('contract_status', 'Contract Status', 'renewed', 'Renewed', '#007bff', 80, 1, NOW()),
('contract_status', 'Contract Status', 'superseded', 'Superseded', '#868e96', 90, 1, NOW());

-- ---------------------------------------------------------------------------
-- PRIORITY LEVELS
-- ---------------------------------------------------------------------------
INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('priority_level', 'Priority Level', 'low', 'Low', '#28a745', 10, 1, NOW()),
('priority_level', 'Priority Level', 'normal', 'Normal', '#007bff', 20, 1, NOW()),
('priority_level', 'Priority Level', 'high', 'High', '#fd7e14', 30, 1, NOW()),
('priority_level', 'Priority Level', 'urgent', 'Urgent', '#dc3545', 40, 1, NOW());

-- ---------------------------------------------------------------------------
-- RISK/SEVERITY LEVELS
-- ---------------------------------------------------------------------------
INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('risk_level', 'Risk Level', 'low', 'Low', '#28a745', 10, 1, NOW()),
('risk_level', 'Risk Level', 'medium', 'Medium', '#ffc107', 20, 1, NOW()),
('risk_level', 'Risk Level', 'high', 'High', '#fd7e14', 30, 1, NOW()),
('risk_level', 'Risk Level', 'critical', 'Critical', '#dc3545', 40, 1, NOW());

-- ---------------------------------------------------------------------------
-- CONDITION GRADES
-- ---------------------------------------------------------------------------
INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('condition_grade', 'Condition Grade', 'excellent', 'Excellent', '#28a745', 10, 1, NOW()),
('condition_grade', 'Condition Grade', 'good', 'Good', '#20c997', 20, 1, NOW()),
('condition_grade', 'Condition Grade', 'fair', 'Fair', '#ffc107', 30, 1, NOW()),
('condition_grade', 'Condition Grade', 'poor', 'Poor', '#fd7e14', 40, 1, NOW()),
('condition_grade', 'Condition Grade', 'critical', 'Critical', '#dc3545', 50, 1, NOW()),
('condition_grade', 'Condition Grade', 'unacceptable', 'Unacceptable', '#343a40', 60, 1, NOW());

-- ---------------------------------------------------------------------------
-- WORKFLOW TASK STATUS
-- ---------------------------------------------------------------------------
INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('workflow_task_status', 'Workflow Task Status', 'pending', 'Pending', '#6c757d', 10, 1, NOW()),
('workflow_task_status', 'Workflow Task Status', 'claimed', 'Claimed', '#17a2b8', 20, 1, NOW()),
('workflow_task_status', 'Workflow Task Status', 'in_progress', 'In Progress', '#007bff', 30, 1, NOW()),
('workflow_task_status', 'Workflow Task Status', 'approved', 'Approved', '#28a745', 40, 1, NOW()),
('workflow_task_status', 'Workflow Task Status', 'rejected', 'Rejected', '#dc3545', 50, 1, NOW()),
('workflow_task_status', 'Workflow Task Status', 'returned', 'Returned', '#fd7e14', 60, 1, NOW()),
('workflow_task_status', 'Workflow Task Status', 'escalated', 'Escalated', '#e83e8c', 70, 1, NOW()),
('workflow_task_status', 'Workflow Task Status', 'cancelled', 'Cancelled', '#6c757d', 80, 1, NOW());

-- ---------------------------------------------------------------------------
-- VENDOR STATUS
-- ---------------------------------------------------------------------------
INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('vendor_status', 'Vendor Status', 'active', 'Active', '#28a745', 10, 1, NOW()),
('vendor_status', 'Vendor Status', 'inactive', 'Inactive', '#6c757d', 20, 1, NOW()),
('vendor_status', 'Vendor Status', 'suspended', 'Suspended', '#dc3545', 30, 1, NOW()),
('vendor_status', 'Vendor Status', 'pending_approval', 'Pending Approval', '#ffc107', 40, 1, NOW());

-- ---------------------------------------------------------------------------
-- VENDOR TYPE
-- ---------------------------------------------------------------------------
INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('vendor_type', 'Vendor Type', 'company', 'Company', '#007bff', 10, 1, NOW()),
('vendor_type', 'Vendor Type', 'individual', 'Individual', '#28a745', 20, 1, NOW()),
('vendor_type', 'Vendor Type', 'institution', 'Institution', '#6f42c1', 30, 1, NOW()),
('vendor_type', 'Vendor Type', 'government', 'Government', '#fd7e14', 40, 1, NOW());

-- ---------------------------------------------------------------------------
-- COUNTERPARTY TYPE (contracts)
-- ---------------------------------------------------------------------------
INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('counterparty_type', 'Counterparty Type', 'vendor', 'Vendor/Supplier', '#007bff', 10, 1, NOW()),
('counterparty_type', 'Counterparty Type', 'institution', 'Institution', '#6f42c1', 20, 1, NOW()),
('counterparty_type', 'Counterparty Type', 'individual', 'Individual', '#28a745', 30, 1, NOW()),
('counterparty_type', 'Counterparty Type', 'government', 'Government', '#fd7e14', 40, 1, NOW()),
('counterparty_type', 'Counterparty Type', 'other', 'Other', '#6c757d', 50, 1, NOW());

-- ---------------------------------------------------------------------------
-- PAYMENT FREQUENCY
-- ---------------------------------------------------------------------------
INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('payment_frequency', 'Payment Frequency', 'once', 'Once', '#6c757d', 10, 1, NOW()),
('payment_frequency', 'Payment Frequency', 'monthly', 'Monthly', '#007bff', 20, 1, NOW()),
('payment_frequency', 'Payment Frequency', 'quarterly', 'Quarterly', '#17a2b8', 30, 1, NOW()),
('payment_frequency', 'Payment Frequency', 'annually', 'Annually', '#28a745', 40, 1, NOW()),
('payment_frequency', 'Payment Frequency', 'on_delivery', 'On Delivery', '#fd7e14', 50, 1, NOW());

-- ---------------------------------------------------------------------------
-- RECURRENCE PATTERN
-- ---------------------------------------------------------------------------
INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('recurrence_pattern', 'Recurrence Pattern', 'daily', 'Daily', '#dc3545', 10, 1, NOW()),
('recurrence_pattern', 'Recurrence Pattern', 'weekly', 'Weekly', '#fd7e14', 20, 1, NOW()),
('recurrence_pattern', 'Recurrence Pattern', 'monthly', 'Monthly', '#ffc107', 30, 1, NOW()),
('recurrence_pattern', 'Recurrence Pattern', 'quarterly', 'Quarterly', '#28a745', 40, 1, NOW()),
('recurrence_pattern', 'Recurrence Pattern', 'yearly', 'Yearly', '#007bff', 50, 1, NOW());

-- ---------------------------------------------------------------------------
-- REMINDER STATUS
-- ---------------------------------------------------------------------------
INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('reminder_status', 'Reminder Status', 'active', 'Active', '#28a745', 10, 1, NOW()),
('reminder_status', 'Reminder Status', 'snoozed', 'Snoozed', '#ffc107', 20, 1, NOW()),
('reminder_status', 'Reminder Status', 'completed', 'Completed', '#6c757d', 30, 1, NOW()),
('reminder_status', 'Reminder Status', 'cancelled', 'Cancelled', '#dc3545', 40, 1, NOW());

-- ---------------------------------------------------------------------------
-- CONTRACT REMINDER TYPE
-- ---------------------------------------------------------------------------
INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('contract_reminder_type', 'Contract Reminder Type', 'expiry_warning', 'Expiry Warning', '#dc3545', 10, 1, NOW()),
('contract_reminder_type', 'Contract Reminder Type', 'review_due', 'Review Due', '#ffc107', 20, 1, NOW()),
('contract_reminder_type', 'Contract Reminder Type', 'renewal_required', 'Renewal Required', '#fd7e14', 30, 1, NOW()),
('contract_reminder_type', 'Contract Reminder Type', 'payment_due', 'Payment Due', '#007bff', 40, 1, NOW()),
('contract_reminder_type', 'Contract Reminder Type', 'deliverable_due', 'Deliverable Due', '#17a2b8', 50, 1, NOW()),
('contract_reminder_type', 'Contract Reminder Type', 'compliance_check', 'Compliance Check', '#6f42c1', 60, 1, NOW()),
('contract_reminder_type', 'Contract Reminder Type', 'insurance_expiry', 'Insurance Expiry', '#e83e8c', 70, 1, NOW()),
('contract_reminder_type', 'Contract Reminder Type', 'audit', 'Audit', '#20c997', 80, 1, NOW()),
('contract_reminder_type', 'Contract Reminder Type', 'custom', 'Custom', '#6c757d', 90, 1, NOW());

-- ---------------------------------------------------------------------------
-- CONTRACT DOCUMENT TYPE
-- ---------------------------------------------------------------------------
INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('contract_document_type', 'Contract Document Type', 'signed_contract', 'Signed Contract', '#28a745', 10, 1, NOW()),
('contract_document_type', 'Contract Document Type', 'draft', 'Draft', '#6c757d', 20, 1, NOW()),
('contract_document_type', 'Contract Document Type', 'amendment', 'Amendment', '#007bff', 30, 1, NOW()),
('contract_document_type', 'Contract Document Type', 'addendum', 'Addendum', '#17a2b8', 40, 1, NOW()),
('contract_document_type', 'Contract Document Type', 'schedule', 'Schedule', '#6f42c1', 50, 1, NOW()),
('contract_document_type', 'Contract Document Type', 'annexure', 'Annexure', '#fd7e14', 60, 1, NOW()),
('contract_document_type', 'Contract Document Type', 'correspondence', 'Correspondence', '#ffc107', 70, 1, NOW()),
('contract_document_type', 'Contract Document Type', 'quote', 'Quote', '#20c997', 80, 1, NOW()),
('contract_document_type', 'Contract Document Type', 'invoice', 'Invoice', '#e83e8c', 90, 1, NOW()),
('contract_document_type', 'Contract Document Type', 'certificate', 'Certificate', '#343a40', 100, 1, NOW()),
('contract_document_type', 'Contract Document Type', 'insurance', 'Insurance', '#dc3545', 110, 1, NOW()),
('contract_document_type', 'Contract Document Type', 'legal_opinion', 'Legal Opinion', '#007bff', 120, 1, NOW()),
('contract_document_type', 'Contract Document Type', 'other', 'Other', '#868e96', 130, 1, NOW());

-- ---------------------------------------------------------------------------
-- LOAN TYPE
-- ---------------------------------------------------------------------------
INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('loan_type', 'Loan Type', 'out', 'Outgoing', '#fd7e14', 10, 1, NOW()),
('loan_type', 'Loan Type', 'in', 'Incoming', '#28a745', 20, 1, NOW());

-- ---------------------------------------------------------------------------
-- LOAN OBJECT STATUS
-- ---------------------------------------------------------------------------
INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('loan_object_status', 'Loan Object Status', 'pending', 'Pending', '#6c757d', 10, 1, NOW()),
('loan_object_status', 'Loan Object Status', 'approved', 'Approved', '#28a745', 20, 1, NOW()),
('loan_object_status', 'Loan Object Status', 'prepared', 'Prepared', '#17a2b8', 30, 1, NOW()),
('loan_object_status', 'Loan Object Status', 'dispatched', 'Dispatched', '#007bff', 40, 1, NOW()),
('loan_object_status', 'Loan Object Status', 'received', 'Received', '#20c997', 50, 1, NOW()),
('loan_object_status', 'Loan Object Status', 'on_display', 'On Display', '#6f42c1', 60, 1, NOW()),
('loan_object_status', 'Loan Object Status', 'packed', 'Packed', '#fd7e14', 70, 1, NOW()),
('loan_object_status', 'Loan Object Status', 'returned', 'Returned', '#343a40', 80, 1, NOW());

-- ---------------------------------------------------------------------------
-- SHIPMENT TYPE
-- ---------------------------------------------------------------------------
INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('shipment_type', 'Shipment Type', 'outbound', 'Outbound', '#fd7e14', 10, 1, NOW()),
('shipment_type', 'Shipment Type', 'return', 'Return', '#28a745', 20, 1, NOW());

-- ---------------------------------------------------------------------------
-- SHIPMENT STATUS
-- ---------------------------------------------------------------------------
INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('shipment_status', 'Shipment Status', 'planned', 'Planned', '#6c757d', 10, 1, NOW()),
('shipment_status', 'Shipment Status', 'picked_up', 'Picked Up', '#17a2b8', 20, 1, NOW()),
('shipment_status', 'Shipment Status', 'in_transit', 'In Transit', '#007bff', 30, 1, NOW()),
('shipment_status', 'Shipment Status', 'customs', 'Customs', '#ffc107', 40, 1, NOW()),
('shipment_status', 'Shipment Status', 'out_for_delivery', 'Out for Delivery', '#fd7e14', 50, 1, NOW()),
('shipment_status', 'Shipment Status', 'delivered', 'Delivered', '#28a745', 60, 1, NOW()),
('shipment_status', 'Shipment Status', 'failed', 'Failed', '#dc3545', 70, 1, NOW()),
('shipment_status', 'Shipment Status', 'returned', 'Returned', '#343a40', 80, 1, NOW());

-- ---------------------------------------------------------------------------
-- INSURANCE TYPE (loans)
-- ---------------------------------------------------------------------------
INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('loan_insurance_type', 'Loan Insurance Type', 'borrower', 'Borrower', '#007bff', 10, 1, NOW()),
('loan_insurance_type', 'Loan Insurance Type', 'lender', 'Lender', '#28a745', 20, 1, NOW()),
('loan_insurance_type', 'Loan Insurance Type', 'shared', 'Shared', '#6f42c1', 30, 1, NOW()),
('loan_insurance_type', 'Loan Insurance Type', 'government', 'Government', '#fd7e14', 40, 1, NOW()),
('loan_insurance_type', 'Loan Insurance Type', 'self', 'Self-Insured', '#ffc107', 50, 1, NOW()),
('loan_insurance_type', 'Loan Insurance Type', 'none', 'None', '#dc3545', 60, 1, NOW());

-- ---------------------------------------------------------------------------
-- GLAM SECTOR
-- ---------------------------------------------------------------------------
INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('glam_sector', 'GLAM Sector', 'archive', 'Archive', '#007bff', 10, 1, NOW()),
('glam_sector', 'GLAM Sector', 'library', 'Library', '#28a745', 20, 1, NOW()),
('glam_sector', 'GLAM Sector', 'museum', 'Museum', '#6f42c1', 30, 1, NOW()),
('glam_sector', 'GLAM Sector', 'gallery', 'Gallery', '#fd7e14', 40, 1, NOW()),
('glam_sector', 'GLAM Sector', 'dam', 'Digital Asset Management', '#17a2b8', 50, 1, NOW());

-- ---------------------------------------------------------------------------
-- NOTIFICATION STATUS
-- ---------------------------------------------------------------------------
INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('notification_status', 'Notification Status', 'pending', 'Pending', '#ffc107', 10, 1, NOW()),
('notification_status', 'Notification Status', 'sent', 'Sent', '#28a745', 20, 1, NOW()),
('notification_status', 'Notification Status', 'failed', 'Failed', '#dc3545', 30, 1, NOW()),
('notification_status', 'Notification Status', 'bounced', 'Bounced', '#fd7e14', 40, 1, NOW()),
('notification_status', 'Notification Status', 'cancelled', 'Cancelled', '#6c757d', 50, 1, NOW());

-- ---------------------------------------------------------------------------
-- FORM FIELD TYPE
-- ---------------------------------------------------------------------------
INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('form_field_type', 'Form Field Type', 'text', 'Text', '#007bff', 10, 1, NOW()),
('form_field_type', 'Form Field Type', 'textarea', 'Textarea', '#28a745', 20, 1, NOW()),
('form_field_type', 'Form Field Type', 'richtext', 'Rich Text', '#6f42c1', 30, 1, NOW()),
('form_field_type', 'Form Field Type', 'date', 'Date', '#fd7e14', 40, 1, NOW()),
('form_field_type', 'Form Field Type', 'daterange', 'Date Range', '#ffc107', 50, 1, NOW()),
('form_field_type', 'Form Field Type', 'select', 'Select', '#17a2b8', 60, 1, NOW()),
('form_field_type', 'Form Field Type', 'multiselect', 'Multi-select', '#20c997', 70, 1, NOW()),
('form_field_type', 'Form Field Type', 'autocomplete', 'Autocomplete', '#e83e8c', 80, 1, NOW()),
('form_field_type', 'Form Field Type', 'checkbox', 'Checkbox', '#343a40', 90, 1, NOW()),
('form_field_type', 'Form Field Type', 'radio', 'Radio', '#6c757d', 100, 1, NOW()),
('form_field_type', 'Form Field Type', 'file', 'File Upload', '#dc3545', 110, 1, NOW()),
('form_field_type', 'Form Field Type', 'hidden', 'Hidden', '#868e96', 120, 1, NOW()),
('form_field_type', 'Form Field Type', 'heading', 'Heading', '#495057', 130, 1, NOW()),
('form_field_type', 'Form Field Type', 'divider', 'Divider', '#adb5bd', 140, 1, NOW());

-- ---------------------------------------------------------------------------
-- FORM FIELD WIDTH
-- ---------------------------------------------------------------------------
INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('form_field_width', 'Form Field Width', 'full', 'Full Width', '#007bff', 10, 1, NOW()),
('form_field_width', 'Form Field Width', 'half', 'Half Width', '#28a745', 20, 1, NOW()),
('form_field_width', 'Form Field Width', 'third', 'One Third', '#ffc107', 30, 1, NOW()),
('form_field_width', 'Form Field Width', 'quarter', 'One Quarter', '#fd7e14', 40, 1, NOW());

-- ---------------------------------------------------------------------------
-- DOI STATUS
-- ---------------------------------------------------------------------------
INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('doi_status', 'DOI Status', 'draft', 'Draft', '#6c757d', 10, 1, NOW()),
('doi_status', 'DOI Status', 'registered', 'Registered', '#17a2b8', 20, 1, NOW()),
('doi_status', 'DOI Status', 'findable', 'Findable', '#28a745', 30, 1, NOW()),
('doi_status', 'DOI Status', 'failed', 'Failed', '#dc3545', 40, 1, NOW()),
('doi_status', 'DOI Status', 'deleted', 'Deleted', '#343a40', 50, 1, NOW());

-- ---------------------------------------------------------------------------
-- WEBHOOK DELIVERY STATUS
-- ---------------------------------------------------------------------------
INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('webhook_status', 'Webhook Status', 'pending', 'Pending', '#ffc107', 10, 1, NOW()),
('webhook_status', 'Webhook Status', 'success', 'Success', '#28a745', 20, 1, NOW()),
('webhook_status', 'Webhook Status', 'failed', 'Failed', '#dc3545', 30, 1, NOW()),
('webhook_status', 'Webhook Status', 'retrying', 'Retrying', '#fd7e14', 40, 1, NOW());

-- ---------------------------------------------------------------------------
-- NER ENTITY CORRECTION TYPE
-- ---------------------------------------------------------------------------
INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('ner_correction_type', 'NER Correction Type', 'none', 'None', '#6c757d', 10, 1, NOW()),
('ner_correction_type', 'NER Correction Type', 'value_edit', 'Value Edited', '#17a2b8', 20, 1, NOW()),
('ner_correction_type', 'NER Correction Type', 'type_change', 'Type Changed', '#fd7e14', 30, 1, NOW()),
('ner_correction_type', 'NER Correction Type', 'both', 'Both Changed', '#6f42c1', 40, 1, NOW()),
('ner_correction_type', 'NER Correction Type', 'rejected', 'Rejected', '#dc3545', 50, 1, NOW()),
('ner_correction_type', 'NER Correction Type', 'approved', 'Approved', '#28a745', 60, 1, NOW());

-- ---------------------------------------------------------------------------
-- NER ENTITY LINK TYPE
-- ---------------------------------------------------------------------------
INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('ner_link_type', 'NER Link Type', 'exact', 'Exact Match', '#28a745', 10, 1, NOW()),
('ner_link_type', 'NER Link Type', 'fuzzy', 'Fuzzy Match', '#ffc107', 20, 1, NOW()),
('ner_link_type', 'NER Link Type', 'manual', 'Manual', '#007bff', 30, 1, NOW());

-- ---------------------------------------------------------------------------
-- DUPLICATE DETECTION STATUS
-- ---------------------------------------------------------------------------
INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('duplicate_status', 'Duplicate Status', 'pending', 'Pending Review', '#ffc107', 10, 1, NOW()),
('duplicate_status', 'Duplicate Status', 'confirmed', 'Confirmed', '#dc3545', 20, 1, NOW()),
('duplicate_status', 'Duplicate Status', 'dismissed', 'Dismissed', '#6c757d', 30, 1, NOW()),
('duplicate_status', 'Duplicate Status', 'merged', 'Merged', '#28a745', 40, 1, NOW());

-- ---------------------------------------------------------------------------
-- ORDER STATUS
-- ---------------------------------------------------------------------------
INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('order_status', 'Order Status', 'pending', 'Pending', '#ffc107', 10, 1, NOW()),
('order_status', 'Order Status', 'paid', 'Paid', '#28a745', 20, 1, NOW()),
('order_status', 'Order Status', 'processing', 'Processing', '#007bff', 30, 1, NOW()),
('order_status', 'Order Status', 'completed', 'Completed', '#20c997', 40, 1, NOW()),
('order_status', 'Order Status', 'cancelled', 'Cancelled', '#6c757d', 50, 1, NOW()),
('order_status', 'Order Status', 'refunded', 'Refunded', '#dc3545', 60, 1, NOW());

-- ---------------------------------------------------------------------------
-- PAYMENT STATUS
-- ---------------------------------------------------------------------------
INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('payment_status', 'Payment Status', 'pending', 'Pending', '#ffc107', 10, 1, NOW()),
('payment_status', 'Payment Status', 'processing', 'Processing', '#17a2b8', 20, 1, NOW()),
('payment_status', 'Payment Status', 'completed', 'Completed', '#28a745', 30, 1, NOW()),
('payment_status', 'Payment Status', 'failed', 'Failed', '#dc3545', 40, 1, NOW()),
('payment_status', 'Payment Status', 'refunded', 'Refunded', '#fd7e14', 50, 1, NOW()),
('payment_status', 'Payment Status', 'not_invoiced', 'Not Invoiced', '#6c757d', 60, 1, NOW()),
('payment_status', 'Payment Status', 'invoiced', 'Invoiced', '#007bff', 70, 1, NOW()),
('payment_status', 'Payment Status', 'disputed', 'Disputed', '#e83e8c', 80, 1, NOW());

-- ---------------------------------------------------------------------------
-- VENDOR TRANSACTION STATUS
-- ---------------------------------------------------------------------------
INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('vendor_transaction_status', 'Vendor Transaction Status', 'pending_approval', 'Pending Approval', '#ffc107', 10, 1, NOW()),
('vendor_transaction_status', 'Vendor Transaction Status', 'approved', 'Approved', '#28a745', 20, 1, NOW()),
('vendor_transaction_status', 'Vendor Transaction Status', 'dispatched', 'Dispatched', '#007bff', 30, 1, NOW()),
('vendor_transaction_status', 'Vendor Transaction Status', 'received_by_vendor', 'Received by Vendor', '#17a2b8', 40, 1, NOW()),
('vendor_transaction_status', 'Vendor Transaction Status', 'in_progress', 'In Progress', '#6f42c1', 50, 1, NOW()),
('vendor_transaction_status', 'Vendor Transaction Status', 'completed', 'Completed', '#20c997', 60, 1, NOW()),
('vendor_transaction_status', 'Vendor Transaction Status', 'ready_for_collection', 'Ready for Collection', '#fd7e14', 70, 1, NOW()),
('vendor_transaction_status', 'Vendor Transaction Status', 'returned', 'Returned', '#343a40', 80, 1, NOW()),
('vendor_transaction_status', 'Vendor Transaction Status', 'cancelled', 'Cancelled', '#dc3545', 90, 1, NOW());

-- ---------------------------------------------------------------------------
-- WORKFLOW TRIGGER EVENT
-- ---------------------------------------------------------------------------
INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('workflow_trigger', 'Workflow Trigger', 'create', 'On Create', '#28a745', 10, 1, NOW()),
('workflow_trigger', 'Workflow Trigger', 'update', 'On Update', '#007bff', 20, 1, NOW()),
('workflow_trigger', 'Workflow Trigger', 'submit', 'On Submit', '#17a2b8', 30, 1, NOW()),
('workflow_trigger', 'Workflow Trigger', 'publish', 'On Publish', '#6f42c1', 40, 1, NOW()),
('workflow_trigger', 'Workflow Trigger', 'manual', 'Manual', '#6c757d', 50, 1, NOW());

-- ---------------------------------------------------------------------------
-- WORKFLOW SCOPE TYPE
-- ---------------------------------------------------------------------------
INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('workflow_scope', 'Workflow Scope', 'global', 'Global', '#dc3545', 10, 1, NOW()),
('workflow_scope', 'Workflow Scope', 'repository', 'Repository', '#007bff', 20, 1, NOW()),
('workflow_scope', 'Workflow Scope', 'collection', 'Collection', '#28a745', 30, 1, NOW());

-- ---------------------------------------------------------------------------
-- WORKFLOW APPLIES TO
-- ---------------------------------------------------------------------------
INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('workflow_applies_to', 'Workflow Applies To', 'information_object', 'Information Object', '#007bff', 10, 1, NOW()),
('workflow_applies_to', 'Workflow Applies To', 'actor', 'Actor', '#28a745', 20, 1, NOW()),
('workflow_applies_to', 'Workflow Applies To', 'accession', 'Accession', '#6f42c1', 30, 1, NOW()),
('workflow_applies_to', 'Workflow Applies To', 'digital_object', 'Digital Object', '#fd7e14', 40, 1, NOW());

-- ---------------------------------------------------------------------------
-- WORKFLOW STEP TYPE
-- ---------------------------------------------------------------------------
INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('workflow_step_type', 'Workflow Step Type', 'review', 'Review', '#007bff', 10, 1, NOW()),
('workflow_step_type', 'Workflow Step Type', 'approve', 'Approve', '#28a745', 20, 1, NOW()),
('workflow_step_type', 'Workflow Step Type', 'edit', 'Edit', '#ffc107', 30, 1, NOW()),
('workflow_step_type', 'Workflow Step Type', 'verify', 'Verify', '#17a2b8', 40, 1, NOW()),
('workflow_step_type', 'Workflow Step Type', 'sign_off', 'Sign Off', '#6f42c1', 50, 1, NOW()),
('workflow_step_type', 'Workflow Step Type', 'custom', 'Custom', '#6c757d', 60, 1, NOW());

-- ---------------------------------------------------------------------------
-- WORKFLOW ACTION REQUIRED
-- ---------------------------------------------------------------------------
INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('workflow_action', 'Workflow Action', 'approve', 'Approve', '#28a745', 10, 1, NOW()),
('workflow_action', 'Workflow Action', 'reject', 'Reject', '#dc3545', 20, 1, NOW()),
('workflow_action', 'Workflow Action', 'approve_reject', 'Approve/Reject', '#ffc107', 30, 1, NOW()),
('workflow_action', 'Workflow Action', 'complete', 'Complete', '#007bff', 40, 1, NOW()),
('workflow_action', 'Workflow Action', 'submit', 'Submit', '#17a2b8', 50, 1, NOW());

-- ---------------------------------------------------------------------------
-- SPELLCHECK RESULT STATUS
-- ---------------------------------------------------------------------------
INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('spellcheck_status', 'Spellcheck Status', 'pending', 'Pending', '#ffc107', 10, 1, NOW()),
('spellcheck_status', 'Spellcheck Status', 'reviewed', 'Reviewed', '#28a745', 20, 1, NOW()),
('spellcheck_status', 'Spellcheck Status', 'ignored', 'Ignored', '#6c757d', 30, 1, NOW());

-- ---------------------------------------------------------------------------
-- TRANSLATION STATUS
-- ---------------------------------------------------------------------------
INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('translation_status', 'Translation Status', 'draft', 'Draft', '#6c757d', 10, 1, NOW()),
('translation_status', 'Translation Status', 'applied', 'Applied', '#28a745', 20, 1, NOW()),
('translation_status', 'Translation Status', 'rejected', 'Rejected', '#dc3545', 30, 1, NOW());

-- ---------------------------------------------------------------------------
-- SETTING TYPE (for various settings tables)
-- ---------------------------------------------------------------------------
INSERT IGNORE INTO ahg_dropdown (taxonomy, taxonomy_label, code, label, color, sort_order, is_active, created_at) VALUES
('setting_type', 'Setting Type', 'string', 'String', '#007bff', 10, 1, NOW()),
('setting_type', 'Setting Type', 'integer', 'Integer', '#28a745', 20, 1, NOW()),
('setting_type', 'Setting Type', 'float', 'Float', '#17a2b8', 30, 1, NOW()),
('setting_type', 'Setting Type', 'boolean', 'Boolean', '#ffc107', 40, 1, NOW()),
('setting_type', 'Setting Type', 'json', 'JSON', '#6f42c1', 50, 1, NOW()),
('setting_type', 'Setting Type', 'array', 'Array', '#fd7e14', 60, 1, NOW());

-- ============================================================================
-- STEP 2: View migration statistics
-- ============================================================================
SELECT
    taxonomy,
    taxonomy_label,
    COUNT(*) as term_count
FROM ahg_dropdown
GROUP BY taxonomy, taxonomy_label
ORDER BY taxonomy_label;

-- ============================================================================
-- NOTE: Column ALTER statements will be in a separate file to allow
-- review before execution. Changing ENUM to VARCHAR is destructive.
-- ============================================================================
