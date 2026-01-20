# API Reference

Complete reference for the AtoM AHG Framework REST API v2.

## Base URL

```
https://your-atom-instance.com/api/v2
```

## Authentication

All requests require authentication via API key:

```
X-API-Key: your-api-key
```

See [Authentication](./03-authentication.md) for details.

---

## Descriptions

Archival records (fonds, series, files, items).

### List Descriptions

```
GET /descriptions
```

**Query Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `limit` | integer | Number of results (default: 10, max: 100) |
| `skip` | integer | Number of results to skip (default: 0) |
| `repository` | string | Filter by repository slug |
| `level` | string | Filter by level (fonds, series, file, item) |
| `sector` | string | Filter by sector (archive, museum, library, dam) |
| `parent` | string | Filter by parent slug |
| `sort` | string | Sort order (title_asc, title_desc, updated_asc, updated_desc) |
| `culture` | string | Language code (default: en) |

**Response:**

```json
{
  "success": true,
  "data": {
    "results": [
      {
        "slug": "example-fonds",
        "title": "Example Fonds",
        "reference_code": "EF",
        "level_of_description": "Fonds",
        "repository": "Main Archive",
        "date_range": "1900-1950",
        "extent": "5 boxes",
        "updated_at": "2024-01-15T10:30:00Z"
      }
    ],
    "total": 150,
    "limit": 10,
    "skip": 0
  }
}
```

### Get Description

```
GET /descriptions/{slug}
```

**Path Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `slug` | string | Description slug |

**Query Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `full` | boolean | Include all fields (default: true) |
| `culture` | string | Language code |

**Response:**

```json
{
  "success": true,
  "data": {
    "id": 123,
    "slug": "example-fonds",
    "title": "Example Fonds",
    "reference_code": "EF",
    "level_of_description": "Fonds",
    "level_of_description_id": 220,
    "repository": {
      "slug": "main-archive",
      "name": "Main Archive"
    },
    "scope_and_content": "Description of the fonds...",
    "dates": [
      {
        "date": "1900-1950",
        "type": "Creation",
        "start_date": "1900-01-01",
        "end_date": "1950-12-31"
      }
    ],
    "extent_and_medium": "5 boxes (2.5 linear meters)",
    "arrangement": "Arranged chronologically",
    "conditions_governing_access": "Open to researchers",
    "finding_aids": "Inventory available",
    "related_units_of_description": "See also EF-2",
    "notes": [],
    "access_points": [
      {
        "name": "South Africa",
        "type": "place"
      }
    ],
    "digital_objects": [],
    "parent": null,
    "children_count": 5,
    "created_at": "2024-01-01T00:00:00Z",
    "updated_at": "2024-01-15T10:30:00Z"
  }
}
```

### Create Description

```
POST /descriptions
```

**Request Body:**

```json
{
  "title": "New Collection",
  "level_of_description_id": 220,
  "scope_and_content": "Description of the collection...",
  "reference_code": "NC",
  "repository_id": 1,
  "parent_id": null,
  "date_of_creation": "1950-1960"
}
```

**Required Fields:**

| Field | Type | Description |
|-------|------|-------------|
| `title` | string | Title of the description |
| `level_of_description_id` | integer | Level term ID (220=Fonds, 221=Series, etc.) |

**Optional Fields:**

| Field | Type | Description |
|-------|------|-------------|
| `reference_code` | string | Reference code |
| `repository_id` | integer | Repository ID |
| `parent_id` | integer | Parent description ID |
| `scope_and_content` | string | Scope and content |
| `date_of_creation` | string | Date or date range |
| `extent_and_medium` | string | Extent and medium |
| `arrangement` | string | Arrangement |
| `conditions_governing_access` | string | Access conditions |
| `finding_aids` | string | Finding aids |

**Response:** `201 Created`

```json
{
  "success": true,
  "data": {
    "id": 456,
    "slug": "new-collection",
    "title": "New Collection",
    ...
  }
}
```

### Update Description

```
PUT /descriptions/{slug}
```

**Request Body:**

```json
{
  "title": "Updated Title",
  "scope_and_content": "Updated description..."
}
```

**Response:** `200 OK`

### Delete Description

```
DELETE /descriptions/{slug}
```

**Response:** `204 No Content`

---

## Authorities

Name records (persons, organizations, families).

### List Authorities

```
GET /authorities
```

**Query Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `limit` | integer | Number of results (default: 10) |
| `skip` | integer | Results to skip |
| `entity_type` | string | Filter by type (person, corporate_body, family) |

**Response:**

```json
{
  "success": true,
  "data": {
    "results": [
      {
        "slug": "john-smith",
        "authorized_form_of_name": "Smith, John",
        "entity_type": "person",
        "dates_of_existence": "1900-1980"
      }
    ],
    "total": 50,
    "limit": 10,
    "skip": 0
  }
}
```

### Get Authority

```
GET /authorities/{slug}
```

**Response:**

