<?php
declare(strict_types=1);

namespace AhgIiif\Services;

use Illuminate\Database\Capsule\Manager as DB;

/**
 * Service for managing IIIF Viewer settings and rendering.
 *
 * @package AhgIiif\Services
 * @author Johan Pieterse - The Archive and Heritage Group
 */
class IiifViewerService
{
    private array $settings = [];
    private bool $loaded = false;

    /**
     * Load all settings from database.
     */
    public function loadSettings(): array
    {
        if ($this->loaded) {
            return $this->settings;
        }

        $rows = DB::table('iiif_viewer_settings')->get();
        foreach ($rows as $row) {
            $this->settings[$row->setting_key] = $row->setting_value;
        }
        $this->loaded = true;

        return $this->settings;
    }

    /**
     * Get a single setting.
     */
    public function getSetting(string $key, $default = null)
    {
        $this->loadSettings();
        return $this->settings[$key] ?? $default;
    }

    /**
     * Update a setting.
     */
    public function updateSetting(string $key, string $value): bool
    {
        $exists = DB::table('iiif_viewer_settings')->where('setting_key', $key)->exists();
        
        if ($exists) {
            DB::table('iiif_viewer_settings')
                ->where('setting_key', $key)
                ->update(['setting_value' => $value]);
        } else {
            DB::table('iiif_viewer_settings')->insert([
                'setting_key' => $key,
                'setting_value' => $value,
            ]);
        }
        
        $this->settings[$key] = $value;
        return true;
    }

    /**
     * Update multiple settings.
     */
    public function updateSettings(array $settings): bool
    {
        foreach ($settings as $key => $value) {
            $this->updateSetting($key, $value);
        }
        return true;
    }

    /**
     * Get all settings as array.
     */
    public function getAllSettings(): array
    {
        return $this->loadSettings();
    }

    /**
     * Get digital objects for an information object.
     */
    public function getDigitalObjects(int $objectId): array
    {
        return DB::table('digital_object')
            ->where('object_id', $objectId)
            ->select('id', 'name', 'path', 'mime_type', 'byte_size')
            ->get()
            ->all();
    }

    /**
     * Build IIIF image URL for Cantaloupe.
     */
    public function buildImageUrl(object $digitalObject, string $size = 'full'): string
    {
        $baseUrl = \sfConfig::get('app_siteBaseUrl', '');
        $imagePath = ltrim($digitalObject->path, '/');
        $cantaloupeId = str_replace('/', '_SL_', $imagePath) . $digitalObject->name;
        
        return "{$baseUrl}/iiif/2/{$cantaloupeId}/full/{$size}/0/default.jpg";
    }

    /**
     * Build thumbnail URL.
     */
    public function buildThumbnailUrl(object $digitalObject, int $width = 200): string
    {
        $baseUrl = \sfConfig::get('app_siteBaseUrl', '');
        $imagePath = ltrim($digitalObject->path, '/');
        $cantaloupeId = str_replace('/', '_SL_', $imagePath) . $digitalObject->name;
        
        return "{$baseUrl}/iiif/2/{$cantaloupeId}/full/{$width},/0/default.jpg";
    }

    /**
     * Get IIIF manifest URL for an object.
     */
    public function getManifestUrl(string $slug): string
    {
        $baseUrl = \sfConfig::get('app_siteBaseUrl', '');
        return "{$baseUrl}/iiif-manifest.php?slug={$slug}";
    }

    // =========================================================================
    // MANIFEST CACHING
    // =========================================================================

