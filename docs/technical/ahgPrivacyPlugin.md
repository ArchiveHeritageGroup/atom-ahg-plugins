# ahgPrivacyPlugin - Technical Documentation

**Version:** 1.0.0  
**Category:** Compliance  
**Dependencies:** atom-framework

---

## Overview

Privacy compliance module supporting POPIA, GDPR, PAIA, and other data protection regulations. Includes DSAR management, breach register, consent tracking, ROPA (Records of Processing Activities), and **AI-powered PII detection**.

---

## Architecture

```
┌─────────────────────────────────────────────────────────────────┐
│                     ahgPrivacyPlugin                            │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  ┌───────────────┐  ┌───────────────┐  ┌───────────────┐       │
│  │     DSAR      │  │    Breach     │  │    Consent    │       │
│  │   Module      │  │   Register    │  │   Management  │       │
│  └───────────────┘  └───────────────┘  └───────────────┘       │
│         │                  │                  │                 │
│         └──────────────────┼──────────────────┘                 │
│                            ▼                                    │
│  ┌─────────────────────────────────────────────────────────┐   │
│  │                   PrivacyService                        │   │
│  └─────────────────────────────────────────────────────────┘   │
│                            │                                    │
│                            ▼                                    │
│  ┌─────────────────────────────────────────────────────────┐   │
│  │                  PrivacyRepository                      │   │
│  └─────────────────────────────────────────────────────────┘   │
│                            │                                    │
│         ┌──────────────────┼──────────────────┐                │
│         ▼                  ▼                  ▼                │
│  ┌───────────┐      ┌───────────┐      ┌───────────┐          │
│  │privacy_   │      │privacy_   │      │privacy_   │          │
│  │dsar       │      │breach     │      │consent    │          │
│  └───────────┘      └───────────┘      └───────────┘          │
│                                                                 │
└─────────────────────────────────────────────────────────────────┘
```

---

## PII Detection System

### Architecture

```
┌─────────────────────────────────────────────────────────────────┐
│                    PII Detection Service                         │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  ┌───────────────┐     ┌───────────────┐     ┌───────────────┐ │
│  │  Regex-Based  │     │   NER-Based   │     │  Risk Score   │ │
│  │   Detection   │     │  Integration  │     │  Calculator   │ │
│  │               │     │               │     │               │ │
│  │ • SA_ID       │     │ • PERSON      │     │ • 0-100 score │ │
│  │ • NG_NIN      │     │ • ORG         │     │ • Risk levels │ │
│  │ • PASSPORT    │     │ • GPE         │     │ • Flagging    │ │
│  │ • EMAIL       │     │ • DATE        │     │               │ │
│  │ • PHONE_SA    │     │               │     │               │ │
│  │ • BANK_ACCOUNT│     │               │     │               │ │
│  │ • CREDIT_CARD │     │               │     │               │ │
│  └───────────────┘     └───────────────┘     └───────────────┘ │
│           │                    │                    │           │
│           └────────────────────┼────────────────────┘           │
│                                ▼                                │
│  ┌─────────────────────────────────────────────────────────┐   │
│  │                   PiiDetectionService                    │   │
│  │  • detectPii()      - Regex patterns                     │   │
│  │  • fullScan()       - Regex + NER combined               │   │
│  │  • scanObject()     - Scan information object            │   │
│  │  • batchScan()      - Scan multiple objects              │   │
│  │  • saveScanResults()- Store in database                  │   │
│  │  • getStatistics()  - Dashboard stats                    │   │
│  └─────────────────────────────────────────────────────────┘   │
│                                │                                │
│         ┌──────────────────────┼──────────────────────┐        │
│         ▼                      ▼                      ▼        │
│  ┌───────────────┐    ┌───────────────┐    ┌───────────────┐  │
│  │ahg_ner_       │    │ahg_ner_entity │    │privacy_data_  │  │
│  │extraction     │    │               │    │inventory      │  │
│  └───────────────┘    └───────────────┘    └───────────────┘  │
│                                                                 │
└─────────────────────────────────────────────────────────────────┘
```

### PII Types Detected

