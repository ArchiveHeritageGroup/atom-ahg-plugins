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
require_once sfConfig::get('sf_lib_dir') . '/arMetadataExtractionTrait.php';

use Illuminate\Database\Capsule\Manager as DB;

class InformationObjectMultiFileUploadAction extends sfAction
{
    // Use the universal metadata extraction trait
    use arMetadataExtractionTrait;

    // Usage IDs
    const USAGE_MASTER = 140;
    const USAGE_REFERENCE = 141;
    const USAGE_THUMBNAIL = 142;

    // Publication status IDs
    const PUBLICATION_STATUS_DRAFT_ID = 159;
    const PUBLICATION_STATUS_TYPE_ID = 158;

    // Root IDs
    const ROOT_INFORMATION_OBJECT_ID = 1;

    // Media type IDs
    const MEDIA_IMAGE = 136;

    // ACL Group IDs
    const GROUP_ADMINISTRATOR = 100;
    const GROUP_EDITOR = 101;

    public function execute($request)
    {
        $this->form = new sfForm();

        $this->resource = $this->getRoute()->resource;

        // Check that object exists and that it is not the root
        if (!isset($this->resource) || !isset($this->resource->parent)) {
            $this->forward404();
        }

        // Check user authorization
        if (!$this->checkAccess($this->resource->id, 'update') && !$this->userHasGroup(self::GROUP_EDITOR)) {
            $this->forward('admin', 'secure');
        }

        // Check if uploads are allowed
        if (!$this->isUploadAllowed()) {
            $this->forward('admin', 'secure');
        }

        // Get max upload size limits
        $this->maxFileSize = $this->getMaxUploadSize();
        $this->maxPostSize = $this->getMaxPostSize();

        // Paths for uploader javascript
        $this->uploadResponsePath = "{$this->context->routing->generate(null, ['module' => 'digitalobject', 'action' => 'upload'])}?" . http_build_query(['informationObjectId' => $this->resource->id]);

        // Add digital object JavaScript
        $this->response->addJavascript('/vendor/jquery.multifile', 'last');

        if ($request->isMethod('post')) {
            $this->processForm($request);
        }
    }

