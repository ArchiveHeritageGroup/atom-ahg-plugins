<?php

use AtomFramework\Http\Controllers\AhgController;
/**
 * Metadata Extraction Module Actions.
 *
 * Provides UI for viewing and managing extracted metadata from digital objects.
 *
 * @author Johan Pieterse <johan@theahg.co.za>
 */
class metadataExtractionActions extends AhgController
{
    /**
     * Index action - display list of digital objects with extraction status.
     */
    public function executeIndex($request)
    {
        // Check user authorization
        if (!$this->getUser()->isAuthenticated()) {
            $this->redirect('user/login');
        }

        if (!QubitAcl::check(QubitInformationObject::getRoot(), 'update')) {
            $this->forward('admin', 'secure');
        }

        // Check ExifTool availability
        $this->exifToolAvailable = ahgMetadataExtractionPluginConfiguration::isExifToolAvailable();

        // Get pagination parameters
        $this->page = max(1, (int) $request->getParameter('page', 1));
        $this->limit = 25;
        $offset = ($this->page - 1) * $this->limit;

        // Get filter parameters
        $this->filterMimeType = $request->getParameter('mime_type', '');
        $this->filterExtracted = $request->getParameter('extracted', '');

        // Build query
        $query = Illuminate\Database\Capsule\Manager::table('digital_object as do')
            ->join('information_object as io', 'do.information_object_id', '=', 'io.id')
            ->leftJoin('information_object_i18n as ioi', function ($join) {
                $join->on('io.id', '=', 'ioi.id')
                    ->where('ioi.culture', '=', 'en');
            })
            ->select(
                'do.id',
                'do.name',
                'do.path',
                'do.mime_type',
                'do.byte_size',
                'do.information_object_id',
                'ioi.title as record_title'
            )
            ->whereNotNull('do.path');

        // Apply mime type filter
        if (!empty($this->filterMimeType)) {
            $query->where('do.mime_type', 'LIKE', $this->filterMimeType . '%');
        }

        // Get total count
        $this->totalCount = $query->count();

        // Get digital objects
        $this->digitalObjects = $query
            ->orderBy('do.id', 'desc')
            ->offset($offset)
            ->limit($this->limit)
            ->get();

        // Get extracted metadata count for each
        foreach ($this->digitalObjects as $obj) {
            $obj->metadata_count = Illuminate\Database\Capsule\Manager::table('property')
                ->where('object_id', $obj->id)
                ->where('scope', 'metadata_extraction')
                ->count();
        }

        // Apply extracted filter (done post-query for simplicity)
        if ($this->filterExtracted === 'yes') {
            $this->digitalObjects = $this->digitalObjects->filter(fn ($obj) => $obj->metadata_count > 0);
        } elseif ($this->filterExtracted === 'no') {
            $this->digitalObjects = $this->digitalObjects->filter(fn ($obj) => $obj->metadata_count == 0);
        }

        // Calculate pagination
        $this->totalPages = ceil($this->totalCount / $this->limit);

        // Get available mime types for filter
        $this->mimeTypes = Illuminate\Database\Capsule\Manager::table('digital_object')
            ->select('mime_type')
            ->distinct()
            ->whereNotNull('mime_type')
            ->orderBy('mime_type')
            ->pluck('mime_type')
            ->toArray();
    }

    /**
     * View metadata for a specific digital object.
     */
    public function executeView($request)
    {
        // Check user authorization
        if (!$this->getUser()->isAuthenticated()) {
            $this->redirect('user/login');
        }

        $this->digitalObjectId = (int) $request->getParameter('id');

        // Get digital object info
        $this->digitalObject = Illuminate\Database\Capsule\Manager::table('digital_object as do')
            ->join('information_object as io', 'do.information_object_id', '=', 'io.id')
            ->leftJoin('information_object_i18n as ioi', function ($join) {
                $join->on('io.id', '=', 'ioi.id')
                    ->where('ioi.culture', '=', 'en');
            })
            ->where('do.id', $this->digitalObjectId)
            ->select(
                'do.id',
                'do.name',
                'do.path',
                'do.mime_type',
                'do.byte_size',
                'do.information_object_id',
                'ioi.title as record_title',
                'io.slug'
            )
            ->first();

        if (!$this->digitalObject) {
            $this->forward404('Digital object not found');
        }

        // Get extracted metadata
        $this->metadata = Illuminate\Database\Capsule\Manager::table('property')
            ->where('object_id', $this->digitalObjectId)
            ->where('scope', 'metadata_extraction')
            ->orderBy('name')
            ->get();

        // Group metadata by category (EXIF group)
        $this->groupedMetadata = [];
        foreach ($this->metadata as $meta) {
            // Parse group from name (e.g., "EXIF:ImageWidth" -> "EXIF")
            $parts = explode(':', $meta->name, 2);
            $group = count($parts) > 1 ? $parts[0] : 'General';
            $fieldName = count($parts) > 1 ? $parts[1] : $meta->name;

            if (!isset($this->groupedMetadata[$group])) {
                $this->groupedMetadata[$group] = [];
            }

            $this->groupedMetadata[$group][] = (object) [
                'name' => $fieldName,
                'full_name' => $meta->name,
                'value' => $meta->value,
            ];
        }

        ksort($this->groupedMetadata);
    }

