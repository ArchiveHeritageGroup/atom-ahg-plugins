# ar3DModelPlugin for AtoM

A comprehensive 3D model viewing plugin for Access to Memory (AtoM) with IIIF 3D extension support, augmented reality (AR) capabilities, and interactive hotspot annotations.

## Features

- **Multiple 3D Formats**: Support for GLB, glTF, OBJ, STL, FBX, PLY, and USDZ formats
- **Google Model Viewer**: Uses Google's `<model-viewer>` component for high-quality WebGL rendering
- **Augmented Reality**: AR viewing on iOS (Quick Look) and Android (Scene Viewer)
- **3D Hotspots**: Interactive annotation points with customizable types (info, damage, detail, link)
- **IIIF 3D Manifests**: Generates IIIF 3D-compliant manifests for interoperability
- **Three.js Fallback**: Alternative viewer for older browsers
- **Responsive Design**: Works on desktop, tablet, and mobile devices
- **Bootstrap 5 Integration**: Seamless integration with AtoM's Bootstrap 5 themes

## Requirements

- AtoM 2.9+ or 2.10
- PHP 8.1+
- MySQL 5.7+ or MariaDB 10.3+
- atom-framework (Laravel Query Builder)
- HTTPS (required for AR features)

## Installation

### 1. Copy Plugin Files

```bash
cd /usr/share/nginx/archive/plugins
cp -r /path/to/ar3DModelPlugin .
```

### 2. Copy Service File

```bash
cp ar3DModelPlugin/services/Model3DService.php \
   /usr/share/nginx/archive/atom-framework/src/Services/
```

### 3. Install Database Schema

```bash
mysql -u root -p archive < ar3DModelPlugin/schema/install.sql
```

### 4. Create Upload Directory

```bash
mkdir -p /usr/share/nginx/archive/uploads/3d
chown -R www-data:www-data /usr/share/nginx/archive/uploads/3d
chmod 755 /usr/share/nginx/archive/uploads/3d
```

### 5. Clear Cache

```bash
php /usr/share/nginx/archive/symfony cc
```

### 6. Enable Plugin

Add to `apps/qubit/config/settings.yml`:

```yaml
all:
  .settings:
    plugins:
      - ar3DModelPlugin
```

Or enable via Admin > Plugins in AtoM.

## Configuration

### Global Settings

Navigate to **Admin > 3D Viewer Settings** to configure:

| Setting | Description | Default |
|---------|-------------|---------|
| Default Viewer | model-viewer or threejs | model-viewer |
| Enable AR | Allow AR viewing | Yes |
| Enable Fullscreen | Show fullscreen button | Yes |
| Allow Downloads | Let users download models | No |
| Max File Size | Upload size limit (MB) | 100 |
| Allowed Formats | Permitted file types | glb, gltf, usdz |
| Enable Hotspots | Allow 3D annotations | Yes |
| Auto-Rotate | Rotate models by default | Yes |
| Rotation Speed | Degrees per second | 30 |

### NGINX Configuration

Add MIME types for 3D files:

```nginx
types {
    model/gltf-binary glb;
    model/gltf+json gltf;
    model/vnd.usdz+zip usdz;
}
```

Increase upload limits if needed:

```nginx
client_max_body_size 100M;
```

## Usage

### Display 3D Models in Templates

Include the partial in your object template (e.g., `indexSuccess.php`):

```php
<?php include_partial('ar3DModel/model3dViewer', ['resource' => $resource]); ?>
```

### Using Helper Functions

```php
// Include helper
include_once sfConfig::get('sf_plugins_dir') . '/ar3DModelPlugin/lib/helper/Model3DHelper.php';

// Check if object has 3D models
if (has_3d_model($resource)) {
    // Render primary model
    echo render_3d_model($resource, ['height' => '500px']);
    
    // Or render gallery of all models
    echo render_3d_model_gallery($resource);
}

// Render upload button (editors only)
echo render_3d_upload_button($resource);

// Get IIIF 3D manifest URL
$manifestUrl = get_iiif_3d_manifest_url($modelId);
```

### API Endpoints

| Endpoint | Method | Description |
|----------|--------|-------------|
| `/index.php/ar3DModel/index` | GET | List all 3D models |
| `/index.php/ar3DModel/view/:id` | GET | View a single model |
| `/index.php/ar3DModel/upload?object_id=:id` | GET/POST | Upload new model |
| `/index.php/ar3DModel/edit/:id` | GET/POST | Edit model settings |
| `/index.php/ar3DModel/delete/:id` | POST | Delete a model |
| `/index.php/ar3DModel/embed/:id` | GET | Embedded viewer (iframe) |
| `/iiif/3d/:id/manifest.json` | GET | IIIF 3D manifest |
| `/api/3d/models/:object_id` | GET | JSON API for models |
| `/api/3d/hotspots/:model_id` | GET | JSON API for hotspots |

