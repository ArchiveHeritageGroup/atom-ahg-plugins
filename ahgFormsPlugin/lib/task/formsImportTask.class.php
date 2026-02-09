<?php

use Illuminate\Database\Capsule\Manager as DB;

/**
 * CLI task to import form templates.
 */
class formsImportTask extends sfBaseTask
{
    protected function configure()
    {
        $this->addOptions([
            new sfCommandOption('application', null, sfCommandOption::PARAMETER_OPTIONAL, 'The application name', 'qubit'),
            new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'The environment', 'cli'),
            new sfCommandOption('input', null, sfCommandOption::PARAMETER_REQUIRED, 'Input JSON file path'),
            new sfCommandOption('name', null, sfCommandOption::PARAMETER_OPTIONAL, 'Override template name'),
            new sfCommandOption('dry-run', null, sfCommandOption::PARAMETER_NONE, 'Preview without importing'),
        ]);

        $this->namespace = 'forms';
        $this->name = 'import';
        $this->briefDescription = 'Import a form template from JSON';
        $this->detailedDescription = <<<EOF
Import a form template from a JSON file.

Examples:
  php symfony forms:import --input=isadg-minimal.json
  php symfony forms:import --input=template.json --name="My Custom Form"
  php symfony forms:import --input=template.json --dry-run
EOF;
    }

    protected function execute($arguments = [], $options = [])
    {
        sfContext::createInstance($this->configuration);
        $bootstrap = sfConfig::get('sf_root_dir') . '/atom-framework/bootstrap.php';
        if (file_exists($bootstrap)) {
            require_once $bootstrap;
        }
        require_once sfConfig::get('sf_root_dir') . '/plugins/ahgFormsPlugin/lib/Services/FormService.php';

        $inputFile = $options['input'];

        if (!file_exists($inputFile)) {
            $this->logSection('forms', "File not found: {$inputFile}", null, 'ERROR');

            return 1;
        }

        $json = file_get_contents($inputFile);
        $data = json_decode($json, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->logSection('forms', 'Invalid JSON: ' . json_last_error_msg(), null, 'ERROR');

            return 1;
        }

        $name = $options['name'] ?? $data['name'];
        $fieldCount = count($data['fields'] ?? []);

        $this->logSection('forms', '=== Template Import ===');
        $this->logSection('forms', "Name: {$name}");
        $this->logSection('forms', "Type: {$data['form_type']}");
        $this->logSection('forms', "Fields: {$fieldCount}");

        if ($options['dry-run']) {
            $this->logSection('forms', '[DRY RUN] Would import template', null, 'COMMENT');

            foreach ($data['fields'] as $i => $field) {
                $this->logSection('forms', "  " . ($i + 1) . ". [{$field['field_type']}] {$field['field_name']}: {$field['label']}");
            }

            return;
        }

        $service = new \ahgFormsPlugin\Services\FormService();

        try {
            $templateId = $service->importTemplate($data, $name);
            $this->logSection('forms', "Template imported successfully! ID: {$templateId}", null, 'INFO');
        } catch (\Exception $e) {
            $this->logSection('forms', "Error: {$e->getMessage()}", null, 'ERROR');

            return 1;
        }
    }
}
