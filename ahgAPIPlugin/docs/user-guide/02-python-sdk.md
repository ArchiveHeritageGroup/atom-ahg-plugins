# Python SDK User Guide

The `atom-ahg` Python SDK provides a complete, type-safe client for the AtoM AHG Framework REST API v2.

## Installation

```bash
pip install atom-ahg
```

**Requirements:**
- Python 3.8+
- httpx (installed automatically)

## Quick Start

```python
from atom_ahg import AtomClient

# Initialize the client
client = AtomClient(
    base_url="https://your-atom-instance.com",
    api_key="your-api-key"
)

# List descriptions
result = client.descriptions.list(limit=10)
print(f"Found {result.total} descriptions")

for desc in result.results:
    print(f"- {desc['title']} ({desc['slug']})")

# Get a specific description
desc = client.descriptions.get("my-description-slug")
print(desc['title'])
```

## Configuration

```python
from atom_ahg import AtomClient, ClientConfig

# Full configuration options
client = AtomClient(
    base_url="https://your-atom-instance.com",
    api_key="your-api-key",
    timeout=60.0,           # Request timeout in seconds
    culture="en",           # Default language
    max_retries=3,          # Retry attempts for failed requests
    retry_base_delay=1.0,   # Initial retry delay
    retry_max_delay=60.0,   # Maximum retry delay
)
```

### Environment Variables

You can also configure the client using environment variables:

```bash
export ATOM_BASE_URL="https://your-atom-instance.com"
export ATOM_API_KEY="your-api-key"
```

```python
import os
from atom_ahg import AtomClient

client = AtomClient(
    base_url=os.environ["ATOM_BASE_URL"],
    api_key=os.environ["ATOM_API_KEY"]
)
```

## Resources

### Descriptions (Archival Records)

```python
# List descriptions with filtering
result = client.descriptions.list(
    limit=20,
    skip=0,
    repository="my-archive",
    level="fonds",
    sector="archive",
    sort="updated_desc"
)

# Get a specific description
desc = client.descriptions.get("my-slug", full=True)

# Create a new description
new_desc = client.descriptions.create({
    "title": "New Collection",
    "level_of_description_id": 220,
    "scope_and_content": "Description of the collection..."
})

# Update a description
updated = client.descriptions.update("my-slug", {
    "title": "Updated Title"
})

# Delete a description
client.descriptions.delete("my-slug")

# Iterate through all descriptions with pagination
for page in client.descriptions.paginate(page_size=50):
    for desc in page.results:
        print(desc['title'])
```

### Authorities (Name Records)

```python
# List authorities
result = client.authorities.list(
    entity_type="corporate_body",
    limit=20
)

# Get a specific authority
auth = client.authorities.get("organization-slug")

# Paginate through all authorities
for page in client.authorities.paginate(page_size=50):
    for auth in page.results:
        print(auth['authorized_form_of_name'])
```

### Repositories

```python
# List all repositories
repos = client.repositories.list()

for repo in repos.results:
    print(f"{repo['name']} - {repo['slug']}")
```

### Taxonomies and Terms

```python
# List taxonomies
taxonomies = client.taxonomies.list()

# Get terms for a specific taxonomy
terms = client.taxonomies.get_terms(taxonomy_id=42)
```

### Search

```python
# Basic search
results = client.search.search(query="heritage artifacts")

# Advanced search with filters
results = client.search.search(
    query="artifact",
    entity_type="informationobject",
    repository="museum-collection",
    sector="museum",
    date_from="1900-01-01",
    date_to="1950-12-31",
    limit=50
)

for result in results.results:
    print(f"{result['title']} - {result['score']}")
```

### Batch Operations

```python
# Get multiple records at once
results = client.batch.get_many(["slug-1", "slug-2", "slug-3"])

# Create multiple records
new_items = client.batch.create_many([
    {"title": "Item 1"},
    {"title": "Item 2"},
    {"title": "Item 3"}
])

# Execute custom batch operations (up to 100)
results = client.batch.execute([
    {"method": "GET", "path": "/descriptions/item-1"},
    {"method": "POST", "path": "/descriptions", "body": {"title": "New"}},
    {"method": "DELETE", "path": "/descriptions/old-item"}
])
```

### Conditions (SPECTRUM)

```python
# List condition assessments
conditions = client.conditions.list(overall_condition="good")

# Create a condition assessment
condition = client.conditions.create({
    "object_id": 123,
    "checked_by": "John Smith",
    "date_checked": "2024-01-15",
    "overall_condition": "good",
    "condition_note": "Minor wear on edges"
})

# Get condition details
cond = client.conditions.get(condition["id"])

# Upload a condition photo
import base64

with open("photo.jpg", "rb") as f:
    photo_data = base64.b64encode(f.read()).decode()

client.conditions.upload_photo(
    condition_id=condition["id"],
    filename="damage-detail.jpg",
    file_data=photo_data,
    photo_type="detail",
    caption="Edge wear detail"
)
```

### Heritage Assets (GRAP 103 / IPSAS 45)

```python
# List heritage assets
assets = client.assets.list()

# Create an asset record
asset = client.assets.create({
    "object_id": 123,
    "acquisition_date": "2024-01-15",
    "acquisition_cost": 50000.00,
    "currency_code": "ZAR",
    "acquisition_method": "purchase"
})

# Add a valuation
valuation = client.valuations.create({
    "asset_id": asset["id"],
    "valuation_date": "2024-06-01",
    "valuation_amount": 75000.00,
    "currency_code": "ZAR",
    "valuation_method": "market_comparison",
    "valuer_name": "Jane Doe"
})
```

### Privacy/Compliance (GDPR, POPIA)

