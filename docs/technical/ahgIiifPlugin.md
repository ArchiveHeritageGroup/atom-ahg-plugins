# ahgIiifPlugin - Technical Documentation

## Overview

The ahgIiifPlugin provides comprehensive IIIF (International Image Interoperability Framework) capabilities for AtoM, including manifest generation, deep zoom viewing, collection management, and authentication (IIIF Auth API 1.0). The plugin supports images, PDFs, multi-page TIFFs, 3D models, and audio/video content.

**Version:** 1.1.0
**Category:** Media/Viewing
**Dependencies:** atom >= 2.8.0, PHP >= 8.1, atom-framework

**Optional Dependencies:**
- Cantaloupe (IIIF Image Server for deep zoom)
- OpenSeadragon (JavaScript viewer)
- Mirador (Multi-window viewer)
- model-viewer (3D model rendering)

---

## Architecture

### Component Diagram

```
+-------------------------------------------------------------------------+
|                           ahgIiifPlugin                                  |
+-------------------------------------------------------------------------+
|                                                                          |
|  +-------------------------------------------------------------------+  |
|  |                      PRESENTATION LAYER                            |  |
|  +-------------------------------------------------------------------+  |
|  |  +----------+ +----------+ +----------+ +----------+ +----------+ |  |
|  |  | Settings | |Collection| |  Auth    | |Clickthru | |  Logout  | |  |
|  |  | Template | | Template | |  Admin   | | Template | | Template | |  |
|  |  +----------+ +----------+ +----------+ +----------+ +----------+ |  |
|  +-------------------------------------------------------------------+  |
|                                    |                                     |
|                                    v                                     |
|  +-------------------------------------------------------------------+  |
|  |                      CONTROLLER LAYER                              |  |
|  +-------------------------------------------------------------------+  |
|  |  +-------------------+  +-------------------+  +-----------------+ |  |
|  |  | ahgIiifActions    |  |ahgIiifCollection  |  |ahgIiifAuthActions| |  |
|  |  |-------------------|  |Actions            |  |-----------------| |  |
|  |  | manifest()        |  |-------------------|  | login()         | |  |
|  |  | manifestById()    |  | index()           |  | token()         | |  |
|  |  | settings()        |  | create()          |  | logout()        | |  |
|  |  |                   |  | edit()            |  | confirm()       | |  |
|  |  |                   |  | delete()          |  | check()         | |  |
|  |  |                   |  | manifest()        |  | protect()       | |  |
|  |  +-------------------+  +-------------------+  +-----------------+ |  |
|  +-------------------------------------------------------------------+  |
|                                    |                                     |
|                                    v                                     |
|  +-------------------------------------------------------------------+  |
|  |                       SERVICE LAYER                                |  |
|  +-------------------------------------------------------------------+  |
|  |  +---------------------------+  +---------------------------+      |  |
|  |  | IiifManifestService       |  | IiifAuthService           |      |  |
|  |  | (atom-framework)          |  | (ahgIiifPlugin)           |      |  |
|  |  |---------------------------|  |---------------------------|      |  |
|  |  | generateObjectManifest()  |  | checkAccess()             |      |  |
|  |  | generateImageManifest()   |  | requestToken()            |      |  |
|  |  | generateCollectionManifest|  | validateCurrentToken()    |      |  |
|  |  | getImageDimensions()      |  | logout()                  |      |  |
|  |  | createImageCanvas()       |  | setObjectAuth()           |      |  |
|  |  | createPdfCanvases()       |  | removeObjectAuth()        |      |  |
|  |  | create3DCanvas()          |  | getServiceDescription()   |      |  |
|  |  | createAVCanvas()          |  | cleanupExpiredTokens()    |      |  |
|  |  +---------------------------+  +---------------------------+      |  |
|  +-------------------------------------------------------------------+  |
|                                    |                                     |
|                                    v                                     |
|  +-------------------------------------------------------------------+  |
|  |                       HELPER LAYER                                 |  |
|  +-------------------------------------------------------------------+  |
|  |                     IiifViewerHelper.php                           |  |
|  |  +---------------------------------------------------------------+ |  |
|  |  | renderIiifViewer()    | getManifestUrl()     | getViewerType()| |  |
|  |  | render3DViewer()      | getCantaloupeUrl()   | getViewerHeight| |  |
|  |  +---------------------------------------------------------------+ |  |
|  +-------------------------------------------------------------------+  |
|                                    |                                     |
|                                    v                                     |
|  +-------------------------------------------------------------------+  |
|  |                       DATA LAYER                                   |  |
|  +-------------------------------------------------------------------+  |
|  |         Illuminate\Database\Capsule\Manager (Laravel QB)          |  |
|  +-------------------------------------------------------------------+  |
|                                                                          |
+-------------------------------------------------------------------------+
```

