<?php

use AtomFramework\Http\Controllers\AhgController;
require_once sfConfig::get('sf_root_dir').'/atom-ahg-plugins/ahgFavoritesPlugin/lib/Services/ResearchBridgeService.php';

use AtomAhgPlugins\ahgFavoritesPlugin\Services\ResearchBridgeService;

/**
 * Send favorites to a research collection
 *
 * @author Johan Pieterse <johan@theahg.co.za>
 */
class favoritesSendToCollectionAction extends AhgController
{
    public function execute($request)
    {
        $this->getResponse()->setContentType('application/json');

        if (!$this->getUser()->isAuthenticated()) {
            echo json_encode(['success' => false, 'message' => 'Not authenticated.']);
            exit;
        }

        $userId = $this->getUser()->getAttribute('user_id');
        $service = new ResearchBridgeService();

        // List mode — return collections for picker
        if ($request->getParameter('list')) {
            $collections = $service->getResearcherCollections($userId);
            echo json_encode(['collections' => $collections]);
            exit;
        }

        if (!$request->isMethod('post')) {
            echo json_encode(['success' => false, 'message' => 'POST required.']);
            exit;
        }

        $ids = $request->getParameter('ids', []);
        $collectionId = (int) $request->getParameter('collection_id');
        $includeNotes = (bool) $request->getParameter('include_notes', true);

        if (empty($ids) || !$collectionId) {
            echo json_encode(['success' => false, 'message' => 'Missing parameters.']);
            exit;
        }

        $result = $service->sendToCollection($userId, $ids, $collectionId, $includeNotes);
        echo json_encode($result);
        exit;
    }
}
