<?php

/**
 * ahg3DModel Actions
 * 
 * Handles 3D model CRUD operations, viewing, and IIIF 3D manifest generation
 * 
 * @package ahg3DModelPlugin
 * @subpackage actions
 * @author Johan Pieterse - The Archive and Heritage Group
 */
class ahg3DModelActions extends sfActions
{
    private $model3DService;
    private $db;

    public function preExecute()
    {
        // Load the Laravel bootstrap
        $bootstrapPath = sfConfig::get('sf_root_dir') . '/atom-framework/bootstrap.php';
        if (file_exists($bootstrapPath)) {
            require_once $bootstrapPath;
        }
        
        // Manually load the service file if not autoloaded
        $servicePath = sfConfig::get('sf_root_dir') . '/atom-framework/src/Services/Model3DService.php';
        if (file_exists($servicePath)) {
            require_once $servicePath;
        }
        
        // Initialize database manager
        $this->db = \Illuminate\Database\Capsule\Manager::class;
        
        // Initialize service
        if (class_exists('\AtomFramework\Services\Model3DService')) {
            $this->model3DService = new \AtomFramework\Services\Model3DService();
        }
    }

    /**
     * List all 3D models (admin view)
     */
    public function executeIndex(sfWebRequest $request)
    {
        // Check authentication
        if (!$this->context->user->isAuthenticated()) {
            $this->redirect('@homepage');
        }

        $db = $this->db;

        $page = max(1, (int)$request->getParameter('page', 1));
        $limit = 20;
        $offset = ($page - 1) * $limit;

        // Get all 3D models with object info
        $this->models = $db::table('object_3d_model as m')
            ->leftJoin('object_3d_model_i18n as i18n', function($join) {
                $join->on('m.id', '=', 'i18n.model_id')
                     ->where('i18n.culture', '=', 'en');
            })
            ->leftJoin('information_object_i18n as ioi', function($join) {
                $join->on('m.object_id', '=', 'ioi.id')
                     ->where('ioi.culture', '=', 'en');
            })
            ->leftJoin('slug', function($join) {
                $join->on('m.object_id', '=', 'slug.object_id');
            })
            ->orderBy('m.created_at', 'desc')
            ->offset($offset)
            ->limit($limit)
            ->select(
                'm.*',
                'i18n.title',
                'i18n.description',
                'ioi.title as object_title',
                'slug.slug as object_slug'
            )
            ->get()
            ->toArray();

        // Total count for pagination
        $this->totalCount = $db::table('object_3d_model')->count();
        $this->currentPage = $page;
        $this->totalPages = ceil($this->totalCount / $limit);
    }

    /**
     * View a single 3D model
     */
    public function executeView(sfWebRequest $request)
    {
        $modelId = (int)$request->getParameter('id');
        $db = $this->db;

        // Get model with translations
        $this->model = $db::table('object_3d_model as m')
            ->leftJoin('object_3d_model_i18n as i18n', function($join) {
                $join->on('m.id', '=', 'i18n.model_id')
                     ->where('i18n.culture', '=', 'en');
            })
            ->where('m.id', $modelId)
            ->select('m.*', 'i18n.title', 'i18n.description', 'i18n.alt_text')
            ->first();

        if (!$this->model) {
            $this->forward404('3D model not found');
        }

        // Get hotspots
        $this->hotspots = $db::table('object_3d_hotspot as h')
            ->leftJoin('object_3d_hotspot_i18n as i18n', function($join) {
                $join->on('h.id', '=', 'i18n.hotspot_id')
                     ->where('i18n.culture', '=', 'en');
            })
            ->where('h.model_id', $modelId)
            ->where('h.is_visible', 1)
            ->orderBy('h.display_order')
            ->select('h.*', 'i18n.title', 'i18n.description')
            ->get()
            ->toArray();

        // Get object info
        $this->object = $db::table('information_object as io')
            ->leftJoin('information_object_i18n as ioi', function($join) {
                $join->on('io.id', '=', 'ioi.id')
                     ->where('ioi.culture', '=', 'en');
            })
            ->leftJoin('slug', 'io.id', '=', 'slug.object_id')
            ->where('io.id', $this->model->object_id)
            ->select('io.id', 'ioi.title', 'slug.slug')
            ->first();

        // Log view action
        $this->logAction($modelId, 'view');
    }

