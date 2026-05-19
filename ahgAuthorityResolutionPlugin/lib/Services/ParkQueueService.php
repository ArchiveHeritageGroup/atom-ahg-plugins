<?php

/**
 * ParkQueueService - service for AtoM Heratio
 *
 * Task 7 of the AHG Authority Resolution Engine. Owns the parked-mention
 * queue: listing parked rows with filters, re-running candidate generation
 * + evidence scoring after a context shift (un-park), and the background
 * sweep that flags parked mentions whose candidate set has changed since
 * parking.
 *
 * Pure Capsule (no Laravel app helpers). Re-uses CandidateGeneratorService
 * + EvidenceScorer so the same scoring lineage applies on un-park as on
 * the initial review.
 *
 * Copyright (C) 2026 Johan Pieterse
 * Plain Sailing Information Systems
 * Email: johan@plainsailingisystems.co.za
 *
 * This file is part of the AHG Authority Resolution Engine plugin for
 * AtoM Heratio. Licensed under the GNU General Public License v3.0 or later,
 * matching the parent atom-ahg-plugins repository.
 */

namespace AtomFramework\Services\AuthorityResolution;

use Illuminate\Database\Capsule\Manager as DB;

require_once dirname(__FILE__) . '/Adapters/CandidateAdapterInterface.php';
require_once dirname(__FILE__) . '/Adapters/MysqlActorAdapter.php';
require_once dirname(__FILE__) . '/Adapters/MysqlTermAdapter.php';
require_once dirname(__FILE__) . '/Adapters/FusekiAgentAdapter.php';
require_once dirname(__FILE__) . '/Adapters/FusekiPlaceAdapter.php';
require_once dirname(__FILE__) . '/CandidateGeneratorService.php';
require_once dirname(__FILE__) . '/Evidence/EvidenceSignal.php';
require_once dirname(__FILE__) . '/Evidence/EvaluatorInterface.php';
require_once dirname(__FILE__) . '/Evidence/TemporalEvaluator.php';
require_once dirname(__FILE__) . '/Evidence/GeographicEvaluator.php';
require_once dirname(__FILE__) . '/Evidence/RelationalEvaluator.php';
require_once dirname(__FILE__) . '/Evidence/RoleEvaluator.php';
require_once dirname(__FILE__) . '/Evidence/ConflictEvaluator.php';
require_once dirname(__FILE__) . '/Evidence/DocumentPriorService.php';
require_once dirname(__FILE__) . '/Evidence/HierarchicalEvaluator.php';
require_once dirname(__FILE__) . '/Evidence/PriorEvaluator.php';
require_once dirname(__FILE__) . '/Evidence/CoOccurringPersonEvaluator.php';
require_once dirname(__FILE__) . '/Evidence/PlaceConflictEvaluator.php';
require_once dirname(__FILE__) . '/Evidence/ScaleEvaluator.php';
require_once dirname(__FILE__) . '/EvidenceScorer.php';

class ParkQueueService
{
    /**
     * List parked mentions joined with their NER entity + context for the
     * Task 7 dedicated park screen.
     *
     * Filters (all optional):
     *   $userId             ahg_mention_park.parked_by_user_id
     *   $entityType         ahg_mention.entity_type (PERSON / ORG / GPE / LOC / PLACE)
     *   $newCandidateOnly   true => only rows where new_candidate_available = 1
     *   $sinceParkedDate    'YYYY-MM-DD' string; row's parked_at >= midnight of that date
     *   $limit              page-size cap; default 50, clamped to [1,500]
     *
     * @return array<int, object>
     */
    public function listFor(
        ?int $userId,
        ?string $entityType,
        ?bool $newCandidateOnly,
        ?string $sinceParkedDate,
        int $limit = 50
    ): array {
        $limit = max(1, min(500, $limit));

        $q = DB::table('ahg_mention_park as p')
            ->join('ahg_mention as m', 'm.id', '=', 'p.mention_id')
            ->join('ahg_ner_entity as n', 'n.id', '=', 'm.ner_entity_id')
            ->leftJoin('ahg_mention_context as ctx', 'ctx.mention_id', '=', 'm.id')
            ->leftJoin('information_object_i18n as ioi', function ($j) {
                $j->on('ioi.id', '=', 'm.object_id')->where('ioi.culture', '=', 'en');
            })
            ->leftJoin('slug as s', 's.object_id', '=', 'm.object_id')
            ->leftJoin('user as u', 'u.id', '=', 'p.parked_by_user_id')
            ->select(
                'p.id as park_id',
                'p.mention_id',
                'p.parked_by_user_id',
                'p.parked_at',
                'p.reason',
                'p.new_candidate_available',
                'p.new_candidate_check_at',
                'm.entity_type',
                'm.state',
                'm.object_id',
                'n.entity_value',
                'ctx.surrounding_text_before',
                'ctx.surrounding_text_after',
                'ioi.title as io_title',
                's.slug as io_slug',
                'u.username as parked_by_username'
            );

        if ($userId !== null && $userId > 0) {
            $q->where('p.parked_by_user_id', '=', $userId);
        }
        if ($entityType !== null && $entityType !== '') {
            $q->where('m.entity_type', '=', $entityType);
        }
        if ($newCandidateOnly === true) {
            $q->where('p.new_candidate_available', '=', 1);
        }
        if ($sinceParkedDate !== null && $sinceParkedDate !== '') {
            $q->where('p.parked_at', '>=', $sinceParkedDate . ' 00:00:00');
        }

        $rows = $q->orderByRaw('p.new_candidate_available DESC, p.parked_at DESC')
            ->limit($limit)
            ->get();

        $out = [];
        foreach ($rows as $r) {
            $out[] = $r;
        }
        return $out;
    }

