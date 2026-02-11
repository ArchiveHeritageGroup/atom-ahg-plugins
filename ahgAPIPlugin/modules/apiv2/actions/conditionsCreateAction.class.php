<?php

use AtomFramework\Http\Controllers\AhgApiController;
class apiv2ConditionsCreateAction extends AhgApiController
{
    public function POST($request)
    {
        if (!$this->hasScope('write')) {
            return $this->error(403, 'Forbidden', 'Write scope required');
        }

        $data = $this->getJsonInput();

        // Validate required fields
        if (empty($data['object_id']) && empty($data['object_slug'])) {
            return $this->error(400, 'Bad Request', 'object_id or object_slug required');
        }

        if (empty($data['checked_by'])) {
            return $this->error(400, 'Bad Request', 'checked_by required');
        }

        // Resolve slug to ID if needed
        if (empty($data['object_id']) && !empty($data['object_slug'])) {
            $objectId = $this->repository->getObjectIdBySlug($data['object_slug']);
            if (!$objectId) {
                return $this->error(404, 'Not Found', 'Object not found');
            }
            $data['object_id'] = $objectId;
        }

        $id = $this->repository->createCondition($data);

        return $this->success([
            'id' => $id,
            'message' => 'Condition check created'
        ], 201);
    }
}
