<?php

/**
 * Embedded-metadata PII scan (Heratio #751 parity).
 *
 * Scans EXIF/IPTC already extracted into digital_object_metadata and
 * dam_iptc_metadata for GPS coordinates, people and creator contact details,
 * recording findings in ahg_pii_finding_embedded for privacy review.
 *
 * Usage:
 *   php symfony privacy:scan-embedded                 # scan all extracted metadata
 *   php symfony privacy:scan-embedded --id=123        # scan one information object
 *   php symfony privacy:scan-embedded --limit=500     # cap source rows
 *
 * @author Johan Pieterse - The Archive and Heritage Group
 */
class privacyScanEmbeddedTask extends arBaseTask
{
    protected function configure()
    {
        $this->addOptions([
            new sfCommandOption('application', null, sfCommandOption::PARAMETER_OPTIONAL, 'The application name', 'qubit'),
            new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'The environment', 'cli'),
            new sfCommandOption('connection', null, sfCommandOption::PARAMETER_REQUIRED, 'The connection name', 'propel'),
            new sfCommandOption('id', null, sfCommandOption::PARAMETER_OPTIONAL, 'Scan a single information object ID'),
            new sfCommandOption('limit', null, sfCommandOption::PARAMETER_OPTIONAL, 'Cap source rows scanned'),
        ]);

        $this->namespace = 'privacy';
        $this->name = 'scan-embedded';
        $this->briefDescription = 'Scan embedded EXIF/IPTC metadata for PII (GPS, people, contacts)';
        $this->detailedDescription = <<<EOF
Scans embedded file metadata (already extracted into digital_object_metadata /
dam_iptc_metadata) for PII and records findings in ahg_pii_finding_embedded.

  php symfony privacy:scan-embedded
  php symfony privacy:scan-embedded --id=123
EOF;
    }

    public function execute($arguments = [], $options = [])
    {
        parent::execute($arguments, $options);

        $this->logSection('pii-embedded', 'Embedded-metadata PII scan');

        require_once sfConfig::get('sf_plugins_dir') . '/ahgPrivacyPlugin/lib/Service/EmbeddedMetadataPiiService.php';
        $service = new \ahgPrivacyPlugin\Service\EmbeddedMetadataPiiService();

        if (!empty($options['id'])) {
            $n = $service->scanInformationObject((int) $options['id']);
            $this->logSection('pii-embedded', sprintf('Information object %d: %d new finding(s).', (int) $options['id'], $n));

            return 0;
        }

        $limit = isset($options['limit']) && $options['limit'] !== null ? (int) $options['limit'] : null;
        $res = $service->scanAll($limit);
        $this->logSection('pii-embedded', sprintf('Scanned %d source row(s); %d new finding(s) recorded.', $res['scanned'], $res['findings']));

        return 0;
    }
}
