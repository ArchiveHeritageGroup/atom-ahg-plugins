<?php

/**
 * Generic CLI task for importing data using saved mappings.
 *
 * Usage:
 *   php symfony migration:import /path/to/file.csv --mapping=3
 *   php symfony migration:import /path/to/file.xlsx --mapping="Vernon CMS (Museum)"
 *   php symfony migration:import /path/to/file.xml --mapping=11 --dry-run
 *   php symfony migration:list-mappings
 */
class migrationImportTask extends sfBaseTask
{
    protected function configure()
    {
        $this->addArguments([
            new sfCommandArgument('source', sfCommandArgument::OPTIONAL, 'Path to import file (CSV, Excel, XML, JSON, OPEX, PAX)'),
        ]);

        $this->addOptions([
            new sfCommandOption('application', null, sfCommandOption::PARAMETER_REQUIRED, 'The application name', 'qubit'),
            new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'The environment', 'cli'),
            new sfCommandOption('mapping', null, sfCommandOption::PARAMETER_REQUIRED, 'Mapping ID or name from atom_data_mapping'),
            new sfCommandOption('list-mappings', null, sfCommandOption::PARAMETER_NONE, 'List available mappings'),
            new sfCommandOption('repository', null, sfCommandOption::PARAMETER_REQUIRED, 'Repository ID for imported records'),
            new sfCommandOption('parent', null, sfCommandOption::PARAMETER_REQUIRED, 'Parent information object ID'),
            new sfCommandOption('culture', null, sfCommandOption::PARAMETER_REQUIRED, 'Culture/language code', 'en'),
            new sfCommandOption('update', null, sfCommandOption::PARAMETER_NONE, 'Update existing records if found'),
            new sfCommandOption('match-field', null, sfCommandOption::PARAMETER_REQUIRED, 'Field to match existing records', 'legacyId'),
            new sfCommandOption('output', null, sfCommandOption::PARAMETER_REQUIRED, 'Output mode: import, csv, preview', 'import'),
            new sfCommandOption('output-file', null, sfCommandOption::PARAMETER_REQUIRED, 'Output file path for CSV export'),
            new sfCommandOption('sheet', null, sfCommandOption::PARAMETER_REQUIRED, 'Excel sheet index (0-based)', 0),
            new sfCommandOption('skip-header', null, sfCommandOption::PARAMETER_NONE, 'First row is NOT a header'),
            new sfCommandOption('delimiter', null, sfCommandOption::PARAMETER_REQUIRED, 'CSV delimiter', 'auto'),
            new sfCommandOption('dry-run', null, sfCommandOption::PARAMETER_NONE, 'Simulate import without database changes'),
            new sfCommandOption('limit', null, sfCommandOption::PARAMETER_REQUIRED, 'Limit number of rows to import'),
        ]);

