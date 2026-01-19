<?php

use Illuminate\Database\Capsule\Manager as DB;

/**
 * Task to convert digital objects to preservation-safe formats.
 */
class preservationConvertTask extends arBaseTask
{
    protected function configure()
    {
        $this->addOptions([
            new sfCommandOption('application', null, sfCommandOption::PARAMETER_OPTIONAL, 'The application name', 'qubit'),
            new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'The environment', 'cli'),
            new sfCommandOption('object-id', null, sfCommandOption::PARAMETER_OPTIONAL, 'Specific digital object ID to convert'),
            new sfCommandOption('format', null, sfCommandOption::PARAMETER_OPTIONAL, 'Target format (e.g., tiff, pdf-a, wav)'),
            new sfCommandOption('mime-type', null, sfCommandOption::PARAMETER_OPTIONAL, 'Filter by source MIME type'),
            new sfCommandOption('limit', null, sfCommandOption::PARAMETER_OPTIONAL, 'Maximum number of objects to convert', 10),
            new sfCommandOption('dry-run', null, sfCommandOption::PARAMETER_NONE, 'Show what would be converted without converting'),
            new sfCommandOption('status', null, sfCommandOption::PARAMETER_NONE, 'Show conversion tools and statistics'),
            new sfCommandOption('quality', null, sfCommandOption::PARAMETER_OPTIONAL, 'Conversion quality (1-100, where applicable)', 95),
        ]);

