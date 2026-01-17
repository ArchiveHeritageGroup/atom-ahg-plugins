<?php

/**
 * Export transformed data to sector-specific CSV format for AtoM import.
 */
class dataMigrationExportCsvAction extends sfAction
{
    public function execute($request)
    {
        // Get session data
        $filename = $this->getUser()->getAttribute('migration_filename');
        $detection = $this->getUser()->getAttribute('migration_detection');
        $mapping = $this->getUser()->getAttribute('migration_mapping');
        $targetSector = $this->getUser()->getAttribute('migration_target_sector', 'archives');

        if (!$filename || !$detection || !$mapping) {
            $this->getUser()->setFlash('error', 'No migration data found. Please upload a file first.');
            $this->redirect(['module' => 'dataMigration', 'action' => 'index']);
        }

        // Include required files
        $pluginPath = sfConfig::get('sf_root_dir') . '/atom-ahg-plugins/ahgDataMigrationPlugin';
        require_once $pluginPath . '/lib/Services/PathTransformer.php';
        require_once $pluginPath . '/lib/Exporters/BaseExporter.php';
        require_once $pluginPath . '/lib/Exporters/ArchivesExporter.php';
        require_once $pluginPath . '/lib/Exporters/MuseumExporter.php';
        require_once $pluginPath . '/lib/Exporters/LibraryExporter.php';
        require_once $pluginPath . '/lib/Exporters/GalleryExporter.php';
        require_once $pluginPath . '/lib/Exporters/DamExporter.php';
        require_once $pluginPath . '/lib/Exporters/ExporterFactory.php';

        // Transform data
        $rows = $detection['rows'] ?? [];
        $headers = $detection['headers'] ?? [];
        $transformed = $this->transformData($rows, $headers, $mapping);

        if (empty($transformed)) {
            $this->getUser()->setFlash('error', 'No data to export. Check your field mappings.');
            $this->redirect(['module' => 'dataMigration', 'action' => 'map']);
        }

        // Get the appropriate exporter
        try {
            $exporter = \ahgDataMigrationPlugin\Exporters\ExporterFactory::create($targetSector);
        } catch (\Exception $e) {
            // Fallback to archives if sector not found
            $exporter = new \ahgDataMigrationPlugin\Exporters\ArchivesExporter();
        }

        // Export to CSV
        $exporter->setData($transformed);
        $csv = $exporter->export();
        $exportFilename = $exporter->getFilename($filename);

        // Send response
        $this->getResponse()->setContentType('text/csv');
        $this->getResponse()->setHttpHeader('Content-Disposition', 'attachment; filename="' . $exportFilename . '"');
        $this->getResponse()->setContent($csv);

        return sfView::NONE;
    }

    protected function transformData($rows, $headers, $mapping)
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
                $transform = $fieldConfig['transform'] ?? '';
                $transformOptions = $fieldConfig['transform_options'] ?? [];

                if (empty($atomField)) {
                    continue;
                }

                $sourceIndex = array_search($sourceField, $headers);
                $value = '';
                if ($sourceIndex !== false && isset($row[$sourceIndex])) {
                    $value = $row[$sourceIndex];
                }

                // Apply path transformation
                if ($transform && $value) {
                    $value = \ahgDataMigrationPlugin\Services\PathTransformer::transform($value, $transform, $transformOptions);
                }

                // Handle constant
                if ($concatConstant && $constantValue && $value) {
                    $value = $constantValue . $value;
                } elseif ($constantValue && empty($value)) {
                    $value = $constantValue;
                }

                if ($value === '' || $value === null) {
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
