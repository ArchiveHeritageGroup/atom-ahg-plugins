<?php

use AtomFramework\Http\Controllers\AhgController;
/**
 * Library Reports Module
 * Reports for library items, creators, subjects, circulation
 */

use Illuminate\Database\Capsule\Manager as DB;

class libraryReportsActions extends AhgController
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
            'items' => [
                'total' => DB::table('library_item')->count(),
                'available' => DB::table('library_item')->where('circulation_status', 'available')->count(),
                'onLoan' => DB::table('library_item')->where('circulation_status', 'on_loan')->count(),
                'reference' => DB::table('library_item')->where('circulation_status', 'reference')->count(),
            ],
            'byType' => DB::table('library_item')
                ->select('material_type', DB::raw('COUNT(*) as count'))
                ->groupBy('material_type')
                ->orderBy('count', 'desc')
                ->get()
                ->toArray(),
            'creators' => DB::table('library_item_creator')->distinct('name')->count('name'),
            'subjects' => DB::table('library_item_subject')->distinct('heading')->count('heading'),
            'recentlyAdded' => DB::table('library_item')
                ->where('created_at', '>=', date('Y-m-d', strtotime('-30 days')))
                ->count(),
        ];
    }

    public function executeCatalogue($request)
    {
        $this->checkAccess();
        
        $materialType = $request->getParameter('material_type');
        $status = $request->getParameter('status');
        $search = $request->getParameter('q');
        $callNumber = $request->getParameter('call_number');
        
        $query = DB::table('library_item as li')
            ->leftJoin('information_object_i18n as ioi', function($join) {
                $join->on('li.information_object_id', '=', 'ioi.id')->where('ioi.culture', '=', 'en');
            })
            ->select(
                'li.*',
                'ioi.title',
                DB::raw('(SELECT GROUP_CONCAT(name SEPARATOR "; ") FROM library_item_creator WHERE library_item_id = li.id AND role = "author" LIMIT 3) as authors')
            );
        
        if ($materialType) {
            $query->where('li.material_type', $materialType);
        }
        if ($status) {
            $query->where('li.circulation_status', $status);
        }
        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('ioi.title', 'like', "%{$search}%")
                  ->orWhere('li.isbn', 'like', "%{$search}%")
                  ->orWhere('li.call_number', 'like', "%{$search}%")
                  ->orWhere('li.publisher', 'like', "%{$search}%");
            });
        }
        if ($callNumber) {
            $query->where('li.call_number', 'like', "{$callNumber}%");
        }
        
        $this->items = $query->orderBy('ioi.title')->get()->toArray();
        
        $this->filters = compact('materialType', 'status', 'search', 'callNumber');
        $this->materialTypes = DB::table('library_item')->distinct()->pluck('material_type')->toArray();
        $this->statuses = ['available', 'on_loan', 'reference', 'processing', 'missing', 'withdrawn'];
    }

    public function executeCreators($request)
    {
        $this->checkAccess();
        
        $role = $request->getParameter('role');
        $search = $request->getParameter('q');
        
        $query = DB::table('library_item_creator')
            ->select('name', 'role', DB::raw('COUNT(*) as item_count'))
            ->groupBy('name', 'role');
        
        if ($role) {
            $query->where('role', $role);
        }
        if ($search) {
            $query->where('name', 'like', "%{$search}%");
        }
        
        $this->creators = $query->orderBy('item_count', 'desc')->get()->toArray();
        
        $this->filters = compact('role', 'search');
        $this->roles = DB::table('library_item_creator')->distinct()->pluck('role')->toArray();
        
        $this->summary = [
            'totalCreators' => DB::table('library_item_creator')->distinct('name')->count('name'),
            'byRole' => DB::table('library_item_creator')
                ->select('role', DB::raw('COUNT(DISTINCT name) as count'))
                ->groupBy('role')
                ->get()
                ->toArray(),
        ];
    }

    public function executeSubjects($request)
    {
        $this->checkAccess();
        
        $subjectType = $request->getParameter('subject_type');
        $source = $request->getParameter('source');
        $search = $request->getParameter('q');
        
        $query = DB::table('library_item_subject')
            ->select('heading', 'subject_type', 'source', DB::raw('COUNT(*) as item_count'))
            ->groupBy('heading', 'subject_type', 'source');
        
        if ($subjectType) {
            $query->where('subject_type', $subjectType);
        }
        if ($source) {
            $query->where('source', $source);
        }
        if ($search) {
            $query->where('heading', 'like', "%{$search}%");
        }
        
        $this->subjects = $query->orderBy('item_count', 'desc')->get()->toArray();
        
        $this->filters = compact('subjectType', 'source', 'search');
        $this->subjectTypes = DB::table('library_item_subject')->distinct()->whereNotNull('subject_type')->pluck('subject_type')->toArray();
        $this->sources = DB::table('library_item_subject')->distinct()->whereNotNull('source')->pluck('source')->toArray();
    }

    public function executePublishers($request)
    {
        $this->checkAccess();
        
        $this->publishers = DB::table('library_item')
            ->select('publisher', 'publication_place', DB::raw('COUNT(*) as item_count'))
            ->whereNotNull('publisher')
            ->where('publisher', '!=', '')
            ->groupBy('publisher', 'publication_place')
            ->orderBy('item_count', 'desc')
            ->get()
            ->toArray();
    }

    public function executeCallNumbers($request)
    {
        $this->checkAccess();
        
        $this->callNumbers = DB::table('library_item as li')
            ->leftJoin('information_object_i18n as ioi', function($join) {
                $join->on('li.information_object_id', '=', 'ioi.id')->where('ioi.culture', '=', 'en');
            })
            ->select('li.call_number', 'li.classification_scheme', 'li.shelf_location', 'ioi.title', 'li.material_type')
            ->whereNotNull('li.call_number')
            ->orderBy('li.call_number')
            ->get()
            ->toArray();
        
        $this->summary = [
            'withCallNumber' => DB::table('library_item')->whereNotNull('call_number')->where('call_number', '!=', '')->count(),
            'withoutCallNumber' => DB::table('library_item')->where(function($q) { $q->whereNull('call_number')->orWhere('call_number', ''); })->count(),
            'byScheme' => DB::table('library_item')
                ->select('classification_scheme', DB::raw('COUNT(*) as count'))
                ->whereNotNull('classification_scheme')
                ->groupBy('classification_scheme')
                ->get()
                ->toArray(),
        ];
    }

    public function executeExportCsv($request)
    {
        $this->checkAccess();
        
        $report = $request->getParameter('report');
        $filename = 'library_' . $report . '_' . date('Y-m-d') . '.csv';
        
        $this->getResponse()->setContentType('text/csv');
        $this->getResponse()->setHttpHeader('Content-Disposition', 'attachment; filename="' . $filename . '"');
        
        $output = fopen('php://output', 'w');
        
        switch ($report) {
            case 'catalogue':
                $data = DB::table('library_item as li')
                    ->leftJoin('information_object_i18n as ioi', function($join) {
                        $join->on('li.information_object_id', '=', 'ioi.id')->where('ioi.culture', '=', 'en');
                    })
                    ->select('ioi.title', 'li.material_type', 'li.call_number', 'li.isbn', 'li.publisher', 'li.publication_date', 'li.circulation_status')
                    ->get()
                    ->toArray();
                break;
            case 'creators':
                $data = DB::table('library_item_creator')
                    ->select('name', 'role', DB::raw('COUNT(*) as item_count'))
                    ->groupBy('name', 'role')
                    ->orderBy('name')
                    ->get()
                    ->toArray();
                break;
            case 'subjects':
                $data = DB::table('library_item_subject')
                    ->select('heading', 'subject_type', 'source', DB::raw('COUNT(*) as item_count'))
                    ->groupBy('heading', 'subject_type', 'source')
                    ->orderBy('heading')
                    ->get()
                    ->toArray();
                break;
            default:
                $data = [];
        }
        
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
