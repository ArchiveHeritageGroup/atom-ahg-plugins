<?php

use Illuminate\Database\Capsule\Manager as DB;

/**
 * Query Builder Service for Report Builder.
 *
 * Provides visual query building and safe raw SQL execution for reports.
 * Includes guardrails to prevent destructive operations.
 */
class QueryBuilder
{
    /**
     * Dangerous SQL keywords that are not allowed.
     *
     * @var array
     */
    private array $dangerousKeywords = [
        'INSERT', 'UPDATE', 'DELETE', 'DROP', 'ALTER', 'CREATE',
        'TRUNCATE', 'GRANT', 'REVOKE', 'REPLACE', 'RENAME',
        'LOAD', 'INTO OUTFILE', 'INTO DUMPFILE',
    ];

    /**
     * Prefix-to-sector mapping for auto-grouping all database tables.
     *
     * @var array<string, string>
     */
    private static array $prefixSectorMap = [
        'information_object' => 'Core AtoM',
        'actor' => 'Core AtoM',
        'repository' => 'Core AtoM',
        'term' => 'Core AtoM',
        'taxonomy' => 'Core AtoM',
        'accession' => 'Core AtoM',
        'digital_object' => 'Core AtoM',
        'physical_object' => 'Core AtoM',
        'rights' => 'Core AtoM',
        'rights_holder' => 'Core AtoM',
        'donor' => 'Core AtoM',
        'contact_information' => 'Core AtoM',
        'relation' => 'Core AtoM',
        'event' => 'Core AtoM',
        'note' => 'Core AtoM',
        'status' => 'Core AtoM',
        'property' => 'Core AtoM',
        'slug' => 'Core AtoM',
        'object' => 'Core AtoM',
        'user' => 'Core AtoM',
        'acl_' => 'Core AtoM',
        'spectrum_' => 'Museum (Spectrum)',
        'museum_' => 'Museum',
        'library_' => 'Library',
        'gallery_' => 'Gallery',
        'exhibition' => 'Gallery (Exhibitions)',
        'dam_' => 'DAM',
        'heritage_' => 'Heritage',
        'grap_' => 'Heritage Accounting',
        'ipsas_' => 'Heritage Accounting (IPSAS)',
        'privacy_' => 'Privacy & Compliance',
        'preservation_' => 'Preservation',
        'oais_' => 'Preservation',
        'condition_' => 'Condition Assessment',
        'provenance_' => 'Provenance',
        'security_' => 'Security & Classification',
        'naz_' => 'Zimbabwe (NAZ)',
        'nmmz_' => 'Zimbabwe (NMMZ)',
        'cdpa_' => 'Zimbabwe (CDPA)',
        'research_' => 'Research & Reading Room',
        'researcher_' => 'Research & Reading Room',
        'access_request' => 'Access Requests',
        'request_to_publish' => 'Access Requests',
        'backup_' => 'Backups',
        'ahg_audit_' => 'Audit Trail',
        'audit_log' => 'Audit Trail',
        'access_audit_log' => 'Audit Trail',
        'ahg_workflow' => 'Workflow',
        'workflow_' => 'Workflow',
        'ahg_ai_' => 'AI & Automation',
        'ahg_ner_' => 'AI & Automation',
        'ahg_translation_' => 'AI & Automation',
        'ahg_spellcheck_' => 'AI & Automation',
        'ahg_semantic_' => 'AI & Automation',
        'ahg_prompt_' => 'AI & Automation',
        'ahg_tts_' => 'AI & Automation',
        'ingest_' => 'Data Ingest',
        'ahg_vendor' => 'Vendors',
        'ahg_loan' => 'Loans',
        'loan' => 'Loans',
        'ahg_doi' => 'DOI',
        'ahg_api_' => 'API & Webhooks',
        'ahg_webhook' => 'API & Webhooks',
        'ahg_graphql_' => 'API & Webhooks',
        'ahg_statistics_' => 'Statistics',
        'ahg_settings' => 'Settings',
        'ahg_form_' => 'Forms',
        'ahg_dedupe_' => 'Deduplication',
        'ahg_duplicate_' => 'Deduplication',
        'ahg_merge_' => 'Deduplication',
        'ahg_contract' => 'Contracts',
        'ahg_dropdown' => 'Configuration',
        'ahg_bot_' => 'Configuration',
        'ahg_encrypted_' => 'Encryption',
        'ahg_encryption_' => 'Encryption',
        'ahg_ecommerce_' => 'E-Commerce',
        'ahg_order' => 'E-Commerce',
        'ahg_payment' => 'E-Commerce',
        'ahg_product_' => 'E-Commerce',
        'ahg_usage_' => 'Usage Analytics',
        'ahg_thesaurus_' => 'Thesaurus',
        'ahg_term_' => 'Thesaurus',
        'ahg_description_' => 'AI & Automation',
        'ahg_llm_' => 'AI & Automation',
        'ahg_download_' => 'Downloads',
        'embargo_' => 'Rights & Embargo',
        'custom_report' => 'Reporting',
        'report_' => 'Reporting',
        'cart' => 'User Engagement',
        'favorites' => 'User Engagement',
        'feedback' => 'User Engagement',
        'atom_' => 'System',
        'setting' => 'System',
        'object_3d_' => '3D Models',
    ];

