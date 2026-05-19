<?php

/**
 * NerFeedbackService - service for AtoM Heratio
 *
 * Task 9 of the AHG Authority Resolution Engine. When an archivist rejects
 * a mention via DecisionRecorder::record() with decision_type='reject',
 * the rejected span + rejection_reason is a training negative for the
 * upstream NER model: the model proposed an entity, the human said no.
 *
 * captureFromRejection() reads the decision row + its mention + ner_entity
 * + context and inserts a row into ahg_ner_feedback.
 *
 * exportUnexported() writes accumulated rows out as JSONL or CoNLL for
 * the retraining pipeline, then flips training_exported=1 + exported_at.
 *
 * Pure Capsule. No Laravel app helpers. Storage path falls back to /tmp
 * when uploads dir is not writable (AtoM doesn't ship Laravel's
 * storage/app convention).
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

class NerFeedbackService
{
    private const PRIMARY_DIR = '/usr/share/nginx/archive/uploads/auth-res/ner-feedback';
    private const FALLBACK_DIR = '/tmp/ahg-auth-res-ner-feedback';

    private const SOURCE_TEXT_FIELDS = [
        'title',
        'scope_and_content',
        'extent_and_medium',
        'arrangement',
        'archival_history',
        'acquisition',
        'physical_characteristics',
    ];

    /**
     * Capture a feedback row from a freshly-recorded reject decision.
     * Returns the new ahg_ner_feedback.id, or null if the decision could
     * not be reconstructed (e.g. caller passed a non-reject decision_id).
     */
    public function captureFromRejection(int $decisionId): ?int
    {
        $decision = DB::table('ahg_mention_decision')->where('id', $decisionId)->first();
        if (!$decision || (string) $decision->decision_type !== 'reject') {
            return null;
        }

        $mention = DB::table('ahg_mention')->where('id', $decision->mention_id)->first();
        if (!$mention) {
            return null;
        }

        $nerEntity = DB::table('ahg_ner_entity')->where('id', $mention->ner_entity_id)->first();
        if (!$nerEntity) {
            return null;
        }

        $context = DB::table('ahg_mention_context')->where('mention_id', $mention->id)->first();

        $sourceText = $this->fetchSourceText((int) $mention->object_id);

        $reason = $this->extractRejectionReason($decision);
        $modelVersion = $context && !empty($context->ner_model_version)
            ? (string) $context->ner_model_version
            : null;

        $now = date('Y-m-d H:i:s');

        $id = DB::table('ahg_ner_feedback')->insertGetId([
            'mention_id' => (int) $mention->id,
            'ner_entity_id' => (int) $mention->ner_entity_id,
            'decision_id' => (int) $decisionId,
            'source_text' => $sourceText,
            'mention_value' => (string) $nerEntity->entity_value,
            'mention_entity_type' => (string) $mention->entity_type,
            'mention_offset_start' => $context ? $this->intOrNull($context->character_offset_start) : null,
            'mention_offset_end' => $context ? $this->intOrNull($context->character_offset_end) : null,
            'rejection_reason' => $reason,
            'archivist_user_id' => (int) $decision->archivist_user_id,
            'ner_model_version' => $modelVersion,
            'training_exported' => 0,
            'exported_at' => null,
            'created_at' => $now,
        ]);

        return (int) $id;
    }

    /**
     * Export every ahg_ner_feedback row with training_exported=0 to a
     * dated file under PRIMARY_DIR (falling back to FALLBACK_DIR when
     * uploads is read-only). Format jsonl|conll.
     *
     * Returns ['ok'=>bool, 'path'=>string, 'count'=>int, 'format'=>string,
     *          'error'=>?string].
     */
    public function exportUnexported(string $format = 'jsonl'): array
    {
        $format = strtolower($format) === 'conll' ? 'conll' : 'jsonl';

        $rows = DB::table('ahg_ner_feedback')
            ->where('training_exported', 0)
            ->orderBy('id')
            ->get();
        if (count($rows) === 0) {
            return [
                'ok' => true,
                'path' => '',
                'count' => 0,
                'format' => $format,
                'error' => null,
            ];
        }

        $dir = $this->resolveExportDir();
        if ($dir === null) {
            return [
                'ok' => false,
                'path' => '',
                'count' => 0,
                'format' => $format,
                'error' => 'no writable export directory (uploads + /tmp both rejected)',
            ];
        }

        $filename = sprintf('ner-feedback-%s.%s', date('Ymd-His'), $format);
        $path = rtrim($dir, '/') . '/' . $filename;

        $fh = @fopen($path, 'wb');
        if (!$fh) {
            return [
                'ok' => false,
                'path' => $path,
                'count' => 0,
                'format' => $format,
                'error' => 'fopen failed',
            ];
        }

        $exportedIds = [];
        foreach ($rows as $r) {
            $line = $format === 'conll'
                ? $this->renderConllRow($r)
                : $this->renderJsonlRow($r);
            if ($line === null) {
                continue;
            }
            fwrite($fh, $line);
            if (substr($line, -1) !== "\n") {
                fwrite($fh, "\n");
            }
            $exportedIds[] = (int) $r->id;
        }
        fclose($fh);

        if (!empty($exportedIds)) {
            $now = date('Y-m-d H:i:s');
            DB::table('ahg_ner_feedback')
                ->whereIn('id', $exportedIds)
                ->update(['training_exported' => 1, 'exported_at' => $now]);
        }

        return [
            'ok' => true,
            'path' => $path,
            'count' => count($exportedIds),
            'format' => $format,
            'error' => null,
        ];
    }

    private function renderJsonlRow($row): ?string
    {
        $start = $this->intOrNull($row->mention_offset_start);
        $end = $this->intOrNull($row->mention_offset_end);
        $span = [
            'type' => (string) $row->mention_entity_type,
            'value' => (string) $row->mention_value,
            'rejection_reason' => (string) $row->rejection_reason,
            'archivist_user_id' => (int) $row->archivist_user_id,
            'ner_model_version' => $row->ner_model_version !== null ? (string) $row->ner_model_version : null,
        ];
        if ($start !== null) {
            $span['start'] = $start;
        }
        if ($end !== null) {
            $span['end'] = $end;
        }
        $payload = [
            'feedback_id' => (int) $row->id,
            'mention_id' => (int) $row->mention_id,
            'decision_id' => (int) $row->decision_id,
            'text' => (string) $row->source_text,
            'spans' => [$span],
        ];
        return json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /**
     * CoNLL-2003 style: one token per line, columns "token TAG". Rejected
     * span gets B-<TYPE> for first token, I-<TYPE> for the rest. Outside
     * tokens are tagged O. Blank line ends each example.
     */
    private function renderConllRow($row): ?string
    {
        $text = (string) $row->source_text;
        if (trim($text) === '') {
            return null;
        }

        $type = strtoupper((string) $row->mention_entity_type);
        $value = (string) $row->mention_value;
        $start = $this->intOrNull($row->mention_offset_start);
        $end = $this->intOrNull($row->mention_offset_end);

        // Tokenise on whitespace, keep simple offsets.
        $tokens = preg_split('/(\s+)/u', $text, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
        $out = "# feedback_id=" . (int) $row->id . " decision_id=" . (int) $row->decision_id
            . " reason=" . str_replace(["\n", "\r"], ' ', (string) $row->rejection_reason) . "\n";

        $cursor = 0;
        $hitFirst = false;
        $spanLow = $value !== '' ? mb_strtolower($value, 'UTF-8') : '';
        foreach ($tokens as $piece) {
            if (preg_match('/^\s+$/u', $piece)) {
                $cursor += mb_strlen($piece, 'UTF-8');
                continue;
            }
            $tokenStart = $cursor;
            $tokenEnd = $cursor + mb_strlen($piece, 'UTF-8');

            $tag = 'O';
            if ($start !== null && $end !== null) {
                if ($tokenStart >= $start && $tokenEnd <= $end) {
                    $tag = ($hitFirst ? 'I-' : 'B-') . $type;
                    $hitFirst = true;
                }
            } elseif ($spanLow !== '' && mb_strpos($spanLow, mb_strtolower($piece, 'UTF-8')) !== false) {
                // Fallback when offsets aren't recorded: B/I tag any token
                // whose lowercase appears in the lowercased span. Imperfect
                // but better than tagging nothing.
                $tag = ($hitFirst ? 'I-' : 'B-') . $type;
                $hitFirst = true;
            }

            $out .= $piece . "\t" . $tag . "\n";
            $cursor = $tokenEnd;
        }
        $out .= "\n";
        return $out;
    }

    private function fetchSourceText(int $objectId): string
    {
        $rows = DB::table('information_object_i18n')->where('id', $objectId)->get();
        $parts = [];
        foreach ($rows as $row) {
            foreach (self::SOURCE_TEXT_FIELDS as $field) {
                $val = isset($row->$field) ? $row->$field : null;
                if (is_string($val) && trim($val) !== '') {
                    $parts[] = $val;
                }
            }
        }
        return implode("\n\n", $parts);
    }

    /**
     * The DecisionRecorder schema doesn't dedicate a column for reject
     * reason - the reason rides through the snapshot column. Older flows
     * may have shipped it elsewhere; we probe the candidates_visible_snapshot
     * JSON for a 'reject_reason' / 'rejection_reason' key first, then fall
     * back to evidence_snapshot, then return ''.
     */
    private function extractRejectionReason($decision): string
    {
        foreach (['candidates_visible_snapshot', 'evidence_snapshot'] as $col) {
            if (empty($decision->$col)) {
                continue;
            }
            $decoded = json_decode((string) $decision->$col, true);
            if (!is_array($decoded)) {
                continue;
            }
            foreach (['reject_reason', 'rejection_reason', 'reason'] as $key) {
                if (isset($decoded[$key]) && is_string($decoded[$key]) && $decoded[$key] !== '') {
                    return $decoded[$key];
                }
            }
        }
        return '';
    }

    /**
     * Try the primary uploads path; create it as the current effective
     * user if it doesn't exist. Fall back to /tmp on failure.
     */
    private function resolveExportDir(): ?string
    {
        foreach ([self::PRIMARY_DIR, self::FALLBACK_DIR] as $dir) {
            if (is_dir($dir)) {
                if (is_writable($dir)) {
                    return $dir;
                }
                continue;
            }
            if (@mkdir($dir, 0775, true) && is_writable($dir)) {
                return $dir;
            }
        }
        return null;
    }

    private function intOrNull($v): ?int
    {
        if ($v === null || $v === '') {
            return null;
        }
        return (int) $v;
    }
}
