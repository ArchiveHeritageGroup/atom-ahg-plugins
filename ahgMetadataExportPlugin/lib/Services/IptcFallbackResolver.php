<?php

declare(strict_types=1);

namespace AhgMetadataExport\Services;

use Illuminate\Database\Capsule\Manager as DB;
use Throwable;

/**
 * IptcFallbackResolver — resolves creator / rights / subject for an
 * information object, falling back to extracted IPTC values in
 * dam_iptc_metadata when the canonical ISAD(G) fields are empty.
 *
 * Single source of truth so every exporter applies the same fallback policy:
 *   By-line          -> dam_iptc_metadata.creator          -> dc:creator
 *   Copyright Notice -> dam_iptc_metadata.copyright_notice -> dc:rights
 *   Keywords         -> dam_iptc_metadata.keywords         -> dc:subject (per term)
 *
 * Precedence: canonical ISAD(G) always wins; IPTC fills only empty fields.
 * Fallback hits are audited (info) to ahg_error_log, deduped per (object,field).
 * Heratio #752 parity.
 *
 * @package    ahgMetadataExportPlugin
 * @subpackage Services
 */
class IptcFallbackResolver
{
    /** @var array<int, object|null> per-request IPTC row cache */
    private static array $iptcCache = [];
    /** @var array<string, true> audit dedup */
    private static array $auditCache = [];
    private static ?bool $errorLogAvailable = null;

    /** Cached dam_iptc_metadata row (creator/copyright_notice/keywords) for an IO. */
    public function getIptc(int $objectId): ?object
    {
        if (array_key_exists($objectId, self::$iptcCache)) {
            return self::$iptcCache[$objectId];
        }
        try {
            if (!DB::schema()->hasTable('dam_iptc_metadata')) {
                return self::$iptcCache[$objectId] = null;
            }
            $row = DB::table('dam_iptc_metadata')
                ->where('object_id', $objectId)
                ->select('creator', 'copyright_notice', 'keywords')
                ->first();

            return self::$iptcCache[$objectId] = ($row ?: null);
        } catch (Throwable $e) {
            return self::$iptcCache[$objectId] = null;
        }
    }

    /** Canonical creators if any, else the IPTC By-line as a single-element list. */
    public function resolveCreatorsWithCanonical(int $objectId, array $canonical): array
    {
        $canonical = array_values(array_filter($canonical, static fn ($v) => is_string($v) && trim($v) !== ''));
        if (!empty($canonical)) {
            return $canonical;
        }
        $iptc = $this->getIptc($objectId);
        $byline = $iptc ? trim((string) ($iptc->creator ?? '')) : '';
        if ($byline === '') {
            return [];
        }
        $this->audit($objectId, 'creator', $byline);

        return [$byline];
    }

    public function resolveRightsWithCanonical(int $objectId, ?string $canonical): ?string
    {
        $canonical = trim((string) $canonical);
        if ($canonical !== '') {
            return $canonical;
        }
        $iptc = $this->getIptc($objectId);
        $rights = $iptc ? trim((string) ($iptc->copyright_notice ?? '')) : '';
        if ($rights === '') {
            return null;
        }
        $this->audit($objectId, 'rights', $rights);

        return $rights;
    }

    public function resolveSubjectsWithCanonical(int $objectId, array $canonical): array
    {
        $canonical = array_values(array_filter(
            array_map(static fn ($v) => is_string($v) ? trim($v) : '', $canonical),
            static fn ($v) => $v !== ''
        ));
        if (!empty($canonical)) {
            return $canonical;
        }
        $iptc = $this->getIptc($objectId);
        $keywords = $iptc ? $this->parseKeywords((string) ($iptc->keywords ?? '')) : [];
        if (empty($keywords)) {
            return [];
        }
        $this->audit($objectId, 'subject', implode('; ', $keywords));

        return $keywords;
    }

