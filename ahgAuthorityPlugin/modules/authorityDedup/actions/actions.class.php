<?php

/**
 * Authority Dedup module actions (#208).
 *
 * Bulk deduplication tool for authority records.
 */
class authorityDedupActions extends sfActions
{
    protected function dedupeService(): \AhgAuthority\Services\AuthorityDedupeService
    {
        require_once dirname(__FILE__) . '/../../../lib/Services/AuthorityDedupeService.php';

        return new \AhgAuthority\Services\AuthorityDedupeService();
    }

    protected function mergeService(): \AhgAuthority\Services\AuthorityMergeService
    {
        require_once dirname(__FILE__) . '/../../../lib/Services/AuthorityMergeService.php';

        return new \AhgAuthority\Services\AuthorityMergeService();
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
    // DEDUP DASHBOARD
    // =========================================================================

    public function executeIndex(sfWebRequest $request)
    {
        $this->requireEditor();

        $this->stats = $this->dedupeService()->getStats();

        // Use indexSuccess.php template
    }

    // =========================================================================
    // SCAN
    // =========================================================================

    public function executeScan(sfWebRequest $request)
    {
        $this->requireEditor();

        if ($request->isMethod('post')) {
            $limit = (int) $request->getParameter('limit', 500);
            $this->pairs = $this->dedupeService()->scan($limit);
            $this->getUser()->setFlash('notice', count($this->pairs) . ' potential duplicate pair(s) found.');
        } else {
            $this->pairs = [];
        }

        // Use scanSuccess.php template
    }

    // =========================================================================
    // COMPARE
    // =========================================================================

    public function executeCompare(sfWebRequest $request)
    {
        $this->requireEditor();

        $primaryId = (int) $request->getParameter('id');
        $secondaryId = (int) $request->getParameter('secondary_id');

        if (!$secondaryId) {
            $this->forward404();
        }

        $this->comparison = $this->mergeService()->compareActors($primaryId, $secondaryId);

        // Use compareSuccess.php template
    }

    // =========================================================================
    // API: DISMISS
    // =========================================================================

    public function executeApiDismiss(sfWebRequest $request)
    {
        $this->requireEditor();

        // Mark a pair as dismissed (false positive) — store in merge table as rejected
        $pairId = (int) $request->getParameter('id');
        $merge = $this->mergeService()->getMerge($pairId);

        if ($merge) {
            \Illuminate\Database\Capsule\Manager::table('ahg_actor_merge')
                ->where('id', $pairId)
                ->update(['status' => 'rejected']);
        }

        return $this->jsonResponse(['success' => true]);
    }

    // =========================================================================
    // API: MERGE
    // =========================================================================

    public function executeApiMerge(sfWebRequest $request)
    {
        $userId = $this->requireEditor();

        $mergeId = (int) $request->getParameter('id');
        $result = $this->mergeService()->executeMerge($mergeId, $userId);

        return $this->jsonResponse(['success' => $result]);
    }
}
