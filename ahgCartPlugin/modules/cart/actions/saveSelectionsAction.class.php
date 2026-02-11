<?php

use AtomFramework\Http\Controllers\AhgController;
use Illuminate\Database\Capsule\Manager as DB;

/**
 * Save Cart Selections Action (AJAX)
 * Saves multiple product selections per cart item
 *
 * @author Johan Pieterse <johan@theahg.co.za>
 */
class cartSaveSelectionsAction extends AhgController
{
    public function execute($request)
    {
        $this->getResponse()->setContentType('application/json');
        
        if (!$this->getUser()->isAuthenticated()) {
            return $this->renderText(json_encode(['success' => false, 'message' => 'Not authenticated']));
        }

        $userId = $this->getUser()->getAttribute('user_id');
        $selections = json_decode($request->getParameter('selections', '{}'), true);
        
        if (empty($selections)) {
            return $this->renderText(json_encode(['success' => true, 'message' => 'No selections']));
        }

        // Store selections in session for checkout
        $this->getUser()->setAttribute('cart_selections', $selections);

        return $this->renderText(json_encode([
            'success' => true,
            'message' => 'Selections saved',
            'count' => count($selections)
        ]));
    }
}
