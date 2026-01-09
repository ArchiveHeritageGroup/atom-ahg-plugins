<?php

class dataMigrationSaveMappingAction extends sfAction
{
    public function execute($request)
    {
        $this->getResponse()->setContentType('application/json');
        
        if (!$this->context->user->isAdministrator()) {
            return $this->renderText(json_encode(['success' => false, 'error' => 'Unauthorized']));
        }
        
        if (!$request->isMethod('post')) {
            return $this->renderText(json_encode(['success' => false, 'error' => 'POST required']));
        }
        
        require_once sfConfig::get('sf_root_dir') . '/atom-framework/bootstrap.php';
        
        $name = trim($request->getParameter('mapping_name', ''));
        $overwrite = $request->getParameter('overwrite', '0') === '1';
        $fields = $request->getParameter('fields', []);
        $targetType = $request->getParameter('target_type', 'archives');
        
        if (empty($name)) {
            return $this->renderText(json_encode(['success' => false, 'error' => 'Mapping name is required']));
        }
        
        // Convert Symfony array decorator to regular array
        if ($fields instanceof sfOutputEscaperArrayDecorator) {
            $fields = $fields->getRawValue();
        }
        
        // Build clean field array
        $cleanFields = [];
        foreach ($fields as $field) {
            // Only save fields that have an atom_field mapping
            $cleanFields[] = [
                'source_field' => $field['source_field'] ?? '',
                'atom_field' => $field['atom_field'] ?? '',
                'constant_value' => $field['constant_value'] ?? '',
                'include' => isset($field['include']) ? true : false,
                'concatenate' => isset($field['concatenate']) ? true : false,
                'concat_constant' => isset($field['concat_constant']) ? true : false,
                'concat_symbol' => $field['concat_symbol'] ?? '|',
            ];
        }
        
        $mappingData = json_encode(['fields' => $cleanFields]);
        
        error_log("SaveMapping: name=$name, fields=" . count($cleanFields) . ", overwrite=$overwrite");
        
        try {
            $DB = \Illuminate\Database\Capsule\Manager::class;
            
            // Check if mapping with this name exists
            $existing = $DB::table('atom_data_mapping')
                ->where('name', $name)
                ->first();
            
            if ($existing) {
                if (!$overwrite) {
                    return $this->renderText(json_encode([
                        'success' => false, 
                        'error' => 'exists',
                        'existing_id' => $existing->id,
                        'message' => 'A mapping with this name already exists. Do you want to overwrite it?'
                    ]));
                }
                
                // Overwrite existing
                $DB::table('atom_data_mapping')
                    ->where('id', $existing->id)
                    ->update([
                        'field_mappings' => $mappingData,
                        'target_type' => $targetType,
                        'updated_at' => date('Y-m-d H:i:s')
                    ]);
                
                return $this->renderText(json_encode([
                    'success' => true, 
                    'id' => $existing->id, 
                    'updated' => true,
                    'field_count' => count($cleanFields)
                ]));
            }
            
            // Insert new
            $id = $DB::table('atom_data_mapping')->insertGetId([
                'name' => $name,
                'target_type' => $targetType,
                'field_mappings' => $mappingData,
                'is_default' => 0,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]);
            
            return $this->renderText(json_encode([
                'success' => true, 
                'id' => $id, 
                'field_count' => count($cleanFields)
            ]));
            
        } catch (\Exception $e) {
            error_log("SaveMapping error: " . $e->getMessage());
            return $this->renderText(json_encode(['success' => false, 'error' => $e->getMessage()]));
        }
    }
}
