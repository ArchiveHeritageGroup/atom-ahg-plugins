<?php

namespace AhgResourceSync\Services;

use Illuminate\Database\Capsule\Manager as DB;

/**
 * ResourceSyncService — builds the four ResourceSync 1.1 Source documents as
 * sitemap-formatted XML strings.
 *
 * Ported from the Heratio ahg-resourcesync package (Laravel) to AtoM / PSIS
 * (Symfony 1.4 + atom-framework). Behaviour and data model are mirrored:
 *
 *   - SourceDescription : .well-known discovery file -> CapabilityList
 *   - CapabilityList    : advertises ResourceList + ChangeList
 *   - ResourceList      : full inventory of published archival records
 *   - ChangeList        : updates + tombstones within a configurable horizon
 *
 * Documents carry the ResourceSync `xmlns:rs` extension namespace alongside
 * the sitemap namespace, with rs:md / rs:ln supplying protocol metadata.
 *
 * Publication-status filter mirrors the OAI-PMH ListRecords shape exactly:
 * `information_object` joined to `object` (updated_at) and `status`
 * (type_id=158, status_id=160 = published), excluding the synthetic root by
 * requiring a non-null, non-zero parent_id. Tombstones come from
 * `oai_deleted_record` — the same table the OAI tombstone worker populates —
 * so ResourceSync and OAI report the same deletion set.
 *
 * Uses Illuminate\Database\Capsule\Manager (Laravel Query Builder) per AtoM
 * framework convention. All schema lookups are guarded so the endpoints
 * degrade gracefully on a fresh install (missing oai_deleted_record table or
 * oai_local_identifier column).
 */
class ResourceSyncService
{
    /** Sitemap base namespace. */
    public const NS_SITEMAP = 'http://www.sitemaps.org/schemas/sitemap/0.9';

    /** ResourceSync extension namespace (rs:md, rs:ln). */
    public const NS_RS = 'http://www.openarchives.org/rs/terms/';

    /** Capability URIs — normative values from the ResourceSync spec. */
    public const CAP_DESCRIPTION = 'description';
    public const CAP_CAPABILITYLIST = 'capabilitylist';
    public const CAP_RESOURCELIST = 'resourcelist';
    public const CAP_CHANGELIST = 'changelist';

    /** Default page size when neither setting nor config provides one. */
    public const DEFAULT_PAGE_SIZE = 1000;

    /** ChangeList horizon default — last 30 days of updates + tombstones. */
    public const DEFAULT_CHANGELIST_DAYS = 30;

    /** AtoM publication-status taxonomy IDs (QubitTerm). */
    private const STATUS_TYPE_PUBLICATION = 158;
    private const STATUS_PUBLISHED = 160;

    /**
     * Absolute base URL of this AtoM instance (no trailing slash).
     *
     * @var string
     */
    private $baseUrl;

    public function __construct(?string $baseUrl = null)
    {
        $this->baseUrl = rtrim($baseUrl ?? $this->detectBaseUrl(), '/');
    }

    // ------------------------------------------------------------------
    // Public document builders — return XML strings.
    // ------------------------------------------------------------------

    /**
     * SourceDescription document — discovery file pointing to the
     * CapabilityList.
     */
    public function sourceDescription(): string
    {
        $writer = $this->openWriter();
        $writer->startElement('urlset');
        $writer->writeAttribute('xmlns', self::NS_SITEMAP);
        $writer->writeAttribute('xmlns:rs', self::NS_RS);

        // describedby link to the operator-facing docs (aggregators ignore it).
        $writer->startElement('rs:ln');
        $writer->writeAttribute('rel', 'describedby');
        $writer->writeAttribute('href', $this->url('/'));
        $writer->endElement();

        // capability="description" declares the document type.
        $writer->startElement('rs:md');
        $writer->writeAttribute('capability', self::CAP_DESCRIPTION);
        $writer->endElement();

        // The one capability list this source exposes.
        $writer->startElement('url');
        $writer->writeElement('loc', $this->url('/resourcesync/capabilitylist.xml'));
        $writer->startElement('rs:md');
        $writer->writeAttribute('capability', self::CAP_CAPABILITYLIST);
        $writer->endElement();
        $writer->endElement(); // </url>

        $writer->endElement(); // </urlset>

        return $this->finish($writer);
    }

