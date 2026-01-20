# Error Handling

This document covers error handling patterns for the AtoM AHG API and SDKs.

## API Error Response Format

All API errors follow a consistent format:

```json
{
  "success": false,
  "error": "error_type",
  "message": "Human-readable error message",
  "errors": {
    "field_name": ["Validation error message"]
  },
  "request_id": "req_a1b2c3d4e5f6"
}
```

| Field | Type | Description |
|-------|------|-------------|
| `success` | boolean | Always `false` for errors |
| `error` | string | Machine-readable error type |
| `message` | string | Human-readable description |
| `errors` | object | Field-specific validation errors (optional) |
| `request_id` | string | Unique request identifier for debugging |

## HTTP Status Codes

### Client Errors (4xx)

| Status | Error Type | Description |
|--------|------------|-------------|
| 400 | `validation_error` | Invalid request data |
| 401 | `authentication_error` | Missing or invalid credentials |
| 403 | `forbidden` | Insufficient permissions |
| 404 | `not_found` | Resource not found |
| 405 | `method_not_allowed` | HTTP method not supported |
| 409 | `conflict` | Resource conflict (e.g., duplicate) |
| 422 | `unprocessable_entity` | Semantic validation failed |
| 429 | `rate_limit` | Too many requests |

### Server Errors (5xx)

| Status | Error Type | Description |
|--------|------------|-------------|
| 500 | `server_error` | Internal server error |
| 502 | `bad_gateway` | Upstream server error |
| 503 | `service_unavailable` | Service temporarily unavailable |
| 504 | `gateway_timeout` | Upstream request timeout |

## Error Type Details

### validation_error (400)

Invalid request data or parameters.

```json
{
  "success": false,
  "error": "validation_error",
  "message": "Validation failed",
  "errors": {
    "title": ["Title is required"],
    "level_of_description_id": ["Invalid level ID"],
    "date_of_creation": ["Invalid date format. Use YYYY-MM-DD"]
  }
}
```

**Common causes:**
- Missing required fields
- Invalid field values
- Wrong data types
- Invalid date/time formats

### authentication_error (401)

Missing or invalid authentication credentials.

```json
{
  "success": false,
  "error": "authentication_error",
  "message": "Invalid API key"
}
```

**Common causes:**
- Missing `X-API-Key` header
- Invalid or revoked key
- Expired key
- Malformed bearer token

### forbidden (403)

Authenticated but lacks permission.

```json
{
  "success": false,
  "error": "forbidden",
  "message": "Insufficient permissions. Required scope: write"
}
```

**Common causes:**
- Key lacks required scope
- Attempting to modify read-only resource
- IP address not whitelisted

### not_found (404)

Resource does not exist.

```json
{
  "success": false,
  "error": "not_found",
  "message": "Description 'non-existent-slug' not found"
}
```

**Common causes:**
- Wrong slug or ID
- Resource was deleted
- Typo in URL

### rate_limit (429)

Too many requests in time window.

```json
{
  "success": false,
  "error": "rate_limit",
  "message": "Rate limit exceeded. Retry after 30 seconds",
  "retry_after": 30
}
```

**Headers included:**
```http
Retry-After: 30
X-RateLimit-Limit: 100
X-RateLimit-Remaining: 0
X-RateLimit-Reset: 1705312800
```

### server_error (500)

Internal server error.

```json
{
  "success": false,
  "error": "server_error",
  "message": "An unexpected error occurred",
  "request_id": "req_a1b2c3d4e5f6"
}
```

**Include request_id when reporting issues.**

---

## SDK Error Classes

### Python

```python
from atom_ahg import (
    AtomAPIError,           # Base class
    AtomAuthenticationError,# 401
    AtomForbiddenError,     # 403
    AtomNotFoundError,      # 404
    AtomValidationError,    # 400
    AtomRateLimitError,     # 429
    AtomServerError,        # 5xx
    AtomNetworkError,       # Connection errors
    AtomTimeoutError,       # Request timeout
)
```

**Exception attributes:**

```python
try:
    client.descriptions.get("non-existent")
except AtomAPIError as e:
    print(e.message)        # "Description not found"
    print(e.status_code)    # 404
    print(e.error_type)     # "not_found"
    print(e.response_data)  # Full response dict
    print(e.request_id)     # "req_a1b2c3d4"

# Validation errors have additional attribute
except AtomValidationError as e:
    print(e.errors)         # {"title": ["Required"]}
```

### JavaScript

```typescript
import {
  AtomAPIError,           // Base class
  AtomAuthenticationError,// 401
  AtomForbiddenError,     // 403
  AtomNotFoundError,      // 404
  AtomValidationError,    // 400
  AtomRateLimitError,     // 429
  AtomServerError,        // 5xx
  AtomNetworkError,       // Connection errors
  AtomTimeoutError,       // Request timeout
} from '@ahg/atom-client';
```

**Exception properties:**

