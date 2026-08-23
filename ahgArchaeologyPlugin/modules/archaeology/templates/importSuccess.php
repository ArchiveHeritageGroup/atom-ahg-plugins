<?php decorate_with('layout_1col'); ?>

<?php slot('title'); ?>
  <h1>Import contexts</h1>
  <p class="text-muted mb-0"><?php echo esc_specialchars($site->title ?: $site->site_number); ?></p>
<?php end_slot(); ?>

<div class="ahg-archaeology-import">

  <div class="d-flex gap-2 mb-3">
    <a class="btn btn-outline-secondary btn-sm" href="<?php echo url_for('@archaeology_contexts?siteId='.$site->id); ?>">Back to stratigraphy</a>
    <a class="btn btn-outline-secondary btn-sm" href="<?php echo url_for('@archaeology_import_template?siteId='.$site->id); ?>">Download template</a>
  </div>

  <?php if ($error) { ?>
    <div class="alert alert-danger"><?php echo esc_specialchars($error); ?></div>
  <?php } ?>

  <?php if ($summary) { ?>
    <div class="alert <?php echo $summary['committed'] ? 'alert-success' : 'alert-info'; ?>">
      <strong>
        <?php echo $summary['committed'] ? 'Imported.' : 'Preview only - nothing was written.'; ?>
      </strong>
      <?php echo (int) $rowCount; ?> row<?php echo 1 === (int) $rowCount ? '' : 's'; ?> read.
      <?php echo (int) $summary['created']; ?> context<?php echo 1 === (int) $summary['created'] ? '' : 's'; ?> created,
      <?php echo (int) $summary['updated']; ?> updated,
      <?php echo (int) $summary['relationships']; ?> relationship<?php echo 1 === (int) $summary['relationships'] ? '' : 's'; ?> recorded.
    </div>

    <?php if ($summary['warnings']) { ?>
      <details class="mb-3" open>
        <summary><?php echo count($summary['warnings']); ?> warning<?php echo 1 === count($summary['warnings']) ? '' : 's'; ?></summary>
        <ul class="mt-2 small">
          <?php foreach ($summary['warnings'] as $warning) { ?>
            <li><?php echo esc_specialchars($warning); ?></li>
          <?php } ?>
        </ul>
      </details>
    <?php } else { ?>
      <p class="text-muted small">No warnings.</p>
    <?php } ?>
  <?php } ?>

  <form method="post" enctype="multipart/form-data"
        action="<?php echo url_for('@archaeology_import?siteId='.$site->id); ?>">

    <?php // No CSRF field - ahgCorePlugin injects it. See contextEditSuccess.php. ?>

    <div class="mb-3">
      <label class="form-label" for="csv">Spreadsheet (CSV)</label>
      <input type="file" class="form-control" id="csv" name="csv" accept=".csv,text/csv" required>
      <div class="form-text">
        One row per context. <code>context_number</code> is the only required column.
        Relationship columns (<code>above</code>, <code>below</code>, <code>cuts</code>,
        <code>cut_by</code>, <code>fills</code>, <code>filled_by</code>, <code>same_as</code>,
        <code>bonds_with</code>, <code>abuts</code>) each take one or more other context
        numbers, separated by commas or semicolons.
      </div>
    </div>

    <div class="form-check mb-3">
      <input class="form-check-input" type="checkbox" value="1" id="commit" name="commit">
      <label class="form-check-label" for="commit">Save changes</label>
      <div class="form-text">
        Leave unticked to preview. A preview runs the whole import and rolls it back, so the
        counts and warnings are the ones a save would produce, not an estimate.
      </div>
    </div>

    <button type="submit" class="btn btn-primary">Run import</button>
  </form>

  <h2 class="h6 mt-4">How it behaves</h2>
  <ul class="small text-muted">
    <li>Contexts are matched on context number within this site, so re-importing a corrected
        sheet updates in place rather than creating duplicates.</li>
    <li>Context type and phase are matched by name against this site's vocabularies, case
        insensitively. An unrecognised value is left blank and reported.</li>
    <li>Relationships are written through the same code as the form, so reciprocals are
        created automatically and a relationship that would make the sequence impossible is
        refused and reported.</li>
    <li>A row may name a context defined further down the same file.</li>
  </ul>

</div>
