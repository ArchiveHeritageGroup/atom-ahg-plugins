<?php

/**
 * storage:migrate-flat-locations - carry the flat location fields into the
 * location tree.
 *
 * Reports what it would do and changes nothing, unless --apply is given. See
 * StorageFlatMigrationService for what is read, in what order, and what is
 * deliberately left alone.
 *
 *   php symfony storage:migrate-flat-locations
 *   php symfony storage:migrate-flat-locations --apply
 *   php symfony storage:migrate-flat-locations --free-text-type=room --apply
 */
class storageMigrateFlatLocationsTask extends sfBaseTask
{
    protected function configure()
    {
        $this->addOptions([
            new sfCommandOption('application', null, sfCommandOption::PARAMETER_OPTIONAL, 'The application name', 'qubit'),
            new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'The environment', 'cli'),
            new sfCommandOption('apply', null, sfCommandOption::PARAMETER_NONE, 'Write the changes. Without this, only report'),
            new sfCommandOption('free-text-type', null, sfCommandOption::PARAMETER_OPTIONAL, 'File objects that have only free-text locations under a root location of this type'),
            new sfCommandOption('culture', null, sfCommandOption::PARAMETER_OPTIONAL, 'Culture to read object names and free text in', 'en'),
        ]);
        $this->namespace = 'storage';
        $this->name = 'migrate-flat-locations';
        $this->briefDescription = 'Place physical objects in the storage location tree from the old flat location fields';
    }

    public function execute($arguments = [], $options = [])
    {
        sfContext::createInstance($this->configuration);
        \AhgCore\Core\AhgDb::init();
        require_once __DIR__.'/../Services/StorageLocationService.php';
        require_once __DIR__.'/../Services/StorageMovementService.php';
        require_once __DIR__.'/../Services/StorageFlatMigrationService.php';

        $apply = !empty($options['apply']);
        $service = new \AhgStorageManage\Services\StorageFlatMigrationService($options['culture'] ?: 'en');

        try {
            $report = $service->run($apply, array_filter(['free_text_type' => $options['free-text-type'] ?: null]));
        } catch (Exception $e) {
            $this->logSection('storage', 'Nothing was changed: '.$e->getMessage(), null, 'ERROR');

            return 1;
        }

        $verb = $apply ? 'created' : 'would be created';

        foreach ($report['locations_created'] as $label) {
            $this->logSection('location', $label.' - '.$verb);
        }

        foreach ($report['placed'] as $row) {
            $this->logSection('object', sprintf('%s (%d) -> %s, from %s', $row['name'], $row['object_id'], $row['path'], $row['source']));
        }

        foreach ($report['skipped'] as $row) {
            $this->logSection('skipped', sprintf('%s (%d): %s', $row['name'], $row['object_id'], $row['reason']));
        }

        $this->logSection('storage', sprintf(
            '%s: %d location(s) %s, %d reused, %d object(s) %s, %d left alone.',
            $apply ? 'Done' : 'Dry run, nothing changed',
            count($report['locations_created']),
            $verb,
            count($report['locations_reused']),
            count($report['placed']),
            $apply ? 'placed' : 'would be placed',
            count($report['skipped'])
        ));

        if (!$apply) {
            $this->logSection('storage', 'Run again with --apply to write this.');
        }

        return 0;
    }
}