```json
{
  "success": true,
  "data": {
    "id": 789,
    "slug": "john-smith",
    "authorized_form_of_name": "Smith, John",
    "entity_type": "person",
    "dates_of_existence": "1900-1980",
    "history": "Biography...",
    "places": ["Johannesburg", "Cape Town"],
    "relationships": [...]
  }
}
```

---

## Repositories

Holding institutions.

### List Repositories

```
GET /repositories
```

**Response:**

```json
{
  "success": true,
  "data": {
    "results": [
      {
        "slug": "main-archive",
        "name": "Main Archive",
        "identifier": "MA",
        "location": "Johannesburg"
      }
    ],
    "total": 5,
    "limit": 10,
    "skip": 0
  }
}
```

---

## Taxonomies

Controlled vocabularies and terms.

### List Taxonomies

```
GET /taxonomies
```

**Response:**

```json
{
  "success": true,
  "data": {
    "results": [
      {
        "id": 42,
        "name": "Level of description",
        "usage": "levels"
      }
    ],
    "total": 20
  }
}
```

### Get Taxonomy Terms

```
GET /taxonomies/{id}/terms
```

**Response:**

```json
{
  "success": true,
  "data": {
    "results": [
      {
        "id": 220,
        "name": "Fonds",
        "parent_id": null
      },
      {
        "id": 221,
        "name": "Series",
        "parent_id": null
      }
    ]
  }
}
```

---

## Search

Full-text and faceted search.

### Search

```
GET /search
```

**Query Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `query` | string | Search query (required) |
| `limit` | integer | Results to return |
| `skip` | integer | Results to skip |
| `entity_type` | string | Filter by entity type |
| `repository` | string | Filter by repository slug |
| `sector` | string | Filter by sector |
| `date_from` | string | Start date (YYYY-MM-DD) |
| `date_to` | string | End date (YYYY-MM-DD) |
| `level` | string | Level of description |

**Response:**

```json
{
  "success": true,
  "data": {
    "results": [
      {
        "slug": "example-item",
        "title": "Example Item",
        "entity_type": "informationobject",
        "reference_code": "EF-001",
        "level_of_description": "Item",
        "scope_and_content": "Matching content...",
        "score": 0.95
      }
    ],
    "total": 25,
    "limit": 10,
    "skip": 0,
    "facets": {
      "level": {
        "fonds": 5,
        "series": 10,
        "item": 10
      },
      "repository": {
        "main-archive": 20,
        "branch-archive": 5
      }
    }
  }
}
```

---

## Batch Operations

Execute multiple operations in a single request (up to 100).

### Execute Batch

```
POST /batch
```

**Request Body:**

```json
{
  "operations": [
    {
      "method": "GET",
      "path": "/descriptions/item-1"
    },
    {
      "method": "POST",
      "path": "/descriptions",
      "body": {
        "title": "New Item",
        "level_of_description_id": 227
      }
    },
    {
      "method": "PUT",
      "path": "/descriptions/item-2",
      "body": {
        "title": "Updated Item"
      }
    },
    {
      "method": "DELETE",
      "path": "/descriptions/item-3"
    }
  ]
}
```

**Response:**

```json
{
  "success": true,
  "data": {
    "results": [
      {
        "status": 200,
        "body": { "slug": "item-1", "title": "Item 1", ... }
      },
      {
        "status": 201,
        "body": { "slug": "new-item", "title": "New Item", ... }
      },
      {
        "status": 200,
        "body": { "slug": "item-2", "title": "Updated Item", ... }
      },
      {
        "status": 204,
        "body": null
      }
    ],
    "success_count": 4,
    "failure_count": 0
  }
}
```

---

## Conditions

SPECTRUM-compliant condition assessments.

### List Conditions

```
GET /conditions
```

**Query Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `object_id` | integer | Filter by object ID |
| `overall_condition` | string | Filter by condition (good, fair, poor) |
| `checked_by` | string | Filter by inspector |

**Response:**

```json
{
  "success": true,
  "data": {
    "results": [
      {
        "id": 1,
        "object_id": 123,
        "date_checked": "2024-01-15",
        "checked_by": "John Smith",
        "overall_condition": "good",
        "condition_note": "Minor wear"
      }
    ],
    "total": 10
  }
}
```

### Create Condition

```
POST /conditions
```

**Request Body:**

```json
{
  "object_id": 123,
  "checked_by": "John Smith",
  "date_checked": "2024-01-15",
  "overall_condition": "good",
  "structural_condition": "good",
  "surface_condition": "fair",
  "condition_note": "Minor wear on edges"
}
```

### Upload Condition Photo

```
POST /conditions/{id}/photos
```

**Request Body:**

```json
{
  "filename": "damage-photo.jpg",
  "file_data": "base64-encoded-data...",
  "photo_type": "detail",
  "caption": "Edge damage detail"
}
```

---

## Assets

Heritage asset management (GRAP 103 / IPSAS 45).

### List Assets

```
GET /assets
```

**Response:**

