<?php

/**
 * OverrideService - reviewer corrections to AI inferences (atom-ahg-plugins).
 *
 * Port of the Heratio ahg-provenance-ai OverrideService to the AtoM-AHG side -
 * issue #140, Phase 4. The original inference is never overwritten; a reviewer
 * correction is recorded as a new event in ahg_ai_override plus a reified
 * PROV-O activity in Fuseki.
 *
 * Copyright (C) 2026 Johan Pieterse, Plain Sailing Information Systems
 * The Archive and Heritage Group (Pty) Ltd. Licensed GPL-3.0-or-later.
 */

namespace AhgProvenancePlugin\Service;

use Illuminate\Database\Capsule\Manager as Capsule;

/**
 * Records reviewer corrections to AI inferences as new record events.
 *
 * Issue #140 / heratio#61 ADR-0002 Phase 3b. The original inference triple is
 * NEVER overwritten. When a reviewer changes a field that was AI-suggested,
 * this service:
 *   1. Writes a row to ahg_ai_override (FK -> ahg_ai_inference) capturing
 *      reviewer + reason + before/after values + lifecycle status.
 *   2. Writes a reified PROV-O activity into Fuseki (PROV-O reification, not an
 *      RDF-Star meta-assertion, because auditors read PROV-O activities more
 *      naturally than turtle-star).
 */
class OverrideService
{
    /**
     * Record a reviewer override on an inference.
     *
     * Returns ['id' => int, 'uuid' => string, 'created' => bool]. The 'created'
     * flag is false when the same reviewer applies the same override_value to
     * the same inference within 60s - duplicates are collapsed (idempotency).
     *
     * @return array{id:int,uuid:string,created:bool}
     */
    public function record(
        int $inferenceId,
        string $originalValue,
        string $overrideValue,
        int $reviewerUserId,
        ?string $reason = null
    ): array {
        // Idempotency window: same inference + same override_value by the same
        // reviewer within the last 60s (e.g. a double Save click).
        $existing = Capsule::table('ahg_ai_override')
            ->where('inference_id', $inferenceId)
            ->where('reviewer_user_id', $reviewerUserId)
            ->where('override_value', $overrideValue)
            ->where('created_at', '>=', date('Y-m-d H:i:s', time() - 60))
            ->orderByDesc('id')
            ->first();
        if ($existing) {
            return ['id' => (int) $existing->id, 'uuid' => (string) $existing->uuid, 'created' => false];
        }

        $uuid = $this->uuid4();
        $now  = date('Y-m-d H:i:s');
        $id   = (int) Capsule::table('ahg_ai_override')->insertGetId([
            'uuid'                => $uuid,
            'inference_id'        => $inferenceId,
            'reviewer_user_id'    => $reviewerUserId,
            'reason'              => $reason,
            'original_value'      => $originalValue,
            'override_value'      => $overrideValue,
            'status'              => 'applied',
            'fuseki_override_uri' => null,
            'occurred_at'         => $now,
            'created_at'          => $now,
            'updated_at'          => $now,
        ]);

        $persisted = (object) [
            'id'               => $id,
            'uuid'             => $uuid,
            'inference_id'     => $inferenceId,
            'reviewer_user_id' => $reviewerUserId,
            'reason'           => $reason,
            'original_value'   => $originalValue,
            'override_value'   => $overrideValue,
            'status'           => 'applied',
            'occurred_at'      => $now,
        ];
        $this->writeProvActivity($id, $persisted);

        return ['id' => $id, 'uuid' => $uuid, 'created' => true];
    }

    /**
     * Bulk-detect overrides from a form submission.
     *
     * For every field where (a) before !== after and (b) the field has at least
     * one inference, an override row is created. Returns the count recorded.
     * Per ADR-0002 the original triple is never overwritten - this only records
     * the correction event; the caller still performs the actual field write.
     *
     * @param array<string,mixed> $before field-key => previous value
     * @param array<string,mixed> $after  field-key => new value
     */
    public function detectOverridesFromForm(
        string $entityType,
        int $entityId,
        array $before,
        array $after,
        int $reviewerUserId
    ): int {
        $count = 0;
        foreach ($after as $field => $newValue) {
            $newValue = (string) ($newValue ?? '');
            $oldValue = (string) ($before[$field] ?? '');
            if ($newValue === $oldValue) {
                continue;
            }
            $inference = $this->findLatestInferenceForField($entityType, $entityId, (string) $field);
            if (!$inference) {
                continue; // a plain manual edit, not an override of any AI suggestion.
            }
            $this->record(
                (int) $inference->id,
                $oldValue,
                $newValue,
                $reviewerUserId,
                null
            );
            $count++;
        }

        return $count;
    }

