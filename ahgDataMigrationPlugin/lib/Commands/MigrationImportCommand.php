<?php

namespace AtomFramework\Console\Commands\DataMigration;

use AtomFramework\Console\BaseCommand;
use Illuminate\Database\Capsule\Manager as DB;

/**
 * Generic CLI command for importing data using saved field mappings.
 */
class MigrationImportCommand extends BaseCommand
{
    protected string $name = 'migration:import';
    protected string $description = 'Import data using saved field mappings';

    protected string $detailedDescription = <<<'EOF'
    Import data from various file formats using saved field mappings
    from the database.

    Supported formats: CSV, Excel (XLS/XLSX), XML, JSON, OPEX, PAX

    List available mappings:
      php bin/atom migration:import --list-mappings

    Import using mapping ID:
      php bin/atom migration:import /path/to/file.csv --mapping=3

    Import using mapping name:
      php bin/atom migration:import /path/to/file.xlsx --mapping="Vernon CMS (Museum)"

    Import Preservica OPEX with mapping:
      php bin/atom migration:import /path/to/file.opex --mapping="Preservica OPEX"

    Dry run (preview only):
      php bin/atom migration:import /path/to/file.csv --mapping=3 --dry-run

    Export to AtoM CSV format:
      php bin/atom migration:import /path/to/file.xlsx --mapping=10 --output=csv --output-file=/tmp/atom.csv
    EOF;

    protected function configure(): void
    {
        $this->addArgument('source', 'Path to import file (CSV, Excel, XML, JSON, OPEX, PAX)');
        $this->addOption('mapping', null, 'Mapping ID or name from atom_data_mapping');
        $this->addOption('list-mappings', null, 'List available mappings');
        $this->addOption('repository', null, 'Repository ID for imported records');
        $this->addOption('parent', null, 'Parent information object ID');
        $this->addOption('culture', null, 'Culture/language code', 'en');
        $this->addOption('update', null, 'Update existing records if found');
        $this->addOption('match-field', null, 'Field to match existing records', 'legacyId');
        $this->addOption('output', null, 'Output mode: import, csv, preview', 'import');
        $this->addOption('output-file', null, 'Output file path for CSV export');
        $this->addOption('sheet', null, 'Excel sheet index (0-based)', '0');
        $this->addOption('skip-header', null, 'First row is NOT a header');
        $this->addOption('delimiter', null, 'CSV delimiter', 'auto');
        $this->addOption('dry-run', null, 'Simulate import without database changes');
        $this->addOption('limit', null, 'Limit number of rows to import');
    }

    protected function handle(): int
    {
        if ($this->hasOption('list-mappings')) {
            return $this->listMappings();
        }

        $source = $this->argument('source');

        if (empty($source)) {
            $this->error('Please provide a source file or use --list-mappings');

            return 1;
        }

        $mappingRef = $this->option('mapping');
        if (empty($mappingRef)) {
            $this->error('Please provide --mapping=ID or --mapping="Name"');
            $this->line('Use --list-mappings to see available mappings');

            return 1;
        }

        if (!file_exists($source)) {
            $this->error("File not found: {$source}");

            return 1;
        }

        $mapping = $this->loadMapping($mappingRef);
        if (!$mapping) {
            $this->error("Mapping not found: {$mappingRef}");
            $this->line('Use --list-mappings to see available mappings');

            return 1;
        }

        $this->info('Starting import');
        $this->info("Source: {$source}");
        $this->info("Mapping: {$mapping->name} (ID: {$mapping->id})");
        $this->info("Target: {$mapping->target_type}");

        if ($this->hasOption('dry-run')) {
            $this->warning('DRY RUN MODE - No database changes');
        }

        $this->info('Parsing file...');
        $detection = $this->parseFile($source);

        if (empty($detection['rows'])) {
            $this->error('No data found in file');

            return 1;
        }

        $this->line("Found {$detection['row_count']} rows, " . count($detection['headers']) . ' columns');

        $fieldMappings = json_decode($mapping->field_mappings, true);
        $fields = $fieldMappings['fields'] ?? [];

        if (empty($fields)) {
            $this->error('No field mappings defined');

            return 1;
        }

        $this->line(count($fields) . ' field mappings loaded');

        $this->info('Transforming data...');
        $transformed = $this->transformData($detection, $fields);

        $this->line(count($transformed) . ' records transformed');

        $outputMode = $this->option('output') ?? 'import';

        if ($outputMode === 'preview') {
            return $this->previewData($transformed);
        }

        if ($outputMode === 'csv') {
            return $this->exportToCsv($transformed, $this->option('output-file'), $mapping->target_type);
        }

        return $this->importToDatabase($transformed, $mapping->target_type);
    }

