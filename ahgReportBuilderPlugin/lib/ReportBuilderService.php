<?php

use Illuminate\Database\Capsule\Manager as DB;

/**
 * Report Builder Service.
 *
 * Main service class for building and executing custom reports.
 */
class ReportBuilderService
{
    private string $culture;

    public function __construct(string $culture = 'en')
    {
        $this->culture = $culture;
    }

    /**
     * Get all custom reports for a user.
     *
     * @param int|null $userId The user ID (null for all accessible reports)
     *
     * @return array The reports
     */
    public function getReports(?int $userId = null): array
    {
        $query = DB::table('custom_report')
            ->select('*')
            ->orderBy('category')
            ->orderBy('sort_order')
            ->orderBy('name');

        if ($userId !== null) {
            $query->where(function ($q) use ($userId) {
                $q->where('user_id', $userId)
                  ->orWhere('is_shared', 1);
            });
        }

        return $query->get()->toArray();
    }

    /**
     * Get a single custom report by ID.
     *
     * @param int $id The report ID
     *
     * @return object|null The report or null if not found
     */
    public function getReport(int $id): ?object
    {
        $report = DB::table('custom_report')
            ->where('id', $id)
            ->first();

        if ($report) {
            // Decode JSON fields
            $report->layout = json_decode($report->layout, true) ?: [];
            $report->columns = json_decode($report->columns, true) ?: [];
            $report->filters = json_decode($report->filters, true) ?: [];
            $report->charts = json_decode($report->charts, true) ?: [];
            $report->sort_config = json_decode($report->sort_config, true) ?: [];
        }

        return $report;
    }

