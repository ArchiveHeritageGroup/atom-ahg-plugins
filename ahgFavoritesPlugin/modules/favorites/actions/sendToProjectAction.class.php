<?php

use AtomFramework\Http\Controllers\AhgController;
require_once sfConfig::get('sf_root_dir').'/atom-ahg-plugins/ahgFavoritesPlugin/lib/Services/ResearchBridgeService.php';

use AtomAhgPlugins\ahgFavoritesPlugin\Services\ResearchBridgeService;

/**
 * Send favorites to a research project
 *
 * @author Johan Pieterse <johan@theahg.co.za>
 */
class favoritesSendToProjectAction extends AhgController
{
    public function execute($request)
    {
        $this->getResponse()->setContentType('application/json');

        if (!$this->getUser()->isAuthenticated()) {
            echo json_encode(['success' => false, 'message' => __('Not authenticated.')]);
            exit;
        }

        $userId = $this->getUser()->getAttribute('user_id');
        $service = new ResearchBridgeService();

        // List mode — return projects for picker
        if ($request->getParameter('list')) {
            $projects = $service->getResearcherProjects($userId);
            echo json_encode(['projects' => $projects]);
            exit;
        }

        if (!$request->isMethod('post')) {
            echo json_encode(['success' => false, 'message' => __('POST required.')]);
            exit;
        }

        $ids = $request->getParameter('ids', []);
        $projectId = (int) $request->getParameter('project_id');

        if (empty($ids) || !$projectId) {
            echo json_encode(['success' => false, 'message' => __('Missing parameters.')]);
            exit;
        }

        $result = $service->sendToProject($userId, $ids, $projectId);
        echo json_encode($result);
        exit;
    }
}
