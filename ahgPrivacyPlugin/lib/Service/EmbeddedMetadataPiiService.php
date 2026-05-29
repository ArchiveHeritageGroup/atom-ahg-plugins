<?php

declare(strict_types=1);

namespace ahgPrivacyPlugin\Service;

use Illuminate\Database\Capsule\Manager as DB;

/**
 * EmbeddedMetadataPiiService
 *
 * Scans EMBEDDED file metadata already extracted into digital_object_metadata
 * and dam_iptc_metadata for PII — GPS coordinates, people shown, creator
 * contact details — and records findings in ahg_pii_finding_embedded for
 * privacy review. Findings are keyed by digital_object.id so the GPS gate
 * (EmbeddedMetadataPiiGate) can withhold flagged coordinates from downstream
 * surfaces (OCFL, C2PA, IIIF, AI context).
 *
 * Heratio #751 parity. Findings dedupe on (digital_object_id, pii_type,
 * source_table, source_field).
 *
 * @package    ahgPrivacyPlugin
 * @subpackage Service
 */
class EmbeddedMetadataPiiService
{
    private const TABLE = 'ahg_pii_finding_embedded';

    /**
     * Scan all extracted embedded metadata and record findings.
     *
     * @return array{scanned:int,findings:int}
     */
    public function scanAll(?int $limit = null): array
    {
        $scanned  = 0;
        $findings = 0;

        // --- digital_object_metadata (keyed directly by digital_object_id) ---
        if (DB::schema()->hasTable('digital_object_metadata')) {
            $q = DB::table('digital_object_metadata')
                ->where(function ($w) {
                    $w->whereNotNull('gps_latitude')->orWhereNotNull('gps_longitude');
                });
            if ($limit) {
                $q->limit($limit);
            }
            foreach ($q->get() as $row) {
                $scanned++;
                $findings += $this->recordGps((int) $row->digital_object_id, 'digital_object_metadata', $row->gps_latitude, $row->gps_longitude);
            }
        }

        // --- dam_iptc_metadata (object_id = information_object.id) ---
        if (DB::schema()->hasTable('dam_iptc_metadata')) {
            $q = DB::table('dam_iptc_metadata')
                ->where(function ($w) {
                    $w->whereNotNull('gps_latitude')->orWhereNotNull('gps_longitude')
                      ->orWhere('persons_shown', '!=', '')
                      ->orWhere('creator_email', '!=', '')
                      ->orWhere('creator_phone', '!=', '');
                });
            if ($limit) {
                $q->limit($limit);
            }
            foreach ($q->get() as $row) {
                $scanned++;
                foreach ($this->digitalObjectIdsForIo((int) $row->object_id) as $doId) {
                    $findings += $this->recordGps($doId, 'dam_iptc_metadata', $row->gps_latitude ?? null, $row->gps_longitude ?? null);
                    if (!empty($row->persons_shown)) {
                        $findings += $this->record($doId, 'person_name', 'dam_iptc_metadata', 'persons_shown', (string) $row->persons_shown, 0.70);
                    }
                    $contact = trim((string) ($row->creator_email ?? '') . ' ' . (string) ($row->creator_phone ?? ''));
                    if ($contact !== '') {
                        $findings += $this->record($doId, 'person_contact', 'dam_iptc_metadata', 'creator_email/creator_phone', $contact, 0.80);
                    }
                }
            }
        }

        return ['scanned' => $scanned, 'findings' => $findings];
    }

    /**
     * Scan a single information object (resolves its digital objects).
     */
    public function scanInformationObject(int $ioId): int
    {
        $count = 0;
        foreach ($this->digitalObjectIdsForIo($ioId) as $doId) {
            $count += $this->scanDigitalObject($doId, $ioId);
        }

        return $count;
    }

