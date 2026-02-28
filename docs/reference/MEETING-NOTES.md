# Artifactual Technical Meeting - Discussion Points

**Date:** January 2026  
**Prepared by:** The Archive and Heritage Group

---

## Opening Statement

> "We've built a **non-invasive modernization layer** for AtoM that:
> - Adds Laravel Query Builder without modifying core files
> - Implements database-driven plugin management
> - Maintains 100% backward compatibility
> - **We enhance AtoM, we don't fork it**"

---

## Key Technical Points

### 1. Integration Point

**Single change to AtoM:**

```php
// ProjectConfiguration.class.php
protected function loadPluginsFromDatabase($corePlugins)
{
    require_once '../atom-framework/bootstrap.php';
    $dbPlugins = DB::table('atom_plugin')
        ->where('is_enabled', 1)
        ->pluck('name')->toArray();
    $this->enablePlugins(array_merge($corePlugins, $dbPlugins));
}
```

**Key points:**
- Template replaces entire file (not patched)
- Falls back to core plugins if framework unavailable
- No modification to Symfony lifecycle

### 2. Database-Driven Plugins

| Feature | Implementation |
|---------|----------------|
| Plugin registry | `atom_plugin` table |
| CLI management | `extension:enable/disable` |
| Locked plugins | ahgThemeB5Plugin, ahgSecurityClearancePlugin |
| Legacy support | `setting_i18n (id=1)` remains for UI only |

### 3. Dual-ORM Strategy

| Propel (Core) | Laravel (Extensions) |
|---------------|----------------------|
| QubitInformationObject | atom_plugin |
| QubitActor | atom_landing_page |
| QubitTerm | atom_security_clearance |
| QubitRepository | atom_researcher |
| QubitDigitalObject | atom_audit_log |

**Exception:** Plugin Manager uses PDO directly (autoloader conflicts during Symfony boot)

### 4. What We DON'T Touch

- Core database schema (object, information_object, actor, term, etc.)
- Core PHP files (lib/Qubit*.php)
- Symfony routing
- Propel model classes
- Core module templates

---

## Questions for Artifactual

1. **Plugin Loading:** Is there a preferred hook point beyond `ProjectConfiguration::setup()`?

2. **ORM Roadmap:** Any plans to modernize from Propel? We'd align with that direction.

3. **Theme System:** Would Artifactual consider adopting Bootstrap 5 themes as an official option?

4. **CLI Standards:** Would standardized plugin management CLI be valuable for the community?

5. **Propel Alternatives:** Are there considerations for migration paths to newer ORMs?

---

## What We Can Offer

| Contribution | Description |
|--------------|-------------|
| Bootstrap 5 Theme | Modern responsive theme implementation |
| Plugin Management | Database-driven plugin system patterns |
| SA Compliance | POPIA, NARSSA, PAIA, GRAP 103 modules |
| GLAM Support | Multi-sector improvements |
| Security | Classification system with clearance levels |

---

## What We're NOT Asking For

- We're **not** asking Artifactual to change core AtoM
- We're **not** creating a fork
- We're **not** breaking backward compatibility
- We're simply adding an **optional enhancement layer**

---

## Statistics

| Metric | Value |
|--------|-------|
| Plugins developed | 26+ |
| Core framework | 100% complete |
| PHP version | 8.3 |
| MySQL version | 8 |
| Elasticsearch | 7.10 |
| GitHub repos | 2 (atom-framework + atom-ahg-plugins) |

---

## Anticipated Questions & Answers

### "How do you handle AtoM upgrades?"

> Framework sits alongside AtoM, doesn't modify core files. Template approach means we can cleanly apply our configuration after AtoM updates. We test against AtoM releases before recommending upgrades.

### "Why not use Symfony's plugin system as-is?"

> `setting_i18n (id=1)` is legacy and limiting. Database-driven approach gives:
> - CLI management
> - Load ordering
> - Dependency tracking
> - Enable/disable without file changes
> - Plugin metadata storage

### "What's the performance impact?"

> Single DB query during bootstrap. Plugins cached after load. No measurable impact on request handling.

### "What about conflicts with core plugins?"

> Extension tables use `atom_` prefix. Never modify core tables. Our plugins add new functionality, don't override existing.

### "Why Laravel Query Builder specifically?"

> - Modern fluent syntax
> - Repository pattern support
> - Collection helpers
> - Well-documented
> - Large community
> - Doesn't require full Laravel framework

---

## Potential Collaboration Areas

1. **Upstream Contributions**
   - Bootstrap 5 theme as optional core theme
   - Database-driven plugin management RFC
   - CLI extension commands

2. **Standards Alignment**
   - Plugin manifest format
   - Dependency declaration
   - Configuration patterns

3. **Community Value**
   - South African compliance modules
   - GLAM sector enhancements
   - Security classification system

---

## Closing Statement

> "We're committed to the AtoM ecosystem. Our enhancements sit alongside core AtoM, not against it. We'd welcome feedback on our integration approach and any opportunities to contribute upstream while maintaining our commercial offering for the South African GLAM market."

---

## Contact Information

| | |
|---|---|
| **Organization** | The Archive and Heritage Group (Pty) Ltd |
| **Website** | [https://theahg.co.za](https://theahg.co.za) |
| **Demo Instance** | [https://psis.theahg.co.za](https://psis.theahg.co.za) |
| **GitHub** | [https://github.com/ArchiveHeritageGroup](https://github.com/ArchiveHeritageGroup) |

---

**Document Version:** 1.0.0  
**Last Updated:** January 2026
