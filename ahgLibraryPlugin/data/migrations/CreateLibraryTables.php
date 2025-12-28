<?php

declare(strict_types=1);

/**
 * Library Plugin Database Migration
 *
 * Creates tables for MARC-inspired library metadata using Laravel Schema Builder
 *
 * @package    ahgLibraryPlugin
 * @subpackage migrations
 */

use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Schema\Blueprint;

class CreateLibraryTables
{
    /**
     * Run the migrations
     */
    public function up(): void
    {
        $schema = DB::schema();

        // Main library metadata table
        if (!$schema->hasTable('library_item')) {
            $schema->create('library_item', function (Blueprint $table) {
                $table->id();
                $table->unsignedInteger('information_object_id')->unique();

                // Material Type
                $table->string('material_type', 50)->default('monograph')
                    ->comment('monograph, serial, volume, issue, chapter, article, manuscript, map, pamphlet');

                // Classification & Call Number
                $table->string('call_number', 100)->nullable()->index();
                $table->string('classification_scheme', 50)->nullable()
                    ->comment('dewey, lcc, udc, bliss, colon, custom');
                $table->string('classification_number', 100)->nullable();
                $table->string('cutter_number', 50)->nullable();
                $table->string('shelf_location', 100)->nullable();
                $table->string('copy_number', 20)->nullable();
                $table->string('volume_designation', 100)->nullable();

                // Standard Identifiers
                $table->string('isbn', 17)->nullable()->index();
                $table->string('issn', 9)->nullable()->index();
                $table->string('lccn', 50)->nullable();
                $table->string('oclc_number', 50)->nullable();
                $table->string('doi', 255)->nullable();
                $table->string('barcode', 50)->nullable()->index();

                // Bibliographic Fields
                $table->string('edition', 255)->nullable();
                $table->string('edition_statement', 500)->nullable();
                $table->string('publisher', 255)->nullable()->index();
                $table->string('publication_place', 255)->nullable();
                $table->string('publication_date', 100)->nullable();
                $table->string('copyright_date', 50)->nullable();
                $table->string('printing', 100)->nullable();

                // Physical Description
                $table->string('pagination', 100)->nullable();
                $table->string('dimensions', 100)->nullable();
                $table->text('physical_details')->nullable();
                $table->text('accompanying_material')->nullable();

                // Series Information
                $table->string('series_title', 500)->nullable();
                $table->string('series_number', 50)->nullable();
                $table->string('series_issn', 9)->nullable();
                $table->string('subseries_title', 500)->nullable();

                // Notes
                $table->text('general_note')->nullable();
                $table->text('bibliography_note')->nullable();
                $table->text('contents_note')->nullable();
                $table->text('summary')->nullable();
                $table->text('target_audience')->nullable();
                $table->text('system_requirements')->nullable();
                $table->text('binding_note')->nullable();

                // Serial-specific
                $table->string('frequency', 50)->nullable();
                $table->string('former_frequency', 100)->nullable();
                $table->string('numbering_peculiarities', 255)->nullable();
                $table->date('publication_start_date')->nullable();
                $table->date('publication_end_date')->nullable();
                $table->string('publication_status', 20)->nullable()
                    ->comment('current, ceased, suspended');

                // Holdings summary
                $table->unsignedSmallInteger('total_copies')->default(1);
                $table->unsignedSmallInteger('available_copies')->default(1);
                $table->string('circulation_status', 30)->default('available')
                    ->comment('available, on_loan, processing, lost, withdrawn, reference');

                // Cataloging
                $table->string('cataloging_source', 100)->nullable();
                $table->string('cataloging_rules', 20)->nullable()
                    ->comment('aacr2, rda, isbd');
                $table->string('encoding_level', 20)->nullable();

                $table->timestamps();

                $table->foreign('information_object_id')
                    ->references('id')
                    ->on('information_object')
                    ->onDelete('cascade');
            });
        }

        // Authors/Contributors table
        if (!$schema->hasTable('library_creator')) {
            $schema->create('library_creator', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('library_item_id');
                $table->unsignedInteger('actor_id')->nullable();

                $table->string('name', 500);
                $table->string('role', 50)->default('author')
                    ->comment('author, editor, translator, illustrator, compiler, contributor');
                $table->string('relator_code', 10)->nullable()
                    ->comment('MARC relator code: aut, edt, trl, ill');
                $table->unsignedTinyInteger('sequence')->default(0);
                $table->boolean('is_primary')->default(false);
                $table->string('dates', 100)->nullable();
                $table->string('fuller_form', 255)->nullable();
                $table->string('affiliation', 500)->nullable();

                $table->timestamps();

                $table->index(['library_item_id', 'sequence']);
                $table->index('name');

                $table->foreign('library_item_id')
                    ->references('id')
                    ->on('library_item')
                    ->onDelete('cascade');
            });
        }

