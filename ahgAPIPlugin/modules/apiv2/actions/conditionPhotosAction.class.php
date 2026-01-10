<?php

class apiv2ConditionPhotosAction extends AhgApiAction
{
    public function GET($request)
    {
        if (!$this->hasScope('read')) {
            return $this->error(403, 'Forbidden', 'Read scope required');
        }

        $id = (int) $request->getParameter('id');
        $photos = $this->repository->getConditionPhotos($id);

        return $this->success(['photos' => $photos]);
    }
}
