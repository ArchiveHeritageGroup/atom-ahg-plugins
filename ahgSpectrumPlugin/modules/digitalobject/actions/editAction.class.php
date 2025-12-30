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

class DigitalObjectEditAction extends sfAction
{
    // Use the universal metadata extraction trait
    use arMetadataExtractionTrait;

    // Usage IDs
    const USAGE_MASTER = 140;
    const USAGE_REFERENCE = 141;
    const USAGE_THUMBNAIL = 142;

    // Taxonomy IDs
    const TAXONOMY_DIGITAL_OBJECT_USAGE = 47;

    // ACL Group IDs
    const GROUP_ADMINISTRATOR = 100;
    const GROUP_EDITOR = 101;

    public function execute($request)
    {
        $this->form = new sfForm();
        $this->form->getValidatorSchema()->setOption('allow_extra_fields', true);

        $this->resource = $this->getRoute()->resource;

        // Check that object exists
        if (!isset($this->resource)) {
            $this->forward404();
        }

        // Get parent information object or actor
        $this->parent = $this->resource->informationObject ?? $this->resource->actor;

        // Check user authorization
        if (isset($this->parent) && !$this->checkAccess($this->parent->id, 'update')) {
            $this->forward('admin', 'secure');
        }

        // Check if uploads are allowed
        if (!$this->isUploadAllowed()) {
            $this->forward('admin', 'secure');
        }

        // Get usageType options
        $this->usageOptions = $this->getUsageOptions();

        // Set max file size
        $this->maxFileSize = $this->getMaxUploadSize();
        $this->maxPostSize = $this->getMaxPostSize();

        // Paths for uploader javascript
        $this->uploadResponsePath = $this->context->routing->generate(null, ['module' => 'digitalobject', 'action' => 'upload']);

        if ($request->isMethod('post')) {
            $this->processForm($request);
        }
    }

    protected function processForm($request)
    {
        // Handle reference representation upload
        if ($request->hasParameter('referenceUploadId') || isset($_FILES['referenceFile'])) {
            $this->processRepresentationUpload($request, 'reference');
        }

        // Handle thumbnail representation upload
        if ($request->hasParameter('thumbnailUploadId') || isset($_FILES['thumbnailFile'])) {
            $this->processRepresentationUpload($request, 'thumbnail');
        }

        // Handle master replacement upload
        if ($request->hasParameter('masterUploadId') || isset($_FILES['masterFile'])) {
            $this->processMasterReplacement($request);
        }

        // Handle metadata updates
        if ($request->hasParameter('mediaType')) {
            $this->updateDigitalObject($this->resource->id, ['media_type_id' => $request->getParameter('mediaType')]);
        }

        if ($request->hasParameter('usageId')) {
            $this->updateDigitalObject($this->resource->id, ['usage_id' => $request->getParameter('usageId')]);
        }

        // Update timestamp
        DB::table('object')
            ->where('id', $this->resource->id)
            ->update(['updated_at' => date('Y-m-d H:i:s')]);

        // Redirect
        if (isset($this->parent)) {
            $parentSlug = $this->getObjectSlug($this->parent->id);
            $parentClassName = $this->getObjectClassName($this->parent->id);

            if ($parentClassName === 'QubitInformationObject') {
                $this->redirect(['module' => 'informationobject', 'slug' => $parentSlug]);
            } elseif ($parentClassName === 'QubitActor') {
                $this->redirect(['module' => 'actor', 'slug' => $parentSlug]);
            }
        }

        $resourceSlug = $this->getObjectSlug($this->resource->id);
        $this->redirect(['module' => 'digitalobject', 'slug' => $resourceSlug]);
    }

