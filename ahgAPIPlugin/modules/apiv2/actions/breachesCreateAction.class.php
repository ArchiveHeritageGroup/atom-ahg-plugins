<?php

class apiv2BreachesCreateAction extends AhgApiAction
{
    public function POST($request)
    {
        if (!$this->hasScope('write')) {
            return $this->error(403, 'Forbidden', 'Write scope required');
        }

        $data = $this->getJsonInput();
        $id = $this->repository->createBreach($data);

        return $this->success([
            'id' => $id,
            'message' => 'Breach record created'
        ], 201);
    }
}
