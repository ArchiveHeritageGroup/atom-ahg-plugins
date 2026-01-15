<?php

/**
 * This file is part of the Access to Memory (AtoM) software.
 *
 * Modified by The AHG to include Universal Metadata Extraction
 * Supports: EXIF, IPTC, XMP (images), PDF metadata, Office documents,
 * Video metadata, Audio ID3 tags, and Face Detection
 *
 * Access to Memory (AtoM) is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * @author Johan Pieterse <johan@theahg.co.za>
 */

// Include the metadata extraction trait
require_once sfConfig::get('sf_root_dir') . '/atom-framework/src/Services/Shared/ahgMetadataExtractionTrait.php';

use Illuminate\Database\Capsule\Manager as DB;

class ObjectAddDigitalObjectAction extends sfAction
{
    // Use the universal metadata extraction trait
    use arMetadataExtractionTrait;

    // Usage IDs
    const USAGE_MASTER = 140;
    const USAGE_REFERENCE = 141;
    const USAGE_THUMBNAIL = 142;

    // Taxonomy IDs
    const TAXONOMY_DIGITAL_OBJECT_USAGE = 47;

    // Media type IDs
    const MEDIA_AUDIO = 135;
    const MEDIA_IMAGE = 136;
    const MEDIA_TEXT = 137;
    const MEDIA_VIDEO = 138;
    const MEDIA_OTHER = 139;

    // ACL Group IDs
    const GROUP_ADMINISTRATOR = 100;
    const GROUP_EDITOR = 101;

    public function execute($request)
    {
        $this->form = new sfForm();
        $this->form->getValidatorSchema()->setOption('allow_extra_fields', true);

        $this->resource = $this->getRoute()->resource;

        // Get resource class name
        $this->resourceClassName = $this->getObjectClassName($this->resource->id);

        // Get repository to test upload limits
        if ($this->resourceClassName === 'QubitInformationObject') {
            $this->repository = $this->getInheritedRepository($this->resource->id);
        } elseif ($this->resourceClassName === 'QubitActor') {
            $this->repository = $this->getMaintainingRepository($this->resource->id);
        }

        // Check that object exists and that it is not the root
        if (!isset($this->resource) || !isset($this->resource->parent)) {
            $this->forward404();
        }

        // Assemble resource description
        if ($this->resourceClassName === 'QubitActor') {
            $this->resourceDescription = $this->getActorTitle($this->resource->id);
        } elseif ($this->resourceClassName === 'QubitInformationObject') {
            $this->resourceDescription = '';

            $ioData = $this->getInformationObjectData($this->resource->id);
            if ($ioData && !empty($ioData->identifier)) {
                $this->resourceDescription .= $ioData->identifier . ' - ';
            }

            $this->resourceDescription .= $ioData->title ?? $this->getObjectSlug($this->resource->id) ?? '';
        }

        // Check user authorization
        if (!$this->checkAccess($this->resource->id, 'update')) {
            $this->forward('admin', 'secure');
        }

        // Check if uploads are allowed
        if (!$this->isUploadAllowed()) {
            $this->forward('admin', 'secure');
        }

        // Handle digital object upload
        $this->uploadLimit = 0;
        if (isset($this->repository) && $this->repository && $this->repository->upload_limit > 0) {
            $this->uploadLimit = $this->repository->upload_limit * pow(10, 9);
        }

        // Paths for uploader javascript
        $this->uploadResponsePath = $this->context->routing->generate(null, ['module' => 'digitalobject', 'action' => 'upload']);

        // Set max file size
        $this->maxFileSize = $this->getMaxUploadSize();
        $this->maxPostSize = $this->getMaxPostSize();

        // Get usageType options
        $this->usageOptions = $this->getUsageOptions();

        // Set default usage type
        $this->defaultUsage = self::USAGE_MASTER;

        if ($request->isMethod('post')) {
            $this->processForm($request);
        }
    }

