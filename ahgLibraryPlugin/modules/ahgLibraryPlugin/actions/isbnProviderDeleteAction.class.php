<?php

class ahgLibraryPluginIsbnProviderDeleteAction extends sfAction
{
    public function execute($request)
    {
        if (!$this->context->user->isAdministrator()) {
            $this->forward('admin', 'secure');
        }

        require_once sfConfig::get('sf_root_dir') . '/atom-framework/bootstrap.php';
        
        $id = $request->getParameter('id');
        $provider = \Illuminate\Database\Capsule\Manager::table('atom_isbn_provider')->find($id);
        
        // Don't allow deleting core providers
        if ($provider && !in_array($provider->slug, ['openlibrary', 'googlebooks', 'worldcat'])) {
            \Illuminate\Database\Capsule\Manager::table('atom_isbn_provider')
                ->where('id', $id)
                ->delete();
            
            $this->getUser()->setFlash('notice', 'Provider deleted successfully.');
        } else {
            $this->getUser()->setFlash('error', 'Cannot delete core providers.');
        }

        $this->redirect(['module' => 'ahgLibraryPlugin', 'action' => 'isbnProviders']);
    }
}