    /**
     * Create a new custom report.
     *
     * @param array $data The report data
     *
     * @return int The new report ID
     */
    public function createReport(array $data): int
    {
        $now = date('Y-m-d H:i:s');

        return DB::table('custom_report')->insertGetId([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'user_id' => $data['user_id'] ?? null,
            'is_shared' => $data['is_shared'] ?? 0,
            'is_public' => $data['is_public'] ?? 0,
            'layout' => json_encode($data['layout'] ?? []),
            'data_source' => $data['data_source'],
            'columns' => json_encode($data['columns'] ?? []),
            'filters' => json_encode($data['filters'] ?? []),
            'charts' => json_encode($data['charts'] ?? []),
            'sort_config' => json_encode($data['sort_config'] ?? []),
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    /**
     * Update an existing custom report.
     *
     * @param int   $id   The report ID
     * @param array $data The report data
     *
     * @return bool True if updated successfully
     */
    public function updateReport(int $id, array $data): bool
    {
        $updateData = [
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        // Only update fields that are provided
        $fields = ['name', 'description', 'is_shared', 'is_public', 'data_source'];
        foreach ($fields as $field) {
            if (isset($data[$field])) {
                $updateData[$field] = $data[$field];
            }
        }

        // JSON fields
        $jsonFields = ['layout', 'columns', 'filters', 'charts', 'sort_config'];
        foreach ($jsonFields as $field) {
            if (isset($data[$field])) {
                $updateData[$field] = json_encode($data[$field]);
            }
        }

        return DB::table('custom_report')
            ->where('id', $id)
            ->update($updateData) > 0;
    }

    /**
     * Delete a custom report.
     *
     * @param int $id The report ID
     *
     * @return bool True if deleted successfully
     */
    public function deleteReport(int $id): bool
    {
        return DB::table('custom_report')
            ->where('id', $id)
            ->delete() > 0;
    }

    /**
     * Clone a custom report.
     *
     * @param int $id     The report ID to clone
     * @param int $userId The user ID for the cloned report
     *
     * @return int The new report ID
     */
    public function cloneReport(int $id, int $userId): int
    {
        $report = $this->getReport($id);
        if (!$report) {
            throw new InvalidArgumentException("Report not found: {$id}");
        }

        return $this->createReport([
            'name' => $report->name . ' (Copy)',
            'description' => $report->description,
            'user_id' => $userId,
            'is_shared' => 0,
            'is_public' => 0,
            'layout' => $report->layout,
            'data_source' => $report->data_source,
            'columns' => $report->columns,
            'filters' => $report->filters,
            'charts' => $report->charts,
            'sort_config' => $report->sort_config,
        ]);
    }

    /**
     * Execute a custom report and return data.
     *
     * @param int   $reportId     The report ID
     * @param array $runtimeFilters Optional runtime filters
     * @param int   $page         Page number
     * @param int   $limit        Results per page
     *
     * @return array The report results
     */
    public function executeReport(int $reportId, array $runtimeFilters = [], int $page = 1, int $limit = 50): array
    {
        $report = $this->getReport($reportId);
        if (!$report) {
            throw new InvalidArgumentException("Report not found: {$reportId}");
        }

        return $this->executeReportDefinition(
            $report->data_source,
            $report->columns,
            array_merge($report->filters, $runtimeFilters),
            $report->sort_config,
            $page,
            $limit
        );
    }

    /**
     * Execute a report definition directly (for preview).
     *
     * @param string $dataSource The data source key
     * @param array  $columns    The columns to include
     * @param array  $filters    The filters to apply
     * @param array  $sortConfig The sort configuration
     * @param int    $page       Page number
     * @param int    $limit      Results per page
     *
     * @return array The report results
     */
    public function executeReportDefinition(
        string $dataSource,
        array $columns,
        array $filters = [],
        array $sortConfig = [],
        int $page = 1,
        int $limit = 50
    ): array {
        $source = DataSourceRegistry::get($dataSource);
        if (!$source) {
            throw new InvalidArgumentException("Unknown data source: {$dataSource}");
        }

        $query = $this->buildQuery($source, $columns, $filters, $sortConfig);

        // Get total count
        $total = $query->count();

        // Apply pagination
        $offset = ($page - 1) * $limit;
        $results = $query->offset($offset)->limit($limit)->get();

        // Resolve foreign key IDs to readable text
        $resolvedResults = $this->resolveColumnValues($results->toArray(), $columns, $dataSource);

        return [
            'results' => $resolvedResults,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'pages' => ceil($total / $limit),
        ];
    }

    /**
     * Resolve foreign key IDs to human-readable text values.
     *
     * @param array  $results    The query results
     * @param array  $columns    The selected columns
     * @param string $dataSource The data source key
     *
     * @return array The results with resolved values
     */
    private function resolveColumnValues(array $results, array $columns, string $dataSource): array
    {
        if (empty($results)) {
            return $results;
        }

        // Get column definitions to know which need resolution
        $columnDefs = ColumnDiscovery::getColumns($dataSource);

        // Known ENUM columns that should be formatted nicely (not looked up as term IDs)
        $enumColumns = [
            'current_status', 'custody_type', 'acquisition_type', 'certainty_level',
            'research_status', 'cultural_property_status', 'condition_rating',
            'severity', 'priority', 'status', 'classification', 'access_level',
            'visibility', 'type', 'category',
        ];

        // Collect all IDs that need lookup
        $termIds = [];
        $repositoryIds = [];
        $actorIds = [];
        $infoObjectIds = [];
        $userIds = [];

        foreach ($results as $row) {
            $rowArray = (array) $row;
            foreach ($columns as $column) {
                $colDef = $columnDefs[$column] ?? null;
                $value = $rowArray[$column] ?? null;

                if (empty($value) || !is_numeric($value)) {
                    continue;
                }

                // Skip ENUM columns - they contain string values, not IDs
                if (in_array($column, $enumColumns)) {
                    continue;
                }

                // First check explicit type from column definition
                $type = $colDef['type'] ?? 'string';

                if ($type === 'term') {
                    $termIds[$value] = true;
                } elseif ($type === 'repository') {
                    $repositoryIds[$value] = true;
                } elseif ($type === 'actor') {
                    $actorIds[$value] = true;
                } elseif ($type === 'information_object') {
                    $infoObjectIds[$value] = true;
                } elseif ($type === 'user') {
                    $userIds[$value] = true;
                }
                // Also detect by column name pattern for dynamically discovered columns
                elseif ($type === 'integer') {
                    // Common term/taxonomy foreign keys (but not ENUM columns)
                    if (preg_match('/_(type|status|level|category|classification)_id$/', $column) ||
                        preg_match('/^(type|status|level)_id$/', $column) ||
                        $column === 'level_of_description_id' ||
                        $column === 'entity_type_id' ||
                        $column === 'desc_status_id' ||
                        $column === 'desc_detail_id' ||
                        $column === 'media_type_id' ||
                        $column === 'usage_id') {
                        $termIds[$value] = true;
                    }
                    // Repository foreign keys
                    elseif ($column === 'repository_id') {
                        $repositoryIds[$value] = true;
                    }
                    // Actor/agent foreign keys
                    elseif (preg_match('/_(actor|agent|creator|donor)_id$/', $column) ||
                            $column === 'provenance_agent_id') {
                        $actorIds[$value] = true;
                    }
                    // Information object foreign keys
                    elseif ($column === 'information_object_id' || $column === 'object_id') {
                        $infoObjectIds[$value] = true;
                    }
                    // User foreign keys
                    elseif (preg_match('/_(user|by)_id$/', $column) ||
                            $column === 'user_id' ||
                            $column === 'created_by' ||
                            $column === 'updated_by') {
                        $userIds[$value] = true;
                    }
                }
            }
        }

        // Batch lookup all needed values
        $termNames = $this->lookupTermNames(array_keys($termIds));
        $repoNames = $this->lookupRepositoryNames(array_keys($repositoryIds));
        $actorNames = $this->lookupActorNames(array_keys($actorIds));
        $ioTitles = $this->lookupInformationObjectTitles(array_keys($infoObjectIds));
        $userNames = $this->lookupUserNames(array_keys($userIds));

        // Replace IDs with text values
        $resolvedResults = [];
        foreach ($results as $row) {
            $rowArray = (array) $row;
            foreach ($columns as $column) {
                $colDef = $columnDefs[$column] ?? null;
                $value = $rowArray[$column] ?? null;

                if (empty($value) && $value !== 0 && $value !== '0') {
                    continue;
                }

                $type = $colDef['type'] ?? 'string';

                // Format ENUM values nicely (convert snake_case to Title Case)
                if (in_array($column, $enumColumns) && is_string($value)) {
                    $rowArray[$column] = $this->formatEnumValue($value);
                    continue;
                }

                // Explicit type resolution
                if ($type === 'term' && isset($termNames[$value])) {
                    $rowArray[$column] = $termNames[$value];
                } elseif ($type === 'repository' && isset($repoNames[$value])) {
                    $rowArray[$column] = $repoNames[$value];
                } elseif ($type === 'actor' && isset($actorNames[$value])) {
                    $rowArray[$column] = $actorNames[$value];
                } elseif ($type === 'information_object' && isset($ioTitles[$value])) {
                    $rowArray[$column] = $ioTitles[$value];
                } elseif ($type === 'user' && isset($userNames[$value])) {
                    $rowArray[$column] = $userNames[$value];
                } elseif ($type === 'boolean') {
                    $rowArray[$column] = $value ? 'Yes' : 'No';
                } elseif ($type === 'enum' && is_string($value)) {
                    $rowArray[$column] = $this->formatEnumValue($value);
                }
                // Pattern-based resolution for integer columns
                elseif ($type === 'integer' && is_numeric($value)) {
                    if (isset($termNames[$value]) && (
                        preg_match('/_(type|status|level|category|classification)_id$/', $column) ||
                        preg_match('/^(type|status|level)_id$/', $column) ||
                        in_array($column, ['level_of_description_id', 'entity_type_id', 'desc_status_id',
                            'desc_detail_id', 'media_type_id', 'usage_id'])
                    )) {
                        $rowArray[$column] = $termNames[$value];
                    } elseif ($column === 'repository_id' && isset($repoNames[$value])) {
                        $rowArray[$column] = $repoNames[$value];
                    } elseif (isset($actorNames[$value]) && (
                        preg_match('/_(actor|agent|creator|donor)_id$/', $column) ||
                        $column === 'provenance_agent_id'
                    )) {
                        $rowArray[$column] = $actorNames[$value];
                    } elseif (isset($ioTitles[$value]) && in_array($column, ['information_object_id', 'object_id'])) {
                        $rowArray[$column] = $ioTitles[$value];
                    } elseif (isset($userNames[$value]) && (
                        preg_match('/_(user|by)_id$/', $column) ||
                        in_array($column, ['user_id', 'created_by', 'updated_by'])
                    )) {
                        $rowArray[$column] = $userNames[$value];
                    }
                }
            }
            $resolvedResults[] = (object) $rowArray;
        }

        return $resolvedResults;
    }

    /**
     * Format ENUM value nicely (snake_case to Title Case).
     *
     * @param string $value The ENUM value (e.g., 'on_loan', 'in_progress')
     *
     * @return string Formatted value (e.g., 'On Loan', 'In Progress')
     */
    private function formatEnumValue(string $value): string
    {
        // Replace underscores with spaces and capitalize each word
        return ucwords(str_replace('_', ' ', $value));
    }

    /**
     * Lookup user names by IDs.
     */
    private function lookupUserNames(array $ids): array
    {
        if (empty($ids)) {
            return [];
        }

        $results = DB::table('user')
            ->whereIn('id', $ids)
            ->get()
            ->mapWithKeys(function ($user) {
                return [$user->id => $user->username ?: $user->email];
            })
            ->toArray();

        return $results;
    }

    /**
     * Lookup term names by IDs.
     */
    private function lookupTermNames(array $ids): array
    {
        if (empty($ids)) {
            return [];
        }

        $results = DB::table('term_i18n')
            ->whereIn('id', $ids)
            ->where('culture', $this->culture)
            ->pluck('name', 'id')
            ->toArray();

        return $results;
    }

    /**
     * Lookup repository names by IDs.
     */
    private function lookupRepositoryNames(array $ids): array
    {
        if (empty($ids)) {
            return [];
        }

        $results = DB::table('actor_i18n')
            ->whereIn('id', $ids)
            ->where('culture', $this->culture)
            ->pluck('authorized_form_of_name', 'id')
            ->toArray();

        return $results;
    }

    /**
     * Lookup actor names by IDs.
     */
    private function lookupActorNames(array $ids): array
    {
        if (empty($ids)) {
            return [];
        }

        $results = DB::table('actor_i18n')
            ->whereIn('id', $ids)
            ->where('culture', $this->culture)
            ->pluck('authorized_form_of_name', 'id')
            ->toArray();

        return $results;
    }

    /**
     * Lookup information object titles by IDs.
     */
    private function lookupInformationObjectTitles(array $ids): array
    {
        if (empty($ids)) {
            return [];
        }

        $results = DB::table('information_object_i18n')
            ->whereIn('id', $ids)
            ->where('culture', $this->culture)
            ->pluck('title', 'id')
            ->toArray();

        return $results;
    }

    /**
     * Get aggregated data for charts.
     *
     * @param int   $reportId    The report ID
     * @param array $chartConfig The chart configuration
     *
     * @return array The chart data
     */
    public function getChartData(int $reportId, array $chartConfig): array
    {
        $report = $this->getReport($reportId);
        if (!$report) {
            throw new InvalidArgumentException("Report not found: {$reportId}");
        }

        $source = DataSourceRegistry::get($report->data_source);
        if (!$source) {
            throw new InvalidArgumentException("Unknown data source: {$report->data_source}");
        }

        return $this->buildChartQuery($source, $chartConfig, $report->filters);
    }

    /**
     * Build a query for a data source.
     *
     * @param array $source     The data source config
     * @param array $columns    The columns to select
     * @param array $filters    The filters to apply
     * @param array $sortConfig The sort configuration
     *
     * @return \Illuminate\Database\Query\Builder
     */
    private function buildQuery(array $source, array $columns, array $filters, array $sortConfig)
    {
        $table = $source['table'];
        $alias = substr($table, 0, 1);

        $query = DB::table("{$table} as {$alias}");

        // Join object table if needed
        if (isset($source['object_table'])) {
            $query->join('object as o', "{$alias}.id", '=', 'o.id');
            if (isset($source['class_name'])) {
                $query->where('o.class_name', $source['class_name']);
            }
        }

        // Apply sector filter (for GLAM sector-based data sources)
        if (isset($source['sector_filter'])) {
            $sector = $source['sector_filter'];
            $query->join('display_standard_sector as dss', "{$alias}.display_standard_id", '=', 'dss.term_id')
                  ->where('dss.sector', $sector);
        }

        // Join i18n table if needed and columns require it
        if (isset($source['i18n_table'])) {
            $i18nTable = $source['i18n_table'];
            $query->leftJoin("{$i18nTable} as i18n", function ($join) use ($alias) {
                $join->on("{$alias}.id", '=', 'i18n.id')
                     ->where('i18n.culture', $this->culture);
            });
        }

        // Join actor_i18n for entities extending Actor (Repository, Donor)
        if ($table === 'repository' || !empty($source['joins_actor_i18n'])) {
            $query->leftJoin('actor_i18n as actor_i18n', function ($join) use ($alias) {
                $join->on("{$alias}.id", '=', 'actor_i18n.id')
                     ->where('actor_i18n.culture', $this->culture);
            });
        }

        // Join contact_information for donor
        if (!empty($source['joins_contact'])) {
            $query->leftJoin('contact_information as contact', "{$alias}.id", '=', 'contact.actor_id');
        }

        // Build select columns
        $selectColumns = $this->buildSelectColumns($alias, $source, $columns);
        $query->select($selectColumns);

        // Apply filters
        $this->applyFilters($query, $alias, $filters, $source);

        // Apply sorting
        $this->applySorting($query, $alias, $source, $sortConfig);

        return $query;
    }

    /**
     * Build select columns for the query.
     *
     * @param string $alias   The main table alias
     * @param array  $source  The data source config
     * @param array  $columns The columns to select
     *
     * @return array The select columns
     */
    private function buildSelectColumns(string $alias, array $source, array $columns): array
    {
        $columnDefs = ColumnDiscovery::getColumns($source['table'] === 'function_object' ? 'function' : str_replace('_', '_', $source['table']));
        $select = [];

        // Always include ID
        $select[] = "{$alias}.id";

        foreach ($columns as $column) {
            if ($column === 'id') {
                continue;
            }

            $columnDef = $columnDefs[$column] ?? null;
            if (!$columnDef) {
                continue;
            }

            switch ($columnDef['source']) {
                case 'main':
                    $select[] = "{$alias}.{$column}";
                    break;
                case 'i18n':
                    $select[] = "i18n.{$column}";
                    break;
                case 'actor_i18n':
                    $select[] = "actor_i18n.{$column}";
                    break;
                case 'contact':
                    $select[] = "contact.{$column}";
                    break;
                case 'object':
                    $select[] = "o.{$column}";
                    break;
                case 'computed':
                    // Handle computed columns with subqueries
                    $computed = $this->getComputedColumnSelect($alias, $source, $column);
                    if ($computed) {
                        $select[] = $computed;
                    }
                    break;
            }
        }

        return $select;
    }

    /**
     * Get a computed column select expression.
     *
     * @param string $alias  The main table alias
     * @param array  $source The data source config
     * @param string $column The computed column name
     *
     * @return \Illuminate\Database\Query\Expression|null
     */
    private function getComputedColumnSelect(string $alias, array $source, string $column)
    {
        switch ($column) {
            case 'publication_status':
                return DB::raw("(SELECT t.name FROM status s
                    JOIN term_i18n t ON s.status_id = t.id AND t.culture = '{$this->culture}'
                    WHERE s.object_id = {$alias}.id AND s.type_id = 159 LIMIT 1) as publication_status");

            case 'has_digital_object':
                return DB::raw("(SELECT COUNT(*) > 0 FROM digital_object d
                    WHERE d.information_object_id = {$alias}.id) as has_digital_object");

            case 'child_count':
                return DB::raw("(SELECT COUNT(*) FROM information_object c
                    WHERE c.parent_id = {$alias}.id) as child_count");

            case 'holdings_count':
                return DB::raw("(SELECT COUNT(*) FROM information_object i
                    WHERE i.repository_id = {$alias}.id) as holdings_count");

            case 'linked_descriptions_count':
                return DB::raw("(SELECT COUNT(*) FROM relation r
                    WHERE r.object_id = {$alias}.id OR r.subject_id = {$alias}.id) as linked_descriptions_count");
        }

        return null;
    }

    /**
     * Apply filters to the query.
     *
     * @param \Illuminate\Database\Query\Builder $query   The query builder
     * @param string                             $alias   The main table alias
     * @param array                              $filters The filters to apply
     * @param array|null                         $source  The data source config
     */
    private function applyFilters($query, string $alias, array $filters, ?array $source = null): void
    {
        foreach ($filters as $filter) {
            if (!isset($filter['column']) || !isset($filter['operator'])) {
                continue;
            }

            $column = $filter['column'];
            $operator = $filter['operator'];
            $value = $filter['value'] ?? null;

            // Determine the qualified column name
            $qualifiedColumn = $this->getQualifiedColumnName($alias, $column, $source);

            switch ($operator) {
                case 'equals':
                    $query->where($qualifiedColumn, '=', $value);
                    break;
                case 'not_equals':
                    $query->where($qualifiedColumn, '!=', $value);
                    break;
                case 'contains':
                    $query->where($qualifiedColumn, 'LIKE', "%{$value}%");
                    break;
                case 'starts_with':
                    $query->where($qualifiedColumn, 'LIKE', "{$value}%");
                    break;
                case 'ends_with':
                    $query->where($qualifiedColumn, 'LIKE', "%{$value}");
                    break;
                case 'greater_than':
                    $query->where($qualifiedColumn, '>', $value);
                    break;
                case 'less_than':
                    $query->where($qualifiedColumn, '<', $value);
                    break;
                case 'between':
                    if (isset($filter['value2'])) {
                        $query->whereBetween($qualifiedColumn, [$value, $filter['value2']]);
                    }
                    break;
                case 'is_null':
                    $query->whereNull($qualifiedColumn);
                    break;
                case 'is_not_null':
                    $query->whereNotNull($qualifiedColumn);
                    break;
                case 'in':
                    if (is_array($value)) {
                        $query->whereIn($qualifiedColumn, $value);
                    }
                    break;
            }
        }
    }

    /**
     * Get the qualified column name for filtering.
     *
     * @param string     $alias  The main table alias
     * @param string     $column The column name
     * @param array|null $source The data source config (optional)
     *
     * @return string The qualified column name
     */
    private function getQualifiedColumnName(string $alias, string $column, ?array $source = null): string
    {
        // Columns that come from actor_i18n (for entities extending Actor)
        $actorI18nColumns = ['authorized_form_of_name', 'history', 'mandates', 'internal_structures',
            'dates_of_existence', 'places', 'legal_status', 'functions', 'general_context'];

        // Columns that come from contact_information
        $contactColumns = ['contact_person', 'email', 'telephone', 'street_address', 'website',
            'postal_code', 'country_code', 'fax', 'city', 'region'];

        // For entities that join actor_i18n (repository, donor)
        if ($source !== null && isset($source['table'])) {
            $table = $source['table'];
            $joinsActorI18n = $table === 'repository' || !empty($source['joins_actor_i18n']);

            if ($joinsActorI18n && in_array($column, $actorI18nColumns)) {
                return "actor_i18n.{$column}";
            }

            // For entities that join contact_information (donor)
            if (!empty($source['joins_contact']) && in_array($column, $contactColumns)) {
                return "contact.{$column}";
            }
        }

        // Check if it's an i18n column (only if source has i18n table)
        $i18nColumns = ['title', 'name', 'description', 'authorized_form_of_name', 'scope_and_content',
            'archival_history', 'acquisition', 'appraisal', 'accruals', 'arrangement', 'access_conditions',
            'reproduction_conditions', 'physical_characteristics', 'finding_aids', 'location_of_originals',
            'location_of_copies', 'related_units_of_description', 'rules', 'sources', 'revision_history',
            'alternate_title', 'extent_and_medium', 'history', 'places', 'legal_status', 'functions',
            'mandates', 'internal_structures', 'general_context', 'geocultural_context', 'collecting_policies',
            'buildings', 'holdings', 'opening_times', 'disabled_access', 'research_services',
            'reproduction_services', 'public_facilities', 'location', 'processing_notes',
            'source_of_acquisition', 'location_information', 'received_extent_units'];

        // Only use i18n prefix if source has an i18n table
        $hasI18n = $source === null || (isset($source['i18n_table']) && $source['i18n_table']);
        if ($hasI18n && in_array($column, $i18nColumns)) {
            return "i18n.{$column}";
        }

        // Check if it's an object column - only if source has object_table
        $hasObjectTable = $source === null || (isset($source['object_table']) && $source['object_table']);
        if ($hasObjectTable && in_array($column, ['created_at', 'updated_at'])) {
            return "o.{$column}";
        }

        return "{$alias}.{$column}";
    }

    /**
     * Apply sorting to the query.
     *
     * @param \Illuminate\Database\Query\Builder $query      The query builder
     * @param string                             $alias      The main table alias
     * @param array                              $source     The data source config
     * @param array                              $sortConfig The sort configuration
     */
    private function applySorting($query, string $alias, array $source, array $sortConfig): void
    {
        if (empty($sortConfig)) {
            // Default sort - use object table only if available
            if (isset($source['object_table']) && $source['object_table']) {
                $query->orderBy('o.updated_at', 'desc');
            } else {
                // Fallback to main table id for tables without object relationship
                $query->orderBy("{$alias}.id", 'desc');
            }

            return;
        }

        foreach ($sortConfig as $sort) {
            if (!isset($sort['column'])) {
                continue;
            }

            $column = $this->getQualifiedColumnName($alias, $sort['column'], $source);
            $direction = isset($sort['direction']) && strtolower($sort['direction']) === 'asc' ? 'asc' : 'desc';

            $query->orderBy($column, $direction);
        }
    }

    /**
     * Build a chart query.
     *
     * @param array $source      The data source config
     * @param array $chartConfig The chart configuration
     * @param array $filters     The filters to apply
     *
     * @return array The chart data
     */
    private function buildChartQuery(array $source, array $chartConfig, array $filters): array
    {
        $table = $source['table'];
        $alias = substr($table, 0, 1);

        $query = DB::table("{$table} as {$alias}");

        // Join object table if needed
        if (isset($source['object_table'])) {
            $query->join('object as o', "{$alias}.id", '=', 'o.id');
            if (isset($source['class_name'])) {
                $query->where('o.class_name', $source['class_name']);
            }
        }

        // Apply sector filter (for GLAM sector-based data sources)
        if (isset($source['sector_filter'])) {
            $sector = $source['sector_filter'];
            $query->join('display_standard_sector as dss', "{$alias}.display_standard_id", '=', 'dss.term_id')
                  ->where('dss.sector', $sector);
        }

        // Join i18n table if needed
        if (isset($source['i18n_table'])) {
            $i18nTable = $source['i18n_table'];
            $query->leftJoin("{$i18nTable} as i18n", function ($join) use ($alias) {
                $join->on("{$alias}.id", '=', 'i18n.id')
                     ->where('i18n.culture', $this->culture);
            });
        }

        // Join actor_i18n for entities extending Actor (Repository, Donor)
        if ($table === 'repository' || !empty($source['joins_actor_i18n'])) {
            $query->leftJoin('actor_i18n as actor_i18n', function ($join) use ($alias) {
                $join->on("{$alias}.id", '=', 'actor_i18n.id')
                     ->where('actor_i18n.culture', $this->culture);
            });
        }

        // Join contact_information for donor
        if (!empty($source['joins_contact'])) {
            $query->leftJoin('contact_information as contact', "{$alias}.id", '=', 'contact.actor_id');
        }

        // Apply filters
        $this->applyFilters($query, $alias, $filters, $source);

        // Build aggregation based on chart type
        $groupBy = $chartConfig['groupBy'] ?? null;
        $aggregate = $chartConfig['aggregate'] ?? 'count';

        if (!$groupBy) {
            // Single value aggregation
            switch ($aggregate) {
                case 'count':
                    return ['value' => $query->count()];
                case 'sum':
                    $field = $chartConfig['field'] ?? 'id';

                    return ['value' => $query->sum($this->getQualifiedColumnName($alias, $field))];
            }
        }

        // Grouped aggregation
        $groupColumn = $this->getQualifiedColumnName($alias, $groupBy);

        switch ($aggregate) {
            case 'count':
                $results = $query->select($groupColumn, DB::raw('COUNT(*) as value'))
                    ->groupBy($groupColumn)
                    ->orderBy('value', 'desc')
                    ->limit(20)
                    ->get();
                break;
            default:
                $results = $query->select($groupColumn, DB::raw('COUNT(*) as value'))
                    ->groupBy($groupColumn)
                    ->orderBy('value', 'desc')
                    ->limit(20)
                    ->get();
        }

        // Known ENUM columns that should be formatted nicely
        $enumColumns = [
            'current_status', 'custody_type', 'acquisition_type', 'certainty_level',
            'research_status', 'cultural_property_status', 'condition_rating',
            'severity', 'priority', 'status', 'classification', 'access_level',
            'visibility', 'type', 'category',
        ];

        $labels = [];
        $data = [];
        foreach ($results as $row) {
            $label = $row->{$groupBy} ?? 'Unknown';
            // Format ENUM values nicely
            if (in_array($groupBy, $enumColumns) && is_string($label)) {
                $label = $this->formatEnumValue($label);
            }
            $labels[] = $label ?: 'Unknown';
            $data[] = $row->value;
        }

        return [
            'labels' => $labels,
            'data' => $data,
        ];
    }

    /**
     * Get statistics for the report builder dashboard.
     *
     * @param int|null $userId The user ID
     *
     * @return array The statistics
     */
    public function getStatistics(?int $userId = null): array
    {
        $query = DB::table('custom_report');
        if ($userId !== null) {
            $query->where(function ($q) use ($userId) {
                $q->where('user_id', $userId)
                  ->orWhere('is_shared', 1);
            });
        }

        $total = $query->count();

        $bySource = DB::table('custom_report')
            ->select('data_source', DB::raw('COUNT(*) as count'))
            ->groupBy('data_source')
            ->get()
            ->pluck('count', 'data_source')
            ->toArray();

        $recentReports = DB::table('custom_report')
            ->select('id', 'name', 'updated_at')
            ->orderBy('updated_at', 'desc')
            ->limit(5)
            ->get()
            ->toArray();

        return [
            'total_reports' => $total,
            'by_source' => $bySource,
            'recent_reports' => $recentReports,
        ];
    }
}
