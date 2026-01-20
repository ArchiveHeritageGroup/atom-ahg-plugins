# Common Use Cases

This guide provides real-world examples for common integration scenarios with the AtoM AHG API.

## 1. Bulk Import from CSV

Import archival records from a CSV file.

### Python

```python
import csv
from atom_ahg import AtomClient

client = AtomClient(
    base_url="https://your-atom-instance.com",
    api_key="your-api-key"
)

def import_from_csv(filename: str):
    """Import descriptions from a CSV file."""
    with open(filename, 'r') as f:
        reader = csv.DictReader(f)

        # Batch for efficiency (up to 100 at a time)
        batch = []
        for row in reader:
            batch.append({
                "title": row["title"],
                "reference_code": row.get("reference_code"),
                "level_of_description_id": int(row.get("level_id", 227)),  # Item
                "scope_and_content": row.get("description"),
                "date_of_creation": row.get("date"),
            })

            if len(batch) >= 50:
                results = client.batch.create_many(batch)
                print(f"Created {len(results)} records")
                batch = []

        # Handle remaining items
        if batch:
            results = client.batch.create_many(batch)
            print(f"Created {len(results)} records")

import_from_csv("records.csv")
```

### JavaScript

```typescript
import { AtomClient } from '@ahg/atom-client';
import * as fs from 'fs';
import { parse } from 'csv-parse/sync';

const client = new AtomClient({
  baseUrl: 'https://your-atom-instance.com',
  apiKey: 'your-api-key'
});

async function importFromCSV(filename: string) {
  const content = fs.readFileSync(filename, 'utf-8');
  const records = parse(content, { columns: true });

  const batch: any[] = [];
  for (const row of records) {
    batch.push({
      title: row.title,
      reference_code: row.reference_code,
      level_of_description_id: parseInt(row.level_id || '227'),
      scope_and_content: row.description,
      date_of_creation: row.date
    });

    if (batch.length >= 50) {
      const results = await client.batch.createMany(batch);
      console.log(`Created ${results.length} records`);
      batch.length = 0;
    }
  }

  if (batch.length > 0) {
    const results = await client.batch.createMany(batch);
    console.log(`Created ${results.length} records`);
  }
}

importFromCSV('records.csv');
```

## 2. Export to JSON

Export all records for backup or migration.

### Python

```python
import json
from atom_ahg import AtomClient

client = AtomClient(
    base_url="https://your-atom-instance.com",
    api_key="your-api-key"
)

def export_all_descriptions(output_file: str):
    """Export all descriptions to JSON."""
    all_records = []

    for page in client.descriptions.paginate(page_size=100):
        print(f"Fetching page {page.page_number} of {page.total_pages}...")

        for summary in page.results:
            # Get full details for each record
            detail = client.descriptions.get(summary["slug"], full=True)
            all_records.append(detail)

    with open(output_file, "w") as f:
        json.dump(all_records, f, indent=2, default=str)

    print(f"Exported {len(all_records)} records to {output_file}")

export_all_descriptions("backup.json")
```

### JavaScript

```typescript
import { AtomClient } from '@ahg/atom-client';
import * as fs from 'fs';

const client = new AtomClient({
  baseUrl: 'https://your-atom-instance.com',
  apiKey: 'your-api-key'
});

async function exportAllDescriptions(outputFile: string) {
  const allRecords = [];

  for await (const page of client.descriptions.paginate({ pageSize: 100 })) {
    console.log(`Fetching page ${page.pageNumber} of ${page.totalPages}...`);

    for (const summary of page.results) {
      const detail = await client.descriptions.get(summary.slug, { full: true });
      allRecords.push(detail);
    }
  }

  fs.writeFileSync(outputFile, JSON.stringify(allRecords, null, 2));
  console.log(`Exported ${allRecords.length} records to ${outputFile}`);
}

exportAllDescriptions('backup.json');
```

## 3. Search and Filter

Build a search interface for archival records.

### Python

