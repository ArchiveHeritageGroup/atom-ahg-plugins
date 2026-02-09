<?php
/**
 * Gallery Reports Module
 * Reports for exhibitions, artists, loans, valuations, and facilities
 */

use Illuminate\Database\Capsule\Manager as DB;

class galleryReportsActions extends AhgActions
{
    protected function checkAccess()
    {
        if (!$this->context->user->isAuthenticated()) {
            $this->forward('admin', 'secure');
        }
    }

    public function executeIndex(sfWebRequest $request)
    {
        $this->checkAccess();
        
        // Dashboard stats - using unified exhibition table from ahgExhibitionPlugin
        $this->stats = [
            'exhibitions' => [
                'total' => DB::table('exhibition')->count(),
                'open' => DB::table('exhibition')->where('status', 'open')->count(),
                'planning' => DB::table('exhibition')->where('status', 'planning')->count(),
                'upcoming' => DB::table('exhibition')
                    ->where('opening_date', '>', date('Y-m-d'))
                    ->whereNotIn('status', ['cancelled', 'canceled'])
                    ->count(),
            ],
            'artists' => [
                'total' => DB::table('gallery_artist')->count(),
                'represented' => DB::table('gallery_artist')->where('represented', 1)->count(),
                'active' => DB::table('gallery_artist')->where('is_active', 1)->count(),
            ],
            'loans' => [
                'total' => DB::table('gallery_loan')->count(),
                'active' => DB::table('gallery_loan')->where('status', 'on_loan')->count(),
                'incoming' => DB::table('gallery_loan')->where('loan_type', 'incoming')->where('status', 'on_loan')->count(),
                'outgoing' => DB::table('gallery_loan')->where('loan_type', 'outgoing')->where('status', 'on_loan')->count(),
                'pending' => DB::table('gallery_loan')->whereIn('status', ['inquiry', 'requested', 'approved'])->count(),
            ],
            'valuations' => [
                'total' => DB::table('gallery_valuation')->count(),
                'current' => DB::table('gallery_valuation')->where('is_current', 1)->count(),
                'totalValue' => DB::table('gallery_valuation')->where('is_current', 1)->sum('value_amount'),
                'expiringSoon' => DB::table('gallery_valuation')
                    ->where('is_current', 1)
                    ->where('valid_until', '<=', date('Y-m-d', strtotime('+90 days')))
                    ->where('valid_until', '>=', date('Y-m-d'))
                    ->count(),
            ],
        ];
    }

    public function executeExhibitions(sfWebRequest $request)
    {
        $this->checkAccess();

        $status = $request->getParameter('status');
        $type = $request->getParameter('type');
        $year = $request->getParameter('year');
        $dateFrom = $request->getParameter('date_from');
        $dateTo = $request->getParameter('date_to');

        // Use unified exhibition table from ahgExhibitionPlugin
        $query = DB::table('exhibition as e')
            ->leftJoin('exhibition_venue as v', 'e.venue_id', '=', 'v.id')
            ->select(
                'e.*',
                'v.name as venue_name',
                DB::raw('(SELECT COUNT(*) FROM exhibition_object WHERE exhibition_id = e.id) as object_count')
            );

        if ($status) {
            $query->where('e.status', $status);
        }
        if ($type) {
            $query->where('e.exhibition_type', $type);
        }
        if ($year) {
            $query->whereYear('e.opening_date', $year);
        }
        if ($dateFrom) {
            $query->where('e.opening_date', '>=', $dateFrom);
        }
        if ($dateTo) {
            $query->where('e.closing_date', '<=', $dateTo);
        }

        $this->exhibitions = $query->orderBy('e.opening_date', 'desc')->get()->toArray();

        $this->filters = compact('status', 'type', 'year', 'dateFrom', 'dateTo');
        $this->statuses = ['concept', 'planning', 'preparation', 'installation', 'open', 'closing', 'closed', 'archived', 'canceled'];
        $this->types = ['permanent', 'temporary', 'traveling', 'online', 'pop_up'];
        $this->years = DB::table('exhibition')
            ->selectRaw('DISTINCT YEAR(opening_date) as year')
            ->whereNotNull('opening_date')
            ->orderBy('year', 'desc')
            ->pluck('year')
            ->toArray();
    }

