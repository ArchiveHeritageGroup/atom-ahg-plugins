# AHG API Plugin

Enhanced REST API v2 for Access to Memory (AtoM) 2.x

## Features

- **API Key Management** - Create and manage API keys with granular scopes (read/write/delete)
- **Rate Limiting** - Configurable rate limits per API key (requests/hour)
- **Request Logging** - Full request/response logging for analytics and debugging
- **Webhooks** - Real-time notifications for record changes
- **Batch Operations** - Process multiple records in a single request
- **Full Data Export** - Get complete record data with `?full=true` parameter
- **GLAM/DAM Support** - Sector filtering for Archive, Museum, Library, Gallery, DAM

## Requirements

- AtoM 2.8 or higher
- PHP 8.1 or higher
- atom-framework installed and configured

## Installation

### Via AHG Framework (Recommended)
```bash
cd /path/to/atom
php bin/atom extension:enable ahgAPIPlugin
```

### Manual Installation

1. Copy the plugin to your AtoM plugins directory:
```bash
   cp -r ahgAPIPlugin /path/to/atom/plugins/
```

2. Run the database migrations:
```bash
   mysql -u root your_database < plugins/ahgAPIPlugin/data/install.sql
```

3. Enable the plugin in `apps/qubit/config/settings.yml`:
```yaml
   all:
     .settings:
       enabled_modules:
         - apiv2
```

4. Clear the cache:
```bash
   php symfony cc
```

## Configuration

### Creating API Keys

1. Go to **Admin > AHG Settings > API Keys**
2. Click **Create New Key**
3. Select user, scopes, and rate limit
4. Copy the generated key (shown only once!)

### API Key Scopes

| Scope | Permissions |
|-------|-------------|
| `read` | View descriptions, authorities, repositories, taxonomies. Search records. |
| `write` | Create new records and update existing records. |
| `delete` | Delete records permanently. |

## API Endpoints

### Authentication

Include your API key in the request header:
```
X-API-Key: your-api-key-here
```

Or use Bearer token:
```
Authorization: Bearer your-api-key-here
```

### Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v2` | API information |
| GET | `/api/v2/descriptions` | List descriptions |
| GET | `/api/v2/descriptions/:slug` | Get single description |
| GET | `/api/v2/descriptions/:slug?full=true` | Get full description with all related data |
| POST | `/api/v2/descriptions` | Create description |
| PUT | `/api/v2/descriptions/:slug` | Update description |
| DELETE | `/api/v2/descriptions/:slug` | Delete description |
| GET | `/api/v2/authorities` | List authority records |
| GET | `/api/v2/authorities/:slug` | Get single authority |
| GET | `/api/v2/repositories` | List repositories |
| GET | `/api/v2/taxonomies` | List taxonomies |
| GET | `/api/v2/taxonomies/:id/terms` | Get taxonomy terms |
| GET,POST | `/api/v2/search` | Search records |
| POST | `/api/v2/batch` | Batch operations |

### Query Parameters

| Parameter | Description | Default |
|-----------|-------------|---------|
| `limit` | Number of results per page | 10 |
| `skip` | Number of results to skip | 0 |
| `sort` | Sort field | updated |
| `sort_direction` | Sort direction (asc/desc) | desc |
| `repository` | Filter by repository slug | - |
| `level` | Filter by level of description | - |
| `sector` | Filter by sector (archive/museum/library/gallery/dam) | - |
| `full` | Return full data with related records | false |

## Examples

### List Descriptions
```bash
curl -H "X-API-Key: your-key" https://your-atom/api/v2/descriptions
```

### Get Full Description
```bash
curl -H "X-API-Key: your-key" https://your-atom/api/v2/descriptions/my-record?full=true
```

### Filter by Sector
```bash
curl -H "X-API-Key: your-key" https://your-atom/api/v2/descriptions?sector=museum
```

### Search
```bash
curl -H "X-API-Key: your-key" \
  -H "Content-Type: application/json" \
  -d '{"query": "search term"}' \
  https://your-atom/api/v2/search
```

## Response Format

All responses follow this format:
```json
{
    "success": true,
    "data": {
        "total": 100,
        "limit": 10,
        "skip": 0,
        "results": [...]
    }
}
```

Error responses:
```json
{
    "success": false,
    "error": "Unauthorized",
    "message": "Invalid or missing API key"
}
```

## Full Data Response

When using `?full=true`, the response includes:

- Basic fields (id, slug, title, identifier, etc.)
- `dates` - Event dates and date displays
- `digital_objects` - Attached files with thumbnail/master URLs
- `subjects` - Subject access points
- `places` - Place access points
- `names` - Related actors (creators, etc.)
- `notes` - All notes
- `properties` - Custom properties
- `hierarchy` - Ancestor records
- `children_count` - Number of child records

## License

GPL-3.0 - GNU General Public License v3.0

## Author

The Archive and Heritage Group
https://theahg.co.za
