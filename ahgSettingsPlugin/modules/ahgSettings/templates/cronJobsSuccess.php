<?php decorate_with('layout_2col.php'); ?>

<?php slot('sidebar'); ?>
  <?php echo get_component('ahgSettings', 'menu'); ?>
<?php end_slot(); ?>

<?php slot('title'); ?>
  <h1>
    <i class="bi bi-clock-history me-2"></i>
    <?php echo __('Cron Jobs & System Info'); ?>
  </h1>
<?php end_slot(); ?>

<?php slot('content'); ?>

<?php
$categories = $sf_data->getRaw('categories');
$softwareVersions = $sf_data->getRaw('softwareVersions');
$atomRoot = $sf_data->getRaw('atomRoot');
?>

<style>
.cron-card {
  transition: all 0.2s ease;
}
.cron-card:hover {
  box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}
.command-box {
  background: #1e1e1e;
  color: #d4d4d4;
  padding: 12px 15px;
  border-radius: 6px;
  font-family: 'Consolas', 'Monaco', monospace;
  font-size: 0.875rem;
  overflow-x: auto;
}
.command-box code {
  color: #9cdcfe;
}
.option-badge {
  font-family: monospace;
  font-size: 0.8rem;
}
.duration-short { color: #198754; }
.duration-medium { color: #fd7e14; }
.duration-long { color: #dc3545; }
.software-card {
  border-left: 4px solid #0d6efd;
}
.software-version {
  font-family: monospace;
  font-weight: bold;
}
.copy-btn {
  cursor: pointer;
  opacity: 0.7;
}
.copy-btn:hover {
  opacity: 1;
}
</style>

<!-- Software Versions Section -->
<div class="card mb-4">
  <div class="card-header bg-primary text-white">
    <i class="bi bi-box-seam me-2"></i>
    <?php echo __('Installed Software & Versions'); ?>
  </div>
  <div class="card-body">
    <div class="row g-3">
      <?php foreach ($softwareVersions as $software): ?>
        <div class="col-md-4 col-lg-3">
          <div class="card software-card h-100">
            <div class="card-body py-2 px-3">
              <div class="d-flex justify-content-between align-items-center">
                <div>
                  <i class="<?php echo $software['icon']; ?> me-2 text-<?php echo $software['status'] === 'ok' ? 'success' : ($software['status'] === 'warning' ? 'warning' : 'danger'); ?>"></i>
                  <strong><?php echo $software['name']; ?></strong>
                </div>
                <?php if ($software['status'] !== 'ok'): ?>
                  <span class="badge bg-<?php echo $software['status'] === 'warning' ? 'warning' : 'danger'; ?>"><?php echo $software['status']; ?></span>
                <?php endif; ?>
              </div>
              <div class="software-version text-<?php echo $software['status'] === 'ok' ? 'success' : 'muted'; ?> mt-1">
                <?php echo $software['version']; ?>
              </div>
              <?php if (!empty($software['path'])): ?>
                <small class="text-muted d-block text-truncate" title="<?php echo htmlspecialchars($software['path']); ?>">
                  <?php echo htmlspecialchars($software['path']); ?>
                </small>
              <?php endif; ?>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<!-- Quick Reference -->
<div class="alert alert-info mb-4">
  <h5 class="alert-heading"><i class="bi bi-info-circle me-2"></i><?php echo __('Cron Syntax Reference'); ?></h5>
  <code class="d-block bg-dark text-light p-2 rounded mb-2">* * * * * command</code>
  <div class="row small">
    <div class="col-auto"><strong>1:</strong> Minute (0-59)</div>
    <div class="col-auto"><strong>2:</strong> Hour (0-23)</div>
    <div class="col-auto"><strong>3:</strong> Day (1-31)</div>
    <div class="col-auto"><strong>4:</strong> Month (1-12)</div>
    <div class="col-auto"><strong>5:</strong> Weekday (0-6, Sun=0)</div>
  </div>
  <hr>
  <small>
    <strong>AtoM Root:</strong> <code><?php echo $atomRoot; ?></code> &nbsp;|&nbsp;
    <strong>Common patterns:</strong>
    <code>*/5 * * * *</code> = every 5 min,
    <code>0 * * * *</code> = hourly,
    <code>0 2 * * *</code> = daily at 2am,
    <code>0 2 * * 0</code> = weekly Sunday 2am
  </small>
</div>

<!-- Cron Jobs by Category -->
<?php foreach ($categories as $catKey => $category): ?>
  <div class="card mb-4">
    <div class="card-header">
      <i class="<?php echo $category['icon']; ?> me-2"></i>
      <strong><?php echo __($category['title']); ?></strong>
      <span class="badge bg-secondary ms-2"><?php echo count($category['jobs']); ?></span>
    </div>
    <div class="card-body p-0">
      <div class="accordion accordion-flush" id="accordion-<?php echo $catKey; ?>">
        <?php foreach ($category['jobs'] as $idx => $job): ?>
          <?php $collapseId = "collapse-{$catKey}-{$idx}"; ?>
          <div class="accordion-item cron-card">
            <h2 class="accordion-header" id="heading-<?php echo $catKey; ?>-<?php echo $idx; ?>">
              <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#<?php echo $collapseId; ?>" aria-expanded="false" aria-controls="<?php echo $collapseId; ?>">
                <span class="me-3">
                  <strong><?php echo $job['name']; ?></strong>
                </span>
                <span class="badge bg-light text-dark me-2">
                  <?php
                  $durationClass = match($job['duration']) {
                    'Short' => 'duration-short',
                    'Medium', 'Medium to Long' => 'duration-medium',
                    default => 'duration-long'
                  };
                  ?>
                  <span class="<?php echo $durationClass; ?>"><?php echo $job['duration']; ?></span>
                </span>
              </button>
            </h2>
            <div id="<?php echo $collapseId; ?>" class="accordion-collapse collapse" aria-labelledby="heading-<?php echo $catKey; ?>-<?php echo $idx; ?>" data-bs-parent="#accordion-<?php echo $catKey; ?>">
              <div class="accordion-body">
                <p class="mb-3"><?php echo $job['description']; ?></p>

                <h6><i class="bi bi-terminal me-1"></i> Command</h6>
                <div class="command-box mb-3">
                  <code><?php echo htmlspecialchars($job['command']); ?></code>
                  <i class="bi bi-clipboard copy-btn float-end" onclick="copyToClipboard('<?php echo htmlspecialchars(addslashes($job['command'])); ?>')" title="Copy"></i>
                </div>

                <?php if (!empty($job['options'])): ?>
                  <h6><i class="bi bi-sliders me-1"></i> Options</h6>
                  <div class="mb-3">
                    <?php foreach ($job['options'] as $opt => $desc): ?>
                      <div class="mb-1">
                        <span class="badge bg-secondary option-badge"><?php echo htmlspecialchars($opt); ?></span>
                        <span class="small text-muted ms-2"><?php echo $desc; ?></span>
                      </div>
                    <?php endforeach; ?>
                  </div>
                <?php endif; ?>

                <h6><i class="bi bi-calendar-event me-1"></i> Recommended Schedule</h6>
                <p class="text-muted small mb-3"><?php echo $job['schedule']; ?></p>

                <h6><i class="bi bi-code-square me-1"></i> Example Cron Entry</h6>
                <div class="command-box">
                  <code><?php echo nl2br(htmlspecialchars(str_replace('{root}', $atomRoot, $job['example']))); ?></code>
                  <i class="bi bi-clipboard copy-btn float-end" onclick="copyToClipboard('<?php echo htmlspecialchars(addslashes(str_replace('{root}', $atomRoot, $job['example']))); ?>')" title="Copy"></i>
                </div>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
<?php endforeach; ?>

<!-- Current Crontab -->
<div class="card mb-4">
  <div class="card-header">
    <i class="bi bi-file-text me-2"></i>
    <?php echo __('View Current Crontab'); ?>
  </div>
  <div class="card-body">
    <p class="text-muted small">To view the current system crontab, run:</p>
    <div class="command-box mb-3">
      <code>sudo crontab -l</code>
    </div>
    <p class="text-muted small">To edit the crontab:</p>
    <div class="command-box">
      <code>sudo crontab -e</code>
    </div>
  </div>
</div>

<script>
function copyToClipboard(text) {
  navigator.clipboard.writeText(text).then(function() {
    // Show brief feedback
    const toast = document.createElement('div');
    toast.className = 'position-fixed bottom-0 end-0 p-3';
    toast.innerHTML = '<div class="toast show" role="alert"><div class="toast-body"><i class="bi bi-check-circle text-success me-2"></i>Copied to clipboard</div></div>';
    document.body.appendChild(toast);
    setTimeout(() => toast.remove(), 2000);
  });
}

// Manual accordion toggle that works with or without Bootstrap
document.addEventListener('DOMContentLoaded', function() {
  document.querySelectorAll('.accordion-button').forEach(function(button) {
    button.addEventListener('click', function(e) {
      e.preventDefault();
      e.stopPropagation();

      var targetSelector = this.getAttribute('data-bs-target');
      var target = document.querySelector(targetSelector);
      var accordion = this.closest('.accordion');

      if (!target) {
        console.error('Accordion target not found:', targetSelector);
        return;
      }

      var isCurrentlyOpen = target.classList.contains('show');

      // Close all other accordion items in the same accordion
      if (accordion) {
        accordion.querySelectorAll('.accordion-collapse.show').forEach(function(openPanel) {
          if (openPanel !== target) {
            openPanel.classList.remove('show');
            var openButton = accordion.querySelector('[data-bs-target="#' + openPanel.id + '"]');
            if (openButton) {
              openButton.classList.add('collapsed');
              openButton.setAttribute('aria-expanded', 'false');
            }
          }
        });
      }

      // Toggle the clicked item
      if (isCurrentlyOpen) {
        target.classList.remove('show');
        this.classList.add('collapsed');
        this.setAttribute('aria-expanded', 'false');
      } else {
        target.classList.add('show');
        this.classList.remove('collapsed');
        this.setAttribute('aria-expanded', 'true');
      }
    });
  });
});
</script>

<?php end_slot(); ?>