    /**
     * Execute a visual query built from a configuration object.
     *
     * @param array    $config The query configuration
     * @param int|null $userId The user executing the query
     *
     * @return array The query results
     *
     * @throws \InvalidArgumentException If configuration is invalid
     */
    public function executeVisualQuery(array $config, ?int $userId = null): array
    {
        $table = $config['table'] ?? null;
        if (!$table) {
            throw new \InvalidArgumentException('Query configuration must include a table');
        }

        $query = DB::table($table);

        // Apply joins
        if (!empty($config['joins'])) {
            foreach ($config['joins'] as $join) {
                $type = $join['type'] ?? 'inner';
                $joinTable = $join['table'] ?? null;
                $first = $join['first'] ?? null;
                $operator = $join['operator'] ?? '=';
                $second = $join['second'] ?? null;

                if (!$joinTable || !$first || !$second) {
                    continue;
                }

                switch ($type) {
                    case 'left':
                        $query->leftJoin($joinTable, $first, $operator, $second);
                        break;
                    case 'right':
                        $query->rightJoin($joinTable, $first, $operator, $second);
                        break;
                    default:
                        $query->join($joinTable, $first, $operator, $second);
                        break;
                }
            }
        }

        // Select columns
        if (!empty($config['columns'])) {
            $query->select($config['columns']);
        }

        // Apply filters
        if (!empty($config['filters'])) {
            foreach ($config['filters'] as $filter) {
                $column = $filter['column'] ?? null;
                $operator = $filter['operator'] ?? '=';
                $value = $filter['value'] ?? null;

                if (!$column) {
                    continue;
                }

                if ($operator === 'IS NULL') {
                    $query->whereNull($column);
                } elseif ($operator === 'IS NOT NULL') {
                    $query->whereNotNull($column);
                } elseif ($operator === 'IN' && is_array($value)) {
                    $query->whereIn($column, $value);
                } elseif ($operator === 'LIKE') {
                    $query->where($column, 'LIKE', $value);
                } elseif ($operator === 'BETWEEN' && isset($filter['value2'])) {
                    $query->whereBetween($column, [$value, $filter['value2']]);
                } else {
                    $query->where($column, $operator, $value);
                }
            }
        }

        // Apply groupBy
        if (!empty($config['groupBy'])) {
            $query->groupBy($config['groupBy']);
        }

        // Apply orderBy
        if (!empty($config['orderBy'])) {
            foreach ($config['orderBy'] as $order) {
                $column = $order['column'] ?? null;
                $direction = $order['direction'] ?? 'asc';
                if ($column) {
                    $query->orderBy($column, $direction);
                }
            }
        }

        // Apply limit (max 1000)
        $limit = min($config['limit'] ?? 1000, 1000);
        $query->limit($limit);

        return $query->get()->toArray();
    }

