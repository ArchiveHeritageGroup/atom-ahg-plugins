<?php use_helper('Date') ?>

<h1><?php echo __('Metadata Export') ?></h1>

<div class="alert alert-info">
  <i class="fa fa-info-circle"></i>
  <?php echo __('Export archival descriptions to various international metadata standards.') ?>
</div>

<div class="row">
  <div class="col-md-8">
    <h2><?php echo __('Select Export Format') ?></h2>

    <?php foreach ($sectors as $sector => $formats): ?>
      <div class="card mb-4">
        <div class="card-header">
          <h3 class="card-title mb-0">
            <?php echo $sector ?>
            <small class="text-muted">(<?php echo count($formats) ?> <?php echo __('formats') ?>)</small>
          </h3>
        </div>
        <div class="card-body">
          <div class="row">
            <?php foreach ($formats as $code => $format): ?>
              <div class="col-md-4 mb-3">
                <div class="card h-100">
                  <div class="card-body">
                    <h5 class="card-title"><?php echo $format['name'] ?></h5>
                    <p class="card-text small text-muted">
                      <?php echo $format['description'] ?>
                    </p>
                    <p class="card-text">
                      <span class="badge bg-secondary"><?php echo $format['output'] ?></span>
                    </p>
                  </div>
                  <div class="card-footer bg-transparent border-0">
                    <div class="btn-group btn-group-sm w-100" role="group">
                      <a href="<?php echo url_for('metadataExport/bulk?format='.$code) ?>" class="btn btn-outline-primary">
                        <i class="fa fa-download"></i> <?php echo __('Bulk Export') ?>
                      </a>
                    </div>
                  </div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

  <div class="col-md-4">
    <div class="card">
      <div class="card-header">
        <h3 class="card-title mb-0"><?php echo __('Quick Export') ?></h3>
      </div>
      <div class="card-body">
        <form action="<?php echo url_for('metadataExport/download') ?>" method="get">
          <div class="mb-3">
            <label for="format" class="form-label"><?php echo __('Format') ?></label>
            <select name="format" id="format" class="form-select" required>
              <option value=""><?php echo __('Select format...') ?></option>
              <?php foreach ($sf_data->getRaw('sectors') as $sector => $formats): ?>
                <optgroup label="<?php echo $sector ?>">
                  <?php foreach ($formats as $code => $format): ?>
                    <option value="<?php echo $code ?>"><?php echo $format['name'] ?></option>
                  <?php endforeach; ?>
                </optgroup>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="mb-3">
            <label for="slug" class="form-label"><?php echo __('Record Slug') ?></label>
            <input type="text" name="slug" id="slug" class="form-control" required
                   placeholder="<?php echo __('e.g., my-fonds') ?>">
            <div class="form-text"><?php echo __('Enter the slug of the record to export') ?></div>
          </div>

          <div class="mb-3">
            <div class="form-check">
              <input type="checkbox" name="include_children" value="1" id="include_children" class="form-check-input" checked>
              <label class="form-check-label" for="include_children"><?php echo __('Include children') ?></label>
            </div>
            <div class="form-check">
              <input type="checkbox" name="include_digital_objects" value="1" id="include_digital_objects" class="form-check-input" checked>
              <label class="form-check-label" for="include_digital_objects"><?php echo __('Include digital objects') ?></label>
            </div>
          </div>

          <button type="submit" class="btn btn-primary w-100">
            <i class="fa fa-download"></i> <?php echo __('Export') ?>
          </button>
        </form>
      </div>
    </div>

    <?php if (!empty($recentExports)): ?>
      <div class="card mt-4">
        <div class="card-header">
          <h3 class="card-title mb-0"><?php echo __('Recent Exports') ?></h3>
        </div>
        <ul class="list-group list-group-flush">
          <?php foreach ($recentExports as $export): ?>
            <li class="list-group-item d-flex justify-content-between align-items-center">
              <span>
                <strong><?php echo strtoupper($export['format_code']) ?></strong>
                <br>
                <small class="text-muted">
                  <?php echo $export['resource_type'] ?>
                  <?php if ($export['resource_id']): ?>
                    #<?php echo $export['resource_id'] ?>
                  <?php endif; ?>
                </small>
              </span>
              <small class="text-muted">
                <?php echo format_date($export['created_at'], 'g') ?>
              </small>
            </li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <div class="card mt-4">
      <div class="card-header">
        <h3 class="card-title mb-0"><?php echo __('CLI Usage') ?></h3>
      </div>
      <div class="card-body">
        <p class="small text-muted"><?php echo __('For bulk exports, use the command line:') ?></p>
        <pre class="bg-light p-2 small"><code>php symfony metadata:export --format=ead3 --slug=my-fonds --output=/exports/</code></pre>
        <pre class="bg-light p-2 small"><code>php symfony metadata:export --list</code></pre>
      </div>
    </div>
  </div>
</div>
