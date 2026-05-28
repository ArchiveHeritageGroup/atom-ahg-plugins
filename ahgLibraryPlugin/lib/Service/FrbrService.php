<?php

declare(strict_types=1);

/**
 * FrbrService
 *
 * Generates FRBR work-level keys for library items and clusters search results
 * by work to show a single representative card with all manifestations.
 *
 * Algorithm:
 *   work_key = SHA-256( normalised_title | creator_last_name | isbn13_base10 )
 *   First 20 chars of the hash serve as the work identifier.
 *
 * @package    ahgLibraryPlugin
 * @subpackage Service
 */

use Illuminate\Database\Capsule\Manager as DB;
use Monolog\Logger;
use Monolog\Handler\RotatingFileHandler;

class FrbrService
{
    protected static ?FrbrService $instance = null;
    protected Logger $logger;

    public function __construct()
    {
        $this->initLogger();
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    protected function initLogger(): void
    {
        $this->logger = new Logger('library.frbr');
        $logPath = \sfConfig::get('sf_log_dir', '/tmp') . '/library.log';
        $this->logger->pushHandler(
            new RotatingFileHandler($logPath, 30, Logger::DEBUG)
        );
    }

    // ========================================================================
    // WORK KEY GENERATION
    // ========================================================================

    /**
     * Generate a stable FRBR work key for an item.
     *
     * Canonical inputs (in priority order):
     *   1. ISBN-13 (base 10, stripped of hyphens + check digit)
     *   2. ISSN (base form)
     *   3. LCCN
     *
     * Normalised inputs:
     *   - title: lower-case, strip leading articles, collapse whitespace,
     *            strip punctuation except hyphens
     *   - creator: lower-case, take last word (family name), strip punctuation
     *
     * Returns NULL if title is empty AND none of isbn/issn/lccn are present.
     */
    public function generateWorkKey(
        ?string $title = null,
        ?string $creatorName = null,
        ?string $isbn = null,
        ?string $issn = null,
        ?string $lccn = null
    ): ?string {

        $parts = [];

        // Priority 1: ISBN-13 normalised (strip hyphens + check digit)
        if (!empty($isbn)) {
            $clean = preg_replace('/[^0-9]/', '', $isbn);
            if (strlen($clean) >= 12) {
                // Use first 12 digits (strip check digit) for base-10 ISBN-13 grouping
                $parts[] = 'isbn:' . substr($clean, 0, 12);
            }
        }

        // Priority 2: ISSN base form (strip hyphen)
        if (!empty($issn)) {
            $parts[] = 'issn:' . preg_replace('/[^0-9X]/', '', strtoupper($issn));
        }

        // Priority 3: LCCN (normalised — strip spaces and hyphens)
        if (!empty($lccn)) {
            $parts[] = 'lccn:' . preg_replace('/[^A-Z0-9]/', '', strtoupper($lccn));
        }

        // Title (required fallback when no identifier)
        if (!empty($title)) {
            $normalisedTitle = $this->normaliseTitle($title);
            $parts[] = 'title:' . $normalisedTitle;

            // Creator last name
            if (!empty($creatorName)) {
                $lastName = $this->extractLastName($creatorName);
                if ($lastName) {
                    $parts[] = 'creator:' . $lastName;
                }
            }
        }

        if (empty($parts)) {
            return null;
        }

        // SHA-256 → first 20 chars: collision-resistant, fits in VARCHAR(64)
        return substr(hash('sha256', implode('|', $parts), false), 0, 20);
    }

    /**
     * Normalise a title per FRBR display-level normalisation rules:
     *   - lower-case
     *   - strip leading articles (the / a / an) and ordinals (1st / 2nd / 3rd…)
     *   - collapse internal whitespace to one space
     *   - strip punctuation except hyphens and apostrophes
     *   - strip trailing " : subtitle" portion
     */
    public function normaliseTitle(string $title): string
    {
        $t = mb_strtolower(trim($title));

        // Strip leading articles
        $t = preg_replace('/^(the|a|an)\s+/i', '', $t);

        // Strip leading ordinals: 1st, 2nd, 3rd, 4th, 21st, 22nd …
        $t = preg_replace('/^\d{1,2}(st|nd|rd|th)\s+/i', '', $t);

        // Strip subtitle after colon
        $t = explode(':', $t)[0];

        // Collapse whitespace
        $t = preg_replace('/\s+/', ' ', $t);

        // Strip punctuation except hyphens and apostrophes
        $t = preg_replace('/[^\p{L}\p{N}\s\-\']/u', '', $t);

        return trim($t);
    }

    /**
     * Extract the last word (family name) from a creator display string.
     * Handles "Smith, John", "John Smith", "Smith Jr., John", "de la Fontaine, Marie".
     */
    public function extractLastName(?string $name): ?string
    {
        if (empty($name)) {
            return null;
        }
        $name = trim($name);

        // Comma-separated: "Smith, John" → "smith"
        if (strpos($name, ',') !== false) {
            $segments = array_map('trim', explode(',', $name));
            $family = $segments[0];
        } else {
            // Space-separated: "John Smith" → last token
            $tokens = preg_split('/\s+/', $name);
            $family = end($tokens);
        }

        // Strip trailing suffixes: Jr., Sr., III, PhD etc.
        $family = preg_replace('/,?\s*(jr|sr|iii|ii|iv|esq|phd|md)$/i', '', $family);

        // Normalise: lower-case, strip punctuation, collapse whitespace
        $family = mb_strtolower(trim($family));
        $family = preg_replace("/[^a-zàâäéèêëïîôùûüÿçœæ]/u", '', $family);

        return $family !== '' ? $family : null;
    }

    /**
     * Compute and store frbr_work_key for a single library_item row.
     */
    public function computeAndStoreWorkKey(int $libraryItemId): ?string
    {
        $item = DB::table('library_item')
            ->where('id', $libraryItemId)
            ->select('title', 'isbn', 'issn', 'lccn')
            ->first();

        if (!$item) {
            return null;
        }

        // Primary creator
        $creator = DB::table('library_item_creator')
            ->where('library_item_id', $libraryItemId)
            ->where('is_primary', 1)
            ->value('name');

        $workKey = $this->generateWorkKey(
            $item->title ?? null,
            $creator,
            $item->isbn ?? null,
            $item->issn ?? null,
            $item->lccn ?? null
        );

        if ($workKey !== null) {
            DB::table('library_item')
                ->where('id', $libraryItemId)
                ->update([
                    'frbr_work_key' => $workKey,
                    'updated_at'    => date('Y-m-d H:i:s'),
                ]);
        }

        return $workKey;
    }

    // ========================================================================
    // BATCH BACKFILL
    // ========================================================================

    /**
     * Backfill frbr_work_key for all library_items that lack one.
     * Generator yielding batch progress arrays so the caller can report.
     *
     * @param int $batchSize Rows per MySQL UPDATE round-trip
     * @return \Generator<array> yields ['done' => int, 'total' => int, 'batches' => int]
     */
    public function backfillWorkKeys(int $batchSize = 500): \Generator
    {
        $total = DB::table('library_item')
            ->where(function ($q) {
                $q->whereNull('frbr_work_key')
                  ->orWhere('frbr_work_key', '');
            })
            ->count();

        $batches = (int) ceil($total / $batchSize);
        $done = 0;

        for ($batch = 0; $batch < $batches; $batch++) {
            $items = DB::table('library_item')
                ->where(function ($q) {
                    $q->whereNull('frbr_work_key')
                      ->orWhere('frbr_work_key', '');
                })
                ->select('id', 'isbn', 'issn', 'lccn')
                ->limit($batchSize)
                ->get()
                ->all();

            if (empty($items)) {
                break;
            }

            foreach ($items as $item) {
                try {
                    $this->computeAndStoreWorkKey((int) $item->id);
                    $done++;
                } catch (\Throwable $e) {
                    $this->logger->warning('FrbrService: failed item ' . $item->id, [
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            yield [
                'done'    => $done,
                'total'   => $total,
                'batches' => $batch + 1,
            ];
        }

        yield ['done' => $done, 'total' => $total, 'batches' => $batches];
    }

    // ========================================================================
    // SEARCH CLUSTERING
    // ========================================================================

    /**
     * Cluster an array of search result rows by frbr_work_key.
     *
     * Rules (applied in order):
     *   1. force_split → item excluded from any work cluster
     *   2. force_group → item merged into target_work_key cluster
     *   3. normal → grouped by frbr_work_key (items with no key treated as singletons)
     *
     * Returns a work-ordered array of clusters.
     * Each cluster: ['primary' => first item, 'manifestations' => [...], 'count' => int]
     */
    public function clusterSearchResults(array $items): array
    {
        // Build override map: library_item_id => [type, target_work_key or null]
        $overrideMap = $this->buildOverrideMap($items);

        // Partition: items → work_key → []
        $works = [];

        foreach ($items as $item) {
            $id    = (int) ($item->id ?? $item['id'] ?? 0);
            $title = $item->title ?? $item['title'] ?? '';
            $override = $overrideMap[$id] ?? ['type' => 'none', 'target' => null];

            if ($override['type'] === 'force_split') {
                // Singleton: own work key = SHA-256('singleton_' . id) to keep unique
                $workKey = 'solo_' . $id;
            } elseif ($override['type'] === 'force_group' && !empty($override['target'])) {
                $workKey = $override['target'];
            } else {
                $workKey = $item->frbr_work_key ?? $item['frbr_work_key'] ?? null;
                if (empty($workKey)) {
                    // No work key: singleton based on item id
                    $workKey = 'solo_' . $id;
                }
            }

            if (!isset($works[$workKey])) {
                $works[$workKey] = [
                    'primary'       => null,
                    'manifestations' => [],
                ];
            }

            $works[$workKey]['manifestations'][] = $item;

            // First item encountered for a work is the primary (display) item
            if ($works[$workKey]['primary'] === null) {
                $works[$workKey]['primary'] = $item;
            }
        }

        // Convert to ordered list
        $clusters = [];
        foreach ($works as $workKey => $cluster) {
            $clusters[] = [
                'work_key'         => $workKey,
                'primary'          => $cluster['primary'],
                'manifestations'   => $cluster['manifestations'],
                'count'            => count($cluster['manifestations']),
            ];
        }

        // Sort clusters: by primary title (alphabetical), then by work_key for singletons
        usort($clusters, function ($a, $b) {
            $ta = mb_strtolower($a['primary']['title'] ?? '');
            $tb = mb_strtolower($b['primary']['title'] ?? '');
            if ($ta !== $tb) {
                return $ta <=> $tb;
            }
            return strcmp($a['work_key'], $b['work_key']);
        });

        return $clusters;
    }

    /**
     * Build override lookup for the item set.
     * Only queries items that appear in the search results to keep queries cheap.
     *
     * @param array $items Search result rows
     * @return array<int, array{type:string, target:?string}>  keyed by library_item_id
     */
    protected function buildOverrideMap(array $items): array
    {
        $itemIds = array_map(fn($item) => (int) ($item->id ?? $item['id'] ?? 0), $items);
        $itemIds = array_filter($itemIds);

        if (empty($itemIds)) {
            return [];
        }

        $overrides = DB::table('library_item_frbr_override')
            ->whereIn('library_item_id', $itemIds)
            ->get()
            ->all();

        $map = [];
        foreach ($overrides as $row) {
            $map[(int) $row->library_item_id] = [
                'type'   => $row->forced_split ? 'force_split' : 'force_group',
                'target' => $row->forced_split ? null : ($row->target_work_key ?? null),
            ];
        }

        return $map;
    }

    // ========================================================================
    // OVERRIDE CRUD (admin)
    // ========================================================================

    /**
     * Force-group an item into a target work key (or null to clear).
     */
    public function setForceGroup(int $libraryItemId, ?string $targetWorkKey, string $reason, ?int $userId = null): void
    {
        DB::table('library_item_frbr_override')->updateOrInsert(
            ['library_item_id' => $libraryItemId],
            [
                'target_work_key' => $targetWorkKey,
                'forced_split'    => 0,
                'reason'          => $reason,
                'created_by'      => $userId,
                'created_at'      => date('Y-m-d H:i:s'),
            ]
        );

        // Update the item's override type flag
        DB::table('library_item')
            ->where('id', $libraryItemId)
            ->update(['frbr_override_type' => 'force_group']);

        $this->logger->info('FrbrService: force_group', [
            'item_id'      => $libraryItemId,
            'target_key'   => $targetWorkKey,
            'reason'       => $reason,
            'by_user_id'   => $userId,
        ]);
    }

    /**
     * Force-split an item from any work cluster.
     */
    public function setForceSplit(int $libraryItemId, string $reason, ?int $userId = null): void
    {
        DB::table('library_item_frbr_override')->updateOrInsert(
            ['library_item_id' => $libraryItemId],
            [
                'target_work_key' => null,
                'forced_split'    => 1,
                'reason'          => $reason,
                'created_by'      => $userId,
                'created_at'      => date('Y-m-d H:i:s'),
            ]
        );

        DB::table('library_item')
            ->where('id', $libraryItemId)
            ->update(['frbr_override_type' => 'force_split']);

        $this->logger->info('FrbrService: force_split', [
            'item_id'    => $libraryItemId,
            'reason'     => $reason,
            'by_user_id' => $userId,
        ]);
    }

    /**
     * Remove an override and restore FRBR auto-clustering.
     */
    public function clearOverride(int $libraryItemId): void
    {
        DB::table('library_item_frbr_override')
            ->where('library_item_id', $libraryItemId)
            ->delete();

        DB::table('library_item')
            ->where('id', $libraryItemId)
            ->update(['frbr_override_type' => 'none']);

        // Re-compute work key (may have been updated since override was set)
        $this->computeAndStoreWorkKey($libraryItemId);
    }

    /**
     * List all overrides with optional work_key filter.
     */
    public function listOverrides(?string $workKeyFilter = null): array
    {
        $query = DB::table('library_item_frbr_override as o')
            ->join('library_item as li', 'o.library_item_id', '=', 'li.id')
            ->leftJoin('information_object_i18n as ioi', 'li.information_object_id', '=', 'ioi.id')
            ->where('ioi.culture', \AtomExtensions\Helpers\CultureHelper::getCulture() ?: 'en')
            ->select(
                'o.id',
                'o.library_item_id',
                'o.target_work_key',
                'o.forced_split',
                'o.reason',
                'o.created_at',
                'ioi.title',
                'li.frbr_work_key',
                'li.frbr_override_type'
            )
            ->orderBy('o.created_at', 'desc');

        if ($workKeyFilter) {
            $query->where('o.target_work_key', $workKeyFilter);
        }

        return $query->get()->all();
    }

    /**
     * Get cluster detail: all items in a work (excluding force_split items).
     */
    public function getWorkCluster(string $workKey): array
    {
        // Check if this work key has forced-group members
        $forcedMembers = DB::table('library_item_frbr_override as o')
            ->join('library_item as li', 'o.library_item_id', '=', 'li.id')
            ->leftJoin('information_object_i18n as ioi', 'li.information_object_id', '=', 'ioi.id')
            ->where('o.target_work_key', $workKey)
            ->where('o.forced_split', 0)
            ->select('o.library_item_id as id', 'ioi.title', 'li.frbr_work_key',
                     DB::raw("'forced' as source"))
            ->get()
            ->all();

        // Natural members by work key
        $natural = DB::table('library_item as li')
            ->leftJoin('information_object_i18n as ioi', 'li.information_object_id', '=', 'ioi.id')
            ->where('li.frbr_work_key', $workKey)
            ->where('li.frbr_override_type', '!=', 'force_split')
            ->select('li.id', 'ioi.title', 'li.frbr_work_key',
                     DB::raw("'natural' as source"))
            ->get()
            ->all();

        $all = array_merge($forcedMembers, $natural);
        usort($all, fn($a, $b) => strcmp($a->title ?? '', $b->title ?? ''));

        return $all;
    }

    /**
     * Count items per work (for COUNTER PR report / analytics).
     */
    public function countWorks(): int
    {
        return DB::table('library_item')
            ->whereNotNull('frbr_work_key')
            ->where('frbr_work_key', '!=', '')
            ->where('frbr_override_type', '!=', 'force_split')
            ->distinct('frbr_work_key')
            ->count('frbr_work_key');
    }
}