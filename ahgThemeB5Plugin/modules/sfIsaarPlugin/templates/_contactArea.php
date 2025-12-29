<?php
/**
 * Extended Contact information area for authority records
 * Supports multiple contacts with add/remove functionality
 */

use AtomFramework\Extensions\Contact\Repositories\ContactInformationRepository;
use AtomFramework\Services\LanguageService;

// Get existing contacts
$contactRepo = new ContactInformationRepository();
$culture = sfContext::getInstance()->getUser()->getCulture();
$contacts = $resource->id ? $contactRepo->getByActorId((int)$resource->id, $culture) : collect([]);

// Ensure at least one empty contact form
if ($contacts->isEmpty()) {
    $contacts = collect([(object)[
        'id' => '',
        'title' => '',
        'contact_person' => '',
        'role' => '',
        'department' => '',
        'contact_type' => '',
        'id_number' => '',
        'preferred_contact_method' => '',
        'language_preference' => '',
        'telephone' => '',
        'cell' => '',
        'fax' => '',
        'email' => '',
        'alternative_email' => '',
        'website' => '',
        'alternative_phone' => '',
        'street_address' => '',
        'city' => '',
        'region' => '',
        'postal_code' => '',
        'country_code' => '',
        'latitude' => '',
        'longitude' => '',
        'note' => '',
        'primary_contact' => 0,
    ]]);
}

// Get languages from service
$languages = LanguageService::getAll();

