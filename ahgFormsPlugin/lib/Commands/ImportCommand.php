<?php

namespace AtomFramework\Console\Commands\Forms;

use AtomFramework\Console\BaseCommand;
use Illuminate\Database\Capsule\Manager as DB;

class ImportCommand extends BaseCommand
{
    protected string $name = 'forms:import';
    protected string $description = 'Import a form template from JSON';
    protected string $detailedDescription = <<<'EOF'
    Import a form template from a JSON file.

    Examples:
      php bin/atom forms:import --input=isadg-minimal.json
      php bin/atom forms:import --input=template.json --name="My Custom Form"
      php bin/atom forms:import --input=template.json --dry-run
    EOF;

    protected function configure(): void
    {
        $this->addOption('input', null, 'Input JSON file path');
        $this->addOption('name', null, 'Override template name');
        $this->addOption('dry-run', null, 'Preview without importing');
    }

    protected function handle(): int
    {
        $inputFile = $this->option('input');

        if (!$inputFile) {
            $this->error('Please specify --input');

            return 1;
        }

        if (!file_exists($inputFile)) {
            $this->error("File not found: {$inputFile}");

            return 1;
        }

        $json = file_get_contents($inputFile);
        $data = json_decode($json, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->error('Invalid JSON: ' . json_last_error_msg());

            return 1;
        }

        $name = $this->option('name') ?? $data['name'];
        $fieldCount = count($data['fields'] ?? []);

        $this->info('=== Template Import ===');
        $this->info("Name: {$name}");
        $this->info("Type: {$data['form_type']}");
        $this->info("Fields: {$fieldCount}");

        if ($this->hasOption('dry-run')) {
            $this->comment('[DRY RUN] Would import template');

            foreach ($data['fields'] as $i => $field) {
                $this->line('  ' . ($i + 1) . ". [{$field['field_type']}] {$field['field_name']}: {$field['label']}");
            }

            return 0;
        }

        $pluginDir = $this->getAtomRoot() . '/atom-ahg-plugins/ahgFormsPlugin';
        require_once $pluginDir . '/lib/Services/FormService.php';

        $service = new \ahgFormsPlugin\Services\FormService();

        try {
            $templateId = $service->importTemplate($data, $name);
            $this->success("Template imported successfully! ID: {$templateId}");
        } catch (\Exception $e) {
            $this->error("Error: {$e->getMessage()}");

            return 1;
        }

        return 0;
    }
}