    /**
     * Find the most-recent inference targeting a specific (entity, field).
     * Returns null when no inference exists - i.e. the change is a plain
     * manual edit, not an override of an AI suggestion.
     */
    public function findLatestInferenceForField(string $entityType, int $entityId, string $field): ?object
    {
        return Capsule::table('ahg_ai_inference')
            ->where('target_entity_type', $entityType)
            ->where('target_entity_id', $entityId)
            ->where('target_field', $field)
            ->orderByDesc('occurred_at')
            ->first() ?: null;
    }

    /** All overrides recorded against a given inference, oldest first. */
    public function listForInference(int $inferenceId): array
    {
        return Capsule::table('ahg_ai_override')
            ->where('inference_id', $inferenceId)
            ->orderBy('occurred_at')
            ->get()
            ->all();
    }

    /**
     * Write the reified PROV-O activity for an override to Fuseki.
     *
     * On success: UPDATE ahg_ai_override SET fuseki_override_uri = <graph>.
     * On failure: log and leave NULL so `ai-provenance:replay` retries.
     */
    private function writeProvActivity(int $id, object $row): void
    {
        try {
            if (!InferenceService::fusekiSyncEnabled()) {
                return; // operator opted out of inline writes; replay handles it.
            }
            $client = InferenceService::fusekiClient();
            if ($client === null) {
                return; // ahgAuthorityResolutionPlugin absent; replay will retry.
            }
            $built  = $this->buildOverrideSparql($row);
            $result = $client->executeUpdate($built['sparql']);

            if (!empty($result['ok'])) {
                Capsule::table('ahg_ai_override')->where('id', $id)
                    ->update(['fuseki_override_uri' => $built['graph']]);
            } else {
                error_log('[ahgProvenancePlugin] override PROV-O write deferred for replay (override '
                    . $id . '): ' . ($result['error'] ?? 'unknown'));
            }
        } catch (\Throwable $e) {
            error_log('[ahgProvenancePlugin] override PROV-O write threw, queued for replay (override '
                . $id . '): ' . $e->getMessage());
        }
    }

    /**
     * Build the SPARQL UPDATE that inserts an override's reified PROV-O activity.
     *
     * Returns ['graph' => <graph-uri>, 'sparql' => <INSERT DATA statement>].
     * Driven by an ahg_ai_override row; the inference UUID is resolved from the
     * inference_id FK so both record() and the replay task emit the same
     * idempotent statement.
     */
    public function buildOverrideSparql(object $row): array
    {
        $uuid     = (string) $row->uuid;
        $graphUri = 'urn:' . InferenceService::TENANT . ':provenance-ai:override:' . $uuid;
        $override = '<' . $graphUri . '>';

        $infUuid = Capsule::table('ahg_ai_inference')
            ->where('id', (int) $row->inference_id)
            ->value('uuid');
        $inference = '<' . InferenceService::inferenceGraphUri(
            is_string($infUuid) && $infUuid !== '' ? $infUuid : 'unknown'
        ) . '>';

        $user = '<urn:' . InferenceService::TENANT . ':user:' . (int) $row->reviewer_user_id . '>';
        $iso  = InferenceService::iso8601((string) ($row->occurred_at ?? ''));

        $lines = [];
        $lines[] = "{$override} a prov:Activity ;";
        $lines[] = "    prov:used {$inference} ;";
        $lines[] = "    prov:wasAssociatedWith {$user} ;";
        $lines[] = "    prov:atTime \"{$iso}\"^^xsd:dateTime ;";
        $lines[] = '    ex:originalValue ' . InferenceService::ttlLiteral((string) ($row->original_value ?? '')) . ' ;';
        $lines[] = '    ex:newValue ' . InferenceService::ttlLiteral((string) ($row->override_value ?? '')) . ' ;';
        if (!empty($row->reason)) {
            $lines[] = '    ex:reason ' . InferenceService::ttlLiteral((string) $row->reason) . ' ;';
        }
        $lines[] = '    ex:status ' . InferenceService::ttlLiteral((string) ($row->status ?? 'applied')) . ' .';

        $sparql = InferenceService::sparqlPrefixes()
            . "INSERT DATA {\n  GRAPH <{$graphUri}> {\n    "
            . implode("\n    ", $lines)
            . "\n  }\n}";

        return ['graph' => $graphUri, 'sparql' => $sparql];
    }

    /** RFC 4122 version-4 UUID, dependency-free. */
    private function uuid4(): string
    {
        $b = random_bytes(16);
        $b[6] = chr((ord($b[6]) & 0x0f) | 0x40);
        $b[8] = chr((ord($b[8]) & 0x3f) | 0x80);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($b), 4));
    }
}
