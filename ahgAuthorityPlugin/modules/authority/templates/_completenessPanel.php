<?php
/**
 * Embeddable panel: Completeness score gauge for actor view pages.
 * Usage: include_partial('authority/completenessPanel', ['actorId' => $actorId])
 */
$actorId = $actorId ?? 0;
if (!$actorId) return;

require_once sfConfig::get('sf_root_dir') . '/atom-ahg-plugins/ahgAuthorityPlugin/lib/Services/AuthorityCompletenessService.php';
$comp = (new \AhgAuthority\Services\AuthorityCompletenessService())->getCompleteness($actorId);
if (!$comp) return;

$score = $comp->completeness_score ?? 0;
$level = $comp->completeness_level ?? 'stub';
$levelColors = ['stub' => 'danger', 'minimal' => 'warning', 'partial' => 'info', 'full' => 'success'];
$color = $levelColors[$level] ?? 'secondary';
?>

<div class="card mb-3 authority-completeness-panel">
  <div class="card-header py-2">
    <i class="fas fa-chart-bar me-1"></i><?php echo __('Completeness'); ?>
  </div>
  <div class="card-body py-2">
    <div class="d-flex align-items-center">
      <div class="flex-grow-1 me-2">
        <div class="progress" style="height:20px">
          <div class="progress-bar bg-<?php echo $color; ?>"
               role="progressbar"
               style="width:<?php echo $score; ?>%"
               aria-valuenow="<?php echo $score; ?>"
               aria-valuemin="0" aria-valuemax="100">
            <?php echo $score; ?>%
          </div>
        </div>
      </div>
      <span class="badge bg-<?php echo $color; ?>"><?php echo ucfirst($level); ?></span>
    </div>
    <?php if ($comp->scored_at): ?>
      <small class="text-muted"><?php echo __('Last scored: %1%', ['%1%' => $comp->scored_at]); ?></small>
    <?php endif; ?>
  </div>
</div>
