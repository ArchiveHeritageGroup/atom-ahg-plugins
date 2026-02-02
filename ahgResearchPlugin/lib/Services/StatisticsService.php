<?php

use Illuminate\Database\Capsule\Manager as DB;

/**
 * StatisticsService - Research Analytics and Reporting
 *
 * Handles daily statistics aggregation and dashboard analytics
 * for researchers and administrators.
 *
 * @package ahgResearchPlugin
 * @version 2.0.0
 */
class StatisticsService
{
    // =========================================================================
    // DAILY AGGREGATION
    // =========================================================================

    /**
     * Aggregate statistics for a specific date.
     *
     * @param string $date Date in Y-m-d format
     * @return array Summary of aggregated stats
     */
    public function aggregateDaily(string $date): array
    {
        $stats = [];

        // Researcher registrations
        $stats['registrations'] = DB::table('research_researcher')
            ->whereDate('created_at', $date)
            ->count();

        $this->saveDailyStat($date, 'registrations', null, null, $stats['registrations']);

        // Researcher approvals
        $stats['approvals'] = DB::table('research_researcher')
            ->whereDate('approved_at', $date)
            ->count();

        $this->saveDailyStat($date, 'approvals', null, null, $stats['approvals']);

        // Bookings created
        $stats['bookings_created'] = DB::table('research_booking')
            ->whereDate('created_at', $date)
            ->count();

        $this->saveDailyStat($date, 'bookings_created', null, null, $stats['bookings_created']);

        // Bookings completed
        $stats['bookings_completed'] = DB::table('research_booking')
            ->where('status', 'completed')
            ->whereDate('checked_out_at', $date)
            ->count();

        $this->saveDailyStat($date, 'bookings_completed', null, null, $stats['bookings_completed']);

        // Materials requested
        $stats['materials_requested'] = DB::table('research_material_request')
            ->whereDate('created_at', $date)
            ->count();

        $this->saveDailyStat($date, 'materials_requested', null, null, $stats['materials_requested']);

        // Collections created
        $stats['collections_created'] = DB::table('research_collection')
            ->whereDate('created_at', $date)
            ->count();

        $this->saveDailyStat($date, 'collections_created', null, null, $stats['collections_created']);

        // Annotations created
        $stats['annotations_created'] = DB::table('research_annotation')
            ->whereDate('created_at', $date)
            ->count();

        $this->saveDailyStat($date, 'annotations_created', null, null, $stats['annotations_created']);

        // Citations generated
        $stats['citations_generated'] = DB::table('research_citation_log')
            ->whereDate('created_at', $date)
            ->count();

        $this->saveDailyStat($date, 'citations_generated', null, null, $stats['citations_generated']);

        // Saved searches created
        $stats['saved_searches'] = DB::table('research_saved_search')
            ->whereDate('created_at', $date)
            ->count();

        $this->saveDailyStat($date, 'saved_searches', null, null, $stats['saved_searches']);

        // Projects created
        $stats['projects_created'] = DB::table('research_project')
            ->whereDate('created_at', $date)
            ->count();

        $this->saveDailyStat($date, 'projects_created', null, null, $stats['projects_created']);

        // Reproduction requests
        $stats['reproduction_requests'] = DB::table('research_reproduction_request')
            ->whereDate('created_at', $date)
            ->count();

        $this->saveDailyStat($date, 'reproduction_requests', null, null, $stats['reproduction_requests']);

        // Activity by type
        $activities = DB::table('research_activity_log')
            ->whereDate('created_at', $date)
            ->selectRaw('activity_type, COUNT(*) as count')
            ->groupBy('activity_type')
            ->pluck('count', 'activity_type')
            ->toArray();

        foreach ($activities as $type => $count) {
            $this->saveDailyStat($date, 'activity', 'type', $type, $count);
        }

        $stats['activities'] = $activities;

        return $stats;
    }