    /**
     * Un-park a mention: delete the ahg_mention_park row, flip the mention
     * back to 'pending', regenerate candidates, re-score evidence. Returns:
     *   ['ok'=>bool, 'mention_id'=>int, 'candidate_ids'=>int[], 'scored'=>int,
     *    'error'=>?string]
     */
    public function unparkAndRereview(int $mentionId, int $userId): array
    {
        $mention = DB::table('ahg_mention')->where('id', $mentionId)->first();
        if (!$mention) {
            return [
                'ok' => false,
                'mention_id' => $mentionId,
                'candidate_ids' => [],
                'scored' => 0,
                'error' => "mention #{$mentionId} not found",
            ];
        }

        $parkRow = DB::table('ahg_mention_park')->where('mention_id', $mentionId)->first();
        if (!$parkRow) {
            return [
                'ok' => false,
                'mention_id' => $mentionId,
                'candidate_ids' => [],
                'scored' => 0,
                'error' => "mention #{$mentionId} is not parked",
            ];
        }

        $now = date('Y-m-d H:i:s');

        DB::table('ahg_mention_park')->where('mention_id', $mentionId)->delete();
        DB::table('ahg_mention')
            ->where('id', $mentionId)
            ->update(['state' => 'pending', 'updated_at' => $now]);

        $candidateIds = [];
        $scored = 0;
        try {
            $generator = new CandidateGeneratorService($this->buildAdapters());
            $candidateIds = $generator->generate($mentionId);
        } catch (\Throwable $e) {
            return [
                'ok' => false,
                'mention_id' => $mentionId,
                'candidate_ids' => [],
                'scored' => 0,
                'error' => 'candidate generation failed: ' . $e->getMessage(),
            ];
        }

        try {
            $scorer = new EvidenceScorer(
                $this->buildEvaluators(),
                new Evidence\DocumentPriorService()
            );
            $scored = $scorer->scoreAllForMention($mentionId);
        } catch (\Throwable $e) {
            // Candidate generation succeeded; scoring failure is reported but
            // doesn't poison the un-park (mention is back in pending).
            return [
                'ok' => true,
                'mention_id' => $mentionId,
                'candidate_ids' => $candidateIds,
                'scored' => 0,
                'error' => 'scoring failed: ' . $e->getMessage(),
            ];
        }

        return [
            'ok' => true,
            'mention_id' => $mentionId,
            'candidate_ids' => $candidateIds,
            'scored' => $scored,
            'error' => null,
        ];
    }

    /**
     * Background sweep. For every parked mention, dry-run candidate
     * generation and compare to the stored set. If different, flag
     * new_candidate_available = 1 + stamp new_candidate_check_at.
     *
     * Returns the count of rows newly flagged.
     */
    public function scanForNewCandidates(): int
    {
        $parked = DB::table('ahg_mention_park as p')
            ->join('ahg_mention as m', 'm.id', '=', 'p.mention_id')
            ->join('ahg_ner_entity as n', 'n.id', '=', 'm.ner_entity_id')
            ->select(
                'p.id as park_id',
                'p.mention_id',
                'p.new_candidate_available',
                'm.entity_type',
                'n.entity_value'
            )
            ->get();

        if (count($parked) === 0) {
            return 0;
        }

        $adapters = $this->buildAdapters();
        $now = date('Y-m-d H:i:s');
        $flagged = 0;

        foreach ($parked as $row) {
            $existing = $this->loadExistingCandidateSet((int) $row->mention_id);
            $fresh = $this->dryRunCandidateSet(
                $adapters,
                (string) $row->entity_value,
                (string) $row->entity_type
            );

            $changed = $this->candidateSetsDiffer($existing, $fresh);

            $update = ['new_candidate_check_at' => $now];
            if ($changed) {
                $update['new_candidate_available'] = 1;
                if ((int) $row->new_candidate_available === 0) {
                    $flagged++;
                }
            }
            // Note: we don't clear the flag on equality - once raised, the
            // archivist decides whether to act. The flag clears when the
            // mention is un-parked (row deleted).

            DB::table('ahg_mention_park')->where('id', $row->park_id)->update($update);
        }

        return $flagged;
    }