    /**
     * Execute a raw SQL query with safety guardrails.
     *
     * Only SELECT statements are allowed. The user must be an administrator.
     * Results are limited to 1000 rows with a 30-second timeout.
     *
     * @param string   $sql    The SQL query
     * @param array    $params Bound parameters
     * @param int|null $userId The user executing the query (must be admin)
     *
     * @return array The query results
     *
     * @throws \InvalidArgumentException If the SQL is not read-only
     * @throws \RuntimeException If the user is not an administrator
     */
    public function executeRawSql(string $sql, array $params = [], ?int $userId = null): array
    {
        // Validate user is administrator (group_id 100 = administrator)
        if ($userId !== null) {
            $isAdmin = DB::table('acl_user_group')
                ->where('user_id', $userId)
                ->where('group_id', 100)
                ->exists();

            if (!$isAdmin) {
                throw new \RuntimeException('Raw SQL execution requires administrator privileges');
            }
        }

        // Validate SQL is read-only
        if (!$this->isReadOnly($sql)) {
            throw new \InvalidArgumentException(
                'Only SELECT statements are allowed. INSERT, UPDATE, DELETE, DROP, ALTER, CREATE, TRUNCATE, GRANT, and REVOKE are prohibited.'
            );
        }

        // Set statement timeout (30 seconds)
        try {
            DB::statement('SET SESSION MAX_EXECUTION_TIME = 30000');
        } catch (\Exception $e) {
            // MAX_EXECUTION_TIME may not be supported on all MySQL versions
        }

        // Ensure LIMIT is present, enforce max 1000
        $normalizedSql = $this->enforceLimit($sql, 1000);

        // Execute the query
        $results = DB::select($normalizedSql, $params);

        return $results;
    }

    /**
     * Validate SQL for potential issues.
     *
     * @param string $sql The SQL to validate
     *
     * @return array Array of issues (empty = valid)
     */
    public function validateSql(string $sql): array
    {
        $issues = [];

        $trimmed = trim($sql);
        if (empty($trimmed)) {
            $issues[] = 'SQL query is empty';

            return $issues;
        }

        // Check it starts with SELECT
        if (!preg_match('/^\s*SELECT\b/i', $trimmed)) {
            $issues[] = 'Query must start with SELECT';
        }

        // Check for dangerous statements
        foreach ($this->dangerousKeywords as $keyword) {
            if (preg_match('/\b' . preg_quote($keyword, '/') . '\b/i', $trimmed)) {
                $issues[] = "Dangerous keyword detected: {$keyword}";
            }
        }

        // Check for multiple statements (semicolons)
        $withoutStrings = preg_replace("/'[^']*'/", '', $trimmed);
        $withoutStrings = preg_replace('/"[^"]*"/', '', $withoutStrings);
        if (substr_count($withoutStrings, ';') > 1) {
            $issues[] = 'Multiple SQL statements are not allowed';
        }

        // Check for suspicious patterns
        if (preg_match('/\bSLEEP\s*\(/i', $trimmed)) {
            $issues[] = 'SLEEP function is not allowed';
        }
        if (preg_match('/\bBENCHMARK\s*\(/i', $trimmed)) {
            $issues[] = 'BENCHMARK function is not allowed';
        }
        if (preg_match('/\bLOAD_FILE\s*\(/i', $trimmed)) {
            $issues[] = 'LOAD_FILE function is not allowed';
        }

        return $issues;
    }

