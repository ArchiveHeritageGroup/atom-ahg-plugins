<?php
/**
 * Watermark Trace Template.
 */
?>

<h1><i class="fas fa-search"></i> <?php echo __('Trace Watermark') ?></h1>

<div class="card mb-4">
  <div class="card-body">
    <form method="get" action="/security/watermark/">
      <div class="row">
        <div class="col-md-8">
          <input type="text" name="code" class="form-control" 
                 placeholder="<?php echo __('Enter watermark code (12 characters)') ?>"
                 value="<?php echo esc_entities($searchCode ?? '') ?>" pattern="[A-Z0-9]{12}">
        </div>
        <div class="col-md-4">
          <button type="submit" class="btn btn-primary w-100">
            <i class="fas fa-search"></i> <?php echo __('Trace') ?>
          </button>
        </div>
      </div>
    </form>
  </div>
</div>

<?php if (isset($watermark)): ?>
  <?php if ($watermark): ?>
  <div class="card">
    <div class="card-header bg-success text-white">
      <h5 class="mb-0"><i class="fas fa-check-circle"></i> <?php echo __('Watermark Found') ?></h5>
    </div>
    <div class="card-body">
      <table class="table table-borderless">
        <tr>
          <th width="25%"><?php echo __('Watermark Code') ?></th>
          <td><code><?php echo esc_entities($watermark->watermark_code) ?></code></td>
        </tr>
        <tr>
          <th><?php echo __('Downloaded By') ?></th>
          <td>
            <a href="/security/audit/user/<?php echo $watermark->user_id ?>">
              <?php echo esc_entities($watermark->username) ?>
            </a>
            (<?php echo esc_entities($watermark->email) ?>)
          </td>
        </tr>
        <tr>
          <th><?php echo __('Object') ?></th>
          <td>
            <?php if ($watermark->object_id): ?>
            <a href="/security/audit/object/<?php echo $watermark->object_id ?>">
              <?php echo esc_entities($watermark->object_title ?? 'ID: '.$watermark->object_id) ?>
            </a>
            <?php else: ?>
            -
            <?php endif ?>
          </td>
        </tr>
        <tr>
          <th><?php echo __('Download Date') ?></th>
          <td><?php echo date('Y-m-d H:i:s', strtotime($watermark->created_at)) ?></td>
        </tr>
        <tr>
          <th><?php echo __('IP Address') ?></th>
          <td><?php echo esc_entities($watermark->ip_address ?? '-') ?></td>
        </tr>
        <tr>
          <th><?php echo __('Watermark Text') ?></th>
          <td><code><?php echo esc_entities($watermark->watermark_text) ?></code></td>
        </tr>
        <?php if ($watermark->file_hash): ?>
        <tr>
          <th><?php echo __('File Hash') ?></th>
          <td><code><?php echo esc_entities($watermark->file_hash) ?></code></td>
        </tr>
        <?php endif ?>
      </table>
    </div>
  </div>
  <?php else: ?>
  <div class="alert alert-warning">
    <i class="fas fa-exclamation-triangle"></i>
    <?php echo __('No watermark found with code:') ?> <code><?php echo esc_entities($searchCode) ?></code>
  </div>
  <?php endif ?>
<?php endif ?>
