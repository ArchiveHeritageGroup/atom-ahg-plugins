<?php decorate_with('layout_1col'); ?>

<?php slot('title'); ?>
  <h1><?php echo __('Sort the records below "%1%"', ['%1%' => esc_entities($resource->getTitle(['cultureFallback' => true]))]); ?></h1>
<?php end_slot(); ?>

<?php slot('content'); ?>
  <?php if ($error) { ?>
    <div class="alert alert-danger"><?php echo esc_entities($error); ?></div>
  <?php } ?>

  <p><?php echo __('Every level below this description is put in the chosen order, all at once. Nothing outside it moves.'); ?></p>

  <form method="get" action="<?php echo url_for('@io_sort_children?slug='.$resource->slug); ?>" class="row g-2 align-items-end mb-4">
    <div class="col-auto">
      <label class="form-label" for="key"><?php echo __('Sort by'); ?></label>
      <select class="form-select" id="key" name="key">
        <?php foreach (['identifier' => __('Identifier (2 before 10)'), 'title' => __('Title'), 'date' => __('Earliest date')] as $v => $l) { ?>
          <option value="<?php echo $v; ?>" <?php echo $v === $key ? 'selected' : ''; ?>><?php echo $l; ?></option>
        <?php } ?>
      </select>
    </div>
    <div class="col-auto">
      <label class="form-label" for="direction"><?php echo __('Order'); ?></label>
      <select class="form-select" id="direction" name="direction">
        <option value="asc" <?php echo 'asc' === $direction ? 'selected' : ''; ?>><?php echo __('Ascending'); ?></option>
        <option value="desc" <?php echo 'desc' === $direction ? 'selected' : ''; ?>><?php echo __('Descending'); ?></option>
      </select>
    </div>
    <div class="col-auto">
      <input class="btn atom-btn-outline-light" type="submit" value="<?php echo __('Preview'); ?>">
    </div>
  </form>

  <?php if ($plan) { ?>
    <?php $raw = $sf_data->getRaw('plan'); ?>
    <p>
      <?php echo __('%1% record(s) in this branch; %2% would move.', ['%1%' => (int) $raw['records'], '%2%' => (int) $raw['moved']]); ?>
      <?php echo __('New order of the first level:'); ?>
    </p>
    <table class="table table-bordered table-sm">
      <thead><tr><th><?php echo __('New'); ?></th><th><?php echo __('Was'); ?></th><th><?php echo __('Identifier'); ?></th><th><?php echo __('Title'); ?></th><th><?php echo __('Earliest date'); ?></th></tr></thead>
      <tbody>
        <?php foreach ($raw['children'] as $row) { ?>
          <tr<?php echo $row['from'] !== $row['to'] ? ' class="table-warning"' : ''; ?>>
            <td><?php echo (int) $row['to']; ?></td>
            <td><?php echo (int) $row['from']; ?></td>
            <td><?php echo esc_entities($row['identifier']); ?></td>
            <td><?php echo esc_entities($row['title']); ?></td>
            <td><?php echo esc_entities((string) $row['date']); ?></td>
          </tr>
        <?php } ?>
      </tbody>
    </table>

    <?php if ($raw['moved'] > 0) { ?>
      <form method="post" action="<?php echo url_for('@io_sort_children?slug='.$resource->slug); ?>">
        <input type="hidden" name="key" value="<?php echo esc_entities($key); ?>">
        <input type="hidden" name="direction" value="<?php echo esc_entities($direction); ?>">
        <ul class="actions mb-3 nav gap-2">
          <li><a class="btn atom-btn-outline-light" href="<?php echo url_for(['module' => 'informationobject', 'slug' => $resource->slug]); ?>"><?php echo __('Cancel'); ?></a></li>
          <li><input class="btn atom-btn-outline-success" type="submit" value="<?php echo __('Sort'); ?>"></li>
        </ul>
      </form>
    <?php } else { ?>
      <div class="alert alert-info"><?php echo __('Already in this order.'); ?></div>
    <?php } ?>
  <?php } ?>
<?php end_slot(); ?>
