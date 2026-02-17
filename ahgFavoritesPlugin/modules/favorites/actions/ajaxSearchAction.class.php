<?php

use AtomFramework\Http\Controllers\AhgController;
require_once sfConfig::get('sf_root_dir').'/atom-ahg-plugins/ahgFavoritesPlugin/lib/Services/FavoritesService.php';

use AtomAhgPlugins\ahgFavoritesPlugin\Services\FavoritesService;

/**
 * AJAX Search Favorites Action
 *
 * @author Johan Pieterse <johan@theahg.co.za>
 */
class favoritesAjaxSearchAction extends AhgController
{
    public function execute($request)
    {
        $this->getResponse()->setContentType('application/json');

        if (!$this->getUser()->isAuthenticated()) {
            return $this->renderText(json_encode(['success' => false, 'message' => __('Not authenticated')]));
        }

        $userId = $this->getUser()->getAttribute('user_id');
        $service = new FavoritesService();

        $params = [
            'query' => $request->getParameter('query', ''),
            'page' => $request->getParameter('page', 1),
            'limit' => $request->getParameter('limit', 25),
            'sort' => $request->getParameter('sort', 'created_at'),
            'sortDir' => $request->getParameter('sortDir', 'desc'),
        ];

        $folderId = $request->getParameter('folder_id');
        if ($folderId) {
            $params['folder_id'] = (int) $folderId;
        }

        $result = $service->browse($userId, $params);

        return $this->renderText(json_encode([
            'success' => true,
            'hits' => $result['hits'],
            'total' => $result['total'],
            'page' => $result['page'],
            'limit' => $result['limit'],
        ]));
    }
}
