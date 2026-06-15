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
            new sfCommandOption('keygen', null, sfCommandOption::PARAMETER_NONE, 'Mint the Ed25519 audit-signing keypair (enables cryptographic seal)'),
            new sfCommandOption('force', null, sfCommandOption::PARAMETER_NONE, 'With --keygen, replace an existing keypair'),
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
        require_once sfConfig::get('sf_plugins_dir').'/ahgAuditTrailPlugin/lib/Services/AuditSigner.php';
        require_once sfConfig::get('sf_plugins_dir').'/ahgAuditTrailPlugin/lib/Services/ChainedAuditWriter.php';

        $writer = '\AtoM\Framework\Plugins\AuditTrail\Services\ChainedAuditWriter';

        if ($options['keygen']) {
            $signer = new \AtoM\Framework\Plugins\AuditTrail\Services\AuditSigner();
            $kid = $signer->generateKeypair((bool) $options['force']);
            $this->logSection('audit', sprintf('Minted audit-signing keypair %s in %s', $kid, $signer->keyDir()));
            $this->logSection('audit', 'New audit entries are now Ed25519-sealed. Existing rows stay chained-but-unsigned.');

            return;
        }

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
            if (!empty($r['signed'])) {
                $this->logSection('audit', sprintf(
                    'Seal: %d signed, %d signatures verified, %d failed%s.',
                    $r['signed'], $r['sig_verified'], $r['sig_failed'],
                    $r['sig_failed'] ? ' (first at id=' . $r['first_sig_fail_id'] . ' — possible tampering or rotated key)' : ''
                ));
            } else {
                $this->logSection('audit', 'Seal: not enabled (run `audit:chain --keygen` to cryptographically sign new entries).');
            }

            return;
        }

        $this->logSection('audit', sprintf(
            'TAMPERED — break at id=%d: %s (verified %d of %d before the break).',
            $r['broken_id'], $r['reason'], $r['checked'], $r['total']
        ), null, 'ERROR');

        exit(1);
    }
}
