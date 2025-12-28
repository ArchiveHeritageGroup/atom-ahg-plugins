<?php
/**
 * DAM Reports Module
 * Reports for digital assets, IPTC metadata, file types, storage
 */

use Illuminate\Database\Capsule\Manager as DB;

class damReportsActions extends sfActions
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
        
        $this->stats = [
            'total' => DB::table('digital_object')->count(),
            'totalSize' => DB::table('digital_object')->sum('byte_size'),
            'byMimeType' => DB::table('digital_object')
                ->select('mime_type', DB::raw('COUNT(*) as count'), DB::raw('SUM(byte_size) as size'))
                ->groupBy('mime_type')
                ->orderBy('count', 'desc')
                ->limit(10)
                ->get()
                ->toArray(),
            'byMediaType' => DB::table('digital_object as d')
                ->leftJoin('term_i18n as t', function($join) {
                    $join->on('d.media_type_id', '=', 't.id')->where('t.culture', '=', 'en');
                })
                ->select('t.name as media_type', DB::raw('COUNT(*) as count'))
                ->groupBy('d.media_type_id', 't.name')
                ->orderBy('count', 'desc')
                ->get()
                ->toArray(),
            'withMetadata' => DB::table('digital_object_metadata')->count(),
            'withIptc' => DB::table('dam_iptc_metadata')->count(),
            'withGps' => DB::table('digital_object_metadata')
                ->whereNotNull('gps_latitude')
                ->whereNotNull('gps_longitude')
                ->count(),
            'recentUploads' => DB::table('digital_object as d')
                ->join('object as o', 'd.id', '=', 'o.id')
                ->where('o.created_at', '>=', date('Y-m-d', strtotime('-30 days')))
                ->count(),
        ];
    }

    public function executeAssets(sfWebRequest $request)
    {
        $this->checkAccess();
        
        $mimeType = $request->getParameter('mime_type');
        $mediaType = $request->getParameter('media_type');
        $minSize = $request->getParameter('min_size');
        $maxSize = $request->getParameter('max_size');
        
        $query = DB::table('digital_object as d')
            ->leftJoin('information_object as io', 'd.object_id', '=', 'io.id')
            ->leftJoin('information_object_i18n as ioi', function($join) {
                $join->on('io.id', '=', 'ioi.id')->where('ioi.culture', '=', 'en');
            })
            ->leftJoin('term_i18n as t', function($join) {
                $join->on('d.media_type_id', '=', 't.id')->where('t.culture', '=', 'en');
            })
            ->select('d.*', 'ioi.title', 't.name as media_type_name');
        
        if ($mimeType) {
            $query->where('d.mime_type', $mimeType);
        }
        if ($mediaType) {
            $query->where('d.media_type_id', $mediaType);
        }
        if ($minSize) {
            $query->where('d.byte_size', '>=', $minSize * 1024 * 1024);
        }
        if ($maxSize) {
            $query->where('d.byte_size', '<=', $maxSize * 1024 * 1024);
        }
        
        $this->assets = $query->orderBy('d.byte_size', 'desc')->limit(500)->get()->toArray();
        
        $this->filters = compact('mimeType', 'mediaType', 'minSize', 'maxSize');
        $this->mimeTypes = DB::table('digital_object')->distinct()->whereNotNull('mime_type')->pluck('mime_type')->toArray();
        $this->mediaTypes = DB::table('digital_object as d')
            ->leftJoin('term_i18n as t', function($join) {
                $join->on('d.media_type_id', '=', 't.id')->where('t.culture', '=', 'en');
            })
            ->select('d.media_type_id', 't.name')
            ->distinct()
            ->whereNotNull('d.media_type_id')
            ->get()
            ->toArray();
    }

    public function executeMetadata(sfWebRequest $request)
    {
        $this->checkAccess();
        
        $fileType = $request->getParameter('file_type');
        $hasGps = $request->getParameter('has_gps');
        
        $query = DB::table('digital_object_metadata as m')
            ->leftJoin('digital_object as d', 'm.digital_object_id', '=', 'd.id')
            ->leftJoin('information_object_i18n as ioi', function($join) {
                $join->on('d.object_id', '=', 'ioi.id')->where('ioi.culture', '=', 'en');
            })
            ->select('m.*', 'd.name as filename', 'd.mime_type', 'ioi.title');
        
        if ($fileType) {
            $query->where('m.file_type', $fileType);
        }
        if ($hasGps) {
            $query->whereNotNull('m.gps_latitude')->whereNotNull('m.gps_longitude');
        }
        
        $this->metadata = $query->orderBy('m.created_at', 'desc')->limit(500)->get()->toArray();
        
        $this->filters = compact('fileType', 'hasGps');
        $this->fileTypes = ['image', 'pdf', 'office', 'video', 'audio', 'other'];
        
        $this->summary = [
            'byFileType' => DB::table('digital_object_metadata')
                ->select('file_type', DB::raw('COUNT(*) as count'))
                ->groupBy('file_type')
                ->get()
                ->toArray(),
            'withGps' => DB::table('digital_object_metadata')
                ->whereNotNull('gps_latitude')
                ->count(),
        ];
    }

    public function executeIptc(sfWebRequest $request)
    {
        $this->checkAccess();
        
        $creator = $request->getParameter('creator');
        $country = $request->getParameter('country');
        $licenseType = $request->getParameter('license_type');
        
        $query = DB::table('dam_iptc_metadata as i')
            ->leftJoin('digital_object as d', 'i.object_id', '=', 'd.id')
            ->leftJoin('information_object_i18n as ioi', function($join) {
                $join->on('d.object_id', '=', 'ioi.id')->where('ioi.culture', '=', 'en');
            })
            ->select('i.*', 'd.name as filename', 'ioi.title as record_title');
        
        if ($creator) {
            $query->where('i.creator', 'like', "%{$creator}%");
        }
        if ($country) {
            $query->where('i.country', $country);
        }
        if ($licenseType) {
            $query->where('i.license_type', $licenseType);
        }
        
        $this->iptcRecords = $query->orderBy('i.created_at', 'desc')->get()->toArray();
        
        $this->filters = compact('creator', 'country', 'licenseType');
        $this->countries = DB::table('dam_iptc_metadata')->distinct()->whereNotNull('country')->pluck('country')->toArray();
        $this->licenseTypes = ['rights_managed', 'royalty_free', 'creative_commons', 'public_domain', 'editorial', 'other'];
        
        $this->summary = [
            'byLicense' => DB::table('dam_iptc_metadata')
                ->select('license_type', DB::raw('COUNT(*) as count'))
                ->whereNotNull('license_type')
                ->groupBy('license_type')
                ->get()
                ->toArray(),
            'byCountry' => DB::table('dam_iptc_metadata')
                ->select('country', DB::raw('COUNT(*) as count'))
                ->whereNotNull('country')
                ->groupBy('country')
                ->orderBy('count', 'desc')
                ->limit(10)
                ->get()
                ->toArray(),
        ];
    }

    public function executeStorage(sfWebRequest $request)
    {
        $this->checkAccess();
        
        $this->storage = [
            'total' => DB::table('digital_object')->sum('byte_size'),
            'byMimeType' => DB::table('digital_object')
                ->select('mime_type', DB::raw('COUNT(*) as count'), DB::raw('SUM(byte_size) as size'))
                ->groupBy('mime_type')
                ->orderBy('size', 'desc')
                ->get()
                ->toArray(),
            'largest' => DB::table('digital_object as d')
                ->leftJoin('information_object_i18n as ioi', function($join) {
                    $join->on('d.object_id', '=', 'ioi.id')->where('ioi.culture', '=', 'en');
                })
                ->select('d.name', 'd.mime_type', 'd.byte_size', 'ioi.title')
                ->orderBy('d.byte_size', 'desc')
                ->limit(20)
                ->get()
                ->toArray(),
            'orphaned' => DB::table('digital_object as d')
                ->leftJoin('information_object as io', 'd.object_id', '=', 'io.id')
                ->whereNull('io.id')
                ->count(),
        ];
    }

    public function executeExportCsv(sfWebRequest $request)
    {
        $this->checkAccess();
        
        $report = $request->getParameter('report');
        $filename = 'dam_' . $report . '_' . date('Y-m-d') . '.csv';
        
        $this->getResponse()->setContentType('text/csv');
        $this->getResponse()->setHttpHeader('Content-Disposition', 'attachment; filename="' . $filename . '"');
        
        $output = fopen('php://output', 'w');
        
        switch ($report) {
            case 'assets':
                $data = DB::table('digital_object')
                    ->select('name', 'mime_type', 'byte_size', 'path', 'checksum')
                    ->get()->toArray();
                break;
            case 'iptc':
                $data = DB::table('dam_iptc_metadata')
                    ->select('creator', 'headline', 'caption', 'keywords', 'country', 'city', 'license_type', 'copyright_notice')
                    ->get()->toArray();
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
