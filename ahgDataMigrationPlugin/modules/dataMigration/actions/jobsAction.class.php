<?php

/**
 * List all migration jobs.
 */
class dataMigrationJobsAction extends sfAction
{
    public function execute($request)
    {
        if (!$this->context->user->isAdministrator()) {
            $this->forward('admin', 'secure');
        }

        \AhgCore\Core\AhgDb::init();
        $DB = \Illuminate\Database\Capsule\Manager::class;

        $this->jobs = $DB::table('atom_migration_job')
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get();

        // Get mapping names
        $mappingIds = array_filter(array_column($this->jobs->toArray(), 'mapping_id'));
        $this->mappings = [];
        if (!empty($mappingIds)) {
            $mappings = $DB::table('atom_data_mapping')
                ->whereIn('id', $mappingIds)
                ->pluck('name', 'id');
            $this->mappings = $mappings->toArray();
        }
    }
}
