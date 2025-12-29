<?php
/**
 * Header Section Block Template
 */
$showLogo = $config['show_logo'] ?? true;
$showTitle = $config['show_title'] ?? true;
$customTitle = $config['title'] ?? '';
$showNav = $config['show_nav'] ?? true;
$bgColor = $config['background_color'] ?? '#ffffff';
$sticky = $config['sticky'] ?? false;

$siteTitle = sfConfig::get('app_siteTitle', 'Archive');
$displayTitle = !empty($customTitle) ? $customTitle : $siteTitle;
?>

<header class="landing-header <?php echo $sticky ? 'sticky-top' : '' ?>" 
        style="background-color: <?php echo esc_entities($bgColor) ?>;">
  <div class="container">
    <div class="d-flex justify-content-between align-items-center py-3">
      
      <!-- Logo & Title -->
      <div class="d-flex align-items-center">
        <?php if ($showLogo): ?>
          <a href="/" class="me-3">
            <?php if (file_exists(sfConfig::get('sf_upload_dir').'/logos/logo.png')): ?>
              <img src="/uploads/logos/logo.png" alt="Logo" style="max-height: 50px;">
            <?php else: ?>
              <div class="bg-primary text-white rounded p-2">
                <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="currentColor" viewBox="0 0 16 16">
                  <path d="M0 2a1 1 0 0 1 1-1h14a1 1 0 0 1 1 1v2a1 1 0 0 1-1 1v7.5a2.5 2.5 0 0 1-2.5 2.5h-9A2.5 2.5 0 0 1 1 12.5V5a1 1 0 0 1-1-1V2zm2 3v7.5A1.5 1.5 0 0 0 3.5 14h9a1.5 1.5 0 0 0 1.5-1.5V5H2z"/>
                </svg>
              </div>
            <?php endif ?>
          </a>
        <?php endif ?>
        
        <?php if ($showTitle): ?>
          <a href="/" class="text-decoration-none">
            <h1 class="h4 mb-0 text-dark"><?php echo esc_entities($displayTitle) ?></h1>
          </a>
        <?php endif ?>
      </div>
      
      <!-- Navigation -->
      <?php if ($showNav): ?>
        <nav class="d-none d-md-flex gap-3">
          <a href="<?php echo url_for(['module' => 'informationobject', 'action' => 'browse']) ?>" class="text-decoration-none text-dark">Browse</a>
          <a href="<?php echo url_for(['module' => 'search', 'action' => 'advanced']) ?>" class="text-decoration-none text-dark">Search</a>
          <a href="<?php echo url_for(['module' => 'repository', 'action' => 'browse']) ?>" class="text-decoration-none text-dark">Repositories</a>
        </nav>
        
        <!-- Mobile Menu Toggle -->
        <button class="btn btn-outline-secondary d-md-none" type="button" data-bs-toggle="collapse" data-bs-target="#mobileNav">
          ☰
        </button>
      <?php endif ?>
    </div>
    
    <?php if ($showNav): ?>
      <!-- Mobile Navigation -->
      <div class="collapse d-md-none pb-3" id="mobileNav">
        <nav class="d-flex flex-column gap-2">
          <a href="<?php echo url_for(['module' => 'informationobject', 'action' => 'browse']) ?>" class="text-decoration-none">Browse</a>
          <a href="<?php echo url_for(['module' => 'search', 'action' => 'advanced']) ?>" class="text-decoration-none">Search</a>
          <a href="<?php echo url_for(['module' => 'repository', 'action' => 'browse']) ?>" class="text-decoration-none">Repositories</a>
        </nav>
      </div>
    <?php endif ?>
  </div>
</header>
