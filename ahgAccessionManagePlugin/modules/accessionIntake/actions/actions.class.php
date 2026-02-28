<?php

class accessionIntakeActions extends sfActions
{
    /**
     * Get the intake service (lazy load).
     */
    protected function getIntakeService(): \AhgAccessionManage\Services\AccessionIntakeService
    {
        return new \AhgAccessionManage\Services\AccessionIntakeService();
    }

    protected function getContainerService(): \AhgAccessionManage\Services\AccessionContainerService
    {
        return new \AhgAccessionManage\Services\AccessionContainerService(null, $this->getIntakeService());
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
        if (!$this->context->user->hasCredential('editor')) {
            $this->forward('admin', 'secure');
        }

        return $userId;
    }

    // =========================================================================
    // INTAKE QUEUE
    // =========================================================================

    public function executeQueue(sfWebRequest $request)
    {
        $this->requireAuth();

        $service = $this->getIntakeService();

        $this->filters = [
            'status' => $request->getParameter('status', ''),
            'priority' => $request->getParameter('priority', ''),
            'assigned_to' => $request->getParameter('assigned_to', ''),
            'search' => $request->getParameter('search', ''),
            'sort' => $request->getParameter('sort', 'created_at'),
            'sortDir' => $request->getParameter('sortDir', 'desc'),
            'page' => $request->getParameter('page', 1),
            'limit' => $request->getParameter('limit', 30),
        ];

        $this->queueData = $service->getQueue($this->filters);
        $this->stats = $service->getQueueStats();

        // Get list of users for assignment dropdown
        $this->users = \Illuminate\Database\Capsule\Manager::table('user')
            ->join('actor_i18n', function ($j) {
                $j->on('user.id', '=', 'actor_i18n.id')
                    ->where('actor_i18n.culture', '=', 'en');
            })
            ->select('user.id', 'actor_i18n.authorized_form_of_name as name')
            ->orderBy('actor_i18n.authorized_form_of_name')
            ->get()
            ->all();

        $this->statuses = ['draft', 'submitted', 'under_review', 'accepted', 'rejected', 'returned'];
        $this->priorities = ['low', 'normal', 'high', 'urgent'];

        return '_blade';
    }

    public function executeQueueDetail(sfWebRequest $request)
    {
        $this->requireAuth();

        $accessionId = (int) $request->getParameter('id');
        $service = $this->getIntakeService();

        $this->accession = \AhgAccessionManage\Services\AccessionCrudService::getById($accessionId);
        if (!$this->accession) {
            $this->forward404();
        }

        $service->ensureV2Record($accessionId);

        $this->v2 = $service->getV2Record($accessionId);
        $this->checklist = $service->getChecklist($accessionId);
        $this->checklistProgress = $service->getChecklistProgress($accessionId);
        $this->timeline = $service->getTimeline($accessionId);
        $this->attachments = $service->getAttachments($accessionId);
        $this->checklistTemplates = $service->getChecklistTemplates();

        return '_blade';
    }

    public function executeSubmit(sfWebRequest $request)
    {
        $userId = $this->requireEditor();

        if ($request->isMethod('post')) {
            $accessionId = (int) $request->getParameter('id');
            $service = $this->getIntakeService();

            if ($service->submit($accessionId, $userId)) {
                $this->getUser()->setFlash('notice', 'Accession submitted for review.');
            } else {
                $this->getUser()->setFlash('error', 'Cannot submit accession. Check status and checklist completion.');
            }

            $this->redirect('@accession_intake_detail?id=' . $accessionId);
        }

        $this->forward404();
    }

    public function executeAssign(sfWebRequest $request)
    {
        $userId = $this->requireEditor();

        if ($request->isMethod('post')) {
            $accessionId = (int) $request->getParameter('accession_id');
            $assigneeId = (int) $request->getParameter('assignee_id');
            $service = $this->getIntakeService();

            if ($service->assign($accessionId, $assigneeId, $userId)) {
                $this->getUser()->setFlash('notice', 'Accession assigned successfully.');
            } else {
                $this->getUser()->setFlash('error', 'Failed to assign accession.');
            }

            $this->redirect('@accession_intake_queue');
        }

        $this->forward404();
    }

