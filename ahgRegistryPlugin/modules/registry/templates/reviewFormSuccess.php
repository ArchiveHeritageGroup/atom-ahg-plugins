<?php decorate_with(sfConfig::get('sf_plugins_dir').'/ahgRegistryPlugin/modules/registry/templates/layout_registry'); ?>

<?php slot('title'); ?><?php echo __('Write a Review'); ?><?php end_slot(); ?>

<?php slot('content'); ?>

<?php $n = sfConfig::get('csp_nonce', ''); $na = $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>

<?php include_partial('registry/breadcrumb', ['items' => [
  ['label' => __('Home'), 'url' => url_for('@homepage')],
  ['label' => __('Registry'), 'url' => url_for(['module' => 'registry', 'action' => 'index'])],
  ['label' => __('Write a Review')],
]]); ?>

<div class="row justify-content-center">
  <div class="col-lg-7">

    <h1 class="h3 mb-4"><?php echo __('Write a Review'); ?></h1>

    <?php if (isset($errors) && count($errors) > 0): ?>
      <div class="alert alert-danger">
        <ul class="mb-0">
          <?php foreach ($errors as $err): ?>
            <li><?php echo htmlspecialchars($err, ENT_QUOTES, 'UTF-8'); ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <!-- Entity display -->
    <div class="card mb-4">
      <div class="card-body">
        <div class="d-flex align-items-center">
          <?php if (!empty($entity->logo_path)): ?>
            <img src="<?php echo htmlspecialchars($entity->logo_path, ENT_QUOTES, 'UTF-8'); ?>" alt="" class="rounded me-3" style="width: 50px; height: 50px; object-fit: contain;">
          <?php else: ?>
            <div class="bg-light rounded me-3 d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
              <i class="fas fa-<?php echo 'vendor' === $entityType ? 'handshake' : 'laptop-code'; ?> text-muted"></i>
            </div>
          <?php endif; ?>
          <div>
            <h5 class="mb-0"><?php echo htmlspecialchars($entity->name ?? '', ENT_QUOTES, 'UTF-8'); ?></h5>
            <small class="text-muted"><?php echo htmlspecialchars(ucfirst($entityType), ENT_QUOTES, 'UTF-8'); ?></small>
          </div>
        </div>
      </div>
    </div>

    <form method="post" action="<?php echo url_for(['module' => 'registry', 'action' => 'myInstitutionReview', 'type' => $entityType, 'id' => $entityId]); ?>">

      <div class="card mb-4">
        <div class="card-body">

          <!-- Star rating -->
          <div class="mb-4">
            <label class="form-label fw-semibold"><?php echo __('Rating'); ?> <span class="text-danger">*</span></label>
            <div id="star-rating" class="d-flex gap-1" role="group" aria-label="<?php echo __('Star rating'); ?>">
              <?php for ($i = 1; $i <= 5; $i++): ?>
                <span class="star-btn fs-3" data-value="<?php echo $i; ?>" style="cursor: pointer; color: #ddd;" title="<?php echo $i; ?> <?php echo __('star'); ?><?php echo $i > 1 ? 's' : ''; ?>">
                  <i class="fas fa-star"></i>
                </span>
              <?php endfor; ?>
            </div>
            <input type="hidden" name="rating" id="rating-value" value="<?php echo htmlspecialchars($sf_request->getParameter('rating', '5'), ENT_QUOTES, 'UTF-8'); ?>">
          </div>

          <!-- Reviewer name -->
          <div class="mb-3">
            <label for="rv-name" class="form-label"><?php echo __('Your Name'); ?></label>
            <?php
              $userName = sfContext::getInstance()->getUser()->getAttribute('user_name', '');
            ?>
            <input type="text" class="form-control" id="rv-name" name="reviewer_name" value="<?php echo htmlspecialchars($sf_request->getParameter('reviewer_name', $userName), ENT_QUOTES, 'UTF-8'); ?>">
          </div>

          <!-- Title -->
          <div class="mb-3">
            <label for="rv-title" class="form-label"><?php echo __('Review Title'); ?></label>
            <input type="text" class="form-control" id="rv-title" name="title" value="<?php echo htmlspecialchars($sf_request->getParameter('title', ''), ENT_QUOTES, 'UTF-8'); ?>" placeholder="<?php echo __('Summarize your experience'); ?>">
          </div>

          <!-- Comment -->
          <div class="mb-3">
            <label for="rv-comment" class="form-label"><?php echo __('Review'); ?> <span class="text-danger">*</span></label>
            <textarea class="form-control" id="rv-comment" name="comment" rows="5" required placeholder="<?php echo __('Share your experience with this vendor or software...'); ?>"><?php echo htmlspecialchars($sf_request->getParameter('comment', ''), ENT_QUOTES, 'UTF-8'); ?></textarea>
          </div>

        </div>
      </div>

      <div class="d-flex justify-content-between">
        <a href="javascript:history.back();" class="btn btn-outline-secondary"><?php echo __('Cancel'); ?></a>
        <button type="submit" class="btn btn-primary"><i class="fas fa-paper-plane me-1"></i> <?php echo __('Submit Review'); ?></button>
      </div>

    </form>

  </div>
</div>

<script <?php echo $na; ?>>
document.addEventListener('DOMContentLoaded', function() {
  var stars = document.querySelectorAll('#star-rating .star-btn');
  var ratingInput = document.getElementById('rating-value');
  var currentRating = parseInt(ratingInput.value) || 5;

  function updateStars(val) {
    stars.forEach(function(s) {
      var sv = parseInt(s.getAttribute('data-value'));
      s.style.color = sv <= val ? '#ffc107' : '#ddd';
    });
  }

  stars.forEach(function(s) {
    s.addEventListener('click', function() {
      currentRating = parseInt(this.getAttribute('data-value'));
      ratingInput.value = currentRating;
      updateStars(currentRating);
    });
    s.addEventListener('mouseenter', function() {
      updateStars(parseInt(this.getAttribute('data-value')));
    });
  });

  document.getElementById('star-rating').addEventListener('mouseleave', function() {
    updateStars(currentRating);
  });

  updateStars(currentRating);
});
</script>

<?php end_slot(); ?>
