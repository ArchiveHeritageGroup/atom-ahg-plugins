<?php

require_once sfConfig::get('sf_root_dir').'/atom-ahg-plugins/ahgCartPlugin/lib/Services/CartService.php';

use AtomAhgPlugins\ahgCartPlugin\Services\CartService;
use Illuminate\Database\Capsule\Manager as DB;

/**
 * Add to Cart Action
 *
 * @author Johan Pieterse <johan@theahg.co.za>
 */
class ahgCartAddAction extends sfAction
{
    public function execute($request)
    {
        if (!$this->context->user->isAuthenticated()) {
            $this->context->user->setFlash('error', 'Please log in to add items to cart.');
            $this->redirect(['module' => 'user', 'action' => 'login']);
            return;
        }

        $slug = $request->getParameter('slug');

        $objectId = DB::table('slug')
            ->where('slug', $slug)
            ->value('object_id');

        if (!$objectId) {
            $this->context->user->setFlash('error', 'Item not found.');
            $this->redirect(['module' => 'informationobject', 'action' => 'browse']);
            return;
        }

        $title = DB::table('information_object_i18n')
            ->where('id', $objectId)
            ->where('culture', 'en')
            ->value('title');

        $userId = $this->context->user->getAttribute('user_id');
        $service = new CartService();

        $result = $service->addToCart($userId, $objectId, $title, $slug);

        $this->context->user->setFlash($result['success'] ? 'notice' : 'error', $result['message']);
        $this->redirect(['module' => 'informationobject', 'slug' => $slug]);
    }
}
