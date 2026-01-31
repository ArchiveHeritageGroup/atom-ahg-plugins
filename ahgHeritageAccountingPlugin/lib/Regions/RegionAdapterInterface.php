<?php

/**
 * RegionAdapterInterface - Interface for regional heritage accounting adapters.
 *
 * Each region implements this interface to provide region-specific functionality.
 */
interface RegionAdapterInterface
{
    /**
     * Get the region code.
     *
     * @return string Region code (e.g., 'africa_ipsas', 'south_africa_grap')
     */
    public function getRegionCode(): string;

    /**
     * Get the accounting standard code.
     *
     * @return string Standard code (e.g., 'IPSAS45', 'GRAP103')
     */
    public function getStandardCode(): string;

    /**
     * Get the region name.
     *
     * @return string Human-readable region name
     */
    public function getRegionName(): string;

    /**
     * Get countries covered by this region.
     *
     * @return array List of country names
     */
    public function getCountries(): array;

    /**
     * Get default currency for the region.
     *
     * @return string Currency code (e.g., 'USD', 'ZAR')
     */
    public function getDefaultCurrency(): string;

    /**
     * Get supported currencies for the region.
     *
     * @return array List of currency codes
     */
    public function getSupportedCurrencies(): array;

    /**
     * Get financial year start date.
     *
     * @return string Date in MM-DD format (e.g., '04-01' for April 1)
     */
    public function getFinancialYearStart(): string;

    /**
     * Get regulatory body name.
     *
     * @return string Name of regulatory body
     */
    public function getRegulatoryBody(): string;

    /**
     * Get available report formats for this region.
     *
     * @return array Report configurations
     */
    public function getReportFormats(): array;

    /**
     * Run compliance check on an asset.
     *
     * @param int $assetId Heritage asset ID
     *
     * @return array Compliance results with issues/warnings
     */
    public function runComplianceCheck(int $assetId): array;

    /**
     * Generate a regional report.
     *
     * @param string $reportType Report type code
     * @param array  $options    Report options (dates, filters, etc.)
     *
     * @return array Report data
     */
    public function generateReport(string $reportType, array $options = []): array;

    /**
     * Get region-specific configuration.
     *
     * @return array Configuration array
     */
    public function getConfig(): array;
}
