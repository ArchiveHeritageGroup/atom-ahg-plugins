<?php

class libraryEditAction extends sfAction
{
    public function execute($request)
    {
        $frameworkPath = sfConfig::get('sf_root_dir') . '/atom-framework';
        require_once $frameworkPath . '/bootstrap.php';

        $this->resource = null;
        $this->libraryData = [];
        $slug = $request->getParameter('slug');

        if ($slug) {
            // Editing existing resource
            $this->resource = QubitInformationObject::getBySlug($slug);
            if (!$this->resource) {
                $this->forward404();
            }
            
            // Check update permission
            if (!QubitAcl::check($this->resource, 'update')) {
                QubitAcl::forwardUnauthorized();
            }
            
            $this->loadLibraryData($this->resource->id);
        } else {
            // Creating new resource
            $this->resource = new QubitInformationObject();
            $this->itemLocation = [];
            
            // Check create permission on root
            if (!QubitAcl::check(QubitInformationObject::getRoot(), 'create')) {
                QubitAcl::forwardUnauthorized();
            }
        }

        if ($request->isMethod('post')) {
            $savedId = $this->processForm($request);

            $db = \Illuminate\Database\Capsule\Manager::connection();
            $slugRow = $db->table('slug')->where('object_id', $savedId)->first();

            if ($slugRow && $slugRow->slug) {
                $this->redirect(['module' => 'ahgLibraryPlugin', 'action' => 'index', 'slug' => $slugRow->slug]);
            } else {
                $this->redirect(['module' => 'ahgLibraryPlugin', 'action' => 'browse']);
            }
        }

        $this->loadFormOptions();
    }


    protected function loadLibraryData(int $informationObjectId): void
    {
        require_once sfConfig::get('sf_root_dir') . '/atom-framework/src/Repositories/LibraryItemRepository.php';

        $repo = new \AtomFramework\Repositories\LibraryItemRepository();
        $this->libraryData = $repo->getLibraryData($informationObjectId) ?? [];

        $libraryItemId = $repo->getLibraryItemId($informationObjectId);
        if ($libraryItemId) {
            $this->libraryData['creators'] = $repo->getCreators($libraryItemId);
            $this->libraryData['subjects'] = $repo->getSubjects($libraryItemId);
        } else {
            $this->libraryData['creators'] = [];
            $this->libraryData['subjects'] = [];
        }
        // Load item physical location
        require_once sfConfig::get('sf_root_dir') . '/atom-framework/src/Repositories/ItemPhysicalLocationRepository.php';
        $locRepo = new \AtomFramework\Repositories\ItemPhysicalLocationRepository();
        $this->itemLocation = $locRepo->getLocationWithContainer($informationObjectId) ?? [];
        }

