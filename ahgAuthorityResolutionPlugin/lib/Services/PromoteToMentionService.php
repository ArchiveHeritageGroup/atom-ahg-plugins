<?php

/**
 * PromoteToMentionService — service for AtoM Heratio
 *
 * Orchestrator. Given a ner_entity_id, fetches the source text from the
 * owning information_object, asks ContextDerivationService for the
 * neighbourhood packet, then INSERTs ahg_mention + ahg_mention_context
 * in a single transaction.
 *
 * Idempotent: UNIQUE on ahg_mention.ner_entity_id makes re-promotion a no-op.
 *
 * Capsule-based DB access (matches existing ahg-* plugins; no Laravel
 * application helpers like now() are used here so the service runs cleanly
 * from any AtoM entry point — CLI tasks, framework commands, action handlers).
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

class PromoteToMentionService
{
    private const SOURCE_TEXT_FIELDS = [
        'title',
        'scope_and_content',
        'extent_and_medium',
        'arrangement',
        'archival_history',
        'acquisition',
        'physical_characteristics',
    ];

    /** @var ContextDerivationService */
    private $contextDeriver;

    public function __construct(ContextDerivationService $contextDeriver)
    {
        $this->contextDeriver = $contextDeriver;
    }

    /**
     * Promote a single ner_entity row to ahg_mention + ahg_mention_context.
     *
     * @param int         $nerEntityId
     * @param string|null $sourceText     Exact text NER ran against (full match rate);
     *                                    null falls back to IO i18n concat (lossy when
     *                                    NER ran against digital-object content).
     * @param array|null  $knownOffset    {start:int,end:int} per-entity offset from
     *                                    the API entities_v2 payload. Forwarded to
     *                                    ContextDerivationService::derive() to skip
     *                                    the lossy stripos scan.
     * @param float|null  $realConfidence Per-entity score from entities_v2 (real
     *                                    float or null — spaCy standard NER emits
     *                                    no per-entity confidence). Written to
     *                                    ahg_mention_context.real_confidence.
     * @return int|null  ahg_mention.id (existing or new), or null if entity missing.
     */
    public function promote(
        int $nerEntityId,
        ?string $sourceText = null,
        ?array $knownOffset = null,
        ?float $realConfidence = null
    ): ?int {
        $entity = DB::table('ahg_ner_entity')->where('id', $nerEntityId)->first();
        if (!$entity) {
            return null;
        }

        $existing = DB::table('ahg_mention')->where('ner_entity_id', $nerEntityId)->first();
        if ($existing) {
            return (int) $existing->id;
        }

        $sourceText = $sourceText !== null
            ? $sourceText
            : $this->fetchSourceText((int) $entity->object_id);
        $others = $this->fetchOtherEntities((int) $entity->object_id, $nerEntityId);
        $roleTokens = $this->loadRoleLanguageTokens();

        $context = $this->contextDeriver->derive(
            $sourceText,
            (string) $entity->entity_value,
            (string) $entity->entity_type,
            $others,
            $roleTokens,
            $knownOffset
        );

        return DB::transaction(function () use ($entity, $context, $realConfidence) {
            $now = date('Y-m-d H:i:s');
            $mentionId = DB::table('ahg_mention')->insertGetId([
                'ner_entity_id' => $entity->id,
                'object_id' => $entity->object_id,
                'entity_type' => $entity->entity_type,
                'state' => 'pending',
                'promoted_at' => $now,
                'updated_at' => $now,
            ]);

            DB::table('ahg_mention_context')->insert([
                'mention_id' => $mentionId,
                'character_offset_start' => $context['character_offset_start'],
                'character_offset_end' => $context['character_offset_end'],
                'paragraph_offset_start' => $context['paragraph_offset_start'],
                'paragraph_offset_end' => $context['paragraph_offset_end'],
                'surrounding_text_before' => $context['surrounding_text_before'],
                'surrounding_text_after' => $context['surrounding_text_after'],
                'ner_model_version' => null,
                'real_confidence' => $realConfidence,
                'co_occurring_entities' => json_encode($context['co_occurring_entities'], JSON_UNESCAPED_UNICODE),
                'nearby_dates' => json_encode($context['nearby_dates'], JSON_UNESCAPED_UNICODE),
                'nearby_places' => json_encode($context['nearby_places'], JSON_UNESCAPED_UNICODE),
                'role_language_tokens' => json_encode($context['role_language_tokens'], JSON_UNESCAPED_UNICODE),
                'computed_at' => $now,
            ]);

            return $mentionId;
        });
    }

    /**
     * Promote every PERSON/ORG/GPE/PLACE/LOC ner_entity row for an object.
     * Returns the count of newly-promoted rows.
     */
    public function promoteAllForObject(int $objectId): int
    {
        $rows = DB::table('ahg_ner_entity')
            ->where('object_id', $objectId)
            ->whereIn('entity_type', ['PERSON', 'ORG', 'GPE', 'PLACE', 'LOC'])
            ->pluck('id');

        $newCount = 0;
        foreach ($rows as $id) {
            $existing = DB::table('ahg_mention')->where('ner_entity_id', $id)->exists();
            if ($existing) {
                continue;
            }
            try {
                $this->promote((int) $id);
                $newCount++;
            } catch (\Throwable $e) {
                error_log('PromoteToMentionService::promoteAllForObject failed (ner_entity_id=' . $id . '): ' . $e->getMessage());
            }
        }
        return $newCount;
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

    private function fetchOtherEntities(int $objectId, int $excludeNerEntityId): array
    {
        $rows = DB::table('ahg_ner_entity')
            ->where('object_id', $objectId)
            ->where('id', '!=', $excludeNerEntityId)
            ->get(['id', 'entity_type', 'entity_value']);

        $out = [];
        foreach ($rows as $r) {
            $out[] = [
                'ner_entity_id' => (int) $r->id,
                'type' => (string) $r->entity_type,
                'value' => (string) $r->entity_value,
            ];
        }
        return $out;
    }

    private function loadRoleLanguageTokens(): array
    {
        $row = DB::table('ahg_settings')
            ->where('setting_group', 'authority_resolution')
            ->where('setting_key', 'authority_resolution.role_language_tokens')
            ->first();

        if (!$row || empty($row->setting_value)) {
            return [];
        }

        $decoded = json_decode($row->setting_value, true);
        return is_array($decoded) ? $decoded : [];
    }
}
