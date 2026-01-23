<?php
/**
 * CLI task for batch spell checking
 */
class aiSpellcheckTask extends sfBaseTask
{
    protected function configure()
    {
        $this->addOptions([
            new sfCommandOption('application', null, sfCommandOption::PARAMETER_OPTIONAL, 'The application name', 'qubit'),
            new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'The environment', 'cli'),
            new sfCommandOption('object', null, sfCommandOption::PARAMETER_OPTIONAL, 'Check specific object ID'),
            new sfCommandOption('repository', null, sfCommandOption::PARAMETER_OPTIONAL, 'Check all in repository ID'),
            new sfCommandOption('all', null, sfCommandOption::PARAMETER_NONE, 'Check all objects'),
            new sfCommandOption('limit', null, sfCommandOption::PARAMETER_OPTIONAL, 'Maximum to check', 100),
            new sfCommandOption('dry-run', null, sfCommandOption::PARAMETER_NONE, 'Show what would be checked'),
            new sfCommandOption('language', null, sfCommandOption::PARAMETER_OPTIONAL, 'Language code (e.g., en_ZA)'),
        ]);
        $this->namespace = 'ai';
        $this->name = 'spellcheck';
        $this->briefDescription = 'Check spelling in archival records';
        $this->detailedDescription = <<<EOD
The [ai:spellcheck|INFO] task checks spelling in metadata fields.

Examples:
  [php symfony ai:spellcheck --all --limit=100|INFO]
  [php symfony ai:spellcheck --object=12345|INFO]
EOD;
    }

    protected function execute($arguments = [], $options = [])
    {
        sfContext::createInstance($this->configuration);
        \AhgCore\Core\AhgDb::init();

        $this->logSection('ai', 'Starting spell check task');

        $settings = $this->getSettings('spellcheck');
        if (($settings['enabled'] ?? '0') !== '1') {
            $this->logSection('ai', 'Spell check disabled', null, 'ERROR');
            return 1;
        }

        $language = $options['language'] ?: ($settings['language'] ?? 'en_US');
        $fieldsJson = $settings['fields'] ?? '["title","scopeAndContent"]';
        $fields = json_decode($fieldsJson, true) ?: ['title', 'scopeAndContent'];

        $objectIds = $this->getObjectsToProcess($options);
        if (empty($objectIds)) {
            $this->logSection('ai', 'No objects to check');
            return 0;
        }

        $this->logSection('ai', sprintf('Checking %d objects (lang: %s)', count($objectIds), $language));

        if ($options['dry-run']) {
            foreach ($objectIds as $id) {
                $this->logSection('ai', "Would check: $id");
            }
            return 0;
        }

        $checked = 0;
        $withErrors = 0;

        foreach ($objectIds as $objectId) {
            if ($checked >= (int)($options['limit'] ?: 100)) {
                break;
            }

            $errors = $this->checkObject($objectId, $fields, $language);
            $checked++;

            if (!empty($errors)) {
                $withErrors++;
                $count = array_sum(array_map('count', $errors));
                $this->logSection('ai', "Object $objectId: $count issues");
            }
        }

        $this->logSection('ai', sprintf('Done: %d checked, %d with issues', $checked, $withErrors));
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

    protected function getObjectsToProcess($options)
    {
        $db = \Illuminate\Database\Capsule\Manager::connection();

        if (!empty($options['object'])) {
            return [(int)$options['object']];
        }

        $query = $db->table('information_object')
            ->select('id')
            ->where('id', '!=', QubitInformationObject::ROOT_ID);

        if (!empty($options['repository'])) {
            $query->where('repository_id', (int)$options['repository']);
        }

        return $query->limit((int)($options['limit'] ?: 100))->pluck('id')->toArray();
    }

    protected function checkObject($objectId, $fields, $language)
    {
        $io = QubitInformationObject::getById($objectId);
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
            exec("cat " . escapeshellarg($tmp) . " | aspell -l " . escapeshellarg($lang) . " list 2>/dev/null | sort -u", $misspelled);
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

    protected function storeResults($objectId, $errors)
    {
        $db = \Illuminate\Database\Capsule\Manager::connection();
        $db->table('ahg_spellcheck_result')->where('object_id', $objectId)->delete();
        $db->table('ahg_spellcheck_result')->insert([
            'object_id' => $objectId,
            'errors_json' => json_encode($errors),
            'error_count' => array_sum(array_map('count', $errors)),
            'status' => 'pending',
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }
}
