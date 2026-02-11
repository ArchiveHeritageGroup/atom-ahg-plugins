<?php

use AtomFramework\Http\Controllers\AhgApiController;
class apiv2ConditionsReadAction extends AhgApiController
{
    public function GET($request)
    {
        if (!$this->hasScope('read')) {
            return $this->error(403, 'Forbidden', 'Read scope required');
        }

        $id = (int) $request->getParameter('id');
        $result = $this->repository->getConditionById($id);

        if (!$result) {
            return $this->error(404, 'Not Found', 'Condition check not found');
        }

        return $this->success($result);
    }
}
