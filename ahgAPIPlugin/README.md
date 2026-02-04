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

### Webhook Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v2/webhooks` | List user's webhooks |
| POST | `/api/v2/webhooks` | Create a webhook |
| GET | `/api/v2/webhooks/:id` | Get webhook details |
| PUT | `/api/v2/webhooks/:id` | Update webhook |
| DELETE | `/api/v2/webhooks/:id` | Delete webhook |
| GET | `/api/v2/webhooks/:id/deliveries` | Get delivery logs |
| POST | `/api/v2/webhooks/:id/regenerate-secret` | Regenerate secret |

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

## Webhooks

Webhooks allow you to receive real-time notifications when records are created, updated, or deleted.

### Supported Events

| Event | Description |
|-------|-------------|
| `item.created` | New record created |
| `item.updated` | Existing record updated |
| `item.deleted` | Record deleted |
| `item.published` | Record published |
| `item.unpublished` | Record unpublished |

### Supported Entity Types

| Entity Type | Description |
|-------------|-------------|
| `informationobject` | Archival descriptions |
| `actor` | Authority records |
| `repository` | Repositories |
| `accession` | Accessions |
| `term` | Taxonomy terms |

### Creating a Webhook

```bash
curl -X POST "https://your-atom/api/v2/webhooks" \
  -H "X-API-Key: your-key" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "My Webhook",
    "url": "https://example.com/webhook",
    "events": ["item.created", "item.updated"],
    "entity_types": ["informationobject"]
  }'
```

Response includes a `secret` - store this securely as it won't be shown again.

### Webhook Payload

When an event occurs, your URL receives a POST request:

```json
{
  "event": "item.created",
  "entity_type": "informationobject",
  "entity_id": 12345,
  "timestamp": "2024-01-15T10:30:00+00:00",
  "delivery_id": 1,
  "data": {
    "id": 12345,
    "slug": "my-record",
    "title": "My Record Title",
    "action": "created"
  }
}
```

### HMAC Signature Verification

Each webhook request includes an `X-Webhook-Signature` header containing an HMAC SHA-256 signature.

**PHP Verification Example:**
```php
$payload = file_get_contents('php://input');
$signature = $_SERVER['HTTP_X_WEBHOOK_SIGNATURE'];
$secret = 'your-webhook-secret';

$expected = 'sha256=' . hash_hmac('sha256', $payload, $secret);

if (!hash_equals($expected, $signature)) {
    http_response_code(401);
    exit('Invalid signature');
}

// Process the webhook...
```

### Retry Logic

Failed deliveries are retried with exponential backoff:

| Attempt | Delay |
|---------|-------|
| 1 | Immediate |
| 2 | 60 seconds |
| 3 | 2 minutes |
| 4 | 4 minutes |
| 5 | 8 minutes |

After 5 failed attempts, the delivery is marked as failed.

### Processing Retries (Cron)

Add to crontab to process pending retries:

```bash
*/5 * * * * cd /path/to/atom && php symfony api:webhook-process-retries >> /var/log/atom/webhooks.log 2>&1
```

Optional cleanup of old deliveries:
```bash
php symfony api:webhook-process-retries --cleanup=30
```

### Delivery Logs

View delivery history:
```bash
curl "https://your-atom/api/v2/webhooks/1/deliveries" \
  -H "X-API-Key: your-key"
```

## License

GPL-3.0 - GNU General Public License v3.0

## Author

The Archive and Heritage Group
https://theahg.co.za