    /**
     * Known table relationships for visual join builder.
     * Format: 'table' => [ ['table' => 'target', 'from' => 'local_col', 'to' => 'remote_col', 'type' => 'left|inner', 'label' => 'description'] ]
     *
     * @var array<string, array>
     */
    private static array $relationships = [
        'information_object' => [
            ['table' => 'information_object_i18n', 'from' => 'id', 'to' => 'id', 'type' => 'left', 'label' => 'Translations'],
            ['table' => 'digital_object', 'from' => 'id', 'to' => 'object_id', 'type' => 'left', 'label' => 'Digital objects'],
            ['table' => 'slug', 'from' => 'id', 'to' => 'object_id', 'type' => 'left', 'label' => 'URL slug'],
            ['table' => 'status', 'from' => 'id', 'to' => 'object_id', 'type' => 'left', 'label' => 'Publication status'],
            ['table' => 'event', 'from' => 'id', 'to' => 'object_id', 'type' => 'left', 'label' => 'Events (dates)'],
            ['table' => 'note', 'from' => 'id', 'to' => 'object_id', 'type' => 'left', 'label' => 'Notes'],
            ['table' => 'relation', 'from' => 'id', 'to' => 'subject_id', 'type' => 'left', 'label' => 'Relations (subject)'],
            ['table' => 'property', 'from' => 'id', 'to' => 'object_id', 'type' => 'left', 'label' => 'Properties'],
            ['table' => 'object', 'from' => 'id', 'to' => 'id', 'type' => 'inner', 'label' => 'Base object (timestamps)'],
            // GLAM/DAM sector extensions
            ['table' => 'library_item', 'from' => 'id', 'to' => 'information_object_id', 'type' => 'left', 'label' => 'Library cataloging'],
            ['table' => 'museum_metadata', 'from' => 'id', 'to' => 'object_id', 'type' => 'left', 'label' => 'Museum metadata'],
            ['table' => 'dam_iptc_metadata', 'from' => 'id', 'to' => 'object_id', 'type' => 'left', 'label' => 'DAM IPTC metadata'],
            ['table' => 'dam_external_links', 'from' => 'id', 'to' => 'object_id', 'type' => 'left', 'label' => 'DAM external links'],
            ['table' => 'dam_format_holdings', 'from' => 'id', 'to' => 'object_id', 'type' => 'left', 'label' => 'DAM format holdings'],
            ['table' => 'dam_version_links', 'from' => 'id', 'to' => 'object_id', 'type' => 'left', 'label' => 'DAM version links'],
            ['table' => 'gallery_valuation', 'from' => 'id', 'to' => 'object_id', 'type' => 'left', 'label' => 'Gallery valuations'],
            ['table' => 'gallery_loan_object', 'from' => 'id', 'to' => 'object_id', 'type' => 'left', 'label' => 'Gallery loan objects'],
        ],
        'actor' => [
            ['table' => 'actor_i18n', 'from' => 'id', 'to' => 'id', 'type' => 'left', 'label' => 'Translations'],
            ['table' => 'slug', 'from' => 'id', 'to' => 'object_id', 'type' => 'left', 'label' => 'URL slug'],
            ['table' => 'contact_information', 'from' => 'id', 'to' => 'actor_id', 'type' => 'left', 'label' => 'Contact info'],
            ['table' => 'event', 'from' => 'id', 'to' => 'actor_id', 'type' => 'left', 'label' => 'Events'],
            ['table' => 'object', 'from' => 'id', 'to' => 'id', 'type' => 'inner', 'label' => 'Base object'],
        ],
        'repository' => [
            ['table' => 'repository_i18n', 'from' => 'id', 'to' => 'id', 'type' => 'left', 'label' => 'Translations'],
            ['table' => 'slug', 'from' => 'id', 'to' => 'object_id', 'type' => 'left', 'label' => 'URL slug'],
            ['table' => 'contact_information', 'from' => 'id', 'to' => 'actor_id', 'type' => 'left', 'label' => 'Contact info'],
            ['table' => 'object', 'from' => 'id', 'to' => 'id', 'type' => 'inner', 'label' => 'Base object'],
        ],
        'accession' => [
            ['table' => 'accession_i18n', 'from' => 'id', 'to' => 'id', 'type' => 'left', 'label' => 'Translations'],
            ['table' => 'slug', 'from' => 'id', 'to' => 'object_id', 'type' => 'left', 'label' => 'URL slug'],
            ['table' => 'relation', 'from' => 'id', 'to' => 'subject_id', 'type' => 'left', 'label' => 'Relations'],
            ['table' => 'object', 'from' => 'id', 'to' => 'id', 'type' => 'inner', 'label' => 'Base object'],
        ],
        'term' => [
            ['table' => 'term_i18n', 'from' => 'id', 'to' => 'id', 'type' => 'left', 'label' => 'Translations'],
            ['table' => 'taxonomy', 'from' => 'taxonomy_id', 'to' => 'id', 'type' => 'inner', 'label' => 'Taxonomy'],
            ['table' => 'object', 'from' => 'id', 'to' => 'id', 'type' => 'inner', 'label' => 'Base object'],
        ],
        'taxonomy' => [
            ['table' => 'taxonomy_i18n', 'from' => 'id', 'to' => 'id', 'type' => 'left', 'label' => 'Translations'],
            ['table' => 'term', 'from' => 'id', 'to' => 'taxonomy_id', 'type' => 'left', 'label' => 'Terms'],
            ['table' => 'object', 'from' => 'id', 'to' => 'id', 'type' => 'inner', 'label' => 'Base object'],
        ],
        'digital_object' => [
            ['table' => 'information_object', 'from' => 'object_id', 'to' => 'id', 'type' => 'inner', 'label' => 'Parent description'],
            ['table' => 'object', 'from' => 'id', 'to' => 'id', 'type' => 'inner', 'label' => 'Base object'],
        ],
        'event' => [
            ['table' => 'information_object', 'from' => 'object_id', 'to' => 'id', 'type' => 'inner', 'label' => 'Description'],
            ['table' => 'actor', 'from' => 'actor_id', 'to' => 'id', 'type' => 'left', 'label' => 'Actor'],
            ['table' => 'term', 'from' => 'type_id', 'to' => 'id', 'type' => 'inner', 'label' => 'Event type'],
        ],
        'relation' => [
            ['table' => 'term', 'from' => 'type_id', 'to' => 'id', 'type' => 'inner', 'label' => 'Relation type'],
        ],
        'note' => [
            ['table' => 'note_i18n', 'from' => 'id', 'to' => 'id', 'type' => 'left', 'label' => 'Translations'],
            ['table' => 'information_object', 'from' => 'object_id', 'to' => 'id', 'type' => 'inner', 'label' => 'Description'],
            ['table' => 'term', 'from' => 'type_id', 'to' => 'id', 'type' => 'inner', 'label' => 'Note type'],
        ],
        'rights' => [
            ['table' => 'rights_i18n', 'from' => 'id', 'to' => 'id', 'type' => 'left', 'label' => 'Translations'],
            ['table' => 'rights_holder', 'from' => 'rights_holder_id', 'to' => 'id', 'type' => 'left', 'label' => 'Rights holder'],
        ],
        'physical_object' => [
            ['table' => 'physical_object_i18n', 'from' => 'id', 'to' => 'id', 'type' => 'left', 'label' => 'Translations'],
            ['table' => 'relation', 'from' => 'id', 'to' => 'object_id', 'type' => 'left', 'label' => 'Linked records'],
            ['table' => 'object', 'from' => 'id', 'to' => 'id', 'type' => 'inner', 'label' => 'Base object'],
        ],
        'property' => [
            ['table' => 'property_i18n', 'from' => 'id', 'to' => 'id', 'type' => 'left', 'label' => 'Translations'],
            ['table' => 'information_object', 'from' => 'object_id', 'to' => 'id', 'type' => 'inner', 'label' => 'Description'],
        ],
        'status' => [
            ['table' => 'information_object', 'from' => 'object_id', 'to' => 'id', 'type' => 'inner', 'label' => 'Description'],
            ['table' => 'term', 'from' => 'status_id', 'to' => 'id', 'type' => 'inner', 'label' => 'Status term'],
        ],
        'user' => [
            ['table' => 'actor', 'from' => 'id', 'to' => 'id', 'type' => 'inner', 'label' => 'Actor record'],
        ],
        'donor' => [
            ['table' => 'actor', 'from' => 'id', 'to' => 'id', 'type' => 'inner', 'label' => 'Actor record'],
            ['table' => 'contact_information', 'from' => 'id', 'to' => 'actor_id', 'type' => 'left', 'label' => 'Contact info'],
        ],
        'rights_holder' => [
            ['table' => 'actor', 'from' => 'id', 'to' => 'id', 'type' => 'inner', 'label' => 'Actor record'],
        ],
        'contact_information' => [
            ['table' => 'actor', 'from' => 'actor_id', 'to' => 'id', 'type' => 'inner', 'label' => 'Actor'],
        ],
        // ── GLAM/DAM sector tables ──────────────────────────────────────
        'library_item' => [
            ['table' => 'information_object', 'from' => 'information_object_id', 'to' => 'id', 'type' => 'inner', 'label' => 'Description'],
            ['table' => 'information_object_i18n', 'from' => 'information_object_id', 'to' => 'id', 'type' => 'left', 'label' => 'Description translations'],
        ],
        'museum_metadata' => [
            ['table' => 'information_object', 'from' => 'object_id', 'to' => 'id', 'type' => 'inner', 'label' => 'Description'],
            ['table' => 'information_object_i18n', 'from' => 'object_id', 'to' => 'id', 'type' => 'left', 'label' => 'Description translations'],
        ],
        'dam_iptc_metadata' => [
            ['table' => 'information_object', 'from' => 'object_id', 'to' => 'id', 'type' => 'inner', 'label' => 'Description'],
            ['table' => 'information_object_i18n', 'from' => 'object_id', 'to' => 'id', 'type' => 'left', 'label' => 'Description translations'],
        ],
        'dam_external_links' => [
            ['table' => 'information_object', 'from' => 'object_id', 'to' => 'id', 'type' => 'inner', 'label' => 'Description'],
        ],
        'dam_format_holdings' => [
            ['table' => 'information_object', 'from' => 'object_id', 'to' => 'id', 'type' => 'inner', 'label' => 'Description'],
        ],
        'dam_version_links' => [
            ['table' => 'information_object', 'from' => 'object_id', 'to' => 'id', 'type' => 'inner', 'label' => 'Description'],
            ['table' => 'information_object', 'from' => 'related_object_id', 'to' => 'id', 'type' => 'left', 'label' => 'Related description'],
        ],
        'gallery_valuation' => [
            ['table' => 'information_object', 'from' => 'object_id', 'to' => 'id', 'type' => 'inner', 'label' => 'Description'],
        ],
        'gallery_loan_object' => [
            ['table' => 'information_object', 'from' => 'object_id', 'to' => 'id', 'type' => 'inner', 'label' => 'Description'],
            ['table' => 'gallery_loan', 'from' => 'loan_id', 'to' => 'id', 'type' => 'inner', 'label' => 'Loan'],
        ],
        'gallery_loan' => [
            ['table' => 'gallery_loan_object', 'from' => 'id', 'to' => 'loan_id', 'type' => 'left', 'label' => 'Loan objects'],
        ],
        'gallery_artist' => [
            ['table' => 'actor', 'from' => 'actor_id', 'to' => 'id', 'type' => 'left', 'label' => 'Actor record'],
        ],
    ];

