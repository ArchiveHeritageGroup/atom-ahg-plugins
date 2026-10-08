<?php

require_once sfConfig::get('sf_plugins_dir').'/ahgAPIPlugin/lib/ApiVisibility.php';

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

        // Hidden descriptions answer exactly like missing ones (ApiVisibility).
        if (!ApiVisibility::canSeeSlug((string) $slug, $this->getUser())) {
            return $this->error(404, 'Not Found', 'Description not found');
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

        // Custom fields (#202): staff keys see every field, other keys only the
        // fields marked visible to the public. Added before redaction, so the
        // privacy rules below apply to them too.
        if (class_exists('\\AtomFramework\\Services\\CustomFieldValues')) {
            $result['custom_fields'] = \AtomFramework\Services\CustomFieldValues::forObject(
                (int) $result['id'],
                'informationobject',
                !ApiVisibility::isStaff($this->getUser())
            );
        }

        // #130 refinement 2 - field-level redaction on the REST layer, using the
        // same authority as the web view so the two cannot drift. Both service
        // files must be required: the namespace is not autoloaded here, and a
        // missing require surfaces as a fatal that silently disables redaction.
        $dir = sfConfig::get('sf_plugins_dir') . '/ahgPrivacyPlugin/lib/Service/';
        require_once $dir . 'RedactionAccess.php';
        require_once $dir . 'PrivacyRedactionService.php';

        if (!\ahgPrivacyPlugin\Service\RedactionAccess::apiMaySeeUnredacted($this->getUser(), $this->hasScope('admin'))) {
            $result = (new \ahgPrivacyPlugin\Service\PrivacyRedactionService())->redactPayload($result);
        }

        return $this->success($result);
    }
}
