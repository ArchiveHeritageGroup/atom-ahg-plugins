<?php

use AtomFramework\Http\Controllers\AhgController;
class dataMigrationRenameMappingAction extends AhgController
{
    public function execute($request)
    {
        $this->getResponse()->setContentType('application/json');
        
        if (!$this->getUser()->isAdministrator()) {
            return $this->renderText(json_encode(['success' => false, 'error' => 'Unauthorized']));
        }
        
        \AhgCore\Core\AhgDb::init();
        
        $id = (int)$request->getParameter('id');
        $name = trim($request->getParameter('name', ''));
        
        if (!$id || !$name) {
            return $this->renderText(json_encode(['success' => false, 'error' => 'Missing ID or name']));
        }
        
        try {
            $DB = \Illuminate\Database\Capsule\Manager::class;
            
            $updated = $DB::table('atom_data_mapping')
                ->where('id', $id)
                ->update(['name' => $name, 'updated_at' => date('Y-m-d H:i:s')]);
            
            return $this->renderText(json_encode(['success' => true, 'updated' => $updated]));
            
        } catch (\Exception $e) {
            return $this->renderText(json_encode(['success' => false, 'error' => $e->getMessage()]));
        }
    }
}
