<?php

require_once sfConfig::get('sf_root_dir').'/atom-ahg-plugins/ahgCartPlugin/lib/Services/EcommerceService.php';
require_once sfConfig::get('sf_root_dir').'/atom-ahg-plugins/ahgCartPlugin/lib/Repositories/EcommerceRepository.php';

use AtomAhgPlugins\ahgCartPlugin\Services\EcommerceService;
use AtomAhgPlugins\ahgCartPlugin\Repositories\EcommerceRepository;

/**
 * Admin E-Commerce Settings Action
 *
 * @author Johan Pieterse <johan@theahg.co.za>
 */
class cartAdminSettingsAction extends sfAction
{
    public function execute($request)
    {
        // Check admin access
        if (!$this->context->user->isAuthenticated()) {
            $this->redirect(['module' => 'user', 'action' => 'login']);
            return;
        }

        if (!$this->context->user->hasCredential("administrator")) {
            $this->forward404();
            return;
        }

        $ecommerceRepo = new EcommerceRepository();
        
        // Get current settings (global = repository_id NULL)
        $this->settings = $ecommerceRepo->getSettings(null);
        $this->productTypes = $ecommerceRepo->getProductTypes(false);
        $this->pricing = $ecommerceRepo->getPricing(null, false);

        // Handle form submission
        if ($request->isMethod('post')) {
            $action = $request->getParameter('action_type');

            if ($action === 'save_settings') {
                $this->saveSettings($request, $ecommerceRepo);
            } elseif ($action === 'save_pricing') {
                $this->savePricing($request, $ecommerceRepo);
            }

            // Refresh data
            $this->settings = $ecommerceRepo->getSettings(null);
            $this->pricing = $ecommerceRepo->getPricing(null, false);
        }
    }

    protected function saveSettings($request, $ecommerceRepo)
    {
        // Get existing settings first
        $existing = $ecommerceRepo->getSettings(null);
        
        // Build data array, preserving existing values for fields not in request
        $data = [
            'repository_id' => null,
            'is_enabled' => $request->getParameter('is_enabled') ? 1 : 0,
            'currency' => $request->getParameter('currency', $existing->currency ?? 'ZAR'),
            'vat_rate' => floatval($request->getParameter('vat_rate', $existing->vat_rate ?? 15.00)),
            'vat_number' => $request->getParameter('vat_number') ?: ($existing->vat_number ?? null),
            'payment_gateway' => $request->getParameter('payment_gateway') ?: ($existing->payment_gateway ?? 'payfast'),
            'admin_notification_email' => $request->getParameter('admin_notification_email') ?: ($existing->admin_notification_email ?? null),
            'terms_conditions' => $request->getParameter('terms_conditions') ?: ($existing->terms_conditions ?? null),
        ];
        
        // PayFast fields - only update if explicitly submitted (check if field exists in POST)
        if ($request->hasParameter('payfast_merchant_id')) {
            $data['payfast_merchant_id'] = $request->getParameter('payfast_merchant_id') ?: null;
            $data['payfast_merchant_key'] = $request->getParameter('payfast_merchant_key') ?: null;
            $data['payfast_passphrase'] = $request->getParameter('payfast_passphrase') ?: null;
            $data['payfast_sandbox'] = $request->getParameter('payfast_sandbox') ? 1 : 0;
        } else {
            // Preserve existing PayFast settings
            $data['payfast_merchant_id'] = $existing->payfast_merchant_id ?? null;
            $data['payfast_merchant_key'] = $existing->payfast_merchant_key ?? null;
            $data['payfast_passphrase'] = $existing->payfast_passphrase ?? null;
            $data['payfast_sandbox'] = $existing->payfast_sandbox ?? 1;
        }

        $ecommerceRepo->saveSettings($data);
        $this->context->user->setFlash('notice', 'E-Commerce settings saved successfully.');
    }

    protected function savePricing($request, $ecommerceRepo)
    {
        $prices = $request->getParameter('price', []);
        $active = $request->getParameter('price_active', []);

        foreach ($prices as $typeId => $price) {
            // Get existing pricing or use default name
            $existingPrice = null;
            foreach ($this->pricing as $p) {
                if ($p->product_type_id == $typeId) {
                    $existingPrice = $p;
                    break;
                }
            }

            $name = $existingPrice->name ?? $this->getProductTypeName($typeId);

            $ecommerceRepo->savePricing([
                'repository_id' => null,
                'product_type_id' => $typeId,
                'name' => $name,
                'price' => floatval($price),
                'is_active' => isset($active[$typeId]) ? 1 : 0,
            ]);
        }

        $this->context->user->setFlash('notice', 'Product pricing updated successfully.');
    }

    protected function getProductTypeName($typeId)
    {
        $names = [
            1 => 'Low Resolution Digital',
            2 => 'High Resolution Digital',
            3 => 'TIFF Master',
            4 => 'A4 Print',
            5 => 'A3 Print',
            6 => 'A2 Print',
            7 => 'Non-Commercial License',
            8 => 'Commercial License',
            9 => 'Research Use',
        ];
        return $names[$typeId] ?? 'Product ' . $typeId;
    }
}
