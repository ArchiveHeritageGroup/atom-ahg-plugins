<?php

use AtomFramework\Http\Controllers\AhgApiController;
class apiv2ConditionsDeleteAction extends AhgApiController
{
    public function DELETE($request)
    {
        if (!$this->hasScope('delete')) {
            return $this->error(403, 'Forbidden', 'Delete scope required');
        }

        $id = (int) $request->getParameter('id');
        $existing = $this->repository->getConditionById($id);

        if (!$existing) {
            return $this->error(404, 'Not Found', 'Condition check not found');
        }

        $this->repository->deleteCondition($id);

        return $this->success(['message' => 'Condition check deleted']);
    }
}
