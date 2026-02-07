<?php

use Illuminate\Database\Capsule\Manager as DB;

/**
 * Add FULLTEXT indexes for fuzzy/typo-tolerant search.
 *
 * Idempotent: checks if each index already exists before creating.
 *
 * Run via: php symfony ahg:add-fulltext-indexes
 */
class ahgAddFulltextIndexesTask extends sfBaseTask
{
    protected function configure()
    {
        $this->addOptions([
            new sfCommandOption('application', null, sfCommandOption::PARAMETER_REQUIRED, 'The application name', 'qubit'),
            new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'The environment', 'prod'),
            new sfCommandOption('connection', null, sfCommandOption::PARAMETER_REQUIRED, 'The connection name', 'propel'),
        ]);

        $this->namespace = 'ahg';
        $this->name = 'add-fulltext-indexes';
        $this->briefDescription = 'Add FULLTEXT indexes for fuzzy search';
        $this->detailedDescription = <<<EOF
The [ahg:add-fulltext-indexes|INFO] task adds MySQL FULLTEXT indexes
to i18n tables for natural language search with stemming.

Indexes added:
  - ft_ioi_title on information_object_i18n(title)
  - ft_ioi_scope on information_object_i18n(scope_and_content)
  - ft_ai_name on actor_i18n(authorized_form_of_name)
  - ft_ti_name on term_i18n(name)

Call it with:

  [php symfony ahg:add-fulltext-indexes|INFO]
EOF;
    }

    protected function execute($arguments = [], $options = [])
    {
        $databaseManager = new sfDatabaseManager($this->configuration);
        $pdo = DB::connection()->getPdo();

        $indexes = [
            ['information_object_i18n', 'ft_ioi_title', 'title'],
            ['information_object_i18n', 'ft_ioi_scope', 'scope_and_content'],
            ['actor_i18n', 'ft_ai_name', 'authorized_form_of_name'],
            ['term_i18n', 'ft_ti_name', 'name'],
        ];

        foreach ($indexes as [$table, $indexName, $column]) {
            if ($this->indexExists($pdo, $table, $indexName)) {
                $this->logSection('skip', "$indexName already exists on $table");
                continue;
            }

            $this->logSection('create', "Adding FULLTEXT index $indexName on $table($column)...");
            try {
                $pdo->exec("CREATE FULLTEXT INDEX `$indexName` ON `$table`(`$column`)");
                $this->logSection('ok', "$indexName created successfully");
            } catch (\Exception $e) {
                $this->logSection('error', "Failed to create $indexName: " . $e->getMessage(), null, 'ERROR');
            }
        }

        $this->logSection('done', 'FULLTEXT index setup complete');
    }

    private function indexExists(\PDO $pdo, string $table, string $indexName): bool
    {
        $stmt = $pdo->prepare("SHOW INDEX FROM `$table` WHERE Key_name = ?");
        $stmt->execute([$indexName]);

        return $stmt->rowCount() > 0;
    }
}
