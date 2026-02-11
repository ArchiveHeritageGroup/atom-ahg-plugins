<?php

use AtomFramework\Http\Controllers\AhgController;
require_once $this->config('sf_root_dir').'/atom-ahg-plugins/ahgFavoritesPlugin/lib/Services/FavoritesService.php';

use AtomAhgPlugins\ahgFavoritesPlugin\Services\FavoritesService;

/**
 * Browse Favorites Action
 *
 * @author Johan Pieterse <johan@theahg.co.za>
 */
class favoritesBrowseAction extends AhgController
{
    public function execute($request)
    {
        // Check if user is authenticated
        if (!$this->getUser()->isAuthenticated()) {
            $this->redirect(['module' => 'user', 'action' => 'login']);
            return;
        }

        $userId = $this->getUser()->getAttribute('user_id');
        $service = new FavoritesService();

        $this->favorites = $service->getUserFavorites($userId);
        $this->count = count($this->favorites);
    }
}
