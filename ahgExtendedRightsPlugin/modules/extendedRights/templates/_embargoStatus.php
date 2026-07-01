<?php
/**
 * Embargo Status Warning Banner
 * Shows if content is under embargo.
 *
 * Reads the canonical rights_embargo table (see EmbargoService, which
 * "Consolidated from dual embargo/rights_embargo tables"). The legacy
 * `embargo` table is no longer written to.
 */

$embargo = \Illuminate\Database\Capsule\Manager::table('rights_embargo')
    ->where('object_id', $objectId)
    ->where('status', 'active')
    ->where(function ($q) {
        $q->whereNull('end_date')
          ->orWhere('end_date', '>=', date('Y-m-d'));
    })
    ->first();

if (!$embargo) {
    return;
}

$user = sfContext::getInstance()->user;
$culture = $user->getCulture();
$i18n = \Illuminate\Database\Capsule\Manager::table('rights_embargo_i18n')
    ->where('id', $embargo->id)
    ->where('culture', $culture)
    ->first();

$typeLabels = [
    'full' => __('This record is restricted'),
    'metadata_only' => __('Digital content is restricted'),
    'digital_only' => __('Digital files are restricted'),
];

$message = $typeLabels[$embargo->embargo_type] ?? __('Access restricted');
// rights_embargo has no is_perpetual flag; auto_release=0 means it does not
// auto-release, i.e. no scheduled release date.
$isPerpetual = isset($embargo->auto_release) ? !$embargo->auto_release : false;
?>

<div class="alert alert-warning d-flex align-items-center mb-3" role="alert">
  <i class="fas fa-lock fa-2x me-3"></i>
  <div>
    <strong><?php echo $message; ?></strong>
    <?php if (!empty($embargo->end_date) && !$isPerpetual): ?>
      <br><small><?php echo __('Available from: %1%', ['%1%' => $embargo->end_date]); ?></small>
    <?php elseif ($isPerpetual): ?>
      <br><small><?php echo __('No scheduled release date'); ?></small>
    <?php endif; ?>
    <?php // reason_note may hold internal context — only expose to authenticated staff. ?>
    <?php if ($user->isAuthenticated() && $i18n && !empty($i18n->reason_note)): ?>
      <br><small><?php echo esc_entities($i18n->reason_note); ?></small>
    <?php endif; ?>
  </div>
</div>
