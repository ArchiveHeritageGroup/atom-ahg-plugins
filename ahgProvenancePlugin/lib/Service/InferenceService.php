<?php

/**
 * InferenceService - AI inference provenance recording (atom-ahg-plugins).
 *
 * Port of heratio#61 / heratio#135 / heratio#136 to the AtoM-AHG side - issue
 * #140. Every AtoM AI action that produces an inference (NER, summarize, HTR,
 * ...) calls record() to persist one ahg_ai_inference row, capture a structured
 * model manifest, Ed25519-sign the canonical manifest, and write the RDF-Star
 * provenance annotation to Fuseki.
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
 * the /ai/governance dashboard, audits). The Fuseki RDF-Star annotation is
 * the canonical defensible semantic record. SQL is written first so an
 * inference is never lost - if Fuseki is down, the row still lands with
 * fuseki_graph_uri NULL and the `ai-provenance:replay` task retries it.
 *
 * Mirrors the Heratio AhgProvenanceAi\Services\InferenceService.
 */
class InferenceService
{
    /** RDF namespaces - kept stable so the AtoM and Heratio graphs are uniform. */
    public const NS_PROV = 'http://www.w3.org/ns/prov#';
    public const NS_XSD  = 'http://www.w3.org/2001/XMLSchema#';
    public const NS_EX   = 'https://heratio.theahg.co.za/ontology/provenance-ai#';

    /** URN tenant segment for inference / output / entity URIs (heratio default). */
    public const TENANT = 'ahg';

    /**
     * Persist an inference. Returns ['id' => int, 'uuid' => string].
     *
     * Best-effort and self-contained: a signing, manifest or Fuseki failure is
     * logged but never thrown, so an AI action's user-facing flow is never
     * broken by a provenance concern. The SQL row itself is the one write that
     * must land; if even that fails the caller's try/catch handles it.
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
            'fuseki_graph_uri'    => null, // populated by writeRdfStarAnnotation / replay.
            'model_manifest'      => json_encode($modelManifest),
            'user_id'             => $r->userId,
            'occurred_at'         => $now,
            'created_at'          => $now,
            'updated_at'          => $now,
        ]);

        // The persisted row, shaped exactly like the one the verify and replay
        // paths select back, so the signed manifest and the RDF-Star turtle are
        // each produced by one and only one builder.
        $persisted = (object) [
            'id'                 => $id,
            'uuid'               => $uuid,
            'occurred_at'        => $now,
            'service_name'       => $r->serviceName,
            'model_name'         => $r->modelName,
            'model_version'      => $r->modelVersion,
            'endpoint'           => $r->endpoint,
            'input_hash'         => $r->inputHash,
            'output_hash'        => $r->outputHash,
            'confidence'         => $confidence,
            'standard'           => $r->standard,
            'model_manifest'     => json_encode($modelManifest),
            'target_entity_type' => $r->targetEntityType,
            'target_entity_id'   => $r->targetEntityId,
            'target_field'       => $r->targetField,
        ];

        // heratio#136 - Ed25519-sign the canonical manifest of this inference.
        // Opt-in: a no-op until `ai-provenance:keygen` has minted a keypair.
        try {
            $signer    = new InferenceSigner();
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

        // Phase 3 (issue #140) - write the RDF-Star annotation to Fuseki.
        // Best-effort: on failure fuseki_graph_uri stays NULL and the
        // `ai-provenance:replay` task retries the write.
        $this->writeRdfStarAnnotation($id, $persisted);

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
     * Phase 3 - write the inference's RDF-Star annotation to Fuseki.
     *
     * On success: UPDATE ahg_ai_inference SET fuseki_graph_uri = <graph>.
     * On failure (or when the SPARQL client / Fuseki are unavailable): log and
     * leave fuseki_graph_uri NULL so `ai-provenance:replay` retries the write.
     */
    private function writeRdfStarAnnotation(int $id, object $row): void
    {
        try {
            if (!self::fusekiSyncEnabled()) {
                return; // operator opted out of inline writes; replay handles it.
            }
            $client = self::fusekiClient();
            if ($client === null) {
                return; // ahgAuthorityResolutionPlugin absent; replay will retry.
            }
            $built  = $this->buildInferenceSparql($row);
            $result = $client->executeUpdate($built['sparql']);

            if (!empty($result['ok'])) {
                Capsule::table('ahg_ai_inference')->where('id', $id)
                    ->update(['fuseki_graph_uri' => $built['graph']]);
            } else {
                error_log('[ahgProvenancePlugin] RDF-Star write deferred for replay (inference '
                    . $id . '): ' . ($result['error'] ?? 'unknown'));
            }
        } catch (\Throwable $e) {
            // The SQL row is already committed; a Fuseki failure must never
            // poison the AI caller. The replay task will retry.
            error_log('[ahgProvenancePlugin] RDF-Star write threw, queued for replay (inference '
                . $id . '): ' . $e->getMessage());
        }
    }

