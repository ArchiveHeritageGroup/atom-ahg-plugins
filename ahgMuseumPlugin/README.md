# ahgMuseumPlugin - Provenance Module Installation

## Quick Fix for 404 Error

The error "Action ahgMuseumPlugin/provenance does not exist" means the action class and template are missing.

## Installation Steps

### 1. Copy the module files

Copy the `modules/ahgMuseumPlugin` directory to your plugin:

```bash
# If ahgMuseumPlugin already exists
cp -r modules/ahgMuseumPlugin/* /usr/share/nginx/archive/plugins/ahgMuseumPlugin/modules/ahgMuseumPlugin/

# OR create new plugin directory structure
mkdir -p /usr/share/nginx/archive/plugins/ahgMuseumPlugin/modules/ahgMuseumPlugin/actions
mkdir -p /usr/share/nginx/archive/plugins/ahgMuseumPlugin/modules/ahgMuseumPlugin/templates
cp modules/ahgMuseumPlugin/actions/actions.class.php /usr/share/nginx/archive/plugins/ahgMuseumPlugin/modules/ahgMuseumPlugin/actions/
cp modules/ahgMuseumPlugin/templates/*.php /usr/share/nginx/archive/plugins/ahgMuseumPlugin/modules/ahgMuseumPlugin/templates/
```

### 2. Add routing

Add these routes to `/usr/share/nginx/archive/plugins/ahgMuseumPlugin/config/routing.yml`:

```yaml
ahgMuseumPlugin_provenance:
  url: /:slug/ahgMuseumPlugin/provenance
  class: QubitInformationObjectRoute
  param: { module: ahgMuseumPlugin, action: provenance }
  options: { model: QubitInformationObject }

ahgMuseumPlugin_index:
  url: /:slug/ahgMuseumPlugin
  class: QubitInformationObjectRoute
  param: { module: ahgMuseumPlugin, action: index }
  options: { model: QubitInformationObject }
```

### 3. Clear cache

```bash
cd /usr/share/nginx/archive
php symfony cc
```

### 4. Test

Navigate to: `https://wdb.theahg.co.za/index.php/av-demo/ahgMuseumPlugin/provenance`

## Optional: Create Provenance Database Table

For detailed custody history tracking, create the `museum_provenance` table:

```sql
CREATE TABLE museum_provenance (
    id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    object_id INT(11) UNSIGNED NOT NULL,
    custodian_name VARCHAR(255) NOT NULL,
    custodian_type VARCHAR(100),
    start_date DATE,
    end_date DATE,
    location VARCHAR(255),
    acquisition_method VARCHAR(100),
    notes TEXT,
    verified TINYINT(1) DEFAULT 0,
    source_reference VARCHAR(255),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME,
    KEY idx_object (object_id),
    KEY idx_dates (start_date, end_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

## Files Included

```
ahgMuseumPlugin/
├── config/
│   └── routing.yml           # Route definitions
├── modules/
│   └── ahgMuseumPlugin/
│       ├── actions/
│       │   └── actions.class.php    # Controller with provenance action
│       └── templates/
│           ├── indexSuccess.php     # Index/overview template
│           └── provenanceSuccess.php # D3.js timeline template
└── README.md
```

## Features

### Provenance Timeline (D3.js)
- Visual timeline of ownership/custody history
- Interactive tooltips with details
- Color-coded by event type
- Supports verified/unverified status

### Data Sources
- ISAD(G) Archival History field
- ISAD(G) Custodial History field  
- Immediate Source of Acquisition
- Related Events (Creation, Accumulation, Collection)
- Optional: museum_provenance table for detailed tracking

## Troubleshooting

### Still getting 404?

1. Check the routing is loaded:
```bash
php symfony app:routes | grep ahgMuseumPlugin
```

2. Check module exists:
```bash
ls -la /usr/share/nginx/archive/plugins/ahgMuseumPlugin/modules/ahgMuseumPlugin/
```

3. Check cache is cleared:
```bash
rm -rf /usr/share/nginx/archive/cache/*
php symfony cc
```

4. Check permissions:
```bash
chown -R www-data:www-data /usr/share/nginx/archive/plugins/ahgMuseumPlugin/
chmod -R 755 /usr/share/nginx/archive/plugins/ahgMuseumPlugin/
```
