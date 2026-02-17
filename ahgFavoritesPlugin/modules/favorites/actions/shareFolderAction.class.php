<?php

use AtomFramework\Http\Controllers\AhgController;
require_once sfConfig::get('sf_root_dir').'/atom-ahg-plugins/ahgFavoritesPlugin/lib/Services/FavoritesShareService.php';

use AtomAhgPlugins\ahgFavoritesPlugin\Services\FavoritesShareService;

/**
 * Share a favorites folder — generates a share link
 *
 * @author Johan Pieterse <johan@theahg.co.za>
 */
class favoritesShareFolderAction extends AhgController
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
        $result = $service->shareFolder($userId, $folderId, [
            'expires_in_days' => (int) $request->getParameter('expires_in_days', 30),
            'shared_via' => $request->getParameter('shared_via', 'link'),
        ]);

        // Return JSON for AJAX requests
        if ($request->isXmlHttpRequest()) {
            header('Content-Type: application/json');
            echo json_encode($result);
            exit;
        }

        if ($result['success']) {
            $this->getUser()->setFlash('notice', 'Folder shared. Link: ' . $result['url']);
        } else {
            $this->getUser()->setFlash('error', $result['message']);
        }

        $this->redirect(['module' => 'favorites', 'action' => 'browse', 'folder_id' => $folderId]);
    }
}