### IIIF 3D Manifest

Each 3D model has an IIIF 3D manifest at:

```
https://your-atom-instance/iiif/3d/{model_id}/manifest.json
```

Example manifest structure:

```json
{
  "@context": [
    "http://iiif.io/api/presentation/3/context.json",
    "http://iiif.io/api/extension/3d/context.json"
  ],
  "id": "https://example.com/iiif/3d/1/manifest.json",
  "type": "Manifest",
  "label": { "en": ["Bronze Statue"] },
  "items": [
    {
      "id": "https://example.com/iiif/3d/1/scene/1",
      "type": "Scene",
      "items": [
        {
          "id": "https://example.com/iiif/3d/1/annotation/1",
          "type": "Annotation",
          "motivation": "painting",
          "body": {
            "id": "https://example.com/uploads/3d/1/model.glb",
            "type": "Model",
            "format": "model/gltf-binary"
          }
        }
      ]
    }
  ]
}
```

## Hotspot Types

| Type | Color | Use Case |
|------|-------|----------|
| annotation | Blue (#1a73e8) | General notes |
| info | Green (#34a853) | Information points |
| damage | Red (#ea4335) | Condition documentation |
| detail | Yellow (#fbbc04) | Highlight features |
| link | Blue (#4285f4) | External links |

## Database Schema

### Tables

- `object_3d_model` - Main model records
- `object_3d_model_i18n` - Translations
- `object_3d_hotspot` - Annotation points
- `object_3d_hotspot_i18n` - Hotspot translations
- `object_3d_texture` - Associated textures
- `viewer_3d_settings` - Global configuration
- `iiif_3d_manifest` - Cached manifests
- `object_3d_audit_log` - Activity tracking

## Recommended 3D Formats

| Format | Pros | Cons |
|--------|------|------|
| **GLB** | ✅ Recommended. Single file, embedded textures, fast loading | Larger file size |
| **glTF** | Industry standard, flexible | Multiple files |
| **USDZ** | Required for iOS AR | iOS only |
| **OBJ** | Universal support | No animations |
| **STL** | 3D printing standard | No textures |

### Converting Models

Use tools like:
- [Blender](https://www.blender.org/) (free)
- [glTF Pipeline](https://github.com/CesiumGS/gltf-pipeline)
- [Reality Composer](https://developer.apple.com/augmented-reality/tools/) (USDZ for iOS)

```bash
# Convert using gltf-pipeline
gltf-pipeline -i model.gltf -o model.glb
```

## Troubleshooting

### Models Not Loading

1. Check browser console for errors
2. Verify file exists in uploads directory
3. Check MIME types in nginx configuration
4. Ensure CORS headers are set for cross-origin requests

### AR Not Working

1. AR requires HTTPS
2. Check device compatibility:
   - iOS 12+ Safari
   - Android 7+ Chrome
3. Verify model format (GLB/GLTF for Android, USDZ for iOS)

### Performance Issues

1. Optimize models before upload (reduce polygons)
2. Use GLB format with compressed textures
3. Enable model-viewer's built-in loading optimization

## File Size Guidelines

| Model Type | Recommended Size |
|------------|-----------------|
| Simple objects | < 5 MB |
| Detailed objects | 5-20 MB |
| High-fidelity scans | 20-50 MB |
| Maximum allowed | 100 MB (configurable) |

## Integration with Condition Reporting

The 3D hotspots integrate with the arConditionPlugin for damage documentation:

```php
// Add damage hotspot from condition report
$service->addHotspot($modelId, [
    'hotspot_type' => 'damage',
    'title' => 'Surface crack',
    'description' => 'Visible crack on base, 5cm length',
    'position_x' => 0.5,
    'position_y' => 0.1,
    'position_z' => 0.3,
]);
```

## Changelog

### Version 1.0.0
- Initial release
- Google Model Viewer integration
- AR support (iOS Quick Look, Android Scene Viewer)
- 3D hotspot annotations
- IIIF 3D manifest generation
- Bootstrap 5 admin interface
- Three.js fallback viewer

## License

GPL-3.0 - See LICENSE file for details.

## Author

Johan Pieterse  
The Archive and Heritage Group  
https://theahg.co.za

## Credits

- [Google Model Viewer](https://modelviewer.dev/)
- [Three.js](https://threejs.org/)
- [IIIF 3D Technical Specification Group](https://iiif.io/community/groups/3d/)
