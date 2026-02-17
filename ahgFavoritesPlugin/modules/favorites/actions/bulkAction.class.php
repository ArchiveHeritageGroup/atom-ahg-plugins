<?php

use AtomFramework\Http\Controllers\AhgController;
require_once sfConfig::get('sf_root_dir').'/atom-ahg-plugins/ahgFavoritesPlugin/lib/Services/FavoritesService.php';

use AtomAhgPlugins\ahgFavoritesPlugin\Services\FavoritesService;

/**
 * Bulk Action on Favorites (remove / move)
 *
 * @author Johan Pieterse <johan@theahg.co.za>
 */
class favoritesBulkAction extends AhgController
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
        $bulkAction = $request->getParameter('bulk_action');
        $ids = $request->getParameter('ids', []);

        if (!is_array($ids) || empty($ids)) {
            $this->getUser()->setFlash('error', 'No items selected.');
            $this->redirect(['module' => 'favorites', 'action' => 'browse']);

            return;
        }

        $service = new FavoritesService();

        switch ($bulkAction) {
            case 'remove':
                $result = $service->bulkRemove($userId, $ids);
                break;

            case 'move':
                $folderId = $request->getParameter('target_folder_id');
                $result = $service->moveToFolder($userId, $ids, $folderId ? (int) $folderId : null);
                break;

            default:
                $result = ['success' => false, 'message' => 'Invalid action.'];
        }

        $this->getUser()->setFlash($result['success'] ? 'notice' : 'error', $result['message']);
        $this->redirect(['module' => 'favorites', 'action' => 'browse']);
    }
}
