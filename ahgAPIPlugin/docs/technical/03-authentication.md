# Authentication

This document covers authentication and authorization for the AtoM AHG API.

## Overview

The API supports two authentication methods:

1. **API Key** (recommended): Long-lived keys for programmatic access
2. **Bearer Token**: Short-lived tokens for session-based access

## API Key Authentication

### Header Format

Include your API key in the `X-API-Key` header:

```http
GET /api/v2/descriptions HTTP/1.1
Host: your-atom-instance.com
X-API-Key: ak_live_xxxxxxxxxxxxxxxxxxxxxxxx
```

### Key Format

API keys follow this format:

```
ak_{environment}_{random_string}

Examples:
- ak_live_a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6
- ak_test_x9y8z7w6v5u4t3s2r1q0p9o8n7m6l5k4
```

**Prefixes:**
- `ak_live_` - Production environment
- `ak_test_` - Test/development environment

### Creating API Keys

#### Via Admin Interface

1. Log in as administrator
2. Navigate to **Admin > API Keys**
3. Click **Generate New Key**
4. Configure settings:
   - **Name**: Descriptive label
   - **Scopes**: Permissions (read, write, delete, admin)
   - **Expiration**: Optional expiry date
5. Copy the key immediately (shown only once)

#### Via API

```bash
curl -X POST \
  -H "X-API-Key: your-admin-key" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Mobile App",
    "scopes": ["read", "write"],
    "expires_at": "2025-12-31T23:59:59Z"
  }' \
  https://your-atom-instance.com/api/v2/api-keys
```

Response:
```json
{
  "success": true,
  "data": {
    "id": 10,
    "name": "Mobile App",
    "key": "ak_live_a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6",
    "scopes": ["read", "write"],
    "expires_at": "2025-12-31T23:59:59Z",
    "created_at": "2024-01-15T10:00:00Z"
  }
}
```

**Important:** The full key is only returned at creation time. Store it securely.

### Revoking API Keys

```bash
curl -X DELETE \
  -H "X-API-Key: your-admin-key" \
  https://your-atom-instance.com/api/v2/api-keys/10
```

## Scopes

API keys have granular permission scopes:

| Scope | Permissions |
|-------|-------------|
| `read` | List and view all records |
| `write` | Create and update records |
| `delete` | Delete records |
| `admin` | Manage API keys and system settings |

### Scope Requirements by Endpoint

| Endpoint | Method | Required Scope |
|----------|--------|----------------|
| `/descriptions` | GET | `read` |
| `/descriptions` | POST | `write` |
| `/descriptions/{slug}` | PUT | `write` |
| `/descriptions/{slug}` | DELETE | `delete` |
| `/api-keys` | * | `admin` |

### Multiple Scopes

Keys can have multiple scopes:

```json
{
  "name": "Full Access",
  "scopes": ["read", "write", "delete"]
}
```

### Checking Your Scopes

```bash
curl -H "X-API-Key: your-key" \
  https://your-atom-instance.com/api/v2/api-keys/me
```

Response:
```json
{
  "success": true,
  "data": {
    "id": 10,
    "name": "Mobile App",
    "scopes": ["read", "write"],
    "expires_at": "2025-12-31T23:59:59Z",
    "last_used_at": "2024-01-15T12:00:00Z"
  }
}
```

## Bearer Token Authentication

For session-based or OAuth-style access:

```http
GET /api/v2/descriptions HTTP/1.1
Host: your-atom-instance.com
Authorization: Bearer eyJhbGciOiJIUzI1NiIs...
```

### Obtaining a Token

```bash
curl -X POST \
  -H "Content-Type: application/json" \
  -d '{
    "username": "user@example.com",
    "password": "password"
  }' \
  https://your-atom-instance.com/api/v2/auth/token
```

Response:
```json
{
  "success": true,
  "data": {
    "access_token": "eyJhbGciOiJIUzI1NiIs...",
    "token_type": "Bearer",
    "expires_in": 3600,
    "refresh_token": "rt_a1b2c3d4e5f6..."
  }
}
```

### Refreshing a Token

```bash
curl -X POST \
  -H "Content-Type: application/json" \
  -d '{
    "refresh_token": "rt_a1b2c3d4e5f6..."
  }' \
  https://your-atom-instance.com/api/v2/auth/refresh
```

## Rate Limiting

The API enforces rate limits to ensure fair usage:

### Default Limits

| Client Type | Requests | Window |
|-------------|----------|--------|
| Anonymous | 10 | 1 minute |
| Authenticated (read) | 100 | 1 minute |
| Authenticated (write) | 50 | 1 minute |
| Admin | 1000 | 1 minute |

### Rate Limit Headers

Every response includes rate limit information:

```http
HTTP/1.1 200 OK
X-RateLimit-Limit: 100
X-RateLimit-Remaining: 95
X-RateLimit-Reset: 1705312800
```

| Header | Description |
|--------|-------------|
| `X-RateLimit-Limit` | Maximum requests allowed |
| `X-RateLimit-Remaining` | Requests remaining |
| `X-RateLimit-Reset` | Unix timestamp when limit resets |

### Rate Limit Exceeded

When rate limited, you'll receive:

```http
HTTP/1.1 429 Too Many Requests
Retry-After: 30
X-RateLimit-Limit: 100
X-RateLimit-Remaining: 0
X-RateLimit-Reset: 1705312800

{
  "success": false,
  "error": "rate_limit",
  "message": "Rate limit exceeded. Retry after 30 seconds."
}
```

