<?php

class dataMigrationLoadMappingAction extends sfAction
{
    public function execute($request)
    {
        $this->getResponse()->setContentType('application/json');
        
        if (!$this->context->user->isAdministrator()) {
            return $this->renderText(json_encode(['success' => false, 'error' => 'Unauthorized']));
        }
        
        require_once sfConfig::get('sf_root_dir') . '/atom-framework/bootstrap.php';
        
        $id = (int)$request->getParameter('id');
        
        if (!$id) {
            return $this->renderText(json_encode(['success' => false, 'error' => 'Missing mapping ID']));
        }
        
        try {
            $DB = \Illuminate\Database\Capsule\Manager::class;
            
            $mapping = $DB::table('atom_data_mapping')
                ->where('id', $id)
                ->first();
            
            if (!$mapping) {
                return $this->renderText(json_encode(['success' => false, 'error' => 'Mapping not found']));
            }
            
            $data = json_decode($mapping->field_mappings, true);
            
            if (!$data) {
                return $this->renderText(json_encode([
                    'success' => false, 
                    'error' => 'Invalid JSON in mapping',
                    'raw' => substr($mapping->field_mappings, 0, 200)
                ]));
            }
            
            // Return the mapping data
            return $this->renderText(json_encode([
                'success' => true,
                'id' => $mapping->id,
                'name' => $mapping->name,
                'mapping' => $data,
                'field_count' => isset($data['fields']) ? count($data['fields']) : 0
            ]));
            
        } catch (\Exception $e) {
            return $this->renderText(json_encode(['success' => false, 'error' => $e->getMessage()]));
        }
    }
}
