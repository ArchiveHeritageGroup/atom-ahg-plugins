<?php use_helper('Text') ?>

<h1>
  <?php echo __('Export Preview') ?>
  <small class="text-muted">- <?php echo $formatInfo['name'] ?></small>
</h1>

<nav aria-label="breadcrumb">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="<?php echo url_for('metadataExport/index') ?>"><?php echo __('Metadata Export') ?></a></li>
    <li class="breadcrumb-item active" aria-current="page"><?php echo __('Preview') ?></li>
  </ol>
</nav>

<div class="row">
  <div class="col-md-3">
    <div class="card">
      <div class="card-header">
        <h3 class="card-title mb-0"><?php echo __('Record Information') ?></h3>
      </div>
      <div class="card-body">
        <dl>
          <dt><?php echo __('Title') ?></dt>
          <dd><?php echo $resource->title ?? $resource->slug ?></dd>

          <dt><?php echo __('Identifier') ?></dt>
          <dd><?php echo $resource->identifier ?? '-' ?></dd>

          <dt><?php echo __('Level') ?></dt>
          <dd><?php echo $resource->levelOfDescription ?? '-' ?></dd>
        </dl>

        <hr>

        <dl>
          <dt><?php echo __('Export Format') ?></dt>
          <dd>
            <strong><?php echo $formatInfo['name'] ?></strong>
            <br>
            <small class="text-muted"><?php echo $formatInfo['description'] ?></small>
          </dd>

          <dt><?php echo __('Output Type') ?></dt>
          <dd><span class="badge bg-secondary"><?php echo $formatInfo['output'] ?></span></dd>

          <dt><?php echo __('Sector') ?></dt>
          <dd><?php echo $formatInfo['sector'] ?></dd>
        </dl>

        <hr>

        <a href="<?php echo url_for('metadataExport/download?format='.$format.'&slug='.$resource->slug) ?>"
           class="btn btn-primary w-100">
          <i class="fa fa-download"></i> <?php echo __('Download') ?>
        </a>
      </div>
    </div>
  </div>

  <div class="col-md-9">
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title mb-0"><?php echo __('Export Preview') ?></h3>
        <span class="badge bg-info"><?php echo $mimeType ?? 'text/plain' ?></span>
      </div>
      <div class="card-body">
        <?php if (isset($error)): ?>
          <div class="alert alert-danger">
            <i class="fa fa-exclamation-triangle"></i>
            <?php echo __('Export Error:') ?> <?php echo $error ?>
          </div>
        <?php elseif ($preview): ?>
          <pre class="bg-light p-3" style="max-height: 600px; overflow: auto;"><code><?php echo htmlspecialchars($preview) ?></code></pre>
        <?php else: ?>
          <div class="alert alert-warning">
            <?php echo __('No preview available.') ?>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>
