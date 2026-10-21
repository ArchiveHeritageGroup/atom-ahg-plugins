<?php

use AtomFramework\Http\Controllers\AhgApiController;
class apiv2BreachesCreateAction extends AhgApiController
{
    public function POST($request)
    {
        if (!$this->hasScope('write')) {
            return $this->error(403, 'Forbidden', 'Write scope required');
        }

        $data = (array) $this->getJsonInput();
        $allowed = [
            'breach_type' => ['confidentiality', 'integrity', 'availability'],
            'severity' => ['low', 'medium', 'high', 'critical'],
            'status' => ['detected', 'investigating', 'contained', 'resolved', 'closed'],
            'risk_to_rights' => ['unlikely', 'possible', 'likely', 'high'],
        ];
        if (empty($data['breach_type'])) {
            return $this->error(400, 'Bad Request', 'breach_type is required: '.implode(', ', $allowed['breach_type']));
        }
        foreach ($allowed as $field => $values) {
            if (isset($data[$field]) && !in_array($data[$field], $values, true)) {
                return $this->error(400, 'Bad Request', "$field must be one of: ".implode(', ', $values));
            }
        }
        $data['created_by'] = (int) $this->getUser()->getAttribute('user_id') ?: null;
        $id = $this->repository->createBreach($data);

        return $this->success([
            'id' => $id,
            'message' => 'Breach record created'
        ], 201);
    }
}
