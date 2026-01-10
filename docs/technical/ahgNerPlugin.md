# ahgNerPlugin - Technical Documentation

## Overview

The AHG NER (Named Entity Recognition) Plugin integrates a Python-based NER API with AtoM to automatically extract and link entities (people, organizations, places, dates) from archival records.

---

## Architecture
```
┌─────────────────────────────────────────────────────────────────────────────┐
│                              SYSTEM ARCHITECTURE                             │
├─────────────────────────────────────────────────────────────────────────────┤
│                                                                              │
│  ┌──────────────────────────────────────────────────────────────────────┐   │
│  │                         AtoM / PHP Layer                              │   │
│  │                                                                       │   │
│  │   ahgNerPlugin/                                                       │   │
│  │   ├── lib/service/NerService.php      → HTTP client to Python API    │   │
│  │   ├── lib/repository/NerRepository.php → Database operations         │   │
│  │   ├── modules/ahgNer/                                                 │   │
│  │   │   ├── actions/actions.class.php   → Controller actions           │   │
│  │   │   └── templates/                  → Review UI templates          │   │
│  │   └── config/routing.yml              → URL routes                   │   │
│  │                                                                       │   │
│  └───────────────────────────────┬──────────────────────────────────────┘   │
│                                  │                                           │
│                            HTTP POST                                         │
│                                  │                                           │
│  ┌───────────────────────────────▼──────────────────────────────────────┐   │
│  │                      Python NER API (Flask)                           │   │
│  │                      Port: 5002 (internal)                            │   │
│  │                                                                       │   │
│  │   /opt/ahg-ner/                                                       │   │
│  │   ├── api/ner_service.py           → Flask REST API                  │   │
│  │   ├── lib/text_extractor.py        → PDF/text extraction             │   │
│  │   ├── lib/text_cleaner.py          → Text preprocessing              │   │
│  │   ├── lib/entity_extractor.py      → spaCy NER extraction            │   │
│  │   └── models/trained_model.spacy   → Custom trained model            │   │
│  │                                                                       │   │
│  │   Systemd Service: ahg-ner.service                                    │   │
│  │                                                                       │   │
│  └──────────────────────────────────────────────────────────────────────┘   │
│                                                                              │
└─────────────────────────────────────────────────────────────────────────────┘
```

---

## Components

### 1. Python NER API

**Location:** `/opt/ahg-ner/`

**Service:** `ahg-ner.service` (systemd)

**Port:** 5002 (internal only)

#### API Endpoints

| Endpoint | Method | Auth | Description |
|----------|--------|------|-------------|
| `/ner/v1/health` | GET | No | Health check |
| `/ner/v1/models` | GET | No | List available models |
| `/ner/v1/extract` | POST | Yes | Extract entities from text |
| `/ner/v1/extract-pdf` | POST | Yes | Extract from PDF file |
| `/ner/v1/usage` | GET | Yes | View usage statistics |

#### Request/Response Examples

**Extract Entities:**
```bash
curl -X POST http://192.168.0.112:5002/ner/v1/extract \
  -H "Content-Type: application/json" \
  -H "X-API-Key: ner_demo_ahg_internal_2026" \
  -d '{"text": "Nelson Mandela was born in Mvezo on 18 July 1918."}'
```

**Response:**
```json
{
    "success": true,
    "entities": {
        "PERSON": ["Nelson Mandela"],
        "GPE": ["Mvezo"],
        "DATE": ["18 July 1918"]
    },
    "entity_count": 3,
    "model": "ahg-ner-v1",
    "processing_time_ms": 13,
    "text_length": 51
}
```

#### Python Dependencies
```
flask>=2.0
spacy>=3.5
pdfminer.six
pytesseract
Pillow
gunicorn
```

#### Service Management
```bash
# Start service
systemctl start ahg-ner

# Stop service
systemctl stop ahg-ner

# Restart service
systemctl restart ahg-ner

# View status
systemctl status ahg-ner

# View logs
journalctl -u ahg-ner -f
```

---

### 2. AtoM Plugin (PHP)

**Location:** `/usr/share/nginx/archive/plugins/ahgNerPlugin/`

#### Directory Structure
```
ahgNerPlugin/
├── config/
│   ├── ahgNerPluginConfiguration.class.php
│   └── routing.yml
├── lib/
│   ├── service/
│   │   └── NerService.php
│   └── repository/
│       └── NerRepository.php
├── modules/
│   └── ahgNer/
│       ├── actions/
│       │   └── actions.class.php
│       └── templates/
│           ├── reviewSuccess.php
│           └── _entityCard.php
├── data/
│   └── install.sql
└── extension.json
```

