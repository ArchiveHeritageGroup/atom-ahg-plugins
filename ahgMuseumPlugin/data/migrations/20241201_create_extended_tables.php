<?php

declare(strict_types=1);

/**
 * Museum Metadata Extended Tables Migration.
 *
 * Creates tables for:
 * - condition_report: Condition assessments
 * - condition_damage: Damage observations
 * - condition_image: Condition photos
 * - provenance_entry: Ownership history
 *
 * @author Johan Pieterse <johan@theahg.co.za>
 */

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;

class MuseumMetadataExtendedMigration
{
    public function up(): void
    {
        $this->createConditionReportTable();
        $this->createConditionDamageTable();
        $this->createConditionImageTable();
        $this->createProvenanceEntryTable();

        echo "Museum metadata extended tables created successfully.\n";
    }

    public function down(): void
    {
        Capsule::schema()->dropIfExists('condition_image');
        Capsule::schema()->dropIfExists('condition_damage');
        Capsule::schema()->dropIfExists('condition_report');
        Capsule::schema()->dropIfExists('provenance_entry');

        echo "Museum metadata extended tables dropped.\n";
    }

    private function createConditionReportTable(): void
    {
        if (Capsule::schema()->hasTable('condition_report')) {
            echo "Table condition_report already exists.\n";

            return;
        }

        Capsule::schema()->create('condition_report', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('information_object_id');
            $table->unsignedInteger('assessor_user_id')->nullable();

            // Assessment details
            $table->date('assessment_date');
            $table->enum('context', [
                'acquisition', 'loan_out', 'loan_in', 'loan_return',
                'exhibition', 'storage', 'conservation', 'routine',
                'incident', 'insurance', 'deaccession',
            ])->default('routine');

            // Overall condition
            $table->enum('overall_rating', [
                'excellent', 'good', 'fair', 'poor', 'unacceptable',
            ])->default('good');
            $table->text('summary')->nullable();

            // Recommendations
            $table->text('recommendations')->nullable();
            $table->enum('priority', ['low', 'normal', 'high', 'urgent'])->default('normal');
            $table->date('next_check_date')->nullable();

            // Notes by category
            $table->text('environmental_notes')->nullable();
            $table->text('handling_notes')->nullable();
            $table->text('display_notes')->nullable();
            $table->text('storage_notes')->nullable();

            $table->timestamps();

            // Indexes
            $table->index('information_object_id', 'idx_cr_object');
            $table->index('assessment_date', 'idx_cr_date');
            $table->index('overall_rating', 'idx_cr_rating');
            $table->index('next_check_date', 'idx_cr_next_check');
        });

