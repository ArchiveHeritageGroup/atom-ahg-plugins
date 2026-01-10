<?php

class apiv2DescriptionConditionsAction extends AhgApiAction
{
    public function GET($request)
    {
        if (!$this->hasScope('read')) {
            return $this->error(403, 'Forbidden', 'Read scope required');
        }

        $slug = $request->getParameter('slug');
        $result = $this->repository->getConditions(['object_slug' => $slug, 'limit' => 100]);

        return $this->success($result);
    }
}
