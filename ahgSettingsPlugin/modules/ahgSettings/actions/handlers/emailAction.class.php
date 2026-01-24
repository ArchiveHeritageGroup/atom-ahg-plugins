<?php
use AtomExtensions\Services\AclService;

use Illuminate\Database\Capsule\Manager as DB;

class AhgSettingsEmailAction extends sfAction
{
    public function execute($request)
    {
        if (!$this->context->user->isAdministrator()) {
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
    }
}
