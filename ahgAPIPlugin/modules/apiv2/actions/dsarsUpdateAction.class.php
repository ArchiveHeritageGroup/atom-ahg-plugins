<?php

use AtomFramework\Http\Controllers\AhgApiController;
class apiv2DsarsUpdateAction extends AhgApiController
{
    public function PUT($request)
    {
        return $this->handleUpdate($request);
    }

    public function PATCH($request)
    {
        return $this->handleUpdate($request);
    }

    protected function handleUpdate($request)
    {
        if (!$this->hasScope('write')) {
            return $this->error(403, 'Forbidden', 'Write scope required');
        }

        $id = (int) $request->getParameter('id');
        $existing = $this->repository->getDsarById($id);

        if (!$existing) {
            return $this->error(404, 'Not Found', 'DSAR not found');
        }

        $data = $this->getJsonInput();
        $data['user_id'] = $this->apiKey['user_id'] ?? null;
        $this->repository->updateDsar($id, $data);

        return $this->success([
            'id' => $id,
            'message' => 'DSAR updated'
        ]);
    }
}
