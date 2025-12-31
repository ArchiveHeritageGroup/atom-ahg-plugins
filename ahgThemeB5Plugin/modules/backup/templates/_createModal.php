<?php
/**
 * Backup create modal with presets
 */
$backupService = new \AtomExtensions\Services\BackupService();
$presets = $backupService->getAvailablePresets();
?>

<div class="modal fade" id="createBackupModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="bi bi-archive me-2"></i><?php echo __('Create Backup') ?></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form id="createBackupForm" action="<?php echo url_for(['module' => 'backup', 'action' => 'create']) ?>" method="post">
        <div class="modal-body">
          <p class="text-muted mb-3"><?php echo __('Select a backup preset or customize options.') ?></p>
          
          <!-- Presets -->
          <div class="row g-3 mb-4">
            <?php foreach ($presets as $key => $preset): ?>
            <div class="col-md-4 col-6">
              <div class="card h-100 preset-card" data-preset="<?php echo $key ?>">
                <div class="card-body text-center py-3">
                  <i class="<?php echo $preset['icon'] ?> fs-2 mb-2 text-primary"></i>
                  <h6 class="mb-1"><?php echo __($preset['name']) ?></h6>
                  <small class="text-muted"><?php echo __($preset['description']) ?></small>
                </div>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
          
          <input type="hidden" name="preset" id="backup-preset" value="ahg">
          
          <!-- Custom Options (collapsed by default) -->
          <div class="collapse" id="customOptions">
            <hr>
            <h6 class="mb-3"><?php echo __('Custom Options') ?></h6>
            <div class="row">
              <div class="col-md-6">
                <div class="form-check mb-2">
                  <input class="form-check-input" type="checkbox" name="database" id="opt-database" checked>
                  <label class="form-check-label" for="opt-database"><?php echo __('Database') ?></label>
                </div>
                <div class="form-check mb-2">
                  <input class="form-check-input" type="checkbox" name="digital_objects" id="opt-digital-objects">
                  <label class="form-check-label" for="opt-digital-objects"><?php echo __('Digital Objects (uploads/r)') ?></label>
                </div>
                <div class="form-check mb-2">
                  <input class="form-check-input" type="checkbox" name="uploads" id="opt-uploads">
                  <label class="form-check-label" for="opt-uploads"><?php echo __('All Uploads') ?></label>
                </div>
              </div>
              <div class="col-md-6">
                <div class="form-check mb-2">
                  <input class="form-check-input" type="checkbox" name="atom_base" id="opt-atom-base">
                  <label class="form-check-label" for="opt-atom-base"><?php echo __('AtoM Base') ?></label>
                </div>
                <div class="form-check mb-2">
                  <input class="form-check-input" type="checkbox" name="plugins" id="opt-plugins" checked>
                  <label class="form-check-label" for="opt-plugins"><?php echo __('AHG Plugins') ?></label>
                </div>
                <div class="form-check mb-2">
                  <input class="form-check-input" type="checkbox" name="framework" id="opt-framework" checked>
                  <label class="form-check-label" for="opt-framework"><?php echo __('AHG Framework') ?></label>
                </div>
                <div class="form-check mb-2">
                  <input class="form-check-input" type="checkbox" name="fuseki" id="opt-fuseki">
                  <label class="form-check-label" for="opt-fuseki"><?php echo __('Fuseki/RIC') ?></label>
                </div>
              </div>
            </div>
          </div>
          
          <div class="mt-3">
            <a href="#" data-bs-toggle="collapse" data-bs-target="#customOptions" class="small">
              <i class="bi bi-gear me-1"></i><?php echo __('Show custom options') ?>
            </a>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo __('Cancel') ?></button>
          <button type="submit" class="btn btn-primary" id="btn-start-backup">
            <i class="bi bi-play-fill me-1"></i><?php echo __('Start Backup') ?>
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<style>
.preset-card {
    cursor: pointer;
    transition: all 0.2s;
    border: 2px solid transparent;
}
.preset-card:hover {
    border-color: var(--bs-primary);
    transform: translateY(-2px);
}
.preset-card.selected {
    border-color: var(--bs-primary);
    background-color: rgba(var(--bs-primary-rgb), 0.1);
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const presetCards = document.querySelectorAll('.preset-card');
    const presetInput = document.getElementById('backup-preset');
    
    // Select AHG by default
    document.querySelector('.preset-card[data-preset="ahg"]')?.classList.add('selected');
    
    presetCards.forEach(card => {
        card.addEventListener('click', function() {
            presetCards.forEach(c => c.classList.remove('selected'));
            this.classList.add('selected');
            presetInput.value = this.dataset.preset;
        });
    });
    
    // Form submit handling
    document.getElementById('createBackupForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const btn = document.getElementById('btn-start-backup');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Creating...';
        
        fetch(this.action, {
            method: 'POST',
            body: new FormData(this)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                window.location.reload();
            } else {
                alert('Backup failed: ' + (data.error || 'Unknown error'));
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-play-fill me-1"></i>Start Backup';
            }
        })
        .catch(error => {
            alert('Backup failed: ' + error.message);
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-play-fill me-1"></i>Start Backup';
        });
    });
});
</script>
