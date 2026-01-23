<?php

class dataMigrationDeleteMappingAction extends sfAction
{
    public function execute($request)
    {
        $this->getResponse()->setContentType('application/json');
        
        if (!$this->context->user->isAdministrator()) {
            return $this->renderText(json_encode(['success' => false, 'error' => 'Unauthorized']));
        }
        
        \AhgCore\Core\AhgDb::init();
        
        $id = (int)$request->getParameter('id');
        
        if (!$id) {
            return $this->renderText(json_encode(['success' => false, 'error' => 'Missing ID']));
        }
        
        try {
            $DB = \Illuminate\Database\Capsule\Manager::class;
            
            $deleted = $DB::table('atom_data_mapping')
                ->where('id', $id)
                ->delete();
            
            return $this->renderText(json_encode(['success' => true, 'deleted' => $deleted]));
            
        } catch (\Exception $e) {
            return $this->renderText(json_encode(['success' => false, 'error' => $e->getMessage()]));
        }
    }
}
