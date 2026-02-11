<?php

use AtomFramework\Http\Controllers\AhgApiController;
class apiv2TaxonomiesBrowseAction extends AhgApiController
{
    public function GET($request)
    {
        if (!$this->hasScope('read')) {
            return $this->error(403, 'Forbidden', 'Read scope required');
        }

        $result = $this->repository->getTaxonomies();

        return $this->success(['taxonomies' => $result]);
    }
}
