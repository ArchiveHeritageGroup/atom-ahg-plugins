<?php

use Illuminate\Database\Capsule\Manager as DB;

/**
 * Metadata Export Actions
 *
 * Web interface for metadata exports.
 *
 * @package    ahgMetadataExportPlugin
 * @subpackage metadataExport
 * @author     The Archive and Heritage Group (Pty) Ltd
 */
class metadataExportActions extends AhgActions
{
    /**
     * Export dashboard - format selection
     *
     * @param sfWebRequest $request
     */
    public function executeIndex(sfWebRequest $request)
    {
        // Check permissions
        if (!$this->context->user->isAuthenticated()) {
            $this->redirect('user/login');
        }

        // Get available formats
        $this->formats = ahgMetadataExportPluginConfiguration::getFormats();

        // Group by sector
        $this->sectors = [];
        foreach ($this->formats as $code => $format) {
            $sector = $format['sector'];
            if (!isset($this->sectors[$sector])) {
                $this->sectors[$sector] = [];
            }
            $this->sectors[$sector][$code] = $format;
        }

        // Get recent exports (last 10)
        $this->recentExports = $this->getRecentExports(10);
    }

    /**
     * Preview export
     *
     * @param sfWebRequest $request
     */
    public function executePreview(sfWebRequest $request)
    {
        // Check permissions
        if (!$this->context->user->isAuthenticated()) {
            $this->forward404();
        }

        $format = $request->getParameter('format');
        $slug = $request->getParameter('slug');

        if (!$format || !$slug) {
            $this->forward404();
        }

        // Get resource
        $this->resource = QubitInformationObject::getBySlug($slug);
        if (!$this->resource) {
            $this->forward404();
        }

        // Validate format
        $this->format = $format;
        $formatInfo = ahgMetadataExportPluginConfiguration::getFormat($format);
        if (!$formatInfo) {
            $this->forward404();
        }

        $this->formatInfo = $formatInfo;

        // Load export service
        $this->loadExportService();

        try {
            $exportService = new \AhgMetadataExport\Services\ExportService();
            $this->preview = $exportService->export($this->resource, $format, [
                'includeDigitalObjects' => true,
                'includeDrafts' => false,
                'includeChildren' => true,
                'prettyPrint' => true,
            ]);
            $this->mimeType = $exportService->getMimeType($format);
        } catch (\Exception $e) {
            $this->error = $e->getMessage();
            $this->preview = null;
        }
    }

    /**
     * Download export
     *
     * @param sfWebRequest $request
     */
    public function executeDownload(sfWebRequest $request)
    {
        // Check permissions
        if (!$this->context->user->isAuthenticated()) {
            $this->forward404();
        }

        $format = $request->getParameter('format');
        $slug = $request->getParameter('slug');

        if (!$format || !$slug) {
            $this->forward404();
        }

        // Get resource
        $resource = QubitInformationObject::getBySlug($slug);
        if (!$resource) {
            $this->forward404();
        }

        // Validate format
        $formatInfo = ahgMetadataExportPluginConfiguration::getFormat($format);
        if (!$formatInfo) {
            $this->forward404();
        }

        // Load export service
        $this->loadExportService();

        try {
            $exportService = new \AhgMetadataExport\Services\ExportService();

            // Build options from request
            $options = [
                'includeDigitalObjects' => $request->getParameter('include_digital_objects', true),
                'includeDrafts' => $request->getParameter('include_drafts', false),
                'includeChildren' => $request->getParameter('include_children', true),
                'maxDepth' => (int) $request->getParameter('max_depth', 0),
                'prettyPrint' => true,
            ];

            // RDF format option
            if (in_array($format, ['rico', 'bibframe'], true)) {
                $options['outputFormat'] = $request->getParameter('rdf_format', 'jsonld');
            }

            $content = $exportService->export($resource, $format, $options);
            $filename = $exportService->generateFilename($resource, $format);
            $mimeType = $exportService->getMimeType($format);

            // Log export
            $exportService->logExport(
                $format,
                get_class($resource),
                $resource->id,
                '',
                strlen($content),
                $this->context->user->getAttribute('user_id')
            );

            // Send response
            $this->response->setContentType($mimeType);
            $this->response->setHttpHeader('Content-Disposition', 'attachment; filename="'.$filename.'"');
            $this->response->setContent($content);

            return sfView::NONE;
        } catch (\Exception $e) {
            $this->getUser()->setFlash('error', 'Export failed: '.$e->getMessage());
            $this->redirect('metadataExport/index');
        }
    }

