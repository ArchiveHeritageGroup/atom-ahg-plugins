<?php

class ahgLibraryPluginIsbnProviderToggleAction extends sfAction
{
    public function execute($request)
    {
        if (!$this->context->user->isAdministrator()) {
            $this->forward('admin', 'secure');
        }

        require_once sfConfig::get('sf_root_dir') . '/atom-framework/bootstrap.php';
        
        $id = $request->getParameter('id');
        $provider = \Illuminate\Database\Capsule\Manager::table('atom_isbn_provider')->find($id);
        
        if ($provider) {
            \Illuminate\Database\Capsule\Manager::table('atom_isbn_provider')
                ->where('id', $id)
                ->update(['enabled' => $provider->enabled ? 0 : 1]);
            
            $this->getUser()->setFlash('notice', 'Provider ' . ($provider->enabled ? 'disabled' : 'enabled') . ' successfully.');
        }

        $this->redirect(['module' => 'ahgLibraryPlugin', 'action' => 'isbnProviders']);
    }
}
