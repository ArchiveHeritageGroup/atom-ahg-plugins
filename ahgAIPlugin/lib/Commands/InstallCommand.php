<?php

namespace AtomFramework\Console\Commands\Ai;

use AtomFramework\Console\BaseCommand;
use Illuminate\Database\Capsule\Manager as DB;

/**
 * AI Plugin Install Command.
 */
class InstallCommand extends BaseCommand
{
    protected string $name = 'ai:install';
    protected string $description = 'Install ahgAIPlugin database tables';
    protected string $detailedDescription = <<<'EOF'
    Creates the database tables required by ahgAIPlugin.

    Usage:
      php bin/atom ai:install
    EOF;

    protected function handle(): int
    {
        $this->info('Installing ahgAIPlugin...');

        $pluginsDir = $this->getAtomRoot() . '/plugins';
        $sqlFile = $pluginsDir . '/ahgAIPlugin/data/install.sql';

        if (!file_exists($sqlFile)) {
            $this->error('Install SQL file not found: ' . $sqlFile);
            return 1;
        }

        $this->info('Running database migrations...');

        $sql = file_get_contents($sqlFile);

        $statements = array_filter(
            array_map('trim', preg_split('/;[\r\n]+/', $sql)),
            function ($stmt) {
                $stmt = trim($stmt);
                return !empty($stmt)
                    && strpos($stmt, '--') !== 0
                    && strpos($stmt, '/*') !== 0;
            }
        );

        $executed = 0;
        foreach ($statements as $statement) {
            if (!empty(trim($statement))) {
                try {
                    DB::statement($statement);
                    $executed++;
                } catch (\Exception $e) {
                    $this->warning('Warning: ' . $e->getMessage());
                }
            }
        }

        $this->info("Executed {$executed} SQL statements");
        $this->success('Installation complete!');
        $this->newline();

        $this->bold('  ahgAIPlugin installed successfully!');
        $this->newline();
        $this->line('  Features available:');
        $this->line('    - NER: Named Entity Recognition');
        $this->line('    - Translation: Offline machine translation (Argos)');
        $this->line('    - Summarization: AI-powered text summarization');
        $this->line('    - Spellcheck: Spelling and grammar checking');
        $this->newline();
        $this->line('  CLI Commands:');
        $this->line('    php bin/atom ai:ner-extract --help');
        $this->line('    php bin/atom ai:translate --help');
        $this->line('    php bin/atom ai:summarize --help');
        $this->line('    php bin/atom ai:spellcheck --help');
        $this->newline();
        $this->line('  Next steps:');
        $this->line('    1. Clear cache: rm -rf cache/*');
        $this->line('    2. Install translation packages (optional):');
        $this->line('       pip install argostranslate');
        $this->newline();

        return 0;
    }
}