    /**
     * Embedded viewer (for iframes)
     */
    public function executeEmbed(sfWebRequest $request)
    {
        $modelId = (int)$request->getParameter('id');
        $db = $this->db;

        $this->model = $db::table('object_3d_model as m')
            ->leftJoin('object_3d_model_i18n as i18n', function($join) {
                $join->on('m.id', '=', 'i18n.model_id')
                     ->where('i18n.culture', '=', 'en');
            })
            ->where('m.id', $modelId)
            ->where('m.is_public', 1)
            ->select('m.*', 'i18n.title', 'i18n.description', 'i18n.alt_text')
            ->first();

        if (!$this->model) {
            $this->forward404('Model not found');
        }

        // Get hotspots
        $this->hotspots = $db::table('object_3d_hotspot as h')
            ->leftJoin('object_3d_hotspot_i18n as i18n', function($join) {
                $join->on('h.id', '=', 'i18n.hotspot_id')
                     ->where('i18n.culture', '=', 'en');
            })
            ->where('h.model_id', $modelId)
            ->where('h.is_visible', 1)
            ->orderBy('h.display_order')
            ->select('h.*', 'i18n.title', 'i18n.description')
            ->get()
            ->toArray();

        $this->viewerType = $request->getParameter('viewer', 'model-viewer');

        // Minimal layout for embed
        $this->setLayout(false);
    }

    /**
     * Upload a new 3D model
     */
    public function executeUpload(sfWebRequest $request)
    {
        // Check authentication
        if (!$this->context->user->isAuthenticated()) {
            $this->redirect('@homepage');
        }

        if (!$this->context->user->hasCredential('administrator') && 
            !$this->context->user->hasCredential('editor')) {
            $this->forward('default', 'secure');
        }

        $this->objectId = (int)$request->getParameter('object_id');
        $db = $this->db;

        // Get object info
        $this->object = $db::table('information_object as io')
            ->leftJoin('information_object_i18n as ioi', function($join) {
                $join->on('io.id', '=', 'ioi.id')
                     ->where('ioi.culture', '=', 'en');
            })
            ->leftJoin('slug', 'io.id', '=', 'slug.object_id')
            ->where('io.id', $this->objectId)
            ->select('io.id', 'ioi.title', 'slug.slug')
            ->first();

        if (!$this->object) {
            $this->forward404('Object not found');
        }

        // Get settings
        $this->maxFileSize = $this->getSetting('max_file_size_mb', 100);
        $this->allowedFormats = json_decode($this->getSetting('allowed_formats', '["glb","gltf","usdz"]'), true);

        // Handle POST
        if ($request->isMethod('post')) {
            $this->processUpload($request);
        }
    }

    /**
     * Process file upload
     */
    private function processUpload(sfWebRequest $request)
    {
        $db = $this->db;
        $uploadDir = sfConfig::get('sf_upload_dir') . '/3d/' . $this->objectId;

        // Create directory
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        // Get uploaded file
        $file = $request->getFiles('model_file');
        if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
            $this->getUser()->setFlash('error', 'File upload failed');
            return;
        }

