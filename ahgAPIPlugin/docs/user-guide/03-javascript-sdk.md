# JavaScript/TypeScript SDK User Guide

The `@ahg/atom-client` SDK provides a complete, type-safe client for the AtoM AHG Framework REST API v2. It works in both Node.js and browser environments.

## Installation

```bash
npm install @ahg/atom-client
```

Or with yarn:
```bash
yarn add @ahg/atom-client
```

**Requirements:**
- Node.js 18+ (with native fetch) or Node.js 16+ with `--experimental-fetch`
- Modern browsers (Chrome 42+, Firefox 39+, Safari 10.1+, Edge 14+)

## Quick Start

```typescript
import { AtomClient } from '@ahg/atom-client';

// Initialize the client
const client = new AtomClient({
  baseUrl: 'https://your-atom-instance.com',
  apiKey: 'your-api-key'
});

// List descriptions
const result = await client.descriptions.list({ limit: 10 });
console.log(`Found ${result.total} descriptions`);

for (const desc of result.results) {
  console.log(`- ${desc.title} (${desc.slug})`);
}

// Get a specific description
const desc = await client.descriptions.get('my-description-slug');
console.log(desc.title);
```

## Configuration

```typescript
import { AtomClient } from '@ahg/atom-client';

// Full configuration options
const client = new AtomClient({
  baseUrl: 'https://your-atom-instance.com',
  apiKey: 'your-api-key',
  timeout: 60000,           // Request timeout in milliseconds
  culture: 'en',            // Default language
  retry: {
    maxRetries: 3,          // Retry attempts for failed requests
    baseDelay: 1000,        // Initial retry delay (ms)
    maxDelay: 60000,        // Maximum retry delay (ms)
  },
  headers: {
    'X-Custom-Header': 'value'  // Additional headers
  }
});
```

### Environment Variables (Node.js)

```typescript
import { AtomClient } from '@ahg/atom-client';

const client = new AtomClient({
  baseUrl: process.env.ATOM_BASE_URL!,
  apiKey: process.env.ATOM_API_KEY!
});
```

## Resources

### Descriptions (Archival Records)

```typescript
// List descriptions with filtering
const result = await client.descriptions.list({
  limit: 20,
  skip: 0,
  repository: 'my-archive',
  level: 'fonds',
  sector: 'archive',
  sort: 'updated_desc'
});

// Get a specific description
const desc = await client.descriptions.get('my-slug', { full: true });

// Create a new description
const newDesc = await client.descriptions.create({
  title: 'New Collection',
  level_of_description_id: 220,
  scope_and_content: 'Description of the collection...'
});

// Update a description
const updated = await client.descriptions.update('my-slug', {
  title: 'Updated Title'
});

// Delete a description
await client.descriptions.delete('my-slug');

// Async pagination
for await (const page of client.descriptions.paginate({ pageSize: 50 })) {
  for (const desc of page.results) {
    console.log(desc.title);
  }
}
```

### Authorities (Name Records)

```typescript
// List authorities
const result = await client.authorities.list({
  entityType: 'corporate_body',
  limit: 20
});

// Get a specific authority
const auth = await client.authorities.get('organization-slug');

// Paginate through all authorities
for await (const page of client.authorities.paginate({ pageSize: 50 })) {
  for (const auth of page.results) {
    console.log(auth.authorized_form_of_name);
  }
}
```

### Repositories

```typescript
// List all repositories
const repos = await client.repositories.list();

for (const repo of repos.results) {
  console.log(`${repo.name} - ${repo.slug}`);
}
```

### Taxonomies and Terms

```typescript
// List taxonomies
const taxonomies = await client.taxonomies.list();

// Get terms for a specific taxonomy
const terms = await client.taxonomies.getTerms(42);
```

### Search

```typescript
// Basic search
const results = await client.search.search({ query: 'heritage artifacts' });

// Advanced search with filters
const results = await client.search.search({
  query: 'artifact',
  entityType: 'informationobject',
  repository: 'museum-collection',
  sector: 'museum',
  dateFrom: '1900-01-01',
  dateTo: '1950-12-31',
  limit: 50
});

for (const result of results.results) {
  console.log(`${result.title} - ${result.score}`);
}
```

### Batch Operations

```typescript
// Get multiple records at once
const results = await client.batch.getMany(['slug-1', 'slug-2', 'slug-3']);

// Create multiple records
const newItems = await client.batch.createMany([
  { title: 'Item 1' },
  { title: 'Item 2' },
  { title: 'Item 3' }
]);

// Execute custom batch operations (up to 100)
const results = await client.batch.execute([
  { method: 'GET', path: '/descriptions/item-1' },
  { method: 'POST', path: '/descriptions', body: { title: 'New' } },
  { method: 'DELETE', path: '/descriptions/old-item' }
]);
```

### Conditions (SPECTRUM)

