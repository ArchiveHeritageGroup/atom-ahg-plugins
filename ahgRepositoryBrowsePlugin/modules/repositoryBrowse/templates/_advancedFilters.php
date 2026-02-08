<form method="get">

  <?php foreach ($hiddenFields as $name => $value) { ?>
    <input type="hidden" name="<?php echo $name; ?>" value="<?php echo $value; ?>"/>
  <?php } ?>

  <div class="row mb-4">

    <div class="col-md-4">
      <label class="form-label" for="thematicAreas"><?php echo __('Thematic area'); ?></label>
      <select class="form-select" name="thematicAreas" id="thematicAreas">
        <option selected="selected"></option>
        <?php foreach ($thematicAreas as $id => $name) { ?>
          <option value="<?php echo $id; ?>"><?php echo htmlspecialchars($name); ?></option>
        <?php } ?>
      </select>
    </div>

    <div class="col-md-4">
      <label class="form-label" for="types"><?php echo __('Archive type'); ?></label>
      <select class="form-select" name="types" id="types">
        <option selected="selected"></option>
        <?php foreach ($repositoryTypes as $id => $name) { ?>
          <option value="<?php echo $id; ?>"><?php echo htmlspecialchars($name); ?></option>
        <?php } ?>
      </select>
    </div>

    <div class="col-md-4">
      <label class="form-label" for="regions"><?php echo __('Region'); ?></label>
      <select class="form-select" name="regions" id="regions">
        <option selected="selected"></option>
        <?php foreach ($regions as $region) { ?>
          <option value="<?php echo htmlspecialchars($region); ?>"><?php echo htmlspecialchars($region); ?></option>
        <?php } ?>
      </select>
    </div>

  </div>

  <ul class="actions mb-1 nav gap-2 justify-content-center">
    <li><input type="submit" class="btn atom-btn-outline-light" value="<?php echo __('Set filters'); ?>"></li>
  </ul>

</form>
