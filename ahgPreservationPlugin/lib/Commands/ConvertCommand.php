<?php

namespace AtomFramework\Console\Commands\Preservation;

use AtomFramework\Console\BaseCommand;
use Illuminate\Database\Capsule\Manager as DB;

class ConvertCommand extends BaseCommand
{
    protected string $name = 'preservation:convert';
    protected string $description = 'Convert digital objects to preservation-safe formats';
    protected string $detailedDescription = <<<'EOF'
Converts digital objects to archival-quality preservation formats using
ImageMagick, FFmpeg, Ghostscript, or LibreOffice.

Examples:
  php bin/atom preservation:convert --status                    # Show available tools
  php bin/atom preservation:convert --dry-run                   # Preview conversions
  php bin/atom preservation:convert --object-id=123 --format=tiff
  php bin/atom preservation:convert --mime-type=image/jpeg --format=tiff --limit=50

Supported conversions:
  Images: JPEG, PNG, BMP, GIF -> TIFF (ImageMagick)
  Audio:  MP3, AAC, OGG -> WAV (FFmpeg)
  Video:  Various -> MKV/FFV1 (FFmpeg)
  Office: DOC, XLS, PPT -> PDF/A (LibreOffice)
  PDF:    PDF -> PDF/A (Ghostscript)
EOF;

    protected function configure(): void
    {
        $this->addOption('object-id', null, 'Specific digital object ID to convert');
        $this->addOption('format', 'f', 'Target format (e.g., tiff, pdf-a, wav)');
        $this->addOption('mime-type', null, 'Filter by source MIME type');
        $this->addOption('limit', 'l', 'Maximum number of objects to convert', '10');
        $this->addOption('dry-run', null, 'Show what would be converted without converting');
        $this->addOption('status', 's', 'Show conversion tools and statistics');
        $this->addOption('quality', 'q', 'Conversion quality (1-100, where applicable)', '95');
    }

    protected function handle(): int
    {
        require_once dirname(__DIR__) . '/PreservationService.php';

        $service = new \PreservationService();

        // Status check
        if ($this->hasOption('status')) {
            $this->showStatus($service);

            return 0;
        }

        $dryRun = $this->hasOption('dry-run');
        $limit = (int) $this->option('limit', '10');
        $targetFormat = $this->hasOption('format') ? $this->option('format') : null;
        $mimeTypeFilter = $this->hasOption('mime-type') ? $this->option('mime-type') : null;
        $quality = (int) $this->option('quality', '95');

        // Single object conversion
        if ($this->hasOption('object-id')) {
            $objectId = (int) $this->option('object-id');

            if (!$targetFormat) {
                $this->error('Error: --format is required when converting a specific object');

                return 1;
            }

            $this->info("Converting digital object ID: $objectId to $targetFormat");

            if ($dryRun) {
                $this->comment('[DRY RUN] Would convert object');

                return 0;
            }

            $result = $service->convertFormat($objectId, $targetFormat, ['quality' => $quality], 'cli-task');

            if ($result['success']) {
                $this->success('Conversion completed');
                $this->line("  Output: {$result['output_path']}");
                $this->line('  Size: ' . number_format($result['output_size']) . ' bytes');
            } else {
                $this->error("FAILED - {$result['error']}");
            }

            return 0;
        }

        // Batch conversion - find objects needing conversion
        $this->info('Searching for objects to convert...');

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
            $this->info('No objects found requiring conversion');

            return 0;
        }

        $this->info("Found {$objects->count()} objects to convert" . ($dryRun ? ' [DRY RUN]' : ''));
        $this->newline();

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

            $this->line("Object {$obj->id}: {$obj->name}");
            $this->line("  From: {$obj->mime_type} -> To: {$targetInfo->mime_type}");

            if ($dryRun) {
                $this->comment('  [WOULD CONVERT]');

                continue;
            }

            $result = $service->convertFormat($obj->id, $target, ['quality' => $quality], 'cli-task');

            if ($result['success']) {
                $this->success("  Converted");
                ++$success;
            } else {
                $this->error("  FAILED: {$result['error']}");
                ++$failed;
            }
        }

        if (!$dryRun) {
            $this->newline();
            $this->bold("Conversion complete: $success succeeded, $failed failed");
        }

        return 0;
    }

    private function showStatus(\PreservationService $service): void
    {
        $tools = $service->getConversionTools();

        $this->bold('Format Conversion Tools Status');
        $this->newline();

        foreach ($tools as $name => $info) {
            if ($info['available']) {
                $this->success("$name: AVAILABLE");
            } else {
                $this->comment("$name: NOT INSTALLED");
            }
            if ($info['available'] && !empty($info['version'])) {
                $this->line("  Version: {$info['version']}");
            }
            $this->line('  Formats: ' . implode(', ', $info['formats']));
        }

        // Show conversion statistics
        $stats = DB::table('preservation_format_conversion')
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        $this->newline();
        $this->info('Conversion Statistics:');
        $this->line('  Completed: ' . ($stats['completed'] ?? 0));
        $this->line('  Pending: ' . ($stats['pending'] ?? 0));
        $this->line('  Processing: ' . ($stats['processing'] ?? 0));
        $this->line('  Failed: ' . ($stats['failed'] ?? 0));

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

        $this->newline();
        $this->line("Objects pending conversion: $needsConversion");
    }

    private function mimeToFormat(string $mimeType): string
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
