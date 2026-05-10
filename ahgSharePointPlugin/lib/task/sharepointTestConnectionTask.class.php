<?php

/**
 * sharepoint:test-connection — acquire token + list sites for a configured tenant.
 *
 * Fail-fast diagnostics. Used from admin UI "Test connection" button and from
 * shell during initial config.
 *
 * @phase 1
 */
class sharepointTestConnectionTask extends sfBaseTask
{
    protected function configure()
    {
        $this->addOptions([
            new sfCommandOption('application', null, sfCommandOption::PARAMETER_OPTIONAL, 'The application name', true),
            new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'The environment', 'cli'),
            new sfCommandOption('connection', null, sfCommandOption::PARAMETER_REQUIRED, 'The connection name', 'propel'),
            new sfCommandOption('tenant', null, sfCommandOption::PARAMETER_REQUIRED, 'sharepoint_tenant.id'),
        ]);

        $this->namespace = 'sharepoint';
        $this->name = 'test-connection';
        $this->briefDescription = 'Test Microsoft Graph connectivity for a configured tenant';
    }

    protected function execute($arguments = [], $options = [])
    {
        if (empty($options['tenant'])) {
            throw new \InvalidArgumentException('--tenant=<id> required');
        }
        $tenantId = (int) $options['tenant'];

        require_once __DIR__ . '/../Services/GraphTokenCache.php';
        require_once __DIR__ . '/../Services/GraphClientService.php';
        require_once __DIR__ . '/../Repositories/SharePointTenantRepository.php';

        $tenants = new \AtomExtensions\SharePoint\Repositories\SharePointTenantRepository();
        $tenant = $tenants->find($tenantId);
        if ($tenant === null) {
            throw new \RuntimeException("Tenant {$tenantId} not found");
        }
        $this->logSection('sharepoint', "testing tenant: {$tenant->name} ({$tenant->tenant_id})");

        $graph = new \AtomExtensions\SharePoint\Services\GraphClientService();

        // Step 1 — token
        $token = $graph->acquireToken($tenantId);
        $this->logSection('sharepoint', 'token acquired (length ' . strlen($token) . ')');

        // Step 2 — list a few sites
        try {
            $sites = $graph->get($tenantId, '/sites?search=*&$top=5');
            $count = count($sites['value'] ?? []);
            $this->logSection('sharepoint', "GET /sites returned {$count} site(s)");
            foreach (($sites['value'] ?? []) as $site) {
                $this->log(sprintf('  - %s (%s)', $site['displayName'] ?? '?', $site['webUrl'] ?? '?'));
            }
        } catch (\Throwable $e) {
            throw new \RuntimeException('GET /sites failed: ' . $e->getMessage(), 0, $e);
        }

        $this->logSection('sharepoint', 'connection OK');
    }
}
