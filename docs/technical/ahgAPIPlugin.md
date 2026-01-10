# ahgAPIPlugin - Technical Documentation

**Version:** 1.0.0  
**Category:** Integration  
**Dependencies:** atom-framework

---

## Overview

Enhanced REST API v2 providing full CRUD operations, batch processing, search integration, and webhook support for external application integration.

---

## Architecture

```
┌─────────────────────────────────────────────────────────────────┐
│                      ahgAPIPlugin                               │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  ┌─────────────────────────────────────────────────────────┐   │
│  │                   HTTP Request                          │   │
│  └─────────────────────────────────────────────────────────┘   │
│                           │                                     │
│                           ▼                                     │
│  ┌─────────────────────────────────────────────────────────┐   │
│  │              AhgApiAction (Base Class)                  │   │
│  │  • Authentication (X-API-Key / Bearer / Session)        │   │
│  │  • Rate Limiting                                        │   │
│  │  • Scope Validation                                     │   │
│  │  • Request Logging                                      │   │
│  └─────────────────────────────────────────────────────────┘   │
│                           │                                     │
│         ┌─────────────────┼─────────────────┐                  │
│         ▼                 ▼                 ▼                  │
│  ┌───────────┐     ┌───────────┐     ┌───────────┐            │
│  │ Browse    │     │ Read      │     │ Write     │            │
│  │ Actions   │     │ Actions   │     │ Actions   │            │
│  └───────────┘     └───────────┘     └───────────┘            │
│         │                 │                 │                  │
│         └─────────────────┼─────────────────┘                  │
│                           ▼                                     │
│  ┌─────────────────────────────────────────────────────────┐   │
│  │              ApiRepository                              │   │
│  │  • Laravel Query Builder                                │   │
│  │  • Data Transformation                                  │   │
│  │  • Sector Mapping                                       │   │
│  └─────────────────────────────────────────────────────────┘   │
│                           │                                     │
│                           ▼                                     │
│  ┌─────────────────────────────────────────────────────────┐   │
│  │              MySQL Database                             │   │
│  └─────────────────────────────────────────────────────────┘   │
│                                                                 │
└─────────────────────────────────────────────────────────────────┘
```

---

## Database Schema

### ERD Diagram

```
┌─────────────────────────────┐
│        ahg_api_key          │
├─────────────────────────────┤
│ PK id BIGINT               │
│ FK user_id INT             │──────┐
│    api_key VARCHAR(64)      │      │
│    name VARCHAR(255)        │      │
│    scopes VARCHAR(255)      │      │
│    rate_limit INT           │      │
│    is_active TINYINT        │      │
│    last_used_at TIMESTAMP   │      │
│    expires_at TIMESTAMP     │      │
│    created_at TIMESTAMP     │      │
│    updated_at TIMESTAMP     │      │
└─────────────────────────────┘      │
              │                       │
              │ 1:N                   │
              ▼                       │
┌─────────────────────────────┐      │
│        ahg_api_log          │      │
├─────────────────────────────┤      │
│ PK id BIGINT               │      │
│ FK api_key_id BIGINT       │──────┘
│ FK user_id INT             │
│    method VARCHAR(10)       │
│    endpoint VARCHAR(500)    │
│    status_code INT          │
│    response_time_ms INT     │
│    ip_address VARCHAR(45)   │
│    user_agent VARCHAR(500)  │
│    request_body TEXT        │
│    created_at TIMESTAMP     │
└─────────────────────────────┘

┌─────────────────────────────┐
│        ahg_webhook          │
├─────────────────────────────┤
│ PK id BIGINT               │
│ FK user_id INT             │
│    url VARCHAR(500)         │
│    events JSON              │
│    secret VARCHAR(64)       │
│    is_active TINYINT        │
│    created_at TIMESTAMP     │
└─────────────────────────────┘
              │
              │ 1:N
              ▼
┌─────────────────────────────┐
│    ahg_webhook_delivery     │
├─────────────────────────────┤
│ PK id BIGINT               │
│ FK webhook_id BIGINT       │
│    event_type VARCHAR(100)  │
│    payload JSON             │
│    response_code INT        │
│    response_body TEXT       │
│    attempts INT             │
│    delivered_at TIMESTAMP   │
│    created_at TIMESTAMP     │
└─────────────────────────────┘
```

### SQL Schema

