<?php

/**
 * InferenceService - AI inference provenance recording (atom-ahg-plugins).
 *
 * Port of heratio#61 / heratio#135 / heratio#136 to the AtoM-AHG side - issue
 * #140. Every AtoM AI action that produces an inference (NER, summarize, HTR,
 * ...) calls record() to persist one ahg_ai_inference row, capture a structured
 * model manifest and Ed25519-sign the canonical manifest.
 *
 * Copyright (C) 2026 Johan Pieterse, Plain Sailing Information Systems
 * The Archive and Heritage Group (Pty) Ltd. Licensed GPL-3.0-or-later.
 */

namespace AhgProvenancePlugin\Service;

use Illuminate\Database\Capsule\Manager as Capsule;

/**
 * Single entry point every AtoM AI action uses to record an inference.
 *
 * The MySQL row in ahg_ai_inference is the operational store (filtering,
 * the /ai/governance dashboard, audits). It mirrors the Heratio
 * AhgProvenanceAi\Services\InferenceService.
 *
 * Scope on the AtoM side (issue #140, Phases 1-2): this writes the SQL row,
 * composes the per-inference model manifest (heratio#135) and Ed25519-signs
 * the canonical manifest (heratio#136). The Fuseki RDF-Star annotation
 * (Phase 3) is deferred - fuseki_graph_uri is left NULL, exactly as on the
 * Heratio side before a replay job catches up, so the row is never blocked
 * on the triplestore.
 */
class InferenceService
{
    /**
     * Persist an inference. Returns ['id' => int, 'uuid' => string].
     *
     * Best-effort and self-contained: a signing or manifest failure is logged
     * but never thrown, so an AI action's user-facing flow is never broken by
     * a provenance concern. The SQL row itself is the one write that must
     * land; if even that fails the caller's try/catch handles it.
     */
    public function record(InferenceRecord $r): array
    {
        $uuid = $this->uuid4();
        $now  = date('Y-m-d H:i:s');

        // heratio#135 - structured model provenance captured at inference time.
        $modelManifest = $this->buildModelManifest($r);

        // Normalise confidence to the ahg_ai_inference.confidence column
        // precision (decimal(6,5)) so the value that is signed is byte-for-byte
        // the value that is stored - this is what makes a recorded signature
        // verifiable straight off the persisted row (see manifestFromRow).
        $confidence = self::normalizeConfidence($r->confidence);

        $id = (int) Capsule::table('ahg_ai_inference')->insertGetId([
            'uuid'                => $uuid,
            'service_name'        => $r->serviceName,
            'model_name'          => $r->modelName,
            'model_version'       => $r->modelVersion,
            'endpoint'            => $r->endpoint,
            'input_hash'          => $r->inputHash,
            'input_excerpt'       => $r->inputExcerpt,
            'output_hash'         => $r->outputHash,
            'output_excerpt'      => $r->outputExcerpt,
            'confidence'          => $confidence,
            'standard'            => $r->standard,
            'target_entity_type'  => $r->targetEntityType,
            'target_entity_id'    => $r->targetEntityId,
            'target_field'        => $r->targetField,
            'elapsed_ms'          => $r->elapsedMs,
            'fuseki_graph_uri'    => null, // Phase 3 (RDF-Star) deferred - issue #140.
            'model_manifest'      => json_encode($modelManifest),
            'user_id'             => $r->userId,
            'occurred_at'         => $now,
            'created_at'          => $now,
            'updated_at'          => $now,
        ]);

        // heratio#136 - Ed25519-sign the canonical manifest of this inference.
        // Opt-in: a no-op until `ai-provenance:keygen` has minted a keypair.
        // The manifest is built from a row object shaped exactly like the one
        // the verify path selects back, so the signed bytes and the verified
        // bytes are produced by one and only one builder (manifestFromRow).
        try {
            $signer    = new InferenceSigner();
            $persisted = (object) [
                'id'                 => $id,
                'uuid'               => $uuid,
                'occurred_at'        => $now,
                'service_name'       => $r->serviceName,
                'model_name'         => $r->modelName,
                'model_version'      => $r->modelVersion,
                'input_hash'         => $r->inputHash,
                'output_hash'        => $r->outputHash,
                'confidence'         => $confidence,
                'model_manifest'     => json_encode($modelManifest),
                'target_entity_type' => $r->targetEntityType,
                'target_entity_id'   => $r->targetEntityId,
                'target_field'       => $r->targetField,
            ];
            $signature = $signer->sign($this->manifestFromRow($persisted));
            if ($signature !== null) {
                Capsule::table('ahg_ai_inference')->where('id', $id)->update([
                    'signature'     => $signature,
                    'signer_key_id' => $signer->keyId(),
                ]);
            }
        } catch (\Throwable $e) {
            error_log('[ahgProvenancePlugin] inference signing failed (id ' . $id . '): ' . $e->getMessage());
        }

        return ['id' => $id, 'uuid' => $uuid];
    }

