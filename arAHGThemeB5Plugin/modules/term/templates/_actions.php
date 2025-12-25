<?php if (\AtomExtensions\Services\AclService::check($resource->taxonomy, 'createTerm') || (\AtomExtensions\Services\AclService::check($resource, ['update', 'delete', 'translate']) && !QubitTerm::isProtected($resource->id))) { ?>
  <ul class="actions mb-3 nav gap-2">

    <?php if ((\AtomExtensions\Services\AclService::check($resource, 'update') || \AtomExtensions\Services\AclService::check($resource, 'translate')) && !QubitTerm::isProtected($resource->id)) { ?>
      <li><?php echo link_to(__('Edit'), [$resource, 'module' => 'term', 'action' => 'edit'], ['class' => 'btn atom-btn-outline-light']); ?></li>
    <?php } ?>

    <?php if (\AtomExtensions\Services\AclService::check($resource, 'delete') && !QubitTerm::isProtected($resource->id)) { ?>
      <li><?php echo link_to(__('Delete'), [$resource, 'module' => 'term', 'action' => 'delete'], ['class' => 'btn atom-btn-outline-danger']); ?></li>
    <?php } ?>

    <?php if (\AtomExtensions\Services\AclService::check($resource->taxonomy, 'createTerm')) { ?>
      <li><?php echo link_to(__('Add new'), ['module' => 'term', 'action' => 'add', 'parent' => url_for([$resource, 'module' => 'term']), 'taxonomy' => url_for([$resource->taxonomy, 'module' => 'taxonomy'])], ['class' => 'btn atom-btn-outline-light']); ?></li>
    <?php } ?>

  </ul>
<?php } ?>
