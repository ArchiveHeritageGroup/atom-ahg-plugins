<?php

class exportActions extends sfActions
{
    public function preExecute()
    {
        parent::preExecute();
        if (!$this->context->user->isAuthenticated()) {
            $this->redirect('user/login');
        }
    }

    /**
     * Export dashboard/index
     */
    public function executeIndex(sfWebRequest $request)
    {
        $this->exportFormats = [
            'ead' => ['name' => 'EAD 2002', 'description' => 'Encoded Archival Description'],
            'dc' => ['name' => 'Dublin Core', 'description' => 'Simple Dublin Core XML'],
            'mods' => ['name' => 'MODS', 'description' => 'Metadata Object Description Schema'],
            'csv' => ['name' => 'CSV', 'description' => 'Comma-separated values'],
            'json' => ['name' => 'JSON', 'description' => 'JavaScript Object Notation'],
        ];
    }

    /**
     * CSV Export page and download
     */
    public function executeCsv(sfWebRequest $request)
    {
        \AhgCore\Core\AhgDb::init();
        $DB = \Illuminate\Database\Capsule\Manager::class;

        $this->format = 'csv';
        $this->formatName = 'CSV Export (ISAD-G)';

        // Get repositories for filter
        $this->repositories = $DB::table('repository')
            ->join('repository_i18n', 'repository.id', '=', 'repository_i18n.id')
            ->where('repository_i18n.culture', '=', 'en')
            ->orderBy('repository_i18n.authorized_form_of_name')
            ->select('repository.id', 'repository_i18n.authorized_form_of_name as name')
            ->get();

        // Get levels of description
        $this->levels = $DB::table('term')
            ->join('term_i18n', 'term.id', '=', 'term_i18n.id')
            ->where('term.taxonomy_id', '=', QubitTaxonomy::LEVEL_OF_DESCRIPTION_ID)
            ->where('term_i18n.culture', '=', 'en')
            ->orderBy('term_i18n.name')
            ->select('term.id', 'term_i18n.name')
            ->get();

        // Handle export
        if ($request->isMethod('post')) {
            return $this->processCsvExport($request, $DB);
        }
    }