| Type | Method | Risk Level | Validation | Source |
|------|--------|------------|------------|--------|
| CREDIT_CARD | Regex | Critical | Luhn algorithm | Metadata |
| SA_ID | Regex | High | SA Luhn checksum | Metadata |
| NG_NIN | Regex | High | 11-digit format | Metadata |
| PASSPORT | Regex | High | Pattern match | Metadata |
| BANK_ACCOUNT | Regex | High | Context-based | Metadata |
| TAX_NUMBER | Regex | High | Context-based | Metadata |
| PERSON | NER (spaCy) | Medium | - | OCR/Text |
| EMAIL | Regex | Medium | RFC validation | Metadata |
| PHONE_SA | Regex | Medium | SA format | Metadata |
| PHONE_INTL | Regex | Medium | Intl format | Metadata |
| ORG | NER (spaCy) | Low | - | OCR/Text |
| GPE | NER (spaCy) | Low | - | OCR/Text |
| DATE | NER (spaCy) | Low | - | OCR/Text |
| ISAD_SUBJECT | ISAD Access Point | Medium | - | Taxonomy 35 |
| ISAD_PLACE | ISAD Access Point | Medium | - | Taxonomy 42 |
| ISAD_NAME | ISAD Access Point | Medium | - | Events/Actors |
| ISAD_DATE | ISAD Access Point | Low | - | Events |

### PiiDetectionService Methods

```php
namespace ahgPrivacyPlugin\Service;

class PiiDetectionService
{
    // Detection
    public function detectPii(string $text): array
    public function fullScan(string $text): array
    public function scanObject(int $objectId, bool $includeDigitalObjects = true): array
    public function scanDigitalObject(int $objectId): ?array

    // ISAD Access Points (NEW)
    public function getIsadAccessPoints(int $objectId): array
    public function convertAccessPointsToEntities(int $objectId): array

    // Batch Processing
    public function batchScan(array $filters = [], int $limit = 100): array

    // Persistence
    public function saveScanResults(int $objectId, array $results, ?int $userId = null): int

    // Statistics
    public function getStatistics(): array

    // Validation
    protected function validateSaId(string $id): bool  // Luhn checksum
    protected function looksLikeFinancial(string $text, int $position, string $value): bool

    // Risk Calculation
    protected function calculateRiskScore(array $summary): int  // 0-100
    protected function calculateConfidence(string $type, string $value, string $text): float
}
```

### ISAD Access Points Integration

The PII scanner extracts potential PII from ISAD(G) access points:

```
┌─────────────────────────────────────────────────────────────────┐
│               ISAD Access Point Extraction                       │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  ┌───────────────┐                                              │
│  │ information_  │                                              │
│  │ object        │                                              │
│  └───────┬───────┘                                              │
│          │                                                      │
│    ┌─────┴─────┬───────────────┬───────────────┐               │
│    ▼           ▼               ▼               ▼               │
│ ┌──────┐  ┌──────────┐   ┌──────────┐   ┌──────────┐          │
│ │object│  │object_   │   │  event   │   │  event   │          │
│ │_term_│  │term_     │   │ (names)  │   │ (dates)  │          │
│ │rel   │  │relation  │   └────┬─────┘   └────┬─────┘          │
│ │(subj)│  │(places)  │        │              │                 │
│ └──┬───┘  └────┬─────┘        │              │                 │
│    │           │              │              │                 │
│    ▼           ▼              ▼              ▼                 │
│ ┌────────────────────────────────────────────────────┐        │
│ │              getIsadAccessPoints()                 │        │
│ │                                                    │        │
│ │  Returns: {                                        │        │
│ │    subjects: [term names from taxonomy 35],        │        │
│ │    places: [term names from taxonomy 42],          │        │
│ │    names: [actor names from events],               │        │
│ │    dates: [date ranges from events]                │        │
│ │  }                                                 │        │
│ └────────────────────────────────────────────────────┘        │
│                                                                 │
└─────────────────────────────────────────────────────────────────┘
```

**Database Queries:**

