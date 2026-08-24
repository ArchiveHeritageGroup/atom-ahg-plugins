<?php decorate_with('layout_1col'); ?>

<?php slot('title'); ?>
  <h1>Stratigraphy</h1>
  <p class="text-muted mb-0"><?php echo esc_specialchars($site->title ?: $site->site_number); ?></p>
<?php end_slot(); ?>

<div class="ahg-archaeology-stratigraphy">

  <div class="d-flex gap-2 mb-3">
    <a class="btn btn-outline-secondary btn-sm" href="<?php echo url_for('@archaeology_site?id='.$site->id); ?>">Back to site</a>
    <a class="btn btn-outline-secondary btn-sm" href="<?php echo url_for('@archaeology_plan?siteId='.$site->id); ?>">Dig plan and map</a>
    <a class="btn btn-outline-secondary btn-sm" href="<?php echo url_for('@archaeology_import?siteId='.$site->id); ?>">Import CSV</a>
    <a class="btn btn-primary btn-sm" href="<?php echo url_for('@archaeology_context_add').'?siteId='.$site->id; ?>">Add context</a>

    <?php // Export is only meaningful once there is a sequence to export. ?>
    <?php if ($contexts) { ?>
      <div class="dropdown">
        <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button"
                id="export-menu" data-bs-toggle="dropdown" aria-expanded="false">Export</button>
        <ul class="dropdown-menu" aria-labelledby="export-menu">
          <li>
            <a class="dropdown-item" href="<?php echo url_for('@archaeology_export?siteId='.$site->id.'&format=datapackage'); ?>">
              Data package (zip)
            </a>
          </li>
          <li>
            <a class="dropdown-item" href="<?php echo url_for('@archaeology_export?siteId='.$site->id.'&format=dot'); ?>">
              GraphViz DOT
            </a>
          </li>
          <li>
            <a class="dropdown-item" href="<?php echo url_for('@archaeology_export?siteId='.$site->id.'&format=phaser'); ?>">
              Phaser CSV
            </a>
          </li>
        </ul>
      </div>
    <?php } ?>
  </div>

  <?php if (!$contexts) { ?>
    <div class="alert alert-info">No contexts recorded for this site.</div>
  <?php } else { ?>

    <p class="text-muted">
      <?php echo $matrix['context_count']; ?> context<?php echo 1 === $matrix['context_count'] ? '' : 's'; ?>,
      <?php echo $matrix['relationship_count']; ?> stratigraphic relationship<?php echo 1 === $matrix['relationship_count'] ? '' : 's'; ?>.
      Cuts and interfaces are shown in square brackets, deposits and fills in round brackets.
      <?php
      // Say when the drawing is a reduction of what was recorded. A matrix that
      // silently shows fewer edges than the record holds would leave a reader
      // unable to tell a correct reduction from lost data.
      $redundant = (int) ($matrix['redundant_count'] ?? 0);
      if ($redundant > 0) { ?>
        <?php echo $redundant; ?> relationship<?php echo 1 === $redundant ? ' is' : 's are'; ?>
        implied by a longer path and <?php echo 1 === $redundant ? 'is' : 'are'; ?> not drawn;
        only immediate relationships appear, as the method requires.
        Nothing recorded has been deleted.
      <?php } ?>
    </p>

    <h2 class="h5">Harris Matrix</h2>

    <?php if ($matrix['has_cycle']) { ?>
      <div class="alert alert-danger">
        The recorded sequence contains a loop, so it cannot be laid out. A context is both
        earlier and later than another. Review the relationships below before relying on this.
      </div>
    <?php } elseif (!$matrix['tiers'] || 0 === $matrix['relationship_count']) { ?>
      <div class="alert alert-info">
        No stratigraphic relationships recorded yet, so there is no sequence to draw.
        Open a context and record what it lies above, below, cuts or fills.
      </div>
    <?php } else { ?>

      <div class="border rounded p-3 mb-3 overflow-auto">
        <?php $tierCount = count($matrix['tiers']); $i = 0; ?>
        <?php foreach ($matrix['tiers'] as $level => $groups) { ?>
          <div class="d-flex flex-wrap justify-content-center gap-3 mb-2">
            <?php foreach ($groups as $members) { ?>
              <?php
              // Contexts recorded as the same event were merged into one node, so
              // both numbers show in a single box rather than appearing twice.
              $labels = [];
              foreach ($members as $member) {
                  $labels[] = $service_->contextLabel($member).($member->type_name ? ' '.$member->type_name : '');
              }
              $first = $members[0];
              ?>
              <a class="btn btn-sm <?php echo count($members) > 1 ? 'btn-outline-primary' : 'btn-outline-dark'; ?>"
                 href="<?php echo url_for('@archaeology_context?id='.$first->id); ?>">
                <?php echo esc_specialchars(implode(' = ', $labels)); ?>
              </a>
            <?php } ?>
          </div>
          <?php if (++$i < $tierCount) { ?>
            <div class="text-center text-muted mb-2">|</div>
          <?php } ?>
        <?php } ?>
        <p class="text-center text-muted small mb-0">latest at the top, earliest at the bottom</p>
      </div>

      <details class="mb-4">
        <summary class="text-muted small">Mermaid source, for redrawing elsewhere</summary>
        <pre class="small border rounded p-2 mt-2 mb-0"><?php echo esc_specialchars($matrix['mermaid']); ?></pre>
      </details>

    <?php } ?>

    <h2 class="h5">Contexts</h2>

    <table class="table table-sm table-striped">
      <thead>
        <tr>
          <th scope="col">Context</th>
          <th scope="col">Type</th>
          <th scope="col">Phase</th>
          <th scope="col">Top (m)</th>
          <th scope="col">Bottom (m)</th>
          <th scope="col">Trench / spit</th>
          <th scope="col">Excavator</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($contexts as $row) { ?>
          <tr>
            <td>
              <a href="<?php echo url_for('@archaeology_context?id='.$row->id); ?>">
                <?php echo esc_specialchars($service_->contextLabel($row)); ?>
              </a>
            </td>
            <td><?php echo esc_specialchars($row->type_name ?? ''); ?></td>
            <td><?php echo esc_specialchars($row->phase_name ?? ''); ?></td>
            <td><?php echo null === $row->top_elevation_m ? '' : esc_specialchars((string) $row->top_elevation_m); ?></td>
            <td><?php echo null === $row->bottom_elevation_m ? '' : esc_specialchars((string) $row->bottom_elevation_m); ?></td>
            <td><?php echo esc_specialchars($row->excavation_reference ?? ''); ?></td>
            <td><?php echo esc_specialchars($row->excavator ?? ''); ?></td>
          </tr>
        <?php } ?>
      </tbody>
    </table>

  <?php } ?>

</div>
