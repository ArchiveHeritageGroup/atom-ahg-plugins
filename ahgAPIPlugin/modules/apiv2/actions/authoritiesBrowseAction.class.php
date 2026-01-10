<?php

class apiv2AuthoritiesBrowseAction extends AhgApiAction
{
    public function GET($request)
    {
        if (!$this->hasScope('read')) {
            return $this->error(403, 'Forbidden', 'Read scope required');
        }

        $params = [
            'limit' => $request->getParameter('limit', 10),
            'skip' => $request->getParameter('skip', 0),
            'entity_type' => $request->getParameter('entity_type')
        ];

        $result = $this->repository->getAuthorities($params);

        return $this->success($result);
    }
}
