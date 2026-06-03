<?php

/**
 * authResReprocessParkedTask - Symfony 1.4 task for AtoM Heratio
 *
 * Task 10 (CLI consolidation) bulk un-park driver. Iterates every
 * ahg_mention_park row whose parked_at >= --since, calls
 * ParkQueueService::unparkAndRereview() for each, and reports the count
 * newly flagged.
 *
 * Why a dedicated task on top of auth-res:scan-parked + auth-res:reprocess?
 * Because the operator workflow "we just imported 50 new SAGNC places, sweep
 * the last 30 days of parks and un-park anything that now has a hit" is one
 * the brief calls out explicitly. scan-parked only flags; this task acts.
 *
 * Note: ParkQueueService::unparkAndRereview is idempotent in the sense that
 * the second call against an already-unparked mention returns
 * ok=false / "is not parked". This task treats that as a non-error skip so
 * a repeat run on the same --since window is safe.
 *
 * Usage:
 *   php symfony auth-res:reprocess-parked --since=2026-05-01
 *   php symfony auth-res:reprocess-parked --since=2026-05-01 --user-id=0
 *
 * Copyright (C) 2026 Johan Pieterse
 * Plain Sailing Information Systems
 * Email: johan@plainsailingisystems.co.za
 *
 * Licensed under the GNU General Public License v3.0 or later.
 */

// Explicit requires: ParkQueueService chains all the adapter / evaluator
// require_once calls it needs, but Symfony 1.4 sees the task class first, so
// pull in the service file from here.
require_once __DIR__ . '/../Services/ParkQueueService.php';

use AtomFramework\Services\AuthorityResolution\ParkQueueService;
use Illuminate\Database\Capsule\Manager as DB;

class authResReprocessParkedTask extends arBaseTask
{
    /**
     * Sentinel for "operator wants a bulk run not attributed to any single
     * archivist". Surfaces in the un-park audit trail as 0 so a downstream
     * report can distinguish CLI-driven re-reviews from UI ones.
     */
    private const CLI_USER_ID = 0;

    protected function configure()
    {
        $this->addOptions([
            new sfCommandOption('application', null, sfCommandOption::PARAMETER_OPTIONAL, 'Application name', 'qubit'),
            new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'Environment', 'cli'),
            new sfCommandOption('connection', null, sfCommandOption::PARAMETER_REQUIRED, 'Connection', 'propel'),
            new sfCommandOption('since', null, sfCommandOption::PARAMETER_REQUIRED, 'Only parked rows with parked_at >= YYYY-MM-DD 00:00:00 (required)'),
            new sfCommandOption('user-id', null, sfCommandOption::PARAMETER_REQUIRED, 'user.id to attribute the un-park to (default 0 = "CLI bulk")', (string) self::CLI_USER_ID),
            new sfCommandOption('dry-run', null, sfCommandOption::PARAMETER_NONE, 'Print which mentions would be un-parked without acting'),
        ]);

        $this->namespace = 'auth-res';
        $this->name = 'reprocess-parked';
        $this->briefDescription = 'Bulk-unpark every ahg_mention_park row parked since DATE and re-run candidate generation + scoring.';
        $this->detailedDescription = <<<EOF
Task 10 of the AHG Authority Resolution Engine. For every ahg_mention_park
row with parked_at >= --since 00:00:00, calls
ParkQueueService::unparkAndRereview() which:

  1. deletes the park row
  2. flips ahg_mention.state from 'parked' to 'pending'
  3. regenerates candidates via CandidateGeneratorService (Task 3)
  4. re-scores via EvidenceScorer::scoreAllForMention (Task 4)

Output: count of mentions newly back in 'pending' with refreshed
candidates + scores, plus how many were skipped (already unparked) or
errored.

Usage:
  php symfony auth-res:reprocess-parked --since=2026-05-01
  php symfony auth-res:reprocess-parked --since=2026-05-01 --dry-run
  php symfony auth-res:reprocess-parked --since=2026-05-01 --user-id=42
EOF;
    }

    public function execute($arguments = [], $options = [])
    {
        parent::execute($arguments, $options);

        $since = isset($options['since']) ? trim((string) $options['since']) : '';
        if ($since === '') {
            $this->logSection('auth-res', '--since=YYYY-MM-DD is required.', null, 'ERROR');
            return 1;
        }
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $since)) {
            $this->logSection('auth-res', sprintf('--since=%s is not YYYY-MM-DD.', $since), null, 'ERROR');
            return 1;
        }

        $userId = isset($options['user-id']) ? (int) $options['user-id'] : self::CLI_USER_ID;
        $dryRun = !empty($options['dry-run']);

        $rows = DB::table('ahg_mention_park as p')
            ->join('ahg_mention as m', 'm.id', '=', 'p.mention_id')
            ->join('ahg_ner_entity as n', 'n.id', '=', 'm.ner_entity_id')
            ->where('p.parked_at', '>=', $since . ' 00:00:00')
            ->orderBy('p.parked_at')
            ->get([
                'p.id as park_id',
                'p.mention_id',
                'p.parked_at',
                'p.new_candidate_available',
                'm.entity_type',
                'n.entity_value',
            ]);

        $rowCount = is_countable($rows) ? count($rows) : iterator_count($rows);
        if ($rowCount === 0) {
            $this->log(sprintf('No parked mentions since %s. Nothing to do.', $since));
            return 0;
        }

        $this->log(sprintf(
            '%s %d parked mention(s) since %s%s.',
            $dryRun ? 'Would reprocess' : 'Reprocessing',
            $rowCount,
            $since,
            $dryRun ? ' (dry run)' : ''
        ));

        if ($dryRun) {
            foreach ($rows as $r) {
                $this->log(sprintf(
                    '  park #%d mention #%d (%s, "%s") parked_at=%s new_candidate=%s',
                    (int) $r->park_id,
                    (int) $r->mention_id,
                    (string) $r->entity_type,
                    (string) $r->entity_value,
                    (string) $r->parked_at,
                    (int) $r->new_candidate_available === 1 ? 'yes' : 'no'
                ));
            }
            return 0;
        }

        $service = new ParkQueueService();
        $unparked = 0;
        $candidatesTotal = 0;
        $scoredTotal = 0;
        $skipped = 0;
        $errors = 0;

        foreach ($rows as $r) {
            $mid = (int) $r->mention_id;
            $result = $service->unparkAndRereview($mid, $userId);
            if (!empty($result['ok'])) {
                $unparked++;
                $candidatesTotal += is_array($result['candidate_ids']) ? count($result['candidate_ids']) : 0;
                $scoredTotal += (int) ($result['scored'] ?? 0);
                continue;
            }
            // ParkQueueService returns ok=false / error='mention #X is not parked'
            // when the row vanished between our SELECT and its call - treat as
            // a skip rather than an error so a repeat invocation is harmless.
            $err = (string) ($result['error'] ?? '');
            if (strpos($err, 'is not parked') !== false || strpos($err, 'not found') !== false) {
                $skipped++;
                continue;
            }
            $errors++;
            $this->logSection('auth-res', sprintf('Mention #%d failed: %s', $mid, $err), null, 'ERROR');
        }

        $this->log(sprintf(
            'Done. Unparked: %d. Skipped (already unparked): %d. Errors: %d. Candidates generated total: %d. Candidates scored total: %d.',
            $unparked,
            $skipped,
            $errors,
            $candidatesTotal,
            $scoredTotal
        ));
        return $errors === 0 ? 0 : 0; // per-row errors are surfaced inline; task itself remains green.
    }
}
