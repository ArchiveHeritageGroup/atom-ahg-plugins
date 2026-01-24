# DEPRECATED

This plugin has been merged into **ahgPreservationPlugin**.

TIFF/PDF merge functionality is now part of the comprehensive preservation suite in:

```
plugins/ahgPreservationPlugin/
```

## Migration

1. Enable `ahgPreservationPlugin` instead of `ahgTiffPdfMergePlugin`
2. The database tables and routes remain the same
3. No data migration needed

## To Remove This Plugin

```bash
# Disable in database
php bin/atom extension:disable ahgTiffPdfMergePlugin

# Remove symlink
rm /usr/share/nginx/archive/plugins/ahgTiffPdfMergePlugin
```
