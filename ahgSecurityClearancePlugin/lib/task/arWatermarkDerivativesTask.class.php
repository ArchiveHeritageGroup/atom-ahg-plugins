<?php

/**
 * Task to apply watermarks to existing derivatives.
 */
class arWatermarkDerivativesTask extends arBaseTask
{
    protected function configure()
    {
        $this->addOptions([
            new sfCommandOption('application', null, sfCommandOption::PARAMETER_OPTIONAL, 'The application name', 'qubit'),
            new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'The environment', 'cli'),
            new sfCommandOption('object-id', null, sfCommandOption::PARAMETER_OPTIONAL, 'Specific object ID to process'),
        ]);

        $this->namespace = 'watermark';
        $this->name = 'apply-derivatives';
        $this->briefDescription = 'Apply watermarks to derivative images';
    }

    protected function execute($arguments = [], $options = [])
    {
        parent::execute($arguments, $options);

        require_once sfConfig::get('sf_root_dir').'/atom-framework/bootstrap.php';
        require_once sfConfig::get('sf_root_dir').'/atom-framework/src/Services/DerivativeWatermarkService.php';

        if (!empty($options['object-id'])) {
            // Process single object
            $objectId = (int) $options['object-id'];
            $this->logSection('watermark', "Processing object $objectId");

            $result = \AtomExtensions\Services\DerivativeWatermarkService::regenerateDerivatives($objectId);
            $this->logSection('watermark', $result ? 'Success' : 'Failed');

            return;
        }

        // Process all objects with watermark settings
        $objects = \Illuminate\Database\Capsule\Manager::table('digital_object')
            ->where('usage_id', 140)  // Masters only
            ->whereNotNull('object_id')
            ->pluck('object_id');

        $count = 0;
        foreach ($objects as $objectId) {
            $config = \AtomExtensions\Services\DerivativeWatermarkService::getWatermarkConfig($objectId);
            if ($config) {
                $this->logSection('watermark', "Processing object $objectId ({$config['type']})");
                \AtomExtensions\Services\DerivativeWatermarkService::regenerateDerivatives($objectId);
                ++$count;
            }
        }

        $this->logSection('watermark', "Processed $count objects");
    }
}
