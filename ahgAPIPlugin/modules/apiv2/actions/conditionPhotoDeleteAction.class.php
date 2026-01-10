<?php

class apiv2ConditionPhotoDeleteAction extends AhgApiAction
{
    public function DELETE($request)
    {
        if (!$this->hasScope('delete')) {
            return $this->error(403, 'Forbidden', 'Delete scope required');
        }

        $photoId = (int) $request->getParameter('photoId');
        $this->repository->deleteConditionPhoto($photoId);

        return $this->success(['message' => 'Photo deleted']);
    }
}
