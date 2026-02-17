<?php

use AtomFramework\Http\Controllers\AhgController;
require_once sfConfig::get('sf_root_dir').'/atom-ahg-plugins/ahgFavoritesPlugin/lib/Services/FavoritesExportService.php';

use AtomAhgPlugins\ahgFavoritesPlugin\Services\FavoritesExportService;

/**
 * Export all favorites in various formats
 *
 * @author Johan Pieterse <johan@theahg.co.za>
 */
class favoritesExportAction extends AhgController
{
    public function execute($request)
    {
        if (!$this->getUser()->isAuthenticated()) {
            $this->redirect(['module' => 'user', 'action' => 'login']);

            return;
        }

        $userId = $this->getUser()->getAttribute('user_id');
        $format = strtolower($request->getParameter('format', 'csv'));
        $service = new FavoritesExportService();

        switch ($format) {
            case 'csv':
                $file = $service->exportCsv($userId);
                $this->streamFile($file, 'favorites.csv', 'text/csv');
                break;

            case 'pdf':
                $file = $service->exportPdf($userId);
                $this->streamFile($file, 'favorites.pdf', 'application/pdf');
                break;

            case 'bibtex':
                $content = $service->exportBibTeX($userId);
                $this->streamContent($content, 'favorites.bib', 'application/x-bibtex');
                break;

            case 'ris':
                $content = $service->exportRis($userId);
                $this->streamContent($content, 'favorites.ris', 'application/x-research-info-systems');
                break;

            case 'json':
                $content = $service->exportJson($userId);
                $this->streamContent($content, 'favorites.json', 'application/json');
                break;

            case 'print':
                $html = $service->exportPrintHtml($userId);
                echo $html;
                exit;

            default:
                $this->getUser()->setFlash('error', __('Unsupported export format.'));
                $this->redirect(['module' => 'favorites', 'action' => 'browse']);

                return;
        }
    }

    private function streamFile(string $filePath, string $filename, string $contentType): void
    {
        header('Content-Type: ' . $contentType);
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($filePath));
        readfile($filePath);
        unlink($filePath);
        exit;
    }

    private function streamContent(string $content, string $filename, string $contentType): void
    {
        header('Content-Type: ' . $contentType);
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($content));
        echo $content;
        exit;
    }
}
