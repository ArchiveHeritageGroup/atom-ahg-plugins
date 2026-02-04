<?php
require_once dirname(__FILE__).'/../../../lib/ConditionConstants.php';

use Illuminate\Database\Capsule\Manager as DB;

/**
 * Condition Photo Annotation Actions
 * Handles photo management and annotation for condition reports
 */
class conditionActions extends sfActions
{
    /**
     * Initialize AhgDb for Laravel Query Builder.
     */
    public function preExecute()
    {
        // For AJAX actions, start output buffering to prevent any stray output
        $ajaxActions = ['upload', 'deletePhoto', 'getAnnotation', 'saveAnnotation', 'listPhotos', 'updatePhotoMeta'];
        $action = $this->getActionName();
        if (in_array($action, $ajaxActions)) {
            ob_start();
            error_reporting(E_ERROR | E_PARSE); // Suppress notices/warnings for AJAX
        }

        // Load AhgDb class for Laravel Query Builder
        $ahgDbFile = sfConfig::get('sf_plugins_dir') . '/ahgCorePlugin/lib/Core/AhgDb.php';
        if (file_exists($ahgDbFile)) {
            require_once $ahgDbFile;
            \AhgCore\Core\AhgDb::init();
        }
    }

    /**
     * Helper to return clean JSON response for AJAX actions.
     */
    protected function jsonResponse(array $data)
    {
        // Clear any buffered output
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        // Send JSON directly and exit to prevent any framework output
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data);
        exit;
    }

    /**
     * Display photos for a condition check
     */
    public function executePhotos(sfWebRequest $request)
    {
        $checkIdParam = $request->getParameter('id');
        $objectId = (int) $request->getParameter('object_id');
        
        // Handle "new" condition check
        if ($checkIdParam === 'new' && $objectId) {
            // Create a new condition check
            $newId = DB::table('spectrum_condition_check')->insertGetId([
                'object_id' => $objectId,
                'condition_check_reference' => 'CC-' . date('Ymd') . '-' . $objectId,
                'check_date' => date('Y-m-d'),
                'overall_condition' => 'pending', 'checked_by' => 'System',
                'created_at' => date('Y-m-d H:i:s'),
            ]);
            
            // Redirect to the new check
            $this->redirect('/condition/check/' . $newId . '/photos');
        }
        
        $this->checkId = (int) $checkIdParam;

        if (!$this->checkId) {
            $this->forward404('Condition check ID required');
        }

        // Load service
        require_once sfConfig::get('sf_root_dir') . '/atom-ahg-plugins/ahgConditionPlugin/lib/Service/ConditionAnnotationService.php';
        $service = new \ahgConditionPlugin\Service\ConditionAnnotationService();

        $this->conditionCheck = $service->getConditionCheckWithObject($this->checkId);

        if (!$this->conditionCheck) {
            $this->forward404('Condition check not found');
        }

        $this->photos = $service->getPhotosForCheck($this->checkId);
        $this->stats = $service->getAnnotationStats($this->checkId);
        $this->canEdit = $this->getUser()->isAuthenticated() &&
                        (($this->getUser()->isAdministrator() || $this->getUser()->hasCredential('editor')) ||
                         $this->getUser()->hasGroup(ConditionConstants::EDITOR_GROUP_ID));

        // Get photo URL helper
        $this->service = $service;
    }

    /**
     * Annotate a single photo
     */
    public function executeAnnotate(sfWebRequest $request)
    {
        $this->photoId = (int) $request->getParameter('id');

        if (!$this->photoId) {
            $this->forward404('Photo ID required');
        }

        require_once sfConfig::get('sf_root_dir') . '/atom-ahg-plugins/ahgConditionPlugin/lib/Service/ConditionAnnotationService.php';
        $service = new \ahgConditionPlugin\Service\ConditionAnnotationService();

        $this->photo = $service->getPhoto($this->photoId);

        if (!$this->photo) {
            $this->forward404('Photo not found');
        }

        $this->conditionCheck = $service->getConditionCheckWithObject((int) $this->photo->condition_check_id);
        $this->annotations = $service->getAnnotations($this->photoId);

        $this->canEdit = $this->getUser()->isAuthenticated() &&
                        (($this->getUser()->isAdministrator() || $this->getUser()->hasCredential('editor')) ||
                         $this->getUser()->hasGroup(ConditionConstants::EDITOR_GROUP_ID));

        $this->imageUrl = '/uploads/condition_photos/' . $this->photo->filename;
    }

    /**
     * Get annotations for a photo (AJAX)
     */
    public function executeGetAnnotation(sfWebRequest $request)
    {
        $this->getResponse()->setContentType('application/json');

        $photoId = (int) $request->getParameter('photo_id');

        // If requesting list of photos
        if ($request->getParameter('list')) {
            return $this->executeListPhotos($request);
        }

        if (!$photoId) {
            return $this->renderText(json_encode(['success' => false, 'error' => 'Missing photo_id']));
        }

        require_once sfConfig::get('sf_root_dir') . '/atom-ahg-plugins/ahgConditionPlugin/lib/Service/ConditionAnnotationService.php';
        $service = new \ahgConditionPlugin\Service\ConditionAnnotationService();

        $annotations = $service->getAnnotations($photoId);

        return $this->renderText(json_encode([
            'success' => true,
            'annotations' => $annotations
        ]));
    }

    /**
     * Save annotations for a photo (AJAX)
     */
    public function executeSaveAnnotation(sfWebRequest $request)
    {
        $this->getResponse()->setContentType('application/json');

        if (!$this->getUser()->isAuthenticated()) {
            return $this->renderText(json_encode(['success' => false, 'error' => 'Not authenticated']));
        }

        // Get JSON body
        $body = json_decode($request->getContent(), true);
        $photoId = (int) ($body['photo_id'] ?? $request->getParameter('photo_id'));
        $annotations = $body['annotations'] ?? [];

        if (!$photoId) {
            return $this->renderText(json_encode(['success' => false, 'error' => 'Missing photo_id']));
        }

        require_once sfConfig::get('sf_root_dir') . '/atom-ahg-plugins/ahgConditionPlugin/lib/Service/ConditionAnnotationService.php';
        $service = new \ahgConditionPlugin\Service\ConditionAnnotationService();

        $userId = (int) $this->getUser()->getAttribute('user_id');
        $result = $service->saveAnnotations($photoId, $annotations, $userId);

        return $this->renderText(json_encode([
            'success' => $result,
            'message' => $result ? 'Annotations saved' : 'Failed to save annotations'
        ]));
    }

    /**
     * Upload new photo
     */
    public function executeUpload(sfWebRequest $request)
    {
        try {
            if (!$this->getUser()->isAuthenticated()) {
                return $this->jsonResponse(['success' => false, 'error' => 'Not authenticated']);
            }

            $checkId = (int) ($request->getParameter('id') ?: $request->getParameter('condition_check_id'));

            if (!$checkId) {
                return $this->jsonResponse(['success' => false, 'error' => 'Missing condition check ID']);
            }

            if (!isset($_FILES['photo']) || $_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
                $uploadError = isset($_FILES['photo']) ? $_FILES['photo']['error'] : 'No file';
                return $this->jsonResponse(['success' => false, 'error' => 'Upload error: ' . $uploadError]);
            }

            require_once sfConfig::get('sf_root_dir') . '/atom-ahg-plugins/ahgConditionPlugin/lib/Service/ConditionAnnotationService.php';
            $service = new \ahgConditionPlugin\Service\ConditionAnnotationService();

            $photoType = $request->getParameter('photo_type', 'general');
            $caption = $request->getParameter('caption', '');
            $userId = (int) $this->getUser()->getAttribute('user_id');

            $photoId = $service->uploadPhoto(
                $checkId,
                $_FILES['photo'],
                $photoType,
                $caption,
                $userId
            );

            if ($photoId) {
                $photo = $service->getPhoto($photoId);
                return $this->jsonResponse([
                    'success' => true,
                    'photo_id' => $photoId,
                    'filename' => $photo->filename,
                    'thumbnail' => $photo->thumbnail ?? null
                ]);
            }

            return $this->jsonResponse(['success' => false, 'error' => 'Upload failed - check server logs']);
        } catch (\Exception $e) {
            return $this->jsonResponse(['success' => false, 'error' => 'Exception: ' . $e->getMessage()]);
        }
    }

    /**
     * Delete a photo
     */
    public function executeDeletePhoto(sfWebRequest $request)
    {
        try {
            if (!$this->getUser()->isAuthenticated()) {
                return $this->jsonResponse(['success' => false, 'error' => 'Not authenticated']);
            }

            $photoId = (int) $request->getParameter('id');

            if (!$photoId) {
                return $this->jsonResponse(['success' => false, 'error' => 'Missing photo_id']);
            }

            require_once sfConfig::get('sf_root_dir') . '/atom-ahg-plugins/ahgConditionPlugin/lib/Service/ConditionAnnotationService.php';
            $service = new \ahgConditionPlugin\Service\ConditionAnnotationService();

            $userId = (int) $this->getUser()->getAttribute('user_id');
            $result = $service->deletePhoto($photoId, $userId);

            return $this->jsonResponse([
                'success' => $result,
                'message' => $result ? 'Photo deleted' : 'Failed to delete photo'
            ]);
        } catch (\Exception $e) {
            return $this->jsonResponse(['success' => false, 'error' => 'Exception: ' . $e->getMessage()]);
        }
    }

    /**
     * View photo details
     */
    public function executeViewPhoto(sfWebRequest $request)
    {
        $this->photoId = (int) $request->getParameter('id');

        if (!$this->photoId) {
            $this->forward404('Photo ID required');
        }

        require_once sfConfig::get('sf_root_dir') . '/atom-ahg-plugins/ahgConditionPlugin/lib/Service/ConditionAnnotationService.php';
        $service = new \ahgConditionPlugin\Service\ConditionAnnotationService();

        $this->photo = $service->getPhoto($this->photoId);

        if (!$this->photo) {
            $this->forward404('Photo not found');
        }

        $this->conditionCheck = $service->getConditionCheckWithObject((int) $this->photo->condition_check_id);
        $this->annotations = $service->getAnnotations($this->photoId);
        $this->imageUrl = '/uploads/condition_photos/' . $this->photo->filename;
    }

    /**
     * Export condition report with annotated images
     */
    public function executeExportReport(sfWebRequest $request)
    {
        $checkId = (int) $request->getParameter('id');

        if (!$checkId) {
            $this->forward404('Condition check ID required');
        }

        $format = $request->getParameter('format', 'pdf');

        require_once sfConfig::get('sf_root_dir') . '/atom-ahg-plugins/ahgConditionPlugin/lib/Service/ConditionAnnotationService.php';
        $service = new \ahgConditionPlugin\Service\ConditionAnnotationService();

        $this->conditionCheck = $service->getConditionCheckWithObject($checkId);

        if (!$this->conditionCheck) {
            $this->forward404('Condition check not found');
        }

        $this->photos = $service->getPhotosForCheck($checkId);
        $this->stats = $service->getAnnotationStats($checkId);

        // For now, render HTML report - PDF generation can be added later
        $this->setLayout(false);
        $this->setTemplate('exportReport');
    }

    /**
     * Get photos list for a condition check (AJAX)
     */
    public function executeListPhotos(sfWebRequest $request)
    {
        $this->getResponse()->setContentType('application/json');

        $checkId = (int) ($request->getParameter('condition_check_id') ?: $request->getParameter('id'));

        if (!$checkId) {
            return $this->renderText(json_encode(['success' => false, 'error' => 'Missing condition_check_id']));
        }

        require_once sfConfig::get('sf_root_dir') . '/atom-ahg-plugins/ahgConditionPlugin/lib/Service/ConditionAnnotationService.php';
        $service = new \ahgConditionPlugin\Service\ConditionAnnotationService();

        $photos = $service->getPhotosForCheck($checkId);

        // Add annotation count and URLs
        $result = [];
        foreach ($photos as $photo) {
            $annotations = json_decode($photo->annotation_data ?: '[]', true);
            $result[] = [
                'id' => $photo->id,
                'filename' => $photo->filename,
                'thumbnail' => $photo->thumbnail,
                'caption' => $photo->caption,
                'photo_type' => $photo->photo_type,
                'annotation_count' => is_array($annotations) ? count($annotations) : 0,
                'url' => '/uploads/condition_photos/' . $photo->filename,
                'thumbnail_url' => '/uploads/condition_photos/' . ($photo->thumbnail ?: $photo->filename),
            ];
        }

        return $this->renderText(json_encode([
            'success' => true,
            'photos' => $result
        ]));
    }

    /**
     * Update photo metadata
     */
    public function executeUpdatePhotoMeta(sfWebRequest $request)
    {
        $this->getResponse()->setContentType('application/json');

        if (!$this->getUser()->isAuthenticated()) {
            return $this->renderText(json_encode(['success' => false, 'error' => 'Not authenticated']));
        }

        $photoId = (int) $request->getParameter('id');
        $caption = $request->getParameter('caption', '');
        $photoType = $request->getParameter('photo_type', 'general');

        if (!$photoId) {
            return $this->renderText(json_encode(['success' => false, 'error' => 'Missing photo_id']));
        }

        require_once sfConfig::get('sf_root_dir') . '/atom-ahg-plugins/ahgConditionPlugin/lib/Service/ConditionAnnotationService.php';
        $service = new \ahgConditionPlugin\Service\ConditionAnnotationService();

        $result = $service->updatePhotoMeta($photoId, $caption, $photoType);

        return $this->renderText(json_encode([
            'success' => $result,
            'message' => $result ? 'Photo info updated' : 'Failed to update photo info'
        ]));
    }

    /**
     * AJAX autocomplete for objects with digital images
     */
    public function executeObjectAutocomplete(sfWebRequest $request)
    {
        $query = $request->getParameter('q', '');
        $results = [];

        if (strlen($query) >= 2) {
            $objects = DB::table('information_object as io')
                ->leftJoin('information_object_i18n as i18n', function ($join) {
                    $join->on('io.id', '=', 'i18n.id')
                        ->where('i18n.culture', '=', 'en');
                })
                ->leftJoin('slug', 'io.id', '=', 'slug.object_id')
                ->leftJoin('digital_object as do', 'io.id', '=', 'do.object_id')
                ->where(function ($q) use ($query) {
                    $q->where('i18n.title', 'LIKE', "%{$query}%")
                        ->orWhere('io.identifier', 'LIKE', "%{$query}%");
                })
                ->whereNotNull('do.id')
                ->select('io.id', 'io.identifier', 'i18n.title', 'slug.slug')
                ->limit(20)
                ->get();

            foreach ($objects as $obj) {
                $results[] = [
                    'id' => $obj->id,
                    'text' => ($obj->identifier ? "[{$obj->identifier}] " : '') . ($obj->title ?: 'Untitled'),
                    'identifier' => $obj->identifier,
                    'title' => $obj->title,
                    'slug' => $obj->slug,
                ];
            }
        }

        $this->getResponse()->setContentType('application/json');
        return $this->renderText(json_encode(['results' => $results]));
    }

    /**
     * Condition check overview for an object
     */
    public function executeConditionCheck(sfWebRequest $request)
    {
        $this->slug = $request->getParameter('slug');

        // Get resource by slug using Laravel
        $slugRecord = DB::table('slug')
            ->where('slug', $this->slug)
            ->first();

        if (!$slugRecord) {
            $this->forward404('Object not found');
        }

        $this->resource = DB::table('information_object')
            ->where('id', $slugRecord->object_id)
            ->first();

        if (!$this->resource) {
            $this->forward404('Object not found');
        }

        // Get title from i18n
        $i18n = DB::table('information_object_i18n')
            ->where('id', $this->resource->id)
            ->where('culture', 'en')
            ->first();

        $this->resourceTitle = $i18n->title ?? $this->slug;

        // Get condition checks for this object
        try {
            $this->conditions = DB::table('spectrum_condition_check')
                ->where('object_id', $this->resource->id)
                ->orderBy('check_date', 'desc')
                ->get()
                ->toArray();
        } catch (\Exception $e) {
            $this->conditions = [];
        }

        $this->latestCondition = !empty($this->conditions) ? $this->conditions[0] : null;
    }
}
