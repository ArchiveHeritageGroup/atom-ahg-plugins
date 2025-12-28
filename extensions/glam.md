# GLAM/DAM Sector Plugins

Specialized cataloging for Galleries, Libraries, Archives, Museums, and Digital Assets.

---

## Overview

| Plugin | Sector | Standards | Status |
|--------|--------|-----------|--------|
| sfMuseumPlugin | Museums | Spectrum 5.0 | ✅ Stable |
| arLibraryPlugin | Libraries | MARC, RDA | ✅ Stable |
| arGalleryPlugin | Galleries | CCO | ✅ Stable |
| arDAMPlugin | Digital Assets | EXIF, IPTC, XMP | ✅ Stable |

---

## Installation
```bash
# Museum
php bin/atom extension:install sfMuseumPlugin
php bin/atom extension:enable sfMuseumPlugin

# Library
php bin/atom extension:install arLibraryPlugin
php bin/atom extension:enable arLibraryPlugin

# Gallery
php bin/atom extension:install arGalleryPlugin
php bin/atom extension:enable arGalleryPlugin

# DAM (required for DAM module in AHG Theme)
php bin/atom extension:install arDAMPlugin
php bin/atom extension:enable arDAMPlugin

# Clear cache
php symfony cc
```

---

## Combining Sectors
```bash
# Full GLAM Suite
php bin/atom extension:install sfMuseumPlugin arLibraryPlugin arGalleryPlugin arDAMPlugin
```

| Institution Type | Recommended Plugins |
|------------------|---------------------|
| Archive | arAHGThemeB5Plugin |
| Museum | arAHGThemeB5Plugin + sfMuseumPlugin |
| Library | arAHGThemeB5Plugin + arLibraryPlugin |
| Gallery | arAHGThemeB5Plugin + arGalleryPlugin |
| Digital Repository | arAHGThemeB5Plugin + arDAMPlugin |
