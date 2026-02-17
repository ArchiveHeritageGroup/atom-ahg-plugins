<?php

use AtomFramework\Http\Controllers\AhgController;
require_once sfConfig::get('sf_root_dir').'/atom-ahg-plugins/ahgFavoritesPlugin/lib/Services/FavoritesService.php';

use AtomAhgPlugins\ahgFavoritesPlugin\Services\FavoritesService;
use Illuminate\Database\Capsule\Manager as DB;

/**
 * AJAX Toggle Favorite Action
 *
 * @author Johan Pieterse <johan@theahg.co.za>
 */
class favoritesAjaxToggleAction extends AhgController
{
    public function execute($request)
    {
        $this->getResponse()->setContentType('application/json');

        if (!$this->getUser()->isAuthenticated()) {
            return $this->renderText(json_encode(['success' => false, 'message' => 'Not authenticated']));
        }

        $userId = $this->getUser()->getAttribute('user_id');
        $slug = $request->getParameter('slug');

        if (!$slug) {
            return $this->renderText(json_encode(['success' => false, 'message' => 'Slug is required']));
        }

        // Resolve object ID from slug
        $objectId = DB::table('slug')->where('slug', $slug)->value('object_id');

        if (!$objectId) {
            return $this->renderText(json_encode(['success' => false, 'message' => 'Item not found']));
        }

        $service = new FavoritesService();
        $result = $service->toggle($userId, (int) $objectId, $slug);

        return $this->renderText(json_encode($result));
    }
}
