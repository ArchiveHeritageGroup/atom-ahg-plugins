# Getting Started with AtoM AHG API

This guide will help you get started with the AtoM AHG Framework REST API v2. Whether you're building a web application, mobile app, or integrating with other systems, this guide covers everything you need to know.

## Overview

The AtoM AHG API provides programmatic access to archival records, museum collections, and heritage assets. It supports:

- **Descriptions**: Archival records (fonds, series, files, items)
- **Authorities**: Name records (persons, organizations, families)
- **Repositories**: Holding institutions
- **Search**: Full-text and faceted search
- **Conditions**: SPECTRUM-compliant condition assessments
- **Assets**: GRAP 103/IPSAS 45 heritage asset management
- **Privacy**: GDPR/POPIA compliance (DSARs, breaches)
- **Batch Operations**: Up to 100 operations per request
- **Sync**: Mobile/offline data synchronization

## Base URL

All API requests are made to:

```
https://your-atom-instance.com/api/v2
```

Replace `your-atom-instance.com` with your AtoM installation URL.

## Authentication

The API uses API keys for authentication. Include your key in the `X-API-Key` header:

```bash
curl -H "X-API-Key: your-api-key" \
     https://your-atom-instance.com/api/v2/descriptions
```

### Getting an API Key

1. Log in to your AtoM instance as an administrator
2. Navigate to **Admin > API Keys**
3. Click **Generate New Key**
4. Copy the generated key (it won't be shown again)

### API Key Scopes

Keys can have different permission levels:

| Scope | Permissions |
|-------|-------------|
| `read` | List and view records |
| `write` | Create and update records |
| `delete` | Delete records |
| `admin` | Manage API keys and settings |

## Quick Start Examples

### List Descriptions

```bash
curl -H "X-API-Key: your-api-key" \
     "https://your-atom-instance.com/api/v2/descriptions?limit=10"
```

Response:
```json
{
  "success": true,
  "data": {
    "results": [
      {
        "slug": "example-fonds",
        "title": "Example Fonds",
        "level_of_description": "Fonds",
        "repository": "Main Archive"
      }
    ],
    "total": 150,
    "limit": 10,
    "skip": 0
  }
}
```

### Get a Specific Record

```bash
curl -H "X-API-Key: your-api-key" \
     "https://your-atom-instance.com/api/v2/descriptions/example-fonds"
```

### Search

```bash
curl -H "X-API-Key: your-api-key" \
     "https://your-atom-instance.com/api/v2/search?query=heritage&sector=museum"
```

### Create a Record

```bash
curl -X POST \
     -H "X-API-Key: your-api-key" \
     -H "Content-Type: application/json" \
     -d '{"title": "New Collection", "level_of_description_id": 220}' \
     "https://your-atom-instance.com/api/v2/descriptions"
```

## Response Format

All API responses follow this structure:

### Success Response
```json
{
  "success": true,
  "data": { ... }
}
```

### Error Response
```json
{
  "success": false,
  "error": "error_type",
  "message": "Human-readable error message",
  "errors": { ... }  // Optional validation errors
}
```

## Pagination

List endpoints support pagination with `limit` and `skip` parameters:

```bash
# Get first 20 records
curl "https://your-atom-instance.com/api/v2/descriptions?limit=20&skip=0"

# Get next 20 records
curl "https://your-atom-instance.com/api/v2/descriptions?limit=20&skip=20"
```

Response includes pagination metadata:
```json
{
  "success": true,
  "data": {
    "results": [...],
    "total": 500,
    "limit": 20,
    "skip": 0
  }
}
```

## Rate Limiting

The API enforces rate limits to ensure fair usage:

- **Default**: 100 requests per minute
- **Authenticated**: Up to 1000 requests per minute (configurable)

When rate limited, you'll receive a `429 Too Many Requests` response with a `Retry-After` header.

## Using the SDKs

For easier integration, use our official SDKs:

### Python

```bash
pip install atom-ahg
```

```python
from atom_ahg import AtomClient

client = AtomClient(
    base_url="https://your-atom-instance.com",
    api_key="your-api-key"
)

# List descriptions
result = client.descriptions.list(limit=10)
for desc in result.results:
    print(desc['title'])
```

### JavaScript/TypeScript

```bash
npm install @ahg/atom-client
```

```typescript
import { AtomClient } from '@ahg/atom-client';

const client = new AtomClient({
  baseUrl: 'https://your-atom-instance.com',
  apiKey: 'your-api-key'
});

// List descriptions
const result = await client.descriptions.list({ limit: 10 });
for (const desc of result.results) {
  console.log(desc.title);
}
```

## Next Steps

- [Python SDK Guide](./02-python-sdk.md) - Complete Python SDK documentation
- [JavaScript SDK Guide](./03-javascript-sdk.md) - Complete JavaScript/TypeScript SDK documentation
- [Common Use Cases](./04-common-use-cases.md) - Real-world integration examples
- [API Reference](../technical/01-api-reference.md) - Complete endpoint documentation

## Support

- **Documentation**: https://github.com/ArchiveHeritageGroup/atom-extensions-catalog/tree/main/docs
- **Issues**: https://github.com/ArchiveHeritageGroup/atom-framework/issues
- **Email**: support@theahg.co.za
