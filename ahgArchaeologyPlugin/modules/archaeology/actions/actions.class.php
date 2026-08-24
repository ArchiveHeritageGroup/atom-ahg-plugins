<?php

use AhgArchaeologyPlugin\Services\ArchaeologyService;
use AtomFramework\Http\Controllers\AhgController;

/**
 * Archaeology: sites, stratigraphic contexts and finds.
 *
 * Access is declared in config/security.yml - browse and view for contributors
 * upward, edit for editors. CSRF is enforced by AhgController::preExecute().
 *
 * RESERVED ACTION PROPERTY NAMES. sfComponent::initialize() assigns $this->context,
 * ->request, ->response, ->dispatcher, ->varHolder, ->moduleName, ->actionName and
 * ->requestParameterHolder as real properties. __set() only fires for INACCESSIBLE
 * properties, so `$this->context = $row` writes straight to Symfony's own context
 * object: the template variable is never created (it reads as null) AND the request's
 * context is destroyed. The context record is therefore carried as $this->ctx. Same
 * class of trap as $csrf_token in AhgController.
 *
 * Phase 1 of atom-ahg-plugins#190. The relationship editor, Harris Matrix, CSV
 * import and PDF context sheet arrive in later phases; the routes and templates
 * here are shaped to receive them without moving.
 */
class archaeologyActions extends AhgController
{
    private ArchaeologyService $service;

    public function boot(): void
    {
        $this->service = new ArchaeologyService();
    }

    public function executeIndex($request)
    {
        $this->guardInstalled();

        $this->stats = $this->service->statistics();
        $this->recentSites = $this->service->sites([], 1, 10)['rows'];
    }

    // -----------------------------------------------------------------------
    // Sites
    // -----------------------------------------------------------------------

    public function executeSites($request)
    {
        $this->guardInstalled();

        $result = $this->service->sites(
            [
                'q' => trim((string) $request->getParameter('q', '')),
                'region' => $request->getParameter('region'),
                'excavated' => $request->getParameter('excavated', ''),
            ],
            (int) $request->getParameter('page', 1)
        );

        $this->sites = $result['rows'];
        $this->total = $result['total'];
        $this->page = $result['page'];
        $this->perPage = $result['perPage'];
        $this->q = $request->getParameter('q', '');
        $this->excavated = $request->getParameter('excavated', '');
    }

    /**
     * Where the sites are.
     *
     * Spatial search runs against the database, not the search index: AtoM's
     * information_object mapping carries no geo field, and adding one means
     * editing base AtoM's config/search.yml.
     */
    public function executeMap($request)
    {
        $this->guardInstalled();

        $this->coverage = $this->service->spatialCoverage();
        $this->near = null;
        $this->lat = trim((string) $request->getParameter('lat', ''));
        $this->lng = trim((string) $request->getParameter('lng', ''));
        $this->radius = trim((string) $request->getParameter('radius', ''));

        if ('' !== $this->lat && '' !== $this->lng && is_numeric($this->lat) && is_numeric($this->lng)) {
            $radius = is_numeric($this->radius) ? max(0.1, min(20000, (float) $this->radius)) : 50.0;
            $this->radius = (string) $radius;
            $this->near = $this->service->sitesNear((float) $this->lat, (float) $this->lng, $radius);
            $this->sites = $this->near;
        } else {
            $this->sites = $this->service->sitesWithCoordinates();
        }
    }

    public function executeSite($request)
    {
        $this->guardInstalled();

        $this->site = $this->service->site((int) $request->getParameter('id'));

        if (!$this->site) {
            $this->forward404('No such site.');

            return;
        }

        $this->contexts = $this->service->contextsForSite((int) $this->site->id);
    }

    public function executeSiteEdit($request)
    {
        $this->guardInstalled();

        $id = $request->getParameter('id') ? (int) $request->getParameter('id') : null;
        $this->site = null;

        if (null !== $id) {
            $this->site = $this->service->site($id);

            if (!$this->site) {
                $this->forward404('No such site.');

                return;
            }
        }

        if ($request->isMethod('post')) {
            $savedId = $this->service->saveSite(
                $request->getParameterHolder()->getAll(),
                $id
            );

            $this->redirect('/archaeology/site/'.$savedId);

            return;
        }

        $this->vocabularies = $this->service->vocabularies();
    }

    // -----------------------------------------------------------------------
    // Contexts
    // -----------------------------------------------------------------------

    public function executeContexts($request)
    {
        $this->guardInstalled();

        $siteId = (int) $request->getParameter('siteId');
        $this->site = $this->service->site($siteId);

        if (!$this->site) {
            $this->forward404('No such site.');

            return;
        }

        $this->contexts = $this->service->contextsForSite($siteId);
        $this->matrix = $this->service->harrisMatrix($siteId);
        $this->service_ = $this->service;
    }

