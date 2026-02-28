# AtoM Heratio Research Portal — Feature Overview

**Product:** AtoM Heratio Research Portal (ahgResearchPlugin)
**Version:** 3.1.0
**Vendor:** The Archive and Heritage Group (Pty) Ltd
**Contact:** johan@theahg.co.za
**Date:** February 2026

---

## What Is It?

The AtoM Heratio Research Portal is a comprehensive research support module built into the AtoM Heratio archival management platform. It transforms AtoM from a catalogue-only system into a full-service research environment — covering everything from researcher registration and reading room bookings through to material retrieval, chain-of-custody tracking, reproduction requests, and collaborative research tools.

The Research Portal is designed for GLAM institutions (Galleries, Libraries, Archives, Museums) and Digital Asset Management organisations that need to manage researcher access, track physical material movements, and provide self-service research tools to their users.

---

## Key Feature Areas

### 1. Researcher Registration and Access Control

| Feature | Description |
|---------|-------------|
| Self-registration | Researchers register online with institutional affiliation, research purpose, and ID verification |
| Admin approval workflow | Staff review, approve, or reject applications with full audit trail |
| Researcher types | Configurable types (Academic, Government, Public, Institutional) with per-type booking limits |
| Profile management | Researchers maintain their profile, ORCID integration, and contact details |

### 2. Reading Room and Bookings

| Feature | Description |
|---------|-------------|
| Room management | Configure reading rooms with capacity, opening hours, and available equipment |
| Seat assignment | Assign specific seats within rooms; track occupancy in real time |
| Online booking | Researchers book reading room sessions online, subject to type-based limits |
| Equipment booking | Book specialised equipment (microfilm readers, scanners, laptops) alongside room bookings |
| Walk-in registration | Quick registration for unbooked visitors arriving at the reading room |

### 3. Material Retrieval Queue

| Feature | Description |
|---------|-------------|
| Queue-based workflow | Requests flow through named queues: New, Rush, Retrieval, Transit, Delivery, Curatorial, Return |
| Priority management | Rush and high-priority requests are visually flagged and can be filtered |
| Call slip printing | Generate printable call slips with item details, shelf location, and researcher information |
| Batch status updates | Select multiple requests and update their status in one action |
| Automatic location tracking | The system automatically tracks each item's current location as it moves through the workflow |
| Scheduled retrieval runs | Configure scheduled retrieval windows with cutoff times |

### 4. Chain-of-Custody Tracking

A full physical chain-of-custody system that records every handoff of archival materials:

| Feature | Description |
|---------|-------------|
| Custody checkout | Record who received materials, when, in what condition, with optional barcode scanning |
| Custody check-in | Record material returns with condition-before and condition-after assessments |
| Return verification | A second staff member verifies the returned material's condition before re-shelving |
| Staff-to-staff transfer | Record handoffs between staff members (e.g., shift changes) |
| Batch checkout | Check out multiple items to researchers in a single operation |
| Batch return | Process multiple returns with per-item condition assessment |
| Full custody chain view | A unified timeline combining custody handoffs, Spectrum movement records, and provenance events |
| Spectrum 5.1 integration | Every custody event auto-generates a Spectrum-compliant movement record |

### 5. Request Lifecycle and SLA

End-to-end lifecycle management for material and reproduction requests:

| Feature | Description |
|---------|-------------|
| Requests dashboard | Combined view of all material and reproduction requests with SLA status badges |
| Triage workflow | Every request is triaged (approve, deny, needs information) before processing begins |
| SLA tracking | Configurable SLA policies with warning, due, and escalation thresholds |
| Staff assignment | Assign requests to specific staff members for fulfilment |
| Correspondence | Built-in threaded messaging between staff and researchers, with internal staff-only notes |
| Request closure | Close requests with a reason (fulfilled, cancelled, duplicate, unable to fulfil) |
| Audit trail | Every status change, triage decision, assignment, and correspondence entry is logged |
| "Request this item" button | Researchers can request materials directly from catalogue description pages |

### 6. Reproduction Requests

| Feature | Description |
|---------|-------------|
| Online submission | Researchers submit reproduction requests with purpose, format, quality, and delivery preferences |
| Item-level detail | Add specific items with page/section requirements and per-item notes |
| Pricing engine | Costs calculated based on item count, format, quality, and urgency |
| Invoice generation | Staff generate invoices attached to reproduction requests |
| File delivery | Completed reproductions are uploaded with unique download tokens |
| Download audit | Every download is logged for compliance |

### 7. Collections and Discovery

| Feature | Description |
|---------|-------------|
| Personal collections | Researchers create and manage personal groupings of archival items |
| Saved searches | Save search queries and receive notifications when new results appear |
| Search result diffing | Compare search results over time using snapshots |
| Citation generation | Generate citations in 6 academic styles (APA, MLA, Chicago, Harvard, Turabian, IEEE) |

