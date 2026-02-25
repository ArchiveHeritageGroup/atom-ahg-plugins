<?php decorate_with(sfConfig::get('sf_plugins_dir').'/ahgRegistryPlugin/modules/registry/templates/layout_registry'); ?>

<?php slot('title'); ?><?php echo htmlspecialchars($newsletter->subject, ENT_QUOTES, 'UTF-8'); ?><?php end_slot(); ?>

<?php slot('content'); ?>

<?php include_partial('registry/breadcrumb', ['items' => [
  ['label' => __('Home'), 'url' => url_for('@homepage')],
  ['label' => __('Registry'), 'url' => url_for(['module' => 'registry', 'action' => 'index'])],
  ['label' => __('Newsletters'), 'url' => '/registry/newsletters'],
  ['label' => htmlspecialchars($newsletter->subject, ENT_QUOTES, 'UTF-8')],
]]); ?>

<div class="row justify-content-center">
  <div class="col-lg-8">

    <article class="mb-5">
      <header class="mb-4">
        <h1 class="h2 mb-3"><?php echo htmlspecialchars($newsletter->subject, ENT_QUOTES, 'UTF-8'); ?></h1>
        <div class="d-flex flex-wrap gap-3 text-muted small">
          <?php if (!empty($newsletter->sent_at)): ?>
          <span><i class="fas fa-calendar me-1"></i><?php echo date('j F Y', strtotime($newsletter->sent_at)); ?></span>
          <?php endif; ?>
          <?php if (!empty($newsletter->recipient_count)): ?>
          <span><i class="fas fa-users me-1"></i><?php echo number_format($newsletter->recipient_count); ?> <?php echo __('recipients'); ?></span>
          <?php endif; ?>
        </div>
      </header>

      <?php if (!empty($newsletter->excerpt)): ?>
      <div class="lead text-muted mb-4" style="font-size: 1.1rem;">
        <?php echo htmlspecialchars($newsletter->excerpt, ENT_QUOTES, 'UTF-8'); ?>
      </div>
      <?php endif; ?>

      <hr>

      <div class="newsletter-content mt-4">
        <?php echo $newsletter->content; ?>
      </div>
    </article>

    <hr class="my-4">

    <div class="d-flex justify-content-between align-items-center">
      <a href="/registry/newsletters" class="btn btn-outline-secondary">
        <i class="fas fa-arrow-left me-1"></i> <?php echo __('All Newsletters'); ?>
      </a>
      <a href="/registry/newsletter/subscribe" class="btn btn-primary">
        <i class="fas fa-envelope me-1"></i> <?php echo __('Subscribe'); ?>
      </a>
    </div>

  </div>

  <div class="col-lg-4">
    <div class="card shadow-sm mt-4 mt-lg-0">
      <div class="card-body text-center">
        <i class="fas fa-envelope-open-text fa-3x text-primary mb-3"></i>
        <h5><?php echo __('Stay Updated'); ?></h5>
        <p class="text-muted small"><?php echo __('Get the latest news about GLAM institutions, software releases, and community events.'); ?></p>
        <a href="/registry/newsletter/subscribe" class="btn btn-primary btn-sm w-100">
          <i class="fas fa-envelope me-1"></i> <?php echo __('Subscribe to Newsletter'); ?>
        </a>
      </div>
    </div>
  </div>
</div>

<?php end_slot(); ?>
