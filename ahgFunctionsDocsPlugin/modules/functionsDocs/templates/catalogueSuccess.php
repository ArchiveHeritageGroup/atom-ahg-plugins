<?php
/* #148 — Routes / CLI tasks / services catalogue. */
$n = sfConfig::get('csp_nonce', '');
$nonceAttr = $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : '';
?>
<div class="container-fluid px-4 py-3 functions-docs">
  <div class="d-flex flex-wrap align-items-baseline mb-3 gap-2">
    <h1 class="h3 mb-0 flex-grow-1"><i class="fas fa-sitemap me-2"></i><?php echo __('System catalogue') ?></h1>
    <div class="text-muted small">
      <?php echo (int) $counts['routes'] ?> <?php echo __('routes') ?> ·
      <?php echo (int) $counts['tasks'] ?> <?php echo __('tasks') ?> ·
      <?php echo (int) $counts['services'] ?> <?php echo __('services') ?>
    </div>
  </div>
  <p class="text-muted small"><?php echo __('Auto-generated from the live routing table and the plugin/framework source. Read-only developer reference.') ?></p>

  <input type="text" id="cat-filter" class="form-control mb-3" placeholder="<?php echo __('Filter everything…') ?>" autocomplete="off">

  <ul class="nav nav-tabs" role="tablist">
    <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-routes" type="button"><?php echo __('Routes') ?></button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-tasks" type="button"><?php echo __('CLI tasks') ?></button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-services" type="button"><?php echo __('Services') ?></button></li>
  </ul>

  <div class="tab-content border border-top-0 rounded-bottom p-0">
    <div class="tab-pane fade show active" id="tab-routes">
      <div class="table-responsive"><table class="table table-sm table-hover mb-0 small cat-table">
        <thead class="table-light"><tr><th><?php echo __('Pattern') ?></th><th><?php echo __('Name') ?></th><th><?php echo __('Module') ?></th><th><?php echo __('Action') ?></th></tr></thead>
        <tbody>
        <?php foreach ($routes as $r): ?>
          <tr><td><code><?php echo esc_entities($r['pattern']) ?></code></td><td><?php echo esc_entities($r['name']) ?></td><td><?php echo esc_entities($r['module']) ?></td><td><?php echo esc_entities($r['action']) ?></td></tr>
        <?php endforeach ?>
        </tbody>
      </table></div>
    </div>

    <div class="tab-pane fade" id="tab-tasks">
      <div class="table-responsive"><table class="table table-sm table-hover mb-0 small cat-table">
        <thead class="table-light"><tr><th><?php echo __('Command') ?></th><th><?php echo __('Description') ?></th><th><?php echo __('Source') ?></th></tr></thead>
        <tbody>
        <?php foreach ($tasks as $t): ?>
          <tr><td><code><?php echo esc_entities($t['command']) ?></code></td><td><?php echo esc_entities($t['description']) ?></td><td><small class="text-muted"><?php echo esc_entities($t['source']) ?></small></td></tr>
        <?php endforeach ?>
        </tbody>
      </table></div>
    </div>

    <div class="tab-pane fade" id="tab-services">
      <div class="table-responsive"><table class="table table-sm table-hover mb-0 small cat-table">
        <thead class="table-light"><tr><th><?php echo __('Class') ?></th><th><?php echo __('Description') ?></th><th><?php echo __('Source') ?></th></tr></thead>
        <tbody>
        <?php foreach ($services as $s): ?>
          <tr><td><code><?php echo esc_entities($s['class']) ?></code></td><td><?php echo esc_entities($s['description']) ?></td><td><small class="text-muted"><?php echo esc_entities($s['source']) ?></small></td></tr>
        <?php endforeach ?>
        </tbody>
      </table></div>
    </div>
  </div>
</div>

<script <?php echo $nonceAttr ?>>
(function () {
  var box = document.getElementById('cat-filter');
  if (!box) { return; }
  box.addEventListener('input', function () {
    var q = box.value.toLowerCase();
    document.querySelectorAll('.cat-table tbody tr').forEach(function (tr) {
      tr.style.display = (tr.textContent.toLowerCase().indexOf(q) !== -1) ? '' : 'none';
    });
  });
})();
</script>
