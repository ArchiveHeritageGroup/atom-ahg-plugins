<?php

use AtomFramework\Http\Controllers\AhgController;

/**
 * SAHRA module actions - NHRA (Act 25 of 1999) heritage permit workflow.
 *
 * Researcher applies -> supervising professor endorses -> coordinator lodges
 * with SAHRA -> SAHRA outcome recorded.
 */
class sahraActions extends AhgController
{
    protected function getService(): \AhgSAHRA\Services\SahraPermitService
    {
        return new \AhgSAHRA\Services\SahraPermitService();
    }

    /**
     * Feature gate: the plugin ships in the shared codebase, so it stays
     * dormant unless an admin has switched it on for this instance. The
     * config/reviewer-admin actions stay reachable so it can be enabled.
     */
    public function preExecute()
    {
        $always = ['config', 'reviewerAdd', 'reviewerRemove'];
        if (in_array($this->getActionName(), $always, true)) {
            return;
        }
        if (!$this->getService()->isFeatureEnabled()) {
            $this->forward404('Heritage permits are not enabled on this instance.');
        }
    }

    // --- auth helpers -------------------------------------------------

    protected function requireAuth(): void
    {
        if (!$this->getUser()->isAuthenticated()) {
            $this->redirect(['module' => 'user', 'action' => 'login']);
        }
    }

    protected function checkAdmin(): void
    {
        $this->requireAuth();
        if (!$this->getUser()->hasCredential('administrator')) {
            $this->forward('admin', 'secure');
        }
    }

    protected function userId(): int
    {
        return (int) $this->getUser()->getAttribute('user_id');
    }

    protected function isAdmin(): bool
    {
        return $this->getUser()->hasCredential('administrator');
    }

    /** A designated SAHRA reviewer, or an administrator, may decide from SAHRA's side. */
    protected function isDecider(): bool
    {
        return $this->isAdmin() || $this->getService()->isSahraReviewer($this->userId());
    }

    protected function checkDecider(): void
    {
        $this->requireAuth();
        if (!$this->isDecider()) {
            $this->forward('admin', 'secure');
        }
    }

    /** The nominated supervisor, an editor, or an administrator may endorse. */
    protected function canEndorse($permit): bool
    {
        return (int) $permit->supervisor_user_id === $this->userId()
            || $this->getUser()->hasCredential('editor')
            || $this->isAdmin();
    }

    // --- dashboard ----------------------------------------------------

    public function executeIndex($request)
    {
        $this->checkAdmin();
        $service = $this->getService();
        $this->stats = $service->getDashboardStats();
        $this->recent = $service->getApplications([]);
        $this->recent = array_slice($this->recent, 0, 10);
    }

    // --- researcher: apply + my applications --------------------------

    public function executeApplicationCreate($request)
    {
        $this->requireAuth();
        $service = $this->getService();
        $this->sections = \AhgSAHRA\Services\SahraPermitService::SECTIONS;
        $this->authorities = $service->getAuthorities();
        $this->supervisors = $this->getSupervisorChoices();

        $user = \Illuminate\Database\Capsule\Manager::table('user')->where('id', $this->userId())->first();
        $this->currentUser = $user;
    }

