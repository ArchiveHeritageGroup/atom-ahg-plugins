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
     */
    public function getCachedManifest(int $objectId, string $culture = 'en'): ?array
    {
        $row = DB::table('iiif_manifest_cache')
            ->where('object_id', $objectId)
            ->where('culture', $culture)
            ->where(function ($q) {
                $q->whereNull('expires_at')
                  ->orWhere('expires_at', '>', DB::raw('NOW()'));
            })
            ->first();

        if (!$row) {
            return null;
        }

        return [
            'manifest_json' => $row->manifest_json,
            'page_count' => $row->page_count,
        ];
    }

    /**
     * Store manifest in cache.
     */
    public function setCachedManifest(int $objectId, string $culture, string $json, ?int $pageCount = null): void
    {
        $signature = $this->buildCacheSignature($objectId, $culture);

        DB::table('iiif_manifest_cache')->updateOrInsert(
            ['object_id' => $objectId, 'culture' => $culture],
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
        $doIds = DB::table('digital_object')
            ->where('object_id', $objectId)
            ->orderBy('id')
            ->pluck('id')
            ->implode(',');

        return hash('sha256', "{$objectId}:{$culture}:{$doIds}");
    }
}
