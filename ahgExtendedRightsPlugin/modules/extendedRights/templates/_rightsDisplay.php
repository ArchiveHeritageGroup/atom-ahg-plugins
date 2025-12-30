<?php if ($rights || $embargo): ?>
<section id="extended-rights-area" class="card mb-3">
  <div class="card-header d-flex justify-content-between align-items-center">
    <h4 class="mb-0"><i class="fas fa-balance-scale me-2"></i><?php echo __('Rights'); ?></h4>
    <?php if ($sf_user->isAuthenticated()): ?>
      <a href="<?php echo url_for(['module' => 'extendedRights', 'action' => 'edit', 'slug' => $sf_request->getParameter('slug')]); ?>" class="btn btn-sm btn-outline-secondary">
        <i class="fas fa-edit"></i>
      </a>
    <?php endif; ?>
  </div>
  <div class="card-body">
    
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
    
    <!-- Rights Badges -->
    <div class="rights-badges mb-3">
      <?php if ($rights->rs_code): ?>
        <a href="<?php echo $rights->rs_uri; ?>" target="_blank" class="badge-link me-2" title="<?php echo esc_entities($rights->rs_name); ?>">
          <span class="badge bg-<?php echo $rights->rs_category === 'in-copyright' ? 'warning text-dark' : ($rights->rs_category === 'no-copyright' ? 'success' : 'secondary'); ?> p-2">
            <i class="fas fa-copyright me-1"></i><?php echo esc_entities($rights->rs_code); ?>
          </span>
        </a>
      <?php endif; ?>
      
      <?php if ($rights->cc_code): ?>
        <a href="<?php echo $rights->cc_uri; ?>" target="_blank" class="badge-link me-2" title="<?php echo esc_entities($rights->cc_name); ?>">
          <span class="badge bg-info p-2">
            <i class="fab fa-creative-commons me-1"></i><?php echo esc_entities($rights->cc_code); ?>
          </span>
        </a>
      <?php endif; ?>
    </div>

    <!-- Rights Statement -->
    <?php if ($rights->rs_name): ?>
    <div class="mb-3">
      <h6 class="text-muted"><?php echo __('Rights Statement'); ?></h6>
      <p class="mb-1"><strong><?php echo esc_entities($rights->rs_name); ?></strong></p>
      <?php if ($rights->rs_definition): ?>
        <p class="text-muted small mb-0"><?php echo esc_entities($rights->rs_definition); ?></p>
      <?php endif; ?>
      <a href="<?php echo $rights->rs_uri; ?>" target="_blank" class="small">
        <?php echo __('Learn more'); ?> <i class="fas fa-external-link-alt"></i>
      </a>
    </div>
    <?php endif; ?>

    <!-- Creative Commons -->
    <?php if ($rights->cc_name): ?>
    <div class="mb-3">
      <h6 class="text-muted"><?php echo __('License'); ?></h6>
      <p class="mb-1"><strong><?php echo esc_entities($rights->cc_name); ?></strong></p>
      <div class="cc-permissions small mb-2">
        <?php if ($rights->allows_commercial): ?>
          <span class="text-success me-2"><i class="fas fa-check"></i> <?php echo __('Commercial use'); ?></span>
        <?php else: ?>
          <span class="text-danger me-2"><i class="fas fa-times"></i> <?php echo __('Non-commercial only'); ?></span>
        <?php endif; ?>
        <?php if ($rights->allows_adaptation): ?>
          <span class="text-success me-2"><i class="fas fa-check"></i> <?php echo __('Adaptations'); ?></span>
        <?php else: ?>
          <span class="text-danger me-2"><i class="fas fa-times"></i> <?php echo __('No derivatives'); ?></span>
        <?php endif; ?>
        <?php if ($rights->requires_sharealike): ?>
          <span class="text-info"><i class="fas fa-share"></i> <?php echo __('Share alike'); ?></span>
        <?php endif; ?>
      </div>
      <a href="<?php echo $rights->cc_uri; ?>" target="_blank" class="small">
        <?php echo __('View license'); ?> <i class="fas fa-external-link-alt"></i>
      </a>
    </div>
    <?php endif; ?>

    <!-- TK Labels -->
    <?php if ($rights->tk_labels && count($rights->tk_labels) > 0): ?>
    <div class="mb-3">
      <h6 class="text-muted"><?php echo __('Traditional Knowledge Labels'); ?></h6>
      <div class="tk-labels-list">
        <?php foreach ($rights->tk_labels as $label): ?>
          <div class="tk-label-item d-flex align-items-start mb-2 p-2 rounded" style="background-color: <?php echo $label->category_color; ?>15; border-left: 3px solid <?php echo $label->category_color; ?>">
            <div>
              <strong><?php echo esc_entities($label->name); ?></strong>
              <?php if ($label->description): ?>
                <p class="small text-muted mb-0"><?php echo esc_entities($label->description); ?></p>
              <?php endif; ?>
              <a href="<?php echo $label->uri; ?>" target="_blank" class="small">
                <?php echo __('Learn more'); ?> <i class="fas fa-external-link-alt"></i>
              </a>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>

    <!-- Rights Holder -->
    <?php if ($rights->rights_holder): ?>
    <div class="mb-3">
      <h6 class="text-muted"><?php echo __('Rights Holder'); ?></h6>
      <?php if ($rights->rights_holder_uri): ?>
        <a href="<?php echo $rights->rights_holder_uri; ?>" target="_blank"><?php echo esc_entities($rights->rights_holder); ?></a>
      <?php else: ?>
        <?php echo esc_entities($rights->rights_holder); ?>
      <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- Copyright Notice -->
    <?php if ($rights->copyright_notice): ?>
    <div class="mb-3">
      <h6 class="text-muted"><?php echo __('Copyright Notice'); ?></h6>
      <p class="mb-0"><?php echo esc_entities($rights->copyright_notice); ?></p>
    </div>
    <?php endif; ?>

    <!-- Rights Note -->
    <?php if ($rights->rights_note): ?>
    <div class="mb-0">
      <h6 class="text-muted"><?php echo __('Rights Note'); ?></h6>
      <p class="mb-0"><?php echo nl2br(esc_entities($rights->rights_note)); ?></p>
    </div>
    <?php endif; ?>

    <?php else: ?>
    
    <?php if ($sf_user->isAuthenticated()): ?>
    <p class="text-muted mb-0">
      <?php echo __('No rights information has been added.'); ?>
      <a href="<?php echo url_for(['module' => 'extendedRights', 'action' => 'edit', 'slug' => $sf_request->getParameter('slug')]); ?>">
        <?php echo __('Add rights'); ?>
      </a>
    </p>
    <?php endif; ?>
    
    <?php endif; ?>
  </div>
</section>
<?php endif; ?>