    public function executeArtists(sfWebRequest $request)
    {
        $this->checkAccess();
        
        $represented = $request->getParameter('represented');
        $nationality = $request->getParameter('nationality');
        $artistType = $request->getParameter('artist_type');
        $active = $request->getParameter('active');
        
        $query = DB::table('gallery_artist as a')
            ->select(
                'a.*',
                DB::raw('(SELECT COUNT(*) FROM gallery_artist_exhibition_history WHERE artist_id = a.id) as exhibition_count')
            );
        
        if ($represented !== null && $represented !== '') {
            $query->where('a.represented', (int)$represented);
        }
        if ($nationality) {
            $query->where('a.nationality', $nationality);
        }
        if ($artistType) {
            $query->where('a.artist_type', $artistType);
        }
        if ($active !== null && $active !== '') {
            $query->where('a.is_active', (int)$active);
        }
        
        $this->artists = $query->orderBy('a.display_name')->get()->toArray();
        
        $this->filters = compact('represented', 'nationality', 'artistType', 'active');
        $this->nationalities = DB::table('gallery_artist')
            ->whereNotNull('nationality')
            ->distinct()
            ->orderBy('nationality')
            ->pluck('nationality')
            ->toArray();
        $this->artistTypes = ['individual', 'collective', 'studio', 'anonymous'];
    }

    public function executeLoans(sfWebRequest $request)
    {
        $this->checkAccess();
        
        $loanType = $request->getParameter('loan_type');
        $status = $request->getParameter('status');
        $year = $request->getParameter('year');
        $overdue = $request->getParameter('overdue');
        
        $query = DB::table('gallery_loan as l')
            ->select(
                'l.*',
                DB::raw('(SELECT COUNT(*) FROM gallery_loan_object WHERE loan_id = l.id) as object_count'),
                DB::raw('DATEDIFF(l.loan_end_date, CURDATE()) as days_remaining')
            );
        
        if ($loanType) {
            $query->where('l.loan_type', $loanType);
        }
        if ($status) {
            $query->where('l.status', $status);
        }
        if ($year) {
            $query->whereYear('l.loan_start_date', $year);
        }
        if ($overdue) {
            $query->where('l.loan_end_date', '<', date('Y-m-d'))
                  ->where('l.status', 'on_loan');
        }
        
        $this->loans = $query->orderBy('l.loan_start_date', 'desc')->get()->toArray();
        
        $this->filters = compact('loanType', 'status', 'year', 'overdue');
        $this->loanTypes = ['incoming', 'outgoing'];
        $this->statuses = ['inquiry', 'requested', 'approved', 'agreed', 'in_transit_out', 'on_loan', 'in_transit_return', 'returned', 'cancelled', 'declined'];
        $this->years = DB::table('gallery_loan')
            ->selectRaw('DISTINCT YEAR(loan_start_date) as year')
            ->whereNotNull('loan_start_date')
            ->orderBy('year', 'desc')
            ->pluck('year')
            ->toArray();
        
        // Summary stats
        $this->summary = [
            'totalInsuranceValue' => DB::table('gallery_loan')->sum('insurance_value'),
            'totalLoanFees' => DB::table('gallery_loan')->sum('loan_fee'),
            'overdueCount' => DB::table('gallery_loan')
                ->where('loan_end_date', '<', date('Y-m-d'))
                ->where('status', 'on_loan')
                ->count(),
        ];
    }

    public function executeValuations(sfWebRequest $request)
    {
        $this->checkAccess();
        
        $valuationType = $request->getParameter('valuation_type');
        $current = $request->getParameter('current');
        $expiring = $request->getParameter('expiring');
        $minValue = $request->getParameter('min_value');
        $maxValue = $request->getParameter('max_value');
        
        $query = DB::table('gallery_valuation as v')
            ->leftJoin('information_object_i18n as ioi', function($join) {
                $join->on('v.object_id', '=', 'ioi.id')->where('ioi.culture', '=', 'en');
            })
            ->select('v.*', 'ioi.title as object_title');
        
        if ($valuationType) {
            $query->where('v.valuation_type', $valuationType);
        }
        if ($current !== null && $current !== '') {
            $query->where('v.is_current', (int)$current);
        }
        if ($expiring) {
            $query->where('v.valid_until', '<=', date('Y-m-d', strtotime('+90 days')))
                  ->where('v.valid_until', '>=', date('Y-m-d'))
                  ->where('v.is_current', 1);
        }
        if ($minValue) {
            $query->where('v.value_amount', '>=', $minValue);
        }
        if ($maxValue) {
            $query->where('v.value_amount', '<=', $maxValue);
        }
        
        $this->valuations = $query->orderBy('v.valuation_date', 'desc')->get()->toArray();
        
        $this->filters = compact('valuationType', 'current', 'expiring', 'minValue', 'maxValue');
        $this->valuationTypes = ['insurance', 'market', 'replacement', 'auction_estimate', 'probate', 'donation'];
        
        // Summary
        $this->summary = [
            'totalCurrentValue' => DB::table('gallery_valuation')->where('is_current', 1)->sum('value_amount'),
            'avgValue' => DB::table('gallery_valuation')->where('is_current', 1)->avg('value_amount'),
            'byType' => DB::table('gallery_valuation')
                ->where('is_current', 1)
                ->select('valuation_type', DB::raw('COUNT(*) as count'), DB::raw('SUM(value_amount) as total'))
                ->groupBy('valuation_type')
                ->get()
                ->toArray(),
        ];
    }

