<?php

use Illuminate\Database\Capsule\Manager as DB;

/**
 * Column Discovery Service for Report Builder.
 *
 * Discovers available columns for each data source, including
 * main table columns, i18n columns, and related entity columns.
 */
class ColumnDiscovery
{
    /**
     * Pre-defined column configurations for common data sources.
     * This provides user-friendly labels and appropriate data types.
     *
     * @var array<string, array>
     */
    private static array $columnDefinitions = [
        'information_object' => [
            'main' => [
                'id' => ['label' => 'ID', 'type' => 'integer', 'sortable' => true],
                'identifier' => ['label' => 'Identifier', 'type' => 'string', 'sortable' => true],
                'level_of_description_id' => ['label' => 'Level of Description', 'type' => 'term', 'sortable' => true],
                'repository_id' => ['label' => 'Repository', 'type' => 'repository', 'sortable' => true],
                'parent_id' => ['label' => 'Parent ID', 'type' => 'integer', 'sortable' => true],
                'lft' => ['label' => 'Left Value', 'type' => 'integer', 'sortable' => true, 'hidden' => true],
                'rgt' => ['label' => 'Right Value', 'type' => 'integer', 'sortable' => true, 'hidden' => true],
                'source_culture' => ['label' => 'Source Culture', 'type' => 'string', 'sortable' => true],
            ],
            'i18n' => [
                'title' => ['label' => 'Title', 'type' => 'string', 'sortable' => true, 'searchable' => true],
                'alternate_title' => ['label' => 'Alternate Title', 'type' => 'string', 'sortable' => true],
                'extent_and_medium' => ['label' => 'Extent and Medium', 'type' => 'text'],
                'archival_history' => ['label' => 'Archival History', 'type' => 'text'],
                'acquisition' => ['label' => 'Acquisition', 'type' => 'text'],
                'scope_and_content' => ['label' => 'Scope and Content', 'type' => 'text', 'searchable' => true],
                'appraisal' => ['label' => 'Appraisal', 'type' => 'text'],
                'accruals' => ['label' => 'Accruals', 'type' => 'text'],
                'arrangement' => ['label' => 'Arrangement', 'type' => 'text'],
                'access_conditions' => ['label' => 'Access Conditions', 'type' => 'text'],
                'reproduction_conditions' => ['label' => 'Reproduction Conditions', 'type' => 'text'],
                'physical_characteristics' => ['label' => 'Physical Characteristics', 'type' => 'text'],
                'finding_aids' => ['label' => 'Finding Aids', 'type' => 'text'],
                'location_of_originals' => ['label' => 'Location of Originals', 'type' => 'text'],
                'location_of_copies' => ['label' => 'Location of Copies', 'type' => 'text'],
                'related_units_of_description' => ['label' => 'Related Descriptions', 'type' => 'text'],
                'rules' => ['label' => 'Rules', 'type' => 'text'],
                'sources' => ['label' => 'Sources', 'type' => 'text'],
                'revision_history' => ['label' => 'Revision History', 'type' => 'text'],
            ],
            'object' => [
                'created_at' => ['label' => 'Created At', 'type' => 'datetime', 'sortable' => true],
                'updated_at' => ['label' => 'Updated At', 'type' => 'datetime', 'sortable' => true],
            ],
            'computed' => [
                'publication_status' => ['label' => 'Publication Status', 'type' => 'status', 'sortable' => true],
                'has_digital_object' => ['label' => 'Has Digital Object', 'type' => 'boolean'],
                'child_count' => ['label' => 'Number of Children', 'type' => 'integer', 'sortable' => true],
            ],
        ],
        'actor' => [
            'main' => [
                'id' => ['label' => 'ID', 'type' => 'integer', 'sortable' => true],
                'corporate_body_identifiers' => ['label' => 'Corporate Identifiers', 'type' => 'string'],
                'entity_type_id' => ['label' => 'Entity Type', 'type' => 'term', 'sortable' => true],
                'description_identifier' => ['label' => 'Description Identifier', 'type' => 'string'],
                'parent_id' => ['label' => 'Parent ID', 'type' => 'integer', 'sortable' => true],
                'source_culture' => ['label' => 'Source Culture', 'type' => 'string'],
            ],
            'i18n' => [
                'authorized_form_of_name' => ['label' => 'Authorized Name', 'type' => 'string', 'sortable' => true, 'searchable' => true],
                'dates_of_existence' => ['label' => 'Dates of Existence', 'type' => 'string'],
                'history' => ['label' => 'History', 'type' => 'text'],
                'places' => ['label' => 'Places', 'type' => 'text'],
                'legal_status' => ['label' => 'Legal Status', 'type' => 'text'],
                'functions' => ['label' => 'Functions', 'type' => 'text'],
                'mandates' => ['label' => 'Mandates', 'type' => 'text'],
                'internal_structures' => ['label' => 'Internal Structures', 'type' => 'text'],
                'general_context' => ['label' => 'General Context', 'type' => 'text'],
            ],
            'object' => [
                'created_at' => ['label' => 'Created At', 'type' => 'datetime', 'sortable' => true],
                'updated_at' => ['label' => 'Updated At', 'type' => 'datetime', 'sortable' => true],
            ],
        ],
        'repository' => [
            'main' => [
                'id' => ['label' => 'ID', 'type' => 'integer', 'sortable' => true],
                'identifier' => ['label' => 'Identifier', 'type' => 'string', 'sortable' => true],
                'desc_status_id' => ['label' => 'Description Status', 'type' => 'term'],
                'desc_detail_id' => ['label' => 'Description Detail', 'type' => 'term'],
                'source_culture' => ['label' => 'Source Culture', 'type' => 'string'],
            ],
            // Columns from actor_i18n (Repository extends Actor)
            'actor_i18n' => [
                'authorized_form_of_name' => ['label' => 'Name', 'type' => 'string', 'sortable' => true, 'searchable' => true],
                'history' => ['label' => 'History', 'type' => 'text'],
                'mandates' => ['label' => 'Mandates', 'type' => 'text'],
                'internal_structures' => ['label' => 'Internal Structures', 'type' => 'text'],
            ],
            // Columns from repository_i18n
            'i18n' => [
                'geocultural_context' => ['label' => 'Geocultural Context', 'type' => 'text'],
                'collecting_policies' => ['label' => 'Collecting Policies', 'type' => 'text'],
                'buildings' => ['label' => 'Buildings', 'type' => 'text'],
                'holdings' => ['label' => 'Holdings', 'type' => 'text'],
                'finding_aids' => ['label' => 'Finding Aids', 'type' => 'text'],
                'opening_times' => ['label' => 'Opening Times', 'type' => 'text'],
                'access_conditions' => ['label' => 'Access Conditions', 'type' => 'text'],
                'disabled_access' => ['label' => 'Disabled Access', 'type' => 'text'],
                'research_services' => ['label' => 'Research Services', 'type' => 'text'],
                'reproduction_services' => ['label' => 'Reproduction Services', 'type' => 'text'],
                'public_facilities' => ['label' => 'Public Facilities', 'type' => 'text'],
                'desc_institution_identifier' => ['label' => 'Institution Identifier', 'type' => 'string'],
                'desc_rules' => ['label' => 'Rules', 'type' => 'text'],
                'desc_sources' => ['label' => 'Sources', 'type' => 'text'],
                'desc_revision_history' => ['label' => 'Revision History', 'type' => 'text'],
            ],
            'object' => [
                'created_at' => ['label' => 'Created At', 'type' => 'datetime', 'sortable' => true],
                'updated_at' => ['label' => 'Updated At', 'type' => 'datetime', 'sortable' => true],
            ],
            'computed' => [
                'holdings_count' => ['label' => 'Number of Holdings', 'type' => 'integer', 'sortable' => true],
            ],
        ],
        'accession' => [
            'main' => [
                'id' => ['label' => 'ID', 'type' => 'integer', 'sortable' => true],
                'identifier' => ['label' => 'Identifier', 'type' => 'string', 'sortable' => true],
                'date' => ['label' => 'Accession Date', 'type' => 'date', 'sortable' => true],
                'source_culture' => ['label' => 'Source Culture', 'type' => 'string'],
            ],
            'i18n' => [
                'title' => ['label' => 'Title', 'type' => 'string', 'sortable' => true, 'searchable' => true],
                'archival_history' => ['label' => 'Archival History', 'type' => 'text'],
                'scope_and_content' => ['label' => 'Scope and Content', 'type' => 'text'],
                'appraisal' => ['label' => 'Appraisal', 'type' => 'text'],
                'physical_characteristics' => ['label' => 'Physical Characteristics', 'type' => 'text'],
                'received_extent_units' => ['label' => 'Received Extent Units', 'type' => 'string'],
                'processing_notes' => ['label' => 'Processing Notes', 'type' => 'text'],
                'source_of_acquisition' => ['label' => 'Source of Acquisition', 'type' => 'text'],
                'location_information' => ['label' => 'Location Information', 'type' => 'text'],
            ],
            'object' => [
                'created_at' => ['label' => 'Created At', 'type' => 'datetime', 'sortable' => true],
                'updated_at' => ['label' => 'Updated At', 'type' => 'datetime', 'sortable' => true],
            ],
        ],
        'physical_object' => [
            'main' => [
                'id' => ['label' => 'ID', 'type' => 'integer', 'sortable' => true],
                'type_id' => ['label' => 'Type', 'type' => 'term', 'sortable' => true],
                'source_culture' => ['label' => 'Source Culture', 'type' => 'string'],
            ],
            'i18n' => [
                'name' => ['label' => 'Name', 'type' => 'string', 'sortable' => true, 'searchable' => true],
                'description' => ['label' => 'Description', 'type' => 'text'],
                'location' => ['label' => 'Location', 'type' => 'string', 'sortable' => true],
            ],
            'object' => [
                'created_at' => ['label' => 'Created At', 'type' => 'datetime', 'sortable' => true],
                'updated_at' => ['label' => 'Updated At', 'type' => 'datetime', 'sortable' => true],
            ],
            'computed' => [
                'linked_descriptions_count' => ['label' => 'Linked Descriptions', 'type' => 'integer', 'sortable' => true],
            ],
        ],
        'digital_object' => [
            'main' => [
                'id' => ['label' => 'ID', 'type' => 'integer', 'sortable' => true],
                'information_object_id' => ['label' => 'Related Description', 'type' => 'information_object'],
                'usage_id' => ['label' => 'Usage', 'type' => 'term', 'sortable' => true],
                'media_type_id' => ['label' => 'Media Type', 'type' => 'term', 'sortable' => true],
                'mime_type' => ['label' => 'MIME Type', 'type' => 'string', 'sortable' => true],
                'byte_size' => ['label' => 'File Size (bytes)', 'type' => 'integer', 'sortable' => true],
                'checksum' => ['label' => 'Checksum', 'type' => 'string'],
                'checksum_type' => ['label' => 'Checksum Type', 'type' => 'string'],
                'path' => ['label' => 'Path', 'type' => 'string'],
                'name' => ['label' => 'Filename', 'type' => 'string', 'sortable' => true],
            ],
            'object' => [
                'created_at' => ['label' => 'Created At', 'type' => 'datetime', 'sortable' => true],
                'updated_at' => ['label' => 'Updated At', 'type' => 'datetime', 'sortable' => true],
            ],
        ],
        'donor' => [
            'main' => [
                'id' => ['label' => 'ID', 'type' => 'integer', 'sortable' => true],
            ],
            // Columns from actor_i18n (Donor extends Actor)
            'actor_i18n' => [
                'authorized_form_of_name' => ['label' => 'Name', 'type' => 'string', 'sortable' => true, 'searchable' => true],
                'history' => ['label' => 'History', 'type' => 'text'],
            ],
            // Columns from contact_information (joined via actor_id)
            'contact' => [
                'contact_person' => ['label' => 'Contact Person', 'type' => 'string', 'sortable' => true],
                'email' => ['label' => 'Email', 'type' => 'string', 'sortable' => true],
                'telephone' => ['label' => 'Telephone', 'type' => 'string'],
                'street_address' => ['label' => 'Street Address', 'type' => 'text'],
                'website' => ['label' => 'Website', 'type' => 'string'],
                'postal_code' => ['label' => 'Postal Code', 'type' => 'string'],
                'country_code' => ['label' => 'Country', 'type' => 'string'],
            ],
            'object' => [
                'created_at' => ['label' => 'Created At', 'type' => 'datetime', 'sortable' => true],
                'updated_at' => ['label' => 'Updated At', 'type' => 'datetime', 'sortable' => true],
            ],
        ],
        'function' => [
            'main' => [
                'id' => ['label' => 'ID', 'type' => 'integer', 'sortable' => true],
                'type_id' => ['label' => 'Type', 'type' => 'term', 'sortable' => true],
                'parent_id' => ['label' => 'Parent ID', 'type' => 'integer'],
                'description_status_id' => ['label' => 'Description Status', 'type' => 'term'],
                'description_detail_id' => ['label' => 'Description Detail', 'type' => 'term'],
                'source_culture' => ['label' => 'Source Culture', 'type' => 'string'],
            ],
            'i18n' => [
                'authorized_form_of_name' => ['label' => 'Name', 'type' => 'string', 'sortable' => true, 'searchable' => true],
                'classification' => ['label' => 'Classification', 'type' => 'string'],
                'dates' => ['label' => 'Dates', 'type' => 'string'],
                'description' => ['label' => 'Description', 'type' => 'text'],
                'history' => ['label' => 'History', 'type' => 'text'],
                'legislation' => ['label' => 'Legislation', 'type' => 'text'],
            ],
            'object' => [
                'created_at' => ['label' => 'Created At', 'type' => 'datetime', 'sortable' => true],
                'updated_at' => ['label' => 'Updated At', 'type' => 'datetime', 'sortable' => true],
            ],
        ],
        // Condition Reports
        'condition_report' => [
            'main' => [
                'id' => ['label' => 'ID', 'type' => 'integer', 'sortable' => true],
                'information_object_id' => ['label' => 'Description', 'type' => 'information_object', 'sortable' => true],
                'assessor_user_id' => ['label' => 'Assessor', 'type' => 'user', 'sortable' => true],
                'assessment_date' => ['label' => 'Assessment Date', 'type' => 'date', 'sortable' => true],
                'context' => ['label' => 'Context', 'type' => 'enum', 'sortable' => true],
                'overall_rating' => ['label' => 'Overall Rating', 'type' => 'enum', 'sortable' => true],
                'priority' => ['label' => 'Priority', 'type' => 'enum', 'sortable' => true],
                'summary' => ['label' => 'Summary', 'type' => 'text'],
                'recommendations' => ['label' => 'Recommendations', 'type' => 'text'],
                'next_check_date' => ['label' => 'Next Check Date', 'type' => 'date', 'sortable' => true],
                'environmental_notes' => ['label' => 'Environmental Notes', 'type' => 'text'],
                'handling_notes' => ['label' => 'Handling Notes', 'type' => 'text'],
                'display_notes' => ['label' => 'Display Notes', 'type' => 'text'],
                'storage_notes' => ['label' => 'Storage Notes', 'type' => 'text'],
                'created_at' => ['label' => 'Created At', 'type' => 'datetime', 'sortable' => true],
                'updated_at' => ['label' => 'Updated At', 'type' => 'datetime', 'sortable' => true],
            ],
        ],
        // Library Items
        'library_item' => [
            'main' => [
                'id' => ['label' => 'ID', 'type' => 'integer', 'sortable' => true],
                'information_object_id' => ['label' => 'Description', 'type' => 'information_object', 'sortable' => true],
                'material_type' => ['label' => 'Material Type', 'type' => 'string', 'sortable' => true],
                'call_number' => ['label' => 'Call Number', 'type' => 'string', 'sortable' => true],
                'isbn' => ['label' => 'ISBN', 'type' => 'string', 'sortable' => true],
                'issn' => ['label' => 'ISSN', 'type' => 'string', 'sortable' => true],
                'publisher' => ['label' => 'Publisher', 'type' => 'string', 'sortable' => true],
                'publication_date' => ['label' => 'Publication Date', 'type' => 'string', 'sortable' => true],
                'edition' => ['label' => 'Edition', 'type' => 'string'],
                'pagination' => ['label' => 'Pagination', 'type' => 'string'],
                'language' => ['label' => 'Language', 'type' => 'string'],
                'circulation_status' => ['label' => 'Circulation Status', 'type' => 'enum', 'sortable' => true],
                'total_copies' => ['label' => 'Total Copies', 'type' => 'integer'],
                'available_copies' => ['label' => 'Available Copies', 'type' => 'integer'],
                'created_at' => ['label' => 'Created At', 'type' => 'datetime', 'sortable' => true],
                'updated_at' => ['label' => 'Updated At', 'type' => 'datetime', 'sortable' => true],
            ],
        ],
        // Museum Metadata
        'museum_metadata' => [
            'main' => [
                'id' => ['label' => 'ID', 'type' => 'integer', 'sortable' => true],
                'object_id' => ['label' => 'Description', 'type' => 'information_object', 'sortable' => true],
                'object_type' => ['label' => 'Object Type', 'type' => 'string', 'sortable' => true],
                'materials' => ['label' => 'Materials', 'type' => 'text'],
                'techniques' => ['label' => 'Techniques', 'type' => 'text'],
                'dimensions' => ['label' => 'Dimensions', 'type' => 'string'],
                'inscription' => ['label' => 'Inscription', 'type' => 'text'],
                'provenance_text' => ['label' => 'Provenance', 'type' => 'text'],
                'style_period' => ['label' => 'Style/Period', 'type' => 'string', 'sortable' => true],
                'cultural_context' => ['label' => 'Cultural Context', 'type' => 'string'],
                'condition_term' => ['label' => 'Condition', 'type' => 'string'],
                'created_at' => ['label' => 'Created At', 'type' => 'datetime', 'sortable' => true],
                'updated_at' => ['label' => 'Updated At', 'type' => 'datetime', 'sortable' => true],
            ],
        ],
        // Security Access Log
        'security_access_log' => [
            'main' => [
                'id' => ['label' => 'ID', 'type' => 'integer', 'sortable' => true],
                'user_id' => ['label' => 'User', 'type' => 'user', 'sortable' => true],
                'object_id' => ['label' => 'Description', 'type' => 'information_object', 'sortable' => true],
                'classification_id' => ['label' => 'Classification', 'type' => 'term', 'sortable' => true],
                'action' => ['label' => 'Action', 'type' => 'string', 'sortable' => true],
                'access_granted' => ['label' => 'Access Granted', 'type' => 'boolean', 'sortable' => true],
                'denial_reason' => ['label' => 'Denial Reason', 'type' => 'string'],
                'justification' => ['label' => 'Justification', 'type' => 'text'],
                'ip_address' => ['label' => 'IP Address', 'type' => 'string', 'sortable' => true],
                'user_agent' => ['label' => 'User Agent', 'type' => 'string'],
                'created_at' => ['label' => 'Accessed At', 'type' => 'datetime', 'sortable' => true],
            ],
        ],
        // Provenance Records
        'provenance_record' => [
            'main' => [
                'id' => ['label' => 'ID', 'type' => 'integer', 'sortable' => true],
                'information_object_id' => ['label' => 'Description', 'type' => 'information_object', 'sortable' => true],
                'provenance_agent_id' => ['label' => 'Provenance Agent', 'type' => 'actor', 'sortable' => true],
                'donor_id' => ['label' => 'Donor', 'type' => 'actor', 'sortable' => true],
                'current_status' => ['label' => 'Current Status', 'type' => 'enum', 'sortable' => true],
                'custody_type' => ['label' => 'Custody Type', 'type' => 'enum', 'sortable' => true],
                'acquisition_type' => ['label' => 'Acquisition Type', 'type' => 'enum', 'sortable' => true],
                'acquisition_date' => ['label' => 'Acquisition Date', 'type' => 'date', 'sortable' => true],
                'acquisition_date_text' => ['label' => 'Acquisition Date (Text)', 'type' => 'string'],
                'certainty_level' => ['label' => 'Certainty Level', 'type' => 'enum', 'sortable' => true],
                'research_status' => ['label' => 'Research Status', 'type' => 'enum', 'sortable' => true],
                'is_complete' => ['label' => 'Complete', 'type' => 'boolean'],
                'is_public' => ['label' => 'Public', 'type' => 'boolean'],
                'created_at' => ['label' => 'Created At', 'type' => 'datetime', 'sortable' => true],
                'updated_at' => ['label' => 'Updated At', 'type' => 'datetime', 'sortable' => true],
            ],
        ],
        // ========== PRIVACY & DATA PROTECTION SOURCES ==========
        'privacy_consent_record' => [
            'main' => [
                'id' => ['label' => 'ID', 'type' => 'integer', 'sortable' => true],
                'data_subject_id' => ['label' => 'Subject ID', 'type' => 'string', 'sortable' => true],
                'subject_name' => ['label' => 'Subject Name', 'type' => 'string', 'sortable' => true],
                'subject_email' => ['label' => 'Subject Email', 'type' => 'string', 'sortable' => true],
                'purpose' => ['label' => 'Purpose', 'type' => 'string', 'sortable' => true],
                'consent_given' => ['label' => 'Consent Given', 'type' => 'boolean', 'sortable' => true],
                'consent_method' => ['label' => 'Consent Method', 'type' => 'enum', 'sortable' => true],
                'consent_date' => ['label' => 'Consent Date', 'type' => 'datetime', 'sortable' => true],
                'withdrawal_date' => ['label' => 'Withdrawal Date', 'type' => 'datetime', 'sortable' => true],
                'source' => ['label' => 'Source', 'type' => 'string', 'sortable' => true],
                'jurisdiction' => ['label' => 'Jurisdiction', 'type' => 'enum', 'sortable' => true],
                'status' => ['label' => 'Status', 'type' => 'enum', 'sortable' => true],
                'created_at' => ['label' => 'Created At', 'type' => 'datetime', 'sortable' => true],
            ],
        ],
        'privacy_dsar_request' => [
            'main' => [
                'id' => ['label' => 'ID', 'type' => 'integer', 'sortable' => true],
                'reference' => ['label' => 'Reference', 'type' => 'string', 'sortable' => true],
                'request_type' => ['label' => 'Request Type', 'type' => 'enum', 'sortable' => true],
                'data_subject_name' => ['label' => 'Subject Name', 'type' => 'string', 'sortable' => true],
                'data_subject_email' => ['label' => 'Subject Email', 'type' => 'string', 'sortable' => true],
                'received_date' => ['label' => 'Received Date', 'type' => 'date', 'sortable' => true],
                'deadline_date' => ['label' => 'Deadline', 'type' => 'date', 'sortable' => true],
                'completed_date' => ['label' => 'Completed Date', 'type' => 'date', 'sortable' => true],
                'status' => ['label' => 'Status', 'type' => 'enum', 'sortable' => true],
                'assigned_to' => ['label' => 'Assigned To', 'type' => 'user', 'sortable' => true],
                'notes' => ['label' => 'Notes', 'type' => 'text'],
                'created_at' => ['label' => 'Created At', 'type' => 'datetime', 'sortable' => true],
            ],
        ],
        'privacy_breach' => [
            'main' => [
                'id' => ['label' => 'ID', 'type' => 'integer', 'sortable' => true],
                'reference_number' => ['label' => 'Reference', 'type' => 'string', 'sortable' => true],
                'jurisdiction' => ['label' => 'Jurisdiction', 'type' => 'enum', 'sortable' => true],
                'breach_type' => ['label' => 'Breach Type', 'type' => 'enum', 'sortable' => true],
                'severity' => ['label' => 'Severity', 'type' => 'enum', 'sortable' => true],
                'status' => ['label' => 'Status', 'type' => 'enum', 'sortable' => true],
                'detected_date' => ['label' => 'Detected Date', 'type' => 'datetime', 'sortable' => true],
                'occurred_date' => ['label' => 'Occurred Date', 'type' => 'datetime', 'sortable' => true],
                'contained_date' => ['label' => 'Contained Date', 'type' => 'datetime', 'sortable' => true],
                'resolved_date' => ['label' => 'Resolved Date', 'type' => 'datetime', 'sortable' => true],
                'data_subjects_affected' => ['label' => 'Subjects Affected', 'type' => 'integer', 'sortable' => true],
                'notification_required' => ['label' => 'Notification Required', 'type' => 'boolean'],
                'regulator_notified' => ['label' => 'Regulator Notified', 'type' => 'boolean'],
                'subjects_notified' => ['label' => 'Subjects Notified', 'type' => 'boolean'],
                'risk_to_rights' => ['label' => 'Risk to Rights', 'type' => 'enum', 'sortable' => true],
                'assigned_to' => ['label' => 'Assigned To', 'type' => 'user', 'sortable' => true],
                'created_at' => ['label' => 'Created At', 'type' => 'datetime', 'sortable' => true],
                'updated_at' => ['label' => 'Updated At', 'type' => 'datetime', 'sortable' => true],
            ],
        ],
        // ========== VENDOR MANAGEMENT SOURCES ==========
        'ahg_vendors' => [
            'main' => [
                'id' => ['label' => 'ID', 'type' => 'integer', 'sortable' => true],
                'name' => ['label' => 'Vendor Name', 'type' => 'string', 'sortable' => true],
                'vendor_code' => ['label' => 'Vendor Code', 'type' => 'string', 'sortable' => true],
                'vendor_type' => ['label' => 'Type', 'type' => 'enum', 'sortable' => true],
                'registration_number' => ['label' => 'Registration No.', 'type' => 'string'],
                'vat_number' => ['label' => 'VAT Number', 'type' => 'string'],
                'city' => ['label' => 'City', 'type' => 'string', 'sortable' => true],
                'province' => ['label' => 'Province', 'type' => 'string', 'sortable' => true],
                'country' => ['label' => 'Country', 'type' => 'string', 'sortable' => true],
                'phone' => ['label' => 'Phone', 'type' => 'string'],
                'email' => ['label' => 'Email', 'type' => 'string', 'sortable' => true],
                'website' => ['label' => 'Website', 'type' => 'string'],
                'has_insurance' => ['label' => 'Has Insurance', 'type' => 'boolean'],
                'insurance_expiry_date' => ['label' => 'Insurance Expiry', 'type' => 'date', 'sortable' => true],
                'quality_rating' => ['label' => 'Quality Rating', 'type' => 'integer', 'sortable' => true],
                'reliability_rating' => ['label' => 'Reliability Rating', 'type' => 'integer', 'sortable' => true],
                'price_rating' => ['label' => 'Price Rating', 'type' => 'integer', 'sortable' => true],
                'status' => ['label' => 'Status', 'type' => 'enum', 'sortable' => true],
                'is_preferred' => ['label' => 'Preferred Vendor', 'type' => 'boolean'],
                'is_bbbee_compliant' => ['label' => 'B-BBEE Compliant', 'type' => 'boolean'],
                'created_at' => ['label' => 'Created At', 'type' => 'datetime', 'sortable' => true],
                'updated_at' => ['label' => 'Updated At', 'type' => 'datetime', 'sortable' => true],
            ],
        ],
        'ahg_vendor_transactions' => [
            'main' => [
                'id' => ['label' => 'ID', 'type' => 'integer', 'sortable' => true],
                'vendor_id' => ['label' => 'Vendor', 'type' => 'integer', 'sortable' => true],
                'transaction_type' => ['label' => 'Type', 'type' => 'enum', 'sortable' => true],
                'reference_number' => ['label' => 'Reference', 'type' => 'string', 'sortable' => true],
                'transaction_date' => ['label' => 'Date', 'type' => 'date', 'sortable' => true],
                'due_date' => ['label' => 'Due Date', 'type' => 'date', 'sortable' => true],
                'total_amount' => ['label' => 'Total Amount', 'type' => 'decimal', 'sortable' => true],
                'currency' => ['label' => 'Currency', 'type' => 'string'],
                'status' => ['label' => 'Status', 'type' => 'enum', 'sortable' => true],
                'created_at' => ['label' => 'Created At', 'type' => 'datetime', 'sortable' => true],
            ],
        ],
        // ========== HERITAGE ASSETS (GRAP 103) ==========
        'heritage_asset' => [
            'main' => [
                'id' => ['label' => 'ID', 'type' => 'integer', 'sortable' => true],
                'information_object_id' => ['label' => 'Description', 'type' => 'information_object', 'sortable' => true],
                'recognition_status' => ['label' => 'Recognition Status', 'type' => 'enum', 'sortable' => true],
                'recognition_date' => ['label' => 'Recognition Date', 'type' => 'date', 'sortable' => true],
                'measurement_basis' => ['label' => 'Measurement Basis', 'type' => 'enum', 'sortable' => true],
                'acquisition_method' => ['label' => 'Acquisition Method', 'type' => 'enum', 'sortable' => true],
                'acquisition_date' => ['label' => 'Acquisition Date', 'type' => 'date', 'sortable' => true],
                'acquisition_cost' => ['label' => 'Acquisition Cost', 'type' => 'decimal', 'sortable' => true],
                'current_carrying_amount' => ['label' => 'Current Value', 'type' => 'decimal', 'sortable' => true],
                'accumulated_depreciation' => ['label' => 'Accumulated Depreciation', 'type' => 'decimal', 'sortable' => true],
                'last_valuation_date' => ['label' => 'Last Valuation', 'type' => 'date', 'sortable' => true],
                'last_valuation_amount' => ['label' => 'Valuation Amount', 'type' => 'decimal', 'sortable' => true],
                'valuation_method' => ['label' => 'Valuation Method', 'type' => 'enum', 'sortable' => true],
                'heritage_significance' => ['label' => 'Significance', 'type' => 'enum', 'sortable' => true],
                'condition_rating' => ['label' => 'Condition', 'type' => 'enum', 'sortable' => true],
                'insurance_value' => ['label' => 'Insurance Value', 'type' => 'decimal', 'sortable' => true],
                'insurance_expiry_date' => ['label' => 'Insurance Expiry', 'type' => 'date', 'sortable' => true],
                'current_location' => ['label' => 'Location', 'type' => 'string', 'sortable' => true],
                'created_at' => ['label' => 'Created At', 'type' => 'datetime', 'sortable' => true],
                'updated_at' => ['label' => 'Updated At', 'type' => 'datetime', 'sortable' => true],
            ],
        ],
        // ========== SPECTRUM 5.0 ==========
        'spectrum_valuation' => [
            'main' => [
                'id' => ['label' => 'ID', 'type' => 'integer', 'sortable' => true],
                'object_id' => ['label' => 'Object', 'type' => 'information_object', 'sortable' => true],
                'valuation_reference' => ['label' => 'Reference', 'type' => 'string', 'sortable' => true],
                'valuation_date' => ['label' => 'Valuation Date', 'type' => 'date', 'sortable' => true],
                'valuation_type' => ['label' => 'Type', 'type' => 'enum', 'sortable' => true],
                'valuation_amount' => ['label' => 'Amount', 'type' => 'decimal', 'sortable' => true],
                'valuation_currency' => ['label' => 'Currency', 'type' => 'string'],
                'valuer_name' => ['label' => 'Valuer', 'type' => 'string', 'sortable' => true],
                'valuer_organization' => ['label' => 'Organization', 'type' => 'string', 'sortable' => true],
                'renewal_date' => ['label' => 'Renewal Date', 'type' => 'date', 'sortable' => true],
                'is_current' => ['label' => 'Current', 'type' => 'boolean'],
                'workflow_state' => ['label' => 'Status', 'type' => 'enum', 'sortable' => true],
                'created_at' => ['label' => 'Created At', 'type' => 'datetime', 'sortable' => true],
            ],
        ],
        'spectrum_loan_in' => [
            'main' => [
                'id' => ['label' => 'ID', 'type' => 'integer', 'sortable' => true],
                'object_id' => ['label' => 'Object', 'type' => 'information_object', 'sortable' => true],
                'loan_in_number' => ['label' => 'Loan Number', 'type' => 'string', 'sortable' => true],
                'lender_name' => ['label' => 'Lender', 'type' => 'string', 'sortable' => true],
                'loan_in_date' => ['label' => 'Loan Date', 'type' => 'date', 'sortable' => true],
                'loan_return_date' => ['label' => 'Return Date', 'type' => 'date', 'sortable' => true],
                'loan_purpose' => ['label' => 'Purpose', 'type' => 'enum', 'sortable' => true],
                'insurance_value' => ['label' => 'Insurance Value', 'type' => 'decimal', 'sortable' => true],
                'loan_status' => ['label' => 'Status', 'type' => 'enum', 'sortable' => true],
                'workflow_state' => ['label' => 'Workflow', 'type' => 'enum', 'sortable' => true],
                'contact_person' => ['label' => 'Contact', 'type' => 'string'],
                'contact_email' => ['label' => 'Email', 'type' => 'string'],
                'created_at' => ['label' => 'Created At', 'type' => 'datetime', 'sortable' => true],
            ],
        ],
        'spectrum_loan_out' => [
            'main' => [
                'id' => ['label' => 'ID', 'type' => 'integer', 'sortable' => true],
                'object_id' => ['label' => 'Object', 'type' => 'information_object', 'sortable' => true],
                'loan_out_number' => ['label' => 'Loan Number', 'type' => 'string', 'sortable' => true],
                'borrower_name' => ['label' => 'Borrower', 'type' => 'string', 'sortable' => true],
                'loan_out_date' => ['label' => 'Loan Date', 'type' => 'date', 'sortable' => true],
                'loan_return_date' => ['label' => 'Return Date', 'type' => 'date', 'sortable' => true],
                'loan_purpose' => ['label' => 'Purpose', 'type' => 'enum', 'sortable' => true],
                'insurance_value' => ['label' => 'Insurance Value', 'type' => 'decimal', 'sortable' => true],
                'loan_status' => ['label' => 'Status', 'type' => 'enum', 'sortable' => true],
                'workflow_state' => ['label' => 'Workflow', 'type' => 'enum', 'sortable' => true],
                'created_at' => ['label' => 'Created At', 'type' => 'datetime', 'sortable' => true],
            ],
        ],
        'spectrum_condition_check' => [
            'main' => [
                'id' => ['label' => 'ID', 'type' => 'integer', 'sortable' => true],
                'object_id' => ['label' => 'Object', 'type' => 'information_object', 'sortable' => true],
                'check_date' => ['label' => 'Check Date', 'type' => 'date', 'sortable' => true],
                'checked_by' => ['label' => 'Checked By', 'type' => 'user', 'sortable' => true],
                'condition_status' => ['label' => 'Status', 'type' => 'enum', 'sortable' => true],
                'overall_condition' => ['label' => 'Condition', 'type' => 'enum', 'sortable' => true],
                'next_check_date' => ['label' => 'Next Check', 'type' => 'date', 'sortable' => true],
                'hazards_present' => ['label' => 'Hazards', 'type' => 'boolean'],
                'recommendations' => ['label' => 'Recommendations', 'type' => 'text'],
                'created_at' => ['label' => 'Created At', 'type' => 'datetime', 'sortable' => true],
            ],
        ],
        'spectrum_movement' => [
            'main' => [
                'id' => ['label' => 'ID', 'type' => 'integer', 'sortable' => true],
                'object_id' => ['label' => 'Object', 'type' => 'information_object', 'sortable' => true],
                'movement_date' => ['label' => 'Date', 'type' => 'date', 'sortable' => true],
                'from_location' => ['label' => 'From', 'type' => 'string', 'sortable' => true],
                'to_location' => ['label' => 'To', 'type' => 'string', 'sortable' => true],
                'movement_reason' => ['label' => 'Reason', 'type' => 'enum', 'sortable' => true],
                'moved_by' => ['label' => 'Moved By', 'type' => 'user', 'sortable' => true],
                'created_at' => ['label' => 'Created At', 'type' => 'datetime', 'sortable' => true],
            ],
        ],
        // ========== ADDITIONAL PRIVACY ==========
        'privacy_processing_activity' => [
            'main' => [
                'id' => ['label' => 'ID', 'type' => 'integer', 'sortable' => true],
                'name' => ['label' => 'Activity Name', 'type' => 'string', 'sortable' => true],
                'jurisdiction' => ['label' => 'Jurisdiction', 'type' => 'enum', 'sortable' => true],
                'purpose' => ['label' => 'Purpose', 'type' => 'text'],
                'lawful_basis' => ['label' => 'Lawful Basis', 'type' => 'enum', 'sortable' => true],
                'data_categories' => ['label' => 'Data Categories', 'type' => 'text'],
                'data_subjects' => ['label' => 'Data Subjects', 'type' => 'text'],
                'recipients' => ['label' => 'Recipients', 'type' => 'text'],
                'retention_period' => ['label' => 'Retention', 'type' => 'string'],
                'dpia_required' => ['label' => 'DPIA Required', 'type' => 'boolean'],
                'dpia_completed' => ['label' => 'DPIA Done', 'type' => 'boolean'],
                'status' => ['label' => 'Status', 'type' => 'enum', 'sortable' => true],
                'owner' => ['label' => 'Owner', 'type' => 'string', 'sortable' => true],
                'department' => ['label' => 'Department', 'type' => 'string', 'sortable' => true],
                'next_review_date' => ['label' => 'Next Review', 'type' => 'date', 'sortable' => true],
                'created_at' => ['label' => 'Created At', 'type' => 'datetime', 'sortable' => true],
            ],
        ],
        'privacy_paia_request' => [
            'main' => [
                'id' => ['label' => 'ID', 'type' => 'integer', 'sortable' => true],
                'reference_number' => ['label' => 'Reference', 'type' => 'string', 'sortable' => true],
                'paia_section' => ['label' => 'PAIA Section', 'type' => 'enum', 'sortable' => true],
                'requestor_name' => ['label' => 'Requestor', 'type' => 'string', 'sortable' => true],
                'requestor_email' => ['label' => 'Email', 'type' => 'string'],
                'access_form' => ['label' => 'Access Form', 'type' => 'enum', 'sortable' => true],
                'status' => ['label' => 'Status', 'type' => 'enum', 'sortable' => true],
                'refusal_grounds' => ['label' => 'Refusal Grounds', 'type' => 'string'],
                'fee_deposit' => ['label' => 'Deposit Fee', 'type' => 'decimal'],
                'fee_access' => ['label' => 'Access Fee', 'type' => 'decimal'],
                'fee_paid' => ['label' => 'Fee Paid', 'type' => 'boolean'],
                'received_date' => ['label' => 'Received', 'type' => 'date', 'sortable' => true],
                'due_date' => ['label' => 'Due Date', 'type' => 'date', 'sortable' => true],
                'completed_date' => ['label' => 'Completed', 'type' => 'date', 'sortable' => true],
                'assigned_to' => ['label' => 'Assigned To', 'type' => 'user', 'sortable' => true],
                'created_at' => ['label' => 'Created At', 'type' => 'datetime', 'sortable' => true],
            ],
        ],
        // ========== RIGHTS MANAGEMENT ==========
        'rights_record' => [
            'main' => [
                'id' => ['label' => 'ID', 'type' => 'integer', 'sortable' => true],
                'object_id' => ['label' => 'Object', 'type' => 'information_object', 'sortable' => true],
                'basis' => ['label' => 'Rights Basis', 'type' => 'enum', 'sortable' => true],
                'copyright_status' => ['label' => 'Copyright Status', 'type' => 'enum', 'sortable' => true],
                'copyright_holder' => ['label' => 'Copyright Holder', 'type' => 'string', 'sortable' => true],
                'copyright_jurisdiction' => ['label' => 'Jurisdiction', 'type' => 'string'],
                'license_identifier' => ['label' => 'License', 'type' => 'string'],
                'donor_name' => ['label' => 'Donor', 'type' => 'string'],
                'start_date' => ['label' => 'Start Date', 'type' => 'date', 'sortable' => true],
                'end_date' => ['label' => 'End Date', 'type' => 'date', 'sortable' => true],
                'created_at' => ['label' => 'Created At', 'type' => 'datetime', 'sortable' => true],
                'updated_at' => ['label' => 'Updated At', 'type' => 'datetime', 'sortable' => true],
            ],
        ],
        'rights' => [
            'main' => [
                'id' => ['label' => 'ID', 'type' => 'integer', 'sortable' => true],
                'start_date' => ['label' => 'Start Date', 'type' => 'date', 'sortable' => true],
                'end_date' => ['label' => 'End Date', 'type' => 'date', 'sortable' => true],
            ],
            'i18n' => [
                'rights_holder' => ['label' => 'Rights Holder', 'type' => 'string', 'sortable' => true],
                'rights_note' => ['label' => 'Note', 'type' => 'text'],
                'copyright_status' => ['label' => 'Copyright Status', 'type' => 'string'],
                'license_identifier' => ['label' => 'License', 'type' => 'string'],
                'license_terms' => ['label' => 'Terms', 'type' => 'text'],
            ],
            'object' => [
                'created_at' => ['label' => 'Created At', 'type' => 'datetime', 'sortable' => true],
                'updated_at' => ['label' => 'Updated At', 'type' => 'datetime', 'sortable' => true],
            ],
        ],
        // ========== GEOSPATIAL SOURCES ==========
        'contact_information' => [
            'main' => [
                'id' => ['label' => 'ID', 'type' => 'integer', 'sortable' => true],
                'actor_id' => ['label' => 'Actor ID', 'type' => 'integer', 'sortable' => true],
                'primary_contact' => ['label' => 'Primary Contact', 'type' => 'boolean'],
                'contact_person' => ['label' => 'Contact Person', 'type' => 'string', 'sortable' => true],
                'street_address' => ['label' => 'Street Address', 'type' => 'string'],
                'city' => ['label' => 'City', 'type' => 'string', 'sortable' => true],
                'region' => ['label' => 'Region/Province', 'type' => 'string', 'sortable' => true],
                'country_code' => ['label' => 'Country Code', 'type' => 'string', 'sortable' => true],
                'postal_code' => ['label' => 'Postal Code', 'type' => 'string'],
                'telephone' => ['label' => 'Telephone', 'type' => 'string'],
                'fax' => ['label' => 'Fax', 'type' => 'string'],
                'email' => ['label' => 'Email', 'type' => 'string', 'sortable' => true],
                'website' => ['label' => 'Website', 'type' => 'string'],
                'latitude' => ['label' => 'Latitude', 'type' => 'decimal', 'sortable' => true],
                'longitude' => ['label' => 'Longitude', 'type' => 'decimal', 'sortable' => true],
                'source_culture' => ['label' => 'Source Culture', 'type' => 'string'],
                'created_at' => ['label' => 'Created At', 'type' => 'datetime', 'sortable' => true],
                'updated_at' => ['label' => 'Updated At', 'type' => 'datetime', 'sortable' => true],
            ],
        ],
        'digital_object_metadata' => [
            'main' => [
                'id' => ['label' => 'ID', 'type' => 'integer', 'sortable' => true],
                'digital_object_id' => ['label' => 'Digital Object', 'type' => 'integer', 'sortable' => true],
                'gps_latitude' => ['label' => 'GPS Latitude', 'type' => 'decimal', 'sortable' => true],
                'gps_longitude' => ['label' => 'GPS Longitude', 'type' => 'decimal', 'sortable' => true],
                'camera_make' => ['label' => 'Camera Make', 'type' => 'string', 'sortable' => true],
                'camera_model' => ['label' => 'Camera Model', 'type' => 'string', 'sortable' => true],
                'date_taken' => ['label' => 'Date Taken', 'type' => 'datetime', 'sortable' => true],
                'image_width' => ['label' => 'Width (px)', 'type' => 'integer', 'sortable' => true],
                'image_height' => ['label' => 'Height (px)', 'type' => 'integer', 'sortable' => true],
                'exposure_time' => ['label' => 'Exposure Time', 'type' => 'string'],
                'f_number' => ['label' => 'F-Number', 'type' => 'string'],
                'iso_speed' => ['label' => 'ISO Speed', 'type' => 'integer'],
                'focal_length' => ['label' => 'Focal Length', 'type' => 'string'],
                'color_space' => ['label' => 'Color Space', 'type' => 'string'],
                'orientation' => ['label' => 'Orientation', 'type' => 'string'],
                'software' => ['label' => 'Software', 'type' => 'string'],
                'created_at' => ['label' => 'Created At', 'type' => 'datetime', 'sortable' => true],
            ],
        ],
        'dam_iptc_metadata' => [
            'main' => [
                'id' => ['label' => 'ID', 'type' => 'integer', 'sortable' => true],
                'digital_object_id' => ['label' => 'Digital Object', 'type' => 'integer', 'sortable' => true],
                'object_name' => ['label' => 'Object Name', 'type' => 'string', 'sortable' => true],
                'headline' => ['label' => 'Headline', 'type' => 'string', 'sortable' => true],
                'caption' => ['label' => 'Caption', 'type' => 'text'],
                'byline' => ['label' => 'By-line (Creator)', 'type' => 'string', 'sortable' => true],
                'byline_title' => ['label' => 'By-line Title', 'type' => 'string'],
                'credit' => ['label' => 'Credit', 'type' => 'string'],
                'source' => ['label' => 'Source', 'type' => 'string', 'sortable' => true],
                'copyright_notice' => ['label' => 'Copyright Notice', 'type' => 'string'],
                'keywords' => ['label' => 'Keywords', 'type' => 'text'],
                'category' => ['label' => 'Category', 'type' => 'string', 'sortable' => true],
                'supplemental_categories' => ['label' => 'Supplemental Categories', 'type' => 'text'],
                'city' => ['label' => 'City', 'type' => 'string', 'sortable' => true],
                'province_state' => ['label' => 'Province/State', 'type' => 'string', 'sortable' => true],
                'country' => ['label' => 'Country', 'type' => 'string', 'sortable' => true],
                'country_code' => ['label' => 'Country Code', 'type' => 'string'],
                'gps_latitude' => ['label' => 'GPS Latitude', 'type' => 'decimal', 'sortable' => true],
                'gps_longitude' => ['label' => 'GPS Longitude', 'type' => 'decimal', 'sortable' => true],
                'date_created' => ['label' => 'Date Created', 'type' => 'date', 'sortable' => true],
                'urgency' => ['label' => 'Urgency', 'type' => 'integer'],
                'special_instructions' => ['label' => 'Special Instructions', 'type' => 'text'],
                'created_at' => ['label' => 'Created At', 'type' => 'datetime', 'sortable' => true],
                'updated_at' => ['label' => 'Updated At', 'type' => 'datetime', 'sortable' => true],
            ],
        ],
        'nmmz_archaeological_site' => [
            'main' => [
                'id' => ['label' => 'ID', 'type' => 'integer', 'sortable' => true],
                'information_object_id' => ['label' => 'Description', 'type' => 'information_object', 'sortable' => true],
                'site_name' => ['label' => 'Site Name', 'type' => 'string', 'sortable' => true],
                'site_number' => ['label' => 'Site Number', 'type' => 'string', 'sortable' => true],
                'site_type' => ['label' => 'Site Type', 'type' => 'enum', 'sortable' => true],
                'period' => ['label' => 'Period', 'type' => 'string', 'sortable' => true],
                'culture' => ['label' => 'Culture', 'type' => 'string', 'sortable' => true],
                'province' => ['label' => 'Province', 'type' => 'string', 'sortable' => true],
                'district' => ['label' => 'District', 'type' => 'string', 'sortable' => true],
                'gps_latitude' => ['label' => 'GPS Latitude', 'type' => 'decimal', 'sortable' => true],
                'gps_longitude' => ['label' => 'GPS Longitude', 'type' => 'decimal', 'sortable' => true],
                'elevation' => ['label' => 'Elevation (m)', 'type' => 'integer', 'sortable' => true],
                'area_hectares' => ['label' => 'Area (ha)', 'type' => 'decimal', 'sortable' => true],
                'protection_status' => ['label' => 'Protection Status', 'type' => 'enum', 'sortable' => true],
                'condition' => ['label' => 'Condition', 'type' => 'enum', 'sortable' => true],
                'threat_level' => ['label' => 'Threat Level', 'type' => 'enum', 'sortable' => true],
                'last_survey_date' => ['label' => 'Last Survey', 'type' => 'date', 'sortable' => true],
                'description' => ['label' => 'Description', 'type' => 'text'],
                'created_at' => ['label' => 'Created At', 'type' => 'datetime', 'sortable' => true],
                'updated_at' => ['label' => 'Updated At', 'type' => 'datetime', 'sortable' => true],
            ],
        ],
        'nmmz_monument' => [
            'main' => [
                'id' => ['label' => 'ID', 'type' => 'integer', 'sortable' => true],
                'information_object_id' => ['label' => 'Description', 'type' => 'information_object', 'sortable' => true],
                'monument_name' => ['label' => 'Monument Name', 'type' => 'string', 'sortable' => true],
                'monument_number' => ['label' => 'Monument Number', 'type' => 'string', 'sortable' => true],
                'monument_type' => ['label' => 'Type', 'type' => 'enum', 'sortable' => true],
                'declaration_date' => ['label' => 'Declaration Date', 'type' => 'date', 'sortable' => true],
                'gazette_notice' => ['label' => 'Gazette Notice', 'type' => 'string'],
                'province' => ['label' => 'Province', 'type' => 'string', 'sortable' => true],
                'district' => ['label' => 'District', 'type' => 'string', 'sortable' => true],
                'physical_address' => ['label' => 'Physical Address', 'type' => 'text'],
                'gps_latitude' => ['label' => 'GPS Latitude', 'type' => 'decimal', 'sortable' => true],
                'gps_longitude' => ['label' => 'GPS Longitude', 'type' => 'decimal', 'sortable' => true],
                'area_hectares' => ['label' => 'Area (ha)', 'type' => 'decimal', 'sortable' => true],
                'ownership' => ['label' => 'Ownership', 'type' => 'enum', 'sortable' => true],
                'management_authority' => ['label' => 'Management Authority', 'type' => 'string', 'sortable' => true],
                'conservation_status' => ['label' => 'Conservation Status', 'type' => 'enum', 'sortable' => true],
                'visitor_access' => ['label' => 'Visitor Access', 'type' => 'enum', 'sortable' => true],
                'annual_visitors' => ['label' => 'Annual Visitors', 'type' => 'integer', 'sortable' => true],
                'significance' => ['label' => 'Significance', 'type' => 'text'],
                'created_at' => ['label' => 'Created At', 'type' => 'datetime', 'sortable' => true],
                'updated_at' => ['label' => 'Updated At', 'type' => 'datetime', 'sortable' => true],
            ],
        ],
        'spectrum_location' => [
            'main' => [
                'id' => ['label' => 'ID', 'type' => 'integer', 'sortable' => true],
                'location_name' => ['label' => 'Location Name', 'type' => 'string', 'sortable' => true],
                'location_code' => ['label' => 'Location Code', 'type' => 'string', 'sortable' => true],
                'location_type' => ['label' => 'Type', 'type' => 'enum', 'sortable' => true],
                'parent_location_id' => ['label' => 'Parent Location', 'type' => 'integer'],
                'building' => ['label' => 'Building', 'type' => 'string', 'sortable' => true],
                'floor' => ['label' => 'Floor', 'type' => 'string', 'sortable' => true],
                'room' => ['label' => 'Room', 'type' => 'string', 'sortable' => true],
                'unit' => ['label' => 'Storage Unit', 'type' => 'string'],
                'shelf' => ['label' => 'Shelf', 'type' => 'string'],
                'location_coordinates' => ['label' => 'Coordinates', 'type' => 'string'],
                'capacity' => ['label' => 'Capacity', 'type' => 'integer'],
                'current_occupancy' => ['label' => 'Current Occupancy', 'type' => 'integer'],
                'environmental_control' => ['label' => 'Environmental Control', 'type' => 'boolean'],
                'security_level' => ['label' => 'Security Level', 'type' => 'enum', 'sortable' => true],
                'is_active' => ['label' => 'Active', 'type' => 'boolean'],
                'notes' => ['label' => 'Notes', 'type' => 'text'],
                'created_at' => ['label' => 'Created At', 'type' => 'datetime', 'sortable' => true],
                'updated_at' => ['label' => 'Updated At', 'type' => 'datetime', 'sortable' => true],
            ],
        ],
        'ahg_usage_event' => [
            'main' => [
                'id' => ['label' => 'ID', 'type' => 'integer', 'sortable' => true],
                'object_id' => ['label' => 'Object', 'type' => 'information_object', 'sortable' => true],
                'user_id' => ['label' => 'User', 'type' => 'user', 'sortable' => true],
                'event_type' => ['label' => 'Event Type', 'type' => 'enum', 'sortable' => true],
                'ip_address' => ['label' => 'IP Address', 'type' => 'string', 'sortable' => true],
                'latitude' => ['label' => 'Latitude', 'type' => 'decimal', 'sortable' => true],
                'longitude' => ['label' => 'Longitude', 'type' => 'decimal', 'sortable' => true],
                'city' => ['label' => 'City', 'type' => 'string', 'sortable' => true],
                'region' => ['label' => 'Region', 'type' => 'string', 'sortable' => true],
                'country' => ['label' => 'Country', 'type' => 'string', 'sortable' => true],
                'user_agent' => ['label' => 'User Agent', 'type' => 'string'],
                'referer' => ['label' => 'Referrer', 'type' => 'string'],
                'session_id' => ['label' => 'Session ID', 'type' => 'string'],
                'created_at' => ['label' => 'Event Time', 'type' => 'datetime', 'sortable' => true],
            ],
        ],
        // ========== DATA SOURCE KEY ALIASES ==========
        // These map the data source keys used in reports to the actual table-based definitions
        'privacy_ropa' => 'alias:privacy_processing_activity',
        'privacy_paia' => 'alias:privacy_paia_request',
        'privacy_dsar' => 'alias:privacy_dsar_request',
        'geospatial_repository' => 'alias:contact_information',
        'geospatial_digital' => 'alias:digital_object_metadata',
        'geospatial_dam' => 'alias:dam_iptc_metadata',
        'geospatial_sites' => 'alias:nmmz_archaeological_site',
        'geospatial_monuments' => 'alias:nmmz_monument',
        'geospatial_storage' => 'alias:spectrum_location',
        'geospatial_usage' => 'alias:ahg_usage_event',
    ];

