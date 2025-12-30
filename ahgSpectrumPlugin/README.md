# ahgSpectrumPlugin v2.0.0

Comprehensive Spectrum Collections Management Plugin for AtoM.

## Overview

This merged plugin combines core Spectrum functionality with enhanced features for complete collections management compliance.

## Features

### Core Spectrum Features
- **Compliance Dashboard** - Per-procedure overview showing objects with missing steps
- **Procedure Event Logging** - First-class events linked to objects with full audit trail
- **PDF/CSV Export** - Export Spectrum histories for audits and inspections
- **Configurable Templates** - Procedure templates customizable per institution
- **Workflow Management** - Track object status through Spectrum procedures

### Enhanced Features
- **Condition Photography** - Document object conditions with photos
- **Photo Comparison Views** - Before/after and multi-image comparisons
- **Media Player Integration** - Video/audio playback with transcription
- **Metadata Extraction** - Automatic extraction from digital objects
- **Face Detection** - AI-powered face detection in images
- **Bulk Operations** - Process multiple objects simultaneously

## Installation

### 1. Copy Plugin
```bash
cp -r ahgSpectrumPlugin /usr/share/nginx/atom/plugins/
```

### 2. Add Routes
Append `config/routing.yml` contents to your AtoM routing configuration:
```bash
cat plugins/ahgSpectrumPlugin/config/routing.yml >> apps/qubit/config/routing.yml
```

### 3. Install Database Schema
```bash
mysql -u root -p atom < plugins/ahgSpectrumPlugin/schema/ahg_settings_schema.sql
mysql -u root -p atom < plugins/ahgSpectrumPlugin/schema/condition_photos_schema.sql
mysql -u root -p atom < plugins/ahgSpectrumPlugin/schema/metadata_extraction_schema.sql
```

Or use the web installer:
```
https://your-site.com/spectrum/install
```

### 4. Enable Plugin
Add to `apps/qubit/config/settings.yml`:
```yaml
all:
  .settings:
    plugins:
      - ahgSpectrumPlugin
```

### 5. Clear Cache
```bash
php symfony cc
```

## File Structure

```
ahgSpectrumPlugin/
├── ahgSpectrumPluginConfiguration.class.php
├── config/
│   └── routing.yml
├── lib/
│   ├── arSpectrumEventService.class.php      # Event logging service
│   ├── arSpectrumExportService.class.php     # PDF/CSV export
│   ├── arSpectrumInstallService.class.php    # Database installer
│   ├── arSpectrumTemplateService.class.php   # Template management
│   ├── arMetadataExtractionTrait.php         # Metadata extraction
│   ├── components/
│   │   └── SpectrumMediaPlayer.php           # Media player component
│   ├── job/
│   │   ├── arMetadataExtractionJob.class.php
│   │   ├── arSpectrumBulkOperationsJob.class.php
│   │   ├── arSpectrumConditionReportJob.class.php
│   │   └── arSpectrumPhotoProcessingJob.class.php
│   ├── model/
│   │   └── SpectrumConditionPhoto.php
│   └── services/
│       ├── SpectrumPhotoService.php
│       ├── arFaceDetectionService.php
│       └── arUniversalMetadataExtractor.php
├── modules/
│   ├── api/                    # REST API endpoints
│   ├── digitalobject/          # Digital object handling
│   ├── informationobject/      # Multi-file upload
│   ├── object/                 # Object operations
│   ├── settings/               # AHG settings
│   └── spectrum/               # Main spectrum module
│       ├── actions/
│       │   ├── conditionPhotosAction.class.php
│       │   ├── dashboardAction.class.php
│       │   ├── exportAction.class.php
│       │   ├── installAction.class.php
│       │   ├── templateConfigAction.class.php
│       │   └── workflowAction.class.php
│       └── templates/
├── schema/                     # SQL schemas
├── templates/                  # Partial templates
└── web/
    └── css/
        └── spectrum-media.css
```

## API Endpoints

| Endpoint | Method | Description |
|----------|--------|-------------|
| `/api/spectrum/events` | GET, POST | List/create events |
| `/api/spectrum/objects/:id/events` | GET, POST | Object events |
| `/api/spectrum/statistics` | GET | Overall statistics |

## Routes

### Core Routes
- `/spectrum/dashboard` - Compliance dashboard
- `/spectrum/:slug/workflow` - Object workflow
- `/spectrum/export` - Export interface
- `/spectrum/config/templates` - Template configuration
- `/spectrum/install` - Database installer

### Photo Routes
- `/spectrum/:slug/condition/:id/photos` - Condition photos
- `/spectrum/photo/:id/edit` - Edit photo
- `/spectrum/photo/:id/rotate` - Rotate photo

### Admin Routes
- `/admin/ahg-settings` - Plugin settings
- `/admin/spectrum/jobs/*` - Background jobs

## Requirements

- AtoM 2.7+
- PHP 8.0+
- MySQL 5.7+ / MariaDB 10.3+
- ImageMagick (for photo processing)
- FFmpeg (for media player)

## License

AGPL-3.0

## Author

The Archives and Heritage Group (AHG)
https://theahg.co.za
