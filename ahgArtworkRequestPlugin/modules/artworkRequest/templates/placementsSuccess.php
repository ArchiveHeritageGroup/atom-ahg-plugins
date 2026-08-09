<?php decorate_with('layout_1col') ?>
<?php slot('title') ?>
  <h1><?php echo $overdueOnly ? __('Overdue artworks') : __('Artworks out on campus') ?></h1>
<?php end_slot() ?>

<p class="btn-group btn-group-sm">
  <a class="btn btn-outline-secondary<?php echo $overdueOnly ? '' : ' active' ?>"
     href="<?php echo url_for(['module' => 'artworkRequest', 'action' => 'placements']) ?>"><?php echo __('All') ?></a>
  <a class="btn btn-outline-danger<?php echo $overdueOnly ? ' active' : '' ?>"
     href="<?php echo url_for(['module' => 'artworkRequest', 'action' => 'placements']).'?overdue=1' ?>"><?php echo __('Overdue') ?></a>
</p>

<?php if (!$placements): ?>
  <div class="alert alert-info"><?php echo $overdueOnly ? __('Nothing overdue.') : __('Nothing out at the moment.') ?></div>
<?php else: ?>
  <table class="table table-sm align-middle">
    <thead><tr>
      <th><?php echo __('Work') ?></th>
      <th><?php echo __('With') ?></th>
      <th><?php echo __('Where') ?></th>
      <th><?php echo __('Due back') ?></th>
      <th><?php echo __('Request') ?></th>
    </tr></thead>
    <tbody>
    <?php foreach ($placements as $p): ?>
      <?php $late = $p->requested_to && $p->requested_to < $today; ?>
      <tr class="<?php echo $late ? 'table-danger' : '' ?>">
        <td>
          <?php echo esc_specialchars($p->object_title ?: __('untitled')) ?>
          <?php if ($p->object_identifier): ?>
            <span class="text-muted small"><?php echo esc_specialchars($p->object_identifier) ?></span>
          <?php endif ?>
        </td>
        <td>
          <?php echo esc_specialchars($p->placement_occupant ?: $p->requester_name) ?>
          <?php if ($p->department): ?><span class="text-muted small"><?php echo esc_specialchars($p->department) ?></span><?php endif ?>
        </td>
        <td><?php echo esc_specialchars(trim($p->placement_building.' '.$p->placement_room)) ?></td>
        <td>
          <?php echo esc_specialchars($p->requested_to) ?>
          <?php if ($late): ?><strong><?php echo __('overdue') ?></strong><?php endif ?>
        </td>
        <td><?php echo esc_specialchars($p->request_number) ?></td>
      </tr>
    <?php endforeach ?>
    </tbody>
  </table>
<?php endif ?>
