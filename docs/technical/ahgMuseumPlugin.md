# ahgMuseumPlugin - Technical Documentation

## Overview

The Museum Plugin provides comprehensive museum object cataloguing following CCO (Cataloging Cultural Objects) and Spectrum 5.0 standards. It includes exhibition management, loan tracking, provenance documentation, condition assessments, and Getty vocabulary integration.

## Version

- **Current Version:** 1.1.0
- **Last Updated:** January 2026
- **Compatibility:** AtoM 2.10+, PHP 8.3+

## Architecture

```
ahgMuseumPlugin/
├── config/
│   ├── ahgMuseumPluginConfiguration.class.php  # Plugin initialization & routing
│   ├── app.yml                                  # Plugin settings
│   └── routing.yml                              # Route definitions (empty, see config class)
├── data/
│   ├── install.sql                              # Core museum tables
│   ├── exhibition_schema.sql                    # Exhibition management tables
│   └── cco_taxonomies.sql                       # CCO vocabulary terms
├── lib/
│   ├── Services/
│   │   ├── Exhibition/
│   │   │   └── ExhibitionService.php            # Exhibition CRUD & operations
│   │   ├── Workflow/
│   │   │   ├── WorkflowEngine.php               # State machine engine
│   │   │   ├── ExhibitionWorkflow.php           # Exhibition state machine
│   │   │   ├── LoanOutWorkflow.php              # Loan out state machine
│   │   │   ├── LoanInWorkflow.php               # Loan in state machine
│   │   │   └── ObjectEntryWorkflow.php          # Object entry state machine
│   │   ├── LoanService.php                      # Loan management
│   │   ├── ConditionReportService.php           # Condition assessments
│   │   ├── ProvenanceService.php                # Ownership history
│   │   ├── MeasurementService.php               # Unit conversion
│   │   ├── AatService.php                       # Getty AAT integration
│   │   └── ObjectComparisonService.php          # Side-by-side comparison
│   └── task/
│       └── museumExhibitionTask.class.php       # CLI commands
└── modules/
    ├── ahgMuseumPlugin/                         # Main museum module
    ├── cco/                                     # CCO cataloguing forms
    ├── exhibition/                              # Exhibition management
    │   ├── actions/
    │   │   └── actions.class.php
    │   └── templates/
    │       ├── indexSuccess.php
    │       ├── showSuccess.php
    │       ├── addSuccess.php
    │       ├── editSuccess.php -> addSuccess.php
    │       ├── dashboardSuccess.php
    │       ├── objectsSuccess.php
    │       ├── sectionsSuccess.php
    │       ├── storylinesSuccess.php
    │       ├── storylineSuccess.php
    │       ├── eventsSuccess.php
    │       ├── checklistsSuccess.php
    │       └── objectListSuccess.php
    ├── museumReports/                           # Reporting module
    ├── cidoc/                                   # CIDOC-CRM export
    └── api/                                     # API endpoints
```

## Database Schema

### Core Museum Tables

```sql
-- Museum object extensions (links to information_object)
museum_object
├── id (PK, FK to object.id)
├── object_number
├── object_type
├── classification
├── culture
├── period
├── materials
├── techniques
├── dimensions_json
├── inscription
├── condition_summary
├── current_location_id
└── timestamps

-- Provenance tracking
museum_provenance
├── id
├── museum_object_id (FK)
├── owner_name
├── owner_type
├── start_date
├── end_date
├── acquisition_method
├── documentation
├── is_verified
└── sequence_order

-- Condition reports
museum_condition_report
├── id
├── museum_object_id (FK)
├── overall_condition
├── condition_details_json
├── recommendations
├── assessor_id
├── assessment_date
└── next_review_date
```

### Exhibition Tables (13 tables)