    protected function processForm($request)
    {
        // Check if user submitted file via standard input or via async upload
        if (null !== $request->getParameter('uploadId')) {
            $uploadId = $request->getParameter('uploadId');
            $uploadDir = sfConfig::get('sf_upload_dir') . '/tmp';
            $uploadFile = $uploadDir . '/' . $uploadId;

            if (is_readable($uploadFile)) {
                // Handle async upload
                $content = file_get_contents($uploadFile);
                $name = $request->getParameter('name');
            } else {
                // Async file not found - error
                $this->form->getErrorSchema()->addError(new sfValidatorError(
                    new sfValidatorPass(),
                    $this->context->i18n->__('Unable to retrieve uploaded file.')
                ));
                return;
            }
        } elseif (0 < count($request->getFiles('file'))) {
            // Handle standard file upload
            $file = $request->getFiles('file');

            if (UPLOAD_ERR_OK !== $file['error']) {
                $this->form->getErrorSchema()->addError(new sfValidatorError(
                    new sfValidatorPass(),
                    $this->getUploadErrorMessage($file['error'])
                ));
                return;
            }

            $content = file_get_contents($file['tmp_name']);
            $name = $file['name'];

            // Store file path for metadata extraction
            $uploadFilePath = $file['tmp_name'];
        } else {
            // No file uploaded
            return;
        }

        // Create digital object
        $usageId = $request->getParameter('usageId') ?? self::USAGE_MASTER;

        $digitalObjectId = $this->createDigitalObject([
            'object_id' => $this->resource->id,
            'usage_id' => $usageId,
            'name' => $name,
            'content' => $content,
        ]);

        // Get the saved digital object for path info
        $digitalObject = $this->getDigitalObjectById($digitalObjectId);

        // Get the saved file path for metadata extraction
        $uploadDir = sfConfig::get('sf_upload_dir');
        $savedFilePath = $uploadDir . $digitalObject->path . $digitalObject->name;

        // If we have a temp file path, use that (better quality metadata from original)
        $extractionPath = isset($uploadFilePath) && file_exists($uploadFilePath)
            ? $uploadFilePath
            : $savedFilePath;

        // =============================================================
        // UNIVERSAL METADATA EXTRACTION
        // =============================================================
        error_log("=== DIGITAL OBJECT UPLOAD - METADATA EXTRACTION ===");
        error_log("File: " . $name);
        error_log("Extraction path: " . $extractionPath);

        // Only process for information objects (not actors)
        if ($this->resourceClassName === 'QubitInformationObject') {
            // Extract all metadata (EXIF, IPTC, XMP, PDF, Office, Video, Audio)
            $metadata = $this->extractAllMetadata($extractionPath);

            if ($metadata) {
                // Apply metadata to information object
                $this->applyMetadataToInformationObject(
                    $this->resource->id,
                    $metadata,
                    $digitalObjectId
                );

                error_log("Metadata applied to information object");

                // Process face detection if enabled and this is an image
                $fileType = $metadata['_extractor']['file_type'] ?? null;
                if ($fileType === 'image') {
                    $this->processFaceDetection($extractionPath, $this->resource->id, $digitalObjectId);
                }
            }
        }

        // Clean up async upload temp file
        if (isset($uploadFile) && is_writable($uploadFile)) {
            unlink($uploadFile);
        }

        // Redirect
        $resourceSlug = $this->getObjectSlug($this->resource->id);
        $this->redirect(['module' => $this->getModuleName(), 'slug' => $resourceSlug]);
    }

    /**
     * Get module name based on resource type
     */
    public function getModuleName(): string
    {
        if ($this->resourceClassName === 'QubitInformationObject') {
            return 'informationobject';
        } elseif ($this->resourceClassName === 'QubitActor') {
            return 'actor';
        }

        return 'object';
    }

    /**
     * Check access permission
     */
    protected function checkAccess(int $objectId, string $action): bool
    {
        $user = $this->getUser();

        if (!$user->isAuthenticated()) {
            return false;
        }

        $userId = $user->getAttribute('user_id');
        if (!$userId) {
            return false;
        }

        // Get user's groups
        $userGroups = DB::table('acl_user_group')
            ->where('user_id', $userId)
            ->pluck('group_id')
            ->toArray();

        // Administrators have full access
        if (in_array(self::GROUP_ADMINISTRATOR, $userGroups)) {
            return true;
        }

        // Editors can update
        if ($action === 'update' && in_array(self::GROUP_EDITOR, $userGroups)) {
            return true;
        }

        return false;
    }

    /**
     * Check if uploads are allowed
     */
    protected function isUploadAllowed(): bool
    {
        $checkDiskUsage = sfConfig::get('app_check_for_updates', false);

        if ($checkDiskUsage) {
            $uploadDir = sfConfig::get('sf_upload_dir');
            $freeSpace = @disk_free_space($uploadDir);

            if ($freeSpace !== false && $freeSpace < 1073741824) { // 1GB minimum
                return false;
            }
        }

        return true;
    }

