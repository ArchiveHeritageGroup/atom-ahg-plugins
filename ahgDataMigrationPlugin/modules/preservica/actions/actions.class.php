<?php

use AtomFramework\Http\Controllers\AhgController;
/**
 * Data Migration module actions.
 * Handles import/export for various formats including Preservica OPEX/PAX.
 */
class preservicaActions extends AhgController
{
    /**
     * Main index - migration dashboard.
     */
    public function executeIndex($request)
    {
        // Check permissions
        if (!$this->getUser()->isAuthenticated()) {
            $this->redirect('user/login');
        }

        // Get available source systems
        $this->sourceSystems = $this->getSourceSystems();
        
        // Get available export formats
        $this->exportFormats = $this->getExportFormats();
        
        // Get repositories for dropdown
        $this->repositories = $this->getRepositories();
        
        // Get recent imports
        $this->recentImports = [];
    }

    /**
     * Preservica-specific import page.
     */
    public function executePreservicaImport($request)
    {
        if (!$this->getUser()->isAuthenticated()) {
            $this->redirect('user/login');
        }

        $this->repositories = $this->getRepositories();
        $this->formats = [
            'opex' => 'OPEX (Open Preservation Exchange)',
            'xip'  => 'XIP/PAX (Preservica Archive eXchange)',
        ];

        if ($request->isMethod('post')) {
            $this->processPreservicaImport($request);
        }
    }

    /**
     * Process Preservica import.
     */
    protected function processPreservicaImport(sfWebRequest $request)
    {
        $frameworkPath = $this->config('sf_root_dir') . '/atom-framework/bootstrap.php';
        if (file_exists($frameworkPath)) {
            require_once $frameworkPath;
        }

        $format = $request->getParameter('format', 'opex');
        $repositoryId = $request->getParameter('repository_id');
        $parentId = $request->getParameter('parent_id');
        $updateExisting = $request->getParameter('update_existing', false);

        // Handle file upload
        $file = $request->getFiles('import_file');
        
        if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
            $this->getUser()->setFlash('error', 'Please upload a valid file.');
            return;
        }

        // Move to temp location
        $tmpDir = $this->config('sf_upload_dir') . '/tmp';
        if (!is_dir($tmpDir)) {
            mkdir($tmpDir, 0755, true);
        }
        $tempPath = $tmpDir . '/' . $file['name'];
        move_uploaded_file($file['tmp_name'], $tempPath);

        // Build options
        $options = [
            'update_existing'        => (bool) $updateExisting,
            'import_digital_objects' => (bool) $request->getParameter('import_digital_objects', true),
            'verify_checksums'       => (bool) $request->getParameter('verify_checksums', true),
            'create_hierarchy'       => (bool) $request->getParameter('create_hierarchy', true),
        ];

