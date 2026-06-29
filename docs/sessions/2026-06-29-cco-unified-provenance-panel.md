# 2026-06-29 — Embed unified provenance panel in CCO/museum view (v3.79.21)

Scope: archaeology + archive canonical. Follow-up to v3.79.19 (CCO provenance link fix).

## Change
`ahgMuseumPlugin/modules/cco/templates/indexSuccess.php` — added the unified,
sector-agnostic provenance section after the accession area, matching the pattern the
provenance unification (v3.79.13) gave mods/dc/rad/dacs/gallery:

```php
<?php if (in_array('ahgProvenancePlugin', sfProjectConfiguration::getActive()->getPlugins())): ?>
<section id="provenanceArea" class="border-bottom">
  <?php echo render_b5_section_heading(__('Provenance & Chain of Custody'), false, null, ['anchor' => 'provenance-collapse']); ?>
  <?php include_component('provenance', 'provenanceDisplay', ['objectId' => $resource->id]); ?>
</section>
<?php endif ?>
```

CCO records now surface unified provenance two ways: the actions-menu link (v3.79.19) and
this inline panel. Uses the same `provenanceDisplay` component the other standards use
(component exists at ahgProvenancePlugin/modules/provenance/actions/components.class.php),
guarded by the plugin-enabled check so it no-ops if ahgProvenancePlugin is absent.

## Verify & deploy
Lint clean; mirrored archive→archaeology; cache cleared + php-fpm restarted; cco view
renders (404 anonymously = auth-gated, expected). Released as v3.79.21 (pushed origin/main
+ tag). The existing inline museum-metadata provenance fields ($museumData['provenance'],
ownership_history) were left as-is — they are descriptive CCO fields, distinct from the
chain-of-custody panel.