    /**
     * Get relationships for a specific table.
     *
     * @param string $tableName The table name
     *
     * @return array The relationships
     */
    public function getRelationships(string $tableName): array
    {
        return self::$relationships[$tableName] ?? [];
    }

    /**
     * Get all relationships (for relationship diagram).
     *
     * @return array All table relationships
     */
    public function getAllRelationships(): array
    {
        return self::$relationships;
    }

    /**
     * Get ALL database tables, auto-grouped by sector/function.
     *
     * Uses prefix matching to classify tables. Tables from plugins that
     * are not installed simply won't exist, so they're excluded automatically.
     *
     * @return array The available tables grouped by sector
     */
    public function getAvailableTables(): array
    {
        $dbName = DB::connection()->getDatabaseName();

        $allTables = DB::select(
            'SELECT TABLE_NAME, TABLE_ROWS, TABLE_COMMENT
             FROM information_schema.TABLES
             WHERE TABLE_SCHEMA = ? AND TABLE_TYPE = ?
             ORDER BY TABLE_NAME',
            [$dbName, 'BASE TABLE']
        );

        // Classify each table into a sector by matching prefixes (longest match first)
        $grouped = [];
        foreach ($allTables as $table) {
            $name = $table->TABLE_NAME;
            $sector = $this->classifyTable($name);

            if (!isset($grouped[$sector])) {
                $grouped[$sector] = [];
            }
            $grouped[$sector][] = [
                'name' => $name,
                'rows' => $table->TABLE_ROWS,
                'comment' => $table->TABLE_COMMENT,
                'sector' => $sector,
            ];
        }

        // Sort sectors alphabetically, but put "Core AtoM" first
        ksort($grouped);
        $result = [];
        if (isset($grouped['Core AtoM'])) {
            $result[] = ['sector' => 'Core AtoM', 'tables' => $grouped['Core AtoM']];
            unset($grouped['Core AtoM']);
        }
        foreach ($grouped as $sector => $tables) {
            $result[] = ['sector' => $sector, 'tables' => $tables];
        }

        return $result;
    }

