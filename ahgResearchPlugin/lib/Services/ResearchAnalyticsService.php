<?php

/**
 * ResearchAnalyticsService - aggregate activity log + citation log into a
 * date-filtered dashboard. No new audit tables; data is already logged.
 *
 * Spec: docs/atom-heratio-research-enhancements-spec.md §2.2
 */

use Illuminate\Database\Capsule\Manager as DB;

class ResearchAnalyticsService
{
    /**
     * Build the dashboard payload for a date range.
     *
     * @param string|null $from YYYY-MM-DD (default: 30 days ago)
     * @param string|null $to   YYYY-MM-DD (default: today)
     */
    public function dashboard(?string $from = null, ?string $to = null): array
    {
        $to   = $to   ?: date('Y-m-d');
        $from = $from ?: date('Y-m-d', strtotime('-30 days'));

        // Hour-truncated boundaries so today's activity is included
        $fromDt = $from . ' 00:00:00';
        $toDt   = $to   . ' 23:59:59';

        return [
            'period' => ['from' => $from, 'to' => $to],
            'usage_totals'            => $this->usageTotals($fromDt, $toDt),
            'daily_series'            => $this->dailySeries($fromDt, $toDt),
            'top_activity_types'      => $this->topActivityTypes($fromDt, $toDt),
            'top_researchers'         => $this->topResearchers($fromDt, $toDt),
            'popular_collections'     => $this->popularCollections($fromDt, $toDt),
            'popular_descriptions'    => $this->popularDescriptions($fromDt, $toDt),
            'search_terms'            => $this->topSearchTerms($fromDt, $toDt),
            'citations_by_style'      => $this->citationsByStyle($fromDt, $toDt),
            'date_range_distribution' => $this->dateRangeDistribution($fromDt, $toDt),
        ];
    }

    protected function usageTotals(string $from, string $to): array
    {
        $base = DB::table('research_activity_log')->whereBetween('created_at', [$from, $to]);

        $total       = (clone $base)->count();
        $researchers = (clone $base)->distinct()->count('researcher_id');
        $objects     = (clone $base)->whereNotNull('entity_id')->distinct()->count('entity_id');

        $byType = (clone $base)
            ->select('activity_type', DB::raw('COUNT(*) AS n'))
            ->groupBy('activity_type')
            ->pluck('n', 'activity_type')
            ->all();

        return [
            'total'        => $total,
            'researchers'  => $researchers,
            'objects'      => $objects,
            'views'        => (int) ($byType['view']        ?? 0),
            'searches'     => (int) ($byType['search']      ?? 0) + (int) ($byType['search_cross_fonds'] ?? 0),
            'citations'    => (int) ($byType['cite']        ?? 0) + (int) ($byType['cite_export']        ?? 0),
            'downloads'    => (int) ($byType['download']    ?? 0),
            'annotations'  => (int) ($byType['annotate']    ?? 0),
            'ai_studio'    => (int) ($byType['ai_studio']   ?? 0),
            'notebook_adds'=> (int) ($byType['notebook_item_added'] ?? 0),
        ];
    }

    protected function dailySeries(string $from, string $to): array
    {
        return DB::table('research_activity_log')
            ->whereBetween('created_at', [$from, $to])
            ->select(DB::raw('DATE(created_at) AS d'), DB::raw('COUNT(*) AS n'))
            ->groupBy('d')
            ->orderBy('d')
            ->get()
            ->map(fn ($r) => ['date' => $r->d, 'count' => (int) $r->n])
            ->all();
    }

    protected function topActivityTypes(string $from, string $to): array
    {
        return DB::table('research_activity_log')
            ->whereBetween('created_at', [$from, $to])
            ->select('activity_type', DB::raw('COUNT(*) AS n'))
            ->groupBy('activity_type')
            ->orderByDesc('n')
            ->limit(15)
            ->get()
            ->map(fn ($r) => ['type' => $r->activity_type, 'count' => (int) $r->n])
            ->all();
    }

    protected function topResearchers(string $from, string $to): array
    {
        return DB::table('research_activity_log as l')
            ->leftJoin('research_researcher as r', 'l.researcher_id', '=', 'r.id')
            ->whereBetween('l.created_at', [$from, $to])
            ->select(
                'l.researcher_id',
                'r.first_name',
                'r.last_name',
                DB::raw('COUNT(*) AS n')
            )
            ->groupBy('l.researcher_id', 'r.first_name', 'r.last_name')
            ->orderByDesc('n')
            ->limit(10)
            ->get()
            ->map(fn ($r) => [
                'researcher_id' => (int) $r->researcher_id,
                'name'          => trim(($r->first_name ?? '') . ' ' . ($r->last_name ?? '')) ?: 'Unknown',
                'count'         => (int) $r->n,
            ])
            ->all();
    }

