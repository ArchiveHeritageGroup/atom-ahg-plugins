<?php

/**
 * Background Gearman job for AI batch processing.
 *
 * Processes individual items from AI batch queues.
 *
 * @author Johan Pieterse <johan@theahg.co.za>
 */
class arAiBatchJob extends arBaseJob
{
    protected $extraRequiredParameters = ['jobId'];

    public function runJob($parameters)
    {
        $jobId = $parameters['jobId'];
        $batchId = $parameters['batchId'] ?? null;

        $this->info("Processing AI batch job: {$jobId}");

        // Bootstrap Laravel
        \AhgCore\Core\AhgDb::init();

        try {
            // Get service and process
            $service = new \ahgAIPlugin\Services\JobQueueService();

            // Check server load before processing
            if (!$service->checkServerLoad()) {
                $this->info("Server under high load, delaying job");
                sleep(10);
            }

            $result = $service->processJob($jobId);

            if ($result['success']) {
                $this->info("Job completed successfully");
                return true;
            } else {
                $this->error("Job failed: " . ($result['error'] ?? 'Unknown error'));
                return false;
            }

        } catch (\Exception $e) {
            $this->error("Exception: " . $e->getMessage());

            // Log the error
            try {
                \Illuminate\Database\Capsule\Manager::table('ahg_ai_job')
                    ->where('id', $jobId)
                    ->update([
                        'status' => 'failed',
                        'error_message' => $e->getMessage(),
                        'completed_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s'),
                    ]);

                if ($batchId) {
                    $service = new \ahgAIPlugin\Services\JobQueueService();
                    $service->updateBatchProgress($batchId, true);
                }
            } catch (\Exception $e2) {
                // Ignore logging errors
            }

            return false;
        }
    }
}
