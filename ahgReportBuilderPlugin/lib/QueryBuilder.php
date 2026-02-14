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
     * Relevant AtoM tables available for querying.
     *
     * @var array
     */
    private array $relevantTables = [
        'information_object', 'information_object_i18n',
        'actor', 'actor_i18n',
        'repository', 'repository_i18n',
        'term', 'term_i18n',
        'taxonomy', 'taxonomy_i18n',
        'accession', 'accession_i18n',
        'digital_object',
        'relation',
        'event',
        'note', 'note_i18n',
        'status',
        'property', 'property_i18n',
        'slug',
        'physical_object', 'physical_object_i18n',
        'rights', 'rights_i18n',
        'rights_holder',
        'donor',
        'contact_information',
        'object',
        'user',
        'custom_report',
        'report_section',
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
        // Validate user is administrator
        if ($userId !== null) {
            $isAdmin = DB::table('user')
                ->join('aclUserGroup as aug', 'user.id', '=', 'aug.userId')
                ->join('aclGroup as ag', 'aug.groupId', '=', 'ag.id')
                ->where('user.id', $userId)
                ->where('ag.name', 'administrator')
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
     * Get the list of available database tables for querying.
     *
     * @return array The available tables
     */
    public function getAvailableTables(): array
    {
        $dbName = DB::connection()->getDatabaseName();

        $allTables = DB::select(
            'SELECT TABLE_NAME, TABLE_ROWS, TABLE_COMMENT
             FROM information_schema.TABLES
             WHERE TABLE_SCHEMA = ?
             ORDER BY TABLE_NAME',
            [$dbName]
        );

        // Filter to relevant tables
        $result = [];
        foreach ($allTables as $table) {
            $tableName = $table->TABLE_NAME;
            if (in_array($tableName, $this->relevantTables, true)) {
                $result[] = [
                    'name' => $tableName,
                    'rows' => $table->TABLE_ROWS,
                    'comment' => $table->TABLE_COMMENT,
                ];
            }
        }

        return $result;
    }

    /**
     * Get columns for a specific table.
     *
     * @param string $tableName The table name
     *
     * @return array The column definitions
     */
    public function getTableColumns(string $tableName): array
    {
        $dbName = DB::connection()->getDatabaseName();

        return DB::select(
            'SELECT COLUMN_NAME, DATA_TYPE, COLUMN_TYPE, IS_NULLABLE,
                    COLUMN_DEFAULT, COLUMN_KEY, COLUMN_COMMENT
             FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?
             ORDER BY ORDINAL_POSITION',
            [$dbName, $tableName]
        );
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

        // Check ownership or admin
        if ((int) $query->created_by !== $userId) {
            $isAdmin = DB::table('user')
                ->join('aclUserGroup as aug', 'user.id', '=', 'aug.userId')
                ->join('aclGroup as ag', 'aug.groupId', '=', 'ag.id')
                ->where('user.id', $userId)
                ->where('ag.name', 'administrator')
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
