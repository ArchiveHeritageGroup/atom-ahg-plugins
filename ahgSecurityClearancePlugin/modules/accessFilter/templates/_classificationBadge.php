<?php // Rules moved out of style attributes: a CSP nonce covers <style>
      // elements and never an attribute, so under an enforcing policy every one
      // of these was dropped silently.
      $cspNonce = sfConfig::get('csp_nonce', ''); ?>
<style <?php echo $cspNonce ? preg_replace('/^nonce=/', 'nonce="', $cspNonce).'"' : ''; ?>>
  .securi-font-size-0-7rem-1982 { font-size:0.7rem; }
</style>
<?php
if (empty($classification)) return;
$colors = ['PUBLIC'=>'success','INTERNAL'=>'info','CONFIDENTIAL'=>'primary','SECRET'=>'warning','TOP_SECRET'=>'danger'];
?>
<span class="badge bg-<?php echo $colors[$classification->code] ?? 'secondary'; ?> securi-font-size-0-7rem-1982" >
    <?php echo esc_entities($classification->code); ?>
</span>
