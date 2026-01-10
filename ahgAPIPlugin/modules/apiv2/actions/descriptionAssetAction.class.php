<?php

class apiv2DescriptionAssetAction extends AhgApiAction
{
    public function GET($request)
    {
        if (!$this->hasScope('read')) {
            return $this->error(403, 'Forbidden', 'Read scope required');
        }

        $slug = $request->getParameter('slug');
        $objectId = $this->repository->getObjectIdBySlug($slug);

        if (!$objectId) {
            return $this->error(404, 'Not Found', 'Description not found');
        }

        $result = $this->repository->getAssetByObjectId($objectId);

        if (!$result) {
            return $this->error(404, 'Not Found', 'No asset for this description');
        }

        return $this->success($result);
    }
}
