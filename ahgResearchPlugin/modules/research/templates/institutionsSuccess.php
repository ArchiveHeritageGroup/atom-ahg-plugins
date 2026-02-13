<?php decorate_with('layout_1col.php') ?>
<?php slot('title') ?>
<div class="d-flex justify-content-between align-items-center">
  <h1><i class="fas fa-university text-primary me-2"></i><?php echo __('Partner Institutions'); ?></h1>
  <a href="<?php echo url_for(['module' => 'research', 'action' => 'editInstitution']); ?>" class="btn btn-primary">
    <i class="fas fa-plus me-1"></i><?php echo __('Add Institution'); ?>
  </a>
</div>
<?php end_slot() ?>

<?php slot('content') ?>
<?php
$institutions = isset($institutions) && is_array($institutions) ? $institutions : (isset($institutions) && method_exists($institutions, 'getRawValue') ? $institutions->getRawValue() : (isset($institutions) && is_iterable($institutions) ? iterator_to_array($institutions) : []));
?>

<?php if ($sf_user->hasFlash('success')): ?>
  <div class="alert alert-success alert-dismissible fade show"><?php echo $sf_user->getFlash('success'); ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>
<?php if ($sf_user->hasFlash('error')): ?>
  <div class="alert alert-danger alert-dismissible fade show"><?php echo $sf_user->getFlash('error'); ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>

<nav aria-label="breadcrumb" class="mb-3">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'research', 'action' => 'dashboard']); ?>"><?php echo __('Research'); ?></a></li>
    <li class="breadcrumb-item active"><?php echo __('Institutions'); ?></li>
  </ol>
</nav>

<?php if (!empty($institutions)): ?>
  <div class="table-responsive">
    <table class="table table-hover align-middle">
      <thead class="table-light">
        <tr>
          <th><?php echo __('Name'); ?></th>
          <th><?php echo __('Code'); ?></th>
          <th><?php echo __('URL'); ?></th>
          <th><?php echo __('Contact'); ?></th>
          <th><?php echo __('Status'); ?></th>
          <th class="text-end"><?php echo __('Actions'); ?></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($institutions as $institution): ?>
        <tr>
          <td>
            <strong><?php echo htmlspecialchars($institution->name); ?></strong>
            <?php if (!empty($institution->description)): ?>
              <br><small class="text-muted"><?php echo htmlspecialchars(mb_substr($institution->description, 0, 60)); ?><?php echo mb_strlen($institution->description ?? '') > 60 ? '...' : ''; ?></small>
            <?php endif; ?>
          </td>
          <td><code><?php echo htmlspecialchars($institution->code ?? ''); ?></code></td>
          <td>
            <?php if (!empty($institution->url)): ?>
              <a href="<?php echo htmlspecialchars($institution->url); ?>" target="_blank" class="text-decoration-none">
                <?php echo htmlspecialchars(preg_replace('#^https?://#', '', $institution->url)); ?>
                <i class="fas fa-external-link-alt ms-1 small"></i>
              </a>
            <?php else: ?>
              <span class="text-muted">-</span>
            <?php endif; ?>
          </td>
          <td>
            <?php if (!empty($institution->contact_name)): ?>
              <?php echo htmlspecialchars($institution->contact_name); ?>
              <?php if (!empty($institution->contact_email)): ?>
                <br><small><a href="mailto:<?php echo htmlspecialchars($institution->contact_email); ?>"><?php echo htmlspecialchars($institution->contact_email); ?></a></small>
              <?php endif; ?>
            <?php else: ?>
              <span class="text-muted">-</span>
            <?php endif; ?>
          </td>
          <td>
            <?php if ($institution->is_active ?? 1): ?>
              <span class="badge bg-success"><?php echo __('Active'); ?></span>
            <?php else: ?>
              <span class="badge bg-secondary"><?php echo __('Inactive'); ?></span>
            <?php endif; ?>
          </td>
          <td class="text-end">
            <a href="<?php echo url_for(['module' => 'research', 'action' => 'editInstitution', 'id' => $institution->id]); ?>" class="btn btn-sm btn-outline-primary" title="<?php echo __('Edit'); ?>">
              <i class="fas fa-edit"></i>
            </a>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
<?php else: ?>
  <div class="text-center py-5">
    <i class="fas fa-university fa-4x text-muted mb-3 opacity-50"></i>
    <h4 class="text-muted"><?php echo __('No partner institutions yet'); ?></h4>
    <p class="text-muted"><?php echo __('Add partner institutions to enable cross-institutional research sharing.'); ?></p>
    <a href="<?php echo url_for(['module' => 'research', 'action' => 'editInstitution']); ?>" class="btn btn-primary">
      <i class="fas fa-plus me-1"></i><?php echo __('Add First Institution'); ?>
    </a>
  </div>
<?php endif; ?>
<?php end_slot() ?>
