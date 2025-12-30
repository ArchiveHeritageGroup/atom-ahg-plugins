<?php
class extendedRightsBatchAction extends sfAction
{
    public function execute($request)
    {
        if (!$this->context->user->isAuthenticated()) {
            $this->redirect(['module' => 'user', 'action' => 'login']);
        }
        $culture = $this->context->user->getCulture();

        // Load service
        require_once sfConfig::get('sf_root_dir').'/atom-framework/app/Services/Rights/ExtendedRightsService.php';
        $service = new \App\Services\Rights\ExtendedRightsService($culture);

        $this->rightsStatements = $service->getRightsStatements();
        $this->ccLicenses = $service->getCreativeCommonsLicenses();
        $this->tkLabels = $service->getTkLabels();
        $this->donors = $service->getDonors(1000);
        $this->topLevelRecords = $service->getTopLevelRecords(1000);

        // Handle form submission
        if ($request->isMethod('post')) {
            $batchAction = $request->getParameter('batch_action', 'assign');
            $objectIds = $request->getParameter('object_ids', []);
            $overwrite = $request->getParameter('overwrite', false);

            // Ensure it's an array
            if (!is_array($objectIds)) {
                $objectIds = [$objectIds];
            }
            $objectIds = array_filter(array_map('intval', $objectIds));

            if (empty($objectIds)) {
                $this->getUser()->setFlash('error', 'Please select at least one object.');
                return;
            }

            $count = 0;

            foreach ($objectIds as $objectId) {
                if ($objectId <= 0) continue;

                switch ($batchAction) {
                    case 'assign':
                        $rsId = $request->getParameter('rights_statement_id');
                        $ccId = $request->getParameter('creative_commons_id');
                        $tkIds = $request->getParameter('tk_label_ids', []);
                        $donorId = $request->getParameter('rights_holder_id');

                        if ($rsId) {
                            $service->assignRightsStatement($objectId, (int)$rsId);
                        }
                        if ($ccId) {
                            $service->assignCreativeCommons($objectId, (int)$ccId);
                        }
                        if (is_array($tkIds)) {
                            foreach ($tkIds as $tkId) {
                                $service->assignTkLabel($objectId, (int)$tkId);
                            }
                        }
                        if ($donorId) {
                            $service->assignRightsHolder($objectId, (int)$donorId);
                        }
                        $count++;
                        break;

                    case 'embargo':
                        $embargoType = $request->getParameter('embargo_type', 'full');
                        $startDate = date('Y-m-d');
                        $endDate = $request->getParameter('embargo_end_date');
                        $service->createEmbargo($objectId, $embargoType, $startDate, $endDate ?: null);
                        $count++;
                        break;

                    case 'clear':
                        $service->clearRights($objectId);
                        $count++;
                        break;
                }
            }

            $this->getUser()->setFlash('notice', sprintf('Batch operation completed on %d objects.', $count));
            $this->redirect(['module' => 'extendedRights', 'action' => 'dashboard']);
        }
    }
}
