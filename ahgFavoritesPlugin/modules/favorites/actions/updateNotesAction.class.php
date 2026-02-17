<?php

use AtomFramework\Http\Controllers\AhgController;
require_once sfConfig::get('sf_root_dir').'/atom-ahg-plugins/ahgFavoritesPlugin/lib/Services/FavoritesService.php';

use AtomAhgPlugins\ahgFavoritesPlugin\Services\FavoritesService;

/**
 * Update Notes on a Favorite (AJAX)
 *
 * @author Johan Pieterse <johan@theahg.co.za>
 */
class favoritesUpdateNotesAction extends AhgController
{
    public function execute($request)
    {
        $this->getResponse()->setContentType('application/json');

        if (!$this->getUser()->isAuthenticated()) {
            return $this->renderText(json_encode(['success' => false, 'message' => 'Not authenticated']));
        }

        if (!$request->isMethod('post')) {
            return $this->renderText(json_encode(['success' => false, 'message' => 'POST required']));
        }

        $userId = $this->getUser()->getAttribute('user_id');
        $id = (int) $request->getParameter('id');
        $notes = $request->getParameter('notes', '');

        $service = new FavoritesService();
        $result = $service->updateNotes($userId, $id, $notes ?: null);

        return $this->renderText(json_encode($result));
    }
}