    /**
     * Save a daily statistic.
     */
    private function saveDailyStat(
        string $date,
        string $statType,
        ?string $dimension,
        ?string $dimensionValue,
        int $count,
        ?float $sum = null
    ): void {
        DB::table('research_statistics_daily')->updateOrInsert(
            [
                'stat_date' => $date,
                'stat_type' => $statType,
                'dimension' => $dimension,
                'dimension_value' => $dimensionValue,
            ],
            [
                'count_value' => $count,
                'sum_value' => $sum,
                'created_at' => date('Y-m-d H:i:s'),
            ]
        );
    }

    // =========================================================================
    // RESEARCHER DASHBOARD
    // =========================================================================

    /**
     * Get statistics for researcher dashboard.
     *
     * @param int $researcherId The researcher ID
     * @return array Dashboard statistics
     */
    public function getResearcherStats(int $researcherId): array
    {
        $stats = [];

        // Collections
        $stats['collections'] = DB::table('research_collection')
            ->where('researcher_id', $researcherId)
            ->count();

        $stats['collection_items'] = DB::table('research_collection_item as ci')
            ->join('research_collection as c', 'ci.collection_id', '=', 'c.id')
            ->where('c.researcher_id', $researcherId)
            ->count();

        // Annotations
        $stats['annotations'] = DB::table('research_annotation')
            ->where('researcher_id', $researcherId)
            ->count();

        // Saved searches
        $stats['saved_searches'] = DB::table('research_saved_search')
            ->where('researcher_id', $researcherId)
            ->count();

        // Bookings
        $stats['total_bookings'] = DB::table('research_booking')
            ->where('researcher_id', $researcherId)
            ->count();

        $stats['upcoming_bookings'] = DB::table('research_booking')
            ->where('researcher_id', $researcherId)
            ->whereIn('status', ['pending', 'confirmed'])
            ->where('booking_date', '>=', date('Y-m-d'))
            ->count();

        // Projects
        $stats['projects'] = DB::table('research_project_collaborator')
            ->where('researcher_id', $researcherId)
            ->where('status', 'accepted')
            ->count();

        $stats['owned_projects'] = DB::table('research_project')
            ->where('owner_id', $researcherId)
            ->count();

        // Bibliographies
        $stats['bibliographies'] = DB::table('research_bibliography')
            ->where('researcher_id', $researcherId)
            ->count();

        // Reproduction requests
        $stats['reproduction_requests'] = DB::table('research_reproduction_request')
            ->where('researcher_id', $researcherId)
            ->count();

        $stats['pending_reproductions'] = DB::table('research_reproduction_request')
            ->where('researcher_id', $researcherId)
            ->whereIn('status', ['submitted', 'processing'])
            ->count();

        // Citations generated this month
        $stats['citations_this_month'] = DB::table('research_citation_log')
            ->where('researcher_id', $researcherId)
            ->where('created_at', '>=', date('Y-m-01'))
            ->count();

        // Activity this month
        $stats['activity_this_month'] = DB::table('research_activity_log')
            ->where('researcher_id', $researcherId)
            ->where('created_at', '>=', date('Y-m-01'))
            ->count();

        return $stats;
    }

    // =========================================================================
    // ADMIN DASHBOARD
    // =========================================================================

