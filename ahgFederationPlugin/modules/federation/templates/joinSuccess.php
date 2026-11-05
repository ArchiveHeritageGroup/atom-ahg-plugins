<?php
$form = $sf_data->getRaw('form') ?: [];
$v = fn ($k) => esc_entities((string) ($form[$k] ?? ''));
?>
<div class="container py-4" style="max-width: 760px">
  <h1 class="h3 mb-3"><?php echo __('Join the network') ?></h1>
  <p><?php echo __('Institutions in the network share their catalogues in one union catalogue and can ask each other for loans. Tell us about your institution and what you would share; an administrator will be in touch.') ?></p>

  <form method="post" action="<?php echo url_for(['module' => 'federation', 'action' => 'join']) ?>" class="card">
    <?php echo \AtomFramework\Services\CsrfService::renderHiddenField() ?>
    <input type="hidden" name="rendered_at" value="<?php echo (int) $renderedAt ?>">
    <div class="visually-hidden" aria-hidden="true"><label for="website">Website</label><input type="text" id="website" name="website" tabindex="-1" autocomplete="off"></div>
    <div class="card-body">
      <div class="mb-3">
        <label class="form-label" for="institution_name"><?php echo __('Institution') ?> <span class="text-danger">*</span></label>
        <input type="text" class="form-control" id="institution_name" name="institution_name" maxlength="255" required value="<?php echo $v('institution_name') ?>">
      </div>
      <div class="row g-3 mb-3">
        <div class="col-md-6">
          <label class="form-label" for="contact_name"><?php echo __('Your name') ?></label>
          <input type="text" class="form-control" id="contact_name" name="contact_name" maxlength="255" autocomplete="name" value="<?php echo $v('contact_name') ?>">
        </div>
        <div class="col-md-6">
          <label class="form-label" for="contact_email"><?php echo __('Email') ?></label>
          <input type="email" class="form-control" id="contact_email" name="contact_email" maxlength="255" autocomplete="email" value="<?php echo $v('contact_email') ?>">
        </div>
      </div>
      <div class="mb-3">
        <label class="form-label" for="base_url"><?php echo __('Catalogue or website address') ?></label>
        <input type="url" class="form-control" id="base_url" name="base_url" maxlength="1024" placeholder="https://" value="<?php echo $v('base_url') ?>">
      </div>
      <div class="mb-3">
        <label class="form-label" for="what_they_share"><?php echo __('What would you share?') ?></label>
        <textarea class="form-control" id="what_they_share" name="what_they_share" rows="4"><?php echo $v('what_they_share') ?></textarea>
      </div>
      <div class="mb-0">
        <label class="form-label" for="notes"><?php echo __('Anything else') ?></label>
        <textarea class="form-control" id="notes" name="notes" rows="3"><?php echo $v('notes') ?></textarea>
      </div>
    </div>
    <div class="card-footer text-end"><button type="submit" class="btn btn-primary"><?php echo __('Send request') ?></button></div>
  </form>
</div>
