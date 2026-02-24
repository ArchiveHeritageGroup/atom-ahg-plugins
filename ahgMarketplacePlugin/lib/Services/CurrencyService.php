<?php

namespace AtomAhgPlugins\ahgMarketplacePlugin\Services;

require_once dirname(__DIR__) . '/Repositories/SettingsRepository.php';

use AtomAhgPlugins\ahgMarketplacePlugin\Repositories\SettingsRepository;

class CurrencyService
{
    private SettingsRepository $settingsRepo;

    public function __construct()
    {
        $this->settingsRepo = new SettingsRepository();
    }

    // =========================================================================
    // Currency Queries
    // =========================================================================

    public function getCurrencies(bool $activeOnly = true): array
    {
        return $this->settingsRepo->getCurrencies($activeOnly);
    }

    public function getCurrency(string $code): ?object
    {
        return $this->settingsRepo->getCurrency(strtoupper($code));
    }

    public function getDefaultCurrency(): string
    {
        return $this->settingsRepo->get('default_currency', 'ZAR');
    }

    // =========================================================================
    // Conversion (via ZAR as base currency)
    // =========================================================================

    public function convert(float $amount, string $from, string $to): array
    {
        $from = strtoupper($from);
        $to = strtoupper($to);

        if ($from === $to) {
            return [
                'success' => true,
                'original_amount' => $amount,
                'converted_amount' => $amount,
                'from' => $from,
                'to' => $to,
                'rate' => 1.0,
            ];
        }

        // Convert source to ZAR first
        $zarAmount = $this->settingsRepo->convertToZar($amount, $from);

        // Convert ZAR to target
        $convertedAmount = $this->settingsRepo->convertFromZar($zarAmount, $to);

        // Calculate effective rate
        $rate = $amount > 0 ? round($convertedAmount / $amount, 6) : 0;

        return [
            'success' => true,
            'original_amount' => $amount,
            'converted_amount' => round($convertedAmount, 2),
            'from' => $from,
            'to' => $to,
            'rate' => $rate,
        ];
    }

    // =========================================================================
    // Formatting
    // =========================================================================

    public function formatPrice(float $amount, string $currencyCode): string
    {
        $currency = $this->settingsRepo->getCurrency(strtoupper($currencyCode));

        if (!$currency) {
            return strtoupper($currencyCode) . ' ' . number_format($amount, 2);
        }

        $symbol = $currency->symbol ?? strtoupper($currencyCode);
        $decimals = (int) ($currency->decimal_places ?? 2);
        $formatted = number_format($amount, $decimals, '.', ',');

        $symbolPosition = $currency->symbol_position ?? 'before';

        if ($symbolPosition === 'after') {
            return $formatted . ' ' . $symbol;
        }

        return $symbol . $formatted;
    }

    // =========================================================================
    // Exchange Rate Management
    // =========================================================================

    public function updateExchangeRate(string $code, float $rate): array
    {
        $code = strtoupper($code);
        $currency = $this->settingsRepo->getCurrency($code);

        if (!$currency) {
            return ['success' => false, 'error' => 'Currency not found: ' . $code];
        }

        if ($rate <= 0) {
            return ['success' => false, 'error' => 'Exchange rate must be greater than zero'];
        }

        $this->settingsRepo->updateCurrency($code, [
            'exchange_rate_to_zar' => $rate,
        ]);

        return ['success' => true, 'code' => $code, 'rate' => $rate];
    }
}