```python
from atom_ahg import AtomClient

client = AtomClient(
    base_url="https://your-atom-instance.com",
    api_key="your-api-key"
)

def search_archive(
    query: str,
    repository: str = None,
    date_from: str = None,
    date_to: str = None,
    sector: str = None
):
    """Search the archive with filters."""
    results = client.search.search(
        query=query,
        repository=repository,
        date_from=date_from,
        date_to=date_to,
        sector=sector,
        limit=50
    )

    print(f"Found {results.total} results for '{query}'")
    print("-" * 50)

    for item in results.results:
        print(f"Title: {item['title']}")
        print(f"Reference: {item.get('reference_code', 'N/A')}")
        print(f"Level: {item.get('level_of_description', 'N/A')}")
        print(f"Score: {item.get('score', 'N/A')}")
        print("-" * 50)

    return results

# Example searches
search_archive("heritage artifacts", sector="museum")
search_archive("correspondence", date_from="1900-01-01", date_to="1950-12-31")
```

### JavaScript

```typescript
import { AtomClient } from '@ahg/atom-client';

const client = new AtomClient({
  baseUrl: 'https://your-atom-instance.com',
  apiKey: 'your-api-key'
});

interface SearchFilters {
  repository?: string;
  dateFrom?: string;
  dateTo?: string;
  sector?: string;
}

async function searchArchive(query: string, filters: SearchFilters = {}) {
  const results = await client.search.search({
    query,
    repository: filters.repository,
    dateFrom: filters.dateFrom,
    dateTo: filters.dateTo,
    sector: filters.sector,
    limit: 50
  });

  console.log(`Found ${results.total} results for '${query}'`);
  console.log('-'.repeat(50));

  for (const item of results.results) {
    console.log(`Title: ${item.title}`);
    console.log(`Reference: ${item.reference_code || 'N/A'}`);
    console.log(`Level: ${item.level_of_description || 'N/A'}`);
    console.log(`Score: ${item.score || 'N/A'}`);
    console.log('-'.repeat(50));
  }

  return results;
}

// Example searches
await searchArchive('heritage artifacts', { sector: 'museum' });
await searchArchive('correspondence', { dateFrom: '1900-01-01', dateTo: '1950-12-31' });
```

## 4. Condition Assessment Workflow

Complete workflow for creating condition assessments with photos.

### Python

```python
import base64
from datetime import date
from atom_ahg import AtomClient

client = AtomClient(
    base_url="https://your-atom-instance.com",
    api_key="your-api-key"
)

def create_condition_assessment(
    object_id: int,
    inspector: str,
    overall_condition: str,
    notes: str,
    photos: list = None
):
    """Create a complete condition assessment with photos."""

    # Create the assessment
    assessment = client.conditions.create({
        "object_id": object_id,
        "checked_by": inspector,
        "date_checked": date.today().isoformat(),
        "overall_condition": overall_condition,
        "condition_note": notes,
        "structural_condition": overall_condition,
        "surface_condition": overall_condition,
    })

    print(f"Created assessment #{assessment['id']}")

    # Upload photos if provided
    if photos:
        for i, photo_path in enumerate(photos):
            with open(photo_path, "rb") as f:
                photo_data = base64.b64encode(f.read()).decode()

            filename = photo_path.split("/")[-1]
            client.conditions.upload_photo(
                condition_id=assessment["id"],
                filename=filename,
                file_data=photo_data,
                photo_type="detail" if i > 0 else "overview",
                caption=f"Condition photo {i + 1}"
            )
            print(f"Uploaded photo: {filename}")

    return assessment

# Example usage
assessment = create_condition_assessment(
    object_id=123,
    inspector="John Smith",
    overall_condition="fair",
    notes="Surface wear on left side, minor scratches",
    photos=["photo1.jpg", "photo2.jpg"]
)
```

### JavaScript