    /**
     * Get comprehensive admin statistics.
     *
     * @param string|null $dateFrom Start date
     * @param string|null $dateTo End date
     * @return array Admin statistics
     */
    public function getAdminStats(?string $dateFrom = null, ?string $dateTo = null): array
    {
        $dateFrom = $dateFrom ?? date('Y-m-d', strtotime('-30 days'));
        $dateTo = $dateTo ?? date('Y-m-d');

        $stats = [];
        $stats['date_range'] = ['from' => $dateFrom, 'to' => $dateTo];

        // Researcher counts
        $stats['researchers'] = [
            'total' => DB::table('research_researcher')->count(),
            'approved' => DB::table('research_researcher')->where('status', 'approved')->count(),
            'pending' => DB::table('research_researcher')->where('status', 'pending')->count(),
            'suspended' => DB::table('research_researcher')->where('status', 'suspended')->count(),
            'expired' => DB::table('research_researcher')->where('status', 'expired')->count(),
            'new_in_period' => DB::table('research_researcher')
                ->whereBetween('created_at', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59'])
                ->count(),
        ];

        // By type
        $stats['researchers']['by_type'] = DB::table('research_researcher as r')
            ->leftJoin('research_researcher_type as t', 'r.researcher_type_id', '=', 't.id')
            ->selectRaw('COALESCE(t.name, "Unassigned") as type_name, COUNT(*) as count')
            ->where('r.status', 'approved')
            ->groupBy('t.name')
            ->pluck('count', 'type_name')
            ->toArray();

        // Booking statistics
        $stats['bookings'] = [
            'total' => DB::table('research_booking')->count(),
            'in_period' => DB::table('research_booking')
                ->whereBetween('created_at', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59'])
                ->count(),
            'today' => DB::table('research_booking')
                ->where('booking_date', date('Y-m-d'))
                ->whereIn('status', ['pending', 'confirmed'])
                ->count(),
            'this_week' => DB::table('research_booking')
                ->whereBetween('booking_date', [date('Y-m-d'), date('Y-m-d', strtotime('+7 days'))])
                ->whereIn('status', ['pending', 'confirmed'])
                ->count(),
            'completed_in_period' => DB::table('research_booking')
                ->where('status', 'completed')
                ->whereBetween('checked_out_at', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59'])
                ->count(),
            'no_shows_in_period' => DB::table('research_booking')
                ->where('status', 'no_show')
                ->whereBetween('booking_date', [$dateFrom, $dateTo])
                ->count(),
        ];

        // Materials
        $stats['materials'] = [
            'requested_in_period' => DB::table('research_material_request')
                ->whereBetween('created_at', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59'])
                ->count(),
            'currently_in_use' => DB::table('research_material_request')
                ->where('status', 'in_use')
                ->count(),
        ];

        // Research activity
        $stats['activity'] = [
            'total_in_period' => DB::table('research_activity_log')
                ->whereBetween('created_at', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59'])
                ->count(),
            'by_type' => DB::table('research_activity_log')
                ->whereBetween('created_at', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59'])
                ->selectRaw('activity_type, COUNT(*) as count')
                ->groupBy('activity_type')
                ->pluck('count', 'activity_type')
                ->toArray(),
        ];

        // Collections
        $stats['collections'] = [
            'total' => DB::table('research_collection')->count(),
            'created_in_period' => DB::table('research_collection')
                ->whereBetween('created_at', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59'])
                ->count(),
            'total_items' => DB::table('research_collection_item')->count(),
        ];

        // Projects
        $stats['projects'] = [
            'total' => DB::table('research_project')->count(),
            'active' => DB::table('research_project')->where('status', 'active')->count(),
            'created_in_period' => DB::table('research_project')
                ->whereBetween('created_at', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59'])
                ->count(),
        ];

        // Citations
        $stats['citations'] = [
            'total' => DB::table('research_citation_log')->count(),
            'in_period' => DB::table('research_citation_log')
                ->whereBetween('created_at', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59'])
                ->count(),
            'by_style' => DB::table('research_citation_log')
                ->whereBetween('created_at', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59'])
                ->selectRaw('citation_style, COUNT(*) as count')
                ->groupBy('citation_style')
                ->pluck('count', 'citation_style')
                ->toArray(),
        ];

        // Reproductions
        $stats['reproductions'] = [
            'total' => DB::table('research_reproduction_request')->count(),
            'pending' => DB::table('research_reproduction_request')
                ->whereIn('status', ['submitted', 'processing'])
                ->count(),
            'completed_in_period' => DB::table('research_reproduction_request')
                ->where('status', 'completed')
                ->whereBetween('completed_at', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59'])
                ->count(),
            'revenue_in_period' => DB::table('research_reproduction_request')
                ->where('status', 'completed')
                ->whereBetween('completed_at', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59'])
                ->sum('final_cost'),
        ];

        return $stats;
    }

    // =========================================================================
    // USAGE REPORTS
    // =========================================================================

    /**
     * Generate usage report for a date range.
     *
     * @param string $dateFrom Start date
     * @param string $dateTo End date
     * @return array Usage report data
     */
    public function getUsageReport(string $dateFrom, string $dateTo): array
    {
        $report = [
            'date_range' => ['from' => $dateFrom, 'to' => $dateTo],
            'generated_at' => date('Y-m-d H:i:s'),
        ];

        // Daily trends
        $report['daily_bookings'] = DB::table('research_booking')
            ->whereBetween('booking_date', [$dateFrom, $dateTo])
            ->selectRaw('booking_date, COUNT(*) as count')
            ->groupBy('booking_date')
            ->orderBy('booking_date')
            ->pluck('count', 'booking_date')
            ->toArray();

        $report['daily_activity'] = DB::table('research_activity_log')
            ->whereBetween('created_at', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59'])
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('date')
            ->pluck('count', 'date')
            ->toArray();

        $report['daily_citations'] = DB::table('research_citation_log')
            ->whereBetween('created_at', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59'])
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('date')
            ->pluck('count', 'date')
            ->toArray();

        // Reading room utilization
        $report['room_utilization'] = DB::table('research_booking as b')
            ->join('research_reading_room as r', 'b.reading_room_id', '=', 'r.id')
            ->whereBetween('b.booking_date', [$dateFrom, $dateTo])
            ->where('b.status', '!=', 'cancelled')
            ->selectRaw('r.name, COUNT(*) as bookings, SUM(TIMESTAMPDIFF(HOUR, b.start_time, b.end_time)) as hours')
            ->groupBy('r.id', 'r.name')
            ->get()
            ->toArray();

        // Top researchers by activity
        $report['top_researchers'] = DB::table('research_activity_log as a')
            ->join('research_researcher as r', 'a.researcher_id', '=', 'r.id')
            ->whereBetween('a.created_at', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59'])
            ->selectRaw('r.id, r.first_name, r.last_name, r.institution, COUNT(*) as activity_count')
            ->groupBy('r.id', 'r.first_name', 'r.last_name', 'r.institution')
            ->orderByDesc('activity_count')
            ->limit(20)
            ->get()
            ->toArray();

        return $report;
    }

    // =========================================================================
    // ITEM ANALYTICS
    // =========================================================================

    /**
     * Get most viewed items.
     *
     * @param int $limit Number of items to return
     * @param string|null $dateFrom Start date
     * @param string|null $dateTo End date
     * @return array List of most viewed items
     */
    public function getMostViewedItems(int $limit = 20, ?string $dateFrom = null, ?string $dateTo = null): array
    {
        $query = DB::table('research_activity_log as a')
            ->leftJoin('information_object_i18n as ioi', function ($join) {
                $join->on('a.entity_id', '=', 'ioi.id')->where('ioi.culture', '=', 'en');
            })
            ->where('a.activity_type', 'view')
            ->where('a.entity_type', 'information_object')
            ->selectRaw('a.entity_id, ioi.title, COUNT(*) as view_count')
            ->groupBy('a.entity_id', 'ioi.title')
            ->orderByDesc('view_count')
            ->limit($limit);

        if ($dateFrom) {
            $query->where('a.created_at', '>=', $dateFrom . ' 00:00:00');
        }
        if ($dateTo) {
            $query->where('a.created_at', '<=', $dateTo . ' 23:59:59');
        }

        return $query->get()->toArray();
    }

    /**
     * Get most cited items.
     *
     * @param int $limit Number of items to return
     * @param string|null $dateFrom Start date
     * @param string|null $dateTo End date
     * @return array List of most cited items
     */
    public function getMostCitedItems(int $limit = 20, ?string $dateFrom = null, ?string $dateTo = null): array
    {
        $query = DB::table('research_citation_log as c')
            ->leftJoin('information_object_i18n as ioi', function ($join) {
                $join->on('c.object_id', '=', 'ioi.id')->where('ioi.culture', '=', 'en');
            })
            ->leftJoin('slug', 'c.object_id', '=', 'slug.object_id')
            ->selectRaw('c.object_id, ioi.title, slug.slug, COUNT(*) as citation_count')
            ->groupBy('c.object_id', 'ioi.title', 'slug.slug')
            ->orderByDesc('citation_count')
            ->limit($limit);

        if ($dateFrom) {
            $query->where('c.created_at', '>=', $dateFrom . ' 00:00:00');
        }
        if ($dateTo) {
            $query->where('c.created_at', '<=', $dateTo . ' 23:59:59');
        }

        return $query->get()->toArray();
    }

    /**
     * Get most collected items.
     *
     * @param int $limit Number of items to return
     * @return array List of most collected items
     */
    public function getMostCollectedItems(int $limit = 20): array
    {
        return DB::table('research_collection_item as ci')
            ->leftJoin('information_object_i18n as ioi', function ($join) {
                $join->on('ci.object_id', '=', 'ioi.id')->where('ioi.culture', '=', 'en');
            })
            ->leftJoin('slug', 'ci.object_id', '=', 'slug.object_id')
            ->selectRaw('ci.object_id, ioi.title, slug.slug, COUNT(*) as collection_count')
            ->groupBy('ci.object_id', 'ioi.title', 'slug.slug')
            ->orderByDesc('collection_count')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    /**
     * Get active researchers.
     *
     * @param int $limit Number of researchers
     * @param int $days Days to look back
     * @return array List of active researchers
     */
    public function getActiveResearchers(int $limit = 20, int $days = 30): array
    {
        $dateFrom = date('Y-m-d', strtotime("-{$days} days"));

        return DB::table('research_activity_log as a')
            ->join('research_researcher as r', 'a.researcher_id', '=', 'r.id')
            ->where('a.created_at', '>=', $dateFrom . ' 00:00:00')
            ->selectRaw('r.id, r.first_name, r.last_name, r.institution, COUNT(*) as activity_count, MAX(a.created_at) as last_active')
            ->groupBy('r.id', 'r.first_name', 'r.last_name', 'r.institution')
            ->orderByDesc('activity_count')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    // =========================================================================
    // EXPORT
    // =========================================================================

    /**
     * Export statistics to CSV.
     *
     * @param array $stats Statistics array
     * @param string $type Type of export
     * @return string CSV content
     */
    public function exportToCsv(array $stats, string $type = 'admin'): string
    {
        $output = fopen('php://temp', 'r+');

        if ($type === 'admin') {
            // Header
            fputcsv($output, ['Metric', 'Value']);

            // Researchers
            fputcsv($output, ['--- RESEARCHERS ---', '']);
            fputcsv($output, ['Total Researchers', $stats['researchers']['total'] ?? 0]);
            fputcsv($output, ['Approved', $stats['researchers']['approved'] ?? 0]);
            fputcsv($output, ['Pending', $stats['researchers']['pending'] ?? 0]);

            // Bookings
            fputcsv($output, ['--- BOOKINGS ---', '']);
            fputcsv($output, ['Total Bookings', $stats['bookings']['total'] ?? 0]);
            fputcsv($output, ['Today', $stats['bookings']['today'] ?? 0]);
            fputcsv($output, ['This Week', $stats['bookings']['this_week'] ?? 0]);

            // Projects
            fputcsv($output, ['--- PROJECTS ---', '']);
            fputcsv($output, ['Total Projects', $stats['projects']['total'] ?? 0]);
            fputcsv($output, ['Active Projects', $stats['projects']['active'] ?? 0]);

            // Citations
            fputcsv($output, ['--- CITATIONS ---', '']);
            fputcsv($output, ['Total Citations', $stats['citations']['total'] ?? 0]);
            fputcsv($output, ['In Period', $stats['citations']['in_period'] ?? 0]);
        }

        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);

        return $csv;
    }
}
