# GitHub Copilot Instructions — atom-ahg-plugins

## Project Identity
All AHG plugins for AtoM 2.10. Each plugin uses Symfony 1.4 architecture with Laravel Query Builder for database access.

## Stack
- Symfony 1.4 plugin architecture
- Laravel Query Builder via `Illuminate\Database\Capsule\Manager as DB`
- PHP 8.3, MySQL 8, Bootstrap 5, Bootstrap Icons

## Template Patterns — Critical
```php
// CORRECT
url_for(['module' => 'x', 'slug' => $resource->slug])
$resource->title ?? $resource->slug

// WRONG — never use
[$resource, 'module' => 'x']
render_title($resource)
```

## Stable Plugins — Do NOT Modify Routing/Core
ahgLibraryPlugin, ahgBackupPlugin, ahgAuditTrailPlugin,
ahgResearchPlugin, ahgThemeB5Plugin, ahgSecurityClearancePlugin

## data/install.sql Rules
- ALLOWED: taxonomy terms, default settings
- FORBIDDEN: INSERT INTO atom_plugin

## What NOT to Suggest
- Eloquent models, standalone Laravel, Docker
- INSERT INTO atom_plugin in data files
- Modifying core AtoM files
