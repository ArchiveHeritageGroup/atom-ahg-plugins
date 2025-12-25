<?php
use AtomExtensions\Services\AclService;

use Illuminate\Database\Capsule\Manager as DB;

/**
 * Import Archival Package Action
 * Import exported archival packages (ZIP files) including metadata and digital objects
 * Pure Laravel Query Builder implementation.
 * 
 * @author Johan Pieterse <johan@theahg.co.za>
 */
class importArchivalPackageAction extends sfAction
{
    protected $tempDir;
    protected $importStats;

    // Taxonomy IDs
    protected const TAXONOMY_LEVEL_OF_DESCRIPTION = 34;

    // Root IDs
    protected const ROOT_INFORMATION_OBJECT_ID = 1;

    public function execute($request)
    {
        // Check permissions
        if (!$this->context->user->isAdministrator()) {
            AclService::forwardUnauthorized();
        }

        $this->form = new sfForm();
        $this->importStats = null;
        $this->error = null;

        if ($request->isMethod('post')) {
            return $this->processImport($request);
        }
    }

    /**
     * Process uploaded package
     */
    protected function processImport($request)
    {
        // Validate upload
        if (!isset($_FILES['package']) || $_FILES['package']['error'] !== UPLOAD_ERR_OK) {
            $this->error = 'File upload failed. Please try again.';
            return sfView::SUCCESS;
        }

        $uploadedFile = $_FILES['package'];
        
        // Validate file type
        $mimeType = mime_content_type($uploadedFile['tmp_name']);
        if ($mimeType !== 'application/zip' && $mimeType !== 'application/x-zip-compressed') {
            $this->error = 'Invalid file type. Please upload a ZIP archive.';
            return sfView::SUCCESS;
        }

        try {
            // Create temp directory
            $this->tempDir = sfConfig::get('sf_cache_dir') . '/import_' . uniqid();
            mkdir($this->tempDir, 0755, true);

            // Extract package
            $this->extractPackage($uploadedFile['tmp_name']);

            // Validate manifest
            $manifest = $this->validateManifest();

            // Import options
            $options = [
                'updateExisting' => $request->getParameter('updateExisting', false),
                'importDigitalObjects' => $request->getParameter('importDigitalObjects', true),
                'parentId' => $request->getParameter('parentId'),
                'repositoryId' => $request->getParameter('repositoryId'),
            ];

            // Process import based on format
            $this->importStats = $this->processPackageContents($manifest, $options);

            // Cleanup
            $this->removeDirectory($this->tempDir);

            $this->getUser()->setFlash('notice', sprintf(
                'Import completed: %d descriptions imported, %d digital objects processed.',
                $this->importStats['descriptions'],
                $this->importStats['digitalObjects']
            ));

        } catch (Exception $e) {
            $this->error = 'Import failed: ' . $e->getMessage();
            
            // Cleanup on error
            if (isset($this->tempDir) && is_dir($this->tempDir)) {
                $this->removeDirectory($this->tempDir);
            }
        }

        return sfView::SUCCESS;
    }

    /**
     * Extract ZIP package
     */
    protected function extractPackage($zipPath)
    {
        $zip = new ZipArchive();
        
        if ($zip->open($zipPath) !== true) {
            throw new Exception('Could not open ZIP archive');
        }

        $zip->extractTo($this->tempDir);
        $zip->close();
    }

    /**
     * Validate package manifest
     */
    protected function validateManifest()
    {
        $manifestPath = $this->tempDir . '/manifest.json';
        
        if (!file_exists($manifestPath)) {
            throw new Exception('Package manifest not found. This may not be a valid AHG export package.');
        }

        $manifest = json_decode(file_get_contents($manifestPath), true);

        if (!$manifest || !isset($manifest['version'])) {
            throw new Exception('Invalid manifest format');
        }

        // Verify file checksums
        foreach ($manifest['files'] as $file) {
            $filePath = $this->tempDir . '/' . $file['path'];
            
            if (!file_exists($filePath)) {
                throw new Exception('Missing file: ' . $file['path']);
            }

            if (md5_file($filePath) !== $file['checksum']) {
                throw new Exception('Checksum mismatch for: ' . $file['path']);
            }
        }

        return $manifest;
    }

