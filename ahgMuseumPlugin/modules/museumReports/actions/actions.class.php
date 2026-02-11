<?php

use AtomFramework\Http\Controllers\AhgController;
/**
 * Museum Reports Module
 * CCO-based reports for museum objects, creators, condition, provenance
 */

use Illuminate\Database\Capsule\Manager as DB;

class museumReportsActions extends AhgController
{
    protected function checkAccess()
    {
        if (!$this->getUser()->isAuthenticated()) {
            $this->forward('admin', 'secure');
        }
    }

    public function executeIndex($request)
    {
        $this->checkAccess();
        
        $this->stats = [
            'totalObjects' => DB::table('museum_metadata')->count(),
            'byWorkType' => DB::table('museum_metadata')
                ->select('work_type', DB::raw('COUNT(*) as count'))
                ->whereNotNull('work_type')
                ->groupBy('work_type')
                ->orderBy('count', 'desc')
                ->get()
                ->toArray(),
            'byClassification' => DB::table('museum_metadata')
                ->select('classification', DB::raw('COUNT(*) as count'))
                ->whereNotNull('classification')
                ->groupBy('classification')
                ->orderBy('count', 'desc')
                ->limit(10)
                ->get()
                ->toArray(),
            'byCondition' => DB::table('museum_metadata')
                ->select('condition_term', DB::raw('COUNT(*) as count'))
                ->whereNotNull('condition_term')
                ->groupBy('condition_term')
                ->get()
                ->toArray(),
            'withProvenance' => DB::table('museum_metadata')
                ->where(function($q) {
                    $q->whereNotNull('provenance')->orWhereNotNull('provenance_text');
                })
                ->count(),
            'byPeriod' => DB::table('museum_metadata')
                ->select('style_period', DB::raw('COUNT(*) as count'))
                ->whereNotNull('style_period')
                ->groupBy('style_period')
                ->orderBy('count', 'desc')
                ->limit(10)
                ->get()
                ->toArray(),
        ];
    }

    public function executeObjects($request)
    {
        $this->checkAccess();
        
        $workType = $request->getParameter('work_type');
        $classification = $request->getParameter('classification');
        $condition = $request->getParameter('condition');
        
        $query = DB::table('museum_metadata as m')
            ->leftJoin('information_object_i18n as ioi', function($join) {
                $join->on('m.object_id', '=', 'ioi.id')->where('ioi.culture', '=', 'en');
            })
            ->leftJoin('information_object as io', 'm.object_id', '=', 'io.id')
            ->leftJoin('slug as s', 'm.object_id', '=', 's.object_id')
            ->select('m.*', 'ioi.title', 'io.identifier', 's.slug');
        
        if ($workType) {
            $query->where('m.work_type', $workType);
        }
        if ($classification) {
            $query->where('m.classification', $classification);
        }
        if ($condition) {
            $query->where('m.condition_term', $condition);
        }
        
        $this->objects = $query->orderBy('ioi.title')->get()->toArray();
        
        $this->filters = compact('workType', 'classification', 'condition');
        $this->workTypes = DB::table('museum_metadata')->distinct()->whereNotNull('work_type')->pluck('work_type')->toArray();
        $this->classifications = DB::table('museum_metadata')->distinct()->whereNotNull('classification')->pluck('classification')->toArray();
        $this->conditions = DB::table('museum_metadata')->distinct()->whereNotNull('condition_term')->pluck('condition_term')->toArray();
    }

    public function executeCreators($request)
    {
        $this->checkAccess();
        
        $this->creators = DB::table('museum_metadata as m')
            ->leftJoin('information_object_i18n as ioi', function($join) {
                $join->on('m.object_id', '=', 'ioi.id')->where('ioi.culture', '=', 'en');
            })
            ->select(
                'm.creator_identity',
                'm.creator_role',
                'm.creator_attribution',
                'm.school',
                DB::raw('COUNT(*) as object_count')
            )
            ->whereNotNull('m.creator_identity')
            ->groupBy('m.creator_identity', 'm.creator_role', 'm.creator_attribution', 'm.school')
            ->orderBy('object_count', 'desc')
            ->get()
            ->toArray();
        
        $this->summary = [
            'totalCreators' => DB::table('museum_metadata')->distinct('creator_identity')->whereNotNull('creator_identity')->count(),
            'byRole' => DB::table('museum_metadata')
                ->select('creator_role', DB::raw('COUNT(*) as count'))
                ->whereNotNull('creator_role')
                ->groupBy('creator_role')
                ->get()
                ->toArray(),
        ];
    }

