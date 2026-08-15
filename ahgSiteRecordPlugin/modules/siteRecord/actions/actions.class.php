<?php

use AhgSiteRecordPlugin\Services\SiteRecordService;
use AtomFramework\Http\Controllers\AhgController;

/**
 * Site record CRUD.
 *
 * Access is declared in config/security.yml - browse and view for contributors
 * upward, edit for editors, delete for administrators only. CSRF is enforced by
 * AhgController::preExecute().
 *
 * Nothing here reads a coordinate. Actions call SiteRecordService::present*(),
 * which returns a record whose raw locality columns have been removed and
 * replaced with a resolved `locality` structure.
 */
class siteRecordActions extends AhgController
{
    private SiteRecordService $service;

    public function boot(): void
    {
        $this->service = new SiteRecordService();
    }

    public function executeBrowse($request)
    {
        $result = $this->service->repository()->browse(
            [
                'q' => trim((string) $request->getParameter('q', '')),
                'region' => $request->getParameter('region'),
                'sort' => $request->getParameter('sort'),
            ],
            (int) $request->getParameter('page', 1)
        );

        // Resolved before the template sees them, so a browse listing cannot leak
        // an exact position through a column nobody thought about.
        $this->records = $this->service->presentMany($result['rows']);
        $this->total = $result['total'];
        $this->page = $result['page'];
        $this->perPage = $result['perPage'];
        $this->q = $request->getParameter('q', '');
        $this->region = $request->getParameter('region');
        $this->regionChoices = $this->service->choices('site_region');
        $this->service_ = $this->service;
    }

    public function executeView($request)
    {
        $id = (int) $request->getParameter('id');
        $this->record = $this->service->findForDisplay($id);

        if (!$this->record) {
            $this->forward404('No such site record.');

            return;
        }

        $this->actor = $this->service->repository()->actor((int) $this->record->actor_id);
        $this->attributes = $this->service->attributesByTaxonomy($id);
        $this->recorders = $this->service->repository()->recorders($id);
        $this->service_ = $this->service;
    }

    public function executeEdit($request)
    {
        $id = $request->getParameter('id') ? (int) $request->getParameter('id') : null;

        if (null !== $id) {
            $raw = $this->service->repository()->find($id);
            if (!$raw) {
                $this->forward404('No such site record.');

                return;
            }
            $actorId = (int) $raw->actor_id;
        } else {
            $actorId = (int) $request->getParameter('actorId');
            $raw = $this->service->repository()->findByActor($actorId);

            // One site record per authority record. Arriving at "add" for an actor
            // that already has one is an edit, not a duplicate.
            if ($raw) {
                $this->redirect('/site-record/'.$raw->id.'/edit');

                return;
            }
        }

        $this->actor = $this->service->repository()->actor($actorId);

        if (!$this->actor) {
            $this->forward404('No such authority record.');

            return;
        }

        if ($request->isMethod('post')) {
            $savedId = $this->service->save(
                $id,
                $actorId,
                $request->getParameterHolder()->getAll(),
                $this->getUserId()
            );

            $this->redirect('/site-record/'.$savedId);

            return;
        }

        // The edit form is for editors, who hold clearance, so it shows the exact
        // values - but it is still resolved through the service rather than read
        // raw, so an editor who somehow lacks clearance sees the coarsened value
        // instead of the true one.
        $this->record = $this->service->present($raw);
        $this->attributes = $raw ? $this->service->attributesByTaxonomy((int) $raw->id) : [];
        $this->recorders = $raw ? $this->service->repository()->recorders((int) $raw->id) : [];
        $this->service_ = $this->service;
    }

    /**
     * Delete. POST only, administrator only, CSRF enforced.
     *
     * The legacy application deleted on a bare GET with no token and no auth, so
     * anything that followed the link - a crawler, a link checker, a prefetch -
     * destroyed field observations that cannot be recollected without revisiting
     * the site.
     */
    public function executeDelete($request)
    {
        if (!$request->isMethod('post')) {
            $this->forward404('Delete requires POST.');

            return;
        }

        $id = (int) $request->getParameter('id');
        $record = $this->service->repository()->find($id);

        if (!$record) {
            $this->forward404('No such site record.');

            return;
        }

        $actorId = (int) $record->actor_id;
        $this->service->repository()->delete($id);

        $this->redirect('/site-record?deleted='.$actorId);
    }

    private function getUserId(): ?int
    {
        try {
            $user = $this->getUser();

            return $user && $user->isAuthenticated() ? (int) $user->getAttribute('user_id') : null;
        } catch (\Throwable $e) {
            return null;
        }
    }
}