    /**
     * Classify a table name into a sector using prefix matching.
     *
     * @param string $tableName The table name
     *
     * @return string The sector name
     */
    private function classifyTable(string $tableName): string
    {
        $bestMatch = '';
        $bestSector = 'Other';

        foreach (self::$prefixSectorMap as $prefix => $sector) {
            // Check if table name matches this prefix
            if ($tableName === $prefix || str_starts_with($tableName, $prefix)) {
                // Longest prefix match wins
                if (strlen($prefix) > strlen($bestMatch)) {
                    $bestMatch = $prefix;
                    $bestSector = $sector;
                }
            }
        }

        return $bestSector;
    }

    /**
     * Get columns for a specific table, enriched with friendly labels from ColumnDiscovery.
     *
     * @param string $tableName The table name
     *
     * @return array The column definitions with labels
     */
    public function getTableColumns(string $tableName): array
    {
        $dbName = DB::connection()->getDatabaseName();

        $columns = DB::select(
            'SELECT COLUMN_NAME, DATA_TYPE, COLUMN_TYPE, IS_NULLABLE,
                    COLUMN_DEFAULT, COLUMN_KEY, COLUMN_COMMENT
             FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?
             ORDER BY ORDINAL_POSITION',
            [$dbName, $tableName]
        );

        // Enrich with friendly labels from ColumnDiscovery
        $labels = $this->getColumnLabels($tableName);
        foreach ($columns as &$col) {
            $colName = $col->COLUMN_NAME;
            $col->label = $labels[$colName] ?? null;
        }

        return $columns;
    }

