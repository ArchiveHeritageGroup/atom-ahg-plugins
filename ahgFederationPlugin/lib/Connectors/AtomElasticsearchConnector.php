<?php

/**
 * AtomElasticsearchConnector — exposes the local PSIS OpenSearch index as a
 * federation peer for the AtoM-side ahgFederationPlugin.
 *
 * Port of the Heratio Laravel implementation (May 2026). Replaces:
 *   - Heratio: AhgSearch\Services\ElasticsearchService::globalSearch()
 *   - AtoM:    direct HTTP POST against $base/$index_prefix_qubitinformationobject/_search
 *
 * Lives at /opt/ahg-sp-integration/F3/atom/lib/Connectors/ per the SP NO-PUSH
 * policy AND is mirrored to atom-ahg-plugins/ahgFederationPlugin/lib/Connectors/
 * on the live host. Both copies must be kept in sync. NEVER commit either.
 *
 * @phase F3-AtoM-port (2026-05-16)
 */

namespace AhgFederation\Connectors;

final class AtomElasticsearchConnector implements PeerConnector
{
    public const PEER_TYPE = 'atom_local';

    private object $peer;
    /** @var array<string,mixed> */
    private array $config = [];

    public function bind(object $peerRow): void
    {
        $this->peer = $peerRow;
        $raw = $peerRow->config ?? null;
        if (is_string($raw)) {
            $decoded = json_decode($raw, true);
            $this->config = is_array($decoded) ? $decoded : [];
        } elseif (is_array($raw)) {
            $this->config = $raw;
        }
    }

    public function peerTypeKey(): string
    {
        return self::PEER_TYPE;
    }

    public function supportsCapability(string $capability): bool
    {
        return in_array($capability, ['full_text_search', 'metadata_filter', 'date_range', 'acl_user_scope'], true);
    }

    /**
     * @param string $query
     * @param array<string,mixed> $filters
     * @param int $limit
     * @return PeerSearchResult[]
     */
    public function search(string $query, array $filters = [], int $limit = 50): array
    {
        $culture = (string) ($filters['culture'] ?? 'en');
        $base = $this->openSearchBase();
        $index = $this->openSearchIndex();

        $body = [
            'size' => max(1, min($limit, 100)),
            '_source' => ['i18n', 'identifier', 'slug', 'level_of_description_id', 'thumbnail_path'],
            'query' => [
                'bool' => [
                    'must' => [
                        ['query_string' => [
                            'query' => $query !== '' ? $query : '*',
                            'fields' => [
                                'i18n.' . $culture . '.title^3',
                                'i18n.' . $culture . '.scope_and_content',
                                'i18n.' . $culture . '.extent_and_medium',
                                'identifier',
                            ],
                        ]],
                    ],
                ],
            ],
            'highlight' => [
                'fields' => [
                    'i18n.' . $culture . '.title' => new \stdClass(),
                    'i18n.' . $culture . '.scope_and_content' => new \stdClass(),
                ],
                'pre_tags' => ['<mark>'],
                'post_tags' => ['</mark>'],
                'fragment_size' => 200,
            ],
        ];

        // Date-range filter (filters.date_range.from / .to)
        if (!empty($filters['date_range']['from']) || !empty($filters['date_range']['to'])) {
            $range = [];
            if (!empty($filters['date_range']['from'])) {
                $range['gte'] = substr((string) $filters['date_range']['from'], 0, 10);
            }
            if (!empty($filters['date_range']['to'])) {
                $range['lte'] = substr((string) $filters['date_range']['to'], 0, 10);
            }
            $body['query']['bool']['filter'][] = ['range' => ['dates.start_date' => $range]];
        }

        $url = rtrim($base, '/') . '/' . $index . '/_search';
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($body),
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 15,
        ]);
        $resp = curl_exec($ch);
        $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);

        if ($http < 200 || $http >= 300 || !$resp) {
            error_log(sprintf('AtomElasticsearchConnector search failed: HTTP=%d err=%s', $http, $err));
            return [];
        }
        $data = json_decode((string) $resp, true);
        $hits = $data['hits']['hits'] ?? [];
        if (!is_array($hits)) {
            return [];
        }

        $appUrl = rtrim((string) (\sfConfig::get('app_site_base_url') ?: 'https://psis.theahg.co.za'), '/');
        $out = [];
        foreach ($hits as $hit) {
            $src = $hit['_source'] ?? [];
            if (!is_array($src)) { continue; }
            $sourceId = (string) ($src['id'] ?? $hit['_id'] ?? '');
            if ($sourceId === '') { continue; }

            $i18n = is_array($src['i18n'][$culture] ?? null) ? $src['i18n'][$culture] : [];
            $title = (string) (
                $i18n['title']
                ?? $i18n['authorized_form_of_name']
                ?? $src['title']
                ?? 'Untitled'
            );

            $snippet = $this->extractHighlight($hit) ?? ($i18n['scope_and_content'] ?? $i18n['history'] ?? null);
            $slug = $src['slug'] ?? null;
            $url = $slug ? $appUrl . '/index.php/' . ltrim($slug, '/') : $appUrl;
            $score = isset($hit['_score']) ? (float) $hit['_score'] : 1.0;

            $out[] = new PeerSearchResult(
                sourceId: $sourceId,
                title: $title,
                snippet: is_string($snippet) ? strip_tags($snippet, '<mark>') : null,
                url: $url,
                peerType: self::PEER_TYPE,
                sourceBadge: 'Archived in AtoM',
                score: $this->normaliseScore($score),
                dedupeKey: $src['sp_item_id'] ?? null,
                date: $src['date'] ?? null,
                extras: [
                    'index'     => $hit['_index'] ?? null,
                    'reference' => $src['identifier'] ?? null,
                    'thumbnail' => $src['thumbnail_path'] ?? null,
                ],
            );
        }
        return $out;
    }

    private function extractHighlight(array $hit): ?string
    {
        $highlights = $hit['highlight'] ?? [];
        if (!is_array($highlights)) { return null; }
        foreach ($highlights as $frags) {
            if (is_array($frags) && !empty($frags[0]) && is_string($frags[0])) {
                return $frags[0];
            }
        }
        return null;
    }

    private function openSearchBase(): string
    {
        // Per-peer override, falls back to AtoM-wide setting
        return (string) ($this->config['base_url']
            ?? (\sfConfig::get('app_search_host_url') ?: 'http://localhost:9200'));
    }

    private function openSearchIndex(): string
    {
        $prefix = $this->config['index_prefix']
            ?? (\sfConfig::get('app_search_index_prefix') ?: 'archive');
        return $prefix . '_qubitinformationobject';
    }

    /** OpenSearch scores are unbounded above ~1 — log-scale clamp into 0..1. */
    private function normaliseScore(float $score): float
    {
        if ($score <= 0) { return 0.0; }
        return min(1.0, log10(1 + $score) / 2.0);
    }
}