```typescript
try {
  await client.descriptions.get('non-existent');
} catch (error) {
  if (error instanceof AtomAPIError) {
    console.log(error.message);      // "Description not found"
    console.log(error.statusCode);   // 404
    console.log(error.errorType);    // "not_found"
    console.log(error.responseData); // Full response object
    console.log(error.requestId);    // "req_a1b2c3d4"
  }

  if (error instanceof AtomValidationError) {
    console.log(error.errors);       // { title: ["Required"] }
  }
}
```

---

## Error Handling Patterns

### Basic Try/Catch

**Python:**
```python
from atom_ahg import AtomClient, AtomNotFoundError, AtomAPIError

client = AtomClient(...)

try:
    desc = client.descriptions.get("my-slug")
except AtomNotFoundError:
    print("Description not found")
except AtomAPIError as e:
    print(f"API error: {e.message}")
```

**JavaScript:**
```typescript
import { AtomClient, AtomNotFoundError, AtomAPIError } from '@ahg/atom-client';

const client = new AtomClient({...});

try {
  const desc = await client.descriptions.get('my-slug');
} catch (error) {
  if (error instanceof AtomNotFoundError) {
    console.log('Description not found');
  } else if (error instanceof AtomAPIError) {
    console.log(`API error: ${error.message}`);
  }
}
```

### Comprehensive Error Handling

**Python:**
```python
from atom_ahg import (
    AtomClient,
    AtomAPIError,
    AtomAuthenticationError,
    AtomForbiddenError,
    AtomNotFoundError,
    AtomValidationError,
    AtomRateLimitError,
    AtomServerError,
    AtomNetworkError,
    AtomTimeoutError,
)
import time

def safe_get_description(client, slug):
    """Get a description with comprehensive error handling."""
    try:
        return client.descriptions.get(slug)

    except AtomNotFoundError:
        print(f"Description '{slug}' not found")
        return None

    except AtomAuthenticationError:
        print("Authentication failed - check your API key")
        raise

    except AtomForbiddenError as e:
        print(f"Permission denied: {e.message}")
        raise

    except AtomValidationError as e:
        print(f"Validation failed: {e.errors}")
        raise

    except AtomRateLimitError as e:
        print(f"Rate limited - waiting {e.retry_after}s")
        time.sleep(e.retry_after)
        return safe_get_description(client, slug)  # Retry

    except AtomServerError as e:
        print(f"Server error (request: {e.request_id})")
        raise

    except AtomNetworkError:
        print("Network error - check connection")
        raise

    except AtomTimeoutError:
        print("Request timed out")
        raise

    except AtomAPIError as e:
        print(f"Unexpected API error: {e.message}")
        raise
```

**JavaScript:**
```typescript
import {
  AtomClient,
  AtomAPIError,
  AtomAuthenticationError,
  AtomForbiddenError,
  AtomNotFoundError,
  AtomValidationError,
  AtomRateLimitError,
  AtomServerError,
  AtomNetworkError,
  AtomTimeoutError,
} from '@ahg/atom-client';

async function safeGetDescription(client: AtomClient, slug: string) {
  try {
    return await client.descriptions.get(slug);
  } catch (error) {
    if (error instanceof AtomNotFoundError) {
      console.log(`Description '${slug}' not found`);
      return null;
    }

    if (error instanceof AtomAuthenticationError) {
      console.log('Authentication failed - check your API key');
      throw error;
    }

    if (error instanceof AtomForbiddenError) {
      console.log(`Permission denied: ${error.message}`);
      throw error;
    }

    if (error instanceof AtomValidationError) {
      console.log(`Validation failed: ${JSON.stringify(error.errors)}`);
      throw error;
    }

    if (error instanceof AtomRateLimitError) {
      console.log(`Rate limited - waiting ${error.retryAfter}s`);
      await new Promise(r => setTimeout(r, error.retryAfter * 1000));
      return safeGetDescription(client, slug); // Retry
    }

    if (error instanceof AtomServerError) {
      console.log(`Server error (request: ${error.requestId})`);
      throw error;
    }

    if (error instanceof AtomNetworkError) {
      console.log('Network error - check connection');
      throw error;
    }

    if (error instanceof AtomTimeoutError) {
      console.log('Request timed out');
      throw error;
    }

    if (error instanceof AtomAPIError) {
      console.log(`Unexpected API error: ${error.message}`);
      throw error;
    }

    throw error;
  }
}
```

### Batch Operation Error Handling

Batch operations return results for each operation:

```python
results = client.batch.execute([
    {"method": "GET", "path": "/descriptions/valid-slug"},
    {"method": "GET", "path": "/descriptions/invalid-slug"},
    {"method": "POST", "path": "/descriptions", "body": {"title": ""}},
])

for i, result in enumerate(results["results"]):
    if result["status"] >= 400:
        print(f"Operation {i} failed: {result['body'].get('message')}")
    else:
        print(f"Operation {i} succeeded")

print(f"Success: {results['success_count']}, Failed: {results['failure_count']}")
```

### Validation Error Details

