<?php

use ahgCorePlugin\Services\AhgTaxonomyService;
use Illuminate\Database\Capsule\Manager as DB;

/**
 * Getty Link Management Action.
 *
 * Admin interface for reviewing, confirming, and managing Getty vocabulary links.
 */
class gettyLinksAction extends sfAction
{
    public $links = [];
    public $statistics = [];
    public $vocabularies = ['aat', 'tgn', 'ulan'];
    public $statuses = [];

    public function preExecute()
    {
        parent::preExecute();
        // Load statuses from database
        $taxonomyService = new AhgTaxonomyService();
        $this->statuses = array_keys($taxonomyService->getLinkStatuses(false));

        // Fallback if not populated
        if (empty($this->statuses)) {
            $this->statuses = ['pending', 'confirmed', 'suggested', 'rejected'];
        }
    }
    
    public function execute($request)
    {
        // Check authentication
        if (!$this->getUser()->isAuthenticated()) {
            $this->redirect('user/login');
        }

        // Get filter parameters
        $vocabulary = $request->getParameter('vocabulary', '');
        $status = $request->getParameter('status', '');
        $search = $request->getParameter('search', '');

        // Build query
        $query = DB::table('getty_vocabulary_link')
            ->leftJoin('term', 'getty_vocabulary_link.term_id', '=', 'term.id')
            ->leftJoin('term_i18n', function($join) {
                $join->on('term.id', '=', 'term_i18n.id')
                     ->where('term_i18n.culture', '=', 'en');
            })
            ->select(
                'getty_vocabulary_link.*',
                'term_i18n.name as term_name'
            );

        if (!empty($vocabulary)) {
            $query->where('vocabulary', $vocabulary);
        }

        if (!empty($status)) {
            $query->where('status', $status);
        }

        if (!empty($search)) {
            $query->where(function($q) use ($search) {
                $q->where('term_i18n.name', 'like', "%{$search}%")
                  ->orWhere('getty_pref_label', 'like', "%{$search}%");
            });
        }

        $this->links = $query->orderBy('created_at', 'desc')
            ->limit(100)
            ->get()
            ->toArray();

        // Get statistics
        $this->statistics = [
            'total' => DB::table('getty_vocabulary_link')->count(),
            'confirmed' => DB::table('getty_vocabulary_link')->where('status', 'confirmed')->count(),
            'pending' => DB::table('getty_vocabulary_link')->where('status', 'pending')->count(),
            'suggested' => DB::table('getty_vocabulary_link')->where('status', 'suggested')->count(),
            'rejected' => DB::table('getty_vocabulary_link')->where('status', 'rejected')->count(),
            'aat' => DB::table('getty_vocabulary_link')->where('vocabulary', 'aat')->count(),
            'tgn' => DB::table('getty_vocabulary_link')->where('vocabulary', 'tgn')->count(),
            'ulan' => DB::table('getty_vocabulary_link')->where('vocabulary', 'ulan')->count(),
        ];

        // Handle POST actions
        if ($request->isMethod('post')) {
            $action = $request->getParameter('action');
            $linkId = $request->getParameter('link_id');

            if ($linkId && in_array($action, ['confirm', 'reject'])) {
                $newStatus = ($action === 'confirm') ? 'confirmed' : 'rejected';
                DB::table('getty_vocabulary_link')
                    ->where('id', $linkId)
                    ->update([
                        'status' => $newStatus,
                        'confirmed_by_user_id' => $this->getUser()->getAttribute('user_id'),
                        'confirmed_at' => date('Y-m-d H:i:s'),
                    ]);

                $this->redirect('api/gettyLinks');
            }
        }
    }
}
