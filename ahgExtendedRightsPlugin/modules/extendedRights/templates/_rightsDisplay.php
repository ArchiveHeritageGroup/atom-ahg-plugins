<?php if ($rights || $embargo): ?>

<?php if ($embargo): ?>
<?php
$embargoTypes = [
    'full' => __('Full Access Restriction'),
    'metadata_only' => __('Digital Content Restricted'),
    'digital_object' => __('Download Restricted'),
    'custom' => __('Access Restricted'),
];
$embargoType = $embargoTypes[$embargo->embargo_type ?? 'full'] ?? __('Access Restricted');
?>
<div class="alert alert-warning border-warning mb-3">
  <div class="d-flex align-items-start">
    <i class="fas fa-exclamation-triangle fa-2x me-3 text-warning"></i>
    <div>
      <h5 class="alert-heading mb-1"><?php echo $embargoType; ?></h5>
      <?php if (!empty($embargo->lift_reason)): ?>
        <p class="mb-1"><?php echo esc_entities($embargo->lift_reason); ?></p>
      <?php else: ?>
        <p class="mb-1"><?php echo __('Access to this material is currently restricted.'); ?></p>
      <?php endif; ?>
      <?php if (!!$embargo->auto_release && $embargo->end_date): ?>
        <small class="text-muted">
          <i class="fas fa-calendar-alt me-1"></i>
          <?php echo __('Available from: %1%', ['%1%' => date('j F Y', strtotime($embargo->end_date))]); ?>
        </small>
      <?php elseif (!$embargo->auto_release): ?>
        <small class="text-muted">
          <i class="fas fa-lock me-1"></i>
          <?php echo __('Indefinite restriction'); ?>
        </small>
      <?php endif; ?>
    </div>
  </div>
</div>
<?php endif; ?>

<?php if ($rights): ?>
<!-- Rights Statement -->
<?php if ($rights->rs_code): ?>
<h4 class="h5 mt-3 mb-2 text-muted"><?php echo __('Rights Statement'); ?></h4>
<?php echo render_show(__('Statement'), $rights->rs_name . ($rights->rs_uri ? ' <a href="' . $rights->rs_uri . '" target="_blank"><i class="fas fa-external-link-alt"></i></a>' : '')); ?>
<?php endif; ?>

<!-- Creative Commons -->
<?php if ($rights->cc_code): ?>
<h4 class="h5 mt-3 mb-2 text-muted"><?php echo __('License'); ?></h4>
<?php echo render_show(__('License'), $rights->cc_name . ($rights->cc_uri ? ' <a href="' . $rights->cc_uri . '" target="_blank"><i class="fas fa-external-link-alt"></i></a>' : '')); ?>
<?php endif; ?>

<!-- TK Labels -->
<?php if (!empty($rights->tk_labels) && count($rights->tk_labels) > 0): ?>
<h4 class="h5 mt-3 mb-2 text-muted"><?php echo __('Traditional Knowledge Labels'); ?></h4>
<?php foreach ($rights->tk_labels as $tk): ?>
<?php echo render_show($tk->category_name ?? __('TK Label'), $tk->name . ($tk->uri ? ' <a href="' . $tk->uri . '" target="_blank"><i class="fas fa-external-link-alt"></i></a>' : '')); ?>
<?php endforeach; ?>
<?php endif; ?>

<?php endif; ?>
<?php endif; ?>