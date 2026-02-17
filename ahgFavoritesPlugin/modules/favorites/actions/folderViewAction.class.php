<?php

use AtomFramework\Http\Controllers\AhgController;

/**
 * View Folder Action - redirects to browse with folder filter
 *
 * @author Johan Pieterse <johan@theahg.co.za>
 */
class favoritesFolderViewAction extends AhgController
{
    public function execute($request)
    {
        if (!$this->getUser()->isAuthenticated()) {
            $this->redirect(['module' => 'user', 'action' => 'login']);

            return;
        }

        $folderId = (int) $request->getParameter('id');

        $this->redirect('/favorites?folder_id='.$folderId);
    }
}
