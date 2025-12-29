<?php
/**
 * Archival Export Service
 * 
 * Exports archival descriptions to CSV (ISAD-G), EAD, Dublin Core formats.
 * Uses Laravel Illuminate Database 
 *
 * @author Johan Pieterse <johan@theahg.co.za>
 * @package ahgThemeB5Plugin
 */

use Illuminate\Database\Capsule\Manager as DB;

class arArchivalExportService
{
    const FORMAT_CSV = 'csv';
    const FORMAT_EAD = 'ead';
    const FORMAT_EAD3 = 'ead3';
    const FORMAT_DC = 'dc';
    const FORMAT_MODS = 'mods';

    const ROOT_ID = 1;

    protected $format = self::FORMAT_CSV;
    protected $includeDescendants = true;
    protected $includeDigitalObjects = false;
    protected $createPackage = false;
    protected $repositoryId = null;
    protected $culture = 'en';

    // ISAD-G CSV columns
    protected static $csvColumns = [
        'legacyId', 'parentId', 'qubitParentSlug', 'accessionNumber', 'identifier',
        'title', 'levelOfDescription', 'extentAndMedium', 'repository', 'archivalHistory',
        'acquisition', 'scopeAndContent', 'appraisal', 'accruals', 'arrangement',
        'accessConditions', 'reproductionConditions', 'language', 'script', 'languageNote',
        'physicalCharacteristics', 'findingAids', 'locationOfOriginals', 'locationOfCopies',
        'relatedUnitsOfDescription', 'publicationNote', 'digitalObjectPath', 'digitalObjectURI',
        'generalNote', 'subjectAccessPoints', 'placeAccessPoints', 'nameAccessPoints',
        'genreAccessPoints', 'descriptionIdentifier', 'institutionIdentifier', 'rules',
        'descriptionStatus', 'levelOfDetail', 'revisionHistory', 'languageOfDescription',
        'scriptOfDescription', 'sources', 'archivistNote', 'publicationStatus',
        'physicalObjectName', 'physicalObjectLocation', 'physicalObjectType',
        'alternativeIdentifiers', 'alternativeIdentifierLabels', 'eventDates', 'eventTypes',
        'eventStartDates', 'eventEndDates', 'eventActors', 'eventActorHistories', 'culture'
    ];

    public function setFormat($format)
    {
        $this->format = $format;
        return $this;
    }

    public function setIncludeDescendants($include)
    {
        $this->includeDescendants = $include;
        return $this;
    }

    public function setIncludeDigitalObjects($include)
    {
        $this->includeDigitalObjects = $include;
        return $this;
    }

    public function setCreatePackage($create)
    {
        $this->createPackage = $create;
        return $this;
    }

    public function setRepositoryId($id)
    {
        $this->repositoryId = $id;
        return $this;
    }

    public function setCulture($culture)
    {
        $this->culture = $culture;
        return $this;
    }

    /**
     * Export by slug
     */
    public function exportBySlug($slug)
    {
        $io = $this->getObjectBySlug($slug);
        if (!$io) {
            return null;
        }
        return $this->export($io->id);
    }

    /**
     * Export by ID
     */
    public function export($objectId)
    {
        $objects = $this->getExportObjects($objectId);

        switch ($this->format) {
            case self::FORMAT_EAD:
            case self::FORMAT_EAD3:
                return $this->toEAD($objects);
            case self::FORMAT_DC:
                return $this->toDublinCore($objects);
            case self::FORMAT_MODS:
                return $this->toMODS($objects);
            case self::FORMAT_CSV:
            default:
                return $this->toCSV($objects);
        }
    }

    /**
     * Export entire repository
     */
    public function exportRepository($repositoryId = null)
    {
        $this->repositoryId = $repositoryId;
        $objects = $this->getAllObjects();

        switch ($this->format) {
            case self::FORMAT_EAD:
            case self::FORMAT_EAD3:
                return $this->toEAD($objects);
            case self::FORMAT_CSV:
            default:
                return $this->toCSV($objects);
        }
    }