```sql
CREATE TABLE ahg_api_key (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    api_key VARCHAR(64) NOT NULL UNIQUE,
    name VARCHAR(255),
    scopes VARCHAR(255) DEFAULT 'read',
    rate_limit INT DEFAULT 1000,
    is_active TINYINT(1) DEFAULT 1,
    last_used_at TIMESTAMP NULL,
    expires_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_api_key (api_key),
    INDEX idx_user_id (user_id),
    FOREIGN KEY (user_id) REFERENCES user(id) ON DELETE CASCADE
);

CREATE TABLE ahg_api_log (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    api_key_id BIGINT UNSIGNED NULL,
    user_id INT NULL,
    method VARCHAR(10) NOT NULL,
    endpoint VARCHAR(500) NOT NULL,
    status_code INT NOT NULL,
    response_time_ms INT,
    ip_address VARCHAR(45),
    user_agent VARCHAR(500),
    request_body TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_api_key_id (api_key_id),
    INDEX idx_created_at (created_at),
    FOREIGN KEY (api_key_id) REFERENCES ahg_api_key(id) ON DELETE SET NULL
);

CREATE TABLE ahg_webhook (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    url VARCHAR(500) NOT NULL,
    events JSON NOT NULL,
    secret VARCHAR(64),
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES user(id) ON DELETE CASCADE
);

CREATE TABLE ahg_webhook_delivery (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    webhook_id BIGINT UNSIGNED NOT NULL,
    event_type VARCHAR(100) NOT NULL,
    payload JSON NOT NULL,
    response_code INT,
    response_body TEXT,
    attempts INT DEFAULT 1,
    delivered_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_webhook_id (webhook_id),
    FOREIGN KEY (webhook_id) REFERENCES ahg_webhook(id) ON DELETE CASCADE
);
```

---

## Endpoints

| Method | Endpoint | Action | Scope |
|--------|----------|--------|-------|
| GET | /api/v2 | Index | - |
| GET | /api/v2/descriptions | Browse | read |
| GET | /api/v2/descriptions/:slug | Read | read |
| POST | /api/v2/descriptions | Create | write |
| PUT | /api/v2/descriptions/:slug | Update | write |
| DELETE | /api/v2/descriptions/:slug | Delete | delete |
| GET | /api/v2/authorities | Browse | read |
| GET | /api/v2/authorities/:slug | Read | read |
| GET | /api/v2/repositories | Browse | read |
| GET | /api/v2/taxonomies | Browse | read |
| GET | /api/v2/taxonomies/:id/terms | Terms | read |
| POST | /api/v2/search | Search | read |
| POST | /api/v2/batch | Batch | write |
| GET | /api/v2/keys | List Keys | admin |
| POST | /api/v2/keys | Create Key | admin |
| DELETE | /api/v2/keys/:id | Delete Key | admin |

---

## Service Methods

### ApiRepository

```php
namespace ahgAPIPlugin\Repository;

class ApiRepository
{
    // Descriptions
    public function getDescriptions(array $params): array
    public function getDescription(string $slug): ?array
    public function createDescription(array $data): array
    public function updateDescription(string $slug, array $data): array
    public function deleteDescription(string $slug): bool
    
    // Authorities
    public function getAuthorities(array $params): array
    public function getAuthority(string $slug): ?array
    
    // Repositories
    public function getRepositories(array $params): array
    
    // Taxonomies
    public function getTaxonomies(): array
    public function getTaxonomyTerms(int $taxonomyId): array
    
    // Search
    public function search(array $query): array
    
    // Batch
    public function processBatch(array $operations): array
    
    // Helpers
    protected function getSectorCode(int $displayStandardId): string
    protected function transformDescription(object $row, bool $detail = false): array
}
```

---

## Configuration

### Plugin Settings

| Setting | Default | Description |
|---------|---------|-------------|
| api_v2_enabled | true | Enable/disable API v2 |
| default_rate_limit | 1000 | Requests per hour |
| log_requests | true | Log API requests |
| max_batch_size | 100 | Maximum batch operations |

---

## Authentication

### Methods

1. **X-API-Key Header** (Recommended)
   ```
   X-API-Key: your-api-key-here
   ```

2. **Bearer Token**
   ```
   Authorization: Bearer your-api-key-here
   ```

3. **Legacy Header**
   ```
   REST-API-Key: your-api-key-here
   ```

4. **Session** (Web browser)

### Scopes

| Scope | Binary | Permissions |
|-------|--------|-------------|
| read | 0001 | GET operations |
| write | 0010 | POST, PUT operations |
| delete | 0100 | DELETE operations |
| admin | 1000 | Key management |

---

## Files

```
ahgAPIPlugin/
├── config/
│   └── ahgAPIPluginConfiguration.class.php
├── lib/
│   ├── AhgApiAction.class.php
│   └── repository/
│       └── ApiRepository.php
├── modules/
│   └── apiv2/
│       └── actions/
│           ├── indexAction.class.php
│           ├── descriptionsBrowseAction.class.php
│           ├── descriptionsReadAction.class.php
│           ├── descriptionsCreateAction.class.php
│           ├── descriptionsUpdateAction.class.php
│           ├── descriptionsDeleteAction.class.php
│           ├── authoritiesBrowseAction.class.php
│           ├── repositoriesBrowseAction.class.php
│           ├── taxonomiesBrowseAction.class.php
│           ├── searchAction.class.php
│           ├── batchAction.class.php
│           └── keysAction.class.php
├── data/
│   └── install.sql
└── extension.json
```

---

*Part of the AtoM AHG Framework*
