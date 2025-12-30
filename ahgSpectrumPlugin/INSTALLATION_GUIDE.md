# AtoM Spectrum Enhancements - Installation Guide

## Overview

This package contains four major enhancements for AtoM's Spectrum plugin:

1. **Condition Report Photos** - Attach and manage photos for condition checks
2. **Enhanced Media Player** - Advanced video/audio playback with custom controls
3. **AtoM Job Tasks** - Background processing for thumbnails, reports, and bulk operations
4. **AHG Theme Settings** - Centralized settings management in the admin menu

---

## Prerequisites

- AtoM 2.6+ with ahgSpectrumPlugin installed
- PHP 7.4+ with GD extension (for image processing)
- MySQL 5.7+ or MariaDB 10.3+
- Write permissions on the uploads directory

---

## Installation Steps

### Step 1: Run Database Migrations

Execute the SQL schema files to create the required tables:

```bash
# Navigate to your AtoM directory
cd /usr/share/nginx/atom

# Run the condition photos schema
mysql -u root -p atom < /path/to/condition_photos_schema.sql

# Run the AHG settings schema
mysql -u root -p atom < /path/to/ahg_settings_schema.sql
```

### Step 2: Copy Files to Plugin Directory

```bash
# Create necessary directories if they don't exist
sudo mkdir -p plugins/ahgSpectrumPlugin/lib/model
sudo mkdir -p plugins/ahgSpectrumPlugin/lib/services
sudo mkdir -p plugins/ahgSpectrumPlugin/lib/components
sudo mkdir -p plugins/ahgSpectrumPlugin/lib/job
sudo mkdir -p plugins/ahgSpectrumPlugin/modules/spectrum/actions
sudo mkdir -p plugins/ahgSpectrumPlugin/modules/spectrum/templates
sudo mkdir -p plugins/ahgSpectrumPlugin/modules/settings/actions
sudo mkdir -p plugins/ahgSpectrumPlugin/modules/settings/templates
sudo mkdir -p plugins/ahgSpectrumPlugin/web/css
sudo mkdir -p plugins/ahgSpectrumPlugin/web/js

# Copy model files
sudo cp lib/model/SpectrumConditionPhoto.php \
    plugins/ahgSpectrumPlugin/lib/model/

# Copy service files
sudo cp lib/services/SpectrumPhotoService.php \
    plugins/ahgSpectrumPlugin/lib/services/

# Copy component files
sudo cp lib/components/SpectrumMediaPlayer.php \
    plugins/ahgSpectrumPlugin/lib/components/

# Copy job files
sudo cp lib/job/*.php \
    plugins/ahgSpectrumPlugin/lib/job/

# Copy action files
sudo cp modules/spectrum/actions/conditionPhotosAction.class.php \
    plugins/ahgSpectrumPlugin/modules/spectrum/actions/

sudo cp modules/settings/actions/ahgSettingsAction.class.php \
    plugins/ahgSpectrumPlugin/modules/settings/actions/

# Copy template files
sudo cp modules/spectrum/templates/conditionPhotosSuccess.php \
    plugins/ahgSpectrumPlugin/modules/spectrum/templates/

sudo cp modules/settings/templates/ahgSettingsSuccess.php \
    plugins/ahgSpectrumPlugin/modules/settings/templates/

# Copy CSS files
sudo cp web/css/spectrum-media.css \
    plugins/ahgSpectrumPlugin/web/css/
```

### Step 3: Update Plugin Routing

Add the routes from `config/routing.yml` to your plugin's routing configuration:

```bash
# Edit the routing file
sudo nano plugins/ahgSpectrumPlugin/config/routing.yml

# Append the contents of the provided routing.yml
```

### Step 4: Add Settings Menu Link

Edit your AtoM theme's admin menu to include the AHG Settings link. 

For the AHG theme, edit `plugins/arAhgThemePlugin/templates/_header.php`:

```php
<?php if ($sf_user->isAdministrator()): ?>
  <li>
    <a href="<?php echo url_for(['module' => 'settings', 'action' => 'ahgSettings']); ?>">
      <i class="fas fa-cogs"></i> AHG Settings
    </a>
  </li>
<?php endif; ?>
```

Or add to the admin dropdown menu in the navigation.

### Step 5: Create Upload Directory

```bash
# Create the condition photos upload directory
sudo mkdir -p uploads/spectrum/condition_photos
sudo mkdir -p uploads/spectrum/reports

# Set permissions
sudo chown -R www-data:www-data uploads/spectrum
sudo chmod -R 755 uploads/spectrum
```

### Step 6: Clear Cache and Restart Services

```bash
# Clear Symfony cache
sudo -u www-data php symfony cc

# Restart PHP-FPM
sudo systemctl restart php8.1-fpm

# Restart nginx
sudo systemctl restart nginx
```

---

## Configuration

### Accessing Settings

After installation, access the settings at:
```
https://your-atom-instance.com/admin/ahg-settings
```

Or navigate to: **Admin > AHG Settings**

### Settings Sections