```typescript
import { AtomClient, fileToBase64 } from '@ahg/atom-client';
import * as fs from 'fs';

const client = new AtomClient({
  baseUrl: 'https://your-atom-instance.com',
  apiKey: 'your-api-key'
});

async function createConditionAssessment(
  objectId: number,
  inspector: string,
  overallCondition: string,
  notes: string,
  photos?: string[]
) {
  // Create the assessment
  const assessment = await client.conditions.create({
    object_id: objectId,
    checked_by: inspector,
    date_checked: new Date().toISOString().split('T')[0],
    overall_condition: overallCondition,
    condition_note: notes,
    structural_condition: overallCondition,
    surface_condition: overallCondition
  });

  console.log(`Created assessment #${assessment.id}`);

  // Upload photos if provided
  if (photos) {
    for (let i = 0; i < photos.length; i++) {
      const photoPath = photos[i];
      const photoData = fs.readFileSync(photoPath).toString('base64');
      const filename = photoPath.split('/').pop()!;

      await client.conditions.uploadPhoto(assessment.id, filename, photoData, {
        photoType: i > 0 ? 'detail' : 'overview',
        caption: `Condition photo ${i + 1}`
      });
      console.log(`Uploaded photo: ${filename}`);
    }
  }

  return assessment;
}

// Example usage
const assessment = await createConditionAssessment(
  123,
  'John Smith',
  'fair',
  'Surface wear on left side, minor scratches',
  ['photo1.jpg', 'photo2.jpg']
);
```

## 5. Heritage Asset Management

Track heritage assets with valuations for GRAP 103/IPSAS 45 compliance.

### Python

```python
from datetime import date
from atom_ahg import AtomClient

client = AtomClient(
    base_url="https://your-atom-instance.com",
    api_key="your-api-key"
)

def register_heritage_asset(
    object_id: int,
    acquisition_date: str,
    acquisition_cost: float,
    currency: str = "ZAR",
    acquisition_method: str = "purchase"
):
    """Register a new heritage asset."""
    asset = client.assets.create({
        "object_id": object_id,
        "acquisition_date": acquisition_date,
        "acquisition_cost": acquisition_cost,
        "currency_code": currency,
        "acquisition_method": acquisition_method
    })

    print(f"Registered asset #{asset['id']} for object {object_id}")
    return asset

def add_valuation(
    asset_id: int,
    amount: float,
    method: str,
    valuer: str,
    currency: str = "ZAR"
):
    """Add a valuation to an existing asset."""
    valuation = client.valuations.create({
        "asset_id": asset_id,
        "valuation_date": date.today().isoformat(),
        "valuation_amount": amount,
        "currency_code": currency,
        "valuation_method": method,
        "valuer_name": valuer
    })

    print(f"Added valuation #{valuation['id']}: {currency} {amount:,.2f}")
    return valuation

def generate_asset_report():
    """Generate a summary of all heritage assets."""
    assets = client.assets.list()

    total_value = 0
    print("Heritage Asset Report")
    print("=" * 60)

    for asset in assets.results:
        # Get latest valuation
        valuations = client.valuations.list(asset_id=asset["id"])
        latest_value = asset.get("acquisition_cost", 0)

        if valuations.results:
            latest = valuations.results[0]
            latest_value = latest.get("valuation_amount", latest_value)

        total_value += latest_value

        print(f"Asset #{asset['id']}: {asset['currency_code']} {latest_value:,.2f}")

    print("=" * 60)
    print(f"Total Portfolio Value: {total_value:,.2f}")

# Example usage
asset = register_heritage_asset(
    object_id=456,
    acquisition_date="2024-01-15",
    acquisition_cost=50000.00
)

add_valuation(
    asset_id=asset["id"],
    amount=75000.00,
    method="market_comparison",
    valuer="Jane Doe, Certified Appraiser"
)

generate_asset_report()
```

## 6. Privacy Compliance (DSAR Handling)

Manage Data Subject Access Requests for GDPR/POPIA compliance.

### Python

```python
from datetime import datetime, timedelta
from atom_ahg import AtomClient

client = AtomClient(
    base_url="https://your-atom-instance.com",
    api_key="your-api-key"
)

