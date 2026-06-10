<?php

/**
 * WebAnnotationService (#146) — W3C Web Annotation Data Model + Protocol persistence.
 *
 * Spec: https://www.w3.org/TR/annotation-model/ + https://www.w3.org/TR/annotation-protocol/
 *
 * Copyright (C) 2026 Johan Pieterse / Plain Sailing Information Systems
 * Licensed under the GNU Affero General Public License v3.0 or later.
 */

use Illuminate\Database\Capsule\Manager as DB;

class WebAnnotationService
{
    public const CONTEXT = 'http://www.w3.org/ns/anno.jsonld';
    public const PROFILE = 'application/ld+json; profile="http://www.w3.org/ns/anno.jsonld"';

    private static string $table = 'ahg_web_annotation';

    private string $base;

    /** @param string $base absolute origin, e.g. https://psis.theahg.co.za */
    public function __construct(string $base = '')
    {
        $this->base = rtrim($base, '/');
    }

    public static function uuid4(): string
    {
        $d = random_bytes(16);
        $d[6] = chr((ord($d[6]) & 0x0f) | 0x40);
        $d[8] = chr((ord($d[8]) & 0x3f) | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($d), 4));
    }

    private function iri(string $uuid): string
    {
        return $this->base.'/annotations/'.$uuid;
    }

    /** Pull the target source IRI out of a (string|object|array) target. */
    private function targetUri($target): ?string
    {
        if (is_string($target)) {
            return $target;
        }
        if (is_array($target)) {
            if (isset($target['source'])) {
                return is_string($target['source']) ? $target['source'] : ($target['source']['id'] ?? null);
            }
            if (isset($target['id'])) {
                return $target['id'];
            }
            // list of targets — use the first
            if (isset($target[0])) {
                return $this->targetUri($target[0]);
            }
        }
        return null;
    }

    /** Create an annotation from a client-supplied W3C body. Returns the stored doc. */
    public function create(array $anno, ?int $userId = null): array
    {
        $uuid = self::uuid4();
        $now = gmdate('Y-m-d\TH:i:s\Z');

        unset($anno['@context'], $anno['id']);
        $doc = array_merge([
            '@context' => self::CONTEXT,
            'type' => 'Annotation',
        ], $anno);
        $doc['@context'] = self::CONTEXT;
        $doc['id'] = $this->iri($uuid);
        $doc['type'] = 'Annotation';
        $doc['created'] = $now;
        if ($userId) {
            $doc['creator'] = $doc['creator'] ?? ($this->base.'/user/'.$userId);
        }

        $target = $doc['target'] ?? null;
        $targetUri = $this->targetUri($target);

        DB::table(self::$table)->insert([
            'anno_uuid' => $uuid,
            'target_uri' => $targetUri ? substr($targetUri, 0, 1024) : null,
            'target_hash' => $targetUri ? sha1($targetUri) : null,
            'motivation' => is_string($doc['motivation'] ?? null) ? substr($doc['motivation'], 0, 64) : null,
            'creator' => is_string($doc['creator'] ?? null) ? substr($doc['creator'], 0, 255) : null,
            'created_by' => $userId,
            'body_json' => json_encode($doc, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        return $doc;
    }

    public function get(string $uuid): ?array
    {
        $row = DB::table(self::$table)->where('anno_uuid', $uuid)->first();
        if (!$row) {
            return null;
        }
        return json_decode($row->body_json, true) ?: null;
    }

    public function update(string $uuid, array $anno): ?array
    {
        $row = DB::table(self::$table)->where('anno_uuid', $uuid)->first();
        if (!$row) {
            return null;
        }
        $now = gmdate('Y-m-d\TH:i:s\Z');
        $existing = json_decode($row->body_json, true) ?: [];

        unset($anno['@context']);
        $doc = array_merge($existing, $anno);
        $doc['@context'] = self::CONTEXT;
        $doc['id'] = $this->iri($uuid);
        $doc['type'] = 'Annotation';
        $doc['created'] = $existing['created'] ?? $now;
        $doc['modified'] = $now;

        $target = $doc['target'] ?? null;
        $targetUri = $this->targetUri($target);

        DB::table(self::$table)->where('anno_uuid', $uuid)->update([
            'target_uri' => $targetUri ? substr($targetUri, 0, 1024) : null,
            'target_hash' => $targetUri ? sha1($targetUri) : null,
            'motivation' => is_string($doc['motivation'] ?? null) ? substr($doc['motivation'], 0, 64) : null,
            'body_json' => json_encode($doc, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            'modified_at' => date('Y-m-d H:i:s'),
        ]);

        return $doc;
    }

    public function delete(string $uuid): bool
    {
        return DB::table(self::$table)->where('anno_uuid', $uuid)->delete() > 0;
    }

    /**
     * Web Annotation Protocol container response. When $targetUri is given,
     * returns annotations on that target; otherwise the most recent.
     */
    public function container(?string $targetUri = null, int $limit = 200): array
    {
        $q = DB::table(self::$table);
        $containerId = $this->base.'/annotations';
        if ($targetUri) {
            $q->where('target_hash', sha1($targetUri));
            $containerId .= '?target='.rawurlencode($targetUri);
        }
        $total = (clone $q)->count();
        $rows = $q->orderByDesc('id')->limit($limit)->get();
        $items = [];
        foreach ($rows as $r) {
            $d = json_decode($r->body_json, true);
            if ($d) {
                $items[] = $d;
            }
        }

        return [
            '@context' => ['http://www.w3.org/ns/anno.jsonld', 'http://www.w3.org/ns/ldp.jsonld'],
            'id' => $containerId,
            'type' => ['BasicContainer', 'AnnotationCollection'],
            'total' => $total,
            'first' => [
                'id' => $containerId.($targetUri ? '&' : '?').'page=0',
                'type' => 'AnnotationPage',
                'startIndex' => 0,
                'items' => $items,
            ],
        ];
    }
}