```sql
-- Main exhibition record
exhibition
├── id (PK)
├── title
├── subtitle
├── description
├── theme
├── exhibition_type (permanent/temporary/traveling/online/pop_up)
├── status (concept/planning/preparation/installation/open/closing/closed/archived/canceled)
├── opening_date
├── closing_date
├── venue_id (FK)
├── venue_name
├── curator_id (FK)
├── curator_name
├── organized_by
├── budget_amount
├── budget_currency
├── expected_visitors
├── actual_visitors
├── admission_fee
├── is_free_admission
├── project_code
├── notes
├── created_by
└── timestamps

-- Exhibition sections/galleries
exhibition_section
├── id
├── exhibition_id (FK)
├── name
├── description
├── gallery_id (FK)
├── gallery_name
├── theme
├── display_order
└── timestamps

-- Objects in exhibition
exhibition_object
├── id
├── exhibition_id (FK)
├── museum_object_id (FK)
├── section_id (FK)
├── display_location
├── display_notes
├── display_order
├── is_loan (boolean)
├── loan_id (FK)
├── insurance_value
└── timestamps

-- Narrative storylines
exhibition_storyline
├── id
├── exhibition_id (FK)
├── title
├── description
├── type (general/guided_tour/self_guided/educational/accessible/highlights/thematic)
├── target_audience
├── duration_minutes
└── timestamps

-- Storyline stops
exhibition_storyline_stop
├── id
├── storyline_id (FK)
├── exhibition_object_id (FK)
├── title
├── narrative_content
├── audio_url
├── video_url
├── stop_order
├── duration_seconds
└── timestamps

-- Checklist templates
exhibition_checklist_template
├── id
├── name
├── checklist_type (planning/installation/opening/operation/closing)
├── items_json
├── is_default
└── timestamps

-- Active checklists
exhibition_checklist
├── id
├── exhibition_id (FK)
├── template_id (FK)
├── name
├── checklist_type
└── timestamps

-- Checklist items
exhibition_checklist_item
├── id
├── checklist_id (FK)
├── task_name
├── assigned_to
├── due_date
├── completed_at
├── completed_by
├── notes
├── item_order
└── timestamps

-- Exhibition events
exhibition_event
├── id
├── exhibition_id (FK)
├── title
├── event_type (opening/closing/talk/tour/workshop/performance/private_view/other)
├── event_date
├── event_time
├── end_time
├── location
├── description
├── capacity
├── registration_required
├── ticket_price
├── is_free
└── timestamps

-- Venues
exhibition_venue
├── id
├── name
├── address
├── city
├── contact_name
├── contact_email
├── contact_phone
└── timestamps

-- Galleries within venues
exhibition_gallery
├── id
├── venue_id (FK)
├── name
├── floor
├── area_sqm
├── climate_controlled
├── security_level
└── timestamps

-- Media/documents
exhibition_media
├── id
├── exhibition_id (FK)
├── media_type (image/document/video/audio)
├── file_path
├── title
├── description
├── is_public
└── timestamps

-- Status change history
exhibition_status_history
├── id
├── exhibition_id (FK)
├── from_status
├── to_status
├── changed_by
├── change_reason
└── created_at
```

### Loan Tables

```sql
-- Loan records (in/out)
museum_loan
├── id
├── loan_type (in/out)
├── loan_number
├── status (workflow state)
├── borrower_lender_name
├── contact_name
├── contact_email
├── purpose
├── start_date
├── end_date
├── insurance_value_total
├── conditions_json
└── timestamps

-- Loan objects
museum_loan_object
├── id
├── loan_id (FK)
├── museum_object_id (FK)
├── insurance_value
├── condition_on_loan
├── condition_on_return
└── timestamps
```

## Services

### ExhibitionService

