<?php

/**
 * sharepoint:subscribe — create Graph webhook subscriptions for a drive.
 *
 * Creates BOTH driveItem and list subscriptions (per Phase 2 spike outcome,
 * plan §6.4): two rows in sharepoint_subscription per drive.
 *
 * @phase 2.A
 */
class sharepointSubscribeTask extends sfBaseTask
{
    protected function configure()
    {
        $this->addOptions([
            new sfCommandOption('application', null, sfCommandOption::PARAMETER_OPTIONAL, 'The application name', true),
            new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'The environment', 'cli'),
            new sfCommandOption('connection', null, sfCommandOption::PARAMETER_REQUIRED, 'The connection name', 'propel'),
            new sfCommandOption('drive', null, sfCommandOption::PARAMETER_REQUIRED, 'sharepoint_drive.id'),
            new sfCommandOption('webhook-url', null, sfCommandOption::PARAMETER_OPTIONAL, 'Public webhook URL (defaults to ahg_settings sharepoint.webhook_public_url)'),
        ]);

        $this->namespace = 'sharepoint';
        $this->name = 'subscribe';
        $this->briefDescription = 'Create Graph webhook subscriptions (driveItem + list) for a drive';
    }

    protected function execute($arguments = [], $options = [])
    {
        if (empty($options['drive'])) {
            throw new \InvalidArgumentException('--drive=<id> required');
        }
        $driveId = (int) $options['drive'];
        $webhookUrl = $options['webhook-url'] ?: $this->resolveWebhookUrl();

        // Lazy DI — Symfony 1.x doesn't autowire namespaced plugin services.
        require_once __DIR__ . '/../Services/GraphClientService.php';
        require_once __DIR__ . '/../Services/GraphTokenCache.php';
        require_once __DIR__ . '/../Services/SharePointSubscriptionService.php';
        require_once __DIR__ . '/../Repositories/SharePointTenantRepository.php';
        require_once __DIR__ . '/../Repositories/SharePointDriveRepository.php';
        require_once __DIR__ . '/../Repositories/SharePointSubscriptionRepository.php';

        $cache = new \AtomExtensions\SharePoint\Services\GraphTokenCache();
        $graph = new \AtomExtensions\SharePoint\Services\GraphClientService();
        $tenants = new \AtomExtensions\SharePoint\Repositories\SharePointTenantRepository();
        $drives = new \AtomExtensions\SharePoint\Repositories\SharePointDriveRepository();
        $subs = new \AtomExtensions\SharePoint\Repositories\SharePointSubscriptionRepository();
        $svc = new \AtomExtensions\SharePoint\Services\SharePointSubscriptionService(
            $graph, $tenants, $drives, $subs,
        );

        $result = $svc->subscribeDrive($driveId, $webhookUrl);
        $this->logSection('sharepoint', "subscribed drive {$driveId}: driveItem sub={$result['drive_item']}, list sub={$result['list']}");
    }

    private function resolveWebhookUrl(): string
    {
        $row = \Illuminate\Database\Capsule\Manager::table('ahg_settings')
            ->where('setting_group', 'sharepoint')
            ->where('setting_key', 'webhook_public_url')
            ->first();
        if ($row === null || empty($row->setting_value)) {
            throw new \RuntimeException('No webhook URL configured. Pass --webhook-url=<url> or set ahg_settings sharepoint.webhook_public_url.');
        }
        return (string) $row->setting_value;
    }
}