    /**
     * CapabilityList document — advertises the ResourceList + ChangeList.
     */
    public function capabilityList(): string
    {
        $writer = $this->openWriter();
        $writer->startElement('urlset');
        $writer->writeAttribute('xmlns', self::NS_SITEMAP);
        $writer->writeAttribute('xmlns:rs', self::NS_RS);

        // rel="up" back to the SourceDescription.
        $writer->startElement('rs:ln');
        $writer->writeAttribute('rel', 'up');
        $writer->writeAttribute('href', $this->url('/.well-known/resourcesync'));
        $writer->endElement();

        $writer->startElement('rs:md');
        $writer->writeAttribute('capability', self::CAP_CAPABILITYLIST);
        $writer->endElement();

        // ResourceList entry.
        $writer->startElement('url');
        $writer->writeElement('loc', $this->url('/resourcesync/resourcelist.xml'));
        $writer->startElement('rs:md');
        $writer->writeAttribute('capability', self::CAP_RESOURCELIST);
        $writer->endElement();
        $writer->endElement();

        // ChangeList entry.
        $writer->startElement('url');
        $writer->writeElement('loc', $this->url('/resourcesync/changelist.xml'));
        $writer->startElement('rs:md');
        $writer->writeAttribute('capability', self::CAP_CHANGELIST);
        $writer->endElement();
        $writer->endElement();

        $writer->endElement(); // </urlset>

        return $this->finish($writer);
    }

    /**
     * ResourceList document — full inventory of published archival records,
     * paginated via $page (1-indexed). Emits sitemap-style rel="next"/"prev"
     * links so aggregators can walk the chain.
     */
    public function resourceList(int $page = 1): string
    {
        $pageSize = $this->pageSize();
        $page = max(1, $page);
        $offset = ($page - 1) * $pageSize;

        $base = $this->publishedRecordQuery();
        $total = (clone $base)->count();
        $totalPages = max(1, (int) ceil($total / $pageSize));

        $records = $base->offset($offset)->limit($pageSize)->get();

        $writer = $this->openWriter();
        $writer->startElement('urlset');
        $writer->writeAttribute('xmlns', self::NS_SITEMAP);
        $writer->writeAttribute('xmlns:rs', self::NS_RS);

        $writer->startElement('rs:ln');
        $writer->writeAttribute('rel', 'up');
        $writer->writeAttribute('href', $this->url('/resourcesync/capabilitylist.xml'));
        $writer->endElement();

        $writer->startElement('rs:md');
        $writer->writeAttribute('capability', self::CAP_RESOURCELIST);
        $writer->writeAttribute('at', $this->isoNow());
        $writer->endElement();

        if ($page < $totalPages) {
            $writer->startElement('rs:ln');
            $writer->writeAttribute('rel', 'next');
            $writer->writeAttribute('href', $this->url('/resourcesync/resourcelist.xml?page='.($page + 1)));
            $writer->endElement();
        }
        if ($page > 1) {
            $writer->startElement('rs:ln');
            $writer->writeAttribute('rel', 'prev');
            $writer->writeAttribute('href', $this->url('/resourcesync/resourcelist.xml?page='.($page - 1)));
            $writer->endElement();
        }

        foreach ($records as $record) {
            $this->writeUrlEntry($writer, $record, null);
        }

        $writer->endElement(); // </urlset>

        return $this->finish($writer);
    }