    public function executeCreate($request)
    {
        $this->requireAuth();
        if (!$request->isMethod('post')) {
            $this->redirect('@sahra_apply');
        }

        $projectTitle = trim((string) $request->getParameter('project_title'));
        if ($projectTitle === '') {
            $this->getUser()->setFlash('error', 'Project title is required.');
            $this->redirect('@sahra_apply');
        }

        $supervisorId = (int) $request->getParameter('supervisor_user_id');
        $supervisorName = null;
        if ($supervisorId) {
            $sup = \Illuminate\Database\Capsule\Manager::table('user')->where('id', $supervisorId)->first();
            $supervisorName = $sup->username ?? null;
        }

        // Site (an information_object) + its dig areas (child records).
        $siteObjectId = (int) $request->getParameter('site_object_id');
        $siteName = trim((string) $request->getParameter('site_name')) ?: null;
        if ($siteObjectId && !$siteName) {
            $siteName = $this->getService()->ioTitle($siteObjectId);
        }
        $digAreaIds = (array) $request->getParameter('dig_area_ids', []);

        $id = $this->getService()->createApplication([
            'applicant_user_id' => $this->userId(),
            'applicant_name' => trim((string) $request->getParameter('applicant_name')) ?: null,
            'applicant_email' => trim((string) $request->getParameter('applicant_email')) ?: null,
            'institution' => trim((string) $request->getParameter('institution')) ?: null,
            'supervisor_user_id' => $supervisorId ?: null,
            'supervisor_name' => $supervisorName,
            'nhra_section' => $request->getParameter('nhra_section', 's35_archaeology'),
            'issuing_authority' => $request->getParameter('issuing_authority', 'SAHRA'),
            'project_title' => $projectTitle,
            'project_description' => trim((string) $request->getParameter('project_description')) ?: null,
            'linked_object_id' => $siteObjectId ?: null,
            'site_name' => $siteName,
            'site_location' => trim((string) $request->getParameter('site_location')) ?: null,
            'province' => trim((string) $request->getParameter('province')) ?: null,
            'dig_area_ids' => $digAreaIds,
            'start_date' => $request->getParameter('start_date') ?: null,
            'end_date' => $request->getParameter('end_date') ?: null,
            'status' => 'pending_supervisor',
        ]);

        // Any files uploaded with the application.
        $this->getService()->storeUploadedDocuments($id, $request->getFiles('documents'), $this->userId(), 'application');

        $this->getUser()->setFlash('success', 'Application submitted for supervisor endorsement.');
        $this->redirect(['module' => 'sahra', 'action' => 'permitView', 'id' => $id]);
    }

    // --- site / dig-area type-ahead (JSON) ---------------------------

    public function executeSearchSites($request)
    {
        $this->requireAuth();
        return $this->renderJson($this->getService()->searchSites((string) $request->getParameter('q', '')));
    }

    public function executeSiteAreas($request)
    {
        $this->requireAuth();
        return $this->renderJson($this->getService()->getSiteAreas((int) $request->getParameter('site_id')));
    }

    public function executeMyApplications($request)
    {
        $this->requireAuth();
        $this->permits = $this->getService()->getMyApplications($this->userId());
    }

    // --- permit detail ------------------------------------------------

    public function executePermitView($request)
    {
        $this->requireAuth();
        $id = (int) $request->getParameter('id');
        $service = $this->getService();

        $this->permit = $service->getPermit($id);
        if (!$this->permit) {
            $this->forward404('Permit not found');
        }

        $uid = $this->userId();
        $isApplicant = (int) $this->permit->applicant_user_id === $uid;
        $isSupervisor = (int) $this->permit->supervisor_user_id === $uid;
        if (!$isApplicant && !$isSupervisor && !$this->isAdmin() && !$this->getUser()->hasCredential('editor')) {
            $this->getUser()->setFlash('error', 'You are not authorised to view this permit.');
            $this->redirect('@sahra_my');
        }

        $this->isApplicant = $isApplicant;
        $this->canEndorse = $this->permit->status === 'pending_supervisor' && $this->canEndorse($this->permit);
        $this->canSubmit = $this->permit->status === 'supervisor_approved' && $this->isAdmin();
        $this->canDecide = $this->permit->status === 'submitted_to_sahra' && $this->isDecider();
        $this->isSahraReviewer = $this->getService()->isSahraReviewer($uid);
        // Anyone who can see the permit and has a stake in it may attach documents.
        $this->canUpload = $isApplicant || $isSupervisor || $this->isDecider() || $this->getUser()->hasCredential('editor');
        $this->log = $service->getPermitLog($id);
        $this->reports = $service->getReports($id);
        $this->areas = $service->getAreas($id);
        $this->documents = $service->getDocuments($id);
        $this->siteSlug = $this->permit->linked_object_id
            ? \Illuminate\Database\Capsule\Manager::table('slug')->where('object_id', $this->permit->linked_object_id)->value('slug')
            : null;
        $this->sections = \AhgSAHRA\Services\SahraPermitService::SECTIONS;
    }

    // --- documents ---------------------------------------------------

    protected function loadPermitOrDeny(int $id)
    {
        $permit = $this->getService()->getPermit($id);
        if (!$permit) {
            $this->forward404('Permit not found');
        }
        $uid = $this->userId();
        $ok = (int) $permit->applicant_user_id === $uid
            || (int) $permit->supervisor_user_id === $uid
            || $this->isDecider()
            || $this->getUser()->hasCredential('editor');
        if (!$ok) {
            $this->forward404('Not authorised');
        }
        return $permit;
    }

