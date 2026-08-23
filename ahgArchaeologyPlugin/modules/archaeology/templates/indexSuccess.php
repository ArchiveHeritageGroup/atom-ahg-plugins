<?php decorate_with('layout_1col'); ?>

<?php slot('title'); ?>
  <h1>Archaeology</h1>
<?php end_slot(); ?>

<div class="ahg-archaeology-index">

  <div class="row g-3 mb-4">
    <?php
    $cards = [
        ['label' => 'Sites', 'value' => $stats['sites'], 'icon' => 'fas fa-map-location-dot'],
        ['label' => 'Excavated sites', 'value' => $stats['excavated_sites'], 'icon' => 'fas fa-trowel-bricks'],
        ['label' => 'Contexts', 'value' => $stats['contexts'], 'icon' => 'fas fa-layer-group'],
        ['label' => 'Finds', 'value' => $stats['finds'], 'icon' => 'fas fa-gem'],
    ];
    ?>
    <?php foreach ($cards as $card) { ?>
      <div class="col-6 col-lg-3">
        <div class="card h-100">
          <div class="card-body">
            <div class="text-muted small"><i class="<?php echo $card['icon']; ?> me-1"></i><?php echo esc_specialchars($card['label']); ?></div>
            <div class="fs-3"><?php echo number_format($card['value']); ?></div>
          </div>
        </div>
      </div>
    <?php } ?>
  </div>

  <?php if ($stats['finds'] > 0 && $stats['finds_with_context'] < $stats['finds']) { ?>
    <div class="alert alert-warning">
      <?php echo number_format($stats['finds'] - $stats['finds_with_context']); ?>
      of <?php echo number_format($stats['finds']); ?> finds are not linked to a context,
      so they cannot be listed as part of an assemblage.
    </div>
  <?php } ?>

  <div class="d-flex gap-2 mb-4">
    <a class="btn btn-primary" href="<?php echo url_for('@archaeology_sites'); ?>">Browse sites</a>
    <a class="btn btn-outline-secondary" href="<?php echo url_for('@archaeology_objects'); ?>">Browse finds</a>
    <a class="btn btn-outline-secondary" href="<?php echo url_for('@archaeology_map'); ?>">Site map</a>
    <a class="btn btn-outline-secondary" href="<?php echo url_for('@archaeology_site_add'); ?>">Add a site</a>
  </div>

  <h2 class="h5">Recent sites</h2>

  <?php if (!$recentSites) { ?>
    <div class="alert alert-info">No sites recorded yet.</div>
  <?php } else { ?>
    <table class="table table-sm table-striped">
      <thead>
        <tr>
          <th scope="col">Site number</th>
          <th scope="col">Name</th>
          <th scope="col">Region</th>
          <th scope="col">Excavated</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($recentSites as $site) { ?>
          <tr>
            <td><a href="<?php echo url_for('@archaeology_site?id='.$site->id); ?>"><?php echo esc_specialchars($site->site_number); ?></a></td>
            <td><?php echo esc_specialchars($site->title ?? ''); ?></td>
            <td><?php echo esc_specialchars($site->region ?? ''); ?></td>
            <td><?php echo $site->excavated ? 'Yes' : 'No'; ?></td>
          </tr>
        <?php } ?>
      </tbody>
    </table>
  <?php } ?>

</div>
