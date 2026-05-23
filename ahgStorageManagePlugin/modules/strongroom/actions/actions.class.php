<?php

/*
 * Strongroom space-allocation module — heratio#145 (AtoM Heratio port of #144).
 *
 * Copyright (C) 2026 The Archive and Heritage Group (Pty) Ltd.
 * Licensed under the GNU Affero General Public License v3.0 or later.
 *
 * Mirrors packages/ahg-storage-manage/src/Controllers/StrongroomController.php
 * on the Heratio Laravel side. Same routes, same UX, same validation rules.
 * Forms use plain $request->getParameter() — no sfForm — matching the
 * established storageManage / physicalobject pattern in this plugin.
 */

use AhgStorageManage\Services\StrongroomService;
use AtomFramework\Http\Controllers\AhgController;

class strongroomActions extends AhgController
{
    /** @var StrongroomService */
    protected $service;

    public function preExecute()
    {
        parent::preExecute();
        // Resolve via the plugin's autoloader (PSR-4 via composer in atom-framework).
        $this->service = new StrongroomService();
    }

    public function executeBrowse($request)
    {
        $this->search = trim((string) $request->getParameter('q', ''));
        $this->rooms = $this->service->browse($this->search, 25);
        $this->capacityUnits = StrongroomService::CAPACITY_UNITS;
    }

    public function executeShow($request)
    {
        $this->room = $this->service->getBySlug((string) $request->getParameter('slug'));
        if (null === $this->room) {
            $this->forward404();
        }

        $this->usedUnits = $this->service->getUsedCapacity((int) $this->room->id);
        $this->remainingUnits = null === $this->room->capacity_value
            ? null
            : (float) $this->room->capacity_value - $this->usedUnits;
        $this->occupants = $this->service->getOccupants((int) $this->room->id);
        $this->capacityUnits = StrongroomService::CAPACITY_UNITS;
    }

    /**
     * /strongroom/add — GET renders the form, POST saves. Same action handles both.
     */
    public function executeCreate($request)
    {
        $this->room = null;
        $this->capacityUnits = StrongroomService::CAPACITY_UNITS;

        if ($request->isMethod('post')) {
            $data = $this->extractFormData($request);
            $errors = $this->validate($data);
            if (!empty($errors)) {
                $this->errors = $errors;
                $this->formData = $data;

                return;
            }
            $id = $this->service->create($data);
            $room = $this->service->getById($id);
            $this->getUser()->setFlash('notice', 'Strongroom created.');
            $this->redirect(['module' => 'strongroom', 'action' => 'show', 'slug' => $room->slug]);
        }
    }

    /**
     * /strongroom/:slug/edit — GET renders the form, POST saves. Same action handles both.
     */
    public function executeEdit($request)
    {
        $this->room = $this->service->getBySlug((string) $request->getParameter('slug'));
        if (null === $this->room) {
            $this->forward404();
        }
        $this->capacityUnits = StrongroomService::CAPACITY_UNITS;

        if ($request->isMethod('post')) {
            $data = $this->extractFormData($request);
            $errors = $this->validate($data);
            if (!empty($errors)) {
                $this->errors = $errors;
                $this->formData = $data;

                return;
            }
            $this->service->update((int) $this->room->id, $data);
            $this->getUser()->setFlash('notice', 'Strongroom updated.');
            $this->redirect(['module' => 'strongroom', 'action' => 'show', 'slug' => $this->room->slug]);
        }
    }

    /**
     * /strongroom/:slug/delete — GET shows the confirmation, POST performs the delete.
     */
    public function executeDelete($request)
    {
        $this->room = $this->service->getBySlug((string) $request->getParameter('slug'));
        if (null === $this->room) {
            $this->forward404();
        }
        $this->occupantCount = $this->service->getOccupants((int) $this->room->id)->count();

        if ($request->isMethod('post')) {
            try {
                $this->service->delete((int) $this->room->id);
            } catch (\RuntimeException $e) {
                $this->getUser()->setFlash('error', $e->getMessage());
                $this->redirect(['module' => 'strongroom', 'action' => 'show', 'slug' => $this->room->slug]);
            }

            $this->getUser()->setFlash('notice', 'Strongroom deleted.');
            $this->redirect(['module' => 'strongroom', 'action' => 'browse']);
        }
    }

