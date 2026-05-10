<?php

/**
 * sharepoint:renew-subscriptions — cron task (hourly).
 *
 * Finds subs expiring within 12h and PATCHes them to extend.
 *
 * @phase 2.A
 */
class sharepointRenewSubscriptionsTask extends sfBaseTask
{
    protected function configure()
    {
        $this->addOptions([
            new sfCommandOption('application', null, sfCommandOption::PARAMETER_OPTIONAL, 'The application name', true),
            new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'The environment', 'cli'),
            new sfCommandOption('connection', null, sfCommandOption::PARAMETER_REQUIRED, 'The connection name', 'propel'),
        ]);

        $this->namespace = 'sharepoint';
        $this->name = 'renew-subscriptions';
        $this->briefDescription = 'Renew Graph webhook subscriptions expiring within 12h';
    }

    protected function execute($arguments = [], $options = [])
    {
        require_once __DIR__ . '/../Services/GraphClientService.php';
        require_once __DIR__ . '/../Services/GraphTokenCache.php';
        require_once __DIR__ . '/../Services/SharePointSubscriptionService.php';
        require_once __DIR__ . '/../Repositories/SharePointTenantRepository.php';
        require_once __DIR__ . '/../Repositories/SharePointDriveRepository.php';
        require_once __DIR__ . '/../Repositories/SharePointSubscriptionRepository.php';

        $graph = new \AtomExtensions\SharePoint\Services\GraphClientService();
        $tenants = new \AtomExtensions\SharePoint\Repositories\SharePointTenantRepository();
        $drives = new \AtomExtensions\SharePoint\Repositories\SharePointDriveRepository();
        $subs = new \AtomExtensions\SharePoint\Repositories\SharePointSubscriptionRepository();
        $svc = new \AtomExtensions\SharePoint\Services\SharePointSubscriptionService(
            $graph, $tenants, $drives, $subs,
        );

        $result = $svc->renewExpiring();
        $this->logSection('sharepoint', "renewed={$result['renewed']} errors={$result['errors']}");
    }
}
