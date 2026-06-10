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
        $all = $this->getService()->browse($search);
        $all = is_array($all) ? $all : (method_exists($all, 'all') ? $all->all() : (array) $all);

        $perPage = 25;
        $this->total = count($all);
        $this->pages = max(1, (int) ceil($this->total / $perPage));
        $page = min(max(1, (int) $request->getParameter('page', 1)), $this->pages);
        $this->page = $page;
        $this->perPage = $perPage;
        $this->rows = array_slice($all, ($page - 1) * $perPage, $perPage);
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
        $svc = $this->getService();
        $this->space = $space;
        $this->placements = $svc->getPlacementsForBuilder((int) $space->id);
        $this->walls = $svc->getWalls((int) $space->id);
        $this->doors = $svc->getDoors((int) $space->id);
        $this->windows = $svc->getWindows((int) $space->id);
        $this->shape = $svc->getShape((int) $space->id);
        $this->roomDims = $svc->roomDims($space);
        $this->capacityUnits = ExhibitionSpaceService::CAPACITY_UNITS;
        $this->furniture = [];
        $this->guidedTour = $svc->getGuidedTour($space) ?: [];
        $this->tourObjects = [];
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
        $svc = $this->getService();
        $this->space = $space;
        $this->building = $svc->getWalkthroughBuilding($space);
        $this->placements = $svc->getPlacementsForBuilder((int) $space->id);
        $this->roomDims = $svc->roomDims($space);
        $this->guidedTour = $svc->getGuidedTour($space) ?: [];
        $this->walls = $svc->getWalls((int) $space->id);
        $this->doors = $svc->getDoors((int) $space->id);
        $this->windows = $svc->getWindows((int) $space->id);
        $this->shape = $svc->getShape((int) $space->id);
    }

    // ════════════════════════════════════════════════════════════════════════
    //  Digital-twin builder AJAX (heratio#1138+ contract). All POST a JSON body
    //  and return HTTP 200 JSON {"ok":true,...} or {"ok":false,"error":...}.
    //  NB: never setStatusCode(4xx/5xx) — AtoM would replace the body with its
    //  themed error page. Errors are HTTP 200 with ok:false.
    // ════════════════════════════════════════════════════════════════════════

    /** Read the request JSON body, falling back to POST params. */
    private function jsonBody($request): array
    {
        $raw = (string) $request->getContent();
        $body = $raw !== '' ? json_decode($raw, true) : null;
        if (!is_array($body)) {
            $body = $request->getPostParameters() ?: [];
        }

        return is_array($body) ? $body : [];
    }

    /** Emit a JSON payload (plain content-type, HTTP 200). */
    private function json(array $data)
    {
        return $this->renderText(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    }

    private function jsonOk(array $extra = [])
    {
        return $this->json(array_merge(['ok' => true], $extra));
    }

    private function jsonErr(string $message)
    {
        return $this->json(['ok' => false, 'error' => $message]);
    }

    /**
     * Resolve the space by slug for an AJAX action, optionally requiring auth.
     * Returns the space object, or emits a JSON error and returns null.
     */
    private function ajaxSpace($request, bool $needAuth = true)
    {
        if ($needAuth && !$this->getUser()->isAuthenticated()) {
            $this->jsonErr('Authentication required');

            return null;
        }
        $space = $this->getService()->getBySlug($request->getParameter('slug'));
        if (!$space) {
            $this->jsonErr('Exhibition space not found');

            return null;
        }

        return $space;
    }

    /** GET: full builder dataset for the canvas. */
    public function executeBuilderPlacements($request)
    {
        $space = $this->ajaxSpace($request, false);
        if (!$space) {
            return sfView::NONE;
        }
        $svc = $this->getService();
        $sid = (int) $space->id;

        return $this->jsonOk([
            'placements' => $svc->getPlacementsForBuilder($sid),
            'walls' => $svc->getWalls($sid),
            'doors' => $svc->getDoors($sid),
            'windows' => $svc->getWindows($sid),
            'shape' => $svc->getShape($sid),
            'room' => $svc->roomDims($space),
        ]);
    }

    /** POST: create a placement at canvas coords. */
    public function executeBuilderPlace($request)
    {
        $space = $this->ajaxSpace($request);
        if (!$space) {
            return sfView::NONE;
        }
        $b = $this->jsonBody($request);
        $ioId = (int) ($b['information_object_id'] ?? 0);
        if ($ioId <= 0) {
            return $this->jsonErr('information_object_id is required');
        }
        try {
            $placement = $this->getService()->createPlacementAt(
                (int) $space->id,
                $ioId,
                (float) ($b['pos_x'] ?? 0.5),
                (float) ($b['pos_y'] ?? 0.5),
                (float) ($b['size_units'] ?? 0)
            );

            return $this->jsonOk(['placement' => $placement]);
        } catch (\Throwable $e) {
            return $this->jsonErr($e->getMessage());
        }
    }

    /** POST: remove a placement from the builder. */
    public function executeBuilderRemove($request)
    {
        $space = $this->ajaxSpace($request);
        if (!$space) {
            return sfView::NONE;
        }
        $b = $this->jsonBody($request);
        $pid = (int) ($b['id'] ?? 0);
        if ($pid <= 0) {
            return $this->jsonErr('id is required');
        }
        $row = \Illuminate\Database\Capsule\Manager::table('ahg_exhibition_placement')
            ->where('id', $pid)->where('exhibition_space_id', (int) $space->id)->first();
        if (!$row) {
            return $this->jsonErr('Placement not found in this space');
        }
        $this->getService()->removePlacement($pid);

        return $this->jsonOk();
    }

    /** POST: set a placement's capacity size (units = metres). */
    public function executeBuilderSize($request)
    {
        $space = $this->ajaxSpace($request);
        if (!$space) {
            return sfView::NONE;
        }
        $b = $this->jsonBody($request);
        $this->getService()->updatePlacementSize((int) $space->id, (int) ($b['id'] ?? 0), (float) ($b['size_units'] ?? 0));

        return $this->jsonOk();
    }

    /** POST: per-object 3D tilt (null axis = auto). */
    public function executeBuilderTilt($request)
    {
        $space = $this->ajaxSpace($request);
        if (!$space) {
            return sfView::NONE;
        }
        $b = $this->jsonBody($request);
        $tx = (isset($b['tilt_x']) && $b['tilt_x'] !== '' && $b['tilt_x'] !== null) ? (float) $b['tilt_x'] : null;
        $tz = (isset($b['tilt_z']) && $b['tilt_z'] !== '' && $b['tilt_z'] !== null) ? (float) $b['tilt_z'] : null;
        $this->getService()->updatePlacementTilt((int) $space->id, (int) ($b['id'] ?? 0), $tx, $tz);

        return $this->jsonOk();
    }

    /** POST: spotlight mode (0 off, 1 on-approach, 2 always-on). */
    public function executeBuilderSpotlight($request)
    {
        $space = $this->ajaxSpace($request);
        if (!$space) {
            return sfView::NONE;
        }
        $b = $this->jsonBody($request);
        $mode = (int) ($b['mode'] ?? 0);
        $this->getService()->updatePlacementSpotlight((int) $space->id, (int) ($b['id'] ?? 0), $mode);

        return $this->jsonOk(['mode' => max(0, min(2, $mode))]);
    }

    /** POST: toggle glass display case. */
    public function executeBuilderDisplayCase($request)
    {
        $space = $this->ajaxSpace($request);
        if (!$space) {
            return sfView::NONE;
        }
        $b = $this->jsonBody($request);
        $this->getService()->updatePlacementDisplayCase((int) $space->id, (int) ($b['id'] ?? 0), !empty($b['on']));

        return $this->jsonOk();
    }

    /** POST: toggle model-stands-on-floor (no pedestal). */
    public function executeBuilderOnFloor($request)
    {
        $space = $this->ajaxSpace($request);
        if (!$space) {
            return sfView::NONE;
        }
        $b = $this->jsonBody($request);
        $this->getService()->updatePlacementOnFloor((int) $space->id, (int) ($b['id'] ?? 0), !empty($b['on']));

        return $this->jsonOk();
    }

    /** POST: set/clear the curator viewing spot (null x/y clears). */
    public function executeBuilderView($request)
    {
        $space = $this->ajaxSpace($request);
        if (!$space) {
            return sfView::NONE;
        }
        $b = $this->jsonBody($request);
        $vx = (isset($b['view_x']) && $b['view_x'] !== '' && $b['view_x'] !== null) ? (float) $b['view_x'] : null;
        $vy = (isset($b['view_y']) && $b['view_y'] !== '' && $b['view_y'] !== null) ? (float) $b['view_y'] : null;
        $this->getService()->updatePlacementView((int) $space->id, (int) ($b['id'] ?? 0), $vx, $vy);

        return $this->jsonOk();
    }

    /** POST: set a placement's z-order. */
    public function executeBuilderZOrder($request)
    {
        $space = $this->ajaxSpace($request);
        if (!$space) {
            return sfView::NONE;
        }
        $b = $this->jsonBody($request);
        $this->getService()->updatePlacementZOrder((int) $space->id, (int) ($b['id'] ?? 0), (int) ($b['z'] ?? 0));

        return $this->jsonOk();
    }

    /** POST: assign a placement to a wall (null/'' = auto). */
    public function executeBuilderWall($request)
    {
        $space = $this->ajaxSpace($request);
        if (!$space) {
            return sfView::NONE;
        }
        $b = $this->jsonBody($request);
        $wall = isset($b['wall']) ? (string) $b['wall'] : null;
        $this->getService()->updatePlacementWall((int) $space->id, (int) ($b['id'] ?? 0), $wall);

        return $this->jsonOk();
    }

    /** POST: room dimensions (metres). */
    public function executeRoomDims($request)
    {
        $space = $this->ajaxSpace($request);
        if (!$space) {
            return sfView::NONE;
        }
        $b = $this->jsonBody($request);
        $w = (isset($b['w']) && $b['w'] !== '' && $b['w'] !== null) ? (float) $b['w'] : null;
        $d = (isset($b['d']) && $b['d'] !== '' && $b['d'] !== null) ? (float) $b['d'] : null;
        $h = (isset($b['h']) && $b['h'] !== '' && $b['h'] !== null) ? (float) $b['h'] : null;
        $this->getService()->updateRoomDims((int) $space->id, $w, $d, $h);

        return $this->jsonOk(['room' => $this->getService()->roomDims($this->getService()->getById((int) $space->id))]);
    }

    /** POST: interior wall segments. */
    public function executePlanWalls($request)
    {
        $space = $this->ajaxSpace($request);
        if (!$space) {
            return sfView::NONE;
        }
        $b = $this->jsonBody($request);
        $walls = (isset($b['walls']) && is_array($b['walls'])) ? $b['walls'] : [];
        $this->getService()->saveWalls((int) $space->id, $walls);

        return $this->jsonOk();
    }

    /** POST: room doors. */
    public function executePlanDoors($request)
    {
        $space = $this->ajaxSpace($request);
        if (!$space) {
            return sfView::NONE;
        }
        $b = $this->jsonBody($request);
        $doors = (isset($b['doors']) && is_array($b['doors'])) ? $b['doors'] : [];
        $this->getService()->saveDoors((int) $space->id, $doors);

        return $this->jsonOk();
    }

    /** POST: room windows. */
    public function executePlanWindows($request)
    {
        $space = $this->ajaxSpace($request);
        if (!$space) {
            return sfView::NONE;
        }
        $b = $this->jsonBody($request);
        $windows = (isset($b['windows']) && is_array($b['windows'])) ? $b['windows'] : [];
        $this->getService()->saveWindows((int) $space->id, $windows);

        return $this->jsonOk();
    }

    /** POST: room footprint polygon (points:[...]|null). */
    public function executePlanShape($request)
    {
        $space = $this->ajaxSpace($request);
        if (!$space) {
            return sfView::NONE;
        }
        $b = $this->jsonBody($request);
        $points = (array_key_exists('points', $b) && is_array($b['points'])) ? $b['points'] : null;
        $this->getService()->saveShape((int) $space->id, $points);

        return $this->jsonOk();
    }
}