### 8. Annotations and Knowledge Management

| Feature | Description |
|---------|-------------|
| W3C Web Annotations | Standards-compliant annotations on archival items with IIIF import/export |
| Annotation Studio | Rich text editor for creating and managing annotations |
| Research journal | Personal research journal with dated entries |
| Hypotheses | Formal hypothesis tracking with evidence linking |
| Assertions | Subject-Predicate-Object triples for building knowledge graphs |
| Source assessment | Trust scoring and source credibility evaluation |

### 9. Collaboration

| Feature | Description |
|---------|-------------|
| Research projects | Create projects with collaborators, shared collections, and milestones |
| Workspaces | Shared workspaces for team research |
| Peer review | Invite peers to review and comment on research outputs |
| Institutional sharing | Share research across partner institutions |

### 10. Publishing and Reproducibility

| Feature | Description |
|---------|-------------|
| Bibliographies | Manage bibliographies with RIS, BibTeX, and Zotero export |
| Reports | Generate research reports in PDF and DOCX format with rich text editing |
| Immutable snapshots | Create hash-verified snapshots of research state for reproducibility |
| RO-Crate packaging | Package research outputs as Research Object Crates |
| DOI minting | Mint DOIs for published research outputs via DataCite |
| ORCID integration | Link researcher profiles to ORCID identifiers |

### 11. AI-Powered Tools

| Feature | Description |
|---------|-------------|
| Entity extraction | AI-powered Named Entity Recognition from archival documents |
| Summarisation | Automatic text summarisation of archival descriptions |
| Validation queue | Staff review and validate AI-generated extractions |
| Entity resolution | Disambiguate and link extracted entities to authority records |

### 12. Visualisation

| Feature | Description |
|---------|-------------|
| Timeline builder | Interactive timeline of research events and archival dates |
| Geographic map | Plot archival items and events on interactive maps |
| Network graph | Visualise relationships between entities and assertions |
| Knowledge graph | Explore assertions and hypotheses as an interactive graph |

---

## Accessibility (WCAG 2.1 AA)

The Research Portal is designed for accessibility compliance across all screens:

- **Skip navigation** on every page for keyboard and screen reader users
- **ARIA live regions** announce dynamic content changes to screen readers
- **Data tables** with proper headers, captions, and scope attributes
- **Status badges** use icons and text alongside colour (never colour alone)
- **Keyboard navigation** for all interactive elements with visible focus indicators
- **Form validation** with programmatic error association and alert roles
- **Decorative icons** hidden from screen readers via `aria-hidden`

---

## Compliance and Standards

| Standard | Coverage |
|----------|----------|
| WCAG 2.1 Level AA | All screens |
| Spectrum 5.1 | Object Location and Movement Control (via custody/movement integration) |
| W3C Web Annotation | Annotation data model |
| IIIF | Manifest and annotation interoperability |
| RO-Crate | Research object packaging |
| DataCite | DOI minting |
| ODRL | Digital rights policy language |

---

## Technical Requirements

| Requirement | Specification |
|-------------|---------------|
| Platform | AtoM 2.10 with AtoM Heratio Framework v2.8.2+ |
| PHP | 8.3+ |
| Database | MySQL 8.0+ |
| Browser | Chrome 90+, Firefox 90+, Safari 14+, Edge 90+ |
| Dependencies | Bootstrap 5 (included), Font Awesome 6 (included) |

---

## Database Footprint

The Research Portal uses 66 dedicated database tables (no modifications to core AtoM tables). Key tables include:

- Researcher management (registration, types, audit)
- Reading room configuration (rooms, seats, bookings, equipment)
- Material requests (retrieval queue, scheduling, status history)
- Custody tracking (handoff records, Spectrum movement integration)
- Reproduction workflow (requests, items, files, invoices)
- Correspondence (threaded messaging with internal notes)
- Collections, annotations, projects, workspaces
- AI extraction, validation, entity resolution
- Reports, snapshots, bibliographies
- ODRL policies, API keys, notification preferences

---

## Getting Started

1. **Install** the ahgResearchPlugin via the AtoM Heratio Extension Manager
2. **Run** the database migration (`database/install.sql` + migration files)
3. **Configure** reading rooms, researcher types, and SLA policies via Admin > AHG Settings
4. **Enable** the "Research" menu in Admin > Menus
5. **Test** by registering a researcher account and creating a booking

For detailed setup instructions, see the full Training Manual (included).

---

*The Archive and Heritage Group (Pty) Ltd — Preserving Heritage Through Technology*
*https://theahg.co.za*
