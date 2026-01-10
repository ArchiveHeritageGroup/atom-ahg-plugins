<?php

class apiv2ValuationsCreateAction extends AhgApiAction
{
    public function POST($request)
    {
        if (!$this->hasScope('write')) {
            return $this->error(403, 'Forbidden', 'Write scope required');
        }

        $data = $this->getJsonInput();

        if (empty($data['asset_id'])) {
            return $this->error(400, 'Bad Request', 'asset_id required');
        }

        if (empty($data['valuation_amount'])) {
            return $this->error(400, 'Bad Request', 'valuation_amount required');
        }

        $id = $this->repository->createValuation($data);

        return $this->success([
            'id' => $id,
            'message' => 'Valuation created'
        ], 201);
    }
}
