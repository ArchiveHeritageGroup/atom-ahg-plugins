# Architecture Overview

This document describes the architecture of the AtoM AHG API and SDKs.

## API Architecture

### System Overview

```
┌─────────────────────────────────────────────────────────────────────────┐
│                              Clients                                     │
│    ┌─────────┐    ┌─────────┐    ┌─────────┐    ┌─────────┐            │
│    │ Python  │    │   JS    │    │  cURL   │    │ Postman │            │
│    │   SDK   │    │   SDK   │    │         │    │         │            │
│    └────┬────┘    └────┬────┘    └────┬────┘    └────┬────┘            │
└─────────┼──────────────┼──────────────┼──────────────┼──────────────────┘
          │              │              │              │
          └──────────────┼──────────────┴──────────────┘
                         │
                   HTTPS + JSON
                         │
                         ▼
┌─────────────────────────────────────────────────────────────────────────┐
│                         API Gateway (Nginx)                              │
│    ┌─────────────────────────────────────────────────────────────────┐  │
│    │  Rate Limiting  │  SSL Termination  │  Request Routing          │  │
│    └─────────────────────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────────────────────┘
                         │
                         ▼
┌─────────────────────────────────────────────────────────────────────────┐
│                        AtoM AHG Framework                                │
│  ┌───────────────────────────────────────────────────────────────────┐  │
│  │                     ahgAPIPlugin                                   │  │
│  │  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐            │  │
│  │  │ Controllers  │  │  Services    │  │ Repositories │            │  │
│  │  │  (Actions)   │◄─┤              │◄─┤              │            │  │
│  │  └──────────────┘  └──────────────┘  └──────────────┘            │  │
│  │         │                 │                  │                     │  │
│  │         ▼                 ▼                  ▼                     │  │
│  │  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐            │  │
│  │  │ Auth/ACL     │  │ Validation   │  │ Serializers  │            │  │
│  │  └──────────────┘  └──────────────┘  └──────────────┘            │  │
│  └───────────────────────────────────────────────────────────────────┘  │
│                                                                          │
│  ┌───────────────────────────────────────────────────────────────────┐  │
│  │                     atom-framework                                 │  │
│  │  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐            │  │
│  │  │   Laravel    │  │  Extension   │  │   Helpers    │            │  │
│  │  │Query Builder │  │   Manager    │  │              │            │  │
│  │  └──────────────┘  └──────────────┘  └──────────────┘            │  │
│  └───────────────────────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────────────────────┘
                         │
                         ▼
┌─────────────────────────────────────────────────────────────────────────┐
│                          Data Layer                                      │
│    ┌─────────────┐    ┌─────────────┐    ┌─────────────┐               │
│    │   MySQL 8   │    │Elasticsearch│    │   Redis     │               │
│    │  (Primary)  │    │  (Search)   │    │  (Cache)    │               │
│    └─────────────┘    └─────────────┘    └─────────────┘               │
└─────────────────────────────────────────────────────────────────────────┘
```

### Request Flow

1. **Client Request**: SDK or HTTP client sends request
2. **Nginx Gateway**: Rate limiting, SSL, routing to PHP-FPM
3. **Symfony Routing**: Routes to appropriate action class
4. **Authentication**: API key validation via `ApiKeyService`
5. **Authorization**: ACL check for requested operation
6. **Controller**: Business logic in action class
7. **Repository**: Data access via Laravel Query Builder
8. **Serialization**: Response formatting
9. **Response**: JSON response to client

### Plugin Structure

```
ahgAPIPlugin/
├── config/
│   └── ahgAPIPluginConfiguration.class.php  # Route definitions
├── lib/
│   ├── AhgApiAction.class.php              # Base action class
│   ├── service/
│   │   ├── ApiKeyService.php               # Key validation
│   │   └── RateLimiter.php                 # Rate limiting
│   ├── repository/
│   │   └── ApiRepository.php               # Data access
│   └── serializer/
│       └── EntitySerializer.php            # Response formatting
├── modules/
│   └── apiv2/
│       ├── actions/
│       │   ├── descriptionsAction.class.php
│       │   ├── authoritiesAction.class.php
│       │   ├── conditionsAction.class.php
│       │   └── ...
│       └── templates/
└── docs/
    ├── openapi.yaml
    └── postman-collection.json
```

### Database Schema

The API operates on AtoM's core tables plus extension tables:

**Core Tables (Read-Only):**
- `information_object` - Archival descriptions
- `actor` - Authority records
- `repository` - Holding institutions
- `term` / `taxonomy` - Controlled vocabularies
- `digital_object` - File attachments

**Extension Tables (Read-Write):**
- `spectrum_condition` - Condition assessments
- `grap_heritage_asset` - Heritage assets
- `grap_valuation` - Asset valuations
- `privacy_dsar` - Data subject requests
- `privacy_breach` - Breach reports
- `api_key` - API authentication

---

## SDK Architecture

### Design Principles