    /**
     * Rebuild the canonical signed manifest for an already-recorded row.
     *
     * The verify task feeds a freshly-selected ahg_ai_inference row through
     * this so the manifest it checks the signature against is byte-identical
     * to the one record() signed. Every column is cast back to the type it
     * had at sign time (the DB hands bigint/decimal columns back as strings).
     */
    public function manifestFromRow(object $row): array
    {
        $modelManifest = [];
        if (isset($row->model_manifest) && is_string($row->model_manifest) && $row->model_manifest !== '') {
            $decoded = json_decode($row->model_manifest, true);
            if (is_array($decoded)) {
                $modelManifest = $decoded;
            }
        }

        return $this->composeManifest(
            (int) $row->id,
            (string) $row->uuid,
            (string) $row->occurred_at,
            (string) $row->service_name,
            (string) $row->model_name,
            (string) $row->model_version,
            (string) $row->input_hash,
            (string) $row->output_hash,
            self::normalizeConfidence(isset($row->confidence) ? $row->confidence : null),
            $modelManifest,
            $this->targetKey(
                (string) $row->target_entity_type,
                (int) $row->target_entity_id,
                (string) $row->target_field
            )
        );
    }

    /**
     * The canonical manifest that is Ed25519-signed. The field set is fixed and
     * must match what manifestFromRow() reconstructs - it mirrors the Heratio
     * AhgProvenanceAi\Services\InferenceService::buildManifest() shape.
     */
    private function composeManifest(
        int $id,
        string $uuid,
        string $occurredAt,
        string $serviceName,
        string $modelName,
        string $modelVersion,
        string $inputHash,
        string $outputHash,
        ?float $confidence,
        array $modelManifest,
        string $target
    ): array {
        return [
            'id'             => $id,
            'uuid'           => $uuid,
            'occurred_at'    => $occurredAt,
            'service_name'   => $serviceName,
            'model_name'     => $modelName,
            'model_version'  => $modelVersion,
            'input_hash'     => $inputHash,
            'output_hash'    => $outputHash,
            'confidence'     => $confidence,
            'model_manifest' => $modelManifest,
            'target'         => $target,
        ];
    }

    /** Stable "{type}:{id}:{field}" target key used inside the signed manifest. */
    private function targetKey(string $entityType, int $entityId, string $field): string
    {
        return $entityType . ':' . $entityId . ':' . $field;
    }

    /**
     * heratio#135 - the per-inference model manifest. Starts from the
     * operator-curated manifest on ahg_llm_config (matched on model name) and
     * overlays the live model identity captured at inference time.
     */
    private function buildModelManifest(InferenceRecord $r): array
    {
        $configManifest = null;
        try {
            $raw = Capsule::table('ahg_llm_config')->where('model', $r->modelName)->value('model_manifest');
            if (is_string($raw) && $raw !== '') {
                $decoded = json_decode($raw, true);
                if (is_array($decoded)) {
                    $configManifest = $decoded;
                }
            }
        } catch (\Throwable $e) {
            // ahg_llm_config or its model_manifest column may be absent on an
            // older install - fall through to the minimal live-only manifest.
        }

        return self::composeModelManifest($r->modelName, $r->modelVersion, $r->serviceName, $configManifest);
    }

    /**
     * Pure composer (no DB): the operator-curated config manifest with the
     * live inference-time identity overlaid. Always returns a non-empty
     * manifest so ahg_ai_inference.model_manifest is populated whenever model
     * metadata is available (heratio#135 acceptance criterion).
     */
    public static function composeModelManifest(
        string $modelName,
        string $modelVersion,
        string $serviceName,
        ?array $configManifest
    ): array {
        $manifest = is_array($configManifest) ? $configManifest : [];
        $manifest['model_name']    = $modelName;
        $manifest['model_version'] = $modelVersion;
        $manifest['service_name']  = $serviceName;
        if (!isset($manifest['model_id']) || $manifest['model_id'] === '') {
            $manifest['model_id'] = $modelName . '@' . $modelVersion;
        }

        return $manifest;
    }

    /**
     * Normalise a confidence score to the ahg_ai_inference.confidence column
     * precision (decimal(6,5)). NULL passes through unchanged.
     *
     * @param mixed $confidence float|string|null as supplied by a DTO or a DB row
     */
    public static function normalizeConfidence($confidence): ?float
    {
        if ($confidence === null || $confidence === '') {
            return null;
        }

        return round((float) $confidence, 5);
    }

    /** Look up an inference by uuid. Returns null when not found. */
    public function findByUuid(string $uuid): ?object
    {
        return Capsule::table('ahg_ai_inference')->where('uuid', $uuid)->first() ?: null;
    }

    /** RFC 4122 version-4 UUID, dependency-free (no Laravel Str helper here). */
    private function uuid4(): string
    {
        $b = random_bytes(16);
        $b[6] = chr((ord($b[6]) & 0x0f) | 0x40); // version 4
        $b[8] = chr((ord($b[8]) & 0x3f) | 0x80); // variant 10xx

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($b), 4));
    }
}