```php
namespace arMuseumMetadataPlugin\Services\Exhibition;

class ExhibitionService
{
    // Constants
    public const TYPES = [
        'permanent' => 'Permanent Exhibition',
        'temporary' => 'Temporary Exhibition',
        'traveling' => 'Traveling Exhibition',
        'online' => 'Online/Virtual Exhibition',
        'pop_up' => 'Pop-up Exhibition',
    ];

    public const STATUSES = [
        'concept' => ['label' => 'Concept', 'color' => '#9e9e9e', 'order' => 1],
        'planning' => ['label' => 'Planning', 'color' => '#2196f3', 'order' => 2],
        'preparation' => ['label' => 'Preparation', 'color' => '#ff9800', 'order' => 3],
        'installation' => ['label' => 'Installation', 'color' => '#ff5722', 'order' => 4],
        'open' => ['label' => 'Open', 'color' => '#4caf50', 'order' => 5],
        'closing' => ['label' => 'Closing', 'color' => '#9c27b0', 'order' => 6],
        'closed' => ['label' => 'Closed', 'color' => '#607d8b', 'order' => 7],
        'archived' => ['label' => 'Archived', 'color' => '#795548', 'order' => 8],
        'canceled' => ['label' => 'Canceled', 'color' => '#f44336', 'order' => 9],
    ];

    // Methods
    public function create(array $data, int $userId): int;
    public function get(int $id, bool $includeRelations = false): ?array;
    public function update(int $id, array $data): bool;
    public function transitionStatus(int $id, string $newStatus, int $userId, ?string $reason = null): bool;
    public function search(array $filters = [], int $limit = 50, int $offset = 0): array;

    // Sections
    public function addSection(int $exhibitionId, array $data): int;
    public function getSections(int $exhibitionId): array;

    // Objects
    public function addObject(int $exhibitionId, int $museumObjectId, array $data = []): int;
    public function getObjects(int $exhibitionId, ?int $sectionId = null): array;

    // Storylines
    public function createStoryline(int $exhibitionId, array $data): int;
    public function getStorylines(int $exhibitionId): array;
    public function addStorylineStop(int $storylineId, array $data): int;
    public function getStorylineWithStops(int $storylineId): ?array;

    // Events
    public function createEvent(int $exhibitionId, array $data): int;
    public function getEvents(int $exhibitionId): array;

    // Checklists
    public function createChecklistFromTemplate(int $exhibitionId, int $templateId): int;
    public function getChecklists(int $exhibitionId): array;
    public function completeChecklistItem(int $itemId, int $userId): bool;

    // Statistics
    public function getStatistics(): array;
    public function getExhibitionStatistics(int $exhibitionId): array;

    // Reports
    public function generateObjectList(int $exhibitionId): array;
}
```

### ExhibitionWorkflow

```php
namespace arMuseumMetadataPlugin\Services\Workflow;

class ExhibitionWorkflow extends AbstractWorkflow
{
    // States
    private const STATES = [
        'concept', 'planning', 'preparation', 'installation',
        'open', 'closing', 'closed', 'archived', 'canceled'
    ];

    // Transitions
    private const TRANSITIONS = [
        'start_planning' => ['from' => 'concept', 'to' => 'planning'],
        'begin_preparation' => ['from' => 'planning', 'to' => 'preparation'],
        'start_installation' => ['from' => 'preparation', 'to' => 'installation'],
        'open_exhibition' => ['from' => 'installation', 'to' => 'open'],
        'begin_closing' => ['from' => 'open', 'to' => 'closing'],
        'close_exhibition' => ['from' => 'closing', 'to' => 'closed'],
        'archive' => ['from' => 'closed', 'to' => 'archived'],
        'cancel' => ['from' => ['concept', 'planning', 'preparation'], 'to' => 'canceled'],
        'reopen_planning' => ['from' => 'canceled', 'to' => 'planning'],
    ];

    public function getProgress(string $state): int;
    public function getChecklistTypeForState(string $state): ?string;
}
```

### LoanService

```php
class LoanService
{
    public function createLoanOut(array $data, int $userId): int;
    public function createLoanIn(array $data, int $userId): int;
    public function addObjectToLoan(int $loanId, int $objectId, array $data = []): int;
    public function transition(int $loanId, string $transition, int $userId): bool;
    public function getLoan(int $id): ?array;
    public function getActiveLoans(string $type = null): array;
    public function getOverdueLoans(): array;
}
```

### ProvenanceService

```php
class ProvenanceService
{
    public function addEntry(int $objectId, array $data): int;
    public function getHistory(int $objectId): array;
    public function getTimeline(int $objectId): array;  // For D3.js visualization
    public function verifyEntry(int $entryId, int $userId): bool;
    public function generateReport(int $objectId): array;
}
```

### AatService (Getty Integration)

```php
class AatService
{
    public function search(string $query, string $type = null): array;
    public function getTermById(string $aatId): ?array;
    public function getHierarchy(string $aatId): array;
    public function suggestMaterials(string $query): array;
    public function suggestObjectTypes(string $query): array;
}
```

## Routes

Routes are defined in `ahgMuseumPluginConfiguration.class.php`:

