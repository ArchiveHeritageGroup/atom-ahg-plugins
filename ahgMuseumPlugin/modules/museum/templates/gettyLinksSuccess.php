<?php decorate_with('layout_1col.php'); ?>

<?php slot('title'); ?>
  <h1><?php echo __('Getty Vocabulary Links'); ?></h1>
<?php end_slot(); ?>

<?php slot('content'); ?>

<!-- Statistics -->
<div class="row mb-4">
  <div class="col-md-3">
    <div class="card text-center">
      <div class="card-body">
        <h3><?php echo $statistics['total'] ?? 0; ?></h3>
        <p class="mb-0"><?php echo __('Total Links'); ?></p>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="card text-center bg-success text-white">
      <div class="card-body">
        <h3><?php echo $statistics['confirmed'] ?? 0; ?></h3>
        <p class="mb-0"><?php echo __('Confirmed'); ?></p>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="card text-center bg-warning">
      <div class="card-body">
        <h3><?php echo $statistics['pending'] ?? 0; ?></h3>
        <p class="mb-0"><?php echo __('Pending'); ?></p>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="card text-center bg-info text-white">
      <div class="card-body">
        <h3><?php echo $statistics['suggested'] ?? 0; ?></h3>
        <p class="mb-0"><?php echo __('Suggested'); ?></p>
      </div>
    </div>
  </div>
</div>

<!-- Vocabulary breakdown -->
<div class="row mb-4">
  <div class="col-md-4">
    <div class="card">
      <div class="card-body text-center">
        <h4><?php echo $statistics['aat'] ?? 0; ?></h4>
        <p class="mb-0">AAT (Art & Architecture)</p>
      </div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="card">
      <div class="card-body text-center">
        <h4><?php echo $statistics['tgn'] ?? 0; ?></h4>
        <p class="mb-0">TGN (Geographic Names)</p>
      </div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="card">
      <div class="card-body text-center">
        <h4><?php echo $statistics['ulan'] ?? 0; ?></h4>
        <p class="mb-0">ULAN (Artist Names)</p>
      </div>
    </div>
  </div>
</div>

<!-- Filters -->
<div class="card mb-4">
  <div class="card-body">
    <form method="get" class="row g-3">
      <div class="col-md-3">
        <label class="form-label"><?php echo __('Vocabulary'); ?></label>
        <select name="vocabulary" class="form-select">
          <option value=""><?php echo __('All'); ?></option>
          <option value="aat">AAT</option>
          <option value="tgn">TGN</option>
          <option value="ulan">ULAN</option>
        </select>
      </div>
      <div class="col-md-3">
        <label class="form-label"><?php echo __('Status'); ?></label>
        <select name="status" class="form-select">
          <option value=""><?php echo __('All'); ?></option>
          <option value="pending">Pending</option>
          <option value="confirmed">Confirmed</option>
          <option value="suggested">Suggested</option>
          <option value="rejected">Rejected</option>
        </select>
      </div>
      <div class="col-md-4">
        <label class="form-label"><?php echo __('Search'); ?></label>
        <input type="text" name="search" class="form-control" placeholder="<?php echo __('Search terms...'); ?>">
      </div>
      <div class="col-md-2 d-flex align-items-end">
        <button type="submit" class="btn btn-primary w-100"><?php echo __('Filter'); ?></button>
      </div>
    </form>
  </div>
</div>

<!-- Links table -->
<?php if (empty($links)): ?>
  <div class="alert alert-info">
    <?php echo __('No Getty vocabulary links found. Use the CLI command to link terms:'); ?>
    <code>php symfony :getty-link</code>
  </div>
<?php else: ?>
  <table class="table table-striped">
    <thead>
      <tr>
        <th><?php echo __('Term'); ?></th>
        <th><?php echo __('Vocabulary'); ?></th>
        <th><?php echo __('Getty Label'); ?></th>
        <th><?php echo __('Status'); ?></th>
        <th><?php echo __('Confidence'); ?></th>
        <th><?php echo __('Actions'); ?></th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($links as $link): ?>
      <tr>
        <td><?php echo $link->term_name ?? 'Term #'.$link->term_id; ?></td>
        <td>
          <span class="badge bg-secondary"><?php echo strtoupper($link->vocabulary); ?></span>
        </td>
        <td>
          <a href="<?php echo $link->getty_uri; ?>" target="_blank">
            <?php echo $link->getty_pref_label; ?>
          </a>
        </td>
        <td>
          <?php
          $statusColors = [
            'confirmed' => 'success',
            'pending' => 'warning',
            'suggested' => 'info',
            'rejected' => 'danger',
          ];
          $color = $statusColors[$link->status] ?? 'secondary';
          ?>
          <span class="badge bg-<?php echo $color; ?>"><?php echo ucfirst($link->status); ?></span>
        </td>
        <td><?php echo number_format($link->confidence * 100, 0); ?>%</td>
        <td>
          <?php if ($link->status !== 'confirmed'): ?>
          <form method="post" style="display: inline;">
            <input type="hidden" name="link_id" value="<?php echo $link->id; ?>">
            <button type="submit" name="action" value="confirm" class="btn btn-sm btn-success">
              <?php echo __('Confirm'); ?>
            </button>
          </form>
          <?php endif; ?>
          <?php if ($link->status !== 'rejected'): ?>
          <form method="post" style="display: inline;">
            <input type="hidden" name="link_id" value="<?php echo $link->id; ?>">
            <button type="submit" name="action" value="reject" class="btn btn-sm btn-danger">
              <?php echo __('Reject'); ?>
            </button>
          </form>
          <?php endif; ?>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
<?php endif; ?>

<?php end_slot(); ?>
