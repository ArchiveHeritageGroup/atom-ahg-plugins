<?php

/**
 * authResScoreEvidenceTask - Symfony 1.4 task for AtoM Heratio
 *
 * Demo task for Task 4 (Evidence assembly + scoring). Loads a mention (or
 * a single candidate) and runs every evaluator registered for the mention's
 * entity_type. Persists per-dimension signals + data + composite_score
 * back to ahg_mention_candidate and re-ranks rank_position by composite
 * DESC.
 *
 * Runs synchronously. Async dispatch via gearman or a wrapped cron is
 * deferred to a follow-up - Symfony 1.4 has no first-class queue.
 *
 * Usage:
 *   php symfony auth-res:score-evidence <mention_id>
 *   php symfony auth-res:score-evidence <mention_id> --show
 *   php symfony auth-res:score-evidence --candidate-id=12 --show
 *   php symfony auth-res:score-evidence --object-id=901990 --show
 *
 * Copyright (C) 2026 Johan Pieterse
 * Plain Sailing Information Systems
 * Email: johan@plainsailingisystems.co.za
 *
 * Licensed under the GNU General Public License v3.0 or later.
 */

// Explicit requires: Symfony 1.4 has no PSR-4 autoloader for our namespaced
// plugin classes. atom-framework boots Capsule but not our lib/Services/ tree.
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

use AtomFramework\Services\AuthorityResolution\EvidenceScorer;
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
use Illuminate\Database\Capsule\Manager as DB;

class authResScoreEvidenceTask extends arBaseTask
{
    protected function configure()
    {
        $this->addArguments(array(
            new sfCommandArgument('mention_id', sfCommandArgument::OPTIONAL, 'ahg_mention.id whose candidates to score'),
        ));
        $this->addOptions(array(
            new sfCommandOption('application', null, sfCommandOption::PARAMETER_OPTIONAL, 'Application name', 'qubit'),
            new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'Environment', 'cli'),
            new sfCommandOption('connection', null, sfCommandOption::PARAMETER_REQUIRED, 'Connection', 'propel'),
            new sfCommandOption('candidate-id', null, sfCommandOption::PARAMETER_REQUIRED, 'Score a single ahg_mention_candidate row by id'),
            new sfCommandOption('object-id', null, sfCommandOption::PARAMETER_REQUIRED, 'Score every mention belonging to this information_object.id'),
            new sfCommandOption('show', null, sfCommandOption::PARAMETER_NONE, 'Print per-dimension signals + composite + rank deltas'),
        ));

        $this->namespace = 'auth-res';
        $this->name = 'score-evidence';
        $this->briefDescription = 'Score evidence signals + composite for each candidate of a mention. Re-ranks by composite.';
        $this->detailedDescription = <<<EOF
Task 4 of the AHG Authority Resolution Engine. Loads the candidates that
CandidateGeneratorService (Task 3) wrote into ahg_mention_candidate, runs
every evaluator that supports the mention's entity_type against each
candidate, writes evidence_signals + evidence_data JSON + composite_score
+ computed_at, then re-ranks rank_position by composite_score DESC.

PERSON / ORG dimensions: temporal, geographic, relational, role, conflict.
PLACE dimensions:        hierarchical, document_prior, co_occurring,
                         conflict, scale.

Runs synchronously. Async dispatch via gearman or a wrapped cron is
deferred to a follow-up - Symfony 1.4 has no first-class queue.

