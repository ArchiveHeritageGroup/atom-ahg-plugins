<?php

use AtomFramework\Http\Controllers\AhgController;
require_once sfConfig::get('sf_root_dir').'/atom-ahg-plugins/ahgFavoritesPlugin/lib/Services/FavoritesService.php';

use AtomAhgPlugins\ahgFavoritesPlugin\Services\FavoritesService;

/**
 * Remove from Favorites Action
 *
 * @author Johan Pieterse <johan@theahg.co.za>
 */
class favoritesRemoveAction extends AhgController
{
    public function execute($request)
    {
        // Check if user is authenticated
        if (!$this->getUser()->isAuthenticated()) {
            $this->redirect(['module' => 'user', 'action' => 'login']);
            return;
        }

        $id = $request->getParameter('id');
        $returnUrl = $request->getReferer();

        $userId = $this->getUser()->getAttribute('user_id');
        $service = new FavoritesService();

        $result = $service->removeFromFavorites($userId, (int) $id);

        $this->getUser()->setFlash($result['success'] ? 'notice' : 'error', $result['message']);

        // Redirect back
        if ($returnUrl) {
            $this->redirect($returnUrl);
        } else {
            $this->redirect(['module' => 'favorites', 'action' => 'browse']);
        }
    }
}
