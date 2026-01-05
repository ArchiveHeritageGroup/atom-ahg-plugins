# ahgConditionPlugin

Condition assessment and reporting for museum objects.

## Requirements

- PHP 8.1+
- PHP GD extension (for image processing)

### Installing PHP GD

**Ubuntu/Debian:**
```bash
sudo apt-get install php8.3-gd
sudo systemctl restart php8.3-fpm
```

**CentOS/RHEL:**
```bash
sudo yum install php-gd
sudo systemctl restart php-fpm
```

## Installation

1. Check requirements:
```bash
php check-requirements.php
```

2. Install via extension manager:
```bash
php bin/atom extension:install ahgConditionPlugin
```

## Features

- Condition check templates (Museum, Library, Archive, Artwork)
- Photo documentation with annotations
- Condition reports (PDF export)
- Integration with object records
