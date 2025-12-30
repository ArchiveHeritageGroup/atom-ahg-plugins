<?php if ($rightsData['has_rights']): ?>

<section id="extended-rights-area" class="card mb-3">
  <div class="card-header">
    <h4 class="mb-0">
      <?php echo __('Rights Information'); ?>
      <?php if ($sf_user->isAuthenticated()): ?>
        <a href="<?php echo url_for(['module' => 'extendedRights', 'action' => 'edit', 'slug' => $resource->slug]); ?>" class="btn btn-sm btn-outline-secondary float-end">
          <i class="fas fa-edit"></i> <?php echo __('Edit'); ?>
        </a>
      <?php endif; ?>
    </h4>
  </div>
  <div class="card-body">
    <!-- Rights Badges -->
    <?php if (!empty($rightsData['badges'])): ?>
      <div class="rights-badges mb-3">
        <?php foreach ($rightsData['badges'] as $badge): ?>
          <a href="<?php echo $badge['uri']; ?>" target="_blank" class="rights-badge me-2 mb-2 d-inline-block" title="<?php echo esc_entities($badge['label']); ?>">
            <?php if ($badge['type'] === 'creative_commons'): ?>
              <?php echo $badge['badge_html']; ?>
            <?php else: ?>
              <img src="<?php echo $badge['icon']; ?>" alt="<?php echo esc_entities($badge['label']); ?>" class="rights-badge-icon">
            <?php endif; ?>
          </a>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <?php $primary = $rightsData['primary']; ?>
    <?php if ($primary): ?>
      <!-- Rights Statement -->
      <?php if ($primary->rightsStatement): ?>
        <div class="field mb-3">
          <h5><?php echo __('Rights Statement'); ?></h5>
          <div class="d-flex align-items-start">
            <img src="<?php echo $primary->rightsStatement->icon_url; ?>" alt="" class="me-3" style="width:88px;">
            <div>
              <strong><?php echo esc_entities($primary->rightsStatement->name); ?></strong>
              <p class="text-muted mb-1"><?php echo esc_entities($primary->rightsStatement->definition); ?></p>
              <a href="<?php echo $primary->rightsStatement->uri; ?>" target="_blank" class="small"><?php echo __('Learn more'); ?> <i class="fas fa-external-link-alt"></i></a>
            </div>
          </div>
        </div>
      <?php endif; ?>

      <!-- Creative Commons -->
      <?php if ($primary->creativeCommonsLicense): ?>
        <div class="field mb-3">
          <h5><?php echo __('License'); ?></h5>
          <div class="d-flex align-items-center">
            <?php echo $primary->creativeCommonsLicense->badge_html; ?>
            <div class="ms-3">
              <strong><?php echo esc_entities($primary->creativeCommonsLicense->name); ?></strong>
              <br>
              <a href="<?php echo $primary->creativeCommonsLicense->uri; ?>" target="_blank" class="small"><?php echo __('View license'); ?> <i class="fas fa-external-link-alt"></i></a>
            </div>
          </div>
        </div>
      <?php endif; ?>

      <!-- TK Labels -->
      <?php if ($primary->tkLabels && count($primary->tkLabels) > 0): ?>
        <div class="field mb-3">
          <h5><?php echo __('Traditional Knowledge Labels'); ?></h5>
          <div class="tk-labels-grid">
            <?php foreach ($primary->tkLabels as $label): ?>
              <div class="tk-label-item d-flex align-items-start mb-2">
                <img src="<?php echo $label->icon_url; ?>" alt="" class="me-2" style="width:48px;height:48px;">
                <div>
                  <strong><?php echo esc_entities($label->name); ?></strong>
                  <p class="small text-muted mb-0"><?php echo esc_entities($label->description); ?></p>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endif; ?>

      <!-- Rights Holder -->
      <?php if ($primary->rights_holder): ?>
        <div class="field mb-3">
          <h5><?php echo __('Rights Holder'); ?></h5>
          <?php if ($primary->rights_holder_uri): ?>
            <a href="<?php echo $primary->rights_holder_uri; ?>" target="_blank"><?php echo esc_entities($primary->rights_holder); ?></a>
          <?php else: ?>
            <?php echo esc_entities($primary->rights_holder); ?>
          <?php endif; ?>
        </div>
      <?php endif; ?>

      <!-- Copyright Notice -->
      <?php if ($primary->copyright_notice): ?>
        <div class="field mb-3">
          <h5><?php echo __('Copyright Notice'); ?></h5>
          <p><?php echo esc_entities($primary->copyright_notice); ?></p>
        </div>
      <?php endif; ?>

      <!-- Usage Conditions -->
      <?php if ($primary->rights_note): ?>
        <div class="field mb-3">
          <h5><?php echo __('Usage Notes'); ?></h5>
          <p><?php echo nl2br(esc_entities($primary->rights_note)); ?></p>
        </div>
      <?php endif; ?>
    <?php endif; ?>
  </div>
</section>

<?php else: ?>

<?php if ($sf_user->isAuthenticated()): ?>
<section id="extended-rights-area" class="card mb-3">
  <div class="card-body text-center">
    <p class="text-muted mb-2"><?php echo __('No extended rights information has been added.'); ?></p>
    <a href="<?php echo url_for(['module' => 'extendedRights', 'action' => 'edit', 'slug' => $resource->slug]); ?>" class="btn btn-primary">
      <i class="fas fa-plus"></i> <?php echo __('Add Rights Information'); ?>
    </a>
  </div>
</section>
<?php endif; ?>

<?php endif; ?>
