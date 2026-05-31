<?php

declare(strict_types=1);

namespace AtomExtensions\Extensions\MetadataExtraction\Services;

use Illuminate\Database\Capsule\Manager as DB;

/**
 * EmbeddedMetadataService — full embedded-metadata capture (#113).
 *
 * AtoM-side twin of Heratio's full-metadata work (heratio#1106). Base AtoM's
 * arEmbeddedMetadataParser only surfaces a curated subset; this service runs
 * ExifTool with ALL groups/tags and stores the complete grouped result verbatim
 * so it can be displayed (grouped + searchable), GPS-gated for the public, and
 * flowed through API / IIIF / preservation — alongside the existing curated fields.
 *
 * Storage: a plugin-owned table `ahg_embedded_metadata` (one row per master DO,
 * raw_metadata LONGTEXT). This avoids touching the core `property` table schema
 * and gives ample capacity for large tag sets. All DB access is wrapped so a
 * missing table degrades gracefully (capture becomes a no-op) until install.sql
 * is applied.
 */
class EmbeddedMetadataService
{
    public const TABLE = 'ahg_embedded_metadata';

    private string $exifToolPath;

    public function __construct(?string $exifToolPath = null)
    {
        if ($exifToolPath) {
            $this->exifToolPath = $exifToolPath;
        } elseif (class_exists('\sfConfig')) {
            // Symfony web/handler context.
            $this->exifToolPath = (string) \sfConfig::get('app_metadata_exiftool_path', '/usr/bin/exiftool');
        } else {
            // bin/atom CLI context (no Symfony) — sensible default.
            $this->exifToolPath = '/usr/bin/exiftool';
        }
    }

    /**
     * Run ExifTool over every group/tag and return the flat "Group:Tag" => value
     * map (SourceFile dropped). Returns null on any failure — full capture is
     * best-effort and must never block an upload.
     */
    public function extractFull(string $absPath): ?array
    {
        if ('' === $absPath || !is_readable($absPath)) {
            return null;
        }
        // Require a real binary unless a bare PATH-resolved command is configured.
        if (false !== strpos($this->exifToolPath, '/') && !is_file($this->exifToolPath)) {
            return null;
        }

        // -a (all, incl. duplicates), -G1 (family-1 group prefix), -struct (keep
        // nested XMP structs), -u (unknown tags). Binary blobs are summarised by
        // ExifTool, not dumped, so output stays compact.
        $command = sprintf(
            '%s -json -a -G1 -struct -u -api largefilesupport=1 %s 2>/dev/null',
            escapeshellcmd($this->exifToolPath),
            escapeshellarg($absPath)
        );

        $output = [];
        $rc = 0;
        exec($command, $output, $rc);

        // Note: ExifTool returns rc=1 for warnings/minor format errors while
        // STILL emitting the tags it could read (the error surfaces as an
        // ExifTool:Error tag). So parse the JSON regardless of exit code and
        // only fail if the output is unusable.
        $data = json_decode(implode("\n", $output), true);
        if (!is_array($data) || !isset($data[0]) || !is_array($data[0])) {
            return null;
        }

        $row = $data[0];
        unset($row['SourceFile']);

        return $row;
    }

    /**
     * Persist the full flat tag map for a master DO (upsert).
     */
    public function store(int $digitalObjectId, array $flat, ?int $informationObjectId = null): void
    {
        if (empty($flat)) {
            return;
        }

        $now = date('Y-m-d H:i:s');
        $payload = [
            'information_object_id' => $informationObjectId,
            'raw_metadata' => json_encode($flat, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            'has_gps' => $this->hasGps($flat) ? 1 : 0,
            'tag_count' => count($flat),
            'updated_at' => $now,
        ];

        try {
            $exists = DB::table(self::TABLE)->where('digital_object_id', $digitalObjectId)->exists();
            if ($exists) {
                DB::table(self::TABLE)->where('digital_object_id', $digitalObjectId)->update($payload);
            } else {
                $payload['digital_object_id'] = $digitalObjectId;
                $payload['extracted_at'] = $now;
                DB::table(self::TABLE)->insert($payload);
            }
        } catch (\Throwable $e) {
            // Table not yet installed — capture is best-effort, never fatal.
        }
    }

    /**
     * Extract + store in one call. Returns true if stored.
     */
    public function captureAndStore(int $digitalObjectId, string $absPath, ?int $informationObjectId = null): bool
    {
        $flat = $this->extractFull($absPath);
        if (null === $flat || empty($flat)) {
            return false;
        }
        $this->store($digitalObjectId, $flat, $informationObjectId);

        return true;
    }

    /**
     * Read the stored raw_metadata flat map for a DO (null if none / table absent).
     */
    public function getRaw(int $digitalObjectId): ?array
    {
        try {
            $value = DB::table(self::TABLE)
                ->where('digital_object_id', $digitalObjectId)
                ->value('raw_metadata');
        } catch (\Throwable $e) {
            return null;
        }

        if (null === $value || '' === $value) {
            return null;
        }

        $decoded = json_decode((string) $value, true);

        return is_array($decoded) ? $decoded : null;
    }

    /**
     * Group a flat "Group:Tag" => value map into [group => [tag => value]],
     * sorted by group for stable display.
     */
    public function group(array $flat): array
    {
        $grouped = [];
        foreach ($flat as $key => $value) {
            $pos = strpos((string) $key, ':');
            if (false === $pos) {
                $grouped['File'][$key] = $value;
                continue;
            }
            $group = substr($key, 0, $pos);
            $tag = substr($key, $pos + 1);
            $grouped[$group][$tag] = $value;
        }
        ksort($grouped);

        return $grouped;
    }

    /**
     * Remove location-bearing data from a grouped map for public display.
     * Drops the whole GPS group and any GPS/location-shaped tag elsewhere.
     */
    public function gpsGate(array $grouped): array
    {
        unset($grouped['GPS']);

        foreach ($grouped as $group => $tags) {
            if (!is_array($tags)) {
                continue;
            }
            foreach ($tags as $tag => $value) {
                if (preg_match('/(GPS|GeoLocation|Location|Latitude|Longitude)/i', (string) $tag)) {
                    unset($grouped[$group][$tag]);
                }
            }
            if (empty($grouped[$group])) {
                unset($grouped[$group]);
            }
        }

        return $grouped;
    }

    /**
     * Whether the flat set contains any GPS/location data.
     */
    public function hasGps(array $flat): bool
    {
        foreach ($flat as $key => $value) {
            if (preg_match('/(GPS|GeoLocation|GPSLatitude|GPSLongitude)/i', (string) $key)) {
                return true;
            }
        }

        return false;
    }
}
