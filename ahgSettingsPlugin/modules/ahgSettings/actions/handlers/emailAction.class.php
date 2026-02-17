<?php

use AtomFramework\Http\Controllers\AhgController;
use AtomExtensions\Services\AclService;

use Illuminate\Database\Capsule\Manager as DB;

class AhgSettingsEmailAction extends AhgController
{
    public function execute($request)
    {
        if (!$this->getUser()->isAdministrator()) {
            AclService::forwardUnauthorized();
        }

        // Bootstrap Laravel DB if needed
        \AhgCore\Core\AhgDb::init();

        if ($request->isMethod('post')) {
            $settings = $request->getParameter('settings', []);

            foreach ($settings as $key => $value) {
                DB::table('email_setting')
                    ->where('setting_key', $key)
                    ->update([
                        'setting_value' => $value,
                        'updated_at' => date('Y-m-d H:i:s'),
                    ]);
            }

            // Save notification toggles to ahg_settings
            $notifKeys = [
                'research_email_notifications',
                'access_request_email_notifications',
                'workflow_email_notifications',
            ];
            $notifToggles = $request->getParameter('notif_toggles', []);
            foreach ($notifKeys as $nk) {
                $val = isset($notifToggles[$nk]) ? 'true' : 'false';
                try {
                    DB::table('ahg_settings')->updateOrInsert(
                        ['setting_key' => $nk],
                        ['setting_value' => $val, 'setting_group' => 'email', 'updated_at' => DB::raw('NOW()')]
                    );
                } catch (\Exception $e) {
                    // ignore
                }
            }

            $this->getUser()->setFlash('success', 'Email settings saved successfully');
            $this->redirect(['module' => 'ahgSettings', 'action' => 'email']);
        }

        // Load settings grouped
        $this->smtpSettings = DB::table('email_setting')
            ->where('setting_group', 'smtp')
            ->orderBy('id')
            ->get()
            ->toArray();

        $this->notificationSettings = DB::table('email_setting')
            ->where('setting_group', 'notifications')
            ->orderBy('id')
            ->get()
            ->toArray();

        $this->templateSettings = DB::table('email_setting')
            ->where('setting_group', 'templates')
            ->orderBy('id')
            ->get()
            ->toArray();

        // Load notification toggle settings from ahg_settings
        $notifToggles = [];
        try {
            $rows = DB::table('ahg_settings')
                ->whereIn('setting_key', [
                    'spectrum_email_notifications',
                    'research_email_notifications',
                    'access_request_email_notifications',
                    'workflow_email_notifications',
                ])
                ->get(['setting_key', 'setting_value']);
            foreach ($rows as $row) {
                $notifToggles[$row->setting_key] = $row->setting_value;
            }
        } catch (\Exception $e) {
            // table may not exist yet
        }
        $this->notifToggles = $notifToggles;
    }
}
