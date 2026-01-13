<?php

require_once sfConfig::get('sf_root_dir').'/atom-ahg-plugins/ahgFavoritesPlugin/lib/Services/FavoritesService.php';

use AtomAhgPlugins\ahgFavoritesPlugin\Services\FavoritesService;

/**
 * Remove from Favorites Action
 *
 * @author Johan Pieterse <johan@theahg.co.za>
 */
class ahgFavoritesRemoveAction extends sfAction
{
    public function execute($request)
    {
        // Check if user is authenticated
        if (!$this->context->user->isAuthenticated()) {
            $this->redirect(['module' => 'user', 'action' => 'login']);
            return;
        }

        $id = $request->getParameter('id');
        $returnUrl = $request->getReferer();

        $userId = $this->context->user->getAttribute('user_id');
        $service = new FavoritesService();

        $result = $service->removeFromFavorites($userId, (int) $id);

        $this->context->user->setFlash($result['success'] ? 'notice' : 'error', $result['message']);

        // Redirect back
        if ($returnUrl) {
            $this->redirect($returnUrl);
        } else {
            $this->redirect(['module' => 'ahgFavorites', 'action' => 'browse']);
        }
    }
}
