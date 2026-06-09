<?php

/**
 * exhibitionSpace actions - PSIS Symfony port of heratio#146.
 *
 * Copyright (C) 2026 Johan Pieterse / Plain Sailing Information Systems
 * Licensed under the GNU Affero General Public License v3.0 or later.
 */

use AtomFramework\Http\Controllers\AhgController;

class exhibitionSpaceActions extends AhgController
{
    protected ?ExhibitionSpaceService $service = null;

    protected function getService(): ExhibitionSpaceService
    {
        if ($this->service === null) {
            require_once $this->config('sf_root_dir').'/plugins/ahgExhibitionPlugin/lib/Services/ExhibitionSpaceService.php';
            $this->service = new ExhibitionSpaceService();
        }
        return $this->service;
    }

    protected function requireAuth(): void
    {
        if (!$this->getUser()->isAuthenticated()) {
            $this->redirect('user/login');
        }
    }

    protected function requireAdmin(): void
    {
        $this->requireAuth();
        if (!$this->getUser()->hasCredential('administrator')) {
            $this->forward404('Administrator access required');
        }
    }

    public function executeBrowse($request)
    {
        $search = trim((string) $request->getParameter('subquery', ''));
        $this->rows = $this->getService()->browse($search);
        $this->search = $search;
        $this->capacityUnits = ExhibitionSpaceService::CAPACITY_UNITS;
    }

    public function executeShow($request)
    {
        $slug = $request->getParameter('slug');
        $space = $this->getService()->getBySlug($slug);
        if (!$space) {
            $this->forward404('Exhibition space not found');
        }
        $this->space = $space;
        $this->placements = $this->getService()->getPlacements((int) $space->id);
        $this->spaceTypes = ExhibitionSpaceService::SPACE_TYPES;
        $this->capacityUnits = ExhibitionSpaceService::CAPACITY_UNITS;
    }

    public function executeCreate($request)
    {
        $this->requireAuth();
        if ($request->isMethod('post')) {
            try {
                $id = $this->getService()->create($request->getPostParameters());
                $space = $this->getService()->getById($id);
                $this->getUser()->setFlash('notice', 'Exhibition space created.');
                $this->redirect("exhibitionSpace/show?slug={$space->slug}");
            } catch (InvalidArgumentException $e) {
                $this->getUser()->setFlash('error', $e->getMessage());
            }
        }
        $this->space = null;
        $this->spaceTypes = ExhibitionSpaceService::SPACE_TYPES;
        $this->capacityUnits = ExhibitionSpaceService::CAPACITY_UNITS;
        $this->setTemplate('edit');
    }

    public function executeEdit($request)
    {
        $this->requireAuth();
        $slug = $request->getParameter('slug');
        $space = $this->getService()->getBySlug($slug);
        if (!$space) {
            $this->forward404('Exhibition space not found');
        }
        if ($request->isMethod('post')) {
            $this->getService()->update((int) $space->id, $request->getPostParameters());
            $this->getUser()->setFlash('notice', 'Exhibition space updated.');
            $this->redirect("exhibitionSpace/show?slug={$space->slug}");
        }
        $this->space = $space;
        $this->spaceTypes = ExhibitionSpaceService::SPACE_TYPES;
        $this->capacityUnits = ExhibitionSpaceService::CAPACITY_UNITS;
    }

    public function executeConfirmDelete($request)
    {
        $this->requireAdmin();
        $slug = $request->getParameter('slug');
        $space = $this->getService()->getBySlug($slug);
        if (!$space) {
            $this->forward404('Exhibition space not found');
        }
        $this->space = $space;
        $this->placementCount = count($this->getService()->getPlacements((int) $space->id));
    }

    public function executeDestroy($request)
    {
        $this->requireAdmin();
        if (!$request->isMethod('post')) {
            $this->forward404();
        }
        $slug = $request->getParameter('slug');
        $space = $this->getService()->getBySlug($slug);
        if (!$space) {
            $this->forward404('Exhibition space not found');
        }
        try {
            $this->getService()->delete((int) $space->id);
            $this->getUser()->setFlash('notice', 'Exhibition space deleted.');
            $this->redirect('exhibitionSpace/browse');
        } catch (RuntimeException $e) {
            $this->getUser()->setFlash('error', $e->getMessage());
            $this->redirect("exhibitionSpace/show?slug={$slug}");
        }
    }

