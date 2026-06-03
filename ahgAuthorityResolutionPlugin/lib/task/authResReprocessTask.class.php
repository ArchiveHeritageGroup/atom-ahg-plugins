<?php

/**
 * authResReprocessTask - Symfony 1.4 task for AtoM Heratio
 *
 * Task 10 (CLI consolidation) reprocess driver. Re-runs the Task 3 candidate
 * generator AND the Task 4 evidence scorer for a single mention, or for
 * every pending mention. Useful after:
 *
 *   - an authority store grows (new actors / new RiC agents in Fuseki),
 *   - the candidate-generation similarity algorithm is tuned,
 *   - the evaluator chain is extended.
 *
 * Replaces the historical "run generate-candidates THEN score-evidence by
 * hand" two-step. Wires the same adapter + evaluator chain that
 * ParkQueueService::unparkAndRereview uses so all three reprocess paths
 * (CLI single, CLI bulk, un-park) score identically.
 *
 * Usage:
 *   php symfony auth-res:reprocess --mention-id=138
 *   php symfony auth-res:reprocess --all-pending
 *   php symfony auth-res:reprocess --all-pending --limit=100
 *
 * Copyright (C) 2026 Johan Pieterse
 * Plain Sailing Information Systems
 * Email: johan@plainsailingisystems.co.za
 *
 * Licensed under the GNU General Public License v3.0 or later.
 */

// Explicit requires: Symfony 1.4 has no PSR-4 autoloader for our namespaced
// plugin classes. Mirror authResScoreEvidenceTask: pull in every evaluator
// + adapter the chain transitively touches.
require_once __DIR__ . '/../Services/Adapters/CandidateAdapterInterface.php';
require_once __DIR__ . '/../Services/Adapters/MysqlActorAdapter.php';
require_once __DIR__ . '/../Services/Adapters/MysqlTermAdapter.php';
require_once __DIR__ . '/../Services/Adapters/FusekiAgentAdapter.php';
require_once __DIR__ . '/../Services/Adapters/FusekiPlaceAdapter.php';
require_once __DIR__ . '/../Services/CandidateGeneratorService.php';
require_once __DIR__ . '/../Services/Evidence/EvidenceSignal.php';
require_once __DIR__ . '/../Services/Evidence/EvaluatorInterface.php';
require_once __DIR__ . '/../Services/Evidence/TemporalEvaluator.php';
require_once __DIR__ . '/../Services/Evidence/GeographicEvaluator.php';
require_once __DIR__ . '/../Services/Evidence/RelationalEvaluator.php';
require_once __DIR__ . '/../Services/Evidence/RoleEvaluator.php';
require_once __DIR__ . '/../Services/Evidence/ConflictEvaluator.php';
require_once __DIR__ . '/../Services/Evidence/DocumentPriorService.php';
require_once __DIR__ . '/../Services/Evidence/HierarchicalEvaluator.php';
require_once __DIR__ . '/../Services/Evidence/PriorEvaluator.php';
require_once __DIR__ . '/../Services/Evidence/CoOccurringPersonEvaluator.php';
require_once __DIR__ . '/../Services/Evidence/PlaceConflictEvaluator.php';
require_once __DIR__ . '/../Services/Evidence/ScaleEvaluator.php';
require_once __DIR__ . '/../Services/EvidenceScorer.php';

use AtomFramework\Services\AuthorityResolution\Adapters\FusekiAgentAdapter;
use AtomFramework\Services\AuthorityResolution\Adapters\FusekiPlaceAdapter;
use AtomFramework\Services\AuthorityResolution\Adapters\MysqlActorAdapter;
use AtomFramework\Services\AuthorityResolution\Adapters\MysqlTermAdapter;
use AtomFramework\Services\AuthorityResolution\CandidateGeneratorService;
use AtomFramework\Services\AuthorityResolution\Evidence\CoOccurringPersonEvaluator;
use AtomFramework\Services\AuthorityResolution\Evidence\ConflictEvaluator;
use AtomFramework\Services\AuthorityResolution\Evidence\DocumentPriorService;
use AtomFramework\Services\AuthorityResolution\Evidence\GeographicEvaluator;
use AtomFramework\Services\AuthorityResolution\Evidence\HierarchicalEvaluator;
use AtomFramework\Services\AuthorityResolution\Evidence\PlaceConflictEvaluator;
use AtomFramework\Services\AuthorityResolution\Evidence\PriorEvaluator;
use AtomFramework\Services\AuthorityResolution\Evidence\RelationalEvaluator;
use AtomFramework\Services\AuthorityResolution\Evidence\RoleEvaluator;
use AtomFramework\Services\AuthorityResolution\Evidence\ScaleEvaluator;
use AtomFramework\Services\AuthorityResolution\Evidence\TemporalEvaluator;
use AtomFramework\Services\AuthorityResolution\EvidenceScorer;
use Illuminate\Database\Capsule\Manager as DB;

class authResReprocessTask extends arBaseTask
{
    protected function configure()
    {
        $this->addOptions([
            new sfCommandOption('application', null, sfCommandOption::PARAMETER_OPTIONAL, 'Application name', 'qubit'),
            new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'Environment', 'cli'),
            new sfCommandOption('connection', null, sfCommandOption::PARAMETER_REQUIRED, 'Connection', 'propel'),
            new sfCommandOption('mention-id', null, sfCommandOption::PARAMETER_REQUIRED, 'Single ahg_mention.id to reprocess'),
            new sfCommandOption('all-pending', null, sfCommandOption::PARAMETER_NONE, 'Reprocess every mention whose state = pending'),
            new sfCommandOption('limit', null, sfCommandOption::PARAMETER_REQUIRED, 'Cap on --all-pending sweep (default 0 = no cap)', '0'),
        ]);

