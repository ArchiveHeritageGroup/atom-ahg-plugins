<?php

use Illuminate\Database\Capsule\Manager as DB;

/**
 * BaseRegionAdapter - Base class for regional heritage accounting adapters.
 *
 * Provides common functionality for all regional adapters.
 */
abstract class BaseRegionAdapter implements RegionAdapterInterface
{
    protected string $regionCode;
    protected string $standardCode;
    protected ?object $regionConfig = null;
    protected ?object $standard = null;

    public function __construct()
    {
        $this->loadRegionConfig();
        $this->loadStandard();
    }

    /**
     * Load region configuration from database.
     */
    protected function loadRegionConfig(): void
    {
        $this->regionConfig = DB::table('heritage_regional_config')
            ->where('region_code', $this->regionCode)
            ->first();
    }

    /**
     * Load accounting standard from database.
     */
    protected function loadStandard(): void
    {
        $this->standard = DB::table('heritage_accounting_standard')
            ->where('code', $this->standardCode)
            ->first();
    }

    public function getRegionCode(): string
    {
        return $this->regionCode;
    }

    public function getStandardCode(): string
    {
        return $this->standardCode;
    }

    public function getRegionName(): string
    {
        return $this->regionConfig->region_name ?? $this->regionCode;
    }

    public function getCountries(): array
    {
        if ($this->regionConfig && $this->regionConfig->countries) {
            return json_decode($this->regionConfig->countries, true) ?? [];
        }

        return [];
    }

    public function getDefaultCurrency(): string
    {
        return $this->regionConfig->default_currency ?? 'USD';
    }

    public function getSupportedCurrencies(): array
    {
        if ($this->regionConfig && $this->regionConfig->config_data) {
            $config = json_decode($this->regionConfig->config_data, true);

            return $config['currencies'] ?? [$this->getDefaultCurrency()];
        }

        return [$this->getDefaultCurrency()];
    }

    public function getFinancialYearStart(): string
    {
        return $this->regionConfig->financial_year_start ?? '01-01';
    }

    public function getRegulatoryBody(): string
    {
        return $this->regionConfig->regulatory_body ?? 'Not specified';
    }

    public function getReportFormats(): array
    {
        if ($this->regionConfig && $this->regionConfig->report_formats) {
            return json_decode($this->regionConfig->report_formats, true) ?? [];
        }

        return [];
    }

    public function getConfig(): array
    {
        if ($this->regionConfig && $this->regionConfig->config_data) {
            return json_decode($this->regionConfig->config_data, true) ?? [];
        }

        return [];
    }

    /**
     * Run compliance check on an asset.
     *
     * @param int $assetId Heritage asset ID
     *
     * @return array Compliance results
     */
    public function runComplianceCheck(int $assetId): array
    {
        $asset = DB::table('heritage_asset')->where('id', $assetId)->first();

        if (!$asset) {
            return [
                'success' => false,
                'error' => 'Asset not found',
                'issues' => [],
                'warnings' => [],
                'info' => [],
            ];
        }

        // Get compliance rules for this standard
        $rules = DB::table('heritage_compliance_rule as r')
            ->join('heritage_accounting_standard as s', 's.id', '=', 'r.standard_id')
            ->where('s.code', $this->standardCode)
            ->where('r.is_active', 1)
            ->orderBy('r.sort_order')
            ->get();

        $issues = [];
        $warnings = [];
        $info = [];

        foreach ($rules as $rule) {
            $result = $this->checkRule($asset, $rule);

            if (!$result['passed']) {
                switch ($rule->severity) {
                    case 'error':
                        $issues[] = [
                            'code' => $rule->code,
                            'name' => $rule->name,
                            'message' => $rule->error_message,
                            'reference' => $rule->reference,
                            'category' => $rule->category,
                        ];
                        break;
                    case 'warning':
                        $warnings[] = [
                            'code' => $rule->code,
                            'name' => $rule->name,
                            'message' => $rule->error_message,
                            'reference' => $rule->reference,
                            'category' => $rule->category,
                        ];
                        break;
                    case 'info':
                        $info[] = [
                            'code' => $rule->code,
                            'name' => $rule->name,
                            'message' => $rule->error_message,
                            'reference' => $rule->reference,
                            'category' => $rule->category,
                        ];
                        break;
                }
            }
        }

        $totalRules = count($rules);
        $passedRules = $totalRules - count($issues) - count($warnings);
        $complianceScore = $totalRules > 0 ? round(($passedRules / $totalRules) * 100) : 100;

        return [
            'success' => true,
            'asset_id' => $assetId,
            'standard' => $this->standardCode,
            'region' => $this->regionCode,
            'compliance_score' => $complianceScore,
            'total_rules' => $totalRules,
            'passed_rules' => $passedRules,
            'issues' => $issues,
            'warnings' => $warnings,
            'info' => $info,
            'is_compliant' => empty($issues),
        ];
    }

