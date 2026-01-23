<?php

class RightBatchAction extends sfAction
{
    public function execute($request)
    {
        if (!$this->context->user->isAdministrator()) {
            $this->forward('admin', 'secure');
        }

        \AhgCore\Core\AhgDb::init();

        $this->rightsService = new \AtomFramework\Services\RightsService();
        $culture = $this->context->user->getCulture();

        // Load form data
        $this->records = $this->rightsService->getInformationObjects($culture);
        $this->rightsStatements = $this->rightsService->getRightsStatements();
        $this->ccLicenses = $this->rightsService->getCreativeCommonsLicenses();
        $this->tkLabels = $this->rightsService->getTkLabels();
        $this->rightsHolders = $this->rightsService->getRightsHolders($culture);

        // Initialize results
        $this->success = null;
        $this->failed = null;
        $this->errors = [];
        $this->message = null;

        if ($request->isMethod('post')) {
            $this->processForm($request);
        }
    }

    protected function processForm($request)
    {
        $targetIds = $this->resolveTargetIds($request);

        if (empty($targetIds)) {
            $this->message = 'Please select at least one record.';
            $this->failed = 0;
            $this->success = 0;
            return;
        }

        $action = $request->getParameter('batch_action', 'assign');

        $rightsData = [
            'rights_statement_id' => $request->getParameter('rights_statement_id') ?: null,
            'creative_commons_id' => $request->getParameter('creative_commons_id') ?: null,
            'rights_holder' => $request->getParameter('rights_holder') ?: null,
            'copyright_notice' => $request->getParameter('copyright_notice') ?: null,
            'tk_label_ids' => $request->getParameter('tk_label_ids', []),
            'overwrite' => $request->getParameter('overwrite'),
        ];

        $result = $this->rightsService->applyBatchRights($targetIds, $rightsData, $action);

        $this->success = $result['success'];
        $this->failed = $result['failed'];
        $this->errors = $result['errors'];
        $this->message = "Batch complete: {$result['success']} successful, {$result['failed']} failed";
    }

    protected function resolveTargetIds($request)
    {
        $ids = [];

        // Option A: From dropdown
        $infoObjectId = $request->getParameter('information_object_id');
        if ($infoObjectId) {
            $scope = $request->getParameter('scope', 'selected');
            $includeParent = $request->getParameter('include_parent');

            if ($scope === 'selected' || $includeParent) {
                $ids[] = (int) $infoObjectId;
            }

            if ($scope === 'children') {
                $ids = array_merge($ids, $this->rightsService->getChildIds((int) $infoObjectId));
            }

            if ($scope === 'all_descendants') {
                $ids = array_merge($ids, $this->rightsService->getAllDescendantIds((int) $infoObjectId));
            }
        }

        // Option B: Manual IDs
        $objectIds = $request->getParameter('object_ids');
        if ($objectIds) {
            $manualIds = array_filter(array_map('intval', explode(',', $objectIds)));
            $ids = array_merge($ids, $manualIds);
        }

        return array_unique($ids);
    }
}
