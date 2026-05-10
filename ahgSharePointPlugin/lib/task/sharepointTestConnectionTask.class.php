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

        // TODO (Phase 1):
        //   1. Resolve tenant via SharePointTenantRepository.
        //   2. GraphClientService::acquireToken().
        //   3. GET /sites?search=* (list 5).
        //   4. Print: tenant name, token expiry, sites returned.
        //   5. On failure: print Graph error code + message + suggestion.

        throw new \RuntimeException('sharepoint:test-connection not implemented yet');
    }
}
