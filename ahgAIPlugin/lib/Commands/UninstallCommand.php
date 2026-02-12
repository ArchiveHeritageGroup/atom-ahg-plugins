<?php

namespace AtomFramework\Console\Commands\Ai;

use AtomFramework\Console\BaseCommand;
use Illuminate\Database\Capsule\Manager as DB;

/**
 * AI Plugin Uninstall Command.
 */
class UninstallCommand extends BaseCommand
{
    protected string $name = 'ai:uninstall';
    protected string $description = 'Uninstall ahgAIPlugin';
    protected string $detailedDescription = <<<'EOF'
    Removes ahgAIPlugin database tables and configuration.

    Usage:
      php bin/atom ai:uninstall
      php bin/atom ai:uninstall --keep-data
    EOF;

    protected function configure(): void
    {
        $this->addOption('keep-data', null, 'Keep database tables');
    }

    protected function handle(): int
    {
        $this->info('Uninstalling ahgAIPlugin...');

        if (!$this->hasOption('keep-data')) {
            $this->info('Removing database tables...');

            $tables = [
                'ahg_ai_usage',
                'ahg_ai_settings',
                'ahg_ner_entity_link',
                'ahg_ner_entity',
                'ahg_ner_extraction',
                'ahg_translation_log',
                'ahg_translation_queue',
            ];

            foreach ($tables as $table) {
                try {
                    DB::statement("DROP TABLE IF EXISTS `{$table}`");
                    $this->line("  Dropped table: {$table}");
                } catch (\Exception $e) {
                    $this->warning('Warning: ' . $e->getMessage());
                }
            }
        } else {
            $this->info('Keeping database tables (--keep-data specified)');
        }

        $this->success('Uninstall complete!');
        $this->newline();

        $this->bold('  ahgAIPlugin uninstalled.');
        $this->newline();
        $this->line('  To completely remove:');
        $this->line('    1. Disable plugin:');
        $this->line('       php bin/atom extension:disable ahgAIPlugin');
        $this->newline();
        $this->line('    2. Remove plugin files:');
        $this->line('       rm -rf plugins/ahgAIPlugin');
        $this->newline();

        return 0;
    }
}
