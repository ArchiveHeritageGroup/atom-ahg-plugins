<?php

use AtomFramework\Http\Controllers\AhgController;

$pluginPath = sfConfig::get('sf_root_dir') . '/atom-ahg-plugins/ahgMarketplacePlugin';
require_once $pluginPath . '/lib/Repositories/SettingsRepository.php';

use AtomAhgPlugins\ahgMarketplacePlugin\Repositories\SettingsRepository;

class marketplaceAdminSettingsAction extends AhgController
{
    public function execute($request)
    {
        // Admin check
        if (!$this->context->user->isAuthenticated()) {
            $this->redirect(['module' => 'user', 'action' => 'login']);
        }
        if (!$this->context->user->hasCredential('administrator')) {
            $this->getUser()->setFlash('error', 'Admin access required.');
            $this->redirect(['module' => 'marketplace', 'action' => 'browse']);
        }

        $settingsRepo = new SettingsRepository();

        // Handle POST: update settings
        if ($request->isMethod('post')) {
            $settings = $settingsRepo->getAll();

            foreach ($settings as $setting) {
                $key = $setting->setting_key;
                $newValue = $request->getParameter('setting_' . $key);

                if ($newValue !== null) {
                    // Handle boolean settings (checkboxes)
                    if ($setting->setting_type === 'boolean') {
                        $settingsRepo->set($key, (bool) $newValue, 'boolean', $setting->setting_group);
                    } else {
                        $settingsRepo->set($key, $newValue, $setting->setting_type, $setting->setting_group);
                    }
                } elseif ($setting->setting_type === 'boolean') {
                    // Unchecked checkboxes won't be in POST data
                    $settingsRepo->set($key, false, 'boolean', $setting->setting_group);
                }
            }

            $this->getUser()->setFlash('notice', 'Settings updated.');
            $this->redirect(['module' => 'marketplace', 'action' => 'adminSettings']);
        }

        $this->settings = $settingsRepo->getAll();
    }
}
