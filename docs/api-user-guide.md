# REST API - User Guide

Connect your applications to AtoM and access your archival collections programmatically.

---

## Table of Contents

1. [What is the API?](#what-is-the-api)
2. [Getting Your API Key](#getting-your-api-key)
3. [Making Your First Request](#making-your-first-request)
4. [Finding Records](#finding-records)
5. [Searching](#searching)
6. [Common Tasks](#common-tasks)
7. [Troubleshooting](#troubleshooting)

---

## What is the API?

The API (Application Programming Interface) allows external applications to communicate with your AtoM system. Use it to:

- Display archival records on your website
- Import records from other systems
- Build custom search interfaces
- Create mobile applications
- Automate repetitive tasks

```
┌─────────────────────────────────────────────────────────────────┐
│                      HOW THE API WORKS                          │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│   Your Website/App              AtoM System                     │
│         │                           │                           │
│         │   "Get all fonds"         │                           │
│         │ ─────────────────────────▶│                           │
│         │                           │                           │
│         │   List of fonds (JSON)    │                           │
│         │◀───────────────────────── │                           │
│         │                           │                           │
│         ▼                           │                           │
│   Display on webpage                │                           │
│                                                                 │
└─────────────────────────────────────────────────────────────────┘
```

---

## Getting Your API Key

Before using the API, you need an API key for authentication.

### Step-by-Step

1. Log in to AtoM as an **administrator**
2. Click your username in the top right
3. Select **Profile**
4. Scroll to **REST API Key**
5. If no key exists, click **Generate API Key**
6. Copy the key and store it securely

```
┌─────────────────────────────────────────────────────────────────┐
│                    API KEY LOCATION                             │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│   Admin Menu                                                    │
│       │                                                         │
│       ▼                                                         │
│   Your Profile                                                  │
│       │                                                         │
│       ▼                                                         │
│   ┌─────────────────────────────────────────────────────────┐  │
│   │  REST API Key                                           │  │
│   │  ─────────────                                          │  │
│   │  abc123def456ghi789jkl012mno345pqr678                   │  │
│   │                                                         │  │
│   │  [Regenerate]                                           │  │
│   └─────────────────────────────────────────────────────────┘  │
│                                                                 │
└─────────────────────────────────────────────────────────────────┘
```

**Important:** Keep your API key secret. Anyone with your key can access the system as you.

---

## Making Your First Request

### Using a Web Browser Extension

Install a REST client extension for your browser (like "RESTer" or "Talend API Tester").

1. Set the URL: `https://your-site.com/api/v2`
2. Add header: `X-API-Key: your-api-key-here`
3. Click Send

### Using Command Line (Advanced)

```bash
curl -H "X-API-Key: your-api-key" https://your-site.com/api/v2
```

### Expected Response

```json
{
  "success": true,
  "data": {
    "name": "AtoM AHG REST API",
    "version": "v2.0.0",
    "endpoints": {
      "descriptions": "/api/v2/descriptions",
      "authorities": "/api/v2/authorities",
      "repositories": "/api/v2/repositories"
    }
  }
}
```

---

## Finding Records

### List All Archival Descriptions

```
GET https://your-site.com/api/v2/descriptions
```

Returns a list of all archival descriptions in your system.

### Filter by Sector

Add `?sector=` to filter by collection type:

| Sector | What it Returns |
|--------|-----------------|
| `archive` | Traditional archival records (ISAD) |
| `library` | Library materials (MARC/Dublin Core) |
| `museum` | Museum objects (Spectrum/CCO) |
| `gallery` | Art and gallery items (CCO) |
| `dam` | Photographs and digital assets (IPTC) |

**Example:** Get only museum objects:
```
GET https://your-site.com/api/v2/descriptions?sector=museum
```

### Filter by Level

| Level | Description |
|-------|-------------|
| `fonds` | Highest level - entire collection |
| `series` | Group of related files |
| `file` | Folder or group of items |
| `item` | Single document or object |

**Example:** Get only fonds-level records:
```
GET https://your-site.com/api/v2/descriptions?level=fonds
```

### Get a Single Record

Use the record's slug (URL identifier):
```
GET https://your-site.com/api/v2/descriptions/company-records-fonds
```

### Pagination

For large result sets, use pagination:

| Parameter | Description |
|-----------|-------------|
| `limit` | Number of results (1-100) |
| `skip` | How many to skip |

**Example:** Get records 51-100:
```
GET https://your-site.com/api/v2/descriptions?limit=50&skip=50
```

---

## Searching

Search across all your collections:

```
POST https://your-site.com/api/v2/search
Content-Type: application/json

{
  "query": "meeting minutes",
  "filters": {
    "sector": "archive",
    "date_start": "1960-01-01",
    "date_end": "1970-12-31"
  }
}
```

### Search Filters

| Filter | Description |
|--------|-------------|
| `sector` | Collection type |
| `repository` | Specific repository |
| `date_start` | Records from this date |
| `date_end` | Records until this date |
| `level` | Level of description |

---

## Common Tasks

### Task 1: Display Fonds on Your Website

1. Request: `GET /api/v2/descriptions?level=fonds&limit=100`
2. Parse the JSON response
3. Display titles and links on your webpage

### Task 2: Find All Records Updated This Month

```
GET /api/v2/descriptions?sort=updated&sort_direction=desc&limit=50
```

### Task 3: Get Repository Information

```
GET /api/v2/repositories
```

Returns all repositories with contact details and record counts.

### Task 4: Browse Subject Terms

```
GET /api/v2/taxonomies
```

Then get terms for a specific taxonomy:
```
GET /api/v2/taxonomies/42/terms
```

---

## Troubleshooting

### Error: 401 Unauthorized

```json
{"success": false, "error": "Unauthorized", "message": "Invalid API key"}
```

**Solution:** Check your API key is correct and included in the header.

### Error: 404 Not Found

```json
{"success": false, "error": "Not Found", "message": "Record not found"}
```

**Solution:** Verify the slug or endpoint URL is correct.

### Error: 429 Too Many Requests

**Solution:** You've exceeded the rate limit (1000 requests/hour). Wait and try again.

### No Results Returned

**Check:**
- Are your filter parameters spelled correctly?
- Does data exist matching your criteria?
- Try removing filters to see all results first

---

## Quick Reference

```
┌─────────────────────────────────────────────────────────────────┐
│                    API QUICK REFERENCE                          │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  BASE URL                                                       │
│  ────────                                                       │
│  https://your-site.com/api/v2                                  │
│                                                                 │
│  AUTHENTICATION                                                 │
│  ──────────────                                                │
│  Header: X-API-Key: your-key-here                              │
│                                                                 │
│  GET DATA                                                       │
│  ────────                                                       │
│  /descriptions         All archival records                    │
│  /descriptions/:slug   Single record                           │
│  /authorities          People and organisations                │
│  /repositories         Repositories                            │
│  /taxonomies           Browse terms                            │
│                                                                 │
│  COMMON FILTERS                                                 │
│  ──────────────                                                │
│  ?sector=archive       Filter by type                          │
│  ?level=fonds          Filter by level                         │
│  ?limit=25             Results per page                        │
│  ?skip=50              Pagination offset                       │
│                                                                 │
└─────────────────────────────────────────────────────────────────┘
```

---

## Getting Help

- Contact your system administrator
- Email: support@theahg.co.za
- Technical documentation: See `api-technical-reference.md`

---

*Part of the AtoM AHG Framework*