    protected function processForm($request): int
    {
        require_once sfConfig::get('sf_root_dir') . '/atom-framework/src/Repositories/LibraryItemRepository.php';

        $repo = new \AtomFramework\Repositories\LibraryItemRepository();

        $isNew = !isset($this->resource->id);
        
        // Capture old values for audit trail
        $oldValues = [];
        if (!$isNew && $this->resource) {
            $oldValues = $this->captureCurrentValues($this->resource->id);
        }

        $this->resource->title = html_entity_decode($request->getParameter('title'), ENT_QUOTES, 'UTF-8');
        $this->resource->identifier = $request->getParameter('identifier');
        $this->resource->levelOfDescriptionId = $request->getParameter('level_of_description_id');
        
        // Set source_standard BEFORE save
        $this->resource->sourceStandard = 'library';

        $scopeAndContent = $request->getParameter('scope_and_content');
        if ($scopeAndContent) {
            $this->resource->setScopeAndContent($scopeAndContent);
        }

        if ($isNew) {
            $parentSlug = $request->getParameter('parent');
            if ($parentSlug) {
                $parent = QubitObject::getBySlug($parentSlug);
                $this->resource->parentId = $parent ? $parent->id : QubitInformationObject::ROOT_ID;
            } else {
                $this->resource->parentId = QubitInformationObject::ROOT_ID;
            }
        }

        // Set library display standard
        $db = \Illuminate\Database\Capsule\Manager::connection();
        $libraryTerm = $db->table('term')
            ->join('term_i18n', 'term.id', '=', 'term_i18n.id')
            ->where('term.taxonomy_id', QubitTaxonomy::INFORMATION_OBJECT_TEMPLATE_ID)
            ->where('term_i18n.name', 'Library (MARC-inspired)')
            ->where('term_i18n.culture', 'en')
            ->first();
        
        if ($libraryTerm) {
            $this->resource->displayStandardId = $libraryTerm->id;
        }

        // Save the information object
        $this->resource->save();
        $savedId = $this->resource->id;

        // Save library_item
        $libraryItemId = $this->saveLibraryItem($request, $isNew);

        // Save creators
        $creators = $request->getParameter('creators', []);
        if (is_array($creators)) {
            $repo->saveCreators($libraryItemId, $creators);
        }

        // Save subjects
        $subjects = $request->getParameter('subjects', []);
        if (is_array($subjects)) {
            $repo->saveSubjects($libraryItemId, $subjects);
        }
        // Save item physical location
        require_once sfConfig::get('sf_root_dir') . '/atom-framework/src/Repositories/ItemPhysicalLocationRepository.php';
        $locRepo = new \AtomFramework\Repositories\ItemPhysicalLocationRepository();
        $locationData = [
            'physical_object_id' => $request->getParameter('item_physical_object_id') ?: null,
            'barcode' => $request->getParameter('item_barcode'),
            'box_number' => $request->getParameter('item_box_number'),
            'folder_number' => $request->getParameter('item_folder_number'),
            'shelf' => $request->getParameter('item_shelf'),
            'row' => $request->getParameter('item_row'),
            'position' => $request->getParameter('item_position'),
            'item_number' => $request->getParameter('item_item_number'),
            'extent_value' => $request->getParameter('item_extent_value') ?: null,
            'extent_unit' => $request->getParameter('item_extent_unit'),
            'condition_status' => $request->getParameter('item_condition_status') ?: null,
            'condition_notes' => $request->getParameter('item_condition_notes'),
            'access_status' => $request->getParameter('item_access_status') ?: 'available',
            'notes' => $request->getParameter('item_location_notes'),
        ];
        // Only save if any location data provided
        if (array_filter($locationData)) {
            $locRepo->saveLocationData($savedId, $locationData);
        }

        // Capture new values and log audit trail
        $newValues = $this->captureCurrentValues($savedId);
        $this->logAudit($isNew ? 'create' : 'update', $savedId, $oldValues, $newValues);

        return $savedId;
    }

