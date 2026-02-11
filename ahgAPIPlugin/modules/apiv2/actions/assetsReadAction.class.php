<?php

use AtomFramework\Http\Controllers\AhgApiController;
class apiv2AssetsReadAction extends AhgApiController
{
    public function GET($request)
    {
        if (!$this->hasScope('read')) {
            return $this->error(403, 'Forbidden', 'Read scope required');
        }

        $id = (int) $request->getParameter('id');
        $result = $this->repository->getAssetById($id);

        if (!$result) {
            return $this->error(404, 'Not Found', 'Asset not found');
        }

        return $this->success($result);
    }
}
