# Data Migration Guide

## Moving Data Into Your Archive System

---

## What is Data Migration?

Data migration means moving your records from one system to another. This guide helps you bring data from other software into AtoM.

---

## Supported Source Systems

We can import data from many different systems:

| System | What It's For |
|--------|---------------|
| **Vernon CMS** | Museum collections |
| **ArchivesSpace** | Archival management |
| **PastPerfect** | Museum/historical societies |
| **CollectiveAccess** | Multi-purpose collections |
| **DB/TextWorks** | Text databases |
| **Excel/CSV** | Any spreadsheet data |
| **EAD files** | Archival finding aids |

---

## The Migration Process

```
┌──────────────────┐
│  STEP 1: UPLOAD  │
│  Your data file  │
│  (CSV, XML, EAD) │
└────────┬─────────┘
         │
         ▼
┌──────────────────┐
│  STEP 2: DETECT  │
│  System auto-    │
│  detects source  │
│  format          │
└────────┬─────────┘
         │
         ▼
┌──────────────────┐
│  STEP 3: SELECT  │
│  Choose target   │
│  type (Archive,  │
│  Museum, etc.)   │
└────────┬─────────┘
         │
         ▼
┌──────────────────┐
│  STEP 4: MAP     │
│  Match your      │
│  fields to our   │
│  fields          │
└────────┬─────────┘
         │
         ▼
┌──────────────────┐
│  STEP 5: PREVIEW │
│  Check the       │
│  results look    │
│  correct         │
└────────┬─────────┘
         │
         ▼
┌──────────────────┐
│  STEP 6: IMPORT  │
│  Load into the   │
│  system OR       │
│  export to file  │
└──────────────────┘
```

---

## Step 1: Prepare Your Data

### Before You Start

1. **Export from your old system**
   - Choose CSV or XML format if possible
   - Include all fields you want to keep
   - Note the column names

2. **Clean your data**
   - Remove duplicate records
   - Fix obvious errors
   - Standardise date formats

3. **Make a backup**
   - Keep the original export file safe
   - Never work on your only copy

### File Formats We Accept

| Format | Extension | Notes |
|--------|-----------|-------|
| CSV | .csv | Most common, opens in Excel |
| XML | .xml | Structured data |
| EAD | .xml | Archival finding aids |
| Excel | .xlsx | We convert to CSV |

---

## Step 2: Upload Your File

1. Go to **Admin → Data Migration**
2. Click **Upload File**
3. Select your file
4. Wait for upload to complete

```
┌─────────────────────────────────────────────────────────────┐
│                    UPLOAD YOUR DATA                          │
├─────────────────────────────────────────────────────────────┤
│                                                              │
│   ┌─────────────────────────────────────────────────────┐   │
│   │                                                      │   │
│   │          Drag and drop your file here               │   │
│   │                                                      │   │
│   │                    or                                │   │
│   │                                                      │   │
│   │              [ Browse Files ]                        │   │
│   │                                                      │   │
│   └─────────────────────────────────────────────────────┘   │
│                                                              │
│   Supported: CSV, XML, EAD, XLSX                            │
│   Maximum size: 50MB                                         │
│                                                              │
└─────────────────────────────────────────────────────────────┘
```

---

## Step 3: System Detection

The system automatically identifies your source format:

```
┌─────────────────────────────────────────────────────────────┐
│                   SOURCE DETECTED                            │
├─────────────────────────────────────────────────────────────┤
│                                                              │
│   ✓ File analysed successfully                              │
│                                                              │
│   Source System:    Vernon CMS                               │
│   Format:           CSV                                      │
│   Records Found:    1,247                                    │
│   Columns:          24                                       │
│                                                              │
│   Sample Fields Detected:                                    │
│   • Object Number                                            │
│   • Object Name                                              │
│   • Primary Maker                                            │
│   • Date Made                                                │
│   • Dimensions                                               │
│                                                              │
│                        [ Continue ]                          │
│                                                              │
└─────────────────────────────────────────────────────────────┘
```

If detection is wrong, you can manually select the source type.

---

## Step 4: Choose Your Target

Select what type of records you're creating:

| Target | Use For |
|--------|---------|
| **Archives (ISAD-G)** | Archival descriptions - fonds, series, files |
| **Museum (Spectrum)** | Museum objects - artworks, specimens, artefacts |
| **Library (RDA)** | Books, journals, bibliographic records |
| **Gallery (CCO)** | Art collections, visual resources |
| **Digital Assets** | Photos, documents, media files |

```
┌─────────────────────────────────────────────────────────────┐
│                 SELECT TARGET TYPE                           │
├─────────────────────────────────────────────────────────────┤
│                                                              │
│   What type of records are you importing?                   │
│                                                              │
│   ○ Archives (ISAD-G)                                       │
│       Fonds, series, files, items                           │
│                                                              │
│   ● Museum (Spectrum)         ← Selected                    │
│       Objects, specimens, artworks                          │
│                                                              │
│   ○ Library (RDA)                                           │
│       Books, journals, articles                             │
│                                                              │
│   ○ Gallery (CCO)                                           │
│       Art cataloguing                                        │
│                                                              │
│   ○ Digital Assets                                          │
│       Photos, documents, media                              │
│                                                              │
│                        [ Continue ]                          │
│                                                              │
└─────────────────────────────────────────────────────────────┘
```

---

## Step 5: Map Your Fields

This is the most important step. You're telling the system which of your columns match which of our fields.

