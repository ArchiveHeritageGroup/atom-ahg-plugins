# Named Entity Recognition (NER) - User Guide

## Overview

The NER system automatically extracts names of **people**, **organizations**, **places**, and **dates** from your archival records. Staff can review extracted entities and link them to existing authority records.

---

## How It Works
```
┌─────────────────────────────────────────────────────────────────────┐
│                     NER EXTRACTION WORKFLOW                          │
├─────────────────────────────────────────────────────────────────────┤
│                                                                      │
│   STEP 1: EXTRACT                                                    │
│   ┌──────────────────┐                                               │
│   │ Archival Record  │                                               │
│   │ with text/PDF    │                                               │
│   └────────┬─────────┘                                               │
│            │                                                         │
│            ▼                                                         │
│   ┌──────────────────┐                                               │
│   │ Click "Extract   │                                               │
│   │ Entities (NER)"  │                                               │
│   └────────┬─────────┘                                               │
│            │                                                         │
│            ▼                                                         │
│   ┌──────────────────┐                                               │
│   │ AI identifies:   │                                               │
│   │ • People names   │                                               │
│   │ • Organizations  │                                               │
│   │ • Places         │                                               │
│   │ • Dates          │                                               │
│   └────────┬─────────┘                                               │
│            │                                                         │
│            ▼                                                         │
│   STEP 2: REVIEW                                                     │
│   ┌──────────────────────────────────────────────────────────────┐  │
│   │                    Review Dashboard                           │  │
│   │  ┌─────────────────────────────────────────────────────────┐ │  │
│   │  │ PERSON: Nelson Mandela        [✓ Approve] [✗ Reject]   │ │  │
│   │  │         → Match: Mandela, Nelson (Actor)  [🔗 Link]    │ │  │
│   │  ├─────────────────────────────────────────────────────────┤ │  │
│   │  │ ORG: African National Congress [✓ Approve] [✗ Reject]  │ │  │
│   │  │      → No matches found        [+ Create]              │ │  │
│   │  ├─────────────────────────────────────────────────────────┤ │  │
│   │  │ GPE: South Africa              [✓ Approve] [✗ Reject]  │ │  │
│   │  │      → Match: South Africa (Place)        [🔗 Link]    │ │  │
│   │  ├─────────────────────────────────────────────────────────┤ │  │
│   │  │ DATE: 18 July 1918             [✓ Approve] [✗ Reject]  │ │  │
│   │  └─────────────────────────────────────────────────────────┘ │  │
│   └──────────────────────────────────────────────────────────────┘  │
│            │                                                         │
│            ▼                                                         │
│   STEP 3: RESULT                                                     │
│   ┌──────────────────┐                                               │
│   │ Linked entities  │                                               │
│   │ appear as Name   │                                               │
│   │ Access Points    │                                               │
│   └──────────────────┘                                               │
│                                                                      │
└─────────────────────────────────────────────────────────────────────┘
```

---

## Step-by-Step Guide

### Step 1: Extract Entities from a Record

1. Navigate to any **archival description** that contains text
2. Look for the **"Extract Entities (NER)"** button in the sidebar
3. Click the button
4. Wait for the AI to analyze the text (usually a few seconds)
5. You'll see a confirmation message when complete
```
┌─────────────────────────────────────────┐
│         ARCHIVAL DESCRIPTION            │
├─────────────────────────────────────────┤
│                                         │
│  Title: Mandela Papers                  │
│  Reference: ZA-NARSSA-001               │
│                                         │
│  Scope and Content:                     │
│  Documents relating to Nelson Mandela   │
│  and the African National Congress...   │
│                                         │
├─────────────────────────────────────────┤
│  ACTIONS SIDEBAR:                       │
│  ┌───────────────────────────────────┐  │
│  │ [📄 Edit]                         │  │
│  │ [🗑️ Delete]                       │  │
│  │ [➕ Add child]                    │  │
│  │ [🧠 Extract Entities (NER)]  ◄───┼──┤ Click here
│  │ [📋 Generate Finding Aid]        │  │
│  └───────────────────────────────────┘  │
└─────────────────────────────────────────┘
```

### Step 2: Review Extracted Entities

1. Go to **NER Review Dashboard**: `/ner/review`
2. You'll see a list of records with pending entities
3. Click **"Review"** next to any record
4. For each entity, choose an action:

| Button | Action | When to Use |
|--------|--------|-------------|
| **✓ Approve** (green) | Accept the entity | Entity is correctly identified |
| **✗ Reject** (red) | Discard the entity | Entity is wrong or irrelevant |
| **🔗 Link** (blue) | Connect to existing Authority | Exact or similar match found |
| **+ Create** | Create new Authority Record | No match exists |

### Step 3: Understanding Entity Types

| Type | Icon | Examples | Links To |
|------|------|----------|----------|
| **PERSON** | 👤 | Nelson Mandela, Jan van Riebeeck | Actor (Person) |
| **ORG** | 🏢 | ANC, VOC, Parliament | Actor (Corporate Body) |
| **GPE** | 📍 | South Africa, Cape Town, Batavia | Place / Subject |
| **DATE** | 📅 | 1994, 18 July 1918 | Reference only |

---

