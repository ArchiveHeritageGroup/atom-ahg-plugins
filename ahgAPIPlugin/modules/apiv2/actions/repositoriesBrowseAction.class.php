<?php

use AtomFramework\Http\Controllers\AhgApiController;
class apiv2RepositoriesBrowseAction extends AhgApiController
{
    public function GET($request)
    {
        if (!$this->hasScope('read')) {
            return $this->error(403, 'Forbidden', 'Read scope required');
        }

        $params = [
            'limit' => $request->getParameter('limit', 10),
            'skip' => $request->getParameter('skip', 0)
        ];

        $result = $this->repository->getRepositories($params);

        return $this->success($result);
    }
}
