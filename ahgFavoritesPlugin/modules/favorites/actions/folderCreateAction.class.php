<?php

use AtomFramework\Http\Controllers\AhgController;
require_once sfConfig::get('sf_root_dir').'/atom-ahg-plugins/ahgFavoritesPlugin/lib/Services/FolderService.php';

use AtomAhgPlugins\ahgFavoritesPlugin\Services\FolderService;

/**
 * Create Folder Action
 *
 * @author Johan Pieterse <johan@theahg.co.za>
 */
class favoritesFolderCreateAction extends AhgController
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
        $name = trim($request->getParameter('folder_name', ''));
        $description = trim($request->getParameter('folder_description', ''));
        $parentId = $request->getParameter('parent_id');

        $service = new FolderService();
        $result = $service->createFolder(
            $userId,
            $name,
            $description ?: null,
            $parentId ? (int) $parentId : null
        );

        $this->getUser()->setFlash($result['success'] ? 'notice' : 'error', $result['message']);
        $this->redirect(['module' => 'favorites', 'action' => 'browse']);
    }
}
