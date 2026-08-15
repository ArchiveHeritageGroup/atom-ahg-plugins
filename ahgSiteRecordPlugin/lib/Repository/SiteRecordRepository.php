<?php

namespace AhgSiteRecordPlugin\Repository;

use Illuminate\Database\Capsule\Manager as DB;

/**
 * Data access for site records.
 *
 * Returns raw rows, including exact coordinates. Callers must not hand these to a
 * template, an export or a response directly - everything user-facing goes through
 * LocalityVisibilityService first. SiteRecordService::present() is the safe path
 * and is what actions should use.
 */
class SiteRecordRepository
{
    /** Columns safe to order a browse by, so the sort parameter cannot be injected. */
    private const SORTABLE = ['site_number', 'date_visited', 'region_code', 'updated_at'];

    public function find(int $id): ?object
    {
        return DB::table('ahg_site_record')->where('id', $id)->first();
    }

    public function findByActor(int $actorId): ?object
    {
        return DB::table('ahg_site_record')->where('actor_id', $actorId)->first();
    }

    /**
     * Browse, joined to the authority record so the site's name comes from the
     * actor rather than being stored twice.
     */
    public function browse(array $filters = [], int $page = 1, int $perPage = 50): array
    {
        $culture = \sfContext::hasInstance()
            ? \sfContext::getInstance()->getUser()->getCulture()
            : 'en';

        $query = DB::table('ahg_site_record as sr')
            ->leftJoin('actor_i18n as ai', function ($join) use ($culture) {
                $join->on('ai.id', '=', 'sr.actor_id')->where('ai.culture', '=', $culture);
            })
            ->leftJoin('slug as sl', 'sl.object_id', '=', 'sr.actor_id')
            ->select([
                'sr.id', 'sr.actor_id', 'sr.site_number', 'sr.date_visited',
                'sr.region_code', 'sr.sub_region_code', 'sr.updated_at',
                // Locality columns are selected because the panel needs them, but
                // they are resolved through the visibility service before display.
                'sr.latitude', 'sr.longitude', 'sr.coordinate_datum', 'sr.altitude_m',
                'sr.map_sheet', 'sr.locality_original', 'sr.locality_sensitive',
                'ai.authorized_form_of_name as site_name',
                'sl.slug as actor_slug',
            ]);

        if (!empty($filters['q'])) {
            $q = '%'.$filters['q'].'%';
            $query->where(function ($w) use ($q) {
                $w->where('sr.site_number', 'like', $q)
                    ->orWhere('ai.authorized_form_of_name', 'like', $q);
            });
        }

        if (!empty($filters['region'])) {
            $query->where('sr.region_code', $filters['region']);
        }

        $sort = in_array($filters['sort'] ?? '', self::SORTABLE, true)
            ? $filters['sort']
            : 'site_number';

        $total = (clone $query)->count();

        $rows = $query->orderBy('sr.'.$sort)
            ->forPage(max(1, $page), $perPage)
            ->get()
            ->all();

        return ['rows' => $rows, 'total' => $total, 'page' => max(1, $page), 'perPage' => $perPage];
    }

    /** @return object[] */
    public function attributes(int $siteRecordId): array
    {
        return DB::table('ahg_site_attribute')
            ->where('site_record_id', $siteRecordId)
            ->orderBy('taxonomy')->orderBy('code')
            ->get()->all();
    }

    /** @return object[] */
    public function recorders(int $siteRecordId): array
    {
        return DB::table('ahg_site_recorder')
            ->where('site_record_id', $siteRecordId)
            ->orderBy('sort_order')->orderBy('id')
            ->get()->all();
    }

    public function insert(array $data): int
    {
        return (int) DB::table('ahg_site_record')->insertGetId($data);
    }

    public function update(int $id, array $data): void
    {
        DB::table('ahg_site_record')->where('id', $id)->update($data);
    }

    public function delete(int $id): void
    {
        // Attributes and recorders cascade via foreign key, so a delete cannot
        // leave orphans behind the way the legacy application did.
        DB::table('ahg_site_record')->where('id', $id)->delete();
    }

    /**
     * Replace a record's attributes with exactly the given set.
     *
     * Rows, not a JSON blob: the legacy encoding silently discarded any value the
     * processing map had not anticipated.
     *
     * @param array<string, string[]> $byTaxonomy taxonomy => list of codes
     */
    public function replaceAttributes(int $siteRecordId, array $byTaxonomy, array $notes = []): void
    {
        DB::transaction(function () use ($siteRecordId, $byTaxonomy, $notes) {
            DB::table('ahg_site_attribute')->where('site_record_id', $siteRecordId)->delete();

            $insert = [];
            foreach ($byTaxonomy as $taxonomy => $codes) {
                foreach ((array) $codes as $code) {
                    if ('' === $code || null === $code) {
                        continue;
                    }
                    $insert[] = [
                        'site_record_id' => $siteRecordId,
                        'taxonomy' => $taxonomy,
                        'code' => $code,
                        'note' => $notes[$taxonomy] ?? null,
                    ];
                }
            }

            if ($insert) {
                DB::table('ahg_site_attribute')->insert($insert);
            }
        });
    }

    /** @param array<int, array{name: string, actor_id: ?int, role_code: ?string}> $recorders */
    public function replaceRecorders(int $siteRecordId, array $recorders): void
    {
        DB::transaction(function () use ($siteRecordId, $recorders) {
            DB::table('ahg_site_recorder')->where('site_record_id', $siteRecordId)->delete();

            $insert = [];
            $order = 0;
            foreach ($recorders as $r) {
                $name = trim((string) ($r['name'] ?? ''));
                if ('' === $name) {
                    continue;
                }
                $insert[] = [
                    'site_record_id' => $siteRecordId,
                    'actor_id' => $r['actor_id'] ?? null,
                    'name' => $name,
                    'role_code' => $r['role_code'] ?? null,
                    'sort_order' => $order++,
                ];
            }

            if ($insert) {
                DB::table('ahg_site_recorder')->insert($insert);
            }
        });
    }

    /** The authority record a site record describes, for headings and links. */
    public function actor(int $actorId): ?object
    {
        $culture = \sfContext::hasInstance()
            ? \sfContext::getInstance()->getUser()->getCulture()
            : 'en';

        return DB::table('actor as a')
            ->leftJoin('actor_i18n as ai', function ($join) use ($culture) {
                $join->on('ai.id', '=', 'a.id')->where('ai.culture', '=', $culture);
            })
            ->leftJoin('slug as sl', 'sl.object_id', '=', 'a.id')
            ->where('a.id', $actorId)
            ->select(['a.id', 'ai.authorized_form_of_name as name', 'sl.slug'])
            ->first();
    }
}
