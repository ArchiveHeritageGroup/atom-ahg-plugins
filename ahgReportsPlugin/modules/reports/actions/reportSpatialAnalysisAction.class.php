<?php

use AtomFramework\Http\Controllers\AhgController;

/**
 * Spatial Analysis Export Report Action
 *
 * Exports site records with GPS coordinates for GIS/spatial analysis.
 * Configurable filtering by place, subject, and coordinate source.
 */
class reportsReportSpatialAnalysisAction extends AhgController
{
    public function execute($request)
    {
        // Check authentication
        if (!$this->getUser()->isAuthenticated()) {
            $this->redirect('user/login');
        }

        // Initialize form
        $this->form = new sfForm();

        // Coordinate source options
        $this->coordinateSources = [
            'property' => 'Property Table (custom fields)',
            'nmmz_site' => 'NMMZ Archaeological Site Table',
            'dam_metadata' => 'DAM IPTC Metadata (from images)',
            'contact_info' => 'Repository Contact Information',
        ];

        // Default tradition terms
        $this->defaultPaintedTerms = "brush painted\nfinger painted\npainted\npaint\npigment\nochre\nSan painting\nrock painting";
        $this->defaultEngravedTerms = "engraving\nengraved\npecking\npecked\nincising\nincised\nscratched\nabraded\nKhoekhoen\nKhoi\ngeometric";

        // Get available places (countries)
        $this->availablePlaces = $this->getAvailablePlaces();

        // Get available levels of description
        $this->availableLevels = $this->getAvailableLevels();

        // Handle export request
        if ($request->isMethod('post') || $request->getParameter('export')) {
            return $this->handleExport($request);
        }

        // Preview mode - show sample
        $this->previewData = null;
        if ($request->getParameter('preview')) {
            $this->previewData = $this->runExport($request, 10);
        }
    }

    /**
     * Handle CSV export
     */
    protected function handleExport($request)
    {
        $result = $this->runExport($request, 0);

        if ($request->getParameter('format') === 'json') {
            $this->getResponse()->setContentType('application/json');
            return $this->renderText(json_encode($result, JSON_PRETTY_PRINT));
        }

        // Generate CSV
        require_once sfConfig::get('sf_plugins_dir') . '/ahgReportsPlugin/lib/SpatialAnalysisExport.php';
        $exporter = new \AhgReports\SpatialAnalysisExport();
        $csv = $exporter->toCsv($result);

        // Set headers for download
        $filename = 'spatial_analysis_export_' . date('Y-m-d_His') . '.csv';
        $this->getResponse()->setHttpHeader('Content-Type', 'text/csv; charset=utf-8');
        $this->getResponse()->setHttpHeader('Content-Disposition', 'attachment; filename="' . $filename . '"');
        $this->getResponse()->setHttpHeader('Content-Length', strlen($csv));

        return $this->renderText($csv);
    }

    /**
     * Run the export with given parameters
     */
    protected function runExport($request, $limit = 0)
    {
        require_once sfConfig::get('sf_plugins_dir') . '/ahgReportsPlugin/lib/SpatialAnalysisExport.php';

        $exporter = new \AhgReports\SpatialAnalysisExport();

        // Configure coordinate source
        $coordinateSource = $request->getParameter('coordinate_source', 'property');
        $exporter->setCoordinateSource($coordinateSource);

        // Configure property names if using property source
        if ($coordinateSource === 'property') {
            $latProp = $request->getParameter('latitude_property', 'latitude');
            $lngProp = $request->getParameter('longitude_property', 'longitude');
            $exporter->setCoordinatePropertyNames($latProp, $lngProp);
        }

        // Configure painted terms
        $paintedTermsRaw = $request->getParameter('painted_terms', '');
        if (!empty($paintedTermsRaw)) {
            $paintedTerms = array_filter(array_map('trim', explode("\n", $paintedTermsRaw)));
            if (!empty($paintedTerms)) {
                $exporter->setPaintedTerms($paintedTerms);
            }
        }

        // Configure engraved terms
        $engravedTermsRaw = $request->getParameter('engraved_terms', '');
        if (!empty($engravedTermsRaw)) {
            $engravedTerms = array_filter(array_map('trim', explode("\n", $engravedTermsRaw)));
            if (!empty($engravedTerms)) {
                $exporter->setEngravedTerms($engravedTerms);
            }
        }

        // Build export options
        $options = [
            'topLevelOnly' => (bool) $request->getParameter('top_level_only', true),
            'requireCoordinates' => (bool) $request->getParameter('require_coordinates', true),
            'limit' => $limit,
        ];

        // Place filters
        $places = $request->getParameter('places', []);
        if (!empty($places)) {
            $options['placeTerms'] = is_array($places) ? $places : [$places];
        }

        // Subject filters
        $subjectTermsRaw = $request->getParameter('subject_filter_terms', '');
        if (!empty($subjectTermsRaw)) {
            $options['subjectTerms'] = array_filter(array_map('trim', explode("\n", $subjectTermsRaw)));
        }

        // Level of description filter
        $level = $request->getParameter('level_of_description', '');
        if (!empty($level)) {
            $options['levelOfDescription'] = $level;
        }

        return $exporter->export($options);
    }

    /**
     * Get available place terms (focusing on countries)
     */
    protected function getAvailablePlaces()
    {
        \AhgCore\Core\AhgDb::init();

        $places = \Illuminate\Database\Capsule\Manager::table('term as t')
            ->join('term_i18n as ti', function ($join) {
                $join->on('t.id', '=', 'ti.id')
                    ->where('ti.culture', '=', 'en');
            })
            ->where('t.taxonomy_id', '=', 42) // Places taxonomy
            ->whereExists(function ($query) {
                $query->select(\Illuminate\Database\Capsule\Manager::raw(1))
                    ->from('object_term_relation as otr')
                    ->whereRaw('otr.term_id = t.id');
            })
            ->orderBy('ti.name')
            ->pluck('ti.name', 't.id')
            ->toArray();

        return $places;
    }

    /**
     * Get available levels of description
     */
    protected function getAvailableLevels()
    {
        \AhgCore\Core\AhgDb::init();

        $levels = \Illuminate\Database\Capsule\Manager::table('term as t')
            ->join('term_i18n as ti', function ($join) {
                $join->on('t.id', '=', 'ti.id')
                    ->where('ti.culture', '=', 'en');
            })
            ->where('t.taxonomy_id', '=', 34) // Level of description taxonomy
            ->orderBy('ti.name')
            ->pluck('ti.name', 't.id')
            ->toArray();

        return $levels;
    }
}