    protected function listMappings(): int
    {
        $this->newline();
        $this->info('=== Available Mappings ===');
        $this->newline();

        $mappings = DB::table('atom_data_mapping')
            ->orderBy('target_type')
            ->orderBy('name')
            ->get();

        $currentType = '';
        foreach ($mappings as $m) {
            if ($m->target_type !== $currentType) {
                $currentType = $m->target_type;
                $this->newline();
                $this->info(strtoupper($currentType) . ':');
            }

            $fields = json_decode($m->field_mappings, true);
            $fieldCount = isset($fields['fields']) ? count($fields['fields']) : 0;

            $this->line(sprintf('  [%d] %s (%d fields)', $m->id, $m->name, $fieldCount));
        }

        $this->newline();
        $this->line('Usage: php bin/atom migration:import /path/to/file --mapping=ID');
        $this->line('   or: php bin/atom migration:import /path/to/file --mapping="Name"');

        return 0;
    }

    protected function loadMapping(string $mappingRef): ?object
    {
        if (is_numeric($mappingRef)) {
            return DB::table('atom_data_mapping')
                ->where('id', (int) $mappingRef)
                ->first();
        }

        return DB::table('atom_data_mapping')
            ->where('name', $mappingRef)
            ->first();
    }

    protected function parseFile(string $filepath): array
    {
        $ext = strtolower(pathinfo($filepath, PATHINFO_EXTENSION));
        $sheetIndex = (int) ($this->option('sheet') ?? 0);
        $firstRowHeader = !$this->hasOption('skip-header');
        $delimiter = $this->option('delimiter') ?? 'auto';

        $headers = [];
        $rows = [];

        if ($ext === 'opex' || ($ext === 'xml' && $this->isOpexFile($filepath))) {
            return $this->parseOpex($filepath);
        }

        if (in_array($ext, ['pax', 'zip'])) {
            return $this->parsePax($filepath);
        }

        if (in_array($ext, ['xls', 'xlsx'])) {
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($filepath);
            $sheet = $spreadsheet->getSheet($sheetIndex);
            $data = $sheet->toArray();

            if ($firstRowHeader && count($data) > 0) {
                $headers = array_map('trim', array_filter($data[0], fn ($v) => $v !== null && $v !== ''));
                $headers = array_values($headers);
                $rows = array_slice($data, 1);
            } else {
                $colCount = count($data[0] ?? []);
                for ($i = 0; $i < $colCount; $i++) {
                    $headers[] = $this->getColumnLetter($i);
                }
                $rows = $data;
            }
        } elseif (in_array($ext, ['csv', 'txt'])) {
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
        } elseif ($ext === 'json') {
            $data = json_decode(file_get_contents($filepath), true);
            if (isset($data['records'])) {
                $data = $data['records'];
            } elseif (isset($data['data'])) {
                $data = $data['data'];
            }

            if (!empty($data) && is_array($data)) {
                $headers = array_keys(reset($data));
                $rows = array_map('array_values', $data);
            }
        } elseif ($ext === 'xml') {
            return $this->parseGenericXml($filepath);
        }

        return [
            'headers' => $headers,
            'rows' => $rows,
            'row_count' => count($rows),
            'format' => $ext,
        ];
    }

    protected function isOpexFile(string $filepath): bool
    {
        $content = file_get_contents($filepath, false, null, 0, 1000);

        return strpos($content, 'opex:') !== false || strpos($content, 'OPEXMetadata') !== false;
    }