    /**
     * Get max upload size
     */
    protected function getMaxUploadSize(): int
    {
        $uploadMax = $this->parseSize(ini_get('upload_max_filesize'));
        $postMax = $this->parseSize(ini_get('post_max_size'));

        return min($uploadMax, $postMax);
    }

    /**
     * Get max post size
     */
    protected function getMaxPostSize(): int
    {
        return $this->parseSize(ini_get('post_max_size'));
    }

    /**
     * Parse size string to bytes
     */
    protected function parseSize(string $size): int
    {
        $unit = strtolower(substr($size, -1));
        $value = (int) $size;

        switch ($unit) {
            case 'g':
                $value *= 1024 * 1024 * 1024;
                break;
            case 'm':
                $value *= 1024 * 1024;
                break;
            case 'k':
                $value *= 1024;
                break;
        }

        return $value;
    }

    /**
     * Get usage options for select list
     */
    protected function getUsageOptions(): array
    {
        $options = [];

        $terms = DB::table('term as t')
            ->leftJoin('term_i18n as ti', function ($join) {
                $join->on('t.id', '=', 'ti.id')
                    ->where('ti.culture', '=', 'en');
            })
            ->where('t.taxonomy_id', self::TAXONOMY_DIGITAL_OBJECT_USAGE)
            ->orderBy('ti.name')
            ->select('t.id', 'ti.name')
            ->get();

        foreach ($terms as $term) {
            $options[$term->id] = $term->name;
        }

        return $options;
    }

    /**
     * Get upload error message
     */
    protected function getUploadErrorMessage(int $errorCode): string
    {
        $messages = [
            UPLOAD_ERR_INI_SIZE => 'The uploaded file exceeds the upload_max_filesize directive in php.ini.',
            UPLOAD_ERR_FORM_SIZE => 'The uploaded file exceeds the MAX_FILE_SIZE directive in the HTML form.',
            UPLOAD_ERR_PARTIAL => 'The uploaded file was only partially uploaded.',
            UPLOAD_ERR_NO_FILE => 'No file was uploaded.',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing a temporary folder.',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
            UPLOAD_ERR_EXTENSION => 'A PHP extension stopped the file upload.',
        ];

        return $messages[$errorCode] ?? 'Unknown upload error.';
    }

    /**
     * Get object class name
     */
    protected function getObjectClassName(int $objectId): ?string
    {
        return DB::table('object')
            ->where('id', $objectId)
            ->value('class_name');
    }

    /**
     * Get object slug
     */
    protected function getObjectSlug(int $objectId): ?string
    {
        return DB::table('slug')
            ->where('object_id', $objectId)
            ->value('slug');
    }

    /**
     * Get information object data
     */
    protected function getInformationObjectData(int $id): ?object
    {
        return DB::table('information_object as io')
            ->leftJoin('information_object_i18n as i18n', function ($join) {
                $join->on('io.id', '=', 'i18n.id')
                    ->where('i18n.culture', '=', 'en');
            })
            ->where('io.id', $id)
            ->select('io.*', 'i18n.title')
            ->first();
    }

    /**
     * Get actor title
     */
    protected function getActorTitle(int $actorId): ?string
    {
        return DB::table('actor_i18n')
            ->where('id', $actorId)
            ->where('culture', 'en')
            ->value('authorized_form_of_name');
    }

    /**
     * Get inherited repository for information object
     */
    protected function getInheritedRepository(int $informationObjectId): ?object
    {
        $current = DB::table('information_object')
            ->where('id', $informationObjectId)
            ->first();

        while ($current) {
            if ($current->repository_id) {
                return DB::table('repository')
                    ->where('id', $current->repository_id)
                    ->first();
            }

            if (!$current->parent_id || $current->parent_id == 1) {
                break;
            }

            $current = DB::table('information_object')
                ->where('id', $current->parent_id)
                ->first();
        }

        return null;
    }

    /**
     * Get maintaining repository for actor
     */
    protected function getMaintainingRepository(int $actorId): ?object
    {
        $relation = DB::table('relation')
            ->where('subject_id', $actorId)
            ->where('type_id', 161) // MAINTAINING_REPOSITORY_RELATION_TYPE
            ->first();

        if ($relation) {
            return DB::table('repository')
                ->where('id', $relation->object_id)
                ->first();
        }

        return null;
    }

    /**
     * Get digital object by ID
     */
    protected function getDigitalObjectById(int $id): ?object
    {
        return DB::table('digital_object')
            ->where('id', $id)
            ->first();
    }