```typescript
// List condition assessments
const conditions = await client.conditions.list({ overallCondition: 'good' });

// Create a condition assessment
const condition = await client.conditions.create({
  object_id: 123,
  checked_by: 'John Smith',
  date_checked: '2024-01-15',
  overall_condition: 'good',
  condition_note: 'Minor wear on edges'
});

// Get condition details
const cond = await client.conditions.get(condition.id);

// Upload a condition photo (base64)
import { fileToBase64 } from '@ahg/atom-client';

// In browser
const fileInput = document.querySelector('input[type="file"]') as HTMLInputElement;
const file = fileInput.files![0];
const photoData = await fileToBase64(file);

await client.conditions.uploadPhoto(condition.id, file.name, photoData, {
  photoType: 'detail',
  caption: 'Edge wear detail'
});
```

### Heritage Assets (GRAP 103 / IPSAS 45)

```typescript
// List heritage assets
const assets = await client.assets.list();

// Create an asset record
const asset = await client.assets.create({
  object_id: 123,
  acquisition_date: '2024-01-15',
  acquisition_cost: 50000.00,
  currency_code: 'ZAR',
  acquisition_method: 'purchase'
});

// Add a valuation
const valuation = await client.valuations.create({
  asset_id: asset.id,
  valuation_date: '2024-06-01',
  valuation_amount: 75000.00,
  currency_code: 'ZAR',
  valuation_method: 'market_comparison',
  valuer_name: 'Jane Doe'
});
```

### Privacy/Compliance (GDPR, POPIA)

```typescript
// List Data Subject Access Requests
const dsars = await client.privacy.listDsars({ status: 'pending' });

// Create a DSAR
const dsar = await client.privacy.createDsar({
  requester_name: 'John Smith',
  requester_email: 'john@example.com',
  request_type: 'access',
  subject_matter: 'Personal records request'
});

// Update DSAR status
await client.privacy.updateDsar(dsar.id, {
  status: 'in_progress',
  assigned_to: 'admin@example.com'
});

// Report a data breach
const breach = await client.privacy.createBreach({
  breach_date: '2024-01-20',
  severity: 'high',
  description: 'Unauthorized access detected',
  affected_records: 50
});
```

### File Uploads

```typescript
import { fileToBase64 } from '@ahg/atom-client';

// Browser: Read file and convert to base64
const fileInput = document.querySelector('input[type="file"]') as HTMLInputElement;
const file = fileInput.files![0];
const fileData = await fileToBase64(file);

// Upload to a description
const result = await client.uploads.uploadToDescription('my-description', {
  filename: file.name,
  fileData: fileData,
  title: 'Supporting Document',
  usageType: 'master'
});
```

### Mobile Sync

```typescript
// Get changes since last sync
const changes = await client.sync.getChanges({
  since: '2024-01-01T00:00:00Z',
  entityTypes: ['condition', 'asset']
});

for (const change of changes.changes) {
  console.log(`${change.action}: ${change.entity_type} ${change.entity_id}`);
}

// Push offline changes
const result = await client.sync.pushBatch([
  {
    entity_type: 'condition',
    action: 'create',
    data: { object_id: 123, overall_condition: 'good' },
    client_id: 'offline-1'
  },
  {
    entity_type: 'condition',
    action: 'update',
    entity_id: 456,
    data: { condition_note: 'Updated note' },
    client_id: 'offline-2'
  }
]);
```

## Error Handling

```typescript
import {
  AtomClient,
  AtomAPIError,
  AtomAuthenticationError,
  AtomNotFoundError,
  AtomRateLimitError,
  AtomValidationError,
  AtomServerError
} from '@ahg/atom-client';

const client = new AtomClient({...});

try {
  const desc = await client.descriptions.get('non-existent');
} catch (error) {
  if (error instanceof AtomNotFoundError) {
    console.log('Description not found');
  } else if (error instanceof AtomAuthenticationError) {
    console.log('Invalid API key');
  } else if (error instanceof AtomRateLimitError) {
    console.log(`Rate limited. Retry after ${error.retryAfter} seconds`);
  } else if (error instanceof AtomValidationError) {
    console.log(`Validation error: ${error.errors}`);
  } else if (error instanceof AtomServerError) {
    console.log('Server error - try again later');
  } else if (error instanceof AtomAPIError) {
    console.log(`API error: ${error.message} (status: ${error.statusCode})`);
  }
}
```

## Pagination

### Async Iterator Pattern

```typescript
// Iterate through all pages
for await (const page of client.descriptions.paginate({ pageSize: 50 })) {
  console.log(`Page ${page.pageNumber} of ${page.totalPages}`);
  for (const desc of page.results) {
    console.log(desc.title);
  }
}
```

### Manual Pagination

```typescript
// First page
let result = await client.descriptions.list({ limit: 20, skip: 0 });
console.log(`Total: ${result.total}`);

// Check if more pages exist
while (result.hasMore) {
  result = await client.descriptions.list({
    limit: 20,
    skip: result.skip + result.limit
  });
  for (const desc of result.results) {
    console.log(desc.title);
  }
}
```

### Collect All Results

```typescript
import { paginateAll } from '@ahg/atom-client';

// Get all descriptions (use with caution on large datasets)
const allDescriptions = await paginateAll(
  client.descriptions.paginate({ pageSize: 100 })
);
```

