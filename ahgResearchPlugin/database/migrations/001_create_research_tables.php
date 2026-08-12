<?php

/**
 * Superseded by database/install.sql. Deliberately does nothing.
 *
 * WHAT THIS USED TO DO, AND WHY IT WAS HARMFUL
 *
 * It created ten tables - research_researcher, research_reading_room,
 * research_booking, research_material_request, research_collection,
 * research_collection_item, research_annotation, research_saved_search,
 * research_citation_log, research_password_reset - in a much narrower shape than
 * install.sql declares for the same names. 119 columns narrower in total;
 * research_researcher alone was 18 columns against install.sql's 47.
 *
 * Both used CREATE TABLE IF NOT EXISTS, so whichever ran first won and the other
 * silently did nothing. That made the outcome depend on the order the installer
 * happened to take:
 *
 *   install.sql first   correct tables, and this migration is a no-op anyway
 *   migrations first    stubs - and install.sql then ABORTS partway through, at
 *                       the first statement referencing a column the stub does
 *                       not have ("Unknown column 'orcid_id' in
 *                       'research_researcher'"), leaving ~77 later tables
 *                       uncreated
 *
 * Measured on a clean AtoM 2.10 on 2026-08-12: the migration runner had swept the
 * plugin before install.sql was ever loaded, so the install produced 43 of 143
 * tables and stopped. The plugin looked installed and was not.
 *
 * install.sql is the single source of truth for these tables. Emptying this
 * migration is what makes that true rather than aspirational - two files
 * declaring the same table is the defect, and the narrower one has to go.
 *
 * Instances that already ran the old version are repaired by
 * 2026_08_12_align_bootstrap_tables_with_install.sql, which adds the 119 missing
 * columns from install.sql's own definitions.
 *
 * The name is kept, and it still runs, so the migration ledger is unbroken:
 * removing the file would make an instance that recorded it look as though it
 * had run a migration that no longer exists.
 */

use Illuminate\Database\Capsule\Manager as DB;

class CreateResearchTables
{
    public function up(): void
    {
        // Intentionally empty - install.sql creates these ten tables.
    }

    public function down(): void
    {
        // Intentionally empty.
        //
        // This used to DROP all ten tables, which on any instance with
        // researchers, bookings and requests in them would have destroyed the
        // lot. A down() that deletes live data to reverse a table creation the
        // installer no longer performs is not a rollback, it is an outage.
    }
}
