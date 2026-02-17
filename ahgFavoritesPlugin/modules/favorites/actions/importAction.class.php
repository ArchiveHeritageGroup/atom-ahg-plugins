<?php

use AtomFramework\Http\Controllers\AhgController;
require_once sfConfig::get('sf_root_dir').'/atom-ahg-plugins/ahgFavoritesPlugin/lib/Services/FavoritesImportService.php';

use AtomAhgPlugins\ahgFavoritesPlugin\Services\FavoritesImportService;

/**
 * Import favorites from CSV or slug list
 *
 * @author Johan Pieterse <johan@theahg.co.za>
 */
class favoritesImportAction extends AhgController
{
    public function execute($request)
    {
        if (!$this->getUser()->isAuthenticated()) {
            $this->redirect(['module' => 'user', 'action' => 'login']);

            return;
        }

        if (!$request->isMethod('post')) {
            $this->redirect(['module' => 'favorites', 'action' => 'browse']);

            return;
        }

        $userId = $this->getUser()->getAttribute('user_id');
        $folderId = $request->getParameter('folder_id') ? (int) $request->getParameter('folder_id') : null;
        $service = new FavoritesImportService();

        // Check for CSV file upload
        $files = $request->getFiles('csv_file');
        $slugText = trim($request->getParameter('slug_list', ''));

        if (!empty($files) && !empty($files['tmp_name']) && is_uploaded_file($files['tmp_name'])) {
            $csvContent = file_get_contents($files['tmp_name']);
            $result = $service->importFromCsv($userId, $csvContent, $folderId);
        } elseif (!empty($slugText)) {
            // Parse slug list (newline or comma separated)
            $slugs = preg_split('/[\n,]+/', $slugText, -1, PREG_SPLIT_NO_EMPTY);
            $slugs = array_map('trim', $slugs);
            $result = $service->importFromSlugs($userId, $slugs, $folderId);
        } else {
            $result = ['imported' => 0, 'skipped' => 0, 'errors' => [__('No CSV file or slug list provided.')]];
        }

        // JSON response for AJAX
        if ($request->isXmlHttpRequest()) {
            header('Content-Type: application/json');
            echo json_encode($result);
            exit;
        }

        $msg = __('Imported %1% items.', ['%1%' => $result['imported']]);
        if ($result['skipped'] > 0) {
            $msg .= ' ' . __('%1% skipped.', ['%1%' => $result['skipped']]);
        }
        if (!empty($result['errors'])) {
            $msg .= ' ' . __('Errors:') . ' ' . implode('; ', array_slice($result['errors'], 0, 5));
        }

        $this->getUser()->setFlash($result['imported'] > 0 ? 'notice' : 'error', $msg);
        $this->redirect(['module' => 'favorites', 'action' => 'browse']);
    }
}
