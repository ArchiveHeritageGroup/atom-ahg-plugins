<?php

class apiv2AssetsCreateAction extends AhgApiAction
{
    public function POST($request)
    {
        if (!$this->hasScope('write')) {
            return $this->error(403, 'Forbidden', 'Write scope required');
        }

        $data = $this->getJsonInput();

        if (empty($data['object_id']) && empty($data['object_slug'])) {
            return $this->error(400, 'Bad Request', 'object_id or object_slug required');
        }

        if (empty($data['object_id']) && !empty($data['object_slug'])) {
            $objectId = $this->repository->getObjectIdBySlug($data['object_slug']);
            if (!$objectId) {
                return $this->error(404, 'Not Found', 'Object not found');
            }
            $data['object_id'] = $objectId;
        }

        $id = $this->repository->createAsset($data);

        return $this->success([
            'id' => $id,
            'message' => 'Asset created'
        ], 201);
    }
}
