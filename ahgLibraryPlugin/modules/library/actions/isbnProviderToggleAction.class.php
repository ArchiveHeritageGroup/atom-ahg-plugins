<?php

use AtomFramework\Http\Controllers\AhgController;
class libraryIsbnProviderToggleAction extends AhgController
{
    public function execute($request)
    {
        if (!$this->getUser()->isAdministrator()) {
            $this->forward('admin', 'secure');
        }

        \AhgCore\Core\AhgDb::init();
        
        $id = $request->getParameter('id');
        $provider = \Illuminate\Database\Capsule\Manager::table('atom_isbn_provider')->find($id);
        
        if ($provider) {
            \Illuminate\Database\Capsule\Manager::table('atom_isbn_provider')
                ->where('id', $id)
                ->update(['enabled' => $provider->enabled ? 0 : 1]);
            
            $this->getUser()->setFlash('notice', 'Provider ' . ($provider->enabled ? 'disabled' : 'enabled') . ' successfully.');
        }

        $this->redirect(['module' => 'library', 'action' => 'isbnProviders']);
    }
}
