<?php decorate_with('layout_1col'); ?>

<?php slot('title'); ?>
  <h1>Finds</h1>
<?php end_slot(); ?>

<?php $pages = (int) ceil($total / max(1, $perPage)); ?>

<div class="ahg-archaeology-finds">

  <form method="get" action="<?php echo url_for('@archaeology_objects'); ?>" class="row g-2 align-items-end mb-3">
    <div class="col-md-4">
      <label class="form-label" for="q">Search</label>
      <input type="text" class="form-control" id="q" name="q"
             value="<?php echo esc_specialchars($q); ?>"
             placeholder="Accession number, title or storage location">
    </div>
    <div class="col-md-3">
      <label class="form-label" for="site_id">Site</label>
      <select class="form-select" id="site_id" name="site_id">
        <option value="">All sites</option>
        <?php foreach ($siteChoices as $s) { ?>
          <option value="<?php echo (int) $s->id; ?>"<?php echo (int) $siteId === (int) $s->id ? ' selected' : ''; ?>>
            <?php echo esc_specialchars($s->site_number); ?>
          </option>
        <?php } ?>
      </select>
    </div>
    <div class="col-md-3">
      <div class="form-check">
        <input class="form-check-input" type="checkbox" value="1" id="no_context" name="no_context"
          <?php echo $noContext ? ' checked' : ''; ?>>
        <label class="form-check-label" for="no_context">Not linked to a context</label>
      </div>
      <div class="form-text">The provenance backlog.</div>
    </div>
    <div class="col-md-2 d-flex gap-2">
      <button type="submit" class="btn btn-primary">Search</button>
      <a class="btn btn-outline-secondary" href="<?php echo url_for('@archaeology_object_add'); ?>">Add</a>
    </div>
  </form>

  <p class="text-muted"><?php echo number_format($total); ?> find<?php echo 1 === $total ? '' : 's'; ?></p>

  <?php if (!$finds) { ?>
    <div class="alert alert-info">No finds match.</div>
  <?php } else { ?>
    <table class="table table-striped table-sm">
      <thead>
        <tr>
          <th scope="col">Accession</th>
          <th scope="col">Description</th>
          <th scope="col">Site</th>
          <th scope="col">Context</th>
          <th scope="col">Type</th>
          <th scope="col">Material</th>
          <th scope="col">Count</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($finds as $find) { ?>
          <tr>
            <td><a href="<?php echo url_for('@archaeology_object?id='.$find->id); ?>"><?php echo esc_specialchars($find->accession_number); ?></a></td>
            <td><?php echo esc_specialchars($find->title ?? ''); ?></td>
            <td><?php echo esc_specialchars($find->site_number ?? ''); ?></td>
            <td>
              <?php if ($find->context_number) { ?>
                <?php echo esc_specialchars($find->context_number); ?>
              <?php } else { ?>
                <span class="text-warning">not linked</span>
              <?php } ?>
            </td>
            <td><?php echo esc_specialchars($find->object_type_name ?? ''); ?></td>
            <td><?php echo esc_specialchars($find->material_name ?? ''); ?></td>
            <td><?php echo number_format((int) $find->item_count); ?></td>
          </tr>
        <?php } ?>
      </tbody>
    </table>

    <?php if ($pages > 1) { ?>
      <nav>
        <ul class="pagination pagination-sm">
          <?php for ($p = 1; $p <= $pages; ++$p) { ?>
            <li class="page-item <?php echo $p === $page ? 'active' : ''; ?>">
              <a class="page-link" href="<?php echo url_for('@archaeology_objects').'?page='.$p.'&q='.urlencode($q); ?>"><?php echo $p; ?></a>
            </li>
          <?php } ?>
        </ul>
      </nav>
    <?php } ?>
  <?php } ?>

</div>
