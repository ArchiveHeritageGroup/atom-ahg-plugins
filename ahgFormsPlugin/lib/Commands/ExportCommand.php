<?php

namespace AtomFramework\Console\Commands\Forms;

use AtomFramework\Console\BaseCommand;
use Illuminate\Database\Capsule\Manager as DB;

class ExportCommand extends BaseCommand
{
    protected string $name = 'forms:export';
    protected string $description = 'Export a form template to JSON';
    protected string $detailedDescription = <<<'EOF'
    Export a form template to a JSON file for backup or sharing.

    Examples:
      php bin/atom forms:export --template-id=1 --output=isadg-minimal.json
      php bin/atom forms:export --template-id=1   # Outputs to stdout
    EOF;

    protected function configure(): void
    {
        $this->addOption('template-id', null, 'Template ID to export');
        $this->addOption('output', null, 'Output file path');
    }

    protected function handle(): int
    {
        $templateId = (int) $this->option('template-id');

        if (!$templateId) {
            $this->error('Please specify --template-id');

            return 1;
        }

        $pluginDir = $this->getAtomRoot() . '/atom-ahg-plugins/ahgFormsPlugin';
        require_once $pluginDir . '/lib/Services/FormService.php';

        $service = new \ahgFormsPlugin\Services\FormService();

        try {
            $export = $service->exportTemplate($templateId);
        } catch (\Exception $e) {
            $this->error("Error: {$e->getMessage()}");

            return 1;
        }

        $json = json_encode($export, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        $output = $this->option('output');
        if ($output) {
            file_put_contents($output, $json);
            $this->info("Template exported to: {$output}");
            $this->info('Fields: ' . count($export['fields']));
        } else {
            $this->line($json);
        }

        return 0;
    }
}
