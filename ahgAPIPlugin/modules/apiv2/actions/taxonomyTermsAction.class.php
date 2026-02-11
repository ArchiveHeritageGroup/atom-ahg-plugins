<?php

use AtomFramework\Http\Controllers\AhgApiController;
class apiv2TaxonomyTermsAction extends AhgApiController
{
    public function GET($request)
    {
        if (!$this->hasScope('read')) {
            return $this->error(403, 'Forbidden', 'Read scope required');
        }

        $taxonomyId = (int) $request->getParameter('id');
        $result = $this->repository->getTaxonomyTerms($taxonomyId);

        return $this->success(['terms' => $result]);
    }
}