    /**
     * Bulk export
     *
     * @param sfWebRequest $request
     */
    public function executeBulk(sfWebRequest $request)
    {
        // Check permissions
        if (!$this->context->user->isAuthenticated()) {
            $this->forward404();
        }

        $format = $request->getParameter('format');

        if (!$format) {
            $this->forward404();
        }

        // Validate format
        $formatInfo = ahgMetadataExportPluginConfiguration::getFormat($format);
        if (!$formatInfo) {
            $this->forward404();
        }

        $this->format = $format;
        $this->formatInfo = $formatInfo;

        // Handle form submission
        if ($request->isMethod('post')) {
            $this->processBulkExport($request);
        }

        // Get repositories for filter
        $this->repositories = QubitRepository::getAll();
    }

    /**
     * Process bulk export
     *
     * @param sfWebRequest $request
     */
    protected function processBulkExport(sfWebRequest $request): void
    {
        $repositoryId = $request->getParameter('repository_id');
        $format = $request->getParameter('format');

        if (!$repositoryId) {
            $this->getUser()->setFlash('error', 'Please select a repository');

            return;
        }

        // Load export service
        $this->loadExportService();

        try {
            $exportService = new \AhgMetadataExport\Services\ExportService();

            // Get top-level records for repository
            $criteria = new Criteria();
            $criteria->add(QubitInformationObject::REPOSITORY_ID, $repositoryId);
            $criteria->add(QubitInformationObject::PARENT_ID, QubitInformationObject::ROOT_ID);

            $resources = QubitInformationObject::get($criteria);

            if (0 === count($resources)) {
                $this->getUser()->setFlash('error', 'No records found for this repository');

                return;
            }

            // Build options
            $options = [
                'includeDigitalObjects' => $request->getParameter('include_digital_objects', true),
                'includeDrafts' => $request->getParameter('include_drafts', false),
                'includeChildren' => $request->getParameter('include_children', true),
                'maxDepth' => (int) $request->getParameter('max_depth', 0),
                'prettyPrint' => true,
            ];

            // Create ZIP file
            $zipFilename = 'export_'.$format.'_'.date('Ymd_His').'.zip';
            $zipPath = sys_get_temp_dir().'/'.$zipFilename;

            $zip = new ZipArchive();
            if (true !== $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE)) {
                throw new \Exception('Could not create ZIP file');
            }

            $count = 0;
            foreach ($resources as $resource) {
                $content = $exportService->export($resource, $format, $options);
                $filename = $exportService->generateFilename($resource, $format);
                $zip->addFromString($filename, $content);
                ++$count;
            }

            $zip->close();

            // Send ZIP file
            $this->response->setContentType('application/zip');
            $this->response->setHttpHeader('Content-Disposition', 'attachment; filename="'.$zipFilename.'"');
            $this->response->setHttpHeader('Content-Length', filesize($zipPath));
            $this->response->setContent(file_get_contents($zipPath));

            // Clean up
            unlink($zipPath);

            // Log export
            $exportService->logExport(
                $format,
                'bulk',
                null,
                '',
                filesize($zipPath),
                $this->context->user->getAttribute('user_id')
            );

            $this->setTemplate('none');

            return;
        } catch (\Exception $e) {
            $this->getUser()->setFlash('error', 'Bulk export failed: '.$e->getMessage());
        }
    }

    /**
     * Get recent exports from log
     *
     * @param int $limit
     *
     * @return array
     */
    protected function getRecentExports(int $limit = 10): array
    {
        try {
            $rows = DB::table('metadata_export_log')
                ->orderBy('created_at', 'desc')
                ->limit($limit)
                ->get(['format_code', 'resource_type', 'resource_id', 'created_at']);

            return $rows->map(function ($row) {
                return (array) $row;
            })->toArray();
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Load export service and dependencies
     */
    protected function loadExportService(): void
    {
        $pluginDir = sfConfig::get('sf_plugins_dir').'/ahgMetadataExportPlugin';

        // Register autoloader
        spl_autoload_register(function ($class) use ($pluginDir) {
            $prefix = 'AhgMetadataExport\\';
            if (0 === strpos($class, $prefix)) {
                $relativeClass = substr($class, strlen($prefix));
                $file = $pluginDir.'/lib/'.str_replace('\\', '/', $relativeClass).'.php';
                if (file_exists($file)) {
                    require_once $file;

                    return true;
                }
            }

            return false;
        });

        // Load required files
        require_once $pluginDir.'/lib/Contracts/ExporterInterface.php';
        require_once $pluginDir.'/lib/Exporters/AbstractXmlExporter.php';
        require_once $pluginDir.'/lib/Exporters/AbstractRdfExporter.php';
        require_once $pluginDir.'/lib/Services/ExportService.php';
    }
}
