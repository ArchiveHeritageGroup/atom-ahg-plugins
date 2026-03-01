<?php decorate_with('layout_1col'); ?>

<?php
  $rawStats = $sf_data->getRaw('stats');
  $stats = is_array($rawStats) ? $rawStats : (array) $rawStats;
?>

<?php slot('title'); ?>
  <h1><i class="fas fa-clone me-2"></i><?php echo __('Authority Deduplication'); ?></h1>
<?php end_slot(); ?>

<?php slot('before-content'); ?>
  <nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb">
      <li class="breadcrumb-item">
        <a href="<?php echo url_for('@ahg_authority_dashboard'); ?>"><?php echo __('Authority Dashboard'); ?></a>
      </li>
      <li class="breadcrumb-item active"><?php echo __('Deduplication'); ?></li>
    </ol>
  </nav>
<?php end_slot(); ?>

<?php slot('content'); ?>

  <div class="row g-3 mb-4">
    <div class="col-md-3">
      <div class="card text-center">
        <div class="card-body">
          <h3><?php echo number_format($stats['total_actors'] ?? 0); ?></h3>
          <small class="text-muted"><?php echo __('Total Actors'); ?></small>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card text-center">
        <div class="card-body">
          <h3><?php echo $stats['threshold'] ?? 0.80; ?></h3>
          <small class="text-muted"><?php echo __('Threshold'); ?></small>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card text-center">
        <div class="card-body">
          <h3><?php echo number_format($stats['pending'] ?? 0); ?></h3>
          <small class="text-muted"><?php echo __('Pending'); ?></small>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card text-center">
        <div class="card-body">
          <h3><?php echo number_format($stats['completed'] ?? 0); ?></h3>
          <small class="text-muted"><?php echo __('Completed Merges'); ?></small>
        </div>
      </div>
    </div>
  </div>

  <div class="card mb-3">
    <div class="card-header">
      <i class="fas fa-search me-1"></i><?php echo __('Run Dedup Scan'); ?>
    </div>
    <div class="card-body">
      <form method="post" action="<?php echo url_for('@ahg_authority_dedup_scan'); ?>">
        <div class="row g-2 align-items-end">
          <div class="col-md-3">
            <label class="form-label"><?php echo __('Max actors to compare'); ?></label>
            <input type="number" name="limit" class="form-control" value="500" min="10" max="5000">
          </div>
          <div class="col-auto">
            <button type="submit" class="btn btn-warning">
              <i class="fas fa-search me-1"></i><?php echo __('Start Scan'); ?>
            </button>
          </div>
        </div>
        <div class="form-text"><?php echo __('Scans actor names using Jaro-Winkler similarity. May take time for large datasets.'); ?></div>
      </form>
    </div>
  </div>

  <div class="card mb-3">
    <div class="card-header">
      <i class="fas fa-terminal me-1"></i><?php echo __('CLI Scan'); ?>
    </div>
    <div class="card-body">
      <p class="text-muted"><?php echo __('For large datasets, run the dedup scan via CLI:'); ?></p>
      <pre class="bg-dark text-light p-3 rounded"><code>php symfony authority:dedup-scan --limit=5000</code></pre>
    </div>
  </div>

<?php end_slot(); ?>
