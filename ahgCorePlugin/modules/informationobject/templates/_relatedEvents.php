<?php $sf_response->addJavaScript('multiDelete', 'last'); ?>
<?php // Rules moved out of style attributes: a CSP nonce covers <style>
      // elements and never an attribute, so under an enforcing policy every one
      // of these was dropped silently.
      $cspNonce = sfConfig::get('csp_nonce', ''); ?>
<style <?php echo $cspNonce ? preg_replace('/^nonce=/', 'nonce="', $cspNonce).'"' : ''; ?>>
  .core-text-align-right-28bb { text-align: right; }
  .core-width-10-828e { width: 10%; }
  .core-width-20-4341 { width: 20%; }
  .core-width-25-3ea1 { width: 25%; }
</style>

<table id="relatedEvents" class="table table-bordered">
  <thead>
    <tr>
      <th class="core-width-25-3ea1">
        <?php echo __('Name'); ?>
      </th><th class="core-width-20-4341">
        <?php echo __('Role/event'); ?>
      </th><th class="core-width-20-4341">
        <?php echo __('Place'); ?>
      </th><th class="core-width-25-3ea1">
        <?php echo __('Date(s)'); ?>
      </th><th class="core-width-10-828e">
        &nbsp;
      </th>
    </tr>
  </thead><tbody>
    <?php foreach ($resource->eventsRelatedByobjectId as $item) { ?>
      <tr class="<?php echo 0 == @++$row % 2 ? 'even' : 'odd'; ?> related_obj_<?php echo $item->id; ?>" id="<?php echo url_for([$item, 'module' => 'event']); ?>">
        <td>
          <div>
            <?php if (isset($item->actor)) { ?>
              <?php echo render_title($item->actor); ?>
            <?php } ?>
          </div>
        </td><td>
          <div>
            <?php echo render_value_inline($item->type); ?>
          </div>
        </td><td>
          <div>
            <?php if (null !== $relation = QubitObjectTermRelation::getOneByObjectId($item->id)) { ?>
              <?php echo render_value_inline($relation->term); ?>
            <?php } ?>
          </div>
        </td><td>
          <div>
            <?php echo render_value_inline(Qubit::renderDateStartEnd($item->getDate(['cultureFallback' => true]), $item->startDate, $item->endDate)); ?>
          </div>
        </td><td class="core-text-align-right-28bb">
          <input class="multiDelete" name="deleteEvents[]" type="checkbox" value="<?php echo url_for([$item, 'module' => 'event']); ?>"/>
        </td>
      </tr>
    <?php } ?>
  </tbody>
</table>
