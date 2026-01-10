# ahgPrivacyPlugin - Technical Documentation

**Version:** 1.0.0  
**Category:** Compliance  
**Dependencies:** atom-framework

---

## Overview

Privacy compliance module supporting POPIA, GDPR, PAIA, and other data protection regulations. Includes DSAR management, breach register, consent tracking, and ROPA (Records of Processing Activities).

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
