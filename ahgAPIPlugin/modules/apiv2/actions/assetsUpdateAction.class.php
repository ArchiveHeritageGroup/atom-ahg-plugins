<?php

class apiv2AssetsUpdateAction extends AhgApiAction
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
        $existing = $this->repository->getAssetById($id);

        if (!$existing) {
            return $this->error(404, 'Not Found', 'Asset not found');
        }

        $data = $this->getJsonInput();
        $this->repository->updateAsset($id, $data);

        return $this->success([
            'id' => $id,
            'message' => 'Asset updated'
        ]);
    }
}