    /**
     * The dig plan: a scaled section per trench, plus where the site is.
     *
     * Both views are rendered server-side as markup and inline SVG. No charting
     * library, no map tiles, no inline style attributes - CSP nonces cover style
     * ELEMENTS but never style ATTRIBUTES, which is exactly what collapses the
     * IIIF viewer on this instance.
     */
    public function executePlan($request)
    {
        $this->guardInstalled();

        $siteId = (int) $request->getParameter('siteId');
        $this->site = $this->service->site($siteId);

        if (!$this->site) {
            $this->forward404('No such site.');

            return;
        }

        // Filters arrive as GET parameters and the whole drawing is re-rendered
        // server-side. No client-side state, and nothing for CSP to block.
        $this->filters = [
            'trenches' => (array) $request->getParameter('trench', []),
            'types' => (array) $request->getParameter('type', []),
            'features' => null === $request->getParameter('applied') ? true : (bool) $request->getParameter('features'),
            'min' => trim((string) $request->getParameter('min', '')),
            'max' => trim((string) $request->getParameter('max', '')),
        ];
        $this->exaggeration = max(1.0, min(4.0, (float) $request->getParameter('exaggeration', 1)));

        $this->plan = $this->service->sitePlan($siteId, $this->filters);
        $this->position = $this->service->sitePosition($siteId);
        $this->service_ = $this->service;
    }

