<?php

/**
 * Verify the tamper-evident hash chain of the security access log (#126).
 *
 * Exits non-zero when the chain is broken, so it can gate cron / CI.
 *
 *   php symfony security:audit-verify
 */
class securityAuditVerifyTask extends arBaseTask
{
    protected function configure()
    {
        $this->addOptions([
            new sfCommandOption('application', null, sfCommandOption::PARAMETER_OPTIONAL, 'The application name', 'qubit'),
            new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'The environment', 'cli'),
        ]);

        $this->namespace = 'security';
        $this->name = 'audit-verify';
        $this->briefDescription = 'Verify the tamper-evident hash chain of the security access log';
        $this->detailedDescription = 'Walks security_access_log in insertion order and recomputes every SHA-256 link, detecting any altered, deleted or inserted entry.';
    }

    public function execute($arguments = [], $options = [])
    {
        parent::execute($arguments, $options);

        require_once sfConfig::get('sf_root_dir').'/atom-framework/bootstrap.php';
        require_once sfConfig::get('sf_plugins_dir').'/ahgSecurityClearancePlugin/lib/Services/SecurityClearanceService.php';

        $r = SecurityClearanceService::verifyAuditChain();

        if ($r['intact']) {
            $this->logSection('audit', sprintf('OK — chain intact (%d of %d entries verified).', $r['checked'], $r['total']));

            return;
        }

        $this->logSection('audit', sprintf(
            'TAMPERED — break at entry id=%d: %s (verified %d of %d before the break).',
            $r['broken_id'], $r['reason'], $r['checked'], $r['total']
        ), null, 'ERROR');

        exit(1);
    }
}
