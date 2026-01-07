<?php
use Illuminate\Database\Capsule\Manager as DB;

return new class {
    public function up(): void
    {
        // Fix heritage_valuation_history
        if (DB::schema()->hasTable('heritage_valuation_history')) {
            if (DB::schema()->hasColumn('heritage_valuation_history', 'asset_id') 
                && !DB::schema()->hasColumn('heritage_valuation_history', 'heritage_asset_id')) {
                DB::statement("ALTER TABLE `heritage_valuation_history` CHANGE `asset_id` `heritage_asset_id` INT UNSIGNED NOT NULL");
            }
            // Add missing columns
            if (!DB::schema()->hasColumn('heritage_valuation_history', 'valuation_change')) {
                DB::statement("ALTER TABLE `heritage_valuation_history` ADD COLUMN `valuation_change` DECIMAL(18,2) NULL AFTER `new_value`");
            }
            if (!DB::schema()->hasColumn('heritage_valuation_history', 'valuation_report_reference')) {
                DB::statement("ALTER TABLE `heritage_valuation_history` ADD COLUMN `valuation_report_reference` VARCHAR(255) NULL AFTER `valuer_organization`");
            }
            if (!DB::schema()->hasColumn('heritage_valuation_history', 'revaluation_surplus_change')) {
                DB::statement("ALTER TABLE `heritage_valuation_history` ADD COLUMN `revaluation_surplus_change` DECIMAL(18,2) NULL AFTER `valuation_report_reference`");
            }
        }

        // Fix heritage_impairment_assessment
        if (DB::schema()->hasTable('heritage_impairment_assessment')) {
            if (DB::schema()->hasColumn('heritage_impairment_assessment', 'asset_id') 
                && !DB::schema()->hasColumn('heritage_impairment_assessment', 'heritage_asset_id')) {
                DB::statement("ALTER TABLE `heritage_impairment_assessment` CHANGE `asset_id` `heritage_asset_id` INT UNSIGNED NOT NULL");
            }
        }

        // Fix heritage_movement_register
        if (DB::schema()->hasTable('heritage_movement_register')) {
            if (DB::schema()->hasColumn('heritage_movement_register', 'asset_id') 
                && !DB::schema()->hasColumn('heritage_movement_register', 'heritage_asset_id')) {
                DB::statement("ALTER TABLE `heritage_movement_register` CHANGE `asset_id` `heritage_asset_id` INT UNSIGNED NOT NULL");
            }
        }

        // Fix heritage_journal_entry
        if (DB::schema()->hasTable('heritage_journal_entry')) {
            if (DB::schema()->hasColumn('heritage_journal_entry', 'asset_id') 
                && !DB::schema()->hasColumn('heritage_journal_entry', 'heritage_asset_id')) {
                DB::statement("ALTER TABLE `heritage_journal_entry` CHANGE `asset_id` `heritage_asset_id` INT UNSIGNED NOT NULL");
            }
        }
    }

    public function down(): void
    {
        // Not reversible - column names should stay as heritage_asset_id
    }
};
