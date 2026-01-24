<?php
use Illuminate\Database\Capsule\Manager as DB;

return new class {
    public function up(): void
    {
        if (DB::schema()->hasTable('heritage_asset')) {
            if (!DB::schema()->hasColumn('heritage_asset', 'last_valuation_date')) {
                DB::statement("ALTER TABLE `heritage_asset` ADD COLUMN `last_valuation_date` DATE NULL");
            }
            if (!DB::schema()->hasColumn('heritage_asset', 'last_valuation_amount')) {
                DB::statement("ALTER TABLE `heritage_asset` ADD COLUMN `last_valuation_amount` DECIMAL(18,2) NULL AFTER `last_valuation_date`");
            }
            if (!DB::schema()->hasColumn('heritage_asset', 'valuation_method')) {
                DB::statement("ALTER TABLE `heritage_asset` ADD COLUMN `valuation_method` ENUM('market','cost','income','expert','insurance','other') NULL AFTER `last_valuation_amount`");
            }
            if (!DB::schema()->hasColumn('heritage_asset', 'valuer_name')) {
                DB::statement("ALTER TABLE `heritage_asset` ADD COLUMN `valuer_name` VARCHAR(255) NULL AFTER `valuation_method`");
            }
            if (!DB::schema()->hasColumn('heritage_asset', 'valuer_credentials')) {
                DB::statement("ALTER TABLE `heritage_asset` ADD COLUMN `valuer_credentials` VARCHAR(255) NULL AFTER `valuer_name`");
            }
        }
    }

    public function down(): void
    {
        // Not reversible
    }
};
