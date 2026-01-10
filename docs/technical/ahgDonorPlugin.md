# ahgDonorPlugin - Technical Documentation

**Version:** 1.0.0  
**Category:** Acquisitions  
**Dependencies:** atom-framework

---

## Overview

Comprehensive donor and agreement management system for tracking donations, gift agreements, restrictions, and provenance chains.

---

## Database Schema

### ERD Diagram

```
┌────────────────────┐
│       actor        │
├────────────────────┤
│ PK id INT         │
│    ...             │
└────────────────────┘
         │
         │ 1:1
         ▼
┌────────────────────┐       ┌─────────────────────────┐
│       donor        │       │    donor_agreement      │
├────────────────────┤       ├─────────────────────────┤
│ PK id INT (=actor) │       │ PK id INT              │
│    donor_type ENUM │◄──────│ FK donor_id INT        │
│    tax_id VARCHAR  │ 1:N   │    agreement_number     │
│    preferred_contact│      │    agreement_type ENUM  │
│    notes TEXT      │       │    title VARCHAR        │
│    created_at      │       │    status ENUM          │
│    updated_at      │       │    start_date DATE      │
└────────────────────┘       │    end_date DATE        │
                             │    description TEXT     │
                             │    total_value DECIMAL  │
                             │    terms TEXT           │
                             │    signed_date DATE     │
                             │    signatory VARCHAR    │
                             │    witness VARCHAR      │
                             │    created_at TIMESTAMP │
                             │    updated_at TIMESTAMP │
                             └─────────────────────────┘
                                        │
              ┌─────────────────────────┼─────────────────────────┐
              │                         │                         │
              ▼                         ▼                         ▼
┌─────────────────────┐  ┌─────────────────────────┐  ┌─────────────────────┐
│donor_agreement_record│  │donor_agreement_restriction│ │donor_agreement_reminder│
├─────────────────────┤  ├─────────────────────────┤  ├─────────────────────┤
│ PK id INT          │  │ PK id INT              │  │ PK id INT          │
│ FK agreement_id INT│  │ FK agreement_id INT    │  │ FK agreement_id INT│
│ FK record_id INT   │  │    restriction_type ENUM│  │    reminder_type    │
│    linked_at       │  │    description TEXT     │  │    due_date DATE    │
└─────────────────────┘  │    start_date DATE      │  │    status ENUM      │
                         │    end_date DATE        │  │    assigned_to INT  │
                         │    applies_to VARCHAR   │  │    notes TEXT       │
                         └─────────────────────────┘  │    completed_at     │
                                                      └─────────────────────┘
                                                               │
                                                               ▼
                                                ┌─────────────────────────┐
                                                │donor_agreement_reminder_log│
                                                ├─────────────────────────┤
                                                │ PK id INT              │
                                                │ FK reminder_id INT     │
                                                │    action VARCHAR       │
                                                │    sent_to VARCHAR      │
                                                │    sent_at TIMESTAMP    │
                                                └─────────────────────────┘
```

### Additional Tables

```
┌─────────────────────────┐    ┌─────────────────────────┐
│ donor_agreement_document│    │  donor_agreement_history │
├─────────────────────────┤    ├─────────────────────────┤
│ PK id INT              │    │ PK id INT              │
│ FK agreement_id INT    │    │ FK agreement_id INT    │
│    filename VARCHAR     │    │    action VARCHAR       │
│    filepath VARCHAR     │    │    field_changed VARCHAR│
│    document_type VARCHAR│    │    old_value TEXT       │
│    uploaded_by INT      │    │    new_value TEXT       │
│    uploaded_at TIMESTAMP│    │    changed_by INT       │
└─────────────────────────┘    │    changed_at TIMESTAMP │
                               └─────────────────────────┘

┌─────────────────────────┐    ┌─────────────────────────┐
│   donor_provenance      │    │ donor_agreement_accession│
├─────────────────────────┤    ├─────────────────────────┤
│ PK id INT              │    │ PK id INT              │
│ FK donor_id INT        │    │ FK agreement_id INT    │
│ FK record_id INT       │    │ FK accession_id INT    │
│    provenance_type ENUM │    │    linked_at TIMESTAMP  │
│    acquisition_date DATE│    └─────────────────────────┘
│    description TEXT     │
│    documentation TEXT   │
│    created_at TIMESTAMP │
└─────────────────────────┘
```

