<?php

class ahgLibraryPluginEditAction extends sfAction
{
    public function execute($request)
    {
        $frameworkPath = sfConfig::get('sf_root_dir') . '/atom-framework';
        require_once $frameworkPath . '/bootstrap.php';

        $this->resource = null;
        $this->libraryData = [];
        $slug = $request->getParameter('slug');

        if ($slug) {
            $this->resource = QubitInformationObject::getBySlug($slug);
            if (!$this->resource) {
                $this->forward404();
            }
            $this->loadLibraryData($this->resource->id);
        } else {
            $this->resource = new QubitInformationObject();
            $this->itemLocation = [];
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

        $this->resource->title = $request->getParameter('title');
        $this->resource->identifier = $request->getParameter('identifier');
        $this->resource->levelOfDescriptionId = $request->getParameter('level_of_description_id');
        
        // Set source_standard BEFORE save
        $this->resource->sourceStandard = 'library';

        $scopeAndContent = $request->getParameter('scope_and_content');
        if ($scopeAndContent) {
            $this->resource->setScopeAndContent($scopeAndContent);
        }

        if ($isNew) {
            $this->resource->parentId = QubitInformationObject::ROOT_ID;
        }

        // Save the information object
        $this->resource->save();
        $savedId = $this->resource->id;

        // Save library_item
        $libraryItemId = $this->saveLibraryItem($request);

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

        return $savedId;
    }

    protected function saveLibraryItem($request): int
    {
        $db = \Illuminate\Database\Capsule\Manager::connection();

        // Check if digital object already exists
        $hasDigitalObject = $this->resource->getDigitalObject() !== null;

        // Get ISBN for Open Library cover lookup
        $isbn = $request->getParameter('isbn');
        // If no digital object and we have ISBN, queue cover download for async processing
        if (!$hasDigitalObject && !empty($isbn)) {
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
}
