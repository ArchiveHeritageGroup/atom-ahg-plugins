<?php

require_once sfConfig::get('sf_root_dir').'/atom-ahg-plugins/ahgFavoritesPlugin/lib/Services/FavoritesService.php';

use AtomAhgPlugins\ahgFavoritesPlugin\Services\FavoritesService;

/**
 * Clear All Favorites Action
 *
 * @author Johan Pieterse <johan@theahg.co.za>
 */
class favoritesClearAction extends sfAction
{
    public function execute($request)
    {
        // Check if user is authenticated
        if (!$this->context->user->isAuthenticated()) {
            $this->redirect(['module' => 'user', 'action' => 'login']);
            return;
        }

        // Only allow POST
        if (!$request->isMethod('post')) {
            $this->redirect(['module' => 'favorites', 'action' => 'browse']);
            return;
        }

        $userId = $this->context->user->getAttribute('user_id');
        $service = new FavoritesService();

        $result = $service->clearAll($userId);

        $this->context->user->setFlash('notice', $result['message']);
        $this->redirect(['module' => 'favorites', 'action' => 'browse']);
    }
}