    public function executeDocumentUpload($request)
    {
        $this->requireAuth();
        $id = (int) $request->getParameter('id');
        $this->loadPermitOrDeny($id);
        if ($request->isMethod('post')) {
            $n = $this->getService()->storeUploadedDocuments(
                $request->getFiles('documents'),
                $id,
                $this->userId(),
                $request->getParameter('doc_type', 'supporting')
            );
            $this->getUser()->setFlash($n ? 'success' : 'error', $n ? ($n . ' document(s) uploaded.') : 'No document uploaded (check file type / size).');
        }
        $this->redirect(['module' => 'sahra', 'action' => 'permitView', 'id' => $id]);
    }

    public function executeDocumentDownload($request)
    {
        $this->requireAuth();
        $doc = $this->getService()->getDocument((int) $request->getParameter('id'));
        if (!$doc) {
            $this->forward404('Document not found');
        }
        $this->loadPermitOrDeny((int) $doc->permit_id);

        $path = $this->getService()->documentPath($doc);
        if (!is_file($path)) {
            $this->forward404('File missing');
        }

        $response = $this->getResponse();
        $response->clearHttpHeaders();
        $response->setContentType($doc->mime_type ?: 'application/octet-stream');
        $response->setHttpHeader('Content-Disposition', 'attachment; filename="' . addslashes($doc->original_name) . '"');
        $response->setHttpHeader('Content-Length', (string) filesize($path));
        $response->sendHttpHeaders();
        readfile($path);

        return sfView::NONE;
    }

    public function executeDocumentDelete($request)
    {
        $this->requireAuth();
        $doc = $this->getService()->getDocument((int) $request->getParameter('id'));
        if (!$doc) {
            $this->forward404('Document not found');
        }
        $permit = $this->loadPermitOrDeny((int) $doc->permit_id);
        // Only the applicant or an admin may delete.
        if ((int) $permit->applicant_user_id !== $this->userId() && !$this->isAdmin()) {
            $this->forward404('Not authorised to delete this document');
        }
        if ($request->isMethod('post')) {
            $this->getService()->deleteDocument((int) $doc->id);
            $this->getUser()->setFlash('success', 'Document removed.');
        }
        $this->redirect(['module' => 'sahra', 'action' => 'permitView', 'id' => (int) $doc->permit_id]);
    }

    // --- supervisor endorsement --------------------------------------

    public function executePendingApprovals($request)
    {
        $this->requireAuth();
        $this->permits = $this->getService()->getPendingForSupervisor($this->userId(), $this->isAdmin());
    }

    public function executeEndorse($request)
    {
        $this->requireAuth();
        $id = (int) $request->getParameter('id');
        $permit = $this->getService()->getPermit($id);
        if (!$permit) {
            $this->forward404('Permit not found');
        }
        if (!$this->canEndorse($permit)) {
            $this->forward404('Not authorised to endorse this application');
        }
        if ($request->isMethod('post')) {
            $notes = trim((string) $request->getParameter('notes')) ?: null;
            if ($this->getService()->endorse($id, $this->userId(), $notes)) {
                $this->getUser()->setFlash('success', 'Application endorsed. It is now ready to lodge with SAHRA.');
            } else {
                $this->getUser()->setFlash('error', 'Could not endorse this application.');
            }
        }
        $this->redirect(['module' => 'sahra', 'action' => 'permitView', 'id' => $id]);
    }

    public function executeReject($request)
    {
        $this->requireAuth();
        $id = (int) $request->getParameter('id');
        $permit = $this->getService()->getPermit($id);
        if (!$permit) {
            $this->forward404('Permit not found');
        }
        if (!$this->canEndorse($permit)) {
            $this->forward404('Not authorised to decide this application');
        }
        if ($request->isMethod('post')) {
            $notes = trim((string) $request->getParameter('notes'));
            if ($notes === '') {
                $this->getUser()->setFlash('error', 'Please give the researcher a reason.');
                $this->redirect(['module' => 'sahra', 'action' => 'permitView', 'id' => $id]);
            }
            $this->getService()->reject($id, $this->userId(), $notes);
            $this->getUser()->setFlash('success', 'Application returned to the researcher.');
        }
        $this->redirect(['module' => 'sahra', 'action' => 'permitView', 'id' => $id]);
    }

