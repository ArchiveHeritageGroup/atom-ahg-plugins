<?php
/**
 * Inline completeness badge for browse result rows.
 * Usage: include_partial('authority/completenessBadge', ['actorId' => $actorId])
 */
$actorId = $actorId ?? 0;
if (!$actorId) return;

$comp = \Illuminate\Database\Capsule\Manager::table('ahg_actor_completeness')
    ->where('actor_id', $actorId)
    ->first();

if (!$comp) return;

$levelColors = ['stub' => 'danger', 'minimal' => 'warning', 'partial' => 'info', 'full' => 'success'];
$color = $levelColors[$comp->completeness_level] ?? 'secondary';
?>
<span class="badge bg-<?php echo $color; ?>" title="<?php echo __('Completeness: %1%%', ['%1%' => $comp->completeness_score]); ?>">
  <?php echo $comp->completeness_score; ?>%
</span>