    /**
     * Resolve data source aliases to their actual definition keys.
     *
     * @param string $source The data source key
     *
     * @return string The resolved source key
     */
    private static function resolveAlias(string $source): string
    {
        if (!isset(self::$columnDefinitions[$source])) {
            return $source;
        }

        $value = self::$columnDefinitions[$source];

        // Check if this is an alias (string starting with "alias:")
        if (is_string($value) && str_starts_with($value, 'alias:')) {
            return substr($value, 6); // Remove "alias:" prefix
        }

        return $source;
    }

    /**
     * Get all columns for a data source.
     *
     * @param string $source  The data source key
     * @param string $culture The culture for i18n columns
     *
     * @return array The columns configuration
     */
    public static function getColumns(string $source, string $culture = 'en'): array
    {
        // Resolve aliases
        $resolvedSource = self::resolveAlias($source);

        if (!isset(self::$columnDefinitions[$resolvedSource])) {
            return self::discoverColumnsFromDatabase($source);
        }

        $columns = [];
        $definitions = self::$columnDefinitions[$resolvedSource];

        // Add main table columns
        if (isset($definitions['main'])) {
            foreach ($definitions['main'] as $column => $config) {
                if (isset($config['hidden']) && $config['hidden']) {
                    continue;
                }
                $columns[$column] = array_merge($config, [
                    'source' => 'main',
                    'column' => $column,
                ]);
            }
        }

        // Add i18n columns
        if (isset($definitions['i18n'])) {
            foreach ($definitions['i18n'] as $column => $config) {
                if (isset($config['hidden']) && $config['hidden']) {
                    continue;
                }
                $columns[$column] = array_merge($config, [
                    'source' => 'i18n',
                    'column' => $column,
                ]);
            }
        }

        // Add object table columns (created_at, updated_at)
        if (isset($definitions['object'])) {
            foreach ($definitions['object'] as $column => $config) {
                $columns[$column] = array_merge($config, [
                    'source' => 'object',
                    'column' => $column,
                ]);
            }
        }

        // Add computed columns
        if (isset($definitions['computed'])) {
            foreach ($definitions['computed'] as $column => $config) {
                $columns[$column] = array_merge($config, [
                    'source' => 'computed',
                    'column' => $column,
                ]);
            }
        }

        // Add actor_i18n columns (for Repository, Donor which extend Actor)
        if (isset($definitions['actor_i18n'])) {
            foreach ($definitions['actor_i18n'] as $column => $config) {
                $columns[$column] = array_merge($config, [
                    'source' => 'actor_i18n',
                    'column' => $column,
                ]);
            }
        }

        // Add contact columns (for Donor contact_information)
        if (isset($definitions['contact'])) {
            foreach ($definitions['contact'] as $column => $config) {
                $columns[$column] = array_merge($config, [
                    'source' => 'contact',
                    'column' => $column,
                ]);
            }
        }

        return $columns;
    }