```php
// Subjects (Taxonomy 35)
DB::table('object_term_relation as otr')
    ->join('term as t', 'otr.term_id', '=', 't.id')
    ->join('term_i18n as ti', 't.id', '=', 'ti.id')
    ->where('otr.object_id', $objectId)
    ->where('t.taxonomy_id', 35)
    ->pluck('ti.name');

// Places (Taxonomy 42)
DB::table('object_term_relation as otr')
    ->join('term as t', 'otr.term_id', '=', 't.id')
    ->join('term_i18n as ti', 't.id', '=', 'ti.id')
    ->where('otr.object_id', $objectId)
    ->where('t.taxonomy_id', 42)
    ->pluck('ti.name');

// Names (from events/actors)
DB::table('event as e')
    ->join('actor as a', 'e.actor_id', '=', 'a.id')
    ->join('actor_i18n as ai', 'a.id', '=', 'ai.id')
    ->where('e.object_id', $objectId)
    ->whereIn('e.type_id', [111, 118])  // creation, accumulation
    ->pluck('ai.authorized_form_of_name');

// Dates (from events)
DB::table('event')
    ->where('object_id', $objectId)
    ->whereNotNull('start_date')
    ->get(['start_date', 'end_date']);
```

### CLI Commands

```bash
# Show statistics
php symfony privacy:scan-pii --stats

# Scan specific object
php symfony privacy:scan-pii --id=123

# Batch scan (default 100 objects)
php symfony privacy:scan-pii --limit=50

# Scan specific repository
php symfony privacy:scan-pii --repository=5

# Re-scan already scanned objects
php symfony privacy:scan-pii --rescan

# Verbose output
php symfony privacy:scan-pii --verbose
```

### Web UI Endpoints

| Action | URL | Method |
|--------|-----|--------|
| PII Scanner Dashboard | `/privacyAdmin/piiScan` | GET |
| Run Batch Scan | `/privacyAdmin/piiScanRun` | POST |
| View Object PII | `/privacyAdmin/piiScanObject?id=X` | GET |
| Review Queue | `/privacyAdmin/piiReview` | GET |
| Entity Action | `/privacyAdmin/piiEntityAction` | POST |
| AJAX Scan | `/privacyAdmin/piiScanAjax?id=X` | GET |

### Information Object Integration

PII scanning is integrated into the information object context menu:

```
┌─────────────────────────────┐
│ Privacy & PII               │
├─────────────────────────────┤
│ 🛡️ Scan for PII             │  ← Opens modal with results
│ 📋 PII Review Queue         │  ← Review pending entities
│ 📊 PII Dashboard            │  ← Statistics overview
└─────────────────────────────┘
```

### Risk Score Calculation

```
Risk Score = (critical × 30) + (high × 20) + (medium × 5) + (low × 1)
Maximum: 100
```

| Score Range | Classification |
|-------------|----------------|
| 0-20 | Low Risk (Green) |
| 21-50 | Medium Risk (Yellow) |
| 51-100 | High Risk (Red) |

---

## PDF Redaction System

### Architecture

```
┌─────────────────────────────────────────────────────────────────┐
│                    PDF Redaction Architecture                    │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  ┌────────────────┐      ┌────────────────┐                    │
│  │ PiiDetection   │      │ PdfRedaction   │                    │
│  │ Service        │─────▶│ Service        │                    │
│  └────────────────┘      └───────┬────────┘                    │
│         │                        │                              │
│         │                        ▼                              │
│         │               ┌────────────────┐                     │
│         │               │ PyMuPDF (fitz) │                     │
│         │               │ Python Script  │                     │
│         │               └───────┬────────┘                     │
│         │                       │                               │
│         ▼                       ▼                               │
│  ┌───────────────┐      ┌────────────────┐                     │
│  │ahg_ner_entity │      │cache/pii_      │                     │
│  │(status=redact)│      │redacted/       │                     │
│  └───────────────┘      └───────┬────────┘                     │
│                                 │                               │
│         ┌───────────────────────┼───────────────┐              │
│         ▼                       ▼               ▼              │
│  ┌──────────────┐      ┌──────────────┐ ┌─────────────┐       │
│  │IiifManifest  │      │ViewerService │ │DigitalObject│       │
│  │Service       │      │              │ │ViewerHelper │       │
│  └──────────────┘      └──────────────┘ └─────────────┘       │
│         │                      │               │               │
│         └──────────────────────┼───────────────┘               │
│                                ▼                                │
│                      ┌──────────────────┐                      │
│                      │  Public sees     │                      │
│                      │  redacted PDF    │                      │
│                      └──────────────────┘                      │
│                                                                 │
└─────────────────────────────────────────────────────────────────┘
```

### PdfRedactionService