    public function executeFacilityReports(sfWebRequest $request)
    {
        $this->checkAccess();
        
        $approved = $request->getParameter('approved');
        $reportType = $request->getParameter('report_type');
        
        $query = DB::table('gallery_facility_report as f')
            ->leftJoin('gallery_loan as l', 'f.loan_id', '=', 'l.id')
            ->select('f.*', 'l.loan_number', 'l.institution_name as loan_institution');
        
        if ($approved !== null && $approved !== '') {
            $query->where('f.approved', (int)$approved);
        }
        if ($reportType) {
            $query->where('f.report_type', $reportType);
        }
        
        $this->reports = $query->orderBy('f.created_at', 'desc')->get()->toArray();
        
        $this->filters = compact('approved', 'reportType');
        
        // Compliance summary
        $this->compliance = [
            'withFireDetection' => DB::table('gallery_facility_report')->where('fire_detection', 1)->count(),
            'withClimateControl' => DB::table('gallery_facility_report')->where('climate_controlled', 1)->count(),
            'with24hrSecurity' => DB::table('gallery_facility_report')->where('security_24hr', 1)->count(),
            'withTrainedHandlers' => DB::table('gallery_facility_report')->where('trained_handlers', 1)->count(),
        ];
    }

    public function executeSpaces(sfWebRequest $request)
    {
        $this->checkAccess();
        
        $this->spaces = DB::table('gallery_space as s')
            ->leftJoin('gallery_venue as v', 's.venue_id', '=', 'v.id')
            ->select('s.*', 'v.name as venue_name')
            ->where('s.is_active', 1)
            ->orderBy('v.name')
            ->orderBy('s.name')
            ->get()
            ->toArray();
        
        $this->summary = [
            'totalSpaces' => count($this->spaces),
            'totalArea' => DB::table('gallery_space')->where('is_active', 1)->sum('area_sqm'),
            'totalWallLength' => DB::table('gallery_space')->where('is_active', 1)->sum('wall_length_m'),
            'climateControlled' => DB::table('gallery_space')->where('is_active', 1)->where('climate_controlled', 1)->count(),
        ];
    }

    public function executeExportCsv(sfWebRequest $request)
    {
        $this->checkAccess();
        
        $report = $request->getParameter('report');
        $data = [];
        $filename = 'gallery_report_' . date('Y-m-d') . '.csv';
        
        switch ($report) {
            case 'exhibitions':
                // Use unified exhibition table from ahgExhibitionPlugin
                $data = DB::table('exhibition')->get()->toArray();
                $filename = 'exhibitions_' . date('Y-m-d') . '.csv';
                break;
            case 'artists':
                $data = DB::table('gallery_artist')->get()->toArray();
                $filename = 'gallery_artists_' . date('Y-m-d') . '.csv';
                break;
            case 'loans':
                $data = DB::table('gallery_loan')->get()->toArray();
                $filename = 'gallery_loans_' . date('Y-m-d') . '.csv';
                break;
            case 'valuations':
                $data = DB::table('gallery_valuation')->get()->toArray();
                $filename = 'gallery_valuations_' . date('Y-m-d') . '.csv';
                break;
        }
        
        $this->getResponse()->setContentType('text/csv');
        $this->getResponse()->setHttpHeader('Content-Disposition', 'attachment; filename="' . $filename . '"');
        
        $output = fopen('php://output', 'w');
        
        if (!empty($data)) {
            // Headers
            fputcsv($output, array_keys((array)$data[0]));
            // Data
            foreach ($data as $row) {
                fputcsv($output, (array)$row);
            }
        }
        
        fclose($output);
        
        return sfView::NONE;
    }
}
