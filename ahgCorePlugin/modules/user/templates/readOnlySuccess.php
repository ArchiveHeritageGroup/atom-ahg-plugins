<?php // Rules moved out of style attributes: a CSP nonce covers <style>
      // elements and never an attribute, so under an enforcing policy every one
      // of these was dropped silently.
      $cspNonce = sfConfig::get('csp_nonce', ''); ?>
<style <?php echo $cspNonce ? preg_replace('/^nonce=/', 'nonce="', $cspNonce).'"' : ''; ?>>
  .core-font-size-20px-af18 { font-size: 20px; }
  .core-text-align-center-cdd8 { text-align: center; }
</style>
<div class="core-text-align-center-cdd8">

  <?php echo image_tag('lock48', ['alt' => __('Read only')]); ?>

  <h2 class="core-font-size-20px-af18"><?php echo __('The system is currently in read-only mode. Please try again later.'); ?></h2>

  <a href="javascript:history.go(-1)"><?php echo __('Back to previous page'); ?></a>

  <br/>

  <?php echo link_to(__('Go to homepage'), '@homepage'); ?>

</div>