    /**
     * Scan a single digital object (and its parent IO's IPTC row).
     */
    public function scanDigitalObject(int $doId, ?int $ioId = null): int
    {
        $count = 0;

        if (DB::schema()->hasTable('digital_object_metadata')) {
            $dom = DB::table('digital_object_metadata')->where('digital_object_id', $doId)->first();
            if ($dom) {
                $count += $this->recordGps($doId, 'digital_object_metadata', $dom->gps_latitude ?? null, $dom->gps_longitude ?? null);
            }
        }

        if ($ioId === null) {
            $ioId = (int) DB::table('digital_object')->where('id', $doId)->value('object_id');
        }
        if ($ioId && DB::schema()->hasTable('dam_iptc_metadata')) {
            $iptc = DB::table('dam_iptc_metadata')->where('object_id', $ioId)->first();
            if ($iptc) {
                $count += $this->recordGps($doId, 'dam_iptc_metadata', $iptc->gps_latitude ?? null, $iptc->gps_longitude ?? null);
                if (!empty($iptc->persons_shown)) {
                    $count += $this->record($doId, 'person_name', 'dam_iptc_metadata', 'persons_shown', (string) $iptc->persons_shown, 0.70);
                }
                $contact = trim((string) ($iptc->creator_email ?? '') . ' ' . (string) ($iptc->creator_phone ?? ''));
                if ($contact !== '') {
                    $count += $this->record($doId, 'person_contact', 'dam_iptc_metadata', 'creator_email/creator_phone', $contact, 0.80);
                }
            }
        }

        return $count;
    }

    private function recordGps(int $doId, string $table, $lat, $lng): int
    {
        if (($lat === null || $lat === '') && ($lng === null || $lng === '')) {
            return 0;
        }

        return $this->record($doId, 'gps_coordinate', $table, 'gps_latitude/gps_longitude', trim((string) $lat . ',' . (string) $lng, ','), 0.95);
    }

    /**
     * Upsert a finding, preserving an existing resolution_status on re-scan.
     */
    private function record(int $doId, string $piiType, string $table, string $field, ?string $value, float $confidence): int
    {
        if ($doId <= 0) {
            return 0;
        }
        $now = date('Y-m-d H:i:s');

        $existing = DB::table(self::TABLE)
            ->where('digital_object_id', $doId)->where('pii_type', $piiType)
            ->where('source_table', $table)->where('source_field', $field)
            ->first();

        if ($existing) {
            // Refresh the raw value + scan time; never reopen a resolved finding.
            DB::table(self::TABLE)->where('id', $existing->id)->update([
                'source_value' => $value,
                'confidence'   => $confidence,
                'scanned_at'   => $now,
                'updated_at'   => $now,
            ]);

            return 0;
        }

        DB::table(self::TABLE)->insert([
            'digital_object_id' => $doId,
            'pii_type'          => $piiType,
            'source_table'      => $table,
            'source_field'      => $field,
            'source_value'      => $value,
            'confidence'        => $confidence,
            'resolution_status' => 'pending',
            'scanned_at'        => $now,
            'created_at'        => $now,
            'updated_at'        => $now,
        ]);

        return 1;
    }

    /** digital_object ids belonging to an information object. */
    private function digitalObjectIdsForIo(int $ioId): array
    {
        if ($ioId <= 0) {
            return [];
        }

        return DB::table('digital_object')->where('object_id', $ioId)->pluck('id')->map(fn ($v) => (int) $v)->all();
    }

    /**
     * List findings, newest first, with optional status / type filters.
     */
    public function getFindings(array $filters = [], int $limit = 200): array
    {
        $q = DB::table(self::TABLE);
        if (!empty($filters['status'])) {
            $q->where('resolution_status', $filters['status']);
        }
        if (!empty($filters['pii_type'])) {
            $q->where('pii_type', $filters['pii_type']);
        }

        return $q->orderByDesc('scanned_at')->limit($limit)->get()->all();
    }

    /**
     * Resolve a finding (redacted / cleared / escalated).
     */
    public function resolve(int $findingId, string $status, ?int $userId = null): bool
    {
        $valid = ['pending', 'redacted', 'cleared', 'escalated'];
        if (!in_array($status, $valid, true)) {
            return false;
        }

        return (bool) DB::table(self::TABLE)->where('id', $findingId)->update([
            'resolution_status'   => $status,
            'resolved_at'         => $status === 'pending' ? null : date('Y-m-d H:i:s'),
            'resolved_by_user_id' => $userId,
            'updated_at'          => date('Y-m-d H:i:s'),
        ]);
    }
}
