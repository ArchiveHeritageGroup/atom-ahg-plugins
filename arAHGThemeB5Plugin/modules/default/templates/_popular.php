<section id="popular-this-week" class="card mb-3">
  <h2 class="h5 p-3 mb-0">
    <?php echo __('Popular this week'); ?>
  </h2>
  <div class="list-group list-group-flush">
    <?php foreach ($popularThisWeek as $item) { ?>
      <?php $object = QubitObject::getById($item[0]); ?>
      <?php if ($object instanceof QubitInformationObject): ?>
        <a class="list-group-item list-group-item-action d-flex justify-content-between align-items-center text-break"
           href="<?php echo url_for([$object, 'module' => 'informationobject']); ?>">
          <?php echo render_title($object); ?>
          <span class="ms-3 text-nowrap">
            <?php echo __('%1% visits', ['%1%' => $item[1]]); ?>
          </span>
        </a>
      <?php elseif ($object instanceof QubitRepository): ?>
        <a class="list-group-item list-group-item-action d-flex justify-content-between align-items-center text-break"
           href="<?php echo url_for([$object, 'module' => 'repository']); ?>">
          <?php echo render_title($object); ?>
          <span class="ms-3 text-nowrap">
            <?php echo __('%1% visits', ['%1%' => $item[1]]); ?>
          </span>
        </a>
      <?php elseif ($object instanceof QubitActor): ?>
        <a class="list-group-item list-group-item-action d-flex justify-content-between align-items-center text-break"
           href="<?php echo url_for([$object, 'module' => 'actor']); ?>">
          <?php echo render_title($object); ?>
          <span class="ms-3 text-nowrap">
            <?php echo __('%1% visits', ['%1%' => $item[1]]); ?>
          </span>
        </a>
      <?php elseif ($object): ?>
        <a class="list-group-item list-group-item-action d-flex justify-content-between align-items-center text-break"
           href="/index.php/<?php echo $object->slug; ?>">
          <?php echo render_title($object); ?>
          <span class="ms-3 text-nowrap">
            <?php echo __('%1% visits', ['%1%' => $item[1]]); ?>
          </span>
        </a>
      <?php endif ?>
    <?php } ?>
  </div>
</section>