        $this->namespace = 'preservation';
        $this->name = 'convert';
        $this->briefDescription = 'Convert digital objects to preservation-safe formats';
        $this->detailedDescription = <<<EOF
Converts digital objects to archival-quality preservation formats using
ImageMagick, FFmpeg, Ghostscript, or LibreOffice.

Examples:
  php symfony preservation:convert --status                    # Show available tools
  php symfony preservation:convert --dry-run                   # Preview conversions
  php symfony preservation:convert --object-id=123 --format=tiff
  php symfony preservation:convert --mime-type=image/jpeg --format=tiff --limit=50

Supported conversions:
  Images: JPEG, PNG, BMP, GIF → TIFF (ImageMagick)
  Audio:  MP3, AAC, OGG → WAV (FFmpeg)
  Video:  Various → MKV/FFV1 (FFmpeg)
  Office: DOC, XLS, PPT → PDF/A (LibreOffice)
  PDF:    PDF → PDF/A (Ghostscript)
EOF;
    }

    protected function execute($arguments = [], $options = [])
    {
        parent::execute($arguments, $options);

        require_once sfConfig::get('sf_root_dir').'/atom-framework/bootstrap.php';
        require_once dirname(__DIR__).'/PreservationService.php';

        $service = new PreservationService();

        // Status check
        if ($options['status']) {
            $this->showStatus($service);

            return;
        }

        $dryRun = !empty($options['dry-run']);
        $limit = (int) ($options['limit'] ?? 10);
        $targetFormat = $options['format'] ?? null;
        $mimeTypeFilter = $options['mime-type'] ?? null;
        $quality = (int) ($options['quality'] ?? 95);

        // Single object conversion
        if (!empty($options['object-id'])) {
            $objectId = (int) $options['object-id'];

            if (!$targetFormat) {
                $this->logSection('convert', 'Error: --format is required when converting a specific object', null, 'ERROR');

                return 1;
            }

            $this->logSection('convert', "Converting digital object ID: $objectId to $targetFormat");

            if ($dryRun) {
                $this->logSection('convert', '[DRY RUN] Would convert object', null, 'COMMENT');

                return;
            }

            $result = $service->convertFormat($objectId, $targetFormat, ['quality' => $quality], 'cli-task');

            if ($result['success']) {
                $this->logSection('convert', 'SUCCESS - Conversion completed', null, 'INFO');
                $this->logSection('convert', "  Output: {$result['output_path']}");
                $this->logSection('convert', "  Size: ".number_format($result['output_size']).' bytes');
            } else {
                $this->logSection('convert', "FAILED - {$result['error']}", null, 'ERROR');
            }

            return;
        }

        // Batch conversion - find objects needing conversion
        $this->logSection('convert', 'Searching for objects to convert...');

        $query = DB::table('digital_object as do')
            ->join('preservation_format as pf', function ($join) {
                $join->on('do.mime_type', '=', 'pf.mime_type');
            })
            ->leftJoin('preservation_format_conversion as pfc', function ($join) {
                $join->on('do.id', '=', 'pfc.digital_object_id')
                    ->where('pfc.status', '=', 'completed');
            })
            ->where('do.usage_id', 140) // Masters only
            ->whereNotNull('pf.migration_target_id')
            ->whereNull('pfc.id'); // Not already converted

        if ($mimeTypeFilter) {
            $query->where('do.mime_type', '=', $mimeTypeFilter);
        }

        $objects = $query
            ->select('do.id', 'do.name', 'do.mime_type', 'do.byte_size', 'pf.migration_target_id')
            ->limit($limit)
            ->get();

        if ($objects->isEmpty()) {
            $this->logSection('convert', 'No objects found requiring conversion');

            return;
        }

        $this->logSection('convert', "Found {$objects->count()} objects to convert".($dryRun ? ' [DRY RUN]' : ''));
        $this->logSection('convert', '');

        $success = 0;
        $failed = 0;

        foreach ($objects as $obj) {
            // Get target format info
            $targetInfo = DB::table('preservation_format')
                ->where('id', $obj->migration_target_id)
                ->first();

            if (!$targetInfo) {
                continue;
            }

            // Determine target format string
            $target = $this->mimeToFormat($targetInfo->mime_type);

            $this->logSection('convert', "Object {$obj->id}: {$obj->name}");
            $this->logSection('convert', "  From: {$obj->mime_type} → To: {$targetInfo->mime_type}");

            if ($dryRun) {
                $this->logSection('convert', '  [WOULD CONVERT]', null, 'COMMENT');

                continue;
            }

            $result = $service->convertFormat($obj->id, $target, ['quality' => $quality], 'cli-task');

            if ($result['success']) {
                $this->logSection('convert', '  SUCCESS', null, 'INFO');
                ++$success;
            } else {
                $this->logSection('convert', "  FAILED: {$result['error']}", null, 'ERROR');
                ++$failed;
            }
        }

        if (!$dryRun) {
            $this->logSection('convert', '');
            $this->logSection('convert', "Conversion complete: $success succeeded, $failed failed");
        }
    }

    protected function showStatus($service)
    {
        $tools = $service->getConversionTools();

        $this->logSection('convert', 'Format Conversion Tools Status');
        $this->logSection('convert', '');

        foreach ($tools as $name => $info) {
            $status = $info['available'] ? 'AVAILABLE' : 'NOT INSTALLED';
            $color = $info['available'] ? 'INFO' : 'COMMENT';

            $this->logSection('convert', "$name: $status", null, $color);
            if ($info['available'] && !empty($info['version'])) {
                $this->logSection('convert', "  Version: {$info['version']}");
            }
            $this->logSection('convert', "  Formats: ".implode(', ', $info['formats']));
        }

        // Show conversion statistics
        $stats = DB::table('preservation_format_conversion')
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        $this->logSection('convert', '');
        $this->logSection('convert', 'Conversion Statistics:');
        $this->logSection('convert', '  Completed: '.($stats['completed'] ?? 0));
        $this->logSection('convert', '  Pending: '.($stats['pending'] ?? 0));
        $this->logSection('convert', '  Processing: '.($stats['processing'] ?? 0));
        $this->logSection('convert', '  Failed: '.($stats['failed'] ?? 0));

        // Count objects needing conversion
        $needsConversion = DB::table('digital_object as do')
            ->join('preservation_format as pf', 'do.mime_type', '=', 'pf.mime_type')
            ->leftJoin('preservation_format_conversion as pfc', function ($join) {
                $join->on('do.id', '=', 'pfc.digital_object_id')
                    ->where('pfc.status', '=', 'completed');
            })
            ->where('do.usage_id', 140)
            ->whereNotNull('pf.migration_target_id')
            ->whereNull('pfc.id')
            ->count();

        $this->logSection('convert', '');
        $this->logSection('convert', "Objects pending conversion: $needsConversion");
    }

    protected function mimeToFormat($mimeType)
    {
        $map = [
            'image/tiff' => 'tiff',
            'audio/wav' => 'wav',
            'audio/x-wav' => 'wav',
            'video/x-matroska' => 'mkv',
            'application/pdf' => 'pdf',
        ];

        return $map[$mimeType] ?? 'tiff';
    }
}