    /**
     * Map of parked count per archivist user (id => count). Used by the
     * dashboard JSON endpoint.
     *
     * @return array<int, int>
     */
    public function dashboardByUser(): array
    {
        $rows = DB::table('ahg_mention_park')
            ->select('parked_by_user_id', DB::raw('COUNT(*) as c'))
            ->groupBy('parked_by_user_id')
            ->get();
        $out = [];
        foreach ($rows as $r) {
            $out[(int) $r->parked_by_user_id] = (int) $r->c;
        }
        return $out;
    }

    /**
     * @return \AtomFramework\Services\AuthorityResolution\Adapters\CandidateAdapterInterface[]
     */
    private function buildAdapters(): array
    {
        return [
            new Adapters\MysqlActorAdapter(),
            new Adapters\MysqlTermAdapter(),
            new Adapters\FusekiAgentAdapter(),
            new Adapters\FusekiPlaceAdapter(),
        ];
    }

    /**
     * @return \AtomFramework\Services\AuthorityResolution\Evidence\EvaluatorInterface[]
     */
    private function buildEvaluators(): array
    {
        return [
            new Evidence\TemporalEvaluator(),
            new Evidence\GeographicEvaluator(),
            new Evidence\RelationalEvaluator(),
            new Evidence\RoleEvaluator(),
            new Evidence\ConflictEvaluator(),
            new Evidence\HierarchicalEvaluator(),
            new Evidence\PriorEvaluator(new Evidence\DocumentPriorService()),
            new Evidence\CoOccurringPersonEvaluator(),
            new Evidence\PlaceConflictEvaluator(),
            new Evidence\ScaleEvaluator(),
        ];
    }

    /**
     * @return string[] normalised candidate keys
     */
    private function loadExistingCandidateSet(int $mentionId): array
    {
        $rows = DB::table('ahg_mention_candidate')
            ->where('mention_id', $mentionId)
            ->get(['candidate_source', 'candidate_authority_id', 'candidate_fuseki_uri', 'candidate_display_name']);
        $keys = [];
        foreach ($rows as $r) {
            $keys[] = $this->candidateKey(
                (string) $r->candidate_source,
                $r->candidate_authority_id !== null ? (int) $r->candidate_authority_id : null,
                $r->candidate_fuseki_uri !== null ? (string) $r->candidate_fuseki_uri : null,
                (string) $r->candidate_display_name
            );
        }
        sort($keys);
        return $keys;
    }

    /**
     * Run adapters without persisting. Returns a sorted, normalised key list
     * comparable byte-for-byte to loadExistingCandidateSet output.
     *
     * @param \AtomFramework\Services\AuthorityResolution\Adapters\CandidateAdapterInterface[] $adapters
     * @return string[]
     */
    private function dryRunCandidateSet(array $adapters, string $entityValue, string $entityType): array
    {
        $entityValue = trim($entityValue);
        if ($entityValue === '') {
            return [];
        }

        $keys = [];
        foreach ($adapters as $adapter) {
            if (!$adapter->supports($entityType)) {
                continue;
            }
            $rows = $adapter->search($entityValue, $entityType, 50);
            foreach ($rows as $c) {
                $keys[] = $this->candidateKey(
                    (string) ($c['source'] ?? '?'),
                    isset($c['authority_id']) && $c['authority_id'] !== null ? (int) $c['authority_id'] : null,
                    isset($c['fuseki_uri']) && $c['fuseki_uri'] !== null ? (string) $c['fuseki_uri'] : null,
                    (string) ($c['display_name'] ?? '')
                );
            }
        }
        $keys = array_values(array_unique($keys));
        sort($keys);
        return $keys;
    }

    private function candidateKey(string $source, ?int $authorityId, ?string $fusekiUri, string $displayName): string
    {
        return $source . '|' . ($authorityId !== null ? (string) $authorityId : '') . '|' . ($fusekiUri ?? '') . '|' . $displayName;
    }

    /**
     * @param string[] $a
     * @param string[] $b
     */
    private function candidateSetsDiffer(array $a, array $b): bool
    {
        if (count($a) !== count($b)) {
            return true;
        }
        for ($i = 0, $n = count($a); $i < $n; $i++) {
            if ($a[$i] !== $b[$i]) {
                return true;
            }
        }
        return false;
    }
}