def submit_dsar(
    requester_name: str,
    requester_email: str,
    request_type: str,
    subject_matter: str
):
    """Submit a new Data Subject Access Request."""
    dsar = client.privacy.create_dsar({
        "requester_name": requester_name,
        "requester_email": requester_email,
        "request_type": request_type,
        "subject_matter": subject_matter
    })

    # Calculate deadline (30 days for POPIA, 30 days for GDPR)
    deadline = datetime.now() + timedelta(days=30)

    print(f"DSAR #{dsar['id']} created")
    print(f"Type: {request_type}")
    print(f"Deadline: {deadline.strftime('%Y-%m-%d')}")

    return dsar

def get_pending_dsars():
    """Get all pending DSARs requiring action."""
    dsars = client.privacy.list_dsars(status="pending")

    print(f"Pending DSARs: {dsars.total}")
    print("-" * 50)

    for dsar in dsars.results:
        created = dsar.get("created_at", "Unknown")
        print(f"#{dsar['id']}: {dsar['requester_name']}")
        print(f"  Type: {dsar['request_type']}")
        print(f"  Created: {created}")
        print(f"  Subject: {dsar['subject_matter'][:50]}...")
        print()

    return dsars

def update_dsar_status(dsar_id: int, status: str, notes: str = None):
    """Update the status of a DSAR."""
    update_data = {"status": status}
    if notes:
        update_data["resolution_notes"] = notes

    client.privacy.update_dsar(dsar_id, update_data)
    print(f"DSAR #{dsar_id} updated to: {status}")

# Example workflow
dsar = submit_dsar(
    requester_name="John Smith",
    requester_email="john@example.com",
    request_type="access",
    subject_matter="Request for all personal data held about me"
)

# Process the request
update_dsar_status(dsar["id"], "in_progress")

# Complete the request
update_dsar_status(
    dsar["id"],
    "completed",
    notes="Data package sent via secure email"
)
```

## 7. Mobile Offline Sync

Implement offline-first mobile app synchronization.

### Python

```python
from datetime import datetime
from atom_ahg import AtomClient

client = AtomClient(
    base_url="https://your-atom-instance.com",
    api_key="your-api-key"
)

class OfflineSync:
    def __init__(self, last_sync: str = None):
        self.last_sync = last_sync or "1970-01-01T00:00:00Z"
        self.pending_changes = []

    def fetch_server_changes(self):
        """Fetch changes from server since last sync."""
        changes = client.sync.get_changes(
            since=self.last_sync,
            entity_types=["condition", "asset"]
        )

        print(f"Server changes since {self.last_sync}:")
        print(f"  - {len(changes['changes'])} changes")

        return changes

    def queue_local_change(self, entity_type: str, action: str, data: dict, entity_id: int = None):
        """Queue a local change for later sync."""
        change = {
            "entity_type": entity_type,
            "action": action,
            "data": data,
            "client_id": f"offline-{len(self.pending_changes) + 1}"
        }
        if entity_id:
            change["entity_id"] = entity_id

        self.pending_changes.append(change)
        print(f"Queued {action} for {entity_type}")

    def push_local_changes(self):
        """Push all pending local changes to server."""
        if not self.pending_changes:
            print("No pending changes to push")
            return

        result = client.sync.push_batch(self.pending_changes)

        print(f"Pushed {len(self.pending_changes)} changes:")
        print(f"  - Success: {result['success_count']}")
        print(f"  - Failed: {result['failure_count']}")

        # Clear successful changes
        self.pending_changes = [
            c for c in self.pending_changes
            if c["client_id"] in [f["client_id"] for f in result.get("failures", [])]
        ]

        # Update last sync time
        self.last_sync = datetime.utcnow().isoformat() + "Z"

        return result

    def full_sync(self):
        """Perform a full bidirectional sync."""
        # 1. Push local changes first (to avoid conflicts)
        self.push_local_changes()

        # 2. Fetch server changes
        server_changes = self.fetch_server_changes()

        # 3. Apply server changes locally (implementation depends on local storage)
        for change in server_changes["changes"]:
            print(f"Apply: {change['action']} {change['entity_type']} #{change['entity_id']}")

        # 4. Update last sync time
        self.last_sync = datetime.utcnow().isoformat() + "Z"

        return server_changes

# Example usage
sync = OfflineSync(last_sync="2024-01-01T00:00:00Z")

