<?php

use Illuminate\Database\Capsule\Manager as DB;

class settingsActions extends sfActions
{
    public function preExecute()
    {
        if (!$this->getUser()->isAuthenticated() || !$this->getUser()->hasCredential('administrator')) {
            $this->getUser()->setFlash('error', 'Administrator access required');
            $this->redirect('@homepage');
        }

        require_once sfConfig::get('sf_plugins_dir') . '/arResearchPlugin/lib/Services/EmailService.php';
    }

    /**
     * Email settings page
     */
    public function executeEmail(sfWebRequest $request)
    {
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

            // Clear cached settings
            EmailService::$loaded = false;

            $this->getUser()->setFlash('success', 'Email settings saved successfully');
            $this->redirect('settings/email');
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

    /**
     * Test email sending
     */
    public function executeTestEmail(sfWebRequest $request)
    {
        $testEmail = $request->getParameter('email');
        
        if (empty($testEmail)) {
            $this->getUser()->setFlash('error', 'Please enter a test email address');
            $this->redirect('settings/email');
        }

        $result = EmailService::testConnection($testEmail);

        if ($result['success']) {
            $this->getUser()->setFlash('success', $result['message']);
        } else {
            $this->getUser()->setFlash('error', $result['message']);
        }

        $this->redirect('settings/email');
    }
}