    /**
     * Get object by slug
     */
    protected function getObjectBySlug($slug)
    {
        return DB::table('slug')
            ->join('information_object', 'slug.object_id', '=', 'information_object.id')
            ->where('slug.slug', $slug)
            ->select('information_object.*', 'slug.slug')
            ->first();
    }

    /**
     * Get objects for export (with optional descendants)
     */
    protected function getExportObjects($objectId)
    {
        $objects = [];

        // Get the main object
        $mainObj = $this->getFullObjectData($objectId);
        if ($mainObj) {
            $objects[] = $mainObj;
        }

        // Get descendants if requested
        if ($this->includeDescendants) {
            $descendants = $this->getDescendants($objectId);
            foreach ($descendants as $desc) {
                $objects[] = $this->getFullObjectData($desc->id);
            }
        }

        return $objects;
    }

    /**
     * Get all objects (optionally filtered by repository)
     */
    protected function getAllObjects()
    {
        $query = DB::table('information_object')
            ->where('information_object.id', '!=', self::ROOT_ID);

        if ($this->repositoryId) {
            $query->where('information_object.repository_id', $this->repositoryId);
        }

        $objects = [];
        foreach ($query->get() as $row) {
            $objects[] = $this->getFullObjectData($row->id);
        }

        return $objects;
    }

    /**
     * Get descendants of an object
     */
    protected function getDescendants($parentId)
    {
        $descendants = [];
        
        $children = DB::table('information_object')
            ->where('parent_id', $parentId)
            ->orderBy('lft')
            ->get();

        foreach ($children as $child) {
            $descendants[] = $child;
            // Recursively get descendants
            $descendants = array_merge($descendants, $this->getDescendants($child->id));
        }

        return $descendants;
    }

