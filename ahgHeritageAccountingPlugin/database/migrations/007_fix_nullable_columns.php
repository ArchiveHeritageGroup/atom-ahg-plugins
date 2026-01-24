<?php
use Illuminate\Database\Capsule\Manager as DB;

return new class {
    public function up(): void
    {
        // Fix heritage_impairment_assessment - make columns nullable
        if (DB::schema()->hasTable('heritage_impairment_assessment')) {
            DB::statement("ALTER TABLE `heritage_impairment_assessment` MODIFY `impairment_loss` DECIMAL(18,2) NULL");
            DB::statement("ALTER TABLE `heritage_impairment_assessment` MODIFY `carrying_amount_before` DECIMAL(18,2) NULL");
            DB::statement("ALTER TABLE `heritage_impairment_assessment` MODIFY `carrying_amount_after` DECIMAL(18,2) NULL");
            if (DB::schema()->hasColumn('heritage_impairment_assessment', 'reason')) {
                DB::statement("ALTER TABLE `heritage_impairment_assessment` MODIFY `reason` TEXT NULL");
            }
        }

        // Fix heritage_journal_entry - make columns nullable
        if (DB::schema()->hasTable('heritage_journal_entry')) {
            if (DB::schema()->hasColumn('heritage_journal_entry', 'amount')) {
                DB::statement("ALTER TABLE `heritage_journal_entry` MODIFY `amount` DECIMAL(18,2) NULL");
            }
        }
    }

    public function down(): void
    {
        // Not reversible
    }
};