    /**
     * Process package contents
     */
    protected function processPackageContents($manifest, $options)
    {
        $stats = [
            'descriptions' => 0,
            'digitalObjects' => 0,
            'errors' => 0,
            'skipped' => 0,
        ];

        // Look for CSV file first (primary import method)
        $csvPath = $this->tempDir . '/metadata/descriptions.csv';
        
        if (file_exists($csvPath)) {
            $csvStats = $this->importFromCSV($csvPath, $options);
            $stats['descriptions'] += $csvStats['imported'];
            $stats['errors'] += $csvStats['errors'];
            $stats['skipped'] += $csvStats['skipped'];
        }

        // Import digital objects if requested
        if ($options['importDigitalObjects']) {
            $objectsDir = $this->tempDir . '/objects';
            
            if (is_dir($objectsDir)) {
                $doStats = $this->importDigitalObjects($objectsDir);
                $stats['digitalObjects'] = $doStats['imported'];
            }
        }

        return $stats;
    }

    /**
     * Import from CSV file
     */
    protected function importFromCSV($csvPath, $options)
    {
        $stats = ['imported' => 0, 'errors' => 0, 'skipped' => 0];

        $handle = fopen($csvPath, 'r');
        
        // Skip BOM if present
        $bom = fread($handle, 3);
        if ($bom !== "\xEF\xBB\xBF") {
            rewind($handle);
        }

        // Read header
        $header = fgetcsv($handle);
        
        if (!$header) {
            throw new Exception('Empty or invalid CSV file');
        }

        // Process rows
        $idMap = []; // Map legacy IDs to new IDs
        $rowNum = 1;

        while (($row = fgetcsv($handle)) !== false) {
            $rowNum++;

            try {
                $data = array_combine($header, $row);
                
                // Determine parent
                $parentId = null;
                if (!empty($data['parentId']) && isset($idMap[$data['parentId']])) {
                    $parentId = $idMap[$data['parentId']];
                } elseif (!empty($data['qubitParentSlug'])) {
                    $parentId = $this->getInformationObjectIdBySlug($data['qubitParentSlug']);
                }

                if (!$parentId) {
                    $parentId = $options['parentId'] ?: self::ROOT_INFORMATION_OBJECT_ID;
                }

                // Check if record exists (for update)
                $existingId = null;
                if (!empty($data['identifier']) && $options['updateExisting']) {
                    $existingId = $this->findExistingRecordId($data);
                }

                if ($existingId && !$options['updateExisting']) {
                    $stats['skipped']++;
                    continue;
                }

                // Create or update record
                $ioId = $this->saveInformationObject($existingId, $parentId, $data, $options);

                // Map legacy ID to new ID
                if (!empty($data['legacyId'])) {
                    $idMap[$data['legacyId']] = $ioId;
                }

                $stats['imported']++;

            } catch (Exception $e) {
                $stats['errors']++;
                error_log("Import error at row $rowNum: " . $e->getMessage());
            }
        }

        fclose($handle);

        return $stats;
    }

