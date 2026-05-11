<?php

/**
 * php symfony version:snapshot --entity=information_object --id=N
 *
 * Smoke-test wrapper around SnapshotBuilder. Prints the JSON snapshot to stdout
 * with deterministic key ordering. Used during Phase B verification.
 *
 * @phase B
 */
class versionSnapshotTask extends sfBaseTask
{
    protected function configure()
    {
        $this->addOptions([
            new sfCommandOption('application', null, sfCommandOption::PARAMETER_OPTIONAL, 'The application name', true),
            new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'The environment', 'cli'),
            new sfCommandOption('connection', null, sfCommandOption::PARAMETER_REQUIRED, 'The connection name', 'propel'),
            new sfCommandOption('entity', null, sfCommandOption::PARAMETER_REQUIRED, 'information_object | actor'),
            new sfCommandOption('id', null, sfCommandOption::PARAMETER_REQUIRED, 'Entity primary key'),
            new sfCommandOption('pretty', null, sfCommandOption::PARAMETER_NONE, 'Pretty-print JSON'),
        ]);
        $this->namespace = 'version';
        $this->name = 'snapshot';
        $this->briefDescription = 'Print a SnapshotBuilder JSON snapshot for an entity (smoke test)';
    }

    protected function execute($arguments = [], $options = [])
    {
        $entity = (string) ($options['entity'] ?? '');
        $id = (int) ($options['id'] ?? 0);
        if ($entity === '' || $id <= 0) {
            throw new \sfCommandException('--entity and --id are required');
        }

        // Plugin autoloader is registered by Configuration::initialize when the
        // plugin is enabled. For the CLI we may not have full plugin init, so
        // load the service explicitly.
        $servicePath = realpath(__DIR__ . '/../Services/SnapshotBuilder.php');
        if ($servicePath && !class_exists('\\AhgVersionControl\\Services\\SnapshotBuilder')) {
            require_once $servicePath;
        }

        $builder = new \AhgVersionControl\Services\SnapshotBuilder();

        $snapshot = match ($entity) {
            'information_object' => $builder->buildForInformationObject($id),
            'actor'              => $builder->buildForActor($id),
            default              => throw new \sfCommandException("Unknown entity: {$entity}. Use information_object or actor."),
        };

        $flags = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE;
        if (!empty($options['pretty'])) {
            $flags |= JSON_PRETTY_PRINT;
        }
        echo json_encode($snapshot, $flags) . PHP_EOL;
    }
}
