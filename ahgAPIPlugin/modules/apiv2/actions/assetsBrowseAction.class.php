<?php

use AtomFramework\Http\Controllers\AhgApiController;
class apiv2AssetsBrowseAction extends AhgApiController
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
            'asset_class_id' => $request->getParameter('asset_class')
        ];

        $result = $this->repository->getAssets($params);
        return $this->success($result);
    }
}
