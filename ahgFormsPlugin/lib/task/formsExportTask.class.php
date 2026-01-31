<?php

use Illuminate\Database\Capsule\Manager as DB;

/**
 * CLI task to export form templates.
 */
class formsExportTask extends sfBaseTask
{
    protected function configure()
    {
        $this->addOptions([
            new sfCommandOption('application', null, sfCommandOption::PARAMETER_OPTIONAL, 'The application name', 'qubit'),
            new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'The environment', 'cli'),
            new sfCommandOption('template-id', null, sfCommandOption::PARAMETER_REQUIRED, 'Template ID to export'),
            new sfCommandOption('output', null, sfCommandOption::PARAMETER_OPTIONAL, 'Output file path'),
        ]);

        $this->namespace = 'forms';
        $this->name = 'export';
        $this->briefDescription = 'Export a form template to JSON';
        $this->detailedDescription = <<<EOF
Export a form template to a JSON file for backup or sharing.

Examples:
  php symfony forms:export --template-id=1 --output=isadg-minimal.json
  php symfony forms:export --template-id=1  # Outputs to stdout
EOF;
    }

    protected function execute($arguments = [], $options = [])
    {
        sfContext::createInstance($this->configuration);
        require_once sfConfig::get('sf_root_dir') . '/atom-framework/bootstrap.php';
        require_once sfConfig::get('sf_root_dir') . '/plugins/ahgFormsPlugin/lib/Services/FormService.php';

        $templateId = (int) $options['template-id'];
        $service = new \ahgFormsPlugin\Services\FormService();

        try {
            $export = $service->exportTemplate($templateId);
        } catch (\Exception $e) {
            $this->logSection('forms', "Error: {$e->getMessage()}", null, 'ERROR');

            return 1;
        }

        $json = json_encode($export, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        if ($options['output']) {
            file_put_contents($options['output'], $json);
            $this->logSection('forms', "Template exported to: {$options['output']}");
            $this->logSection('forms', "Fields: " . count($export['fields']));
        } else {
            echo $json . "\n";
        }
    }
}