---

## Database Schema

### Entity Relationship Diagram

```
+-------------------------------------------------------------------------+
|                    IIIF PLUGIN DATABASE SCHEMA                           |
+-------------------------------------------------------------------------+

                    +------------------------+
                    |   information_object   |
                    |    (AtoM Core Table)   |
                    +------------------------+
                    | PK id                  |
                    |    repository_id       |
                    |    ...                 |
                    +------------------------+
                              ^
                              |
         +--------------------+--------------------+
         |                    |                    |
         v                    v                    v
+-------------------+  +-------------------+  +-------------------+
| iiif_collection_  |  | iiif_annotation   |  | iiif_auth_        |
|       item        |  +-------------------+  |     resource      |
+-------------------+  | PK id             |  +-------------------+
| PK id             |  | FK object_id -----+->| PK id             |
| FK collection_id  |  |    canvas_id      |  | FK object_id -----+
| FK object_id -----+  |    target_canvas  |  | FK service_id     |
|    manifest_uri   |  |    target_selector|  |    apply_to_      |
|    item_type      |  |    motivation     |  |      children     |
|    label          |  |    created_by     |  |    degraded_      |
|    sort_order     |  |    created_at     |  |      access       |
+-------------------+  +-------------------+  |    degraded_width |
         |                    |              |    notes           |
         v                    v              +-------------------+
+-------------------+  +-------------------+          |
| iiif_collection   |  |iiif_annotation_   |          v
+-------------------+  |       body        |  +-------------------+
| PK id             |  +-------------------+  | iiif_auth_service |
|    name           |  | PK id             |  +-------------------+
|    slug           |  | FK annotation_id  |  | PK id             |
|    description    |  |    body_type      |  |    name           |
|    attribution    |  |    body_value     |  |    profile        |
|    logo_url       |  |    body_format    |  |    label          |
|    thumbnail_url  |  |    body_language  |  |    description    |
|    viewing_hint   |  +-------------------+  |    confirm_label  |
|    parent_id      |                         |    failure_header |
|    is_public      |                         |    failure_desc   |
+-------------------+                         |    login_url      |
         |                                    |    logout_url     |
         v                                    |    token_ttl      |
+-------------------+                         |    is_active      |
|iiif_collection_   |                         +-------------------+
|       i18n        |                                  |
+-------------------+                                  |
| PK id             |                                  v
| FK collection_id  |                         +-------------------+
|    culture        |                         | iiif_auth_token   |
|    name           |                         +-------------------+
|    description    |                         | PK id             |
+-------------------+                         |    token_hash     |
                                              | FK user_id        |
                                              | FK service_id     |
+-------------------+                         |    session_id     |
|  iiif_ocr_text    |                         |    ip_address     |
+-------------------+                         |    user_agent     |
| PK id             |                         |    issued_at      |
| FK digital_object_|                         |    expires_at     |
|       id          |                         |    last_used_at   |
| FK object_id      |                         |    is_revoked     |
|    full_text      |                         +-------------------+
|    format         |
|    language       |                         +-------------------+
|    confidence     |                         |iiif_auth_access_  |
+-------------------+                         |       log         |
         |                                    +-------------------+
         v                                    | PK id             |
+-------------------+                         | FK object_id      |
|  iiif_ocr_block   |                         | FK user_id        |
+-------------------+                         | FK token_id       |
| PK id             |                         |    action         |
| FK ocr_id         |                         |    ip_address     |
|    page_number    |                         |    user_agent     |
|    block_type     |                         |    details (JSON) |
|    text           |                         |    created_at     |
|    x, y, w, h     |                         +-------------------+
|    confidence     |
+-------------------+                         +-------------------+
                                              |iiif_auth_         |
+-------------------+                         |   repository      |
|iiif_viewer_       |                         +-------------------+
|   settings        |                         | PK id             |
+-------------------+                         | FK repository_id  |
| PK id             |                         | FK service_id     |
|    setting_key    |                         |    degraded_access|
|    setting_value  |                         |    degraded_width |
|    description    |                         +-------------------+
+-------------------+
```

---

## IIIF Auth API 1.0 Implementation

### Authentication Flow Diagram

