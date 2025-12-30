<?php

declare(strict_types=1);

/**
 * Getty Vocabulary Links Migration.
 *
 * Creates the getty_vocabulary_link table for storing mappings between
 * AtoM taxonomy terms and Getty Vocabulary URIs (AAT, TGN, ULAN).
 *
 * @author Johan Pieterse <johan@theahg.co.za>
 */

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;

class GettyVocabularyLinkMigration
{
    private const TABLE = 'getty_vocabulary_link';

    public function up(): void
    {
        if (Capsule::schema()->hasTable(self::TABLE)) {
            echo "Table ".self::TABLE." already exists, skipping.\n";

            return;
        }

        Capsule::schema()->create(self::TABLE, function (Blueprint $table) {
            $table->id();

            // Reference to AtoM term
            $table->unsignedInteger('term_id');

            // Getty vocabulary type
            $table->enum('vocabulary', ['aat', 'tgn', 'ulan'])
                ->comment('Getty vocabulary: aat=Art & Architecture Thesaurus, tgn=Thesaurus of Geographic Names, ulan=Union List of Artist Names');

            // Getty identifiers
            $table->string('getty_uri', 255)
                ->comment('Full Getty URI (e.g., http://vocab.getty.edu/aat/300015050)');
            $table->string('getty_id', 50)
                ->comment('Getty numeric ID (e.g., 300015050)');

            // Cached Getty data (for display without API calls)
            $table->string('getty_pref_label', 500)->nullable()
                ->comment('Preferred label from Getty');
            $table->text('getty_scope_note')->nullable()
                ->comment('Scope note/definition from Getty');

            // Linking status
            $table->enum('status', ['confirmed', 'suggested', 'rejected', 'pending'])
                ->default('pending')
                ->comment('Link status: confirmed=verified by user, suggested=auto-matched, rejected=user rejected, pending=not reviewed');

            // Match confidence (0.0-1.0)
            $table->decimal('confidence', 3, 2)->default(0.00)
                ->comment('Auto-matching confidence score (0.00-1.00)');

            // Audit fields
            $table->unsignedInteger('confirmed_by_user_id')->nullable()
                ->comment('User who confirmed/rejected the link');
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamps();

            // Indexes
            $table->unique(['term_id', 'getty_uri'], 'uniq_term_getty');
            $table->index('vocabulary', 'idx_vocabulary');
            $table->index('status', 'idx_status');
            $table->index('getty_id', 'idx_getty_id');
            $table->index(['vocabulary', 'status'], 'idx_vocab_status');

            // Foreign keys (term_id references atom.term.id)
            // Note: AtoM uses MyISAM for some tables, so FK might not work
            // $table->foreign('term_id')->references('id')->on('term')->onDelete('cascade');
        });

        echo "Created table: ".self::TABLE."\n";
    }

    public function down(): void
    {
        Capsule::schema()->dropIfExists(self::TABLE);
        echo "Dropped table: ".self::TABLE."\n";
    }
}

// CLI execution
if (php_sapi_name() === 'cli' && isset($argv[0]) && basename($argv[0]) === basename(__FILE__)) {
    require_once dirname(__DIR__, 4).'/atom-framework/vendor/autoload.php';

    // Database configuration (adjust as needed)
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

    $migration = new GettyVocabularyLinkMigration();

    $action = $argv[1] ?? 'up';

    if ('down' === $action) {
        $migration->down();
    } else {
        $migration->up();
    }
}