    public function executeReview(sfWebRequest $request)
    {
        $userId = $this->requireEditor();

        if ($request->isMethod('post')) {
            $accessionId = (int) $request->getParameter('id');
            $service = $this->getIntakeService();

            if ($service->review($accessionId, $userId)) {
                $this->getUser()->setFlash('notice', 'Accession is now under review.');
            } else {
                $this->getUser()->setFlash('error', 'Cannot move accession to review. Check current status.');
            }

            $this->redirect('@accession_intake_detail?id=' . $accessionId);
        }

        $this->forward404();
    }

    public function executeAccept(sfWebRequest $request)
    {
        $userId = $this->requireEditor();

        if ($request->isMethod('post')) {
            $accessionId = (int) $request->getParameter('id');
            $service = $this->getIntakeService();

            if ($service->accept($accessionId, $userId)) {
                $this->getUser()->setFlash('notice', 'Accession accepted.');
            } else {
                $this->getUser()->setFlash('error', 'Cannot accept accession. Check current status.');
            }

            $this->redirect('@accession_intake_detail?id=' . $accessionId);
        }

        $this->forward404();
    }

    public function executeReject(sfWebRequest $request)
    {
        $userId = $this->requireEditor();

        if ($request->isMethod('post')) {
            $accessionId = (int) $request->getParameter('id');
            $reason = $request->getParameter('rejection_reason', '');
            $service = $this->getIntakeService();

            if ($service->reject($accessionId, $reason, $userId)) {
                $this->getUser()->setFlash('notice', 'Accession rejected.');
            } else {
                $this->getUser()->setFlash('error', 'Cannot reject accession. Check current status.');
            }

            $this->redirect('@accession_intake_detail?id=' . $accessionId);
        }

        $this->forward404();
    }

    public function executeReturnRevision(sfWebRequest $request)
    {
        $userId = $this->requireEditor();

        if ($request->isMethod('post')) {
            $accessionId = (int) $request->getParameter('id');
            $notes = $request->getParameter('return_notes', '');
            $service = $this->getIntakeService();

            if ($service->returnForRevision($accessionId, $notes, $userId)) {
                $this->getUser()->setFlash('notice', 'Accession returned for revision.');
            } else {
                $this->getUser()->setFlash('error', 'Cannot return accession. Check current status.');
            }

            $this->redirect('@accession_intake_detail?id=' . $accessionId);
        }

        $this->forward404();
    }

    // =========================================================================
    // TIMELINE
    // =========================================================================

    public function executeTimeline(sfWebRequest $request)
    {
        $this->requireAuth();

        $accessionId = (int) $request->getParameter('id');
        $service = $this->getIntakeService();

        $this->accession = \AhgAccessionManage\Services\AccessionCrudService::getById($accessionId);
        if (!$this->accession) {
            $this->forward404();
        }

        $this->timeline = $service->getTimeline($accessionId);

        return '_blade';
    }

    // =========================================================================
    // CHECKLIST
    // =========================================================================

    public function executeChecklist(sfWebRequest $request)
    {
        $this->requireAuth();

        $accessionId = (int) $request->getParameter('id');
        $service = $this->getIntakeService();

        $this->accession = \AhgAccessionManage\Services\AccessionCrudService::getById($accessionId);
        if (!$this->accession) {
            $this->forward404();
        }

        $this->checklist = $service->getChecklist($accessionId);
        $this->checklistProgress = $service->getChecklistProgress($accessionId);
        $this->checklistTemplates = $service->getChecklistTemplates();

        return '_blade';
    }