    /**
     * Get cached manifest for an object.
     *
     * @param string $version 'v2' or 'v3' — appended to culture key to separate cache entries
     */
    public function getCachedManifest(int $objectId, string $culture = 'en', string $version = 'v2'): ?array
    {
        $cultureKey = $version === 'v2' ? $culture : "{$culture}:{$version}";

        $row = DB::table('iiif_manifest_cache')
            ->where('object_id', $objectId)
            ->where('culture', $cultureKey)
            ->where(function ($q) {
                $q->whereNull('expires_at')
                  ->orWhere('expires_at', '>', DB::raw('NOW()'));
            })
            ->first();

        if (!$row) {
            return null;
        }

        // Content-aware invalidation. The stored cache_key is a signature of the
        // object's digital objects (id + checksum). If it no longer matches the
        // current signature - because an image was added, replaced, or removed -
        // treat this as a cache MISS so the manifest is regenerated against the
        // current master image. Otherwise a replaced image keeps serving the old
        // (often now-deleted) Cantaloupe path until the 24h expiry, showing a
        // blank/stale image on the record page.
        $currentSignature = $this->buildCacheSignature($objectId, $cultureKey);
        if (!empty($row->cache_key) && !hash_equals((string) $row->cache_key, $currentSignature)) {
            return null;
        }

        return [
            'manifest_json' => $row->manifest_json,
            'page_count' => $row->page_count,
        ];
    }

    /**
     * Store manifest in cache.
     *
     * @param string $version 'v2' or 'v3' — appended to culture key to separate cache entries
     */
    public function setCachedManifest(int $objectId, string $culture, string $json, ?int $pageCount = null, string $version = 'v2'): void
    {
        $cultureKey = $version === 'v2' ? $culture : "{$culture}:{$version}";
        $signature = $this->buildCacheSignature($objectId, $cultureKey);

        DB::table('iiif_manifest_cache')->updateOrInsert(
            ['object_id' => $objectId, 'culture' => $cultureKey],
            [
                'manifest_json' => $json,
                'cache_key' => $signature,
                'page_count' => $pageCount,
                'created_at' => DB::raw('NOW()'),
                'expires_at' => DB::raw("DATE_ADD(NOW(), INTERVAL 24 HOUR)"),
            ]
        );
    }

    /**
     * Invalidate manifest cache for an object.
     */
    public function invalidateManifestCache(int $objectId): int
    {
        return DB::table('iiif_manifest_cache')
            ->where('object_id', $objectId)
            ->delete();
    }

    /**
     * Get cached page count for multi-page TIFF objects.
     */
    public function getPageCount(int $objectId): ?int
    {
        $row = DB::table('iiif_manifest_cache')
            ->where('object_id', $objectId)
            ->whereNotNull('page_count')
            ->first();

        return $row ? (int) $row->page_count : null;
    }

    /**
     * Build SHA-256 cache signature from object data.
     */
    private function buildCacheSignature(int $objectId, string $culture): string
    {
        // Include each digital object's checksum, not just its id, so that
        // replacing an image in place (same row id, new file/checksum) also
        // changes the signature and invalidates the cached manifest.
        $parts = DB::table('digital_object')
            ->where('object_id', $objectId)
            ->orderBy('id')
            ->get(['id', 'checksum'])
            ->map(fn ($r) => $r->id . ':' . (string) $r->checksum)
            ->implode(',');

        return hash('sha256', "{$objectId}:{$culture}:{$this->cacheHost()}:{$parts}");
    }

    /**
     * The host a manifest was built for, folded into the cache signature.
     *
     * A manifest embeds absolute URLs - the image service id, the canvas ids,
     * the search service - so one built for a given hostname is wrong for every
     * other one. The signature covered the digital objects and the culture but
     * not the host, which meant whichever request populated the cache first fixed
     * those URLs for everybody until the 24 hour expiry.
     *
     * That is not a theoretical multi-tenant concern. A localhost health check, a
     * cron warm-up or an internal monitor reaching a manifest first leaves every
     * public reader with image URLs pointing at 127.0.0.1, and the images simply
     * do not load - no error anywhere, because from the server's point of view
     * the manifest is perfectly valid. Found exactly that way on the test VM,
     * where my own curl had poisoned it.
     *
     * Folded into the signature rather than added to the cache key because the
     * `culture` column is varchar(10) and a host does not fit. The cost is that
     * two hostnames serving the same site take turns regenerating rather than
     * both being cached; the alternative was a schema change to a table clients
     * already have.
     */
    private function cacheHost(): string
    {
        $host = $_SERVER['HTTP_HOST'] ?? 'cli';
        $scheme = (!empty($_SERVER['HTTPS']) && 'off' !== $_SERVER['HTTPS']) ? 'https' : 'http';

        return $scheme.'://'.$host;
    }
}
