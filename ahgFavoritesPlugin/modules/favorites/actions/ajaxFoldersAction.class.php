<?php

use AtomFramework\Http\Controllers\AhgController;
require_once sfConfig::get('sf_root_dir').'/atom-ahg-plugins/ahgFavoritesPlugin/lib/Services/FolderService.php';

use AtomAhgPlugins\ahgFavoritesPlugin\Services\FolderService;

/**
 * AJAX Get User Folders Action
 *
 * Returns JSON list of user's favorites folders for folder picker dropdowns.
 *
 * @author Johan Pieterse <johan@theahg.co.za>
 */
class favoritesAjaxFoldersAction extends AhgController
{
    public function execute($request)
    {
        $this->getResponse()->setContentType('application/json');

        if (!$this->getUser()->isAuthenticated()) {
            return $this->renderText(json_encode(['success' => false, 'folders' => []]));
        }

        $userId = (int) $this->getUser()->getAttribute('user_id');
        $service = new FolderService();
        $folders = $service->getUserFolders($userId);

        return $this->renderText(json_encode(['success' => true, 'folders' => $folders]));
    }
}
