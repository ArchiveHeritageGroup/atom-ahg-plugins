<?php decorate_with(sfConfig::get('sf_plugins_dir').'/ahgRegistryPlugin/modules/registry/templates/layout_registry'); ?>

<?php slot('title'); ?><?php echo $newsletter ? __('Edit Newsletter') : __('New Newsletter'); ?> — Admin<?php end_slot(); ?>

<?php slot('content'); ?>

<?php $n = sfConfig::get('csp_nonce', ''); $na = $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>

<?php include_partial('registry/breadcrumb', ['items' => [
  ['label' => __('Home'), 'url' => url_for('@homepage')],
  ['label' => __('Registry'), 'url' => url_for(['module' => 'registry', 'action' => 'index'])],
  ['label' => __('Admin'), 'url' => url_for(['module' => 'registry', 'action' => 'adminDashboard'])],
  ['label' => __('Newsletters'), 'url' => url_for(['module' => 'registry', 'action' => 'adminNewsletters'])],
  ['label' => $newsletter ? __('Edit') : __('New')],
]]); ?>

<div class="row justify-content-center">
  <div class="col-lg-10">

    <h1 class="h3 mb-4">
      <i class="fas fa-<?php echo $newsletter ? 'edit' : 'plus'; ?> me-2"></i>
      <?php echo $newsletter ? __('Edit Newsletter') : __('New Newsletter'); ?>
    </h1>

    <?php if (isset($errors) && count($errors) > 0): ?>
      <div class="alert alert-danger">
        <ul class="mb-0">
          <?php foreach ($errors as $err): ?>
            <li><?php echo htmlspecialchars($err, ENT_QUOTES, 'UTF-8'); ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <?php
      $formAction = $newsletter && !empty($newsletter->id)
        ? url_for(['module' => 'registry', 'action' => 'adminNewsletterForm', 'id' => $newsletter->id])
        : url_for(['module' => 'registry', 'action' => 'adminNewsletterForm']);
    ?>

    <form method="post" action="<?php echo $formAction; ?>">
      <div class="card mb-4">
        <div class="card-body">

          <div class="mb-3">
            <label for="nl-subject" class="form-label fw-semibold"><?php echo __('Subject'); ?> <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="nl-subject" name="subject" required
                   value="<?php echo htmlspecialchars($newsletter->subject ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                   placeholder="<?php echo __('Newsletter subject line...'); ?>">
          </div>

          <div class="mb-3">
            <label for="nl-excerpt" class="form-label fw-semibold"><?php echo __('Excerpt / Preview Text'); ?></label>
            <input type="text" class="form-control" id="nl-excerpt" name="excerpt"
                   value="<?php echo htmlspecialchars($newsletter->excerpt ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                   placeholder="<?php echo __('Brief preview text shown in email clients...'); ?>">
            <div class="form-text"><?php echo __('Optional — shown as preview in email clients.'); ?></div>
          </div>

          <div class="mb-3">
            <label for="nl-content" class="form-label fw-semibold"><?php echo __('Content'); ?> <span class="text-danger">*</span></label>
            <div id="editor-toolbar">
              <span class="ql-formats">
                <select class="ql-header">
                  <option value="2">Heading</option>
                  <option value="3">Sub-heading</option>
                  <option selected>Normal</option>
                </select>
              </span>
              <span class="ql-formats">
                <button class="ql-bold"></button>
                <button class="ql-italic"></button>
                <button class="ql-underline"></button>
              </span>
              <span class="ql-formats">
                <button class="ql-list" value="ordered"></button>
                <button class="ql-list" value="bullet"></button>
              </span>
              <span class="ql-formats">
                <button class="ql-link"></button>
                <button class="ql-image"></button>
              </span>
              <span class="ql-formats">
                <button class="ql-blockquote"></button>
                <button class="ql-code-block"></button>
              </span>
              <span class="ql-formats">
                <button class="ql-clean"></button>
              </span>
            </div>
            <div id="editor-container" style="min-height:300px;"></div>
            <textarea name="content" id="nl-content" class="d-none"><?php echo htmlspecialchars($newsletter->content ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
          </div>

        </div>
      </div>

      <div class="d-flex justify-content-between">
        <a href="<?php echo url_for(['module' => 'registry', 'action' => 'adminNewsletters']); ?>" class="btn btn-outline-secondary">
          <i class="fas fa-arrow-left me-1"></i> <?php echo __('Back to Newsletters'); ?>
        </a>
        <button type="submit" class="btn btn-primary">
          <i class="fas fa-save me-1"></i> <?php echo $newsletter ? __('Update Newsletter') : __('Create Newsletter'); ?>
        </button>
      </div>
    </form>

  </div>
</div>

<!-- Quill.js Rich Text Editor -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/quill@2.0.3/dist/quill.snow.css">
<script <?php echo $na; ?> src="https://cdn.jsdelivr.net/npm/quill@2.0.3/dist/quill.js"></script>
<script <?php echo $na; ?>>
document.addEventListener('DOMContentLoaded', function() {
  var quill = new Quill('#editor-container', {
    theme: 'snow',
    modules: {
      toolbar: '#editor-toolbar'
    },
    placeholder: 'Write your newsletter content here...'
  });

  // Load existing content
  var existing = document.getElementById('nl-content').value;
  if (existing) {
    quill.root.innerHTML = existing;
  }

  // Sync to hidden textarea on form submit
  quill.root.closest('form').addEventListener('submit', function() {
    document.getElementById('nl-content').value = quill.root.innerHTML;
  });
});
</script>

<?php end_slot(); ?>
