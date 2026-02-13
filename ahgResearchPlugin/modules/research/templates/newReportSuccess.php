<?php decorate_with('layout_1col.php') ?>
<?php slot('title') ?>
<h1><i class="fas fa-file-alt text-primary me-2"></i><?php echo __('Create New Report'); ?></h1>
<?php end_slot() ?>

<?php slot('content') ?>
<?php
$projects = isset($projects) && is_array($projects) ? $projects : (isset($projects) && method_exists($projects, 'getRawValue') ? $projects->getRawValue() : (isset($projects) && is_iterable($projects) ? iterator_to_array($projects) : []));
$templates = isset($templates) && is_array($templates) ? $templates : (isset($templates) && method_exists($templates, 'getRawValue') ? $templates->getRawValue() : (isset($templates) && is_iterable($templates) ? iterator_to_array($templates) : []));
?>

<?php if ($sf_user->hasFlash('error')): ?>
  <div class="alert alert-danger alert-dismissible fade show"><?php echo $sf_user->getFlash('error'); ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>

<nav aria-label="breadcrumb" class="mb-3">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'research', 'action' => 'dashboard']); ?>"><?php echo __('Research'); ?></a></li>
    <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'research', 'action' => 'reports']); ?>"><?php echo __('Reports'); ?></a></li>
    <li class="breadcrumb-item active"><?php echo __('New Report'); ?></li>
  </ol>
</nav>

<form method="post" id="newReportForm">
  <input type="hidden" name="form_action" value="create">
  <input type="hidden" name="template_type" id="selectedTemplate" value="custom">

  <!-- Step 1: Choose Template -->
  <h5 class="mb-3"><span class="badge bg-primary me-2">1</span><?php echo __('Choose a Template'); ?></h5>
  <div class="row mb-4">
    <?php
    $defaultTemplates = [
      'research_summary' => [
        'icon' => 'fas fa-clipboard-list',
        'color' => 'primary',
        'label' => 'Research Summary',
        'desc' => 'General research summary with introduction, methodology, findings, and conclusions.',
      ],
      'genealogical' => [
        'icon' => 'fas fa-sitemap',
        'color' => 'success',
        'label' => 'Genealogical Report',
        'desc' => 'Family history report with pedigree charts, source citations, and lineage documentation.',
      ],
      'historical' => [
        'icon' => 'fas fa-landmark',
        'color' => 'info',
        'label' => 'Historical Analysis',
        'desc' => 'In-depth historical analysis with context, primary sources, and chronological narrative.',
      ],
      'source_analysis' => [
        'icon' => 'fas fa-search',
        'color' => 'warning',
        'label' => 'Source Analysis',
        'desc' => 'Critical analysis of archival sources with provenance, reliability assessment, and interpretation.',
      ],
      'finding_aid' => [
        'icon' => 'fas fa-map',
        'color' => 'secondary',
        'label' => 'Finding Aid',
        'desc' => 'Structured finding aid with scope, arrangement, access conditions, and container list.',
      ],
      'custom' => [
        'icon' => 'fas fa-pencil-alt',
        'color' => 'dark',
        'label' => 'Custom Report',
        'desc' => 'Start with a blank report and add your own sections as needed.',
      ],
    ];
    ?>
    <?php foreach ($defaultTemplates as $code => $tpl): ?>
    <div class="col-md-4 mb-3">
      <div class="card h-100 template-card" data-template="<?php echo $code; ?>" role="button" style="cursor: pointer;">
        <div class="card-body text-center">
          <div class="mb-3">
            <i class="<?php echo $tpl['icon']; ?> fa-2x text-<?php echo $tpl['color']; ?>"></i>
          </div>
          <h6 class="card-title"><?php echo __($tpl['label']); ?></h6>
          <p class="card-text small text-muted"><?php echo __($tpl['desc']); ?></p>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>

  <!-- Step 2: Report Details -->
  <h5 class="mb-3"><span class="badge bg-primary me-2">2</span><?php echo __('Report Details'); ?></h5>
  <div class="card mb-4">
    <div class="card-body">
      <div class="mb-3">
        <label class="form-label"><?php echo __('Report Title'); ?> *</label>
        <input type="text" name="title" class="form-control" required placeholder="<?php echo __('Enter report title...'); ?>">
      </div>
      <div class="mb-3">
        <label class="form-label"><?php echo __('Description'); ?></label>
        <textarea name="description" class="form-control" rows="3" placeholder="<?php echo __('Brief description of the report...'); ?>"></textarea>
      </div>
      <div class="mb-3">
        <label class="form-label"><?php echo __('Project'); ?></label>
        <select name="project_id" class="form-select">
          <option value=""><?php echo __('No Project'); ?></option>
          <?php foreach ($projects as $project): ?>
            <option value="<?php echo $project->id; ?>"><?php echo htmlspecialchars($project->title); ?></option>
          <?php endforeach; ?>
        </select>
        <div class="form-text"><?php echo __('Link this report to a research project to auto-populate data.'); ?></div>
      </div>
    </div>
  </div>

  <div class="d-flex gap-2">
    <button type="submit" class="btn btn-primary btn-lg"><i class="fas fa-plus me-1"></i><?php echo __('Create Report'); ?></button>
    <a href="<?php echo url_for(['module' => 'research', 'action' => 'reports']); ?>" class="btn btn-secondary btn-lg"><?php echo __('Cancel'); ?></a>
  </div>
</form>

<style <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
.template-card { transition: all 0.2s ease; border: 2px solid transparent; }
.template-card:hover { border-color: #0d6efd; box-shadow: 0 0.25rem 0.5rem rgba(0,0,0,0.1); }
.template-card.selected { border-color: #0d6efd; background-color: #f0f7ff; }
</style>

<script <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
document.addEventListener('DOMContentLoaded', function() {
  var cards = document.querySelectorAll('.template-card');
  var hiddenInput = document.getElementById('selectedTemplate');

  // Select 'custom' by default
  document.querySelector('.template-card[data-template="custom"]').classList.add('selected');

  cards.forEach(function(card) {
    card.addEventListener('click', function() {
      cards.forEach(function(c) { c.classList.remove('selected'); });
      card.classList.add('selected');
      hiddenInput.value = card.dataset.template;
    });
  });
});
</script>
<?php end_slot() ?>
