# Technical Documentation Index

Detailed technical reference for all AHG plugins including architecture diagrams, ERD schemas, and API documentation.

**Version:** 2.1.17
**Last Updated:** January 2026

---

## Architecture & System Design

| Document | Description |
|----------|-------------|
| [PLUGIN_ARCHITECTURE.md](PLUGIN_ARCHITECTURE.md) | Plugin types, module ownership, extension surface API |
| [DATABASE_ERD.md](DATABASE_ERD.md) | Entity-relationship diagrams for all plugin tables |
| [SYSTEM_FLOWS.md](SYSTEM_FLOWS.md) | Request processing, installation, audit, IIIF, NER flows |
| [TECHNICAL_DOCUMENTATION.md](TECHNICAL_DOCUMENTATION.md) | Comprehensive technical reference |

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

### Media & Viewing

| Plugin | Category | Description |
|--------|----------|-------------|
| [ahgIiifPlugin](ahgIiifPlugin.md) | Media | IIIF manifests, viewers, auth, collections |

### Research & AI

| Plugin | Category | Description |
|--------|----------|-------------|
| [ahgResearchPlugin](ahgResearchPlugin.md) | Public Services | Researcher portal, bookings |
| [ahgNerPlugin](ahgNerPlugin.md) | AI/NLP | Named Entity Recognition |

### Preservation

| Plugin | Category | Description |
|--------|----------|-------------|
| [ahgPreservationPlugin](ahgPreservationPlugin.md) | Preservation | OAIS/PREMIS, format migration, fixity |

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