#### Routes (routing.yml)
```yaml
ahg_ner_extract:
  url: /ner/extract/:id
  param: { module: ahgNer, action: extract }

ahg_ner_review:
  url: /ner/review
  param: { module: ahgNer, action: review }

ahg_ner_entities:
  url: /ner/entities/:id
  param: { module: ahgNer, action: getEntities }

ahg_ner_update:
  url: /ner/entity/update
  param: { module: ahgNer, action: updateEntity }

ahg_ner_create_actor:
  url: /ner/create/actor
  param: { module: ahgNer, action: createActor }

ahg_ner_create_place:
  url: /ner/create/place
  param: { module: ahgNer, action: createPlace }

ahg_ner_create_subject:
  url: /ner/create/subject
  param: { module: ahgNer, action: createSubject }

ahg_ner_health:
  url: /ner/health
  param: { module: ahgNer, action: health }
```

#### NerService.php
```php
<?php

namespace ahgNerPlugin\Service;

class NerService
{
    private string $apiUrl = 'http://192.168.0.112:5002/ner/v1';
    private string $apiKey = 'ner_demo_ahg_internal_2026';
    private int $timeout = 30;

    public function extract(string $text, bool $clean = true): array
    {
        return $this->request('POST', '/extract', [
            'text' => $text,
            'clean' => $clean
        ]);
    }

    public function health(): array
    {
        return $this->request('GET', '/health');
    }

    private function request(string $method, string $endpoint, array $data = []): array
    {
        $ch = curl_init($this->apiUrl . $endpoint);
        
        $headers = [
            'Content-Type: application/json',
            'X-API-Key: ' . $this->apiKey
        ];
        
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_HTTPHEADER => $headers
        ]);
        
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200) {
            return ['success' => false, 'error' => 'API error: ' . $httpCode];
        }
        
        return json_decode($response, true) ?? ['success' => false];
    }
}
```

#### NerRepository.php
```php
<?php

namespace ahgNerPlugin\Repository;

use Illuminate\Database\Capsule\Manager as DB;

class NerRepository
{
    public function saveExtraction(int $objectId, array $entities): int
    {
        $extractionId = DB::table('ahg_ner_extraction')->insertGetId([
            'object_id' => $objectId,
            'backend_used' => 'local',
            'status' => 'completed',
            'entity_count' => array_sum(array_map('count', $entities)),
            'extracted_at' => now()
        ]);

        foreach ($entities as $type => $values) {
            foreach ($values as $value) {
                DB::table('ahg_ner_entity')->insert([
                    'extraction_id' => $extractionId,
                    'object_id' => $objectId,
                    'entity_type' => $type,
                    'entity_value' => $value,
                    'status' => 'pending',
                    'created_at' => now()
                ]);
            }
        }

        return $extractionId;
    }

    public function getPendingEntities(int $objectId): Collection
    {
        return DB::table('ahg_ner_entity')
            ->where('object_id', $objectId)
            ->where('status', 'pending')
            ->get();
    }

    public function updateEntityStatus(int $entityId, string $status, ?int $actorId = null): bool
    {
        return DB::table('ahg_ner_entity')
            ->where('id', $entityId)
            ->update([
                'status' => $status,
                'linked_actor_id' => $actorId,
                'updated_at' => now()
            ]) > 0;
    }

    public function findMatchingActors(string $name, string $type): array
    {
        $query = DB::table('actor')
            ->join('actor_i18n', 'actor.id', '=', 'actor_i18n.id')
            ->where('actor_i18n.culture', 'en');

        // Exact matches
        $exact = (clone $query)
            ->where('actor_i18n.authorized_form_of_name', $name)
            ->select('actor.id', 'actor_i18n.authorized_form_of_name')
            ->get();

        // Partial matches
        $partial = (clone $query)
            ->where('actor_i18n.authorized_form_of_name', 'LIKE', '%' . $name . '%')
            ->whereNotIn('actor.id', $exact->pluck('id'))
            ->select('actor.id', 'actor_i18n.authorized_form_of_name')
            ->limit(5)
            ->get();

        return [
            'exact' => $exact->toArray(),
            'partial' => $partial->toArray()
        ];
    }

    public function getPendingCount(): int
    {
        return DB::table('ahg_ner_entity')
            ->where('status', 'pending')
            ->count();
    }
}
```

---