    public function executePlace($request)
    {
        $this->requireAuth();
        if (!$request->isMethod('post')) {
            $this->forward404();
        }
        $slug = $request->getParameter('slug');
        $space = $this->getService()->getBySlug($slug);
        if (!$space) {
            $this->forward404('Exhibition space not found');
        }
        try {
            $this->getService()->placePlacement([
                'id' => $request->getParameter('placement_id'),
                'exhibition_space_id' => (int) $space->id,
                'information_object_id' => (int) $request->getParameter('information_object_id'),
                'size_units_used' => (float) $request->getParameter('size_units_used', 0),
                'starts_at' => $request->getParameter('starts_at') ?: null,
                'ends_at' => $request->getParameter('ends_at') ?: null,
                'exhibition_id' => $request->getParameter('exhibition_id'),
                'notes' => $request->getParameter('notes'),
            ]);
            $this->getUser()->setFlash('notice', 'Placement saved.');
        } catch (\Throwable $e) {
            $this->getUser()->setFlash('error', $e->getMessage());
        }
        $this->redirect("exhibitionSpace/show?slug={$slug}");
    }

    public function executeRemovePlacement($request)
    {
        $this->requireAuth();
        if (!$request->isMethod('post')) {
            $this->forward404();
        }
        $pid = (int) $request->getParameter('id', 0);
        $row = \Illuminate\Database\Capsule\Manager::table('ahg_exhibition_placement')->where('id', $pid)->first();
        if (!$row) {
            $this->forward404();
        }
        $space = $this->getService()->getById((int) $row->exhibition_space_id);
        $this->getService()->removePlacement($pid);
        $this->getUser()->setFlash('notice', 'Placement removed.');
        $slug = $space ? $space->slug : '';
        $this->redirect("exhibitionSpace/show?slug={$slug}");
    }

    // ── Builder (#136) ──────────────────────────────────────────────────────

    /** Drag-and-drop builder canvas. */
    public function executeBuilder($request)
    {
        $this->requireAuth();
        $space = $this->getService()->getBySlug($request->getParameter('slug'));
        if (!$space) {
            $this->forward404('Exhibition space not found');
        }
        $this->space = $space;
        $this->placements = $this->getService()->getBuilderPlacements((int) $space->id);
    }

    /** AJAX: bulk-save the builder layout (JSON `items`). */
    public function executeSaveLayout($request)
    {
        $this->requireAuth();
        if (!$request->isMethod('post')) {
            return $this->renderJsonError('POST required', 405);
        }
        $space = $this->getService()->getBySlug($request->getParameter('slug'));
        if (!$space) {
            return $this->renderJsonError('Exhibition space not found', 404);
        }
        $items = json_decode((string) $request->getParameter('items', '[]'), true);
        if (!is_array($items)) {
            $items = [];
        }
        try {
            $n = $this->getService()->saveLayout((int) $space->id, $items);

            return $this->renderJsonSuccess(['saved' => $n], 'Layout saved.');
        } catch (\Throwable $e) {
            return $this->renderJsonError($e->getMessage(), 500);
        }
    }

    /** Save room canvas dimensions/colours, back to the builder. */
    public function executeSaveRoom($request)
    {
        $this->requireAuth();
        $space = $this->getService()->getBySlug($request->getParameter('slug'));
        if (!$space) {
            $this->forward404('Exhibition space not found');
        }
        if ($request->isMethod('post')) {
            $this->getService()->updateRoom((int) $space->id, $request->getPostParameters());
            $this->getUser()->setFlash('notice', 'Room settings saved.');
        }
        $this->redirect("exhibitionSpace/builder?slug={$space->slug}");

        return;
    }

    /** Public 2.5D pannable walkthrough viewer. */
    public function executeWalkthrough($request)
    {
        $space = $this->getService()->getBySlug($request->getParameter('slug'));
        if (!$space) {
            $this->forward404('Exhibition space not found');
        }
        $this->space = $space;
        $this->placements = $this->getService()->getBuilderPlacements((int) $space->id);
    }
}