    // --- SAHRA submission + outcome (coordinator/admin) --------------

    public function executeSahraQueue($request)
    {
        $this->checkAdmin();
        $this->permits = $this->getService()->getSahraQueue();
    }

    public function executeSubmitToSahra($request)
    {
        $this->checkAdmin();
        $id = (int) $request->getParameter('id');
        if ($request->isMethod('post')) {
            $ref = trim((string) $request->getParameter('sahra_reference')) ?: null;
            $notes = trim((string) $request->getParameter('notes')) ?: null;
            if ($this->getService()->submitToSahra($id, $this->userId(), $ref, $notes)) {
                $this->getUser()->setFlash('success', 'Marked as lodged with SAHRA.');
            } else {
                $this->getUser()->setFlash('error', 'Could not submit this application (must be endorsed first).');
            }
        }
        $this->redirect(['module' => 'sahra', 'action' => 'permitView', 'id' => $id]);
    }

    /** SAHRA reviewer queue: applications lodged and awaiting SAHRA's decision. */
    public function executeSahraReview($request)
    {
        $this->checkDecider();
        $this->permits = $this->getService()->getSubmittedQueue();
    }

    public function executeRecordDecision($request)
    {
        $this->checkDecider();
        $id = (int) $request->getParameter('id');
        if ($request->isMethod('post')) {
            $outcome = $request->getParameter('outcome'); // issued | rejected
            $data = [
                'sahra_permit_number' => trim((string) $request->getParameter('sahra_permit_number')) ?: null,
                'sahra_reference' => trim((string) $request->getParameter('sahra_reference')) ?: null,
                'start_date' => $request->getParameter('start_date') ?: null,
                'end_date' => $request->getParameter('end_date') ?: null,
                'conditions' => trim((string) $request->getParameter('conditions')) ?: null,
                'sahra_notes' => trim((string) $request->getParameter('sahra_notes')) ?: null,
            ];
            if ($this->getService()->recordDecision($id, $this->userId(), $outcome === 'issued' ? 'issued' : 'rejected', $data)) {
                $this->getUser()->setFlash('success', $outcome === 'issued' ? 'Permit recorded as issued.' : 'SAHRA decision recorded.');
            } else {
                $this->getUser()->setFlash('error', 'Could not record the decision.');
            }
        }
        $this->redirect(['module' => 'sahra', 'action' => 'permitView', 'id' => $id]);
    }

    public function executeRevoke($request)
    {
        $this->checkAdmin();
        $id = (int) $request->getParameter('id');
        if ($request->isMethod('post')) {
            $notes = trim((string) $request->getParameter('notes')) ?: null;
            $this->getService()->revoke($id, $this->userId(), $notes);
            $this->getUser()->setFlash('success', 'Permit revoked.');
        }
        $this->redirect(['module' => 'sahra', 'action' => 'permitView', 'id' => $id]);
    }

    public function executeCancel($request)
    {
        $this->requireAuth();
        $id = (int) $request->getParameter('id');
        if ($request->isMethod('post')) {
            if ($this->getService()->cancel($id, $this->userId())) {
                $this->getUser()->setFlash('success', 'Application cancelled.');
            } else {
                $this->getUser()->setFlash('error', 'This application cannot be cancelled.');
            }
        }
        $this->redirect('@sahra_my');
    }

    // --- reporting obligations ---------------------------------------

    public function executeReportAdd($request)
    {
        $this->checkAdmin();
        $id = (int) $request->getParameter('id');
        if ($request->isMethod('post')) {
            $this->getService()->addReport($id, [
                'report_type' => $request->getParameter('report_type', 'interim'),
                'due_date' => $request->getParameter('due_date') ?: null,
                'notes' => trim((string) $request->getParameter('notes')) ?: null,
            ], $this->userId());
            $this->getUser()->setFlash('success', 'Reporting obligation added.');
        }
        $this->redirect(['module' => 'sahra', 'action' => 'permitView', 'id' => $id]);
    }

