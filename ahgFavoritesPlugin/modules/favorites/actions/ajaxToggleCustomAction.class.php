<?php

use AtomFramework\Http\Controllers\AhgController;
require_once sfConfig::get('sf_root_dir').'/atom-ahg-plugins/ahgFavoritesPlugin/lib/Services/FavoritesService.php';

use AtomAhgPlugins\ahgFavoritesPlugin\Services\FavoritesService;

/**
 * AJAX Toggle Custom Favorite Action
 *
 * Supports non-AtoM entity types (research journals, collections, projects, etc.)
 * Parameters: object_id, object_type, title, url
 *
 * @author Johan Pieterse <johan@theahg.co.za>
 */
class favoritesAjaxToggleCustomAction extends AhgController
{
    public function execute($request)
    {
        $this->getResponse()->setContentType('application/json');

        if (!$this->getUser()->isAuthenticated()) {
            return $this->renderText(json_encode(['success' => false, 'message' => 'Not authenticated']));
        }

        $userId = (int) $this->getUser()->getAttribute('user_id');
        $objectId = (int) $request->getParameter('object_id');
        $objectType = $request->getParameter('object_type', '');
        $title = $request->getParameter('title', '');
        $url = $request->getParameter('url', '');

        if (!$objectId || !$objectType) {
            return $this->renderText(json_encode(['success' => false, 'message' => 'object_id and object_type are required']));
        }

        $folderId = $request->getParameter('folder_id');
        $folderId = $folderId ? (int) $folderId : null;

        $service = new FavoritesService();
        $result = $service->toggleCustom($userId, $objectId, $objectType, $title, $url, $folderId);

        return $this->renderText(json_encode($result));
    }
}
