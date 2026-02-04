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
