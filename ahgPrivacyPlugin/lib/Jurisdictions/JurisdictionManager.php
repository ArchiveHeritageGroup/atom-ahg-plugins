<?php

use Illuminate\Database\Capsule\Manager as DB;

/**
 * JurisdictionManager - Manages privacy compliance jurisdictions.
 *
 * Handles jurisdiction discovery, installation, and configuration.
 */
class JurisdictionManager
{
    private static ?self $instance = null;

    public static function getInstance(): self
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Get all available jurisdictions.
     *
     * @return array Available jurisdictions from database
     */
    public function getAvailableJurisdictions(): array
    {
        return DB::table('privacy_jurisdiction_registry')
            ->orderBy('sort_order')
            ->get()
            ->toArray();
    }

    /**
     * Get installed jurisdictions.
     *
     * @return array Installed jurisdictions
     */
    public function getInstalledJurisdictions(): array
    {
        return DB::table('privacy_jurisdiction_registry')
            ->where('is_installed', 1)
            ->orderBy('sort_order')
            ->get()
            ->toArray();
    }

    /**
     * Check if a jurisdiction is installed.
     *
     * @param string $code Jurisdiction code
     *
     * @return bool
     */
    public function isInstalled(string $code): bool
    {
        return DB::table('privacy_jurisdiction_registry')
            ->where('code', $code)
            ->where('is_installed', 1)
            ->exists();
    }

