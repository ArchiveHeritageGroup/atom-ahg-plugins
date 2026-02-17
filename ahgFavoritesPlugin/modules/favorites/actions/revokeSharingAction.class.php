<?php

use AtomFramework\Http\Controllers\AhgController;
require_once sfConfig::get('sf_root_dir').'/atom-ahg-plugins/ahgFavoritesPlugin/lib/Services/FavoritesShareService.php';

use AtomAhgPlugins\ahgFavoritesPlugin\Services\FavoritesShareService;

/**
 * Revoke sharing for a favorites folder
 *
 * @author Johan Pieterse <johan@theahg.co.za>
 */
class favoritesRevokeSharingAction extends AhgController
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

        $service = new FavoritesShareService();
        $revoked = $service->revokeShare($userId, $folderId);

        if ($request->isXmlHttpRequest()) {
            header('Content-Type: application/json');
            echo json_encode(['success' => $revoked]);
            exit;
        }

        if ($revoked) {
            $this->getUser()->setFlash('notice', 'Folder sharing has been revoked.');
        } else {
            $this->getUser()->setFlash('error', 'Could not revoke sharing.');
        }

        $this->redirect(['module' => 'favorites', 'action' => 'browse', 'folder_id' => $folderId]);
    }
}