    /**
     * Extract metadata from a digital object.
     */
    public function executeExtract($request)
    {
        // Check user authorization
        if (!$this->getUser()->isAuthenticated()) {
            $this->getResponse()->setStatusCode(401);

            return $this->renderText(json_encode(['error' => 'Unauthorized']));
        }

        if (!QubitAcl::check(QubitInformationObject::getRoot(), 'update')) {
            $this->getResponse()->setStatusCode(403);

            return $this->renderText(json_encode(['error' => 'Forbidden']));
        }

        $this->getResponse()->setContentType('application/json');

        $digitalObjectId = (int) $request->getParameter('id');

        // Get digital object
        $digitalObject = Illuminate\Database\Capsule\Manager::table('digital_object')
            ->where('id', $digitalObjectId)
            ->first();

        if (!$digitalObject) {
            $this->getResponse()->setStatusCode(404);

            return $this->renderText(json_encode(['error' => 'Digital object not found']));
        }

        // Check ExifTool availability
        if (!ahgMetadataExtractionPluginConfiguration::isExifToolAvailable()) {
            $this->getResponse()->setStatusCode(500);

            return $this->renderText(json_encode(['error' => 'ExifTool is not installed on this system']));
        }

        // Build file path
        $filePath = $this->config('sf_web_dir') . '/' . $digitalObject->path;

        if (!file_exists($filePath)) {
            $this->getResponse()->setStatusCode(404);

            return $this->renderText(json_encode(['error' => 'File not found: ' . $digitalObject->path]));
        }

        try {
            // Use ExifTool to extract metadata
            $exifToolPath = $this->config('app_metadata_exiftool_path', '/usr/bin/exiftool');

            $command = sprintf(
                '%s -json -a -G1 %s 2>&1',
                escapeshellcmd($exifToolPath),
                escapeshellarg($filePath)
            );

            exec($command, $output, $returnCode);

            if ($returnCode !== 0) {
                throw new RuntimeException('ExifTool failed: ' . implode("\n", $output));
            }

            $json = implode("\n", $output);
            $data = json_decode($json, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new RuntimeException('Failed to parse ExifTool output');
            }

            $metadata = $data[0] ?? [];

            // Delete existing metadata
            $this->deleteExistingMetadata($digitalObjectId);

            // Save new metadata
            $savedCount = 0;
            foreach ($metadata as $key => $value) {
                if (is_array($value)) {
                    $value = json_encode($value);
                }

                $this->saveMetadataProperty($digitalObjectId, $key, (string) $value);
                ++$savedCount;
            }

            return $this->renderText(json_encode([
                'success' => true,
                'message' => "Extracted {$savedCount} metadata fields",
                'count' => $savedCount,
            ]));
        } catch (Exception $e) {
            $this->getResponse()->setStatusCode(500);

            return $this->renderText(json_encode(['error' => $e->getMessage()]));
        }
    }

    /**
     * Batch extract metadata.
     */
    public function executeBatchExtract($request)
    {
        // Check user authorization
        if (!$this->getUser()->isAuthenticated()) {
            $this->redirect('user/login');
        }

        if (!QubitAcl::check(QubitInformationObject::getRoot(), 'update')) {
            $this->forward('admin', 'secure');
        }

        // Check ExifTool availability
        if (!ahgMetadataExtractionPluginConfiguration::isExifToolAvailable()) {
            $this->getUser()->setFlash('error', 'ExifTool is not installed on this system');
            $this->redirect(['module' => 'metadataExtraction', 'action' => 'index']);
        }

        // Get digital objects without extracted metadata
        $digitalObjects = Illuminate\Database\Capsule\Manager::table('digital_object')
            ->whereNotNull('path')
            ->whereNotIn('id', function ($query) {
                $query->select('object_id')
                    ->from('property')
                    ->where('scope', 'metadata_extraction')
                    ->distinct();
            })
            ->limit(50) // Process 50 at a time
            ->get();

        $processed = 0;
        $errors = 0;
        $exifToolPath = $this->config('app_metadata_exiftool_path', '/usr/bin/exiftool');

        foreach ($digitalObjects as $obj) {
            $filePath = $this->config('sf_web_dir') . '/' . $obj->path;

            if (!file_exists($filePath)) {
                ++$errors;

                continue;
            }

            try {
                $command = sprintf(
                    '%s -json -a -G1 %s 2>&1',
                    escapeshellcmd($exifToolPath),
                    escapeshellarg($filePath)
                );

                exec($command, $output, $returnCode);

                if ($returnCode === 0) {
                    $json = implode("\n", $output);
                    $data = json_decode($json, true);

                    if (json_last_error() === JSON_ERROR_NONE && isset($data[0])) {
                        foreach ($data[0] as $key => $value) {
                            if (is_array($value)) {
                                $value = json_encode($value);
                            }
                            $this->saveMetadataProperty($obj->id, $key, (string) $value);
                        }
                        ++$processed;
                    }
                }

                $output = []; // Reset for next iteration
            } catch (Exception $e) {
                ++$errors;
            }
        }

        $remaining = Illuminate\Database\Capsule\Manager::table('digital_object')
            ->whereNotNull('path')
            ->whereNotIn('id', function ($query) {
                $query->select('object_id')
                    ->from('property')
                    ->where('scope', 'metadata_extraction')
                    ->distinct();
            })
            ->count();

        if ($remaining > 0) {
            $this->getUser()->setFlash('notice', "Processed {$processed} files ({$errors} errors). {$remaining} remaining - run again to continue.");
        } else {
            $this->getUser()->setFlash('notice', "Batch extraction complete. Processed {$processed} files ({$errors} errors).");
        }

        $this->redirect(['module' => 'metadataExtraction', 'action' => 'index']);
    }

