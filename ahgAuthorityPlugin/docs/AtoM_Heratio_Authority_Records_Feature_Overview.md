# AtoM Heratio — Authority Records Enhancement

**Version:** 1.0.0
**Plugin:** ahgAuthorityPlugin
**Author:** The Archive and Heritage Group (Pty) Ltd
**Compatibility:** AtoM Heratio Framework v2.8+

---

## Overview

The **Authority Records Enhancement** plugin delivers comprehensive authority record management for GLAM and DAM institutions. It extends AtoM's existing authority record functionality with external authority file linking, completeness scoring, NER-to-authority pipelines, merge/dedup workflows, structured occupations, ISDF function linking, relationship visualization, and enriched EAC-CPF export.

The plugin addresses 10 enhancement areas that collectively transform authority records from passive metadata into actively managed, quality-assured, and internationally linked entities.

---

## Key Features

### 1. External Authority Linking (Wikidata, VIAF, ULAN, LCNAF, ISNI)

- Link authority records to international authority files
- Server-side search proxy for each authority source (avoids CORS issues)
- Automatic URI construction from identifier values
- Verification workflow with timestamps and user attribution
- Support for manual entry, reconciliation, and import sources
- Unique constraint: one identifier per type per actor

### 2. Completeness & Quality Dashboard

- Automated completeness scoring (0-100) based on 17 ISAAR(CPF) fields
- Four quality levels: Stub, Minimal, Partial, Full
- Bonus scoring for external identifiers, relations, linked resources, and contacts
- KPI dashboard with breakdown by level and entity type
- Assignable workqueue for archivist-driven quality improvement
- Batch and on-demand recalculation

### 3. NER-to-Authority Pipeline

- Bridges Named Entity Recognition output to authority record creation
- Confidence-based filtering (configurable threshold, default 0.85)
- Duplicate detection against existing actors before stub creation
- Review queue with promote/reject workflow
- Entity types: PERSON, ORG, GPE (geographic/political entities)
- Integrates with ahgAIPlugin's NER infrastructure

### 4. Relationship Graph Visualization

- Interactive agent-to-agent relationship graph using Cytoscape.js
- Configurable depth traversal (1-3 levels)
- Color-coded relationship types (associative, hierarchical, temporal, family)
- Embeddable panel for authority record view pages
- Graph data exposed via JSON API endpoint

### 5. ISDF Function Linking

- Structured actor-to-function links with relation types (responsible, participates, authorizes)
- Date range support for temporal function assignments
- Browse actors by function
- Sync validation CLI task for orphaned link cleanup
- Integrates with ahgFunctionManagePlugin when available

### 6. Structured Occupations

- Replace free-text occupations with structured, date-ranged entries
- Optional taxonomy term linking for controlled vocabulary
- Timeline display on authority record view
- Sort ordering for occupation history

### 7. Merge & Split Workflow

- Side-by-side field comparison for merge candidates
- Configurable per-field choice (primary vs. secondary actor values)
- Automatic transfer of relations, resources, contacts, and identifiers
- Slug redirect from merged actor to survivor
- Optional approval workflow (integrates with ahgWorkflowPlugin)
- Merge history with reversal tracking

### 8. Bulk Deduplication

- Name similarity detection using Jaro-Winkler algorithm
- Date overlap analysis for disambiguation
- Shared identifier boosting for high-confidence matches
- Configurable similarity threshold (default 0.80)
- Scan results dashboard with compare, merge, and dismiss actions
- CLI task for scheduled background scanning

### 9. EAC-CPF Export Enrichment

- Adds `<otherRecordId>` elements for each external identifier
- Adds `<source>` elements with URIs and labels
- Preserves standard EAC-CPF structure
- Integrates with ahgExportPlugin's export pipeline

### 10. Contact Information Surfacing

- Surfaces contact details on authority record pages
- Delegates to ahgContactPlugin for data retrieval
- Displays address, telephone, email, and website information
- Respects existing contact data model

---

## Architecture

### Plugin Design

- **Non-invasive:** Layers on top of existing ahgActorManagePlugin without modifying locked plugins
- **Modular services:** 10 dedicated service classes with clear single responsibilities
- **Optional dependencies:** Gracefully degrades when optional plugins are absent
- **Database isolation:** 7 new tables, zero modifications to core AtoM schema

### Database Tables

| Table | Purpose |
|-------|---------|
| `ahg_actor_identifier` | External authority file identifiers |
| `ahg_actor_completeness` | Quality scores and level assignments |
| `ahg_actor_occupation` | Structured occupation entries |
| `ahg_actor_merge` | Merge/split operation log |
| `ahg_ner_authority_stub` | NER-to-authority pipeline tracking |
| `ahg_actor_function_link` | Actor-to-function ISDF links |
| `ahg_authority_config` | Plugin configuration settings |

### CLI Tasks

| Command | Purpose | Schedule |
|---------|---------|----------|
| `php symfony authority:completeness-scan` | Batch completeness scoring | Daily/Weekly |
| `php symfony authority:ner-pipeline` | Process NER entities to stubs | After NER runs |
| `php symfony authority:dedup-scan` | Detect duplicate actors | Weekly |
| `php symfony authority:merge-report` | Merge operation summary | Monthly |
| `php symfony authority:function-sync` | Validate function links | Daily |

---

## Integration Points

| Plugin | Integration |
|--------|-------------|
| ahgActorManagePlugin | Actor data access (read-only) |
| ahgAIPlugin | NER entity data and matching algorithms |
| ahgDedupePlugin | Similarity algorithms (Jaro-Winkler, Levenshtein) |
| ahgRicExplorerPlugin | Graph visualization libraries |
| ahgContactPlugin | Contact information retrieval |
| ahgWorkflowPlugin | Merge approval workflows |
| ahgFunctionManagePlugin | Function entity CRUD |
| ahgExportPlugin | EAC-CPF export enrichment |
| ahgSettingsPlugin | Centralized configuration UI |

---

## Standards Compliance

- **ISAAR(CPF):** Completeness scoring based on all ISAAR field groups
- **ISDF:** Structured actor-to-function linking
- **EAC-CPF:** Export enrichment with external identifiers
- **RiC (Records in Contexts):** Graph-based relationship visualization
- **Wikidata/VIAF/ULAN/LCNAF/ISNI:** International authority file linking

---

## Technical Requirements

- AtoM Heratio Framework v2.8+
- PHP 8.1+
- MySQL 8.0+
- Required plugins: ahgCorePlugin, ahgActorManagePlugin
- Optional plugins: ahgAIPlugin, ahgDedupePlugin, ahgRicExplorerPlugin, ahgContactPlugin, ahgWorkflowPlugin, ahgFunctionManagePlugin, ahgExportPlugin

---

## Administration

Configuration is available at **Admin > AHG Settings > Authority Records** with sections for:

- External authority sources (enable/disable per source)
- Completeness and quality settings
- NER pipeline configuration (auto-stub, threshold)
- Merge and deduplication thresholds
- ISDF function linking toggle

All cron jobs are visible in **Admin > AHG Settings > Cron Jobs** under the AHG Extensions category.

---

*Copyright 2026 The Archive and Heritage Group (Pty) Ltd. All rights reserved.*