**Python:**
```python
from atom_ahg import AtomValidationError

try:
    client.descriptions.create({
        # Missing required fields
    })
except AtomValidationError as e:
    print("Validation errors:")
    for field, messages in e.errors.items():
        for msg in messages:
            print(f"  {field}: {msg}")

# Output:
# Validation errors:
#   title: Title is required
#   level_of_description_id: Level of description is required
```

**JavaScript:**
```typescript
import { AtomValidationError } from '@ahg/atom-client';

try {
  await client.descriptions.create({
    // Missing required fields
  });
} catch (error) {
  if (error instanceof AtomValidationError) {
    console.log('Validation errors:');
    for (const [field, messages] of Object.entries(error.errors)) {
      for (const msg of messages as string[]) {
        console.log(`  ${field}: ${msg}`);
      }
    }
  }
}
```

---

## Retry Strategies

### Built-in Retry

Both SDKs have built-in retry with exponential backoff:

**Python:**
```python
client = AtomClient(
    base_url="...",
    api_key="...",
    max_retries=3,
    retry_base_delay=1.0,
    retry_max_delay=60.0,
)
```

**JavaScript:**
```typescript
const client = new AtomClient({
  baseUrl: '...',
  apiKey: '...',
  retry: {
    maxRetries: 3,
    baseDelay: 1000,
    maxDelay: 60000,
  }
});
```

### Custom Retry Logic

**Python:**
```python
import time
from atom_ahg import AtomClient, AtomRateLimitError, AtomServerError

def with_retry(func, max_attempts=3, base_delay=1):
    """Custom retry wrapper with exponential backoff."""
    for attempt in range(max_attempts):
        try:
            return func()
        except AtomRateLimitError as e:
            if attempt == max_attempts - 1:
                raise
            delay = e.retry_after or base_delay * (2 ** attempt)
            print(f"Rate limited, waiting {delay}s...")
            time.sleep(delay)
        except AtomServerError as e:
            if attempt == max_attempts - 1:
                raise
            delay = base_delay * (2 ** attempt)
            print(f"Server error, retrying in {delay}s...")
            time.sleep(delay)

# Usage
result = with_retry(lambda: client.descriptions.list(limit=100))
```

### Circuit Breaker Pattern

For high-availability applications:

```python
import time
from dataclasses import dataclass
from atom_ahg import AtomClient, AtomServerError

@dataclass
class CircuitBreaker:
    failure_threshold: int = 5
    reset_timeout: int = 60
    failures: int = 0
    last_failure: float = 0
    state: str = "closed"  # closed, open, half-open

    def call(self, func):
        if self.state == "open":
            if time.time() - self.last_failure > self.reset_timeout:
                self.state = "half-open"
            else:
                raise RuntimeError("Circuit breaker is open")

        try:
            result = func()
            if self.state == "half-open":
                self.state = "closed"
                self.failures = 0
            return result
        except AtomServerError:
            self.failures += 1
            self.last_failure = time.time()
            if self.failures >= self.failure_threshold:
                self.state = "open"
            raise

# Usage
breaker = CircuitBreaker()
client = AtomClient(...)

try:
    result = breaker.call(lambda: client.descriptions.list())
except RuntimeError as e:
    print("Service unavailable, circuit breaker open")
except AtomServerError:
    print("Server error")
```

---

## Logging Errors

### Python

```python
import logging
from atom_ahg import AtomClient, AtomAPIError

logging.basicConfig(level=logging.INFO)
logger = logging.getLogger(__name__)

client = AtomClient(...)

try:
    client.descriptions.get("slug")
except AtomAPIError as e:
    logger.error(
        "API request failed",
        extra={
            "error_type": e.error_type,
            "status_code": e.status_code,
            "message": e.message,
            "request_id": e.request_id,
        }
    )
```

### JavaScript

```typescript
import { AtomClient, AtomAPIError } from '@ahg/atom-client';

const client = new AtomClient({...});

try {
  await client.descriptions.get('slug');
} catch (error) {
  if (error instanceof AtomAPIError) {
    console.error('API request failed', {
      errorType: error.errorType,
      statusCode: error.statusCode,
      message: error.message,
      requestId: error.requestId,
    });
  }
}
```

---

## Debugging Tips

### Enable Debug Logging

**Python:**
```python
import logging
logging.basicConfig(level=logging.DEBUG)

# SDK will log all requests and responses
client = AtomClient(...)
```

**JavaScript:**
```typescript
// SDK respects DEBUG environment variable
// DEBUG=atom-client:* node your-script.js
```

### Include Request ID in Bug Reports

When reporting issues, always include the `request_id`:

```
Bug Report:
- Endpoint: GET /api/v2/descriptions/my-slug
- Request ID: req_a1b2c3d4e5f6
- Error: 500 Internal Server Error
- Time: 2024-01-15T10:30:00Z
```

### Test Error Handling

```python
# Test that your error handling works
from atom_ahg import AtomClient, AtomNotFoundError

client = AtomClient(...)

# This should be handled gracefully
try:
    client.descriptions.get("definitely-does-not-exist-12345")
    assert False, "Should have raised NotFoundError"
except AtomNotFoundError:
    print("Correctly handled NotFoundError")
```
