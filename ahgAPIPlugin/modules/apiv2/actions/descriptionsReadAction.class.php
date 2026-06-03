<?php

use AtomFramework\Http\Controllers\AhgApiController;
class apiv2DescriptionsReadAction extends AhgApiController
{
    public function GET($request)
    {
        if (!$this->hasScope('read')) {
            return $this->error(403, 'Forbidden', 'Read scope required');
        }

        $slug = $request->getParameter('slug');
        if (empty($slug)) {
            return $this->error(400, 'Bad Request', 'Slug parameter required');
        }

        $full = filter_var($request->getParameter('full', false), FILTER_VALIDATE_BOOLEAN);

        if ($full) {
            $result = $this->repository->getFullDescription($slug);
        } else {
            $result = $this->repository->getDescriptionBySlug($slug);
        }

        if (!$result) {
            return $this->error(404, 'Not Found', "Description '{$slug}' not found");
        }

        // #130 refinement 2 - field-level redaction on the REST layer. Admin-
        // scoped keys see the full record; every other key gets the same
        // redacted view as the public web view. No-ops for IOs with no rules.
        if (!$this->hasScope('admin')) {
            require_once sfConfig::get('sf_plugins_dir') . '/ahgPrivacyPlugin/lib/Service/PrivacyRedactionService.php';
            $result = (new \ahgPrivacyPlugin\Service\PrivacyRedactionService())->redactPayload($result);
        }

        return $this->success($result);
    }
}
