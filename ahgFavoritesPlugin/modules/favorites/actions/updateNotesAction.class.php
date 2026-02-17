<?php

use AtomFramework\Http\Controllers\AhgController;
require_once sfConfig::get('sf_root_dir').'/atom-ahg-plugins/ahgFavoritesPlugin/lib/Services/FavoritesService.php';

use AtomAhgPlugins\ahgFavoritesPlugin\Services\FavoritesService;
use Illuminate\Database\Capsule\Manager as DB;

/**
 * Update Notes on a Favorite (AJAX)
 * Also handles last_viewed_at tracking via sendBeacon
 *
 * @author Johan Pieterse <johan@theahg.co.za>
 */
class favoritesUpdateNotesAction extends AhgController
{
    public function execute($request)
    {
        $this->getResponse()->setContentType('application/json');

        if (!$this->getUser()->isAuthenticated()) {
            return $this->renderText(json_encode(['success' => false, 'message' => __('Not authenticated')]));
        }

        $userId = $this->getUser()->getAttribute('user_id');
        $id = (int) $request->getParameter('id');

        // Handle track_view beacon (lightweight last_viewed_at update)
        if ($request->getParameter('track_view')) {
            DB::table('favorites')
                ->where('id', $id)
                ->where('user_id', $userId)
                ->update(['last_viewed_at' => date('Y-m-d H:i:s')]);

            return $this->renderText(json_encode(['success' => true]));
        }

        if (!$request->isMethod('post')) {
            return $this->renderText(json_encode(['success' => false, 'message' => __('POST required')]));
        }

        $notes = $request->getParameter('notes', '');

        $service = new FavoritesService();
        $result = $service->updateNotes($userId, $id, $notes ?: null);

        return $this->renderText(json_encode($result));
    }
}
