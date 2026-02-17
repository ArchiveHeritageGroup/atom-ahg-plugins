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
        if (!$this->getUser()->isAuthenticated()) {
            if ($this->isAjax($request)) {
                $this->getResponse()->setContentType('application/json');
                return $this->renderText(json_encode(['success' => false, 'message' => __('Not authenticated.')]));
            }
            $this->redirect('user/login');
        }

        $userId = $this->getUser()->getAttribute('user_id');
        $service = new ResearchBridgeService();

        // List mode — return collections for picker
        if ($request->getParameter('list')) {
            $this->getResponse()->setContentType('application/json');
            $collections = $service->getResearcherCollections($userId);
            return $this->renderText(json_encode(['collections' => $collections]));
        }

        if (!$request->isMethod('post')) {
            $this->redirect(['module' => 'favorites', 'action' => 'browse']);
        }

        $ids = $request->getParameter('ids', []);
        $collectionId = (int) $request->getParameter('collection_id');
        $includeNotes = (bool) $request->getParameter('include_notes', true);

        if (empty($ids) || !$collectionId) {
            if ($this->isAjax($request)) {
                $this->getResponse()->setContentType('application/json');
                return $this->renderText(json_encode(['success' => false, 'message' => __('Missing parameters.')]));
            }
            $this->getUser()->setFlash('error', __('Please select items and a collection.'));
            $this->redirect(['module' => 'favorites', 'action' => 'browse']);
        }

        $result = $service->sendToCollection($userId, $ids, $collectionId, $includeNotes);

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
