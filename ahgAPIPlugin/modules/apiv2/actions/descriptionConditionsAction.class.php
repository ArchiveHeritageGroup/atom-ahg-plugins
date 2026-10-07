<?php

require_once sfConfig::get('sf_plugins_dir').'/ahgAPIPlugin/lib/ApiVisibility.php';

use AtomFramework\Http\Controllers\AhgApiController;
class apiv2DescriptionConditionsAction extends AhgApiController
{
    public function GET($request)
    {
        if (!$this->hasScope('read')) {
            return $this->error(403, 'Forbidden', 'Read scope required');
        }

        $slug = $request->getParameter('slug');
        // Hidden descriptions answer exactly like missing ones (ApiVisibility).
        if (!ApiVisibility::canSeeSlug((string) $slug, $this->getUser())) {
            return $this->error(404, 'Not Found', 'Description not found');
        }
        $result = $this->repository->getConditions(['object_slug' => $slug, 'limit' => 100]);

        return $this->success($result);
    }
}
