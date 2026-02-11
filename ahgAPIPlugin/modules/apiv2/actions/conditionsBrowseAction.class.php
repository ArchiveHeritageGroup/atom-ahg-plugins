<?php

use AtomFramework\Http\Controllers\AhgApiController;
class apiv2ConditionsBrowseAction extends AhgApiController
{
    public function GET($request)
    {
        if (!$this->hasScope('read')) {
            return $this->error(403, 'Forbidden', 'Read scope required');
        }

        $params = [
            'limit' => $request->getParameter('limit', 10),
            'skip' => $request->getParameter('skip', 0),
            'object_slug' => $request->getParameter('object'),
            'overall_condition' => $request->getParameter('condition'),
            'checked_by' => $request->getParameter('checked_by')
        ];

        $result = $this->repository->getConditions($params);
        return $this->success($result);
    }
}
