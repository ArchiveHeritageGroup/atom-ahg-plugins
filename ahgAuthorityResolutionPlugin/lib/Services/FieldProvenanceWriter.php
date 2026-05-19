<?php

/**
 * FieldProvenanceWriter - AtoM Heratio
 *
 * Task 6 sibling of DecisionProvenanceWriter. Emits per-field provenance
 * to Fuseki when a new authority record is created via the "Create new
 * authority" sub-workflow. Each pre-filled field becomes one reified
 * RDF-Star assertion carrying source URI, retrieval time, license note,
 * and license URL - the audit chain a future FOIA / lineage query walks.
 *
 * Example (one field):
 *
 *   << <https://psis.theahg.co.za/actor/123>
 *        auth_res:hasField "authorized_form_of_name" >>
 *       auth_res:fieldValue   "Nelson Mandela" ;
 *       prov:wasDerivedFrom   <https://viaf.org/viaf/12345/> ;
 *       prov:generatedAtTime  "2026-05-19T12:00:00Z"^^xsd:dateTime ;
 *       auth_res:source       "viaf" ;
 *       auth_res:licence      "CC0-1.0" ;
 *       auth_res:licenceUrl   <https://creativecommons.org/publicdomain/zero/1.0/> .
 *
 * Distinct named graph from the decisions graph so SPARQL queries can
 * target one or the other:
 *   urn:atom:auth-res:graph:field-provenance vs.
 *   urn:atom:auth-res:graph:decisions
 *
 * Best-effort: failures are logged but do not throw - the SQL insert of
 * the new authority record is already durable; provenance can be replayed.
 *
 * Copyright (C) 2026 Johan Pieterse
 * Plain Sailing Information Systems
 * Email: johan@plainsailingisystems.co.za
 *
 * Licensed under the GNU General Public License v3.0 or later.
 */

namespace AtomFramework\Services\AuthorityResolution;

use Illuminate\Database\Capsule\Manager as DB;

require_once dirname(__FILE__) . '/FusekiUpdateService.php';

class FieldProvenanceWriter
{
    public const DEFAULT_GRAPH_URI = 'urn:atom:auth-res:graph:field-provenance';
    public const NS_PROV = 'http://www.w3.org/ns/prov#';
    public const NS_AUTH_RES = 'https://psis.theahg.co.za/ontology/auth-res#';

    /** @var FusekiUpdateService */
    private $sparql;

    public function __construct(FusekiUpdateService $sparql)
    {
        $this->sparql = $sparql;
    }

    /**
     * Write provenance triples for every pre-filled field on a freshly
     * created authority record.
     *
     * `$mergedFields` is the PrefillEngine `merged_fields` map where each
     * value is either:
     *   - a primitive (the bare value), OR
     *   - ['value' => ..., '_provenance' => [...]] (the PrefillEngine shape)
     *
     * `$prefillProvenance` is an optional explicit map keyed by field name
     * with per-field provenance. When set, it takes precedence over
     * `_provenance` keys embedded inside `$mergedFields`.
     *
     * @param int    $authorityId    actor.id or term.id of the new record
     * @param string $authorityType  'actor' | 'term'
     * @param array  $mergedFields   per-field value map (see above)
     * @param array  $prefillProvenance optional per-field provenance map
     * @param string|null $graphUri  override the default named graph
     *
     * @return array{
     *   ok: bool,
     *   triple_count: int,
     *   graph: string,
     *   turtle?: string,
     *   status?: int,
     *   error?: string
     * }
     */
    public function writeForCreation(
        int $authorityId,
        string $authorityType,
        array $mergedFields,
        array $prefillProvenance = [],
        ?string $graphUri = null
    ): array {
        if (!in_array($authorityType, ['actor', 'term'], true)) {
            return [
                'ok' => false,
                'triple_count' => 0,
                'graph' => '',
                'error' => "authorityType must be 'actor' or 'term'",
            ];
        }

        $graphUri = $graphUri !== null && $graphUri !== ''
            ? $graphUri
            : $this->loadGraphUri();

        $base = rtrim($this->siteBaseUrl(), '/');
        $subjectUri = $authorityType === 'actor'
            ? "<{$base}/actor/{$authorityId}>"
            : "<{$base}/place/{$authorityId}>";

        // Strip internal markers before iterating real fields.
        unset($mergedFields['_provenance']);

        $tripleCount = 0;
        $turtleChunks = [];

        foreach ($mergedFields as $field => $entry) {
            [$value, $prov] = $this->extractValueAndProv($entry, $prefillProvenance[$field] ?? null);
            if ($value === null || $value === '') {
                continue;
            }
            if (!is_array($prov)) {
                continue; // no provenance -> nothing to assert (user-typed field)
            }
            $turtleChunks[] = $this->buildOneFieldTurtle(
                $subjectUri,
                (string) $field,
                $this->stringify($value),
                $prov
            );
            $tripleCount++;
        }

        if ($tripleCount === 0) {
            return [
                'ok' => true,
                'triple_count' => 0,
                'graph' => $graphUri,
            ];
        }

        $turtleBody = implode("\n\n", $turtleChunks);
        $sparqlUpdate = $this->buildPrefixes()
            . "\nINSERT DATA {\n  GRAPH <{$graphUri}> {\n{$turtleBody}\n  }\n}";

        try {
            $result = $this->sparql->executeUpdate($sparqlUpdate);
        } catch (\Throwable $e) {
            error_log('FieldProvenanceWriter: sparql threw: ' . $e->getMessage());
            return [
                'ok' => false,
                'triple_count' => $tripleCount,
                'graph' => $graphUri,
                'turtle' => $turtleBody,
                'error' => $e->getMessage(),
            ];
        }

        if (!empty($result['ok'])) {
            return [
                'ok' => true,
                'triple_count' => $tripleCount,
                'graph' => $graphUri,
                'turtle' => $turtleBody,
                'status' => $result['status'] ?? 200,
            ];
        }

        error_log(sprintf(
            'FieldProvenanceWriter::writeForCreation failed authority=%d status=%s error=%s',
            $authorityId,
            isset($result['status']) ? (string) $result['status'] : 'n/a',
            isset($result['error']) ? (string) $result['error'] : 'unknown'
        ));

        return [
            'ok' => false,
            'triple_count' => $tripleCount,
            'graph' => $graphUri,
            'turtle' => $turtleBody,
            'status' => $result['status'] ?? 0,
            'error' => $result['error'] ?? 'unknown',
        ];
    }

