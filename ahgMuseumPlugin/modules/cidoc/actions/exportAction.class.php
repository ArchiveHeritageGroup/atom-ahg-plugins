<?php
/**
 * CIDOC CRM Export Action
 *
 * Provides UI and download endpoints for CIDOC-CRM exports.
 * Uses Laravel Illuminate Database
 *
 * @author Johan Pieterse <johan@theahg.co.za>
 * @package ahgMuseumPlugin
 */

use Illuminate\Database\Capsule\Manager as DB;

class cidocExportAction extends sfAction
{
    public function execute($request)
    {
        // Check permissions
        if (!$this->context->user->isAuthenticated()) {
            $this->forwardUnauthorized();
        }

        $this->format = $request->getParameter('format', arCIDOCExportService::FORMAT_JSONLD);
        $this->repositoryId = $request->getParameter('repository');
        $this->objectSlug = $request->getParameter('slug');
        $this->includeLinkedData = $request->getParameter('linkedData', '1') === '1';

        // Get repositories for selector using Laravel
        $this->repositories = $this->getRepositories();

        // Handle download
        if ($request->getParameter('download')) {
            return $this->handleDownload($request);
        }

        // Available formats
        $this->formats = [
            arCIDOCExportService::FORMAT_JSONLD => [
                'label' => 'JSON-LD',
                'description' => 'JSON for Linked Data - recommended for web applications and APIs',
                'extension' => 'jsonld',
                'mime' => 'application/ld+json',
            ],
            arCIDOCExportService::FORMAT_RDFXML => [
                'label' => 'RDF/XML',
                'description' => 'W3C RDF XML serialization - compatible with triple stores',
                'extension' => 'rdf',
                'mime' => 'application/rdf+xml',
            ],
            arCIDOCExportService::FORMAT_TURTLE => [
                'label' => 'Turtle',
                'description' => 'Terse RDF Triple Language - human-readable RDF format',
                'extension' => 'ttl',
                'mime' => 'text/turtle',
            ],
            arCIDOCExportService::FORMAT_NTRIPLES => [
                'label' => 'N-Triples',
                'description' => 'Line-based RDF format - simple and fast to parse',
                'extension' => 'nt',
                'mime' => 'application/n-triples',
            ],
            arCIDOCExportService::FORMAT_CSV => [
                'label' => 'CSV',
                'description' => 'Comma-separated values - easy to open in Excel',
                'extension' => 'csv',
                'mime' => 'text/csv',
            ],
        ];

        // Get preview if single object
        if ($this->objectSlug) {
            $this->object = $this->getObjectBySlug($this->objectSlug);
            if ($this->object) {
                $service = new arCIDOCExportService();
                $service->setFormat(arCIDOCExportService::FORMAT_JSONLD);
                $service->setIncludeLinkedData($this->includeLinkedData);
                $this->preview = $service->exportObject($this->object);
            }
        }

        // CIDOC-CRM class reference
        $this->crmClasses = [
            'E22_Man-Made_Object' => 'Objects created by human activity',
            'E21_Person' => 'Individual human beings',
            'E74_Group' => 'Groups of people acting collectively',
            'E40_Legal_Body' => 'Institutions with legal personality',
            'E53_Place' => 'Geographic locations',
            'E12_Production' => 'Creation activities',
            'E52_Time-Span' => 'Temporal extents',
            'E55_Type' => 'Types and classifications',
            'E36_Visual_Item' => 'Visual representations',
        ];
    }

    /**
     * Forward to unauthorized page
     */
    protected function forwardUnauthorized(): void
    {
        $this->forward('admin', 'secure');
    }

    /**
     * Get repositories using Laravel
     */
    protected function getRepositories()
    {
        $culture = $this->getUser()->getCulture() ?? 'en';

        return DB::table('repository as r')
            ->leftJoin('actor_i18n as ai', function ($join) use ($culture) {
                $join->on('r.id', '=', 'ai.id')
                    ->where('ai.culture', '=', $culture);
            })
            ->leftJoin('actor_i18n as ai_en', function ($join) {
                $join->on('r.id', '=', 'ai_en.id')
                    ->where('ai_en.culture', '=', 'en');
            })
            ->whereNotNull(DB::raw('COALESCE(ai.authorized_form_of_name, ai_en.authorized_form_of_name)'))
            ->where(DB::raw('COALESCE(ai.authorized_form_of_name, ai_en.authorized_form_of_name)'), '!=', '')
            ->orderBy(DB::raw('COALESCE(ai.authorized_form_of_name, ai_en.authorized_form_of_name)'))
            ->pluck(DB::raw('COALESCE(ai.authorized_form_of_name, ai_en.authorized_form_of_name)'), 'r.id')
            ->toArray();
    }

