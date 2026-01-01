<?php

/**
 * ISBN Providers Admin Action
 */
class ahgLibraryPluginIsbnProvidersAction extends sfAction
{
    public function execute($request)
    {
        // Check admin permission
        if (!$this->context->user->isAdministrator()) {
            $this->forward('admin', 'secure');
        }

        require_once sfConfig::get('sf_root_dir') . '/atom-framework/bootstrap.php';

        $this->providers = \Illuminate\Database\Capsule\Manager::table('atom_isbn_provider')
            ->orderBy('priority')
            ->get();
    }
}
