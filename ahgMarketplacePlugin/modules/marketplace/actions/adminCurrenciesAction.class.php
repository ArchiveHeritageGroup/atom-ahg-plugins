<?php

use AtomFramework\Http\Controllers\AhgController;

$pluginPath = sfConfig::get('sf_root_dir') . '/atom-ahg-plugins/ahgMarketplacePlugin';
require_once $pluginPath . '/lib/Repositories/SettingsRepository.php';

use AtomAhgPlugins\ahgMarketplacePlugin\Repositories\SettingsRepository;

class marketplaceAdminCurrenciesAction extends AhgController
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

        // Handle POST actions
        if ($request->isMethod('post')) {
            $formAction = $request->getParameter('form_action');

            if ($formAction === 'update') {
                $code = trim($request->getParameter('code', ''));
                $exchangeRate = (float) $request->getParameter('exchange_rate_to_zar', 1);

                if (!empty($code) && $exchangeRate > 0) {
                    $settingsRepo->updateCurrency($code, [
                        'exchange_rate_to_zar' => $exchangeRate,
                    ]);
                    $this->getUser()->setFlash('notice', 'Exchange rate updated for ' . $code . '.');
                } else {
                    $this->getUser()->setFlash('error', 'Invalid currency code or exchange rate.');
                }
            } elseif ($formAction === 'add') {
                $code = strtoupper(trim($request->getParameter('code', '')));
                $name = trim($request->getParameter('name', ''));
                $symbol = trim($request->getParameter('symbol', ''));
                $exchangeRate = (float) $request->getParameter('exchange_rate_to_zar', 1);
                $sortOrder = (int) $request->getParameter('sort_order', 100);

                if (empty($code) || empty($name)) {
                    $this->getUser()->setFlash('error', 'Currency code and name are required.');
                } else {
                    // Check if already exists
                    $existing = $settingsRepo->getCurrency($code);
                    if ($existing) {
                        $this->getUser()->setFlash('error', 'Currency ' . $code . ' already exists.');
                    } else {
                        $settingsRepo->addCurrency([
                            'code' => $code,
                            'name' => $name,
                            'symbol' => $symbol,
                            'exchange_rate_to_zar' => $exchangeRate,
                            'sort_order' => $sortOrder,
                            'is_active' => 1,
                            'created_at' => date('Y-m-d H:i:s'),
                            'updated_at' => date('Y-m-d H:i:s'),
                        ]);
                        $this->getUser()->setFlash('notice', 'Currency ' . $code . ' added.');
                    }
                }
            } elseif ($formAction === 'toggle') {
                $code = trim($request->getParameter('code', ''));
                if (!empty($code)) {
                    $currency = $settingsRepo->getCurrency($code);
                    if ($currency) {
                        $newStatus = $currency->is_active ? 0 : 1;
                        $settingsRepo->updateCurrency($code, ['is_active' => $newStatus]);
                        $statusLabel = $newStatus ? 'activated' : 'deactivated';
                        $this->getUser()->setFlash('notice', 'Currency ' . $code . ' ' . $statusLabel . '.');
                    }
                }
            }

            $this->redirect(['module' => 'marketplace', 'action' => 'adminCurrencies']);
        }

        // Get all currencies (including inactive)
        $this->currencies = $settingsRepo->getCurrencies(false);
    }
}
