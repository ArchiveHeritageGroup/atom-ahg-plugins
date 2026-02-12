<?php

namespace AtomFramework\Console\Commands\Display;

use AtomFramework\Console\BaseCommand;
use Illuminate\Database\Capsule\Manager as DB;

/**
 * Add FULLTEXT indexes for fuzzy/typo-tolerant search.
 *
 * Idempotent: checks if each index already exists before creating.
 */
class AddFulltextIndexesCommand extends BaseCommand
{
    protected string $name = 'ahg:add-fulltext-indexes';
    protected string $description = 'Add fulltext indexes for search optimization';
    protected string $detailedDescription = <<<'EOF'
    Adds MySQL FULLTEXT indexes to i18n tables for natural language
    search with stemming.

    Indexes added:
      - ft_ioi_title on information_object_i18n(title)
      - ft_ioi_scope on information_object_i18n(scope_and_content)
      - ft_ai_name on actor_i18n(authorized_form_of_name)
      - ft_ti_name on term_i18n(name)
    EOF;

    protected function handle(): int
    {
        $pdo = DB::connection()->getPdo();

        $indexes = [
            ['information_object_i18n', 'ft_ioi_title', 'title'],
            ['information_object_i18n', 'ft_ioi_scope', 'scope_and_content'],
            ['actor_i18n', 'ft_ai_name', 'authorized_form_of_name'],
            ['term_i18n', 'ft_ti_name', 'name'],
        ];

        foreach ($indexes as [$table, $indexName, $column]) {
            if ($this->indexExists($pdo, $table, $indexName)) {
                $this->comment("  skip: {$indexName} already exists on {$table}");

                continue;
            }

            $this->info("  Creating FULLTEXT index {$indexName} on {$table}({$column})...");

            try {
                $pdo->exec("CREATE FULLTEXT INDEX `{$indexName}` ON `{$table}`(`{$column}`)");
                $this->success("{$indexName} created successfully");
            } catch (\Exception $e) {
                $this->error("Failed to create {$indexName}: " . $e->getMessage());
            }
        }

        $this->success('FULLTEXT index setup complete');

        return 0;
    }

    private function indexExists(\PDO $pdo, string $table, string $indexName): bool
    {
        $stmt = $pdo->prepare("SHOW INDEX FROM `{$table}` WHERE Key_name = ?");
        $stmt->execute([$indexName]);

        return $stmt->rowCount() > 0;
    }
}