```python
# List Data Subject Access Requests
dsars = client.privacy.list_dsars(status="pending")

# Create a DSAR
dsar = client.privacy.create_dsar({
    "requester_name": "John Smith",
    "requester_email": "john@example.com",
    "request_type": "access",
    "subject_matter": "Personal records request"
})

# Update DSAR status
client.privacy.update_dsar(dsar["id"], {
    "status": "in_progress",
    "assigned_to": "admin@example.com"
})

# Report a data breach
breach = client.privacy.create_breach({
    "breach_date": "2024-01-20",
    "severity": "high",
    "description": "Unauthorized access detected",
    "affected_records": 50
})
```

### File Uploads

```python
import base64

# Read file and convert to base64
with open("document.pdf", "rb") as f:
    file_data = base64.b64encode(f.read()).decode()

# Upload to a description
result = client.uploads.upload_to_description(
    slug="my-description",
    filename="document.pdf",
    file_data=file_data,
    title="Supporting Document",
    usage_type="master"
)
```

### Mobile Sync

```python
# Get changes since last sync
changes = client.sync.get_changes(
    since="2024-01-01T00:00:00Z",
    entity_types=["condition", "asset"]
)

for change in changes["changes"]:
    print(f"{change['action']}: {change['entity_type']} {change['entity_id']}")

# Push offline changes
result = client.sync.push_batch([
    {
        "entity_type": "condition",
        "action": "create",
        "data": {"object_id": 123, "overall_condition": "good"},
        "client_id": "offline-1"
    },
    {
        "entity_type": "condition",
        "action": "update",
        "entity_id": 456,
        "data": {"condition_note": "Updated note"},
        "client_id": "offline-2"
    }
])
```

## Async Support

The SDK provides async versions of all methods:

```python
import asyncio
from atom_ahg import AtomClient

async def main():
    client = AtomClient(
        base_url="https://your-atom-instance.com",
        api_key="your-api-key"
    )

    # Async list
    result = await client.descriptions.list_async(limit=10)

    # Async pagination
    async for page in client.descriptions.paginate_async(page_size=50):
        for desc in page.results:
            print(desc['title'])

    # Concurrent requests
    tasks = [
        client.descriptions.get_async("slug-1"),
        client.descriptions.get_async("slug-2"),
        client.descriptions.get_async("slug-3")
    ]
    results = await asyncio.gather(*tasks)

    client.close()

asyncio.run(main())
```

## Error Handling

```python
from atom_ahg import (
    AtomClient,
    AtomAPIError,
    AtomAuthenticationError,
    AtomNotFoundError,
    AtomRateLimitError,
    AtomValidationError,
    AtomServerError
)

client = AtomClient(...)

try:
    desc = client.descriptions.get("non-existent")
except AtomNotFoundError:
    print("Description not found")
except AtomAuthenticationError:
    print("Invalid API key")
except AtomRateLimitError as e:
    print(f"Rate limited. Retry after {e.retry_after} seconds")
except AtomValidationError as e:
    print(f"Validation error: {e.errors}")
except AtomServerError:
    print("Server error - try again later")
except AtomAPIError as e:
    print(f"API error: {e.message} (status: {e.status_code})")
```

## Pagination

### Simple Iteration

```python
# Iterate through all pages
for page in client.descriptions.paginate(page_size=50):
    print(f"Page {page.page_number} of {page.total_pages}")
    for desc in page.results:
        print(desc['title'])
```

### Manual Pagination

```python
# First page
result = client.descriptions.list(limit=20, skip=0)
print(f"Total: {result.total}")

# Check if more pages exist
while result.has_more:
    result = client.descriptions.list(
        limit=20,
        skip=result.skip + result.limit
    )
    for desc in result.results:
        print(desc['title'])
```

### Collect All Results

```python
from atom_ahg import paginateAll

# Get all descriptions (use with caution on large datasets)
all_descriptions = list(paginateAll(
    client.descriptions.paginate(page_size=100)
))
```

## Context Manager

```python
from atom_ahg import AtomClient

# Automatically close connection when done
with AtomClient(base_url="...", api_key="...") as client:
    result = client.descriptions.list()
    # ...
# Connection closed automatically
```

## Retry Configuration

The SDK automatically retries failed requests:

```python
client = AtomClient(
    base_url="...",
    api_key="...",
    max_retries=5,           # Number of retry attempts
    retry_base_delay=1.0,    # Initial delay (seconds)
    retry_max_delay=120.0,   # Maximum delay (seconds)
)
```

Retries are attempted for:
- Rate limit errors (429)
- Server errors (5xx)
- Network timeouts

## Type Hints

The SDK includes comprehensive type hints:

```python
from atom_ahg import AtomClient
from atom_ahg.types import DescriptionCreate, DescriptionDetail

client = AtomClient(...)

# Type-safe creation
data: DescriptionCreate = {
    "title": "New Record",
    "level_of_description_id": 220
}
result: DescriptionDetail = client.descriptions.create(data)
```

## Logging

Enable debug logging to see API requests:

```python
import logging

logging.basicConfig(level=logging.DEBUG)

# SDK will log all requests and responses
client = AtomClient(...)
```

## Best Practices

1. **Use pagination** for large datasets instead of high limits
2. **Handle rate limits** gracefully with retry logic (built-in)
3. **Close the client** when done to release connections
4. **Use async** for high-throughput applications
5. **Cache results** when appropriate to reduce API calls
6. **Use batch operations** for multiple creates/updates

## Examples

See the `examples/` directory in the SDK repository for complete examples:

- `basic_usage.py` - Simple CRUD operations
- `batch_import.py` - Importing multiple records
- `condition_report.py` - Creating condition assessments
- `search_export.py` - Searching and exporting results
- `async_sync.py` - Mobile sync workflow
