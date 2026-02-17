<?php

use AtomFramework\Http\Controllers\AhgController;

/**
 * Batch export existing AtoM records to sector-specific CSV format.
 *
 * Allows users to export records from AtoM using sector-specific CSV formats
 * that can be re-imported or used for reporting.
 */
class dataMigrationBatchExportAction extends AhgController
{
    public function execute($request)
    {
        if (!$this->context->user->isAdministrator()) {
            $this->forward('admin', 'secure');
        }

        \AhgCore\Core\AhgDb::init();
        $DB = \Illuminate\Database\Capsule\Manager::class;

        // Handle form submission
        if ($request->isMethod('post')) {
            return $this->processExport($request, $DB);
        }

        // Get repositories for filter dropdown (Repository extends Actor, so name is in actor_i18n)
        $this->repositories = $DB::table('repository')
            ->join('actor_i18n', 'repository.id', '=', 'actor_i18n.id')
            ->where('actor_i18n.culture', '=', 'en')
            ->orderBy('actor_i18n.authorized_form_of_name')
            ->select('repository.id', 'actor_i18n.authorized_form_of_name as name')
            ->get();

        // Get levels of description
        $this->levels = $DB::table('term')
            ->join('term_i18n', 'term.id', '=', 'term_i18n.id')
            ->where('term.taxonomy_id', '=', QubitTaxonomy::LEVEL_OF_DESCRIPTION_ID)
            ->where('term_i18n.culture', '=', 'en')
            ->orderBy('term_i18n.name')
            ->select('term.id', 'term_i18n.name')
            ->get();

        // Available sectors
        $this->sectors = [
            'archives' => 'Archives (ISAD-G)',
            'museum' => 'Museum (Spectrum 5.1)',
            'library' => 'Library (MARC/RDA)',
            'gallery' => 'Gallery (CCO/VRA)',
            'dam' => 'Digital Assets (Dublin Core/IPTC)',
        ];
    }

    protected function processExport($request, $DB)
    {
        $sector = $request->getParameter('sector', 'archives');
        $repositoryId = $request->getParameter('repository_id');
        $levelIds = $request->getParameter('level_ids', []);
        $parentSlug = trim($request->getParameter('parent_slug', ''));
        $includeDescendants = $request->getParameter('include_descendants', false);

        // Build query
        $query = $DB::table('information_object')
            ->join('information_object_i18n', 'information_object.id', '=', 'information_object_i18n.id')
            ->where('information_object_i18n.culture', '=', 'en')
            ->where('information_object.id', '!=', QubitInformationObject::ROOT_ID);

        // Filter by repository
        if ($repositoryId) {
            $query->where('information_object.repository_id', '=', $repositoryId);
        }

        // Filter by level of description
        if (!empty($levelIds)) {
            $query->whereIn('information_object.level_of_description_id', $levelIds);
        }

        // Filter by parent (scope)
        if ($parentSlug) {
            $parent = $DB::table('slug')
                ->where('slug', '=', $parentSlug)
                ->first();

            if ($parent) {
                if ($includeDescendants) {
                    // Get parent's lft/rgt for descendant query
                    $parentObj = $DB::table('information_object')
                        ->where('id', '=', $parent->object_id)
                        ->first();

                    if ($parentObj) {
                        $query->where('information_object.lft', '>', $parentObj->lft)
                            ->where('information_object.rgt', '<', $parentObj->rgt);
                    }
                } else {
                    $query->where('information_object.parent_id', '=', $parent->object_id);
                }
            } else {
                $this->getUser()->setFlash('error', 'Parent record not found: ' . $parentSlug);
                $this->redirect(['module' => 'dataMigration', 'action' => 'batchExport']);
            }
        }

        // Get count first
        $count = $query->count();

        if ($count === 0) {
            $this->getUser()->setFlash('error', 'No records match your filter criteria.');
            $this->redirect(['module' => 'dataMigration', 'action' => 'batchExport']);
        }

        // For large exports (>500), queue a background job
        if ($count > 500) {
            return $this->queueBackgroundExport($request, $DB, $count);
        }

        // Direct export for smaller datasets
        return $this->directExport($query, $sector, $DB);
    }

