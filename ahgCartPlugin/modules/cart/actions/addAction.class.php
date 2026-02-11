<?php

use AtomFramework\Http\Controllers\AhgController;
require_once $this->config('sf_root_dir').'/atom-ahg-plugins/ahgCartPlugin/lib/Services/CartService.php';
use AtomAhgPlugins\ahgCartPlugin\Services\CartService;
use Illuminate\Database\Capsule\Manager as DB;

/**
 * Add to Cart Action - Supports both logged-in users and guests
 *
 * @author Johan Pieterse <johan@theahg.co.za>
 */
class cartAddAction extends AhgController
{
    public function execute($request)
    {
        $slug = $request->getParameter('slug');
        
        $objectId = DB::table('slug')
            ->where('slug', $slug)
            ->value('object_id');
        
        if (!$objectId) {
            $this->getUser()->setFlash('error', 'Item not found.');
            $this->redirect(['module' => 'informationobject', 'action' => 'browse']);
            return;
        }
        
        $title = DB::table('information_object_i18n')
            ->where('id', $objectId)
            ->where('culture', 'en')
            ->value('title');
        
        // Get user ID or session ID for guests
        $userId = null;
        $sessionId = null;
        
        if ($this->getUser()->isAuthenticated()) {
            $userId = $this->getUser()->getAttribute('user_id');
        } else {
            // Guest user - use session ID
            $sessionId = session_id();
            if (empty($sessionId)) {
                @session_start();
                $sessionId = session_id();
            }
            error_log("CART ADD DEBUG: Guest session_id = " . $sessionId);
        }
        
        $service = new CartService();
        $result = $service->addToCart($userId, $objectId, $title, $slug, $sessionId);
        
        $this->getUser()->setFlash($result['success'] ? 'notice' : 'error', $result['message']);
        // Use direct slug URL to avoid routing conflicts
        $this->redirect('/' . $slug);
    }
}