    /**
     * Get full object data with all related info
     */
    protected function getFullObjectData($id)
    {
        // Main object
        $io = DB::table('information_object')
            ->where('id', $id)
            ->first();

        if (!$io) {
            return null;
        }

        // I18n data
        $i18n = DB::table('information_object_i18n')
            ->where('id', $id)
            ->where('culture', $this->culture)
            ->first();

        if (!$i18n) {
            $i18n = DB::table('information_object_i18n')
                ->where('id', $id)
                ->first();
        }

        // Slug
        $slug = DB::table('slug')
            ->where('object_id', $id)
            ->first();

        // Parent slug
        $parentSlug = null;
        if ($io->parent_id && $io->parent_id != self::ROOT_ID) {
            $ps = DB::table('slug')
                ->where('object_id', $io->parent_id)
                ->first();
            $parentSlug = $ps ? $ps->slug : null;
        }

        // Level of description
        $level = DB::table('term_i18n')
            ->where('id', $io->level_of_description_id)
            ->where('culture', $this->culture)
            ->first();

        // Repository
        $repository = null;
        if ($io->repository_id) {
            $repository = DB::table('actor_i18n')
                ->where('id', $io->repository_id)
                ->where('culture', $this->culture)
                ->first();
        }

        // Events (dates, creators)
        $events = DB::table('event')
            ->leftJoin('event_i18n', 'event.id', '=', 'event_i18n.id')
            ->leftJoin('actor_i18n', 'event.actor_id', '=', 'actor_i18n.id')
            ->leftJoin('term_i18n', 'event.type_id', '=', 'term_i18n.id')
            ->where('event.object_id', $id)
            ->select('event.*', 'event_i18n.date', 'event_i18n.name as event_name',
                     'actor_i18n.authorized_form_of_name as actor_name',
                     'term_i18n.name as type_name')
            ->get();

        // Subject access points
        $subjects = DB::table('object_term_relation')
            ->join('term_i18n', 'object_term_relation.term_id', '=', 'term_i18n.id')
            ->join('term', 'object_term_relation.term_id', '=', 'term.id')
            ->where('object_term_relation.object_id', $id)
            ->where('term.taxonomy_id', 35) // Subject taxonomy
            ->pluck('term_i18n.name')
            ->toArray();

        // Place access points
        $places = DB::table('object_term_relation')
            ->join('term_i18n', 'object_term_relation.term_id', '=', 'term_i18n.id')
            ->join('term', 'object_term_relation.term_id', '=', 'term.id')
            ->where('object_term_relation.object_id', $id)
            ->where('term.taxonomy_id', 42) // Place taxonomy
            ->pluck('term_i18n.name')
            ->toArray();

        // Name access points
        $names = DB::table('relation')
            ->join('actor_i18n', 'relation.object_id', '=', 'actor_i18n.id')
            ->where('relation.subject_id', $id)
            ->where('relation.type_id', 161) // Name access point relation
            ->pluck('actor_i18n.authorized_form_of_name')
            ->toArray();

        // Genre access points
        $genres = DB::table('object_term_relation')
            ->join('term_i18n', 'object_term_relation.term_id', '=', 'term_i18n.id')
            ->join('term', 'object_term_relation.term_id', '=', 'term.id')
            ->where('object_term_relation.object_id', $id)
            ->where('term.taxonomy_id', 78) // Genre taxonomy
            ->pluck('term_i18n.name')
            ->toArray();

        // Digital objects
        $digitalObjects = DB::table('digital_object')
            ->where('object_id', $id)
            ->get();

        // Physical objects
        $physicalObjects = DB::table('relation')
            ->join('physical_object', 'relation.object_id', '=', 'physical_object.id')
            ->join('physical_object_i18n', 'physical_object.id', '=', 'physical_object_i18n.id')
            ->leftJoin('term_i18n', 'physical_object.type_id', '=', 'term_i18n.id')
            ->where('relation.subject_id', $id)
            ->where('relation.type_id', 173) // Physical object relation
            ->select('physical_object_i18n.name', 'physical_object_i18n.location', 'term_i18n.name as type')
            ->get();

        // Notes
        $notes = DB::table('note')
            ->join('note_i18n', 'note.id', '=', 'note_i18n.id')
            ->where('note.object_id', $id)
            ->select('note.type_id', 'note_i18n.content')
            ->get();

        // Alternative identifiers
        $altIds = DB::table('property')
            ->join('property_i18n', 'property.id', '=', 'property_i18n.id')
            ->where('property.object_id', $id)
            ->where('property.name', 'alternativeIdentifiers')
            ->first();

        return (object)[
            'io' => $io,
            'i18n' => $i18n,
            'slug' => $slug ? $slug->slug : null,
            'parentSlug' => $parentSlug,
            'level' => $level ? $level->name : null,
            'repository' => $repository ? $repository->authorized_form_of_name : null,
            'events' => $events,
            'subjects' => $subjects,
            'places' => $places,
            'names' => $names,
            'genres' => $genres,
            'digitalObjects' => $digitalObjects,
            'physicalObjects' => $physicalObjects,
            'notes' => $notes,
            'altIds' => $altIds ? $altIds->value : null
        ];
    }

    /**
     * Convert to CSV (ISAD-G format)
     */
    protected function toCSV($objects)
    {
        $output = fopen('php://temp', 'r+');

        // Write header
        fputcsv($output, self::$csvColumns);

        // Write rows
        foreach ($objects as $obj) {
            if (!$obj) continue;
            fputcsv($output, $this->buildCSVRow($obj));
        }

        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);

