<?php

class embargoActions extends sfActions
{
    protected function getService(): \App\Services\Rights\EmbargoService
    {
        return new \App\Services\Rights\EmbargoService();
    }

    public function executeIndex(sfWebRequest $request)
    {
        $this->checkAuthorization();
        
        $service = $this->getService();
        $this->activeEmbargoes = $service->getActiveEmbargoes();
        $this->expiringEmbargoes = $service->getExpiringEmbargoes(30);
    }

    public function executeView(sfWebRequest $request)
    {
        $this->checkAuthorization();
        
        $embargoId = $request->getParameter('id');
        $service = $this->getService();
        $this->embargo = $service->getEmbargo((int)$embargoId);
        
        if (!$this->embargo) {
            $this->forward404();
        }
    }

    public function executeObject(sfWebRequest $request)
    {
        $this->resource = $this->getRoute()->resource;
        if (!isset($this->resource)) {
            $this->forward404();
        }

        $service = $this->getService();
        $this->embargoes = $service->getObjectEmbargoes($this->resource->id);
    }

    public function executeAdd(sfWebRequest $request)
    {
        $this->checkAuthorization();
        
        $this->resource = $this->getRoute()->resource;
        if (!isset($this->resource)) {
            $this->forward404();
        }

        if ($request->isMethod('post')) {
            $this->processAddForm($request);
        }
    }

    protected function processAddForm(sfWebRequest $request)
    {
        $service = $this->getService();
        
        $data = [
            'embargo_type' => $request->getParameter('embargo_type'),
            'start_date' => $request->getParameter('start_date'),
            'end_date' => $request->getParameter('end_date') ?: null,
            'is_perpetual' => $request->getParameter('is_perpetual', false),
            'notify_on_expiry' => $request->getParameter('notify_on_expiry', true),
            'notify_days_before' => $request->getParameter('notify_days_before', 30),
            'user_id' => $this->context->user->getAttribute('user_id'),
            'i18n' => [
                $this->context->user->getCulture() => [
                    'reason' => $request->getParameter('reason'),
                    'notes' => $request->getParameter('notes'),
                    'public_message' => $request->getParameter('public_message'),
                ],
            ],
        ];

        $service->createEmbargo($this->resource->id, $data);

        $this->redirect([
            'module' => 'informationobject',
            'action' => 'embargo',
            'slug' => $this->resource->slug,
        ]);
    }

    public function executeEdit(sfWebRequest $request)
    {
        $this->checkAuthorization();
        
        $embargoId = $request->getParameter('id');
        $service = $this->getService();
        $this->embargo = $service->getEmbargo((int)$embargoId);
        
        if (!$this->embargo) {
            $this->forward404();
        }

        if ($request->isMethod('post')) {
            $this->processEditForm($request, (int)$embargoId);
        }
    }

    protected function processEditForm(sfWebRequest $request, int $embargoId)
    {
        $service = $this->getService();
        
        $data = [
            'embargo_type' => $request->getParameter('embargo_type'),
            'start_date' => $request->getParameter('start_date'),
            'end_date' => $request->getParameter('end_date') ?: null,
            'is_perpetual' => $request->getParameter('is_perpetual', false),
            'notify_on_expiry' => $request->getParameter('notify_on_expiry', true),
            'notify_days_before' => $request->getParameter('notify_days_before', 30),
            'user_id' => $this->context->user->getAttribute('user_id'),
            'i18n' => [
                $this->context->user->getCulture() => [
                    'reason' => $request->getParameter('reason'),
                    'notes' => $request->getParameter('notes'),
                    'public_message' => $request->getParameter('public_message'),
                ],
            ],
        ];

        $service->updateEmbargo($embargoId, $data);

        $this->redirect(['module' => 'embargo', 'action' => 'view', 'id' => $embargoId]);
    }

    public function executeLift(sfWebRequest $request)
    {
        $this->checkAuthorization();
        
        $embargoId = $request->getParameter('id');
        
        if ($request->isMethod('post')) {
            $service = $this->getService();
            $reason = $request->getParameter('lift_reason');
            $service->liftEmbargo((int)$embargoId, $reason);
        }

        $this->redirect(['module' => 'embargo', 'action' => 'view', 'id' => $embargoId]);
    }

    public function executeAddException(sfWebRequest $request)
    {
        $this->checkAuthorization();
        
        $embargoId = $request->getParameter('embargo_id');
        
        if ($request->isMethod('post')) {
            $service = $this->getService();
            
            $data = [
                'exception_type' => $request->getParameter('exception_type'),
                'exception_id' => $request->getParameter('exception_id'),
                'ip_range_start' => $request->getParameter('ip_range_start'),
                'ip_range_end' => $request->getParameter('ip_range_end'),
                'valid_from' => $request->getParameter('valid_from'),
                'valid_until' => $request->getParameter('valid_until'),
                'notes' => $request->getParameter('notes'),
                'user_id' => $this->context->user->getAttribute('user_id'),
            ];

            $service->addException((int)$embargoId, $data);
        }

        $this->redirect(['module' => 'embargo', 'action' => 'view', 'id' => $embargoId]);
    }

    public function executeRemoveException(sfWebRequest $request)
    {
        $this->checkAuthorization();
        
        $exceptionId = $request->getParameter('id');
        $embargoId = $request->getParameter('embargo_id');
        
        $service = $this->getService();
        $service->removeException((int)$exceptionId);

        $this->redirect(['module' => 'embargo', 'action' => 'view', 'id' => $embargoId]);
    }

    protected function checkAuthorization()
    {
        if (!$this->context->user->isAuthenticated()) {
            $this->redirect(['module' => 'user', 'action' => 'login']);
        }
    }
}