## Review Dashboard Overview
```
┌─────────────────────────────────────────────────────────────────────┐
│                     NER REVIEW DASHBOARD                             │
│                     /ner/review                                      │
├─────────────────────────────────────────────────────────────────────┤
│                                                                      │
│  ┌────────────────────┐    ┌────────────────────┐                   │
│  │   PENDING: 45      │    │   OBJECTS: 12      │                   │
│  │   entities         │    │   to review        │                   │
│  └────────────────────┘    └────────────────────┘                   │
│                                                                      │
│  ┌─────────────────────────────────────────────────────────────────┐│
│  │ OBJECTS WITH PENDING ENTITIES                                   ││
│  ├─────────────────────────────────────────────────────────────────┤│
│  │ Record Title                              Pending    Actions    ││
│  │ ─────────────────────────────────────────────────────────────── ││
│  │ Mandela Papers Collection                    8      [Review]   ││
│  │ VOC Archives Series A                       12      [Review]   ││
│  │ Cape Town Municipal Records                  5      [Review]   ││
│  │ Apartheid Era Documents                     20      [Review]   ││
│  └─────────────────────────────────────────────────────────────────┘│
│                                                                      │
└─────────────────────────────────────────────────────────────────────┘
```

---

## Entity Matching

The system automatically searches for matches in your existing Authority Records:

### Exact Match (Green Badge)
- Entity name exactly matches an existing Authority Record
- Click **"Link"** to connect them immediately

### Similar Matches (Suggestions)
- Partial or fuzzy matches found
- Review suggestions and click the correct match
- Or click **"Create"** if none match

### No Match
- No existing Authority Record found
- Click **"Create"** to add a new Authority Record
- Or **"Approve"** to keep for reference without linking
```
┌─────────────────────────────────────────────────────────────────────┐
│  MATCHING EXAMPLES                                                   │
├─────────────────────────────────────────────────────────────────────┤
│                                                                      │
│  Extracted: "Nelson Mandela"                                         │
│  ┌─────────────────────────────────────────────────────────────────┐│
│  │ ✅ EXACT MATCH                                                  ││
│  │    Mandela, Nelson Rolihlahla (1918-2013)                      ││
│  │    [🔗 Link]                                                    ││
│  └─────────────────────────────────────────────────────────────────┘│
│                                                                      │
│  Extracted: "ANC"                                                    │
│  ┌─────────────────────────────────────────────────────────────────┐│
│  │ 🔍 SIMILAR MATCHES                                              ││
│  │    • African National Congress              [Link]              ││
│  │    • ANC Youth League                       [Link]              ││
│  │    [+ Create New]                                               ││
│  └─────────────────────────────────────────────────────────────────┘│
│                                                                      │
│  Extracted: "Chief Buthelezi"                                        │
│  ┌─────────────────────────────────────────────────────────────────┐│
│  │ ⚠️ NO MATCH FOUND                                               ││
│  │    [+ Create New Authority Record]                              ││
│  │    [✓ Approve without linking]                                  ││
│  └─────────────────────────────────────────────────────────────────┘│
│                                                                      │
└─────────────────────────────────────────────────────────────────────┘
```

---

## After Linking

When you link an entity to an Authority Record:

1. The entity status changes to **"Linked"**
2. A **Name Access Point** is created on the archival record
3. The entity appears in the record's "Name access points" section
4. Researchers can now search and find records by that name
```
┌─────────────────────────────────────────┐
│  ARCHIVAL DESCRIPTION (after linking)   │
├─────────────────────────────────────────┤
│                                         │
│  Name Access Points:                    │
│  • Mandela, Nelson Rolihlahla           │
│  • African National Congress            │
│  • South Africa                         │
│                                         │
│  Subject Access Points:                 │
│  • Anti-apartheid movements             │
│  • Political prisoners                  │
│                                         │
└─────────────────────────────────────────┘
```

---

## Tips & Best Practices

### Do's ✓

- **Review regularly** - Check the dashboard daily for pending entities
- **Link when possible** - Linking improves searchability
- **Create new authorities** - If a person/org is important and will appear again
- **Approve dates** - Keep dates for reference even without linking

### Don'ts ✗

- **Don't link incorrect matches** - Better to create new than link wrong
- **Don't ignore the queue** - Pending entities don't help researchers
- **Don't rush** - Take time to verify names, especially historical figures

---

## Troubleshooting

| Issue | Solution |
|-------|----------|
| "Extract Entities" button missing | Check if you have edit permissions for the record |
| No entities extracted | Record may have no text content, or text is in image format |
| Wrong entities extracted | Reject incorrect ones; AI isn't perfect with historical names |
| Can't find match | Use "Create" to add new Authority Record |
| Link button doesn't work | Check if you have permission to edit Authority Records |

---

## Access & Permissions

| Action | Required Permission |
|--------|---------------------|
| Extract entities | Edit access to the record |
| Review dashboard | Any authenticated user |
| Approve/Reject | Edit access to the record |
| Link to Authority | Edit access to Authority Records |
| Create new Authority | Create access to Authority Records |

---

## Quick Reference

| Location | URL |
|----------|-----|
| Review Dashboard | `/ner/review` |
| Extract Button | On any archival description page |

| Keyboard Shortcuts | Action |
|--------------------|--------|
| `A` | Approve selected entity |
| `R` | Reject selected entity |
| `L` | Link (if match available) |

---

*Last Updated: January 2026*
*AHG NER Plugin v1.0.0*
