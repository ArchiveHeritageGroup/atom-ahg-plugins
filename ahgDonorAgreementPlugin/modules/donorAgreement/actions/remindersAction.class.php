<?php

class donorAgreementRemindersAction extends sfAction
{
    public function execute($request)
    {
        if (!$this->context->user->isAuthenticated()) {
            $this->redirect(['module' => 'user', 'action' => 'login']);
        }

        \AhgCore\Core\AhgDb::init();

        $this->reminders = \Illuminate\Database\Capsule\Manager::table('donor_agreement_reminder as r')
            ->join('donor_agreement as da', 'r.donor_agreement_id', '=', 'da.id')
            ->leftJoin('agreement_type as at', 'da.agreement_type_id', '=', 'at.id')
            ->select([
                'r.id',
                'r.donor_agreement_id',
                'r.subject',
                'r.reminder_type',
                'r.reminder_date',
                'r.priority',
                'r.status',
                'da.agreement_number',
                'da.title as agreement_title',
                'at.name as type_name',
            ])
            ->where('r.status', 'active')
            ->where('r.reminder_date', '<=', date('Y-m-d', strtotime('+30 days')))
            ->orderBy('r.reminder_date', 'asc')
            ->get();
    }
}
