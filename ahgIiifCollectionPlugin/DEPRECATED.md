# DEPRECATED

This plugin has been merged into **ahgIiifPlugin**.

All IIIF functionality (viewer, manifests, collections) is now consolidated in:

```
plugins/ahgIiifPlugin/
```

## Migration

1. Enable `ahgIiifPlugin` instead of `ahgIiifCollectionPlugin`
2. The database tables and routes remain the same
3. No data migration needed

## To Remove This Plugin

```bash
# Disable in database
php bin/atom extension:disable ahgIiifCollectionPlugin

# Remove symlink
rm /usr/share/nginx/archive/plugins/ahgIiifCollectionPlugin
```
