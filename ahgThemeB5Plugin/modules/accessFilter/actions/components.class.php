<?php

class accessFilterComponents extends sfComponents
{
    public function executeAccessBadge(sfWebRequest $request)
    {
        $this->objectId = $this->getVar('objectId');
        $this->userId = $this->getUser()->isAuthenticated()
            ? $this->getUser()->getAttribute('user_id')
            : null;
        $service = \AtomExtensions\Services\Access\AccessFilterService::getInstance();
        $this->access = $service->checkAccess($this->objectId, $this->userId);
        $this->userContext = $service->getUserContext($this->userId);
    }

    public function executeClassificationBadge(sfWebRequest $request)
    {
        $this->objectId = $this->getVar('objectId');
        $this->classification = \Illuminate\Database\Capsule\Manager::table('object_security_classification as osc')
            ->join('security_classification as sc', 'sc.id', '=', 'osc.classification_id')
            ->where('osc.object_id', $this->objectId)
            ->where('osc.active', 1)
            ->select('sc.code', 'sc.name', 'sc.level')
            ->first();
    }

    public function executeDonorRestrictions(sfWebRequest $request)
    {
        $this->objectId = $this->getVar('objectId');
        $this->restrictions = \Illuminate\Database\Capsule\Manager::table('object_rights_holder as orh')
            ->leftJoin('actor_i18n as ai', function ($join) {
                $join->on('ai.id', '=', 'orh.donor_id')->where('ai.culture', '=', 'en');
            })
            ->leftJoin('donor_agreement as da', 'da.donor_id', '=', 'orh.donor_id')
            ->leftJoin('donor_agreement_restriction as dar', 'dar.donor_agreement_id', '=', 'da.id')
            ->where('orh.object_id', $this->objectId)
            ->whereNotNull('dar.restriction_type')
            ->select('ai.authorized_form_of_name as donor_name', 'dar.restriction_type', 'dar.end_date', 'dar.reason')
            ->get();
    }
}
