<?php use_helper('I18N') ?>
<h1><?php echo __('Translation settings') ?></h1>

<?php if ($sf_user->hasFlash('notice')): ?>
  <div class="alert alert-success"><?php echo $sf_user->getFlash('notice') ?></div>
<?php endif; ?>

<form method="post">
  <p class="text-muted"><?php echo __('Translations go through the AHG AI gateway. Its address and key are set under Admin > AHG Settings > AI Services.') ?></p>

  <div class="form-group">
    <label><?php echo __('Timeout (seconds)') ?></label>
    <input class="form-control" name="timeout" value="<?php echo htmlspecialchars($timeout, ENT_QUOTES, 'UTF-8') ?>" />
  </div>

  <button class="btn btn-primary" type="submit"><?php echo __('Save') ?></button>
</form>

<hr/>
<p>
  <?php echo __('Health check:') ?>
  <a href="/translation/health" target="_blank">/translation/health</a>
</p>