    /**
     * Get columns grouped by category for the UI.
     *
     * @param string $source The data source key
     *
     * @return array Columns grouped by category
     */
    public static function getColumnsGrouped(string $source): array
    {
        // Resolve aliases
        $resolvedSource = self::resolveAlias($source);

        if (!isset(self::$columnDefinitions[$resolvedSource])) {
            return ['Other' => self::discoverColumnsFromDatabase($source)];
        }

        $definitions = self::$columnDefinitions[$resolvedSource];
        $grouped = [];

        if (isset($definitions['main'])) {
            $grouped['Core Fields'] = [];
            foreach ($definitions['main'] as $column => $config) {
                if (isset($config['hidden']) && $config['hidden']) {
                    continue;
                }
                $grouped['Core Fields'][$column] = array_merge($config, [
                    'source' => 'main',
                    'column' => $column,
                ]);
            }
        }

        if (isset($definitions['i18n'])) {
            $grouped['Descriptive Fields'] = [];
            foreach ($definitions['i18n'] as $column => $config) {
                $grouped['Descriptive Fields'][$column] = array_merge($config, [
                    'source' => 'i18n',
                    'column' => $column,
                ]);
            }
        }

        if (isset($definitions['object'])) {
            $grouped['System Fields'] = [];
            foreach ($definitions['object'] as $column => $config) {
                $grouped['System Fields'][$column] = array_merge($config, [
                    'source' => 'object',
                    'column' => $column,
                ]);
            }
        }

        if (isset($definitions['computed'])) {
            $grouped['Computed Fields'] = [];
            foreach ($definitions['computed'] as $column => $config) {
                $grouped['Computed Fields'][$column] = array_merge($config, [
                    'source' => 'computed',
                    'column' => $column,
                ]);
            }
        }

        if (isset($definitions['actor_i18n'])) {
            $grouped['Actor Fields'] = [];
            foreach ($definitions['actor_i18n'] as $column => $config) {
                $grouped['Actor Fields'][$column] = array_merge($config, [
                    'source' => 'actor_i18n',
                    'column' => $column,
                ]);
            }
        }

        if (isset($definitions['contact'])) {
            $grouped['Contact Fields'] = [];
            foreach ($definitions['contact'] as $column => $config) {
                $grouped['Contact Fields'][$column] = array_merge($config, [
                    'source' => 'contact',
                    'column' => $column,
                ]);
            }
        }

        return $grouped;
    }