    /**
     * ChangeList document — records updated or tombstoned within the
     * configured horizon (default 30 days). change="created"|"updated"|
     * "deleted" per entry. Tombstones share the OAI oai_deleted_record table.
     */
    public function changeList(int $page = 1): string
    {
        $pageSize = $this->pageSize();
        $page = max(1, $page);
        $offset = ($page - 1) * $pageSize;

        $horizonDays = $this->changelistDays();
        $cutoff = $this->cutoffDateTime($horizonDays);

        // Live record changes within the horizon.
        $changesBase = $this->publishedRecordQuery()
            ->where('o.updated_at', '>=', $cutoff);
        $changesTotal = (clone $changesBase)->count();

        // Tombstones within the horizon.
        $tombstonesTotal = $this->countTombstonesSince($cutoff);

        $grandTotal = $changesTotal + $tombstonesTotal;
        $totalPages = max(1, (int) ceil($grandTotal / $pageSize));

        // Single virtual list: live changes (by updated_at) then tombstones
        // (by deleted_at). The offset walks across the boundary.
        $liveSlice = [];
        $tombSlice = [];
        if ($offset < $changesTotal) {
            $liveLimit = min($pageSize, $changesTotal - $offset);
            $liveSlice = $changesBase
                ->orderBy('o.updated_at')
                ->orderBy('io.id')
                ->offset($offset)
                ->limit($liveLimit)
                ->get();

            $remaining = $pageSize - count($liveSlice);
            if ($remaining > 0 && $tombstonesTotal > 0) {
                $tombSlice = $this->getTombstonesSince($cutoff, 0, $remaining);
            }
        } else {
            $tombOffset = $offset - $changesTotal;
            $tombSlice = $this->getTombstonesSince($cutoff, $tombOffset, $pageSize);
        }

        $writer = $this->openWriter();
        $writer->startElement('urlset');
        $writer->writeAttribute('xmlns', self::NS_SITEMAP);
        $writer->writeAttribute('xmlns:rs', self::NS_RS);

        $writer->startElement('rs:ln');
        $writer->writeAttribute('rel', 'up');
        $writer->writeAttribute('href', $this->url('/resourcesync/capabilitylist.xml'));
        $writer->endElement();

        $writer->startElement('rs:md');
        $writer->writeAttribute('capability', self::CAP_CHANGELIST);
        $writer->writeAttribute('from', $this->isoDate($cutoff));
        $writer->writeAttribute('until', $this->isoNow());
        $writer->endElement();

        if ($page < $totalPages) {
            $writer->startElement('rs:ln');
            $writer->writeAttribute('rel', 'next');
            $writer->writeAttribute('href', $this->url('/resourcesync/changelist.xml?page='.($page + 1)));
            $writer->endElement();
        }
        if ($page > 1) {
            $writer->startElement('rs:ln');
            $writer->writeAttribute('rel', 'prev');
            $writer->writeAttribute('href', $this->url('/resourcesync/changelist.xml?page='.($page - 1)));
            $writer->endElement();
        }

        // Live changes — created vs updated heuristic.
        foreach ($liveSlice as $record) {
            $change = (! empty($record->created_at) && ! empty($record->updated_at)
                && $record->created_at === $record->updated_at)
                ? 'created'
                : 'updated';
            $this->writeUrlEntry($writer, $record, $change);
        }

        // Tombstones — change="deleted".
        foreach ($tombSlice as $tomb) {
            $this->writeTombstoneEntry($writer, $tomb);
        }

        $writer->endElement(); // </urlset>

        return $this->finish($writer);
    }

    // ------------------------------------------------------------------
    // Query helpers
    // ------------------------------------------------------------------

    /**
     * Base query for published archival records. Mirrors the OAI shape
     * (status type_id=158, status_id=160, non-root parent_id).
     *
     * @return \Illuminate\Database\Query\Builder
     */
    private function publishedRecordQuery()
    {
        $hasOaiCol = $this->hasOaiLocalIdentifier();

        $select = [
            'io.id',
            'io.identifier',
            'o.created_at',
            'o.updated_at',
            'slug.slug',
        ];
        if ($hasOaiCol) {
            $select[] = 'io.oai_local_identifier';
        }

        return DB::table('information_object as io')
            ->join('object as o', 'io.id', '=', 'o.id')
            ->join('status as st', function ($join) {
                $join->on('io.id', '=', 'st.object_id')
                    ->where('st.type_id', '=', self::STATUS_TYPE_PUBLICATION);
            })
            ->leftJoin('slug', 'slug.object_id', '=', 'io.id')
            ->where('st.status_id', '=', self::STATUS_PUBLISHED)
            ->whereNotNull('io.parent_id')
            ->where('io.parent_id', '!=', 0)
            ->select($select)
            ->orderBy('io.id');
    }

    /**
     * Page of tombstones whose deleted_at falls within the horizon window.
     *
     * @return array<int, object>
     */
    private function getTombstonesSince(string $cutoff, int $offset, int $limit): array
    {
        if (! $this->hasTable('oai_deleted_record')) {
            return [];
        }

        return DB::table('oai_deleted_record')
            ->where('deleted_at', '>=', $cutoff)
            ->orderBy('deleted_at')
            ->orderBy('oai_local_identifier')
            ->offset($offset)
            ->limit($limit)
            ->select('oai_local_identifier', 'deleted_at')
            ->get()
            ->all();
    }

