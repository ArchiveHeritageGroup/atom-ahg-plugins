# Technical Documentation Index

Detailed technical reference for all AHG plugins including architecture diagrams, ERD schemas, and API documentation.

---

## Plugin Documentation

### Core & Integration

| Plugin | Category | Description |
|--------|----------|-------------|
| [ahgAPIPlugin](ahgAPIPlugin.md) | Integration | REST API v2 - Full CRUD, batch, webhooks |
| [ahgAuditTrailPlugin](ahgAuditTrailPlugin.md) | Compliance | Comprehensive audit logging |
| [ahgBackupPlugin](ahgBackupPlugin.md) | Administration | Backup and restore |

### Security & Compliance

| Plugin | Category | Description |
|--------|----------|-------------|
| [ahgSecurityClearancePlugin](ahgSecurityClearancePlugin.md) | Security | Multi-level access control |
| [ahgPrivacyPlugin](ahgPrivacyPlugin.md) | Compliance | POPIA/GDPR/PAIA compliance |
| [ahgEmbargoPlugin](ahgEmbargoPlugin.md) | Access Control | Time-based restrictions |

### Collections Management

| Plugin | Category | Description |
|--------|----------|-------------|
| [ahgConditionPlugin](ahgConditionPlugin.md) | Conservation | Condition assessment |
| [ahgSpectrumPlugin](ahgSpectrumPlugin.md) | Museum | Spectrum 5.0 procedures |
| [ahgGrapPlugin](ahgGrapPlugin.md) | Financial | GRAP 103 heritage accounting |
| [ahgDonorPlugin](ahgDonorPlugin.md) | Acquisitions | Donor and agreement management |
| [ahgVendorPlugin](ahgVendorPlugin.md) | Administration | Vendor/supplier management |

### Research & AI

| Plugin | Category | Description |
|--------|----------|-------------|
| [ahgResearchPlugin](ahgResearchPlugin.md) | Public Services | Researcher portal, bookings |
| [ahgNerPlugin](ahgNerPlugin.md) | AI/NLP | Named Entity Recognition |

---

## ERD Legend

```
┌─────────────────────┐
│     table_name      │
├─────────────────────┤
│ PK id INT          │  ← Primary Key
│ FK foreign_id INT  │  ← Foreign Key
│    column VARCHAR   │  ← Regular column
└─────────────────────┘
        │
        │ 1:N           ← One-to-Many
        ▼
```

---

*Part of the AtoM AHG Framework*
