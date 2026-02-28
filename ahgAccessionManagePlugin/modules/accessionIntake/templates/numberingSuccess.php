<?php decorate_with('layout_1col'); ?>

<?php
  $rawDefaultMask = $sf_data->getRaw('defaultMask');
  $rawSequences   = $sf_data->getRaw('sequences');

  $defaultMask  = $rawDefaultMask ?: '{YEAR}-{SEQ:5}';
  $sequencesArr = is_array($rawSequences) ? $rawSequences : [];
?>

<?php slot('title'); ?>
  <h1><i class="fas fa-hashtag me-2"></i><?php echo __('Accession numbering'); ?></h1>
<?php end_slot(); ?>

<?php slot('before-content'); ?>
  <nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb">
      <li class="breadcrumb-item">
        <a href="<?php echo url_for('@accession_intake_queue'); ?>"><?php echo __('Intake queue'); ?></a>
      </li>
      <li class="breadcrumb-item">
        <a href="<?php echo url_for('@accession_intake_config'); ?>"><?php echo __('Configuration'); ?></a>
      </li>
      <li class="breadcrumb-item active"><?php echo __('Numbering'); ?></li>
    </ol>
  </nav>
<?php end_slot(); ?>

<?php slot('content'); ?>
  <!-- Default mask -->
  <div class="card mb-3">
    <div class="card-header">
      <i class="fas fa-cog me-1"></i><?php echo __('Default numbering mask'); ?>
    </div>
    <div class="card-body">
      <div class="row align-items-center">
        <div class="col-md-4">
          <code class="fs-5"><?php echo htmlspecialchars($defaultMask); ?></code>
        </div>
        <div class="col-md-8">
          <div class="text-muted small">
            <p class="mb-1"><?php echo __('Available tokens:'); ?></p>
            <ul class="mb-0">
              <li><code>{YEAR}</code> - <?php echo __('Current four-digit year (e.g., 2026)'); ?></li>
              <li><code>{SEQ:N}</code> - <?php echo __('Auto-incrementing sequence, zero-padded to N digits'); ?></li>
              <li><code>{REPO}</code> - <?php echo __('Repository code'); ?></li>
              <li><code>{MONTH}</code> - <?php echo __('Current two-digit month'); ?></li>
            </ul>
          </div>
        </div>
      </div>
      <div class="mt-2">
        <a href="<?php echo url_for('@accession_intake_config'); ?>" class="btn btn-sm btn-outline-secondary">
          <i class="fas fa-edit me-1"></i><?php echo __('Change default mask in Configuration'); ?>
        </a>
      </div>
    </div>
  </div>

  <!-- Per-repository sequences -->
  <div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
      <span><i class="fas fa-database me-1"></i><?php echo __('Per-repository numbering sequences'); ?></span>
      <span class="badge bg-secondary"><?php echo count($sequencesArr); ?> <?php echo __('sequence(s)'); ?></span>
    </div>
    <div class="card-body p-0">
      <?php if (count($sequencesArr) > 0): ?>
        <div class="table-responsive">
          <table class="table table-bordered table-hover mb-0">
            <thead class="table-light">
              <tr>
                <th><?php echo __('Repository'); ?></th>
                <th><?php echo __('Mask'); ?></th>
                <th class="text-center"><?php echo __('Last sequence'); ?></th>
                <th class="text-center"><?php echo __('Last year'); ?></th>
                <th><?php echo __('Preview (next number)'); ?></th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($sequencesArr as $seq): ?>
                <?php
                  $mask        = $seq->mask ?? $defaultMask;
                  $lastSeq     = (int) ($seq->last_sequence ?? 0);
                  $lastYear    = (int) ($seq->last_year ?? date('Y'));
                  $currentYear = (int) date('Y');

                  // Preview: if year changed, sequence resets to 1
                  $nextSeq = ($lastYear < $currentYear) ? 1 : $lastSeq + 1;

                  // Simple preview render
                  $preview = $mask;
                  $preview = str_replace('{YEAR}', (string) $currentYear, $preview);
                  $preview = str_replace('{MONTH}', date('m'), $preview);
                  $preview = str_replace('{REPO}', $seq->repo_name ?? '', $preview);
                  if (preg_match('/\{SEQ:(\d+)\}/', $preview, $m)) {
                      $preview = str_replace($m[0], str_pad((string) $nextSeq, (int) $m[1], '0', STR_PAD_LEFT), $preview);
                  }
                ?>
                <tr>
                  <td>
                    <?php if (!empty($seq->repo_name)): ?>
                      <i class="fas fa-building me-1 text-muted"></i><?php echo htmlspecialchars($seq->repo_name); ?>
                    <?php else: ?>
                      <span class="text-muted"><?php echo __('(Global / no repository)'); ?></span>
                    <?php endif; ?>
                  </td>
                  <td><code><?php echo htmlspecialchars($mask); ?></code></td>
                  <td class="text-center">
                    <span class="badge bg-secondary"><?php echo $lastSeq; ?></span>
                  </td>
                  <td class="text-center"><?php echo $lastYear; ?></td>
                  <td>
                    <code class="text-primary"><?php echo htmlspecialchars($preview); ?></code>
                    <?php if ($lastYear < $currentYear): ?>
                      <span class="badge bg-info ms-1" title="<?php echo __('Sequence will reset for new year'); ?>">
                        <?php echo __('Year reset'); ?>
                      </span>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php else: ?>
        <div class="text-center py-5 text-muted">
          <i class="fas fa-hashtag fa-3x mb-3"></i>
          <p class="mb-0"><?php echo __('No numbering sequences configured yet.'); ?></p>
          <p class="small"><?php echo __('Sequences are created automatically when accessions are assigned to repositories.'); ?></p>
        </div>
      <?php endif; ?>
    </div>
  </div>
<?php end_slot(); ?>

<?php slot('after-content'); ?>
  <section class="actions mb-3">
    <a href="<?php echo url_for('@accession_intake_config'); ?>" class="btn atom-btn-outline-light">
      <i class="fas fa-cog me-1"></i><?php echo __('Configuration'); ?>
    </a>
    <a href="<?php echo url_for('@accession_intake_queue'); ?>" class="btn atom-btn-outline-light">
      <i class="fas fa-arrow-left me-1"></i><?php echo __('Back to queue'); ?>
    </a>
  </section>
<?php end_slot(); ?>
