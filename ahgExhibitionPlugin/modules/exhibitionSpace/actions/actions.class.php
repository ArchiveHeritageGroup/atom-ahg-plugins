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

    // ════════════════════════════════════════════════════════════════════════
    //  Building-plan editor (#1143). PAGE + AJAX actions. All AJAX POST a JSON
    //  body and return HTTP 200 JSON {ok:true,...}/{ok:false,error}. Never
    //  setStatusCode(4xx/5xx) — AtoM would replace the body with its error page.
    // ════════════════════════════════════════════════════════════════════════

    /** PAGE: building-plan editor. Mirrors Heratio plan() — passes $space + $plan. */
    public function executePlan($request)
    {
        $this->requireAuth();
        $space = $this->getService()->getBySlug($request->getParameter('slug'));
        if (!$space) {
            $this->forward404('Exhibition space not found');
        }
        $this->space = $space;
        $this->plan = $this->getService()->getBuildingPlan($space);
    }

    /** POST: bulk-save room plan positions/sizes. body {rooms:[{id,x,y,w?,d?,rot?}]}. */
    public function executePlanSave($request)
    {
        $space = $this->ajaxSpace($request);
        if (!$space) {
            return sfView::NONE;
        }
        $b = $this->jsonBody($request);
        $rooms = (isset($b['rooms']) && is_array($b['rooms'])) ? $b['rooms'] : [];
        $svc = $this->getService();
        $n = 0;
        foreach ($rooms as $r) {
            $rid = (int) ($r['id'] ?? 0);
            if ($rid <= 0) {
                continue;
            }
            $ok = $svc->savePlanRoom(
                (int) $space->id,
                $rid,
                (float) ($r['x'] ?? 0),
                (float) ($r['y'] ?? 0),
                isset($r['w']) ? (float) $r['w'] : null,
                isset($r['d']) ? (float) $r['d'] : null,
                isset($r['rot']) ? (float) $r['rot'] : null
            );
            if ($ok) {
                $n++;
            }
        }

        return $this->jsonOk(['saved' => $n]);
    }

    /** POST: add a new room to this building. body {name?} -> {ok, room}. */
    public function executePlanAddRoom($request)
    {
        $space = $this->ajaxSpace($request);
        if (!$space) {
            return sfView::NONE;
        }
        $b = $this->jsonBody($request);
        $name = isset($b['name']) ? (string) $b['name'] : null;
        $room = $this->getService()->addBuildingRoom($space, $name);

        return $this->jsonOk(['room' => $room]);
    }

    /** POST: delete a room from this building. body {room_id}. */
    public function executePlanDeleteRoom($request)
    {
        $space = $this->ajaxSpace($request);
        if (!$space) {
            return sfView::NONE;
        }
        $b = $this->jsonBody($request);
        $roomId = (int) ($b['room_id'] ?? 0);
        if ($roomId <= 0) {
            return $this->jsonErr('room_id is required');
        }
        $ok = $this->getService()->deleteBuildingRoom($space, $roomId);

        return $ok ? $this->jsonOk() : $this->jsonErr('Room could not be deleted');
    }

    /** POST: set plan group keys (move-as-one-unit). body {groups:[{room_id,group}]}. */
    public function executePlanGroup($request)
    {
        $space = $this->ajaxSpace($request);
        if (!$space) {
            return sfView::NONE;
        }
        $b = $this->jsonBody($request);
        $groups = (isset($b['groups']) && is_array($b['groups'])) ? $b['groups'] : [];
        $n = $this->getService()->setRoomGroups($space, $groups);

        return $this->jsonOk(['updated' => $n]);
    }

    /** POST: save building stairs. body {stairs:[...]}. */
    public function executePlanStairs($request)
    {
        $space = $this->ajaxSpace($request);
        if (!$space) {
            return sfView::NONE;
        }
        $b = $this->jsonBody($request);
        $stairs = (isset($b['stairs']) && is_array($b['stairs'])) ? $b['stairs'] : [];
        $this->getService()->saveBuildingStairs($space, $stairs);

        return $this->jsonOk();
    }

    /** POST: set a room's floor level. body {room_id,floor}. */
    public function executePlanRoomFloor($request)
    {
        $space = $this->ajaxSpace($request);
        if (!$space) {
            return sfView::NONE;
        }
        $b = $this->jsonBody($request);
        $roomId = (int) ($b['room_id'] ?? 0);
        if ($roomId <= 0) {
            return $this->jsonErr('room_id is required');
        }
        $ok = $this->getService()->setRoomFloor($space, $roomId, (int) ($b['floor'] ?? 0));

        return $ok ? $this->jsonOk() : $this->jsonErr('Floor could not be set');
    }

    /** POST: lock/unlock a room. body {room_id,locked}. */
    public function executePlanRoomLock($request)
    {
        $space = $this->ajaxSpace($request);
        if (!$space) {
            return sfView::NONE;
        }
        $b = $this->jsonBody($request);
        $roomId = (int) ($b['room_id'] ?? 0);
        if ($roomId <= 0) {
            return $this->jsonErr('room_id is required');
        }
        $locked = filter_var($b['locked'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $ok = $this->getService()->setRoomLocked($space, $roomId, $locked);

        return $ok ? $this->jsonOk() : $this->jsonErr('Lock could not be set');
    }

    /** POST: save the blueprint's world rect (derived on PSIS; kept for parity). body {x,y,w,h}. */
    public function executePlanImageRect($request)
    {
        $space = $this->ajaxSpace($request);
        if (!$space) {
            return sfView::NONE;
        }
        $b = $this->jsonBody($request);
        $this->getService()->savePlanImageRect(
            $space,
            (float) ($b['x'] ?? 0),
            (float) ($b['y'] ?? 0),
            (float) ($b['w'] ?? 1),
            (float) ($b['h'] ?? 1)
        );

        return $this->jsonOk();
    }

    /** POST: add a corridor object at building-fraction. body {information_object_id,fx,fy}. */
    public function executePlanCorridorAdd($request)
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
            $placement = $this->getService()->createCorridorPlacement(
                $space,
                $ioId,
                (float) ($b['fx'] ?? 0.5),
                (float) ($b['fy'] ?? 0.5)
            );

            return $this->jsonOk(['placement' => $placement]);
        } catch (\Throwable $e) {
            return $this->jsonErr($e->getMessage());
        }
    }

    /** POST: move a corridor object. body {id,fx,fy}. */
    public function executePlanCorridorMove($request)
    {
        $space = $this->ajaxSpace($request);
        if (!$space) {
            return sfView::NONE;
        }
        $b = $this->jsonBody($request);
        $id = (int) ($b['id'] ?? 0);
        if ($id <= 0) {
            return $this->jsonErr('id is required');
        }
        $ok = $this->getService()->moveCorridorPlacement(
            $space,
            $id,
            (float) ($b['fx'] ?? 0.5),
            (float) ($b['fy'] ?? 0.5)
        );

        return $ok ? $this->jsonOk() : $this->jsonErr('Object could not be moved');
    }

    /** POST: remove a corridor object. body {id}. Scoped to this building. */
    public function executePlanCorridorRemove($request)
    {
        $space = $this->ajaxSpace($request);
        if (!$space) {
            return sfView::NONE;
        }
        $b = $this->jsonBody($request);
        $id = (int) ($b['id'] ?? 0);
        if ($id <= 0) {
            return $this->jsonErr('id is required');
        }
        // Scope: only remove a corridor placement that belongs to this building.
        $corridor = null;
        foreach ($this->getService()->getBuildingCorridorObjects($space) as $c) {
            if ((int) ($c['id'] ?? 0) === $id) {
                $corridor = $c;
                break;
            }
        }
        if (!$corridor) {
            return $this->jsonErr('Object not found in this building');
        }
        $ok = $this->getService()->removePlacement($id);

        return $ok ? $this->jsonOk() : $this->jsonErr('Object could not be removed');
    }

    /**
     * POST: upload a floorplan/blueprint background image (multipart). Stores the
     * file under web/uploads/exhibition/ and sets floorplan_image_path on every
     * room of the building. body: file field `plan_image` (or `file`).
     */
    public function executePlanImage($request)
    {
        $space = $this->ajaxSpace($request);
        if (!$space) {
            return sfView::NONE;
        }
        $files = $request->getFiles();
        $file = $files['plan_image'] ?? ($files['file'] ?? null);
        if (!is_array($file) || empty($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            return $this->jsonErr('No file uploaded');
        }
        if (!empty($file['error'])) {
            return $this->jsonErr('Upload failed (error '.(int) $file['error'].')');
        }
        $allowed = ['jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp', 'svg' => 'image/svg+xml'];
        $orig = (string) ($file['name'] ?? '');
        $ext = strtolower(pathinfo($orig, PATHINFO_EXTENSION) ?: 'png');
        if (!isset($allowed[$ext])) {
            return $this->jsonErr('Unsupported image type');
        }
        if ((int) ($file['size'] ?? 0) > 8 * 1024 * 1024) {
            return $this->jsonErr('Image too large (max 8 MB)');
        }
        $webRoot = $this->config('sf_web_dir');
        $relDir = '/plugins/ahgExhibitionPlugin/web/uploads/exhibition';
        $absDir = $webRoot.$relDir;
        if (!is_dir($absDir) && !@mkdir($absDir, 0775, true) && !is_dir($absDir)) {
            return $this->jsonErr('Could not create upload directory');
        }
        $base = ($space->building_id ?: $space->slug);
        $filename = preg_replace('/[^a-z0-9_-]+/', '-', strtolower((string) $base)).'-'.substr(md5((string) microtime(true)), 0, 8).'.'.$ext;
        $dest = $absDir.'/'.$filename;
        if (!@move_uploaded_file($file['tmp_name'], $dest)) {
            return $this->jsonErr('Could not store uploaded file');
        }
        $publicUrl = $relDir.'/'.$filename;
        $this->getService()->setBuildingPlanImage($space, $publicUrl);

        return $this->jsonOk(['url' => $publicUrl]);
    }

    /** POST: clear the building plan/blueprint image. */
    public function executePlanImageClear($request)
    {
        $space = $this->ajaxSpace($request);
        if (!$space) {
            return sfView::NONE;
        }
        $this->getService()->setBuildingPlanImage($space, null);

        return $this->jsonOk();
    }

    // ════════════════════════════════════════════════════════════════════════
    //  Analytics + conservation forecast (Heratio parity, heratio#1146/1148/
    //  1173/1187/1188). Pages are PUBLIC (matching Heratio's public-demo routes);
    //  the sensor token + ingest write actions require auth.
    // ════════════════════════════════════════════════════════════════════════

    /** PAGE (public): analytics dashboard — historical reading trends per room (heratio#1148). */
    public function executeAnalytics($request)
    {
        $space = $this->getService()->getBySlug($request->getParameter('slug'));
        if (!$space) {
            $this->forward404('Exhibition space not found');
        }
        $svc = $this->getService();
        $days = (int) $request->getParameter('days', 7);
        $this->space = $space;
        $this->days = $days;
        $this->data = $svc->buildingAnalytics($space, $days);
        $this->visitors = $svc->visitorAnalytics($space, $days);   // #1173
        $this->heatmap = $svc->visitorHeatmap($space, $days);      // #1187
        $this->sensor = $this->getUser()->isAuthenticated()        // #1188 (token only for logged-in staff)
            ? ['token' => $svc->getOrCreateSensorToken((int) $space->id), 'alerts' => $svc->recentAlerts($space)]
            : null;
    }

    /** PAGE (public): conservation forecast — projected light dose, risk, visitors (heratio#1147). */
    public function executeForecast($request)
    {
        $space = $this->getService()->getBySlug($request->getParameter('slug'));
        if (!$space) {
            $this->forward404('Exhibition space not found');
        }
        $svc = $this->getService();
        $this->space = $space;
        $this->rooms = $svc->buildingForecast($space);
        $this->timeline = $svc->conservationTimeline($space);   // #1189 time-scrubber
    }

    /**
     * POST (auth): live data link (heratio#1146) — ingest sensor/occupancy readings for a space.
     * Accepts a single {metric,value,recorded_at?} or {readings:[...]} batch.
     */
    public function executeRecordReadings($request)
    {
        $space = $this->ajaxSpace($request);   // requires auth
        if (!$space) {
            return sfView::NONE;
        }
        $b = $this->jsonBody($request);
        $batch = $b['readings'] ?? null;
        if (!is_array($batch)) {
            $batch = [['metric' => $b['metric'] ?? null, 'value' => $b['value'] ?? null, 'recorded_at' => $b['recorded_at'] ?? null]];
        }
        $n = 0;
        foreach ($batch as $r) {
            if (!isset($r['metric']) || !isset($r['value']) || !is_numeric($r['value'])) {
                continue;
            }
            $this->getService()->recordReading((int) $space->id, (string) $r['metric'], (float) $r['value'], $r['recorded_at'] ?? null);
            $n++;
        }

        return $this->jsonOk(['recorded' => $n, 'live' => $this->getService()->liveState($space)]);
    }

    /** POST (auth): seed demo readings across the building so charts/forecast show data (heratio#1147). */
    public function executeSimulateReadings($request)
    {
        $space = $this->ajaxSpace($request);   // requires auth
        if (!$space) {
            return sfView::NONE;
        }
        $n = $this->getService()->simulateReadings($space);

        return $this->jsonOk(['recorded' => $n]);
    }

    /** POST (auth): rotate a space's sensor token (heratio#1188). */
    public function executeSensorRegen($request)
    {
        $space = $this->ajaxSpace($request);   // requires auth
        if (!$space) {
            return sfView::NONE;
        }

        return $this->jsonOk(['token' => $this->getService()->regenerateSensorToken((int) $space->id)]);
    }
}
