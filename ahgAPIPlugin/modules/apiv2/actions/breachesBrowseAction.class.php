<?php

use AtomFramework\Http\Controllers\AhgApiController;
class apiv2BreachesBrowseAction extends AhgApiController
{
    public function GET($request)
    {
        if (!$this->hasScope('read')) {
            return $this->error(403, 'Forbidden', 'Read scope required');
        }

        $params = [
            'limit' => $request->getParameter('limit', 10),
            'skip' => $request->getParameter('skip', 0),
            'status' => $request->getParameter('status'),
            'severity' => $request->getParameter('severity')
        ];

        $result = $this->repository->getBreaches($params);
        return $this->success($result);
    }
}
