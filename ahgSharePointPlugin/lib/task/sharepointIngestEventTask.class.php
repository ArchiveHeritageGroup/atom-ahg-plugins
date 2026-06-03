<?php

/**
 * sharepoint:ingest-event — queue handler entry point.
 *
 * Invoked by QueueService (registered in ahgSharePointPluginConfiguration::initialize)
 * for each row inserted into sharepoint_event by the webhook receiver.
 *
 * @phase 2.A
 */
class sharepointIngestEventTask extends sfBaseTask
{
    protected function configure()
    {
        $this->addOptions([
            new sfCommandOption('application', null, sfCommandOption::PARAMETER_OPTIONAL, 'The application name', true),
            new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'The environment', 'cli'),
            new sfCommandOption('connection', null, sfCommandOption::PARAMETER_REQUIRED, 'The connection name', 'propel'),
            new sfCommandOption('event-id', null, sfCommandOption::PARAMETER_REQUIRED, 'sharepoint_event.id'),
        ]);

        $this->namespace = 'sharepoint';
        $this->name = 'ingest-event';
        $this->briefDescription = 'Process one inbound SharePoint webhook event';
    }

    public function execute($arguments = [], $options = [])
    {
        if (empty($options['event-id'])) {
            throw new \InvalidArgumentException('--event-id=<id> required');
        }
        $eventId = (int) $options['event-id'];

        // Lazy DI
        require_once __DIR__ . '/../Services/GraphClientService.php';
        require_once __DIR__ . '/../Services/GraphTokenCache.php';
        require_once __DIR__ . '/../Services/SharePointMappingService.php';
        require_once __DIR__ . '/../Services/SharePointRetentionMapper.php';
        require_once __DIR__ . '/../Services/SharePointIngestAdapter.php';
        require_once __DIR__ . '/../Repositories/SharePointTenantRepository.php';
        require_once __DIR__ . '/../Repositories/SharePointDriveRepository.php';
        require_once __DIR__ . '/../Repositories/SharePointEventRepository.php';

        $graph = new \AtomExtensions\SharePoint\Services\GraphClientService();
        $tenants = new \AtomExtensions\SharePoint\Repositories\SharePointTenantRepository();
        $drives = new \AtomExtensions\SharePoint\Repositories\SharePointDriveRepository();
        $events = new \AtomExtensions\SharePoint\Repositories\SharePointEventRepository();
        $mapping = new \AtomExtensions\SharePoint\Services\SharePointMappingService();
        $retention = new \AtomExtensions\SharePoint\Services\SharePointRetentionMapper();

        $adapter = new \AtomExtensions\SharePoint\Services\SharePointIngestAdapter(
            $graph, $tenants, $drives, $events, $mapping, $retention,
        );

        $status = $adapter->ingest($eventId);
        $this->logSection('sharepoint', "event {$eventId} -> {$status}");
    }
}