    /**
     * Install a jurisdiction.
     *
     * @param string $code Jurisdiction code to install
     *
     * @return array Installation result
     */
    public function installJurisdiction(string $code): array
    {
        // Check if jurisdiction exists in registry
        $jurisdiction = DB::table('privacy_jurisdiction_registry')
            ->where('code', $code)
            ->first();

        if (!$jurisdiction) {
            return [
                'success' => false,
                'error' => "Unknown jurisdiction: {$code}",
            ];
        }

        if ($jurisdiction->is_installed) {
            return [
                'success' => true,
                'message' => "Jurisdiction {$code} is already installed",
                'already_installed' => true,
            ];
        }

        // Find and execute the SQL file
        $sqlFile = dirname(__FILE__) . '/../../database/jurisdictions/' . $code . '.sql';

        if (!file_exists($sqlFile)) {
            return [
                'success' => false,
                'error' => "SQL file not found for jurisdiction: {$code}",
            ];
        }

        try {
            $sql = file_get_contents($sqlFile);

            // Execute SQL statements
            $pdo = DB::connection()->getPdo();
            $pdo->exec($sql);

            // Get counts for installed items
            $lawfulBasesCount = DB::table('privacy_lawful_basis')
                ->where('jurisdiction_code', $code)
                ->count();

            $specialCategoriesCount = DB::table('privacy_special_category')
                ->where('jurisdiction_code', $code)
                ->count();

            $requestTypesCount = DB::table('privacy_request_type')
                ->where('jurisdiction_code', $code)
                ->count();

            $complianceRulesCount = DB::table('privacy_compliance_rule')
                ->where('jurisdiction_code', $code)
                ->count();

            return [
                'success' => true,
                'message' => "Jurisdiction {$code} installed successfully",
                'jurisdiction_code' => $code,
                'jurisdiction_name' => $jurisdiction->name,
                'full_name' => $jurisdiction->full_name,
                'lawful_bases_installed' => $lawfulBasesCount,
                'special_categories_installed' => $specialCategoriesCount,
                'request_types_installed' => $requestTypesCount,
                'compliance_rules_installed' => $complianceRulesCount,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Installation failed: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Uninstall a jurisdiction.
     *
     * @param string $code Jurisdiction code to uninstall
     *
     * @return array Uninstall result
     */
    public function uninstallJurisdiction(string $code): array
    {
        $jurisdiction = DB::table('privacy_jurisdiction_registry')
            ->where('code', $code)
            ->first();

        if (!$jurisdiction || !$jurisdiction->is_installed) {
            return [
                'success' => false,
                'error' => "Jurisdiction {$code} is not installed",
            ];
        }

        // Check if jurisdiction is in use (DSARs, breaches, etc.)
        $dsarCount = DB::table('privacy_dsar')
            ->where('jurisdiction', $code)
            ->count();

        $breachCount = DB::table('privacy_breach')
            ->where('jurisdiction', $code)
            ->count();

        if ($dsarCount > 0 || $breachCount > 0) {
            return [
                'success' => false,
                'error' => "Cannot uninstall: {$dsarCount} DSARs and {$breachCount} breaches use this jurisdiction",
            ];
        }

        try {
            // Delete jurisdiction-specific data
            DB::table('privacy_lawful_basis')
                ->where('jurisdiction_code', $code)
                ->delete();

            DB::table('privacy_special_category')
                ->where('jurisdiction_code', $code)
                ->delete();

            DB::table('privacy_request_type')
                ->where('jurisdiction_code', $code)
                ->delete();

            DB::table('privacy_compliance_rule')
                ->where('jurisdiction_code', $code)
                ->delete();

            // Mark as uninstalled
            DB::table('privacy_jurisdiction_registry')
                ->where('code', $code)
                ->update([
                    'is_installed' => 0,
                    'is_active' => 0,
                    'installed_at' => null,
                ]);

            return [
                'success' => true,
                'message' => "Jurisdiction {$code} uninstalled successfully",
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Uninstall failed: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Set active jurisdiction for an institution.
     *
     * @param string   $code         Jurisdiction code
     * @param int|null $repositoryId Repository ID (null for global)
     * @param array    $options      Additional options
     *
     * @return array Result
     */
    public function setActiveJurisdiction(string $code, ?int $repositoryId = null, array $options = []): array
    {
        if (!$this->isInstalled($code)) {
            return [
                'success' => false,
                'error' => "Jurisdiction {$code} is not installed. Run: php symfony privacy:jurisdiction --install={$code}",
            ];
        }

        $jurisdiction = DB::table('privacy_jurisdiction_registry')
            ->where('code', $code)
            ->first();

        $data = [
            'jurisdiction_code' => $code,
            'dsar_response_days' => $options['dsar_days'] ?? $jurisdiction->dsar_days,
            'breach_notification_hours' => $options['breach_hours'] ?? $jurisdiction->breach_hours,
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        if (isset($options['organization_name'])) {
            $data['organization_name'] = $options['organization_name'];
        }

        // Upsert
        $existing = DB::table('privacy_institution_config')
            ->where('repository_id', $repositoryId)
            ->first();

        if ($existing) {
            DB::table('privacy_institution_config')
                ->where('id', $existing->id)
                ->update($data);
        } else {
            $data['repository_id'] = $repositoryId;
            $data['created_at'] = date('Y-m-d H:i:s');
            DB::table('privacy_institution_config')->insert($data);
        }

        return [
            'success' => true,
            'message' => "Active jurisdiction set to {$code}",
            'jurisdiction_code' => $code,
            'jurisdiction_name' => $jurisdiction->name,
        ];
    }

    /**
     * Get active jurisdiction for a repository.
     *
     * @param int|null $repositoryId Repository ID (null for global)
     *
     * @return object|null Active jurisdiction configuration
     */
    public function getActiveJurisdiction(?int $repositoryId = null): ?object
    {
        $config = DB::table('privacy_institution_config')
            ->where('repository_id', $repositoryId)
            ->first();

        if (!$config) {
            // Try global config
            $config = DB::table('privacy_institution_config')
                ->whereNull('repository_id')
                ->first();
        }

        if (!$config) {
            return null;
        }

        // Get full jurisdiction details
        $jurisdiction = DB::table('privacy_jurisdiction_registry')
            ->where('code', $config->jurisdiction_code)
            ->first();

        if ($jurisdiction) {
            $config->jurisdiction = $jurisdiction;
        }

        return $config;
    }

    /**
     * Get lawful bases for a jurisdiction.
     *
     * @param string $code Jurisdiction code
     *
     * @return array Lawful bases
     */
    public function getLawfulBases(string $code): array
    {
        return DB::table('privacy_lawful_basis')
            ->where('jurisdiction_code', $code)
            ->where('is_active', 1)
            ->orderBy('sort_order')
            ->get()
            ->toArray();
    }

    /**
     * Get special categories for a jurisdiction.
     *
     * @param string $code Jurisdiction code
     *
     * @return array Special categories
     */
    public function getSpecialCategories(string $code): array
    {
        return DB::table('privacy_special_category')
            ->where('jurisdiction_code', $code)
            ->where('is_active', 1)
            ->orderBy('sort_order')
            ->get()
            ->toArray();
    }

    /**
     * Get request types for a jurisdiction.
     *
     * @param string $code Jurisdiction code
     *
     * @return array Request types
     */
    public function getRequestTypes(string $code): array
    {
        return DB::table('privacy_request_type')
            ->where('jurisdiction_code', $code)
            ->where('is_active', 1)
            ->orderBy('sort_order')
            ->get()
            ->toArray();
    }

    /**
     * Get compliance rules for a jurisdiction.
     *
     * @param string      $code     Jurisdiction code
     * @param string|null $category Optional category filter
     *
     * @return array Compliance rules
     */
    public function getComplianceRules(string $code, ?string $category = null): array
    {
        $query = DB::table('privacy_compliance_rule')
            ->where('jurisdiction_code', $code)
            ->where('is_active', 1);

        if ($category) {
            $query->where('category', $category);
        }

        return $query->orderBy('category')
            ->orderBy('sort_order')
            ->get()
            ->toArray();
    }

    /**
     * Get jurisdiction statistics.
     *
     * @param string $code Jurisdiction code
     *
     * @return array Statistics
     */
    public function getJurisdictionStats(string $code): array
    {
        return [
            'lawful_bases' => DB::table('privacy_lawful_basis')
                ->where('jurisdiction_code', $code)->count(),
            'special_categories' => DB::table('privacy_special_category')
                ->where('jurisdiction_code', $code)->count(),
            'request_types' => DB::table('privacy_request_type')
                ->where('jurisdiction_code', $code)->count(),
            'compliance_rules' => DB::table('privacy_compliance_rule')
                ->where('jurisdiction_code', $code)->count(),
            'dsars' => DB::table('privacy_dsar')
                ->where('jurisdiction', $code)->count(),
            'breaches' => DB::table('privacy_breach')
                ->where('jurisdiction', $code)->count(),
        ];
    }
}
