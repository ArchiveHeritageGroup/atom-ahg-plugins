<?php

/**
 * AJAX endpoint to get job progress.
 */
class dataMigrationJobProgressAction extends sfAction
{
    public function execute($request)
    {
        $this->getResponse()->setContentType('application/json');

        if (!$this->context->user->isAdministrator()) {
            return $this->renderText(json_encode(['error' => 'Unauthorized']));
        }

        require_once sfConfig::get('sf_root_dir') . '/atom-framework/bootstrap.php';
        $DB = \Illuminate\Database\Capsule\Manager::class;

        $id = (int)$request->getParameter('id');

        if (!$id) {
            return $this->renderText(json_encode(['error' => 'Job ID required']));
        }

        $job = $DB::table('atom_migration_job')
            ->where('id', $id)
            ->first();

        if (!$job) {
            return $this->renderText(json_encode(['error' => 'Job not found']));
        }

        $percent = 0;
        if ($job->total_records > 0) {
            $percent = round(($job->processed_records / $job->total_records) * 100);
        }

        return $this->renderText(json_encode([
            'id' => $job->id,
            'status' => $job->status,
            'total_records' => $job->total_records,
            'processed_records' => $job->processed_records,
            'imported_records' => $job->imported_records,
            'updated_records' => $job->updated_records,
            'skipped_records' => $job->skipped_records,
            'error_count' => $job->error_count,
            'progress_message' => $job->progress_message,
            'percent' => $percent,
            'started_at' => $job->started_at,
            'completed_at' => $job->completed_at,
        ]));
    }
}