1. **Resource-Based**: Each API resource has a dedicated class
2. **Lazy Loading**: Resources instantiated on first access
3. **Type Safety**: Full type definitions for IDE support
4. **Consistent API**: Same patterns across both SDKs
5. **Zero Dependencies**: Native HTTP clients only

### Python SDK Structure

```
atom-ahg-python/
├── src/atom_ahg/
│   ├── __init__.py         # Package exports
│   ├── client.py           # Main AtomClient class
│   ├── config.py           # Configuration handling
│   ├── exceptions.py       # Exception hierarchy
│   ├── pagination.py       # Pagination helpers
│   ├── retry.py            # Retry logic
│   ├── types.py            # TypedDict definitions
│   └── resources/
│       ├── base.py         # BaseResource class
│       ├── descriptions.py
│       ├── authorities.py
│       ├── conditions.py
│       └── ...
├── tests/
└── examples/
```

### JavaScript SDK Structure

```
atom-client-js/
├── src/
│   ├── index.ts            # Package exports
│   ├── client.ts           # Main AtomClient class
│   ├── config.ts           # Configuration handling
│   ├── errors.ts           # Exception classes
│   ├── pagination.ts       # Pagination helpers
│   ├── retry.ts            # Retry logic
│   ├── types/
│   │   └── index.ts        # Interface definitions
│   └── resources/
│       ├── base.ts         # BaseResource class
│       ├── descriptions.ts
│       ├── authorities.ts
│       ├── conditions.ts
│       └── ...
├── tests/
└── dist/                   # Built output (cjs + esm + types)
```

### Class Hierarchy

```
AtomClient
├── config: ClientConfig
├── httpClient: httpx.Client / fetch
│
├── descriptions: DescriptionsResource
├── authorities: AuthoritiesResource
├── repositories: RepositoriesResource
├── taxonomies: TaxonomiesResource
├── search: SearchResource
├── batch: BatchResource
├── conditions: ConditionsResource
├── assets: AssetsResource
├── valuations: ValuationsResource
├── privacy: PrivacyResource
├── uploads: UploadsResource
└── sync: SyncResource

BaseResource
├── client: AtomClient
├── _request(method, path, params, data)
├── _build_paginated_response(data)
└── _paginate(path, params, page_size)
```

### Request Flow (SDK)

```
┌──────────────────────────────────────────────────────────────────┐
│                        User Code                                  │
│   result = client.descriptions.get("my-slug")                    │
└────────────────────────────┬─────────────────────────────────────┘
                             │
                             ▼
┌──────────────────────────────────────────────────────────────────┐
│                   DescriptionsResource                           │
│   def get(self, slug, full=True):                               │
│       return self._request("GET", f"/descriptions/{slug}")      │
└────────────────────────────┬─────────────────────────────────────┘
                             │
                             ▼
┌──────────────────────────────────────────────────────────────────┐
│                      BaseResource                                 │
│   def _request(self, method, path, ...):                        │
│       return self.client.request(method, path, ...)             │
└────────────────────────────┬─────────────────────────────────────┘
                             │
                             ▼
┌──────────────────────────────────────────────────────────────────┐
│                       AtomClient                                  │
│   1. Build URL: base_url + "/api/v2" + path                     │
│   2. Add headers: X-API-Key, Content-Type                       │
│   3. Execute with retry logic                                    │
│   4. Parse response                                              │
│   5. Handle errors                                               │
│   6. Return data                                                 │
└────────────────────────────┬─────────────────────────────────────┘
                             │
                             ▼
┌──────────────────────────────────────────────────────────────────┐
│                     HTTP Client                                   │
│   Python: httpx.Client                                           │
│   JavaScript: native fetch                                       │
└────────────────────────────┬─────────────────────────────────────┘
                             │
                     HTTPS Request
                             │
                             ▼
                    [ AtoM API Server ]
```

---

## Pagination Architecture

### API Pagination

The API uses offset-based pagination:

```json
{
  "results": [...],
  "total": 500,      // Total matching records
  "limit": 20,       // Records per page
  "skip": 40         // Records skipped (offset)
}
```

**Calculation:**
- Current page: `floor(skip / limit) + 1`
- Total pages: `ceil(total / limit)`
- Has more: `skip + len(results) < total`

### SDK Pagination

Both SDKs provide iterator-based pagination:

**Python (sync):**
```python
for page in client.descriptions.paginate(page_size=50):
    for item in page.results:
        process(item)
```

**Python (async):**
```python
async for page in client.descriptions.paginate_async(page_size=50):
    for item in page.results:
        process(item)
```

**JavaScript:**
```typescript
for await (const page of client.descriptions.paginate({ pageSize: 50 })) {
  for (const item of page.results) {
    process(item);
  }
}
```

### Paginator Implementation

```python
class Paginator:
    def __init__(self, fetch_fn, page_size):
        self.fetch_fn = fetch_fn
        self.page_size = page_size
        self.skip = 0
        self.done = False

    def __iter__(self):
        return self

    def __next__(self):
        if self.done:
            raise StopIteration

        result = self.fetch_fn(limit=self.page_size, skip=self.skip)
        self.skip += self.page_size

        if self.skip >= result.total:
            self.done = True

        return result
```

