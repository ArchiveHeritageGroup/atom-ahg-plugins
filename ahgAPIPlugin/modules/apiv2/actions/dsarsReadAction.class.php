<?php

use AtomFramework\Http\Controllers\AhgApiController;
class apiv2DsarsReadAction extends AhgApiController
{
    public function GET($request)
    {
        if (!$this->hasScope('read')) {
            return $this->error(403, 'Forbidden', 'Read scope required');
        }

        $id = (int) $request->getParameter('id');
        $result = $this->repository->getDsarById($id);

        if (!$result) {
            return $this->error(404, 'Not Found', 'DSAR not found');
        }

        return $this->success($result);
    }
}