    protected function processForm($request)
    {
        // Get the parent information object
        $parent = $this->resource;
        $parentId = is_object($parent) ? $parent->id : (int) $parent;

        // Get uploaded files
        $uploadIds = $request->getParameter('uploadId', []);
        $titles = $request->getParameter('title', []);
        $levelOfDescriptions = $request->getParameter('levelOfDescription', []);

        if (!is_array($uploadIds)) {
            $uploadIds = [$uploadIds];
            $titles = [$titles];
            $levelOfDescriptions = [$levelOfDescriptions];
        }

        $uploadDir = sfConfig::get('sf_upload_dir') . '/tmp';
        $processedCount = 0;
        $errorCount = 0;

        foreach ($uploadIds as $index => $uploadId) {
            if (empty($uploadId)) {
                continue;
            }

            $uploadFile = $uploadDir . '/' . $uploadId;

            if (!is_readable($uploadFile)) {
                $errorCount++;
                error_log("Multi-upload: Cannot read file {$uploadId}");
                continue;
            }

            try {
                // Set title from form or filename
                $title = !empty($titles[$index]) ? $titles[$index] : $uploadId;

                // Create child information object
                $informationObjectId = $this->createInformationObject([
                    'parent_id' => $parentId,
                    'title' => $title,
                    'level_of_description_id' => !empty($levelOfDescriptions[$index]) ? $levelOfDescriptions[$index] : null,
                    'publication_status_id' => self::PUBLICATION_STATUS_DRAFT_ID,
                ]);

                // Get file content
                $content = file_get_contents($uploadFile);
                $originalName = $request->getParameter('name')[$index] ?? $uploadId;

                // Create digital object
                $digitalObjectId = $this->createDigitalObject([
                    'object_id' => $informationObjectId,
                    'usage_id' => self::USAGE_MASTER,
                    'name' => $originalName,
                    'content' => $content,
                ]);

                // Get the saved digital object for path info
                $digitalObject = $this->getDigitalObjectById($digitalObjectId);

                // =============================================================
                // UNIVERSAL METADATA EXTRACTION
                // =============================================================
                error_log("=== MULTI-FILE UPLOAD - METADATA EXTRACTION ===");
                error_log("File {$index}: " . $originalName);

                // Extract all metadata from original temp file (better quality)
                $metadata = $this->extractAllMetadata($uploadFile);

                if ($metadata) {
                    // Apply metadata to information object
                    $this->applyMetadataToInformationObject(
                        $informationObjectId,
                        $metadata,
                        $digitalObjectId
                    );

                    // Override title with metadata title if form title was empty
                    $keyFields = $metadata['_extractor']['key_fields'] ?? [];
                    if (empty($titles[$index]) && !empty($keyFields['title'])) {
                        $this->setI18nField($informationObjectId, 'title', $keyFields['title']);
                        error_log("Title set from metadata: " . $keyFields['title']);
                    }

                    // Process face detection if enabled and this is an image
                    $fileType = $metadata['_extractor']['file_type'] ?? null;
                    if ($fileType === 'image') {
                        $this->processFaceDetection($uploadFile, $informationObjectId, $digitalObjectId);
                    }
                }

                // Clean up temp file
                if (is_writable($uploadFile)) {
                    unlink($uploadFile);
                }

                $processedCount++;

            } catch (Exception $e) {
                $errorCount++;
                error_log("Multi-upload error for file {$index}: " . $e->getMessage());

                // Clean up temp file even on error
                if (is_writable($uploadFile)) {
                    unlink($uploadFile);
                }
            }
        }

        // Set flash message
        if ($processedCount > 0) {
            $this->getUser()->setFlash('notice', sprintf(
                'Successfully uploaded %d file(s)%s',
                $processedCount,
                $errorCount > 0 ? " ({$errorCount} error(s))" : ''
            ));
        } elseif ($errorCount > 0) {
            $this->getUser()->setFlash('error', 'Failed to upload files');
        }

        // Redirect to parent
        $parentSlug = $this->getObjectSlug($parentId);
        $this->redirect(['module' => 'informationobject', 'slug' => $parentSlug]);
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
     * Check if user has specific group
     */
    protected function userHasGroup(int $groupId): bool
    {
        $user = $this->getUser();

        if (!$user->isAuthenticated()) {
            return false;
        }

        $userId = $user->getAttribute('user_id');
        if (!$userId) {
            return false;
        }

        return DB::table('acl_user_group')
            ->where('user_id', $userId)
            ->where('group_id', $groupId)
            ->exists();
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
     * Create information object
     */
    protected function createInformationObject(array $data): int
    {
        // Create object entry first
        $objectId = DB::table('object')->insertGetId([
            'class_name' => 'QubitInformationObject',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        // Create information object
        DB::table('information_object')->insert([
            'id' => $objectId,
            'parent_id' => $data['parent_id'] ?? self::ROOT_INFORMATION_OBJECT_ID,
            'level_of_description_id' => $data['level_of_description_id'] ?? null,
        ]);

        // Create i18n entry
        DB::table('information_object_i18n')->insert([
            'id' => $objectId,
            'culture' => 'en',
            'title' => $data['title'] ?? null,
        ]);

        // Generate and save slug
        $slug = $this->generateSlugFromName($data['title'] ?? 'untitled');
        DB::table('slug')->insert([
            'object_id' => $objectId,
            'slug' => $slug,
        ]);

        // Set publication status
        if (!empty($data['publication_status_id'])) {
            $this->setPublicationStatus($objectId, $data['publication_status_id']);
        }

        return $objectId;
    }

    /**
     * Set publication status for an object
     */
    protected function setPublicationStatus(int $objectId, int $statusId): void
    {
        // Create status object entry
        $statusObjectId = DB::table('object')->insertGetId([
            'class_name' => 'QubitStatus',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        // Create status
        DB::table('status')->insert([
            'id' => $statusObjectId,
            'object_id' => $objectId,
            'type_id' => self::PUBLICATION_STATUS_TYPE_ID,
            'status_id' => $statusId,
        ]);
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

        // Generate derivatives asynchronously or inline
        $this->generateDerivatives($objectId, $fullPath . $name, $mimeType, $data['object_id'] ?? null);

        return $objectId;
    }

    /**
     * Get media type ID from MIME type
     */
    protected function getMediaTypeIdFromMime(string $mimeType): int
    {
        $mediaTypes = [
            'audio' => 135,
            'image' => 136,
            'text' => 137,
            'video' => 138,
            'other' => 139,
        ];

        $type = explode('/', $mimeType)[0] ?? 'other';

        return $mediaTypes[$type] ?? $mediaTypes['other'];
    }

    /**
     * Generate derivatives for digital object
     */
    protected function generateDerivatives(int $digitalObjectId, string $filePath, string $mimeType, ?int $informationObjectId = null): void
    {
        // For images, generate reference and thumbnail
        if (strpos($mimeType, 'image/') === 0 && $mimeType !== 'image/svg+xml') {
            $this->generateImageDerivatives($digitalObjectId, $filePath, $informationObjectId);
        }
    }

    /**
     * Generate image derivatives (reference and thumbnail)
     */
    protected function generateImageDerivatives(int $parentId, string $masterPath, ?int $informationObjectId = null): void
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
                    'object_id' => $informationObjectId ?? $digitalObject->object_id,
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
     * Get object slug
     */
    protected function getObjectSlug(int $objectId): ?string
    {
        return DB::table('slug')
            ->where('object_id', $objectId)
            ->value('slug');
    }

    /**
     * Set i18n field value
     */
    protected function setI18nField(int $id, string $field, string $value): void
    {
        DB::table('information_object_i18n')
            ->updateOrInsert(
                ['id' => $id, 'culture' => 'en'],
                [$field => $value]
            );
    }

    /**
     * Generate unique slug from name
     */
    protected function generateSlugFromName(string $name): string
    {
        $slug = strtolower(trim($name));
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
        $slug = trim($slug, '-');

        if (empty($slug)) {
            $slug = 'untitled';
        }

        // Ensure uniqueness
        $baseSlug = $slug;
        $counter = 1;

        while (DB::table('slug')->where('slug', $slug)->exists()) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }
}