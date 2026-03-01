<?php decorate_with('layout_1col'); ?>

<?php
  $rawActor    = $sf_data->getRaw('actor');
  $rawContacts = $sf_data->getRaw('contacts');

  $actor    = is_object($rawActor) ? $rawActor : (object) $rawActor;
  $contacts = is_array($rawContacts) ? $rawContacts : [];
?>

<?php slot('title'); ?>
  <h1><i class="fas fa-address-book me-2"></i><?php echo __('Contact Information'); ?></h1>
<?php end_slot(); ?>

<?php slot('before-content'); ?>
  <nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb">
      <li class="breadcrumb-item">
        <a href="<?php echo url_for('@ahg_authority_dashboard'); ?>"><?php echo __('Authority Dashboard'); ?></a>
      </li>
      <li class="breadcrumb-item">
        <a href="/<?php echo $actor->slug ?? ''; ?>"><?php echo htmlspecialchars($actor->name ?? ''); ?></a>
      </li>
      <li class="breadcrumb-item active"><?php echo __('Contact'); ?></li>
    </ol>
  </nav>
<?php end_slot(); ?>

<?php slot('content'); ?>

  <div class="card mb-3">
    <div class="card-header">
      <i class="fas fa-address-book me-1"></i><?php echo __('Contact details for %1%', ['%1%' => htmlspecialchars($actor->name ?? '')]); ?>
    </div>
    <div class="card-body">
      <?php if (empty($contacts)): ?>
        <p class="text-muted"><?php echo __('No contact information available for this authority record.'); ?></p>
        <p>
          <a href="/<?php echo $actor->slug ?? ''; ?>" class="btn btn-outline-primary">
            <i class="fas fa-edit me-1"></i><?php echo __('Edit actor record to add contacts'); ?>
          </a>
        </p>
      <?php else: ?>
        <div class="row g-3">
          <?php foreach ($contacts as $contact): ?>
            <div class="col-md-6">
              <div class="card border">
                <div class="card-body">
                  <?php if (!empty($contact->contact_person)): ?>
                    <h6><?php echo htmlspecialchars($contact->contact_person); ?></h6>
                  <?php endif; ?>

                  <?php if (!empty($contact->street_address)): ?>
                    <p class="mb-1">
                      <i class="fas fa-map-marker-alt text-muted me-1"></i>
                      <?php echo nl2br(htmlspecialchars($contact->street_address)); ?>
                      <?php if (!empty($contact->city)): ?><br><?php echo htmlspecialchars($contact->city); ?><?php endif; ?>
                      <?php if (!empty($contact->region)): ?>, <?php echo htmlspecialchars($contact->region); ?><?php endif; ?>
                      <?php if (!empty($contact->postal_code)): ?> <?php echo htmlspecialchars($contact->postal_code); ?><?php endif; ?>
                      <?php if (!empty($contact->country_code)): ?><br><?php echo htmlspecialchars($contact->country_code); ?><?php endif; ?>
                    </p>
                  <?php endif; ?>

                  <?php if (!empty($contact->telephone)): ?>
                    <p class="mb-1"><i class="fas fa-phone text-muted me-1"></i><?php echo htmlspecialchars($contact->telephone); ?></p>
                  <?php endif; ?>
                  <?php if (!empty($contact->email)): ?>
                    <p class="mb-1"><i class="fas fa-envelope text-muted me-1"></i>
                      <a href="mailto:<?php echo htmlspecialchars($contact->email); ?>"><?php echo htmlspecialchars($contact->email); ?></a>
                    </p>
                  <?php endif; ?>
                  <?php if (!empty($contact->website)): ?>
                    <p class="mb-0"><i class="fas fa-globe text-muted me-1"></i>
                      <a href="<?php echo htmlspecialchars($contact->website); ?>" target="_blank" rel="noopener"><?php echo htmlspecialchars($contact->website); ?></a>
                    </p>
                  <?php endif; ?>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>

<?php end_slot(); ?>
