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
        if (!$this->getUser()->isAuthenticated()) {
            if ($this->isAjax($request)) {
                $this->getResponse()->setContentType('application/json');
                return $this->renderText(json_encode(['success' => false, 'message' => __('Not authenticated.')]));
            }
            $this->redirect('user/login');
        }

        $userId = $this->getUser()->getAttribute('user_id');
        $service = new ResearchBridgeService();

        // List mode — return projects for picker
        if ($request->getParameter('list')) {
            $this->getResponse()->setContentType('application/json');
            $projects = $service->getResearcherProjects($userId);
            return $this->renderText(json_encode(['projects' => $projects]));
        }

        if (!$request->isMethod('post')) {
            $this->redirect(['module' => 'favorites', 'action' => 'browse']);
        }

        $ids = $request->getParameter('ids', []);
        $projectId = (int) $request->getParameter('project_id');

        if (empty($ids) || !$projectId) {
            if ($this->isAjax($request)) {
                $this->getResponse()->setContentType('application/json');
                return $this->renderText(json_encode(['success' => false, 'message' => __('Missing parameters.')]));
            }
            $this->getUser()->setFlash('error', __('Please select items and a project.'));
            $this->redirect(['module' => 'favorites', 'action' => 'browse']);
        }

        $result = $service->sendToProject($userId, $ids, $projectId);

        if ($this->isAjax($request)) {
            $this->getResponse()->setContentType('application/json');
            return $this->renderText(json_encode($result));
        }

        $this->getUser()->setFlash($result['success'] ? 'notice' : 'error', $result['message']);
        $this->redirect(['module' => 'favorites', 'action' => 'browse']);
    }

    private function isAjax($request): bool
    {
        return $request->isXmlHttpRequest() || $request->getParameter('list');
    }
}
