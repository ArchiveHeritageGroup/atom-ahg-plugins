<?php

use AtomFramework\Http\Controllers\AhgApiController;
class apiv2DescriptionsBrowseAction extends AhgApiController
{
    public function GET($request)
    {
        if (!$this->hasScope('read')) {
            return $this->error(403, 'Forbidden', 'Read scope required');
        }

        $params = [
            'limit' => $request->getParameter('limit', 10),
            'skip' => $request->getParameter('skip', 0),
            'sort' => $request->getParameter('sort', 'updated'),
            'sort_direction' => $request->getParameter('sort_direction', 'desc'),
            'repository' => $request->getParameter('repository'),
            'level' => $request->getParameter('level'),
            'parent' => $request->getParameter('parent')
        ];

        $result = $this->repository->getDescriptions($params);

        return $this->success($result);
    }
}