    /**
     * Count tombstones within the horizon window.
     */
    private function countTombstonesSince(string $cutoff): int
    {
        if (! $this->hasTable('oai_deleted_record')) {
            return 0;
        }

        return (int) DB::table('oai_deleted_record')
            ->where('deleted_at', '>=', $cutoff)
            ->count();
    }

    // ------------------------------------------------------------------
    // XML emission helpers
    // ------------------------------------------------------------------

    /**
     * Write a sitemap <url> entry for a live record. Optional change attribute
     * (used by ChangeList only — null on ResourceList).
     */
    private function writeUrlEntry(\XMLWriter $writer, $record, ?string $change): void
    {
        $loc = $this->locFor($record);

        $writer->startElement('url');
        $writer->writeElement('loc', $loc);
        if (! empty($record->updated_at)) {
            $writer->writeElement('lastmod', $this->isoDate($record->updated_at));
        }
        $writer->startElement('rs:md');
        if ($change !== null) {
            $writer->writeAttribute('change', $change);
        }
        $writer->writeAttribute('datetime', $this->isoDate($record->updated_at ?? null));
        $writer->endElement();
        $writer->endElement();
    }

    /**
     * Write a sitemap <url> entry for a tombstone. change="deleted".
     */
    private function writeTombstoneEntry(\XMLWriter $writer, $tomb): void
    {
        $loc = $this->url('/informationobject/by-oai/'.((int) $tomb->oai_local_identifier));

        $writer->startElement('url');
        $writer->writeElement('loc', $loc);
        $writer->writeElement('lastmod', $this->isoDate($tomb->deleted_at));
        $writer->startElement('rs:md');
        $writer->writeAttribute('change', 'deleted');
        $writer->writeAttribute('datetime', $this->isoDate($tomb->deleted_at));
        $writer->endElement();
        $writer->endElement();
    }

    /**
     * Resolve the canonical archival-record URL. Uses the slug when present;
     * falls back to an oai-id route for pre-slug records.
     */
    private function locFor($record): string
    {
        if (! empty($record->slug)) {
            return $this->url('/'.$record->slug);
        }

        $oaiId = isset($record->oai_local_identifier) ? (int) $record->oai_local_identifier : 0;

        return $this->url('/informationobject/by-oai/'.$oaiId);
    }

    // ------------------------------------------------------------------
    // Configuration / settings resolution
    // ------------------------------------------------------------------

    /**
     * Resolve the per-document page size. Honours the OAI resumption-token
     * limit when set so operators tune one knob; falls back to the default.
     */
    private function pageSize(): int
    {
        $oaiLimit = null;
        try {
            $row = DB::table('setting as s')
                ->join('setting_i18n as si', 'si.id', '=', 's.id')
                ->where('s.scope', 'oai')
                ->where('s.name', 'resumption_token_limit')
                ->where('si.culture', 'en')
                ->value('si.value');
            if ($row !== null && $row !== '') {
                $oaiLimit = (int) $row;
            }
        } catch (\Throwable $e) {
            $oaiLimit = null;
        }

        if ($oaiLimit !== null && $oaiLimit > 0) {
            return $oaiLimit;
        }

        $cfg = $this->intSetting('resourcesync', 'page_size', self::DEFAULT_PAGE_SIZE);

        return $cfg > 0 ? $cfg : self::DEFAULT_PAGE_SIZE;
    }

    /**
     * ChangeList horizon in days, from the resourcesync setting group.
     */
    private function changelistDays(): int
    {
        $days = $this->intSetting('resourcesync', 'changelist_days', self::DEFAULT_CHANGELIST_DAYS);

        return $days > 0 ? $days : self::DEFAULT_CHANGELIST_DAYS;
    }

    /**
     * Read an integer value from the ahg_settings table (group + key) with a
     * fallback default. Safe when ahg_settings or the row is absent.
     */
    private function intSetting(string $group, string $key, int $default): int
    {
        try {
            if (! $this->hasTable('ahg_settings')) {
                return $default;
            }
            $val = DB::table('ahg_settings')
                ->where('setting_group', $group)
                ->where('setting_key', $key)
                ->value('setting_value');
            if ($val !== null && $val !== '') {
                return (int) $val;
            }
        } catch (\Throwable $e) {
            // fall through
        }

        return $default;
    }

