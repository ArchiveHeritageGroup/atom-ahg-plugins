<?php
/**
 * PSIS / AtoM-AHG - load embedded image/media metadata from sidecar tables
 * (digital_object_metadata, dam_iptc_metadata, media_metadata) and project it
 * into the three C2PA 2.1 "Standard Metadata Assertions": stds.exif,
 * stds.iptc, stds.xmp.
 *
 * Symfony 1.4 / PHP 8.3 port of Heratio's AhgC2pa\Manifest\StandardMetadataLoader.
 * The Laravel DB/Schema/Log facades are replaced by the Capsule manager
 * (Illuminate\Database\Capsule\Manager) which is what atom-framework boots.
 * error_log() stands in for the Log facade.
 *
 * Every loader method is defensive: it returns [] (never throws) when a sidecar
 * table is missing, the row is absent, or every relevant column is empty. This
 * is exactly the behaviour the ManifestBuilder relies on - empty assertions are
 * never emitted. Because the table set differs slightly between AtoM and Heratio
 * deployments, the table-absent path is the normal case on a base AtoM install.
 *
 * @copyright Copyright (c) 2026, Plain Sailing Information Systems
 * @author    Johan Pieterse <johan@plainsailingisystems.co.za>
 * @license   AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace AhgC2pa\Manifest;

use Illuminate\Database\Capsule\Manager as DB;
use Throwable;

final class StandardMetadataLoader
{
    /**
     * Build a stds.exif payload from digital_object_metadata + media_metadata.
     *
     * PII gate (parity with Heratio #751): if ahg_pii_finding_embedded has any
     * pending/escalated gps_coordinate finding for this digital object, the GPS
     * keys are stripped and a _pii_redacted marker is set. The gate fails open
     * when the table is absent.
     *
     * @return array<string,scalar|bool> empty when no usable data was found.
     */
    public function loadExif(int $digitalObjectId): array
    {
        $row = $this->fetchRow('digital_object_metadata', 'digital_object_id', $digitalObjectId);
        $media = $this->fetchRow('media_metadata', 'digital_object_id', $digitalObjectId);

        $out = [];

        if ($row !== null) {
            $this->copyIfSet($out, 'Exif/DateTimeOriginal', $row, 'date_created');
            $this->copyIfSet($out, 'Exif/Make', $row, 'camera_make');
            $this->copyIfSet($out, 'Exif/Model', $row, 'camera_model');
            $this->copyIfSet($out, 'Exif/ImageWidth', $row, 'image_width');
            $this->copyIfSet($out, 'Exif/ImageHeight', $row, 'image_height');
            $this->copyIfSet($out, 'Exif/Artist', $row, 'creator');
            $this->copyIfSet($out, 'Exif/Copyright', $row, 'copyright');
            $this->copyIfSet($out, 'Exif/ImageDescription', $row, 'description');

            $lat = $row['gps_latitude'] ?? null;
            $lon = $row['gps_longitude'] ?? null;
            if (self::numeric($lat)) {
                $out['Exif/GPSLatitude'] = (float) $lat;
                $out['Exif/GPSLatitudeRef'] = ((float) $lat) >= 0 ? 'N' : 'S';
            }
            if (self::numeric($lon)) {
                $out['Exif/GPSLongitude'] = (float) $lon;
                $out['Exif/GPSLongitudeRef'] = ((float) $lon) >= 0 ? 'E' : 'W';
            }
        }

        if ($media !== null) {
            $this->copyIfSet($out, 'Exif/Make', $media, 'make');
            $this->copyIfSet($out, 'Exif/Model', $media, 'model');
            $this->copyIfSet($out, 'Exif/Software', $media, 'software');
            if (!isset($out['Exif/Duration']) && isset($media['duration']) && self::numeric($media['duration'])) {
                $out['Exif/Duration'] = (float) $media['duration'];
            }
        }

        if ($this->hasPendingGpsFinding($digitalObjectId)) {
            $hadGps = isset($out['Exif/GPSLatitude']) || isset($out['Exif/GPSLongitude']);
            unset(
                $out['Exif/GPSLatitude'],
                $out['Exif/GPSLatitudeRef'],
                $out['Exif/GPSLongitude'],
                $out['Exif/GPSLongitudeRef'],
            );
            if ($hadGps) {
                $out['_pii_redacted'] = true;
            }
        }

        return $out;
    }

    /**
     * True when ahg_pii_finding_embedded has a pending/escalated gps_coordinate
     * finding for the digital object. Fails open (false) on any error or when
     * the table is missing.
     */
    private function hasPendingGpsFinding(int $digitalObjectId): bool
    {
        if (!$this->tableExists('ahg_pii_finding_embedded')) {
            error_log('[c2pa] stds_exif: PII gate table absent, GPS not redacted for digital_object_id=' . $digitalObjectId);

            return false;
        }
        try {
            return DB::table('ahg_pii_finding_embedded')
                ->where('digital_object_id', $digitalObjectId)
                ->where('pii_type', 'gps_coordinate')
                ->whereIn('resolution_status', ['pending', 'escalated'])
                ->exists();
        } catch (Throwable $e) {
            error_log('[c2pa] stds_exif: PII gate query failed; proceeding without redaction: ' . $e->getMessage());

            return false;
        }
    }

    /**
     * Build a stds.iptc payload from dam_iptc_metadata.
     *
     * @return array<string,mixed>
     */
    public function loadIptc(int $digitalObjectId, ?int $objectId = null): array
    {
        $row = null;

        if ($this->tableExists('dam_iptc_metadata')) {
            try {
                if ($objectId !== null) {
                    $row = (array) DB::table('dam_iptc_metadata')
                        ->where('object_id', $objectId)
                        ->first();
                }
                if (!$row) {
                    $row = (array) DB::table('dam_iptc_metadata')
                        ->where('object_id', $digitalObjectId)
                        ->first();
                }
            } catch (Throwable) {
                $row = null;
            }
        }

        if (!$row) {
            return [];
        }

        $out = [];
        $this->copyIfSet($out, 'By-line', $row, 'creator');
        $this->copyIfSet($out, 'By-lineTitle', $row, 'creator_job_title');
        $this->copyIfSet($out, 'CopyrightNotice', $row, 'copyright_notice');
        $this->copyIfSet($out, 'Headline', $row, 'headline');
        $this->copyIfSet($out, 'Caption-Abstract', $row, 'caption');
        $this->copyIfSet($out, 'ObjectName', $row, 'title');
        $this->copyIfSet($out, 'City', $row, 'city');
        $this->copyIfSet($out, 'Province-State', $row, 'state_province');
        $this->copyIfSet($out, 'Country-PrimaryLocationName', $row, 'country');
        $this->copyIfSet($out, 'Country-PrimaryLocationCode', $row, 'country_code');
        $this->copyIfSet($out, 'Sub-location', $row, 'sublocation');
        $this->copyIfSet($out, 'Credit', $row, 'credit_line');
        $this->copyIfSet($out, 'Source', $row, 'source');
        $this->copyIfSet($out, 'SpecialInstructions', $row, 'instructions');
        $this->copyIfSet($out, 'IntellectualGenre', $row, 'intellectual_genre');
        $this->copyIfSet($out, 'SubjectReference', $row, 'iptc_subject_code');
        $this->copyIfSet($out, 'Scene', $row, 'iptc_scene');
        $this->copyIfSet($out, 'DateCreated', $row, 'date_created');

        if (isset($row['keywords']) && is_string($row['keywords']) && trim($row['keywords']) !== '') {
            $parts = preg_split('/[,;]\s*/', (string) $row['keywords']) ?: [];
            $parts = array_values(array_filter(array_map('trim', $parts), fn ($s) => $s !== ''));
            if ($parts !== []) {
                $out['Keywords'] = $parts;
            }
        }

        return $out;
    }

    /**
     * Build a stds.xmp payload (Dublin Core + xmpRights subset).
     *
     * @return array<string,mixed>
     */
    public function loadXmp(int $digitalObjectId, ?int $objectId = null): array
    {
        $row = $this->fetchRow('digital_object_metadata', 'digital_object_id', $digitalObjectId);
        $iptc = null;
        if ($this->tableExists('dam_iptc_metadata')) {
            try {
                if ($objectId !== null) {
                    $iptc = (array) DB::table('dam_iptc_metadata')->where('object_id', $objectId)->first();
                }
                if (!$iptc) {
                    $iptc = (array) DB::table('dam_iptc_metadata')->where('object_id', $digitalObjectId)->first();
                }
            } catch (Throwable) {
                $iptc = null;
            }
        }

        $out = [];

        $creator = $iptc['creator'] ?? null;
        if (!self::nonEmptyString($creator) && $row !== null) {
            $creator = $row['creator'] ?? null;
        }
        if (self::nonEmptyString($creator)) {
            $out['dc:creator'] = [trim((string) $creator)];
        }

        $rights = $iptc['copyright_notice'] ?? null;
        if (!self::nonEmptyString($rights) && $row !== null) {
            $rights = $row['copyright'] ?? null;
        }
        if (self::nonEmptyString($rights)) {
            $out['dc:rights'] = ['x-default' => trim((string) $rights)];
        }

        $title = $iptc['title'] ?? null;
        if (!self::nonEmptyString($title) && $row !== null) {
            $title = $row['title'] ?? null;
        }
        if (self::nonEmptyString($title)) {
            $out['dc:title'] = ['x-default' => trim((string) $title)];
        }

        if ($row !== null && self::nonEmptyString($row['description'] ?? null)) {
            $out['dc:description'] = ['x-default' => trim((string) $row['description'])];
        } elseif ($iptc !== null && self::nonEmptyString($iptc['caption'] ?? null)) {
            $out['dc:description'] = ['x-default' => trim((string) $iptc['caption'])];
        }

        $keywordsSrc = null;
        if ($iptc !== null && self::nonEmptyString($iptc['keywords'] ?? null)) {
            $keywordsSrc = $iptc['keywords'];
        } elseif ($row !== null && self::nonEmptyString($row['keywords'] ?? null)) {
            $keywordsSrc = $row['keywords'];
        }
        if ($keywordsSrc !== null) {
            $parts = preg_split('/[,;]\s*/', (string) $keywordsSrc) ?: [];
            $parts = array_values(array_filter(array_map('trim', $parts), fn ($s) => $s !== ''));
            if ($parts !== []) {
                $out['dc:subject'] = $parts;
            }
        }

        $date = $iptc['date_created'] ?? null;
        if (!self::nonEmptyString($date) && $row !== null) {
            $date = $row['date_created'] ?? null;
        }
        if (self::nonEmptyString($date)) {
            $out['dc:date'] = [trim((string) $date)];
        }

        if ($iptc !== null) {
            if (self::nonEmptyString($iptc['copyright_notice'] ?? null)) {
                $out['xmpRights:Marked'] = true;
            }
            if (self::nonEmptyString($iptc['rights_usage_terms'] ?? null)) {
                $out['xmpRights:UsageTerms'] = ['x-default' => trim((string) $iptc['rights_usage_terms'])];
            }
        }

        return $out;
    }

    /**
     * Load all three and return a list of Assertion objects, skipping any
     * that came back empty. ManifestBuilder uses this.
     *
     * @return list<Assertion>
     */
    public function loadAssertions(int $digitalObjectId, ?int $objectId = null): array
    {
        $out = [];
        $exif = $this->loadExif($digitalObjectId);
        $iptc = $this->loadIptc($digitalObjectId, $objectId);
        $xmp = $this->loadXmp($digitalObjectId, $objectId);
        if ($exif !== []) {
            $out[] = Assertion::stdsExif($exif);
        }
        if ($iptc !== []) {
            $out[] = Assertion::stdsIptc($iptc);
        }
        if ($xmp !== []) {
            $out[] = Assertion::stdsXmp($xmp);
        }

        return $out;
    }

    /**
     * @return array<string,mixed>|null
     */
    private function fetchRow(string $table, string $column, int $value): ?array
    {
        if (!$this->tableExists($table)) {
            return null;
        }
        try {
            $row = DB::table($table)->where($column, $value)->first();
        } catch (Throwable) {
            return null;
        }
        if ($row === null) {
            return null;
        }

        return (array) $row;
    }

    private function tableExists(string $table): bool
    {
        try {
            return DB::schema()->hasTable($table);
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * Copy $row[$srcKey] to $out[$dstKey] if the source is non-empty.
     *
     * @param array<string,scalar|null> $out
     * @param array<string,mixed> $row
     */
    private function copyIfSet(array &$out, string $dstKey, array $row, string $srcKey): void
    {
        $v = $row[$srcKey] ?? null;
        if ($v === null || $v === '') {
            return;
        }
        if (is_string($v)) {
            $v = trim($v);
            if ($v === '') {
                return;
            }
        }
        if (is_scalar($v)) {
            $out[$dstKey] = $v;
        }
    }

    private static function nonEmptyString(mixed $v): bool
    {
        return is_string($v) && trim($v) !== '';
    }

    private static function numeric(mixed $v): bool
    {
        return is_numeric($v) && (string) $v !== '';
    }
}
