<?php

/**
 * AHG Central API Integration Settings Action
 *
 * Provides UI for configuring the AHG Central cloud API connection - Issue #52
 * This is for cloud services (NER training sync, future AI features) not local AI services.
 */
class settingsAhgIntegrationAction extends sfAction
{
    public function execute($request)
    {
        // Check admin permission
        if (!$this->context->user->isAdministrator()) {
            $this->forward('admin', 'secure');
        }

        $this->i18n = sfContext::getInstance()->i18n;
        $this->form = new sfForm();

        // Define settings
        $settings = [
            'ahg_central_api_url' => [
                'label' => 'AHG Central API URL',
                'help' => 'The base URL for AHG Central cloud services (e.g., https://train.theahg.co.za/api)',
                'default' => 'https://train.theahg.co.za/api',
                'type' => 'url',
            ],
            'ahg_central_api_key' => [
                'label' => 'API Key',
                'help' => 'Your AHG Central API key for authentication. Contact support@theahg.co.za to request one.',
                'default' => '',
                'type' => 'password',
            ],
            'ahg_central_site_id' => [
                'label' => 'Site ID',
                'help' => 'Unique identifier for this AtoM instance. Used for NER training contributions.',
                'default' => $this->generateDefaultSiteId(),
                'type' => 'text',
            ],
            'ahg_central_enabled' => [
                'label' => 'Enable AHG Central Integration',
                'help' => 'When enabled, NER training data and other features will sync with AHG Central.',
                'default' => '0',
                'type' => 'boolean',
            ],
        ];

        // Load current values and setup form
        foreach ($settings as $name => $config) {
            $setting = \AtomExtensions\Services\SettingService::getByName($name);
            $value = $setting ? $setting->getValue(['sourceCulture' => true]) : $config['default'];

            if ($config['type'] === 'boolean') {
                $choices = [
                    '0' => $this->i18n->__('No'),
                    '1' => $this->i18n->__('Yes'),
                ];
                $this->form->setWidget($name, new sfWidgetFormChoice([
                    'choices' => $choices,
                    'expanded' => true,
                ], ['class' => 'radio']));
                $this->form->setValidator($name, new sfValidatorChoice([
                    'choices' => array_keys($choices),
                    'required' => false,
                ]));
            } elseif ($config['type'] === 'password') {
                $this->form->setWidget($name, new sfWidgetFormInputPassword([
                    'always_render_empty' => false,
                ]));
                $this->form->setValidator($name, new sfValidatorString(['required' => false]));
            } else {
                $this->form->setWidget($name, new sfWidgetFormInput());
                $this->form->setValidator($name, new sfValidatorString(['required' => false]));
            }

            $this->form->setDefault($name, $value);
        }

        $this->settings = $settings;

        // Handle test connection
        if ($request->isMethod('post') && $request->getParameter('test_connection')) {
            $this->testResult = $this->testConnection($request);
            return sfView::SUCCESS;
        }

        // Handle form submission
        if ($request->isMethod('post')) {
            $this->form->bind($request->getPostParameters());

            if ($this->form->isValid()) {
                foreach ($settings as $name => $config) {
                    $value = $this->form->getValue($name);

                    // Don't save empty password (keep existing)
                    if ($config['type'] === 'password' && empty($value)) {
                        continue;
                    }

                    $setting = \AtomExtensions\Services\SettingService::getByName($name);
                    if (!$setting) {
                        $setting = new QubitSetting();
                        $setting->name = $name;
                    }
                    $setting->setValue((string) $value, ['sourceCulture' => true]);
                    $setting->save();
                }

                \AtomExtensions\Services\CacheService::getInstance()->removePattern('settings:i18n:*');
                $this->getUser()->setFlash('notice', $this->i18n->__('AHG Central integration settings saved.'));
                $this->redirect(['module' => 'ahgSettings', 'action' => 'ahgIntegration']);
            }
        }

        return sfView::SUCCESS;
    }

    protected function generateDefaultSiteId(): string
    {
        // Try to get existing site ID from environment
        $envSiteId = getenv('NER_SITE_ID');
        if ($envSiteId) {
            return $envSiteId;
        }

        // Generate based on domain or hostname
        $host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : php_uname('n');
        return preg_replace('/[^a-z0-9-]/', '-', strtolower($host));
    }

    protected function testConnection($request): array
    {
        $url = trim($request->getParameter('ahg_central_api_url'));
        $apiKey = trim($request->getParameter('ahg_central_api_key'));

        // If no API key provided in form, try to get existing
        if (empty($apiKey)) {
            $setting = \AtomExtensions\Services\SettingService::getByName('ahg_central_api_key');
            $apiKey = $setting ? $setting->getValue(['sourceCulture' => true]) : '';
        }

        if (empty($url)) {
            return ['success' => false, 'message' => 'API URL is required'];
        }

        // Test the connection
        $testUrl = rtrim($url, '/') . '/health';

        $ch = curl_init($testUrl);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_HTTPHEADER => [
                'Accept: application/json',
                'X-API-Key: ' . $apiKey,
            ],
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            return [
                'success' => false,
                'message' => 'Connection failed: ' . $error,
            ];
        }

        if ($httpCode === 200) {
            $data = json_decode($response, true);
            return [
                'success' => true,
                'message' => 'Connection successful! Server status: ' . ($data['status'] ?? 'OK'),
            ];
        } elseif ($httpCode === 401 || $httpCode === 403) {
            return [
                'success' => false,
                'message' => 'Authentication failed. Please check your API key.',
            ];
        } elseif ($httpCode === 404) {
            // Health endpoint might not exist, try base URL
            return [
                'success' => true,
                'message' => 'Server reachable but health endpoint not found. Integration may still work.',
            ];
        } else {
            return [
                'success' => false,
                'message' => "Connection failed with HTTP status: {$httpCode}",
            ];
        }
    }
}
