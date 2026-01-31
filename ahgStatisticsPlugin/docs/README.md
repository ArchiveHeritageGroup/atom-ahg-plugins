# ahgStatisticsPlugin

Usage statistics tracking for AtoM with page views, downloads, GeoIP lookup, and reporting dashboards.

## Features

- **Page View Tracking**: Automatic tracking of information object views
- **Download Tracking**: Track file downloads with digital object details
- **GeoIP Lookup**: Geographic location data using MaxMind GeoLite2
- **Bot Filtering**: Configurable bot/spider detection and filtering
- **Pre-Aggregation**: Daily and monthly statistics for performance
- **Dashboards**: Interactive charts and reports
- **Export**: CSV export for all reports
- **Privacy**: IP anonymization option for GDPR compliance
- **CLI Commands**: Cron support for aggregation and reporting

## Installation

1. Enable the plugin:
   ```bash
   php bin/atom extension:enable ahgStatisticsPlugin
   ```

2. Run database migration:
   ```bash
   mysql -u root archive < plugins/ahgStatisticsPlugin/database/install.sql
   ```

3. (Optional) Install MaxMind GeoLite2 database:
   ```bash
   # Download from https://dev.maxmind.com/geoip/geolite2-free-geolocation-data
   # Place at: /usr/share/GeoIP/GeoLite2-City.mmdb
   ```

4. Clear cache:
   ```bash
   php symfony cc
   ```

## Usage

### Web Interface

Access the statistics dashboard at: `/statistics`

- **Dashboard**: Overview with charts and key metrics
- **Views**: Page view trends over time
- **Downloads**: Download statistics and top items
- **Top Items**: Most viewed/downloaded items
- **Geographic**: Visitor distribution by country
- **Settings**: Configure tracking options

### CLI Commands

**Aggregate statistics (for cron):**
```bash
# Run all aggregations (daily + cleanup)
php symfony statistics:aggregate --all

# Daily aggregation only
php symfony statistics:aggregate --daily

# Monthly aggregation
php symfony statistics:aggregate --monthly

# Cleanup old events
php symfony statistics:aggregate --cleanup --days=90

# Backfill 30 days of daily aggregates
php symfony statistics:aggregate --backfill=30
```

**Generate reports:**
```bash
# Show summary
php symfony statistics:report

# Views report
php symfony statistics:report --type=views

# Top items report
php symfony statistics:report --type=top_items --limit=100

# Export to CSV
php symfony statistics:report --type=views --format=csv --output=/tmp/views.csv

# Custom date range
php symfony statistics:report --start=2024-01-01 --end=2024-01-31
```

### Cron Setup

Add to crontab for automated processing:
```bash
# Aggregate daily at 2am
0 2 * * * cd /usr/share/nginx/archive && php symfony statistics:aggregate --daily >> /var/log/atom-stats.log 2>&1

# Monthly aggregation on 1st of month
0 3 1 * * cd /usr/share/nginx/archive && php symfony statistics:aggregate --monthly >> /var/log/atom-stats.log 2>&1

# Weekly cleanup of old events
0 4 * * 0 cd /usr/share/nginx/archive && php symfony statistics:aggregate --cleanup >> /var/log/atom-stats.log 2>&1
```

## Database Tables

- `ahg_usage_event`: Raw event log (views, downloads)
- `ahg_statistics_daily`: Pre-aggregated daily statistics
- `ahg_statistics_monthly`: Pre-aggregated monthly statistics
- `ahg_bot_list`: Bot/spider patterns for filtering
- `ahg_statistics_config`: Plugin configuration

## GeoIP Setup

For geographic data, download the MaxMind GeoLite2 City database:

1. Register at https://www.maxmind.com/en/geolite2/signup
2. Download GeoLite2-City.mmdb
3. Place at `/usr/share/GeoIP/GeoLite2-City.mmdb`
4. Optionally install PHP library: `composer require geoip2/geoip2`

## Privacy Features

- **IP Anonymization**: Store hashed IPs instead of raw addresses
- **Retention Period**: Automatically delete old raw events
- **Bot Filtering**: Exclude automated traffic from statistics

## Programmatic Usage

```php
require_once sfConfig::get('sf_root_dir') . '/plugins/ahgStatisticsPlugin/lib/Services/StatisticsService.php';
$service = new StatisticsService();

// Log a view manually
$service->logView('information_object', $objectId);

// Get item statistics
$stats = $service->getItemStats($objectId, '2024-01-01', '2024-01-31');

// Get dashboard summary
$summary = $service->getDashboardStats('2024-01-01', '2024-01-31');
```

## License

GPL-3.0 - The Archive and Heritage Group
