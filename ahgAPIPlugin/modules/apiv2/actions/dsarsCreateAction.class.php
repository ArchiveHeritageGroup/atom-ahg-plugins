<?php

use AtomFramework\Http\Controllers\AhgApiController;
class apiv2DsarsCreateAction extends AhgApiController
{
    public function POST($request)
    {
        if (!$this->hasScope('write')) {
            return $this->error(403, 'Forbidden', 'Write scope required');
        }

        $data = $this->getJsonInput();

        if (empty($data['requester_name'])) {
            return $this->error(400, 'Bad Request', 'requester_name required');
        }

        $id = $this->repository->createDsar($data);

        return $this->success([
            'id' => $id,
            'message' => 'DSAR created'
        ], 201);
    }
}