        // Subject headings table
        if (!$schema->hasTable('library_subject')) {
            $schema->create('library_subject', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('library_item_id');
                $table->unsignedInteger('term_id')->nullable();

                $table->string('heading', 500);
                $table->string('heading_type', 30)->default('topical')
                    ->comment('topical, personal, corporate, geographic, genre, meeting');
                $table->string('source', 50)->nullable()
                    ->comment('lcsh, mesh, aat, fast, local');
                $table->string('source_code', 10)->nullable();
                $table->json('subdivisions')->nullable();

                $table->timestamps();

                $table->index('heading');
                $table->index('heading_type');

                $table->foreign('library_item_id')
                    ->references('id')
                    ->on('library_item')
                    ->onDelete('cascade');
            });
        }

        // Physical copies/holdings table
        if (!$schema->hasTable('library_copy')) {
            $schema->create('library_copy', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('library_item_id');

                $table->string('barcode', 50)->nullable()->unique();
                $table->string('copy_number', 20)->nullable();
                $table->string('location_code', 50)->nullable();
                $table->string('shelf_location', 100)->nullable();
                $table->string('status', 30)->default('available')
                    ->comment('available, on_loan, processing, lost, withdrawn, reference, reserved');
                $table->string('condition', 30)->nullable()
                    ->comment('excellent, good, fair, poor, damaged');
                $table->text('condition_note')->nullable();

                $table->date('acquisition_date')->nullable();
                $table->string('acquisition_source', 255)->nullable();
                $table->string('acquisition_method', 50)->nullable()
                    ->comment('purchase, donation, exchange, deposit');
                $table->decimal('acquisition_cost', 10, 2)->nullable();
                $table->string('fund_code', 50)->nullable();

                $table->date('last_inventory_date')->nullable();
                $table->date('last_circulation_date')->nullable();
                $table->unsignedInteger('circulation_count')->default(0);

                $table->timestamps();

                $table->index(['library_item_id', 'status']);

                $table->foreign('library_item_id')
                    ->references('id')
                    ->on('library_item')
                    ->onDelete('cascade');
            });
        }

        // Serial holdings (enumeration/chronology)
        if (!$schema->hasTable('library_serial_holding')) {
            $schema->create('library_serial_holding', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('library_item_id');

                $table->string('enumeration', 100)->nullable();
                $table->string('chronology', 100)->nullable();
                $table->string('volume', 50)->nullable();
                $table->string('issue', 50)->nullable();
                $table->string('part', 50)->nullable();
                $table->string('supplement', 100)->nullable();

                $table->date('coverage_start')->nullable();
                $table->date('coverage_end')->nullable();
                $table->boolean('is_complete')->default(true);
                $table->text('gaps_note')->nullable();

                $table->timestamps();

                $table->index(['library_item_id', 'volume', 'issue']);

                $table->foreign('library_item_id')
                    ->references('id')
                    ->on('library_item')
                    ->onDelete('cascade');
            });
        }

        // Circulation transactions
        if (!$schema->hasTable('library_circulation')) {
            $schema->create('library_circulation', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('copy_id');
                $table->unsignedInteger('user_id');
                $table->unsignedInteger('staff_id')->nullable();

                $table->string('transaction_type', 30)
                    ->comment('checkout, checkin, renewal, reserve, recall, lost');
                $table->dateTime('transaction_date');
                $table->dateTime('due_date')->nullable();
                $table->dateTime('return_date')->nullable();
                $table->unsignedTinyInteger('renewal_count')->default(0);
                $table->decimal('fine_amount', 8, 2)->nullable();
                $table->boolean('fine_paid')->default(false);
                $table->text('notes')->nullable();

                $table->timestamps();

                $table->index(['copy_id', 'transaction_date']);
                $table->index(['user_id', 'transaction_type']);

                $table->foreign('copy_id')
                    ->references('id')
                    ->on('library_copy')
                    ->onDelete('cascade');
            });
        }
    }

    /**
     * Reverse the migrations
     */
    public function down(): void
    {
        $schema = DB::schema();

        $schema->dropIfExists('library_circulation');
        $schema->dropIfExists('library_serial_holding');
        $schema->dropIfExists('library_copy');
        $schema->dropIfExists('library_subject');
        $schema->dropIfExists('library_creator');
        $schema->dropIfExists('library_item');
    }

    /**
     * Run migration
     */
    public static function migrate(): void
    {
        $migration = new self();
        $migration->up();
    }

    /**
     * Rollback migration
     */
    public static function rollback(): void
    {
        $migration = new self();
        $migration->down();
    }
}
