<?php decorate_with('layout_1col'); ?>

<?php slot('title'); ?>
  <h1><?php echo __('Move "%1%" to another taxonomy', ['%1%' => esc_entities($resource->getName(['cultureFallback' => true]))]); ?></h1>
<?php end_slot(); ?>

<?php slot('content'); ?>
  <?php if ($error) { ?>
    <div class="alert alert-danger"><?php echo esc_entities($error); ?></div>
  <?php } ?>

  <?php if (!$movable) { ?>
    <div class="alert alert-warning"><?php echo __('This term cannot be moved: AtoM relies on it or on its taxonomy.'); ?></div>
  <?php } else { ?>
    <p>
      <?php echo __('Now in: %1%', ['%1%' => '<strong>'.esc_entities($resource->taxonomy->getName(['cultureFallback' => true])).'</strong>']); ?><br>
      <?php echo __('This moves %1% term(s): this one and every term below it. %2% description(s) use them and keep their links.', ['%1%' => (int) $impact['terms'], '%2%' => (int) $impact['descriptions']]); ?>
    </p>

    <form method="post" action="<?php echo url_for('@term_move?slug='.$resource->slug); ?>">
      <div class="mb-3">
        <label class="form-label" for="taxonomy_id"><?php echo __('Move to'); ?></label>
        <select class="form-select" id="taxonomy_id" name="taxonomy_id" required>
          <option value=""><?php echo __('Choose a taxonomy'); ?></option>
          <?php foreach ($sf_data->getRaw('targets') as $id => $name) { ?>
            <option value="<?php echo (int) $id; ?>"><?php echo esc_entities($name); ?></option>
          <?php } ?>
        </select>
        <div class="form-text"><?php echo __('The term goes to the top level of the chosen taxonomy.'); ?></div>
      </div>
      <ul class="actions mb-3 nav gap-2">
        <li><?php echo link_to(__('Cancel'), url_for(['module' => 'term', 'slug' => $resource->slug]), ['class' => 'btn atom-btn-outline-light']); ?></li>
        <li><input class="btn atom-btn-outline-success" type="submit" value="<?php echo __('Move'); ?>"></li>
      </ul>
    </form>
  <?php } ?>
<?php end_slot(); ?>
