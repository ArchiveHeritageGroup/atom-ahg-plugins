# AtoM Heratio — Authority Records User Manual

**Version:** 1.0.0
**Plugin:** ahgAuthorityPlugin
**Author:** The Archive and Heritage Group (Pty) Ltd

---

## Table of Contents

1. [Getting Started](#1-getting-started)
2. [Completeness Dashboard](#2-completeness-dashboard)
3. [Workqueue](#3-workqueue)
4. [External Authority Linking](#4-external-authority-linking)
5. [NER-to-Authority Pipeline](#5-ner-to-authority-pipeline)
6. [Relationship Graph](#6-relationship-graph)
7. [ISDF Function Linking](#7-isdf-function-linking)
8. [Structured Occupations](#8-structured-occupations)
9. [Merge & Split](#9-merge--split)
10. [Bulk Deduplication](#10-bulk-deduplication)
11. [Contact Information](#11-contact-information)
12. [EAC-CPF Export](#12-eac-cpf-export)
13. [Configuration](#13-configuration)
14. [CLI Tasks](#14-cli-tasks)

---

## 1. Getting Started

### Prerequisites

- Administrator access to AtoM Heratio
- ahgAuthorityPlugin enabled via Admin > Plugins

### Accessing Authority Records Enhancement

Navigate to **Admin > Authority Dashboard** from the main menu. This opens the completeness dashboard, which serves as the central hub for authority record management.

The following pages are available:

| Page | URL Path | Purpose |
|------|----------|---------|
| Dashboard | `/admin/authority/dashboard` | Completeness KPIs and statistics |
| Workqueue | `/admin/authority/workqueue` | Assigned records for improvement |
| Dedup | `/admin/authority/dedup` | Duplicate detection and resolution |
| NER Pipeline | `/admin/authority/ner-pipeline` | NER entity review and stub creation |

---

## 2. Completeness Dashboard

The dashboard provides a bird's-eye view of authority record quality across your repository.

### Key Performance Indicators (KPIs)

- **Total Actors:** Count of all authority records
- **Scored:** Number of records with completeness scores
- **Average Score:** Mean completeness percentage
- **Full Records:** Count of records at "Full" quality level

### Completeness Levels

| Level | Score Range | Description |
|-------|------------|-------------|
| Stub | 0-24 | Minimal data — typically auto-created from NER |
| Minimal | 25-49 | Basic identification only (name, type) |
| Partial | 50-74 | Core fields populated but gaps remain |
| Full | 75-100 | Comprehensive record meeting ISAAR standards |

### Scoring Methodology

The completeness score is calculated from 17 ISAAR(CPF) field groups:

- **Identity Area:** Authorized form of name, parallel names, standardized names
- **Description Area:** Dates of existence, history/biography, places, legal status, functions, mandates, internal structures, general context
- **Relationships Area:** Relations to other actors
- **Control Area:** Description identifier, institution identifier, rules/conventions, status, dates of revision, sources, maintenance notes

Each populated field contributes equally. Bonus points are awarded for:
- External identifiers (+5 per identifier type)
- Relations to other actors (+5)
- Linked resources (+5)
- Contact information (+5)

### Actions

- **Recalculate All:** Triggers a full rescan of all actor completeness scores
- **View Workqueue:** Navigate to the assignable work queue

---

## 3. Workqueue

The workqueue displays authority records assigned to archivists for quality improvement.

### Filtering

- **Level:** Filter by completeness level (Stub, Minimal, Partial, Full)
- **Entity Type:** Filter by actor type (Person, Corporate Body, Family)
- **Assignee:** Filter by assigned archivist

### Assigning Records

1. Select records using checkboxes
2. Choose an assignee from the dropdown
3. Click "Assign" to bulk-assign records

### Working on Records

Click any actor name in the workqueue to navigate to their authority record edit page, where you can add missing information to improve the completeness score.

---

## 4. External Authority Linking

Link your authority records to international authority files for interoperability and enrichment.

### Supported Sources

| Source | Description | Identifier Format |
|--------|-------------|-------------------|
| Wikidata | Wikimedia structured data | Q-number (e.g., Q42) |
| VIAF | Virtual International Authority File | Cluster number |
| ULAN | Getty Union List of Artist Names | ULAN ID |
| LCNAF | Library of Congress Name Authority | LCCN |
| ISNI | International Standard Name Identifier | 16-digit ISNI |

### Adding an External Identifier

1. Navigate to an authority record's **Identifiers** tab
2. Click **"Add Identifier"**
3. Select the identifier type from the dropdown
4. Enter the identifier value
5. Optionally use **"Search"** to look up the authority source
6. Click **"Save"**

### Using the Lookup Tool

1. Click the search icon next to any authority source
2. Enter a search term (person name, organization name)
3. Results appear from the selected authority source
4. Click **"Use"** to populate the identifier field
5. The URI is automatically constructed

### Verification

- Click **"Verify"** on any identifier to mark it as verified
- Verification records the verifying user and timestamp
- Auto-verification can be enabled for Wikidata in settings

---

## 5. NER-to-Authority Pipeline

The NER pipeline bridges Named Entity Recognition output with authority record creation.

### How It Works

1. NER extraction identifies persons, organizations, and places in archival descriptions
2. The pipeline checks each entity against existing authority records
3. Entities above the confidence threshold are eligible for stub creation
4. Stubs appear in the review queue for archivist approval

### Review Queue

Navigate to **Admin > NER Pipeline** to see pending NER entities.

Each entity shows:
- Entity value (the extracted name)
- Entity type (PERSON, ORG, GPE)
- Confidence score
- Source document (the information object where it was found)
- Matching actors (potential duplicates in the system)

### Actions

- **Create Stub:** Creates a new stub authority record from the NER entity
- **Promote:** Elevates a stub to a full authority record (changes status)
- **Reject:** Marks the entity as not suitable for authority record creation

### Automatic Processing

When "Auto-Create Stubs" is enabled in settings, entities above the threshold are automatically converted to stub authority records without manual review.

---

## 6. Relationship Graph

The relationship graph provides an interactive visualization of agent-to-agent relationships.

### Viewing the Graph

The graph panel appears on authority record view pages when the plugin is active. It displays:

- The current actor as the central node
- Related actors connected by labeled edges
- Relationship types color-coded by category

### Relationship Colors

| Color | Category |
|-------|----------|
| Blue | Associative relationships |
| Green | Hierarchical relationships |
| Orange | Temporal relationships |
| Purple | Family relationships |

### Interaction

- **Click** a node to navigate to that actor's page
- **Scroll** to zoom in/out
- **Drag** nodes to rearrange the layout
- The graph automatically expands to show relationships up to the configured depth

---

## 7. ISDF Function Linking

Link authority records to ISDF function entities for structured function documentation.

### Adding Function Links

1. Navigate to an authority record's **Functions** tab
2. Click **"Add Function Link"**
3. Search for and select the function entity
4. Choose the relation type:
   - **Responsible:** The actor is responsible for the function
   - **Participates:** The actor participates in the function
   - **Authorizes:** The actor authorizes the function
5. Optionally set date range and notes
6. Click **"Save"**

### Browse by Function

Navigate to **Admin > Authority > Browse by Function** to see all functions and the actors linked to each one.

---

## 8. Structured Occupations

Replace free-text occupation fields with structured, date-ranged occupation entries.

### Adding Occupations

1. Navigate to an authority record's **Occupations** tab
2. Click **"Add Occupation"**
3. Either:
   - Select a term from the occupation taxonomy, or
   - Enter free text if no matching term exists
4. Set date range (from/to) if applicable
5. Add any notes
6. Click **"Save"**

Occupations are displayed in chronological order on the authority record.

---

## 9. Merge & Split

### Merging Records

When duplicate authority records are identified:

1. Navigate to the **Merge** page for the primary (surviving) actor
2. Search for and select the secondary (duplicate) actor
3. Review the side-by-side field comparison
4. For each field, choose whether to keep the primary or secondary value
5. Review the transfer summary (relations, resources, contacts, identifiers)
6. Click **"Execute Merge"**

After merging:
- The secondary actor's slug redirects to the primary actor
- All relations, resources, contacts, and identifiers are transferred
- A merge record is created in the history

### Splitting Records

When an authority record conflates two distinct entities:

1. Navigate to the **Split** page for the actor
2. Enter the name for the new actor to be split off
3. Select which relations and resources to move to the new actor
4. Click **"Execute Split"**

### Approval Workflow

If "Require Approval for Merge" is enabled in settings (and ahgWorkflowPlugin is active), merge operations require supervisor approval before execution.

---

## 10. Bulk Deduplication

### Running a Scan

1. Navigate to **Admin > Authority > Dedup**
2. Review the current statistics (pending pairs, resolved merges, dismissed)
3. Click **"Start Scan"** or use the CLI: `php symfony authority:dedup-scan`

### Review Results

After scanning, potential duplicates appear as pairs with:
- **Score:** Similarity percentage (0-100)
- **Match Type:** How the match was detected (name, identifier, combined)
- **Actions:** Compare, Merge, or Dismiss

### Comparing Pairs

Click **"Compare"** to see a side-by-side comparison of two potential duplicates with:
- Field-by-field matching indicators (green = match, red = mismatch)
- External identifier comparison
- Relationship and resource counts
- Recommended action

---

## 11. Contact Information

The contact panel displays contact details associated with an authority record.

When ahgContactPlugin is installed, contact information (addresses, telephone numbers, email addresses, and websites) appears on the authority record page in a dedicated panel.

---

## 12. EAC-CPF Export

When exporting authority records in EAC-CPF format, the plugin automatically enriches the XML with:

- **`<otherRecordId>`** elements for each external identifier (Wikidata, VIAF, etc.)
- **`<source>`** elements with URIs and display labels

This enrichment is automatic and requires no user action.

---

## 13. Configuration

Navigate to **Admin > AHG Settings > Authority Records** to configure:

### External Authority Sources
- Enable/disable each authority source (Wikidata, VIAF, ULAN, LCNAF, ISNI)
- Auto-verify Wikidata identifiers

### Completeness & Quality
- Auto-recalculate completeness scores
- Hide stub records from public browse

### NER Pipeline
- Enable/disable automatic stub creation
- Set confidence threshold (0.0-1.0)

### Merge & Deduplication
- Require approval for merge operations
- Set dedup similarity threshold (0.0-1.0)

### ISDF Functions
- Enable/disable function linking

---

## 14. CLI Tasks

All CLI tasks are run from the AtoM root directory.

### Completeness Scan

```bash
php symfony authority:completeness-scan
php symfony authority:completeness-scan --limit=100
```

Scans authority records and calculates completeness scores. Use `--limit` to process a subset for testing.

### NER Pipeline

```bash
php symfony authority:ner-pipeline
php symfony authority:ner-pipeline --dry-run
php symfony authority:ner-pipeline --threshold=0.90
```

Processes unlinked NER entities. Use `--dry-run` to preview without creating stubs.

### Dedup Scan

```bash
php symfony authority:dedup-scan
php symfony authority:dedup-scan --limit=500
```

Scans for potential duplicate authority records using name similarity.

### Merge Report

```bash
php symfony authority:merge-report
```

Generates a summary of all merge/split operations.

### Function Sync

```bash
php symfony authority:function-sync
php symfony authority:function-sync --clean
```

Validates actor-function links. Use `--clean` to remove orphaned links.

---

## Cron Job Examples

```bash
# Daily completeness scan at 3 AM
0 3 * * * cd /usr/share/nginx/archive && php symfony authority:completeness-scan >> /var/log/atom/authority-completeness.log 2>&1

# Daily NER pipeline at 4 AM
0 4 * * * cd /usr/share/nginx/archive && php symfony authority:ner-pipeline >> /var/log/atom/authority-ner.log 2>&1

# Weekly dedup scan on Sunday at 2 AM
0 2 * * 0 cd /usr/share/nginx/archive && php symfony authority:dedup-scan >> /var/log/atom/authority-dedup.log 2>&1

# Monthly merge report on the 1st at 6 AM
0 6 1 * * cd /usr/share/nginx/archive && php symfony authority:merge-report >> /var/log/atom/authority-merge.log 2>&1

# Daily function sync at 5 AM
0 5 * * * cd /usr/share/nginx/archive && php symfony authority:function-sync >> /var/log/atom/authority-function-sync.log 2>&1
```

---

*Copyright 2026 The Archive and Heritage Group (Pty) Ltd. All rights reserved.*
