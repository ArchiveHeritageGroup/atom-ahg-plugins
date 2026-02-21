<?php

use AtomFramework\Http\Controllers\AhgController;
/**
 * AHG Dropdown Management Actions
 *
 * Admin interface for managing controlled vocabularies (dropdowns).
 */
class ahgDropdownActions extends AhgController
{
    /**
     * Section labels for taxonomy grouping in Dropdown Manager
     */
    public const SECTION_LABELS = [
        'access_research'    => 'Access & Research',
        'ai'                 => 'AI & Automation',
        'condition'          => 'Condition & Conservation',
        'core'               => 'Core & System',
        'digital_media'      => 'Digital Assets & Media',
        'display_ui'         => 'Display & UI',
        'donor_agreement'    => 'Donors & Agreements',
        'exhibition_loan'    => 'Exhibitions & Loans',
        'export_import'      => 'Export, Import & Ingest',
        'federation'         => 'Federation',
        'finance'            => 'Finance & Valuation',
        'forms_metadata'     => 'Forms & Metadata',
        'heritage_monuments' => 'Heritage & Monuments',
        'integration'        => 'Integrations & Extensions',
        'people'             => 'People & Contacts',
        'preservation'       => 'Digital Preservation',
        'privacy_compliance' => 'Privacy & Compliance',
        'provenance_rights'  => 'Provenance & Rights',
        'reporting_workflow' => 'Reporting & Workflow',
        'reproduction'       => 'Reproduction & Publishing',
        'vendor'             => 'Vendors & Contracts',
        'uncategorized'      => 'Uncategorized',
    ];

    /**
     * Section icons for Dropdown Manager UI
     */
    public const SECTION_ICONS = [
        'access_research'    => 'fa-book-reader',
        'ai'                 => 'fa-robot',
        'condition'          => 'fa-clipboard-check',
        'core'               => 'fa-cogs',
        'digital_media'      => 'fa-photo-video',
        'display_ui'         => 'fa-desktop',
        'donor_agreement'    => 'fa-handshake',
        'exhibition_loan'    => 'fa-university',
        'export_import'      => 'fa-file-export',
        'federation'         => 'fa-project-diagram',
        'finance'            => 'fa-coins',
        'forms_metadata'     => 'fa-file-alt',
        'heritage_monuments' => 'fa-landmark',
        'integration'        => 'fa-plug',
        'people'             => 'fa-users',
        'preservation'       => 'fa-shield-alt',
        'privacy_compliance' => 'fa-user-shield',
        'provenance_rights'  => 'fa-balance-scale',
        'reporting_workflow' => 'fa-tasks',
        'reproduction'       => 'fa-copy',
        'vendor'             => 'fa-store',
        'uncategorized'      => 'fa-folder',
    ];
    /**
     * Pre-execute - require admin access
     */
    public function boot(): void
    {
        if (!$this->getUser()->isAdministrator()) {
            $this->forward('admin', 'secure');
        }

        // Initialize database and services
        $bootstrap = $this->config('sf_root_dir') . '/atom-framework/bootstrap.php';
        if (file_exists($bootstrap)) {
            require_once $bootstrap;
        }
        $taxonomyService = $this->config('sf_root_dir') . '/atom-ahg-plugins/ahgCorePlugin/lib/Services/AhgTaxonomyService.php';
        if (file_exists($taxonomyService)) {
            require_once $taxonomyService;
        }
    }