# Queue changes made while offline
sync.queue_local_change("condition", "create", {
    "object_id": 123,
    "overall_condition": "good",
    "checked_by": "Field Inspector"
})

sync.queue_local_change("condition", "update", {
    "condition_note": "Additional damage found"
}, entity_id=456)

# When back online, sync
sync.full_sync()
```

## 8. Building a Search Portal

Build a public search interface for your archive.

### JavaScript (React)

```tsx
import { useState, useCallback } from 'react';
import { AtomClient } from '@ahg/atom-client';
import type { SearchResult } from '@ahg/atom-client';

const client = new AtomClient({
  baseUrl: process.env.REACT_APP_ATOM_URL!,
  apiKey: process.env.REACT_APP_ATOM_API_KEY!
});

interface SearchState {
  query: string;
  results: SearchResult[];
  total: number;
  loading: boolean;
  error: string | null;
}

function SearchPortal() {
  const [state, setState] = useState<SearchState>({
    query: '',
    results: [],
    total: 0,
    loading: false,
    error: null
  });

  const [filters, setFilters] = useState({
    repository: '',
    dateFrom: '',
    dateTo: '',
    sector: ''
  });

  const handleSearch = useCallback(async () => {
    if (!state.query.trim()) return;

    setState(s => ({ ...s, loading: true, error: null }));

    try {
      const result = await client.search.search({
        query: state.query,
        repository: filters.repository || undefined,
        dateFrom: filters.dateFrom || undefined,
        dateTo: filters.dateTo || undefined,
        sector: filters.sector || undefined,
        limit: 20
      });

      setState(s => ({
        ...s,
        results: result.results,
        total: result.total,
        loading: false
      }));
    } catch (err) {
      setState(s => ({
        ...s,
        error: err instanceof Error ? err.message : 'Search failed',
        loading: false
      }));
    }
  }, [state.query, filters]);

  return (
    <div className="search-portal">
      <h1>Archive Search</h1>

      <div className="search-box">
        <input
          type="text"
          value={state.query}
          onChange={e => setState(s => ({ ...s, query: e.target.value }))}
          placeholder="Search the archive..."
          onKeyPress={e => e.key === 'Enter' && handleSearch()}
        />
        <button onClick={handleSearch} disabled={state.loading}>
          {state.loading ? 'Searching...' : 'Search'}
        </button>
      </div>

      <div className="filters">
        <select
          value={filters.sector}
          onChange={e => setFilters(f => ({ ...f, sector: e.target.value }))}
        >
          <option value="">All Sectors</option>
          <option value="archive">Archives</option>
          <option value="museum">Museums</option>
          <option value="library">Libraries</option>
        </select>

        <input
          type="date"
          value={filters.dateFrom}
          onChange={e => setFilters(f => ({ ...f, dateFrom: e.target.value }))}
          placeholder="From date"
        />
        <input
          type="date"
          value={filters.dateTo}
          onChange={e => setFilters(f => ({ ...f, dateTo: e.target.value }))}
          placeholder="To date"
        />
      </div>

      {state.error && <div className="error">{state.error}</div>}

      <div className="results">
        <p>Found {state.total} results</p>

        {state.results.map(result => (
          <div key={result.slug} className="result-item">
            <h3>{result.title}</h3>
            <p className="meta">
              {result.reference_code} | {result.level_of_description}
            </p>
            <p className="snippet">{result.scope_and_content?.slice(0, 200)}...</p>
            <a href={`/record/${result.slug}`}>View Record</a>
          </div>
        ))}
      </div>
    </div>
  );
}

export default SearchPortal;
```

## 9. Automated Quality Checks

Run automated checks on archival descriptions.

### Python

```python
from atom_ahg import AtomClient

client = AtomClient(
    base_url="https://your-atom-instance.com",
    api_key="your-api-key"
)