```
+----------+                                              +----------+
|  IIIF    |                                              |   AtoM   |
|  Client  |                                              |  Server  |
| (Mirador)|                                              |          |
+----+-----+                                              +----+-----+
     |                                                         |
     |  1. Request Manifest                                    |
     |-------------------------------------------------------->|
     |                                                         |
     |  2. Manifest with Auth Service Description              |
     |<--------------------------------------------------------|
     |                                                         |
     |  3. Open Login Window (popup)                           |
     |  +-----------------+                                    |
     |  | /iiif/auth/     |                                    |
     |  | login/:service  |----------------------------------->|
     |  +-----------------+                                    |
     |                                                         |
     |                         4a. Clickthrough: Show Terms    |
     |                    <------------------------------------|
     |                                                         |
     |                         4b. Login: Redirect to AtoM     |
     |                    <------------------------------------|
     |                                                         |
     |  5. User Authenticates/Agrees                           |
     |  +-----------------+                                    |
     |  | POST /iiif/auth/|                                    |
     |  | confirm/:service|----------------------------------->|
     |  +-----------------+                                    |
     |                                                         |
     |                         6. Set Cookie, Close Window     |
     |                    <------------------------------------|
     |                                                         |
     |  7. Token Request (JSONP)                               |
     |  +-----------------+                                    |
     |  | /iiif/auth/     |                                    |
     |  | token/:service  |----------------------------------->|
     |  +-----------------+                                    |
     |                                                         |
     |                         8. Access Token Response        |
     |                    <------------------------------------|
     |                                                         |
     |  9. Request Image with Token                            |
     |-------------------------------------------------------->|
     |                                                         |
     |                         10. Full Resolution Image       |
     |<--------------------------------------------------------|
     |                                                         |
```

### Auth Profiles

| Profile | Description | Use Case |
|---------|-------------|----------|
| `login` | Requires AtoM authentication | Registered users only |
| `clickthrough` | User agrees to terms | Public with acknowledgment |
| `kiosk` | Location-based access | On-premises terminals |
| `external` | External auth provider | SSO integration |

### Manifest with Auth Service

```json
{
  "@context": "http://iiif.io/api/presentation/3/context.json",
  "id": "https://example.org/iiif/manifest/abc123",
  "type": "Manifest",
  "label": {"en": ["Protected Resource"]},
  "service": [
    {
      "@context": "http://iiif.io/api/auth/1/context.json",
      "@id": "https://example.org/iiif/auth/login/login",
      "profile": "http://iiif.io/api/auth/1/login",
      "label": "Login Required",
      "header": "Authentication Required",
      "description": "Please log in to access this resource.",
      "confirmLabel": "Login",
      "failureHeader": "Authentication Failed",
      "failureDescription": "You must be logged in to view this content.",
      "service": [
        {
          "@id": "https://example.org/iiif/auth/token/login",
          "profile": "http://iiif.io/api/auth/1/token"
        },
        {
          "@id": "https://example.org/iiif/auth/logout/login",
          "profile": "http://iiif.io/api/auth/1/logout",
          "label": "Logout"
        }
      ]
    }
  ]
}
```

---

## API Reference

### Routes

#### Manifest Routes

| Method | Route | Action | Description |
|--------|-------|--------|-------------|
| GET | `/iiif/manifest/:slug` | manifest | Get IIIF manifest by slug |
| GET | `/iiif/manifest/id/:id` | manifestById | Get IIIF manifest by ID |

#### Collection Routes

| Method | Route | Action | Description |
|--------|-------|--------|-------------|
| GET | `/manifest-collections` | index | List all collections |
| GET | `/manifest-collection/new` | new | Create collection form |
| POST | `/manifest-collection/create` | create | Create collection |
| GET | `/manifest-collection/:id/edit` | edit | Edit collection form |
| PUT | `/manifest-collection/:id/update` | update | Update collection |
| DELETE | `/manifest-collection/:id/delete` | delete | Delete collection |
| GET | `/manifest-collection/:slug/manifest.json` | manifest | Get collection manifest |

#### Auth Routes (IIIF Auth API 1.0)

| Method | Route | Action | Description |
|--------|-------|--------|-------------|
| GET | `/iiif/auth/login/:service` | login | Interactive login |
| GET | `/iiif/auth/token/:service` | token | Request access token |
| GET | `/iiif/auth/logout/:service` | logout | Revoke token |
| POST | `/iiif/auth/confirm/:service` | confirm | Confirm clickthrough |
| GET | `/iiif/auth/check/:id` | check | Check access for object |

#### Admin Routes

| Method | Route | Action | Description |
|--------|-------|--------|-------------|
| GET | `/admin/iiif-settings` | settings | Viewer settings |
| GET | `/admin/iiif-auth` | index | Auth admin panel |
| POST | `/admin/iiif-auth/protect` | protect | Protect resource |
| POST | `/admin/iiif-auth/unprotect` | unprotect | Remove protection |

---

## IiifAuthService Methods

### Access Control

