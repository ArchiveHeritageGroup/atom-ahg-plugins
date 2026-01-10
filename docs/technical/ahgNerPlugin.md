# ahgNerPlugin - Technical Documentation

**Version:** 1.0.0  
**Category:** AI/NLP  
**Dependencies:** atom-framework, Python NER API

---

## Overview

Named Entity Recognition (NER) plugin integrating Python-based NLP for automatic extraction of people, organizations, places, and dates from archival descriptions.

---

## Architecture

```
┌─────────────────────────────────────────────────────────────────┐
│                      ahgNerPlugin                               │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  ┌─────────────────────────────────────────────────────────┐   │
│  │                   AtoM (PHP)                            │   │
│  │  ┌───────────────┐  ┌───────────────┐                  │   │
│  │  │ NerService    │  │ NerRepository │                  │   │
│  │  └───────────────┘  └───────────────┘                  │   │
│  └─────────────────────────────────────────────────────────┘   │
│                           │                                     │
│                           │ HTTP/REST                           │
│                           ▼                                     │
│  ┌─────────────────────────────────────────────────────────┐   │
│  │               Python NER API (Flask)                    │   │
│  │  ┌───────────────┐  ┌───────────────┐                  │   │
│  │  │ spaCy Model   │  │ Custom Rules  │                  │   │
│  │  │ (en_core_web) │  │ (SA names)    │                  │   │
│  │  └───────────────┘  └───────────────┘                  │   │
│  └─────────────────────────────────────────────────────────┘   │
│                           │                                     │
│                           ▼                                     │
│  ┌─────────────────────────────────────────────────────────┐   │
│  │                  Database Tables                        │   │
│  └─────────────────────────────────────────────────────────┘   │
│                                                                 │
└─────────────────────────────────────────────────────────────────┘
```

---

## Database Schema

### ERD Diagram

```
┌─────────────────────────────────────────┐
│           ner_extraction                │
├─────────────────────────────────────────┤
│ PK id INT                              │
│ FK object_id INT                       │──────┐
│    object_type VARCHAR                  │      │
│    source_field VARCHAR                 │      │
│    source_text TEXT                     │      │
│    extraction_date TIMESTAMP            │      │
│    model_version VARCHAR                │      │
│    confidence_threshold DECIMAL         │      │
│    status ENUM                          │      │
│    processed_by INT                     │      │
│    created_at TIMESTAMP                 │      │
│    updated_at TIMESTAMP                 │      │
└─────────────────────────────────────────┘      │
              │                                   │
              │ 1:N                               │
              ▼                                   │
┌─────────────────────────────────────────┐      │
│            ner_entity                   │      │
├─────────────────────────────────────────┤      │
│ PK id INT                              │      │
│ FK extraction_id INT                   │──────┤
│    entity_text VARCHAR                  │      │
│    entity_type ENUM                     │      │
│    start_position INT                   │      │
│    end_position INT                     │      │
│    confidence DECIMAL                   │      │
│    status ENUM                          │      │
│    linked_actor_id INT                  │      │
│    linked_term_id INT                   │      │
│    reviewed_by INT                      │      │
│    reviewed_at TIMESTAMP                │      │
│    notes TEXT                           │      │
│    created_at TIMESTAMP                 │      │
└─────────────────────────────────────────┘      │
                                                  │
┌─────────────────────────────────────────┐      │
│           ner_model_config              │      │
├─────────────────────────────────────────┤      │
│ PK id INT                              │      │
│    model_name VARCHAR                   │      │
│    model_version VARCHAR                │      │
│    language VARCHAR                     │      │
│    entity_types JSON                    │      │
│    custom_rules JSON                    │      │
│    is_active TINYINT                    │      │
│    created_at TIMESTAMP                 │      │
└─────────────────────────────────────────┘      │
                                                  │
┌─────────────────────────────────────────┐      │
│          ner_training_data              │      │
├─────────────────────────────────────────┤      │
│ PK id INT                              │      │
│    text TEXT                            │      │
│    entities JSON                        │      │
│    source VARCHAR                       │      │
│    validated TINYINT                    │      │
│    created_at TIMESTAMP                 │      │
└─────────────────────────────────────────┘
```

