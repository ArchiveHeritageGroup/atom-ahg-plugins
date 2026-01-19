# ahgMuseumPlugin

Museum and GLAM (Galleries, Libraries, Archives, Museums) extension for AtoM, providing specialized functionality for cultural heritage institutions.

## Modules

1. [Exhibition Management](#exhibition-management)
2. [Provenance Tracking](#provenance-tracking)
3. [Loan Management](#loan-management) *(coming soon)*

---

## Exhibition Management

Comprehensive exhibition planning and management system for museums and galleries.

### Features

- **Exhibition Lifecycle Management** - 9-stage workflow from concept to archived
- **Object Management** - Link collection objects with drag & drop ordering
- **Sections/Galleries** - Organize objects into themed sections
- **Multiple Storylines** - Create different narrative paths for various audiences
- **Event Scheduling** - Plan openings, tours, lectures, workshops
- **Checklists** - Task management with templates, assignments, due dates
- **Reporting** - Object lists, statistics, progress tracking

### Database Tables

| Table | Purpose |
|-------|---------|
| `exhibition` | Main exhibition records |
| `exhibition_section` | Gallery/room sections within exhibitions |
| `exhibition_object` | Objects assigned to exhibitions |
| `exhibition_storyline` | Narrative paths through exhibitions |
| `exhibition_storyline_stop` | Individual stops on a storyline |
| `exhibition_event` | Scheduled events (openings, tours, etc.) |
| `exhibition_checklist` | Task checklists for exhibition phases |
| `exhibition_checklist_item` | Individual checklist tasks |
| `exhibition_checklist_template` | Reusable checklist templates |
| `exhibition_checklist_template_item` | Template task items |

### Exhibition Statuses

| Status | Color | Description |
|--------|-------|-------------|
| `concept` | Gray | Initial idea phase |
| `planning` | Blue | Active planning |
| `preparation` | Cyan | Content preparation |
| `installation` | Orange | Physical setup |
| `open` | Green | Currently open to public |
| `closing` | Yellow | Closing phase |
| `closed` | Red | Closed to public |
| `archived` | Dark | Historical record |
| `canceled` | Dark Red | Canceled exhibition |

### Routes/URLs

| URL Pattern | Action | Description |
|-------------|--------|-------------|
| `/exhibition` | index | List all exhibitions |
| `/exhibition/:id` | show | Exhibition details |
| `/exhibition/add` | add | Create new exhibition |
| `/exhibition/edit/id/:id` | edit | Edit exhibition |
| `/exhibition/:id/objects` | objects | Manage exhibition objects |
| `/exhibition/:id/sections` | sections | Manage sections |
| `/exhibition/:id/storylines` | storylines | List storylines |
| `/exhibition/storyline/id/:id` | storyline | Storyline details |
| `/exhibition/:id/events` | events | Manage events |
| `/exhibition/:id/checklists` | checklists | Manage checklists |
| `/exhibition/:id/object-list` | objectList | Object list report |

### AJAX Endpoints

| URL | Method | Purpose |
|-----|--------|---------|
| `/exhibition/searchObjects` | GET | Search collection objects |
| `/exhibition/reorderObjects/id/:id` | POST | Save drag & drop order |
| `/exhibition/transition/id/:id` | POST | Change exhibition status |
| `/exhibition/completeItem/id/:id` | POST | Mark checklist item done |

### Controller Actions

Located in `modules/exhibition/actions/actions.class.php`:

```
executeIndex          - List exhibitions with filters
executeShow           - Exhibition detail view
executeAdd            - Create exhibition form
executeEdit           - Edit exhibition form
executeObjects        - Manage exhibition objects
executeAddObject      - Add object to exhibition
executeUpdateObject   - Update object placement
executeRemoveObject   - Remove object from exhibition
executeReorderObjects - Save drag & drop order (AJAX)
executeSections       - Manage sections
executeAddSection     - Add section
executeUpdateSection  - Update section
executeDeleteSection  - Delete section
executeStorylines     - List storylines
executeStoryline      - Storyline detail with stops
executeAddStoryline   - Add storyline
executeUpdateStoryline - Update storyline
executeDeleteStoryline - Delete storyline
executeAddStop        - Add storyline stop
executeUpdateStop     - Update stop
executeDeleteStop     - Delete stop
executeEvents         - Manage events
executeAddEvent       - Add event
executeUpdateEvent    - Update event
executeDeleteEvent    - Delete event
executeChecklists     - Manage checklists
executeCreateChecklist - Create from template
executeAddChecklistItem - Add checklist item
executeCompleteItem   - Mark item complete
executeObjectList     - Generate object list report
executeSearchObjects  - AJAX object search
executeTransition     - Change exhibition status
```

### Service Class

Located in `lib/Services/Exhibition/ExhibitionService.php`:

**Exhibition Methods:**
- `search(filters, limit, offset)` - Search exhibitions
- `get(id, includeDetails)` - Get exhibition by ID
- `getBySlug(slug)` - Get exhibition by slug
- `create(data, userId)` - Create exhibition
- `update(id, data, userId)` - Update exhibition
- `transitionStatus(id, newStatus, userId, reason)` - Change status
- `getStatistics()` - Overall statistics
- `getTypes()` / `getStatuses()` - Get enum values

**Object Methods:**
- `getObjects(exhibitionId, sectionId)` - Get exhibition objects
- `addObject(exhibitionId, objectId, data)` - Add object
- `updateObject(id, data)` - Update object placement
- `removeObject(id)` - Remove object
- `reorderObjects(order)` - Save new order
- `checkObjectAvailability(objectId, exhibitionId)` - Check conflicts

**Section Methods:**
- `getSections(exhibitionId)` - Get sections
- `addSection(exhibitionId, data)` - Add section
- `updateSection(id, data)` - Update section
- `deleteSection(id)` - Delete section

**Storyline Methods:**
- `getStorylines(exhibitionId)` - Get storylines
- `getStoryline(id)` - Get storyline with stops
- `addStoryline(exhibitionId, data)` - Add storyline
- `updateStoryline(id, data)` - Update storyline
- `deleteStoryline(id)` - Delete storyline
- `addStorylineStop(storylineId, data)` - Add stop
- `updateStorylineStop(id, data)` - Update stop
- `deleteStorylineStop(id)` - Delete stop

**Event Methods:**
- `getEvents(exhibitionId)` - Get events
- `addEvent(exhibitionId, data)` - Add event
- `updateEvent(id, data)` - Update event
- `deleteEvent(id)` - Delete event

**Checklist Methods:**
- `getChecklists(exhibitionId)` - Get checklists with items
- `getChecklistTemplates()` - Get available templates
- `createChecklistFromTemplate(exhibitionId, templateId)` - Create from template
- `addChecklistItem(checklistId, data)` - Add item
- `completeChecklistItem(itemId, userId, notes)` - Mark complete

### Templates

Located in `modules/exhibition/templates/`:

| Template | Purpose |
|----------|---------|
| `indexSuccess.php` | Exhibition list with filters |
| `showSuccess.php` | Exhibition detail page |
| `addSuccess.php` | Create exhibition form |
| `editSuccess.php` | Edit exhibition form |
| `objectsSuccess.php` | Object management with drag & drop |
| `sectionsSuccess.php` | Section management |
| `storylinesSuccess.php` | Storyline list |
| `storylineSuccess.php` | Storyline detail with stops |
| `eventsSuccess.php` | Event management |
| `checklistsSuccess.php` | Checklist management |
| `objectListSuccess.php` | Object list report |

### Usage Example

```php
// Get exhibition service
$service = new \AtomExtensions\Services\Exhibition\ExhibitionService();

// Search exhibitions
$results = $service->search(['status' => 'open'], 20, 0);

// Get exhibition with all details
$exhibition = $service->get(1, true);

// Add object to exhibition
$service->addObject(1, 553, [
    'section_id' => 1,
    'display_position' => 'Case A1',
    'insurance_value' => 15000.00,
    'label_text' => 'Archaeological artifact...'
]);

// Create checklist from template
$checklistId = $service->createChecklistFromTemplate(1, 1);
```

---

## Provenance Tracking

Visual timeline of object ownership and custody history using D3.js.

### Features

- Interactive timeline visualization
- Color-coded event types
- Verified/unverified status tracking
- Multiple data sources (ISAD-G fields, events, custom table)

### Routes

| URL Pattern | Action | Description |
|-------------|--------|-------------|
| `/:slug/ahgMuseumPlugin/provenance` | provenance | Provenance timeline |

### Data Sources

- ISAD(G) Archival History field
- ISAD(G) Custodial History field
- Immediate Source of Acquisition
- Related Events (Creation, Accumulation, Collection)
- Optional: `museum_provenance` table for detailed tracking

### Optional Database Table

```sql
CREATE TABLE museum_provenance (
    id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    object_id INT(11) UNSIGNED NOT NULL,
    custodian_name VARCHAR(255) NOT NULL,
    custodian_type VARCHAR(100),
    start_date DATE,
    end_date DATE,
    location VARCHAR(255),
    acquisition_method VARCHAR(100),
    notes TEXT,
    verified TINYINT(1) DEFAULT 0,
    source_reference VARCHAR(255),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME,
    KEY idx_object (object_id),
    KEY idx_dates (start_date, end_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

---

## Loan Management

*Coming soon* - Incoming and outgoing loan management for GLAM institutions.

### Planned Features

- Loan requests and approvals workflow
- Borrower/lender management
- Insurance and indemnity tracking
- Condition reporting integration
- Shipping and courier management
- Loan agreements and contracts
- Due date monitoring and renewals
- Integration with Exhibition module

---

## Installation

### 1. Enable the plugin

```bash
php bin/atom extension:enable ahgMuseumPlugin
```

### 2. Run database migrations

```bash
mysql -u root archive < plugins/ahgMuseumPlugin/data/install.sql
```

### 3. Clear cache

```bash
php symfony cc
```

### 4. Access the modules

- Exhibitions: `/index.php/exhibition`
- Provenance: `/index.php/{object-slug}/ahgMuseumPlugin/provenance`

---

## File Structure

```
ahgMuseumPlugin/
├── config/
│   ├── ahgMuseumPluginConfiguration.class.php
│   └── routing.yml
├── data/
│   └── install.sql
├── lib/
│   └── Services/
│       └── Exhibition/
│           └── ExhibitionService.php
├── modules/
│   ├── ahgMuseumPlugin/
│   │   ├── actions/
│   │   │   └── actions.class.php
│   │   └── templates/
│   │       ├── indexSuccess.php
│   │       └── provenanceSuccess.php
│   └── exhibition/
│       ├── actions/
│       │   └── actions.class.php
│       └── templates/
│           ├── indexSuccess.php
│           ├── showSuccess.php
│           ├── addSuccess.php
│           ├── editSuccess.php
│           ├── objectsSuccess.php
│           ├── sectionsSuccess.php
│           ├── storylinesSuccess.php
│           ├── storylineSuccess.php
│           ├── eventsSuccess.php
│           ├── checklistsSuccess.php
│           └── objectListSuccess.php
└── README.md
```

---

## Requirements

- AtoM 2.10+
- PHP 8.1+
- MySQL 8.0+
- atom-framework (Laravel Query Builder)

---

## License

Copyright (c) 2024-2025 The Archive and Heritage Group (Pty) Ltd
All rights reserved.
