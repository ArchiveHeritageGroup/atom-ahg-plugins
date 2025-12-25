<?php decorate_with('layout_1col.php'); ?>

<?php
// Get contacts if exist
$contacts = collect([]);
if (isset($donor->contactInformations) && count($donor->contactInformations) > 0) {
    $contacts = $donor->contactInformations;
    if (!$contacts instanceof \Illuminate\Support\Collection) {
        $contacts = collect($contacts);
    }
}

// Ensure at least one empty contact form
if ($contacts->isEmpty()) {
    $contacts = collect([(object)[
        'id' => '',
        'title' => '',
        'contactPerson' => '',
        'role' => '',
        'department' => '',
        'contactType' => '',
        'idNumber' => '',
        'preferredContactMethod' => '',
        'languagePreference' => '',
        'telephone' => '',
        'cell' => '',
        'fax' => '',
        'email' => '',
        'alternativeEmail' => '',
        'website' => '',
        'alternativePhone' => '',
        'streetAddress' => '',
        'city' => '',
        'region' => '',
        'postalCode' => '',
        'countryCode' => '',
        'latitude' => '',
        'longitude' => '',
        'note' => '',
        'extendedNotes' => '',
        'primaryContact' => 0,
    ]]);
}

// Get countries and languages from AtoM/Symfony
$cultureInfo = sfCultureInfo::getInstance($sf_user->getCulture());
$countries = $cultureInfo->getCountries();
$languages = $cultureInfo->getLanguages();

// Sort alphabetically by value
asort($countries);
asort($languages);

// Title options
$titles = ['Mr', 'Mrs', 'Ms', 'Miss', 'Dr', 'Prof', 'Rev', 'Hon', 'Sir', 'Dame', 'Adv'];
?>

<?php slot('title'); ?>
  <h1 class="h3 mb-0">
    <?php if (!$isNew) { ?>
      <?php echo __('Edit donor'); ?>
      <small class="text-muted d-block mt-1"><?php echo esc_entities($donor->authorizedFormOfName) ?: $donor->slug; ?></small>
    <?php } else { ?>
      <?php echo __('Add new donor'); ?>
    <?php } ?>
  </h1>
<?php end_slot(); ?>

<?php slot('content'); ?>

<?php if (isset($errors) && is_array($errors) && count($errors) > 0) { ?>
  <div class="alert alert-danger" role="alert">
    <h5 class="alert-heading"><i class="bi bi-exclamation-triangle me-2"></i><?php echo __('Please correct the following errors'); ?></h5>
    <ul class="mb-0">
      <?php foreach ($errors as $field => $error) { ?>
        <li><?php echo esc_entities($error); ?></li>
      <?php } ?>
    </ul>
  </div>
<?php } ?>

