<?php decorate_with('layout_2col') ?>
<?php slot('sidebar') ?>
<?php include_partial('training/trainingSidebar', ['active' => $sidebarActive ?? 'training']) ?>
<?php end_slot() ?>
<?php
$enrol = isset($enrol) && is_array($enrol) ? $enrol : [];
$course = isset($course) && is_array($course) ? $course : [];
$cert = isset($cert) && is_array($cert) ? $cert : [];
$issued = !empty($cert['issued_at']) ? date('j F Y', strtotime((string) $cert['issued_at'])) : '';
?>
<?php slot('title') ?>
<div class="d-flex justify-content-between align-items-center">
  <h1><i class="fas fa-certificate text-success me-2"></i><?php echo __('Certificate'); ?></h1>
  <button type="button" class="btn btn-outline-secondary" onclick="window.print()"><i class="fas fa-print me-1"></i><?php echo __('Print'); ?></button>
</div>
<?php end_slot() ?>

<?php slot('content') ?>
<div class="card border-success shadow-sm mx-auto" style="max-width: 720px;">
  <div class="card-body text-center p-5">
    <p class="text-uppercase text-muted mb-1" style="letter-spacing:.2em;"><?php echo __('Certificate of completion'); ?></p>
    <h2 class="my-4"><?php echo htmlspecialchars((string) ($course['title'] ?? '')); ?></h2>
    <p class="mb-1"><?php echo __('This certifies that'); ?></p>
    <h3 class="mb-4"><?php echo htmlspecialchars((string) ($enrol['learner_name'] ?? '')); ?></h3>
    <p class="mb-4"><?php echo __('has successfully completed the course with a score of'); ?> <strong><?php echo (int) ($cert['score'] ?? 0); ?>%</strong>.</p>
    <hr>
    <div class="row text-start small text-muted mt-3">
      <div class="col-6"><strong><?php echo __('Certificate no.'); ?></strong><br><?php echo htmlspecialchars((string) ($cert['certificate_no'] ?? '')); ?></div>
      <div class="col-6 text-end"><strong><?php echo __('Issued'); ?></strong><br><?php echo htmlspecialchars($issued); ?></div>
    </div>
  </div>
</div>
<?php end_slot() ?>
