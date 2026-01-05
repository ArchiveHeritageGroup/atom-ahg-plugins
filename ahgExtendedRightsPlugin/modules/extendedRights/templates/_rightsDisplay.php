<?php if ($rights || $embargo): ?>

<?php if ($embargo): ?>
<div class="alert alert-danger mb-3">
  <strong><i class="fas fa-lock me-2"></i><?php echo __('Access Restricted'); ?></strong>
  <?php if ($embargo->public_message): ?>
    <p class="mb-0 mt-2"><?php echo esc_entities($embargo->public_message); ?></p>
  <?php endif; ?>
  <?php if (!$embargo->is_perpetual && $embargo->end_date): ?>
    <small class="text-muted"><?php echo __('Until: %1%', ['%1%' => $embargo->end_date]); ?></small>
  <?php endif; ?>
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