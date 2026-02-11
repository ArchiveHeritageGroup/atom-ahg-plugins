<?php

use AtomFramework\Http\Controllers\AhgController;
require_once $this->config('sf_root_dir').'/atom-ahg-plugins/ahgFavoritesPlugin/lib/Services/FavoritesService.php';

use AtomAhgPlugins\ahgFavoritesPlugin\Services\FavoritesService;

/**
 * Clear All Favorites Action
 *
 * @author Johan Pieterse <johan@theahg.co.za>
 */
class favoritesClearAction extends AhgController
{
    public function execute($request)
    {
        // Check if user is authenticated
        if (!$this->getUser()->isAuthenticated()) {
            $this->redirect(['module' => 'user', 'action' => 'login']);
            return;
        }

        // Only allow POST
        if (!$request->isMethod('post')) {
            $this->redirect(['module' => 'favorites', 'action' => 'browse']);
            return;
        }

        $userId = $this->getUser()->getAttribute('user_id');
        $service = new FavoritesService();

        $result = $service->clearAll($userId);

        $this->getUser()->setFlash('notice', $result['message']);
        $this->redirect(['module' => 'favorites', 'action' => 'browse']);
    }
}
