<?php decorate_with('layout_1col.php'); ?>
<?php
/*
 * Public "check content credentials" tool: upload any file (image, PDF or a
 * .c2pa.json sidecar) and get its C2PA verdict. Nothing is stored.
 * PSIS twin of Heratio resources/views/verify/check.blade.php. A plain
 * multipart form that works without JavaScript; the nonce'd script only adds
 * drag-and-drop and a chosen-file label.
 */
$state = $sf_data->getRaw('state');
$result = $sf_data->getRaw('result');
$fileName = $sf_data->getRaw('fileName');
$errorMsg = $sf_data->getRaw('errorMsg');
$h = static fn ($v): string => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
?>
<?php slot('title'); ?>
<h1><i class="fas fa-shield-alt me-2" aria-hidden="true"></i><?php echo __('Check content credentials'); ?></h1>
<?php end_slot(); ?>

<?php slot('content'); ?>
<style <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
  #c2pa-dropzone { border-style: dashed !important; cursor: pointer; }
  #c2pa-file { max-width: 24rem; margin: 0 auto; }
</style>

<p class="text-muted"><?php echo __('Drop in any image, PDF or C2PA sidecar manifest to check whether it carries signed content credentials, and what they say. Authenticity is checked against the file\'s own cryptographic signature using the open C2PA standard. This works for files from anywhere - not only material held here.'); ?></p>

<?php if (!empty($errorMsg)) { ?>
  <div class="alert alert-warning" role="alert"><i class="fas fa-exclamation-triangle me-1" aria-hidden="true"></i><?php echo $h($errorMsg); ?></div>
<?php } ?>

<div class="card mb-3">
  <div class="card-header"><i class="fas fa-upload me-2" aria-hidden="true"></i><?php echo __('Choose a file'); ?></div>
  <div class="card-body">
    <form id="c2pa-check-form" action="<?php echo url_for('@ahg_c2pa_check'); ?>" method="post" enctype="multipart/form-data">
      <input type="hidden" name="_ahg_csrf_token" value="<?php echo htmlspecialchars(class_exists('\AtomFramework\Services\CsrfService') ? \AtomFramework\Services\CsrfService::generateToken() : '', ENT_QUOTES); ?>">
      <div id="c2pa-dropzone" class="border border-2 border-secondary-subtle rounded text-center p-4 mb-3 bg-light">
        <i class="fas fa-cloud-upload-alt fa-2x mb-2 text-muted" aria-hidden="true"></i>
        <p class="mb-2 fw-semibold"><?php echo __('Drag a file here, or click to browse'); ?></p>
        <p class="text-muted small mb-2" id="c2pa-chosen" aria-live="polite"></p>
        <input type="file" id="c2pa-file" name="file" class="form-control"
               aria-label="<?php echo $h(__('File to check')); ?>"
               accept="image/jpeg,image/png,image/tiff,image/webp,image/gif,image/avif,image/jp2,application/pdf,application/json,.jpg,.jpeg,.png,.tif,.tiff,.webp,.gif,.avif,.jp2,.pdf,.json">
      </div>
      <p class="text-muted small mb-3"><i class="fas fa-lock me-1" aria-hidden="true"></i><?php echo __('Your file is checked and discarded when the request ends. Nothing is stored and no account is needed. JPEG, PNG, TIFF, WebP, PDF or .c2pa.json, up to 25 MB.'); ?></p>
      <button type="submit" class="btn btn-primary"><i class="fas fa-shield-alt me-1" aria-hidden="true"></i><?php echo __('Check this file'); ?></button>
    </form>
  </div>
</div>