---

## Agreement Types

| Type | Description |
|------|-------------|
| gift | Outright gift/donation |
| bequest | Testamentary gift |
| purchase | Purchased materials |
| deposit | On deposit (not ownership transfer) |
| loan | Temporary loan |
| exchange | Exchange agreement |

---

## Restriction Types

| Type | Description |
|------|-------------|
| access | Access restrictions |
| reproduction | Copying/reproduction limits |
| publication | Publishing restrictions |
| exhibition | Display limitations |
| disposal | Cannot dispose/deaccession |
| digital | Digital access restrictions |

---

## Service Methods

### DonorService

```php
namespace ahgDonorPlugin\Service;

class DonorService
{
    // Donors
    public function createDonor(array $data): int
    public function updateDonor(int $id, array $data): bool
    public function getDonor(int $id): ?array
    public function searchDonors(string $query): Collection
    public function getDonorAgreements(int $donorId): Collection
    
    // Agreements
    public function createAgreement(array $data): int
    public function updateAgreement(int $id, array $data): bool
    public function getAgreement(int $id): ?array
    public function listAgreements(array $filters): Collection
    public function linkRecord(int $agreementId, int $recordId): bool
    public function unlinkRecord(int $agreementId, int $recordId): bool
    
    // Restrictions
    public function addRestriction(int $agreementId, array $data): int
    public function updateRestriction(int $id, array $data): bool
    public function getActiveRestrictions(int $recordId): Collection
    public function checkRestriction(int $recordId, string $type): bool
    
    // Reminders
    public function createReminder(int $agreementId, array $data): int
    public function getUpcomingReminders(int $days = 30): Collection
    public function markReminderComplete(int $id): bool
    public function sendReminderNotifications(): int
    
    // Provenance
    public function recordProvenance(array $data): int
    public function getProvenanceChain(int $recordId): Collection
}
```

---

## Workflow

```
┌─────────────────────────────────────────────────────────────────┐
│                    DONOR AGREEMENT WORKFLOW                     │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│   ┌─────────────┐                                              │
│   │ Create/Find │                                              │
│   │   Donor     │                                              │
│   └─────────────┘                                              │
│         │                                                       │
│         ▼                                                       │
│   ┌─────────────┐                                              │
│   │   Draft     │  status = 'draft'                            │
│   │  Agreement  │                                              │
│   └─────────────┘                                              │
│         │                                                       │
│         ▼                                                       │
│   ┌─────────────┐     ┌─────────────┐                          │
│   │    Add      │────▶│    Add      │                          │
│   │Restrictions │     │  Documents  │                          │
│   └─────────────┘     └─────────────┘                          │
│         │                                                       │
│         ▼                                                       │
│   ┌─────────────┐                                              │
│   │   Review    │  status = 'pending'                          │
│   │   & Sign    │                                              │
│   └─────────────┘                                              │
│         │                                                       │
│         ▼                                                       │
│   ┌─────────────┐                                              │
│   │   Active    │  status = 'active'                           │
│   │  Agreement  │                                              │
│   └─────────────┘                                              │
│         │                                                       │
│         ▼                                                       │
│   ┌─────────────┐     ┌─────────────┐                          │
│   │    Link     │────▶│    Set      │                          │
│   │   Records   │     │  Reminders  │                          │
│   └─────────────┘     └─────────────┘                          │
│                                                                 │
└─────────────────────────────────────────────────────────────────┘
```

---

*Part of the AtoM AHG Framework*
