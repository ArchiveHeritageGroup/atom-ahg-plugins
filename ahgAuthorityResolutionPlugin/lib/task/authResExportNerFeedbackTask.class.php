<?php

/**
 * authResExportNerFeedbackTask - Symfony 1.4 task for AtoM Heratio
 *
 * Task 9 retraining-export driver. Writes accumulated ahg_ner_feedback
 * rows (training_exported = 0) out as JSONL or CoNLL-2003 to a dated file
 * under /usr/share/nginx/archive/uploads/auth-res/ner-feedback/, then
 * flips training_exported = 1 + exported_at on the rows shipped.
 *
 * Usage:
 *   php symfony auth-res:export-ner-feedback
 *   php symfony auth-res:export-ner-feedback --format=conll
 *
 * Operator wires to cron weekly when retraining cadence is steady.
 *
 * Copyright (C) 2026 Johan Pieterse
 * Plain Sailing Information Systems
 * Email: johan@plainsailingisystems.co.za
 *
 * Licensed under the GNU General Public License v3.0 or later.
 */

require_once __DIR__ . '/../Services/NerFeedbackService.php';

use AtomFramework\Services\AuthorityResolution\NerFeedbackService;

class authResExportNerFeedbackTask extends arBaseTask
{
    protected function configure()
    {
        $this->addOptions([
            new sfCommandOption('application', null, sfCommandOption::PARAMETER_OPTIONAL, 'Application name', 'qubit'),
            new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'Environment', 'cli'),
            new sfCommandOption('connection', null, sfCommandOption::PARAMETER_REQUIRED, 'Connection', 'propel'),
            new sfCommandOption('format', null, sfCommandOption::PARAMETER_REQUIRED, 'Output format: jsonl or conll', 'jsonl'),
        ]);

        $this->namespace = 'auth-res';
        $this->name = 'export-ner-feedback';
        $this->briefDescription = 'Export rejected-mention feedback as a training corpus (JSONL or CoNLL).';
        $this->detailedDescription = <<<EOF
Task 9 of the AHG Authority Resolution Engine. Drains every unexported
ahg_ner_feedback row (training_exported = 0) to a dated file under
/usr/share/nginx/archive/uploads/auth-res/ner-feedback/, then flips
training_exported = 1 + exported_at = NOW() on the shipped rows.

Falls back to /tmp/ahg-auth-res-ner-feedback/ when the uploads path is
read-only.

Usage:
  php symfony auth-res:export-ner-feedback
  php symfony auth-res:export-ner-feedback --format=conll
EOF;
    }

    public function execute($arguments = [], $options = [])
    {
        parent::execute($arguments, $options);

        $format = isset($options['format']) && $options['format'] !== ''
            ? (string) $options['format']
            : 'jsonl';

        $service = new NerFeedbackService();
        $result = $service->exportUnexported($format);

        if (empty($result['ok'])) {
            $this->logSection('auth-res', 'Export failed: ' . ($result['error'] ?? 'unknown'), null, 'ERROR');
            return 1;
        }

        if ((int) $result['count'] === 0) {
            $this->logSection('auth-res', 'No unexported feedback rows. Nothing to do.');
            return 0;
        }

        $this->logSection('auth-res', sprintf(
            'Exported %d row(s) (%s) to %s',
            (int) $result['count'],
            $result['format'],
            $result['path']
        ));
        return 0;
    }
}