    protected function popularCollections(string $from, string $to): array
    {
        return DB::table('research_activity_log as l')
            ->leftJoin('research_collection as c', 'l.entity_id', '=', 'c.id')
            ->whereBetween('l.created_at', [$from, $to])
            ->where('l.entity_type', 'collection')
            ->select('l.entity_id', 'c.name', DB::raw('COUNT(*) AS n'))
            ->groupBy('l.entity_id', 'c.name')
            ->orderByDesc('n')
            ->limit(10)
            ->get()
            ->map(fn ($r) => [
                'id'    => (int) $r->entity_id,
                'name'  => $r->name ?? '#' . $r->entity_id,
                'count' => (int) $r->n,
            ])
            ->all();
    }

    protected function popularDescriptions(string $from, string $to): array
    {
        $culture = $this->culture();
        return DB::table('research_activity_log as l')
            ->leftJoin('information_object_i18n as ioi', function ($join) use ($culture) {
                $join->on('l.entity_id', '=', 'ioi.id')->where('ioi.culture', '=', $culture);
            })
            ->whereBetween('l.created_at', [$from, $to])
            ->where('l.entity_type', 'information_object')
            ->select('l.entity_id', 'ioi.title', DB::raw('COUNT(*) AS n'))
            ->groupBy('l.entity_id', 'ioi.title')
            ->orderByDesc('n')
            ->limit(10)
            ->get()
            ->map(fn ($r) => [
                'id'    => (int) $r->entity_id,
                'title' => $r->title ?: '#' . $r->entity_id,
                'count' => (int) $r->n,
            ])
            ->all();
    }

    protected function topSearchTerms(string $from, string $to): array
    {
        // Search terms live in details JSON for activity_type='search'
        $rows = DB::table('research_activity_log')
            ->whereBetween('created_at', [$from, $to])
            ->whereIn('activity_type', ['search', 'search_cross_fonds'])
            ->select('details')
            ->get();

        $counts = [];
        foreach ($rows as $r) {
            $d = json_decode((string) $r->details, true);
            if (!is_array($d)) continue;
            $q = $d['q'] ?? ($d['query'] ?? null);
            if (!$q) continue;
            $q = mb_strtolower(trim((string) $q));
            if ($q === '' || mb_strlen($q) < 2) continue;
            $counts[$q] = ($counts[$q] ?? 0) + 1;
        }
        arsort($counts);
        $out = [];
        foreach (array_slice($counts, 0, 15, true) as $term => $n) {
            $out[] = ['term' => $term, 'count' => $n];
        }
        return $out;
    }

    protected function citationsByStyle(string $from, string $to): array
    {
        // Existing logCitation writes to research_citation_log; for the new
        // export action we also log to activity_log with details.format. Pull
        // both for a complete picture.
        $out = [];

        if ($this->tableExists('research_citation_log')) {
            $rows = DB::table('research_citation_log')
                ->whereBetween('created_at', [$from, $to])
                ->select('citation_style', DB::raw('COUNT(*) AS n'))
                ->groupBy('citation_style')
                ->orderByDesc('n')
                ->get();
            foreach ($rows as $r) {
                $out[$r->citation_style] = (int) $r->n;
            }
        }

        $exportRows = DB::table('research_activity_log')
            ->whereBetween('created_at', [$from, $to])
            ->where('activity_type', 'cite_export')
            ->select('details')
            ->get();
        foreach ($exportRows as $r) {
            $d = json_decode((string) $r->details, true);
            if (!is_array($d) || empty($d['format'])) continue;
            $out[$d['format']] = ($out[$d['format']] ?? 0) + 1;
        }

        arsort($out);
        $result = [];
        foreach ($out as $style => $n) {
            $result[] = ['style' => $style, 'count' => $n];
        }
        return $result;
    }

    protected function dateRangeDistribution(string $from, string $to): array
    {
        // 0 = Monday, 6 = Sunday by ISO day-of-week
        $rows = DB::table('research_activity_log')
            ->whereBetween('created_at', [$from, $to])
            ->select(DB::raw('DAYOFWEEK(created_at) AS dow'), DB::raw('COUNT(*) AS n'))
            ->groupBy('dow')
            ->orderBy('dow')
            ->get();
        $names = [1 => 'Sun', 2 => 'Mon', 3 => 'Tue', 4 => 'Wed', 5 => 'Thu', 6 => 'Fri', 7 => 'Sat'];
        $out = [];
        foreach ($rows as $r) {
            $out[] = ['day' => $names[$r->dow] ?? '?', 'count' => (int) $r->n];
        }
        return $out;
    }

    protected function tableExists(string $name): bool
    {
        try {
            return DB::getSchemaBuilder()->hasTable($name);
        } catch (\Throwable $e) {
            return false;
        }
    }

    protected function culture(): string
    {
        if (class_exists('\\AtomExtensions\\Helpers\\CultureHelper')) {
            return \AtomExtensions\Helpers\CultureHelper::getCulture();
        }
        return class_exists('\\sfContext') ? \sfContext::getInstance()->getUser()->getCulture() : 'en';
    }
}
