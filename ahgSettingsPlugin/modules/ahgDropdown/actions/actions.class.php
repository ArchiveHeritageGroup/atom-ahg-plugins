<?php

/**
 * AHG Dropdown Management Actions
 *
 * Admin interface for managing controlled vocabularies (dropdowns).
 */
class ahgDropdownActions extends AhgActions
{
    /**
     * Pre-execute - require admin access
     */
    public function preExecute()
    {
        parent::preExecute();

        if (!$this->context->user->isAdministrator()) {
            $this->forward('admin', 'secure');
        }

        // Initialize database and services
        $bootstrap = sfConfig::get('sf_root_dir') . '/atom-framework/bootstrap.php';
        if (file_exists($bootstrap)) {
            require_once $bootstrap;
        }
        $taxonomyService = sfConfig::get('sf_root_dir') . '/atom-ahg-plugins/ahgCorePlugin/lib/Services/AhgTaxonomyService.php';
        if (file_exists($taxonomyService)) {
            require_once $taxonomyService;
        }
    }

    /**
     * List all taxonomies
     */
    public function executeIndex(sfWebRequest $request)
    {
        $service = new \ahgCorePlugin\Services\AhgTaxonomyService();
        $this->taxonomies = $service->getAllTaxonomies();

        // Get term counts
        $this->termCounts = [];
        foreach ($this->taxonomies as $tax) {
            $this->termCounts[$tax->taxonomy] = $service->getTermCount($tax->taxonomy);
        }
    }

    /**
     * View/edit a single taxonomy's terms
     */
    public function executeEdit(sfWebRequest $request)
    {
        $taxonomy = $request->getParameter('taxonomy');

        if (!$taxonomy) {
            $this->forward404();
        }

        $service = new \ahgCorePlugin\Services\AhgTaxonomyService();

        $this->taxonomy = $taxonomy;
        $this->taxonomyLabel = $service->getTaxonomyLabel($taxonomy);

        if (!$this->taxonomyLabel) {
            $this->forward404();
        }

        // Get all terms including inactive
        $this->terms = \Illuminate\Database\Capsule\Manager::table('ahg_dropdown')
            ->where('taxonomy', $taxonomy)
            ->orderBy('sort_order')
            ->orderBy('label')
            ->get();
    }

    /**
     * Create new taxonomy (AJAX)
     */
    public function executeCreate(sfWebRequest $request)
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

        $service = new \ahgCorePlugin\Services\AhgTaxonomyService();

        if ($service->taxonomyExists($code)) {
            return $this->renderText(json_encode(['success' => false, 'error' => 'Taxonomy code already exists']));
        }

        // Create with a default term
        $service->createTaxonomy($code, $label, [
            ['code' => 'default', 'label' => 'Default', 'is_default' => 1]
        ]);

        return $this->renderText(json_encode(['success' => true, 'taxonomy' => $code]));
    }

    /**
     * Rename taxonomy (AJAX)
     */
    public function executeRename(sfWebRequest $request)
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
    public function executeDeleteTaxonomy(sfWebRequest $request)
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
    public function executeAddTerm(sfWebRequest $request)
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
    public function executeUpdateTerm(sfWebRequest $request)
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
    public function executeDeleteTerm(sfWebRequest $request)
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
    public function executeReorder(sfWebRequest $request)
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
    public function executeSetDefault(sfWebRequest $request)
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
