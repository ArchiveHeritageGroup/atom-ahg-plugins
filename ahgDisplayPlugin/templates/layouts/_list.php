<?php // Rules moved out of style attributes: a CSP nonce covers <style>
      // elements and never an attribute, so under an enforcing policy every one
      // of these was dropped silently.
      $cspNonce = sfConfig::get('csp_nonce', ''); ?>
<style <?php echo $cspNonce ? preg_replace('/^nonce=/', 'nonce="', $cspNonce).'"' : ''; ?>>
  .displa-width-50px-height-50px-objec-dea7 { width: 50px; height: 50px; object-fit: cover; }
</style>
<?php
/**
 * List layout - tabular view for libraries/search
 */
?>
<tr class="list-item" data-id="<?php echo $object->id; ?>">
    <?php if ($digitalObject && $data['thumbnail_size'] !== 'none'): ?>
    <td width="60">
        <img src="<?php echo $digitalObject->path; ?>" class="rounded displa-width-50px-height-50px-objec-dea7"  alt="">
    </td>
    <?php endif; ?>
    
    <?php foreach ($fields['identity'] as $field): ?>
    <td>
        <?php if ($field['code'] === 'title'): ?>
        <a href="<?php echo url_for(['module' => 'informationobject', 'slug' => $object->slug]); ?>">
            <strong><?php echo $field['value']; ?></strong>
        </a>
        <?php else: ?>
        <?php echo format_field_value($field); ?>
        <?php endif; ?>
    </td>
    <?php endforeach; ?>
    
    <td class="text-end">
        <?php foreach ($data['actions'] as $action): ?>
            <?php if ($action === 'view'): ?>
            <a href="<?php echo url_for(['module' => 'informationobject', 'slug' => $object->slug]); ?>" class="btn btn-sm btn-outline-primary">
                <i class="fas fa-eye"></i>
            </a>
            <?php endif; ?>
        <?php endforeach; ?>
    </td>
</tr>
