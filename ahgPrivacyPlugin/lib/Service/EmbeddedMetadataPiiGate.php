<?php

declare(strict_types=1);

namespace ahgPrivacyPlugin\Service;

use Illuminate\Database\Capsule\Manager as DB;

/**
 * EmbeddedMetadataPiiGate
 *
 * Defensive privacy gate: withholds GPS-shaped fields from downstream surfaces
 * (OCFL inventory, C2PA assertions, IIIF manifests, AI context) while a
 * gps_coordinate finding for the object is pending/escalated review in
 * ahg_pii_finding_embedded (#751).
 *
 * Fail-open: every DB probe is guarded; on any error or when the findings
 * table is absent the input is returned unchanged (never break a downstream
 * write because the privacy side is unavailable).
 *
 * @package    ahgPrivacyPlugin
 * @subpackage Service
 */
class EmbeddedMetadataPiiGate
{
    /** Field-name prefixes considered GPS-shaped (case-insensitive). */
    private const GPS_PREFIXES = ['gps', 'geolocation', 'location'];

    private const TABLE = 'ahg_pii_finding_embedded';

    /** True when a pending/escalated GPS finding exists for any DO of the IO. */
    public function hasPendingGpsForIo(int $ioId): bool
    {
        try {
            $doIds = DB::table('digital_object')->where('object_id', $ioId)->pluck('id')->all();

            return $doIds ? $this->pendingGps($doIds) : false;
        } catch (\Throwable $e) {
            return false;
        }
    }

    public function hasPendingGpsForDigitalObject(int $doId): bool
    {
        try {
            return $this->pendingGps([$doId]);
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function pendingGps(array $doIds): bool
    {
        if (!DB::schema()->hasTable(self::TABLE)) {
            return false;
        }

        return DB::table(self::TABLE)
            ->whereIn('digital_object_id', $doIds)
            ->where('pii_type', 'gps_coordinate')
            ->whereIn('resolution_status', ['pending', 'escalated'])
            ->exists();
    }

    /**
     * Strip GPS-shaped keys from a flat metadata array IF the IO has a pending
     * GPS finding. Returns the (possibly unchanged) array.
     *
     * @param array<string,mixed> $metadata
     * @return array<string,mixed>
     */
    public function redactGpsForIo(int $ioId, array $metadata): array
    {
        return $this->hasPendingGpsForIo($ioId) ? self::stripGpsKeys($metadata) : $metadata;
    }

    /**
     * @param array<string,mixed> $metadata
     * @return array<string,mixed>
     */
    public function redactGpsForDigitalObject(int $doId, array $metadata): array
    {
        return $this->hasPendingGpsForDigitalObject($doId) ? self::stripGpsKeys($metadata) : $metadata;
    }

    /**
     * Remove GPS-shaped keys from a flat associative array. Pure function.
     *
     * @param array<string,mixed> $arr
     * @return array<string,mixed>
     */
    public static function stripGpsKeys(array $arr): array
    {
        foreach (array_keys($arr) as $k) {
            $lc = strtolower((string) $k);
            foreach (self::GPS_PREFIXES as $prefix) {
                if (str_starts_with($lc, $prefix)) {
                    unset($arr[$k]);
                    break;
                }
            }
        }

        return $arr;
    }

    /**
     * Strip GPS-shaped keys from the 'exif' sub-block of a C2PA/OCFL-style
     * block ['exif'=>[...], 'iptc'=>[...], 'xmp'=>[...]]. Returns null when no
     * meaningful sub-block remains (caller drops the block). Pure function.
     *
     * @param array<string,mixed> $block
     * @return array<string,mixed>|null
     */
    public static function stripGpsFromBlock(array $block): ?array
    {
        if (isset($block['exif']) && is_array($block['exif'])) {
            $exif = self::stripGpsKeys($block['exif']);
            if ($exif === []) {
                unset($block['exif']);
            } else {
                $block['exif'] = $exif;
            }
        }

        $hasAny = isset($block['exif']) || isset($block['iptc']) || isset($block['xmp']);

        return $hasAny ? $block : null;
    }
}
