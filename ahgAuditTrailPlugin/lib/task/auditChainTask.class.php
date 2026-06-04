<?php

/**
 * Verify (or seal) the ahg_audit_log tamper-evident hash chain (#126).
 *
 *   php symfony audit:chain            # verify the chain
 *   php symfony audit:chain --seal     # (re)seal forward from the current head
 *
 * Exits non-zero when the chain is broken, so it can gate cron / CI.
 */
class auditChainTask extends arBaseTask
{
    protected function configure()
    {
        $this->addOptions([
            new sfCommandOption('application', null, sfCommandOption::PARAMETER_OPTIONAL, 'The application name', 'qubit'),
            new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'The environment', 'cli'),
            new sfCommandOption('seal', null, sfCommandOption::PARAMETER_NONE, 'Seal the chain forward from the current head (one-time init)'),
        ]);

        $this->namespace = 'audit';
        $this->name = 'chain';
        $this->briefDescription = 'Verify (or --seal) the ahg_audit_log tamper-evident hash chain';
    }

    public function execute($arguments = [], $options = [])
    {
        parent::execute($arguments, $options);

        require_once sfConfig::get('sf_root_dir').'/atom-framework/bootstrap.php';
        require_once sfConfig::get('sf_plugins_dir').'/ahgAuditTrailPlugin/lib/Models/AuditLog.php';
        require_once sfConfig::get('sf_plugins_dir').'/ahgAuditTrailPlugin/lib/Services/ChainedAuditWriter.php';

        $writer = '\AtoM\Framework\Plugins\AuditTrail\Services\ChainedAuditWriter';

        if ($options['seal']) {
            $r = $writer::seal();
            $this->logSection('audit', sprintf('Sealed forward from id=%d (genesis %s…).', $r['sealed_from_id'], substr($r['genesis'], 0, 12)));

            return;
        }

        $r = $writer::verifyChain();
        if (!$r['sealed']) {
            $this->logSection('audit', 'Chain not sealed yet — run `audit:chain --seal` first.');

            return;
        }
        if ($r['intact']) {
            $this->logSection('audit', sprintf('OK — chain intact (%d entries verified).', $r['checked']));

            return;
        }

        $this->logSection('audit', sprintf(
            'TAMPERED — break at id=%d: %s (verified %d of %d before the break).',
            $r['broken_id'], $r['reason'], $r['checked'], $r['total']
        ), null, 'ERROR');

        exit(1);
    }
}
