<?php

use AtomFramework\Http\Controllers\AhgController;
require_once sfConfig::get('sf_root_dir').'/atom-ahg-plugins/ahgFavoritesPlugin/lib/Services/ResearchBridgeService.php';

use AtomAhgPlugins\ahgFavoritesPlugin\Services\ResearchBridgeService;

/**
 * Send favorites to a research bibliography (cite)
 *
 * @author Johan Pieterse <johan@theahg.co.za>
 */
class favoritesSendToBibliographyAction extends AhgController
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

        // List mode — return bibliographies for picker
        if ($request->getParameter('list')) {
            $bibliographies = $service->getResearcherBibliographies($userId);
            echo json_encode(['bibliographies' => $bibliographies]);
            exit;
        }

        if (!$request->isMethod('post')) {
            echo json_encode(['success' => false, 'message' => 'POST required.']);
            exit;
        }

        $ids = $request->getParameter('ids', []);
        $bibliographyId = (int) $request->getParameter('bibliography_id');
        $style = $request->getParameter('style', 'chicago');

        if (empty($ids) || !$bibliographyId) {
            echo json_encode(['success' => false, 'message' => 'Missing parameters.']);
            exit;
        }

        $result = $service->sendToBibliography($userId, $ids, $bibliographyId, $style);
        echo json_encode($result);
        exit;
    }
}
