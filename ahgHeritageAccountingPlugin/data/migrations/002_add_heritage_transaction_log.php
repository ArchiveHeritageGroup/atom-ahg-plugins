<?php
use Illuminate\Database\Capsule\Manager as DB;

return new class {
    public function up(): void
    {
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
        DB::schema()->dropIfExists('heritage_transaction_log');
    }
};