    protected function directExport($query, $sector, $DB)
    {
        // Include required files
        $pluginPath = sfConfig::get('sf_root_dir') . '/atom-ahg-plugins/ahgDataMigrationPlugin';
        require_once $pluginPath . '/lib/Exporters/BaseExporter.php';
        require_once $pluginPath . '/lib/Exporters/ArchivesExporter.php';
        require_once $pluginPath . '/lib/Exporters/MuseumExporter.php';
        require_once $pluginPath . '/lib/Exporters/LibraryExporter.php';
        require_once $pluginPath . '/lib/Exporters/GalleryExporter.php';
        require_once $pluginPath . '/lib/Exporters/DamExporter.php';
        require_once $pluginPath . '/lib/Exporters/ExporterFactory.php';

        // Select fields based on what we need for export
        $records = $query->select([
            'information_object.id',
            'information_object.identifier',
            'information_object.level_of_description_id',
            'information_object.repository_id',
            'information_object.parent_id',
            'information_object_i18n.title',
            'information_object_i18n.scope_and_content as scopeAndContent',
            'information_object_i18n.extent_and_medium as extentAndMedium',
            'information_object_i18n.archival_history as archivalHistory',
            'information_object_i18n.arrangement as arrangement',
            'information_object_i18n.access_conditions as accessConditions',
            'information_object_i18n.reproduction_conditions as reproductionConditions',
            'information_object_i18n.physical_characteristics as physicalCharacteristics',
            'information_object_i18n.finding_aids as findingAids',
            'information_object_i18n.location_of_originals as locationOfOriginals',
            'information_object_i18n.location_of_copies as locationOfCopies',
            'information_object_i18n.related_units_of_description as relatedUnitsOfDescription',
            'information_object_i18n.rules as rules',
            'information_object_i18n.sources as sources',
            'information_object_i18n.revision_history as revisionHistory',
        ])->orderBy('information_object.lft')->get();

        // Build export data with additional lookups
        $data = [];
        foreach ($records as $record) {
            $row = (array) $record;

            // Get slug
            $slug = $DB::table('slug')
                ->where('object_id', '=', $record->id)
                ->value('slug');
            $row['slug'] = $slug;

            // Get parent slug
            if ($record->parent_id && $record->parent_id != QubitInformationObject::ROOT_ID) {
                $parentSlug = $DB::table('slug')
                    ->where('object_id', '=', $record->parent_id)
                    ->value('slug');
                $row['parentId'] = $parentSlug;
            }

            // Get level of description name
            if ($record->level_of_description_id) {
                $levelName = $DB::table('term_i18n')
                    ->where('id', '=', $record->level_of_description_id)
                    ->where('culture', '=', \AtomExtensions\Helpers\CultureHelper::getCulture())
                    ->value('name');
                $row['levelOfDescription'] = $levelName;
            }

            // Get repository name (Repository extends Actor, name is in actor_i18n)
            if ($record->repository_id) {
                $repoName = $DB::table('actor_i18n')
                    ->where('id', '=', $record->repository_id)
                    ->where('culture', '=', \AtomExtensions\Helpers\CultureHelper::getCulture())
                    ->value('authorized_form_of_name');
                $row['repository'] = $repoName;
            }

            // Get dates
            $events = $DB::table('event')
                ->join('event_i18n', 'event.id', '=', 'event_i18n.id')
                ->where('event.object_id', '=', $record->id)
                ->where('event_i18n.culture', '=', 'en')
                ->select('event_i18n.date', 'event.start_date', 'event.end_date', 'event.type_id')
                ->get();

            $dates = [];
            foreach ($events as $event) {
                if ($event->date) {
                    $dates[] = $event->date;
                } elseif ($event->start_date || $event->end_date) {
                    $dates[] = trim($event->start_date . ' - ' . $event->end_date, ' -');
                }
            }
            $row['eventDates'] = implode('|', $dates);

            // Get subject access points
            $subjects = $DB::table('object_term_relation')
                ->join('term', 'object_term_relation.term_id', '=', 'term.id')
                ->join('term_i18n', 'term.id', '=', 'term_i18n.id')
                ->where('object_term_relation.object_id', '=', $record->id)
                ->where('term.taxonomy_id', '=', QubitTaxonomy::SUBJECT_ID)
                ->where('term_i18n.culture', '=', 'en')
                ->pluck('term_i18n.name')
                ->toArray();
            $row['subjectAccessPoints'] = implode('|', $subjects);

            // Get place access points
            $places = $DB::table('object_term_relation')
                ->join('term', 'object_term_relation.term_id', '=', 'term.id')
                ->join('term_i18n', 'term.id', '=', 'term_i18n.id')
                ->where('object_term_relation.object_id', '=', $record->id)
                ->where('term.taxonomy_id', '=', QubitTaxonomy::PLACE_ID)
                ->where('term_i18n.culture', '=', 'en')
                ->pluck('term_i18n.name')
                ->toArray();
            $row['placeAccessPoints'] = implode('|', $places);

            // Get name access points
            $names = $DB::table('relation')
                ->join('actor', 'relation.object_id', '=', 'actor.id')
                ->join('actor_i18n', 'actor.id', '=', 'actor_i18n.id')
                ->where('relation.subject_id', '=', $record->id)
                ->where('relation.type_id', '=', QubitTerm::NAME_ACCESS_POINT_ID)
                ->where('actor_i18n.culture', '=', 'en')
                ->pluck('actor_i18n.authorized_form_of_name')
                ->toArray();
            $row['nameAccessPoints'] = implode('|', $names);

            // Get digital object path (combine path + name for full file path)
            $digitalObject = $DB::table('digital_object')
                ->where('object_id', '=', $record->id)
                ->first();
            if ($digitalObject) {
                // Combine path and name for full file path
                $fullPath = rtrim($digitalObject->path ?? '', '/');
                if (!empty($digitalObject->name)) {
                    $fullPath .= '/' . $digitalObject->name;
                }
                $row['digitalObjectPath'] = $fullPath;
                $row['digitalObjectURI'] = $digitalObject->uri ?? '';
            }

            // Legacy ID
            $row['legacyId'] = $record->id;

            $data[] = $row;
        }

        // Get exporter
        try {
            $exporter = \ahgDataMigrationPlugin\Exporters\ExporterFactory::create($sector);
        } catch (\Exception $e) {
            $exporter = new \ahgDataMigrationPlugin\Exporters\ArchivesExporter();
        }

        // Export
        $exporter->setData($data);
        $csv = $exporter->export();
        $filename = 'atom_' . $sector . '_export_' . date('Y-m-d_His') . '.csv';

        // Send response
        $this->getResponse()->setContentType('text/csv');
        $this->getResponse()->setHttpHeader('Content-Disposition', 'attachment; filename="' . $filename . '"');
        $this->getResponse()->setContent($csv);

        return sfView::NONE;
    }

    protected function queueBackgroundExport($request, $DB, $count)
    {
        // Create a migration job for background processing
        $jobId = $DB::table('atom_migration_job')->insertGetId([
            'type' => 'batch_export',
            'status' => 'pending',
            'total_rows' => $count,
            'processed_rows' => 0,
            'parameters' => json_encode([
                'sector' => $request->getParameter('sector', 'archives'),
                'repository_id' => $request->getParameter('repository_id'),
                'level_ids' => $request->getParameter('level_ids', []),
                'parent_slug' => $request->getParameter('parent_slug', ''),
                'include_descendants' => $request->getParameter('include_descendants', false),
            ]),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $this->getUser()->setFlash(
            'notice',
            sprintf(
                'Export queued for %d records. Job ID: %d. Check the Jobs page for status.',
                $count,
                $jobId
            )
        );

        $this->redirect(['module' => 'dataMigration', 'action' => 'jobs']);
    }
}