---

## Retry Architecture

### Retry Logic

Both SDKs implement exponential backoff with jitter:

```python
def calculate_delay(attempt, config, retry_after=None):
    if retry_after:
        return retry_after

    # Exponential backoff: base_delay * 2^attempt
    delay = config.base_delay * (2 ** attempt)

    # Add jitter (0-25%)
    jitter = delay * random.random() * 0.25
    delay += jitter

    # Cap at max_delay
    return min(delay, config.max_delay)
```

### Retryable Conditions

| Condition | Retry? | Delay |
|-----------|--------|-------|
| 429 Rate Limit | Yes | Retry-After header or calculated |
| 500 Server Error | Yes | Exponential backoff |
| 502/503/504 Gateway | Yes | Exponential backoff |
| Connection Error | Yes | Exponential backoff |
| Timeout | Yes | Exponential backoff |
| 400 Bad Request | No | - |
| 401 Unauthorized | No | - |
| 403 Forbidden | No | - |
| 404 Not Found | No | - |

### Retry Configuration

```python
# Python
client = AtomClient(
    max_retries=3,
    retry_base_delay=1.0,   # 1 second
    retry_max_delay=60.0    # 60 seconds
)

# JavaScript
const client = new AtomClient({
  retry: {
    maxRetries: 3,
    baseDelay: 1000,   // 1 second
    maxDelay: 60000    // 60 seconds
  }
});
```

---

## Error Architecture

### Exception Hierarchy

```
AtomAPIError
├── AtomAuthenticationError (401)
├── AtomForbiddenError (403)
├── AtomNotFoundError (404)
├── AtomValidationError (400)
├── AtomRateLimitError (429)
├── AtomServerError (5xx)
├── AtomNetworkError
└── AtomTimeoutError
```

### Error Response Mapping

```python
def map_error(status_code, response_data):
    error_map = {
        400: AtomValidationError,
        401: AtomAuthenticationError,
        403: AtomForbiddenError,
        404: AtomNotFoundError,
        429: AtomRateLimitError,
    }

    if status_code >= 500:
        return AtomServerError(...)

    error_class = error_map.get(status_code, AtomAPIError)
    return error_class(
        message=response_data.get("message"),
        status_code=status_code,
        error_type=response_data.get("error"),
        response_data=response_data
    )
```

---

## Security Architecture

### Authentication Flow

```
┌──────────┐     ┌───────────┐     ┌─────────────┐     ┌──────────┐
│  Client  │────▶│   API     │────▶│ ApiKeyService│────▶│ Database │
│          │     │ Endpoint  │     │             │     │          │
└──────────┘     └───────────┘     └─────────────┘     └──────────┘
     │                │                   │                  │
     │  X-API-Key     │                   │                  │
     │────────────────▶                   │                  │
     │                │  validate(key)    │                  │
     │                │──────────────────▶│                  │
     │                │                   │ SELECT * FROM    │
     │                │                   │ api_key WHERE..  │
     │                │                   │─────────────────▶│
     │                │                   │                  │
     │                │                   │◀─────────────────│
     │                │                   │                  │
     │                │◀──────────────────│                  │
     │                │  {user, scopes}   │                  │
     │                │                   │                  │
```

### Scope Validation

```php
// API action checks required scope
public function execute($request) {
    $this->requireScope('write');  // Throws 403 if insufficient

    // Proceed with operation
}
```

### Rate Limiting

```php
// Rate limit check
$limiter = new RateLimiter($redis);
$result = $limiter->check($apiKey, $limit = 100, $window = 60);

if (!$result->allowed) {
    throw new RateLimitException($result->retryAfter);
}
```

---

## Deployment Architecture

### Single Server

```
┌─────────────────────────────────────────┐
│              Single Server               │
│  ┌─────────┐  ┌─────────┐  ┌─────────┐ │
│  │  Nginx  │──│ PHP-FPM │──│  MySQL  │ │
│  └─────────┘  └─────────┘  └─────────┘ │
│                    │                     │
│              ┌─────────┐                 │
│              │  Redis  │                 │
│              └─────────┘                 │
└─────────────────────────────────────────┘
```

### High Availability

```
                    ┌─────────────┐
                    │Load Balancer│
                    └──────┬──────┘
           ┌───────────────┼───────────────┐
           ▼               ▼               ▼
    ┌──────────┐    ┌──────────┐    ┌──────────┐
    │ App Node │    │ App Node │    │ App Node │
    │ (Nginx + │    │ (Nginx + │    │ (Nginx + │
    │  PHP-FPM)│    │  PHP-FPM)│    │  PHP-FPM)│
    └────┬─────┘    └────┬─────┘    └────┬─────┘
         │               │               │
         └───────────────┼───────────────┘
                         │
              ┌──────────┴──────────┐
              ▼                     ▼
       ┌────────────┐        ┌────────────┐
       │MySQL Primary│◀──────▶│MySQL Replica│
       └────────────┘        └────────────┘
              │
              ▼
       ┌────────────┐
       │Redis Cluster│
       └────────────┘
```
