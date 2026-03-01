<?php

/**
 * Authority NER Pipeline module actions (#204).
 *
 * NER-to-authority record creation pipeline.
 */
class authorityNerActions extends sfActions
{
    protected function nerService(): \AhgAuthority\Services\AuthorityNerPipelineService
    {
        require_once dirname(__FILE__) . '/../../../lib/Services/AuthorityNerPipelineService.php';

        return new \AhgAuthority\Services\AuthorityNerPipelineService();
    }

    protected function requireAuth(): int
    {
        if (!$this->context->user->isAuthenticated()) {
            $this->forward('admin', 'secure');
        }

        return (int) $this->context->user->getAttribute('user_id');
    }

    protected function requireEditor(): int
    {
        $userId = $this->requireAuth();
        if (!$this->context->user->hasCredential('editor') && !$this->context->user->isAdministrator()) {
            $this->forward('admin', 'secure');
        }

        return $userId;
    }

    protected function jsonResponse(array $data): string
    {
        $this->getResponse()->setContentType('application/json');

        return $this->renderText(json_encode($data));
    }

    // =========================================================================
    // NER PIPELINE DASHBOARD
    // =========================================================================

    public function executeIndex(sfWebRequest $request)
    {
        $this->requireEditor();

        $service = $this->nerService();

        $this->stats = $service->getStats();

        $this->filters = [
            'status'      => $request->getParameter('status', 'stub'),
            'entity_type' => $request->getParameter('entity_type', ''),
            'search'      => $request->getParameter('search', ''),
            'sort'        => $request->getParameter('sort', 's.created_at'),
            'sortDir'     => $request->getParameter('sortDir', 'desc'),
            'page'        => $request->getParameter('page', 1),
            'limit'       => $request->getParameter('limit', 50),
        ];

        $this->stubs = $service->getStubs($this->filters);

        // Get pending NER entities (not yet stubbed)
        $this->pendingFilters = [
            'entity_type'    => $request->getParameter('entity_type', ''),
            'min_confidence' => $request->getParameter('min_confidence', ''),
            'search'         => $request->getParameter('search', ''),
            'sort'           => 'ne.confidence',
            'sortDir'        => 'desc',
            'page'           => 1,
            'limit'          => 20,
        ];

        $this->pendingEntities = $service->getPendingEntities($this->pendingFilters);

        // Use indexSuccess.php template
    }

    // =========================================================================
    // API: CREATE STUB
    // =========================================================================

    public function executeApiCreateStub(sfWebRequest $request)
    {
        $userId = $this->requireEditor();

        $nerEntityId = (int) $request->getParameter('ner_entity_id');
        $service = $this->nerService();

        $actorId = $service->createStub($nerEntityId, $userId);

        if ($actorId) {
            return $this->jsonResponse(['success' => true, 'actor_id' => $actorId]);
        }

        return $this->jsonResponse(['success' => false, 'error' => 'Failed to create stub']);
    }

    // =========================================================================
    // API: PROMOTE STUB
    // =========================================================================

    public function executeApiPromote(sfWebRequest $request)
    {
        $userId = $this->requireEditor();

        $stubId = (int) $request->getParameter('id');
        $result = $this->nerService()->promoteStub($stubId, $userId);

        return $this->jsonResponse(['success' => $result]);
    }

    // =========================================================================
    // API: REJECT STUB
    // =========================================================================

    public function executeApiReject(sfWebRequest $request)
    {
        $userId = $this->requireEditor();

        $stubId = (int) $request->getParameter('id');
        $result = $this->nerService()->rejectStub($stubId, $userId);

        return $this->jsonResponse(['success' => $result]);
    }
}