<?php if (!empty($state)) { ?>
  <?php
    $stateBadge = match ($state) {
        'verified' => ['bg-success', 'fa-check-circle', __('Content credentials verified')],
        'invalid'  => ['bg-danger', 'fa-times-circle', __('Could not be verified')],
        default    => ['bg-secondary', 'fa-minus-circle', __('No content credentials')],
    };
    $assertions = $result['assertions'] ?? [];
    $resultErrors = $result['errors'] ?? [];
  ?>
  <div class="card mb-3 border-2 border-success-subtle">
    <div class="card-header bg-success text-white"><i class="fas fa-certificate me-2" aria-hidden="true"></i><?php echo __('Verdict'); ?></div>
    <div class="card-body">
      <?php if (!empty($fileName)) { ?>
        <p class="text-muted small mb-2"><i class="fas fa-file me-1" aria-hidden="true"></i><?php echo $h($fileName); ?></p>
      <?php } ?>
      <p class="mb-2"><span class="badge <?php echo $stateBadge[0]; ?> fs-5"><i class="fas <?php echo $stateBadge[1]; ?> me-1" aria-hidden="true"></i><?php echo $h($stateBadge[2]); ?></span></p>
      <?php if ($state === 'verified') { ?>
        <p class="text-success mb-0"><i class="fas fa-lock me-1" aria-hidden="true"></i><?php echo __('This file carries signed content credentials, and they have not been altered since they were created. Every step below re-computes to the value pinned in the signed claim, and the Ed25519 signature checks out.'); ?></p>
        <?php if (!empty($result['signer'])) { ?>
          <p class="text-muted small mt-2 mb-0"><?php echo __('Signed under key'); ?> <code><?php echo $h($result['signer']); ?></code>.</p>
        <?php } ?>
      <?php } elseif ($state === 'invalid') { ?>
        <p class="text-danger mb-0"><i class="fas fa-exclamation-triangle me-1" aria-hidden="true"></i><?php echo __('This file has content credentials, but they could not be verified. The signature does not match the recorded content, or it was signed by a key this service does not recognise. Treat the steps below with caution - the credentials may have been tampered with.'); ?></p>
        <?php if (!empty($resultErrors)) { ?>
          <ul class="text-danger small mt-2 mb-0">
            <?php foreach ($resultErrors as $err) { ?><li><?php echo $h($err); ?></li><?php } ?>
          </ul>
        <?php } ?>
      <?php } else { ?>
        <p class="text-muted mb-0"><?php echo __('This file has no content credentials we can read. That does not mean it is fake - only that it carries no signed provenance that this checker can verify. Some files embed credentials in a form that needs the native C2PA tool to read; if you have a sidecar manifest for this file, upload that instead.'); ?></p>
      <?php } ?>
    </div>
  </div>

  <?php if (!empty($assertions)) { ?>
    <div class="card mb-3">
      <div class="card-header"><i class="fas fa-list-ol me-2" aria-hidden="true"></i><?php echo __('What the credentials say'); ?></div>
      <div class="card-body">
        <p class="text-muted small"><?php echo __('The actions and declarations recorded in the signed manifest, in order.'); ?></p>
        <?php include_partial('c2paVerify/steps', ['steps' => $assertions]); ?>
      </div>
    </div>
  <?php } ?>

  <p class="mb-3"><a href="<?php echo url_for('@ahg_c2pa_check'); ?>" class="btn btn-outline-secondary btn-sm"><i class="fas fa-redo me-1" aria-hidden="true"></i><?php echo __('Check another file'); ?></a></p>
<?php } ?>

<p class="text-muted small"><i class="fas fa-info-circle me-1" aria-hidden="true"></i><?php echo __('Content credentials follow the C2PA open standard. Signatures are Ed25519 and checked live, every time - nothing here is cached or stored.'); ?></p>

<script <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
  (function () {
    'use strict';
    var dz = document.getElementById('c2pa-dropzone');
    var input = document.getElementById('c2pa-file');
    var chosen = document.getElementById('c2pa-chosen');
    if (!dz || !input) {
      return;
    }
    function setChosen() {
      if (chosen) {
        chosen.textContent = (input.files && input.files.length) ? input.files[0].name : '';
      }
    }
    dz.addEventListener('click', function (e) {
      if (e.target !== input) {
        input.click();
      }
    });
    input.addEventListener('change', setChosen);
    ['dragenter', 'dragover'].forEach(function (name) {
      dz.addEventListener(name, function (e) {
        e.preventDefault();
        e.stopPropagation();
        dz.classList.add('border-primary');
      });
    });
    ['dragleave', 'drop'].forEach(function (name) {
      dz.addEventListener(name, function (e) {
        e.preventDefault();
        e.stopPropagation();
        dz.classList.remove('border-primary');
      });
    });
    dz.addEventListener('drop', function (e) {
      var dt = e.dataTransfer;
      if (dt && dt.files && dt.files.length) {
        try {
          input.files = dt.files;
        } catch (err) {
          // Older browsers: the user can still click to browse.
        }
        setChosen();
      }
    });
  })();
</script>
<?php end_slot(); ?>