    /**
     * Process representation upload (reference or thumbnail)
     */
    protected function processRepresentationUpload($request, $type): void
    {
        $uploadIdParam = $type . 'UploadId';
        $fileParam = $type . 'File';

        // Determine usage type
        $usageId = $type === 'reference' ? self::USAGE_REFERENCE : self::USAGE_THUMBNAIL;

        // Get file content
        if ($request->hasParameter($uploadIdParam)) {
            // Async upload
            $uploadId = $request->getParameter($uploadIdParam);
            $uploadDir = sfConfig::get('sf_upload_dir') . '/tmp';
            $uploadFile = $uploadDir . '/' . $uploadId;

            if (!is_readable($uploadFile)) {
                error_log("Cannot read {$type} upload file: {$uploadFile}");
                return;
            }

            $content = file_get_contents($uploadFile);
            $name = $request->getParameter($type . 'Name') ?? $uploadId;
            $tempPath = $uploadFile;

        } elseif (isset($_FILES[$fileParam]) && $_FILES[$fileParam]['error'] === UPLOAD_ERR_OK) {
            // Standard upload
            $content = file_get_contents($_FILES[$fileParam]['tmp_name']);
            $name = $_FILES[$fileParam]['name'];
            $tempPath = $_FILES[$fileParam]['tmp_name'];

        } else {
            return;
        }

        // Delete existing representation of this type
        $existingDerivatives = DB::table('digital_object')
            ->where('parent_id', $this->resource->id)
            ->where('usage_id', $usageId)
            ->get();

        foreach ($existingDerivatives as $existing) {
            $this->deleteDigitalObject($existing->id);
        }

        // Create new representation
        $representationId = $this->createDigitalObject([
            'parent_id' => $this->resource->id,
            'object_id' => $this->resource->object_id,
            'usage_id' => $usageId,
            'name' => $name,
            'content' => $content,
        ]);

        error_log("Created {$type} representation: {$name}");

        // Clean up temp file
        if (isset($uploadFile) && is_writable($uploadFile)) {
            unlink($uploadFile);
        }
    }

    /**
     * Process master file replacement
     */
    protected function processMasterReplacement($request): void
    {
        // Get file content
        if ($request->hasParameter('masterUploadId')) {
            // Async upload
            $uploadId = $request->getParameter('masterUploadId');
            $uploadDir = sfConfig::get('sf_upload_dir') . '/tmp';
            $uploadFile = $uploadDir . '/' . $uploadId;

            if (!is_readable($uploadFile)) {
                error_log("Cannot read master upload file: {$uploadFile}");
                return;
            }

            $content = file_get_contents($uploadFile);
            $name = $request->getParameter('masterName') ?? $uploadId;
            $tempPath = $uploadFile;

        } elseif (isset($_FILES['masterFile']) && $_FILES['masterFile']['error'] === UPLOAD_ERR_OK) {
            // Standard upload
            $content = file_get_contents($_FILES['masterFile']['tmp_name']);
            $name = $_FILES['masterFile']['name'];
            $tempPath = $_FILES['masterFile']['tmp_name'];

        } else {
            return;
        }

        // Update master digital object
        $this->updateMasterAsset($this->resource->id, $name, $content);

        error_log("=== MASTER REPLACEMENT - METADATA EXTRACTION ===");
        error_log("File: " . $name);

        // Get saved file path
        $digitalObject = $this->getDigitalObjectById($this->resource->id);
        $uploadDir = sfConfig::get('sf_upload_dir');
        $savedFilePath = $uploadDir . $digitalObject->path . $digitalObject->name;

        // =============================================================
        // UNIVERSAL METADATA EXTRACTION
        // =============================================================
        // Use temp file if available (better quality), otherwise use saved file
        $extractionPath = isset($tempPath) && file_exists($tempPath) ? $tempPath : $savedFilePath;

        // Extract metadata
        $metadata = $this->extractAllMetadata($extractionPath);

        if ($metadata && isset($this->parent)) {
            $parentClassName = $this->getObjectClassName($this->parent->id);

            if ($parentClassName === 'QubitInformationObject') {
                // Apply metadata to information object
                $this->applyMetadataToInformationObject(
                    $this->parent->id,
                    $metadata,
                    $this->resource->id
                );

                error_log("Metadata applied to information object");

                // Process face detection if enabled and this is an image
                $fileType = $metadata['_extractor']['file_type'] ?? null;
                if ($fileType === 'image') {
                    $this->processFaceDetection($extractionPath, $this->parent->id, $this->resource->id);
                }
            }
        }

        // Clean up temp file
        if (isset($uploadFile) && is_writable($uploadFile)) {
            unlink($uploadFile);
        }
    }

