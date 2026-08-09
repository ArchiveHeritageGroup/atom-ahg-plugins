<?php decorate_with('layout_1col') ?>
<?php slot('title') ?>
  <h1><?php echo __('My artwork requests') ?></h1>
<?php end_slot() ?>

<p>
  <a class="btn btn-primary btn-sm" href="<?php echo url_for(['module' => 'artworkRequest', 'action' => 'request']) ?>">
    <?php echo __('Request an artwork') ?>
  </a>
</p>

<?php if (!$requests): ?>
  <div class="alert alert-info"><?php echo __('You have not asked for anything yet.') ?></div>
<?php else: ?>
  <table class="table table-sm align-middle">
    <thead><tr>
      <th><?php echo __('Request') ?></th><th><?php echo __('Works') ?></th>
      <th><?php echo __('Period') ?></th><th><?php echo __('Status') ?></th>
    </tr></thead>
    <tbody>
    <?php foreach ($requests as $r): ?>
      <tr>
        <td>
          <a href="<?php echo url_for(['module' => 'artworkRequest', 'action' => 'view', 'id' => $r->id]) ?>">
            <?php echo esc_specialchars($r->request_number) ?>
          </a>
        </td>
        <td class="small">
          <?php foreach ($works[$r->id] as $w): ?>
            <div><?php echo esc_specialchars($w->object_title ?: '#'.$w->information_object_id) ?>
              <span class="text-muted">- <?php echo esc_specialchars($w->status) ?></span></div>
          <?php endforeach ?>
        </td>
        <td><?php echo esc_specialchars($r->requested_from) ?> - <?php echo esc_specialchars($r->requested_to) ?></td>
        <td><span class="badge bg-secondary"><?php echo esc_specialchars($r->status) ?></span></td>
      </tr>
    <?php endforeach ?>
    </tbody>
  </table>
<?php endif ?>
