<?php

use AtomFramework\Http\Controllers\AhgController;
require_once sfConfig::get('sf_root_dir').'/atom-ahg-plugins/ahgFavoritesPlugin/lib/Services/FolderService.php';

use AtomAhgPlugins\ahgFavoritesPlugin\Services\FolderService;

/**
 * Edit Folder Action
 *
 * @author Johan Pieterse <johan@theahg.co.za>
 */
class favoritesFolderEditAction extends AhgController
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

        $data = [];
        $name = $request->getParameter('folder_name');
        if ($name !== null) {
            $data['name'] = trim($name);
        }

        $description = $request->getParameter('folder_description');
        if ($description !== null) {
            $data['description'] = trim($description) ?: null;
        }

        $service = new FolderService();
        $result = $service->updateFolder($userId, $folderId, $data);

        $this->getUser()->setFlash($result['success'] ? 'notice' : 'error', $result['message']);
        $this->redirect('/favorites?folder_id='.$folderId);
    }
}