    protected function parseOpex(string $filepath): array
    {
        $pluginPath = $this->getAtomRoot() . '/plugins/ahgDataMigrationPlugin';
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
            'format' => 'opex',
        ];
    }

    protected function parsePax(string $filepath): array
    {
        $pluginPath = $this->getAtomRoot() . '/plugins/ahgDataMigrationPlugin';
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
            'format' => 'pax',
        ];
    }

    protected function parseGenericXml(string $filepath): array
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
            'format' => 'xml',
        ];
    }

    protected function xmlToArray($element, string $prefix = ''): array
    {
        $result = [];

        foreach ($element->children() as $child) {
            $name = $prefix ? $prefix . '_' . $child->getName() : $child->getName();

            if ($child->count() > 0) {
                $result = array_merge($result, $this->xmlToArray($child, $name));
            } else {
                $value = (string) $child;
                $result[$name] = isset($result[$name]) ? $result[$name] . ' | ' . $value : $value;
            }
        }

        return $result;
    }

    protected function transformData(array $detection, array $fields): array
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

                $sourceIndex = array_search($sourceField, $headers);
                $value = '';

                if ($sourceIndex !== false && isset($row[$sourceIndex])) {
                    $value = trim($row[$sourceIndex]);
                }

                if ($concatConstant && !empty($constantValue)) {
                    $value = $constantValue . $value;
                } elseif (empty($value) && !empty($constantValue)) {
                    $value = $constantValue;
                }

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

    protected function previewData(array $transformed): int
    {
        $this->newline();
        $this->info('=== Preview (first 5 records) ===');

        $preview = array_slice($transformed, 0, 5);

        foreach ($preview as $i => $record) {
            $this->newline();
            $this->line('--- Record ' . ($i + 1) . ' ---');
            foreach ($record as $field => $value) {
                $value = mb_substr($value, 0, 80);
                $this->line("  {$field}: {$value}");
            }
        }

        $this->newline();
        $this->line('Total records: ' . count($transformed));

        return 0;
    }

    protected function exportToCsv(array $transformed, ?string $outputFile, string $targetType): int
    {
        if (empty($outputFile)) {
            $outputFile = '/tmp/atom_export_' . date('Ymd_His') . '.csv';
        }

        $allFields = [];
        foreach ($transformed as $record) {
            foreach (array_keys($record) as $field) {
                if (!in_array($field, $allFields)) {
                    $allFields[] = $field;
                }
            }
        }

        $handle = fopen($outputFile, 'w');

        fputcsv($handle, $allFields);

        foreach ($transformed as $record) {
            $row = [];
            foreach ($allFields as $field) {
                $row[] = $record[$field] ?? '';
            }
            fputcsv($handle, $row);
        }

        fclose($handle);

        $this->newline();
        $this->success("CSV exported to: {$outputFile}");
        $this->line(count($transformed) . ' records written');

        return 0;
    }

    protected function importToDatabase(array $transformed, string $targetType): int
    {
        $dryRun = $this->hasOption('dry-run');
        $update = $this->hasOption('update');
        $matchField = $this->option('match-field') ?? 'legacyId';
        $repositoryOpt = $this->option('repository');
        $repositoryId = $repositoryOpt ? (int) $repositoryOpt : null;
        $parentOpt = $this->option('parent');
        $parentId = $parentOpt ? (int) $parentOpt : null;
        $culture = $this->option('culture') ?? 'en';
        $limitOpt = $this->option('limit');
        $limit = $limitOpt ? (int) $limitOpt : null;

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

        $this->newline();
        $this->info('Importing ' . $stats['total'] . ' records...');

        foreach ($transformed as $i => $record) {
            try {
                if ($dryRun) {
                    $stats['imported']++;

                    continue;
                }

                $existingId = null;
                if ($update && !empty($record[$matchField])) {
                    $existingId = $this->findExisting($record[$matchField], $matchField, $targetType);
                }

                if ($existingId) {
                    $this->updateRecord($existingId, $record, $culture);
                    $stats['updated']++;
                } else {
                    $this->createRecord($record, $targetType, $culture, $repositoryId, $parentId);
                    $stats['imported']++;
                }

                if (($i + 1) % 100 === 0) {
                    $this->info('  Processed ' . ($i + 1) . ' / ' . $stats['total']);
                }
            } catch (\Exception $e) {
                $stats['errors']++;
                $this->error('  ERROR row ' . ($i + 1) . ': ' . $e->getMessage());
            }
        }

        $this->newline();
        $this->info('=== Import Results ===');
        $this->line("Total:    {$stats['total']}");
        $this->line("Imported: {$stats['imported']}");
        $this->line("Updated:  {$stats['updated']}");
        $this->line("Errors:   {$stats['errors']}");

        if (!$dryRun && $stats['imported'] > 0) {
            $this->newline();
            $this->comment('Remember to rebuild the search index:');
            $this->comment('  php symfony search:populate');
        }

        return $stats['errors'] > 0 ? 1 : 0;
    }

    protected function findExisting(string $value, string $matchField, string $targetType): ?int
    {
        if ($matchField === 'legacyId') {
            $keymap = DB::table('keymap')
                ->where('source_id', $value)
                ->where('target_name', $this->getTableName($targetType))
                ->first();

            return $keymap ? (int) $keymap->target_id : null;
        }

        if ($matchField === 'identifier') {
            $record = DB::table('information_object')
                ->where('identifier', $value)
                ->first();

            return $record ? (int) $record->id : null;
        }

        return null;
    }

    protected function getTableName(string $targetType): string
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

    protected function createRecord(array $record, string $targetType, string $culture, ?int $repositoryId, ?int $parentId): int
    {
        if (!$parentId) {
            $parentId = \QubitInformationObject::ROOT_ID;
        }

        $levelId = $this->getLevelId($record['levelOfDescription'] ?? 'Item', $culture);

        $objectId = DB::table('object')->insertGetId([
            'class_name' => 'QubitInformationObject',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        DB::table('information_object')->insert([
            'id' => $objectId,
            'identifier' => $record['identifier'] ?? null,
            'level_of_description_id' => $levelId,
            'parent_id' => $parentId,
            'repository_id' => $repositoryId,
            'source_culture' => $culture,
            'lft' => 0,
            'rgt' => 0,
        ]);

        DB::table('information_object_i18n')->insert([
            'id' => $objectId,
            'culture' => $culture,
            'title' => $record['title'] ?? 'Untitled',
            'scope_and_content' => $record['scopeAndContent'] ?? null,
            'extent_and_medium' => $record['extentAndMedium'] ?? null,
            'archival_history' => $record['archivalHistory'] ?? null,
            'access_conditions' => $record['accessConditions'] ?? null,
            'reproduction_conditions' => $record['reproductionConditions'] ?? null,
        ]);

        if (!empty($record['legacyId'])) {
            DB::table('keymap')->insert([
                'source_name' => 'migration',
                'source_id' => $record['legacyId'],
                'target_id' => $objectId,
                'target_name' => 'information_object',
            ]);
        }

        return $objectId;
    }

    protected function updateRecord(int $objectId, array $record, string $culture): int
    {
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
            DB::table('information_object_i18n')
                ->where('id', $objectId)
                ->where('culture', $culture)
                ->update($i18nData);
        }

        return $objectId;
    }

    protected function getLevelId(string $levelName, string $culture): int
    {
        $term = DB::table('term')
            ->join('term_i18n', 'term.id', '=', 'term_i18n.id')
            ->where('term.taxonomy_id', \QubitTaxonomy::LEVEL_OF_DESCRIPTION_ID)
            ->where('term_i18n.name', $levelName)
            ->where('term_i18n.culture', $culture)
            ->first();

        return $term ? (int) $term->id : 228;
    }

    protected function detectDelimiter(string $content): string
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

    protected function getColumnLetter(int $index): string
    {
        $letter = '';
        while ($index >= 0) {
            $letter = chr(65 + ($index % 26)) . $letter;
            $index = intval($index / 26) - 1;
        }

        return $letter;
    }
}
