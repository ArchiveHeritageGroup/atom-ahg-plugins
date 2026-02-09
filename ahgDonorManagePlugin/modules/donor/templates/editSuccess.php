<?php decorate_with('layout_1col.php'); ?>

<?php slot('title'); ?>
  <div class="multiline-header d-flex flex-column mb-3">
    <h1 class="mb-0" aria-describedby="heading-label">
      <?php echo $isNew ? __('Add new donor') : __('Edit donor'); ?>
    </h1>
    <?php if (!$isNew) { ?>
      <span class="small" id="heading-label">
        <?php echo esc_specialchars($donor['authorizedFormOfName'] ?: __('Untitled')); ?>
      </span>
    <?php } ?>
  </div>
<?php end_slot(); ?>

<?php slot('content'); ?>

  <form method="post" action="<?php echo $isNew ? url_for(['module' => 'donor', 'action' => 'add']) : url_for(['module' => 'donor', 'slug' => $donor['slug'], 'action' => 'edit']); ?>" id="editForm">

    <?php echo $form->renderHiddenFields(); ?>

    <div class="accordion mb-3">
      <div class="accordion-item">
        <h2 class="accordion-header" id="identity-heading">
          <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#identity-collapse" aria-expanded="true" aria-controls="identity-collapse">
            <?php echo __('Identity area'); ?>
          </button>
        </h2>
        <div id="identity-collapse" class="accordion-collapse collapse show" aria-labelledby="identity-heading">
          <div class="accordion-body">
            <div class="mb-3">
              <label for="authorizedFormOfName" class="form-label">
                <?php echo __('Authorized form of name'); ?>
                <span class="form-required" title="<?php echo __('This is a mandatory field.'); ?>">*</span>
              </label>
              <input type="text" class="form-control" id="authorizedFormOfName" name="authorizedFormOfName"
                     value="<?php echo esc_specialchars($donor['authorizedFormOfName']); ?>" required>
            </div>
          </div>
        </div>
      </div>

      <div class="accordion-item">
        <h2 class="accordion-header" id="contact-heading">
          <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#contact-collapse" aria-expanded="false" aria-controls="contact-collapse">
            <?php echo __('Contact area'); ?>
          </button>
        </h2>
        <div id="contact-collapse" class="accordion-collapse collapse" aria-labelledby="contact-heading">
          <div class="accordion-body">
            <?php
              // Get primary contact data
              $rawContacts = $sf_data->getRaw('contacts');
              $c = !empty($rawContacts) ? $rawContacts[0] : null;
            ?>

            <div class="mb-3">
              <label for="contact_person" class="form-label"><?php echo __('Contact person'); ?></label>
              <input type="text" class="form-control" id="contact_person" name="contact_person"
                     value="<?php echo esc_specialchars($c->contact_person ?? ''); ?>">
            </div>

            <div class="mb-3">
              <label for="street_address" class="form-label"><?php echo __('Street address'); ?></label>
              <textarea class="form-control" id="street_address" name="street_address" rows="2"><?php echo esc_specialchars($c->street_address ?? ''); ?></textarea>
            </div>

            <div class="row">
              <div class="col-md-4 mb-3">
                <label for="city" class="form-label"><?php echo __('City'); ?></label>
                <input type="text" class="form-control" id="city" name="city"
                       value="<?php echo esc_specialchars($c->city ?? ''); ?>">
              </div>
              <div class="col-md-4 mb-3">
                <label for="region" class="form-label"><?php echo __('Region/province'); ?></label>
                <input type="text" class="form-control" id="region" name="region"
                       value="<?php echo esc_specialchars($c->region ?? ''); ?>">
              </div>
              <div class="col-md-4 mb-3">
                <label for="postal_code" class="form-label"><?php echo __('Postal code'); ?></label>
                <input type="text" class="form-control" id="postal_code" name="postal_code"
                       value="<?php echo esc_specialchars($c->postal_code ?? ''); ?>">
              </div>
            </div>

            <div class="mb-3">
              <label for="country_code" class="form-label"><?php echo __('Country'); ?></label>
              <input type="text" class="form-control" id="country_code" name="country_code"
                     value="<?php echo esc_specialchars($c->country_code ?? ''); ?>">
            </div>

            <div class="row">
              <div class="col-md-6 mb-3">
                <label for="telephone" class="form-label"><?php echo __('Telephone'); ?></label>
                <input type="text" class="form-control" id="telephone" name="telephone"
                       value="<?php echo esc_specialchars($c->telephone ?? ''); ?>">
              </div>
              <div class="col-md-6 mb-3">
                <label for="fax" class="form-label"><?php echo __('Fax'); ?></label>
                <input type="text" class="form-control" id="fax" name="fax"
                       value="<?php echo esc_specialchars($c->fax ?? ''); ?>">
              </div>
            </div>

            <div class="row">
              <div class="col-md-6 mb-3">
                <label for="email" class="form-label"><?php echo __('Email'); ?></label>
                <input type="email" class="form-control" id="email" name="email"
                       value="<?php echo esc_specialchars($c->email ?? ''); ?>">
              </div>
              <div class="col-md-6 mb-3">
                <label for="website" class="form-label"><?php echo __('Website'); ?></label>
                <input type="url" class="form-control" id="website" name="website"
                       value="<?php echo esc_specialchars($c->website ?? ''); ?>">
              </div>
            </div>

            <div class="mb-3">
              <label for="note" class="form-label"><?php echo __('Note'); ?></label>
              <textarea class="form-control" id="note" name="note" rows="3"><?php echo esc_specialchars($c->note ?? ''); ?></textarea>
            </div>
          </div>
        </div>
      </div>
    </div>

    <ul class="actions mb-3 nav gap-2">
      <?php if (!$isNew) { ?>
        <li><?php echo link_to(__('Cancel'), ['module' => 'donor', 'slug' => $donor['slug']], ['class' => 'btn atom-btn-outline-light', 'role' => 'button']); ?></li>
        <li><input class="btn atom-btn-outline-success" type="submit" value="<?php echo __('Save'); ?>"></li>
      <?php } else { ?>
        <li><?php echo link_to(__('Cancel'), ['module' => 'donor', 'action' => 'browse'], ['class' => 'btn atom-btn-outline-light', 'role' => 'button']); ?></li>
        <li><input class="btn atom-btn-outline-success" type="submit" value="<?php echo __('Create'); ?>"></li>
      <?php } ?>
    </ul>

  </form>

<?php end_slot(); ?>