    /**
     * List all taxonomies grouped by section
     */
    public function executeIndex($request)
    {
        // Direct query to include taxonomy_section (not in base service)
        $taxonomies = \Illuminate\Database\Capsule\Manager::table('ahg_dropdown')
            ->select('taxonomy', 'taxonomy_label', 'taxonomy_section')
            ->selectRaw('COUNT(*) as term_count')
            ->where('is_active', 1)
            ->groupBy('taxonomy', 'taxonomy_label', 'taxonomy_section')
            ->orderBy('taxonomy_label')
            ->get()
            ->all();

        // Get term counts
        $termCounts = [];
        foreach ($taxonomies as $tax) {
            $termCounts[$tax->taxonomy] = $tax->term_count;
        }

        // Group taxonomies by section
        $sections = [];
        foreach ($taxonomies as $tax) {
            $section = $tax->taxonomy_section ?? 'uncategorized';
            if (!isset($sections[$section])) {
                $sections[$section] = [];
            }
            $sections[$section][] = $tax;
        }

        // Sort sections by label
        uksort($sections, function ($a, $b) {
            $labelA = self::SECTION_LABELS[$a] ?? $a;
            $labelB = self::SECTION_LABELS[$b] ?? $b;
            return strcasecmp($labelA, $labelB);
        });

        // Build section metadata
        $sectionMeta = [];
        foreach ($sections as $code => $items) {
            $sectionMeta[$code] = [
                'label' => self::SECTION_LABELS[$code] ?? ucfirst(str_replace('_', ' ', $code)),
                'icon'  => self::SECTION_ICONS[$code] ?? 'fa-folder',
                'count' => count($items),
            ];
        }

        // Get available sections for create modal
        $availableSections = [];
        foreach (self::SECTION_LABELS as $code => $label) {
            $availableSections[$code] = $label;
        }

        return $this->renderBlade('index', [
            'taxonomies' => $taxonomies,
            'termCounts' => $termCounts,
            'sections' => $sections,
            'sectionMeta' => $sectionMeta,
            'availableSections' => $availableSections,
        ]);
    }

    /**
     * View/edit a single taxonomy's terms
     */
    public function executeEdit($request)
    {
        $taxonomy = $request->getParameter('taxonomy');

        if (!$taxonomy) {
            $this->forward404();
        }

        $service = new \ahgCorePlugin\Services\AhgTaxonomyService();

        $taxonomyLabel = $service->getTaxonomyLabel($taxonomy);

        if (!$taxonomyLabel) {
            $this->forward404();
        }

        // Get all terms including inactive
        $terms = \Illuminate\Database\Capsule\Manager::table('ahg_dropdown')
            ->where('taxonomy', $taxonomy)
            ->orderBy('sort_order')
            ->orderBy('label')
            ->get();

        return $this->renderBlade('edit', [
            'taxonomy' => $taxonomy,
            'taxonomyLabel' => $taxonomyLabel,
            'terms' => $terms,
        ]);
    }

    /**
     * Create new taxonomy (AJAX)
     */
    public function executeCreate($request)
    {
        $this->getResponse()->setContentType('application/json');

        if (!$request->isMethod('post')) {
            return $this->renderText(json_encode(['success' => false, 'error' => 'Invalid method']));
        }

        $code = $this->sanitizeCode($request->getParameter('code'));
        $label = trim($request->getParameter('label'));

        if (empty($code) || empty($label)) {
            return $this->renderText(json_encode(['success' => false, 'error' => 'Code and label are required']));
        }

        $section = $this->sanitizeCode($request->getParameter('section', 'uncategorized')) ?: 'uncategorized';

        $service = new \ahgCorePlugin\Services\AhgTaxonomyService();

        if ($service->taxonomyExists($code)) {
            return $this->renderText(json_encode(['success' => false, 'error' => 'Taxonomy code already exists']));
        }

        // Create with a default term
        $service->createTaxonomy($code, $label, [
            ['code' => 'default', 'label' => 'Default', 'is_default' => 1]
        ]);

        // Assign section
        \Illuminate\Database\Capsule\Manager::table('ahg_dropdown')
            ->where('taxonomy', $code)
            ->update(['taxonomy_section' => $section]);

        return $this->renderText(json_encode(['success' => true, 'taxonomy' => $code]));
    }