        try {
            $service = new \ahgDataMigrationPlugin\Services\PreservicaImportService($format, $options);
            
            if ($repositoryId) {
                $service->setRepository((int) $repositoryId);
            }
            
            if ($parentId) {
                $service->setParent((int) $parentId);
            }

            // Import based on format
            if ($format === 'xip' || pathinfo($file['name'], PATHINFO_EXTENSION) === 'pax') {
                $result = $service->importPaxPackage($tempPath);
            } else {
                $result = $service->importOpexFile($tempPath);
            }

            // Cleanup
            @unlink($tempPath);

            // Store result for display
            $this->importResult = $result;
            
            if ($result['success']) {
                $this->getUser()->setFlash('notice', sprintf(
                    'Import completed: %d imported, %d updated, %d errors',
                    $result['stats']['imported'],
                    $result['stats']['updated'],
                    $result['stats']['errors']
                ));
            } else {
                $this->getUser()->setFlash('error', 'Import completed with errors. See details below.');
            }

        } catch (\Exception $e) {
            @unlink($tempPath);
            $this->getUser()->setFlash('error', 'Import failed: ' . $e->getMessage());
        }
    }

    /**
     * Preservica-specific export page.
     */
    public function executePreservicaExport($request)
    {
        if (!$this->getUser()->isAuthenticated()) {
            $this->redirect('user/login');
        }

        $this->repositories = $this->getRepositories();
        $this->formats = [
            'opex' => 'OPEX (Open Preservation Exchange)',
            'xip'  => 'XIP/PAX (Preservica Archive eXchange)',
        ];

        // Get object ID from URL if provided
        $this->objectId = $request->getParameter('id');
        if ($this->objectId) {
            $this->object = QubitInformationObject::getById($this->objectId);
        }

        if ($request->isMethod('post')) {
            $this->processPreservicaExport($request);
        }
    }

    /**
     * Process Preservica export.
     */
    protected function processPreservicaExport(sfWebRequest $request)
    {
        $frameworkPath = $this->config('sf_root_dir') . '/atom-framework/bootstrap.php';
        if (file_exists($frameworkPath)) {
            require_once $frameworkPath;
        }

        $format = $request->getParameter('format', 'opex');
        $objectId = $request->getParameter('object_id');
        $repositoryId = $request->getParameter('repository_id');
        $exportHierarchy = $request->getParameter('export_hierarchy', false);
        $includeDigitalObjects = $request->getParameter('include_digital_objects', true);

        // Build options
        $options = [
            'include_digital_objects' => (bool) $includeDigitalObjects,
            'include_children'        => (bool) $exportHierarchy,
            'create_package'          => ($format === 'xip'),
            'security_descriptor'     => $request->getParameter('security_descriptor', 'open'),
        ];

        try {
            $service = new \ahgDataMigrationPlugin\Services\PreservicaExportService($format, $options);
            
            // Set output directory
            $outputDir = $this->config('sf_upload_dir') . '/exports/preservica';
            if (!is_dir($outputDir)) {
                mkdir($outputDir, 0755, true);
            }
            $service->setOutputDir($outputDir);

            // Export based on selection
            if ($repositoryId && !$objectId) {
                $path = $service->exportRepository((int) $repositoryId);
            } elseif ($objectId && $exportHierarchy) {
                $path = $service->exportHierarchy((int) $objectId);
            } elseif ($objectId) {
                if ($format === 'xip') {
                    $path = $service->exportToPax((int) $objectId);
                } else {
                    $path = $service->exportToOpex((int) $objectId);
                }
            } else {
                throw new \Exception('Please select a record or repository to export.');
            }

            $stats = $service->getStats();
            
            // Store for download
            $this->exportPath = $path;
            $this->exportStats = $stats;
            
            $this->getUser()->setFlash('notice', sprintf(
                'Export completed: %d records, %d digital objects',
                $stats['exported'],
                $stats['digital_objects']
            ));

        } catch (\Exception $e) {
            $this->getUser()->setFlash('error', 'Export failed: ' . $e->getMessage());
        }
    }

    /**
     * Download exported file.
     */
    public function executeDownload($request)
    {
        $filename = $request->getParameter('file');
        $filepath = $this->config('sf_upload_dir') . '/exports/preservica/' . basename($filename);

        if (!file_exists($filepath)) {
            $this->forward404('File not found');
        }

        // Determine content type
        $ext = pathinfo($filepath, PATHINFO_EXTENSION);
        $contentTypes = [
            'opex' => 'application/xml',
            'xml'  => 'application/xml',
            'pax'  => 'application/zip',
            'zip'  => 'application/zip',
            'json' => 'application/json',
        ];
        $contentType = $contentTypes[$ext] ?? 'application/octet-stream';

        // Send file
        $this->getResponse()->clearHttpHeaders();
        $this->getResponse()->setHttpHeader('Content-Type', $contentType);
        $this->getResponse()->setHttpHeader('Content-Disposition', 'attachment; filename="' . basename($filepath) . '"');
        $this->getResponse()->setHttpHeader('Content-Length', filesize($filepath));
        $this->getResponse()->sendHttpHeaders();

        readfile($filepath);

        return sfView::NONE;
    }

    /**
     * Import form - step 1: upload and configure.
     */
    public function executeImport($request)
    {
        if (!$this->getUser()->isAuthenticated()) {
            $this->redirect('user/login');
        }

        $this->sourceSystems = $this->getSourceSystems();
        $this->repositories = $this->getRepositories();
        $this->defaultMappings = $this->getDefaultMappings();
    }

    /**
     * Export form.
     */
    public function executeExport($request)
    {
        if (!$this->getUser()->isAuthenticated()) {
            $this->redirect('user/login');
        }

        $this->exportFormats = $this->getExportFormats();
        $this->repositories = $this->getRepositories();
    }

    /**
     * AJAX: Get field mapping for source system.
     */
    public function executeGetMapping($request)
    {
        $sourceSystem = $request->getParameter('source');
        
        $mappingFile = $this->config('sf_plugins_dir') 
            . '/ahgDataMigrationPlugin/data/mappings/defaults/' 
            . $sourceSystem . '.json';

        if (file_exists($mappingFile)) {
            $mapping = json_decode(file_get_contents($mappingFile), true);
            return $this->renderText(json_encode($mapping));
        }

        return $this->renderText(json_encode(['error' => 'Mapping not found']));
    }

    /**
     * Get available source systems.
     */
    protected function getSourceSystems(): array
    {
        return [
            'preservica_opex' => [
                'name'        => 'Preservica OPEX',
                'description' => 'Open Preservation Exchange XML format',
                'extensions'  => ['opex', 'xml'],
                'icon'        => 'bi-archive',
            ],
            'preservica_xip' => [
                'name'        => 'Preservica PAX/XIP',
                'description' => 'Preservica Archive eXchange packages',
                'extensions'  => ['pax', 'zip'],
                'icon'        => 'bi-file-zip',
            ],
            'archivesspace' => [
                'name'        => 'ArchivesSpace',
                'description' => 'ArchivesSpace EAD or JSON export',
                'extensions'  => ['xml', 'json'],
                'icon'        => 'bi-box',
            ],
            'vernon' => [
                'name'        => 'Vernon CMS',
                'description' => 'Vernon museum collection export',
                'extensions'  => ['csv', 'xml'],
                'icon'        => 'bi-building',
            ],
            'pastperfect' => [
                'name'        => 'PastPerfect',
                'description' => 'PastPerfect museum software export',
                'extensions'  => ['csv', 'txt'],
                'icon'        => 'bi-clock-history',
            ],
            'collectiveaccess' => [
                'name'        => 'CollectiveAccess',
                'description' => 'CollectiveAccess cataloguing system',
                'extensions'  => ['csv', 'xml'],
                'icon'        => 'bi-collection',
            ],
            'atom_csv' => [
                'name'        => 'AtoM CSV',
                'description' => 'Standard AtoM CSV import format',
                'extensions'  => ['csv'],
                'icon'        => 'bi-filetype-csv',
            ],
            'ead' => [
                'name'        => 'EAD 2002',
                'description' => 'Encoded Archival Description',
                'extensions'  => ['xml'],
                'icon'        => 'bi-file-code',
            ],
            'dublin_core' => [
                'name'        => 'Dublin Core',
                'description' => 'Dublin Core metadata XML',
                'extensions'  => ['xml'],
                'icon'        => 'bi-journal',
            ],
            'generic_csv' => [
                'name'        => 'Generic CSV',
                'description' => 'Custom CSV with field mapping',
                'extensions'  => ['csv'],
                'icon'        => 'bi-table',
            ],
        ];
    }

    /**
     * Get available export formats.
     */
    protected function getExportFormats(): array
    {
        return [
            'preservica_opex' => [
                'name'        => 'Preservica OPEX',
                'description' => 'Export to Open Preservation Exchange format',
                'icon'        => 'bi-archive',
            ],
            'preservica_xip' => [
                'name'        => 'Preservica PAX',
                'description' => 'Export to Preservica Archive eXchange package',
                'icon'        => 'bi-file-zip',
            ],
            'atom_csv' => [
                'name'        => 'AtoM CSV',
                'description' => 'Export to AtoM standard CSV format',
                'icon'        => 'bi-filetype-csv',
            ],
            'ead' => [
                'name'        => 'EAD 2002',
                'description' => 'Export to Encoded Archival Description',
                'icon'        => 'bi-file-code',
            ],
            'dublin_core' => [
                'name'        => 'Dublin Core',
                'description' => 'Export to Dublin Core XML',
                'icon'        => 'bi-journal',
            ],
        ];
    }

    /**
     * Get repositories for dropdown.
     */
    protected function getRepositories(): array
    {
        $repos = [];
        $culture = $this->culture();

        $rows = \Illuminate\Database\Capsule\Manager::table('repository as r')
            ->leftJoin('actor_i18n as ai', function ($join) use ($culture) {
                $join->on('r.id', '=', 'ai.id')
                    ->where('ai.culture', '=', $culture);
            })
            ->whereNotNull('ai.authorized_form_of_name')
            ->orderBy('ai.authorized_form_of_name')
            ->select('r.id', 'ai.authorized_form_of_name')
            ->get();

        foreach ($rows as $row) {
            $repos[$row->id] = $row->authorized_form_of_name;
        }

        return $repos;
    }

    /**
     * Get default mappings.
     */
    protected function getDefaultMappings(): array
    {
        $mappings = [];
        $mappingDir = $this->config('sf_plugins_dir') . '/ahgDataMigrationPlugin/data/mappings/defaults/';
        
        if (is_dir($mappingDir)) {
            foreach (glob($mappingDir . '*.json') as $file) {
                $name = basename($file, '.json');
                $content = json_decode(file_get_contents($file), true);
                $mappings[$name] = $content['name'] ?? $name;
            }
        }

        return $mappings;
    }
}