    /**
     * Build the SPARQL UPDATE that inserts an inference's RDF-Star annotation.
     *
     * Returns ['graph' => <graph-uri>, 'sparql' => <INSERT DATA statement>].
     * Driven entirely by a row object, so both record() (with the row it just
     * persisted) and the replay task (with a row selected from the DB) emit an
     * identical, idempotent statement.
     *
     * Turtle shape mirrors heratio's buildInferenceTurtle(): the inference is a
     * prov:Activity, and an RDF-Star meta-assertion anchors the generated
     * triple back to that activity.
     */
    public function buildInferenceSparql(object $row): array
    {
        $uuid     = (string) $row->uuid;
        $graphUri = self::inferenceGraphUri($uuid);
        $activity = '<' . $graphUri . '>';
        $output   = '<urn:' . self::TENANT . ':provenance-ai:output:' . (string) $row->output_hash . '>';
        $target   = self::entityUri(
            (string) $row->target_entity_type,
            (int) $row->target_entity_id,
            (string) $row->target_field
        );
        $iso = self::iso8601((string) ($row->occurred_at ?? ''));

        $lines = [];
        $lines[] = "{$activity} a prov:Activity ;";
        $lines[] = "    prov:atTime \"{$iso}\"^^xsd:dateTime ;";
        $lines[] = '    ex:service ' . self::ttlLiteral((string) $row->service_name) . ' ;';
        $lines[] = '    ex:model ' . self::ttlLiteral((string) $row->model_name) . ' ;';
        $lines[] = '    ex:modelVersion ' . self::ttlLiteral((string) $row->model_version) . ' ;';
        $lines[] = '    ex:inputHash ' . self::ttlLiteral((string) $row->input_hash) . ' ;';
        $lines[] = '    ex:outputHash ' . self::ttlLiteral((string) $row->output_hash) . ' ;';
        if (isset($row->confidence) && $row->confidence !== null && $row->confidence !== '') {
            $lines[] = "    ex:confidence \"" . (string) $row->confidence . "\"^^xsd:decimal ;";
        }
        if (!empty($row->standard)) {
            $lines[] = '    ex:standard ' . self::ttlLiteral((string) $row->standard) . ' ;';
        }
        if (!empty($row->endpoint)) {
            $lines[] = '    ex:endpoint ' . self::ttlLiteral((string) $row->endpoint) . ' ;';
        }
        $lines[] = "    prov:generated {$output} .";
        // RDF-Star meta-assertion: the generated triple carries a back-pointer
        // to the inference activity that produced it.
        $lines[] = "<< {$target} ex:hasGenerated {$output} >> prov:wasGeneratedBy {$activity} .";

        $sparql = self::sparqlPrefixes()
            . "INSERT DATA {\n  GRAPH <{$graphUri}> {\n    "
            . implode("\n    ", $lines)
            . "\n  }\n}";

        return ['graph' => $graphUri, 'sparql' => $sparql];
    }

    /**
     * Resolve a Fuseki SPARQL-update client, or null when ahgAuthorityResolutionPlugin
     * is not installed.
     *
     * The AtoM-AHG `AtomFramework\Services\AuthorityResolution\` namespace has
     * no PSR-4 autoloader (its own tasks require_once the files explicitly), so
     * the class is loaded by absolute path from the plugins directory before
     * use - relying on class_exists() autoload alone would silently disable
     * every Fuseki write.
     *
     * @return object|null an AtomFramework\Services\AuthorityResolution\FusekiUpdateService
     */
    public static function fusekiClient()
    {
        $cls = 'AtomFramework\\Services\\AuthorityResolution\\FusekiUpdateService';
        if (!class_exists($cls, false)) {
            $base = class_exists('sfConfig') ? (string) \sfConfig::get('sf_plugins_dir') : '';
            $path = $base . '/ahgAuthorityResolutionPlugin/lib/Services/FusekiUpdateService.php';
            if ($base !== '' && is_file($path)) {
                require_once $path;
            }
        }

        return class_exists($cls, false) ? new $cls() : null;
    }

    /** The SPARQL UPDATE PREFIX block shared by the inference + override writers. */
    public static function sparqlPrefixes(): string
    {
        return 'PREFIX prov: <' . self::NS_PROV . ">\n"
             . 'PREFIX xsd: <' . self::NS_XSD . ">\n"
             . 'PREFIX ex: <' . self::NS_EX . ">\n";
    }

    /** Graph / activity URI for an inference. */
    public static function inferenceGraphUri(string $uuid): string
    {
        return 'urn:' . self::TENANT . ':provenance-ai:inference:' . $uuid;
    }

    /** Stable URI for an inference target field. */
    public static function entityUri(string $type, int $id, string $field): string
    {
        return '<urn:' . self::TENANT . ':entity:' . rawurlencode($type) . ':' . $id . ':' . rawurlencode($field) . '>';
    }

    /** A turtle / SPARQL string literal with the meaning-changing chars escaped. */
    public static function ttlLiteral(string $s): string
    {
        return '"' . str_replace(
            ['\\', '"', "\n", "\r", "\t"],
            ['\\\\', '\\"', '\\n', '\\r', '\\t'],
            $s
        ) . '"';
    }

    /** Format a MySQL datetime as an xsd:dateTime Zulu string. */
    public static function iso8601(string $mysqlDateTime): string
    {
        try {
            return (new \DateTimeImmutable($mysqlDateTime, new \DateTimeZone('UTC')))
                ->format('Y-m-d\TH:i:s\Z');
        } catch (\Throwable $e) {
            return gmdate('Y-m-d\TH:i:s\Z');
        }
    }

    /**
     * Whether inline Fuseki writes are enabled. Reads ahg_settings.fuseki_sync_enabled;
     * an absent setting defaults to enabled (the replay task is the safety net),
     * an unreadable settings table defaults to disabled.
     */
    public static function fusekiSyncEnabled(): bool
    {
        try {
            $v = Capsule::table('ahg_settings')->where('setting_key', 'fuseki_sync_enabled')->value('setting_value');
            if ($v === null) {
                return true;
            }

            return !in_array(strtolower(trim((string) $v)), ['0', 'false', 'off', 'no', ''], true);
        } catch (\Throwable $e) {
            return false;
        }
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