    /**
     * Delete metadata for a digital object.
     */
    public function executeDelete($request)
    {
        // Check user authorization
        if (!$this->getUser()->isAuthenticated()) {
            $this->getResponse()->setStatusCode(401);

            return $this->renderText(json_encode(['error' => 'Unauthorized']));
        }

        if (!QubitAcl::check(QubitInformationObject::getRoot(), 'delete')) {
            $this->getResponse()->setStatusCode(403);

            return $this->renderText(json_encode(['error' => 'Forbidden']));
        }

        $this->getResponse()->setContentType('application/json');

        $digitalObjectId = (int) $request->getParameter('id');

        $this->deleteExistingMetadata($digitalObjectId);

        return $this->renderText(json_encode([
            'success' => true,
            'message' => 'Metadata deleted',
        ]));
    }

    /**
     * Status check action.
     */
    public function executeStatus($request)
    {
        // Check user authorization
        if (!$this->getUser()->isAuthenticated()) {
            $this->redirect('user/login');
        }

        $this->exifToolAvailable = ahgMetadataExtractionPluginConfiguration::isExifToolAvailable();

        // Get ExifTool version if available
        $this->exifToolVersion = null;
        if ($this->exifToolAvailable) {
            $exifToolPath = $this->config('app_metadata_exiftool_path', '/usr/bin/exiftool');
            exec("{$exifToolPath} -ver 2>&1", $output);
            $this->exifToolVersion = $output[0] ?? 'Unknown';
        }

        // Get statistics
        $this->totalDigitalObjects = Illuminate\Database\Capsule\Manager::table('digital_object')
            ->whereNotNull('path')
            ->count();

        $this->objectsWithMetadata = Illuminate\Database\Capsule\Manager::table('property')
            ->where('scope', 'metadata_extraction')
            ->distinct('object_id')
            ->count('object_id');

        $this->totalMetadataFields = Illuminate\Database\Capsule\Manager::table('property')
            ->where('scope', 'metadata_extraction')
            ->count();

        // Get supported mime types breakdown
        $this->mimeTypeBreakdown = Illuminate\Database\Capsule\Manager::table('digital_object')
            ->select('mime_type', Illuminate\Database\Capsule\Manager::raw('count(*) as count'))
            ->whereNotNull('mime_type')
            ->groupBy('mime_type')
            ->orderByDesc('count')
            ->limit(10)
            ->get();
    }

    /**
     * Delete existing metadata for digital object.
     */
    private function deleteExistingMetadata(int $digitalObjectId): void
    {
        $properties = Illuminate\Database\Capsule\Manager::table('property')
            ->where('object_id', $digitalObjectId)
            ->where('scope', 'metadata_extraction')
            ->get();

        foreach ($properties as $property) {
            Illuminate\Database\Capsule\Manager::table('property_i18n')
                ->where('id', $property->id)
                ->delete();

            Illuminate\Database\Capsule\Manager::table('object')
                ->where('id', $property->id)
                ->delete();

            Illuminate\Database\Capsule\Manager::table('property')
                ->where('id', $property->id)
                ->delete();
        }
    }

    /**
     * Save a metadata property.
     */
    private function saveMetadataProperty(int $objectId, string $name, string $value): void
    {
        // Insert into property table
        Illuminate\Database\Capsule\Manager::table('property')->insert([
            'object_id' => $objectId,
            'name' => $name,
            'value' => $value,
            'scope' => 'metadata_extraction',
            'source_culture' => 'en',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        // Get the inserted ID
        $propertyId = Illuminate\Database\Capsule\Manager::getPdo()->lastInsertId();

        // Create object record
        Illuminate\Database\Capsule\Manager::table('object')->insert([
            'id' => $propertyId,
            'class_name' => 'QubitProperty',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        // Create i18n record
        Illuminate\Database\Capsule\Manager::table('property_i18n')->insert([
            'id' => $propertyId,
            'culture' => 'en',
        ]);
    }
}
