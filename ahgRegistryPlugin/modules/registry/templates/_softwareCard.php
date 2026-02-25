<?php
  $catBg = [
    'ams' => 'bg-primary',
    'dams' => 'bg-success',
    'cms' => 'bg-info text-dark',
    'ils' => 'bg-warning text-dark',
    'preservation' => 'bg-dark',
    'digitization' => 'bg-secondary',
    'discovery' => 'bg-danger',
  ];
  $cat = $item->category ?? '';
  $catClass = $catBg[strtolower($cat)] ?? 'bg-info text-dark';

  $licenseBg = [
    'open_source' => 'bg-success',
    'proprietary' => 'bg-danger',
    'freemium' => 'bg-warning text-dark',
    'saas' => 'bg-primary',
  ];
  $lic = $item->license ?? '';
  $licClass = $licenseBg[strtolower(str_replace(' ', '_', $lic))] ?? 'bg-secondary';

  $pricingBg = [
    'free' => 'bg-success',
    'subscription' => 'bg-primary',
    'one_time' => 'bg-info text-dark',
    'per_user' => 'bg-warning text-dark',
    'custom' => 'bg-secondary',
  ];
  $pm = $item->pricing_model ?? '';
  $pmClass = $pricingBg[strtolower(str_replace(' ', '_', $pm))] ?? 'bg-secondary';

  $gitIcons = [
    'github' => 'fab fa-github',
    'gitlab' => 'fab fa-gitlab',
    'bitbucket' => 'fab fa-bitbucket',
  ];
?>
<div class="col">
  <div class="card h-100">
    <div class="card-body">
      <div class="d-flex align-items-start mb-2">
        <?php if (!empty($item->logo_path)): ?>
          <img src="<?php echo htmlspecialchars($item->logo_path, ENT_QUOTES, 'UTF-8'); ?>" alt="" class="rounded me-3 flex-shrink-0" style="width: 48px; height: 48px; object-fit: contain;">
        <?php else: ?>
          <div class="bg-light rounded me-3 d-flex align-items-center justify-content-center flex-shrink-0" style="width: 48px; height: 48px;">
            <i class="fas fa-code text-muted"></i>
          </div>
        <?php endif; ?>
        <div class="min-width-0">
          <h6 class="card-title mb-1">
            <a href="<?php echo url_for(['module' => 'registry', 'action' => 'softwareView', 'slug' => $item->slug ?? '']); ?>" class="text-decoration-none stretched-link">
              <?php echo htmlspecialchars($item->name ?? '', ENT_QUOTES, 'UTF-8'); ?>
            </a>
            <?php if (!empty($item->is_verified)): ?>
              <i class="fas fa-check-circle text-primary ms-1" title="<?php echo __('Verified'); ?>"></i>
            <?php endif; ?>
          </h6>
          <div>
            <span class="badge <?php echo $catClass; ?>"><?php echo htmlspecialchars(strtoupper($cat), ENT_QUOTES, 'UTF-8'); ?></span>
            <?php if (!empty($lic)): ?>
              <span class="badge <?php echo $licClass; ?>"><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $lic)), ENT_QUOTES, 'UTF-8'); ?></span>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <?php if (!empty($item->git_url)): ?>
        <?php
          $gitProvider = '';
          if (stripos($item->git_url, 'github.com') !== false) { $gitProvider = 'github'; }
          elseif (stripos($item->git_url, 'gitlab.com') !== false) { $gitProvider = 'gitlab'; }
          elseif (stripos($item->git_url, 'bitbucket.org') !== false) { $gitProvider = 'bitbucket'; }
          $gitIcon = $gitIcons[$gitProvider] ?? 'fas fa-code-branch';
        ?>
        <div class="small mb-2">
          <a href="<?php echo htmlspecialchars($item->git_url, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener" class="text-decoration-none">
            <i class="<?php echo $gitIcon; ?> me-1"></i><?php echo __('Source'); ?>
            <i class="fas fa-external-link-alt ms-1" style="font-size: 0.7em;"></i>
          </a>
        </div>
      <?php endif; ?>

      <div class="mb-2">
        <?php if (!empty($item->latest_version)): ?>
          <span class="badge bg-secondary">v<?php echo htmlspecialchars($item->latest_version, ENT_QUOTES, 'UTF-8'); ?></span>
        <?php endif; ?>
        <?php if (!empty($pm)): ?>
          <span class="badge <?php echo $pmClass; ?>"><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $pm)), ENT_QUOTES, 'UTF-8'); ?></span>
        <?php endif; ?>
      </div>

      <?php if (!empty($item->average_rating) && ($item->rating_count ?? 0) > 0): ?>
      <div class="mb-2">
        <?php include_partial('registry/ratingStars', ['rating' => (float) $item->average_rating, 'count' => (int) ($item->rating_count ?? 0)]); ?>
      </div>
      <?php endif; ?>

      <?php
        $desc = $item->short_description ?? ($item->description ?? '');
        if (!empty($desc)):
      ?>
      <p class="card-text small text-muted mb-0">
        <?php echo htmlspecialchars(mb_strimwidth(strip_tags($desc), 0, 120, '...'), ENT_QUOTES, 'UTF-8'); ?>
      </p>
      <?php endif; ?>
    </div>
  </div>
</div>
