<?php
// Unified, sector-agnostic provenance panel. Included by every sector view
// (archive/sfIsad, museum, library, dam, gallery, …). Self-hides when empty
// (outputs nothing), so the record view can drop the whole section rather than
// show an empty "no provenance recorded" block. "Add provenance" stays reachable
// from the left context menu on every sector.
$io = QubitInformationObject::getById($objectId);
$slug = $io ? $io->slug : null;
$canEdit = $sf_user->isAuthenticated();

// Left-border colour carries the certainty of each link, so a chain that is
// mostly solid with one weak step reads as such at a glance.
$certaintyClass = function ($c) {
    switch ($c) {
        case 'certain':   return 'c-certain';
        case 'probable':  return 'c-probable';
        case 'possible':  return 'c-possible';
        case 'uncertain': return 'c-uncertain';
        default:          return 'c-unknown';
    }
};
?>
<?php if ($provenance['exists']): ?>
<?php $record = $provenance['record']; ?>
<?php if (!defined('AHG_PROV_CHAIN_CSS')) { define('AHG_PROV_CHAIN_CSS', 1); ?>
<style <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
.ahg-prov-chain{display:flex;flex-wrap:wrap;align-items:stretch;list-style:none;padding:0;margin:0}
.ahg-prov-step{display:flex;align-items:center;margin:0 0 .6rem 0;max-width:100%}
.ahg-prov-card{position:relative;border:1px solid #dee2e6;border-left:4px solid #adb5bd;
  border-radius:.4rem;padding:.45rem .75rem .5rem;background:#fff;min-width:9.5rem;max-width:15rem}
.ahg-prov-seq{position:absolute;top:-.55rem;left:-.55rem;width:1.35rem;height:1.35rem;
  border-radius:50%;background:#6c757d;color:#fff;font-size:.7rem;line-height:1.35rem;
  text-align:center;font-weight:600}
.ahg-prov-owner{display:block;font-weight:600;font-size:.86rem;line-height:1.25;color:#212529}
.ahg-prov-dates{display:block;font-size:.76rem;color:#495057;margin-top:.1rem}
.ahg-prov-meta{display:block;font-size:.72rem;color:#6c757d;margin-top:.08rem}
.ahg-prov-arrow{color:#ced4da;margin:0 .45rem;font-size:1.1rem;flex:0 0 auto}
.ahg-prov-card.c-certain{border-left-color:#198754}
.ahg-prov-card.c-probable{border-left-color:#0d6efd}
.ahg-prov-card.c-possible{border-left-color:#ffc107}
.ahg-prov-card.c-uncertain{border-left-color:#fd7e14}
.ahg-prov-card.c-unknown{border-left-color:#adb5bd}
.ahg-prov-step.is-gap .ahg-prov-card{border-style:dashed;background:#fffdf3}
.ahg-prov-step.is-gap .ahg-prov-seq{background:#ffc107;color:#212529}
@media (max-width:575.98px){.ahg-prov-card{max-width:100%}.ahg-prov-arrow{display:none}
  .ahg-prov-step{width:100%}}
</style>
<?php } ?>
<div class="card mb-3 provenance-display">
  <div class="card-header bg-light d-flex justify-content-between align-items-center py-2">
    <h6 class="mb-0"><i class="fas fa-clock-rotate-left me-2"></i><?php echo __('Provenance') ?></h6>
    <?php if ($slug): ?>
    <div class="btn-group btn-group-sm" role="group">
      <a href="<?php echo url_for(['module' => 'provenance', 'action' => 'view', 'slug' => $slug]) ?>" class="btn btn-outline-primary">
        <?php echo __('View details') ?>
      </a>
      <?php if ($canEdit): ?>
      <a href="<?php echo url_for(['module' => 'provenance', 'action' => 'edit', 'slug' => $slug]) ?>" class="btn btn-outline-secondary">
        <i class="fas fa-pen me-1"></i><?php echo __('Manage') ?>
      </a>
      <?php endif ?>
    </div>
    <?php endif ?>
  </div>
  <div class="card-body">

    <!-- Quick Stats -->
    <div class="row g-2 small mb-3">
      <div class="col-auto">
        <span class="badge bg-<?php echo $record->certainty_level === 'certain' ? 'success' : ($record->certainty_level === 'uncertain' ? 'warning' : 'secondary') ?>">
          <?php echo __('Certainty') ?>: <?php echo ucfirst($record->certainty_level ?? 'unknown') ?>
        </span>
      </div>
      <?php if (!empty($provenance['timeline'])): ?>
      <div class="col-auto">
        <span class="badge bg-light text-dark border">
          <?php echo count($provenance['timeline']) ?> <?php echo __('in chain of custody') ?>
        </span>
      </div>
      <?php endif ?>
      <?php if ($record->has_gaps): ?>
      <div class="col-auto">
        <span class="badge bg-warning text-dark"><i class="fas fa-link-slash me-1"></i><?php echo __('Has gaps') ?></span>
      </div>
      <?php endif ?>
      <?php if ($record->nazi_era_provenance_checked): ?>
      <div class="col-auto">
        <span class="badge bg-<?php echo $record->nazi_era_provenance_clear ? 'success' : 'danger' ?>">
          <i class="fas fa-shield-halved me-1"></i>Nazi-era <?php echo $record->nazi_era_provenance_clear ? 'Clear' : 'Flagged' ?>
        </span>
      </div>
      <?php endif ?>
    </div>

    <!-- Chain of custody, drawn in full. Every link is shown: a chain that is
         summarised away to a count is not a provenance display. -->
    <?php if (!empty($provenance['timeline'])): ?>
    <small class="text-muted d-block mb-2"><?php echo __('Chain of custody') ?></small>
    <ol class="ahg-prov-chain">
      <?php foreach ($provenance['timeline'] as $i => $event): ?>
      <?php $isGap = !empty($event['is_gap']); ?>
      <li class="ahg-prov-step<?php echo $isGap ? ' is-gap' : '' ?>">
        <?php if ($i > 0): ?><span class="ahg-prov-arrow" aria-hidden="true">&rarr;</span><?php endif ?>
        <div class="ahg-prov-card <?php echo $certaintyClass($event['certainty'] ?? 'unknown') ?>"
             <?php if (!empty($event['description'])): ?>title="<?php echo htmlspecialchars($event['description']) ?>"<?php endif ?>>
          <span class="ahg-prov-seq"><?php echo $i + 1 ?></span>
          <span class="ahg-prov-owner"><?php echo htmlspecialchars($event['to'] ?? $event['type_label']) ?></span>
          <span class="ahg-prov-dates"><?php echo htmlspecialchars($event['date_display']) ?></span>
          <span class="ahg-prov-meta">
            <?php echo htmlspecialchars($event['type_label']) ?><?php if (!empty($event['location'])): ?>
            &middot; <?php echo htmlspecialchars($event['location']) ?><?php endif ?>
          </span>
        </div>
      </li>
      <?php endforeach ?>
    </ol>
    <?php elseif (!empty($provenance['summary'])): ?>
    <p class="mb-0"><?php echo nl2br(htmlspecialchars($provenance['summary'])) ?></p>
    <?php endif ?>

  </div>
</div>
<?php endif ?>
<?php // When no provenance is recorded, render NOTHING - the record view hides the
      // whole section, and "Add provenance" is reachable from the left context menu. ?>
