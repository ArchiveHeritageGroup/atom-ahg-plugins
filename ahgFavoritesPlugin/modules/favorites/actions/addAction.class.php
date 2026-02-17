<?php

use AtomFramework\Http\Controllers\AhgController;
require_once sfConfig::get('sf_root_dir').'/atom-ahg-plugins/ahgFavoritesPlugin/lib/Services/FavoritesService.php';

use AtomAhgPlugins\ahgFavoritesPlugin\Services\FavoritesService;
use Illuminate\Database\Capsule\Manager as DB;

/**
 * Add to Favorites Action
 *
 * @author Johan Pieterse <johan@theahg.co.za>
 */
class favoritesAddAction extends AhgController
{
    public function execute($request)
    {
        if (!$this->getUser()->isAuthenticated()) {
            $this->getUser()->setFlash('error', __('Please log in to add favorites.'));
            $this->redirect(['module' => 'user', 'action' => 'login']);

            return;
        }

        $slug = $request->getParameter('slug');

        // Get object ID from slug
        $objectId = DB::table('slug')
            ->where('slug', $slug)
            ->value('object_id');

        if (!$objectId) {
            $this->getUser()->setFlash('error', __('Item not found.'));
            $this->redirect(['module' => 'informationobject', 'action' => 'browse']);

            return;
        }

        // Get title in user's culture
        $culture = $this->getUser()->getCulture() ?: 'en';
        $title = DB::table('information_object_i18n')
            ->where('id', $objectId)
            ->where('culture', $culture)
            ->value('title');

        if (!$title && $culture !== 'en') {
            $title = DB::table('information_object_i18n')
                ->where('id', $objectId)
                ->where('culture', 'en')
                ->value('title');
        }

        $userId = $this->getUser()->getAttribute('user_id');
        $service = new FavoritesService();

        $result = $service->addToFavorites($userId, $objectId, $title, $slug);

        $this->getUser()->setFlash($result['success'] ? 'notice' : 'error', $result['message']);

        // Redirect back to the item using direct slug URL
        $this->redirect('/'.$slug);
    }
}
