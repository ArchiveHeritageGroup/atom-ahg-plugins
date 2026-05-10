<?php

/**
 * sharepoint module — admin UI + AJAX endpoints + (Phase 2) webhook receiver.
 *
 * Phase 1 actions: index, tenants, tenantEdit, tenantTest, drives, driveBrowse, mapping.
 * Phase 2 actions: subscriptions, events, eventDetail, webhook (returns 503 in Phase 1).
 * Phase 3 action: federatedSearch.
 *
 * @phase 1
 */
class sharepointActions extends sfActions
{
    public function executeIndex(sfWebRequest $request)
    {
        $this->checkAdmin();
        // TODO (Phase 1): aggregate tenant/drive/sync_state counts for the dashboard tile grid.
        return sfView::SUCCESS;
    }

    public function executeTenants(sfWebRequest $request)
    {
        $this->checkAdmin();
        // TODO: list rows from SharePointTenantRepository::all().
        return sfView::SUCCESS;
    }

    public function executeTenantEdit(sfWebRequest $request)
    {
        $this->checkAdmin();
        // TODO: GET = render form, POST = validate + persist via repository.
        // client_secret field is write-only (renders empty, only updates on non-empty submit).
        // Encrypt client_secret via EncryptionService before persisting.
        return sfView::SUCCESS;
    }

    public function executeTenantTest(sfWebRequest $request)
    {
        $this->checkAdmin();
        // TODO: invoke same logic as `php symfony sharepoint:test-connection --tenant=:id`,
        // return JSON result for AJAX consumer in tenantEdit page.
        $this->renderText(json_encode(['status' => 'not_implemented']));
        return sfView::NONE;
    }

    public function executeDrives(sfWebRequest $request)
    {
        $this->checkAdmin();
        // TODO: list registered drives across all tenants.
        return sfView::SUCCESS;
    }

    public function executeDriveBrowse(sfWebRequest $request)
    {
        $this->checkAdmin();
        // TODO: AJAX — given tenantId, GET /sites + drives via Graph; return JSON for picker UI.
        $this->renderText(json_encode(['status' => 'not_implemented']));
        return sfView::NONE;
    }

    public function executeMapping(sfWebRequest $request)
    {
        $this->checkAdmin();
        // TODO: GET = render mapping editor (current rows + SP columns inferred via Graph).
        // POST = persist rows in sharepoint_mapping.
        return sfView::SUCCESS;
    }

    // ---- Phase 2 stubs ----

    public function executeSubscriptions(sfWebRequest $request)
    {
        return $this->phaseUnavailable(2);
    }

    public function executeEvents(sfWebRequest $request)
    {
        return $this->phaseUnavailable(2);
    }

    public function executeEventDetail(sfWebRequest $request)
    {
        return $this->phaseUnavailable(2);
    }

    /**
     * PUBLIC, NO CSRF, NO AUTH. Graph webhook receiver.
     * In Phase 1 this returns 503 so a misconfigured Graph subscription
     * fails loudly rather than silently dropping.
     */
    public function executeWebhook(sfWebRequest $request)
    {
        // Phase 1: subscriptions don't exist yet. Return 503 with explanatory body.
        $this->getResponse()->setStatusCode(503);
        $this->getResponse()->setContentType('text/plain');
        $this->renderText('SharePoint webhook receiver not enabled (Phase 2). See ahgSharePointPlugin docs.');
        return sfView::NONE;
    }

    // ---- Phase 3 stubs ----

    public function executeFederatedSearch(sfWebRequest $request)
    {
        return $this->phaseUnavailable(3);
    }

    // ---- helpers ----

    private function checkAdmin(): void
    {
        if (!\sfContext::getInstance()->getUser()->hasGroup(\QubitAclGroup::ADMINISTRATOR_ID)) {
            $this->forward('admin', 'secure');
        }
    }

    private function phaseUnavailable(int $phase): string
    {
        $this->getResponse()->setStatusCode(503);
        $this->renderText("Action belongs to Phase {$phase} of ahgSharePointPlugin (not yet shipped).");
        return sfView::NONE;
    }
}
