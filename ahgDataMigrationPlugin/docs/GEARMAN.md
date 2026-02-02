# Gearman Setup for Data Migration Plugin

This document describes how to set up Gearman for background job processing in the Data Migration Plugin.

## Overview

Gearman is a job queue system that allows AtoM to process large imports/exports in the background. This is essential for:

- Imports with more than 500 rows
- Batch exports of large record sets
- Any long-running data transformation tasks

## Prerequisites

- Ubuntu 22.04 or compatible Linux distribution
- PHP 8.x with Gearman extension
- Root/sudo access for service installation

## Installation

### 1. Install Gearman Server and PHP Extension

```bash
# Install Gearman job server
sudo apt-get update
sudo apt-get install -y gearman-job-server

# Install PHP Gearman extension
sudo apt-get install -y php8.3-gearman

# Restart PHP-FPM to load the extension
sudo systemctl restart php8.3-fpm
```

### 2. Verify Installation

```bash
# Check if Gearman server is running
sudo systemctl status gearman-job-server

# Check if PHP extension is loaded
php -m | grep gearman

# Check Gearman server status
gearadmin --status
```

### 3. Enable Gearman on Boot

```bash
sudo systemctl enable gearman-job-server
```

## Configuration

### AtoM Configuration

The plugin reads Gearman configuration from `apps/qubit/config/gearman.yml`:

```yaml
all:
  servers:
    default:
      host: 127.0.0.1
      port: 4730
```

If this file doesn't exist, the plugin defaults to `127.0.0.1:4730`.

### Connection Settings

| Setting | Default | Description |
|---------|---------|-------------|
| host | 127.0.0.1 | Gearman server hostname or IP |
| port | 4730 | Gearman server port |

## Running the Worker

### Manual (Development/Testing)

Run the worker manually for testing:

```bash
cd /usr/share/nginx/archive
php symfony jobs:worker
```

This will process jobs one at a time. Press Ctrl+C to stop.

### Systemd Service (Production)

Create a systemd service for automatic worker management:

```bash
# Create service file
sudo tee /etc/systemd/system/atom-worker.service << 'EOF'
[Unit]
Description=AtoM Gearman Worker
After=network.target gearman-job-server.service
Requires=gearman-job-server.service

[Service]
Type=simple
User=www-data
Group=www-data
WorkingDirectory=/usr/share/nginx/archive
ExecStart=/usr/bin/php symfony jobs:worker
Restart=always
RestartSec=5

[Install]
WantedBy=multi-user.target
EOF

# Reload systemd
sudo systemctl daemon-reload

# Enable and start the service
sudo systemctl enable atom-worker
sudo systemctl start atom-worker
```

### Check Worker Status

```bash
# Service status
sudo systemctl status atom-worker

# View logs
sudo journalctl -u atom-worker -f

# Gearman queue status
gearadmin --status
```

## Job Types

The Data Migration Plugin uses these Gearman job types:

| Job Name | Description |
|----------|-------------|
| `arDataMigrationImportJob` | Process CSV/Excel imports |
| `arDataMigrationExportJob` | Batch export to CSV |
| `arDataMigrationTransformJob` | Apply field transformations |

## Monitoring

### Gearman Admin Commands

```bash
# Show registered functions and workers
gearadmin --status

# Show connected workers
gearadmin --workers

# Show server version
gearadmin --version
```

### Check Pending Jobs

```bash
# Using gearadmin
gearadmin --status

# Output format: FUNCTION_NAME\tQUEUED\tRUNNING\tWORKERS
# Example: arDataMigrationImportJob    2    1    1
```

## Troubleshooting

### Worker Not Starting

1. Check PHP extension:
   ```bash
   php -m | grep gearman
   ```

2. Check Gearman server:
   ```bash
   sudo systemctl status gearman-job-server
   ```

3. Check permissions:
   ```bash
   ls -la /usr/share/nginx/archive/cache/
   # Should be writable by www-data
   ```

### Jobs Stuck in Queue

1. Check if worker is running:
   ```bash
   gearadmin --workers
   ```

2. Restart worker:
   ```bash
   sudo systemctl restart atom-worker
   ```

3. Check for errors in logs:
   ```bash
   sudo journalctl -u atom-worker -n 50
   ```

### Connection Refused

1. Verify Gearman is listening:
   ```bash
   netstat -tlnp | grep 4730
   ```

2. Check firewall:
   ```bash
   sudo ufw status
   ```

3. Verify config in `gearman.yml`

### Memory Issues

For large imports, increase PHP memory limit:

```bash
# In php.ini or php-fpm pool config
memory_limit = 512M

# Or in the worker service file
ExecStart=/usr/bin/php -d memory_limit=512M symfony jobs:worker
```

## Multiple Workers

For high-volume systems, run multiple workers:

```bash
# Create additional service instances
sudo tee /etc/systemd/system/atom-worker@.service << 'EOF'
[Unit]
Description=AtoM Gearman Worker %i
After=network.target gearman-job-server.service

[Service]
Type=simple
User=www-data
Group=www-data
WorkingDirectory=/usr/share/nginx/archive
ExecStart=/usr/bin/php symfony jobs:worker
Restart=always
RestartSec=5

[Install]
WantedBy=multi-user.target
EOF

# Start multiple instances
sudo systemctl daemon-reload
sudo systemctl enable atom-worker@{1..3}
sudo systemctl start atom-worker@{1..3}
```

## Security Notes

- Gearman by default listens only on localhost (127.0.0.1)
- Do not expose port 4730 to external networks
- The worker runs as www-data and has access to AtoM files

## Related Documentation

- [AtoM Job Scheduler Documentation](https://www.accesstomemory.org/docs/latest/admin-manual/maintenance/asynchronous-jobs/)
- [Gearman Manual](http://gearman.org/manual/)
- [PHP Gearman Extension](https://www.php.net/manual/en/book.gearman.php)
