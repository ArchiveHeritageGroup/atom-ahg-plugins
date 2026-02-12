<?php

namespace AtomFramework\Console\Commands\Security;

use AtomFramework\Console\BaseCommand;
use Illuminate\Database\Capsule\Manager as DB;

class WatermarkApplyDerivativesCommand extends BaseCommand
{
    protected string $name = 'watermark:apply-derivatives';
    protected string $description = 'Apply watermark to derivative images';
    protected string $detailedDescription = <<<'EOF'
    Apply watermarks to existing derivative images based on security
    classification settings.

    Examples:
      php bin/atom watermark:apply-derivatives                   # Process all
      php bin/atom watermark:apply-derivatives --object-id=123   # Single object
    EOF;

    protected function configure(): void
    {
        $this->addOption('object-id', null, 'Specific object ID to process');
    }

    protected function handle(): int
    {
        $servicePath = $this->getAtomRoot() . '/atom-framework/src/Services/DerivativeWatermarkService.php';
        require_once $servicePath;

        $objectId = $this->option('object-id');

        if ($objectId) {
            // Process single object
            $objectId = (int) $objectId;
            $this->info("Processing object {$objectId}");

            $result = \AtomExtensions\Services\DerivativeWatermarkService::regenerateDerivatives($objectId);
            if ($result) {
                $this->success('Success');
            } else {
                $this->error('Failed');
            }

            return $result ? 0 : 1;
        }

        // Process all objects with watermark settings
        $objects = DB::table('digital_object')
            ->where('usage_id', 140)  // Masters only
            ->whereNotNull('object_id')
            ->pluck('object_id');

        $count = 0;
        foreach ($objects as $oid) {
            $config = \AtomExtensions\Services\DerivativeWatermarkService::getWatermarkConfig($oid);
            if ($config) {
                $this->info("Processing object {$oid} ({$config['type']})");
                \AtomExtensions\Services\DerivativeWatermarkService::regenerateDerivatives($oid);
                ++$count;
            }
        }

        $this->success("Processed {$count} objects");

        return 0;
    }
}
