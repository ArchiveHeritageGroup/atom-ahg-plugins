<?php

use Illuminate\Database\Capsule\Manager as DB;

require_once dirname(__FILE__) . '/RegionAdapterInterface.php';
require_once dirname(__FILE__) . '/BaseRegionAdapter.php';

/**
 * RegionManager - Manages regional heritage accounting adapters.
 *
 * Handles region discovery, installation, and adapter loading.
 */
class RegionManager
{
    private static ?self $instance = null;

    /**
     * Map of region codes to adapter classes.
     */
    private array $adapterMap = [
        'africa_ipsas' => 'AfricaIpsasAdapter',
        'south_africa_grap' => 'SouthAfricaGrapAdapter',
        'uk_frs' => 'UkFrsAdapter',
        'usa_government' => 'UsaGovernmentAdapter',
        'usa_nonprofit' => 'UsaNonprofitAdapter',
        'australia_nz' => 'AustraliaNzAdapter',
        'canada_psas' => 'CanadaPsasAdapter',
        'international_private' => 'InternationalPrivateAdapter',
    ];

    private array $loadedAdapters = [];

    public static function getInstance(): self
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Get all available regions.
     *
     * @return array Available regions from database
     */
    public function getAvailableRegions(): array
    {
        return DB::table('heritage_regional_config')
            ->select(['region_code', 'region_name', 'countries', 'default_currency', 'regulatory_body', 'is_installed', 'installed_at'])
            ->orderBy('region_name')
            ->get()
            ->map(function ($row) {
                $row->countries = json_decode($row->countries, true) ?? [];

                return $row;
            })
            ->toArray();
    }

    /**
     * Get installed regions.
     *
     * @return array Installed regions
     */
    public function getInstalledRegions(): array
    {
        return DB::table('heritage_regional_config')
            ->where('is_installed', 1)
            ->select(['region_code', 'region_name', 'countries', 'default_currency', 'regulatory_body', 'installed_at'])
            ->orderBy('region_name')
            ->get()
            ->map(function ($row) {
                $row->countries = json_decode($row->countries, true) ?? [];

                return $row;
            })
            ->toArray();
    }

    /**
     * Check if a region is installed.
     *
     * @param string $regionCode Region code
     *
     * @return bool
     */
    public function isInstalled(string $regionCode): bool
    {
        return DB::table('heritage_regional_config')
            ->where('region_code', $regionCode)
            ->where('is_installed', 1)
            ->exists();
    }