```php
namespace ahgPrivacyPlugin\Service;

class PdfRedactionService
{
    // Get all redactable terms for an object
    public function getAllPotentialTerms(int $objectId): array

    // Generate redacted PDF
    public function redactPdf(string $inputPath, string $outputPath, array $terms): bool

    // Check if redacted version exists
    public function hasRedactedVersion(int $objectId): bool

    // Get path to redacted PDF
    public function getRedactedPath(int $objectId): ?string

    // Delete redacted version (re-generate)
    public function clearRedactedVersion(int $objectId): bool
}
```

### Redaction Term Sources

The `getAllPotentialTerms()` method collects terms from:

| Source | Database Table | Condition |
|--------|---------------|-----------|
| NER Entities | `ahg_ner_entity` | `status = 'redacted'` |
| ISAD Subjects | `object_term_relation` + `term` | `taxonomy_id = 35` |
| ISAD Places | `object_term_relation` + `term` | `taxonomy_id = 42` |
| ISAD Names | `event` + `actor` | Event types 111, 118 |

### Python Redaction Script

Location: `ahgPrivacyPlugin/lib/python/redact_pdf.py`

```python
#!/usr/bin/env python3
import sys
import fitz  # PyMuPDF

def redact_pdf(input_path, output_path, terms):
    doc = fitz.open(input_path)

    for page in doc:
        for term in terms:
            instances = page.search_for(term)
            for inst in instances:
                page.add_redact_annot(inst, fill=(0, 0, 0))
        page.apply_redactions()

    doc.save(output_path)
    doc.close()

if __name__ == "__main__":
    input_path = sys.argv[1]
    output_path = sys.argv[2]
    terms = sys.argv[3:]  # Remaining args are terms
    redact_pdf(input_path, output_path, terms)
```

### Storage Paths

| Type | Path | Example |
|------|------|---------|
| Original PDF | `uploads/r/{repo}/digitalobjects/{id}/` | `uploads/r/1/digitalobjects/902316/doc.pdf` |
| Redacted PDF | `cache/pii_redacted/` | `cache/pii_redacted/redacted_902316_doc.pdf` |
| IIIF Symlink | `uploads/pii_redacted/` | `uploads/pii_redacted/redacted_902316_doc.pdf` |

### Viewer Integration

#### IiifManifestService

Modified to check for PII redaction and use redacted PDF:

```php
private function getPdfPathWithRedaction(object $do): array
{
    $objectId = $do->object_id;

    // Check for redacted entities
    $redactedCount = DB::table('ahg_ner_entity')
        ->where('object_id', $objectId)
        ->where('status', 'redacted')
        ->count();

    if ($redactedCount > 0) {
        $redactedPath = $this->getOrGenerateRedactedPdf($objectId, $do);
        if ($redactedPath) {
            return [
                'path' => $redactedPath,
                'identifier' => basename($redactedPath),
                'is_redacted' => true
            ];
        }
    }

    // Return original
    return [
        'path' => $this->getDigitalObjectPath($do),
        'identifier' => $do->name,
        'is_redacted' => false
    ];
}
```

#### ViewerService

Modified to pass redacted PDF URL to PDF.js viewer:

```php
public function getPdfUrlWithRedaction(int $objectId): ?string
{
    if (!$this->hasPiiRedaction($objectId)) {
        return null;
    }

    return url_for([
        'module' => 'privacyAdmin',
        'action' => 'downloadPdf',
        'id' => $objectId
    ]);
}
```

#### DigitalObjectViewerHelper

Modified to use redacted PDF in iframe embeds:

```php
if ($isPDF) {
    $pdfViewUrl = $digitalObjectLink;

    if (in_array('ahgPrivacyPlugin', sfProjectConfiguration::getActive()->getPlugins())) {
        $redactedCount = DB::table('ahg_ner_entity')
            ->where('object_id', $objectId)
            ->where('status', 'redacted')
            ->count();

        if ($redactedCount > 0) {
            $pdfViewUrl = url_for([
                'module' => 'privacyAdmin',
                'action' => 'downloadPdf',
                'id' => $objectId
            ]);
        }
    }
}
```

### Public Access Endpoint

The `downloadPdf` action allows unauthenticated access to redacted PDFs:

```php
// privacyAdmin/actions/actions.class.php

public function preExecute()
{
    // downloadPdf can be accessed by anyone (public access)
    if ($this->getActionName() === 'downloadPdf') {
        return;
    }

    // All other actions require authentication
    if (!$this->getUser()->isAuthenticated()) {
        $this->redirect(['module' => 'user', 'action' => 'login']);
    }
}

public function executeDownloadPdf(sfWebRequest $request)
{
    $id = $request->getParameter('id');

    // Get redacted PDF path
    $redactedPath = sfConfig::get('sf_cache_dir') . '/pii_redacted/redacted_' . $id . '_*.pdf';
    $files = glob($redactedPath);

    if (empty($files)) {
        $this->forward404('No redacted PDF found');
    }

    $this->getResponse()->setHttpHeader('Content-Type', 'application/pdf');
    return $this->renderText(file_get_contents($files[0]));
}
```

### Web UI Endpoints (Updated)

| Action | URL | Method | Auth |
|--------|-----|--------|------|
| PII Scanner Dashboard | `/privacyAdmin/piiScan` | GET | Required |
| Run Batch Scan | `/privacyAdmin/piiScanRun` | POST | Required |
| View Object PII | `/privacyAdmin/piiScanObject?id=X` | GET | Required |
| Review Queue | `/privacyAdmin/piiReview` | GET | Required |
| Entity Action | `/privacyAdmin/piiEntityAction` | POST | Required |
| AJAX Scan | `/privacyAdmin/piiScanAjax?id=X` | GET | Required |
| **Download Redacted PDF** | `/privacyAdmin/downloadPdf?id=X` | GET | **Public** |

---

## Database Schema

### ERD Diagram

```
┌─────────────────────────────┐       ┌─────────────────────────────┐
│       privacy_dsar          │       │     privacy_dsar_note       │
├─────────────────────────────┤       ├─────────────────────────────┤
│ PK id INT                  │       │ PK id INT                  │
│    reference_number VARCHAR │◄──────│ FK dsar_id INT             │
│    request_type ENUM        │ 1:N   │    note TEXT                │
│    status ENUM              │       │    created_by INT           │
│    data_subject_name        │       │    created_at TIMESTAMP     │
│    data_subject_email       │       └─────────────────────────────┘
│    data_subject_id_type     │
│    data_subject_id_number   │       ┌─────────────────────────────┐
│    description TEXT         │       │   privacy_dsar_document     │
│    jurisdiction ENUM        │       ├─────────────────────────────┤
│    received_date DATE       │       │ PK id INT                  │
│    due_date DATE            │◄──────│ FK dsar_id INT             │
│    completed_date DATE      │ 1:N   │    filename VARCHAR         │
│    assigned_to INT          │       │    filepath VARCHAR         │
│    verified_identity TINYINT│       │    document_type VARCHAR    │
│    fee_required TINYINT     │       │    uploaded_at TIMESTAMP    │
│    fee_amount DECIMAL       │       └─────────────────────────────┘
│    fee_paid TINYINT         │
│    extension_requested      │
│    extension_reason TEXT    │
│    outcome ENUM             │
│    outcome_notes TEXT       │
│    created_at TIMESTAMP     │
│    updated_at TIMESTAMP     │
└─────────────────────────────┘

┌─────────────────────────────┐       ┌─────────────────────────────┐
│      privacy_breach         │       │   privacy_breach_affected   │
├─────────────────────────────┤       ├─────────────────────────────┤
│ PK id INT                  │       │ PK id INT                  │
│    reference_number VARCHAR │◄──────│ FK breach_id INT           │
│    breach_date DATETIME     │ 1:N   │    data_subject_id INT      │
│    discovery_date DATETIME  │       │    data_categories JSON     │
│    reported_date DATETIME   │       │    notified TINYINT         │
│    breach_type ENUM         │       │    notified_at TIMESTAMP    │
│    severity ENUM            │       └─────────────────────────────┘
│    status ENUM              │
│    description TEXT         │
│    data_categories JSON     │
│    estimated_affected INT   │
│    actual_affected INT      │
│    cause ENUM               │
│    containment_actions TEXT │
│    remediation_actions TEXT │
│    regulator_notified TINYINT│
│    regulator_reference VARCHAR│
│    lessons_learned TEXT     │
│    created_at TIMESTAMP     │
│    updated_at TIMESTAMP     │
└─────────────────────────────┘

┌─────────────────────────────┐       ┌─────────────────────────────┐
│      privacy_consent        │       │   privacy_consent_log       │
├─────────────────────────────┤       ├─────────────────────────────┤
│ PK id INT                  │       │ PK id INT                  │
│ FK data_subject_id INT     │◄──────│ FK consent_id INT          │
│    consent_type VARCHAR     │ 1:N   │    action ENUM              │
│    purpose VARCHAR          │       │    previous_status VARCHAR  │
│    status ENUM              │       │    new_status VARCHAR       │
│    given_at TIMESTAMP       │       │    ip_address VARCHAR       │
│    expires_at TIMESTAMP     │       │    user_agent VARCHAR       │
│    withdrawn_at TIMESTAMP   │       │    created_at TIMESTAMP     │
│    source VARCHAR           │       └─────────────────────────────┘
│    evidence TEXT            │
│    created_at TIMESTAMP     │
│    updated_at TIMESTAMP     │
└─────────────────────────────┘

┌─────────────────────────────┐
│ privacy_processing_activity │
├─────────────────────────────┤
│ PK id INT                  │
│    name VARCHAR             │
│    purpose TEXT             │
│    legal_basis ENUM         │
│    data_categories JSON     │
│    data_subjects JSON       │
│    recipients JSON          │
│    transfers JSON           │
│    retention_period VARCHAR │
│    security_measures TEXT   │
│    dpia_required TINYINT    │
│    dpia_reference VARCHAR   │
│    status ENUM              │
│    owner_id INT             │
│    reviewed_at TIMESTAMP    │
│    created_at TIMESTAMP     │
│    updated_at TIMESTAMP     │
└─────────────────────────────┘
```