    public function executeConditionReport($request)
    {
        $this->checkAccess();
        
        $conditionTerm = $request->getParameter('condition_term');
        
        $query = DB::table('museum_metadata as m')
            ->leftJoin('information_object_i18n as ioi', function($join) {
                $join->on('m.object_id', '=', 'ioi.id')->where('ioi.culture', '=', 'en');
            })
            ->leftJoin('information_object as io', 'm.object_id', '=', 'io.id')
            ->leftJoin('slug as s', 'm.object_id', '=', 's.object_id')
            ->select('m.*', 'ioi.title', 'io.identifier', 's.slug')
            ->where(function($q) {
                $q->whereNotNull('m.condition_term')
                  ->orWhereNotNull('m.condition_description')
                  ->orWhereNotNull('m.treatment_type');
            });
        
        if ($conditionTerm) {
            $query->where('m.condition_term', $conditionTerm);
        }
        
        $this->records = $query->orderBy('m.condition_date', 'desc')->get()->toArray();
        
        $this->filters = compact('conditionTerm');
        $this->conditionTerms = DB::table('museum_metadata')->distinct()->whereNotNull('condition_term')->pluck('condition_term')->toArray();
        
        $this->summary = [
            'byCondition' => DB::table('museum_metadata')
                ->select('condition_term', DB::raw('COUNT(*) as count'))
                ->whereNotNull('condition_term')
                ->groupBy('condition_term')
                ->get()
                ->toArray(),
            'needingTreatment' => DB::table('museum_metadata')
                ->whereIn('condition_term', ['poor', 'critical', 'unstable'])
                ->count(),
        ];
    }

    public function executeProvenance($request)
    {
        $this->checkAccess();
        
        $this->records = DB::table('museum_metadata as m')
            ->leftJoin('information_object_i18n as ioi', function($join) {
                $join->on('m.object_id', '=', 'ioi.id')->where('ioi.culture', '=', 'en');
            })
            ->leftJoin('information_object as io', 'm.object_id', '=', 'io.id')
            ->leftJoin('slug as s', 'm.object_id', '=', 's.object_id')
            ->select('m.*', 'ioi.title', 'io.identifier', 's.slug')
            ->where(function($q) {
                $q->whereNotNull('m.provenance')
                  ->orWhereNotNull('m.provenance_text')
                  ->orWhereNotNull('m.ownership_history');
            })
            ->orderBy('ioi.title')
            ->get()
            ->toArray();
        
        $this->summary = [
            'withProvenance' => count($this->records),
            'withLegalStatus' => DB::table('museum_metadata')->whereNotNull('legal_status')->count(),
        ];
    }

    public function executeStylePeriod($request)
    {
        $this->checkAccess();
        
        $this->byStyle = DB::table('museum_metadata')
            ->select('style', DB::raw('COUNT(*) as count'))
            ->whereNotNull('style')
            ->groupBy('style')
            ->orderBy('count', 'desc')
            ->get()
            ->toArray();
        
        $this->byPeriod = DB::table('museum_metadata')
            ->select('period', DB::raw('COUNT(*) as count'))
            ->whereNotNull('period')
            ->groupBy('period')
            ->orderBy('count', 'desc')
            ->get()
            ->toArray();
        
        $this->byCulture = DB::table('museum_metadata')
            ->select('cultural_context', DB::raw('COUNT(*) as count'))
            ->whereNotNull('cultural_context')
            ->groupBy('cultural_context')
            ->orderBy('count', 'desc')
            ->get()
            ->toArray();
        
        $this->byMovement = DB::table('museum_metadata')
            ->select('movement', DB::raw('COUNT(*) as count'))
            ->whereNotNull('movement')
            ->groupBy('movement')
            ->orderBy('count', 'desc')
            ->get()
            ->toArray();
    }

    public function executeMaterials($request)
    {
        $this->checkAccess();
        
        $this->records = DB::table('museum_metadata as m')
            ->leftJoin('information_object_i18n as ioi', function($join) {
                $join->on('m.object_id', '=', 'ioi.id')->where('ioi.culture', '=', 'en');
            })
            ->select('m.materials', 'm.techniques', 'm.dimensions', 'm.measurements', 'ioi.title', 'm.object_id')
            ->where(function($q) {
                $q->whereNotNull('m.materials')->orWhereNotNull('m.techniques');
            })
            ->orderBy('ioi.title')
            ->get()
            ->toArray();
    }

    public function executeExportCsv($request)
    {
        $this->checkAccess();
        
        $report = $request->getParameter('report');
        $filename = 'museum_' . $report . '_' . date('Y-m-d') . '.csv';
        
        $this->getResponse()->setContentType('text/csv');
        $this->getResponse()->setHttpHeader('Content-Disposition', 'attachment; filename="' . $filename . '"');
        
        $output = fopen('php://output', 'w');
        
        $data = DB::table('museum_metadata as m')
            ->leftJoin('information_object_i18n as ioi', function($join) {
                $join->on('m.object_id', '=', 'ioi.id')->where('ioi.culture', '=', 'en');
            })
            ->select('ioi.title', 'm.work_type', 'm.classification', 'm.materials', 'm.techniques', 'm.condition_term', 'm.creator_identity')
            ->get()
            ->toArray();
        
        if (!empty($data)) {
            fputcsv($output, array_keys((array)$data[0]));
            foreach ($data as $row) {
                fputcsv($output, (array)$row);
            }
        }
        
        fclose($output);
        return sfView::NONE;
    }
}
