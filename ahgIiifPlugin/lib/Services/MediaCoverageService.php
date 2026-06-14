<?php

namespace AhgIiif\Services;

use Illuminate\Database\Capsule\Manager as DB;

/**
 * Derivative coverage reporting for digital objects.
 *
 * A "primary" digital object is the object-linked, top-level row
 * (object_id IS NOT NULL AND parent_id IS NULL). Its derivatives are child rows
 * (parent_id = primary.id) with usage Reference (141) or Thumbnail (142).
 * Pure read; surfaces objects that need derivative regeneration. No AI.
 */
class MediaCoverageService
{
    private const USAGE_REFERENCE = 141;
    private const USAGE_THUMBNAIL = 142;
    private const USAGE_EXTERNAL = 166;

    public function report(): array
    {
        $primary = $this->primaryCount();
        $withRef = $this->withChildUsage(self::USAGE_REFERENCE);
        $withThumb = $this->withChildUsage(self::USAGE_THUMBNAIL);

        return [
            'primary_total' => $primary,
            'with_reference' => $withRef,
            'with_thumbnail' => $withThumb,
            'reference_pct' => $primary ? (int) round($withRef * 100 / $primary) : 0,
            'thumbnail_pct' => $primary ? (int) round($withThumb * 100 / $primary) : 0,
            'missing_reference' => max(0, $primary - $withRef),
            'missing_thumbnail' => max(0, $primary - $withThumb),
            'external_uri' => (int) DB::table('digital_object')->where('usage_id', self::USAGE_EXTERNAL)->count(),
            'by_media_type' => $this->byMediaType(),
        ];
    }

    private function primaryQuery()
    {
        return DB::table('digital_object as p')
            ->whereNotNull('p.object_id')
            ->whereNull('p.parent_id');
    }

    private function primaryCount(): int
    {
        return (int) $this->primaryQuery()->count();
    }

    private function withChildUsage(int $usageId): int
    {
        return (int) $this->primaryQuery()
            ->whereExists(function ($q) use ($usageId) {
                $q->select(DB::raw(1))->from('digital_object as c')
                    ->whereColumn('c.parent_id', 'p.id')
                    ->where('c.usage_id', $usageId);
            })
            ->count();
    }

    /** @return array<string,int> media type name => primary count */
    private function byMediaType(): array
    {
        $rows = $this->primaryQuery()
            ->leftJoin('term_i18n as ti', function ($j) {
                $j->on('ti.id', '=', 'p.media_type_id')->where('ti.culture', '=', 'en');
            })
            ->select(DB::raw('COALESCE(ti.name, "Unknown") as media_type'), DB::raw('COUNT(*) as c'))
            ->groupBy('media_type')
            ->get();
        $out = [];
        foreach ($rows as $r) {
            $out[(string) $r->media_type] = (int) $r->c;
        }
        arsort($out);

        return $out;
    }

    /** Primaries lacking a derivative of the given usage. */
    public function missing(string $kind, int $limit = 100, string $culture = 'en'): array
    {
        $usageId = ('reference' === $kind) ? self::USAGE_REFERENCE : self::USAGE_THUMBNAIL;

        $rows = $this->primaryQuery()
            ->leftJoin('slug as s', 's.object_id', '=', 'p.object_id')
            ->leftJoin('information_object_i18n as ioi', function ($j) use ($culture) {
                $j->on('ioi.id', '=', 'p.object_id')->where('ioi.culture', '=', $culture);
            })
            ->whereNotExists(function ($q) use ($usageId) {
                $q->select(DB::raw(1))->from('digital_object as c')
                    ->whereColumn('c.parent_id', 'p.id')
                    ->where('c.usage_id', $usageId);
            })
            ->orderBy('p.object_id')
            ->limit($limit)
            ->get(['p.object_id', 'p.name', 'p.mime_type', 's.slug', 'ioi.title']);

        return array_map(fn ($r) => (array) $r, $rows->all());
    }
}
