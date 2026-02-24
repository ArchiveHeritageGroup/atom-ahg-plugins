<?php

use AtomFramework\Http\Controllers\AhgController;

require_once sfConfig::get('sf_root_dir') . '/atom-ahg-plugins/ahgMarketplacePlugin/lib/Services/CurrencyService.php';

use AtomAhgPlugins\ahgMarketplacePlugin\Services\CurrencyService;

class marketplaceApiCurrenciesAction extends AhgController
{
    public function execute($request)
    {
        $this->getResponse()->setContentType('application/json');

        $currencyService = new CurrencyService();
        $currencies = $currencyService->getCurrencies();

        $result = [];
        foreach ($currencies as $currency) {
            $result[] = [
                'code' => $currency->code,
                'name' => $currency->name,
                'symbol' => $currency->symbol,
                'rate' => (float) ($currency->exchange_rate_to_zar ?? 1.0),
                'decimal_places' => (int) ($currency->decimal_places ?? 2),
                'symbol_position' => $currency->symbol_position ?? 'before',
                'is_active' => (bool) $currency->is_active,
            ];
        }

        echo json_encode([
            'success' => true,
            'currencies' => $result,
        ]);

        return sfView::NONE;
    }
}