    /**
     * Create digital object
     */
    protected function createDigitalObject(array $data): int
    {
        $content = $data['content'] ?? null;
        $name = $data['name'] ?? 'unnamed';
        unset($data['content']);

        // Determine MIME type
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->buffer($content);

        // Determine media type from MIME
        $mediaTypeId = $this->getMediaTypeIdFromMime($mimeType);

        // Generate storage path
        $uploadDir = sfConfig::get('sf_upload_dir');
        $relativePath = '/r/' . date('Y/m/d') . '/' . uniqid() . '/';
        $fullPath = $uploadDir . $relativePath;

        if (!is_dir($fullPath)) {
            mkdir($fullPath, 0755, true);
        }

        // Save file
        $filePath = $fullPath . $name;
        file_put_contents($filePath, $content);

        // Create object entry
        $objectId = DB::table('object')->insertGetId([
            'class_name' => 'QubitDigitalObject',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        // Create digital object
        DB::table('digital_object')->insert([
            'id' => $objectId,
            'object_id' => $data['object_id'] ?? null,
            'parent_id' => $data['parent_id'] ?? null,
            'usage_id' => $data['usage_id'] ?? self::USAGE_MASTER,
            'media_type_id' => $mediaTypeId,
            'name' => $name,
            'path' => '/uploads' . $relativePath,
            'mime_type' => $mimeType,
            'byte_size' => strlen($content),
            'checksum' => md5($content),
            'checksum_type' => 'md5',
        ]);

        // Generate derivatives for images
        if (strpos($mimeType, 'image/') === 0 && $mimeType !== 'image/svg+xml') {
            $this->generateImageDerivatives($objectId, $filePath, $data['object_id'] ?? null);
        }

        return $objectId;
    }

    /**
     * Get media type ID from MIME type
     */
    protected function getMediaTypeIdFromMime(string $mimeType): int
    {
        $type = explode('/', $mimeType)[0] ?? 'other';

        $mediaTypes = [
            'audio' => self::MEDIA_AUDIO,
            'image' => self::MEDIA_IMAGE,
            'text' => self::MEDIA_TEXT,
            'video' => self::MEDIA_VIDEO,
        ];

        return $mediaTypes[$type] ?? self::MEDIA_OTHER;
    }

    /**
     * Generate image derivatives (reference and thumbnail)
     */
    protected function generateImageDerivatives(int $parentId, string $masterPath, ?int $informationObjectId): void
    {
        if (!file_exists($masterPath)) {
            return;
        }

        $uploadDir = sfConfig::get('sf_upload_dir');
        $digitalObject = $this->getDigitalObjectById($parentId);

        if (!$digitalObject) {
            return;
        }

        $sizes = [
            'reference' => ['usage_id' => self::USAGE_REFERENCE, 'max' => 480],
            'thumbnail' => ['usage_id' => self::USAGE_THUMBNAIL, 'max' => 150],
        ];

        foreach ($sizes as $type => $config) {
            $relativePath = '/r/' . date('Y/m/d') . '/' . uniqid() . '/';
            $fullPath = $uploadDir . $relativePath;

            if (!is_dir($fullPath)) {
                mkdir($fullPath, 0755, true);
            }

            $derivativeName = pathinfo($digitalObject->name, PATHINFO_FILENAME) . "_{$type}.jpg";
            $derivativePath = $fullPath . $derivativeName;

            // Generate derivative using ImageMagick
            $cmd = sprintf(
                'convert %s -resize %dx%d -quality 85 %s 2>&1',
                escapeshellarg($masterPath),
                $config['max'],
                $config['max'],
                escapeshellarg($derivativePath)
            );

            exec($cmd, $output, $returnCode);

            if ($returnCode === 0 && file_exists($derivativePath)) {
                // Create object entry
                $objectId = DB::table('object')->insertGetId([
                    'class_name' => 'QubitDigitalObject',
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);

                // Create digital object
                DB::table('digital_object')->insert([
                    'id' => $objectId,
                    'object_id' => $informationObjectId,
                    'parent_id' => $parentId,
                    'usage_id' => $config['usage_id'],
                    'media_type_id' => self::MEDIA_IMAGE,
                    'name' => $derivativeName,
                    'path' => '/uploads' . $relativePath,
                    'mime_type' => 'image/jpeg',
                    'byte_size' => filesize($derivativePath),
                    'checksum' => md5_file($derivativePath),
                    'checksum_type' => 'md5',
                ]);
            }
        }
    }
}