**Handling Rate Limits:**

```python
from atom_ahg import AtomClient, AtomRateLimitError
import time

client = AtomClient(...)

try:
    result = client.descriptions.list()
except AtomRateLimitError as e:
    print(f"Rate limited. Waiting {e.retry_after} seconds...")
    time.sleep(e.retry_after)
    result = client.descriptions.list()  # Retry
```

## Security Best Practices

### Key Storage

**Do:**
- Store keys in environment variables
- Use secret management systems (AWS Secrets Manager, HashiCorp Vault)
- Encrypt keys at rest

**Don't:**
- Hard-code keys in source code
- Commit keys to version control
- Share keys via insecure channels

### Environment Variables

```bash
# .env file (not committed)
ATOM_API_KEY=ak_live_xxxxxxxxxxxxxxxx

# Python
import os
from atom_ahg import AtomClient

client = AtomClient(
    base_url=os.environ["ATOM_BASE_URL"],
    api_key=os.environ["ATOM_API_KEY"]
)

# JavaScript
const client = new AtomClient({
  baseUrl: process.env.ATOM_BASE_URL,
  apiKey: process.env.ATOM_API_KEY
});
```

### Key Rotation

1. Generate a new key
2. Update applications to use new key
3. Verify new key works
4. Revoke old key

```python
# Example rotation script
from atom_ahg import AtomClient

admin_client = AtomClient(
    base_url="https://your-atom-instance.com",
    api_key="admin-key"
)

# Create new key
new_key = admin_client.api_keys.create({
    "name": "Mobile App v2",
    "scopes": ["read", "write"]
})

print(f"New key: {new_key['key']}")

# After updating applications, revoke old key
admin_client.api_keys.revoke(old_key_id)
```

### Principle of Least Privilege

Grant only necessary scopes:

```python
# Read-only reporting application
report_key = admin_client.api_keys.create({
    "name": "Reporting Dashboard",
    "scopes": ["read"]  # Only read access
})

# Mobile app that creates conditions
mobile_key = admin_client.api_keys.create({
    "name": "Field App",
    "scopes": ["read", "write"]  # No delete
})
```

### Expiration

Set expiration for temporary access:

```python
from datetime import datetime, timedelta

# Key expires in 90 days
expires = datetime.utcnow() + timedelta(days=90)

temp_key = admin_client.api_keys.create({
    "name": "Contractor Access",
    "scopes": ["read"],
    "expires_at": expires.isoformat() + "Z"
})
```

### Audit Logging

Monitor key usage:

```sql
-- Query API key usage logs
SELECT
    ak.name,
    al.endpoint,
    al.method,
    al.status_code,
    al.created_at
FROM api_key ak
JOIN api_log al ON ak.id = al.api_key_id
WHERE ak.id = 10
ORDER BY al.created_at DESC
LIMIT 100;
```

## Troubleshooting

### 401 Unauthorized

**Causes:**
- Missing `X-API-Key` header
- Invalid or revoked key
- Expired key

**Solution:**
```bash
# Verify key is valid
curl -v -H "X-API-Key: your-key" \
  https://your-atom-instance.com/api/v2/api-keys/me
```

### 403 Forbidden

**Causes:**
- Key lacks required scope
- IP restriction violation

**Solution:**
```bash
# Check your scopes
curl -H "X-API-Key: your-key" \
  https://your-atom-instance.com/api/v2/api-keys/me

# Response shows your scopes
{
  "data": {
    "scopes": ["read"]  # Missing "write" for POST requests
  }
}
```

### Key Not Working After Creation

1. Ensure you copied the complete key
2. Check for leading/trailing whitespace
3. Verify the key hasn't expired

```python
# Test key validity
from atom_ahg import AtomClient, AtomAuthenticationError

try:
    client = AtomClient(
        base_url="https://your-atom-instance.com",
        api_key="ak_live_..."
    )
    info = client.api_keys.me()
    print(f"Key is valid: {info['name']}")
except AtomAuthenticationError:
    print("Key is invalid")
```

## SDK Authentication

### Python

```python
from atom_ahg import AtomClient

# API key (recommended)
client = AtomClient(
    base_url="https://your-atom-instance.com",
    api_key="ak_live_..."
)

# Bearer token
client = AtomClient(
    base_url="https://your-atom-instance.com",
    bearer_token="eyJhbGciOiJIUzI1NiIs..."
)
```

### JavaScript

```typescript
import { AtomClient } from '@ahg/atom-client';

// API key (recommended)
const client = new AtomClient({
  baseUrl: 'https://your-atom-instance.com',
  apiKey: 'ak_live_...'
});

// Bearer token
const client = new AtomClient({
  baseUrl: 'https://your-atom-instance.com',
  bearerToken: 'eyJhbGciOiJIUzI1NiIs...'
});
```

## CORS Configuration

For browser-based applications, configure CORS on your AtoM server:

```nginx
# nginx configuration
location /api/v2 {
    # Allow specific origins
    add_header Access-Control-Allow-Origin "https://your-app.com";
    add_header Access-Control-Allow-Methods "GET, POST, PUT, DELETE, OPTIONS";
    add_header Access-Control-Allow-Headers "X-API-Key, Content-Type";
    add_header Access-Control-Max-Age 3600;

    # Handle preflight
    if ($request_method = OPTIONS) {
        return 204;
    }
}
```

**Warning:** Never use `Access-Control-Allow-Origin: *` with authenticated endpoints.
