<?php

/**
 * aiProvenanceReplayTask - retry deferred Fuseki writes for AI provenance.
 *
 * Port of the Heratio `ahg:provenance-ai:replay` command to the AtoM-AHG side -
 * issue #140, Phase 4. ADR-0002 dual-store: an inference / override row whose
 * synchronous Fuseki write failed at record() time persists with a NULL graph
 * URI; this task picks those up and retries the SPARQL INSERT.
 *
 * Copyright (C) 2026 Johan Pieterse, Plain Sailing Information Systems
 * The Archive and Heritage Group (Pty) Ltd. Licensed GPL-3.0-or-later.
 */

require_once dirname(__FILE__) . '/../Service/InferenceRecord.php';
require_once dirname(__FILE__) . '/../Service/InferenceSigner.php';
require_once dirname(__FILE__) . '/../Service/InferenceService.php';
require_once dirname(__FILE__) . '/../Service/OverrideService.php';

use AhgProvenancePlugin\Service\InferenceService;
use AhgProvenancePlugin\Service\OverrideService;
use Illuminate\Database\Capsule\Manager as Capsule;

/**
 * Replay queued AI provenance Fuseki writes (rows with NULL graph URIs).
 *
 *     php symfony ai-provenance:replay              retry up to 200 of each
 *     php symfony ai-provenance:replay --batch=500
 *     php symfony ai-provenance:replay --dry-run    count pending, write nothing
 *
 * Idempotent: each row's graph URI is derived from its uuid, so an INSERT DATA
 * that already landed in Fuseki is harmless on retry. Recommended cadence:
 * every 5 minutes via cron.
 */
class aiProvenanceReplayTask extends sfBaseTask
{
    protected function configure()
    {
        $this->addOptions([
            new sfCommandOption('application', null, sfCommandOption::PARAMETER_OPTIONAL, 'The application name', 'qubit'),
            new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'The environment', 'cli'),
            new sfCommandOption('batch', null, sfCommandOption::PARAMETER_OPTIONAL, 'Max rows to attempt per pass', 200),
            new sfCommandOption('dry-run', null, sfCommandOption::PARAMETER_NONE, 'Count pending without writing to Fuseki'),
        ]);

        $this->namespace = 'ai-provenance';
        $this->name = 'replay';
        $this->briefDescription = 'Replay queued AI inference + override Fuseki writes';
        $this->detailedDescription = <<<EOD
The [ai-provenance:replay|INFO] task retries the Fuseki RDF-Star / PROV-O writes
for ahg_ai_inference and ahg_ai_override rows whose synchronous write was
deferred (NULL graph URI) - issue #140.

  [php symfony ai-provenance:replay|INFO]
  [php symfony ai-provenance:replay --batch=500|INFO]
  [php symfony ai-provenance:replay --dry-run|INFO]

Idempotent - safe to run on a cron schedule (every 5 minutes recommended).
EOD;
    }

    protected function execute($arguments = [], $options = [])
    {
        sfContext::createInstance($this->configuration);
        \AhgCore\Core\AhgDb::init();

        $client = InferenceService::fusekiClient();
        if ($client === null) {
            $this->logSection('ai-provenance', 'FusekiUpdateService unavailable (ahgAuthorityResolutionPlugin) - cannot replay.', null, 'ERROR');

            return 1;
        }

        $batch  = max(1, (int) ($options['batch'] ?: 200));
        $dryRun = !empty($options['dry-run']);

        $infOk = 0;
        $infFail = 0;
        $ovOk = 0;
        $ovFail = 0;

        // ── Pass 1: inference rows ───────────────────────────────────────────
        $inferenceSvc = new InferenceService();
        $pending = Capsule::table('ahg_ai_inference')
            ->whereNull('fuseki_graph_uri')
            ->orderBy('id')
            ->limit($batch)
            ->get();
        $this->logSection('ai-provenance', sprintf('inference pending=%d (batch=%d)', count($pending), $batch));

        foreach ($pending as $row) {
            if ($dryRun) {
                $infOk++;
                continue;
            }
            try {
                $built  = $inferenceSvc->buildInferenceSparql($row);
                $result = $client->executeUpdate($built['sparql']);
                if (!empty($result['ok'])) {
                    Capsule::table('ahg_ai_inference')->where('id', $row->id)
                        ->update(['fuseki_graph_uri' => $built['graph']]);
                    $infOk++;
                } else {
                    $infFail++;
                    $this->logSection('ai-provenance', sprintf('inference id %s still failing: %s', $row->id, $result['error'] ?? ''), null, 'ERROR');
                }
            } catch (\Throwable $e) {
                $infFail++;
                $this->logSection('ai-provenance', sprintf('inference id %s threw: %s', $row->id, $e->getMessage()), null, 'ERROR');
            }
        }

        // ── Pass 2: override rows ────────────────────────────────────────────
        try {
            $overrideSvc = new OverrideService();
            $pending = Capsule::table('ahg_ai_override')
                ->whereNull('fuseki_override_uri')
                ->orderBy('id')
                ->limit($batch)
                ->get();
            $this->logSection('ai-provenance', sprintf('override pending=%d', count($pending)));

            foreach ($pending as $row) {
                if ($dryRun) {
                    $ovOk++;
                    continue;
                }
                try {
                    $built  = $overrideSvc->buildOverrideSparql($row);
                    $result = $client->executeUpdate($built['sparql']);
                    if (!empty($result['ok'])) {
                        Capsule::table('ahg_ai_override')->where('id', $row->id)
                            ->update(['fuseki_override_uri' => $built['graph']]);
                        $ovOk++;
                    } else {
                        $ovFail++;
                        $this->logSection('ai-provenance', sprintf('override id %s still failing: %s', $row->id, $result['error'] ?? ''), null, 'ERROR');
                    }
                } catch (\Throwable $e) {
                    $ovFail++;
                    $this->logSection('ai-provenance', sprintf('override id %s threw: %s', $row->id, $e->getMessage()), null, 'ERROR');
                }
            }
        } catch (\Throwable $e) {
            $this->logSection('ai-provenance', 'override pass skipped: ' . $e->getMessage(), null, 'ERROR');
        }

        $this->logSection('ai-provenance', sprintf(
            '%s inference: replayed=%d failed=%d | override: replayed=%d failed=%d',
            $dryRun ? 'DRY-RUN' : 'live',
            $infOk, $infFail, $ovOk, $ovFail
        ));

        return ($infFail > 0 || $ovFail > 0) ? 1 : 0;
    }
}
