<?php

class apiv2AssetValuationsAction extends AhgApiAction
{
    public function GET($request)
    {
        if (!$this->hasScope('read')) {
            return $this->error(403, 'Forbidden', 'Read scope required');
        }

        $id = (int) $request->getParameter('id');
        $valuations = $this->repository->getAssetValuations($id);

        return $this->success(['valuations' => $valuations]);
    }
}
