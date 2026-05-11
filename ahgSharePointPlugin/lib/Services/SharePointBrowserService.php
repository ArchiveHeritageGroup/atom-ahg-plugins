<?php

namespace AtomExtensions\SharePoint\Services;

/**
 * SharePointBrowserService — pure Graph wrapper used by both
 *   - the ingest wizard "From SharePoint" picker (AJAX)
 *   - SharePointAutoIngestService (cron-driven scanner)
 *
 * Methods return plain associative arrays so callers can serialize
 * straight to JSON without intermediate DTOs.
 *
 * Listing endpoints page automatically up to MAX_PAGES (5000 items each)
 * — SharePoint UIs rarely list more, and the wizard picker streams
 * folder-by-folder anyway.
 *
 * @phase 2 (v2 ingest plan, step 2)
 */
class SharePointBrowserService
{
    private const MAX_PAGES = 20;

    public function __construct(
        private GraphClientService $graph = new GraphClientService(),
    ) {
    }

    /**
     * List SharePoint sites visible to the tenant.
     *
     * @return array<int, array{id:string,displayName:string,name:?string,webUrl:string,description:?string}>
     */
    public function listSites(int $tenantId, ?string $search = null): array
    {
        $query = $search !== null && $search !== ''
            ? '/sites?search=' . rawurlencode($search) . '&$top=200'
            : '/sites?search=*&$top=200';

        return $this->collect($tenantId, $query, fn (array $row) => [
            'id' => (string) ($row['id'] ?? ''),
            'displayName' => (string) ($row['displayName'] ?? ($row['name'] ?? '')),
            'name' => $row['name'] ?? null,
            'webUrl' => (string) ($row['webUrl'] ?? ''),
            'description' => $row['description'] ?? null,
        ]);
    }

    /**
     * List drives (document libraries) inside a site.
     *
     * @return array<int, array{id:string,name:string,driveType:?string,webUrl:string}>
     */
    public function listDrives(int $tenantId, string $siteId): array
    {
        $siteId = rawurlencode($siteId);
        return $this->collect($tenantId, "/sites/{$siteId}/drives?\$top=200", fn (array $row) => [
            'id' => (string) ($row['id'] ?? ''),
            'name' => (string) ($row['name'] ?? ''),
            'driveType' => $row['driveType'] ?? null,
            'webUrl' => (string) ($row['webUrl'] ?? ''),
        ]);
    }

    /**
     * List immediate children of a folder. Pass itemId='root' for the drive root.
     *
     * @return array<int, array{
     *   id:string, name:string, isFolder:bool, isFile:bool,
     *   size:int, mimeType:?string, webUrl:string, etag:?string,
     *   lastModifiedDateTime:?string, createdDateTime:?string,
     *   parentReference:?array
     * }>
     */
    public function listChildren(int $tenantId, string $driveId, string $itemId = 'root'): array
    {
        $driveId = rawurlencode($driveId);
        $itemId = rawurlencode($itemId);
        // $select forces Graph to return retentionLabel (omitted from default response).
        // Note: any field we map in mapDriveItem() must be included here.
        $select = 'id,name,size,file,folder,eTag,cTag,webUrl,lastModifiedDateTime,createdDateTime,parentReference,retentionLabel';
        return $this->collect(
            $tenantId,
            "/drives/{$driveId}/items/{$itemId}/children?\$top=500&\$select=" . rawurlencode($select),
            fn (array $row) => $this->mapDriveItem($row),
        );
    }

    /**
     * Download a single driveItem to local disk. Returns the absolute path.
     *
     * Caller is responsible for picking an appropriate destination (typically
     * uploads/ingest/{session_id}/{name}).
     */
    public function downloadItem(int $tenantId, string $driveId, string $itemId, string $destPath): string
    {
        $this->graph->downloadDriveItemByDriveId($tenantId, $driveId, $itemId, $destPath);
        return $destPath;
    }

    /**
     * SharePoint internal column names that the SP "List settings → Columns"
     * admin page hides. Graph returns all of these as columnGroup='Custom
     * Columns' with hidden=false, so neither field is usable for filtering.
     * We match SP's own admin behavior with a static blacklist.
     */
    private const SP_SYSTEM_COLUMNS = [
        'ID', 'ContentType', 'DocIcon', 'FileLeafRef',
        '_ColorTag', 'ComplianceAssetId',
        'LinkFilename', 'LinkFilenameNoMenu', 'LinkFilename2',
        'LinkTitle', 'LinkTitleNoMenu',
        '_CopySource', '_CheckinComment',
        'FileSizeDisplay', 'ItemChildCount', 'FolderChildCount',
        '_ComplianceFlags', '_ComplianceTag', '_ComplianceTagWrittenTime',
        '_ComplianceTagUserId', '_IsRecord',
        '_CommentCount', '_LikeCount', '_DisplayName',
        'AppAuthor', 'AppEditor', 'Edit',
        '_UIVersionString', 'ParentVersionString', 'ParentLeafName',
        'SelectTitle', 'Order', 'GUID', 'WorkflowVersion',
        '_HasCopyDestinations', '_ModerationStatus', '_ModerationComments',
        '_Level', '_IsCurrentVersion', 'ItemChildCount', 'FolderChildCount',
    ];