    /**
     * Process CSV export
     */
    protected function processCsvExport($request, $DB)
    {
        $repositoryId = $request->getParameter('repository_id');
        $levelIds = $request->getParameter('level_ids', []);
        $parentSlug = trim($request->getParameter('parent_slug', ''));
        $includeDescendants = $request->getParameter('include_descendants', false);

        // Build query
        $query = $DB::table('information_object as io')
            ->join('information_object_i18n as ioi', 'io.id', '=', 'ioi.id')
            ->where('ioi.culture', '=', 'en')
            ->where('io.id', '!=', QubitInformationObject::ROOT_ID);

        if ($repositoryId) {
            $query->where('io.repository_id', '=', $repositoryId);
        }

        if (!empty($levelIds)) {
            $query->whereIn('io.level_of_description_id', $levelIds);
        }

        if ($parentSlug) {
            $parent = $DB::table('slug')->where('slug', '=', $parentSlug)->first();
            if ($parent) {
                $parentObj = $DB::table('information_object')->where('id', '=', $parent->object_id)->first();
                if ($parentObj) {
                    if ($includeDescendants) {
                        $query->where('io.lft', '>', $parentObj->lft)
                            ->where('io.rgt', '<', $parentObj->rgt);
                    } else {
                        $query->where('io.parent_id', '=', $parent->object_id);
                    }
                }
            }
        }

        $records = $query->select([
            'io.id',
            'io.identifier',
            'io.level_of_description_id',
            'io.repository_id',
            'io.parent_id',
            'ioi.title',
            'ioi.scope_and_content',
            'ioi.extent_and_medium',
            'ioi.archival_history',
            'ioi.arrangement',
            'ioi.access_conditions',
            'ioi.reproduction_conditions',
            'ioi.physical_characteristics',
            'ioi.finding_aids',
            'ioi.location_of_originals',
            'ioi.location_of_copies',
            'ioi.related_units_of_description',
        ])->orderBy('io.lft')->get();

        // Build CSV
        $columns = [
            'legacyId', 'identifier', 'title', 'levelOfDescription', 'repository',
            'scopeAndContent', 'extentAndMedium', 'archivalHistory', 'arrangement',
            'accessConditions', 'reproductionConditions', 'physicalCharacteristics',
            'findingAids', 'locationOfOriginals', 'locationOfCopies',
            'relatedUnitsOfDescription', 'eventDates', 'subjectAccessPoints',
            'placeAccessPoints', 'nameAccessPoints',
        ];

        $output = fopen('php://temp', 'r+');
        fputcsv($output, $columns);

        foreach ($records as $r) {
            // Get level name
            $levelName = '';
            if ($r->level_of_description_id) {
                $levelName = $DB::table('term_i18n')
                    ->where('id', '=', $r->level_of_description_id)
                    ->where('culture', '=', 'en')
                    ->value('name') ?? '';
            }

            // Get repository name
            $repoName = '';
            if ($r->repository_id) {
                $repoName = $DB::table('repository_i18n')
                    ->where('id', '=', $r->repository_id)
                    ->where('culture', '=', 'en')
                    ->value('authorized_form_of_name') ?? '';
            }

            // Get dates
            $dates = $DB::table('event')
                ->join('event_i18n', 'event.id', '=', 'event_i18n.id')
                ->where('event.object_id', '=', $r->id)
                ->where('event_i18n.culture', '=', 'en')
                ->pluck('event_i18n.date')
                ->filter()
                ->implode('|');

            // Get subjects
            $subjects = $DB::table('object_term_relation')
                ->join('term_i18n', 'object_term_relation.term_id', '=', 'term_i18n.id')
                ->join('term', 'object_term_relation.term_id', '=', 'term.id')
                ->where('object_term_relation.object_id', '=', $r->id)
                ->where('term.taxonomy_id', '=', QubitTaxonomy::SUBJECT_ID)
                ->where('term_i18n.culture', '=', 'en')
                ->pluck('term_i18n.name')
                ->implode('|');

            // Get places
            $places = $DB::table('object_term_relation')
                ->join('term_i18n', 'object_term_relation.term_id', '=', 'term_i18n.id')
                ->join('term', 'object_term_relation.term_id', '=', 'term.id')
                ->where('object_term_relation.object_id', '=', $r->id)
                ->where('term.taxonomy_id', '=', QubitTaxonomy::PLACE_ID)
                ->where('term_i18n.culture', '=', 'en')
                ->pluck('term_i18n.name')
                ->implode('|');

            // Get names
            $names = $DB::table('relation')
                ->join('actor_i18n', 'relation.object_id', '=', 'actor_i18n.id')
                ->where('relation.subject_id', '=', $r->id)
                ->where('relation.type_id', '=', QubitTerm::NAME_ACCESS_POINT_ID)
                ->where('actor_i18n.culture', '=', 'en')
                ->pluck('actor_i18n.authorized_form_of_name')
                ->implode('|');

            fputcsv($output, [
                $r->id,
                $r->identifier,
                $r->title,
                $levelName,
                $repoName,
                $r->scope_and_content,
                $r->extent_and_medium,
                $r->archival_history,
                $r->arrangement,
                $r->access_conditions,
                $r->reproduction_conditions,
                $r->physical_characteristics,
                $r->finding_aids,
                $r->location_of_originals,
                $r->location_of_copies,
                $r->related_units_of_description,
                $dates,
                $subjects,
                $places,
                $names,
            ]);
        }

        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);

        $filename = 'atom_csv_export_' . date('Y-m-d_His') . '.csv';
        $this->getResponse()->setContentType('text/csv');
        $this->getResponse()->setHttpHeader('Content-Disposition', 'attachment; filename="' . $filename . '"');
        $this->getResponse()->setContent($csv);

