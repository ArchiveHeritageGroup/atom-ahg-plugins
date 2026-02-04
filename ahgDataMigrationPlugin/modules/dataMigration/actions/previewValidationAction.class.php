<?php

/**
 * AJAX preview validation action.
 *
 * Validates a sample of rows for live preview during mapping configuration.
 * Returns validation results for display in the UI.
 */
class previewValidationAction extends sfAction
{
    public function execute($request)
    {
        // Check user authentication
        if (!$this->context->user->isAuthenticated()) {
            $this->getResponse()->setStatusCode(403);

            return $this->renderText(json_encode(['error' => 'Not authenticated']));
        }

        $this->getResponse()->setContentType('application/json');

        try {
            // Get parameters
            $filename = $request->getParameter('filename');
            $sector = $request->getParameter('sector', 'archive');
            $mappingJson = $request->getParameter('mapping', '{}');
            $sampleRows = (int) $request->getParameter('sample_rows', 10);

            // Validate filename
            if (empty($filename)) {
                return $this->renderText(json_encode(['error' => 'No filename specified']));
            }

            // Parse mapping
            $mapping = json_decode($mappingJson, true);
            if (!is_array($mapping)) {
                $mapping = [];
            }

            // Convert mapping format if needed
            $mappingMap = $this->convertMapping($mapping);

            // Get file path
            $uploadPath = sfConfig::get('sf_upload_dir').'/dataMigration/'.$filename;
            if (!file_exists($uploadPath)) {
                return $this->renderText(json_encode(['error' => 'File not found']));
            }

            // Read sample rows
            $rows = $this->readSampleRows($uploadPath, $mappingMap, $sampleRows);

            if (empty($rows)) {
                return $this->renderText(json_encode(['error' => 'No data rows found in file']));
            }

            // Create validation service
            $validationService = new \ahgDataMigrationPlugin\Services\ValidationService($sector, [
                'checkDatabase' => false, // Don't hit database for preview
                'schema' => true,
                'referential' => false, // Skip for preview
                'duplicates' => false,   // Skip for preview
                'sector' => true,
            ]);

            // Run validation on sample
            $report = $validationService->validate($uploadPath, $mappingMap, $rows);

            // Format response
            $response = [
                'success' => true,
                'is_valid' => $report->isValid(),
                'total_rows' => $report->getTotalRows(),
                'valid_rows' => $report->getValidRows(),
                'error_count' => $report->getErrorCount(),
                'warning_count' => $report->getWarningCount(),
                'errors' => $this->formatErrors($report, 20),
            ];

            return $this->renderText(json_encode($response));
        } catch (Exception $e) {
            return $this->renderText(json_encode([
                'success' => false,
                'error' => $e->getMessage(),
            ]));
        }
    }

    /**
     * Convert mapping array to source => target format.
     *
     * @param array $mapping
     *
     * @return array<string, string>
     */
    protected function convertMapping(array $mapping): array
    {
        $result = [];

        foreach ($mapping as $item) {
            if (is_array($item) && isset($item['source']) && isset($item['target'])) {
                $result[$item['source']] = $item['target'];
            } elseif (is_array($item) && isset($item['sourceColumn']) && isset($item['targetField'])) {
                $result[$item['sourceColumn']] = $item['targetField'];
            }
        }

        return $result;
    }

    /**
     * Read sample rows from CSV file.
     *
     * @param string              $filepath
     * @param array<string, string> $mapping
     * @param int                 $limit
     *
     * @return array<int, array<string, mixed>>
     */
    protected function readSampleRows(string $filepath, array $mapping, int $limit): array
    {
        $rows = [];

        $handle = fopen($filepath, 'r');
        if (false === $handle) {
            return [];
        }

        // Read header
        $header = fgetcsv($handle);
        if (false === $header) {
            fclose($handle);

            return [];
        }

        $header = array_map('trim', $header);
        $rowNumber = 2;

        while (false !== ($row = fgetcsv($handle)) && count($rows) < $limit) {
            $combined = [];
            foreach ($header as $i => $col) {
                $value = $row[$i] ?? '';
                $targetCol = $mapping[$col] ?? $col;
                $combined[$targetCol] = $value;
            }

            $rows[$rowNumber] = $combined;
            ++$rowNumber;
        }

        fclose($handle);

        return $rows;
    }

    /**
     * Format errors for JSON response.
     *
     * @param \ahgDataMigrationPlugin\Validation\AhgValidationReport $report
     * @param int                                                    $limit
     *
     * @return array<array<string, mixed>>
     */
    protected function formatErrors($report, int $limit): array
    {
        $errors = [];
        $count = 0;

        foreach ($report->getRowErrors() as $row => $columns) {
            foreach ($columns as $column => $issues) {
                foreach ($issues as $issue) {
                    if ($count >= $limit) {
                        break 3;
                    }

                    $errors[] = [
                        'row' => $row,
                        'column' => $column,
                        'message' => $issue['message'],
                        'severity' => $issue['severity'],
                        'rule' => $issue['rule'],
                    ];
                    ++$count;
                }
            }
        }

        return $errors;
    }
}