        $this->namespace = 'migration';
        $this->name = 'import';
        $this->briefDescription = 'Import data using saved field mappings';
        $this->detailedDescription = <<<EOF
The [migration:import|INFO] task imports data from various file formats using 
saved field mappings from the database.

Supported formats: CSV, Excel (XLS/XLSX), XML, JSON, OPEX, PAX

List available mappings:
  [php symfony migration:import --list-mappings|INFO]

Import using mapping ID:
  [php symfony migration:import /path/to/file.csv --mapping=3|INFO]

Import using mapping name:
  [php symfony migration:import /path/to/file.xlsx --mapping="Vernon CMS (Museum)"|INFO]

Import Preservica OPEX with mapping:
  [php symfony migration:import /path/to/file.opex --mapping="Preservica OPEX"|INFO]

Dry run (preview only):
  [php symfony migration:import /path/to/file.csv --mapping=3 --dry-run|INFO]

Export to AtoM CSV format:
  [php symfony migration:import /path/to/file.xlsx --mapping=10 --output=csv --output-file=/tmp/atom.csv|INFO]
EOF;
    }

    protected function execute($arguments = [], $options = [])
    {
        sfContext::createInstance($this->configuration);

        // Load framework
        \AhgCore\Core\AhgDb::init();

        $DB = \Illuminate\Database\Capsule\Manager::class;

        // List mappings mode
        if ($options['list-mappings']) {
            return $this->listMappings($DB);
        }

        // Validate arguments
        if (empty($arguments['source'])) {
            $this->logSection('migration', 'ERROR: Please provide a source file or use --list-mappings', null, 'ERROR');
            return 1;
        }

        if (empty($options['mapping'])) {
            $this->logSection('migration', 'ERROR: Please provide --mapping=ID or --mapping="Name"', null, 'ERROR');
            $this->logSection('migration', 'Use --list-mappings to see available mappings');
            return 1;
        }

        $source = $arguments['source'];

        if (!file_exists($source)) {
            $this->logSection('migration', "ERROR: File not found: {$source}", null, 'ERROR');
            return 1;
        }

        // Load mapping
        $mapping = $this->loadMapping($DB, $options['mapping']);
        if (!$mapping) {
            $this->logSection('migration', "ERROR: Mapping not found: {$options['mapping']}", null, 'ERROR');
            $this->logSection('migration', 'Use --list-mappings to see available mappings');
            return 1;
        }

        $this->logSection('migration', 'Starting import');
        $this->logSection('migration', "Source: {$source}");
        $this->logSection('migration', "Mapping: {$mapping->name} (ID: {$mapping->id})");
        $this->logSection('migration', "Target: {$mapping->target_type}");

        if ($options['dry-run']) {
            $this->logSection('migration', 'DRY RUN MODE - No database changes');
        }

        // Parse file
        $this->logSection('migration', 'Parsing file...');
        $detection = $this->parseFile($source, $options);

        if (empty($detection['rows'])) {
            $this->logSection('migration', 'ERROR: No data found in file', null, 'ERROR');
            return 1;
        }

        $this->logSection('migration', "Found {$detection['row_count']} rows, " . count($detection['headers']) . " columns");

        // Load field mappings
        $fieldMappings = json_decode($mapping->field_mappings, true);
        $fields = $fieldMappings['fields'] ?? [];

        if (empty($fields)) {
            $this->logSection('migration', 'ERROR: No field mappings defined', null, 'ERROR');
            return 1;
        }

        $this->logSection('migration', count($fields) . " field mappings loaded");

        // Transform data
        $this->logSection('migration', 'Transforming data...');
        $transformed = $this->transformData($detection, $fields);

        $this->logSection('migration', count($transformed) . " records transformed");

        // Output based on mode
        $outputMode = $options['output'];

        if ($outputMode === 'preview') {
            return $this->previewData($transformed);
        }

        if ($outputMode === 'csv') {
            return $this->exportToCsv($transformed, $options['output-file'], $mapping->target_type);
        }

        // Import to database
        return $this->importToDatabase($transformed, $mapping->target_type, $options);
    }

    /**
     * List available mappings.
     */
    protected function listMappings($DB)
    {
        $this->logSection('migration', '');
        $this->logSection('migration', '=== Available Mappings ===');
        $this->logSection('migration', '');

        $mappings = $DB::table('atom_data_mapping')
            ->orderBy('target_type')
            ->orderBy('name')
            ->get();

        $currentType = '';
        foreach ($mappings as $m) {
            if ($m->target_type !== $currentType) {
                $currentType = $m->target_type;
                $this->logSection('migration', '');
                $this->logSection('migration', strtoupper($currentType) . ':');
            }

            $fields = json_decode($m->field_mappings, true);
            $fieldCount = isset($fields['fields']) ? count($fields['fields']) : 0;

            $this->logSection('migration', sprintf(
                "  [%d] %s (%d fields)",
                $m->id,
                $m->name,
                $fieldCount
            ));
        }

        $this->logSection('migration', '');
        $this->logSection('migration', 'Usage: php symfony migration:import /path/to/file --mapping=ID');
        $this->logSection('migration', '   or: php symfony migration:import /path/to/file --mapping="Name"');

        return 0;
    }

    /**
     * Load mapping by ID or name.
     */
    protected function loadMapping($DB, $mappingRef)
    {
        // Try by ID first
        if (is_numeric($mappingRef)) {
            return $DB::table('atom_data_mapping')
                ->where('id', (int)$mappingRef)
                ->first();
        }

        // Try by name
        return $DB::table('atom_data_mapping')
            ->where('name', $mappingRef)
            ->first();
    }

    /**
     * Parse input file.
     */
    protected function parseFile($filepath, $options)
    {
        $ext = strtolower(pathinfo($filepath, PATHINFO_EXTENSION));
        $sheetIndex = (int)$options['sheet'];
        $firstRowHeader = empty($options['skip-header']);
        $delimiter = $options['delimiter'];

        $headers = [];
        $rows = [];

        // OPEX
        if ($ext === 'opex' || ($ext === 'xml' && $this->isOpexFile($filepath))) {
            return $this->parseOpex($filepath);
        }

        // PAX/ZIP
        if (in_array($ext, ['pax', 'zip'])) {
            return $this->parsePax($filepath);
        }

        // Excel
        if (in_array($ext, ['xls', 'xlsx'])) {
            $bootstrap = sfConfig::get('sf_root_dir') . '/atom-framework/bootstrap.php';
            if (file_exists($bootstrap)) {
                require_once $bootstrap;
            }

            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($filepath);
            $sheet = $spreadsheet->getSheet($sheetIndex);
            $data = $sheet->toArray();

            if ($firstRowHeader && count($data) > 0) {
                $headers = array_map('trim', array_filter($data[0], fn($v) => $v !== null && $v !== ''));
                $headers = array_values($headers);
                $rows = array_slice($data, 1);
            } else {
                $colCount = count($data[0] ?? []);
                for ($i = 0; $i < $colCount; $i++) {
                    $headers[] = $this->getColumnLetter($i);
                }
                $rows = $data;
            }
        }
        // CSV
        elseif (in_array($ext, ['csv', 'txt'])) {
            $content = file_get_contents($filepath);

            if ($delimiter === 'auto') {
                $delimiter = $this->detectDelimiter($content);
            }

            $handle = fopen('php://temp', 'r+');
            fwrite($handle, $content);
            rewind($handle);

            $allRows = [];
            while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
                $allRows[] = $row;
            }
            fclose($handle);

            if ($firstRowHeader && count($allRows) > 0) {
                $headers = array_map('trim', $allRows[0]);
                $rows = array_slice($allRows, 1);
            } else {
                $colCount = count($allRows[0] ?? []);
                for ($i = 0; $i < $colCount; $i++) {
                    $headers[] = $this->getColumnLetter($i);
                }
                $rows = $allRows;
            }
        }
        // JSON
        elseif ($ext === 'json') {
            $data = json_decode(file_get_contents($filepath), true);
            if (isset($data['records'])) $data = $data['records'];
            elseif (isset($data['data'])) $data = $data['data'];

            if (!empty($data) && is_array($data)) {
                $headers = array_keys(reset($data));
                $rows = array_map('array_values', $data);
            }
        }
        // Generic XML
        elseif ($ext === 'xml') {
            return $this->parseGenericXml($filepath);
        }

        return [
            'headers' => $headers,
            'rows' => $rows,
            'row_count' => count($rows),
            'format' => $ext
        ];
    }

    /**
     * Check if XML file is OPEX format.
     */
    protected function isOpexFile($filepath)
    {
        $content = file_get_contents($filepath, false, null, 0, 1000);
        return strpos($content, 'opex:') !== false || strpos($content, 'OPEXMetadata') !== false;
    }

    /**
     * Parse OPEX file.
     */
    protected function parseOpex($filepath)
    {
        $pluginPath = sfConfig::get('sf_plugins_dir') . '/ahgDataMigrationPlugin';
        require_once $pluginPath . '/lib/Parsers/OpexParser.php';

        $parser = new \ahgDataMigrationPlugin\Parsers\OpexParser();
        $records = $parser->parse($filepath);

        if (empty($records)) {
            return ['headers' => [], 'rows' => [], 'row_count' => 0, 'format' => 'opex'];
        }

        $headers = array_keys($records[0]);
        $rows = array_map('array_values', $records);

        return [
            'headers' => $headers,
            'rows' => $rows,
            'row_count' => count($rows),
            'format' => 'opex'
        ];
    }

    /**
     * Parse PAX file.
     */
    protected function parsePax($filepath)
    {
        $pluginPath = sfConfig::get('sf_plugins_dir') . '/ahgDataMigrationPlugin';
        require_once $pluginPath . '/lib/Parsers/PaxParser.php';

        $parser = new \ahgDataMigrationPlugin\Parsers\PaxParser();
        $records = $parser->parse($filepath);

        if (empty($records)) {
            return ['headers' => [], 'rows' => [], 'row_count' => 0, 'format' => 'pax'];
        }

        $headers = [];
        foreach ($records as $record) {
            foreach (array_keys($record) as $key) {
                if (!in_array($key, $headers)) {
                    $headers[] = $key;
                }
            }
        }

        $rows = [];
        foreach ($records as $record) {
            $row = [];
            foreach ($headers as $h) {
                $row[] = $record[$h] ?? '';
            }
            $rows[] = $row;
        }

        return [
            'headers' => $headers,
            'rows' => $rows,
            'row_count' => count($rows),
            'format' => 'pax'
        ];
    }

    /**
     * Parse generic XML.
     */
    protected function parseGenericXml($filepath)
    {
        $content = file_get_contents($filepath);
        $xml = new \SimpleXMLElement($content);

        $records = [];
        foreach ($xml->children() as $child) {
            $record = $this->xmlToArray($child);
            if (!empty($record)) {
                $records[] = $record;
            }
        }

        if (empty($records)) {
            return ['headers' => [], 'rows' => [], 'row_count' => 0, 'format' => 'xml'];
        }

        $headers = [];
        foreach ($records as $record) {
            foreach (array_keys($record) as $key) {
                if (!in_array($key, $headers)) {
                    $headers[] = $key;
                }
            }
        }

        $rows = [];
        foreach ($records as $record) {
            $row = [];
            foreach ($headers as $h) {
                $row[] = $record[$h] ?? '';
            }
            $rows[] = $row;
        }

        return [
            'headers' => $headers,
            'rows' => $rows,
            'row_count' => count($rows),
            'format' => 'xml'
        ];
    }

    /**
     * Convert XML element to flat array.
     */
    protected function xmlToArray($element, $prefix = '')
    {
        $result = [];

        foreach ($element->children() as $child) {
            $name = $prefix ? $prefix . '_' . $child->getName() : $child->getName();

            if ($child->count() > 0) {
                $result = array_merge($result, $this->xmlToArray($child, $name));
            } else {
                $value = (string)$child;
                $result[$name] = isset($result[$name]) ? $result[$name] . ' | ' . $value : $value;
            }
        }

        return $result;
    }

    /**
     * Transform data using field mappings.
     */
    protected function transformData($detection, $fields)
    {
        $headers = $detection['headers'];
        $rows = $detection['rows'];
        $transformed = [];

        foreach ($rows as $row) {
            $record = [];

            foreach ($fields as $fieldConfig) {
                if (empty($fieldConfig['include'])) {
                    continue;
                }

                $sourceField = $fieldConfig['source_field'] ?? '';
                $atomField = $fieldConfig['atom_field'] ?? '';
                $constantValue = $fieldConfig['constant_value'] ?? '';
                $concatenate = !empty($fieldConfig['concatenate']);
                $concatConstant = !empty($fieldConfig['concat_constant']);
                $concatSymbol = $fieldConfig['concat_symbol'] ?? '|';

                if (empty($atomField)) {
                    continue;
                }

                // Get source value
                $sourceIndex = array_search($sourceField, $headers);
                $value = '';

                if ($sourceIndex !== false && isset($row[$sourceIndex])) {
                    $value = trim($row[$sourceIndex]);
                }

                // Apply constant
                if ($concatConstant && !empty($constantValue)) {
                    $value = $constantValue . $value;
                } elseif (empty($value) && !empty($constantValue)) {
                    $value = $constantValue;
                }

                // Handle concatenation
                if ($concatenate && isset($record[$atomField]) && !empty($record[$atomField])) {
                    $record[$atomField] .= $concatSymbol . $value;
                } elseif (!empty($value)) {
                    $record[$atomField] = $value;
                }
            }

            if (!empty($record)) {
                $transformed[] = $record;
            }
        }

        return $transformed;
    }

    /**
     * Preview transformed data.
     */
    protected function previewData($transformed)
    {
        $this->logSection('migration', '');
        $this->logSection('migration', '=== Preview (first 5 records) ===');

        $preview = array_slice($transformed, 0, 5);

        foreach ($preview as $i => $record) {
            $this->logSection('migration', '');
            $this->logSection('migration', "--- Record " . ($i + 1) . " ---");
            foreach ($record as $field => $value) {
                $value = mb_substr($value, 0, 80);
                $this->logSection('migration', "  {$field}: {$value}");
            }
        }

        $this->logSection('migration', '');
        $this->logSection('migration', 'Total records: ' . count($transformed));

        return 0;
    }

    /**
     * Export to AtoM CSV format.
     */
    protected function exportToCsv($transformed, $outputFile, $targetType)
    {
        if (empty($outputFile)) {
            $outputFile = '/tmp/atom_export_' . date('Ymd_His') . '.csv';
        }

        // Get all unique fields
        $allFields = [];
        foreach ($transformed as $record) {
            foreach (array_keys($record) as $field) {
                if (!in_array($field, $allFields)) {
                    $allFields[] = $field;
                }
            }
        }

        $handle = fopen($outputFile, 'w');

        // Write header
        fputcsv($handle, $allFields);

        // Write rows
        foreach ($transformed as $record) {
            $row = [];
            foreach ($allFields as $field) {
                $row[] = $record[$field] ?? '';
            }
            fputcsv($handle, $row);
        }

        fclose($handle);

        $this->logSection('migration', '');
        $this->logSection('migration', "CSV exported to: {$outputFile}");
        $this->logSection('migration', count($transformed) . " records written");

        return 0;
    }

    /**
     * Import data to database.
     */
    protected function importToDatabase($transformed, $targetType, $options)
    {
        $dryRun = $options['dry-run'];
        $update = $options['update'];
        $matchField = $options['match-field'];
        $repositoryId = $options['repository'] ? (int)$options['repository'] : null;
        $parentId = $options['parent'] ? (int)$options['parent'] : null;
        $culture = $options['culture'];
        $limit = $options['limit'] ? (int)$options['limit'] : null;

        if ($limit) {
            $transformed = array_slice($transformed, 0, $limit);
        }

        $stats = [
            'total' => count($transformed),
            'imported' => 0,
            'updated' => 0,
            'skipped' => 0,
            'errors' => 0,
        ];

        $this->logSection('migration', '');
        $this->logSection('migration', 'Importing ' . $stats['total'] . ' records...');

        foreach ($transformed as $i => $record) {
            try {
                if ($dryRun) {
                    $stats['imported']++;
                    continue;
                }

                // Check for existing record
                $existingId = null;
                if ($update && !empty($record[$matchField])) {
                    $existingId = $this->findExisting($record[$matchField], $matchField, $targetType);
                }

                if ($existingId) {
                    $this->updateRecord($existingId, $record, $targetType, $culture);
                    $stats['updated']++;
                } else {
                    $this->createRecord($record, $targetType, $culture, $repositoryId, $parentId);
                    $stats['imported']++;
                }

                // Progress
                if (($i + 1) % 100 === 0) {
                    $this->logSection('migration', "  Processed " . ($i + 1) . " / " . $stats['total']);
                }

            } catch (\Exception $e) {
                $stats['errors']++;
                $this->logSection('migration', "  ERROR row " . ($i + 1) . ": " . $e->getMessage(), null, 'ERROR');
            }
        }

        $this->logSection('migration', '');
        $this->logSection('migration', '=== Import Results ===');
        $this->logSection('migration', "Total:    {$stats['total']}");
        $this->logSection('migration', "Imported: {$stats['imported']}");
        $this->logSection('migration', "Updated:  {$stats['updated']}");
        $this->logSection('migration', "Errors:   {$stats['errors']}");

        if (!$dryRun && $stats['imported'] > 0) {
            $this->logSection('migration', '');
            $this->logSection('migration', 'Remember to rebuild the search index:');
            $this->logSection('migration', '  php symfony search:populate');
        }

        return $stats['errors'] > 0 ? 1 : 0;
    }

    /**
     * Find existing record.
     */
    protected function findExisting($value, $matchField, $targetType)
    {
        $DB = \Illuminate\Database\Capsule\Manager::class;

        if ($matchField === 'legacyId') {
            $keymap = $DB::table('keymap')
                ->where('source_id', $value)
                ->where('target_name', $this->getTableName($targetType))
                ->first();

            return $keymap ? $keymap->target_id : null;
        }

        if ($matchField === 'identifier') {
            $record = $DB::table('information_object')
                ->where('identifier', $value)
                ->first();

            return $record ? $record->id : null;
        }

        return null;
    }

    /**
     * Get table name for target type.
     */
    protected function getTableName($targetType)
    {
        $map = [
            'archives' => 'information_object',
            'library' => 'information_object',
            'museum' => 'information_object',
            'gallery' => 'information_object',
            'dam' => 'information_object',
            'actor' => 'actor',
            'accession' => 'accession',
            'repository' => 'repository',
            'event' => 'event',
        ];

        return $map[$targetType] ?? 'information_object';
    }

    /**
     * Create new record.
     */
    protected function createRecord($record, $targetType, $culture, $repositoryId, $parentId)
    {
        $DB = \Illuminate\Database\Capsule\Manager::class;

        // Default parent
        if (!$parentId) {
            $parentId = QubitInformationObject::ROOT_ID;
        }

        // Get level ID
        $levelId = $this->getLevelId($record['levelOfDescription'] ?? 'Item', $culture);

        // Create object
        $objectId = $DB::table('object')->insertGetId([
            'class_name' => 'QubitInformationObject',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        // Create information_object
        $DB::table('information_object')->insert([
            'id' => $objectId,
            'identifier' => $record['identifier'] ?? null,
            'level_of_description_id' => $levelId,
            'parent_id' => $parentId,
            'repository_id' => $repositoryId,
            'source_culture' => $culture,
            'lft' => 0,
            'rgt' => 0,
        ]);

        // Create i18n
        $DB::table('information_object_i18n')->insert([
            'id' => $objectId,
            'culture' => $culture,
            'title' => $record['title'] ?? 'Untitled',
            'scope_and_content' => $record['scopeAndContent'] ?? null,
            'extent_and_medium' => $record['extentAndMedium'] ?? null,
            'archival_history' => $record['archivalHistory'] ?? null,
            'access_conditions' => $record['accessConditions'] ?? null,
            'reproduction_conditions' => $record['reproductionConditions'] ?? null,
        ]);

        // Store keymap
        if (!empty($record['legacyId'])) {
            $DB::table('keymap')->insert([
                'source_name' => 'migration',
                'source_id' => $record['legacyId'],
                'target_id' => $objectId,
                'target_name' => 'information_object',
            ]);
        }

        return $objectId;
    }

    /**
     * Update existing record.
     */
    protected function updateRecord($objectId, $record, $targetType, $culture)
    {
        $DB = \Illuminate\Database\Capsule\Manager::class;

        $i18nData = [];
        $fieldMap = [
            'title' => 'title',
            'scopeAndContent' => 'scope_and_content',
            'extentAndMedium' => 'extent_and_medium',
            'archivalHistory' => 'archival_history',
            'accessConditions' => 'access_conditions',
            'reproductionConditions' => 'reproduction_conditions',
        ];

        foreach ($fieldMap as $key => $dbField) {
            if (!empty($record[$key])) {
                $i18nData[$dbField] = $record[$key];
            }
        }

        if (!empty($i18nData)) {
            $DB::table('information_object_i18n')
                ->where('id', $objectId)
                ->where('culture', $culture)
                ->update($i18nData);
        }

        return $objectId;
    }

    /**
     * Get level of description ID.
     */
    protected function getLevelId($levelName, $culture)
    {
        $DB = \Illuminate\Database\Capsule\Manager::class;

        $term = $DB::table('term')
            ->join('term_i18n', 'term.id', '=', 'term_i18n.id')
            ->where('term.taxonomy_id', QubitTaxonomy::LEVEL_OF_DESCRIPTION_ID)
            ->where('term_i18n.name', $levelName)
            ->where('term_i18n.culture', $culture)
            ->first();

        return $term ? $term->id : 228; // Default to Item
    }

    protected function detectDelimiter($content)
    {
        $firstLine = strtok($content, "\n");
        $delimiters = [',', ';', "\t", '|'];
        $counts = [];

        foreach ($delimiters as $d) {
            $counts[$d] = substr_count($firstLine, $d);
        }

        arsort($counts);
        return key($counts);
    }

    protected function getColumnLetter($index)
    {
        $letter = '';
        while ($index >= 0) {
            $letter = chr(65 + ($index % 26)) . $letter;
            $index = intval($index / 26) - 1;
        }
        return $letter;
    }
}
