<?php
/**
 * Embargo Status Warning Banner
 * Shows if content is under embargo
 */

$embargo = \Illuminate\Database\Capsule\Manager::table('embargo')
    ->where('object_id', $objectId)
    ->where('is_active', true)
    ->where(function ($q) {
        $q->whereNull('end_date')
          ->orWhere('end_date', '>=', date('Y-m-d'));
    })
    ->first();

if (!$embargo) {
    return;
}

$culture = sfContext::getInstance()->user->getCulture();
$i18n = \Illuminate\Database\Capsule\Manager::table('embargo_i18n')
    ->where('embargo_id', $embargo->id)
    ->where('culture', $culture)
    ->first();

$typeLabels = [
    'full' => __('This record is restricted'),
    'metadata_only' => __('Digital content is restricted'),
    'digital_only' => __('Digital files are restricted'),
];

$message = $typeLabels[$embargo->embargo_type] ?? __('Access restricted');
?>

<div class="alert alert-warning d-flex align-items-center mb-3" role="alert">
  <i class="fas fa-lock fa-2x me-3"></i>
  <div>
    <strong><?php echo $message; ?></strong>
    <?php if ($embargo->end_date && !$embargo->is_perpetual): ?>
      <br><small><?php echo __('Available from: %1%', ['%1%' => $embargo->end_date]); ?></small>
    <?php endif; ?>
    <?php if ($i18n && $i18n->public_message): ?>
      <br><small><?php echo esc_entities($i18n->public_message); ?></small>
    <?php endif; ?>
  </div>
</div>