```php
// Museum object routes
/museum/:slug          → ahgMuseumPlugin/index
/museum/browse         → ahgMuseumPlugin/browse
/museum/add            → ahgMuseumPlugin/add
/museum/edit/:slug     → ahgMuseumPlugin/edit

// Exhibition routes
/exhibition            → exhibition/index
/exhibition/dashboard  → exhibition/dashboard
/exhibition/add        → exhibition/add
/exhibition/:id        → exhibition/show
/exhibition/:id/edit   → exhibition/edit
/exhibition/:id/objects    → exhibition/objects
/exhibition/:id/sections   → exhibition/sections
/exhibition/:id/storylines → exhibition/storylines
/exhibition/:id/events     → exhibition/events
/exhibition/:id/checklists → exhibition/checklists

// API routes
/ahgMuseumPlugin/vocabulary    → vocabulary autocomplete
/ahgMuseumPlugin/getty         → Getty AAT lookup
```

## CLI Commands

```bash
# Exhibition management
php symfony museum:exhibition --list                    # List all exhibitions
php symfony museum:exhibition --show --id=5             # Show exhibition details
php symfony museum:exhibition --create                  # Interactive creation
php symfony museum:exhibition --status --id=5 --to=open # Change status
php symfony museum:exhibition --statistics              # Overall statistics
php symfony museum:exhibition --object-list --id=5      # Generate object list
php symfony museum:exhibition --upcoming                # List upcoming exhibitions
php symfony museum:exhibition --current                 # List currently open
php symfony museum:exhibition --overdue                 # List overdue closings
php symfony museum:exhibition --install-schema          # Install database tables
```

## Configuration

### app.yml

```yaml
all:
  ahgMuseumPlugin:
    enabled: true
    default_object_type: artifact
    default_currency: ZAR
    getty_api_enabled: true
    provenance_visualization: true
    loan_approval_required: true
    exhibition_checklist_templates: true
```

## Integration Points

### With Core AtoM
- Links to `information_object` for archival context
- Uses `actor` for creators, donors, lenders
- Integrates with `digital_object` for images
- Uses `physical_object` for storage locations

### With Other Plugins
- **ahgConditionPlugin**: Shared condition assessment framework
- **ahgSpectrumPlugin**: Spectrum 5.0 procedure workflows
- **ahgAuditTrailPlugin**: Change tracking
- **ahgPreservationPlugin**: Digital preservation for museum media

### External APIs
- Getty AAT/ULAN/TGN via SPARQL
- CIDOC-CRM export capability

## Security

- All actions require authentication
- Admin/editor roles for modifications
- Loan approvals require manager role
- Exhibition status changes logged

## Performance Considerations

- Indexes on `exhibition.status`, `exhibition.opening_date`, `exhibition.closing_date`
- Indexes on `exhibition_object.exhibition_id`, `exhibition_object.museum_object_id`
- Lazy loading for related objects
- Caching for Getty vocabulary lookups

## Migration

To install or upgrade:

```bash
# Install core museum tables
mysql -u root database < atom-ahg-plugins/ahgMuseumPlugin/data/install.sql

# Install exhibition tables
mysql -u root database < atom-ahg-plugins/ahgMuseumPlugin/data/exhibition_schema.sql

# Load CCO taxonomies
mysql -u root database < atom-ahg-plugins/ahgMuseumPlugin/data/cco_taxonomies.sql

# Clear cache
php symfony cc
```

## Troubleshooting

### Common Issues

1. **Exhibition module not found**
   - Ensure `exhibition` is in enabled modules in plugin configuration
   - Clear Symfony cache

2. **Routes not working**
   - Check `ahgMuseumPluginConfiguration.class.php` has routes defined
   - Verify routing cache is cleared

3. **Getty lookups failing**
   - Check network connectivity to Getty SPARQL endpoint
   - Verify `getty_api_enabled: true` in config

4. **Workflow transitions blocked**
   - Check current state allows the transition
   - Verify user has required permissions

## Changelog

### v1.1.0 (January 2026)
- Added Exhibition Management module
  - Full exhibition lifecycle workflow
  - Sections and gallery organization
  - Storyline/narrative creation
  - Event scheduling
  - Checklist management
  - Object list reports
- Added exhibition database schema (13 tables)
- Added ExhibitionService and ExhibitionWorkflow
- Added CLI commands for exhibition management
- Added Museum Dashboard link to Central Dashboard
- Updated user documentation

### v1.0.0 (Initial Release)
- CCO cataloguing forms
- Spectrum 5.0 procedures
- Loan management (in/out)
- Provenance tracking with D3.js visualization
- Condition assessments
- Getty AAT/ULAN/TGN integration
- CIDOC-CRM export
- Object comparison tool

---

*Part of the AtoM AHG Framework*
