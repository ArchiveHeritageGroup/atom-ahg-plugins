<?php

declare(strict_types=1);

use AtomFramework\Http\Controllers\AhgController;

/**
 * libraryExportAction — export library items to CSV, BibTeX, or RIS.
 *
 * GET  /library/export            → show export form with filters
 * GET  /library/export?format=...&download=1 → immediate download (backwards compatible)
 * POST /library/export            → download with applied filters
 *
 * Supported formats:
 *   csv    — CSV (spreadsheet)
 *   bibtex — BibTeX bibliography
 *   ris    — RIS (EndNote / Reference Manager)
 *
 * Filter params (all optional):
 *   search, material_type, language, publisher, date_from, date_to, format, download
 *
 * @package ahgLibraryPlugin
 */
class libraryExportAction extends AhgController
{
    /** @var string */
    public $format = 'csv';

    /** @var array */
    public $filters = [];

    /** @var int */
    public $itemCount = 0;

    /** @var array|null */
    public $preview = null;

    public function execute($request)
    {
        $this->format = strtolower(trim($request->getParameter('format', 'csv')));
        if (!in_array($this->format, ['csv', 'bibtex', 'ris'], true)) {
            $this->format = 'csv';
        }

        // Build filter params
        $this->filters = [
            'search'        => trim($request->getParameter('search', '')),
            'material_type' => trim($request->getParameter('material_type', '')),
            'language'      => trim($request->getParameter('language', '')),
            'publisher'     => trim($request->getParameter('publisher', '')),
            'date_from'     => trim($request->getParameter('date_from', '')),
            'date_to'       => trim($request->getParameter('date_to', '')),
        ];
        $this->filters = array_filter($this->filters);

        // Immediate download via GET ?download=1
        if ($request->getParameter('download')) {
            return $this->streamDownload();
        }

        // GET (no download param) — show the filter UI + preview
        // POST — stream download with filters applied
        if ($request->isMethod('post')) {
            return $this->streamDownload();
        }

        // Preview row count for the filter summary
        try {
            require_once sfConfig::get('sf_root_dir')
                . '/atom-ahg-plugins/ahgLibraryPlugin/lib/Service/ExportService.php';

            $svc = \ahgLibraryPlugin\Service\ExportService::getInstance();
            $items = $svc->fetchItems($this->filters);
            $this->itemCount = $items->count();
            $this->preview = $items->take(5)->map(fn($r) => (array) $r)->values()->all();
        } catch (\Exception $e) {
            $this->itemCount = 0;
            $this->preview = [];
        }

        return sfView::SUCCESS;
    }

    /**
     * Stream the export file as a direct download.
     *
     * @return string sfView::NONE (no template rendered)
     */
    protected function streamDownload(): string
    {
        try {
            require_once sfConfig::get('sf_root_dir')
                . '/atom-ahg-plugins/ahgLibraryPlugin/lib/Service/ExportService.php';

            $svc = \ahgLibraryPlugin\Service\ExportService::getInstance();
            $result = $svc->export($this->format, $this->filters);
        } catch (\Exception $e) {
            error_log('libraryExportAction error: ' . $e->getMessage());
            $result = [
                'content'  => "Export error: " . $e->getMessage(),
                'filename' => "export_error.txt",
                'mime'     => 'text/plain; charset=utf-8',
            ];
        }

        $response = $this->getResponse();
        $response->setContentType($result['mime']);
        $response->setHttpHeader(
            'Content-Disposition',
            'attachment; filename="' . $result['filename'] . '"'
        );
        $response->setHttpHeader('Pragma', 'no-cache');
        $response->setHttpHeader('Cache-Control', 'no-store, no-cache, must-revalidate');

        return $this->renderText($result['content']);
    }
}