    /**
     * Get sortable columns for a data source.
     *
     * @param string $source The data source key
     *
     * @return array Sortable columns
     */
    public static function getSortableColumns(string $source): array
    {
        $columns = self::getColumns($source);

        return array_filter($columns, function ($config) {
            return isset($config['sortable']) && $config['sortable'];
        });
    }

    /**
     * Get searchable columns for a data source.
     *
     * @param string $source The data source key
     *
     * @return array Searchable columns
     */
    public static function getSearchableColumns(string $source): array
    {
        $columns = self::getColumns($source);

        return array_filter($columns, function ($config) {
            return isset($config['searchable']) && $config['searchable'];
        });
    }

    /**
     * Discover columns from database schema (fallback).
     *
     * @param string $source The data source key
     *
     * @return array Discovered columns
     */
    private static function discoverColumnsFromDatabase(string $source): array
    {
        $dataSource = DataSourceRegistry::get($source);
        if (!$dataSource) {
            return [];
        }

        $columns = [];
        $table = $dataSource['table'];

        try {
            $dbColumns = DB::select("SHOW COLUMNS FROM {$table}");
            foreach ($dbColumns as $col) {
                $columns[$col->Field] = [
                    'label' => ucwords(str_replace('_', ' ', $col->Field)),
                    'type' => self::mapMysqlType($col->Type),
                    'source' => 'main',
                    'column' => $col->Field,
                    'sortable' => true,
                ];
            }
        } catch (\Exception $e) {
            // Table doesn't exist or other error
        }

        return $columns;
    }

    /**
     * Map MySQL column types to our internal types.
     *
     * @param string $mysqlType The MySQL column type
     *
     * @return string The internal type
     */
    private static function mapMysqlType(string $mysqlType): string
    {
        $mysqlType = strtolower($mysqlType);

        if (strpos($mysqlType, 'int') !== false) {
            return 'integer';
        }
        if (strpos($mysqlType, 'datetime') !== false || strpos($mysqlType, 'timestamp') !== false) {
            return 'datetime';
        }
        if (strpos($mysqlType, 'date') !== false) {
            return 'date';
        }
        if (strpos($mysqlType, 'text') !== false || strpos($mysqlType, 'blob') !== false) {
            return 'text';
        }
        if (strpos($mysqlType, 'tinyint(1)') !== false) {
            return 'boolean';
        }

        return 'string';
    }
}