    /**
     * Get friendly column labels for a table from ColumnDiscovery.
     *
     * Maps table names to their ColumnDiscovery data source definitions.
     * For i18n tables (e.g. information_object_i18n), looks up the 'i18n' section
     * of the parent table.
     *
     * @param string $tableName The database table name
     *
     * @return array<string, string> Map of column_name => friendly label
     */
    private function getColumnLabels(string $tableName): array
    {
        if (!class_exists('ColumnDiscovery')) {
            $path = dirname(__FILE__) . '/ColumnDiscovery.php';
            if (file_exists($path)) {
                require_once $path;
            } else {
                return [];
            }
        }

        $labels = [];

        // Check if this is an i18n table (e.g. information_object_i18n → information_object)
        $isI18n = str_ends_with($tableName, '_i18n');
        $baseTable = $isI18n ? substr($tableName, 0, -5) : $tableName;

        try {
            $columns = ColumnDiscovery::getColumns($baseTable);
        } catch (\Exception $e) {
            return [];
        }

        if (empty($columns)) {
            return [];
        }

        foreach ($columns as $colName => $config) {
            $source = $config['source'] ?? 'main';
            if ($isI18n && $source === 'i18n') {
                $labels[$colName] = $config['label'] ?? null;
            } elseif (!$isI18n && ($source === 'main' || $source === 'object')) {
                $labels[$colName] = $config['label'] ?? null;
            }
        }

        return $labels;
    }

