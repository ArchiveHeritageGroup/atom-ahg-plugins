<?php

use AtomFramework\Http\Controllers\AhgController;

/**
 * Create or edit a serial subscription.
 *
 * GET: display form.
 * POST: validate and save.
 */
class serialEditAction extends AhgController
{
    public function execute($request)
    {
        
        // Load framework
        require_once $this->config('sf_root_dir') . '/atom-framework/bootstrap.php';

        // Load SerialService
        $servicePath = \sfConfig::get('sf_plugins_dir')
            . '/ahgLibraryPlugin/lib/Service/SerialService.php';
        if (file_exists($servicePath)) {
            require_once $servicePath;
        }

        $this->error = null;
        $this->notice = null;
        $this->subscriptionId = (int) $request->getParameter('id', 0);
        $this->subscription = null;

        // Load existing subscription for edit
        if ($this->subscriptionId) {
            try {
                $service = SerialService::getInstance();
                $data = $service->getSubscription($this->subscriptionId);
                if ($data) {
                    $this->subscription = $data['subscription'];
                }
            } catch (\Exception $e) {
                // ignore — form will be blank
            }
        }

        // POST: save
        if ('POST' === $request->getMethod()) {
            $libraryItemId = $request->getParameter('library_item_id');

            if (empty($libraryItemId)) {
                $this->error = __('Library item ID is required.');
                return;
            }

            $formData = [
                'library_item_id'      => (int) $libraryItemId,
                'vendor_name'          => $request->getParameter('vendor_name'),
                'subscription_number'  => $request->getParameter('subscription_number'),
                'frequency'            => $request->getParameter('frequency', 'monthly'),
                'start_date'           => $request->getParameter('start_date'),
                'end_date'             => $request->getParameter('end_date'),
                'renewal_date'         => $request->getParameter('renewal_date'),
                'expected_issues_year' => $request->getParameter('expected_issues_year'),
                'cost_per_year'        => $request->getParameter('cost_per_year'),
                'currency'             => $request->getParameter('currency', 'USD'),
                'notes'                => $request->getParameter('notes'),
            ];

            try {
                if (!class_exists('SerialService')) {
                    throw new \RuntimeException('SerialService not available.');
                }

                $service = SerialService::getInstance();

                if ($this->subscriptionId) {
                    $service->updateSubscription($this->subscriptionId, $formData);
                    $this->getUser()->setFlash('notice', __('Subscription updated successfully.'));
                    $this->redirect(['module' => 'serial', 'action' => 'view', 'id' => $this->subscriptionId]);
                } else {
                    $newId = $service->createSubscription($formData);
                    $this->getUser()->setFlash('notice', __('Subscription created successfully.'));
                    $this->redirect(['module' => 'serial', 'action' => 'view', 'id' => $newId]);
                }
            } catch (\Exception $e) {
                $this->error = __('Error saving subscription: %1%', ['%1%' => $e->getMessage()]);
            }
        }
    }
}