    // ------------------------------------------------------------------
    // Schema guards
    // ------------------------------------------------------------------

    /**
     * Whether information_object carries the oai_local_identifier column.
     * Cached for the lifetime of the service instance.
     *
     * @var bool|null
     */
    private $hasOaiCol;

    private function hasOaiLocalIdentifier(): bool
    {
        if ($this->hasOaiCol !== null) {
            return $this->hasOaiCol;
        }
        try {
            $this->hasOaiCol = DB::schema()->hasColumn('information_object', 'oai_local_identifier');
        } catch (\Throwable $e) {
            $this->hasOaiCol = false;
        }

        return $this->hasOaiCol;
    }

    /**
     * @var array<string, bool>
     */
    private $tableCache = [];

    private function hasTable(string $table): bool
    {
        if (array_key_exists($table, $this->tableCache)) {
            return $this->tableCache[$table];
        }
        try {
            $this->tableCache[$table] = DB::schema()->hasTable($table);
        } catch (\Throwable $e) {
            $this->tableCache[$table] = false;
        }

        return $this->tableCache[$table];
    }

    // ------------------------------------------------------------------
    // URL + XML utilities
    // ------------------------------------------------------------------

    /**
     * Build an absolute URL from a root-relative path.
     */
    private function url(string $path): string
    {
        if ($path === '' || $path === '/') {
            return $this->baseUrl.'/';
        }

        return $this->baseUrl.'/'.ltrim($path, '/');
    }

    /**
     * Detect the public base URL from the current request, falling back to the
     * AtoM `siteBaseUrl` setting and finally to an empty (relative) origin.
     */
    private function detectBaseUrl(): string
    {
        // 1. Live request scheme + host.
        if (! empty($_SERVER['HTTP_HOST'])) {
            $https = (! empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
                || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
                || (isset($_SERVER['SERVER_PORT']) && (int) $_SERVER['SERVER_PORT'] === 443);

            return ($https ? 'https://' : 'http://').$_SERVER['HTTP_HOST'];
        }

        // 2. AtoM siteBaseUrl setting (CLI context).
        try {
            $row = DB::table('setting as s')
                ->join('setting_i18n as si', 'si.id', '=', 's.id')
                ->where('s.name', 'siteBaseUrl')
                ->where('si.culture', 'en')
                ->value('si.value');
            if (! empty($row)) {
                return rtrim((string) $row, '/');
            }
        } catch (\Throwable $e) {
            // fall through
        }

        return '';
    }

    /**
     * Open a buffered, indented XMLWriter with the XML declaration.
     */
    private function openWriter(): \XMLWriter
    {
        $w = new \XMLWriter;
        $w->openMemory();
        $w->setIndent(true);
        $w->setIndentString('  ');
        $w->startDocument('1.0', 'UTF-8');

        return $w;
    }

    /**
     * Finish the document and return the XML string.
     */
    private function finish(\XMLWriter $writer): string
    {
        $writer->endDocument();

        return $writer->outputMemory(true);
    }

    /**
     * ISO 8601 UTC timestamp for the current moment.
     */
    private function isoNow(): string
    {
        return gmdate('Y-m-d\TH:i:s\Z');
    }

    /**
     * ISO 8601 UTC timestamp for an arbitrary input (MySQL datetime,
     * DateTime, or null). Returns the current moment when input is empty.
     */
    private function isoDate($date): string
    {
        if ($date === null || $date === '') {
            return $this->isoNow();
        }
        if ($date instanceof \DateTimeInterface) {
            return gmdate('Y-m-d\TH:i:s\Z', $date->getTimestamp());
        }
        $ts = strtotime((string) $date);
        if ($ts === false) {
            return $this->isoNow();
        }

        return gmdate('Y-m-d\TH:i:s\Z', $ts);
    }

    /**
     * Compute the horizon cutoff as a MySQL datetime string N days ago.
     */
    private function cutoffDateTime(int $days): string
    {
        return gmdate('Y-m-d H:i:s', time() - ($days * 86400));
    }
}