### The Mapping Screen

```
┌─────────────────────────────────────────────────────────────┐
│                    FIELD MAPPING                             │
├─────────────────────────────────────────────────────────────┤
│                                                              │
│   YOUR FIELD              →    OUR FIELD                    │
│   ─────────────────────────────────────────────────────     │
│   Object Number           →    Identifier         [▼]       │
│   Object Name             →    Title              [▼]       │
│   Primary Maker           →    Creator            [▼]       │
│   Date Made               →    Date               [▼]       │
│   Dimensions              →    Physical Desc.     [▼]       │
│   Description             →    Scope & Content    [▼]       │
│   Current Location        →    Location           [▼]       │
│   Condition               →    Condition Note     [▼]       │
│   Notes                   →    (Skip)             [▼]       │
│                                                              │
│   [ Load Saved Mapping ]  [ Save This Mapping ]             │
│                                                              │
│                        [ Preview ]                           │
│                                                              │
└─────────────────────────────────────────────────────────────┘
```

### Tips for Mapping

**Direct matches** - When your field name matches ours, it maps automatically.

**Combine fields** - You can join multiple fields together:
```
First Name + Last Name  →  Creator
```

**Add constants** - Prepend text to every value:
```
"ACC-" + Accession Number  →  Identifier
Result: ACC-00123
```

**Skip fields** - Choose "(Skip)" for fields you don't need.

### Save Your Mapping

If you're importing multiple batches, save your mapping to reuse later:

1. Click **Save This Mapping**
2. Give it a name (e.g., "Vernon to Museum")
3. Next time, click **Load Saved Mapping**

---

## Step 6: Preview Results

Before importing, check that everything looks correct:

```
┌─────────────────────────────────────────────────────────────┐
│                    PREVIEW RESULTS                           │
├─────────────────────────────────────────────────────────────┤
│                                                              │
│   Showing 5 of 1,247 records                                │
│                                                              │
│   ┌─────────────────────────────────────────────────────┐   │
│   │ Record 1                                             │   │
│   │ Identifier: ACC-00123                                │   │
│   │ Title: Blue Ceramic Vase                             │   │
│   │ Creator: Unknown                                     │   │
│   │ Date: circa 1850                                     │   │
│   │ Description: Decorative vase with floral motifs...  │   │
│   └─────────────────────────────────────────────────────┘   │
│                                                              │
│   ┌─────────────────────────────────────────────────────┐   │
│   │ Record 2                                             │   │
│   │ Identifier: ACC-00124                                │   │
│   │ Title: Portrait of Lady Smith                        │   │
│   │ Creator: John Brown                                  │   │
│   │ Date: 1892                                           │   │
│   └─────────────────────────────────────────────────────┘   │
│                                                              │
│   [ ← Back to Mapping ]              [ Import Now ]         │
│                                                              │
└─────────────────────────────────────────────────────────────┘
```

### What to Check

- Are identifiers correct?
- Are titles appearing properly?
- Are dates formatted correctly?
- Is any data missing?

If something is wrong, go back and adjust your mapping.

---

## Step 7: Choose Output

You can either import directly or export to a file:

### Option A: Import Directly

Records go straight into your database.

```
┌─────────────────────────────────────────────────────────────┐
│                    IMPORT PROGRESS                           │
├─────────────────────────────────────────────────────────────┤
│                                                              │
│   Importing records...                                       │
│                                                              │
│   ████████████████████░░░░░░░░░░  750 / 1,247               │
│                                                              │
│   ✓ 750 records imported                                    │
│   ⚠ 3 warnings (duplicates skipped)                         │
│   ✗ 0 errors                                                │
│                                                              │
│   Time elapsed: 2 minutes 34 seconds                        │
│                                                              │
└─────────────────────────────────────────────────────────────┘
```

### Option B: Export to File

Create a file to import later or share:

| Format | Use For |
|--------|---------|
| **CSV** | Review in Excel first |
| **EAD** | Share with other archives |
| **Dublin Core** | Web publishing |

---

## Common Migration Scenarios

### From Vernon CMS

Vernon fields map to museum objects:

| Vernon | AtoM |
|--------|------|
| Object Number | Identifier |
| Object Name | Title |
| Primary Maker | Creator |
| Date Made | Date |
| Simple Name | Object type |
| Dimensions | Physical description |

### From ArchivesSpace

ArchivesSpace exports map naturally:

| ArchivesSpace | AtoM |
|---------------|------|
| identifier | Reference code |
| title | Title |
| date_expression | Date |
| extents | Extent |
| scope_content | Scope and content |

### From Excel Spreadsheets

1. Save as CSV
2. Make sure first row contains column headers
3. Upload and map manually

---

## Troubleshooting

| Problem | Solution |
|---------|----------|
| File won't upload | Check file size (max 50MB) |
| Wrong source detected | Manually select source type |
| Fields not mapping | Check column names match |
| Characters look wrong | Save CSV as UTF-8 |
| Import very slow | Try smaller batches |
| Duplicates created | Check identifier mapping |

### Getting Help

If you have problems:
1. Check the preview carefully
2. Try a small test batch first
3. Contact your system administrator

---

## Best Practices

**Start small**
- Import 10 records first
- Check they look correct
- Then import the rest

**Keep records**
- Save your mapping
- Note any issues
- Keep original files

**Verify after import**
- Spot check random records
- Search for known items
- Check counts match

---

*For technical support, contact your system administrator.*