    public function executeApiChecklistToggle(sfWebRequest $request)
    {
        $userId = $this->requireAuth();

        $itemId = (int) $request->getParameter('id');
        $service = $this->getIntakeService();
        $result = $service->toggleChecklistItem($itemId, $userId);

        $this->getResponse()->setContentType('application/json');

        return $this->renderText(json_encode(['success' => $result]));
    }

    public function executeApiChecklistApplyTemplate(sfWebRequest $request)
    {
        $this->requireEditor();

        $accessionId = (int) $request->getParameter('accession_id');
        $templateId = (int) $request->getParameter('template_id');
        $service = $this->getIntakeService();
        $count = $service->applyChecklistTemplate($accessionId, $templateId);

        $this->getResponse()->setContentType('application/json');

        return $this->renderText(json_encode(['success' => true, 'count' => $count]));
    }

    // =========================================================================
    // ATTACHMENTS
    // =========================================================================

    public function executeAttachments(sfWebRequest $request)
    {
        $this->requireAuth();

        $accessionId = (int) $request->getParameter('id');
        $service = $this->getIntakeService();

        $this->accession = \AhgAccessionManage\Services\AccessionCrudService::getById($accessionId);
        if (!$this->accession) {
            $this->forward404();
        }

        $this->attachments = $service->getAttachments($accessionId);

        return '_blade';
    }

    public function executeApiAttachmentUpload(sfWebRequest $request)
    {
        $userId = $this->requireEditor();

        $accessionId = (int) $request->getParameter('accession_id');
        $category = $request->getParameter('category', 'general');
        $service = $this->getIntakeService();

        $file = $request->getFiles('file');
        if (!$file || !isset($file['tmp_name'])) {
            $this->getResponse()->setContentType('application/json');

            return $this->renderText(json_encode(['success' => false, 'error' => 'No file uploaded']));
        }

        $id = $service->addAttachment($accessionId, $file, $category, $userId);

        $this->getResponse()->setContentType('application/json');

        return $this->renderText(json_encode(['success' => true, 'id' => $id]));
    }

    public function executeApiAttachmentDelete(sfWebRequest $request)
    {
        $this->requireEditor();

        $attachmentId = (int) $request->getParameter('id');
        $service = $this->getIntakeService();
        $result = $service->deleteAttachment($attachmentId);

        $this->getResponse()->setContentType('application/json');

        return $this->renderText(json_encode(['success' => $result]));
    }

    // =========================================================================
    // CONFIGURATION
    // =========================================================================

    public function executeConfig(sfWebRequest $request)
    {
        $this->requireEditor();

        $containerService = $this->getContainerService();

        if ($request->isMethod('post')) {
            $settings = $request->getParameter('config', []);
            foreach ($settings as $key => $value) {
                $containerService->setConfig($key, $value);
            }
            $this->getUser()->setFlash('notice', 'Configuration saved.');
            $this->redirect('@accession_intake_config');
        }

        $this->config = $containerService->getAllConfig();
        $this->checklistTemplates = $this->getIntakeService()->getChecklistTemplates();
        $appraisalService = new \AhgAccessionManage\Services\AccessionAppraisalService();
        $this->appraisalTemplates = $appraisalService->listTemplates();

        return '_blade';
    }

    public function executeNumbering(sfWebRequest $request)
    {
        $this->requireEditor();

        $this->sequences = \Illuminate\Database\Capsule\Manager::table('accession_numbering_sequence')
            ->leftJoin('repository_i18n as ri', function ($j) {
                $j->on('accession_numbering_sequence.repository_id', '=', 'ri.id')
                    ->where('ri.culture', '=', 'en');
            })
            ->select('accession_numbering_sequence.*', 'ri.authorized_form_of_name as repo_name')
            ->orderBy('accession_numbering_sequence.repository_id')
            ->get()
            ->all();

        $containerService = $this->getContainerService();
        $this->defaultMask = $containerService->getConfig('numbering_mask', '{YEAR}-{SEQ:5}');

        return '_blade';
    }
}
