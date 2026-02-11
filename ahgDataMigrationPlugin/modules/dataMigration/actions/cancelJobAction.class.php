<?php

use AtomFramework\Http\Controllers\AhgController;
/**
 * Cancel a running or pending migration job.
 */
class dataMigrationCancelJobAction extends AhgController
{
    public function execute($request)
    {
        $this->getResponse()->setContentType('application/json');

        if (!$this->getUser()->isAdministrator()) {
            return $this->renderText(json_encode(['success' => false, 'error' => 'Unauthorized']));
        }

        \AhgCore\Core\AhgDb::init();
        $DB = \Illuminate\Database\Capsule\Manager::class;

        $id = (int)$request->getParameter('id');

        if (!$id) {
            return $this->renderText(json_encode(['success' => false, 'error' => 'Job ID required']));
        }

        $job = $DB::table('atom_migration_job')
            ->where('id', $id)
            ->first();

        if (!$job) {
            return $this->renderText(json_encode(['success' => false, 'error' => 'Job not found']));
        }

        if (in_array($job->status, ['completed', 'failed', 'cancelled'])) {
            return $this->renderText(json_encode(['success' => false, 'error' => 'Job already finished']));
        }

        $DB::table('atom_migration_job')
            ->where('id', $id)
            ->update([
                'status' => 'cancelled',
                'completed_at' => date('Y-m-d H:i:s'),
                'progress_message' => 'Cancelled by user'
            ]);

        return $this->renderText(json_encode(['success' => true]));
    }
}