    // -------------------------------------------------------------------
    // Internal helpers
    // -------------------------------------------------------------------

    /**
     * Accept either the PrefillEngine shape (['value'=>..,'_provenance'=>..])
     * or a bare value with provenance supplied separately.
     *
     * @param mixed $entry
     * @param mixed $explicitProv
     * @return array{0: mixed, 1: array|null}
     */
    private function extractValueAndProv($entry, $explicitProv): array
    {
        $value = $entry;
        $prov = is_array($explicitProv) ? $explicitProv : null;

        if (is_array($entry)) {
            if (array_key_exists('value', $entry)) {
                $value = $entry['value'];
            }
            if ($prov === null && isset($entry['_provenance']) && is_array($entry['_provenance'])) {
                $prov = $entry['_provenance'];
            }
        }
        return [$value, $prov];
    }

    private function buildOneFieldTurtle(string $subjectUri, string $field, string $value, array $prov): string
    {
        $assertion = "{$subjectUri} auth_res:hasField " . $this->literal($field);
        $reified = "<< {$assertion} >>";

        $sourceUri = isset($prov['uri']) && is_string($prov['uri']) && trim($prov['uri']) !== ''
            ? '<' . trim($prov['uri']) . '>'
            : null;
        $sourceName = (string) ($prov['source'] ?? 'unknown');
        $licence = isset($prov['license'])
            ? (string) $prov['license']
            : (isset($prov['licence']) ? (string) $prov['licence'] : null);
        $licenceUrlRaw = $prov['license_url'] ?? ($prov['licence_url'] ?? null);
        $licenceUrl = is_string($licenceUrlRaw) && trim($licenceUrlRaw) !== ''
            ? '<' . trim($licenceUrlRaw) . '>'
            : null;
        $retrievedAt = isset($prov['at']) && is_string($prov['at']) && trim($prov['at']) !== ''
            ? $prov['at']
            : (isset($prov['retrieved_at']) && is_string($prov['retrieved_at']) ? $prov['retrieved_at'] : gmdate('Y-m-d\TH:i:s\Z'));

        $parts = [];
        $parts[] = 'auth_res:fieldValue ' . $this->literal($value);
        if ($sourceUri !== null) {
            $parts[] = 'prov:wasDerivedFrom ' . $sourceUri;
        }
        $parts[] = "prov:generatedAtTime \"{$retrievedAt}\"^^xsd:dateTime";
        $parts[] = 'auth_res:source ' . $this->literal($sourceName);
        if ($licence !== null && $licence !== '') {
            $parts[] = 'auth_res:licence ' . $this->literal($licence);
        }
        if ($licenceUrl !== null) {
            $parts[] = 'auth_res:licenceUrl ' . $licenceUrl;
        }

        return $reified . "\n    " . implode(" ;\n    ", $parts) . " .";
    }

    private function buildPrefixes(): string
    {
        return implode("\n", [
            'PREFIX prov: <' . self::NS_PROV . '>',
            'PREFIX auth_res: <' . self::NS_AUTH_RES . '>',
            'PREFIX xsd: <http://www.w3.org/2001/XMLSchema#>',
        ]);
    }

    private function loadGraphUri(): string
    {
        try {
            $row = DB::table('ahg_settings')
                ->where('setting_key', 'authority_resolution.field_provenance_graph_uri')
                ->value('setting_value');
            if (is_string($row) && trim($row) !== '') {
                return $row;
            }
        } catch (\Throwable $e) {
            // fall through
        }
        return self::DEFAULT_GRAPH_URI;
    }

    private function siteBaseUrl(): string
    {
        try {
            $row = DB::table('ahg_settings')->where('setting_key', 'site_base_url')->value('setting_value');
            if (is_string($row) && trim($row) !== '') {
                return $row;
            }
        } catch (\Throwable $e) {
            // fall through
        }
        return 'https://psis.theahg.co.za';
    }

    private function literal(?string $s): string
    {
        if ($s === null) {
            return '""';
        }
        $escaped = str_replace(
            ['\\', '"', "\n", "\r", "\t"],
            ['\\\\', '\\"', '\\n', '\\r', '\\t'],
            $s
        );
        return '"' . $escaped . '"';
    }

    private function stringify($value): string
    {
        if (is_scalar($value)) {
            return (string) $value;
        }
        if (is_array($value)) {
            return (string) json_encode($value, JSON_UNESCAPED_UNICODE);
        }
        return '';
    }
}
