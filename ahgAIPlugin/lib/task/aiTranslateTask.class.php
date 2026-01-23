<?php
/**
 * CLI task for batch translation using Argos Translate
 */
class aiTranslateTask extends sfBaseTask
{
    protected function configure()
    {
        $this->addOptions([
            new sfCommandOption('application', null, sfCommandOption::PARAMETER_OPTIONAL, 'The application name', 'qubit'),
            new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'The environment', 'cli'),
            new sfCommandOption('object', null, sfCommandOption::PARAMETER_OPTIONAL, 'Translate specific object ID'),
            new sfCommandOption('repository', null, sfCommandOption::PARAMETER_OPTIONAL, 'Translate all in repository ID'),
            new sfCommandOption('from', null, sfCommandOption::PARAMETER_REQUIRED, 'Source culture (e.g., en)'),
            new sfCommandOption('to', null, sfCommandOption::PARAMETER_REQUIRED, 'Target culture (e.g., af)'),
            new sfCommandOption('fields', null, sfCommandOption::PARAMETER_OPTIONAL, 'Fields to translate (comma-separated)', 'title,scope_and_content'),
            new sfCommandOption('limit', null, sfCommandOption::PARAMETER_OPTIONAL, 'Maximum to translate', 100),
            new sfCommandOption('dry-run', null, sfCommandOption::PARAMETER_NONE, 'Show what would be translated'),
            new sfCommandOption('install-package', null, sfCommandOption::PARAMETER_NONE, 'Install language package if missing'),
        ]);
        $this->namespace = 'ai';
        $this->name = 'translate';
        $this->briefDescription = 'Translate archival records between cultures';
        $this->detailedDescription = <<<EOD
The [ai:translate|INFO] task translates records using Argos Translate (offline).

Examples:
  [php symfony ai:translate --from=en --to=af --object=12345|INFO]
  [php symfony ai:translate --from=en --to=af --repository=5 --limit=50|INFO]
  [php symfony ai:translate --from=en --to=af --install-package|INFO]
EOD;
    }

    protected function execute($arguments = [], $options = [])
    {
        sfContext::createInstance($this->configuration);
        \AhgCore\Core\AhgDb::init();

        $this->logSection('ai', 'Starting translation task');

        $settings = $this->getSettings('translate');
        if (($settings['enabled'] ?? '1') !== '1') {
            $this->logSection('ai', 'Translation is disabled', null, 'ERROR');
            return 1;
        }

        $fromCulture = $options['from'];
        $toCulture = $options['to'];
        $fields = explode(',', $options['fields']);

        $this->logSection('ai', sprintf('Translating: %s -> %s', $fromCulture, $toCulture));
        $this->logSection('ai', sprintf('Fields: %s', implode(', ', $fields)));

        // Check if language package is installed
        $pythonPath = $this->getPythonPath();
        if (!$this->checkLanguagePackage($pythonPath, $fromCulture, $toCulture)) {
            if ($options['install-package']) {
                $this->logSection('ai', 'Installing language package...');
                $this->installLanguagePackage($pythonPath, $fromCulture, $toCulture);
            } else {
                $this->logSection('ai', "Language package not installed. Use --install-package", null, 'ERROR');
                return 1;
            }
        }

        $objectIds = $this->getObjectsToProcess($options, $fromCulture);
        if (empty($objectIds)) {
            $this->logSection('ai', 'No objects found to translate');
            return 0;
        }

        $this->logSection('ai', sprintf('Found %d objects', count($objectIds)));

        if ($options['dry-run']) {
            foreach ($objectIds as $id) {
                $this->logSection('ai', "Would translate: $id");
            }
            return 0;
        }

        $translated = 0;
        $errors = 0;

        foreach ($objectIds as $objectId) {
            if ($translated >= (int)($options['limit'] ?: 100)) {
                break;
            }

            try {
                $this->translateObject($objectId, $fromCulture, $toCulture, $fields, $pythonPath);
                $this->logSection('ai', "Translated: $objectId");
                $translated++;
            } catch (Exception $e) {
                $this->logSection('ai', "Error on $objectId: " . $e->getMessage(), null, 'ERROR');
                $errors++;
            }
        }

        $this->logSection('ai', sprintf('Done: %d translated, %d errors', $translated, $errors));
        return 0;
    }

    protected function getSettings(string $feature): array
    {
        $settings = [];
        $rows = \Illuminate\Database\Capsule\Manager::table('ahg_ai_settings')
            ->where('feature', $feature)
            ->get();
        foreach ($rows as $row) {
            $settings[$row->setting_key] = $row->setting_value;
        }
        return $settings;
    }

    protected function getPythonPath(): string
    {
        // Check for python3 in common locations
        $paths = ['/usr/bin/python3', '/usr/local/bin/python3', 'python3'];
        foreach ($paths as $path) {
            exec("which $path 2>/dev/null", $output, $code);
            if ($code === 0) {
                return trim($output[0] ?? $path);
            }
            $output = [];
        }
        return 'python3';
    }

    protected function checkLanguagePackage(string $pythonPath, string $from, string $to): bool
    {
        $script = sfConfig::get('sf_root_dir') . '/atom-ahg-python/src/atom_ahg/resources/translation.py';
        if (!file_exists($script)) {
            return false;
        }

        $cmd = sprintf(
            '%s %s list 2>/dev/null',
            escapeshellarg($pythonPath),
            escapeshellarg($script)
        );

        exec($cmd, $output, $code);
        if ($code !== 0) {
            return false;
        }

        $result = json_decode(implode('', $output), true);
        if (!isset($result['installed_pairs'])) {
            return false;
        }

        foreach ($result['installed_pairs'] as $pair) {
            if ($pair['from'] === $from && $pair['to'] === $to) {
                return true;
            }
        }

        return false;
    }

    protected function installLanguagePackage(string $pythonPath, string $from, string $to): void
    {
        $script = sfConfig::get('sf_root_dir') . '/atom-ahg-python/src/atom_ahg/resources/translation.py';

        $cmd = sprintf(
            '%s %s install --from=%s --to=%s 2>&1',
            escapeshellarg($pythonPath),
            escapeshellarg($script),
            escapeshellarg($from),
            escapeshellarg($to)
        );

        exec($cmd, $output, $code);
        if ($code !== 0) {
            throw new Exception("Failed to install language package: " . implode("\n", $output));
        }

        $this->logSection('ai', 'Language package installed successfully');
    }

    protected function getObjectsToProcess($options, $fromCulture)
    {
        $db = \Illuminate\Database\Capsule\Manager::connection();

        if (!empty($options['object'])) {
            return [(int)$options['object']];
        }

        $query = $db->table('information_object as io')
            ->join('information_object_i18n as ioi', function ($join) use ($fromCulture) {
                $join->on('io.id', '=', 'ioi.id')
                     ->where('ioi.culture', '=', $fromCulture);
            })
            ->select('io.id')
            ->where('io.id', '!=', QubitInformationObject::ROOT_ID);

        if (!empty($options['repository'])) {
            $query->where('io.repository_id', (int)$options['repository']);
        }

        return $query->limit((int)($options['limit'] ?: 100))->pluck('id')->toArray();
    }

    protected function translateObject($objectId, $fromCulture, $toCulture, $fields, $pythonPath)
    {
        $db = \Illuminate\Database\Capsule\Manager::connection();

        // Get source text
        $source = $db->table('information_object_i18n')
            ->where('id', $objectId)
            ->where('culture', $fromCulture)
            ->first();

        if (!$source) {
            throw new Exception("No source text in culture: $fromCulture");
        }

        $script = sfConfig::get('sf_root_dir') . '/atom-ahg-python/src/atom_ahg/resources/translation.py';

        foreach ($fields as $field) {
            $field = trim($field);
            $sourceText = $source->$field ?? null;

            if (empty($sourceText)) {
                continue;
            }

            // Call Python translation
            $cmd = sprintf(
                '%s %s translate %s --from=%s --to=%s 2>&1',
                escapeshellarg($pythonPath),
                escapeshellarg($script),
                escapeshellarg($sourceText),
                escapeshellarg($fromCulture),
                escapeshellarg($toCulture)
            );

            exec($cmd, $output, $code);
            $result = json_decode(implode('', $output), true);
            $output = [];

            if (!isset($result['translation'])) {
                $this->logSection('ai', "Translation failed for field: $field", null, 'ERROR');
                continue;
            }

            $translatedText = $result['translation'];

            // Check if target culture record exists
            $exists = $db->table('information_object_i18n')
                ->where('id', $objectId)
                ->where('culture', $toCulture)
                ->exists();

            if ($exists) {
                $db->table('information_object_i18n')
                    ->where('id', $objectId)
                    ->where('culture', $toCulture)
                    ->update([$field => $translatedText]);
            } else {
                // Copy source record and update with translation
                $newRecord = (array)$source;
                $newRecord['culture'] = $toCulture;
                $newRecord[$field] = $translatedText;
                unset($newRecord['id']); // Remove auto-increment if present
                $db->table('information_object_i18n')->insert($newRecord);
            }

            // Log translation
            $db->table('ahg_translation_log')->insert([
                'object_id' => $objectId,
                'field_name' => $field,
                'source_culture' => $fromCulture,
                'target_culture' => $toCulture,
                'source_text' => substr($sourceText, 0, 1000),
                'translated_text' => substr($translatedText, 0, 1000),
                'translation_engine' => 'argos',
                'created_by' => null,
                'created_at' => date('Y-m-d H:i:s')
            ]);
        }
    }
}
