# ahgCorePlugin

**Version:** 1.1.0
**Category:** Core
**Status:** Core Plugin (Locked)

Core utilities and shared services for AHG plugins. This plugin provides the foundation layer that all other AHG plugins depend on.

## Features

- **AhgDb**: Centralized database bootstrap using Laravel Query Builder
- **AhgConfig**: Configuration resolver (no more hardcoded paths/URLs)
- **AhgTaxonomy**: AtoM taxonomy and term ID resolution (no more hardcoded IDs)
- **AhgTaxonomyService**: Custom dropdown vocabularies (database-driven dropdowns)
- **AhgStorage**: File storage utilities with proper permissions
- **AhgHooks**: Hook/event dispatcher for plugin extensibility
- **AhgPanels**: Panel registry for display pages
- **AhgCapabilities**: Feature registry for dependency-free detection
- **Contracts**: Interfaces for cross-plugin communication

## Installation

This plugin is automatically loaded as a core dependency. It should be enabled in the database:

```sql
INSERT INTO atom_plugin (name, class_name, version, is_enabled, is_core, is_locked, load_order)
VALUES ('ahgCorePlugin', 'ahgCorePluginConfiguration', '1.0.0', 1, 1, 1, 1);
```

## Usage

### Database Access

```php
use AhgCore\Core\AhgDb;

// Instead of: require_once bootstrap.php; DB::table(...)
$results = AhgDb::table('information_object')->where('id', 1)->first();

// Transactions
AhgDb::transaction(function() {
    AhgDb::table('my_table')->insert([...]);
});
```

### Configuration

```php
use AhgCore\Core\AhgConfig;

// Instead of hardcoded URLs
$baseUrl = AhgConfig::getSiteBaseUrl();

// Instead of hardcoded paths
$uploadPath = AhgConfig::getUploadPath('documents');

// Get settings
$culture = AhgConfig::getCulture();
```

### Taxonomy Resolution (AtoM Standard)

```php
use AhgCore\Core\AhgTaxonomy;

// Instead of: const TAXONOMY_SUBJECT = 35;
$taxonomyId = AhgTaxonomy::getTaxonomyId('SUBJECT');

// Instead of: const TERM_CREATION_ID = 111;
$termId = AhgTaxonomy::getTermId('EVENT_TYPE', 'Creation');

// Or use the constants (for known standard IDs)
$id = AhgTaxonomy::TERM_CREATION; // 111
```

### Custom Dropdown Vocabularies (AhgTaxonomyService)

Database-driven dropdowns stored in `ahg_dropdown` table. Replaces hardcoded dropdown values with admin-manageable terms.

```php
use ahgCorePlugin\Services\AhgTaxonomyService;

$taxonomyService = new AhgTaxonomyService();

// Get dropdown choices (code => label)
$statuses = $taxonomyService->getLoanStatuses();
// Returns: ['' => '', 'draft' => 'Draft', 'active' => 'Active', ...]

// Without empty option
$statuses = $taxonomyService->getLoanStatuses(false);
// Returns: ['draft' => 'Draft', 'active' => 'Active', ...]

// Get full term data with colors/icons
$statusesWithColors = $taxonomyService->getLoanStatusesWithColors();
// Returns: ['draft' => {code, name, color, icon, sort_order, ...}, ...]

// Get single term attributes
$label = $taxonomyService->getTermName('loan_status', 'active');
$color = $taxonomyService->getTermColor('loan_status', 'active');

// Generic methods
$choices = $taxonomyService->getTermsAsChoices('my_taxonomy');
$terms = $taxonomyService->getTermsWithAttributes('my_taxonomy');
```

**Available Taxonomies (35):**

| Category | Taxonomies |
|----------|------------|
| Exhibition | `exhibition_type`, `exhibition_status`, `exhibition_object_status` |
| Loans | `loan_status`, `loan_type` |
| Workflow | `workflow_status`, `rtp_status` |
| Rights | `rights_basis`, `copyright_status`, `act_type`, `restriction_type` |
| Embargo | `embargo_type`, `embargo_reason`, `embargo_status` |
| Condition | `condition_grade`, `damage_type`, `report_type`, `image_type` |
| Shipping | `shipment_type`, `shipment_status`, `cost_type` |
| Research | `id_type`, `organization_type`, `equipment_type`, `equipment_condition`, `workspace_privacy` |
| Library | `creator_role` |
| Documents | `document_type`, `reminder_type` |
| Export | `rdf_format` |
| Agreements | `agreement_status` |
| Links | `link_status` |
| Other | `work_type`, `source_type` |

