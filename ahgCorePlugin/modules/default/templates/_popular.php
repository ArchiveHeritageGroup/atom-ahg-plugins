<section id="popular-this-week" class="card mb-3">
  <h2 class="h5 p-3 mb-0">
    <?php echo __('Popular this week'); ?>
  </h2>
  <div class="list-group list-group-flush">
    <?php foreach ($popularThisWeek as $item) { ?>
      <?php $object = QubitObject::getById($item[0]); ?>
      <?php if (null === $object) { continue; } ?>
      <?php
      // Not every object has a title. Repositories, actors and functions are
      // QubitActor descendants and carry authorizedFormOfName; asking them for
      // ->title throws "Unknown record property" from BaseRepository::__isset()
      // and takes the whole home page down with a 500.
      //
      // The ?? operator does not help: the property does not merely resolve to
      // null, the magic getter raises. So the type has to be asked first.
      //
      // Found on a clean AtoM with only ahgCorePlugin enabled. Instances with
      // more plugins were unaffected only because no repository happened to
      // appear in the popular list.
      $label = '';

      if ($object instanceof QubitActor || $object instanceof QubitRepository) {
          $label = (string) $object->authorizedFormOfName;
      } elseif (isset($object->title)) {
          $label = (string) $object->title;
      }

      if ('' === trim($label)) {
          $label = (string) ($object->slug ?? '');
      }

      // The module has to be named. url_for([$object]) supplies no module and no
      // action, and sfRoute::matchesParameters() fills both in from whichever
      // route it is testing - so the first route in the collection with no
      // variables of its own matches, and the object is serialised into its query
      // string. On a clean AtoM every link in this list rendered as
      // /ahg/rights/embargo/edit/?0%5BdisableNestedSetUpdating%5D=0 the moment a
      // plugin owning a static route was enabled, and moved to a different wrong
      // route as the enabled set changed.
      //
      // Repository extends Actor, so it must be tested first. Anything unrecognised
      // is skipped rather than linked somewhere arbitrary.
      $module = null;

      if ($object instanceof QubitInformationObject) {
          $module = 'informationobject';
      } elseif ($object instanceof QubitRepository) {
          $module = 'repository';
      } elseif ($object instanceof QubitActor) {
          $module = 'actor';
      } elseif ($object instanceof QubitFunctionObject) {
          $module = 'function';
      } elseif ($object instanceof QubitTerm) {
          $module = 'term';
      } elseif ($object instanceof QubitAccession) {
          $module = 'accession';
      }

      if (null === $module) {
          continue;
      }
      ?>
      <a
        class="list-group-item list-group-item-action d-flex justify-content-between align-items-center text-break"
        href="<?php echo url_for([$object, 'module' => $module]); ?>">
        <?php echo $label; ?>
        <span class="ms-3 text-nowrap">
          <?php echo __('%1% visits', ['%1%' => $item[1]]); ?>
        </span>
      </a>
    <?php } ?>
  </div>
</section>
