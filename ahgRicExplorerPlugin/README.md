# AtoM RiC Explorer Plugin

Records in Context (RiC) visualization and exploration plugin for Access to Memory (AtoM).

## Features

- **RiC Relations Panel**: Shows creators, accumulators, and related records on description pages
- **Graph Visualization**: Interactive mini-graph showing entity relationships
- **Full Explorer**: Modal window with comprehensive graph exploration
- **Cross-fonds Discovery**: Find related records via shared creators

## Requirements

- AtoM 2.x
- Apache Jena Fuseki (or compatible SPARQL endpoint) with RiC data loaded
- RiC Extractor for populating the triplestore

## Installation

### 1. Copy Plugin Files

```bash
cp -r arRicExplorerPlugin /usr/share/nginx/atom/plugins/
```

### 2. Configure Settings

Add to `apps/qubit/config/app.yml`:

```yaml
all:
  ric_explorer:
    sparql_endpoint: 'http://localhost:3030/ric/query'
    base_uri: 'https://your-domain.com/ric/atom'
    explorer_url: '/ric/'
    enabled: true
```

### 3. Enable the Plugin

Add to `apps/qubit/config/settings.yml`:

```yaml
all:
  .settings:
    plugins:
      - arRicExplorerPlugin
```

### 4. Include Panel in Templates

Edit `plugins/arDominionPlugin/modules/informationobject/templates/indexSuccess.php` (or your theme's equivalent):

Add after the main description content:

```php
<?php include_component('arRicExplorer', 'ricPanel', array('resource' => $resource)); ?>
```

### 5. Clear Cache

```bash
php symfony cc
```

### 6. Deploy Static Assets

```bash
# Copy CSS and JS to web directory
cp plugins/arRicExplorerPlugin/css/ric-explorer.css web/plugins/arRicExplorerPlugin/css/
cp plugins/arRicExplorerPlugin/js/ric-explorer.js web/plugins/arRicExplorerPlugin/js/
```

Or create symlinks:
```bash
mkdir -p web/plugins/arRicExplorerPlugin
ln -s ../../../plugins/arRicExplorerPlugin/css web/plugins/arRicExplorerPlugin/css
ln -s ../../../plugins/arRicExplorerPlugin/js web/plugins/arRicExplorerPlugin/js
```

## Configuration Options

| Option | Default | Description |
|--------|---------|-------------|
| `sparql_endpoint` | `http://localhost:3030/ric/query` | SPARQL endpoint URL |
| `base_uri` | `https://archives.theahg.co.za/ric/atom-psis` | Base URI for RiC entities |
| `explorer_url` | `/ric/` | URL to standalone explorer |
| `enabled` | `true` | Enable/disable the plugin |
| `show_related_records` | `true` | Show related records section |
| `related_records_limit` | `10` | Max related records to display |
| `show_mini_graph` | `true` | Show graph visualization |

## CORS Configuration

If Fuseki is on a different domain/port, configure CORS:

```bash
docker run -d --name fuseki -p 3030:3030 \
  -e ADMIN_PASSWORD=admin123 \
  stain/jena-fuseki
```

Or for standalone Fuseki, add to `shiro.ini`:
```
[main]
corsFilter = org.apache.shiro.web.filter.authz.PermissionsAuthorizationFilter
```

## Troubleshooting

### Panel not showing
1. Check `enabled: true` in app.yml
2. Verify SPARQL endpoint is accessible
3. Ensure RiC data exists for the record
4. Check browser console for JavaScript errors

### No RiC data found
1. Run the RiC extractor for the fonds
2. Load JSON-LD into Fuseki
3. Verify URI patterns match between extractor and plugin

### Graph not rendering
1. Check Cytoscape.js is loading (network tab)
2. Verify CORS allows requests from AtoM domain
3. Check console for SPARQL query errors

## File Structure

```
arRicExplorerPlugin/
├── config/
│   ├── arRicExplorerPluginConfiguration.class.php
│   └── app.yml
├── modules/
│   └── arRicExplorer/
│       ├── actions/
│       │   └── components.class.php
│       ├── config/
│       │   └── module.yml
│       └── templates/
│           └── _ricPanel.php
├── css/
│   └── ric-explorer.css
├── js/
│   └── ric-explorer.js
└── README.md
```

## Integration with RiC Extractor

This plugin works with the RiC extraction pipeline:

```
AtoM MySQL → ric_extractor.py → JSON-LD → Fuseki → Plugin queries
```

Ensure the `base_uri` in plugin config matches the extractor's `RIC_BASE_URI`.

## License

GNU Affero General Public License v3.0 (same as AtoM)

## Author

The Archives and Heritage Group (The AHG) / Plain Sailing