  php symfony auth-res:score-evidence 138 --show
  php symfony auth-res:score-evidence --candidate-id=13 --show
  php symfony auth-res:score-evidence --object-id=901990 --show
EOF;
    }

    protected function execute($arguments = array(), $options = array())
    {
        parent::execute($arguments, $options);

        $scorer = $this->buildScorer();
        $show = !empty($options['show']);

        if (!empty($options['candidate-id'])) {
            return $this->runCandidateMode((int) $options['candidate-id'], $scorer, $show);
        }
        if (!empty($options['object-id'])) {
            return $this->runObjectMode((int) $options['object-id'], $scorer, $show);
        }
        $mentionId = isset($arguments['mention_id']) ? (int) $arguments['mention_id'] : 0;
        if ($mentionId <= 0) {
            $this->logSection('auth-res', 'Provide mention_id (or --candidate-id, or --object-id).', null, 'ERROR');
            return 1;
        }
        return $this->runMentionMode($mentionId, $scorer, $show);
    }

    private function buildScorer(): EvidenceScorer
    {
        $prior = new DocumentPriorService();
        $evaluators = array(
            // Person/Org
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
        );
        return new EvidenceScorer($evaluators, $prior);
    }

    private function runCandidateMode(int $candidateId, EvidenceScorer $scorer, bool $show): int
    {
        $before = DB::table('ahg_mention_candidate')->where('id', $candidateId)->first();
        if (!$before) {
            $this->logSection('auth-res', sprintf('Candidate #%d not found.', $candidateId), null, 'ERROR');
            return 1;
        }
        $result = $scorer->scoreCandidate($candidateId);
        $this->log(sprintf(
            'Scored candidate #%d (mention #%d, %s, "%s"): composite=%s',
            $candidateId,
            (int) $before->mention_id,
            (string) $before->candidate_source,
            (string) $before->candidate_display_name,
            isset($result['composite_score']) ? (string) $result['composite_score'] : 'n/a'
        ));
        if ($show) {
            $this->log('  signals: ' . json_encode($result['signals'] ?? [], JSON_UNESCAPED_SLASHES));
            $this->log('  data:    ' . json_encode($result['data'] ?? [], JSON_UNESCAPED_SLASHES));
        }
        return 0;
    }

    private function runMentionMode(int $mentionId, EvidenceScorer $scorer, bool $show): int
    {
        $mention = DB::table('ahg_mention as m')
            ->join('ahg_ner_entity as n', 'n.id', '=', 'm.ner_entity_id')
            ->where('m.id', $mentionId)
            ->first(array('m.id', 'm.entity_type', 'm.object_id', 'n.entity_value'));
        if (!$mention) {
            $this->logSection('auth-res', sprintf('Mention #%d not found.', $mentionId), null, 'ERROR');
            return 1;
        }
        $before = DB::table('ahg_mention_candidate')
            ->where('mention_id', $mentionId)
            ->orderBy('rank_position')
            ->get(array('id', 'rank_position', 'candidate_display_name', 'name_similarity_score', 'composite_score'));
        if (count($before) === 0) {
            $this->logSection('auth-res', sprintf('Mention #%d has no candidates. Run auth-res:generate-candidates first.', $mentionId), null, 'ERROR');
            return 1;
        }

        $this->log(sprintf(
            'Mention #%d (%s, "%s", object_id=%d) - %d candidates:',
            (int) $mention->id,
            (string) $mention->entity_type,
            (string) $mention->entity_value,
            (int) $mention->object_id,
            count($before)
        ));

        $beforeMap = array();
        foreach ($before as $row) {
            $beforeMap[(int) $row->id] = $row;
        }

        $scored = $scorer->scoreAllForMention($mentionId);
        $after = DB::table('ahg_mention_candidate')
            ->where('mention_id', $mentionId)
            ->orderBy('rank_position')
            ->get();

        $this->log(sprintf('Scored %d candidates. Post-rerank table:', $scored));
        $this->log(str_repeat('-', 100));
        $this->log(sprintf('%-3s %-3s %-30s %-9s %-9s %s', '#', 'was', 'display_name', 'name_sim', 'composite', 'signals'));
        $this->log(str_repeat('-', 100));
        foreach ($after as $row) {
            $beforeRow = isset($beforeMap[(int) $row->id]) ? $beforeMap[(int) $row->id] : null;
            $wasRank = $beforeRow ? (int) $beforeRow->rank_position : 0;
            $signalsJson = (string) ($row->evidence_signals ?? '{}');
            $this->log(sprintf(
                '%-3d %-3d %-30s %-9s %-9s %s',
                (int) $row->rank_position,
                $wasRank,
                $this->trunc((string) $row->candidate_display_name, 30),
                (string) $row->name_similarity_score,
                (string) $row->composite_score,
                $signalsJson
            ));
            if ($show) {
                $this->log('     data: ' . (string) ($row->evidence_data ?? '{}'));
            }
        }
        $this->log(str_repeat('-', 100));
        return 0;
    }

    private function runObjectMode(int $objectId, EvidenceScorer $scorer, bool $show): int
    {
        $mentionIds = DB::table('ahg_mention')
            ->where('object_id', $objectId)
            ->pluck('id')
            ->all();
        if (empty($mentionIds)) {
            $this->logSection('auth-res', sprintf('No mentions for object_id=%d.', $objectId), null, 'ERROR');
            return 1;
        }
        $rc = 0;
        foreach ($mentionIds as $mid) {
            $sub = $this->runMentionMode((int) $mid, $scorer, $show);
            if ($sub !== 0) {
                $rc = $sub;
            }
        }
        return $rc;
    }

    private function trunc(string $value, int $len): string
    {
        if (function_exists('mb_strlen') && function_exists('mb_substr')) {
            if (mb_strlen($value, 'UTF-8') <= $len) {
                return $value;
            }
            return mb_substr($value, 0, $len - 1, 'UTF-8') . '…';
        }
        return strlen($value) <= $len ? $value : substr($value, 0, $len - 1) . '.';
    }
}