## TypeScript Support

The SDK provides full TypeScript support with comprehensive type definitions:

```typescript
import { AtomClient } from '@ahg/atom-client';
import type {
  DescriptionSummary,
  DescriptionDetail,
  DescriptionCreate,
  DescriptionUpdate,
  ConditionCreate,
  AssetCreate,
  SearchParams
} from '@ahg/atom-client';

const client = new AtomClient({...});

// Type-safe creation
const data: DescriptionCreate = {
  title: 'New Record',
  level_of_description_id: 220
};
const result: DescriptionDetail = await client.descriptions.create(data);

// Type inference
const desc = await client.descriptions.get('my-slug');
// desc is automatically typed as DescriptionDetail
```

## Browser Usage

```html
<!DOCTYPE html>
<html>
<head>
  <script type="module">
    import { AtomClient } from 'https://cdn.jsdelivr.net/npm/@ahg/atom-client/dist/esm/index.js';

    const client = new AtomClient({
      baseUrl: 'https://your-atom-instance.com',
      apiKey: 'your-api-key'
    });

    const result = await client.descriptions.list({ limit: 10 });
    console.log(result);
  </script>
</head>
<body></body>
</html>
```

### With Bundlers (Webpack, Vite, etc.)

```typescript
// Just import normally - tree-shaking is supported
import { AtomClient, AtomNotFoundError } from '@ahg/atom-client';
```

## React Example

```tsx
import { useState, useEffect } from 'react';
import { AtomClient, DescriptionSummary } from '@ahg/atom-client';

const client = new AtomClient({
  baseUrl: process.env.REACT_APP_ATOM_URL!,
  apiKey: process.env.REACT_APP_ATOM_API_KEY!
});

function DescriptionList() {
  const [descriptions, setDescriptions] = useState<DescriptionSummary[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    async function fetchData() {
      try {
        const result = await client.descriptions.list({ limit: 20 });
        setDescriptions(result.results);
      } catch (err) {
        setError(err instanceof Error ? err.message : 'Unknown error');
      } finally {
        setLoading(false);
      }
    }
    fetchData();
  }, []);

  if (loading) return <div>Loading...</div>;
  if (error) return <div>Error: {error}</div>;

  return (
    <ul>
      {descriptions.map(desc => (
        <li key={desc.slug}>{desc.title}</li>
      ))}
    </ul>
  );
}
```

## Node.js Example

```typescript
import { AtomClient } from '@ahg/atom-client';
import * as fs from 'fs';

const client = new AtomClient({
  baseUrl: process.env.ATOM_URL!,
  apiKey: process.env.ATOM_API_KEY!
});

async function exportDescriptions() {
  const allDescriptions = [];

  for await (const page of client.descriptions.paginate({ pageSize: 100 })) {
    allDescriptions.push(...page.results);
    console.log(`Fetched ${allDescriptions.length} / ${page.total}`);
  }

  fs.writeFileSync(
    'descriptions.json',
    JSON.stringify(allDescriptions, null, 2)
  );
  console.log(`Exported ${allDescriptions.length} descriptions`);
}

exportDescriptions();
```

## Retry Configuration

The SDK automatically retries failed requests:

```typescript
const client = new AtomClient({
  baseUrl: '...',
  apiKey: '...',
  retry: {
    maxRetries: 5,      // Number of retry attempts
    baseDelay: 1000,    // Initial delay (ms)
    maxDelay: 120000,   // Maximum delay (ms)
  }
});
```

Retries are attempted for:
- Rate limit errors (429)
- Server errors (5xx)
- Network timeouts

## Abort/Cancel Requests

```typescript
// Create an AbortController
const controller = new AbortController();

// Pass signal to request
const promise = client.descriptions.list(
  { limit: 100 },
  { signal: controller.signal }
);

// Cancel after 5 seconds
setTimeout(() => controller.abort(), 5000);

try {
  const result = await promise;
} catch (error) {
  if (error.name === 'AbortError') {
    console.log('Request was cancelled');
  }
}
```

## Best Practices

1. **Use pagination** for large datasets instead of high limits
2. **Handle rate limits** gracefully with retry logic (built-in)
3. **Use TypeScript** for better IDE support and type safety
4. **Implement proper error handling** with typed exceptions
5. **Use batch operations** for multiple creates/updates
6. **Cache results** when appropriate to reduce API calls
7. **Use environment variables** for credentials

## CommonJS Usage

```javascript
const { AtomClient } = require('@ahg/atom-client');

const client = new AtomClient({
  baseUrl: 'https://your-atom-instance.com',
  apiKey: 'your-api-key'
});

async function main() {
  const result = await client.descriptions.list({ limit: 10 });
  console.log(result);
}

main();
```

## Examples

See the `examples/` directory in the SDK repository for complete examples:

- `basic-usage.ts` - Simple CRUD operations
- `batch-import.ts` - Importing multiple records
- `condition-report.ts` - Creating condition assessments
- `search-export.ts` - Searching and exporting results
- `react-app/` - React integration example
- `sync-offline.ts` - Mobile sync workflow
