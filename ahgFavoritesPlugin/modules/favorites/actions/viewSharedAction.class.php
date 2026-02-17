<?php

use AtomFramework\Http\Controllers\AhgController;
require_once sfConfig::get('sf_root_dir').'/atom-ahg-plugins/ahgFavoritesPlugin/lib/Services/FavoritesShareService.php';

use AtomAhgPlugins\ahgFavoritesPlugin\Services\FavoritesShareService;

/**
 * View a shared folder (public — no auth required)
 *
 * @author Johan Pieterse <johan@theahg.co.za>
 */
class favoritesViewSharedAction extends AhgController
{
    public function execute($request)
    {
        $token = $request->getParameter('token');
        if (!$token) {
            $this->forward404();

            return;
        }

        $service = new FavoritesShareService();
        $shared = $service->getSharedFolder($token);

        if (!$shared) {
            $this->forward404();

            return;
        }

        // Handle "copy to my favourites" POST
        if ($request->isMethod('post') && $request->getParameter('copy_to_favorites')) {
            if (!$this->getUser()->isAuthenticated()) {
                $this->getUser()->setFlash('error', __('You must be logged in to copy favorites.'));
                $this->redirect('/favorites/shared/' . $token);

                return;
            }

            $userId = $this->getUser()->getAttribute('user_id');
            $result = $service->copySharedToFavorites($userId, $token);
            $this->getUser()->setFlash('notice', $result['message']);
            $this->redirect('/favorites/shared/' . $token);

            return;
        }

        $this->folder = $shared['folder'];
        $this->items = $shared['items'];
        $this->ownerName = $shared['owner_name'];
        $this->token = $token;
        $this->isAuthenticated = $this->getUser()->isAuthenticated();

        $this->setTemplate('sharedView');
    }
}
