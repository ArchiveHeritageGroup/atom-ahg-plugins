# AtoM AHG API Documentation

Complete documentation for the AtoM AHG Framework REST API v2 and official SDKs.

## Quick Links

- **Python SDK**: `pip install atom-ahg`
- **JavaScript SDK**: `npm install @ahg/atom-client`
- **API Base URL**: `https://your-atom-instance.com/api/v2`

## User Guide

Documentation for developers integrating with the API.

| Document | Description |
|----------|-------------|
| [Getting Started](./user-guide/01-getting-started.md) | Quick start guide, authentication, basic usage |
| [Python SDK](./user-guide/02-python-sdk.md) | Complete Python SDK documentation |
| [JavaScript SDK](./user-guide/03-javascript-sdk.md) | Complete JavaScript/TypeScript SDK documentation |
| [Common Use Cases](./user-guide/04-common-use-cases.md) | Real-world integration examples |

## Technical Documentation

In-depth technical reference for the API and SDKs.

| Document | Description |
|----------|-------------|
| [API Reference](./technical/01-api-reference.md) | Complete endpoint documentation (44 endpoints) |
| [Architecture](./technical/02-architecture.md) | System architecture, request flow, class diagrams |
| [Authentication](./technical/03-authentication.md) | API keys, scopes, rate limiting, security |
| [Error Handling](./technical/04-error-handling.md) | Error types, exception handling, retry patterns |
| [SDK Internals](./technical/05-sdk-internals.md) | SDK implementation details, contributing guide |

## API Specifications

Machine-readable API documentation.

| File | Format | Description |
|------|--------|-------------|
| [openapi.yaml](./openapi.yaml) | OpenAPI 3.0 | Complete API specification |
| [postman-collection.json](./postman-collection.json) | Postman | Ready-to-use API collection |

## API Overview

The AtoM AHG API provides access to:

### Core Resources
- **Descriptions**: Archival records (fonds, series, files, items)
- **Authorities**: Name records (persons, organizations, families)
- **Repositories**: Holding institutions
- **Taxonomies**: Controlled vocabularies

### Search & Batch
- **Search**: Full-text and faceted search
- **Batch**: Execute up to 100 operations per request

### GLAM/DAM Extensions
- **Conditions**: SPECTRUM-compliant condition assessments
- **Assets**: Heritage asset management (GRAP 103/IPSAS 45)
- **Valuations**: Asset valuation tracking

### Compliance
- **Privacy**: GDPR/POPIA DSARs and breach reporting

### Integration
- **Uploads**: Digital object file uploads
- **Sync**: Mobile/offline data synchronization

## Quick Example

### Python
```python
from atom_ahg import AtomClient

client = AtomClient(
    base_url="https://your-atom-instance.com",
    api_key="your-api-key"
)

# List descriptions
for page in client.descriptions.paginate(page_size=50):
    for desc in page.results:
        print(desc['title'])
```

### JavaScript
```typescript
import { AtomClient } from '@ahg/atom-client';

const client = new AtomClient({
  baseUrl: 'https://your-atom-instance.com',
  apiKey: 'your-api-key'
});

// List descriptions
for await (const page of client.descriptions.paginate({ pageSize: 50 })) {
  for (const desc of page.results) {
    console.log(desc.title);
  }
}
```

### cURL
```bash
curl -H "X-API-Key: your-api-key" \
     "https://your-atom-instance.com/api/v2/descriptions?limit=10"
```

## Support

- **GitHub Issues**: https://github.com/ArchiveHeritageGroup/atom-framework/issues
- **Email**: support@theahg.co.za

## License

MIT License - see LICENSE file for details.
