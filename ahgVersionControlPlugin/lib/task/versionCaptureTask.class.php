<?php

/**
 * php symfony version:capture --entity=information_object --id=N [--summary="..."] [--user-id=N]
 *
 * Combines SnapshotBuilder + VersionWriter into a single CLI invocation.
 * Used during Phase C verification + the Phase L backfill task.
 *
 * @phase C
 */
class versionCaptureTask extends sfBaseTask
{
    protected function configure()
    {
        $this->addOptions([
            new sfCommandOption('application', null, sfCommandOption::PARAMETER_OPTIONAL, 'The application name', true),
            new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'The environment', 'cli'),
            new sfCommandOption('connection', null, sfCommandOption::PARAMETER_REQUIRED, 'The connection name', 'propel'),
            new sfCommandOption('entity', null, sfCommandOption::PARAMETER_REQUIRED, 'information_object | actor'),
            new sfCommandOption('id', null, sfCommandOption::PARAMETER_REQUIRED, 'Entity primary key'),
            new sfCommandOption('summary', null, sfCommandOption::PARAMETER_OPTIONAL, 'Change summary text'),
            new sfCommandOption('user-id', null, sfCommandOption::PARAMETER_OPTIONAL, 'created_by user id'),
        ]);
        $this->namespace = 'version';
        $this->name = 'capture';
        $this->briefDescription = 'Build snapshot + write as the next version for an entity';
    }

    protected function execute($arguments = [], $options = [])
    {
        $entity = (string) ($options['entity'] ?? '');
        $id = (int) ($options['id'] ?? 0);
        if ($entity === '' || $id <= 0) {
            throw new \sfCommandException('--entity and --id are required');
        }

        $pluginLib = realpath(__DIR__ . '/..');
        foreach (['Services/SnapshotBuilder.php', 'Services/VersionWriter.php'] as $rel) {
            require_once $pluginLib . '/' . $rel;
        }

        $builder = new \AhgVersionControl\Services\SnapshotBuilder();
        $writer  = new \AhgVersionControl\Services\VersionWriter();

        $snapshot = match ($entity) {
            'information_object' => $builder->buildForInformationObject($id),
            'actor'              => $builder->buildForActor($id),
            default              => throw new \sfCommandException("Unknown entity: {$entity}"),
        };

        $version = $writer->write(
            entityType: $entity,
            entityId: $id,
            snapshot: $snapshot,
            changeSummary: $options['summary'] ?? null,
            userId: !empty($options['user-id']) ? (int) $options['user-id'] : null,
        );

        echo "version_number={$version} entity_type={$entity} entity_id={$id}\n";
    }
}