        return sfView::NONE;
    }

    /**
     * EAD Export page and download
     */
    public function executeEad(sfWebRequest $request)
    {
        \AhgCore\Core\AhgDb::init();
        $DB = \Illuminate\Database\Capsule\Manager::class;

        $this->format = 'ead';
        $this->formatName = 'EAD 2002 Export';

        // Get repositories
        $this->repositories = $DB::table('repository')
            ->join('repository_i18n', 'repository.id', '=', 'repository_i18n.id')
            ->where('repository_i18n.culture', '=', 'en')
            ->orderBy('repository_i18n.authorized_form_of_name')
            ->select('repository.id', 'repository_i18n.authorized_form_of_name as name')
            ->get();

        // Get top-level fonds for selection
        $fondsLevelId = $DB::table('term')
            ->join('term_i18n', 'term.id', '=', 'term_i18n.id')
            ->where('term.taxonomy_id', '=', QubitTaxonomy::LEVEL_OF_DESCRIPTION_ID)
            ->where('term_i18n.name', 'LIKE', '%fonds%')
            ->where('term_i18n.culture', '=', 'en')
            ->value('term.id');

        $this->fonds = $DB::table('information_object as io')
            ->join('information_object_i18n as ioi', 'io.id', '=', 'ioi.id')
            ->where('ioi.culture', '=', 'en')
            ->where('io.parent_id', '=', QubitInformationObject::ROOT_ID)
            ->orderBy('ioi.title')
            ->select('io.id', 'ioi.title', 'io.identifier')
            ->get();

        // Handle export
        if ($request->isMethod('post')) {
            return $this->processEadExport($request, $DB);
        }
    }

    /**
     * Process EAD export
     */
    protected function processEadExport($request, $DB)
    {
        $objectId = (int) $request->getParameter('object_id');
        $includeDescendants = $request->getParameter('include_descendants', true);

        if (!$objectId) {
            $this->getUser()->setFlash('error', 'Please select a record to export');
            $this->redirect(['module' => 'export', 'action' => 'ead']);
        }

        $record = $DB::table('information_object as io')
            ->join('information_object_i18n as ioi', 'io.id', '=', 'ioi.id')
            ->where('io.id', '=', $objectId)
            ->where('ioi.culture', '=', 'en')
            ->first();

        if (!$record) {
            $this->getUser()->setFlash('error', 'Record not found');
            $this->redirect(['module' => 'export', 'action' => 'ead']);
        }

        // Build EAD XML
        $xml = $this->buildEadXml($record, $DB, $includeDescendants);

        $filename = 'ead_' . ($record->identifier ?: $objectId) . '_' . date('Y-m-d') . '.xml';
        $this->getResponse()->setContentType('application/xml');
        $this->getResponse()->setHttpHeader('Content-Disposition', 'attachment; filename="' . $filename . '"');
        $this->getResponse()->setContent($xml);

        return sfView::NONE;
    }

    /**
     * Build EAD XML document
     */
    protected function buildEadXml($record, $DB, $includeDescendants)
    {
        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->formatOutput = true;

        // EAD root
        $ead = $dom->createElementNS('urn:isbn:1-931666-22-9', 'ead');
        $ead->setAttribute('xmlns:xlink', 'http://www.w3.org/1999/xlink');
        $ead->setAttribute('xmlns:xsi', 'http://www.w3.org/2001/XMLSchema-instance');
        $ead->setAttributeNS('http://www.w3.org/2001/XMLSchema-instance', 'xsi:schemaLocation',
            'urn:isbn:1-931666-22-9 http://www.loc.gov/ead/ead.xsd');
        $dom->appendChild($ead);

        // eadheader
        $header = $dom->createElement('eadheader');
        $header->setAttribute('langencoding', 'iso639-2b');
        $header->setAttribute('countryencoding', 'iso3166-1');
        $header->setAttribute('dateencoding', 'iso8601');
        $header->setAttribute('repositoryencoding', 'iso15511');
        $ead->appendChild($header);

        // eadid
        $eadid = $dom->createElement('eadid', htmlspecialchars($record->identifier ?? 'ead-' . $record->id));
        $header->appendChild($eadid);

        // filedesc
        $filedesc = $dom->createElement('filedesc');
        $header->appendChild($filedesc);

        $titlestmt = $dom->createElement('titlestmt');
        $filedesc->appendChild($titlestmt);

        $titleproper = $dom->createElement('titleproper', htmlspecialchars($record->title ?? 'Untitled'));
        $titlestmt->appendChild($titleproper);

        // profiledesc
        $profiledesc = $dom->createElement('profiledesc');
        $header->appendChild($profiledesc);

        $creation = $dom->createElement('creation');
        $creation->appendChild($dom->createTextNode('Exported from AtoM on '));
        $date = $dom->createElement('date', date('Y-m-d'));
        $creation->appendChild($date);
        $profiledesc->appendChild($creation);

        // archdesc
        $archdesc = $dom->createElement('archdesc');
        $archdesc->setAttribute('level', $this->getEadLevel($record->level_of_description_id, $DB));
        $ead->appendChild($archdesc);

        // did
        $did = $dom->createElement('did');
        $archdesc->appendChild($did);

        if ($record->identifier) {
            $unitid = $dom->createElement('unitid', htmlspecialchars($record->identifier));
            $did->appendChild($unitid);
        }

        $unittitle = $dom->createElement('unittitle', htmlspecialchars($record->title ?? 'Untitled'));
        $did->appendChild($unittitle);

        // Get dates
        $dates = $DB::table('event')
            ->join('event_i18n', 'event.id', '=', 'event_i18n.id')
            ->where('event.object_id', '=', $record->id)
            ->where('event_i18n.culture', '=', 'en')
            ->first();

        if ($dates && $dates->date) {
            $unitdate = $dom->createElement('unitdate', htmlspecialchars($dates->date));
            $did->appendChild($unitdate);
        }

        if ($record->extent_and_medium) {
            $physdesc = $dom->createElement('physdesc', htmlspecialchars($record->extent_and_medium));
            $did->appendChild($physdesc);
        }

        // Scope and content
        if ($record->scope_and_content) {
            $scopecontent = $dom->createElement('scopecontent');
            $p = $dom->createElement('p', htmlspecialchars($record->scope_and_content));
            $scopecontent->appendChild($p);
            $archdesc->appendChild($scopecontent);
        }

        // Arrangement
        if ($record->arrangement) {
            $arrangement = $dom->createElement('arrangement');
            $p = $dom->createElement('p', htmlspecialchars($record->arrangement));
            $arrangement->appendChild($p);
            $archdesc->appendChild($arrangement);
        }

        // Access restrictions
        if ($record->access_conditions) {
            $accessrestrict = $dom->createElement('accessrestrict');
            $p = $dom->createElement('p', htmlspecialchars($record->access_conditions));
            $accessrestrict->appendChild($p);
            $archdesc->appendChild($accessrestrict);
        }

        // Use restrictions
        if ($record->reproduction_conditions) {
            $userestrict = $dom->createElement('userestrict');
            $p = $dom->createElement('p', htmlspecialchars($record->reproduction_conditions));
            $userestrict->appendChild($p);
            $archdesc->appendChild($userestrict);
        }

        // Custodial history
        if ($record->archival_history) {
            $custodhist = $dom->createElement('custodhist');
            $p = $dom->createElement('p', htmlspecialchars($record->archival_history));
            $custodhist->appendChild($p);
            $archdesc->appendChild($custodhist);
        }

        // Control access (subjects)
        $subjects = $DB::table('object_term_relation')
            ->join('term_i18n', 'object_term_relation.term_id', '=', 'term_i18n.id')
            ->join('term', 'object_term_relation.term_id', '=', 'term.id')
            ->where('object_term_relation.object_id', '=', $record->id)
            ->where('term.taxonomy_id', '=', QubitTaxonomy::SUBJECT_ID)
            ->where('term_i18n.culture', '=', 'en')
            ->pluck('term_i18n.name');

        if ($subjects->count() > 0) {
            $controlaccess = $dom->createElement('controlaccess');
            foreach ($subjects as $subject) {
                $subj = $dom->createElement('subject', htmlspecialchars($subject));
                $controlaccess->appendChild($subj);
            }
            $archdesc->appendChild($controlaccess);
        }

        // Include descendants (dsc)
        if ($includeDescendants) {
            $children = $DB::table('information_object as io')
                ->join('information_object_i18n as ioi', 'io.id', '=', 'ioi.id')
                ->where('io.parent_id', '=', $record->id)
                ->where('ioi.culture', '=', 'en')
                ->orderBy('io.lft')
                ->get();

            if ($children->count() > 0) {
                $dsc = $dom->createElement('dsc');
                $archdesc->appendChild($dsc);

                foreach ($children as $child) {
                    $this->addEadComponent($dom, $dsc, $child, $DB);
                }
            }
        }

        return $dom->saveXML();
    }

    /**
     * Add EAD component (c) element for child record
     */
    protected function addEadComponent($dom, $parent, $record, $DB)
    {
        $c = $dom->createElement('c');
        $c->setAttribute('level', $this->getEadLevel($record->level_of_description_id, $DB));
        $parent->appendChild($c);

        $did = $dom->createElement('did');
        $c->appendChild($did);

        if ($record->identifier) {
            $unitid = $dom->createElement('unitid', htmlspecialchars($record->identifier));
            $did->appendChild($unitid);
        }

        $unittitle = $dom->createElement('unittitle', htmlspecialchars($record->title ?? 'Untitled'));
        $did->appendChild($unittitle);

        if ($record->scope_and_content) {
            $scopecontent = $dom->createElement('scopecontent');
            $p = $dom->createElement('p', htmlspecialchars($record->scope_and_content));
            $scopecontent->appendChild($p);
            $c->appendChild($scopecontent);
        }

        // Recurse for children
        $children = $DB::table('information_object as io')
            ->join('information_object_i18n as ioi', 'io.id', '=', 'ioi.id')
            ->where('io.parent_id', '=', $record->id)
            ->where('ioi.culture', '=', 'en')
            ->orderBy('io.lft')
            ->get();

        foreach ($children as $child) {
            $this->addEadComponent($dom, $c, $child, $DB);
        }
    }

    /**
     * Get EAD level string from level_of_description_id
     */
    protected function getEadLevel($levelId, $DB)
    {
        if (!$levelId) {
            return 'otherlevel';
        }

        $levelName = $DB::table('term_i18n')
            ->where('id', '=', $levelId)
            ->where('culture', '=', 'en')
            ->value('name');

        $levelMap = [
            'fonds' => 'fonds',
            'collection' => 'collection',
            'series' => 'series',
            'sub-series' => 'subseries',
            'subseries' => 'subseries',
            'file' => 'file',
            'item' => 'item',
            'class' => 'class',
            'recordgrp' => 'recordgrp',
            'subgrp' => 'subgrp',
        ];

        $level = strtolower($levelName ?? '');

        return $levelMap[$level] ?? 'otherlevel';
    }

    /**
     * Archival descriptions export
     */
    public function executeArchival(sfWebRequest $request)
    {
        $this->format = $request->getParameter('format', 'ead');
    }

    /**
     * Authority records export
     */
    public function executeAuthority(sfWebRequest $request)
    {
        $this->format = $request->getParameter('format', 'eac');
    }

    /**
     * Repository export
     */
    public function executeRepository(sfWebRequest $request)
    {
        $this->format = $request->getParameter('format', 'csv');
    }
}
