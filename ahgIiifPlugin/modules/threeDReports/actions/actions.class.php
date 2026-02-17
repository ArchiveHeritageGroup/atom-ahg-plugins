<?php

use AtomFramework\Http\Controllers\AhgController;
/**
 * 3D Object Reports Module
 * Reports for 3D models, hotspots, thumbnails, file inventory
 */

use Illuminate\Database\Capsule\Manager as DB;

class threeDReportsActions extends AhgController
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
        
        // Count 3D digital objects
        $this->digitalObjects3D = DB::table('digital_object')
            ->where(function($q) {
                $q->where('name', 'like', '%.glb')
                  ->orWhere('name', 'like', '%.gltf')
                  ->orWhere('name', 'like', '%.obj')
                  ->orWhere('name', 'like', '%.stl')
                  ->orWhere('name', 'like', '%.fbx')
                  ->orWhere('name', 'like', '%.ply')
                  ->orWhere('mime_type', 'like', '%gltf%');
            })
            ->count();
        
        $this->stats = [
            'totalModels' => DB::table('object_3d_model')->count(),
            'totalHotspots' => DB::table('object_3d_hotspot')->count(),
            'withThumbnails' => DB::table('object_3d_model')->whereNotNull('thumbnail')->where('thumbnail', '!=', '')->count(),
            'withPosters' => DB::table('object_3d_model')->whereNotNull('poster_image')->where('poster_image', '!=', '')->count(),
            'arEnabled' => DB::table('object_3d_model')->where('ar_enabled', 1)->count(),
            'byFormat' => DB::table('object_3d_model')
                ->select('format', DB::raw('COUNT(*) as count'))
                ->groupBy('format')
                ->get()
                ->toArray(),
            'totalSize' => DB::table('object_3d_model')->sum('file_size'),
            'digitalObjects3D' => $this->digitalObjects3D,
        ];
        
        $this->hotspotTypes = DB::table('object_3d_hotspot')
            ->select('hotspot_type', DB::raw('COUNT(*) as count'))
            ->groupBy('hotspot_type')
            ->get()
            ->toArray();
    }

    public function executeModels($request)
    {
        $this->checkAccess();
        
        $format = $request->getParameter('format');
        $hasThumbnail = $request->getParameter('has_thumbnail');
        $isPublic = $request->getParameter('is_public');
        
        $query = DB::table('object_3d_model as m')
            ->leftJoin('information_object_i18n as ioi', function($join) {
                $join->on('m.object_id', '=', 'ioi.id')->where('ioi.culture', '=', \AtomExtensions\Helpers\CultureHelper::getCulture());
            })
            ->leftJoin('slug as s', 'm.object_id', '=', 's.object_id')
            ->select('m.*', 'ioi.title', 's.slug');
        
        if ($format) {
            $query->where('m.format', $format);
        }
        if ($hasThumbnail === '1') {
            $query->whereNotNull('m.thumbnail')->where('m.thumbnail', '!=', '');
        } elseif ($hasThumbnail === '0') {
            $query->where(function($q) { $q->whereNull('thumbnail')->orWhere('thumbnail', ''); });
        }
        if ($isPublic !== null && $isPublic !== '') {
            $query->where('m.is_public', $isPublic);
        }
        
        $this->models = $query->orderBy('m.created_at', 'desc')->get()->toArray();
        
        $this->filters = compact('format', 'hasThumbnail', 'isPublic');
        $this->formats = ['glb', 'gltf', 'obj', 'fbx', 'stl', 'ply', 'usdz'];
    }

    public function executeHotspots($request)
    {
        $this->checkAccess();
        
        $hotspotType = $request->getParameter('hotspot_type');
        
        $query = DB::table('object_3d_hotspot as h')
            ->leftJoin('object_3d_model as m', 'h.model_id', '=', 'm.id')
            ->leftJoin('object_3d_hotspot_i18n as hi', function($join) {
                $join->on('h.id', '=', 'hi.id')->where('hi.culture', '=', \AtomExtensions\Helpers\CultureHelper::getCulture());
            })
            ->leftJoin('information_object_i18n as ioi', function($join) {
                $join->on('m.object_id', '=', 'ioi.id')->where('ioi.culture', '=', \AtomExtensions\Helpers\CultureHelper::getCulture());
            })
            ->select('h.*', 'hi.title as hotspot_title', 'hi.description', 'm.filename as model_name', 'ioi.title as object_title');
        
        if ($hotspotType) {
            $query->where('h.hotspot_type', $hotspotType);
        }
        
        $this->hotspots = $query->orderBy('h.model_id')->orderBy('h.display_order')->get()->toArray();
        
        $this->filters = compact('hotspotType');
        $this->hotspotTypes = ['annotation', 'info', 'link', 'damage', 'detail'];
        
        $this->summary = [
            'total' => count($this->hotspots),
            'byType' => DB::table('object_3d_hotspot')
                ->select('hotspot_type', DB::raw('COUNT(*) as count'))
                ->groupBy('hotspot_type')
                ->get()
                ->toArray(),
        ];
    }

    public function executeThumbnails($request)
    {
        $this->checkAccess();
        
        $this->withThumbnails = DB::table('object_3d_model as m')
            ->leftJoin('information_object_i18n as ioi', function($join) {
                $join->on('m.object_id', '=', 'ioi.id')->where('ioi.culture', '=', \AtomExtensions\Helpers\CultureHelper::getCulture());
            })
            ->leftJoin('slug as s', 'm.object_id', '=', 's.object_id')
            ->select('m.id', 'm.filename', 'm.thumbnail', 'm.poster_image', 'ioi.title', 's.slug')
            ->whereNotNull('m.thumbnail')
            ->where('m.thumbnail', '!=', '')
            ->get()
            ->toArray();
        
        $this->withoutThumbnails = DB::table('object_3d_model as m')
            ->leftJoin('information_object_i18n as ioi', function($join) {
                $join->on('m.object_id', '=', 'ioi.id')->where('ioi.culture', '=', \AtomExtensions\Helpers\CultureHelper::getCulture());
            })
            ->leftJoin('slug as s', 'm.object_id', '=', 's.object_id')
            ->select('m.id', 'm.filename', 'm.object_id', 'ioi.title', 's.slug')
            ->where(function($q) { $q->whereNull('thumbnail')->orWhere('thumbnail', ''); })
            ->get()
            ->toArray();
        
        $this->summary = [
            'withThumbnails' => count($this->withThumbnails),
            'withoutThumbnails' => count($this->withoutThumbnails),
            'withPosters' => DB::table('object_3d_model')->whereNotNull('poster_image')->where('poster_image', '!=', '')->count(),
        ];
    }

    public function executeDigitalObjects($request)
    {
        $this->checkAccess();
        
        $this->objects = DB::table('digital_object as d')
            ->leftJoin('information_object_i18n as ioi', function($join) {
                $join->on('d.object_id', '=', 'ioi.id')->where('ioi.culture', '=', \AtomExtensions\Helpers\CultureHelper::getCulture());
            })
            ->leftJoin('slug as s', 'd.object_id', '=', 's.object_id')
            ->leftJoin('object_3d_model as m', 'd.object_id', '=', 'm.object_id')
            ->select('d.id', 'd.object_id', 'd.name', 'd.mime_type', 'd.byte_size', 'd.path', 'ioi.title', 's.slug', 'm.id as model_id')
            ->where(function($q) {
                $q->where('d.name', 'like', '%.glb')
                  ->orWhere('d.name', 'like', '%.gltf')
                  ->orWhere('d.name', 'like', '%.obj')
                  ->orWhere('d.name', 'like', '%.stl')
                  ->orWhere('d.name', 'like', '%.fbx')
                  ->orWhere('d.name', 'like', '%.ply')
                  ->orWhere('d.mime_type', 'like', '%gltf%');
            })
            ->orderBy('d.byte_size', 'desc')
            ->get()
            ->toArray();
        
        $this->summary = [
            'total' => count($this->objects),
            'totalSize' => array_sum(array_column($this->objects, 'byte_size')),
            'withModel' => count(array_filter($this->objects, fn($o) => !empty($o->model_id))),
            'withoutModel' => count(array_filter($this->objects, fn($o) => empty($o->model_id))),
        ];
    }

    public function executeSettings($request)
    {
        $this->checkAccess();
        
        $this->settings = DB::table('object_3d_model as m')
            ->leftJoin('information_object_i18n as ioi', function($join) {
                $join->on('m.object_id', '=', 'ioi.id')->where('ioi.culture', '=', \AtomExtensions\Helpers\CultureHelper::getCulture());
            })
            ->select('m.id', 'm.filename', 'ioi.title', 'm.auto_rotate', 'm.rotation_speed', 
                     'm.camera_orbit', 'm.field_of_view', 'm.exposure', 'm.shadow_intensity',
                     'm.ar_enabled', 'm.ar_placement', 'm.background_color')
            ->get()
            ->toArray();
    }

    /**
     * Create model config for a digital object
     */
    public function executeCreateConfig($request)
    {
        $this->checkAccess();
        
        $digitalObjectId = $request->getParameter('do_id');
        $objectId = $request->getParameter('object_id');
        
        if (!$digitalObjectId || !$objectId) {
            $this->getUser()->setFlash('error', 'Missing parameters');
            $this->redirect(['module' => 'threeDReports', 'action' => 'digitalObjects']);
        }
        
        // Get digital object info
        $digitalObject = DB::table('digital_object')
            ->where('id', $digitalObjectId)
            ->first();
        
        if (!$digitalObject) {
            $this->getUser()->setFlash('error', 'Digital object not found');
            $this->redirect(['module' => 'threeDReports', 'action' => 'digitalObjects']);
        }
        
        // Check if config already exists
        $existing = DB::table('object_3d_model')
            ->where('object_id', $objectId)
            ->first();
        
        if ($existing) {
            $this->getUser()->setFlash('notice', 'Model config already exists');
            $this->redirect(['module' => 'threeDReports', 'action' => 'digitalObjects']);
        }
        
        // Determine format from filename
        $filename = $digitalObject->name;
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        $formatMap = ['glb' => 'glb', 'gltf' => 'gltf', 'obj' => 'obj', 'fbx' => 'fbx', 'stl' => 'stl', 'ply' => 'ply', 'usdz' => 'usdz'];
        $format = $formatMap[$ext] ?? 'glb';
        
        // Build file path
        $filePath = '/uploads/r/' . $digitalObject->path . '/' . $filename;
        
        // Insert new model config with defaults
        DB::table('object_3d_model')->insert([
            'object_id' => $objectId,
            'filename' => $filename,
            'original_filename' => $filename,
            'file_path' => $filePath,
            'file_size' => $digitalObject->byte_size,
            'mime_type' => $digitalObject->mime_type,
            'format' => $format,
            'auto_rotate' => 1,
            'rotation_speed' => 1.00,
            'camera_orbit' => '0deg 75deg 105%',
            'field_of_view' => '30deg',
            'exposure' => 1.00,
            'shadow_intensity' => 1.00,
            'shadow_softness' => 1.00,
            'background_color' => '#f5f5f5',
            'ar_enabled' => 1,
            'ar_scale' => 'auto',
            'ar_placement' => 'floor',
            'is_primary' => 1,
            'is_public' => 1,
            'display_order' => 0,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        
        $this->getUser()->setFlash('notice', 'Model config created for: ' . $filename);
        $this->redirect(['module' => 'threeDReports', 'action' => 'digitalObjects']);
    }

    /**
     * Bulk create configs for all unconfigured 3D files
     */
    public function executeBulkCreateConfig($request)
    {
        $this->checkAccess();
        
        // Get all 3D digital objects without configs
        $unconfigured = DB::table('digital_object as d')
            ->leftJoin('object_3d_model as m', 'd.object_id', '=', 'm.object_id')
            ->whereNull('m.id')
            ->where(function($q) {
                $q->where('d.name', 'like', '%.glb')
                  ->orWhere('d.name', 'like', '%.gltf')
                  ->orWhere('d.name', 'like', '%.obj')
                  ->orWhere('d.name', 'like', '%.stl')
                  ->orWhere('d.name', 'like', '%.fbx')
                  ->orWhere('d.name', 'like', '%.ply')
                  ->orWhere('d.mime_type', 'like', '%gltf%');
            })
            ->select('d.*')
            ->get();
        
        $created = 0;
        $formatMap = ['glb' => 'glb', 'gltf' => 'gltf', 'obj' => 'obj', 'fbx' => 'fbx', 'stl' => 'stl', 'ply' => 'ply', 'usdz' => 'usdz'];
        
        foreach ($unconfigured as $do) {
            $ext = strtolower(pathinfo($do->name, PATHINFO_EXTENSION));
            $format = $formatMap[$ext] ?? 'glb';
            $filePath = '/uploads/r/' . $do->path . '/' . $do->name;
            
            DB::table('object_3d_model')->insert([
                'object_id' => $do->object_id,
                'filename' => $do->name,
                'original_filename' => $do->name,
                'file_path' => $filePath,
                'file_size' => $do->byte_size,
                'mime_type' => $do->mime_type,
                'format' => $format,
                'auto_rotate' => 1,
                'rotation_speed' => 1.00,
                'camera_orbit' => '0deg 75deg 105%',
                'field_of_view' => '30deg',
                'exposure' => 1.00,
                'shadow_intensity' => 1.00,
                'shadow_softness' => 1.00,
                'background_color' => '#f5f5f5',
                'ar_enabled' => 1,
                'ar_scale' => 'auto',
                'ar_placement' => 'floor',
                'is_primary' => 1,
                'is_public' => 1,
                'display_order' => 0,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
            $created++;
        }
        
        $this->getUser()->setFlash('notice', "Created {$created} model configurations");
        $this->redirect(['module' => 'threeDReports', 'action' => 'digitalObjects']);
	}
}