| Section | Description |
|---------|-------------|
| General | Theme colors, logo, branding |
| Spectrum | Collections management defaults |
| Media | Video/audio player settings |
| Photos | Upload limits, thumbnail sizes |
| Data Protection | POPIA/GDPR compliance settings |
| IIIF | Image viewer configuration |
| Jobs | Background processing settings |

---

## Using Condition Photos

### Adding Photos to a Condition Check

1. Navigate to an object's Spectrum page
2. Click on an existing condition check or create a new one
3. Click **"Manage Photos"** button
4. Drag & drop photos or click to browse
5. Select photo type (Before, After, Detail, Damage, Overall)
6. Enter optional caption and metadata
7. Click **Upload**

### Photo Types

| Type | Use Case |
|------|----------|
| Before | Pre-treatment documentation |
| After | Post-treatment documentation |
| Detail | Close-up of specific area |
| Damage | Documenting damage |
| Overall | Full object view |
| Other | Any other documentation |

### Creating Before/After Comparisons

1. Upload at least one "Before" and one "After" photo
2. Scroll to "Create New Comparison" section
3. Select the before and after photos
4. Add optional title and notes
5. Click **Create Comparison**

The comparison creates an interactive slider for side-by-side viewing.

---

## Using the Enhanced Media Player

### In Templates

```php
<?php
$player = new SpectrumMediaPlayer('/uploads/video.mp4', [
    'autoplay' => false,
    'controls' => true,
    'theme' => 'dark'
]);

echo $player->render('my-video-player');
?>
```

### Player Options

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| autoplay | bool | false | Auto-play on load |
| controls | bool | true | Show player controls |
| loop | bool | false | Loop playback |
| volume | float | 0.8 | Default volume (0-1) |
| playerType | string | 'enhanced' | 'basic' or 'enhanced' |
| theme | string | 'dark' | 'dark' or 'light' |
| showDownload | bool | false | Show download button |

---

## Background Jobs

### Available Jobs

1. **Photo Processing Job**
   - Generates thumbnails
   - Extracts EXIF data
   - Can regenerate all thumbnails

2. **Condition Report Job**
   - Generates PDF reports
   - Includes photos and conservation history
   - Supports HTML and DOCX formats

3. **Bulk Operations Job**
   - Regenerate all thumbnails
   - Export condition reports
   - Bulk location updates
   - Calculate statistics
   - Cleanup orphaned photos
   - Archive old records

### Running Jobs Manually

```bash
# Run photo processing for condition check ID 123
php symfony spectrum:process-photos --condition-check-id=123

# Generate PDF report
php symfony spectrum:generate-report --condition-check-id=123 --format=pdf

# Run bulk thumbnail regeneration
php symfony spectrum:bulk --operation=regenerate_thumbnails
```

### Scheduling Jobs (via cron)

```bash
# Add to crontab
0 2 * * * cd /usr/share/nginx/atom && php symfony jobs:worker >> /var/log/atom-jobs.log 2>&1
```

---

## Troubleshooting

### Photos Not Uploading

1. Check PHP upload limits:
   ```bash
   php -i | grep -E "upload_max_filesize|post_max_size"
   ```
   
2. Increase if needed in `/etc/php/8.1/fpm/php.ini`:
   ```ini
   upload_max_filesize = 20M
   post_max_size = 25M
   ```

3. Check directory permissions:
   ```bash
   ls -la uploads/spectrum/
   ```

### Thumbnails Not Generating

1. Verify GD extension is installed:
   ```bash
   php -m | grep gd
   ```

2. Install if missing:
   ```bash
   sudo apt install php-gd
   sudo systemctl restart php8.1-fpm
   ```

### Settings Not Saving

1. Check database permissions
2. Verify table exists:
   ```sql
   SHOW TABLES LIKE 'ahg_settings';
   ```

3. Clear cache:
   ```bash
   sudo -u www-data php symfony cc
   ```

### Jobs Not Running

1. Check job worker is running
2. View job logs in AtoM admin
3. Check PHP error logs

---

## File Structure

```
plugins/ahgSpectrumPlugin/
├── config/
│   └── routing.yml          # Routes for photos, settings, jobs
├── lib/
│   ├── model/
│   │   └── SpectrumConditionPhoto.php
│   ├── services/
│   │   └── SpectrumPhotoService.php
│   ├── components/
│   │   └── SpectrumMediaPlayer.php
│   └── job/
│       ├── arSpectrumPhotoProcessingJob.class.php
│       ├── arSpectrumConditionReportJob.class.php
│       └── arSpectrumBulkOperationsJob.class.php
├── modules/
│   ├── spectrum/
│   │   ├── actions/
│   │   │   └── conditionPhotosAction.class.php
│   │   └── templates/
│   │       └── conditionPhotosSuccess.php
│   └── settings/
│       ├── actions/
│       │   └── ahgSettingsAction.class.php
│       └── templates/
│           └── ahgSettingsSuccess.php
└── web/
    └── css/
        └── spectrum-media.css
```

---

## Support

For issues or feature requests, contact The AHG support team.

**Version:** 1.0.0  
**Last Updated:** December 2025  
**Compatible with:** AtoM 2.6+, 2.7+, 2.8+