```php
/**
 * Check if access is allowed for an object.
 *
 * @param int $objectId Information object ID
 * @param int|null $userId Current user ID (null for anonymous)
 * @return array ['allowed' => bool, 'degraded' => bool, 'service' => ?array]
 */
public function checkAccess(int $objectId, ?int $userId = null): array

/**
 * Get auth resource configuration for an object.
 * Checks: direct assignment → repository level → parent hierarchy
 */
protected function getAuthResourceForObject(int $objectId): ?object
```

### Token Management

```php
/**
 * Request access token.
 *
 * @param string $serviceName Service identifier
 * @param int|null $userId Authenticated user ID
 * @param string|null $messageId Client message ID for CORS
 * @return array Token response or error
 */
public function requestToken(string $serviceName, ?int $userId = null, ?string $messageId = null): array

/**
 * Validate current request's token.
 * Checks cookie and Authorization header.
 */
public function validateCurrentToken(?int $serviceId = null): ?object

/**
 * Revoke token and clear cookie.
 */
public function logout(): bool

/**
 * Cleanup expired and revoked tokens.
 */
public function cleanupExpiredTokens(): int
```

### Resource Protection

```php
/**
 * Set auth requirement for an object.
 *
 * @param int $objectId Information object ID
 * @param string $serviceName Service name (login, public, restricted)
 * @param array $options [apply_to_children, degraded_access, degraded_width, notes]
 */
public function setObjectAuth(int $objectId, string $serviceName, array $options = []): bool

/**
 * Remove auth requirement from an object.
 */
public function removeObjectAuth(int $objectId, ?string $serviceName = null): bool
```

---

## Viewer Settings

Settings are stored in `iiif_viewer_settings` table:

| Key | Default | Description |
|-----|---------|-------------|
| `viewer_type` | mirador | openseadragon, mirador, carousel, single |
| `viewer_height` | 500px | Viewer container height |
| `background_color` | #b1aaaa | Viewer background |
| `enable_fullscreen` | 1 | Show fullscreen button |
| `show_zoom_controls` | 1 | Show zoom buttons |
| `carousel_autoplay` | 1 | Auto-rotate carousel |
| `carousel_interval` | 5000 | Rotation interval (ms) |
| `homepage_collection_enabled` | 1 | Show on homepage |
| `homepage_collection_id` | null | Featured collection ID |

---

## Integration with atom-framework

The plugin uses services from atom-framework for manifest generation:

```
atom-framework/src/Extensions/IiifViewer/
├── Services/
│   ├── IiifManifestService.php    # IIIF 3.0 manifest generation
│   ├── AnnotationService.php       # W3C Web Annotations
│   ├── OcrService.php              # OCR text overlays
│   ├── TranscriptionService.php    # Transcription management
│   └── ViewerService.php           # Viewer configuration
├── Controllers/
│   └── IiifController.php          # Direct API access
└── Helpers/
    └── IiifViewerHelper.php        # Template helpers
```

---

## Security Considerations

### Token Security

- Tokens are hashed with SHA-256 before storage
- Cookies use `HttpOnly`, `Secure`, and `SameSite=None` flags
- Token TTL configurable per service (default: 1 hour)
- Expired tokens automatically cleaned up

### Access Inheritance

```
Repository Level
    └── Fonds/Collection (apply_to_children=true)
        └── Series (inherits protection)
            └── File (inherits protection)
                └── Item (inherits protection)
```

### Degraded Access

When `degraded_access=true`:
- Thumbnails accessible without authentication
- Full resolution requires valid token
- Configurable max width for degraded images

---

## CLI Commands

Currently, auth management is via admin UI. Future CLI commands planned:

```bash
# Protect a resource
php symfony iiif:protect --object=12345 --service=login

# List protected resources
php symfony iiif:list-protected

# Cleanup expired tokens
php symfony iiif:cleanup-tokens
```

---

## Troubleshooting

### Common Issues

| Issue | Cause | Solution |
|-------|-------|----------|
| Token not accepted | Cookie blocked by browser | Check SameSite settings |
| Auth popup blocked | Browser popup blocker | Whitelist domain |
| 403 on images | Missing Cantaloupe config | Check nginx proxy |
| Manifest returns 404 | Missing digital object | Verify object has media |

### Debug Logging

```php
// Enable in IiifAuthService
error_log('IIIF Auth: ' . json_encode([
    'action' => 'checkAccess',
    'object_id' => $objectId,
    'result' => $result
]));
```

---

## Version History

| Version | Date | Changes |
|---------|------|---------|
| 1.1.0 | 2025-01-24 | Added IIIF Auth API 1.0 support |
| 1.0.0 | 2025-01-15 | Initial release with manifests and collections |

---

*Part of the AtoM AHG Framework*
