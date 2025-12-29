<?php
/**
 * GRAP Plugin Installation Service
 * 
 * Handles database schema creation and plugin initialization.
 * 
 * @author Johan Pieterse <johan@theahg.co.za>
 * @package ahgGrapPlugin
 */

class ahgGrapInstallService
{
    /**
     * Install database schema
     */
    public static function install()
    {
        $conn = Propel::getConnection();

        // Create grap_heritage_asset table
        $sql = "CREATE TABLE IF NOT EXISTS grap_heritage_asset (
            id INT AUTO_INCREMENT PRIMARY KEY,
            object_id INT NOT NULL UNIQUE,
            recognition_status VARCHAR(50) NOT NULL DEFAULT 'unrecognised',
            asset_class VARCHAR(50),
            measurement_basis VARCHAR(50),
            initial_recognition_date DATE,
            initial_cost DECIMAL(18,2),
            carrying_amount DECIMAL(18,2),
            accumulated_impairment DECIMAL(18,2) DEFAULT 0,
            revaluation_surplus DECIMAL(18,2) DEFAULT 0,
            last_valuation_date DATE,
            last_valuation_amount DECIMAL(18,2),
            valuation_history JSON,
            impairment_history JSON,
            derecognition_date DATE,
            derecognition_reason VARCHAR(50),
            disposal_proceeds DECIMAL(18,2),
            gain_loss_on_disposal DECIMAL(18,2),
            useful_life VARCHAR(50) DEFAULT 'indefinite',
            depreciation_method VARCHAR(50) DEFAULT 'none',
            residual_value DECIMAL(18,2),
            funding_source VARCHAR(255),
            donor_restrictions TEXT,
            insurance_value DECIMAL(18,2),
            metadata JSON,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            INDEX idx_object (object_id),
            INDEX idx_status (recognition_status),
            INDEX idx_class (asset_class),
            INDEX idx_recognition_date (initial_recognition_date),
            INDEX idx_carrying_amount (carrying_amount),
            FOREIGN KEY (object_id) REFERENCES information_object(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        try {
            $conn->exec($sql);
            echo "Created grap_heritage_asset table\n";
        } catch (Exception $e) {
            if (strpos($e->getMessage(), 'already exists') === false) {
                throw $e;
            }
            echo "grap_heritage_asset table already exists\n";
        }

        // Create grap_transaction_log table
        $sql = "CREATE TABLE IF NOT EXISTS grap_transaction_log (
            id INT AUTO_INCREMENT PRIMARY KEY,
            object_id INT NOT NULL,
            transaction_type VARCHAR(50) NOT NULL,
            transaction_data JSON,
            user_id INT,
            created_at DATETIME NOT NULL,
            INDEX idx_object (object_id),
            INDEX idx_type (transaction_type),
            INDEX idx_created (created_at),
            INDEX idx_user (user_id),
            FOREIGN KEY (object_id) REFERENCES information_object(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        try {
            $conn->exec($sql);
            echo "Created grap_transaction_log table\n";
        } catch (Exception $e) {
            if (strpos($e->getMessage(), 'already exists') === false) {
                throw $e;
            }
            echo "grap_transaction_log table already exists\n";
        }

        // Create grap_financial_year_snapshot table (for trend reporting)
        $sql = "CREATE TABLE IF NOT EXISTS grap_financial_year_snapshot (
            id INT AUTO_INCREMENT PRIMARY KEY,
            repository_id INT,
            financial_year_end DATE NOT NULL,
            asset_class VARCHAR(50),
            total_assets INT DEFAULT 0,
            total_carrying_amount DECIMAL(18,2) DEFAULT 0,
            total_impairment DECIMAL(18,2) DEFAULT 0,
            total_revaluation_surplus DECIMAL(18,2) DEFAULT 0,
            acquisitions_count INT DEFAULT 0,
            acquisitions_value DECIMAL(18,2) DEFAULT 0,
            disposals_count INT DEFAULT 0,
            disposals_value DECIMAL(18,2) DEFAULT 0,
            impairments_count INT DEFAULT 0,
            impairments_value DECIMAL(18,2) DEFAULT 0,
            snapshot_data JSON,
            created_at DATETIME NOT NULL,
            INDEX idx_repository (repository_id),
            INDEX idx_fy (financial_year_end),
            INDEX idx_class (asset_class),
            UNIQUE KEY uk_repo_fy_class (repository_id, financial_year_end, asset_class)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        try {
            $conn->exec($sql);
            echo "Created grap_financial_year_snapshot table\n";
        } catch (Exception $e) {
            if (strpos($e->getMessage(), 'already exists') === false) {
                throw $e;
            }
            echo "grap_financial_year_snapshot table already exists\n";
        }

        // Create grap_compliance_assessment table
        $sql = "CREATE TABLE IF NOT EXISTS grap_compliance_assessment (
            id INT AUTO_INCREMENT PRIMARY KEY,
            object_id INT NOT NULL,
            assessment_date DATETIME NOT NULL,
            overall_score INT,
            category_scores JSON,
            issues JSON,
            recommendations JSON,
            assessed_by INT,
            created_at DATETIME NOT NULL,
            INDEX idx_object (object_id),
            INDEX idx_date (assessment_date),
            FOREIGN KEY (object_id) REFERENCES information_object(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        try {
            $conn->exec($sql);
            echo "Created grap_compliance_assessment table\n";
        } catch (Exception $e) {
            if (strpos($e->getMessage(), 'already exists') === false) {
                throw $e;
            }
            echo "grap_compliance_assessment table already exists\n";
        }

        echo "ahgGrapPlugin installation complete\n";
        return true;
    }

    /**
     * Uninstall database schema
     */
    public static function uninstall($dropTables = false)
    {
        if (!$dropTables) {
            echo "Uninstall called but dropTables=false, skipping\n";
            return true;
        }

        $conn = Propel::getConnection();

        $tables = [
            'grap_compliance_assessment',
            'grap_financial_year_snapshot',
            'grap_transaction_log',
            'grap_heritage_asset'
        ];

        foreach ($tables as $table) {
            try {
                $conn->exec("DROP TABLE IF EXISTS {$table}");
                echo "Dropped {$table}\n";
            } catch (Exception $e) {
                echo "Error dropping {$table}: " . $e->getMessage() . "\n";
            }
        }

        echo "ahgGrapPlugin uninstallation complete\n";
        return true;
    }

    /**
     * Check if plugin is installed
     */
    public static function isInstalled()
    {
        $conn = Propel::getConnection();

        try {
            $stmt = $conn->query("SHOW TABLES LIKE 'grap_heritage_asset'");
            return $stmt->rowCount() > 0;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Get installation status
     */
    public static function getStatus()
    {
        $conn = Propel::getConnection();
        $status = [];

        $tables = [
            'grap_heritage_asset',
            'grap_transaction_log',
            'grap_financial_year_snapshot',
            'grap_compliance_assessment'
        ];

        foreach ($tables as $table) {
            try {
                $stmt = $conn->query("SHOW TABLES LIKE '{$table}'");
                $exists = $stmt->rowCount() > 0;

                $count = 0;
                if ($exists) {
                    $stmt = $conn->query("SELECT COUNT(*) FROM {$table}");
                    $count = $stmt->fetchColumn();
                }

                $status[$table] = [
                    'exists' => $exists,
                    'count' => $count
                ];
            } catch (Exception $e) {
                $status[$table] = [
                    'exists' => false,
                    'error' => $e->getMessage()
                ];
            }
        }

        return $status;
    }

    /**
     * Create financial year snapshot
     */
    public static function createFinancialYearSnapshot($repositoryId = null, $financialYearEnd = null)
    {
        $conn = Propel::getConnection();
        $fyEnd = $financialYearEnd ?? date('Y') . '-03-31';

        // Get summary by class
        $sql = "SELECT 
                    g.asset_class,
                    COUNT(*) as total_assets,
                    SUM(COALESCE(g.current_carrying_amount, 0)) as total_carrying_amount,
                    SUM(COALESCE(g.accumulated_impairment, 0)) as total_impairment,
                    SUM(COALESCE(g.revaluation_surplus, 0)) as total_revaluation_surplus
                FROM grap_heritage_asset g
                JOIN information_object io ON g.object_id = io.id
                WHERE g.recognition_status IN ('recognised', 'impaired')";

        $params = [];
        if ($repositoryId) {
            $sql .= " AND io.repository_id = :repository_id";
            $params[':repository_id'] = $repositoryId;
        }

        $sql .= " GROUP BY g.asset_class";

        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Insert snapshots
        $insertSql = "INSERT INTO grap_financial_year_snapshot 
                      (repository_id, financial_year_end, asset_class, total_assets, 
                       total_carrying_amount, total_impairment, total_revaluation_surplus, created_at)
                      VALUES (:repository_id, :fy_end, :asset_class, :total_assets,
                              :total_carrying, :total_impairment, :total_surplus, :created_at)
                      ON DUPLICATE KEY UPDATE
                      total_assets = VALUES(total_assets),
                      total_carrying_amount = VALUES(total_carrying_amount),
                      total_impairment = VALUES(total_impairment),
                      total_revaluation_surplus = VALUES(total_revaluation_surplus)";

        $insertStmt = $conn->prepare($insertSql);
        $now = date('Y-m-d H:i:s');

        foreach ($results as $row) {
            $insertStmt->execute([
                ':repository_id' => $repositoryId,
                ':fy_end' => $fyEnd,
                ':asset_class' => $row['asset_class'],
                ':total_assets' => $row['total_assets'],
                ':total_carrying' => $row['total_carrying_amount'],
                ':total_impairment' => $row['total_impairment'],
                ':total_surplus' => $row['total_revaluation_surplus'],
                ':created_at' => $now
            ]);
        }

        return count($results);
    }

    /**
     * Migrate existing valuation data from AtoM properties
     */
    public static function migrateExistingData()
    {
        $conn = Propel::getConnection();
        $assetService = new ahgGrapHeritageAssetService();

        // Look for objects with existing valuation properties
        $sql = "SELECT DISTINCT p.object_id 
                FROM property p 
                WHERE p.name IN ('estimatedValue', 'insuranceValue', 'acquisitionValue')";

        $stmt = $conn->query($sql);
        $objects = $stmt->fetchAll(PDO::FETCH_COLUMN);

        $migrated = 0;
        foreach ($objects as $objectId) {
            try {
                // Get existing properties
                $props = [];
                $propSql = "SELECT name, value FROM property WHERE object_id = :object_id";
                $propStmt = $conn->prepare($propSql);
                $propStmt->execute([':object_id' => $objectId]);
                
                while ($row = $propStmt->fetch(PDO::FETCH_ASSOC)) {
                    $props[$row['name']] = $row['value'];
                }

                // Create GRAP record
                $data = [
                    'recognition_status' => arGrapHeritageAssetService::STATUS_PENDING_RECOGNITION,
                    'initial_cost' => $props['acquisitionValue'] ?? $props['estimatedValue'] ?? null,
                    'carrying_amount' => $props['estimatedValue'] ?? $props['acquisitionValue'] ?? null,
                    'insurance_value' => $props['insuranceValue'] ?? null,
                    'metadata' => ['migrated_from_properties' => true]
                ];

                $assetService->saveAssetRecord($objectId, $data);
                $migrated++;
            } catch (Exception $e) {
                echo "Error migrating object {$objectId}: " . $e->getMessage() . "\n";
            }
        }

        return $migrated;
    }
}