```json
{
  "success": true,
  "data": {
    "results": [
      {
        "id": 1,
        "object_id": 123,
        "acquisition_date": "2024-01-15",
        "acquisition_cost": 50000.00,
        "currency_code": "ZAR",
        "acquisition_method": "purchase",
        "current_value": 75000.00
      }
    ],
    "total": 50
  }
}
```

### Create Asset

```
POST /assets
```

**Request Body:**

```json
{
  "object_id": 123,
  "acquisition_date": "2024-01-15",
  "acquisition_cost": 50000.00,
  "currency_code": "ZAR",
  "acquisition_method": "purchase"
}
```

---

## Valuations

Asset valuations for financial reporting.

### List Valuations

```
GET /valuations
```

**Query Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `asset_id` | integer | Filter by asset ID |

### Create Valuation

```
POST /valuations
```

**Request Body:**

```json
{
  "asset_id": 1,
  "valuation_date": "2024-06-01",
  "valuation_amount": 75000.00,
  "currency_code": "ZAR",
  "valuation_method": "market_comparison",
  "valuer_name": "Jane Doe"
}
```

---

## Privacy (DSARs)

Data Subject Access Requests for GDPR/POPIA compliance.

### List DSARs

```
GET /privacy/dsars
```

**Query Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `status` | string | Filter by status (pending, in_progress, completed) |

### Create DSAR

```
POST /privacy/dsars
```

**Request Body:**

```json
{
  "requester_name": "John Smith",
  "requester_email": "john@example.com",
  "request_type": "access",
  "subject_matter": "Request for personal data"
}
```

### Update DSAR

```
PUT /privacy/dsars/{id}
```

**Request Body:**

```json
{
  "status": "completed",
  "resolution_notes": "Data provided via email"
}
```

---

## Privacy (Breaches)

Data breach reporting.

### List Breaches

```
GET /privacy/breaches
```

### Create Breach

```
POST /privacy/breaches
```

**Request Body:**

```json
{
  "breach_date": "2024-01-20",
  "severity": "high",
  "description": "Unauthorized access detected",
  "affected_records": 50
}
```

---

## Uploads

File uploads for digital objects.

### Upload to Description

```
POST /uploads/descriptions/{slug}
```

**Request Body:**

```json
{
  "filename": "document.pdf",
  "file_data": "base64-encoded-data...",
  "title": "Supporting Document",
  "usage_type": "master"
}
```

**Response:**

```json
{
  "success": true,
  "data": {
    "id": 789,
    "filename": "document.pdf",
    "mime_type": "application/pdf",
    "size": 1234567,
    "url": "/uploads/123/document.pdf"
  }
}
```

---

## Sync

Mobile/offline data synchronization.

### Get Changes

```
GET /sync/changes
```

**Query Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `since` | string | ISO 8601 timestamp |
| `entity_types` | string | Comma-separated entity types |

**Response:**

```json
{
  "success": true,
  "data": {
    "changes": [
      {
        "entity_type": "condition",
        "entity_id": 123,
        "action": "create",
        "data": {...},
        "timestamp": "2024-01-15T10:30:00Z"
      }
    ],
    "server_time": "2024-01-15T12:00:00Z"
  }
}
```

### Push Changes

```
POST /sync/push
```

**Request Body:**

```json
{
  "changes": [
    {
      "entity_type": "condition",
      "action": "create",
      "data": {...},
      "client_id": "offline-1"
    }
  ]
}
```

**Response:**

```json
{
  "success": true,
  "data": {
    "success_count": 1,
    "failure_count": 0,
    "results": [
      {
        "client_id": "offline-1",
        "status": "success",
        "entity_id": 456
      }
    ]
  }
}
```

---

## API Keys

Manage API keys (admin only).

### List API Keys

```
GET /api-keys
```

### Create API Key

```
POST /api-keys
```

**Request Body:**

```json
{
  "name": "Mobile App",
  "scopes": ["read", "write"],
  "expires_at": "2025-01-01T00:00:00Z"
}
```

**Response:**

```json
{
  "success": true,
  "data": {
    "id": 10,
    "name": "Mobile App",
    "key": "ak_live_xxxxxxxxxxxxxxxx",
    "scopes": ["read", "write"],
    "expires_at": "2025-01-01T00:00:00Z"
  }
}
```

**Note:** The full API key is only returned once at creation.

### Revoke API Key

```
DELETE /api-keys/{id}
```

---

## Error Responses

All error responses follow this format:

```json
{
  "success": false,
  "error": "error_type",
  "message": "Human-readable error message",
  "errors": {
    "field": ["Validation error message"]
  }
}
```

### HTTP Status Codes

| Code | Error Type | Description |
|------|------------|-------------|
| 400 | `validation_error` | Invalid request data |
| 401 | `authentication_error` | Invalid or missing API key |
| 403 | `forbidden` | Insufficient permissions |
| 404 | `not_found` | Resource not found |
| 429 | `rate_limit` | Too many requests |
| 500 | `server_error` | Internal server error |

See [Error Handling](./04-error-handling.md) for details.
