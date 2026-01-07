# GLAM/DAM Sector Plugins

Specialized cataloging for Galleries, Libraries, Archives, Museums, and Digital Assets.

---

## Overview

| Plugin | Sector | Standards | Status |
|--------|--------|-----------|--------|
| sfMuseumPlugin | Museums | Spectrum 5.0 | ✅ Stable |
| ahgLibraryPlugin | Libraries | MARC, RDA | ✅ Stable |
| ahgGalleryPlugin | Galleries | CCO | ✅ Stable |
| ahgDAMPlugin | Digital Assets | EXIF, IPTC, XMP | ✅ Stable |

---

## Installation
```bash
# Museum
php bin/atom extension:install sfMuseumPlugin
php bin/atom extension:enable sfMuseumPlugin

# Library
php bin/atom extension:install ahgLibraryPlugin
php bin/atom extension:enable ahgLibraryPlugin

# Gallery
php bin/atom extension:install ahgGalleryPlugin
php bin/atom extension:enable ahgGalleryPlugin

# DAM (required for DAM module in AHG Theme)
php bin/atom extension:install ahgDAMPlugin
php bin/atom extension:enable ahgDAMPlugin

# Clear cache
php symfony cc
```

---

## Combining Sectors
```bash
# Full GLAM Suite
php bin/atom extension:install sfMuseumPlugin ahgLibraryPlugin ahgGalleryPlugin ahgDAMPlugin
```

| Institution Type | Recommended Plugins |
|------------------|---------------------|
| Archive | arAHGThemeB5Plugin |
| Museum | arAHGThemeB5Plugin + sfMuseumPlugin |
| Library | arAHGThemeB5Plugin + ahgLibraryPlugin |
| Gallery | arAHGThemeB5Plugin + ahgGalleryPlugin |
| Digital Repository | arAHGThemeB5Plugin + ahgDAMPlugin |
