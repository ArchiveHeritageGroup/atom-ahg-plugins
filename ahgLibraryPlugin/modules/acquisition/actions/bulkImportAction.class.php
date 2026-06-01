<?php

declare(strict_types=1);

use AtomFramework\Http\Controllers\AhgController;

/**
 * bulkImportAction — bulk import library items from CSV or MARCXML.
 *
 * GET  /acquisition/bulk-import   → show upload form
 * POST /acquisition/bulk-import   → process upload
 *
 * Supported formats:
 *   - CSV  (headers required; see importCsv column mapping)
 *   - MARCXML  (.xml)
 *
 * POST params:
 *   file           — uploaded file (required)
 *   format         — csv | marcxml  (auto-detected from extension if omitted)
 *   repository_id   — AtoM repository ID for imported items
 *   dry_run        — 1 = preview only, no changes written
 *
 * @package ahgLibraryPlugin
 */
class acquisitionBulkImportAction extends AhgController
{
    /** @var array|null */
    public $result;

    /** @var string|null */
    public $format;

    /** @var string|null */
    public $error;

    /** @var bool */
    public $dryRun = false;

    public function execute($request)
    {
        if ($request->isMethod('post')) {
            return $this->handlePost($request);
        }

        // GET — show form
        return sfView::SUCCESS;
    }

    protected function handlePost($request): string
    {
        $file = $request->getFile('file');

        if (empty($file) || empty($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            $this->error = __('No file uploaded or upload failed.');
            return sfView::SUCCESS;
        }

        $tmpPath = $file['tmp_name'];
        $originalName = $file['name'] ?? 'unknown';

        // Detect format
        $format = strtolower(trim($request->getParameter('format', '')));
        if (empty($format)) {
            $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
            if ($ext === 'xml') {
                $format = 'marcxml';
            } elseif (in_array($ext, ['csv', 'txt'])) {
                $format = 'csv';
            } else {
                $this->error = __('Cannot detect file format from extension: ') . $ext;
                return sfView::SUCCESS;
            }
        }

        $this->format = $format;
        $this->dryRun = (bool) $request->getParameter('dry_run', false);
        $repositoryId = $request->getParameter('repository_id') ? (int) $request->getParameter('repository_id') : null;

        // Move uploaded file to a safe temp location
        $safeName = preg_replace('/[^a-zA-Z0-9._-]/', '_', $originalName);
        $destPath = sys_get_temp_dir() . '/library_import_' . uniqid() . '_' . $safeName;

        if (!move_uploaded_file($tmpPath, $destPath)) {
            $this->error = __('Failed to save uploaded file.');
            return sfView::SUCCESS;
        }

        try {
            require_once sfConfig::get('sf_root_dir')
                . '/atom-ahg-plugins/ahgLibraryPlugin/lib/Service/MarcService.php';

            $svc = \ahgLibraryPlugin\Service\MarcService::getInstance();

            if ($format === 'csv') {
                $options = [
                    'dry_run'   => $this->dryRun,
                    'delimiter' => $request->getParameter('delimiter', ';'),
                    'enclosure' => $request->getParameter('enclosure', '"'),
                ];
                $this->result = $svc->importCsv($destPath, $repositoryId, $options);
            } else {
                $this->result = $svc->importMarcXml($destPath, $repositoryId);
            }

            $this->result['file'] = $originalName;
            $this->result['format'] = $this->format;
            $this->result['dry_run'] = $this->dryRun;

        } catch (\Exception $e) {
            $this->error = $e->getMessage();
            $this->result = [
                'imported' => 0,
                'skipped'  => 0,
                'errors'   => [$e->getMessage()],
                'results'  => [],
            ];
        } finally {
            // Clean up temp file
            @unlink($destPath);
        }

        return sfView::SUCCESS;
    }
}
