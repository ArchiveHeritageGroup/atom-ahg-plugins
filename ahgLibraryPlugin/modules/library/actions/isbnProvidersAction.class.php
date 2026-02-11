<?php

use AtomFramework\Http\Controllers\AhgController;
/**
 * ISBN Providers Admin Action
 */
class libraryIsbnProvidersAction extends AhgController
{
    public function execute($request)
    {
        // Check admin permission
        if (!$this->getUser()->isAdministrator()) {
            $this->forward('admin', 'secure');
        }

        \AhgCore\Core\AhgDb::init();

        $this->providers = \Illuminate\Database\Capsule\Manager::table('atom_isbn_provider')
            ->orderBy('priority')
            ->get();
    }
}
