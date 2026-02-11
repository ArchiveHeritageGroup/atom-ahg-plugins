<?php

use AtomFramework\Http\Controllers\AhgController;
class donorAgreementBrowseAction extends AhgController
{
    public function execute($request)
    {
        if (!$this->getUser()->isAuthenticated()) {
            $this->redirect(['module' => 'user', 'action' => 'login']);
        }

        \AhgCore\Core\AhgDb::init();

        $this->filters = [
            'status' => $request->getParameter('status'),
            'type' => $request->getParameter('type'),
            'search' => $request->getParameter('q'),
        ];

        $page = max(1, (int) $request->getParameter('page', 1));
        $perPage = 25;
        $offset = ($page - 1) * $perPage;

        $query = \Illuminate\Database\Capsule\Manager::table('donor_agreement as da')
            ->leftJoin('agreement_type as at', 'da.agreement_type_id', '=', 'at.id')
            ->leftJoin('donor as d', 'da.donor_id', '=', 'd.id')
            ->leftJoin('actor as a', 'd.id', '=', 'a.id')
            ->leftJoin('actor_i18n as ai', function($join) {
                $join->on('a.id', '=', 'ai.id')->where('ai.culture', '=', 'en');
            })
            ->select([
                'da.id',
                'da.agreement_number',
                'da.title',
                'da.status',
                'da.agreement_date',
                'da.effective_date',
                'da.expiry_date',
                'at.name as type_name',
                'at.color as type_color',
                'ai.authorized_form_of_name as donor_name',
            ]);

        if (!empty($this->filters['status'])) {
            $query->where('da.status', $this->filters['status']);
        }
        if (!empty($this->filters['type'])) {
            $query->where('da.agreement_type_id', $this->filters['type']);
        }
        if (!empty($this->filters['search'])) {
            $search = '%' . $this->filters['search'] . '%';
            $query->where(function($q) use ($search) {
                $q->where('da.agreement_number', 'LIKE', $search)
                  ->orWhere('da.title', 'LIKE', $search)
                  ->orWhere('ai.authorized_form_of_name', 'LIKE', $search);
            });
        }

        // Clone before count to preserve query
        $countQuery = clone $query;
        $this->total = $countQuery->count();
        $this->totalPages = ceil($this->total / $perPage);
        $this->page = $page;

        $this->agreements = $query
            ->orderBy('da.created_at', 'desc')
            ->offset($offset)
            ->limit($perPage)
            ->get();

        $this->statuses = [
            'draft' => 'Draft',
            'pending_review' => 'Pending Review',
            'pending_signature' => 'Pending Signature',
            'active' => 'Active',
            'expired' => 'Expired',
            'terminated' => 'Terminated',
            'superseded' => 'Superseded',
        ];

        $this->types = \Illuminate\Database\Capsule\Manager::table('agreement_type')
            ->where('is_active', 1)
            ->orderBy('sort_order')
            ->get();
    }
}