    public function executeReportSubmit($request)
    {
        $this->requireAuth();
        $reportId = (int) $request->getParameter('id');
        $report = \Illuminate\Database\Capsule\Manager::table('sahra_permit_report')->where('id', $reportId)->first();
        if (!$report) {
            $this->forward404('Report not found');
        }
        if ($request->isMethod('post')) {
            $this->getService()->submitReport($reportId, [
                'document_ref' => trim((string) $request->getParameter('document_ref')) ?: null,
                'notes' => trim((string) $request->getParameter('notes')) ?: null,
            ], $this->userId());
            $this->getUser()->setFlash('success', 'Report marked as submitted.');
        }
        $this->redirect(['module' => 'sahra', 'action' => 'permitView', 'id' => (int) $report->permit_id]);
    }

    // --- admin: all permits, reports, config -------------------------

    public function executePermits($request)
    {
        $this->checkAdmin();
        $this->currentStatus = $request->getParameter('status');
        $this->permits = $this->getService()->getApplications(['status' => $this->currentStatus]);
        $this->statusLabels = \AhgSAHRA\Services\SahraPermitService::STATUS_LABELS;
    }

    public function executeReports($request)
    {
        $this->checkAdmin();
        $this->overdue = $this->getService()->getOverdueReports();
    }

    public function executeConfig($request)
    {
        $this->checkAdmin();
        $service = $this->getService();
        if ($request->isMethod('post')) {
            // Master feature gate (also adds/removes the nav links).
            $service->setFeatureEnabled((bool) $request->getParameter('sahra_enabled'));
            foreach (['permit_validity_months', 'default_authority', 'authorities', 'expiry_warning_days', 'application_prefix'] as $key) {
                $val = $request->getParameter($key);
                if ($val !== null) {
                    $service->setConfig($key, is_array($val) ? implode('|', $val) : trim((string) $val));
                }
            }
            $this->getUser()->setFlash('success', 'Settings saved.');
            $this->redirect('@sahra_config');
        }
        $this->featureEnabled = $service->isFeatureEnabled();
        $this->config = [
            'permit_validity_months' => $service->getConfig('permit_validity_months', 36),
            'default_authority' => $service->getConfig('default_authority', 'SAHRA'),
            'authorities' => $service->getConfig('authorities', 'SAHRA'),
            'expiry_warning_days' => $service->getConfig('expiry_warning_days', 30),
            'application_prefix' => $service->getConfig('application_prefix', 'SAHRA-APP'),
        ];
        $this->reviewers = $service->getReviewers();
        $this->authorities = $service->getAuthorities();
        $this->candidateUsers = $this->getSupervisorChoices();
    }

    /** Designate a user as a SAHRA reviewer (can decide from SAHRA's side). */
    public function executeReviewerAdd($request)
    {
        $this->checkAdmin();
        if ($request->isMethod('post')) {
            $userId = (int) $request->getParameter('user_id');
            $authority = trim((string) $request->getParameter('authority')) ?: 'SAHRA';
            if ($userId) {
                $this->getService()->addReviewer($userId, $authority, $this->userId());
                $this->getUser()->setFlash('success', 'SAHRA reviewer added.');
            }
        }
        $this->redirect('@sahra_config');
    }

    public function executeReviewerRemove($request)
    {
        $this->checkAdmin();
        $userId = (int) $request->getParameter('id');
        $this->getService()->removeReviewer($userId);
        $this->getUser()->setFlash('success', 'SAHRA reviewer removed.');
        $this->redirect('@sahra_config');
    }

    // --- helpers ------------------------------------------------------

    /** Candidate supervisors: staff (editors/administrators) by username. */
    protected function getSupervisorChoices(): array
    {
        $db = \Illuminate\Database\Capsule\Manager::class;
        // Users who belong to any ACL group (i.e. staff), fall back to all users.
        $rows = $db::table('user as u')
            ->join('acl_user_group as g', 'u.id', '=', 'g.user_id')
            ->whereNotNull('u.username')
            ->where('u.id', '!=', $this->userId())
            ->select('u.id', 'u.username', 'u.email')
            ->distinct()
            ->orderBy('u.username')
            ->get()
            ->all();

        if (empty($rows)) {
            $rows = $db::table('user')->whereNotNull('username')->where('id', '!=', $this->userId())
                ->orderBy('username')->get(['id', 'username', 'email'])->all();
        }
        return $rows;
    }
}
