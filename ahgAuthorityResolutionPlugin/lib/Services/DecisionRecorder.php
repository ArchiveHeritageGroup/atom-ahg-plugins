<?php

/**
 * DecisionRecorder - service for AtoM Heratio
 *
 * Single entry point for committing an archivist decision in the authority
 * resolution workflow. Wraps:
 *   - ahg_mention_decision row insert (with frozen evidence + candidates snapshots)
 *   - ahg_mention.state transition
 *   - ahg_ner_entity.linked_actor_id back-update for link / link_different
 *   - ahg_mention_park row insert for park
 *   - synchronous DecisionProvenanceWriter::write() call (no queue on AtoM)
 *
 * Reused by every authorityResolution action handler so the state machine,
 * snapshot shape, and provenance handoff stay identical across decision types.
 *
 * Mirror of the Laravel-side DecisionRecorder; both codebases produce the
 * same audit row shape so federation queries can union across them.
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

require_once dirname(__FILE__) . '/FusekiUpdateService.php';
require_once dirname(__FILE__) . '/DecisionProvenanceWriter.php';
require_once dirname(__FILE__) . '/NerFeedbackService.php';

class DecisionRecorder
{
    public const DECISION_LINK = 'link';
    public const DECISION_LINK_DIFFERENT = 'link_different';
    public const DECISION_CREATE_NEW = 'create_new';
    public const DECISION_PARK = 'park';
    public const DECISION_REJECT = 'reject';

    private const STATE_FOR = [
        self::DECISION_LINK => 'linked',
        self::DECISION_LINK_DIFFERENT => 'linked',
        self::DECISION_CREATE_NEW => 'new_record_created',
        self::DECISION_PARK => 'parked',
        self::DECISION_REJECT => 'rejected',
    ];

    /** @var DecisionProvenanceWriter|null */
    private $provenance;

    public function __construct(?DecisionProvenanceWriter $provenance = null)
    {
        $this->provenance = $provenance;
    }

    /**
     * Record a decision and fire provenance. Returns:
     *   ['ok'=>bool, 'decision_id'=>int, 'state'=>string, 'provenance'=>array|null, 'error'=>?string]
     *
     * @param int    $mentionId         ahg_mention.id
     * @param string $decisionType      one of DECISION_*
     * @param int    $archivistUserId   user.id (acting user)
     * @param array  $opts              {
     *     candidate_id?: int|null,        // ahg_mention_candidate.id (link / link_different / create_new)
     *     authority_id?: int|null,        // actor.id or term.id; can be null for park/reject
     *     reason?: string|null,           // park reason
     * }
     */
    public function record(int $mentionId, string $decisionType, int $archivistUserId, array $opts = []): array
    {
        if (!isset(self::STATE_FOR[$decisionType])) {
            return ['ok' => false, 'error' => "unknown decision_type: {$decisionType}"];
        }

        $mention = DB::table('ahg_mention')->where('id', $mentionId)->first();
        if (!$mention) {
            return ['ok' => false, 'error' => "mention #{$mentionId} not found"];
        }

        $candidateId = isset($opts['candidate_id']) ? (int) $opts['candidate_id'] : null;
        $authorityId = isset($opts['authority_id']) ? (int) $opts['authority_id'] : null;
        $reason = isset($opts['reason']) ? (string) $opts['reason'] : null;

        // Pull the chosen candidate row for snapshot + authority_id default.
        $chosen = null;
        if ($candidateId) {
            $chosen = DB::table('ahg_mention_candidate')
                ->where('id', $candidateId)
                ->where('mention_id', $mentionId)
                ->first();
            if ($chosen && $authorityId === null) {
                $authorityId = $chosen->candidate_authority_id !== null
                    ? (int) $chosen->candidate_authority_id
                    : null;
            }
        }

        // Freeze the candidate slate as seen at decision time.
        $candidatesAtDecision = DB::table('ahg_mention_candidate')
            ->where('mention_id', $mentionId)
            ->orderBy('rank_position', 'asc')
            ->get([
                'id',
                'rank_position',
                'candidate_source',
                'candidate_authority_id',
                'candidate_fuseki_uri',
                'candidate_display_name',
                'name_similarity_score',
                'composite_score',
                'evidence_signals',
                'evidence_data',
            ]);

        $candidatesSnapshot = [];
        $topScore = null;
        foreach ($candidatesAtDecision as $c) {
            $candidatesSnapshot[] = [
                'candidate_id' => (int) $c->id,
                'rank' => (int) $c->rank_position,
                'source' => $c->candidate_source,
                'display_name' => $c->candidate_display_name,
                'name_similarity_score' => $c->name_similarity_score !== null ? (float) $c->name_similarity_score : null,
                'composite_score' => $c->composite_score !== null ? (float) $c->composite_score : null,
            ];
            if ((int) $c->rank_position === 1 && $c->composite_score !== null) {
                $topScore = (float) $c->composite_score;
            }
        }

        // Evidence snapshot = the chosen candidate's signals + data (when applicable).
        $evidenceSnapshot = null;
        if ($chosen !== null) {
            $signals = $this->decodeJson($chosen->evidence_signals ?? null);
            $data = $this->decodeJson($chosen->evidence_data ?? null);
            if ($signals !== null || $data !== null) {
                $evidenceSnapshot = [
                    'candidate_id' => (int) $chosen->id,
                    'evidence_signals' => $signals,
                    'evidence_data' => $data,
                    'composite_score' => $chosen->composite_score !== null ? (float) $chosen->composite_score : null,
                ];
            }
        }

        // Task 9: persist rejection reason on the decision row so the NER
        // feedback service can recover it later. We embed it in evidence_snapshot
        // (the table has no dedicated reason column) under a non-conflicting key.
        if ($decisionType === self::DECISION_REJECT && $reason !== null && $reason !== '') {
            $evidenceSnapshot = is_array($evidenceSnapshot) ? $evidenceSnapshot : [];
            $evidenceSnapshot['rejection_reason'] = $reason;
        }

        $now = date('Y-m-d H:i:s');

        $decisionId = DB::table('ahg_mention_decision')->insertGetId([
            'mention_id' => $mentionId,
            'decision_type' => $decisionType,
            'chosen_candidate_id' => $candidateId,
            'chosen_authority_id' => $authorityId,
            'original_system_top_score' => $topScore,
            'archivist_user_id' => $archivistUserId,
            'decided_at' => $now,
            'evidence_snapshot' => $evidenceSnapshot !== null
                ? json_encode($evidenceSnapshot, JSON_UNESCAPED_UNICODE)
                : null,
            'candidates_visible_snapshot' => !empty($candidatesSnapshot)
                ? json_encode($candidatesSnapshot, JSON_UNESCAPED_UNICODE)
                : null,
        ]);

        // Update mention state.
        DB::table('ahg_mention')
            ->where('id', $mentionId)
            ->update([
                'state' => self::STATE_FOR[$decisionType],
                'updated_at' => $now,
            ]);

        // For link / link_different, back-update the consumer contract on ahg_ner_entity.
        if (in_array($decisionType, [self::DECISION_LINK, self::DECISION_LINK_DIFFERENT], true) && $authorityId !== null) {
            DB::table('ahg_ner_entity')
                ->where('id', $mention->ner_entity_id)
                ->update([
                    'linked_actor_id' => $authorityId,
                    'status' => 'linked',
                    'reviewed_by' => $archivistUserId,
                    'reviewed_at' => $now,
                ]);
        }

        // For park, insert / replace the ahg_mention_park row.
        if ($decisionType === self::DECISION_PARK) {
            DB::table('ahg_mention_park')
                ->where('mention_id', $mentionId)
                ->delete();
            DB::table('ahg_mention_park')->insert([
                'mention_id' => $mentionId,
                'parked_by_user_id' => $archivistUserId,
                'parked_at' => $now,
                'reason' => $reason ?? '',
                'new_candidate_available' => 0,
            ]);
        }

        // Fire provenance synchronously. Failure is non-fatal: decision row is
        // already committed and the auth-res:write-provenance task can retry.
        $provenance = null;
        try {
            $writer = $this->provenance ?: new DecisionProvenanceWriter(new FusekiUpdateService());
            $provenance = $writer->write((int) $decisionId);
        } catch (\Throwable $e) {
            $provenance = ['ok' => false, 'error' => 'writer threw: ' . $e->getMessage()];
        }

        // Task 9: capture NER feedback on reject decisions. Failure here must
        // not break the decision (already committed); we report it through
        // a non-fatal channel only.
        $feedbackId = null;
        $feedbackError = null;
        if ($decisionType === self::DECISION_REJECT) {
            try {
                $feedbackId = (new NerFeedbackService())->captureFromRejection((int) $decisionId);
            } catch (\Throwable $e) {
                $feedbackError = 'feedback capture failed: ' . $e->getMessage();
            }
        }

        return [
            'ok' => true,
            'decision_id' => (int) $decisionId,
            'state' => self::STATE_FOR[$decisionType],
            'provenance' => $provenance,
            'feedback_id' => $feedbackId,
            'feedback_error' => $feedbackError,
            'error' => null,
        ];
    }

    private function decodeJson(?string $json): ?array
    {
        if ($json === null || trim($json) === '') {
            return null;
        }
        $decoded = json_decode($json, true);
        return is_array($decoded) ? $decoded : null;
    }
}
