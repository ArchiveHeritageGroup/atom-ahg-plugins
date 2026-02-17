<?php

use AtomFramework\Http\Controllers\AhgController;
require_once sfConfig::get('sf_root_dir').'/atom-ahg-plugins/ahgFavoritesPlugin/lib/Services/FavoritesExportService.php';

use AtomAhgPlugins\ahgFavoritesPlugin\Services\FavoritesExportService;

/**
 * Export a specific folder's favorites in various formats
 *
 * @author Johan Pieterse <johan@theahg.co.za>
 */
class favoritesExportFolderAction extends AhgController
{
    public function execute($request)
    {
        if (!$this->getUser()->isAuthenticated()) {
            $this->redirect(['module' => 'user', 'action' => 'login']);

            return;
        }

        $userId = $this->getUser()->getAttribute('user_id');
        $folderId = (int) $request->getParameter('id');
        $format = strtolower($request->getParameter('format', 'csv'));

        if (!$folderId) {
            $this->getUser()->setFlash('error', __('No folder specified.'));
            $this->redirect(['module' => 'favorites', 'action' => 'browse']);

            return;
        }

        // Verify folder ownership
        $folder = \Illuminate\Database\Capsule\Manager::table('favorites_folder')
            ->where('id', $folderId)
            ->where('user_id', $userId)
            ->first();

        if (!$folder) {
            $this->getUser()->setFlash('error', __('Folder not found.'));
            $this->redirect(['module' => 'favorites', 'action' => 'browse']);

            return;
        }

        $service = new FavoritesExportService();
        $safeName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $folder->name);

        switch ($format) {
            case 'csv':
                $file = $service->exportCsv($userId, $folderId);
                $this->streamFile($file, "favorites_{$safeName}.csv", 'text/csv');
                break;

            case 'pdf':
                $file = $service->exportPdf($userId, $folderId);
                $this->streamFile($file, "favorites_{$safeName}.pdf", 'application/pdf');
                break;

            case 'bibtex':
                $content = $service->exportBibTeX($userId, $folderId);
                $this->streamContent($content, "favorites_{$safeName}.bib", 'application/x-bibtex');
                break;

            case 'ris':
                $content = $service->exportRis($userId, $folderId);
                $this->streamContent($content, "favorites_{$safeName}.ris", 'application/x-research-info-systems');
                break;

            case 'json':
                $content = $service->exportJson($userId, $folderId);
                $this->streamContent($content, "favorites_{$safeName}.json", 'application/json');
                break;

            case 'print':
                $html = $service->exportPrintHtml($userId, $folderId);
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
