<?php

use AtomFramework\Http\Controllers\AhgApiController;
class apiv2DescriptionsBrowseAction extends AhgApiController
{
    public function GET($request)
    {
        if (!$this->hasScope('read')) {
            return $this->error(403, 'Forbidden', 'Read scope required');
        }

        $params = [
            'limit' => $request->getParameter('limit', 10),
            'skip' => $request->getParameter('skip', 0),
            'sort' => $request->getParameter('sort', 'updated'),
            'sort_direction' => $request->getParameter('sort_direction', 'desc'),
            'repository' => $request->getParameter('repository'),
            'level' => $request->getParameter('level'),
            'parent' => $request->getParameter('parent')
        ];

        $result = $this->repository->getDescriptions($params);

        // #130 refinement 2 - redact the list layer too (title is the redactable
        // field exposed here), using the same authority as the web view and the
        // read action. Both service files must be required; the namespace is not
        // autoloaded here.
        $dir = sfConfig::get('sf_plugins_dir') . '/ahgPrivacyPlugin/lib/Service/';
        require_once $dir . 'RedactionAccess.php';
        require_once $dir . 'PrivacyRedactionService.php';

        if (!\ahgPrivacyPlugin\Service\RedactionAccess::apiMaySeeUnredacted($this->getUser(), $this->hasScope('admin'))
            && !empty($result['results'])) {
            $svc = new \ahgPrivacyPlugin\Service\PrivacyRedactionService();
            foreach ($result['results'] as $k => $item) {
                $result['results'][$k] = $svc->redactPayload((array) $item);
            }
        }

        return $this->success($result);
    }
}
