<?php

/**
 * RiC Queue Process Task
 *
 * Processes pending sync items by running the RiC extractor for each fonds/record.
 * Logs results to ric_sync_log.
 *
 * Usage:
 *   php symfony ric:queue-process                # Sync all fonds
 *   php symfony ric:queue-process --limit=50     # Limit to 50 records
 *   php symfony ric:queue-process --clear        # Clear triplestore first
 *   php symfony ric:queue-process --fonds=776    # Sync specific fonds
 *   php symfony ric:queue-process --status       # Show triplestore status
 *
 * @package    ahgRicExplorerPlugin
 */
class ricQueueProcessTask extends arBaseTask
{
    protected function configure()
    {
        $this->addOptions([
            new sfCommandOption('application', null, sfCommandOption::PARAMETER_OPTIONAL, 'The application name', 'qubit'),
            new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'The environment', 'cli'),
            new sfCommandOption('limit', null, sfCommandOption::PARAMETER_OPTIONAL, 'Max records to process', null),
            new sfCommandOption('clear', null, sfCommandOption::PARAMETER_NONE, 'Clear triplestore before sync'),
            new sfCommandOption('fonds', null, sfCommandOption::PARAMETER_OPTIONAL, 'Specific fonds IDs (comma-separated)', null),
            new sfCommandOption('status', null, sfCommandOption::PARAMETER_NONE, 'Show triplestore status only'),
            new sfCommandOption('validate', null, sfCommandOption::PARAMETER_NONE, 'Run SHACL validation after sync'),
            new sfCommandOption('backup', null, sfCommandOption::PARAMETER_NONE, 'Create backup before sync'),
        ]);

        $this->namespace = 'ric';
        $this->name = 'queue-process';
        $this->briefDescription = 'Sync AtoM records to Fuseki RiC triplestore';
        $this->detailedDescription = <<<'EOF'
Processes AtoM records and syncs them to the Fuseki triplestore as RiC-O linked data.

Uses the Python RiC extractor (ric_extractor_v5.py) to generate JSON-LD from
AtoM's database and loads it into Fuseki.

Results are logged to the ric_sync_log table for the dashboard.
EOF;
    }

    protected function execute($arguments = [], $options = [])
    {
        parent::execute($arguments, $options);

        $pluginDir = sfConfig::get('sf_root_dir') . '/atom-ahg-plugins/ahgRicExplorerPlugin';
        $syncScript = $pluginDir . '/bin/ric_sync.sh';

        if (!file_exists($syncScript)) {
            $this->logSection('ric', 'ERROR: ric_sync.sh not found at ' . $syncScript, null, 'ERROR');
            return 1;
        }

        // Bootstrap Laravel DB for logging
        $bootstrapFile = sfConfig::get('sf_root_dir') . '/atom-framework/bootstrap.php';
        if (file_exists($bootstrapFile)) {
            require_once $bootstrapFile;
        }

        $DB = \Illuminate\Database\Capsule\Manager::class;
        $startTime = microtime(true);

        // Build command arguments
        $args = [];
        if ($options['status']) {
            $args[] = '--status';
        }
        if ($options['clear']) {
            $args[] = '--clear';
        }
        if ($options['backup']) {
            $args[] = '--backup';
        }
        if ($options['validate']) {
            $args[] = '--validate';
        }
        if (!empty($options['fonds'])) {
            $args[] = '--fonds ' . escapeshellarg($options['fonds']);
        }

        $cmd = 'bash ' . escapeshellarg($syncScript) . ' ' . implode(' ', $args) . ' 2>&1';

        $this->logSection('ric', 'Starting RiC sync...');
        $this->logSection('ric', 'Command: ' . $cmd);

        // Execute sync
        $output = [];
        $returnCode = 0;
        exec($cmd, $output, $returnCode);

        $duration = round((microtime(true) - $startTime) * 1000);

        // Output results
        $processedCount = 0;
        $skippedCount = 0;
        $tripleCount = 0;

        foreach ($output as $line) {
            $this->log($line);

            // Parse output for stats
            if (preg_match('/Processed:\s*(\d+)/', $line, $m)) {
                $processedCount = (int) $m[1];
            }
            if (preg_match('/Skipped:\s*(\d+)/', $line, $m)) {
                $skippedCount = (int) $m[1];
            }
            if (preg_match('/Total triples:\s*(\d+)/', $line, $m)) {
                $tripleCount = (int) $m[1];
            }
        }

        // Log to ric_sync_log
        $status = $returnCode === 0 ? 'success' : 'failure';
        $details = json_encode([
            'processed' => $processedCount,
            'skipped' => $skippedCount,
            'triples' => $tripleCount,
            'duration_ms' => $duration,
            'clear' => $options['clear'] ?? false,
            'fonds' => $options['fonds'] ?? null,
            'limit' => $options['limit'] ?? null,
        ]);

        try {
            $DB::table('ric_sync_log')->insert([
                'operation' => 'sync',
                'entity_type' => 'batch',
                'entity_id' => null,
                'status' => $status,
                'details' => $details,
                'execution_time_ms' => $duration,
                'triggered_by' => 'cli',
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        } catch (\Exception $e) {
            $this->logSection('ric', 'Warning: Could not log to ric_sync_log: ' . $e->getMessage());
        }

        $this->logSection('ric', sprintf(
            'Sync %s: %d processed, %d skipped, %d triples (%dms)',
            $status,
            $processedCount,
            $skippedCount,
            $tripleCount,
            $duration
        ));

        return $returnCode;
    }
}