        echo "Created table: condition_report\n";
    }

    private function createConditionDamageTable(): void
    {
        if (Capsule::schema()->hasTable('condition_damage')) {
            echo "Table condition_damage already exists.\n";

            return;
        }

        Capsule::schema()->create('condition_damage', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('condition_report_id');

            // Damage details
            $table->string('damage_type', 50);
            $table->string('location', 50)->default('overall');
            $table->enum('severity', ['minor', 'moderate', 'severe'])->default('minor');
            $table->text('description')->nullable();
            $table->string('dimensions', 100)->nullable(); // e.g., "2cm x 3cm"

            // Status
            $table->boolean('is_active')->default(true);
            $table->boolean('treatment_required')->default(false);
            $table->text('treatment_notes')->nullable();

            $table->timestamp('created_at')->useCurrent();

            // Indexes
            $table->index('condition_report_id', 'idx_cd_report');
            $table->index('damage_type', 'idx_cd_type');
            $table->index('severity', 'idx_cd_severity');

            $table->foreign('condition_report_id')
                ->references('id')
                ->on('condition_report')
                ->onDelete('cascade');
        });

        echo "Created table: condition_damage\n";
    }

    private function createConditionImageTable(): void
    {
        if (Capsule::schema()->hasTable('condition_image')) {
            echo "Table condition_image already exists.\n";

            return;
        }

        Capsule::schema()->create('condition_image', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('condition_report_id');

            // Image reference
            $table->unsignedInteger('digital_object_id')->nullable();
            $table->string('file_path', 500)->nullable();

            // Metadata
            $table->string('caption', 500)->nullable();
            $table->enum('image_type', [
                'general', 'detail', 'damage', 'before', 'after', 'raking', 'uv',
            ])->default('general');

            // Annotations (JSON for D3/SVG overlay data)
            $table->json('annotations')->nullable();

            $table->timestamp('created_at')->useCurrent();

            // Indexes
            $table->index('condition_report_id', 'idx_ci_report');

            $table->foreign('condition_report_id')
                ->references('id')
                ->on('condition_report')
                ->onDelete('cascade');
        });

        echo "Created table: condition_image\n";
    }

    private function createProvenanceEntryTable(): void
    {
        if (Capsule::schema()->hasTable('provenance_entry')) {
            echo "Table provenance_entry already exists.\n";

            return;
        }

        Capsule::schema()->create('provenance_entry', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('information_object_id');
            $table->unsignedSmallInteger('sequence')->default(1);

            // Owner information
            $table->string('owner_name', 500);
            $table->enum('owner_type', [
                'person', 'family', 'dealer', 'auction_house', 'museum',
                'corporate', 'government', 'religious', 'artist', 'unknown',
            ])->default('unknown');
            $table->unsignedInteger('owner_actor_id')->nullable(); // Link to AtoM actor
            $table->string('owner_location', 255)->nullable();
            $table->string('owner_location_tgn', 100)->nullable(); // Getty TGN URI

            // Date range
            $table->string('start_date', 50)->nullable(); // Flexible format
            $table->enum('start_date_qualifier', ['circa', 'before', 'after', 'by'])->nullable();
            $table->string('end_date', 50)->nullable();
            $table->enum('end_date_qualifier', ['circa', 'before', 'after', 'by'])->nullable();

            // Transfer details
            $table->enum('transfer_type', [
                'sale', 'auction', 'gift', 'bequest', 'inheritance', 'commission',
                'exchange', 'seizure', 'restitution', 'transfer', 'loan',
                'found', 'created', 'unknown',
            ])->default('unknown');
            $table->text('transfer_details')->nullable();

            // Sale information
            $table->decimal('sale_price', 15, 2)->nullable();
            $table->string('sale_currency', 10)->nullable();
            $table->string('auction_house', 255)->nullable();
            $table->string('auction_lot', 50)->nullable();

            // Certainty and documentation
            $table->enum('certainty', [
                'certain', 'probable', 'possible', 'uncertain', 'unknown',
            ])->default('unknown');
            $table->text('sources')->nullable(); // Bibliography/documentation
            $table->text('notes')->nullable();

            // Gap tracking
            $table->boolean('is_gap')->default(false);
            $table->text('gap_explanation')->nullable();

            $table->timestamps();

            // Indexes
            $table->index('information_object_id', 'idx_pe_object');
            $table->index(['information_object_id', 'sequence'], 'idx_pe_object_seq');
            $table->index('owner_name', 'idx_pe_owner');
            $table->index('transfer_type', 'idx_pe_transfer');
            $table->index('certainty', 'idx_pe_certainty');
        });

        echo "Created table: provenance_entry\n";
    }
}

// CLI execution
if ('cli' === php_sapi_name() && isset($argv[0]) && basename($argv[0]) === basename(__FILE__)) {
    require_once dirname(__DIR__, 4).'/atom-framework/vendor/autoload.php';

    $capsule = new Capsule();
    $capsule->addConnection([
        'driver' => 'mysql',
        'host' => getenv('ATOM_DB_HOST') ?: 'localhost',
        'database' => getenv('ATOM_DB_NAME') ?: 'archive',
        'username' => getenv('ATOM_DB_USER') ?: 'root',
        'password' => getenv('ATOM_DB_PASS') ?: 'Merlot@123',
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
        'prefix' => '',
    ]);
    $capsule->setAsGlobal();
    $capsule->bootEloquent();

    $migration = new MuseumMetadataExtendedMigration();

    $action = $argv[1] ?? 'up';

    if ('down' === $action) {
        $migration->down();
    } else {
        $migration->up();
    }
}
