<?php

/**
 * Data Source Registry for Report Builder.
 *
 * Provides a registry of available data sources (entities) that can be used
 * for custom report building.
 */
class DataSourceRegistry
{
    /**
     * Available data sources with their configurations.
     *
     * @var array<string, array>
     */
    private static array $sources = [
        'information_object' => [
            'label' => 'Archival Descriptions',
            'table' => 'information_object',
            'i18n_table' => 'information_object_i18n',
            'object_table' => 'object',
            'class_name' => 'QubitInformationObject',
            'icon' => 'bi-archive',
            'description' => 'Records, files, items, and other archival descriptions',
        ],
        'actor' => [
            'label' => 'Authority Records',
            'table' => 'actor',
            'i18n_table' => 'actor_i18n',
            'object_table' => 'object',
            'class_name' => 'QubitActor',
            'icon' => 'bi-person',
            'description' => 'Persons, families, and corporate bodies',
        ],
        'repository' => [
            'label' => 'Repositories',
            'table' => 'repository',
            'i18n_table' => 'repository_i18n',
            'object_table' => 'object',
            'class_name' => 'QubitRepository',
            'icon' => 'bi-building',
            'description' => 'Archival institutions and repositories',
        ],
        'accession' => [
            'label' => 'Accessions',
            'table' => 'accession',
            'i18n_table' => 'accession_i18n',
            'object_table' => 'object',
            'class_name' => 'QubitAccession',
            'icon' => 'bi-inbox',
            'description' => 'Accession records',
        ],
        'physical_object' => [
            'label' => 'Physical Storage',
            'table' => 'physical_object',
            'i18n_table' => 'physical_object_i18n',
            'object_table' => 'object',
            'class_name' => 'QubitPhysicalObject',
            'icon' => 'bi-box',
            'description' => 'Storage locations and containers',
        ],
        'digital_object' => [
            'label' => 'Digital Objects',
            'table' => 'digital_object',
            'i18n_table' => null,
            'object_table' => 'object',
            'class_name' => 'QubitDigitalObject',
            'icon' => 'bi-file-earmark-image',
            'description' => 'Uploaded files and digital representations',
        ],
        'donor' => [
            'label' => 'Donors',
            'table' => 'donor',
            'i18n_table' => null,
            'object_table' => 'object',
            'class_name' => 'QubitDonor',
            'icon' => 'bi-gift',
            'description' => 'Donor records',
            'joins_actor_i18n' => true,
            'joins_contact' => true,
        ],
        'function' => [
            'label' => 'Functions',
            'table' => 'function_object',
            'i18n_table' => 'function_object_i18n',
            'object_table' => 'object',
            'class_name' => 'QubitFunctionObject',
            'icon' => 'bi-diagram-3',
            'description' => 'Functional classifications (ISDF)',
        ],
        // ========== GLAM SECTOR SOURCES ==========
        'library_item' => [
            'label' => 'Library Items',
            'table' => 'library_item',
            'i18n_table' => null,
            'object_table' => null,
            'class_name' => null,
            'icon' => 'bi-book',
            'description' => 'Library catalog items (books, periodicals, etc.)',
            'category' => 'GLAM',
        ],
        'museum_object' => [
            'label' => 'Museum Objects',
            'table' => 'information_object',
            'i18n_table' => 'information_object_i18n',
            'object_table' => 'object',
            'class_name' => 'QubitInformationObject',
            'icon' => 'bi-bank',
            'description' => 'Museum collection objects (CCO display standard)',
            'category' => 'GLAM',
            'sector_filter' => 'museum',
        ],
        'gallery_artist' => [
            'label' => 'Gallery Artists',
            'table' => 'gallery_artist',
            'i18n_table' => null,
            'object_table' => null,
            'class_name' => null,
            'icon' => 'bi-palette',
            'description' => 'Artist records for gallery collections',
            'category' => 'GLAM',
        ],
        'gallery_exhibition' => [
            'label' => 'Gallery Exhibitions',
            'table' => 'gallery_exhibition',
            'i18n_table' => null,
            'object_table' => null,
            'class_name' => null,
            'icon' => 'bi-easel',
            'description' => 'Exhibition records',
            'category' => 'GLAM',
        ],
        'gallery_loan' => [
            'label' => 'Gallery Loans',
            'table' => 'gallery_loan',
            'i18n_table' => null,
            'object_table' => null,
            'class_name' => null,
            'icon' => 'bi-arrow-left-right',
            'description' => 'Loan records (incoming and outgoing)',
            'category' => 'GLAM',
        ],
        // ========== DAM SOURCE ==========
        'dam_metadata' => [
            'label' => 'DAM Assets',
            'table' => 'dam_iptc_metadata',
            'i18n_table' => null,
            'object_table' => null,
            'class_name' => null,
            'icon' => 'bi-images',
            'description' => 'Digital Asset Management metadata (IPTC)',
            'category' => 'DAM',
        ],
        // ========== CONDITION SOURCES ==========
        'condition_report' => [
            'label' => 'Condition Reports',
            'table' => 'condition_report',
            'i18n_table' => null,
            'object_table' => null,
            'class_name' => null,
            'icon' => 'bi-clipboard-check',
            'description' => 'Object condition assessment reports',
            'category' => 'Condition',
        ],
        // ========== SECURITY SOURCES ==========
        'security_classification' => [
            'label' => 'Security Classifications',
            'table' => 'object_security_classification',
            'i18n_table' => null,
            'object_table' => null,
            'class_name' => null,
            'icon' => 'bi-shield-lock',
            'description' => 'Object security classification records',
            'category' => 'Security',
        ],
        'user_clearance' => [
            'label' => 'User Security Clearances',
            'table' => 'user_security_clearance',
            'i18n_table' => null,
            'object_table' => null,
            'class_name' => null,
            'icon' => 'bi-person-badge',
            'description' => 'User security clearance levels',
            'category' => 'Security',
        ],
        'security_access_log' => [
            'label' => 'Security Access Logs',
            'table' => 'security_access_log',
            'i18n_table' => null,
            'object_table' => null,
            'class_name' => null,
            'icon' => 'bi-journal-text',
            'description' => 'Security access audit trail',
            'category' => 'Security',
        ],
        // ========== PROVENANCE SOURCES ==========
        'provenance_record' => [
            'label' => 'Provenance Records',
            'table' => 'provenance_record',
            'i18n_table' => 'provenance_record_i18n',
            'object_table' => null,
            'class_name' => null,
            'icon' => 'bi-diagram-2',
            'description' => 'Full provenance chain records',
            'category' => 'Provenance',
        ],
        'provenance_event' => [
            'label' => 'Provenance Events',
            'table' => 'provenance_event',
            'i18n_table' => 'provenance_event_i18n',
            'object_table' => null,
            'class_name' => null,
            'icon' => 'bi-calendar-event',
            'description' => 'Individual provenance events (transfers, custody changes)',
            'category' => 'Provenance',
        ],
        'object_provenance' => [
            'label' => 'Object Provenance Links',
            'table' => 'object_provenance',
            'i18n_table' => null,
            'object_table' => null,
            'class_name' => null,
            'icon' => 'bi-link-45deg',
            'description' => 'Links between objects and provenance records',
            'category' => 'Provenance',
        ],
        // ========== PRIVACY & DATA PROTECTION SOURCES ==========
        'privacy_consent' => [
            'label' => 'Privacy Consent Records',
            'table' => 'privacy_consent_record',
            'i18n_table' => null,
            'object_table' => null,
            'class_name' => null,
            'icon' => 'bi-shield-check',
            'description' => 'Data subject consent records (POPIA/GDPR)',
            'category' => 'Privacy',
        ],
        'privacy_dsar' => [
            'label' => 'Data Subject Requests',
            'table' => 'privacy_dsar_request',
            'i18n_table' => null,
            'object_table' => null,
            'class_name' => null,
            'icon' => 'bi-person-lines-fill',
            'description' => 'Data subject access requests (DSAR)',
            'category' => 'Privacy',
        ],
        'privacy_breach' => [
            'label' => 'Privacy Breaches',
            'table' => 'privacy_breach',
            'i18n_table' => 'privacy_breach_i18n',
            'object_table' => null,
            'class_name' => null,
            'icon' => 'bi-exclamation-triangle',
            'description' => 'Data breach incident records',
            'category' => 'Privacy',
        ],
        // ========== VENDOR MANAGEMENT SOURCES ==========
        'vendor' => [
            'label' => 'Vendors',
            'table' => 'ahg_vendors',
            'i18n_table' => null,
            'object_table' => null,
            'class_name' => null,
            'icon' => 'bi-truck',
            'description' => 'Vendor/supplier records',
            'category' => 'Vendor',
        ],
        'vendor_transaction' => [
            'label' => 'Vendor Transactions',
            'table' => 'ahg_vendor_transactions',
            'i18n_table' => null,
            'object_table' => null,
            'class_name' => null,
            'icon' => 'bi-receipt',
            'description' => 'Purchase orders and invoices',
            'category' => 'Vendor',
        ],
        // ========== HERITAGE ASSETS (GRAP 103) ==========
        'heritage_asset' => [
            'label' => 'Heritage Assets',
            'table' => 'heritage_asset',
            'i18n_table' => null,
            'object_table' => null,
            'class_name' => null,
            'icon' => 'bi-bank2',
            'description' => 'Heritage assets register (GRAP 103 compliant)',
            'category' => 'Heritage',
        ],
        'heritage_valuation' => [
            'label' => 'Heritage Valuations',
            'table' => 'heritage_valuation_history',
            'i18n_table' => null,
            'object_table' => null,
            'class_name' => null,
            'icon' => 'bi-currency-dollar',
            'description' => 'Heritage asset valuation history',
            'category' => 'Heritage',
        ],
        // ========== SPECTRUM 5.0 ==========
        'spectrum_valuation' => [
            'label' => 'Spectrum Valuations',
            'table' => 'spectrum_valuation',
            'i18n_table' => null,
            'object_table' => null,
            'class_name' => null,
            'icon' => 'bi-cash-stack',
            'description' => 'Object valuations (Spectrum 5.0)',
            'category' => 'Spectrum',
        ],
        'spectrum_loan_in' => [
            'label' => 'Loans In',
            'table' => 'spectrum_loan_in',
            'i18n_table' => null,
            'object_table' => null,
            'class_name' => null,
            'icon' => 'bi-box-arrow-in-right',
            'description' => 'Incoming loans (Spectrum 5.0)',
            'category' => 'Spectrum',
        ],
        'spectrum_loan_out' => [
            'label' => 'Loans Out',
            'table' => 'spectrum_loan_out',
            'i18n_table' => null,
            'object_table' => null,
            'class_name' => null,
            'icon' => 'bi-box-arrow-right',
            'description' => 'Outgoing loans (Spectrum 5.0)',
            'category' => 'Spectrum',
        ],
        'spectrum_condition' => [
            'label' => 'Condition Checks',
            'table' => 'spectrum_condition_check',
            'i18n_table' => null,
            'object_table' => null,
            'class_name' => null,
            'icon' => 'bi-clipboard2-pulse',
            'description' => 'Object condition checks (Spectrum 5.0)',
            'category' => 'Spectrum',
        ],
        'spectrum_movement' => [
            'label' => 'Object Movements',
            'table' => 'spectrum_movement',
            'i18n_table' => null,
            'object_table' => null,
            'class_name' => null,
            'icon' => 'bi-arrows-move',
            'description' => 'Object location movements (Spectrum 5.0)',
            'category' => 'Spectrum',
        ],
        // ========== ADDITIONAL PRIVACY SOURCES ==========
        'privacy_ropa' => [
            'label' => 'Processing Activities (ROPA)',
            'table' => 'privacy_processing_activity',
            'i18n_table' => null,
            'object_table' => null,
            'class_name' => null,
            'icon' => 'bi-list-check',
            'description' => 'Records of processing activities (ROPA)',
            'category' => 'Privacy',
        ],
        'privacy_paia' => [
            'label' => 'PAIA Requests',
            'table' => 'privacy_paia_request',
            'i18n_table' => null,
            'object_table' => null,
            'class_name' => null,
            'icon' => 'bi-file-earmark-text',
            'description' => 'PAIA access requests (SA legislation)',
            'category' => 'Privacy',
        ],
        // ========== RIGHTS MANAGEMENT ==========
        'rights_record' => [
            'label' => 'Rights Records',
            'table' => 'rights_record',
            'i18n_table' => 'rights_record_i18n',
            'object_table' => null,
            'class_name' => null,
            'icon' => 'bi-c-circle',
            'description' => 'Object rights records (copyright, licenses)',
            'category' => 'Rights',
        ],
        'rights' => [
            'label' => 'Rights Statements',
            'table' => 'rights',
            'i18n_table' => 'rights_i18n',
            'object_table' => 'object',
            'class_name' => 'QubitRights',
            'icon' => 'bi-shield-fill-check',
            'description' => 'Rights statements linked to objects',
            'category' => 'Rights',
        ],
    ];

