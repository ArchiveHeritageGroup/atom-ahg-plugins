<?php

class accessFilterComponents extends AhgComponents
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
        $this->restrictions = [];

        // Donor restrictions live in other plugins.
        //
        // object_rights_holder belongs to ahgExtendedRightsPlugin; donor_agreement
        // and donor_agreement_restriction to ahgDonorAgreementPlugin. This plugin
        // declares neither as a dependency, so on an instance without them the
        // query threw and EVERY archival description page returned HTTP 500 -
        // observed on a clean install, 2026-08-18.
        //
        // A component that decorates a record must never be able to take the
        // record down. Absent tables mean there are no donor restrictions to show.
        $schema = \Illuminate\Database\Capsule\Manager::schema();

        foreach (['object_rights_holder', 'donor_agreement', 'donor_agreement_restriction'] as $required) {
            if (!$schema->hasTable($required)) {
                return;
            }
        }

        $this->restrictions = \Illuminate\Database\Capsule\Manager::table('object_rights_holder as orh')
            ->leftJoin('actor_i18n as ai', function ($join) {
                $join->on('ai.id', '=', 'orh.donor_id')->where('ai.culture', '=', \AtomExtensions\Helpers\CultureHelper::getCulture());
            })
            ->leftJoin('donor_agreement as da', 'da.donor_id', '=', 'orh.donor_id')
            ->leftJoin('donor_agreement_restriction as dar', 'dar.donor_agreement_id', '=', 'da.id')
            ->where('orh.object_id', $this->objectId)
            ->whereNotNull('dar.restriction_type')
            ->select('ai.authorized_form_of_name as donor_name', 'dar.restriction_type', 'dar.end_date', 'dar.reason')
            ->get();
    }
}
