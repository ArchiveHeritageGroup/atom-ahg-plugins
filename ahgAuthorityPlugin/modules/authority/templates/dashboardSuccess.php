<?php decorate_with('layout_1col'); ?>

<?php
  $rawStats           = $sf_data->getRaw('stats');
  $rawIdentifierStats = $sf_data->getRaw('identifierStats');

  $stats     = is_array($rawStats) ? $rawStats : (array) $rawStats;
  $idStats   = is_array($rawIdentifierStats) ? $rawIdentifierStats : [];

  $byLevel = $stats['by_level'] ?? [];
  $totalActors = $stats['total_actors'] ?? 0;
  $totalScored = $stats['total_scored'] ?? 0;
  $avgScore = $stats['avg_score'] ?? 0;
  $unscored = $stats['unscored'] ?? 0;
?>

<?php slot('title'); ?>
  <h1><i class="fas fa-id-card me-2"></i><?php echo __('Authority Records Dashboard'); ?></h1>
<?php end_slot(); ?>

<?php slot('before-content'); ?>
  <nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb">
      <li class="breadcrumb-item active"><?php echo __('Authority Dashboard'); ?></li>
    </ol>
  </nav>
<?php end_slot(); ?>

<?php slot('content'); ?>

  <!-- KPI Cards -->
  <div class="row g-3 mb-4">
    <div class="col-md-3">
      <div class="card text-center border-primary">
        <div class="card-body">
          <h3 class="mb-0"><?php echo number_format($totalActors); ?></h3>
          <small class="text-muted"><?php echo __('Total Actors'); ?></small>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card text-center border-info">
        <div class="card-body">
          <h3 class="mb-0"><?php echo number_format($totalScored); ?></h3>
          <small class="text-muted"><?php echo __('Scored'); ?></small>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card text-center border-warning">
        <div class="card-body">
          <h3 class="mb-0"><?php echo number_format($unscored); ?></h3>
          <small class="text-muted"><?php echo __('Unscored'); ?></small>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card text-center border-success">
        <div class="card-body">
          <h3 class="mb-0"><?php echo $avgScore; ?>%</h3>
          <small class="text-muted"><?php echo __('Average Score'); ?></small>
        </div>
      </div>
    </div>
  </div>

  <!-- Completeness by Level -->
  <div class="row g-3 mb-4">
    <div class="col-md-6">
      <div class="card">
        <div class="card-header">
          <i class="fas fa-chart-pie me-1"></i><?php echo __('Completeness by Level'); ?>
        </div>
        <div class="card-body">
          <canvas id="completenessChart" height="250"></canvas>
          <div class="mt-3">
            <?php
              $levelColors = ['stub' => 'danger', 'minimal' => 'warning', 'partial' => 'info', 'full' => 'success'];
              foreach (['stub', 'minimal', 'partial', 'full'] as $level):
                $count = isset($byLevel[$level]) ? $byLevel[$level]->count : 0;
            ?>
              <div class="d-flex justify-content-between mb-1">
                <span>
                  <span class="badge bg-<?php echo $levelColors[$level]; ?>"><?php echo ucfirst($level); ?></span>
                </span>
                <strong><?php echo number_format($count); ?></strong>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
    </div>

    <div class="col-md-6">
      <div class="card">
        <div class="card-header">
          <i class="fas fa-link me-1"></i><?php echo __('External Identifiers'); ?>
        </div>
        <div class="card-body">
          <?php if (empty($idStats)): ?>
            <p class="text-muted"><?php echo __('No external identifiers recorded yet.'); ?></p>
          <?php else: ?>
            <table class="table table-sm">
              <thead>
                <tr>
                  <th><?php echo __('Source'); ?></th>
                  <th class="text-end"><?php echo __('Count'); ?></th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($idStats as $stat): ?>
                  <tr>
                    <td>
                      <i class="fas fa-external-link-alt me-1 text-muted"></i>
                      <?php echo htmlspecialchars(ucfirst($stat->identifier_type)); ?>
                    </td>
                    <td class="text-end"><?php echo number_format($stat->count); ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>

  <!-- Quick Actions -->
  <div class="card mb-3">
    <div class="card-header">
      <i class="fas fa-bolt me-1"></i><?php echo __('Quick Actions'); ?>
    </div>
    <div class="card-body">
      <div class="row g-2">
        <div class="col-auto">
          <a href="<?php echo url_for('@ahg_authority_workqueue'); ?>" class="btn btn-outline-primary">
            <i class="fas fa-tasks me-1"></i><?php echo __('Workqueue'); ?>
          </a>
        </div>
        <div class="col-auto">
          <a href="<?php echo url_for('@ahg_authority_dedup'); ?>" class="btn btn-outline-warning">
            <i class="fas fa-clone me-1"></i><?php echo __('Deduplication'); ?>
          </a>
        </div>
        <div class="col-auto">
          <a href="<?php echo url_for('@ahg_authority_ner_pipeline'); ?>" class="btn btn-outline-info">
            <i class="fas fa-robot me-1"></i><?php echo __('NER Pipeline'); ?>
          </a>
        </div>
        <div class="col-auto">
          <a href="<?php echo url_for('@ahg_authority_function_browse'); ?>" class="btn btn-outline-secondary">
            <i class="fas fa-sitemap me-1"></i><?php echo __('Functions Browse'); ?>
          </a>
        </div>
        <div class="col-auto">
          <a href="<?php echo url_for('@ahg_authority_config'); ?>" class="btn btn-outline-dark">
            <i class="fas fa-cog me-1"></i><?php echo __('Configuration'); ?>
          </a>
        </div>
      </div>
    </div>
  </div>

<?php end_slot(); ?>

<script <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
document.addEventListener('DOMContentLoaded', function() {
  if (typeof Chart !== 'undefined') {
    var ctx = document.getElementById('completenessChart');
    if (ctx) {
      new Chart(ctx.getContext('2d'), {
        type: 'doughnut',
        data: {
          labels: ['Stub', 'Minimal', 'Partial', 'Full'],
          datasets: [{
            data: [
              <?php echo isset($byLevel['stub']) ? $byLevel['stub']->count : 0; ?>,
              <?php echo isset($byLevel['minimal']) ? $byLevel['minimal']->count : 0; ?>,
              <?php echo isset($byLevel['partial']) ? $byLevel['partial']->count : 0; ?>,
              <?php echo isset($byLevel['full']) ? $byLevel['full']->count : 0; ?>
            ],
            backgroundColor: ['#dc3545', '#ffc107', '#0dcaf0', '#198754']
          }]
        },
        options: { responsive: true, plugins: { legend: { position: 'bottom' } } }
      });
    }
  }
});
</script>
