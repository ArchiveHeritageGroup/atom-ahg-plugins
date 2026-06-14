<?php

namespace AhgProvenancePlugin\Service;

use Illuminate\Database\Capsule\Manager as DB;

/**
 * Provenance coverage / completeness reporting.
 *
 * Aggregate gap analysis over provenance_record: how much of the published
 * catalogue has provenance, certainty distribution, recorded gaps, due-diligence
 * outstanding (Nazi-era, cultural property), and which published descriptions
 * have no provenance record at all.
 */
class CoverageService
{
    private const PUB_STATUS_TYPE_ID = 158;
    private const PUB_STATUS_PUBLISHED_ID = 160;

    public function report(): array
    {
        $publishedTotal = $this->publishedQuery()->count();

        $withProv = (int) $this->publishedQuery()
            ->join('provenance_record as pr', 'pr.information_object_id', '=', 'io.id')
            ->distinct()
            ->count('io.id');

        return [
            'published_total' => $publishedTotal,
            'with_provenance' => $withProv,
            'coverage_pct' => $publishedTotal ? (int) round($withProv * 100 / $publishedTotal) : 0,
            'records_total' => (int) DB::table('provenance_record')->count(),
            'by_certainty' => $this->groupCount('certainty_level'),
            'by_research_status' => $this->groupCount('research_status'),
            'with_gaps' => (int) DB::table('provenance_record')->where('has_gaps', 1)->count(),
            'incomplete' => (int) DB::table('provenance_record')->where('is_complete', 0)->count(),
            'nazi_era' => [
                'checked' => (int) DB::table('provenance_record')->where('nazi_era_provenance_checked', 1)->count(),
                'unchecked' => (int) DB::table('provenance_record')->where('nazi_era_provenance_checked', 0)->count(),
                'flagged_unclear' => (int) DB::table('provenance_record')
                    ->where('nazi_era_provenance_checked', 1)->where('nazi_era_provenance_clear', 0)->count(),
            ],
            'cultural_property' => $this->groupCount('cultural_property_status', ['none']),
        ];
    }

    /** Records explicitly flagged with provenance gaps. */
    public function gaps(int $limit = 100): array
    {
        $rows = DB::table('provenance_record as pr')
            ->leftJoin('slug as s', 's.object_id', '=', 'pr.information_object_id')
            ->where('pr.has_gaps', 1)
            ->orderBy('pr.information_object_id')
            ->limit($limit)
            ->get(['pr.information_object_id', 's.slug', 'pr.gap_description', 'pr.certainty_level', 'pr.current_status']);

        return array_map(fn ($r) => (array) $r, $rows->all());
    }

    /** Published descriptions with NO provenance record at all. */
    public function uncovered(int $limit = 100, string $culture = 'en'): array
    {
        $rows = $this->publishedQuery()
            ->leftJoin('provenance_record as pr', 'pr.information_object_id', '=', 'io.id')
            ->leftJoin('slug as s', 's.object_id', '=', 'io.id')
            ->leftJoin('information_object_i18n as ioi', function ($j) use ($culture) {
                $j->on('ioi.id', '=', 'io.id')->where('ioi.culture', '=', $culture);
            })
            ->whereNull('pr.id')
            ->orderBy('io.id')
            ->limit($limit)
            ->get(['io.id', 's.slug', 'ioi.title']);

        return array_map(fn ($r) => (array) $r, $rows->all());
    }

    private function publishedQuery()
    {
        return DB::table('information_object as io')
            ->join('status as st', function ($j) {
                $j->on('st.object_id', '=', 'io.id')
                    ->where('st.type_id', '=', self::PUB_STATUS_TYPE_ID)
                    ->where('st.status_id', '=', self::PUB_STATUS_PUBLISHED_ID);
            })
            ->where('io.id', '>', 1);
    }

    /** @return array<string,int> value => count, optionally excluding some values */
    private function groupCount(string $column, array $exclude = []): array
    {
        $q = DB::table('provenance_record')->select($column, DB::raw('COUNT(*) as c'))->groupBy($column);
        $out = [];
        foreach ($q->get() as $row) {
            $val = (string) ($row->$column ?? '');
            if ('' === $val || in_array($val, $exclude, true)) {
                continue;
            }
            $out[$val] = (int) $row->c;
        }
        arsort($out);

        return $out;
    }
}
