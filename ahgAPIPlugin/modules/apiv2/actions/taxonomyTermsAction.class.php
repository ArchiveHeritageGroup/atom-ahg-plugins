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
        // ?search= returns at most 50 terms whose name holds every word.
        $result = $this->repository->getTaxonomyTerms($taxonomyId, (string) $request->getParameter('search', ''));

        return $this->success(['terms' => $result]);
    }
}
