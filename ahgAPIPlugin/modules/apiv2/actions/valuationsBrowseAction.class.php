<?php

use AtomFramework\Http\Controllers\AhgApiController;
class apiv2ValuationsBrowseAction extends AhgApiController
{
    public function GET($request)
    {
        if (!$this->hasScope('read')) {
            return $this->error(403, 'Forbidden', 'Read scope required');
        }

        // Get all assets with valuations
        $result = $this->repository->getAssets([
            'limit' => $request->getParameter('limit', 10),
            'skip' => $request->getParameter('skip', 0)
        ]);

        return $this->success($result);
    }
}