    /**
     * Install a region.
     *
     * @param string $regionCode Region code to install
     *
     * @return array Installation result
     */
    public function installRegion(string $regionCode): array
    {
        // Check if region exists
        $region = DB::table('heritage_regional_config')
            ->where('region_code', $regionCode)
            ->first();

        if (!$region) {
            return [
                'success' => false,
                'error' => "Unknown region: {$regionCode}",
            ];
        }

        if ($region->is_installed) {
            return [
                'success' => true,
                'message' => "Region {$regionCode} is already installed",
                'already_installed' => true,
            ];
        }

        // Find and execute the SQL file
        $sqlFile = dirname(__FILE__) . '/../../database/regions/' . $regionCode . '.sql';

        if (!file_exists($sqlFile)) {
            return [
                'success' => false,
                'error' => "SQL file not found for region: {$regionCode}",
            ];
        }

        try {
            $sql = file_get_contents($sqlFile);

            // Execute SQL statements
            $pdo = DB::connection()->getPdo();
            $pdo->exec($sql);

            // Verify installation
            DB::table('heritage_regional_config')
                ->where('region_code', $regionCode)
                ->update([
                    'is_installed' => 1,
                    'installed_at' => date('Y-m-d H:i:s'),
                ]);

            // Get standard info
            $standard = DB::table('heritage_accounting_standard')
                ->where('region_code', $regionCode)
                ->first();

            // Count rules installed
            $rulesCount = 0;
            if ($standard) {
                $rulesCount = DB::table('heritage_compliance_rule')
                    ->where('standard_id', $standard->id)
                    ->count();
            }

            return [
                'success' => true,
                'message' => "Region {$regionCode} installed successfully",
                'region_code' => $regionCode,
                'region_name' => $region->region_name,
                'standard_code' => $standard->code ?? null,
                'standard_name' => $standard->name ?? null,
                'compliance_rules_installed' => $rulesCount,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Installation failed: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Uninstall a region (remove standard and rules, keep config).
     *
     * @param string $regionCode Region code to uninstall
     *
     * @return array Uninstall result
     */
    public function uninstallRegion(string $regionCode): array
    {
        $region = DB::table('heritage_regional_config')
            ->where('region_code', $regionCode)
            ->first();

        if (!$region || !$region->is_installed) {
            return [
                'success' => false,
                'error' => "Region {$regionCode} is not installed",
            ];
        }

        try {
            // Get standard
            $standard = DB::table('heritage_accounting_standard')
                ->where('region_code', $regionCode)
                ->first();

            if ($standard) {
                // Check for assets using this standard
                $assetsCount = DB::table('heritage_asset')
                    ->where('accounting_standard_id', $standard->id)
                    ->count();

                if ($assetsCount > 0) {
                    return [
                        'success' => false,
                        'error' => "Cannot uninstall: {$assetsCount} assets are using this standard",
                    ];
                }

                // Delete compliance rules
                DB::table('heritage_compliance_rule')
                    ->where('standard_id', $standard->id)
                    ->delete();

                // Delete standard
                DB::table('heritage_accounting_standard')
                    ->where('id', $standard->id)
                    ->delete();
            }

            // Mark as uninstalled
            DB::table('heritage_regional_config')
                ->where('region_code', $regionCode)
                ->update([
                    'is_installed' => 0,
                    'installed_at' => null,
                ]);

            return [
                'success' => true,
                'message' => "Region {$regionCode} uninstalled successfully",
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Uninstall failed: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Get adapter for a region.
     *
     * @param string $regionCode Region code
     *
     * @return RegionAdapterInterface|null
     */
    public function getAdapter(string $regionCode): ?RegionAdapterInterface
    {
        if (isset($this->loadedAdapters[$regionCode])) {
            return $this->loadedAdapters[$regionCode];
        }

        if (!isset($this->adapterMap[$regionCode])) {
            return null;
        }

        $className = $this->adapterMap[$regionCode];
        $filePath = dirname(__FILE__) . '/' . $className . '.php';

        if (!file_exists($filePath)) {
            // Try to use base adapter
            return null;
        }

        require_once $filePath;

        if (!class_exists($className)) {
            return null;
        }

        $this->loadedAdapters[$regionCode] = new $className();

        return $this->loadedAdapters[$regionCode];
    }

    /**
     * Get adapter for current institution configuration.
     *
     * @param int|null $repositoryId Repository ID (null for global)
     *
     * @return RegionAdapterInterface|null
     */
    public function getActiveAdapter(?int $repositoryId = null): ?RegionAdapterInterface
    {
        $config = DB::table('heritage_institution_config')
            ->where('repository_id', $repositoryId)
            ->first();

        if (!$config) {
            // Try global config
            $config = DB::table('heritage_institution_config')
                ->whereNull('repository_id')
                ->first();
        }

        if (!$config) {
            return null;
        }

        return $this->getAdapter($config->region_code);
    }

    /**
     * Set active region for an institution.
     *
     * @param string   $regionCode   Region code
     * @param int|null $repositoryId Repository ID (null for global)
     * @param array    $options      Additional options
     *
     * @return array Result
     */
    public function setActiveRegion(string $regionCode, ?int $repositoryId = null, array $options = []): array
    {
        if (!$this->isInstalled($regionCode)) {
            return [
                'success' => false,
                'error' => "Region {$regionCode} is not installed. Run: php symfony heritage:region --install={$regionCode}",
            ];
        }

        $region = DB::table('heritage_regional_config')
            ->where('region_code', $regionCode)
            ->first();

        $standard = DB::table('heritage_accounting_standard')
            ->where('region_code', $regionCode)
            ->first();

        $data = [
            'region_code' => $regionCode,
            'accounting_standard_id' => $standard->id ?? null,
            'currency' => $options['currency'] ?? $region->default_currency,
            'financial_year_start' => $options['financial_year_start'] ?? $region->financial_year_start,
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        // Upsert
        $existing = DB::table('heritage_institution_config')
            ->where('repository_id', $repositoryId)
            ->first();

        if ($existing) {
            DB::table('heritage_institution_config')
                ->where('id', $existing->id)
                ->update($data);
        } else {
            $data['repository_id'] = $repositoryId;
            $data['created_at'] = date('Y-m-d H:i:s');
            DB::table('heritage_institution_config')->insert($data);
        }

        return [
            'success' => true,
            'message' => "Active region set to {$regionCode}",
            'region_code' => $regionCode,
            'standard_code' => $standard->code ?? null,
        ];
    }
}