    /**
     * Get object by slug using Laravel
     * Returns object compatible with arCIDOCExportService
     */
    protected function getObjectBySlug($slug)
    {
        $culture = $this->getUser()->getCulture() ?? 'en';

        $obj = DB::table('slug')
            ->join('information_object as io', 'slug.object_id', '=', 'io.id')
            ->leftJoin('information_object_i18n as ioi', function ($join) use ($culture) {
                $join->on('io.id', '=', 'ioi.id')
                    ->where('ioi.culture', '=', $culture);
            })
            ->leftJoin('information_object_i18n as ioi_en', function ($join) {
                $join->on('io.id', '=', 'ioi_en.id')
                    ->where('ioi_en.culture', '=', 'en');
            })
            ->where('slug.slug', $slug)
            ->select([
                'io.*',
                'slug.slug',
                DB::raw('COALESCE(ioi.title, ioi_en.title) as title'),
                DB::raw('COALESCE(ioi.scope_and_content, ioi_en.scope_and_content) as scope_and_content'),
                DB::raw('COALESCE(ioi.extent_and_medium, ioi_en.extent_and_medium) as extent_and_medium'),
                DB::raw('COALESCE(ioi.archival_history, ioi_en.archival_history) as archival_history'),
                DB::raw('COALESCE(ioi.acquisition, ioi_en.acquisition) as acquisition'),
                DB::raw('COALESCE(ioi.physical_characteristics, ioi_en.physical_characteristics) as physical_characteristics'),
            ])
            ->first();

        if ($obj) {
            // Enhance object with additional data needed by CIDOC service
            $obj = $this->enhanceObjectForExport($obj);
        }

        return $obj;
    }

    /**
     * Enhance object with additional data needed for CIDOC export
     */
    protected function enhanceObjectForExport($obj)
    {
        $culture = $this->getUser()->getCulture() ?? 'en';

        // Get creators
        $obj->creators = $this->getObjectCreators($obj->id);

        // Get level of description name
        $obj->level_of_description_name = $this->getTermName($obj->level_of_description_id ?? null);

        // Get digital objects
        $obj->digitalObjects = DB::table('digital_object')
            ->where('object_id', $obj->id)
            ->get()
            ->toArray();

        // Get subject access points
        $obj->subjectAccessPoints = DB::table('object_term_relation as otr')
            ->join('term as t', 'otr.term_id', '=', 't.id')
            ->leftJoin('term_i18n as ti', function ($join) use ($culture) {
                $join->on('t.id', '=', 'ti.id')
                    ->where('ti.culture', '=', $culture);
            })
            ->leftJoin('term_i18n as ti_en', function ($join) {
                $join->on('t.id', '=', 'ti_en.id')
                    ->where('ti_en.culture', '=', 'en');
            })
            ->where('otr.object_id', $obj->id)
            ->where('t.taxonomy_id', 35) // TAXONOMY_SUBJECT_ID
            ->select([
                't.id',
                DB::raw('COALESCE(ti.name, ti_en.name) as name'),
            ])
            ->get()
            ->toArray();

        // Get place access points
        $obj->placeAccessPoints = DB::table('object_term_relation as otr')
            ->join('term as t', 'otr.term_id', '=', 't.id')
            ->leftJoin('term_i18n as ti', function ($join) use ($culture) {
                $join->on('t.id', '=', 'ti.id')
                    ->where('ti.culture', '=', $culture);
            })
            ->leftJoin('term_i18n as ti_en', function ($join) {
                $join->on('t.id', '=', 'ti_en.id')
                    ->where('ti_en.culture', '=', 'en');
            })
            ->where('otr.object_id', $obj->id)
            ->where('t.taxonomy_id', 42) // TAXONOMY_PLACE_ID
            ->select([
                't.id',
                DB::raw('COALESCE(ti.name, ti_en.name) as name'),
            ])
            ->get()
            ->toArray();

        // Get museum data if exists
        $obj->museumData = DB::table('museum_object')
            ->where('information_object_id', $obj->id)
            ->first();

        // Get repository
        if ($obj->repository_id) {
            $obj->repository = DB::table('repository as r')
                ->leftJoin('actor_i18n as ai', function ($join) use ($culture) {
                    $join->on('r.id', '=', 'ai.id')
                        ->where('ai.culture', '=', $culture);
                })
                ->leftJoin('actor_i18n as ai_en', function ($join) {
                    $join->on('r.id', '=', 'ai_en.id')
                        ->where('ai_en.culture', '=', 'en');
                })
                ->leftJoin('slug', 'r.id', '=', 'slug.object_id')
                ->where('r.id', $obj->repository_id)
                ->select([
                    'r.id',
                    'slug.slug',
                    DB::raw('COALESCE(ai.authorized_form_of_name, ai_en.authorized_form_of_name) as name'),
                ])
                ->first();
        }

        // Get dates/events
        $obj->dates = $this->getObjectDates($obj->id);

        return $obj;
    }