    /**
     * /strongroom/:slug/assign — POST: assign a physical object to this
     * strongroom (or re-assign if already there). The admin enters the
     * physical-object slug (copied from its show page) and a size. Capacity
     * overflow does NOT block the save but flashes a warning, matching the
     * Heratio Laravel UX.
     */
    public function executeAssign($request)
    {
        if (!$request->isMethod('post')) {
            $this->redirect(['module' => 'strongroom', 'action' => 'show', 'slug' => $request->getParameter('slug')]);
        }

        $room = $this->service->getBySlug((string) $request->getParameter('slug'));
        if (null === $room) {
            $this->forward404();
        }

        $poSlug = trim((string) $request->getParameter('physical_object_slug', ''));
        $size = (float) $request->getParameter('size_units_used', 0);

        if ('' === $poSlug) {
            $this->getUser()->setFlash('error', 'Physical-object slug is required.');
            $this->redirect(['module' => 'strongroom', 'action' => 'show', 'slug' => $room->slug]);
        }

        $poId = (int) \Illuminate\Database\Capsule\Manager::table('slug')
            ->where('slug', $poSlug)
            ->value('object_id');

        if (0 === $poId) {
            $this->getUser()->setFlash('error', sprintf('No object found with slug "%s".', $poSlug));
            $this->redirect(['module' => 'strongroom', 'action' => 'show', 'slug' => $room->slug]);
        }

        // Confirm the object is actually a physical_object (the slug table
        // covers every object type — IO, actor, term, etc.).
        $isPhysical = \Illuminate\Database\Capsule\Manager::table('physical_object')
            ->where('id', $poId)
            ->exists();
        if (!$isPhysical) {
            $this->getUser()->setFlash('error', sprintf('Slug "%s" is not a physical object.', $poSlug));
            $this->redirect(['module' => 'strongroom', 'action' => 'show', 'slug' => $room->slug]);
        }

        $overflow = $this->service->capacityOverflow((int) $room->id, $size, $poId);
        $this->service->assign($poId, (int) $room->id, $size);

        if (null !== $overflow && $overflow > 0) {
            $this->getUser()->setFlash('error', sprintf(
                'Saved, but strongroom is now over capacity by %s unit(s).',
                rtrim(rtrim(number_format($overflow, 2), '0'), '.')
            ));
        } else {
            $this->getUser()->setFlash('notice', 'Physical object assigned.');
        }

        $this->redirect(['module' => 'strongroom', 'action' => 'show', 'slug' => $room->slug]);
    }

    /**
     * /strongroom/unassign — POST: remove a physical object's strongroom
     * assignment. Posted from the occupant list on the strongroom show page.
     */
    public function executeUnassign($request)
    {
        if (!$request->isMethod('post')) {
            $this->redirect(['module' => 'strongroom', 'action' => 'browse']);
        }

        $poId = (int) $request->getParameter('physical_object_id', 0);
        $returnSlug = (string) $request->getParameter('return_slug', '');

        if ($poId > 0) {
            $this->service->unassign($poId);
            $this->getUser()->setFlash('notice', 'Physical object unassigned.');
        }

        if ('' !== $returnSlug) {
            $this->redirect(['module' => 'strongroom', 'action' => 'show', 'slug' => $returnSlug]);
        }
        $this->redirect(['module' => 'strongroom', 'action' => 'browse']);
    }

    // -----------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------

    private function extractFormData($request): array
    {
        return [
            'name'                 => $request->getParameter('name'),
            'location_description' => $request->getParameter('location_description'),
            'capacity_value'       => $request->getParameter('capacity_value'),
            'capacity_unit'        => $request->getParameter('capacity_unit'),
            'notes'                => $request->getParameter('notes'),
        ];
    }

    /**
     * Lightweight server-side validation (mirrors the Laravel-side validate()).
     * Returns a [field => message] array; empty array means valid.
     */
    private function validate(array $data): array
    {
        $errors = [];

        if ('' === trim((string) ($data['name'] ?? ''))) {
            $errors['name'] = 'Name is required.';
        } elseif (mb_strlen((string) $data['name']) > 255) {
            $errors['name'] = 'Name is too long (max 255 characters).';
        }

        if (isset($data['capacity_value']) && '' !== $data['capacity_value']) {
            if (!is_numeric($data['capacity_value']) || (float) $data['capacity_value'] < 0) {
                $errors['capacity_value'] = 'Capacity must be a non-negative number.';
            }
        }

        if (isset($data['capacity_unit']) && '' !== $data['capacity_unit']
            && !isset(StrongroomService::CAPACITY_UNITS[$data['capacity_unit']])) {
            $errors['capacity_unit'] = 'Invalid capacity unit.';
        }

        return $errors;
    }
}