    protected function saveLibraryItem($request, $isNew = false): int
    {
        $db = \Illuminate\Database\Capsule\Manager::connection();

        // Check if digital object already exists
        $hasDigitalObject = $this->resource->getDigitalObject() !== null;

        // Get ISBN for Open Library cover lookup
        $isbn = $request->getParameter('isbn');
        // If no digital object and we have ISBN
        if (!$hasDigitalObject && !empty($isbn)) {
            if ($isNew) {
                // Queue for background processing (avoids transaction issues on first save)
                $db->table('atom_library_cover_queue')->updateOrInsert(
                    ['information_object_id' => $this->resource->id],
                    [
                        'isbn' => $isbn,
                        'status' => 'pending',
                        'attempts' => 0,
                        'error_message' => null,
                        'created_at' => date('Y-m-d H:i:s'),
                        'processed_at' => null,
                    ]
                );
            } else {
                // For existing records, download immediately
                $this->downloadCoverNow($isbn);
            }
        }

        $data = [
            'information_object_id' => $this->resource->id,
            'material_type' => $request->getParameter('material_type') ?: 'monograph',
            'subtitle' => $request->getParameter('subtitle'),
            'responsibility_statement' => $request->getParameter('responsibility_statement'),
            'isbn' => $isbn,
            'issn' => $request->getParameter('issn'),
            'doi' => $request->getParameter('doi'),
            'lccn' => $request->getParameter('lccn'),
            'oclc_number' => $request->getParameter('oclc_number'),
            'openlibrary_id' => $request->getParameter('openlibrary_id'),
            'goodreads_id' => $request->getParameter('goodreads_id'),
            'librarything_id' => $request->getParameter('librarything_id'),
            'openlibrary_url' => $request->getParameter('openlibrary_url'),
            'ebook_preview_url' => $request->getParameter('ebook_preview_url'),
            'barcode' => $request->getParameter('barcode'),
            'call_number' => $request->getParameter('call_number'),
            'classification_scheme' => $request->getParameter('classification_scheme'),
            'dewey_decimal' => $request->getParameter('dewey_decimal'),
            'shelf_location' => $request->getParameter('shelf_location'),
            'copy_number' => $request->getParameter('copy_number'),
            'volume_designation' => $request->getParameter('volume_designation'),
            'publisher' => $request->getParameter('publisher'),
            'publication_place' => $request->getParameter('publication_place'),
            'publication_date' => $request->getParameter('publication_date'),
            'edition' => $request->getParameter('edition'),
            'edition_statement' => $request->getParameter('edition_statement'),
            'series_title' => $request->getParameter('series_title'),
            'series_number' => $request->getParameter('series_number'),
            'pagination' => $request->getParameter('pagination'),
            'dimensions' => $request->getParameter('dimensions'),
            'physical_details' => $request->getParameter('physical_details'),
            'language' => $request->getParameter('language'),
            'summary' => $request->getParameter('summary'),
            'contents_note' => $request->getParameter('contents_note'),
            'general_note' => $request->getParameter('general_note'),
            'bibliography_note' => $request->getParameter('bibliography_note'),
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        $existing = $db->table('library_item')
            ->where('information_object_id', $this->resource->id)
            ->first();

        if ($existing) {
            $db->table('library_item')->where('id', $existing->id)->update($data);
            return (int) $existing->id;
        } else {
            $data['created_at'] = date('Y-m-d H:i:s');
            return (int) $db->table('library_item')->insertGetId($data);
        }
    }

    protected function loadFormOptions(): void
    {
        $db = \Illuminate\Database\Capsule\Manager::connection();
        $culture = sfContext::getInstance()->user->getCulture() ?? 'en';

        $levels = $db->table('level_of_description_sector as los')
            ->join('term', 'los.term_id', '=', 'term.id')
            ->join('term_i18n', function($j) use ($culture) {
                $j->on('term.id', '=', 'term_i18n.id')->where('term_i18n.culture', '=', $culture);
            })
            ->where('los.sector', 'library')
            ->orderBy('los.display_order')
            ->select('term.id', 'term_i18n.name')
            ->get();

        $this->levelOptions = [];
        foreach ($levels as $level) {
            $this->levelOptions[$level->id] = $level->name;
        }

        $this->materialTypes = [
            'monograph' => 'Monograph', 'book' => 'Book', 'ebook' => 'E-Book',
            'journal' => 'Journal', 'magazine' => 'Magazine', 'newspaper' => 'Newspaper',
            'thesis' => 'Thesis/Dissertation', 'report' => 'Report',
            'conference' => 'Conference Proceedings', 'manuscript' => 'Manuscript',
            'map' => 'Map', 'music_score' => 'Music Score', 'audio' => 'Audio Recording',
            'video' => 'Video Recording', 'microform' => 'Microform',
            'electronic' => 'Electronic Resource', 'kit' => 'Kit', 'other' => 'Other',
        ];

        $this->classificationSchemes = [
            'lcc' => 'Library of Congress (LCC)', 'ddc' => 'Dewey Decimal (DDC)',
            'udc' => 'Universal Decimal (UDC)', 'nlm' => 'National Library of Medicine (NLM)',
            'sudocs' => 'SuDocs', 'local' => 'Local Scheme', 'other' => 'Other',
        ];

        // Languages from AtoM's sfCultureInfo
        $cultureInfo = sfCultureInfo::getInstance($culture);
        $this->languageOptions = $cultureInfo->getLanguages();
        asort($this->languageOptions);
    }

    protected function syncDisplayConfig(int $objectId): void
    {
        try {
            // Use a separate PDO connection to avoid transaction conflicts
            $config = include(sfConfig::get('sf_root_dir') . '/config/config.php');
            $dsn = $config['all']['propel']['param']['dsn'];
            $user = $config['all']['propel']['param']['username'];
            $pass = $config['all']['propel']['param']['password'];
            
            $pdo = new \PDO($dsn, $user, $pass);
            $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            
            // Check if exists
            $stmt = $pdo->prepare("SELECT id FROM display_object_config WHERE object_id = ?");
            $stmt->execute([$objectId]);
            
            if (!$stmt->fetch()) {
                // Insert new
                $stmt = $pdo->prepare("INSERT INTO display_object_config (object_id, object_type, created_at, updated_at) VALUES (?, 'library', NOW(), NOW())");
                $stmt->execute([$objectId]);
            } else {
                // Update existing
                $stmt = $pdo->prepare("UPDATE display_object_config SET object_type = 'library', updated_at = NOW() WHERE object_id = ?");
                $stmt->execute([$objectId]);
            }
        } catch (\Exception $e) {
            // Log error but don't fail the save
            error_log("Failed to sync display_object_config for object $objectId: " . $e->getMessage());
        }
    }

    /**
     * Capture current values for audit trail
     */
    protected function captureCurrentValues(int $resourceId): array
    {
        try {
            $db = \Illuminate\Database\Capsule\Manager::connection();
            $culture = $this->getUser()->getCulture() ?? 'en';
            
            $io = $db->table('information_object as io')
                ->leftJoin('information_object_i18n as ioi', function ($join) use ($culture) {
                    $join->on('io.id', '=', 'ioi.id')->where('ioi.culture', '=', $culture);
                })
                ->leftJoin('information_object_i18n as ioi_en', function ($join) {
                    $join->on('io.id', '=', 'ioi_en.id')->where('ioi_en.culture', '=', 'en');
                })
                ->where('io.id', $resourceId)
                ->select([
                    'io.identifier',
                    $db->raw('COALESCE(ioi.title, ioi_en.title) as title'),
                    $db->raw('COALESCE(ioi.scope_and_content, ioi_en.scope_and_content) as scope_and_content'),
                ])
                ->first();
            
            $libraryItem = $db->table('library_item')
                ->where('information_object_id', $resourceId)
                ->first();
            
            $values = [];
            if ($io) {
                if ($io->identifier) $values['identifier'] = $io->identifier;
                if ($io->title) $values['title'] = $io->title;
                if ($io->scope_and_content) $values['scope_and_content'] = $io->scope_and_content;
            }
            
            if ($libraryItem) {
                $libraryFields = ['material_type', 'subtitle', 'isbn', 'issn', 'publisher', 
                    'publication_place', 'publication_date', 'edition', 'call_number', 
                    'series_title', 'language', 'summary'];
                foreach ($libraryFields as $field) {
                    if (!empty($libraryItem->$field)) {
                        $values[$field] = $libraryItem->$field;
                    }
                }
            }
            
            return $values;
        } catch (\Exception $e) {
            error_log("Library AUDIT CAPTURE ERROR: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Log audit trail entry via AhgAuditService
     */
    protected function logAudit(string $action, int $resourceId, array $oldValues, array $newValues): void
    {
        try {
            $auditServicePath = sfConfig::get('sf_root_dir') . '/plugins/ahgAuditTrailPlugin/lib/Services/AhgAuditService.php';
            if (file_exists($auditServicePath)) {
                require_once $auditServicePath;
            }

            if (class_exists('AhgAuditTrail\\Services\\AhgAuditService')) {
                $changedFields = [];
                foreach ($newValues as $key => $val) {
                    if (($oldValues[$key] ?? null) !== $val) {
                        $changedFields[] = $key;
                    }
                }
                if ($action === 'delete') {
                    $changedFields = array_keys($oldValues);
                }

                \AhgAuditTrail\Services\AhgAuditService::logAction(
                    $action,
                    'LibraryItem',
                    $resourceId,
                    [
                        'title' => $newValues['title'] ?? $oldValues['title'] ?? null,
                        'slug' => $this->resource->slug ?? null,
                        'module' => 'ahgLibraryPlugin',
                        'action_name' => 'edit',
                        'old_values' => $oldValues,
                        'new_values' => $newValues,
                        'changed_fields' => $changedFields,
                    ]
                );
            }
        } catch (\Exception $e) {
            error_log("Library AUDIT ERROR: " . $e->getMessage());
        }
    }


    protected function downloadCoverNow($isbn)
    {
        $cleanIsbn = preg_replace('/[^0-9X]/i', '', $isbn);
        if (empty($cleanIsbn)) return;

        $coverUrl = "https://covers.openlibrary.org/b/isbn/{$cleanIsbn}-L.jpg";

        $ch = curl_init($coverUrl);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_USERAGENT => 'AtoM/2.10 (Library Cover Fetcher)',
        ]);

        $imageData = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        // Check if we got a valid image
        if ($httpCode !== 200 || strlen($imageData) < 1000) {
            return;
        }

        // Save temp file and create digital object
        $tmpFile = tempnam(sys_get_temp_dir(), 'cover_') . '.jpg';
        file_put_contents($tmpFile, $imageData);

        try {
            QubitSearch::disable();
            $do = new QubitDigitalObject();
            $do->objectId = $this->resource->id;
            $do->usageId = QubitTerm::MASTER_ID;
            $do->mediaTypeId = QubitTerm::IMAGE_ID;
            $do->assets[] = new QubitAsset($tmpFile);
            $do->save();
            QubitSearch::enable();

            // Update library_item with cover URL
            $db = \Illuminate\Database\Capsule\Manager::connection();
            $db->table('library_item')
                ->where('information_object_id', $this->resource->id)
                ->update(['cover_url' => $coverUrl, 'cover_url_original' => $coverUrl]);
        } catch (Exception $e) {
            error_log('Cover download error: ' . $e->getMessage());
        } finally {
            @unlink($tmpFile);
        }
    }
}
