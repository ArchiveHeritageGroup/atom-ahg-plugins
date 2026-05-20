<?php

/**
 * SharePointGraphConnector — federation peer implemented against the
 * Microsoft Graph search API for the AtoM-side ahgFederationPlugin.
 *
 * Port of the Heratio Laravel implementation (May 2026). Uses the existing
 * AtomExtensions\SharePoint\Services\GraphClientService (which is wired to
 * the sharepoint_tenant table and handles app-only token acquisition + Graph
 * HTTP calls).
 *
 * Peer config (JSON in federation_peer.config):
 *   {
 *     "tenant_id": 1,                       // FK to sharepoint_tenant.id (NOT the AAD tenant GUID)
 *     "default_site_ids": ["site-guid-1"],  // optional KQL scope
 *     "default_drive_ids": ["drive-id-1"],  // optional KQL scope
 *     "max_results_per_query": 50
 *   }
 *
 * Lives at /opt/ahg-sp-integration/F3/atom/lib/Connectors/ per the SP NO-PUSH
 * policy AND is mirrored to atom-ahg-plugins/ahgFederationPlugin/lib/Connectors/
 * on the live host. Both copies must be kept in sync. NEVER commit either.
 *
 * @phase F3-AtoM-port (2026-05-16)
 */

namespace AhgFederation\Connectors;