// Title options
$titles = ['Mr', 'Mrs', 'Ms', 'Miss', 'Dr', 'Prof', 'Rev', 'Hon', 'Sir', 'Dame', 'Adv'];
?>

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
            <input type="text" name="contacts[<?php echo $index; ?>][contact_person]" class="form-control"
                   value="<?php echo esc_specialchars($contact->contact_person ?? ''); ?>">
          </div>
          <div class="col-md-5 mb-3">
            <label class="form-label"><?php echo __('Role/Position'); ?></label>
            <input type="text" name="contacts[<?php echo $index; ?>][role]" class="form-control"
                   value="<?php echo esc_specialchars($contact->role ?? ''); ?>"
                   placeholder="<?php echo __('e.g., Director, Manager, Curator'); ?>">
          </div>
        </div>

        <div class="row">
          <div class="col-md-6 mb-3">
            <label class="form-label"><?php echo __('Department'); ?></label>
            <input type="text" name="contacts[<?php echo $index; ?>][department]" class="form-control"
                   value="<?php echo esc_specialchars($contact->department ?? ''); ?>">
          </div>
          <div class="col-md-6 mb-3">
            <label class="form-label"><?php echo __('Contact type'); ?></label>
            <input type="text" name="contacts[<?php echo $index; ?>][contact_type]" class="form-control"
                   value="<?php echo esc_specialchars($contact->contact_type ?? ''); ?>"
                   placeholder="<?php echo __('e.g., Primary, Business, Home'); ?>">
          </div>
        </div>

        <div class="row">
          <div class="col-md-4 mb-3">
            <label class="form-label"><?php echo __('ID/Passport number'); ?></label>
            <input type="text" name="contacts[<?php echo $index; ?>][id_number]" class="form-control"
                   value="<?php echo esc_specialchars($contact->id_number ?? ''); ?>">
          </div>
          <div class="col-md-4 mb-3">
            <label class="form-label"><?php echo __('Preferred contact method'); ?></label>
            <select name="contacts[<?php echo $index; ?>][preferred_contact_method]" class="form-select">
              <option value=""><?php echo __('Select...'); ?></option>
              <option value="email" <?php echo ($contact->preferred_contact_method ?? '') === 'email' ? 'selected' : ''; ?>><?php echo __('Email'); ?></option>
              <option value="phone" <?php echo ($contact->preferred_contact_method ?? '') === 'phone' ? 'selected' : ''; ?>><?php echo __('Phone'); ?></option>
              <option value="cell" <?php echo ($contact->preferred_contact_method ?? '') === 'cell' ? 'selected' : ''; ?>><?php echo __('Cell/Mobile'); ?></option>
              <option value="fax" <?php echo ($contact->preferred_contact_method ?? '') === 'fax' ? 'selected' : ''; ?>><?php echo __('Fax'); ?></option>
              <option value="mail" <?php echo ($contact->preferred_contact_method ?? '') === 'mail' ? 'selected' : ''; ?>><?php echo __('Post/Mail'); ?></option>
            </select>
          </div>
          <div class="col-md-4 mb-3">
            <label class="form-label"><?php echo __('Language preference'); ?></label>
            <select name="contacts[<?php echo $index; ?>][language_preference]" class="form-select">
              <option value=""><?php echo __('Select...'); ?></option>
              <?php foreach ($languages as $lang): ?>
                <option value="<?php echo $lang->id; ?>" <?php echo ($contact->language_preference ?? '') == $lang->id ? 'selected' : ''; ?>>
                  <?php echo esc_specialchars($lang->name); ?>
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
                   value="<?php echo esc_specialchars($contact->telephone ?? ''); ?>">
          </div>
          <div class="col-md-4 mb-3">
            <label class="form-label"><?php echo __('Cell/Mobile'); ?></label>
            <input type="tel" name="contacts[<?php echo $index; ?>][cell]" class="form-control"
                   value="<?php echo esc_specialchars($contact->cell ?? ''); ?>">
          </div>
          <div class="col-md-4 mb-3">
            <label class="form-label"><?php echo __('Fax'); ?></label>
            <input type="tel" name="contacts[<?php echo $index; ?>][fax]" class="form-control"
                   value="<?php echo esc_specialchars($contact->fax ?? ''); ?>">
          </div>
        </div>

        <div class="row">
          <div class="col-md-6 mb-3">
            <label class="form-label"><?php echo __('Email'); ?></label>
            <input type="email" name="contacts[<?php echo $index; ?>][email]" class="form-control"
                   value="<?php echo esc_specialchars($contact->email ?? ''); ?>">
          </div>
          <div class="col-md-6 mb-3">
            <label class="form-label"><?php echo __('Alternative email'); ?></label>
            <input type="email" name="contacts[<?php echo $index; ?>][alternative_email]" class="form-control"
                   value="<?php echo esc_specialchars($contact->alternative_email ?? ''); ?>">
          </div>
        </div>

        <div class="row">
          <div class="col-md-6 mb-3">
            <label class="form-label"><?php echo __('Website'); ?></label>
            <input type="url" name="contacts[<?php echo $index; ?>][website]" class="form-control"
                   value="<?php echo esc_specialchars($contact->website ?? ''); ?>" placeholder="https://">
          </div>
          <div class="col-md-6 mb-3">
            <label class="form-label"><?php echo __('Alternative phone'); ?></label>
            <input type="tel" name="contacts[<?php echo $index; ?>][alternative_phone]" class="form-control"
                   value="<?php echo esc_specialchars($contact->alternative_phone ?? ''); ?>">
          </div>
        </div>

        <hr>
        <h6 class="text-muted mb-3"><i class="bi bi-geo-alt me-2"></i><?php echo __('Physical location'); ?></h6>

        <div class="mb-3">
          <label class="form-label"><?php echo __('Street address'); ?></label>
          <textarea name="contacts[<?php echo $index; ?>][street_address]" class="form-control" rows="2"><?php echo esc_specialchars($contact->street_address ?? ''); ?></textarea>
        </div>

        <div class="row">
          <div class="col-md-4 mb-3">
            <label class="form-label"><?php echo __('City'); ?></label>
            <input type="text" name="contacts[<?php echo $index; ?>][city]" class="form-control"
                   value="<?php echo esc_specialchars($contact->city ?? ''); ?>">
          </div>
          <div class="col-md-4 mb-3">
            <label class="form-label"><?php echo __('Region/Province'); ?></label>
            <input type="text" name="contacts[<?php echo $index; ?>][region]" class="form-control"
                   value="<?php echo esc_specialchars($contact->region ?? ''); ?>">
          </div>
          <div class="col-md-4 mb-3">
            <label class="form-label"><?php echo __('Postal code'); ?></label>
            <input type="text" name="contacts[<?php echo $index; ?>][postal_code]" class="form-control"
                   value="<?php echo esc_specialchars($contact->postal_code ?? ''); ?>">
          </div>
        </div>

        <div class="row">
          <div class="col-md-4 mb-3">
            <label class="form-label"><?php echo __('Country'); ?></label>
            <input type="text" name="contacts[<?php echo $index; ?>][country_code]" class="form-control"
                   value="<?php echo esc_specialchars($contact->country_code ?? ''); ?>" placeholder="e.g. ZA, US, GB">
          </div>
          <div class="col-md-4 mb-3">
            <label class="form-label"><?php echo __('Latitude'); ?></label>
            <input type="text" name="contacts[<?php echo $index; ?>][latitude]" class="form-control"
                   value="<?php echo esc_specialchars($contact->latitude ?? ''); ?>">
          </div>
          <div class="col-md-4 mb-3">
            <label class="form-label"><?php echo __('Longitude'); ?></label>
            <input type="text" name="contacts[<?php echo $index; ?>][longitude]" class="form-control"
                   value="<?php echo esc_specialchars($contact->longitude ?? ''); ?>">
          </div>
        </div>

        <hr>

        <div class="mb-3">
          <label class="form-label"><?php echo __('Note'); ?></label>
          <textarea name="contacts[<?php echo $index; ?>][note]" class="form-control" rows="2"><?php echo esc_specialchars($contact->note ?? ''); ?></textarea>
        </div>

        <div class="form-check">
          <input type="checkbox" name="contacts[<?php echo $index; ?>][primary_contact]" value="1" class="form-check-input primary-contact-check"
                 <?php echo ($contact->primary_contact ?? 0) ? 'checked' : ''; ?>>
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
            <input type="text" name="contacts[__INDEX__][contact_person]" class="form-control">
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
            <input type="text" name="contacts[__INDEX__][contact_type]" class="form-control"
                   placeholder="<?php echo __('e.g., Primary, Business, Home'); ?>">
          </div>
        </div>

        <div class="row">
          <div class="col-md-4 mb-3">
            <label class="form-label"><?php echo __('ID/Passport number'); ?></label>
            <input type="text" name="contacts[__INDEX__][id_number]" class="form-control">
          </div>
          <div class="col-md-4 mb-3">
            <label class="form-label"><?php echo __('Preferred contact method'); ?></label>
            <select name="contacts[__INDEX__][preferred_contact_method]" class="form-select">
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
            <select name="contacts[__INDEX__][language_preference]" class="form-select">
              <option value=""><?php echo __('Select...'); ?></option>
              <?php foreach ($languages as $lang): ?>
                <option value="<?php echo $lang->id; ?>"><?php echo esc_specialchars($lang->name); ?></option>
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
            <input type="email" name="contacts[__INDEX__][alternative_email]" class="form-control">
          </div>
        </div>

        <div class="row">
          <div class="col-md-6 mb-3">
            <label class="form-label"><?php echo __('Website'); ?></label>
            <input type="url" name="contacts[__INDEX__][website]" class="form-control" placeholder="https://">
          </div>
          <div class="col-md-6 mb-3">
            <label class="form-label"><?php echo __('Alternative phone'); ?></label>
            <input type="tel" name="contacts[__INDEX__][alternative_phone]" class="form-control">
          </div>
        </div>

        <hr>
        <h6 class="text-muted mb-3"><i class="bi bi-geo-alt me-2"></i><?php echo __('Physical location'); ?></h6>

        <div class="mb-3">
          <label class="form-label"><?php echo __('Street address'); ?></label>
          <textarea name="contacts[__INDEX__][street_address]" class="form-control" rows="2"></textarea>
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
            <input type="text" name="contacts[__INDEX__][postal_code]" class="form-control">
          </div>
        </div>

        <div class="row">
          <div class="col-md-4 mb-3">
            <label class="form-label"><?php echo __('Country'); ?></label>
            <input type="text" name="contacts[__INDEX__][country_code]" class="form-control" placeholder="e.g. ZA, US, GB">
          </div>
          <div class="col-md-4 mb-3">
            <label class="form-label"><?php echo __('Latitude'); ?></label>
            <input type="text" name="contacts[__INDEX__][latitude]" class="form-control">
          </div>
          <div class="col-md-4 mb-3">
            <label class="form-label"><?php echo __('Longitude'); ?></label>
            <input type="text" name="contacts[__INDEX__][longitude]" class="form-control">
          </div>
        </div>

        <hr>

        <div class="mb-3">
          <label class="form-label"><?php echo __('Note'); ?></label>
          <textarea name="contacts[__INDEX__][note]" class="form-control" rows="2"></textarea>
        </div>

        <div class="form-check">
          <input type="checkbox" name="contacts[__INDEX__][primary_contact]" value="1" class="form-check-input primary-contact-check">
          <label class="form-check-label"><?php echo __('Primary contact'); ?></label>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
