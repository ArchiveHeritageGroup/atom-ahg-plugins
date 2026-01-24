<?php
use Illuminate\Database\Capsule\Manager as DB;

return new class {
    public function up(): void
    {
        // Fix heritage_journal_entry
        if (DB::schema()->hasTable('heritage_journal_entry')) {
            // Rename entry_date to journal_date
            if (DB::schema()->hasColumn('heritage_journal_entry', 'entry_date') 
                && !DB::schema()->hasColumn('heritage_journal_entry', 'journal_date')) {
                DB::statement("ALTER TABLE `heritage_journal_entry` CHANGE `entry_date` `journal_date` DATE NOT NULL");
            }
            // Rename entry_type to journal_type
            if (DB::schema()->hasColumn('heritage_journal_entry', 'entry_type') 
                && !DB::schema()->hasColumn('heritage_journal_entry', 'journal_type')) {
                DB::statement("ALTER TABLE `heritage_journal_entry` CHANGE `entry_type` `journal_type` ENUM('recognition','revaluation','depreciation','impairment','impairment_reversal','derecognition','adjustment','transfer') NOT NULL");
            }
            // Add missing columns
            if (!DB::schema()->hasColumn('heritage_journal_entry', 'journal_number')) {
                DB::statement("ALTER TABLE `heritage_journal_entry` ADD COLUMN `journal_number` VARCHAR(50) NULL AFTER `journal_date`");
            }
            if (!DB::schema()->hasColumn('heritage_journal_entry', 'debit_amount')) {
                DB::statement("ALTER TABLE `heritage_journal_entry` ADD COLUMN `debit_amount` DECIMAL(18,2) NOT NULL DEFAULT 0 AFTER `debit_account`");
            }
            if (!DB::schema()->hasColumn('heritage_journal_entry', 'credit_amount')) {
                DB::statement("ALTER TABLE `heritage_journal_entry` ADD COLUMN `credit_amount` DECIMAL(18,2) NOT NULL DEFAULT 0 AFTER `credit_account`");
            }
            if (!DB::schema()->hasColumn('heritage_journal_entry', 'reference_document')) {
                DB::statement("ALTER TABLE `heritage_journal_entry` ADD COLUMN `reference_document` VARCHAR(255) NULL AFTER `description`");
            }
            if (!DB::schema()->hasColumn('heritage_journal_entry', 'fiscal_year')) {
                DB::statement("ALTER TABLE `heritage_journal_entry` ADD COLUMN `fiscal_year` INT NULL AFTER `reference_document`");
            }
            if (!DB::schema()->hasColumn('heritage_journal_entry', 'fiscal_period')) {
                DB::statement("ALTER TABLE `heritage_journal_entry` ADD COLUMN `fiscal_period` INT NULL AFTER `fiscal_year`");
            }
            if (!DB::schema()->hasColumn('heritage_journal_entry', 'posted')) {
                DB::statement("ALTER TABLE `heritage_journal_entry` ADD COLUMN `posted` TINYINT(1) DEFAULT 0 AFTER `fiscal_period`");
            }
            if (!DB::schema()->hasColumn('heritage_journal_entry', 'posted_by')) {
                DB::statement("ALTER TABLE `heritage_journal_entry` ADD COLUMN `posted_by` INT NULL AFTER `posted`");
            }
            if (!DB::schema()->hasColumn('heritage_journal_entry', 'posted_at')) {
                DB::statement("ALTER TABLE `heritage_journal_entry` ADD COLUMN `posted_at` DATETIME NULL AFTER `posted_by`");
            }
            if (!DB::schema()->hasColumn('heritage_journal_entry', 'reversed')) {
                DB::statement("ALTER TABLE `heritage_journal_entry` ADD COLUMN `reversed` TINYINT(1) DEFAULT 0 AFTER `posted_at`");
            }
            if (!DB::schema()->hasColumn('heritage_journal_entry', 'reversal_journal_id')) {
                DB::statement("ALTER TABLE `heritage_journal_entry` ADD COLUMN `reversal_journal_id` INT UNSIGNED NULL AFTER `reversed`");
            }
            if (!DB::schema()->hasColumn('heritage_journal_entry', 'reversal_date')) {
                DB::statement("ALTER TABLE `heritage_journal_entry` ADD COLUMN `reversal_date` DATE NULL AFTER `reversal_journal_id`");
            }
            if (!DB::schema()->hasColumn('heritage_journal_entry', 'reversal_reason')) {
                DB::statement("ALTER TABLE `heritage_journal_entry` ADD COLUMN `reversal_reason` TEXT NULL AFTER `reversal_date`");
            }
        }

        // Fix heritage_movement_register - add missing columns
        if (DB::schema()->hasTable('heritage_movement_register')) {
            if (!DB::schema()->hasColumn('heritage_movement_register', 'authorization_date')) {
                DB::statement("ALTER TABLE `heritage_movement_register` ADD COLUMN `authorization_date` DATE NULL AFTER `authorized_by`");
            }
            if (!DB::schema()->hasColumn('heritage_movement_register', 'expected_return_date')) {
                DB::statement("ALTER TABLE `heritage_movement_register` ADD COLUMN `expected_return_date` DATE NULL AFTER `authorization_date`");
            }
            if (!DB::schema()->hasColumn('heritage_movement_register', 'actual_return_date')) {
                DB::statement("ALTER TABLE `heritage_movement_register` ADD COLUMN `actual_return_date` DATE NULL AFTER `expected_return_date`");
            }
            if (!DB::schema()->hasColumn('heritage_movement_register', 'condition_on_departure')) {
                DB::statement("ALTER TABLE `heritage_movement_register` ADD COLUMN `condition_on_departure` ENUM('excellent','good','fair','poor') NULL AFTER `actual_return_date`");
            }
            if (!DB::schema()->hasColumn('heritage_movement_register', 'condition_on_return')) {
                DB::statement("ALTER TABLE `heritage_movement_register` ADD COLUMN `condition_on_return` ENUM('excellent','good','fair','poor') NULL AFTER `condition_on_departure`");
            }
            if (!DB::schema()->hasColumn('heritage_movement_register', 'condition_notes')) {
                DB::statement("ALTER TABLE `heritage_movement_register` ADD COLUMN `condition_notes` TEXT NULL AFTER `condition_on_return`");
            }
            if (!DB::schema()->hasColumn('heritage_movement_register', 'insurance_confirmed')) {
                DB::statement("ALTER TABLE `heritage_movement_register` ADD COLUMN `insurance_confirmed` TINYINT(1) DEFAULT 0 AFTER `condition_notes`");
            }
        }
    }

    public function down(): void
    {
        // Not reversible
    }
};
