<?php decorate_with('layout_1col'); ?>

<?php slot('title'); ?>
  <h1>Archaeological sites</h1>
<?php end_slot(); ?>

<?php $pages = (int) ceil($total / max(1, $perPage)); ?>

<div class="ahg-archaeology-sites">

  <form method="get" action="<?php echo url_for('@archaeology_sites'); ?>" class="row g-2 mb-3">
    <div class="col-md-6">
      <label class="form-label" for="q">Search</label>
      <input type="text" class="form-control" id="q" name="q"
             value="<?php echo esc_specialchars($q); ?>"
             placeholder="Site number, national number, locality or name">
    </div>
    <div class="col-md-3">
      <label class="form-label" for="excavated">Excavated</label>
      <select class="form-select" id="excavated" name="excavated">
        <option value="">Any</option>
        <option value="1" <?php echo '1' === (string) $excavated ? 'selected' : ''; ?>>Excavated</option>
        <option value="0" <?php echo '0' === (string) $excavated ? 'selected' : ''; ?>>Not excavated</option>
      </select>
    </div>
    <div class="col-md-3 d-flex align-items-end gap-2">
      <button type="submit" class="btn btn-primary">Search</button>
      <a class="btn btn-outline-secondary" href="<?php echo url_for('@archaeology_map'); ?>">Map</a>
      <a class="btn btn-outline-secondary" href="<?php echo url_for('@archaeology_site_add'); ?>">Add</a>
    </div>
  </form>

  <p class="text-muted"><?php echo number_format($total); ?> site<?php echo 1 === $total ? '' : 's'; ?></p>

  <?php if (!$sites) { ?>
    <div class="alert alert-info">No sites match.</div>
  <?php } else { ?>
    <table class="table table-striped table-sm">
      <thead>
        <tr>
          <th scope="col">Site number</th>
          <th scope="col">Name</th>
          <th scope="col">Type</th>
          <th scope="col">Period</th>
          <th scope="col">Region</th>
          <th scope="col">Excavated</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($sites as $site) { ?>
          <tr>
            <td><a href="<?php echo url_for('@archaeology_site?id='.$site->id); ?>"><?php echo esc_specialchars($site->site_number); ?></a></td>
            <td><?php echo esc_specialchars($site->title ?? ''); ?></td>
            <td><?php echo esc_specialchars($site->site_type_name ?? ''); ?></td>
            <td><?php echo esc_specialchars($site->period_name ?? ''); ?></td>
            <td><?php echo esc_specialchars($site->region ?? ''); ?></td>
            <td><?php echo $site->excavated ? 'Yes' : 'No'; ?></td>
          </tr>
        <?php } ?>
      </tbody>
    </table>

    <?php if ($pages > 1) { ?>
      <nav>
        <ul class="pagination pagination-sm">
          <?php for ($p = 1; $p <= $pages; ++$p) { ?>
            <li class="page-item <?php echo $p === $page ? 'active' : ''; ?>">
              <a class="page-link" href="<?php echo url_for('@archaeology_sites').'?page='.$p.'&q='.urlencode($q); ?>"><?php echo $p; ?></a>
            </li>
          <?php } ?>
        </ul>
      </nav>
    <?php } ?>
  <?php } ?>

</div>
