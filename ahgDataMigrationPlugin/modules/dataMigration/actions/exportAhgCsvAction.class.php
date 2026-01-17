<?php

/**
 * Export transformed data to AHG Extended CSV format.
 * Includes standard ISAD fields PLUS AHG extended fields for:
 * - Security Classification
 * - Extended Rights
 * - Provenance Events
 * - Digital Object metadata
 */
class dataMigrationExportAhgCsvAction extends sfAction
{
    public function execute($request)
    {
        // Get session data
        $filename = $this->getUser()->getAttribute('migration_filename');
        $detection = $this->getUser()->getAttribute('migration_detection');
        $mapping = $this->getUser()->getAttribute('migration_mapping');

        if (!$filename || !$detection || !$mapping) {
            $this->getUser()->setFlash('error', 'No migration data found. Please upload a file first.');
            $this->redirect(['module' => 'dataMigration', 'action' => 'index']);
        }

        // Transform data
        $rows = $detection['rows'] ?? [];
        $headers = $detection['headers'] ?? [];
        $transformed = $this->transformData($rows, $headers, $mapping);

        if (empty($transformed)) {
            $this->getUser()->setFlash('error', 'No data to export. Check your field mappings.');
            $this->redirect(['module' => 'dataMigration', 'action' => 'map']);
        }

        // Build CSV with AHG extended columns
        $csv = $this->buildAhgCsv($transformed);
        
        // Generate filename
        $baseName = pathinfo($filename, PATHINFO_FILENAME);
        $exportFilename = $baseName . '_ahg_extended_' . date('Ymd_His') . '.csv';

        // Send response
        $this->getResponse()->setContentType('text/csv; charset=utf-8');
        $this->getResponse()->setHttpHeader('Content-Disposition', 'attachment; filename="' . $exportFilename . '"');
        $this->getResponse()->setContent($csv);

        return sfView::NONE;
    }

    /**
     * Build CSV with AHG extended columns
     */
    protected function buildAhgCsv(array $records): string
    {
        // Define column order: Standard ISAD + AHG Extended
        $columns = [
            // Standard ISAD fields
            'legacyId',
            'parentId',
            'qubitParentSlug',
            'identifier',
            'title',
            'levelOfDescription',
            'extentAndMedium',
            'repository',
            'archivalHistory',
            'acquisition',
            'scopeAndContent',
            'appraisal',
            'accruals',
            'arrangement',
            'accessConditions',
            'reproductionConditions',
            'language',
            'script',
            'languageNote',
            'physicalCharacteristics',
            'findingAids',
            'locationOfOriginals',
            'locationOfCopies',
            'relatedUnitsOfDescription',
            'publicationNote',
            'generalNote',
            'archivistNote',
            'rules',
            'descriptionIdentifier',
            'institutionIdentifier',
            'revisionHistory',
            'sources',
            'eventDates',
            'eventTypes',
            'eventActors',
            'eventActorHistories',
            'eventPlaces',
            'subjectAccessPoints',
            'placeAccessPoints',
            'nameAccessPoints',
            'genreAccessPoints',
            'digitalObjectPath',
            'digitalObjectURI',
            // AHG Extended Fields
            'ahgSecurityClassification',
            'ahgAccessLevel',
            'ahgRightsStatement',
            'ahgRightsBasis',
            'ahgCopyrightStatus',
            'ahgProvenanceHistory',
            'ahgProvenanceFirstDate',
            'ahgProvenanceLastDate',
            'ahgProvenanceEventCount',
            'ahgRelationships',
            'Filename',
            'digitalObjectChecksum',
            'digitalObjectMimeType',
            'digitalObjectSize',
            'allFilenames',
        ];

        // Build CSV
        $output = fopen('php://temp', 'r+');
        
        // Write BOM for Excel UTF-8 compatibility
        fwrite($output, "\xEF\xBB\xBF");
        
        // Write header row
        fputcsv($output, $columns);

        // Write data rows
        foreach ($records as $record) {
            $row = [];
            foreach ($columns as $col) {
                $value = $record[$col] ?? '';
                // Handle arrays (access points, etc.)
                if (is_array($value)) {
                    $value = implode('|', $value);
                }
                $row[] = $value;
            }
            fputcsv($output, $row);
        }

        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);

        return $csv;
    }

    /**
     * Transform source data using field mapping
     */
    protected function transformData(array $rows, array $headers, array $mapping): array
    {
        $transformed = [];

        foreach ($rows as $row) {
            $record = [];

            foreach ($mapping as $fieldConfig) {
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

                // Get value from source or use constant
                $value = '';
                if ($concatConstant && !empty($constantValue)) {
                    $value = $constantValue;
                } elseif (!empty($sourceField)) {
                    $sourceIndex = array_search($sourceField, $headers);
                    if ($sourceIndex !== false && isset($row[$sourceIndex])) {
                        $value = trim($row[$sourceIndex]);
                    }
                }

                if ($value === '') {
                    continue;
                }

                // Handle concatenation
                if ($concatenate && isset($record[$atomField]) && $record[$atomField] !== '') {
                    $symbol = ($concatSymbol === '\n' || $concatSymbol === "\\n") ? "\n" : $concatSymbol;
                    $record[$atomField] .= $symbol . $value;
                } else {
                    if (!isset($record[$atomField]) || $record[$atomField] === '') {
                        $record[$atomField] = $value;
                    } elseif ($concatenate) {
                        $symbol = ($concatSymbol === '\n' || $concatSymbol === "\\n") ? "\n" : $concatSymbol;
                        $record[$atomField] .= $symbol . $value;
                    }
                }
            }

            if (!empty($record)) {
                $transformed[] = $record;
            }
        }

        return $transformed;
    }
}
