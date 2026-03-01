<?php decorate_with('layout_1col'); ?>

<?php
  $rawFunctions = $sf_data->getRaw('functions');
  $functions    = is_array($rawFunctions) ? $rawFunctions : [];
?>

<?php slot('title'); ?>
  <h1><i class="fas fa-sitemap me-2"></i><?php echo __('Browse by Function'); ?></h1>
<?php end_slot(); ?>

<?php slot('before-content'); ?>
  <nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb">
      <li class="breadcrumb-item">
        <a href="<?php echo url_for('@ahg_authority_dashboard'); ?>"><?php echo __('Authority Dashboard'); ?></a>
      </li>
      <li class="breadcrumb-item active"><?php echo __('Functions Browse'); ?></li>
    </ol>
  </nav>
<?php end_slot(); ?>

<?php slot('content'); ?>

  <div class="card">
    <div class="card-header">
      <i class="fas fa-sitemap me-1"></i><?php echo __('ISDF Functions'); ?>
    </div>
    <div class="card-body p-0">
      <table class="table table-hover mb-0">
        <thead>
          <tr>
            <th><?php echo __('Function'); ?></th>
            <th class="text-center"><?php echo __('Linked Actors'); ?></th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($functions)): ?>
            <tr><td colspan="3" class="text-center text-muted py-3"><?php echo __('No ISDF functions found.'); ?></td></tr>
          <?php else: ?>
            <?php foreach ($functions as $func): ?>
              <tr>
                <td>
                  <?php if ($func->slug): ?>
                    <a href="/<?php echo htmlspecialchars($func->slug); ?>"><?php echo htmlspecialchars($func->title); ?></a>
                  <?php else: ?>
                    <?php echo htmlspecialchars($func->title); ?>
                  <?php endif; ?>
                </td>
                <td class="text-center">
                  <span class="badge bg-secondary"><?php echo $func->actor_count; ?></span>
                </td>
                <td>
                  <?php if ($func->slug): ?>
                    <a href="/<?php echo htmlspecialchars($func->slug); ?>" class="btn btn-sm btn-outline-primary">
                      <i class="fas fa-eye"></i>
                    </a>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

<?php end_slot(); ?>
