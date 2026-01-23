<?php

use Illuminate\Database\Capsule\Manager as DB;

class embargoActions extends sfActions
{
    protected function initDb()
    {
        // Initialize Laravel DB via AhgDb (idempotent)
        \AhgCore\Core\AhgDb::init();
    }

    
    protected function getService(): \ahgExtendedRightsPlugin\Services\EmbargoService
    {
        $this->initDb();
        require_once sfConfig::get('sf_root_dir') . '/atom-ahg-plugins/ahgExtendedRightsPlugin/lib/Services/EmbargoService.php';
        return new \ahgExtendedRightsPlugin\Services\EmbargoService();
    }

    protected function getResource(int $objectId)
    {
        $this->initDb();
        require_once sfConfig::get('sf_root_dir') . '/atom-ahg-plugins/ahgExtendedRightsPlugin/lib/Services/EmbargoService.php';
        return DB::table('information_object as io')
            ->leftJoin('information_object_i18n as ioi', function ($join) {
                $join->on('io.id', '=', 'ioi.id')
                    ->where('ioi.culture', '=', 'en');
            })
            ->leftJoin('slug', 'io.id', '=', 'slug.object_id')
            ->where('io.id', $objectId)
            ->select(['io.id', 'io.lft', 'io.rgt', 'io.parent_id', 'ioi.title', 'slug.slug'])
            ->first();
    }

    public function executeIndex(sfWebRequest $request)
    {
        $service = $this->getService();
        $this->activeEmbargoes = $service->getActiveEmbargoes();
        $this->expiringEmbargoes = $service->getExpiringEmbargoes(30);
    }

    public function executeView(sfWebRequest $request)
    {
        $embargoId = $request->getParameter('id');
        $service = $this->getService();
        $this->embargo = $service->getEmbargo((int)$embargoId);
        if (!$this->embargo) {
            $this->forward404();
        }
    }

    public function executeAdd(sfWebRequest $request)
    {
        $objectId = $request->getParameter('objectId');
        if (!$objectId) {
            $this->forward404('Object ID required');
        }

        $this->resource = $this->getResource((int)$objectId);
        if (!$this->resource) {
            $this->forward404('Object not found');
        }

        $this->objectId = $objectId;

        if ($request->isMethod('post')) {
            $this->processAddForm($request, (int)$objectId);
            return sfView::NONE;
        }
    }

    protected function processAddForm(sfWebRequest $request, int $objectId)
    {
        $service = $this->getService();
        $userId = $this->context->user->getAttribute('user_id');

        $data = [
            'embargo_type' => $request->getParameter('embargo_type', 'full'),
            'start_date' => $request->getParameter('start_date', date('Y-m-d')),
            'end_date' => $request->getParameter('end_date') ?: null,
            'is_perpetual' => (bool)$request->getParameter('is_perpetual', false),
            'notify_on_expiry' => (bool)$request->getParameter('notify_on_expiry', true),
            'notify_days_before' => (int)$request->getParameter('notify_days_before', 30),
            'reason' => $request->getParameter('reason'),
            'notes' => $request->getParameter('notes'),
            'public_message' => $request->getParameter('public_message'),
        ];

        // Check if propagation to children is requested
        $applyToChildren = (bool)$request->getParameter('apply_to_children', false);
        
        if ($applyToChildren) {
            $results = $service->createEmbargoWithPropagation($objectId, $data, $userId, true);
            // Flash message with results
            $this->context->user->setFlash('notice', sprintf(
                'Embargo created for %d records (%d failed)',
                $results['created'],
                $results['failed']
            ));
        } else {
            $service->createEmbargo($objectId, $data, $userId);
            $this->context->user->setFlash('notice', 'Embargo created successfully');
        }

        $resource = $this->getResource($objectId);
        $this->redirect(['module' => 'informationobject', 'slug' => $resource->slug]);
    }

