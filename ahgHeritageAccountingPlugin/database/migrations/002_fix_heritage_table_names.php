<?php
use Illuminate\Database\Capsule\Manager as DB;

return new class {
    public function up(): void
    {
        // Rename tables to match code
        $renames = [
            'heritage_valuation' => 'heritage_valuation_history',
            'heritage_impairment' => 'heritage_impairment_assessment',
            'heritage_movement' => 'heritage_movement_register',
        ];
        
        foreach ($renames as $old => $new) {
            if (DB::schema()->hasTable($old) && !DB::schema()->hasTable($new)) {
                DB::statement("RENAME TABLE `{$old}` TO `{$new}`");
            }
        }
        
        // Create heritage_transaction_log if missing
        if (!DB::schema()->hasTable('heritage_transaction_log')) {
            DB::statement("
                CREATE TABLE `heritage_transaction_log` (
                    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    `heritage_asset_id` INT UNSIGNED NOT NULL,
                    `object_id` INT NULL,
                    `transaction_type` VARCHAR(50) NOT NULL,
                    `transaction_date` DATE NOT NULL,
                    `amount` DECIMAL(15,2) NULL,
                    `transaction_data` JSON NULL,
                    `user_id` INT NULL,
                    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
                    INDEX `idx_heritage_asset` (`heritage_asset_id`),
                    INDEX `idx_object` (`object_id`),
                    INDEX `idx_type` (`transaction_type`),
                    INDEX `idx_date` (`transaction_date`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
        }
    }

    public function down(): void
    {
        // Reverse renames
        $renames = [
            'heritage_valuation_history' => 'heritage_valuation',
            'heritage_impairment_assessment' => 'heritage_impairment',
            'heritage_movement_register' => 'heritage_movement',
        ];
        
        foreach ($renames as $old => $new) {
            if (DB::schema()->hasTable($old)) {
                DB::statement("RENAME TABLE `{$old}` TO `{$new}`");
            }
        }
    }
};