    /**
     * CSV import of contexts and their relationships.
     *
     * The preview is a real run that is rolled back, not a simulation: it reports
     * the counts and warnings the commit would produce, because a simulation that
     * diverges from the real thing is worse than no preview.
     */
    public function executeImport($request)
    {
        $this->guardInstalled();

        $siteId = (int) $request->getParameter('siteId');
        $this->site = $this->service->site($siteId);

        if (!$this->site) {
            $this->forward404('No such site.');

            return;
        }

        $this->summary = null;
        $this->error = null;
        $this->kind = 'contexts';
        $this->otherSites = [];
        $this->lstUnits = 0;
        $this->lstContemporary = 0;

        if ($request->isMethod('post')) {
            $file = $request->getFiles('csv');

            if (empty($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
                $this->error = 'No file was uploaded.';

                return;
            }

            // Three shapes of file land here. Contexts is the original; the other
            // two bring relationships from tools that already hold a dig's
            // stratigraphy, which we could previously only export, never ingest.
            $kind = (string) $request->getParameter('kind', 'contexts');
            $commit = (bool) $request->getParameter('commit');
            $this->kind = $kind;

            if ('relationships' === $kind || 'lst' === $kind) {
                if ('lst' === $kind) {
                    $parsed = $this->service->parseLst($file['tmp_name']);
                    $this->lstUnits = $parsed['units'];
                    $this->lstContemporary = $parsed['contemporary'];
                } else {
                    $parsed = $this->service->parsePhaserCsv(
                        $file['tmp_name'],
                        (string) $this->site->site_number
                    );
                    $this->otherSites = $parsed['other_sites'];
                }

                if ($parsed['error']) {
                    $this->error = $parsed['error'];

                    return;
                }

                if (!$parsed['rows']) {
                    $this->error = 'No relationships were found in the file.';

                    return;
                }

                $this->summary = $this->service->importRelationshipsCsv($siteId, $parsed['rows'], $commit);
                $this->rowCount = count($parsed['rows']);

                return;
            }

            $parsed = $this->service->parseCsv($file['tmp_name']);

            if ($parsed['error']) {
                $this->error = $parsed['error'];

                return;
            }

            if (!$parsed['rows']) {
                $this->error = 'The file has a header but no rows.';

                return;
            }

            $this->summary = $this->service->importContextsCsv(
                $siteId,
                $parsed['rows'],
                $commit
            );
            $this->rowCount = count($parsed['rows']);
        }
    }

    /** A ready-to-fill CSV template with the full header and a worked example. */
    public function executeImportTemplate($request)
    {
        $this->guardInstalled();

        $siteId = (int) $request->getParameter('siteId');
        $site = $this->service->site($siteId);

        if (!$site) {
            $this->forward404('No such site.');

            return;
        }

        $response = $this->getResponse();
        $response->setContentType('text/csv');
        $response->setHttpHeader(
            'Content-Disposition',
            'attachment; filename="contexts-'.preg_replace('/[^A-Za-z0-9_-]/', '', (string) $site->site_number).'.csv"'
        );

        return $this->renderText($this->service->csvTemplate());
    }

    /**
     * Printable context sheet.
     *
     * Rendered through dompdf where it is available, and as a styled HTML page
     * where it is not, so an instance without the library still produces
     * something printable instead of an error.
     */
    /**
     * Export a site's stratigraphy.
     *
     * Three formats, because they answer different questions. The data package is
     * the interchange an archaeologist can hand to another tool; DOT is for anyone
     * who wants to draw it themselves; the Phaser CSV is for the MATRIX project's
     * analysis tool.
     */
    public function executeExport($request)
    {
        $this->guardInstalled();

        $siteId = (int) $request->getParameter('siteId');
        $site = $this->service->site($siteId);

        if (!$site) {
            $this->forward404('No such site.');

            return sfView::NONE;
        }

        $format = (string) $request->getParameter('format', 'datapackage');
        $stem = preg_replace('/[^A-Za-z0-9_-]/', '', (string) $site->site_number) ?: 'site';
        $response = $this->getResponse();

        if ('dot' === $format) {
            return $this->sendFile($response, $this->service->exportDot($siteId), 'text/vnd.graphviz', $stem.'.dot');
        }

        if ('phaser' === $format) {
            return $this->sendFile($response, $this->service->exportPhaserCsv($siteId), 'text/csv', $stem.'-phaser.csv');
        }

        $files = $this->service->exportDataPackage($siteId);

        // A data package is several files, so it has to travel as one. ZipArchive
        // needs a real path, hence the temp file - removed on the way out whatever
        // happens, so a failed export does not leave the stratigraphy in /tmp.
        if (!class_exists('\ZipArchive')) {
            $this->getUser()->setFlash('error', 'The zip extension is not available, so the data package cannot be assembled.');
            $this->redirect('@archaeology_contexts?siteId='.$siteId);

            return sfView::NONE;
        }

        $tmp = tempnam(sys_get_temp_dir(), 'hmdp');

        try {
            $zip = new \ZipArchive();
            $zip->open($tmp, \ZipArchive::OVERWRITE);

            foreach ($files as $name => $contents) {
                $zip->addFromString($name, $contents);
            }

            $zip->close();

            return $this->sendFile($response, (string) file_get_contents($tmp), 'application/zip', $stem.'-datapackage.zip');
        } finally {
            @unlink($tmp);
        }
    }

    private function sendFile($response, string $body, string $type, string $filename)
    {
        $response->setContentType($type);
        $response->setHttpHeader('Content-Disposition', 'attachment; filename="'.$filename.'"');
        $response->setContent($body);
        $response->send();

        return sfView::NONE;
    }

    public function executeContextPdf($request)
    {
        $this->guardInstalled();

        $ctx = $this->service->context((int) $request->getParameter('id'));

        if (!$ctx) {
            $this->forward404('No such context.');

            return;
        }

        $html = $this->service->contextSheetHtml((int) $ctx->id);
        $name = 'context-'.preg_replace('/[^A-Za-z0-9_-]/', '', (string) $ctx->context_number).'.pdf';

        if (class_exists('\\Dompdf\\Dompdf')) {
            $dompdf = new \Dompdf\Dompdf([
                'isRemoteEnabled' => false,
                'isHtml5ParserEnabled' => true,
                'defaultFont' => 'DejaVu Sans',
            ]);
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();

            $response = $this->getResponse();
            $response->setContentType('application/pdf');
            $response->setHttpHeader('Content-Disposition', 'attachment; filename="'.$name.'"');

            return $this->renderText($dompdf->output());
        }

        $this->getResponse()->setContentType('text/html');

        return $this->renderText($html);
    }

    public function executeContext($request)
    {
        $this->guardInstalled();

        $this->ctx = $this->service->context((int) $request->getParameter('id'));

        if (!$this->ctx) {
            $this->forward404('No such context.');

            return;
        }

        $this->finds = $this->service->findsForContext((int) $this->ctx->id);
        $this->relationships = $this->service->relationshipsForContext((int) $this->ctx->id);
        $this->relatedChoices = $this->service->contextPickList(
            (int) $this->ctx->site_id,
            (int) $this->ctx->id
        );
        $this->relTypes = ArchaeologyService::REL_TYPES;
        $this->service_ = $this->service;
    }

    /**
     * Add a stratigraphic relationship.
     *
     * The service writes the reciprocal edge and refuses anything that would make
     * the sequence impossible; this only reports the outcome. A rejected loop is
     * not an error condition - it is the feature working - so it comes back as a
     * warning on the context sheet rather than an exception.
     */
    public function executeRelationshipStore($request)
    {
        $this->guardInstalled();

        $contextId = (int) $request->getParameter('id');
        $context = $this->service->context($contextId);

        if (!$context) {
            $this->forward404('No such context.');

            return;
        }

        $result = $this->service->addRelationship(
            $contextId,
            (int) $request->getParameter('related_context_id'),
            (string) $request->getParameter('relationship_type'),
            trim((string) $request->getParameter('note', '')) ?: null
        );

        if (!($result['ok'] ?? false)) {
            $this->getUser()->setFlash('error', $result['error'] ?? 'The relationship could not be added.');
        }

        $this->redirect('/archaeology/context/'.$contextId);
    }

    /** Remove a relationship and its mirror. */
    public function executeRelationshipDelete($request)
    {
        $this->guardInstalled();

        $contextId = (int) $request->getParameter('id');

        $this->service->removeRelationship((int) $request->getParameter('relId'));

        $this->redirect('/archaeology/context/'.$contextId);
    }

    public function executeContextEdit($request)
    {
        $this->guardInstalled();

        $id = $request->getParameter('id') ? (int) $request->getParameter('id') : null;
        $this->ctx = null;
        $siteId = (int) $request->getParameter('siteId', 0);

        if (null !== $id) {
            $this->ctx = $this->service->context($id);

            if (!$this->ctx) {
                $this->forward404('No such context.');

                return;
            }

            $siteId = (int) $this->ctx->site_id;
        }

        if ($siteId <= 0) {
            // A context with no site is not a context. Send the user to pick one
            // rather than rendering a form that cannot be saved.
            $this->redirect('/archaeology/sites');

            return;
        }

        $this->site = $this->service->site($siteId);

        if (!$this->site) {
            $this->forward404('No such site.');

            return;
        }

        if ($request->isMethod('post')) {
            $data = $request->getParameterHolder()->getAll();
            $data['site_id'] = $siteId;

            $savedId = $this->service->saveContext($data, $id);

            $this->redirect('/archaeology/context/'.$savedId);

            return;
        }

        $this->siteId = $siteId;
        $this->vocabularies = $this->service->vocabularies();
    }

    // -----------------------------------------------------------------------
    // Finds
    // -----------------------------------------------------------------------

    public function executeObjects($request)
    {
        $this->guardInstalled();

        $result = $this->service->objects(
            [
                'q' => trim((string) $request->getParameter('q', '')),
                'site_id' => $request->getParameter('site_id'),
                'context_id' => $request->getParameter('context_id'),
                'no_context' => $request->getParameter('no_context'),
            ],
            (int) $request->getParameter('page', 1)
        );

        $this->finds = $result['rows'];
        $this->total = $result['total'];
        $this->page = $result['page'];
        $this->perPage = $result['perPage'];
        $this->q = $request->getParameter('q', '');
        $this->siteId = $request->getParameter('site_id');
        $this->noContext = $request->getParameter('no_context');
        $this->siteChoices = $this->service->sitePickList();
    }

    public function executeObject($request)
    {
        $this->guardInstalled();

        $this->find = $this->service->object((int) $request->getParameter('id'));

        if (!$this->find) {
            $this->forward404('No such find.');

            return;
        }
    }

    public function executeObjectEdit($request)
    {
        $this->guardInstalled();

        $id = $request->getParameter('id') ? (int) $request->getParameter('id') : null;
        $this->find = null;

        if (null !== $id) {
            $this->find = $this->service->object($id);

            if (!$this->find) {
                $this->forward404('No such find.');

                return;
            }
        }

        if ($request->isMethod('post')) {
            try {
                $savedId = $this->service->saveFind($request->getParameterHolder()->getAll(), $id);
            } catch (\InvalidArgumentException $e) {
                // A site and context that disagree is a user error, not a crash.
                $this->getUser()->setFlash('error', $e->getMessage());
                $this->redirect($id ? '/archaeology/find/'.$id.'/edit' : '/archaeology/find/add');

                return;
            }

            $this->redirect('/archaeology/find/'.$savedId);

            return;
        }

        $siteId = (int) ($this->find->site_id ?? $request->getParameter('siteId', 0));

        $this->siteChoices = $this->service->sitePickList();
        $this->contextChoices = $siteId ? $this->service->contextPickList($siteId) : [];
        $this->selectedSiteId = $siteId;
        $this->vocabularies = $this->service->vocabularies();
    }

    /**
     * A site's contexts as JSON, for the find form's context picker.
     *
     * Without this the picker only ever offers the contexts of whichever site was
     * selected when the page loaded, so changing the site silently leaves the
     * wrong list in place.
     */
    public function executeContextsJson($request)
    {
        $this->guardInstalled();

        $contexts = $this->service->contextPickList((int) $request->getParameter('siteId'));

        $out = [];

        foreach ($contexts as $c) {
            $out[] = ['id' => (int) $c->id, 'number' => (string) $c->context_number];
        }

        $this->getResponse()->setContentType('application/json');

        return $this->renderText(json_encode($out));
    }

    // -----------------------------------------------------------------------

    /**
     * Stop with a clear message when the schema is not installed.
     *
     * A module whose tables are missing otherwise fails as a database error deep
     * inside a query, which reads like a bug rather than an incomplete install.
     */
    private function guardInstalled(): void
    {
        if (!$this->service->installed()) {
            $this->forward404('The archaeology tables are not installed. Run the plugin install to create them.');
        }
    }
}