    /**
     * Save a query for reuse.
     *
     * @param array $data The query data
     *
     * @return int The saved query ID
     */
    public function saveQuery(array $data): int
    {
        $now = date('Y-m-d H:i:s');

        // Check if updating an existing query
        if (!empty($data['id'])) {
            DB::table('report_query')
                ->where('id', $data['id'])
                ->update([
                    'name' => $data['name'] ?? 'Unnamed Query',
                    'query_text' => $data['query_text'] ?? '',
                    'query_type' => $data['query_type'] ?? 'visual',
                    'visual_config' => isset($data['visual_config']) ? json_encode($data['visual_config']) : null,
                    'parameters' => isset($data['parameters']) ? json_encode($data['parameters']) : null,
                    'row_limit' => $data['row_limit'] ?? 1000,
                    'timeout_seconds' => $data['timeout_seconds'] ?? 30,
                    'is_shared' => $data['is_shared'] ?? 0,
                    'report_id' => $data['report_id'] ?? null,
                    'section_id' => $data['section_id'] ?? null,
                    'updated_at' => $now,
                ]);

            return (int) $data['id'];
        }

        return DB::table('report_query')->insertGetId([
            'report_id' => $data['report_id'] ?? null,
            'section_id' => $data['section_id'] ?? null,
            'name' => $data['name'] ?? 'Unnamed Query',
            'query_text' => $data['query_text'] ?? '',
            'query_type' => $data['query_type'] ?? 'visual',
            'visual_config' => isset($data['visual_config']) ? json_encode($data['visual_config']) : null,
            'parameters' => isset($data['parameters']) ? json_encode($data['parameters']) : null,
            'row_limit' => $data['row_limit'] ?? 1000,
            'timeout_seconds' => $data['timeout_seconds'] ?? 30,
            'created_by' => $data['created_by'],
            'is_shared' => $data['is_shared'] ?? 0,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    /**
     * Get saved queries for a user.
     *
     * @param int $userId The user ID
     *
     * @return array The saved queries
     */
    public function getSavedQueries(int $userId): array
    {
        return DB::table('report_query')
            ->where(function ($q) use ($userId) {
                $q->where('created_by', $userId)
                  ->orWhere('is_shared', 1);
            })
            ->orderBy('name')
            ->get()
            ->map(function ($query) {
                $query->visual_config = json_decode($query->visual_config, true) ?: [];
                $query->parameters = json_decode($query->parameters, true) ?: [];

                return $query;
            })
            ->toArray();
    }

    /**
     * Delete a saved query.
     *
     * Only the owner or an administrator can delete a query.
     *
     * @param int $queryId The query ID
     * @param int $userId  The user requesting deletion
     *
     * @return bool True if deleted
     *
     * @throws \RuntimeException If the user is not authorized
     */
    public function deleteQuery(int $queryId, int $userId): bool
    {
        $query = DB::table('report_query')->where('id', $queryId)->first();
        if (!$query) {
            return false;
        }

        // Check ownership or admin (group_id 100 = administrator)
        if ((int) $query->created_by !== $userId) {
            $isAdmin = DB::table('acl_user_group')
                ->where('user_id', $userId)
                ->where('group_id', 100)
                ->exists();

            if (!$isAdmin) {
                throw new \RuntimeException('You can only delete your own queries');
            }
        }

        return DB::table('report_query')
            ->where('id', $queryId)
            ->delete() > 0;
    }

    /**
     * Check if a SQL string is read-only (SELECT only).
     *
     * @param string $sql The SQL to check
     *
     * @return bool True if read-only
     */
    private function isReadOnly(string $sql): bool
    {
        $trimmed = trim($sql);

        // Must start with SELECT
        if (!preg_match('/^\s*SELECT\b/i', $trimmed)) {
            return false;
        }

        // Check for dangerous keywords
        foreach ($this->dangerousKeywords as $keyword) {
            // Use word boundary to avoid false positives (e.g., "CREATED_AT" matching "CREATE")
            if (preg_match('/\b' . preg_quote($keyword, '/') . '\b/i', $trimmed)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Ensure a SQL query has a LIMIT clause, enforcing a maximum.
     *
     * @param string $sql      The SQL query
     * @param int    $maxLimit The maximum number of rows
     *
     * @return string The SQL with LIMIT enforced
     */
    private function enforceLimit(string $sql, int $maxLimit): string
    {
        $trimmed = rtrim(trim($sql), ';');

        // Check if LIMIT already exists
        if (preg_match('/\bLIMIT\s+(\d+)/i', $trimmed, $matches)) {
            $existingLimit = (int) $matches[1];
            if ($existingLimit > $maxLimit) {
                $trimmed = preg_replace('/\bLIMIT\s+\d+/i', "LIMIT {$maxLimit}", $trimmed);
            }
        } else {
            $trimmed .= " LIMIT {$maxLimit}";
        }

        return $trimmed;
    }
}