    /**
     * Re-extract metadata from existing digital object
     * Useful for batch processing
     */
    public function reextractMetadata(): bool
    {
        if (!$this->resource) {
            return false;
        }

        $digitalObject = $this->getDigitalObjectById($this->resource->id);

        if (!$digitalObject || !$digitalObject->path) {
            return false;
        }

        $uploadDir = sfConfig::get('sf_upload_dir');
        $filePath = $uploadDir . $digitalObject->path . $digitalObject->name;

        if (!file_exists($filePath)) {
            error_log("File not found for re-extraction: {$filePath}");
            return false;
        }

        $metadata = $this->extractAllMetadata($filePath);

        if (!$metadata) {
            return false;
        }

        if (isset($this->parent)) {
            $parentClassName = $this->getObjectClassName($this->parent->id);

            if ($parentClassName === 'QubitInformationObject') {
                return $this->applyMetadataToInformationObject(
                    $this->parent->id,
                    $metadata,
                    $this->resource->id
                );
            }
        }

        return true;
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
        // Check disk usage setting
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
     * Get object class name
     */
    protected function getObjectClassName(int $objectId): ?string
    {
        return DB::table('object')
            ->where('id', $objectId)
            ->value('class_name');
    }

    /**
     * Update digital object
     */
    protected function updateDigitalObject(int $id, array $data): void
    {
        DB::table('digital_object')
            ->where('id', $id)
            ->update($data);
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
     * Update master asset
     */
    protected function updateMasterAsset(int $digitalObjectId, string $name, string $content): void
    {
        $digitalObject = $this->getDigitalObjectById($digitalObjectId);

        if (!$digitalObject) {
            return;
        }

        // Determine MIME type
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->buffer($content);

        // Determine media type from MIME
        $mediaTypeId = $this->getMediaTypeIdFromMime($mimeType);

        // Generate new storage path
        $uploadDir = sfConfig::get('sf_upload_dir');
        $relativePath = '/r/' . date('Y/m/d') . '/' . uniqid() . '/';
        $fullPath = $uploadDir . $relativePath;

        if (!is_dir($fullPath)) {
            mkdir($fullPath, 0755, true);
        }

        // Delete old file if exists
        if ($digitalObject->path && $digitalObject->name) {
            $oldFile = $uploadDir . str_replace('/uploads', '', $digitalObject->path) . $digitalObject->name;
            if (file_exists($oldFile)) {
                @unlink($oldFile);
            }
        }

        // Save new file
        $filePath = $fullPath . $name;
        file_put_contents($filePath, $content);

        // Update digital object record
        DB::table('digital_object')
            ->where('id', $digitalObjectId)
            ->update([
                'name' => $name,
                'path' => '/uploads' . $relativePath,
                'mime_type' => $mimeType,
                'media_type_id' => $mediaTypeId,
                'byte_size' => strlen($content),
                'checksum' => md5($content),
                'checksum_type' => 'md5',
            ]);

        // Update object timestamp
        DB::table('object')
            ->where('id', $digitalObjectId)
            ->update(['updated_at' => date('Y-m-d H:i:s')]);
    }

    /**
     * Delete digital object
     */
    protected function deleteDigitalObject(int $id): void
    {
        $digitalObject = $this->getDigitalObjectById($id);

        if (!$digitalObject) {
            return;
        }

        // Delete file
        if ($digitalObject->path && $digitalObject->name) {
            $uploadDir = sfConfig::get('sf_upload_dir');
            $filePath = $uploadDir . str_replace('/uploads', '', $digitalObject->path) . $digitalObject->name;
            if (file_exists($filePath)) {
                @unlink($filePath);
            }
        }

        // Delete database records
        DB::table('digital_object')->where('id', $id)->delete();
        DB::table('object')->where('id', $id)->delete();
    }
}