    /**
     * Check a single compliance rule against an asset.
     *
     * @param object $asset Asset record
     * @param object $rule  Compliance rule
     *
     * @return array ['passed' => bool]
     */
    protected function checkRule(object $asset, object $rule): array
    {
        $fieldName = $rule->field_name;
        $value = $asset->{$fieldName} ?? null;

        switch ($rule->check_type) {
            case 'required_field':
                return ['passed' => !empty($value) || '0' === $value];

            case 'value_check':
                if (null === $value) {
                    return ['passed' => false];
                }
                $condition = $rule->condition;
                if (str_starts_with($condition, '>=')) {
                    $threshold = (float) substr($condition, 2);

                    return ['passed' => (float) $value >= $threshold];
                } elseif (str_starts_with($condition, '>')) {
                    $threshold = (float) substr($condition, 1);

                    return ['passed' => (float) $value > $threshold];
                } elseif (str_starts_with($condition, '<=')) {
                    $threshold = (float) substr($condition, 2);

                    return ['passed' => (float) $value <= $threshold];
                } elseif (str_starts_with($condition, '<')) {
                    $threshold = (float) substr($condition, 1);

                    return ['passed' => (float) $value < $threshold];
                }

                return ['passed' => true];

            case 'date_check':
                return ['passed' => !empty($value) && false !== strtotime($value)];

            case 'custom':
                // Override in subclasses for custom checks
                return ['passed' => true];

            default:
                return ['passed' => true];
        }
    }

    /**
     * Generate a regional report.
     *
     * @param string $reportType Report type code
     * @param array  $options    Report options
     *
     * @return array Report data
     */
    public function generateReport(string $reportType, array $options = []): array
    {
        $formats = $this->getReportFormats();

        if (!isset($formats[$reportType])) {
            return [
                'success' => false,
                'error' => "Unknown report type: {$reportType}",
            ];
        }

        $format = $formats[$reportType];

        // Base query for assets
        $query = DB::table('heritage_asset as a')
            ->leftJoin('heritage_accounting_standard as s', 's.id', '=', 'a.accounting_standard_id')
            ->leftJoin('heritage_asset_class as c', 'c.id', '=', 'a.asset_class_id')
            ->where('s.code', $this->standardCode);

        // Apply date filters if provided
        if (!empty($options['from_date'])) {
            $query->where('a.created_at', '>=', $options['from_date']);
        }
        if (!empty($options['to_date'])) {
            $query->where('a.created_at', '<=', $options['to_date']);
        }

        // Select fields based on report format
        $query->select([
            'a.*',
            'c.name as asset_class_name',
            'c.code as asset_class_code',
            's.name as standard_name',
        ]);

        $assets = $query->get();

        return [
            'success' => true,
            'report_type' => $reportType,
            'report_name' => $format['name'],
            'format' => $format['format'],
            'region' => $this->regionCode,
            'standard' => $this->standardCode,
            'generated_at' => date('Y-m-d H:i:s'),
            'options' => $options,
            'data' => $assets->toArray(),
            'summary' => $this->generateReportSummary($assets),
        ];
    }

    /**
     * Generate summary statistics for a report.
     *
     * @param \Illuminate\Support\Collection $assets Assets collection
     *
     * @return array Summary data
     */
    protected function generateReportSummary($assets): array
    {
        return [
            'total_assets' => $assets->count(),
            'total_carrying_amount' => $assets->sum('current_carrying_amount'),
            'recognised_count' => $assets->where('recognition_status', 'recognised')->count(),
            'not_recognised_count' => $assets->where('recognition_status', 'not_recognised')->count(),
            'pending_count' => $assets->where('recognition_status', 'pending')->count(),
            'by_class' => $assets->groupBy('asset_class_name')->map(function ($group) {
                return [
                    'count' => $group->count(),
                    'total_value' => $group->sum('current_carrying_amount'),
                ];
            })->toArray(),
        ];
    }

    /**
     * Check if the region is installed.
     *
     * @return bool
     */
    public function isInstalled(): bool
    {
        return $this->regionConfig && $this->regionConfig->is_installed;
    }

    /**
     * Get standard information.
     *
     * @return array
     */
    public function getStandardInfo(): array
    {
        if (!$this->standard) {
            return [];
        }

        return [
            'code' => $this->standard->code,
            'name' => $this->standard->name,
            'country' => $this->standard->country,
            'description' => $this->standard->description,
            'capitalisation_required' => (bool) $this->standard->capitalisation_required,
            'valuation_methods' => json_decode($this->standard->valuation_methods, true) ?? [],
            'disclosure_requirements' => json_decode($this->standard->disclosure_requirements, true) ?? [],
        ];
    }
}
