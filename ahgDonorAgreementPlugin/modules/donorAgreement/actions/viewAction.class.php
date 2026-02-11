<?php

use AtomFramework\Http\Controllers\AhgController;
class donorAgreementViewAction extends AhgController
{
    public function execute($request)
    {
        if (!$this->getUser()->isAuthenticated()) {
            $this->redirect(['module' => 'user', 'action' => 'login']);
        }
        
        $id = (int) $request->getParameter('id');
        if (!$id) {
            $this->forward404('Agreement ID required');
        }
        
        // Initialize Laravel DB
        $this->initDatabase();
        
        // Get agreement
        $this->agreement = \Illuminate\Database\Capsule\Manager::table('donor_agreement as da')
            ->leftJoin('donor_agreement_i18n as dai', function($join) {
                $join->on('da.id', '=', 'dai.id')->where('dai.culture', '=', 'en');
            })
            ->leftJoin('agreement_type as at', 'da.agreement_type_id', '=', 'at.id')
            ->leftJoin('donor as d', 'da.donor_id', '=', 'd.id')
            ->leftJoin('actor as a', 'd.id', '=', 'a.id')
            ->leftJoin('slug as s_donor', 'd.id', '=', 's_donor.object_id')
            ->leftJoin('actor_i18n as ai', function($join) {
                $join->on('a.id', '=', 'ai.id')->where('ai.culture', '=', 'en');
            })
            ->where('da.id', $id)
            ->select([
                'da.*',
                'dai.title as i18n_title',
                'dai.description as i18n_description',
                'at.name as agreement_type_name',
                'at.color as agreement_type_color',
                'ai.authorized_form_of_name as donor_name',
                's_donor.slug as donor_slug'
            ])
            ->first();
        
        if (!$this->agreement) {
            $this->forward404('Agreement not found');
        }
        
        // Use i18n title if main title is empty
        if (empty($this->agreement->title) && !empty($this->agreement->i18n_title)) {
            $this->agreement->title = $this->agreement->i18n_title;
        }
        if (empty($this->agreement->description) && !empty($this->agreement->i18n_description)) {
            $this->agreement->description = $this->agreement->i18n_description;
        }
        
        // Get documents (uses donor_agreement_id)
        $this->documents = \Illuminate\Database\Capsule\Manager::table('donor_agreement_document')
            ->where('donor_agreement_id', $id)
            ->orderBy('created_at', 'desc')
            ->get()
            ->all();
        
        // Get reminders (uses donor_agreement_id)
        $this->reminders = \Illuminate\Database\Capsule\Manager::table('donor_agreement_reminder')
            ->where('donor_agreement_id', $id)
            ->orderBy('reminder_date', 'asc')
            ->get()
            ->all();
        
        // Get history/audit log (uses agreement_id)
        try {
            $this->history = \Illuminate\Database\Capsule\Manager::table('donor_agreement_history')
                ->where('agreement_id', $id)
                ->orderBy('created_at', 'desc')
                ->limit(20)
                ->get()
                ->all();
        } catch (Exception $e) {
            $this->history = [];
        }
        
        // Get linked records (uses agreement_id) - with slug from slug table
        try {
            $this->linkedRecords = \Illuminate\Database\Capsule\Manager::table('donor_agreement_record as dar')
                ->join('information_object as io', 'dar.information_object_id', '=', 'io.id')
                ->leftJoin('information_object_i18n as ioi', function($join) {
                    $join->on('io.id', '=', 'ioi.id')->where('ioi.culture', '=', 'en');
                })
                ->leftJoin('slug as s', 'io.id', '=', 's.object_id')
                ->where('dar.agreement_id', $id)
                ->select(['io.id', 'io.identifier', 's.slug', 'ioi.title'])
                ->get()
                ->all();
        } catch (Exception $e) {
            $this->linkedRecords = [];
        }
        
        // Get linked accessions (uses donor_agreement_id)
        try {
            $this->linkedAccessions = \Illuminate\Database\Capsule\Manager::table('donor_agreement_accession as daa')
                ->join('accession as acc', 'daa.accession_id', '=', 'acc.id')
                ->leftJoin('accession_i18n as acci', function($join) {
                    $join->on('acc.id', '=', 'acci.id')->where('acci.culture', '=', 'en');
                })
                ->where('daa.donor_agreement_id', $id)
                ->leftJoin('slug as s', 'acc.id', '=', 's.object_id')->select(['acc.id', 'acc.identifier', 'acci.title', 's.slug'])
                ->get()
                ->all();
        } catch (Exception $e) {
            $this->linkedAccessions = [];
        }
        
        // Get statuses for display
        $this->statuses = [
            'draft' => ['label' => 'Draft', 'class' => 'secondary'],
            'pending_review' => ['label' => 'Pending Review', 'class' => 'warning'],
            'pending_signature' => ['label' => 'Pending Signature', 'class' => 'info'],
            'active' => ['label' => 'Active', 'class' => 'success'],
            'expired' => ['label' => 'Expired', 'class' => 'danger'],
            'terminated' => ['label' => 'Terminated', 'class' => 'dark']
        ];
    }

    protected function initDatabase()
    {
        $bootstrap = $this->config('sf_root_dir') . '/atom-framework/bootstrap.php';
        if (file_exists($bootstrap)) {
            require_once $bootstrap;
        }
    }
}