    public function executeEdit(sfWebRequest $request)
    {
        $embargoId = $request->getParameter('id');
        $objectId = $request->getParameter('objectId');

        $service = $this->getService();

        if ($embargoId) {
            $this->embargo = $service->getEmbargo((int)$embargoId);
            if (!$this->embargo) {
                $this->forward404('Embargo not found');
            }
            $this->resource = $this->getResource($this->embargo->object_id);
            $this->objectId = $this->embargo->object_id;
        } elseif ($objectId) {
            $this->redirect(['module' => 'embargo', 'action' => 'add', 'objectId' => $objectId]);
            return sfView::NONE;
        } else {
            $this->forward404('Embargo ID or Object ID required');
        }

        if ($request->isMethod('post')) {
            $this->processEditForm($request, (int)$embargoId);
            return sfView::NONE;
        }
    }

    protected function processEditForm(sfWebRequest $request, int $embargoId)
    {
        $service = $this->getService();
        $userId = $this->context->user->getAttribute('user_id');

        $data = [
            'embargo_type' => $request->getParameter('embargo_type'),
            'start_date' => $request->getParameter('start_date'),
            'end_date' => $request->getParameter('end_date') ?: null,
            'is_perpetual' => (bool)$request->getParameter('is_perpetual', false),
            'notify_on_expiry' => (bool)$request->getParameter('notify_on_expiry', true),
            'notify_days_before' => (int)$request->getParameter('notify_days_before', 30),
            'reason' => $request->getParameter('reason'),
            'notes' => $request->getParameter('notes'),
            'public_message' => $request->getParameter('public_message'),
        ];

        $service->updateEmbargo($embargoId, $data, $userId);

        $embargo = $service->getEmbargo($embargoId);
        $resource = $this->getResource($embargo->object_id);
        $this->redirect(['module' => 'informationobject', 'slug' => $resource->slug]);
    }

    public function executeLift(sfWebRequest $request)
    {
        $embargoId = $request->getParameter('id');

        if (!$embargoId) {
            $this->forward404();
        }

        $service = $this->getService();
        $embargo = $service->getEmbargo((int)$embargoId);

        if (!$embargo) {
            $this->forward404();
        }

        if ($request->isMethod('post')) {
            $reason = $request->getParameter('lift_reason');
            $userId = $this->context->user->getAttribute('user_id');
            $service->liftEmbargo((int)$embargoId, $reason, $userId);

            $resource = $this->getResource($embargo->object_id);
            $this->redirect(['module' => 'informationobject', 'slug' => $resource->slug]);
        }

        $this->embargo = $embargo;
        $this->resource = $this->getResource($embargo->object_id);
    }

    public function executeAddException(sfWebRequest $request)
    {
        $embargoId = $request->getParameter('embargo_id');

        $service = $this->getService();
        $this->embargo = $service->getEmbargo((int)$embargoId);

        if (!$this->embargo) {
            $this->forward404();
        }

        if ($request->isMethod('post')) {
            $this->processAddExceptionForm($request, (int)$embargoId);
            return sfView::NONE;
        }
    }

    protected function processAddExceptionForm(sfWebRequest $request, int $embargoId)
    {
        $this->initDb();
        require_once sfConfig::get('sf_root_dir') . '/atom-ahg-plugins/ahgExtendedRightsPlugin/lib/Services/EmbargoService.php';
        $now = date('Y-m-d H:i:s');

        DB::table('embargo_exception')->insert([
            'embargo_id' => $embargoId,
            'exception_type' => $request->getParameter('exception_type'),
            'exception_id' => $request->getParameter('exception_id'),
            'ip_range_start' => $request->getParameter('ip_range_start'),
            'ip_range_end' => $request->getParameter('ip_range_end'),
            'valid_from' => $request->getParameter('valid_from') ?: null,
            'valid_until' => $request->getParameter('valid_until') ?: null,
            'created_at' => $now,
        ]);

        $this->redirect(['module' => 'embargo', 'action' => 'view', 'id' => $embargoId]);
    }
}