    /**
     * Get creators for object
     */
    protected function getObjectCreators(int $objectId): array
    {
        $culture = $this->getUser()->getCulture() ?? 'en';
        $creationTypeId = 111; // TERM_CREATION_ID

        return DB::table('event as e')
            ->join('actor as a', 'e.actor_id', '=', 'a.id')
            ->leftJoin('actor_i18n as ai', function ($join) use ($culture) {
                $join->on('a.id', '=', 'ai.id')
                    ->where('ai.culture', '=', $culture);
            })
            ->leftJoin('actor_i18n as ai_en', function ($join) {
                $join->on('a.id', '=', 'ai_en.id')
                    ->where('ai_en.culture', '=', 'en');
            })
            ->leftJoin('slug', 'a.id', '=', 'slug.object_id')
            ->where('e.object_id', $objectId)
            ->where('e.type_id', $creationTypeId)
            ->select([
                'a.id',
                'slug.slug',
                DB::raw('COALESCE(ai.authorized_form_of_name, ai_en.authorized_form_of_name) as name'),
                DB::raw('COALESCE(ai.history, ai_en.history) as history'),
            ])
            ->get()
            ->toArray();
    }

    /**
     * Get dates for object
     */
    protected function getObjectDates(int $objectId): array
    {
        $culture = $this->getUser()->getCulture() ?? 'en';

        return DB::table('event as e')
            ->leftJoin('event_i18n as ei', function ($join) use ($culture) {
                $join->on('e.id', '=', 'ei.id')
                    ->where('ei.culture', '=', $culture);
            })
            ->leftJoin('event_i18n as ei_en', function ($join) {
                $join->on('e.id', '=', 'ei_en.id')
                    ->where('ei_en.culture', '=', 'en');
            })
            ->leftJoin('term_i18n as ti', function ($join) use ($culture) {
                $join->on('e.type_id', '=', 'ti.id')
                    ->where('ti.culture', '=', $culture);
            })
            ->leftJoin('term_i18n as ti_en', function ($join) {
                $join->on('e.type_id', '=', 'ti_en.id')
                    ->where('ti_en.culture', '=', 'en');
            })
            ->where('e.object_id', $objectId)
            ->whereNotNull('e.type_id')
            ->select([
                'e.id',
                'e.type_id',
                'e.start_date',
                'e.end_date',
                DB::raw('COALESCE(ei.date, ei_en.date) as date_display'),
                DB::raw('COALESCE(ti.name, ti_en.name) as type_name'),
            ])
            ->get()
            ->toArray();
    }

    /**
     * Get term name by ID
     */
    protected function getTermName(?int $termId): ?string
    {
        if (!$termId) {
            return null;
        }

        $culture = $this->getUser()->getCulture() ?? 'en';

        $name = DB::table('term_i18n')
            ->where('id', $termId)
            ->where('culture', $culture)
            ->value('name');

        if ($name === null) {
            $name = DB::table('term_i18n')
                ->where('id', $termId)
                ->where('culture', 'en')
                ->value('name');
        }

        return $name;
    }

    /**
     * Handle export download
     */
    protected function handleDownload($request)
    {
        $service = new arCIDOCExportService();
        $service->setFormat($this->format);
        $service->setIncludeLinkedData($this->includeLinkedData);

        $formatInfo = [
            arCIDOCExportService::FORMAT_JSONLD => ['ext' => 'jsonld', 'mime' => 'application/ld+json'],
            arCIDOCExportService::FORMAT_RDFXML => ['ext' => 'rdf', 'mime' => 'application/rdf+xml'],
            arCIDOCExportService::FORMAT_TURTLE => ['ext' => 'ttl', 'mime' => 'text/turtle'],
            arCIDOCExportService::FORMAT_NTRIPLES => ['ext' => 'nt', 'mime' => 'application/n-triples'],
            arCIDOCExportService::FORMAT_CSV => ['ext' => 'csv', 'mime' => 'text/csv'],
        ];

        $info = $formatInfo[$this->format] ?? $formatInfo[arCIDOCExportService::FORMAT_JSONLD];

        if ($this->objectSlug) {
            // Single object export
            $object = $this->getObjectBySlug($this->objectSlug);
            if (!$object) {
                $this->forward404();
            }
            $output = $service->exportObject($object);
            $filename = 'cidoc_' . $this->objectSlug . '.' . $info['ext'];
        } else {
            // Collection export
            $output = $service->exportCollection($this->repositoryId);
            $filename = 'cidoc_export_' . date('Y-m-d') . '.' . $info['ext'];
        }

        header('Content-Type: ' . $info['mime']);
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');

        echo $output;

        return sfView::NONE;
    }
}