---

## Entity Types

| Type | Label | Description |
|------|-------|-------------|
| PERSON | Person | Individual names |
| ORG | Organization | Companies, institutions |
| GPE | Place | Countries, cities |
| LOC | Location | Geographic features |
| DATE | Date | Dates and periods |
| EVENT | Event | Historical events |
| WORK | Work | Publications, artworks |

---

## Python API Endpoints

| Endpoint | Method | Description |
|----------|--------|-------------|
| /api/ner/extract | POST | Extract entities from text |
| /api/ner/batch | POST | Batch extraction |
| /api/ner/models | GET | List available models |
| /api/ner/health | GET | API health check |

---

## Service Methods

### NerService

```php
namespace ahgNerPlugin\Service;

class NerService
{
    // Extraction
    public function extractEntities(int $objectId, string $field): int
    public function extractFromText(string $text, array $options): array
    public function batchExtract(array $objectIds): array
    public function reprocessObject(int $objectId): bool
    
    // Review
    public function getExtraction(int $id): ?array
    public function getEntitiesForReview(array $filters): Collection
    public function approveEntity(int $entityId, int $userId): bool
    public function rejectEntity(int $entityId, int $userId, string $reason): bool
    public function linkToAuthority(int $entityId, int $actorId): bool
    public function linkToTerm(int $entityId, int $termId): bool
    
    // Bulk
    public function approveAll(int $extractionId, int $userId): int
    public function createAuthoritiesFromEntities(int $extractionId): int
    
    // Stats
    public function getExtractionStats(): array
    public function getEntityTypeDistribution(): array
}
```

---

## Python NER API

```python
# api/ner_api.py
from flask import Flask, request, jsonify
import spacy

app = Flask(__name__)
nlp = spacy.load("en_core_web_lg")

@app.route('/api/ner/extract', methods=['POST'])
def extract():
    data = request.json
    text = data.get('text', '')
    doc = nlp(text)
    
    entities = []
    for ent in doc.ents:
        entities.append({
            'text': ent.text,
            'type': ent.label_,
            'start': ent.start_char,
            'end': ent.end_char,
            'confidence': 0.85  # spaCy doesn't provide confidence
        })
    
    return jsonify({
        'success': True,
        'entities': entities
    })
```

---

## Workflow

```
┌─────────────────────────────────────────────────────────────────┐
│                      NER WORKFLOW                               │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│   ┌─────────────┐                                              │
│   │  Archival   │                                              │
│   │  Record     │                                              │
│   └─────────────┘                                              │
│         │                                                       │
│         │ "Extract Entities"                                   │
│         ▼                                                       │
│   ┌─────────────┐     ┌─────────────┐                          │
│   │  Get Text   │────▶│  Call NER   │                          │
│   │  Fields     │     │  API        │                          │
│   └─────────────┘     └─────────────┘                          │
│                             │                                   │
│                             ▼                                   │
│   ┌─────────────────────────────────────────────────────────┐  │
│   │  Store Entities (status = 'pending')                    │  │
│   └─────────────────────────────────────────────────────────┘  │
│         │                                                       │
│         ▼                                                       │
│   ┌─────────────┐                                              │
│   │   Review    │  Staff reviews each entity                   │
│   │   Queue     │                                              │
│   └─────────────┘                                              │
│         │                                                       │
│    ┌────┴────┬────────────┐                                   │
│    ▼         ▼            ▼                                   │
│ ┌──────┐ ┌────────┐ ┌──────────┐                             │
│ │Approve│ │ Link   │ │ Reject   │                             │
│ │      │ │ to     │ │          │                             │
│ │      │ │Authority│ │          │                             │
│ └──────┘ └────────┘ └──────────┘                             │
│    │         │                                                 │
│    ▼         ▼                                                 │
│ ┌──────────────────────────────────────────────────────────┐  │
│ │  Create Access Points / Link to Existing Authorities     │  │
│ └──────────────────────────────────────────────────────────┘  │
│                                                                 │
└─────────────────────────────────────────────────────────────────┘
```

---

*Part of the AtoM AHG Framework*
