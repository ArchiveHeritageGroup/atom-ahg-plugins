<?php

require_once sfConfig::get('sf_root_dir').'/atom-ahg-plugins/ahgFavoritesPlugin/lib/Services/FavoritesService.php';

use AtomAhgPlugins\ahgFavoritesPlugin\Services\FavoritesService;
use Illuminate\Database\Capsule\Manager as DB;

/**
 * Add to Favorites Action
 *
 * @author Johan Pieterse <johan@theahg.co.za>
 */
class ahgFavoritesAddAction extends sfAction
{
    public function execute($request)
    {
        // Check if user is authenticated
        if (!$this->context->user->isAuthenticated()) {
            $this->context->user->setFlash('error', 'Please log in to add favorites.');
            $this->redirect(['module' => 'user', 'action' => 'login']);
            return;
        }

        $slug = $request->getParameter('slug');

        // Get object ID from slug
        $objectId = DB::table('slug')
            ->where('slug', $slug)
            ->value('object_id');

        if (!$objectId) {
            $this->context->user->setFlash('error', 'Item not found.');
            $this->redirect(['module' => 'informationobject', 'action' => 'browse']);
            return;
        }

        // Get title
        $title = DB::table('information_object_i18n')
            ->where('id', $objectId)
            ->where('culture', 'en')
            ->value('title');

        $userId = $this->context->user->getAttribute('user_id');
        $service = new FavoritesService();

        $result = $service->addToFavorites($userId, $objectId, $title, $slug);

        $this->context->user->setFlash($result['success'] ? 'notice' : 'error', $result['message']);

        // Redirect back to the item
        $this->redirect(['module' => 'informationobject', 'slug' => $slug]);
    }
}
