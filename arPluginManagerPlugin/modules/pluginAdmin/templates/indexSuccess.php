<?php use_helper('Javascript'); ?>

<?php slot('title'); ?>
  <?php echo __('Plugin Management'); ?>
<?php end_slot(); ?>

<?php slot('content'); ?>

<div class="container-fluid py-4">
  <div class="row mb-4">
    <div class="col">
      <div class="d-flex justify-content-between align-items-center">
        <div>
          <h1 class="h3 mb-1"><?php echo __('Plugin Management'); ?></h1>
          <p class="text-muted mb-0"><?php echo __('Enable or disable plugins'); ?></p>
        </div>
        <div>
          <a href="<?php echo url_for(['module' => 'pluginAdmin', 'action' => 'auditLog']); ?>" class="btn btn-outline-info">
            <i class="fas fa-history me-1"></i><?php echo __('Audit Log'); ?>
          </a>
        </div>
      </div>
    </div>
  </div>

  <?php if (!$databaseAvailable): ?>
    <div class="alert alert-warning">
      <i class="fas fa-exclamation-triangle me-2"></i>
      <strong><?php echo __('Limited Mode'); ?></strong> - 
      <?php echo __('Run "php symfony plugin:manage migrate" to enable database-driven management.'); ?>
    </div>
  <?php endif; ?>

  <?php foreach ($categories as $category => $categoryPlugins): ?>
    <div class="card mb-4">
      <div class="card-header bg-light">
        <h5 class="mb-0"><?php echo ucfirst($category); ?></h5>
      </div>
      <div class="card-body p-0">
        <table class="table table-hover mb-0">
          <thead class="table-light">
            <tr>
              <th style="width:40%"><?php echo __('Plugin'); ?></th>
              <th style="width:30%"><?php echo __('Status'); ?></th>
              <th style="width:30%"><?php echo __('Actions'); ?></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($categoryPlugins as $plugin): ?>
              <tr id="plugin-row-<?php echo $plugin['name']; ?>">
                <td>
                  <strong><?php echo $plugin['name']; ?></strong>
                  <?php if (!empty($plugin['is_core'])): ?>
                    <span class="badge bg-primary ms-1">Core</span>
                  <?php endif; ?>
                  <?php if (!empty($plugin['is_locked'])): ?>
                    <span class="badge bg-warning ms-1">Locked</span>
                  <?php endif; ?>
                </td>
                <td>
                  <?php if ($plugin['is_enabled']): ?>
                    <span class="badge bg-success">Enabled</span>
                  <?php else: ?>
                    <span class="badge bg-secondary">Disabled</span>
                  <?php endif; ?>
                </td>
                <td>
                  <?php if (!$plugin['is_core'] && !$plugin['is_locked']): ?>
                    <?php if ($plugin['is_enabled']): ?>
                      <button class="btn btn-sm btn-outline-danger toggle-plugin" 
                              data-plugin="<?php echo $plugin['name']; ?>" data-enable="false">
                        <i class="fas fa-power-off me-1"></i>Disable
                      </button>
                    <?php else: ?>
                      <button class="btn btn-sm btn-outline-success toggle-plugin"
                              data-plugin="<?php echo $plugin['name']; ?>" data-enable="true">
                        <i class="fas fa-check me-1"></i>Enable
                      </button>
                    <?php endif; ?>
                  <?php else: ?>
                    <span class="text-muted">—</span>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  <?php endforeach; ?>
</div>

<script>
document.querySelectorAll('.toggle-plugin').forEach(btn => {
  btn.addEventListener('click', async function() {
    const plugin = this.dataset.plugin;
    const enable = this.dataset.enable;
    const reason = prompt('Reason for this change (optional):') || '';
    
    this.disabled = true;
    this.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
    
    try {
      const response = await fetch('<?php echo url_for(['module' => 'pluginAdmin', 'action' => 'toggle']); ?>', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `name=${plugin}&enable=${enable}&reason=${encodeURIComponent(reason)}`
      });
      const data = await response.json();
      
      if (data.success) {
        location.reload();
      } else {
        alert('Error: ' + (data.error || 'Unknown error'));
        this.disabled = false;
        this.innerHTML = enable === 'true' ? 'Enable' : 'Disable';
      }
    } catch (err) {
      alert('Request failed');
      this.disabled = false;
    }
  });
});
</script>

<?php end_slot(); ?>