final class SharePointGraphConnector implements PeerConnector
{
    public const PEER_TYPE = 'sharepoint_graph_search';

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
        return in_array($capability, ['full_text_search', 'metadata_filter', 'date_range'], true);
    }

    public function search(string $query, array $filters = [], int $limit = 50): array
    {
        $tenantId = (int) ($this->config['tenant_id'] ?? 0);
        if ($tenantId <= 0) {
            return [];
        }

        $configuredLimit = (int) ($this->config['max_results_per_query'] ?? $limit);
        $effectiveLimit = max(1, min($limit, $configuredLimit, 50));

        $kql = $this->buildKql($query, $filters);
        $body = [
            'requests' => [
                [
                    'entityTypes' => ['driveItem', 'listItem'],
                    'query'       => ['queryString' => $kql],
                    'from'        => 0,
                    'size'        => $effectiveLimit,
                ],
            ],
        ];

        $graph = $this->getGraphClient();
        if ($graph === null) {
            error_log('SharePointGraphConnector: GraphClientService not available');
            return [];
        }

        try {
            $response = $graph->post($tenantId, '/search/query', $body);
        } catch (\Throwable $e) {
            error_log(sprintf('SharePointGraphConnector search failed: tenant=%d err=%s', $tenantId, $e->getMessage()));
            return [];
        }

        $hits = $this->extractHits($response);
        if (empty($hits)) {
            return [];
        }

        $maxRank = max(array_column($hits, 'rank') ?: [1]);

        $out = [];
        foreach ($hits as $hit) {
            $resource = $hit['resource'] ?? [];
            if (!is_array($resource)) { continue; }
            $sourceId = (string) ($resource['id'] ?? '');
            if ($sourceId === '') { continue; }

            $title = (string) ($resource['name'] ?? $resource['title'] ?? $resource['displayName'] ?? 'Untitled');
            $summary = $hit['summary'] ?? null;
            if (is_string($summary)) {
                $summary = strip_tags($summary, '<mark>');
            }
            $url = (string) ($resource['webUrl'] ?? '');
            $modified = $resource['lastModifiedDateTime']
                ?? $resource['fileSystemInfo']['lastModifiedDateTime']
                ?? null;

            $rank = isset($hit['rank']) ? (int) $hit['rank'] : 1;
            $score = $maxRank > 0 ? max(0.0, 1.0 - ($rank - 1) / max(1, $maxRank)) : 1.0;

            $siteId = $resource['parentReference']['siteId'] ?? '';
            $badge = $siteId !== ''
                ? sprintf('Active in SharePoint · %s', $this->siteLabel($siteId))
                : 'Active in SharePoint';

            $out[] = new PeerSearchResult(
                sourceId: $sourceId,
                title: $title,
                snippet: is_string($summary) ? $summary : null,
                url: $url,
                peerType: self::PEER_TYPE,
                sourceBadge: $badge,
                score: $score,
                dedupeKey: $sourceId,
                date: is_string($modified) ? $modified : null,
                extras: [
                    'tenant_id' => $tenantId,
                    'site_id'   => $resource['parentReference']['siteId']  ?? null,
                    'drive_id'  => $resource['parentReference']['driveId'] ?? null,
                    'item_id'   => $resource['id']                          ?? null,
                    'mime_type' => $resource['file']['mimeType']            ?? null,
                    'size_bytes'=> $resource['size']                        ?? null,
                ],
            );
        }
        return $out;
    }

    /**
     * Resolve the AtoM-side GraphClientService. Loaded lazily so the connector
     * doesn't blow up at file-load time when ahgSharePointPlugin isn't installed.
     */
    private function getGraphClient(): ?object
    {
        $class = '\\AtomExtensions\\SharePoint\\Services\\GraphClientService';
        if (!class_exists($class)) {
            // The plugin's autoloader may not be registered yet — try require_once
            $candidates = [
                \sfConfig::get('sf_plugins_dir') . '/ahgSharePointPlugin/lib/Services/GraphClientService.php',
                '/usr/share/nginx/archive/atom-ahg-plugins/ahgSharePointPlugin/lib/Services/GraphClientService.php',
            ];
            foreach ($candidates as $p) {
                if (is_file($p)) {
                    require_once $p;
                    break;
                }
            }
        }
        if (!class_exists($class)) {
            return null;
        }
        // GraphClientService expects a GraphTokenCache + optional GraphTokenValidator —
        // load them then construct.
        $tokenCacheClass = '\\AtomExtensions\\SharePoint\\Services\\GraphTokenCache';
        if (!class_exists($tokenCacheClass)) {
            $cachePath = \sfConfig::get('sf_plugins_dir') . '/ahgSharePointPlugin/lib/Services/GraphTokenCache.php';
            if (is_file($cachePath)) {
                require_once $cachePath;
            }
        }
        if (!class_exists($tokenCacheClass)) {
            return null;
        }
        try {
            $cache = new $tokenCacheClass();
            return new $class($cache);
        } catch (\Throwable $e) {
            error_log('SharePointGraphConnector: failed to construct GraphClient: ' . $e->getMessage());
            return null;
        }
    }

    private function buildKql(string $query, array $filters): string
    {
        $clauses = [trim($query) !== '' ? '(' . $query . ')' : '*'];

        $siteIds = (array) ($this->config['default_site_ids'] ?? []);
        if (!empty($siteIds)) {
            $siteClauses = array_map(
                static fn ($s) => 'siteId:"' . str_replace('"', '', (string) $s) . '"',
                $siteIds,
            );
            $clauses[] = '(' . implode(' OR ', $siteClauses) . ')';
        }

        $driveIds = (array) ($this->config['default_drive_ids'] ?? []);
        if (!empty($driveIds)) {
            $driveClauses = array_map(
                static fn ($d) => 'driveId:"' . str_replace('"', '', (string) $d) . '"',
                $driveIds,
            );
            $clauses[] = '(' . implode(' OR ', $driveClauses) . ')';
        }

        if (!empty($filters['date_range']['from'])) {
            $clauses[] = 'Modified>=' . substr((string) $filters['date_range']['from'], 0, 10);
        }
        if (!empty($filters['date_range']['to'])) {
            $clauses[] = 'Modified<=' . substr((string) $filters['date_range']['to'], 0, 10);
        }

        return implode(' AND ', $clauses);
    }

    private function extractHits(array $response): array
    {
        $values = $response['value'] ?? [];
        if (!is_array($values)) {
            return [];
        }
        $all = [];
        foreach ($values as $value) {
            $containers = $value['hitsContainers'] ?? [];
            if (!is_array($containers)) { continue; }
            foreach ($containers as $container) {
                $hits = $container['hits'] ?? [];
                if (is_array($hits)) {
                    foreach ($hits as $hit) {
                        if (is_array($hit)) {
                            $all[] = $hit;
                        }
                    }
                }
            }
        }
        return $all;
    }

    private function siteLabel(string $siteId): string
    {
        // siteId format: {hostname},{site-guid},{web-guid}. Surface the hostname.
        $parts = explode(',', $siteId, 2);
        return $parts[0] !== '' ? $parts[0] : $siteId;
    }
}