### 3. Database Schema
```sql
-- Extraction tracking
CREATE TABLE IF NOT EXISTS ahg_ner_extraction (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    object_id INT NOT NULL,
    backend_used VARCHAR(50) DEFAULT 'local',
    status VARCHAR(50) DEFAULT 'pending',
    entity_count INT DEFAULT 0,
    extracted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_object_id (object_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Individual entities
CREATE TABLE IF NOT EXISTS ahg_ner_entity (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    extraction_id BIGINT UNSIGNED NOT NULL,
    object_id INT NOT NULL,
    entity_type VARCHAR(50) NOT NULL,
    entity_value VARCHAR(500) NOT NULL,
    confidence DECIMAL(5,4) DEFAULT NULL,
    status ENUM('pending', 'approved', 'rejected', 'linked') DEFAULT 'pending',
    linked_actor_id INT DEFAULT NULL,
    reviewed_by INT DEFAULT NULL,
    reviewed_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_object_id (object_id),
    INDEX idx_status (status),
    INDEX idx_entity_type (entity_type),
    FOREIGN KEY (extraction_id) REFERENCES ahg_ner_extraction(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

---

## Entity Types

| Type | spaCy Label | Description | Links To |
|------|-------------|-------------|----------|
| PERSON | PER | Person names | Actor (entity_type_id = 132) |
| ORG | ORG | Organizations | Actor (entity_type_id = 131) |
| GPE | GPE | Geo-political entities | Actor or Subject |
| DATE | DATE | Dates and time periods | Reference only |

---

## Workflow
```
┌─────────────────────────────────────────────────────────────────────┐
│                         NER EXTRACTION FLOW                          │
├─────────────────────────────────────────────────────────────────────┤
│                                                                      │
│  1. User clicks "Extract Entities (NER)" on record                  │
│     │                                                                │
│     ▼                                                                │
│  2. PHP NerService::extract() called                                │
│     │                                                                │
│     ▼                                                                │
│  3. HTTP POST to Python API /ner/v1/extract                         │
│     │                                                                │
│     ▼                                                                │
│  4. Python: text_extractor → text_cleaner → entity_extractor        │
│     │                                                                │
│     ▼                                                                │
│  5. spaCy processes text with custom model                          │
│     │                                                                │
│     ▼                                                                │
│  6. JSON response with entities returned to PHP                     │
│     │                                                                │
│     ▼                                                                │
│  7. NerRepository::saveExtraction() stores in database              │
│     │                                                                │
│     ▼                                                                │
│  8. User reviews entities at /ner/review                            │
│     │                                                                │
│     ├── Approve → status = 'approved'                               │
│     ├── Reject  → status = 'rejected'                               │
│     └── Link    → status = 'linked', linked_actor_id set            │
│                   Creates relation record (Name Access Point)        │
│                                                                      │
└─────────────────────────────────────────────────────────────────────┘
```

---

## Configuration

### Python API Configuration

**File:** `/opt/ahg-ner/config.py`
```python
# API Settings
API_HOST = '0.0.0.0'
API_PORT = 5002
API_KEY = 'ner_demo_ahg_internal_2026'

# Model Settings
MODEL_PATH = '/opt/ahg-ner/models/trained_model.spacy'
FALLBACK_MODEL = 'en_core_web_trf'

# Processing
MAX_TEXT_LENGTH = 100000
BATCH_SIZE = 100
```

### PHP Plugin Configuration

**File:** `ahgNerPluginConfiguration.class.php`
```php
// API URL (change for production)
$this->apiUrl = 'http://192.168.0.112:5002/ner/v1';
// $this->apiUrl = 'https://api.theahg.co.za/ner/v1';

// API Key
$this->apiKey = 'ner_demo_ahg_internal_2026';
```

---

## Installation

### 1. Python NER Service
```bash
# Create directory
mkdir -p /opt/ahg-ner/{api,lib,models}

# Install dependencies
pip3 install flask spacy pdfminer.six pytesseract Pillow gunicorn

# Download spaCy model (if not using custom)
python3 -m spacy download en_core_web_trf

# Copy custom model
cp -r /path/to/trained_model.spacy /opt/ahg-ner/models/

# Create systemd service
cat > /etc/systemd/system/ahg-ner.service << 'EOF'
[Unit]
Description=AHG NER API Service
After=network.target

[Service]
Type=simple
User=www-data
WorkingDirectory=/opt/ahg-ner
ExecStart=/usr/bin/python3 /opt/ahg-ner/api/ner_service.py
Restart=always
RestartSec=5

[Install]
WantedBy=multi-user.target
