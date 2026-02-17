<?php

use AtomFramework\Http\Controllers\AhgController;
require_once sfConfig::get('sf_root_dir').'/atom-ahg-plugins/ahgFavoritesPlugin/lib/Services/FavoritesService.php';

use AtomAhgPlugins\ahgFavoritesPlugin\Services\FavoritesService;

/**
 * Move Favorites to Folder Action
 *
 * @author Johan Pieterse <johan@theahg.co.za>
 */
class favoritesMoveToFolderAction extends AhgController
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
        $ids = $request->getParameter('ids', []);
        $folderId = $request->getParameter('folder_id');

        if (!is_array($ids) || empty($ids)) {
            $this->getUser()->setFlash('error', __('No items selected.'));
            $this->redirect(['module' => 'favorites', 'action' => 'browse']);

            return;
        }

        $service = new FavoritesService();
        $result = $service->moveToFolder($userId, $ids, $folderId ? (int) $folderId : null);

        $this->getUser()->setFlash($result['success'] ? 'notice' : 'error', $result['message']);
        $this->redirect(['module' => 'favorites', 'action' => 'browse']);
    }
}
