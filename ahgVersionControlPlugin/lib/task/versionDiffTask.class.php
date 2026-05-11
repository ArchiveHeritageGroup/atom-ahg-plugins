<?php

/**
 * php symfony version:diff --entity=information_object --id=N --v1=A --v2=B
 *
 * Loads two stored snapshots and prints the structured diff. Used during
 * Phase E verification and the Phase G UI's data source contract check.
 *
 * @phase E
 */
class versionDiffTask extends sfBaseTask
{
    protected function configure()
    {
        $this->addOptions([
            new sfCommandOption('application', null, sfCommandOption::PARAMETER_OPTIONAL, 'The application name', true),
            new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'The environment', 'cli'),
            new sfCommandOption('connection', null, sfCommandOption::PARAMETER_REQUIRED, 'The connection name', 'propel'),
            new sfCommandOption('entity', null, sfCommandOption::PARAMETER_REQUIRED, 'information_object | actor'),
            new sfCommandOption('id', null, sfCommandOption::PARAMETER_REQUIRED, 'Entity primary key'),
            new sfCommandOption('v1', null, sfCommandOption::PARAMETER_REQUIRED, 'Older version_number'),
            new sfCommandOption('v2', null, sfCommandOption::PARAMETER_REQUIRED, 'Newer version_number'),
            new sfCommandOption('pretty', null, sfCommandOption::PARAMETER_NONE, 'Pretty-print JSON output'),
        ]);
        $this->namespace = 'version';
        $this->name = 'diff';
        $this->briefDescription = 'Print a structured diff between two stored versions';
    }

    protected function execute($arguments = [], $options = [])
    {
        $entity = (string) ($options['entity'] ?? '');
        $id = (int) ($options['id'] ?? 0);
        $v1 = (int) ($options['v1'] ?? 0);
        $v2 = (int) ($options['v2'] ?? 0);
        if ($entity === '' || $id <= 0 || $v1 <= 0 || $v2 <= 0) {
            throw new \sfCommandException('--entity, --id, --v1 and --v2 are required');
        }

        $lib = realpath(__DIR__ . '/..');
        require_once $lib . '/Services/DiffComputer.php';

        $tableMap = [
            'information_object' => ['table' => 'information_object_version', 'fk' => 'information_object_id'],
            'actor'              => ['table' => 'actor_version',              'fk' => 'actor_id'],
        ];
        if (!isset($tableMap[$entity])) {
            throw new \sfCommandException("Unknown entity: {$entity}");
        }

        $table = $tableMap[$entity]['table'];
        $fk = $tableMap[$entity]['fk'];

        $snap1Json = \Illuminate\Database\Capsule\Manager::table($table)
            ->where($fk, $id)->where('version_number', $v1)->value('snapshot');
        $snap2Json = \Illuminate\Database\Capsule\Manager::table($table)
            ->where($fk, $id)->where('version_number', $v2)->value('snapshot');

        if (!is_string($snap1Json)) {
            throw new \sfCommandException("Version {$v1} not found for {$entity} {$id}");
        }
        if (!is_string($snap2Json)) {
            throw new \sfCommandException("Version {$v2} not found for {$entity} {$id}");
        }

        $diff = (new \AhgVersionControl\Services\DiffComputer())->diff(
            json_decode($snap1Json, true) ?? [],
            json_decode($snap2Json, true) ?? [],
        );

        $flags = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE;
        if (!empty($options['pretty'])) {
            $flags |= JSON_PRETTY_PRINT;
        }
        echo json_encode($diff, $flags) . PHP_EOL;
    }
}