    /**
     * Rename taxonomy (AJAX)
     */
    public function executeRename($request)
    {
        $this->getResponse()->setContentType('application/json');

        if (!$request->isMethod('post')) {
            return $this->renderText(json_encode(['success' => false, 'error' => 'Invalid method']));
        }

        $taxonomy = $request->getParameter('taxonomy');
        $newLabel = trim($request->getParameter('label'));

        if (empty($taxonomy) || empty($newLabel)) {
            return $this->renderText(json_encode(['success' => false, 'error' => 'Taxonomy and label are required']));
        }

        $service = new \ahgCorePlugin\Services\AhgTaxonomyService();
        $result = $service->renameTaxonomy($taxonomy, $newLabel);

        return $this->renderText(json_encode(['success' => $result]));
    }

    /**
     * Delete taxonomy (AJAX)
     */
    public function executeDeleteTaxonomy($request)
    {
        $this->getResponse()->setContentType('application/json');

        if (!$request->isMethod('post')) {
            return $this->renderText(json_encode(['success' => false, 'error' => 'Invalid method']));
        }

        $taxonomy = $request->getParameter('taxonomy');
        $hardDelete = $request->getParameter('hard_delete') === '1';

        if (empty($taxonomy)) {
            return $this->renderText(json_encode(['success' => false, 'error' => 'Taxonomy is required']));
        }

        $service = new \ahgCorePlugin\Services\AhgTaxonomyService();
        $result = $service->deleteTaxonomy($taxonomy, $hardDelete);

        return $this->renderText(json_encode(['success' => $result]));
    }

    /**
     * Add term to taxonomy (AJAX)
     */
    public function executeAddTerm($request)
    {
        $this->getResponse()->setContentType('application/json');

        if (!$request->isMethod('post')) {
            return $this->renderText(json_encode(['success' => false, 'error' => 'Invalid method']));
        }

        $taxonomy = $request->getParameter('taxonomy');
        $taxonomyLabel = $request->getParameter('taxonomy_label');
        $code = $this->sanitizeCode($request->getParameter('code'));
        $label = trim($request->getParameter('label'));
        $color = $request->getParameter('color') ?: null;
        $icon = $request->getParameter('icon') ?: null;

        if (empty($taxonomy) || empty($code) || empty($label)) {
            return $this->renderText(json_encode(['success' => false, 'error' => 'Required fields missing']));
        }

        $service = new \ahgCorePlugin\Services\AhgTaxonomyService();

        // Check if code already exists in this taxonomy
        if ($service->getTermByCode($taxonomy, $code)) {
            return $this->renderText(json_encode(['success' => false, 'error' => 'Term code already exists in this taxonomy']));
        }

        $id = $service->addTerm($taxonomy, $taxonomyLabel, $code, $label, [
            'color' => $color,
            'icon' => $icon,
        ]);

        return $this->renderText(json_encode(['success' => true, 'id' => $id]));
    }

    /**
     * Update term (AJAX)
     */
    public function executeUpdateTerm($request)
    {
        $this->getResponse()->setContentType('application/json');

        if (!$request->isMethod('post')) {
            return $this->renderText(json_encode(['success' => false, 'error' => 'Invalid method']));
        }

        $id = (int) $request->getParameter('id');
        $data = [];

        if ($request->hasParameter('label')) {
            $data['label'] = trim($request->getParameter('label'));
        }
        if ($request->hasParameter('color')) {
            $data['color'] = $request->getParameter('color') ?: null;
        }
        if ($request->hasParameter('icon')) {
            $data['icon'] = $request->getParameter('icon') ?: null;
        }
        if ($request->hasParameter('sort_order')) {
            $data['sort_order'] = (int) $request->getParameter('sort_order');
        }
        if ($request->hasParameter('is_default')) {
            $data['is_default'] = $request->getParameter('is_default') ? 1 : 0;
        }
        if ($request->hasParameter('is_active')) {
            $data['is_active'] = $request->getParameter('is_active') ? 1 : 0;
        }

        if (empty($data)) {
            return $this->renderText(json_encode(['success' => false, 'error' => 'No data to update']));
        }

        $service = new \ahgCorePlugin\Services\AhgTaxonomyService();
        $result = $service->updateTerm($id, $data);

        return $this->renderText(json_encode(['success' => $result]));
    }

