<?php decorate_with('layout_1col'); ?>

<?php slot('title'); ?>
  <h1><i class="fas fa-link me-2"></i><?php echo __('OpenURL Resolver'); ?></h1>
<?php end_slot(); ?>

<?php $results = $sf_data->getRaw('results'); ?>

<?php if (empty($results)): ?>
  <div class="alert alert-warning">
    <i class="fas fa-exclamation-triangle me-2"></i>
    <?php echo __('No catalogue record matched this citation'); ?><?php echo $query ? ': ' . esc_entities($query) : ''; ?>.
  </div>
<?php else: ?>
  <p class="text-muted"><?php echo __('%1% matching record(s)', ['%1%' => count($results)]); ?></p>
  <div class="card">
    <div class="table-responsive">
      <table class="table table-striped table-hover mb-0">
        <thead class="table-light">
          <tr><th><?php echo __('Title'); ?></th><th><?php echo __('ISBN/ISSN'); ?></th><th><?php echo __('Publisher'); ?></th><th><?php echo __('Date'); ?></th></tr>
        </thead>
        <tbody>
          <?php foreach ($results as $r): ?>
            <tr>
              <td>
                <?php if (!empty($r->slug)): ?>
                  <a href="<?php echo url_for(['module' => 'library', 'action' => 'index', 'slug' => $r->slug]); ?>"><?php echo esc_entities($r->title ?? '(untitled)'); ?></a>
                <?php else: ?>
                  <?php echo esc_entities($r->title ?? '(untitled)'); ?>
                <?php endif; ?>
              </td>
              <td><?php echo esc_entities($r->isbn ?? $r->issn ?? ''); ?></td>
              <td><?php echo esc_entities($r->publisher ?? ''); ?></td>
              <td><?php echo esc_entities($r->publication_date ?? ''); ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
<?php endif; ?>
