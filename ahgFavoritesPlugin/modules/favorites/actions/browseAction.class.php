<?php

use AtomFramework\Http\Controllers\AhgController;
require_once sfConfig::get('sf_root_dir').'/atom-ahg-plugins/ahgFavoritesPlugin/lib/Services/FavoritesService.php';
require_once sfConfig::get('sf_root_dir').'/atom-ahg-plugins/ahgFavoritesPlugin/lib/Services/FolderService.php';
require_once sfConfig::get('sf_root_dir').'/atom-ahg-plugins/ahgFavoritesPlugin/lib/SimplePager.php';

use AtomAhgPlugins\ahgFavoritesPlugin\Services\FavoritesService;
use AtomAhgPlugins\ahgFavoritesPlugin\Services\FolderService;
use AtomAhgPlugins\ahgFavoritesPlugin\SimplePager;

/**
 * Browse Favorites Action
 *
 * @author Johan Pieterse <johan@theahg.co.za>
 */
class favoritesBrowseAction extends AhgController
{
    public function execute($request)
    {
        if (!$this->getUser()->isAuthenticated()) {
            $this->redirect(['module' => 'user', 'action' => 'login']);

            return;
        }

        $userId = $this->getUser()->getAttribute('user_id');
        $service = new FavoritesService();
        $folderService = new FolderService();

        // Build browse params from request
        $params = [
            'page' => $request->getParameter('page', 1),
            'limit' => $request->getParameter('limit', 25),
            'sort' => $request->getParameter('sort', 'created_at'),
            'sortDir' => $request->getParameter('sortDir', 'desc'),
            'query' => $request->getParameter('query', ''),
        ];

        // Folder filter
        $folderId = $request->getParameter('folder_id');
        $unfiled = $request->getParameter('unfiled');
        if ($folderId) {
            $params['folder_id'] = (int) $folderId;
        } elseif ($unfiled) {
            $params['unfiled'] = true;
        }

        // Browse favourites
        $result = $service->browse($userId, $params);

        // Build pager
        $this->pager = new SimplePager(
            $result['hits'],
            $result['total'],
            $result['page'],
            $result['limit']
        );

        $this->favorites = $result['hits'];
        $this->total = $result['total'];
        $this->count = $result['total'];

        // View mode
        $this->viewMode = $request->getParameter('view', 'table');

        // Current filters for template
        $this->currentQuery = $params['query'];
        $this->currentSort = $params['sort'];
        $this->currentSortDir = $params['sortDir'];
        $this->currentFolderId = $folderId ? (int) $folderId : null;
        $this->currentUnfiled = !empty($unfiled);

        // Folders for sidebar
        $this->folders = $folderService->getUserFolders($userId);
        $this->unfiledCount = $folderService->getUnfiledCount($userId);

        // Current folder details
        $this->currentFolder = null;
        if ($this->currentFolderId) {
            $this->currentFolder = $folderService->getFolder($userId, $this->currentFolderId);
        }
    }
}
