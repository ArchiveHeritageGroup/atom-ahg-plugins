<?php

use AtomFramework\Http\Controllers\AhgController;

/*
 * Auto-Update settings (issue #72).
 *
 * Config + status + CLI page. Because php-fpm runs under ProtectSystem=full
 * (the code tree under /usr is read-only for the worker), the web app cannot
 * run git itself — so this page SAVES the schedule/notify config, DISPLAYS the
 * last-run status that bin/auto-update records, and SHOWS the exact cron line
 * and CLI commands the admin runs. The hardened engine lives in
 * atom-framework/bin/auto-update.
 *
 * Config is written to <root>/log/auto-update.conf (sourced by the bash script)
 * and mirrored to the AtoM settings table for the record.
 */
class SettingsAutoUpdateAction extends AhgController
{
    public function execute($request)
    {
        if (!$this->getUser()->isAuthenticated()) {
            $this->redirect('user/login');
        }
        if (!$this->getUser()->isAdministrator()) {
            $this->forward('admin', 'secure');
        }

        $root = $this->config('sf_root_dir');
        $logDir = $root . '/log';
        $confFile = $logDir . '/auto-update.conf';
        $stateFile = $logDir . '/auto-update.state';

        // Dynamic props (-> __set -> templateVars -> reach the blade).
        $this->atomRoot = $root;
        $this->frameworkPath = $root . '/atom-framework';
        $this->logFile = $logDir . '/auto-update.log';
        $this->confFile = $confFile;

        if ($request->isMethod('post')) {
            $enabled = $request->getParameter('auto_update_enabled') ? '1' : '0';
            $frequency = ('weekly' === $request->getParameter('auto_update_frequency')) ? 'weekly' : 'daily';
            $email = trim((string) $request->getParameter('auto_update_notify_email'));

            $conf = "# Managed by Admin > AHG Settings > Auto-Update\n"
                . "AUTO_UPDATE_ENABLED={$enabled}\n"
                . "AUTO_UPDATE_FREQUENCY={$frequency}\n"
                . "AUTO_UPDATE_NOTIFY_EMAIL={$email}\n";

            if (!is_dir($logDir)) {
                @mkdir($logDir, 0775, true);
            }
            $written = @file_put_contents($confFile, $conf);

            // Mirror to the settings table for the record (non-fatal).
            try {
                $svc = \AtomFramework\Services\Write\WriteServiceFactory::settings();
                $svc->save('auto_update_enabled', $enabled, 'auto_update');
                $svc->save('auto_update_frequency', $frequency, 'auto_update');
                $svc->save('auto_update_notify_email', $email, 'auto_update');
            } catch (\Throwable $e) {
                // Settings mirror is best-effort; the conf file is authoritative.
            }

            if (false === $written) {
                $this->getUser()->setFlash('error', 'Settings saved to database, but ' . $confFile . ' is not writable — the cron script will use defaults until that path is writable.');
            } else {
                $this->getUser()->setFlash('notice', 'Auto-update settings saved.');
            }

            $this->redirect(['module' => 'ahgSettings', 'action' => 'autoUpdate']);
        }

        // GET — load config (conf file is authoritative for the script).
        $config = $this->parseKeyValueFile($confFile) + [
            'AUTO_UPDATE_ENABLED' => '1',
            'AUTO_UPDATE_FREQUENCY' => 'daily',
            'AUTO_UPDATE_NOTIFY_EMAIL' => '',
        ];
        $this->autoUpdateConfig = $config;
        $this->autoUpdateState = $this->parseKeyValueFile($stateFile);
        $this->confExists = is_file($confFile);

        $schedule = ('weekly' === $config['AUTO_UPDATE_FREQUENCY']) ? '0 2 * * 0' : '0 2 * * *';
        $this->cronLine = $schedule . ' root cd ' . $this->frameworkPath
            . ' && bash bin/auto-update --cron >> ' . $this->logFile . ' 2>&1';
    }

    /**
     * Parse a KEY=VALUE file (conf/state) into an associative array.
     */
    private function parseKeyValueFile(string $path): array
    {
        $out = [];
        if (is_file($path) && is_readable($path)) {
            foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
                if ('' === $line || '#' === $line[0] || false === strpos($line, '=')) {
                    continue;
                }
                [$k, $v] = explode('=', $line, 2);
                $out[trim($k)] = trim($v);
            }
        }

        return $out;
    }
}
