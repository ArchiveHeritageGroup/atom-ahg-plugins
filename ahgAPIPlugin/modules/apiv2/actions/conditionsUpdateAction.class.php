<?php

use AtomFramework\Http\Controllers\AhgApiController;
class apiv2ConditionsUpdateAction extends AhgApiController
{
    public function PUT($request)
    {
        return $this->handleUpdate($request);
    }

    public function PATCH($request)
    {
        return $this->handleUpdate($request);
    }

    protected function handleUpdate($request)
    {
        if (!$this->hasScope('write')) {
            return $this->error(403, 'Forbidden', 'Write scope required');
        }

        $id = (int) $request->getParameter('id');
        $existing = $this->repository->getConditionById($id);

        if (!$existing) {
            return $this->error(404, 'Not Found', 'Condition check not found');
        }

        $data = $this->getJsonInput();
        $this->repository->updateCondition($id, $data);

        return $this->success([
            'id' => $id,
            'message' => 'Condition check updated'
        ]);
    }
}
