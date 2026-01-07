<?php
use Illuminate\Database\Capsule\Manager as DB;

return new class {
    public function up(): void
    {
        // Fix heritage_impairment_assessment - rename impairment_date to assessment_date
        if (DB::schema()->hasTable('heritage_impairment_assessment')) {
            if (DB::schema()->hasColumn('heritage_impairment_assessment', 'impairment_date') 
                && !DB::schema()->hasColumn('heritage_impairment_assessment', 'assessment_date')) {
                DB::statement("ALTER TABLE `heritage_impairment_assessment` CHANGE `impairment_date` `assessment_date` DATE NOT NULL");
            }
            
            // Add missing columns from 112 schema
            if (!DB::schema()->hasColumn('heritage_impairment_assessment', 'physical_damage')) {
                DB::statement("ALTER TABLE `heritage_impairment_assessment` ADD COLUMN `physical_damage` TINYINT(1) DEFAULT 0 AFTER `assessment_date`");
            }
            if (!DB::schema()->hasColumn('heritage_impairment_assessment', 'physical_damage_details')) {
                DB::statement("ALTER TABLE `heritage_impairment_assessment` ADD COLUMN `physical_damage_details` TEXT NULL AFTER `physical_damage`");
            }
            if (!DB::schema()->hasColumn('heritage_impairment_assessment', 'obsolescence')) {
                DB::statement("ALTER TABLE `heritage_impairment_assessment` ADD COLUMN `obsolescence` TINYINT(1) DEFAULT 0 AFTER `physical_damage_details`");
            }
            if (!DB::schema()->hasColumn('heritage_impairment_assessment', 'obsolescence_details')) {
                DB::statement("ALTER TABLE `heritage_impairment_assessment` ADD COLUMN `obsolescence_details` TEXT NULL AFTER `obsolescence`");
            }
            if (!DB::schema()->hasColumn('heritage_impairment_assessment', 'change_in_use')) {
                DB::statement("ALTER TABLE `heritage_impairment_assessment` ADD COLUMN `change_in_use` TINYINT(1) DEFAULT 0 AFTER `obsolescence_details`");
            }
            if (!DB::schema()->hasColumn('heritage_impairment_assessment', 'change_in_use_details')) {
                DB::statement("ALTER TABLE `heritage_impairment_assessment` ADD COLUMN `change_in_use_details` TEXT NULL AFTER `change_in_use`");
            }
            if (!DB::schema()->hasColumn('heritage_impairment_assessment', 'external_factors')) {
                DB::statement("ALTER TABLE `heritage_impairment_assessment` ADD COLUMN `external_factors` TINYINT(1) DEFAULT 0 AFTER `change_in_use_details`");
            }
            if (!DB::schema()->hasColumn('heritage_impairment_assessment', 'external_factors_details')) {
                DB::statement("ALTER TABLE `heritage_impairment_assessment` ADD COLUMN `external_factors_details` TEXT NULL AFTER `external_factors`");
            }
            if (!DB::schema()->hasColumn('heritage_impairment_assessment', 'impairment_identified')) {
                DB::statement("ALTER TABLE `heritage_impairment_assessment` ADD COLUMN `impairment_identified` TINYINT(1) DEFAULT 0 AFTER `external_factors_details`");
            }
            if (!DB::schema()->hasColumn('heritage_impairment_assessment', 'recoverable_amount')) {
                DB::statement("ALTER TABLE `heritage_impairment_assessment` ADD COLUMN `recoverable_amount` DECIMAL(18,2) NULL AFTER `carrying_amount_before`");
            }
            if (!DB::schema()->hasColumn('heritage_impairment_assessment', 'reversal_applicable')) {
                DB::statement("ALTER TABLE `heritage_impairment_assessment` ADD COLUMN `reversal_applicable` TINYINT(1) DEFAULT 0 AFTER `carrying_amount_after`");
            }
            if (!DB::schema()->hasColumn('heritage_impairment_assessment', 'assessor_name')) {
                DB::statement("ALTER TABLE `heritage_impairment_assessment` ADD COLUMN `assessor_name` VARCHAR(255) NULL AFTER `reversal_amount`");
            }
            if (!DB::schema()->hasColumn('heritage_impairment_assessment', 'notes')) {
                DB::statement("ALTER TABLE `heritage_impairment_assessment` ADD COLUMN `notes` TEXT NULL AFTER `assessor_name`");
            }
        }
    }

    public function down(): void
    {
        // Not reversible
    }
};