    /**
     * List the SharePoint list columns that back a drive (a Documents library
     * IS a list under the hood). Used by the mapping editor so curators see
     * the actual SP fields instead of typing them from memory.
     *
     * Returns: [{name, displayName, type, indexed, readOnly, hidden, isSystem, columnGroup}, ...]
     *
     * `isSystem=true` matches columns SP's admin UI hides (a curated
     * blacklist — Graph itself reports them as visible/non-system).
     */
    public function listColumns(int $tenantId, string $driveId): array
    {
        $driveId = rawurlencode($driveId);
        $resp = $this->graph->get($tenantId, "/drives/{$driveId}/list/columns?\$top=200");
        $systemLookup = array_flip(self::SP_SYSTEM_COLUMNS);
        $out = [];
        foreach (($resp['value'] ?? []) as $col) {
            if (!is_array($col)) {
                continue;
            }
            $type = 'text';
            foreach (['text', 'number', 'dateTime', 'boolean', 'choice', 'lookup', 'personOrGroup', 'hyperlinkOrPicture', 'currency', 'note'] as $t) {
                if (isset($col[$t])) {
                    $type = $t;
                    break;
                }
            }
            $name = (string) ($col['name'] ?? '');
            $columnGroup = (string) ($col['columnGroup'] ?? '');
            $hidden = (bool) ($col['hidden'] ?? false);
            $isSystem = $hidden
                || isset($systemLookup[$name])
                || $columnGroup === '_Hidden';
            $out[] = [
                'name' => $name,
                'displayName' => (string) ($col['displayName'] ?? $name),
                'type' => $type,
                'indexed' => (bool) ($col['indexed'] ?? false),
                'readOnly' => (bool) ($col['readOnly'] ?? false),
                'hidden' => $hidden,
                'isSystem' => $isSystem,
                'columnGroup' => $columnGroup,
            ];
        }
        return $out;
    }

    /**
     * Full driveItem metadata (incl. listItem fields if requested).
     *
     * @return array<string, mixed>
     */
    public function getMetadata(int $tenantId, string $driveId, string $itemId, bool $expandListItem = false): array
    {
        $driveIdEnc = rawurlencode($driveId);
        $itemIdEnc = rawurlencode($itemId);
        $path = "/drives/{$driveIdEnc}/items/{$itemIdEnc}";
        if ($expandListItem) {
            $path .= '?$expand=listItem(expand=fields)';
        }
        $raw = $this->graph->get($tenantId, $path);
        return $this->mapDriveItem($raw) + ['_raw' => $raw];
    }

    // ---- helpers ----

    /**
     * Collect paginated Graph results (follows @odata.nextLink) and map each row.
     *
     * @param callable(array):array $mapper
     * @return array<int, array>
     */
    private function collect(int $tenantId, string $initialPath, callable $mapper): array
    {
        $out = [];
        $path = $initialPath;
        for ($page = 0; $page < self::MAX_PAGES; ++$page) {
            $resp = $this->graph->get($tenantId, $path);
            foreach (($resp['value'] ?? []) as $row) {
                if (is_array($row)) {
                    $out[] = $mapper($row);
                }
            }
            $next = $resp['@odata.nextLink'] ?? null;
            if (!is_string($next) || $next === '') {
                break;
            }
            // nextLink is absolute — strip the base so GraphClientService can re-add it.
            $path = $this->stripGraphBase($next);
        }
        return $out;
    }

    private function stripGraphBase(string $absoluteUrl): string
    {
        // Graph nextLink looks like https://graph.microsoft.com/v1.0/sites/...?$skiptoken=...
        $parsed = parse_url($absoluteUrl);
        $relative = ($parsed['path'] ?? '/') . (isset($parsed['query']) ? '?' . $parsed['query'] : '');
        // strip the version prefix (/v1.0 or /beta) — GraphClientService::resolveBase already includes it.
        $relative = preg_replace('#^/(v1\.0|beta)#', '', $relative);
        return $relative === '' ? '/' : $relative;
    }

    /**
     * Normalize a Graph driveItem to the shape our UI expects.
     */
    private function mapDriveItem(array $row): array
    {
        $isFolder = isset($row['folder']);
        $isFile = isset($row['file']);
        $retentionLabel = null;
        $retentionLabelAppliedAt = null;
        if (isset($row['retentionLabel']) && is_array($row['retentionLabel'])) {
            $retentionLabel = isset($row['retentionLabel']['name']) ? (string) $row['retentionLabel']['name'] : null;
            $retentionLabelAppliedAt = $row['retentionLabel']['labelAppliedDateTime'] ?? null;
        }
        return [
            'id' => (string) ($row['id'] ?? ''),
            'name' => (string) ($row['name'] ?? ''),
            'isFolder' => $isFolder,
            'isFile' => $isFile,
            'size' => (int) ($row['size'] ?? 0),
            'mimeType' => $row['file']['mimeType'] ?? null,
            'webUrl' => (string) ($row['webUrl'] ?? ''),
            'etag' => $row['eTag'] ?? ($row['cTag'] ?? null),
            'lastModifiedDateTime' => $row['lastModifiedDateTime'] ?? null,
            'createdDateTime' => $row['createdDateTime'] ?? null,
            'parentReference' => $row['parentReference'] ?? null,
            'childCount' => $isFolder ? (int) ($row['folder']['childCount'] ?? 0) : null,
            'retentionLabel' => $retentionLabel,
            'retentionLabelAppliedAt' => $retentionLabelAppliedAt,
        ];
    }
}