    /** First ISAD author (event type 111) else IPTC By-line else null. */
    public function resolveCreator(int $objectId): ?string
    {
        $canonical = [];
        try {
            if (DB::schema()->hasTable('event') && DB::schema()->hasTable('actor_i18n')) {
                $rows = DB::table('event as e')
                    ->leftJoin('actor_i18n as ai', function ($j) {
                        $j->on('e.actor_id', '=', 'ai.id')->where('ai.culture', '=', 'en');
                    })
                    ->where('e.object_id', $objectId)->where('e.type_id', 111)
                    ->whereNotNull('e.actor_id')
                    ->pluck('ai.authorized_form_of_name');
                foreach ($rows as $name) {
                    if (!empty($name)) {
                        $canonical[] = (string) $name;
                    }
                }
            }
        } catch (Throwable $e) {
            // fall through to IPTC
        }

        return $this->resolveCreatorsWithCanonical($objectId, $canonical)[0] ?? null;
    }

    /** ISAD 3.4.2 reproduction_conditions else IPTC Copyright Notice else null. */
    public function resolveRights(int $objectId): ?string
    {
        $canonical = null;
        try {
            if (DB::schema()->hasTable('information_object_i18n')) {
                $val = DB::table('information_object_i18n')->where('id', $objectId)->where('culture', 'en')->value('reproduction_conditions');
                if (!empty($val)) {
                    $canonical = strip_tags((string) $val);
                }
            }
        } catch (Throwable $e) {
            // fall through
        }

        return $this->resolveRightsWithCanonical($objectId, $canonical);
    }

    /** Subject access points (taxonomy 35) else IPTC Keywords else []. */
    public function resolveSubjects(int $objectId): array
    {
        $canonical = [];
        try {
            if (DB::schema()->hasTable('object_term_relation') && DB::schema()->hasTable('term_i18n')) {
                $rows = DB::table('object_term_relation as otr')
                    ->join('term as t', 'otr.term_id', '=', 't.id')
                    ->join('term_i18n as ti', function ($j) {
                        $j->on('t.id', '=', 'ti.id')->where('ti.culture', '=', 'en');
                    })
                    ->where('otr.object_id', $objectId)->where('t.taxonomy_id', 35)
                    ->pluck('ti.name');
                foreach ($rows as $name) {
                    if (!empty($name)) {
                        $canonical[] = (string) $name;
                    }
                }
            }
        } catch (Throwable $e) {
            // fall through
        }

        return $this->resolveSubjectsWithCanonical($objectId, $canonical);
    }

    /** Parse IPTC keywords: JSON array (preferred) or delimited string. */
    private function parseKeywords(string $raw): array
    {
        $raw = trim($raw);
        if ($raw === '') {
            return [];
        }
        if ($raw[0] === '[' || $raw[0] === '"') {
            try {
                $decoded = json_decode($raw, true, 16, JSON_THROW_ON_ERROR);
                if (is_array($decoded)) {
                    $flat = [];
                    array_walk_recursive($decoded, static function ($v) use (&$flat) {
                        if (is_string($v) && trim($v) !== '') {
                            $flat[] = trim($v);
                        }
                    });

                    return array_values(array_unique($flat));
                }
                if (is_string($decoded) && trim($decoded) !== '') {
                    return [trim($decoded)];
                }
            } catch (Throwable $e) {
                // fall through to delimited
            }
        }
        $parts = preg_split('/[\r\n;,|]+/', $raw) ?: [];
        $clean = [];
        foreach ($parts as $part) {
            $part = trim($part);
            if ($part !== '') {
                $clean[] = $part;
            }
        }

        return array_values(array_unique($clean));
    }

    private function audit(int $objectId, string $field, string $value): void
    {
        $key = $objectId . ':' . $field;
        if (isset(self::$auditCache[$key])) {
            return;
        }
        self::$auditCache[$key] = true;

        if (self::$errorLogAvailable === null) {
            try {
                self::$errorLogAvailable = DB::schema()->hasTable('ahg_error_log');
            } catch (Throwable $e) {
                self::$errorLogAvailable = false;
            }
        }
        if (!self::$errorLogAvailable) {
            return;
        }
        try {
            DB::table('ahg_error_log')->insert([
                'level'      => 'info',
                'message'    => sprintf('IPTC fallback fired for information_object.id=%d field=%s value="%s"', $objectId, $field, mb_substr($value, 0, 200)),
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        } catch (Throwable $e) {
            // audit is best-effort
        }
    }

    public static function resetCaches(): void
    {
        self::$iptcCache = [];
        self::$auditCache = [];
        self::$errorLogAvailable = null;
    }
}
