<?php

/**
 * Queue a migration import as a background Gearman job.
 * Called from the mapping page when user clicks "Import (Background)"
 */
class dataMigrationQueueJobAction extends sfAction
{
    public function execute($request)
    {
        if (!$this->context->user->isAdministrator()) {
            $this->forward('admin', 'secure');
        }

        if (!$request->isMethod('post')) {
            $this->redirect(['module' => 'dataMigration', 'action' => 'index']);
        }

        // Get session data
        $filepath = $this->getUser()->getAttribute('migration_file');
        $filename = $this->getUser()->getAttribute('migration_filename');
        $detection = $this->getUser()->getAttribute('migration_detection');
        $targetType = $this->getUser()->getAttribute('migration_target_type', 'archives');

        if (!$filepath || !file_exists($filepath)) {
            $this->getUser()->setFlash('error', 'Session expired. Please upload file again.');
            $this->redirect(['module' => 'dataMigration', 'action' => 'index']);
        }

        // Get mapping from POST
        $mapping = $request->getParameter('mapping', []);
        $mappingId = $request->getParameter('saved_mapping_id');
        
        // Get import options
        $options = [
            'repository_id' => $request->getParameter('repository_id'),
            'parent_id' => $request->getParameter('parent_id'),
            'culture' => $request->getParameter('culture', 'en'),
            'update_existing' => $request->getParameter('update_existing', false),
            'match_field' => $request->getParameter('match_field', 'legacyId'),
            'image_path' => $request->getParameter('image_path'),
            'first_row_header' => $this->getUser()->getAttribute('migration_options')['first_row_header'] ?? 1,
            'sheet_index' => $this->getUser()->getAttribute('migration_options')['sheet_index'] ?? 0,
            'delimiter' => $this->getUser()->getAttribute('migration_options')['delimiter'] ?? 'auto',
            'digital_object_path' => $this->getUser()->getAttribute('migration_options')['digital_object_path'] ?? null,
        ];

        // Load framework
        require_once sfConfig::get('sf_root_dir') . '/atom-framework/bootstrap.php';
        $DB = \Illuminate\Database\Capsule\Manager::class;

        try {
            // Build mapping snapshot
            $mappingSnapshot = ['fields' => []];
            if (!empty($mapping)) {
                foreach ($mapping as $fieldConfig) {
                    if (!empty($fieldConfig['include'])) {
                        $mappingSnapshot['fields'][] = $fieldConfig;
                    }
                }
            } elseif ($mappingId) {
                // Load from saved mapping
                $savedMapping = $DB::table('atom_data_mapping')->where('id', $mappingId)->first();
                if ($savedMapping) {
                    $mappingSnapshot = json_decode($savedMapping->field_mappings, true);
                }
            }

            // Get file format
            $ext = strtolower(pathinfo($filepath, PATHINFO_EXTENSION));

            // Create migration job record
            $migrationJobId = $DB::table('atom_migration_job')->insertGetId([
                'name' => $filename,
                'target_type' => $targetType,
                'source_file' => $filepath,
                'source_format' => $ext,
                'mapping_id' => $mappingId,
                'mapping_snapshot' => json_encode($mappingSnapshot),
                'import_options' => json_encode($options),
                'status' => 'pending',
                'total_records' => count($detection['rows'] ?? []),
                'created_by' => $this->context->user->getUserID(),
                'created_at' => date('Y-m-d H:i:s'),
            ]);

            // Create AtoM job record
            $job = new QubitJob();
            $job->name = 'arMigrationImportJob';
            $job->userId = $this->context->user->getUserID();
            $job->statusId = QubitTerm::JOB_STATUS_IN_PROGRESS_ID;
            $job->save();

            // Update migration job with AtoM job ID
            $DB::table('atom_migration_job')
                ->where('id', $migrationJobId)
                ->update(['job_id' => $job->id]);

            // Queue Gearman job
            $jobParams = [
                'id' => $job->id,
                'name' => 'arMigrationImportJob',
                'migrationJobId' => $migrationJobId,
            ];

            arGearman::addJob('arMigrationImportJob', $jobParams);

            // Update status to queued
            $DB::table('atom_migration_job')
                ->where('id', $migrationJobId)
                ->update(['status' => 'pending', 'progress_message' => 'Queued for processing...']);

            $this->getUser()->setFlash('notice', "Import job #{$migrationJobId} queued successfully. Processing in background.");
            
            // Clear session data (file stays for job to process)
            $this->getUser()->setAttribute('migration_file', null);
            $this->getUser()->setAttribute('migration_filename', null);
            $this->getUser()->setAttribute('migration_detection', null);

            // Redirect to job status page
            $this->redirect(['module' => 'dataMigration', 'action' => 'jobStatus', 'id' => $migrationJobId]);

        } catch (\Exception $e) {
            $this->getUser()->setFlash('error', 'Failed to queue job: ' . $e->getMessage());
            $this->redirect(['module' => 'dataMigration', 'action' => 'map']);
        }
    }
}
