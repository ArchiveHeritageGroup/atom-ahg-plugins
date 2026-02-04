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
