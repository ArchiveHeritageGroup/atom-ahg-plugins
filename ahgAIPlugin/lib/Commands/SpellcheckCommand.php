<?php

namespace AtomFramework\Console\Commands\Ai;

use AtomFramework\Console\BaseCommand;
use Illuminate\Database\Capsule\Manager as DB;

/**
 * CLI command for batch spell checking.
 */
class SpellcheckCommand extends BaseCommand
{
    protected string $name = 'ai:spellcheck';
    protected string $description = 'Check spelling in archival records';
    protected string $detailedDescription = <<<'EOF'
    Check spelling in metadata fields using aspell.

    Examples:
      php bin/atom ai:spellcheck --all --limit=100
      php bin/atom ai:spellcheck --object=12345
      php bin/atom ai:spellcheck --repository=5 --language=en_ZA
    EOF;

    protected function configure(): void
    {
        $this->addOption('object', null, 'Check specific object ID');
        $this->addOption('repository', null, 'Check all in repository ID');
        $this->addOption('all', null, 'Check all objects');
        $this->addOption('limit', null, 'Maximum to check', '100');
        $this->addOption('dry-run', null, 'Show what would be checked');
        $this->addOption('language', null, 'Language code (e.g., en_ZA)');
    }

    protected function handle(): int
    {
        $this->info('Starting spell check task');

        $settings = $this->getSettings('spellcheck');
        if (($settings['enabled'] ?? '0') !== '1') {
            $this->error('Spell check disabled');
            return 1;
        }

        $language = $this->option('language') ?: ($settings['language'] ?? 'en_US');
        $fieldsJson = $settings['fields'] ?? '["title","scopeAndContent"]';
        $fields = json_decode($fieldsJson, true) ?: ['title', 'scopeAndContent'];

        $objectIds = $this->getObjectsToProcess();
        if (empty($objectIds)) {
            $this->info('No objects to check');
            return 0;
        }

        $this->info(sprintf('Checking %d objects (lang: %s)', count($objectIds), $language));

        if ($this->hasOption('dry-run')) {
            foreach ($objectIds as $id) {
                $this->line("  Would check: $id");
            }
            return 0;
        }

        $checked = 0;
        $withErrors = 0;

        foreach ($objectIds as $objectId) {
            if ($checked >= (int) ($this->option('limit') ?: 100)) {
                break;
            }

            $errors = $this->checkObject($objectId, $fields, $language);
            $checked++;

            if (!empty($errors)) {
                $withErrors++;
                $count = array_sum(array_map('count', $errors));
                $this->line("  Object $objectId: $count issues");
            }
        }

        $this->info(sprintf('Done: %d checked, %d with issues', $checked, $withErrors));
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

    private function getObjectsToProcess(): array
    {
        if ($this->option('object')) {
            return [(int) $this->option('object')];
        }

        $query = DB::table('information_object')
            ->select('id')
            ->where('id', '!=', \QubitInformationObject::ROOT_ID);

        if ($this->option('repository')) {
            $query->where('repository_id', (int) $this->option('repository'));
        }

        return $query->limit((int) ($this->option('limit') ?: 100))->pluck('id')->toArray();
    }

    private function checkObject(int $objectId, array $fields, string $language): array
    {
        $io = \QubitInformationObject::getById($objectId);
        if (!$io) {
            return [];
        }

        $errors = [];
        $lang = explode('_', $language)[0];

        foreach ($fields as $field) {
            $getter = 'get' . ucfirst($field);
            if (!method_exists($io, $getter)) {
                continue;
            }

            $text = $io->$getter(['fallback' => true]);
            if (empty($text)) {
                continue;
            }

            $tmp = tempnam(sys_get_temp_dir(), 'spell_');
            file_put_contents($tmp, $text);

            $misspelled = [];
            exec('cat ' . escapeshellarg($tmp) . ' | aspell -l ' . escapeshellarg($lang) . ' list 2>/dev/null | sort -u', $misspelled);
            unlink($tmp);

            if (!empty($misspelled)) {
                $errors[$field] = $misspelled;
            }
        }

        if (!empty($errors)) {
            $this->storeResults($objectId, $errors);
        }

        return $errors;
    }

    private function storeResults(int $objectId, array $errors): void
    {
        DB::table('ahg_spellcheck_result')->where('object_id', $objectId)->delete();
        DB::table('ahg_spellcheck_result')->insert([
            'object_id' => $objectId,
            'errors_json' => json_encode($errors),
            'error_count' => array_sum(array_map('count', $errors)),
            'status' => 'pending',
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }
}