    /**
     * Get all available data sources.
     *
     * @return array<string, array>
     */
    public static function getAll(): array
    {
        return self::$sources;
    }

    /**
     * Get a specific data source by key.
     *
     * @param string $key The data source key
     *
     * @return array|null The data source configuration or null if not found
     */
    public static function get(string $key): ?array
    {
        return self::$sources[$key] ?? null;
    }

    /**
     * Check if a data source exists.
     *
     * @param string $key The data source key
     *
     * @return bool True if the data source exists
     */
    public static function exists(string $key): bool
    {
        return isset(self::$sources[$key]);
    }

    /**
     * Get data sources as select options for forms.
     *
     * @return array<string, string>
     */
    public static function getSelectOptions(): array
    {
        $options = [];
        foreach (self::$sources as $key => $source) {
            $options[$key] = $source['label'];
        }

        return $options;
    }

    /**
     * Register a custom data source (for extension by other plugins).
     *
     * @param string $key    The data source key
     * @param array  $config The data source configuration
     */
    public static function register(string $key, array $config): void
    {
        $required = ['label', 'table'];
        foreach ($required as $field) {
            if (!isset($config[$field])) {
                throw new InvalidArgumentException("Data source config missing required field: {$field}");
            }
        }
        self::$sources[$key] = array_merge([
            'i18n_table' => null,
            'object_table' => null,
            'class_name' => null,
            'icon' => 'bi-file-earmark',
            'description' => '',
        ], $config);
    }
}