    /**
     * Save information object (create or update)
     */
    protected function saveInformationObject(?int $existingId, int $parentId, array $data, array $options): int
    {
        $culture = !empty($data['culture']) ? $data['culture'] : 'en';
        
        if ($existingId) {
            // Update existing
            $ioId = $existingId;
            
            $updateData = ['parent_id' => $parentId];
            
            if (!empty($data['identifier'])) {
                $updateData['identifier'] = $data['identifier'];
            }
            
            // Level of description
            if (!empty($data['levelOfDescription'])) {
                $lodId = $this->findOrCreateTermId($data['levelOfDescription'], self::TAXONOMY_LEVEL_OF_DESCRIPTION);
                if ($lodId) {
                    $updateData['level_of_description_id'] = $lodId;
                }
            }
            
            // Repository
            if (!empty($data['repository'])) {
                $repoId = $this->findRepositoryId($data['repository']);
                if ($repoId) {
                    $updateData['repository_id'] = $repoId;
                }
            } elseif ($options['repositoryId']) {
                $updateData['repository_id'] = $options['repositoryId'];
            }
            
            DB::table('information_object')
                ->where('id', $ioId)
                ->update($updateData);
            
            DB::table('object')
                ->where('id', $ioId)
                ->update(['updated_at' => date('Y-m-d H:i:s')]);
            
        } else {
            // Create new object record
            $ioId = DB::table('object')->insertGetId([
                'class_name' => 'QubitInformationObject',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
            
            // Build information_object data
            $ioData = [
                'id' => $ioId,
                'parent_id' => $parentId,
                'source_culture' => $culture,
            ];
            
            if (!empty($data['identifier'])) {
                $ioData['identifier'] = $data['identifier'];
            }
            
            // Level of description
            if (!empty($data['levelOfDescription'])) {
                $lodId = $this->findOrCreateTermId($data['levelOfDescription'], self::TAXONOMY_LEVEL_OF_DESCRIPTION);
                if ($lodId) {
                    $ioData['level_of_description_id'] = $lodId;
                }
            }
            
            // Repository
            if (!empty($data['repository'])) {
                $repoId = $this->findRepositoryId($data['repository']);
                if ($repoId) {
                    $ioData['repository_id'] = $repoId;
                }
            } elseif ($options['repositoryId']) {
                $ioData['repository_id'] = $options['repositoryId'];
            }
            
            DB::table('information_object')->insert($ioData);
            
            // Generate slug
            $slug = $this->generateSlug($data['title'] ?? $data['identifier'] ?? 'record-' . $ioId);
            DB::table('slug')->insert([
                'object_id' => $ioId,
                'slug' => $slug,
            ]);
        }
        
        // Update or insert i18n data
        $i18nData = $this->buildI18nData($data);
        
        $existingI18n = DB::table('information_object_i18n')
            ->where('id', $ioId)
            ->where('culture', $culture)
            ->exists();
        
        if ($existingI18n) {
            DB::table('information_object_i18n')
                ->where('id', $ioId)
                ->where('culture', $culture)
                ->update($i18nData);
        } else {
            $i18nData['id'] = $ioId;
            $i18nData['culture'] = $culture;
            DB::table('information_object_i18n')->insert($i18nData);
        }
        
        return $ioId;
    }

    /**
     * Build i18n data from CSV row
     */
    protected function buildI18nData(array $data): array
    {
        $i18nData = [];
        
        $fieldMap = [
            'title' => 'title',
            'extentAndMedium' => 'extent_and_medium',
            'scopeAndContent' => 'scope_and_content',
            'archivalHistory' => 'archival_history',
            'acquisition' => 'acquisition',
            'appraisal' => 'appraisal',
            'accruals' => 'accruals',
            'arrangement' => 'arrangement',
            'accessConditions' => 'access_conditions',
            'reproductionConditions' => 'reproduction_conditions',
            'physicalCharacteristics' => 'physical_characteristics',
            'findingAids' => 'finding_aids',
            'locationOfOriginals' => 'location_of_originals',
            'locationOfCopies' => 'location_of_copies',
            'relatedUnitsOfDescription' => 'related_units_of_description',
            'publicationNote' => 'publication_note',
        ];
        
        foreach ($fieldMap as $csvField => $dbField) {
            if (!empty($data[$csvField])) {
                $i18nData[$dbField] = $data[$csvField];
            }
        }
        
        return $i18nData;
    }

    /**
     * Import digital objects
     */
    protected function importDigitalObjects($objectsDir)
    {
        $stats = ['imported' => 0, 'errors' => 0];

        $files = scandir($objectsDir);
        
        foreach ($files as $file) {
            if ($file === '.' || $file === '..') continue;

            $filePath = $objectsDir . '/' . $file;
            
            // Parse filename to get record ID (format: {id}_{originalname})
            if (preg_match('/^(\d+)_/', $file, $matches)) {
                $recordId = (int) $matches[1];
                
                // Check if information object exists
                $ioExists = DB::table('information_object')
                    ->where('id', $recordId)
                    ->exists();
                
                if ($ioExists) {
                    try {
                        $this->createDigitalObject($recordId, $filePath, $file);
                        $stats['imported']++;
                    } catch (Exception $e) {
                        $stats['errors']++;
                        error_log("Error importing digital object for record $recordId: " . $e->getMessage());
                    }
                }
            }
        }

        return $stats;
    }

    /**
     * Create digital object
     */
    protected function createDigitalObject(int $informationObjectId, string $filePath, string $filename): int
    {
        $content = file_get_contents($filePath);
        $mimeType = mime_content_type($filePath);
        
        // Create object record
        $doId = DB::table('object')->insertGetId([
            'class_name' => 'QubitDigitalObject',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        
        // Generate storage path
        $storagePath = $this->generateStoragePath($doId);
        $uploadDir = sfConfig::get('sf_upload_dir');
        $fullPath = $uploadDir . '/' . $storagePath;
        
        if (!is_dir($fullPath)) {
            mkdir($fullPath, 0755, true);
        }
        
        // Store file
        $storedFilename = $doId . '_' . preg_replace('/[^a-z0-9_\.-]/i', '_', $filename);
        file_put_contents($fullPath . '/' . $storedFilename, $content);
        
        // Create digital object record
        DB::table('digital_object')->insert([
            'id' => $doId,
            'object_id' => $informationObjectId,
            'usage_id' => 140, // MASTER
            'mime_type' => $mimeType,
            'media_type_id' => $this->getMediaTypeFromMime($mimeType),
            'name' => $filename,
            'path' => $storagePath . '/' . $storedFilename,
            'byte_size' => strlen($content),
            'checksum' => md5($content),
            'checksum_type' => 'md5',
        ]);
        
        // Generate slug
        $slug = $this->generateSlug('do-' . $doId);
        DB::table('slug')->insert([
            'object_id' => $doId,
            'slug' => $slug,
        ]);
        
        return $doId;
    }

    /**
     * Find existing record by identifier
     */
    protected function findExistingRecordId($data): ?int
    {
        if (!empty($data['identifier'])) {
            return DB::table('information_object')
                ->where('identifier', $data['identifier'])
                ->value('id');
        }

        return null;
    }

    /**
     * Get information object ID by slug
     */
    protected function getInformationObjectIdBySlug(string $slug): ?int
    {
        return DB::table('slug')
            ->join('object', 'slug.object_id', '=', 'object.id')
            ->where('slug.slug', $slug)
            ->where('object.class_name', 'QubitInformationObject')
            ->value('slug.object_id');
    }

    /**
     * Find or create term
     */
    protected function findOrCreateTermId(string $name, int $taxonomyId): ?int
    {
        // Find existing
        $termId = DB::table('term')
            ->join('term_i18n', 'term.id', '=', 'term_i18n.id')
            ->where('term.taxonomy_id', $taxonomyId)
            ->where('term_i18n.name', $name)
            ->where('term_i18n.culture', 'en')
            ->value('term.id');
        
        if ($termId) {
            return $termId;
        }
        
        // Create new term
        $termId = DB::table('object')->insertGetId([
            'class_name' => 'QubitTerm',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        
        // Get root term for taxonomy
        $rootTermId = DB::table('term')
            ->where('taxonomy_id', $taxonomyId)
            ->whereNull('parent_id')
            ->value('id') ?? $termId;
        
        DB::table('term')->insert([
            'id' => $termId,
            'taxonomy_id' => $taxonomyId,
            'parent_id' => $rootTermId,
        ]);
        
        DB::table('term_i18n')->insert([
            'id' => $termId,
            'culture' => 'en',
            'name' => $name,
        ]);
        
        return $termId;
    }

    /**
     * Find repository by name
     */
    protected function findRepositoryId(string $name): ?int
    {
        return DB::table('actor')
            ->join('object', 'actor.id', '=', 'object.id')
            ->join('actor_i18n', 'actor.id', '=', 'actor_i18n.id')
            ->where('object.class_name', 'QubitRepository')
            ->where('actor_i18n.authorized_form_of_name', $name)
            ->where('actor_i18n.culture', 'en')
            ->value('actor.id');
    }

    /**
     * Generate storage path
     */
    protected function generateStoragePath(int $id): string
    {
        $parts = str_split(str_pad((string) $id, 9, '0', STR_PAD_LEFT), 3);
        return implode('/', $parts);
    }

    /**
     * Generate unique slug
     */
    protected function generateSlug(string $text): string
    {
        $slug = strtolower(trim(preg_replace('/[^a-zA-Z0-9]+/', '-', $text), '-'));
        
        if (empty($slug)) {
            $slug = 'record';
        }
        
        $baseSlug = $slug;
        $counter = 1;
        
        while (DB::table('slug')->where('slug', $slug)->exists()) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }
        
        return $slug;
    }

    /**
     * Get media type ID from MIME type
     */
    protected function getMediaTypeFromMime(string $mimeType): int
    {
        if (strpos($mimeType, 'image/') === 0) {
            return 136; // Image
        }
        if (strpos($mimeType, 'audio/') === 0) {
            return 135; // Audio
        }
        if (strpos($mimeType, 'video/') === 0) {
            return 138; // Video
        }
        if (strpos($mimeType, 'text/') === 0 || $mimeType === 'application/pdf') {
            return 137; // Text
        }
        return 139; // Other
    }

    /**
     * Remove directory recursively
     */
    protected function removeDirectory($dir)
    {
        if (is_dir($dir)) {
            $objects = scandir($dir);
            foreach ($objects as $object) {
                if ($object != '.' && $object != '..') {
                    if (is_dir($dir . '/' . $object)) {
                        $this->removeDirectory($dir . '/' . $object);
                    } else {
                        unlink($dir . '/' . $object);
                    }
                }
            }
            rmdir($dir);
        }
    }
}