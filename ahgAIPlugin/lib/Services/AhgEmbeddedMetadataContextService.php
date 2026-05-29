<?php

declare(strict_types=1);

/**
 * AhgEmbeddedMetadataContextService
 *
 * Surfaces embedded EXIF/IPTC metadata (already extracted into
 * dam_iptc_metadata / digital_object_metadata) as AI context hints — capture
 * date, place, creator, subjects — so NER/HTR/LLM services can be primed and
 * stop hallucinating dates/places. Heratio #750 parity.
 *
 * Privacy gate: GPS coordinates are dropped from the place hint while a
 * gps_coordinate finding is pending in ahg_pii_finding_embedded (#751). When
 * the privacy gate is unavailable the service degrades to text-only place
 * hints (city/country), never raw coordinates — fail-safe for GPS.
 *
 * @package    ahgAIPlugin
 * @subpackage Services
 */
class AhgEmbeddedMetadataContextService
{
    /** @var array<int, AhgAiContextHints> per-request cache, keyed by IO id */
    private array $cache = [];

    /**
     * Hints for an information object (reads its IPTC row + digital objects).
     */
    public function forInformationObject(?int $ioId): AhgAiContextHints
    {
        if (!$ioId || $ioId <= 0) {
            return AhgAiContextHints::empty();
        }
        if (array_key_exists($ioId, $this->cache)) {
            return $this->cache[$ioId];
        }

        return $this->cache[$ioId] = $this->build($ioId);
    }

    /**
     * Hints for a digital object (resolves its parent IO).
     */
    public function forDigitalObject(?int $doId): AhgAiContextHints
    {
        if (!$doId || $doId <= 0) {
            return AhgAiContextHints::empty();
        }
        try {
            $ioId = (int) \Illuminate\Database\Capsule\Manager::table('digital_object')->where('id', $doId)->value('object_id');

            return $this->forInformationObject($ioId);
        } catch (\Throwable $e) {
            return AhgAiContextHints::empty();
        }
    }

    private function build(int $ioId): AhgAiContextHints
    {
        $date = $place = $creator = null;
        $subjects = [];
        $gpsLat = $gpsLng = null;
        $placeParts = [];

        try {
            $db = \Illuminate\Database\Capsule\Manager::class;

            if ($db::schema()->hasTable('dam_iptc_metadata')) {
                $iptc = $db::table('dam_iptc_metadata')->where('object_id', $ioId)->first();
                if ($iptc) {
                    $date    = $this->firstNonEmpty([$iptc->date_created ?? null]);
                    $creator = $this->firstNonEmpty([$iptc->creator ?? null]);
                    foreach (['city', 'state_province', 'country'] as $c) {
                        if (!empty($iptc->{$c})) {
                            $placeParts[] = trim((string) $iptc->{$c});
                        }
                    }
                    $gpsLat = $iptc->gps_latitude ?? null;
                    $gpsLng = $iptc->gps_longitude ?? null;
                    $subjects = $this->parseKeywords((string) ($iptc->keywords ?? ''));
                }
            }

            // digital_object_metadata (per-DO embedded block) fills gaps.
            if ($db::schema()->hasTable('digital_object_metadata')) {
                $doIds = $db::table('digital_object')->where('object_id', $ioId)->pluck('id')->all();
                if ($doIds) {
                    $dom = $db::table('digital_object_metadata')->whereIn('digital_object_id', $doIds)->first();
                    if ($dom) {
                        $date    = $date ?: $this->firstNonEmpty([$dom->date_created ?? null]);
                        $creator = $creator ?: $this->firstNonEmpty([$dom->creator ?? null]);
                        $gpsLat  = $gpsLat ?: ($dom->gps_latitude ?? null);
                        $gpsLng  = $gpsLng ?: ($dom->gps_longitude ?? null);
                        if (!$subjects) {
                            $subjects = $this->parseKeywords((string) ($dom->keywords ?? ''));
                        }
                    }
                }
            }
        } catch (\Throwable $e) {
            return AhgAiContextHints::empty();
        }

        // Place hint: prefer textual city/country; append GPS coords ONLY when
        // not gated. GPS is fail-safe — suppressed whenever the gate is unsure.
        $place = $placeParts ? implode(', ', array_unique($placeParts)) : null;
        if (($gpsLat || $gpsLng) && !$this->gpsGated($ioId)) {
            $coord = trim((string) $gpsLat . ',' . (string) $gpsLng, ',');
            $place = $place ? ($place . ' (' . $coord . ')') : $coord;
        }

        return new AhgAiContextHints($date, $place, $creator, $subjects);
    }

    /**
     * True when GPS must be withheld: a pending finding exists, OR the privacy
     * gate is unavailable (fail-safe for coordinates specifically).
     */
    private function gpsGated(int $ioId): bool
    {
        $gateFile = \sfConfig::get('sf_plugins_dir') . '/ahgPrivacyPlugin/lib/Service/EmbeddedMetadataPiiGate.php';
        if (!is_file($gateFile)) {
            return true; // no gate installed → do not leak coordinates
        }
        try {
            require_once $gateFile;
            $gate = new \ahgPrivacyPlugin\Service\EmbeddedMetadataPiiGate();

            return $gate->hasPendingGpsForIo($ioId);
        } catch (\Throwable $e) {
            return true; // gate errored → withhold coordinates
        }
    }

    private function firstNonEmpty(array $candidates): ?string
    {
        foreach ($candidates as $c) {
            $c = trim((string) $c);
            if ($c !== '') {
                return $c;
            }
        }

        return null;
    }

    /** Parse IPTC keywords (JSON array or delimited string). */
    private function parseKeywords(string $raw): array
    {
        $raw = trim($raw);
        if ($raw === '') {
            return [];
        }
        if ($raw[0] === '[' || $raw[0] === '"') {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                $flat = [];
                array_walk_recursive($decoded, static function ($v) use (&$flat) {
                    if (is_string($v) && trim($v) !== '') {
                        $flat[] = trim($v);
                    }
                });

                return array_values(array_unique($flat));
            }
        }
        $parts = preg_split('/[\r\n;,|]+/', $raw) ?: [];

        return array_values(array_unique(array_filter(array_map('trim', $parts), fn ($v) => $v !== '')));
    }

    /**
     * Best-effort audit that embedded context was used for an inference.
     */
    public function logContextEvent(string $service, int $ioId, AhgAiContextHints $hints): void
    {
        try {
            $db = \Illuminate\Database\Capsule\Manager::class;
            if ($db::schema()->hasTable('ahg_error_log')) {
                $db::table('ahg_error_log')->insert([
                    'level'      => 'info',
                    'message'    => sprintf('inference_context_used service=%s io=%d hints=%s', $service, $ioId, json_encode($hints->toArray())),
                    'created_at' => date('Y-m-d H:i:s'),
                ]);
            }
        } catch (\Throwable $e) {
            // observability only
        }
    }
}
