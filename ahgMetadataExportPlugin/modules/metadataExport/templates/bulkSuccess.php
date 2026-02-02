<?php use_helper('Form') ?>

<h1>
  <?php echo __('Bulk Export') ?>
  <small class="text-muted">- <?php echo $formatInfo['name'] ?></small>
</h1>

<nav aria-label="breadcrumb">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="<?php echo url_for('metadataExport/index') ?>"><?php echo __('Metadata Export') ?></a></li>
    <li class="breadcrumb-item active" aria-current="page"><?php echo __('Bulk Export') ?></li>
  </ol>
</nav>

<?php if ($sf_user->hasFlash('error')): ?>
  <div class="alert alert-danger">
    <i class="fa fa-exclamation-triangle"></i>
    <?php echo $sf_user->getFlash('error') ?>
  </div>
<?php endif; ?>

<?php if ($sf_user->hasFlash('success')): ?>
  <div class="alert alert-success">
    <i class="fa fa-check-circle"></i>
    <?php echo $sf_user->getFlash('success') ?>
  </div>
<?php endif; ?>

<div class="row">
  <div class="col-md-8">
    <div class="card">
      <div class="card-header">
        <h3 class="card-title mb-0"><?php echo __('Export Settings') ?></h3>
      </div>
      <div class="card-body">
        <form action="<?php echo url_for('metadataExport/bulk?format='.$format) ?>" method="post">

          <input type="hidden" name="format" value="<?php echo $format ?>">

          <div class="mb-3">
            <label for="repository_id" class="form-label"><?php echo __('Repository') ?> <span class="text-danger">*</span></label>
            <select name="repository_id" id="repository_id" class="form-select" required>
              <option value=""><?php echo __('Select repository...') ?></option>
              <?php foreach ($repositories as $repo): ?>
                <option value="<?php echo $repo->id ?>">
                  <?php echo $repo->authorizedFormOfName ?? $repo->slug ?>
                </option>
              <?php endforeach; ?>
            </select>
            <div class="form-text"><?php echo __('Select the repository to export records from') ?></div>
          </div>

          <hr>

          <h4><?php echo __('Export Options') ?></h4>

          <div class="mb-3">
            <div class="form-check">
              <input type="checkbox" name="include_children" value="1" id="include_children" class="form-check-input" checked>
              <label class="form-check-label" for="include_children">
                <?php echo __('Include child records') ?>
              </label>
              <div class="form-text"><?php echo __('Export the full hierarchy including all descendants') ?></div>
            </div>
          </div>

          <div class="mb-3">
            <div class="form-check">
              <input type="checkbox" name="include_digital_objects" value="1" id="include_digital_objects" class="form-check-input" checked>
              <label class="form-check-label" for="include_digital_objects">
                <?php echo __('Include digital objects') ?>
              </label>
              <div class="form-text"><?php echo __('Include references to attached digital objects') ?></div>
            </div>
          </div>

          <div class="mb-3">
            <div class="form-check">
              <input type="checkbox" name="include_drafts" value="1" id="include_drafts" class="form-check-input">
              <label class="form-check-label" for="include_drafts">
                <?php echo __('Include draft records') ?>
              </label>
              <div class="form-text"><?php echo __('Also export records with draft publication status') ?></div>
            </div>
          </div>

          <div class="mb-3">
            <label for="max_depth" class="form-label"><?php echo __('Maximum Depth') ?></label>
            <input type="number" name="max_depth" id="max_depth" class="form-control" value="0" min="0" max="99">
            <div class="form-text"><?php echo __('Limit hierarchy depth (0 = unlimited)') ?></div>
          </div>

          <?php if (in_array($format, ['rico', 'bibframe'])): ?>
            <hr>
            <h4><?php echo __('RDF Options') ?></h4>

            <div class="mb-3">
              <label for="rdf_format" class="form-label"><?php echo __('Output Format') ?></label>
              <select name="rdf_format" id="rdf_format" class="form-select">
                <option value="jsonld" selected><?php echo __('JSON-LD') ?></option>
                <option value="turtle"><?php echo __('Turtle') ?></option>
                <option value="rdfxml"><?php echo __('RDF/XML') ?></option>
                <option value="ntriples"><?php echo __('N-Triples') ?></option>
              </select>
            </div>
          <?php endif; ?>

          <hr>

          <div class="d-flex justify-content-between">
            <a href="<?php echo url_for('metadataExport/index') ?>" class="btn btn-secondary">
              <i class="fa fa-arrow-left"></i> <?php echo __('Back') ?>
            </a>
            <button type="submit" class="btn btn-primary">
              <i class="fa fa-download"></i> <?php echo __('Export as ZIP') ?>
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <div class="col-md-4">
    <div class="card">
      <div class="card-header">
        <h3 class="card-title mb-0"><?php echo __('Format Information') ?></h3>
      </div>
      <div class="card-body">
        <dl>
          <dt><?php echo __('Format') ?></dt>
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
      </div>
    </div>

    <div class="card mt-4">
      <div class="card-header">
        <h3 class="card-title mb-0"><?php echo __('Export Information') ?></h3>
      </div>
      <div class="card-body">
        <p class="small text-muted">
          <?php echo __('This will export all top-level records from the selected repository. Each record will be exported as a separate file, and all files will be packaged into a ZIP archive for download.') ?>
        </p>

        <div class="alert alert-info small">
          <i class="fa fa-info-circle"></i>
          <?php echo __('For very large repositories, consider using the CLI command for better performance.') ?>
        </div>

        <pre class="bg-light p-2 small"><code>php symfony metadata:export \
  --format=<?php echo $format ?> \
  --repository=ID \
  --output=/exports/</code></pre>
      </div>
    </div>
  </div>
</div>
