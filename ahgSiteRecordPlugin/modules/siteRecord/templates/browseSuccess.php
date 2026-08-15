<?php decorate_with('layout_1col'); ?>

<?php slot('title'); ?>
  <h1>Site records</h1>
<?php end_slot(); ?>

<?php
$pages = (int) ceil($total / max(1, $perPage));
?>

<div class="ahg-site-record-browse">

  <form method="get" action="<?php echo url_for('@site_record_browse'); ?>" class="row g-2 mb-3">
    <div class="col-md-5">
      <label class="form-label" for="q">Search</label>
      <input type="text" class="form-control" id="q" name="q"
             value="<?php echo esc_specialchars($q); ?>"
             placeholder="Site number or name">
    </div>
    <div class="col-md-4">
      <label class="form-label" for="region">Region</label>
      <select class="form-select" id="region" name="region">
        <option value="">All regions</option>
        <?php foreach ($regionChoices as $code => $label) { ?>
          <option value="<?php echo esc_specialchars($code); ?>"
            <?php echo $region === $code ? 'selected' : ''; ?>><?php echo esc_specialchars($label); ?></option>
        <?php } ?>
      </select>
    </div>
    <div class="col-md-3 d-flex align-items-end">
      <button type="submit" class="btn btn-primary">Search</button>
    </div>
  </form>

  <p class="text-muted"><?php echo number_format($total); ?> site record<?php echo 1 === $total ? '' : 's'; ?></p>

  <?php if (!$records) { ?>
    <div class="alert alert-info">No site records match.</div>
  <?php } else { ?>
    <table class="table table-striped table-sm">
      <thead>
        <tr>
          <th scope="col">Site number</th>
          <th scope="col">Name</th>
          <th scope="col">Region</th>
          <th scope="col">Date visited</th>
          <th scope="col">Location</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($records as $r) { ?>
          <tr>
            <td>
              <a href="<?php echo url_for('@site_record_view?id='.$r->id); ?>">
                <?php echo esc_specialchars($r->site_number ?: '(none)'); ?>
              </a>
            </td>
            <td><?php echo esc_specialchars($r->site_name ?? ''); ?></td>
            <td><?php echo esc_specialchars($service_->label('site_region', $r->region_code ?? null)); ?></td>
            <td><?php echo esc_specialchars($r->date_visited ?? ''); ?></td>
            <td>
              <?php if (!$r->locality['has_coordinates']) { ?>
                <span class="text-muted">-</span>
              <?php } elseif ($r->locality['exact']) { ?>
                <?php echo esc_specialchars(sprintf('%.5F, %.5F', $r->locality['latitude'], $r->locality['longitude'])); ?>
              <?php } else { ?>
                <span class="badge bg-secondary">approximate</span>
              <?php } ?>
            </td>
          </tr>
        <?php } ?>
      </tbody>
    </table>

    <?php if ($pages > 1) { ?>
      <nav aria-label="Site record pages">
        <ul class="pagination pagination-sm">
          <?php for ($p = 1; $p <= $pages; ++$p) { ?>
            <li class="page-item <?php echo $p === $page ? 'active' : ''; ?>">
              <a class="page-link"
                 href="<?php echo url_for('@site_record_browse?page='.$p.'&q='.urlencode($q).'&region='.urlencode((string) $region)); ?>"><?php echo $p; ?></a>
            </li>
          <?php } ?>
        </ul>
      </nav>
    <?php } ?>
  <?php } ?>
</div>
