<?php use_helper('Text'); ?>
<?php // Rules moved out of style attributes: a CSP nonce covers <style>
      // elements and never an attribute, so under an enforcing policy every one
      // of these was dropped silently.
      $cspNonce = sfConfig::get('csp_nonce', ''); ?>
<style <?php echo $cspNonce ? preg_replace('/^nonce=/', 'nonce="', $cspNonce).'"' : ''; ?>>
  .core-font-weight-normal-a20a { font-weight: normal; }
</style>

<h1><?php echo render_title($title); ?></h1>

<?php if (isset($pager)) { ?>
    <?php $form->pager = true; ?>
<?php } ?>

<?php if (isset($form->confirm)) { ?>

  <h3 class="core-font-weight-normal-a20a"><?php echo __('This will permanently modify %1% records.', ['%1%' => count($pager->hits)]); ?></h3>
  <div class="error">
    <h2><?php echo __('This action cannot be undone!'); ?></li></h2>
  </div>

<?php } ?>

<?php echo get_partial('search/advancedSearch', ['form' => $form, 'action' => 'globalReplace']); ?>

<?php if (isset($error)) { ?>

  <div class="error">
    <ul>
      <li><?php echo $error; ?></li>
    </ul>
  </div>

<?php } ?>
