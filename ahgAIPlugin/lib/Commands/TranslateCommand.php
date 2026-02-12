<?php

namespace AtomFramework\Console\Commands\Ai;

use AtomFramework\Console\BaseCommand;
use Illuminate\Database\Capsule\Manager as DB;

/**
 * CLI command for batch translation using Argos Translate.
 */
class TranslateCommand extends BaseCommand
{
    protected string $name = 'ai:translate';
    protected string $description = 'Translate archival records between cultures';
    protected string $detailedDescription = <<<'EOF'
    Translate records using Argos Translate (offline).

    Examples:
      php bin/atom ai:translate --from=en --to=af --object=12345
      php bin/atom ai:translate --from=en --to=af --repository=5 --limit=50
      php bin/atom ai:translate --from=en --to=af --install-package
    EOF;

    protected function configure(): void
    {
        $this->addOption('object', null, 'Translate specific object ID');
        $this->addOption('repository', null, 'Translate all in repository ID');
        $this->addOption('from', null, 'Source culture (e.g., en)');
        $this->addOption('to', null, 'Target culture (e.g., af)');
        $this->addOption('fields', null, 'Fields to translate (comma-separated)', 'title,scope_and_content');
        $this->addOption('limit', null, 'Maximum to translate', '100');
        $this->addOption('dry-run', null, 'Show what would be translated');
        $this->addOption('install-package', null, 'Install language package if missing');
    }

    protected function handle(): int
    {
        $this->info('Starting translation task');

        $settings = $this->getSettings('translate');
        if (($settings['enabled'] ?? '1') !== '1') {
            $this->error('Translation is disabled');
            return 1;
        }

        $fromCulture = $this->option('from');
        $toCulture = $this->option('to');

        if (!$fromCulture || !$toCulture) {
            $this->error('Both --from and --to options are required');
            return 1;
        }

        $fields = explode(',', $this->option('fields') ?: 'title,scope_and_content');

        $this->info(sprintf('Translating: %s -> %s', $fromCulture, $toCulture));
        $this->info(sprintf('Fields: %s', implode(', ', $fields)));

        // Check if language package is installed
        $pythonPath = $this->getPythonPath();
        if (!$this->checkLanguagePackage($pythonPath, $fromCulture, $toCulture)) {
            if ($this->hasOption('install-package')) {
                $this->info('Installing language package...');
                $this->installLanguagePackage($pythonPath, $fromCulture, $toCulture);
            } else {
                $this->error('Language package not installed. Use --install-package');
                return 1;
            }
        }

        $objectIds = $this->getObjectsToProcess($fromCulture);
        if (empty($objectIds)) {
            $this->info('No objects found to translate');
            return 0;
        }

        $this->info(sprintf('Found %d objects', count($objectIds)));

        if ($this->hasOption('dry-run')) {
            foreach ($objectIds as $id) {
                $this->line("  Would translate: $id");
            }
            return 0;
        }

        $translated = 0;
        $errors = 0;

        foreach ($objectIds as $objectId) {
            if ($translated >= (int) ($this->option('limit') ?: 100)) {
                break;
            }

            try {
                $this->translateObject($objectId, $fromCulture, $toCulture, $fields, $pythonPath);
                $this->line("  Translated: $objectId");
                $translated++;
            } catch (\Exception $e) {
                $this->error("Error on $objectId: " . $e->getMessage());
                $errors++;
            }
        }

        $this->info(sprintf('Done: %d translated, %d errors', $translated, $errors));
        return 0;
    }

    private function getSettings(string $feature): array
    {
        $settings = [];
        $rows = DB::table('ahg_ai_settings')
            ->where('feature', $feature)
            ->get();
        foreach ($rows as $row) {
            $settings[$row->setting_key] = $row->setting_value;
        }
        return $settings;
    }

    private function getPythonPath(): string
    {
        $paths = ['/usr/bin/python3', '/usr/local/bin/python3', 'python3'];
        foreach ($paths as $path) {
            $output = [];
            $code = 0;
            exec("which $path 2>/dev/null", $output, $code);
            if ($code === 0) {
                return trim($output[0] ?? $path);
            }
        }
        return 'python3';
    }

    private function checkLanguagePackage(string $pythonPath, string $from, string $to): bool
    {
        $script = $this->getAtomRoot() . '/atom-ahg-python/src/atom_ahg/resources/translation.py';
        if (!file_exists($script)) {
            return false;
        }

        $cmd = sprintf(
            '%s %s list 2>/dev/null',
            escapeshellarg($pythonPath),
            escapeshellarg($script)
        );

        $output = [];
        $code = 0;
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

    private function installLanguagePackage(string $pythonPath, string $from, string $to): void
    {
        $script = $this->getAtomRoot() . '/atom-ahg-python/src/atom_ahg/resources/translation.py';

        $cmd = sprintf(
            '%s %s install --from=%s --to=%s 2>&1',
            escapeshellarg($pythonPath),
            escapeshellarg($script),
            escapeshellarg($from),
            escapeshellarg($to)
        );

        $output = [];
        $code = 0;
        exec($cmd, $output, $code);
        if ($code !== 0) {
            throw new \Exception('Failed to install language package: ' . implode("\n", $output));
        }

        $this->success('Language package installed successfully');
    }

    private function getObjectsToProcess(string $fromCulture): array
    {
        if ($this->option('object')) {
            return [(int) $this->option('object')];
        }

        $query = DB::table('information_object as io')
            ->join('information_object_i18n as ioi', function ($join) use ($fromCulture) {
                $join->on('io.id', '=', 'ioi.id')
                     ->where('ioi.culture', '=', $fromCulture);
            })
            ->select('io.id')
            ->where('io.id', '!=', \QubitInformationObject::ROOT_ID);

        if ($this->option('repository')) {
            $query->where('io.repository_id', (int) $this->option('repository'));
        }

        return $query->limit((int) ($this->option('limit') ?: 100))->pluck('id')->toArray();
    }

    private function translateObject(int $objectId, string $fromCulture, string $toCulture, array $fields, string $pythonPath): void
    {
        // Get source text
        $source = DB::table('information_object_i18n')
            ->where('id', $objectId)
            ->where('culture', $fromCulture)
            ->first();

        if (!$source) {
            throw new \Exception("No source text in culture: $fromCulture");
        }

        $script = $this->getAtomRoot() . '/atom-ahg-python/src/atom_ahg/resources/translation.py';

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

            $output = [];
            $code = 0;
            exec($cmd, $output, $code);
            $result = json_decode(implode('', $output), true);

            if (!isset($result['translation'])) {
                $this->warning("Translation failed for field: $field");
                continue;
            }

            $translatedText = $result['translation'];

            // Check if target culture record exists
            $exists = DB::table('information_object_i18n')
                ->where('id', $objectId)
                ->where('culture', $toCulture)
                ->exists();

            if ($exists) {
                DB::table('information_object_i18n')
                    ->where('id', $objectId)
                    ->where('culture', $toCulture)
                    ->update([$field => $translatedText]);
            } else {
                $newRecord = (array) $source;
                $newRecord['culture'] = $toCulture;
                $newRecord[$field] = $translatedText;
                unset($newRecord['id']);
                DB::table('information_object_i18n')->insert($newRecord);
            }

            // Log translation
            DB::table('ahg_translation_log')->insert([
                'object_id' => $objectId,
                'field_name' => $field,
                'source_culture' => $fromCulture,
                'target_culture' => $toCulture,
                'source_text' => substr($sourceText, 0, 1000),
                'translated_text' => substr($translatedText, 0, 1000),
                'translation_engine' => 'argos',
                'created_by' => null,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        }
    }
}