---

## Jurisdiction Support

| Jurisdiction | Regulation | DSAR Deadline | Breach Notification |
|--------------|------------|---------------|---------------------|
| ZA | POPIA | 30 days | 72 hours |
| EU | GDPR | 30 days | 72 hours |
| UK | UK GDPR | 30 days | 72 hours |
| US-CA | CCPA | 45 days | Varies |
| CA | PIPEDA | 30 days | ASAP |
| NG | NDPA | 30 days | 72 hours |
| KE | DPA | 30 days | 72 hours |

---

## Service Methods

### PrivacyService

```php
namespace ahgPrivacyPlugin\Service;

class PrivacyService
{
    // DSAR
    public function createDsar(array $data): int
    public function updateDsar(int $id, array $data): bool
    public function getDsar(int $id): ?array
    public function listDsars(array $filters): Collection
    public function calculateDueDate(string $jurisdiction, DateTime $received): DateTime
    public function checkOverdue(): Collection
    
    // Breach
    public function reportBreach(array $data): int
    public function updateBreach(int $id, array $data): bool
    public function getBreach(int $id): ?array
    public function listBreaches(array $filters): Collection
    public function notifyRegulator(int $breachId): bool
    
    // Consent
    public function recordConsent(array $data): int
    public function withdrawConsent(int $id, string $reason): bool
    public function checkConsent(int $subjectId, string $purpose): bool
    public function getConsentHistory(int $subjectId): Collection
    
    // ROPA
    public function createProcessingActivity(array $data): int
    public function updateProcessingActivity(int $id, array $data): bool
    public function exportRopa(string $format): string
}
```

---

## PAIA Integration

```
┌─────────────────────────────────────────────────────────────────┐
│                      PAIA Request Flow                          │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│   Request Received                                              │
│         │                                                       │
│         ▼                                                       │
│   ┌─────────────┐                                              │
│   │ Create DSAR │  request_type = 'access'                     │
│   │ (PAIA Form) │  jurisdiction = 'ZA'                         │
│   └─────────────┘                                              │
│         │                                                       │
│         ▼                                                       │
│   ┌─────────────┐     ┌─────────────┐                          │
│   │  Verify ID  │────▶│ Fee Required│                          │
│   └─────────────┘     └─────────────┘                          │
│         │                    │                                  │
│         ▼                    ▼                                  │
│   ┌─────────────┐     ┌─────────────┐                          │
│   │  Process    │◀────│  Fee Paid   │                          │
│   │  Request    │     └─────────────┘                          │
│   └─────────────┘                                              │
│         │                                                       │
│         ▼                                                       │
│   ┌─────────────┐                                              │
│   │  Complete   │  Deadline: 30 days from receipt              │
│   │  Response   │  Extension: +30 days if approved             │
│   └─────────────┘                                              │
│                                                                 │
└─────────────────────────────────────────────────────────────────┘
```

---

*Part of the AtoM AHG Framework*