<form method="post" action="<?php echo $isNew ? url_for(['module' => 'donor', 'action' => 'add']) : url_for(['module' => 'donor', 'action' => 'edit', 'slug' => $donor->slug]); ?>" id="editForm">

  <!-- Identity Area -->
  <div class="card mb-4">
    <div class="card-header bg-primary text-white">
      <h5 class="mb-0"><i class="bi bi-person-badge me-2"></i><?php echo __('Identity area'); ?></h5>
    </div>
    <div class="card-body">
      <div class="mb-3">
        <label for="authorizedFormOfName" class="form-label">
          <?php echo __('Authorized form of name'); ?>
          <span class="text-danger" title="<?php echo __('This is a mandatory element.'); ?>">*</span>
        </label>
        <input type="text" name="authorizedFormOfName" id="authorizedFormOfName"
               class="form-control <?php echo isset($errors['authorizedFormOfName']) ? 'is-invalid' : ''; ?>"
               value="<?php echo esc_entities($donor->authorizedFormOfName ?? ''); ?>" required>
        <?php if (isset($errors['authorizedFormOfName'])) { ?>
          <div class="invalid-feedback"><?php echo esc_entities($errors['authorizedFormOfName']); ?></div>
        <?php } ?>
      </div>
    </div>
  </div>

  <!-- Contacts Container -->
  <div id="contacts-container">
  <?php $index = 0; foreach ($contacts as $contact): ?>
    <div class="contact-entry" data-index="<?php echo $index; ?>">
      <!-- Contact Area - Main -->
      <div class="card mb-4">
        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
          <h5 class="mb-0"><i class="bi bi-telephone me-2"></i><?php echo __('Contact'); ?> #<span class="contact-number"><?php echo $index + 1; ?></span></h5>
          <button type="button" class="btn btn-sm btn-outline-light remove-contact" <?php echo $index === 0 && $contacts->count() === 1 ? 'style="display:none;"' : ''; ?>>
            <i class="bi bi-trash"></i> <?php echo __('Remove'); ?>
          </button>
        </div>
        <div class="card-body">
          <input type="hidden" name="contacts[<?php echo $index; ?>][id]" value="<?php echo $contact->id ?? ''; ?>">
          <input type="hidden" name="contacts[<?php echo $index; ?>][delete]" value="0" class="delete-flag">

          <div class="row">
            <div class="col-md-2 mb-3">
              <label class="form-label"><?php echo __('Title'); ?></label>
              <select name="contacts[<?php echo $index; ?>][title]" class="form-select">
                <option value=""><?php echo __('Select...'); ?></option>
                <?php foreach ($titles as $t): ?>
                  <option value="<?php echo $t; ?>" <?php echo ($contact->title ?? '') === $t ? 'selected' : ''; ?>><?php echo $t; ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-5 mb-3">
              <label class="form-label"><?php echo __('Contact person'); ?></label>
              <input type="text" name="contacts[<?php echo $index; ?>][contactPerson]" class="form-control"
                     value="<?php echo esc_entities($contact->contactPerson ?? ''); ?>">
            </div>
            <div class="col-md-5 mb-3">
              <label class="form-label"><?php echo __('Role/Position'); ?></label>
              <input type="text" name="contacts[<?php echo $index; ?>][role]" class="form-control"
                     value="<?php echo esc_entities($contact->role ?? ''); ?>"
                     placeholder="<?php echo __('e.g., Director, Manager, Curator'); ?>">
            </div>
          </div>

          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label"><?php echo __('Department'); ?></label>
              <input type="text" name="contacts[<?php echo $index; ?>][department]" class="form-control"
                     value="<?php echo esc_entities($contact->department ?? ''); ?>">
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label"><?php echo __('Contact type'); ?></label>
              <input type="text" name="contacts[<?php echo $index; ?>][contactType]" class="form-control"
                     value="<?php echo esc_entities($contact->contactType ?? ''); ?>"
                     placeholder="<?php echo __('e.g., Primary, Business, Home'); ?>">
            </div>
          </div>

          <div class="row">
            <div class="col-md-4 mb-3">
              <label class="form-label"><?php echo __('ID/Passport number'); ?></label>
              <input type="text" name="contacts[<?php echo $index; ?>][idNumber]" class="form-control"
                     value="<?php echo esc_entities($contact->idNumber ?? ''); ?>">
            </div>
            <div class="col-md-4 mb-3">
              <label class="form-label"><?php echo __('Preferred contact method'); ?></label>
              <select name="contacts[<?php echo $index; ?>][preferredContactMethod]" class="form-select">
                <option value=""><?php echo __('Select...'); ?></option>
                <option value="email" <?php echo ($contact->preferredContactMethod ?? '') === 'email' ? 'selected' : ''; ?>><?php echo __('Email'); ?></option>
                <option value="phone" <?php echo ($contact->preferredContactMethod ?? '') === 'phone' ? 'selected' : ''; ?>><?php echo __('Phone'); ?></option>
                <option value="cell" <?php echo ($contact->preferredContactMethod ?? '') === 'cell' ? 'selected' : ''; ?>><?php echo __('Cell/Mobile'); ?></option>
                <option value="fax" <?php echo ($contact->preferredContactMethod ?? '') === 'fax' ? 'selected' : ''; ?>><?php echo __('Fax'); ?></option>
                <option value="mail" <?php echo ($contact->preferredContactMethod ?? '') === 'mail' ? 'selected' : ''; ?>><?php echo __('Post/Mail'); ?></option>
              </select>
            </div>
            <div class="col-md-4 mb-3">
              <label class="form-label"><?php echo __('Language preference'); ?></label>
              <select name="contacts[<?php echo $index; ?>][languagePreference]" class="form-select">
                <option value=""><?php echo __('Select...'); ?></option>
                <?php foreach ($languages as $code => $name): ?>
                  <option value="<?php echo $code; ?>" <?php echo ($contact->languagePreference ?? '') === $code ? 'selected' : ''; ?>>
                    <?php echo $name; ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>

          <hr>

          <div class="row">
            <div class="col-md-4 mb-3">
              <label class="form-label"><?php echo __('Telephone'); ?></label>
              <input type="tel" name="contacts[<?php echo $index; ?>][telephone]" class="form-control"
                     value="<?php echo esc_entities($contact->telephone ?? ''); ?>">
            </div>
            <div class="col-md-4 mb-3">
              <label class="form-label"><?php echo __('Cell/Mobile'); ?></label>
              <input type="tel" name="contacts[<?php echo $index; ?>][cell]" class="form-control"
                     value="<?php echo esc_entities($contact->cell ?? ''); ?>">
            </div>
            <div class="col-md-4 mb-3">
              <label class="form-label"><?php echo __('Fax'); ?></label>
              <input type="tel" name="contacts[<?php echo $index; ?>][fax]" class="form-control"
                     value="<?php echo esc_entities($contact->fax ?? ''); ?>">
            </div>
          </div>

          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label"><?php echo __('Email'); ?></label>
              <input type="email" name="contacts[<?php echo $index; ?>][email]" class="form-control"
                     value="<?php echo esc_entities($contact->email ?? ''); ?>">
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label"><?php echo __('Alternative email'); ?></label>
              <input type="email" name="contacts[<?php echo $index; ?>][alternativeEmail]" class="form-control"
                     value="<?php echo esc_entities($contact->alternativeEmail ?? ''); ?>">
            </div>
          </div>

          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label"><?php echo __('Website'); ?></label>
              <input type="url" name="contacts[<?php echo $index; ?>][website]" class="form-control"
                     value="<?php echo esc_entities($contact->website ?? ''); ?>" placeholder="https://">
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label"><?php echo __('Alternative phone'); ?></label>
              <input type="tel" name="contacts[<?php echo $index; ?>][alternativePhone]" class="form-control"
                     value="<?php echo esc_entities($contact->alternativePhone ?? ''); ?>">
            </div>
          </div>

          <hr>
          <h6 class="text-muted mb-3"><i class="bi bi-geo-alt me-2"></i><?php echo __('Physical location'); ?></h6>

          <div class="mb-3">
            <label class="form-label"><?php echo __('Street address'); ?></label>
            <textarea name="contacts[<?php echo $index; ?>][streetAddress]" class="form-control" rows="2"><?php echo esc_entities($contact->streetAddress ?? ''); ?></textarea>
          </div>

          <div class="row">
            <div class="col-md-4 mb-3">
              <label class="form-label"><?php echo __('City'); ?></label>
              <input type="text" name="contacts[<?php echo $index; ?>][city]" class="form-control"
                     value="<?php echo esc_entities($contact->city ?? ''); ?>">
            </div>
            <div class="col-md-4 mb-3">
              <label class="form-label"><?php echo __('Region/Province'); ?></label>
              <input type="text" name="contacts[<?php echo $index; ?>][region]" class="form-control"
                     value="<?php echo esc_entities($contact->region ?? ''); ?>">
            </div>
            <div class="col-md-4 mb-3">
              <label class="form-label"><?php echo __('Postal code'); ?></label>
              <input type="text" name="contacts[<?php echo $index; ?>][postalCode]" class="form-control"
                     value="<?php echo esc_entities($contact->postalCode ?? ''); ?>">
            </div>
          </div>

          <div class="row">
            <div class="col-md-4 mb-3">
              <label class="form-label"><?php echo __('Country'); ?></label>
              <select name="contacts[<?php echo $index; ?>][countryCode]" class="form-select">
                <option value=""><?php echo __('Select country...'); ?></option>
                <?php foreach ($countries as $code => $name): ?>
                  <option value="<?php echo $code; ?>" <?php echo ($contact->countryCode ?? '') === $code ? 'selected' : ''; ?>>
                    <?php echo $name; ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-4 mb-3">
              <label class="form-label"><?php echo __('Latitude'); ?></label>
              <input type="text" name="contacts[<?php echo $index; ?>][latitude]" class="form-control"
                     value="<?php echo esc_entities($contact->latitude ?? ''); ?>"
                     placeholder="<?php echo __('e.g., -26.2041'); ?>">
            </div>
            <div class="col-md-4 mb-3">
              <label class="form-label"><?php echo __('Longitude'); ?></label>
              <input type="text" name="contacts[<?php echo $index; ?>][longitude]" class="form-control"
                     value="<?php echo esc_entities($contact->longitude ?? ''); ?>"
                     placeholder="<?php echo __('e.g., 28.0473'); ?>">
            </div>
          </div>

          <hr>

          <div class="mb-3">
            <label class="form-label"><?php echo __('General note'); ?></label>
            <textarea name="contacts[<?php echo $index; ?>][note]" class="form-control" rows="2"><?php echo esc_entities($contact->note ?? ''); ?></textarea>
          </div>

          <div class="mb-3">
            <label class="form-label"><?php echo __('Additional notes'); ?></label>
            <textarea name="contacts[<?php echo $index; ?>][extendedNotes]" class="form-control" rows="2"><?php echo esc_entities($contact->extendedNotes ?? ''); ?></textarea>
          </div>

          <div class="form-check">
            <input type="checkbox" name="contacts[<?php echo $index; ?>][primaryContact]" value="1" class="form-check-input primary-contact-check"
                   <?php echo ($contact->primaryContact ?? 0) ? 'checked' : ''; ?>>
            <label class="form-check-label"><?php echo __('Primary contact'); ?></label>
          </div>
        </div>
      </div>
    </div>
  <?php $index++; endforeach; ?>
  </div>

  <div class="mb-4">
    <button type="button" id="add-contact" class="btn btn-outline-primary">
      <i class="bi bi-plus-circle me-2"></i><?php echo __('Add another contact'); ?>
    </button>
  </div>

  <!-- Template for new contact (hidden) -->
  <template id="contact-template">
    <div class="contact-entry" data-index="__INDEX__">
      <div class="card mb-4">
        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
          <h5 class="mb-0"><i class="bi bi-telephone me-2"></i><?php echo __('Contact'); ?> #<span class="contact-number">__NUMBER__</span></h5>
          <button type="button" class="btn btn-sm btn-outline-light remove-contact">
            <i class="bi bi-trash"></i> <?php echo __('Remove'); ?>
          </button>
        </div>
        <div class="card-body">
          <input type="hidden" name="contacts[__INDEX__][id]" value="">
          <input type="hidden" name="contacts[__INDEX__][delete]" value="0" class="delete-flag">

          <div class="row">
            <div class="col-md-2 mb-3">
              <label class="form-label"><?php echo __('Title'); ?></label>
              <select name="contacts[__INDEX__][title]" class="form-select">
                <option value=""><?php echo __('Select...'); ?></option>
                <?php foreach ($titles as $t): ?>
                  <option value="<?php echo $t; ?>"><?php echo $t; ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-5 mb-3">
              <label class="form-label"><?php echo __('Contact person'); ?></label>
              <input type="text" name="contacts[__INDEX__][contactPerson]" class="form-control">
            </div>
            <div class="col-md-5 mb-3">
              <label class="form-label"><?php echo __('Role/Position'); ?></label>
              <input type="text" name="contacts[__INDEX__][role]" class="form-control"
                     placeholder="<?php echo __('e.g., Director, Manager, Curator'); ?>">
            </div>
          </div>

          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label"><?php echo __('Department'); ?></label>
              <input type="text" name="contacts[__INDEX__][department]" class="form-control">
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label"><?php echo __('Contact type'); ?></label>
              <input type="text" name="contacts[__INDEX__][contactType]" class="form-control"
                     placeholder="<?php echo __('e.g., Primary, Business, Home'); ?>">
            </div>
          </div>

          <div class="row">
            <div class="col-md-4 mb-3">
              <label class="form-label"><?php echo __('ID/Passport number'); ?></label>
              <input type="text" name="contacts[__INDEX__][idNumber]" class="form-control">
            </div>
            <div class="col-md-4 mb-3">
              <label class="form-label"><?php echo __('Preferred contact method'); ?></label>
              <select name="contacts[__INDEX__][preferredContactMethod]" class="form-select">
                <option value=""><?php echo __('Select...'); ?></option>
                <option value="email"><?php echo __('Email'); ?></option>
                <option value="phone"><?php echo __('Phone'); ?></option>
                <option value="cell"><?php echo __('Cell/Mobile'); ?></option>
                <option value="fax"><?php echo __('Fax'); ?></option>
                <option value="mail"><?php echo __('Post/Mail'); ?></option>
              </select>
            </div>
            <div class="col-md-4 mb-3">
              <label class="form-label"><?php echo __('Language preference'); ?></label>
              <select name="contacts[__INDEX__][languagePreference]" class="form-select">
                <option value=""><?php echo __('Select...'); ?></option>
                <?php foreach ($languages as $code => $name): ?>
                  <option value="<?php echo $code; ?>"><?php echo $name; ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>

          <hr>

          <div class="row">
            <div class="col-md-4 mb-3">
              <label class="form-label"><?php echo __('Telephone'); ?></label>
              <input type="tel" name="contacts[__INDEX__][telephone]" class="form-control">
            </div>
            <div class="col-md-4 mb-3">
              <label class="form-label"><?php echo __('Cell/Mobile'); ?></label>
              <input type="tel" name="contacts[__INDEX__][cell]" class="form-control">
            </div>
            <div class="col-md-4 mb-3">
              <label class="form-label"><?php echo __('Fax'); ?></label>
              <input type="tel" name="contacts[__INDEX__][fax]" class="form-control">
            </div>
          </div>

          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label"><?php echo __('Email'); ?></label>
              <input type="email" name="contacts[__INDEX__][email]" class="form-control">
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label"><?php echo __('Alternative email'); ?></label>
              <input type="email" name="contacts[__INDEX__][alternativeEmail]" class="form-control">
            </div>
          </div>

          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label"><?php echo __('Website'); ?></label>
              <input type="url" name="contacts[__INDEX__][website]" class="form-control" placeholder="https://">
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label"><?php echo __('Alternative phone'); ?></label>
              <input type="tel" name="contacts[__INDEX__][alternativePhone]" class="form-control">
            </div>
          </div>

          <hr>
          <h6 class="text-muted mb-3"><i class="bi bi-geo-alt me-2"></i><?php echo __('Physical location'); ?></h6>

          <div class="mb-3">
            <label class="form-label"><?php echo __('Street address'); ?></label>
            <textarea name="contacts[__INDEX__][streetAddress]" class="form-control" rows="2"></textarea>
          </div>

          <div class="row">
            <div class="col-md-4 mb-3">
              <label class="form-label"><?php echo __('City'); ?></label>
              <input type="text" name="contacts[__INDEX__][city]" class="form-control">
            </div>
            <div class="col-md-4 mb-3">
              <label class="form-label"><?php echo __('Region/Province'); ?></label>
              <input type="text" name="contacts[__INDEX__][region]" class="form-control">
            </div>
            <div class="col-md-4 mb-3">
              <label class="form-label"><?php echo __('Postal code'); ?></label>
              <input type="text" name="contacts[__INDEX__][postalCode]" class="form-control">
            </div>
          </div>

          <div class="row">
            <div class="col-md-4 mb-3">
              <label class="form-label"><?php echo __('Country'); ?></label>
              <select name="contacts[__INDEX__][countryCode]" class="form-select">
                <option value=""><?php echo __('Select country...'); ?></option>
                <?php foreach ($countries as $code => $name): ?>
                  <option value="<?php echo $code; ?>"><?php echo $name; ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-4 mb-3">
              <label class="form-label"><?php echo __('Latitude'); ?></label>
              <input type="text" name="contacts[__INDEX__][latitude]" class="form-control"
                     placeholder="<?php echo __('e.g., -26.2041'); ?>">
            </div>
            <div class="col-md-4 mb-3">
              <label class="form-label"><?php echo __('Longitude'); ?></label>
              <input type="text" name="contacts[__INDEX__][longitude]" class="form-control"
                     placeholder="<?php echo __('e.g., 28.0473'); ?>">
            </div>
          </div>

          <hr>

          <div class="mb-3">
            <label class="form-label"><?php echo __('General note'); ?></label>
            <textarea name="contacts[__INDEX__][note]" class="form-control" rows="2"></textarea>
          </div>

          <div class="mb-3">
            <label class="form-label"><?php echo __('Additional notes'); ?></label>
            <textarea name="contacts[__INDEX__][extendedNotes]" class="form-control" rows="2"></textarea>
          </div>

          <div class="form-check">
            <input type="checkbox" name="contacts[__INDEX__][primaryContact]" value="1" class="form-check-input primary-contact-check">
            <label class="form-check-label"><?php echo __('Primary contact'); ?></label>
          </div>
        </div>
      </div>
    </div>
  </template>

  <!-- Actions -->
  <div class="d-flex flex-wrap gap-2 mt-4">
    <?php if (!$isNew) { ?>
      <a href="<?php echo url_for(['module' => 'donor', 'action' => 'index', 'slug' => $donor->slug]); ?>" class="btn btn-outline-secondary">
        <i class="bi bi-x-lg me-1"></i><?php echo __('Cancel'); ?>
      </a>
      <button type="submit" class="btn btn-primary">
        <i class="bi bi-check-lg me-1"></i><?php echo __('Save'); ?>
      </button>
    <?php } else { ?>
      <a href="<?php echo url_for(['module' => 'donor', 'action' => 'browse']); ?>" class="btn btn-outline-secondary">
        <i class="bi bi-x-lg me-1"></i><?php echo __('Cancel'); ?>
      </a>
      <button type="submit" class="btn btn-primary">
        <i class="bi bi-plus-lg me-1"></i><?php echo __('Create'); ?>
      </button>
    <?php } ?>
  </div>

