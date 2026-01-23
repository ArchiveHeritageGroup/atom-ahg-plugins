<?php

class dataMigrationIndexAction extends sfAction
{
    public function execute($request)
    {
        if (!$this->context->user->isAdministrator()) {
            $this->forward('admin', 'secure');
        }
        
        \AhgCore\Core\AhgDb::init();
        
        $DB = \Illuminate\Database\Capsule\Manager::class;
        
        // Get saved mappings with field count
        $this->savedMappings = [];
        try {
            $mappings = $DB::table('atom_data_mapping')
                ->whereNotNull('field_mappings')
                ->orderBy('name')
                ->get();
            
            foreach ($mappings as $mapping) {
                $fields = json_decode($mapping->field_mappings, true);
                $mapping->field_count = isset($fields['fields']) ? count($fields['fields']) : 0;
                $this->savedMappings[] = $mapping;
            }
        } catch (\Exception $e) {
            // Table might not exist
        }
        
        // Get recent imports
        $this->recentImports = [];
        try {
            $this->recentImports = $DB::table('atom_data_import_log')
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get()
                ->toArray();
        } catch (\Exception $e) {
            // Table might not exist
        }
    }
}