        $this->namespace = 'auth-res';
        $this->name = 'reprocess';
        $this->briefDescription = 'Re-run candidate generation + evidence scoring for a mention (or every pending mention).';
        $this->detailedDescription = <<<EOF
Task 10 of the AHG Authority Resolution Engine. Re-runs:

   CandidateGeneratorService::generate(mention_id)    (Task 3)
   EvidenceScorer::scoreAllForMention(mention_id)     (Task 4)

for a single mention (--mention-id) or for every mention in state =
pending (--all-pending). Output is the count of mentions regenerated and
the total candidates+scores produced.

Both modes use the same adapter + evaluator chain as
ParkQueueService::unparkAndRereview, so a re-park-rereview, an unpark,
and an explicit reprocess all converge on the same candidate set.

Usage:
  php symfony auth-res:reprocess --mention-id=138
  php symfony auth-res:reprocess --all-pending
  php symfony auth-res:reprocess --all-pending --limit=100
EOF;
    }

    public function execute($arguments = [], $options = [])
    {
        parent::execute($arguments, $options);

        $mentionId = !empty($options['mention-id']) ? (int) $options['mention-id'] : 0;
        $allPending = !empty($options['all-pending']);
        $limit = isset($options['limit']) ? max(0, (int) $options['limit']) : 0;

        if ($mentionId <= 0 && !$allPending) {
            $this->logSection('auth-res', 'Provide --mention-id=X or --all-pending.', null, 'ERROR');
            return 1;
        }
        if ($mentionId > 0 && $allPending) {
            $this->logSection('auth-res', 'Pass --mention-id OR --all-pending, not both.', null, 'ERROR');
            return 1;
        }

        $generator = new CandidateGeneratorService($this->buildAdapters());
        $scorer = new EvidenceScorer($this->buildEvaluators(), new DocumentPriorService());

        if ($mentionId > 0) {
            return $this->runOne($mentionId, $generator, $scorer);
        }
        return $this->runAllPending($generator, $scorer, $limit);
    }

    private function runOne(int $mentionId, CandidateGeneratorService $generator, EvidenceScorer $scorer): int
    {
        $mention = DB::table('ahg_mention')->where('id', $mentionId)->first();
        if (!$mention) {
            $this->logSection('auth-res', sprintf('Mention #%d not found.', $mentionId), null, 'ERROR');
            return 1;
        }

        try {
            $candidateIds = $generator->generate($mentionId);
        } catch (\Throwable $e) {
            $this->logSection('auth-res', 'Candidate generation failed: ' . $e->getMessage(), null, 'ERROR');
            return 2;
        }

        try {
            $scored = $scorer->scoreAllForMention($mentionId);
        } catch (\Throwable $e) {
            $this->logSection('auth-res', sprintf(
                'Generated %d candidate(s) but scoring failed: %s',
                count($candidateIds),
                $e->getMessage()
            ), null, 'ERROR');
            return 3;
        }

        $this->log(sprintf(
            'Reprocessed 1 mention (#%d). Candidates generated: %d. Candidates scored: %d.',
            $mentionId,
            count($candidateIds),
            $scored
        ));
        return 0;
    }

    private function runAllPending(CandidateGeneratorService $generator, EvidenceScorer $scorer, int $limit): int
    {
        $q = DB::table('ahg_mention')
            ->where('state', '=', 'pending')
            ->orderBy('id');
        if ($limit > 0) {
            $q->limit($limit);
        }
        $ids = $q->pluck('id')->all();

        if (empty($ids)) {
            $this->log('No pending mentions to reprocess.');
            return 0;
        }

        $this->log(sprintf('Reprocessing %d pending mention(s)%s...', count($ids), $limit > 0 ? " (limit={$limit})" : ''));

        $reprocessed = 0;
        $candidatesTotal = 0;
        $scoredTotal = 0;
        $failed = 0;

        foreach ($ids as $rawId) {
            $mid = (int) $rawId;
            try {
                $candidateIds = $generator->generate($mid);
                $scored = $scorer->scoreAllForMention($mid);
                $candidatesTotal += count($candidateIds);
                $scoredTotal += $scored;
                $reprocessed++;
            } catch (\Throwable $e) {
                $failed++;
                $this->logSection('auth-res', sprintf('Mention #%d failed: %s', $mid, $e->getMessage()), null, 'ERROR');
            }
        }

        $this->log(sprintf(
            'Done. Reprocessed: %d. Failed: %d. Candidates generated total: %d. Candidates scored total: %d.',
            $reprocessed,
            $failed,
            $candidatesTotal,
            $scoredTotal
        ));
        return $failed === 0 ? 0 : 0; // partial failure surfaces per-mention but task itself is not a failure.
    }

    /**
     * @return \AtomFramework\Services\AuthorityResolution\Adapters\CandidateAdapterInterface[]
     */
    private function buildAdapters(): array
    {
        return [
            new MysqlActorAdapter(),
            new MysqlTermAdapter(),
            new FusekiAgentAdapter(),
            new FusekiPlaceAdapter(),
        ];
    }

    /**
     * @return \AtomFramework\Services\AuthorityResolution\Evidence\EvaluatorInterface[]
     */
    private function buildEvaluators(): array
    {
        $prior = new DocumentPriorService();
        return [
            // Person / Org
            new TemporalEvaluator(),
            new GeographicEvaluator(),
            new RelationalEvaluator(),
            new RoleEvaluator(),
            new ConflictEvaluator(),
            // Place
            new HierarchicalEvaluator(),
            new PriorEvaluator($prior),
            new CoOccurringPersonEvaluator(),
            new PlaceConflictEvaluator(),
            new ScaleEvaluator(),
        ];
    }
}
