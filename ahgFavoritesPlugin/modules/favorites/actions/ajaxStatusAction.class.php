<?php

use AtomFramework\Http\Controllers\AhgController;
require_once sfConfig::get('sf_root_dir').'/atom-ahg-plugins/ahgFavoritesPlugin/lib/Services/FavoritesService.php';

use AtomAhgPlugins\ahgFavoritesPlugin\Services\FavoritesService;
use Illuminate\Database\Capsule\Manager as DB;

/**
 * AJAX Check Favorite Status Action
 *
 * @author Johan Pieterse <johan@theahg.co.za>
 */
class favoritesAjaxStatusAction extends AhgController
{
    public function execute($request)
    {
        $this->getResponse()->setContentType('application/json');

        if (!$this->getUser()->isAuthenticated()) {
            return $this->renderText(json_encode(['favorited' => false, 'authenticated' => false]));
        }

        $userId = $this->getUser()->getAttribute('user_id');
        $slug = $request->getParameter('slug');

        if (!$slug) {
            return $this->renderText(json_encode(['favorited' => false, 'error' => __('Slug is required')]));
        }

        $objectId = DB::table('slug')->where('slug', $slug)->value('object_id');

        if (!$objectId) {
            return $this->renderText(json_encode(['favorited' => false, 'error' => __('Item not found')]));
        }

        $service = new FavoritesService();
        $isFav = $service->isFavorited($userId, (int) $objectId);

        return $this->renderText(json_encode([
            'favorited' => $isFav,
            'authenticated' => true,
        ]));
    }
}