</form>

<script>
document.addEventListener('DOMContentLoaded', function() {
  const container = document.getElementById('contacts-container');
  const addBtn = document.getElementById('add-contact');
  const template = document.getElementById('contact-template');

  function getNextIndex() {
    const entries = container.querySelectorAll('.contact-entry');
    let maxIndex = -1;
    entries.forEach(function(entry) {
      const idx = parseInt(entry.dataset.index, 10);
      if (idx > maxIndex) maxIndex = idx;
    });
    return maxIndex + 1;
  }

  function updateContactNumbers() {
    const entries = container.querySelectorAll('.contact-entry:not([style*="display: none"])');
    entries.forEach(function(entry, i) {
      const numSpan = entry.querySelector('.contact-number');
      if (numSpan) numSpan.textContent = i + 1;
    });

    const visibleEntries = container.querySelectorAll('.contact-entry:not([style*="display: none"])');
    visibleEntries.forEach(function(entry) {
      const removeBtn = entry.querySelector('.remove-contact');
      if (removeBtn) {
        removeBtn.style.display = visibleEntries.length > 1 ? '' : 'none';
      }
    });
  }

  addBtn.addEventListener('click', function() {
    const newIndex = getNextIndex();
    const newNumber = container.querySelectorAll('.contact-entry:not([style*="display: none"])').length + 1;

    let html = template.innerHTML;
    html = html.replace(/__INDEX__/g, newIndex);
    html = html.replace(/__NUMBER__/g, newNumber);

    const div = document.createElement('div');
    div.innerHTML = html;
    const newEntry = div.firstElementChild;

    container.appendChild(newEntry);
    updateContactNumbers();

    newEntry.scrollIntoView({ behavior: 'smooth', block: 'start' });
  });

  container.addEventListener('click', function(e) {
    if (e.target.closest('.remove-contact')) {
      const entry = e.target.closest('.contact-entry');
      const idInput = entry.querySelector('input[name$="[id]"]');
      const deleteFlag = entry.querySelector('.delete-flag');

      if (idInput && idInput.value) {
        deleteFlag.value = '1';
        entry.style.display = 'none';
      } else {
        entry.remove();
      }

      updateContactNumbers();
    }
  });

  container.addEventListener('change', function(e) {
    if (e.target.classList.contains('primary-contact-check') && e.target.checked) {
      container.querySelectorAll('.primary-contact-check').forEach(function(cb) {
        if (cb !== e.target) cb.checked = false;
      });
    }
  });

  updateContactNumbers();
});
</script>

<?php end_slot(); ?>
