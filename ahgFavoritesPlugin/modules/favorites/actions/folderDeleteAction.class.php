<?php

use AtomFramework\Http\Controllers\AhgController;
require_once sfConfig::get('sf_root_dir').'/atom-ahg-plugins/ahgFavoritesPlugin/lib/Services/FolderService.php';

use AtomAhgPlugins\ahgFavoritesPlugin\Services\FolderService;

/**
 * Delete Folder Action
 *
 * @author Johan Pieterse <johan@theahg.co.za>
 */
class favoritesFolderDeleteAction extends AhgController
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
        $folderId = (int) $request->getParameter('id');

        $service = new FolderService();
        $result = $service->deleteFolder($userId, $folderId);

        $this->getUser()->setFlash($result['success'] ? 'notice' : 'error', $result['message']);
        $this->redirect(['module' => 'favorites', 'action' => 'browse']);
    }
}