        return $csv;
    }

    /**
     * Build CSV row for an object
     */
    protected function buildCSVRow($obj)
    {
        $io = $obj->io;
        $i18n = $obj->i18n;

        // Extract event data
        $eventDates = [];
        $eventTypes = [];
        $eventStartDates = [];
        $eventEndDates = [];
        $eventActors = [];
        $eventActorHistories = [];

        foreach ($obj->events as $event) {
            $eventDates[] = $event->date ?? '';
            $eventTypes[] = $event->type_name ?? '';
            $eventStartDates[] = $event->start_date ?? '';
            $eventEndDates[] = $event->end_date ?? '';
            $eventActors[] = $event->actor_name ?? '';
            $eventActorHistories[] = '';
        }

        // Extract physical object data
        $physNames = [];
        $physLocations = [];
        $physTypes = [];
        foreach ($obj->physicalObjects as $po) {
            $physNames[] = $po->name ?? '';
            $physLocations[] = $po->location ?? '';
            $physTypes[] = $po->type ?? '';
        }

        // Digital object paths
        $doPaths = [];
        $doURIs = [];
        foreach ($obj->digitalObjects as $do) {
            $doPaths[] = $do->path ?? '';
            $doURIs[] = $do->uri ?? '';
        }

        // Extract notes by type
        $generalNote = '';
        $archivistNote = '';
        foreach ($obj->notes as $note) {
            if ($note->type_id == 121) { // General note
                $generalNote = $note->content;
            } elseif ($note->type_id == 122) { // Archivist note
                $archivistNote = $note->content;
            }
        }

        return [
            $io->id, // legacyId
            $io->parent_id != self::ROOT_ID ? $io->parent_id : '', // parentId
            $obj->parentSlug ?? '', // qubitParentSlug
            $i18n->accession_number ?? '', // accessionNumber
            $io->identifier ?? '', // identifier
            $i18n->title ?? '', // title
            $obj->level ?? '', // levelOfDescription
            $i18n->extent_and_medium ?? '', // extentAndMedium
            $obj->repository ?? '', // repository
            $i18n->archival_history ?? '', // archivalHistory
            $i18n->acquisition ?? '', // acquisition
            $i18n->scope_and_content ?? '', // scopeAndContent
            $i18n->appraisal ?? '', // appraisal
            $i18n->accruals ?? '', // accruals
            $i18n->arrangement ?? '', // arrangement
            $i18n->access_conditions ?? '', // accessConditions
            $i18n->reproduction_conditions ?? '', // reproductionConditions
            $io->language ?? '', // language (serialized)
            $io->script ?? '', // script (serialized)
            $i18n->language_note ?? '', // languageNote
            $i18n->physical_characteristics ?? '', // physicalCharacteristics
            $i18n->finding_aids ?? '', // findingAids
            $i18n->location_of_originals ?? '', // locationOfOriginals
            $i18n->location_of_copies ?? '', // locationOfCopies
            $i18n->related_units_of_description ?? '', // relatedUnitsOfDescription
            $i18n->publication_note ?? '', // publicationNote
            implode('|', $doPaths), // digitalObjectPath
            implode('|', $doURIs), // digitalObjectURI
            $generalNote, // generalNote
            implode('|', $obj->subjects), // subjectAccessPoints
            implode('|', $obj->places), // placeAccessPoints
            implode('|', $obj->names), // nameAccessPoints
            implode('|', $obj->genres), // genreAccessPoints
            $io->description_identifier ?? '', // descriptionIdentifier
            $i18n->institution_responsible_identifier ?? '', // institutionIdentifier
            $i18n->rules ?? '', // rules
            $io->description_status_id ?? '', // descriptionStatus
            $io->description_detail_id ?? '', // levelOfDetail
            $i18n->revision_history ?? '', // revisionHistory
            $io->language ?? '', // languageOfDescription
            $io->script ?? '', // scriptOfDescription
            $i18n->sources ?? '', // sources
            $archivistNote, // archivistNote
            $io->publication_status_id == 160 ? 'Published' : 'Draft', // publicationStatus
            implode('|', $physNames), // physicalObjectName
            implode('|', $physLocations), // physicalObjectLocation
            implode('|', $physTypes), // physicalObjectType
            $obj->altIds ?? '', // alternativeIdentifiers
            '', // alternativeIdentifierLabels
            implode('|', $eventDates), // eventDates
            implode('|', $eventTypes), // eventTypes
            implode('|', $eventStartDates), // eventStartDates
            implode('|', $eventEndDates), // eventEndDates
            implode('|', $eventActors), // eventActors
            implode('|', $eventActorHistories), // eventActorHistories
            $this->culture // culture
        ];
    }

    /**
     * Convert to EAD XML
     */
    protected function toEAD($objects)
    {
        $isEAD3 = ($this->format == self::FORMAT_EAD3);
        $ns = $isEAD3 ? 'https://archivists.org/ns/ead/v3' : 'urn:isbn:1-931666-22-9';

        $xml = new DOMDocument('1.0', 'UTF-8');
        $xml->formatOutput = true;

        // Root element
        $ead = $xml->createElementNS($ns, 'ead');
        if (!$isEAD3) {
            $ead->setAttribute('xmlns:xsi', 'http://www.w3.org/2001/XMLSchema-instance');
            $ead->setAttribute('xsi:schemaLocation', $ns . ' http://www.loc.gov/ead/ead.xsd');
        }
        $xml->appendChild($ead);

        // EAD Header
        $header = $xml->createElement($isEAD3 ? 'control' : 'eadheader');
        $ead->appendChild($header);

        // Get first object for header info
        $firstObj = $objects[0] ?? null;
        if ($firstObj) {
            if ($isEAD3) {
                $recordid = $xml->createElement('recordid', $firstObj->io->identifier ?? 'export');
                $header->appendChild($recordid);
            } else {
                $eadid = $xml->createElement('eadid', $firstObj->io->identifier ?? 'export');
                $header->appendChild($eadid);

                $filedesc = $xml->createElement('filedesc');
                $header->appendChild($filedesc);

                $titlestmt = $xml->createElement('titlestmt');
                $filedesc->appendChild($titlestmt);

                $titleproper = $xml->createElement('titleproper', htmlspecialchars($firstObj->i18n->title ?? 'Finding Aid'));
                $titlestmt->appendChild($titleproper);
            }
        }

        // Archival description
        $archdesc = $xml->createElement('archdesc');
        $archdesc->setAttribute('level', strtolower($firstObj->level ?? 'collection'));
        $ead->appendChild($archdesc);

        // Add DID for main description
        if ($firstObj) {
            $did = $this->createEADDid($xml, $firstObj, $isEAD3);
            $archdesc->appendChild($did);

            // Add other elements
            $this->addEADElements($xml, $archdesc, $firstObj, $isEAD3);
        }

        // Add component hierarchy for descendants
        if (count($objects) > 1) {
            $dsc = $xml->createElement('dsc');
            $archdesc->appendChild($dsc);

            // Build hierarchical structure
            $this->addEADComponents($xml, $dsc, array_slice($objects, 1), $isEAD3);
        }

        return $xml->saveXML();
    }

    /**
     * Create EAD DID element
     */
    protected function createEADDid($xml, $obj, $isEAD3 = false)
    {
        $did = $xml->createElement('did');

        // Unit ID
        if ($obj->io->identifier) {
            $unitid = $xml->createElement('unitid', htmlspecialchars($obj->io->identifier));
            $did->appendChild($unitid);
        }

        // Unit Title
        if ($obj->i18n->title) {
            $unittitle = $xml->createElement('unittitle', htmlspecialchars($obj->i18n->title));
            $did->appendChild($unittitle);
        }

        // Unit Date
        foreach ($obj->events as $event) {
            if ($event->date) {
                $unitdate = $xml->createElement('unitdate', htmlspecialchars($event->date));
                if ($event->start_date) {
                    $unitdate->setAttribute('normal', $event->start_date . '/' . ($event->end_date ?? $event->start_date));
                }
                $did->appendChild($unitdate);
                break;
            }
        }

        // Extent
        if ($obj->i18n->extent_and_medium) {
            $physdesc = $xml->createElement('physdesc');
            $extent = $xml->createElement('extent', htmlspecialchars($obj->i18n->extent_and_medium));
            $physdesc->appendChild($extent);
            $did->appendChild($physdesc);
        }

        // Repository
        if ($obj->repository) {
            $repository = $xml->createElement('repository');
            $corpname = $xml->createElement('corpname', htmlspecialchars($obj->repository));
            $repository->appendChild($corpname);
            $did->appendChild($repository);
        }

        // Origination (creators)
        foreach ($obj->events as $event) {
            if ($event->actor_name && $event->type_id == 111) { // Creation event
                $origination = $xml->createElement('origination');
                $persname = $xml->createElement('persname', htmlspecialchars($event->actor_name));
                $origination->appendChild($persname);
                $did->appendChild($origination);
            }
        }

        return $did;
    }

    /**
     * Add additional EAD elements
     */
    protected function addEADElements($xml, $parent, $obj, $isEAD3 = false)
    {
        // Scope and Content
        if ($obj->i18n->scope_and_content) {
            $scopecontent = $xml->createElement('scopecontent');
            $p = $xml->createElement('p', htmlspecialchars($obj->i18n->scope_and_content));
            $scopecontent->appendChild($p);
            $parent->appendChild($scopecontent);
        }

        // Archival History (Custodial History)
        if ($obj->i18n->archival_history) {
            $custodhist = $xml->createElement('custodhist');
            $p = $xml->createElement('p', htmlspecialchars($obj->i18n->archival_history));
            $custodhist->appendChild($p);
            $parent->appendChild($custodhist);
        }

        // Acquisition
        if ($obj->i18n->acquisition) {
            $acqinfo = $xml->createElement('acqinfo');
            $p = $xml->createElement('p', htmlspecialchars($obj->i18n->acquisition));
            $acqinfo->appendChild($p);
            $parent->appendChild($acqinfo);
        }

        // Access Conditions
        if ($obj->i18n->access_conditions) {
            $accessrestrict = $xml->createElement('accessrestrict');
            $p = $xml->createElement('p', htmlspecialchars($obj->i18n->access_conditions));
            $accessrestrict->appendChild($p);
            $parent->appendChild($accessrestrict);
        }

        // Reproduction Conditions
        if ($obj->i18n->reproduction_conditions) {
            $userestrict = $xml->createElement('userestrict');
            $p = $xml->createElement('p', htmlspecialchars($obj->i18n->reproduction_conditions));
            $userestrict->appendChild($p);
            $parent->appendChild($userestrict);
        }

        // Arrangement
        if ($obj->i18n->arrangement) {
            $arrangement = $xml->createElement('arrangement');
            $p = $xml->createElement('p', htmlspecialchars($obj->i18n->arrangement));
            $arrangement->appendChild($p);
            $parent->appendChild($arrangement);
        }

        // Control Access (subjects, places, names)
        if (count($obj->subjects) > 0 || count($obj->places) > 0 || count($obj->names) > 0) {
            $controlaccess = $xml->createElement('controlaccess');

            foreach ($obj->subjects as $subject) {
                $subj = $xml->createElement('subject', htmlspecialchars($subject));
                $controlaccess->appendChild($subj);
            }

            foreach ($obj->places as $place) {
                $geog = $xml->createElement('geogname', htmlspecialchars($place));
                $controlaccess->appendChild($geog);
            }

            foreach ($obj->names as $name) {
                $persname = $xml->createElement('persname', htmlspecialchars($name));
                $controlaccess->appendChild($persname);
            }

            $parent->appendChild($controlaccess);
        }
    }

    /**
     * Add EAD component hierarchy
     */
    protected function addEADComponents($xml, $parent, $objects, $isEAD3 = false)
    {
        foreach ($objects as $obj) {
            if (!$obj) continue;

            $c = $xml->createElement('c');
            $c->setAttribute('level', strtolower($obj->level ?? 'item'));

            $did = $this->createEADDid($xml, $obj, $isEAD3);
            $c->appendChild($did);

            $this->addEADElements($xml, $c, $obj, $isEAD3);

            $parent->appendChild($c);
        }
    }

    /**
     * Convert to Dublin Core
     */
    protected function toDublinCore($objects)
    {
        $xml = new DOMDocument('1.0', 'UTF-8');
        $xml->formatOutput = true;

        $records = $xml->createElement('records');
        $records->setAttribute('xmlns:dc', 'http://purl.org/dc/elements/1.1/');
        $records->setAttribute('xmlns:dcterms', 'http://purl.org/dc/terms/');
        $xml->appendChild($records);

        foreach ($objects as $obj) {
            if (!$obj) continue;

            $record = $xml->createElement('record');

            // DC elements
            if ($obj->i18n->title) {
                $record->appendChild($xml->createElement('dc:title', htmlspecialchars($obj->i18n->title)));
            }

            foreach ($obj->events as $event) {
                if ($event->actor_name) {
                    $record->appendChild($xml->createElement('dc:creator', htmlspecialchars($event->actor_name)));
                }
                if ($event->date) {
                    $record->appendChild($xml->createElement('dc:date', htmlspecialchars($event->date)));
                }
            }

            if ($obj->i18n->scope_and_content) {
                $record->appendChild($xml->createElement('dc:description', htmlspecialchars($obj->i18n->scope_and_content)));
            }

            foreach ($obj->subjects as $subject) {
                $record->appendChild($xml->createElement('dc:subject', htmlspecialchars($subject)));
            }

            if ($obj->io->identifier) {
                $record->appendChild($xml->createElement('dc:identifier', htmlspecialchars($obj->io->identifier)));
            }

            if ($obj->repository) {
                $record->appendChild($xml->createElement('dc:publisher', htmlspecialchars($obj->repository)));
            }

            $record->appendChild($xml->createElement('dc:type', htmlspecialchars($obj->level ?? 'Collection')));

            $records->appendChild($record);
        }

        return $xml->saveXML();
    }

    /**
     * Convert to MODS
     */
    protected function toMODS($objects)
    {
        $xml = new DOMDocument('1.0', 'UTF-8');
        $xml->formatOutput = true;

        $collection = $xml->createElementNS('http://www.loc.gov/mods/v3', 'modsCollection');
        $xml->appendChild($collection);

        foreach ($objects as $obj) {
            if (!$obj) continue;

            $mods = $xml->createElement('mods');

            // Title
            if ($obj->i18n->title) {
                $titleInfo = $xml->createElement('titleInfo');
                $title = $xml->createElement('title', htmlspecialchars($obj->i18n->title));
                $titleInfo->appendChild($title);
                $mods->appendChild($titleInfo);
            }

            // Names
            foreach ($obj->events as $event) {
                if ($event->actor_name && $event->type_id == 111) {
                    $name = $xml->createElement('name');
                    $name->setAttribute('type', 'personal');
                    $namePart = $xml->createElement('namePart', htmlspecialchars($event->actor_name));
                    $name->appendChild($namePart);
                    $role = $xml->createElement('role');
                    $roleTerm = $xml->createElement('roleTerm', 'creator');
                    $role->appendChild($roleTerm);
                    $name->appendChild($role);
                    $mods->appendChild($name);
                }
            }

            // Date
            foreach ($obj->events as $event) {
                if ($event->date) {
                    $originInfo = $xml->createElement('originInfo');
                    $dateCreated = $xml->createElement('dateCreated', htmlspecialchars($event->date));
                    $originInfo->appendChild($dateCreated);
                    $mods->appendChild($originInfo);
                    break;
                }
            }

            // Abstract
            if ($obj->i18n->scope_and_content) {
                $abstract = $xml->createElement('abstract', htmlspecialchars($obj->i18n->scope_and_content));
                $mods->appendChild($abstract);
            }

            // Subjects
            foreach ($obj->subjects as $subject) {
                $subjectEl = $xml->createElement('subject');
                $topic = $xml->createElement('topic', htmlspecialchars($subject));
                $subjectEl->appendChild($topic);
                $mods->appendChild($subjectEl);
            }

            // Identifier
            if ($obj->io->identifier) {
                $identifier = $xml->createElement('identifier', htmlspecialchars($obj->io->identifier));
                $identifier->setAttribute('type', 'local');
                $mods->appendChild($identifier);
            }

            $collection->appendChild($mods);
        }

        return $xml->saveXML();
    }

    /**
     * Create transportable package (ZIP)
     */
    public function createExportPackage($objectId, $outputPath)
    {
        $zip = new ZipArchive();
        $zipPath = $outputPath . '/export_' . date('Y-m-d_His') . '.zip';

        if ($zip->open($zipPath, ZipArchive::CREATE) !== true) {
            return false;
        }

        $objects = $this->getExportObjects($objectId);

        // Add CSV
        $this->format = self::FORMAT_CSV;
        $csv = $this->toCSV($objects);
        $zip->addFromString('metadata/descriptions.csv', $csv);

        // Add EAD
        $this->format = self::FORMAT_EAD;
        $ead = $this->toEAD($objects);
        $zip->addFromString('metadata/ead.xml', $ead);

        // Add DC
        $this->format = self::FORMAT_DC;
        $dc = $this->toDublinCore($objects);
        $zip->addFromString('metadata/dc.xml', $dc);

        // Add digital objects if requested
        if ($this->includeDigitalObjects) {
            $baseDir = sfConfig::get('sf_web_dir', sfConfig::get('sf_root_dir'));
            foreach ($objects as $obj) {
                foreach ($obj->digitalObjects as $do) {
                    $filePath = $baseDir . '/uploads/' . $do->path;
                    if (file_exists($filePath)) {
                        $zip->addFile($filePath, 'objects/' . $do->id . '_' . basename($do->path));
                    }
                }
            }
        }

        // Create manifest
        $manifest = [
            'created' => date('c'),
            'generator' => 'AHG AtoM Export Module',
            'version' => '2.0',
            'format' => 'csv',
            'includesDigitalObjects' => $this->includeDigitalObjects,
            'recordCount' => count($objects)
        ];
        $zip->addFromString('manifest.json', json_encode($manifest, JSON_PRETTY_PRINT));

        $zip->close();

        return $zipPath;
    }

    /**
     * Get export statistics
     */
    public function getExportStats($objectId = null)
    {
        $query = DB::table('information_object')
            ->where('id', '!=', self::ROOT_ID);

        if ($objectId) {
            // Count descendants
            $obj = DB::table('information_object')
                ->where('id', $objectId)
                ->first();

            if ($obj) {
                $query->where('lft', '>=', $obj->lft)
                      ->where('rgt', '<=', $obj->rgt);
            }
        }

        if ($this->repositoryId) {
            $query->where('repository_id', $this->repositoryId);
        }

        $totalRecords = $query->count();

        // Count digital objects
        $doQuery = DB::table('digital_object')
            ->join('information_object', 'digital_object.object_id', '=', 'information_object.id')
            ->where('information_object.id', '!=', self::ROOT_ID);

        if ($objectId) {
            $obj = DB::table('information_object')
                ->where('id', $objectId)
                ->first();

            if ($obj) {
                $doQuery->where('information_object.lft', '>=', $obj->lft)
                        ->where('information_object.rgt', '<=', $obj->rgt);
            }
        }

        if ($this->repositoryId) {
            $doQuery->where('information_object.repository_id', $this->repositoryId);
        }

        $totalDigitalObjects = $doQuery->count();

        return [
            'totalRecords' => $totalRecords,
            'totalDigitalObjects' => $totalDigitalObjects
        ];
    }
}
