<?php

use AtomFramework\Http\Controllers\AhgController;
/**
 * Show status of a migration job with live progress updates.
 */
class dataMigrationJobStatusAction extends AhgController
{
    public function execute($request)
    {
        if (!$this->getUser()->isAdministrator()) {
            $this->forward('admin', 'secure');
        }

        \AhgCore\Core\AhgDb::init();
        $DB = \Illuminate\Database\Capsule\Manager::class;

        $id = (int)$request->getParameter('id');

        if (!$id) {
            $this->getUser()->setFlash('error', 'Job ID required');
            $this->redirect(['module' => 'dataMigration', 'action' => 'jobs']);
        }

        $this->job = $DB::table('atom_migration_job')
            ->where('id', $id)
            ->first();

        if (!$this->job) {
            $this->getUser()->setFlash('error', 'Job not found');
            $this->redirect(['module' => 'dataMigration', 'action' => 'jobs']);
        }

        // Get mapping name if available
        $this->mappingName = null;
        if ($this->job->mapping_id) {
            $mapping = $DB::table('atom_data_mapping')
                ->where('id', $this->job->mapping_id)
                ->first();
            $this->mappingName = $mapping ? $mapping->name : null;
        }

        // Parse error log
        $this->errors = [];
        if ($this->job->error_log) {
            $this->errors = json_decode($this->job->error_log, true) ?? [];
        }
    }
}