**In Templates:**
```php
<?php
$taxonomyService = new \ahgCorePlugin\Services\AhgTaxonomyService();
$statuses = $taxonomyService->getLoanStatuses(false);
?>
<select name="status" class="form-select">
  <?php foreach ($statuses as $code => $label): ?>
    <option value="<?php echo $code ?>"><?php echo __($label) ?></option>
  <?php endforeach; ?>
</select>
```

**Admin UI:** Managed via `/admin/dropdowns`

### File Storage

```php
use AhgCore\Core\AhgStorage;

// Store uploaded file
$result = AhgStorage::store($_FILES['upload'], 'documents');
// Returns: ['path' => '...', 'filename' => '...', 'url' => '...']

// Sanitize filename
$safe = AhgStorage::sanitizeFilename('My File (1).pdf');

// Create directory with proper permissions
AhgStorage::mkdir('/path/to/dir');
```

### Facade Class

```php
use AhgCore\AhgCore;

// Quick access to all services
$db = AhgCore::db();
$config = AhgCore::config();
$taxonomy = AhgCore::taxonomy();
$storage = AhgCore::storage();

// Direct table access
$results = AhgCore::table('information_object')->get();

// Current user
$userId = AhgCore::getCurrentUserId();
```

### Service Registration (for plugins)

```php
use AhgCore\AhgCore;
use AhgCore\Contracts\AuditServiceInterface;

// Register a service
AhgCore::registerService(AuditServiceInterface::class, new MyAuditService());

// Use the service
$audit = AhgCore::getService(AuditServiceInterface::class);
$audit->log('create', 'QubitInformationObject', $id);
```

## Contracts

### AuditServiceInterface

For audit logging services. Implemented by ahgAuditTrailPlugin.

```php
interface AuditServiceInterface
{
    public function log(string $action, string $entityType, ?int $entityId = null, array $options = []): mixed;
    public function logCreate(object $entity, array $newValues = [], array $options = []): mixed;
    public function logUpdate(object $entity, array $oldValues = [], array $newValues = [], array $options = []): mixed;
    public function logDelete(object $entity, array $options = []): mixed;
}
```

### DisplayActionProviderInterface

For plugins that provide actions/panels/badges to display views.

```php
interface DisplayActionProviderInterface
{
    public function getActions(object $entity, array $context = []): array;
    public function getPanels(object $entity, array $context = []): array;
    public function getBadges(object $entity, array $context = []): array;
}
```

## Vendor Assets

This plugin includes commonly-used vendor JavaScript and CSS libraries:

- **chart.min.js** - Chart.js for data visualization
- **tom-select.complete.min.js** - Tom Select for enhanced select inputs
- **select2.min.js** - Select2 for searchable dropdowns
- **model-viewer.min.js** - 3D model viewer
- **Sortable.min.js** - Drag and drop sorting

CSS:
- select2.min.css
- select2-bootstrap-5-theme.min.css
- tom-select.bootstrap5.min.css

## Database Tables

### ahg_dropdown

Custom controlled vocabulary terms for plugin dropdowns.

| Column | Type | Description |
|--------|------|-------------|
| id | INT | Primary key |
| taxonomy | VARCHAR(100) | Taxonomy code (e.g., 'loan_status') |
| taxonomy_label | VARCHAR(255) | Display name (e.g., 'Loan Status') |
| code | VARCHAR(100) | Term code (e.g., 'draft') |
| label | VARCHAR(255) | Term display label |
| color | VARCHAR(7) | Hex color (e.g., '#4caf50') |
| icon | VARCHAR(50) | Icon class (e.g., 'fa-check') |
| sort_order | INT | Display order |
| is_default | TINYINT | Default selection flag |
| is_active | TINYINT | Soft delete flag |
| metadata | JSON | Extended attributes |

## Requirements

- PHP 8.1+
- AtoM 2.8+
- atom-framework 1.0.0+

## License

Proprietary - The Archive and Heritage Group (Pty) Ltd

## Author

Johan Pieterse <johan@theahg.co.za>
