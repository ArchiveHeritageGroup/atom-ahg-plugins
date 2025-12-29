# ahgDisplayPlugin

Context-aware display system for AtoM 2.10+ with **Elasticsearch 7** integration. Supports archives, museums, galleries, book collections, and photo archives.

## Features

- **Object Type Configuration**: Set collections as archive, museum, gallery, library, or DAM
- **Display Profiles**: Automatic layout selection based on object type
- **Extended Levels of Description**: 35+ levels across all domains
- **Field Mapping**: Maps display fields to existing AtoM database tables
- **Multiple Layout Modes**: detail, hierarchy, grid, gallery, list, card, catalog, masonry
- **Elasticsearch 7 Integration**: Type-based faceting and search
- **Browse by Type**: `/displaySearch/browse?type=museum`

## Elasticsearch 7 Integration

```
┌─────────────────────────────────────────────────────────────┐
│                    ES 7 INDEX STRUCTURE                     │
├─────────────────────────────────────────────────────────────┤
│  display_object_type    → archive|museum|gallery|library|dam│
│  display_profile        → isad_full|spectrum_full|etc.     │
│  display_level_code     → fonds|series|object|artwork|etc. │
│  display.title          → Searchable title                 │
│  display.creator        → Searchable creator               │
│  display.has_digital_object → Boolean for filtering        │
│  display.media_type     → image|video|audio|document       │
│  display.subjects[]     → Subject facets                   │
│  display.thumbnail_path → For grid/gallery views           │
└─────────────────────────────────────────────────────────────┘
```

### ES 7 Compatibility Notes

- **No mapping types**: ES 7 removed types, plugin uses index directly
- **Partial updates**: Reindex adds display fields without full re-index
- **Aggregations**: Object type, media type, subjects as facets

## Installation

```bash
# Extract to your AtoM plugins directory
tar -xzf ahgDisplayPlugin.tar.gz -C /path/to/atom/plugins/

# Run installation script
cd /path/to/atom/plugins/ahgDisplayPlugin
./INSTALL.sh

# Update Elasticsearch mapping
php symfony display:reindex --update-mapping

# Reindex display data
php symfony display:reindex --batch=100
```

## Object Types

| Type | Default Layout | Field Focus | ES Facets |
|------|---------------|-------------|-----------|
| Archive | Hierarchy | ISAD(G) fields | Level, Subject |
| Museum | Detail | Spectrum fields | Classification, Material |
| Gallery | Gallery | VRA fields | Artist, Medium |
| Library | List | Bibliographic | Author, Publisher |
| DAM | Grid | Technical fields | Media type, Date |

## URLs

| URL | Description |
|-----|-------------|
| `/display` | Admin dashboard |
| `/display/profiles` | Manage display profiles |
| `/display/levels` | View extended levels |
| `/display/bulkSetType` | Bulk set object types |
| `/displaySearch/search` | Search with facets |
| `/displaySearch/browse?type=museum` | Browse by type |
| `/displaySearch/reindex` | ES reindex admin |

## CLI Tasks

```bash
# Reindex display data
php symfony display:reindex

# With options
php symfony display:reindex --batch=200 --update-mapping

# Update mapping only
php symfony display:reindex --update-mapping
```

## Service API

```php
// Display Service (MySQL)
$displayService = new DisplayService();
$data = $displayService->prepareForDisplay($objectId);

// Elasticsearch Service
$esService = new DisplayElasticsearchService();

// Search with type filter
$results = $esService->search([
    'query' => 'landscape',
    'object_type' => 'gallery',
    'has_digital_object' => true,
    'sort' => 'date_desc',
    'size' => 20,
]);

// Browse by type
$results = $esService->browseByType('museum', [
    'sort' => 'title_asc',
]);

// Get facets
$facets = $esService->getFacets(['object_type' => 'dam']);

// Autocomplete
$suggestions = $esService->autocomplete('van go', 10);
```

## Search Result Adapter

```php
// Transform ES hits for display rendering
$adapter = new DisplaySearchResultAdapter();

// Single hit to display data
$displayData = $adapter->transformHit($esHit);

// Render results with layout
echo $adapter->renderResults($searchResults, 'grid');

// Render facets
echo $adapter->renderFacets($searchResults['aggregations']);
```

## ES Mapping Fields

Added to `information_object` index:

```json
{
  "display_object_type": "keyword",
  "display_profile": "keyword",
  "display_level_code": "keyword",
  "display": {
    "type": "object",
    "properties": {
      "title": "text",
      "identifier": "keyword",
      "creator": "text",
      "creator_keyword": "keyword",
      "date_display": "text",
      "date_start": "date",
      "has_digital_object": "boolean",
      "thumbnail_path": "keyword",
      "media_type": "keyword",
      "subjects": "keyword",
      "child_count": "integer"
    }
  }
}
```

## Architecture

```
┌─────────────────┐     ┌──────────────────┐
│  DisplayService │     │ DisplayES Service│
│    (MySQL)      │     │   (ES 7)         │
└────────┬────────┘     └────────┬─────────┘
         │                       │
         ▼                       ▼
┌─────────────────┐     ┌──────────────────┐
│  Single Record  │     │  Search/Browse   │
│  Display        │     │  Results         │
└────────┬────────┘     └────────┬─────────┘
         │                       │
         └───────────┬───────────┘
                     ▼
         ┌──────────────────────┐
         │  Layout Templates    │
         │  (detail, grid, etc) │
         └──────────────────────┘
```

## License

GNU Affero General Public License v3.0