document.addEventListener('DOMContentLoaded', function() {
  const container = document.getElementById('contacts-container');
  const addBtn = document.getElementById('add-contact');
  const template = document.getElementById('contact-template');

  // Get next index
  function getNextIndex() {
    const entries = container.querySelectorAll('.contact-entry');
    let maxIndex = -1;
    entries.forEach(function(entry) {
      const idx = parseInt(entry.dataset.index, 10);
      if (idx > maxIndex) maxIndex = idx;
    });
    return maxIndex + 1;
  }

  // Update contact numbers
  function updateContactNumbers() {
    const entries = container.querySelectorAll('.contact-entry:not([style*="display: none"])');
    entries.forEach(function(entry, i) {
      const numSpan = entry.querySelector('.contact-number');
      if (numSpan) numSpan.textContent = i + 1;
    });

    // Show/hide remove buttons based on visible count
    const visibleEntries = container.querySelectorAll('.contact-entry:not([style*="display: none"])');
    visibleEntries.forEach(function(entry) {
      const removeBtn = entry.querySelector('.remove-contact');
      if (removeBtn) {
        removeBtn.style.display = visibleEntries.length > 1 ? '' : 'none';
      }
    });
  }

  // Add contact
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

    // Scroll to new contact
    newEntry.scrollIntoView({ behavior: 'smooth', block: 'start' });
  });

  // Remove contact (delegate)
  container.addEventListener('click', function(e) {
    if (e.target.closest('.remove-contact')) {
      const entry = e.target.closest('.contact-entry');
      const idInput = entry.querySelector('input[name$="[id]"]');
      const deleteFlag = entry.querySelector('.delete-flag');

      if (idInput && idInput.value) {
        // Existing contact - mark for deletion
        deleteFlag.value = '1';
        entry.style.display = 'none';
      } else {
        // New contact - just remove from DOM
        entry.remove();
      }

      updateContactNumbers();
    }
  });

  // Only one primary contact allowed
  container.addEventListener('change', function(e) {
    if (e.target.classList.contains('primary-contact-check') && e.target.checked) {
      container.querySelectorAll('.primary-contact-check').forEach(function(cb) {
        if (cb !== e.target) cb.checked = false;
      });
    }
  });

  // Initial update
  updateContactNumbers();
});
</script>