    /**
     * Delete term (AJAX)
     */
    public function executeDeleteTerm($request)
    {
        $this->getResponse()->setContentType('application/json');

        if (!$request->isMethod('post')) {
            return $this->renderText(json_encode(['success' => false, 'error' => 'Invalid method']));
        }

        $id = (int) $request->getParameter('id');
        $hardDelete = $request->getParameter('hard_delete') === '1';

        if (!$id) {
            return $this->renderText(json_encode(['success' => false, 'error' => 'Term ID required']));
        }

        $service = new \ahgCorePlugin\Services\AhgTaxonomyService();
        $result = $service->deleteTerm($id, $hardDelete);

        return $this->renderText(json_encode(['success' => $result]));
    }

    /**
     * Reorder terms (AJAX)
     */
    public function executeReorder($request)
    {
        $this->getResponse()->setContentType('application/json');

        if (!$request->isMethod('post')) {
            return $this->renderText(json_encode(['success' => false, 'error' => 'Invalid method']));
        }

        $order = $request->getParameter('order', []);

        if (empty($order) || !is_array($order)) {
            return $this->renderText(json_encode(['success' => false, 'error' => 'Order data required']));
        }

        $service = new \ahgCorePlugin\Services\AhgTaxonomyService();

        foreach ($order as $position => $id) {
            $service->updateTerm((int) $id, ['sort_order' => ($position + 1) * 10]);
        }

        return $this->renderText(json_encode(['success' => true]));
    }

    /**
     * Set default term (AJAX)
     */
    public function executeSetDefault($request)
    {
        $this->getResponse()->setContentType('application/json');

        if (!$request->isMethod('post')) {
            return $this->renderText(json_encode(['success' => false, 'error' => 'Invalid method']));
        }

        $id = (int) $request->getParameter('id');
        $taxonomy = $request->getParameter('taxonomy');

        if (!$id || !$taxonomy) {
            return $this->renderText(json_encode(['success' => false, 'error' => 'ID and taxonomy required']));
        }

        // Clear all defaults for this taxonomy
        \Illuminate\Database\Capsule\Manager::table('ahg_dropdown')
            ->where('taxonomy', $taxonomy)
            ->update(['is_default' => 0]);

        // Set new default
        $service = new \ahgCorePlugin\Services\AhgTaxonomyService();
        $result = $service->updateTerm($id, ['is_default' => 1]);

        return $this->renderText(json_encode(['success' => $result]));
    }

    /**
     * Move taxonomy to a different section (AJAX)
     */
    public function executeMoveSection($request)
    {
        $this->getResponse()->setContentType('application/json');

        if (!$request->isMethod('post')) {
            return $this->renderText(json_encode(['success' => false, 'error' => 'Invalid method']));
        }

        $taxonomy = $request->getParameter('taxonomy');
        $section = $this->sanitizeCode($request->getParameter('section', 'uncategorized')) ?: 'uncategorized';

        if (empty($taxonomy)) {
            return $this->renderText(json_encode(['success' => false, 'error' => 'Taxonomy is required']));
        }

        $result = \Illuminate\Database\Capsule\Manager::table('ahg_dropdown')
            ->where('taxonomy', $taxonomy)
            ->update(['taxonomy_section' => $section]);

        return $this->renderText(json_encode(['success' => true, 'rows' => $result]));
    }

    /**
     * Sanitize code to lowercase alphanumeric with underscores
     */
    private function sanitizeCode(string $code): string
    {
        $code = strtolower(trim($code));
        $code = preg_replace('/[^a-z0-9_]/', '_', $code);
        $code = preg_replace('/_+/', '_', $code);
        return trim($code, '_');
    }
}