def check_description_quality(slug: str):
    """Check quality of a single description."""
    desc = client.descriptions.get(slug, full=True)
    issues = []

    # Check required fields
    if not desc.get("title"):
        issues.append("Missing title")

    if not desc.get("scope_and_content"):
        issues.append("Missing scope and content")

    if not desc.get("level_of_description"):
        issues.append("Missing level of description")

    # Check title quality
    title = desc.get("title", "")
    if title and len(title) < 10:
        issues.append("Title too short (< 10 characters)")

    if title and title.isupper():
        issues.append("Title is all uppercase")

    # Check scope length
    scope = desc.get("scope_and_content", "")
    if scope and len(scope) < 50:
        issues.append("Scope and content too brief (< 50 characters)")

    # Check for dates
    if not desc.get("dates"):
        issues.append("No dates recorded")

    return {
        "slug": slug,
        "title": desc.get("title"),
        "issues": issues,
        "quality_score": max(0, 100 - (len(issues) * 15))
    }

def audit_all_descriptions():
    """Run quality audit on all descriptions."""
    results = []

    for page in client.descriptions.paginate(page_size=100):
        for desc in page.results:
            result = check_description_quality(desc["slug"])
            results.append(result)

            if result["issues"]:
                print(f"Issues with '{result['title'][:40]}...':")
                for issue in result["issues"]:
                    print(f"  - {issue}")

    # Summary
    total = len(results)
    with_issues = len([r for r in results if r["issues"]])
    avg_score = sum(r["quality_score"] for r in results) / total if total else 0

    print("\n" + "=" * 50)
    print("QUALITY AUDIT SUMMARY")
    print("=" * 50)
    print(f"Total records: {total}")
    print(f"Records with issues: {with_issues} ({with_issues/total*100:.1f}%)")
    print(f"Average quality score: {avg_score:.1f}")

    return results

# Run audit
audit_all_descriptions()
```

## 10. Integration with External Systems

Example of integrating with a museum management system.

### Python

```python
import requests
from atom_ahg import AtomClient

# AtoM client
atom = AtomClient(
    base_url="https://your-atom-instance.com",
    api_key="your-api-key"
)

# External system API
MUSEUM_API = "https://museum-system.example.com/api"
MUSEUM_KEY = "museum-api-key"

def sync_object_to_atom(museum_object_id: str):
    """Sync a museum object to AtoM."""

    # Fetch from museum system
    response = requests.get(
        f"{MUSEUM_API}/objects/{museum_object_id}",
        headers={"Authorization": f"Bearer {MUSEUM_KEY}"}
    )
    obj = response.json()

    # Map to AtoM fields
    atom_data = {
        "title": obj["name"],
        "level_of_description_id": 227,  # Item
        "scope_and_content": obj.get("description"),
        "date_of_creation": obj.get("date_created"),
        "physical_description": obj.get("dimensions"),
        "alternative_identifiers": [
            {"type": "museum_id", "value": museum_object_id}
        ]
    }

    # Check if already exists in AtoM
    existing = None
    try:
        # Search by alternative identifier
        results = atom.search.search(query=f'"{museum_object_id}"')
        if results.results:
            existing = results.results[0]
    except Exception:
        pass

    if existing:
        # Update existing record
        updated = atom.descriptions.update(existing["slug"], atom_data)
        print(f"Updated: {updated['slug']}")
        return updated
    else:
        # Create new record
        created = atom.descriptions.create(atom_data)
        print(f"Created: {created['slug']}")
        return created

def sync_all_museum_objects():
    """Sync all museum objects to AtoM."""
    page = 1
    while True:
        response = requests.get(
            f"{MUSEUM_API}/objects",
            params={"page": page, "limit": 100},
            headers={"Authorization": f"Bearer {MUSEUM_KEY}"}
        )
        data = response.json()

        if not data["objects"]:
            break

        for obj in data["objects"]:
            try:
                sync_object_to_atom(obj["id"])
            except Exception as e:
                print(f"Error syncing {obj['id']}: {e}")

        page += 1

    print("Sync complete")

# Run sync
sync_all_museum_objects()
```

## Next Steps

- [API Reference](../technical/01-api-reference.md) - Complete endpoint documentation
- [Error Handling](../technical/04-error-handling.md) - Handling errors gracefully
- [SDK Internals](../technical/05-sdk-internals.md) - Understanding the SDK architecture