        // Validate extension
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $this->allowedFormats)) {
            $this->getUser()->setFlash('error', 'Invalid file format. Allowed: ' . implode(', ', $this->allowedFormats));
            return;
        }

        // Validate size
        $maxBytes = $this->maxFileSize * 1024 * 1024;
        if ($file['size'] > $maxBytes) {
            $this->getUser()->setFlash('error', 'File too large. Maximum: ' . $this->maxFileSize . 'MB');
            return;
        }

        // Generate unique filename
        $filename = uniqid() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $file['name']);
        $filePath = $uploadDir . '/' . $filename;

        // Move file
        if (!move_uploaded_file($file['tmp_name'], $filePath)) {
            $this->getUser()->setFlash('error', 'Failed to save file');
            return;
        }

        // Determine format
        $format = $ext;
        $mimeTypes = [
            'glb' => 'model/gltf-binary',
            'gltf' => 'model/gltf+json',
            'obj' => 'model/obj',
            'stl' => 'model/stl',
            'fbx' => 'application/octet-stream',
            'ply' => 'application/x-ply',
            'usdz' => 'model/vnd.usdz+zip',
        ];
        $mimeType = $mimeTypes[$ext] ?? 'application/octet-stream';

        // Check if first model for this object
        $existingCount = $db::table('object_3d_model')
            ->where('object_id', $this->objectId)
            ->count();
        $isPrimary = ($existingCount === 0) ? 1 : 0;

        // Insert record
        $modelId = $db::table('object_3d_model')->insertGetId([
            'object_id' => $this->objectId,
            'filename' => $filename,
            'original_filename' => $file['name'],
            'file_path' => '3d/' . $this->objectId . '/' . $filename,
            'file_size' => $file['size'],
            'mime_type' => $mimeType,
            'format' => $format,
            'auto_rotate' => 1,
            'rotation_speed' => 30,
            'camera_orbit' => '0deg 75deg 105%',
            'field_of_view' => '30deg',
            'exposure' => 1.0,
            'shadow_intensity' => 1.0,
            'shadow_softness' => 1.0,
            'background_color' => '#f5f5f5',
            'ar_enabled' => 1,
            'ar_scale' => 'auto',
            'ar_placement' => 'floor',
            'is_primary' => $isPrimary,
            'is_public' => $request->getParameter('is_public', 1),
            'display_order' => $existingCount,
            'created_by' => $this->context->user->getUserId(),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        // Insert translation
        $title = $request->getParameter('title') ?: pathinfo($file['name'], PATHINFO_FILENAME);
        $db::table('object_3d_model_i18n')->insert([
            'model_id' => $modelId,
            'culture' => 'en',
            'title' => $title,
            'description' => $request->getParameter('description'),
            'alt_text' => $request->getParameter('alt_text'),
        ]);

        // Log action
        $this->logAction($modelId, 'upload', ['filename' => $file['name'], 'size' => $file['size']]);

        $this->getUser()->setFlash('notice', '3D model uploaded successfully');
        $this->redirect(['module' => 'ahg3DModel', 'action' => 'view', 'id' => $modelId]);
    }

    /**
     * Edit 3D model settings
     */
    public function executeEdit(sfWebRequest $request)
    {
        // Check authentication
        if (!$this->context->user->isAuthenticated()) {
            $this->redirect('@homepage');
        }

        if (!$this->context->user->hasCredential('administrator') && 
            !$this->context->user->hasCredential('editor')) {
            $this->forward('default', 'secure');
        }

        $modelId = (int)$request->getParameter('id');
        $db = $this->db;

        // Get model
        $this->model = $db::table('object_3d_model as m')
            ->leftJoin('object_3d_model_i18n as i18n', function($join) {
                $join->on('m.id', '=', 'i18n.model_id')
                     ->where('i18n.culture', '=', 'en');
            })
            ->where('m.id', $modelId)
            ->select('m.*', 'i18n.title', 'i18n.description', 'i18n.alt_text')
            ->first();

        if (!$this->model) {
            $this->forward404('Model not found');
        }

        // Get hotspots
        $this->hotspots = $db::table('object_3d_hotspot as h')
            ->leftJoin('object_3d_hotspot_i18n as i18n', function($join) {
                $join->on('h.id', '=', 'i18n.hotspot_id')
                     ->where('i18n.culture', '=', 'en');
            })
            ->where('h.model_id', $modelId)
            ->orderBy('h.display_order')
            ->select('h.*', 'i18n.title', 'i18n.description')
            ->get()
            ->toArray();

        // Get object info
        $this->object = $db::table('information_object as io')
            ->leftJoin('information_object_i18n as ioi', function($join) {
                $join->on('io.id', '=', 'ioi.id')
                     ->where('ioi.culture', '=', 'en');
            })
            ->leftJoin('slug', 'io.id', '=', 'slug.object_id')
            ->where('io.id', $this->model->object_id)
            ->select('io.id', 'ioi.title', 'slug.slug')
            ->first();

        // Handle POST
        if ($request->isMethod('post')) {
            $this->processSave($request, $modelId);
        }
    }

    /**
     * Process model update
     */
    private function processSave(sfWebRequest $request, int $modelId)
    {
        $db = $this->db;
        
        // Capture old values for audit trail
        $oldValues = $this->captureModelValues($modelId);

        // Update model settings
        $db::table('object_3d_model')
            ->where('id', $modelId)
            ->update([
                'auto_rotate' => $request->getParameter('auto_rotate', 0) ? 1 : 0,
                'rotation_speed' => (int)$request->getParameter('rotation_speed', 30),
                'camera_orbit' => $request->getParameter('camera_orbit', '0deg 75deg 105%'),
                'field_of_view' => $request->getParameter('field_of_view', '30deg'),
                'exposure' => (float)$request->getParameter('exposure', 1.0),
                'shadow_intensity' => (float)$request->getParameter('shadow_intensity', 1.0),
                'shadow_softness' => (float)$request->getParameter('shadow_softness', 1.0),
                'background_color' => $request->getParameter('background_color', '#f5f5f5'),
                'ar_enabled' => $request->getParameter('ar_enabled', 0) ? 1 : 0,
                'ar_scale' => $request->getParameter('ar_scale', 'auto'),
                'ar_placement' => $request->getParameter('ar_placement', 'floor'),
                'is_primary' => $request->getParameter('is_primary', 0) ? 1 : 0,
                'is_public' => $request->getParameter('is_public', 0) ? 1 : 0,
                'updated_by' => $this->context->user->getUserId(),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

        // Update translations
        $db::table('object_3d_model_i18n')
            ->updateOrInsert(
                ['model_id' => $modelId, 'culture' => 'en'],
                [
                    'title' => $request->getParameter('title'),
                    'description' => $request->getParameter('description'),
                    'alt_text' => $request->getParameter('alt_text'),
                ]
            );

        // Clear manifest cache
        $db::table('iiif_3d_manifest')
            ->where('model_id', $modelId)
            ->delete();

        // Log action
        $this->logAction($modelId, 'update');
        
        // Capture new values and log to central audit trail
        $newValues = $this->captureModelValues($modelId);
        $this->logAuditTrail('update', $modelId, $oldValues, $newValues);

        $this->getUser()->setFlash('notice', 'Model settings updated');
        $this->redirect(['module' => 'ahg3DModel', 'action' => 'edit', 'id' => $modelId]);
    }

    /**
     * Delete a 3D model
     */
    public function executeDelete(sfWebRequest $request)
    {
        // Check authentication
        if (!$this->context->user->isAuthenticated()) {
            $this->redirect('@homepage');
        }

        if (!$this->context->user->hasCredential('administrator')) {
            $this->forward('default', 'secure');
        }

        $modelId = (int)$request->getParameter('id');
        $db = $this->db;

        // Get model
        $model = $db::table('object_3d_model')->where('id', $modelId)->first();
        if (!$model) {
            $this->forward404('Model not found');
        }

        $objectId = $model->object_id;

        // Delete file
        $filePath = sfConfig::get('sf_upload_dir') . '/' . $model->file_path;
        if (file_exists($filePath)) {
            unlink($filePath);
        }

        // Delete poster if exists
        if ($model->poster_image) {
            $posterPath = sfConfig::get('sf_upload_dir') . '/' . $model->poster_image;
            if (file_exists($posterPath)) {
                unlink($posterPath);
            }
        }

        // Log before delete
        $this->logAction($modelId, 'delete', ['filename' => $model->original_filename]);

        // Delete related records
        $hotspotIds = $db::table('object_3d_hotspot')
            ->where('model_id', $modelId)
            ->pluck('id');
        
        $db::table('object_3d_hotspot_i18n')
            ->whereIn('hotspot_id', $hotspotIds)
            ->delete();
        
        $db::table('object_3d_hotspot')
            ->where('model_id', $modelId)
            ->delete();
        
        $db::table('object_3d_texture')
            ->where('model_id', $modelId)
            ->delete();
        
        $db::table('object_3d_model_i18n')
            ->where('model_id', $modelId)
            ->delete();
        
        $db::table('iiif_3d_manifest')
            ->where('model_id', $modelId)
            ->delete();
        
        $db::table('object_3d_model')
            ->where('id', $modelId)
            ->delete();

        $this->getUser()->setFlash('notice', '3D model deleted');
        $this->redirect(['module' => 'informationobject', 'action' => 'index', 'slug' => $objectId]);
    }

    /**
     * Generate IIIF 3D manifest
     */
    public function executeIiifManifest(sfWebRequest $request)
    {
        $modelId = (int)$request->getParameter('id');
        $db = $this->db;

        // Get model
        $model = $db::table('object_3d_model as m')
            ->leftJoin('object_3d_model_i18n as i18n', function($join) {
                $join->on('m.id', '=', 'i18n.model_id')
                     ->where('i18n.culture', '=', 'en');
            })
            ->where('m.id', $modelId)
            ->where('m.is_public', 1)
            ->select('m.*', 'i18n.title', 'i18n.description', 'i18n.alt_text')
            ->first();

        if (!$model) {
            $this->getResponse()->setStatusCode(404);
            return sfView::NONE;
        }

        // Get hotspots
        $hotspots = $db::table('object_3d_hotspot as h')
            ->leftJoin('object_3d_hotspot_i18n as i18n', function($join) {
                $join->on('h.id', '=', 'i18n.hotspot_id')
                     ->where('i18n.culture', '=', 'en');
            })
            ->where('h.model_id', $modelId)
            ->where('h.is_visible', 1)
            ->orderBy('h.display_order')
            ->select('h.*', 'i18n.title', 'i18n.description')
            ->get()
            ->toArray();

        // Build IIIF 3D manifest
        $baseUrl = $request->getUriPrefix();
        $modelUrl = $baseUrl . '/uploads/' . $model->file_path;
        $manifestId = $baseUrl . '/iiif/3d/' . $modelId . '/manifest.json';

        $manifest = [
            '@context' => [
                'http://iiif.io/api/presentation/3/context.json',
                'http://iiif.io/api/extension/3d/context.json'
            ],
            'id' => $manifestId,
            'type' => 'Manifest',
            'label' => ['en' => [$model->title ?: 'Untitled 3D Model']],
            'metadata' => [
                ['label' => ['en' => ['Format']], 'value' => ['en' => [strtoupper($model->format)]]],
                ['label' => ['en' => ['File Size']], 'value' => ['en' => [AhgCentralHelpers::formatBytes($model->file_size)]]],
            ],
            'items' => [
                [
                    'id' => $baseUrl . '/iiif/3d/' . $modelId . '/scene/1',
                    'type' => 'Scene',
                    'items' => [
                        [
                            'id' => $baseUrl . '/iiif/3d/' . $modelId . '/annotation/1',
                            'type' => 'Annotation',
                            'motivation' => 'painting',
                            'body' => [
                                'id' => $modelUrl,
                                'type' => 'Model',
                                'format' => $model->mime_type,
                            ],
                            'target' => $baseUrl . '/iiif/3d/' . $modelId . '/scene/1'
                        ]
                    ]
                ]
            ]
        ];

        // Add description
        if ($model->description) {
            $manifest['summary'] = ['en' => [$model->description]];
        }

        // Add hotspots as annotations
        if (!empty($hotspots)) {
            $annotations = [];
            foreach ($hotspots as $index => $hotspot) {
                $annotations[] = [
                    'id' => $baseUrl . '/iiif/3d/' . $modelId . '/hotspot/' . $hotspot->id,
                    'type' => 'Annotation',
                    'motivation' => 'commenting',
                    'body' => [
                        'type' => 'TextualBody',
                        'value' => $hotspot->title . ($hotspot->description ? ': ' . $hotspot->description : ''),
                        'format' => 'text/plain'
                    ],
                    'target' => [
                        'type' => 'PointSelector',
                        'x' => (float)$hotspot->position_x,
                        'y' => (float)$hotspot->position_y,
                        'z' => (float)$hotspot->position_z
                    ]
                ];
            }
            
            $manifest['annotations'] = [
                [
                    'id' => $baseUrl . '/iiif/3d/' . $modelId . '/annotations/1',
                    'type' => 'AnnotationPage',
                    'items' => $annotations
                ]
            ];
        }

        // Add viewer settings as extension
        $manifest['extensions'] = [
            'viewer' => [
                'autoRotate' => (bool)$model->auto_rotate,
                'rotationSpeed' => (int)$model->rotation_speed,
                'cameraOrbit' => $model->camera_orbit,
                'fieldOfView' => $model->field_of_view,
                'exposure' => (float)$model->exposure,
                'backgroundColor' => $model->background_color,
                'arEnabled' => (bool)$model->ar_enabled
            ]
        ];

        // Set headers
        $this->getResponse()->setContentType('application/json');
        $this->getResponse()->setHttpHeader('Access-Control-Allow-Origin', '*');
        $this->getResponse()->setHttpHeader('Access-Control-Allow-Methods', 'GET');

        // Output JSON
        echo json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        return sfView::NONE;
    }

    /**
     * Add a hotspot
     */
    public function executeAddHotspot(sfWebRequest $request)
    {
        if (!$request->isMethod('post')) {
            $this->getResponse()->setStatusCode(405);
            return sfView::NONE;
        }

        if (!$this->context->user->isAuthenticated()) {
            $this->getResponse()->setStatusCode(401);
            return sfView::NONE;
        }

        $modelId = (int)$request->getParameter('id');
        $db = $this->db;

        // Get input
        $input = json_decode(file_get_contents('php://input'), true) ?: $request->getParameterHolder()->getAll();

        // Get next display order
        $maxOrder = $db::table('object_3d_hotspot')
            ->where('model_id', $modelId)
            ->max('display_order') ?? -1;

        // Hotspot colors
        $colors = [
            'annotation' => '#1a73e8',
            'info' => '#34a853',
            'link' => '#4285f4',
            'damage' => '#ea4335',
            'detail' => '#fbbc04',
        ];
        $hotspotType = $input['hotspot_type'] ?? 'annotation';
        $color = $colors[$hotspotType] ?? '#1a73e8';

        // Insert hotspot
        $hotspotId = $db::table('object_3d_hotspot')->insertGetId([
            'model_id' => $modelId,
            'hotspot_type' => $hotspotType,
            'position_x' => (float)($input['position_x'] ?? 0),
            'position_y' => (float)($input['position_y'] ?? 0),
            'position_z' => (float)($input['position_z'] ?? 0),
            'normal_x' => (float)($input['normal_x'] ?? 0),
            'normal_y' => (float)($input['normal_y'] ?? 1),
            'normal_z' => (float)($input['normal_z'] ?? 0),
            'color' => $color,
            'link_url' => $input['link_url'] ?? null,
            'link_target' => $input['link_target'] ?? '_blank',
            'display_order' => $maxOrder + 1,
            'is_visible' => 1,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        // Insert translation
        $db::table('object_3d_hotspot_i18n')->insert([
            'hotspot_id' => $hotspotId,
            'culture' => 'en',
            'title' => $input['title'] ?? '',
            'description' => $input['description'] ?? '',
        ]);

        // Log action
        $this->logAction($modelId, 'hotspot_add', ['hotspot_id' => $hotspotId]);

        // Clear manifest cache
        $db::table('iiif_3d_manifest')->where('model_id', $modelId)->delete();

        $this->getResponse()->setContentType('application/json');
        echo json_encode(['success' => true, 'id' => $hotspotId, 'color' => $color]);

        return sfView::NONE;
    }

    /**
     * Delete a hotspot
     */
    public function executeDeleteHotspot(sfWebRequest $request)
    {
        if (!$request->isMethod('post')) {
            $this->getResponse()->setStatusCode(405);
            return sfView::NONE;
        }

        if (!$this->context->user->isAuthenticated()) {
            $this->getResponse()->setStatusCode(401);
            return sfView::NONE;
        }

        $hotspotId = (int)$request->getParameter('id');
        $db = $this->db;

        // Get hotspot for logging
        $hotspot = $db::table('object_3d_hotspot')->where('id', $hotspotId)->first();
        if (!$hotspot) {
            $this->getResponse()->setStatusCode(404);
            return sfView::NONE;
        }

        // Log action
        $this->logAction($hotspot->model_id, 'hotspot_delete', ['hotspot_id' => $hotspotId]);

        // Delete
        $db::table('object_3d_hotspot_i18n')->where('hotspot_id', $hotspotId)->delete();
        $db::table('object_3d_hotspot')->where('id', $hotspotId)->delete();

        // Clear manifest cache
        $db::table('iiif_3d_manifest')->where('model_id', $hotspot->model_id)->delete();

        $this->getResponse()->setContentType('application/json');
        echo json_encode(['success' => true]);

        return sfView::NONE;
    }

    /**
     * API: Get models for an object
     */
    public function executeApiModels(sfWebRequest $request)
    {
        $objectId = (int)$request->getParameter('object_id');
        $db = $this->db;

        $models = $db::table('object_3d_model as m')
            ->leftJoin('object_3d_model_i18n as i18n', function($join) {
                $join->on('m.id', '=', 'i18n.model_id')
                     ->where('i18n.culture', '=', 'en');
            })
            ->where('m.object_id', $objectId)
            ->where('m.is_public', 1)
            ->orderBy('m.is_primary', 'desc')
            ->orderBy('m.display_order')
            ->select('m.*', 'i18n.title', 'i18n.description', 'i18n.alt_text')
            ->get()
            ->toArray();

        $this->getResponse()->setContentType('application/json');
        $this->getResponse()->setHttpHeader('Access-Control-Allow-Origin', '*');
        echo json_encode(['models' => $models]);

        return sfView::NONE;
    }

    /**
     * API: Get hotspots for a model
     */
    public function executeApiHotspots(sfWebRequest $request)
    {
        $modelId = (int)$request->getParameter('model_id');
        $db = $this->db;

        $hotspots = $db::table('object_3d_hotspot as h')
            ->leftJoin('object_3d_hotspot_i18n as i18n', function($join) {
                $join->on('h.id', '=', 'i18n.hotspot_id')
                     ->where('i18n.culture', '=', 'en');
            })
            ->where('h.model_id', $modelId)
            ->where('h.is_visible', 1)
            ->orderBy('h.display_order')
            ->select('h.*', 'i18n.title', 'i18n.description')
            ->get()
            ->toArray();

        $this->getResponse()->setContentType('application/json');
        $this->getResponse()->setHttpHeader('Access-Control-Allow-Origin', '*');
        echo json_encode(['hotspots' => $hotspots]);

        return sfView::NONE;
    }

    /**
     * Get a setting from viewer_3d_settings
     */
    private function getSetting(string $key, $default = null)
    {
        $db = $this->db;
        
        $setting = $db::table('viewer_3d_settings')
            ->where('setting_key', $key)
            ->first();

        return $setting ? $setting->setting_value : $default;
    }

    /**
     * Log an action
     */
    private function logAction(int $modelId, string $action, array $details = [])
    {
        $db = $this->db;
        
        // Get object_id
        $model = $db::table('object_3d_model')->where('id', $modelId)->first();
        $objectId = $model ? $model->object_id : null;

        $db::table('object_3d_audit_log')->insert([
            'model_id' => $modelId,
            'object_id' => $objectId,
            'user_id' => $this->context->user->getUserId(),
            'user_name' => $this->context->user->getUsername(),
            'action' => $action,
            'details' => !empty($details) ? json_encode($details) : null,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Capture model values for audit trail
     */
    private function captureModelValues(int $modelId): array
    {
        try {
            $db = $this->db;
            $model = $db::table('object_3d_model as m')
                ->leftJoin('object_3d_model_i18n as i18n', function($join) {
                    $join->on('m.id', '=', 'i18n.model_id')->where('i18n.culture', '=', 'en');
                })
                ->where('m.id', $modelId)
                ->select('m.*', 'i18n.title', 'i18n.description', 'i18n.alt_text')
                ->first();
            
            if (!$model) return [];
            
            return [
                'title' => $model->title ?? null,
                'description' => $model->description ?? null,
                'auto_rotate' => $model->auto_rotate ?? 0,
                'rotation_speed' => $model->rotation_speed ?? 30,
                'camera_orbit' => $model->camera_orbit ?? null,
                'is_public' => $model->is_public ?? 0,
                'ar_enabled' => $model->ar_enabled ?? 0,
            ];
        } catch (\Exception $e) {
            error_log("3D AUDIT CAPTURE ERROR: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Log to central audit trail with old/new values
     */
    private function logAuditTrail(string $action, int $modelId, array $oldValues, array $newValues): void
    {
        try {
            $db = $this->db;
            $model = $db::table('object_3d_model')->where('id', $modelId)->first();
            
            $userId = $this->context->user->getUserId();
            $username = $this->context->user->getUsername();

            $changedFields = [];
            foreach ($newValues as $key => $newVal) {
                $oldVal = $oldValues[$key] ?? null;
                if ($newVal !== $oldVal) {
                    $changedFields[] = $key;
                }
            }

            $uuid = sprintf(
                '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                mt_rand(0, 0xffff), mt_rand(0, 0xffff),
                mt_rand(0, 0xffff),
                mt_rand(0, 0x0fff) | 0x4000,
                mt_rand(0, 0x3fff) | 0x8000,
                mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
            );

            $db::table('ahg_audit_log')->insert([
                'uuid' => $uuid,
                'user_id' => $userId,
                'username' => $username,
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
                'session_id' => session_id() ?: null,
                'action' => $action,
                'entity_type' => 'Object3D',
                'entity_id' => $model->object_id ?? $modelId,
                'entity_slug' => null,
                'entity_title' => $newValues['title'] ?? null,
                'module' => 'ahg3DModelPlugin',
                'action_name' => 'edit',
                'request_method' => $_SERVER['REQUEST_METHOD'] ?? null,
                'request_uri' => $_SERVER['REQUEST_URI'] ?? null,
                'old_values' => !empty($oldValues) ? json_encode($oldValues) : null,
                'new_values' => !empty($newValues) ? json_encode($newValues) : null,
                'changed_fields' => !empty($changedFields) ? json_encode($changedFields) : null,
                'status' => 'success',
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        } catch (\Exception $e) {
            error_log("3D AUDIT ERROR: " . $e->getMessage());
        }
    }

}
