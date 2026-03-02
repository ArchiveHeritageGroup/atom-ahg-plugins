<?php
  $n = sfConfig::get('csp_nonce', '');
  $na = $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : '';
  $user = sfContext::getInstance()->getUser();
  $isLoggedIn = $user && $user->isAuthenticated();
  $currentUrl = sfContext::getInstance()->getRequest()->getUri();
?>
<!DOCTYPE html>
<html lang="<?php echo sfConfig::get('sf_default_culture', 'en'); ?>">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?php if (!include_slot('title')): ?>AtoM Registry<?php endif; ?></title>
  <link rel="icon" href="/favicon.ico">

  <!-- Google Fonts -->
  <link href="https://fonts.googleapis.com/css2?family=Source+Sans+3:wght@300;400;600;700&display=swap" rel="stylesheet" <?php echo $na; ?>>
  <!-- Bootstrap 5 CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" <?php echo $na; ?>>
  <!-- Font Awesome -->
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet" <?php echo $na; ?>>
  <!-- Registry CSS -->
  <link href="/plugins/ahgRegistryPlugin/css/registry.css" rel="stylesheet" <?php echo $na; ?>>

  <style <?php echo $na; ?>>
    :root {
      --atm-primary: #225b7b;
      --atm-primary-dark: #174a65;
      --atm-primary-light: #2d7da8;
      --atm-accent: #d4a843;
      --atm-bg: #f5f3ef;
      --atm-card-bg: #ffffff;
      --atm-text: #333333;
      --atm-text-muted: #6c757d;
      --atm-navbar-bg: #225b7b;
      --atm-footer-bg: #1a2332;
      --atm-border: #e0dcd5;
    }
    body {
      font-family: 'Source Sans 3', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
      background: var(--atm-bg);
      color: var(--atm-text);
      min-height: 100vh;
      display: flex;
      flex-direction: column;
      font-size: 0.95rem;
    }
    h1, h2, h3, h4, h5, h6 { font-weight: 600; color: #2c3e50; }

    /* Navbar */
    .reg-navbar {
      background: var(--atm-navbar-bg) !important;
      box-shadow: 0 2px 8px rgba(0,0,0,0.15);
      padding: 0.4rem 0;
    }
    .reg-navbar .navbar-brand {
      font-weight: 700;
      font-size: 1.1rem;
      letter-spacing: -0.01em;
      color: #fff !important;
    }
    .reg-navbar .navbar-brand img {
      height: 42px;
      margin-right: 10px;
    }
    .reg-navbar .nav-link {
      color: rgba(255,255,255,0.85) !important;
      font-weight: 500;
      font-size: 0.88rem;
      padding: 0.4rem 0.65rem !important;
      border-radius: 4px;
      transition: background 0.15s, color 0.15s;
    }
    .reg-navbar .nav-link:hover,
    .reg-navbar .nav-link.active {
      background: rgba(255,255,255,0.12);
      color: #fff !important;
    }
    .reg-navbar .nav-link i { margin-right: 3px; }
    .reg-navbar .dropdown-menu {
      border: 1px solid var(--atm-border);
      box-shadow: 0 4px 12px rgba(0,0,0,0.1);
      border-radius: 6px;
    }

    /* Cards */
    .card {
      border: 1px solid var(--atm-border);
      border-radius: 6px;
      box-shadow: 0 1px 3px rgba(0,0,0,0.04);
    }
    .card-header {
      background: #faf9f7;
      border-bottom: 1px solid var(--atm-border);
      font-weight: 600;
      font-size: 0.9rem;
    }

    /* Buttons - primary uses the AtoM teal */
    .btn-primary {
      background: var(--atm-primary) !important;
      border-color: var(--atm-primary) !important;
    }
    .btn-primary:hover {
      background: var(--atm-primary-dark) !important;
      border-color: var(--atm-primary-dark) !important;
    }
    .btn-outline-primary {
      color: var(--atm-primary) !important;
      border-color: var(--atm-primary) !important;
    }
    .btn-outline-primary:hover {
      background: var(--atm-primary) !important;
      color: #fff !important;
    }
    .text-primary { color: var(--atm-primary) !important; }
    .bg-primary { background-color: var(--atm-primary) !important; }
    .border-primary { border-color: var(--atm-primary) !important; }

    /* Badges */
    .badge { font-weight: 500; font-size: 0.78rem; letter-spacing: 0.02em; }

    /* Footer */
    .reg-footer {
      background: var(--atm-footer-bg);
      color: #94a3b8;
      padding: 2rem 0;
      margin-top: auto;
    }
    .reg-footer h6 { color: #e2e8f0 !important; font-size: 0.85rem; }
    .reg-footer a { color: #93c5fd; text-decoration: none; }
    .reg-footer a:hover { color: #fff; }
    .reg-footer .small { font-size: 0.82rem; }

    /* Main content area */
    .reg-main { flex: 1; padding-top: 1.25rem; padding-bottom: 2rem; }

    /* Breadcrumbs */
    .breadcrumb { font-size: 0.82rem; }
    .breadcrumb-item a { color: var(--atm-primary); }

    /* Table improvements */
    .table { font-size: 0.9rem; }
    .table thead { font-size: 0.82rem; text-transform: uppercase; letter-spacing: 0.04em; }

    /* Form controls */
    .form-control:focus, .form-select:focus {
      border-color: var(--atm-primary-light);
      box-shadow: 0 0 0 0.2rem rgba(34,91,123,0.15);
    }

    /* Hero banner override */
    .hero-banner {
      background: linear-gradient(135deg, var(--atm-primary) 0%, var(--atm-primary-dark) 100%);
      border-radius: 8px;
    }

    /* Filter sidebar */
    .filter-sidebar .form-select { font-size: 0.85rem; }
    .filter-sidebar label { font-size: 0.82rem; font-weight: 600; }

    /* Links */
    a { color: var(--atm-primary); }
    a:hover { color: var(--atm-primary-dark); }

    /* Pagination */
    .page-link { color: var(--atm-primary); }
    .page-item.active .page-link { background: var(--atm-primary); border-color: var(--atm-primary); }
  </style>
  <?php include_slot('head'); ?>
</head>
<body>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark reg-navbar sticky-top">
  <div class="container">
    <a class="navbar-brand d-flex align-items-center" href="/registry/">
      <img src="/uploads/atom_registry_logo.png" alt="AtoM Registry">
      AtoM Registry
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#regNav">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="regNav">
      <ul class="navbar-nav me-auto">
        <li class="nav-item">
          <a class="nav-link" href="/registry/institutions"><i class="fas fa-university"></i> Institutions</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="/registry/vendors"><i class="fas fa-building"></i> Vendors</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="/registry/software"><i class="fas fa-cube"></i> Software</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="/registry/community"><i class="fas fa-users"></i> Community</a>
        </li>
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
            <i class="fas fa-ellipsis-h"></i> More
          </a>
          <ul class="dropdown-menu">
            <li><a class="dropdown-item" href="/registry/groups"><i class="fas fa-user-friends me-2"></i>User Groups</a></li>
            <li><a class="dropdown-item" href="/registry/blog"><i class="fas fa-rss me-2"></i>Blog</a></li>
            <li><a class="dropdown-item" href="/registry/newsletters"><i class="fas fa-envelope me-2"></i>Newsletters</a></li>
            <li><a class="dropdown-item" href="/registry/map"><i class="fas fa-map me-2"></i>Map</a></li>
            <li><a class="dropdown-item" href="/registry/search"><i class="fas fa-search me-2"></i>Search</a></li>
          </ul>
        </li>
      </ul>

      <!-- Right side: Search + Auth -->
      <form class="d-flex me-3" method="get" action="/registry/search">
        <div class="input-group input-group-sm">
          <input type="text" class="form-control" name="q" placeholder="Search..." style="max-width: 180px;">
          <button class="btn btn-outline-light" type="submit"><i class="fas fa-search"></i></button>
        </div>
      </form>

      <ul class="navbar-nav">
        <?php if ($isLoggedIn): ?>
          <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
              <i class="fas fa-user-circle"></i> <?php echo htmlspecialchars($user->getAttribute('username', 'Account'), ENT_QUOTES, 'UTF-8'); ?>
            </a>
            <ul class="dropdown-menu dropdown-menu-end">
              <li><a class="dropdown-item" href="/registry/my/institution"><i class="fas fa-university me-2"></i>My Institution</a></li>
              <li><a class="dropdown-item" href="/registry/my/vendor"><i class="fas fa-building me-2"></i>My Vendor</a></li>
              <li><a class="dropdown-item" href="/registry/my/groups"><i class="fas fa-user-friends me-2"></i>My Groups</a></li>
              <li><hr class="dropdown-divider"></li>
              <?php if ($user->hasCredential('administrator')): ?>
                <li><a class="dropdown-item" href="/registry/my/blog"><i class="fas fa-rss me-2"></i>Blog Posts</a></li>
                <li><a class="dropdown-item" href="/registry/admin"><i class="fas fa-cog me-2"></i>Admin</a></li>
                <li><hr class="dropdown-divider"></li>
              <?php endif; ?>
              <li><a class="dropdown-item" href="/registry/logout"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
            </ul>
          </li>
        <?php else: ?>
          <li class="nav-item">
            <a class="nav-link" href="/registry/login"><i class="fas fa-sign-in-alt"></i> Login</a>
          </li>
          <li class="nav-item">
            <a class="btn btn-sm btn-outline-light ms-2 mt-1" href="/registry/register">Register</a>
          </li>
        <?php endif; ?>
      </ul>
    </div>
  </div>
</nav>

<!-- Main content -->
<main class="reg-main">
  <div class="container">
    <?php if (!include_slot('content')): ?>
      <?php echo $sf_content; ?>
    <?php endif; ?>
  </div>
</main>

<!-- Footer -->
<?php
  // Load footer settings from database
  $_footerDesc = 'The global community hub for AtoM institutions, vendors, and archival software. Connect, collaborate, and discover.';
  $_footerCopyright = '&copy; ' . date('Y') . ' The Archive and Heritage Group (Pty) Ltd. &middot; Powered by <a href="https://accesstomemoryfoundation.org" target="_blank">Access to Memory (AtoM)</a>';
  $_footerColumns = [
    ['title' => 'Directory', 'links' => [
      ['label' => 'Institutions', 'url' => '/registry/institutions'],
      ['label' => 'Vendors', 'url' => '/registry/vendors'],
      ['label' => 'Software', 'url' => '/registry/software'],
      ['label' => 'Map', 'url' => '/registry/map'],
    ]],
    ['title' => 'Community', 'links' => [
      ['label' => 'User Groups', 'url' => '/registry/groups'],
      ['label' => 'Blog', 'url' => '/registry/blog'],
      ['label' => 'Newsletters', 'url' => '/registry/newsletters'],
      ['label' => 'Community Hub', 'url' => '/registry/community'],
    ]],
    ['title' => 'Get Started', 'links' => [
      ['label' => 'Create Account', 'url' => '/registry/register'],
      ['label' => 'Register Institution', 'url' => '/registry/my/institution/register'],
      ['label' => 'Register as Vendor', 'url' => '/registry/my/vendor/register'],
      ['label' => 'Register Software', 'url' => '/registry/my/vendor/software/add'],
    ]],
    ['title' => 'About', 'links' => [
      ['label' => 'AtoM Foundation', 'url' => 'https://accesstomemoryfoundation.org'],
      ['label' => 'The AHG', 'url' => 'https://www.theahg.co.za'],
      ['label' => 'GitHub', 'url' => 'https://github.com/ArchiveHeritageGroup'],
      ['label' => 'API', 'url' => '/registry/api/directory'],
    ]],
  ];

  try {
    $_fDescRow = \Illuminate\Database\Capsule\Manager::table('registry_settings')
      ->where('setting_key', 'footer_description')->first();
    if ($_fDescRow && '' !== trim($_fDescRow->setting_value)) {
      $_footerDesc = $_fDescRow->setting_value;
    }

    $_fCopyRow = \Illuminate\Database\Capsule\Manager::table('registry_settings')
      ->where('setting_key', 'footer_copyright')->first();
    if ($_fCopyRow && '' !== trim($_fCopyRow->setting_value)) {
      $_footerCopyright = str_replace('{year}', date('Y'), html_entity_decode($_fCopyRow->setting_value, ENT_QUOTES, 'UTF-8'));
    }

    $_fColsRow = \Illuminate\Database\Capsule\Manager::table('registry_settings')
      ->where('setting_key', 'footer_columns')->first();
    if ($_fColsRow && '' !== trim($_fColsRow->setting_value)) {
      $_decoded = json_decode($_fColsRow->setting_value, true);
      if (is_array($_decoded) && count($_decoded) > 0) {
        $_footerColumns = $_decoded;
      }
    }
  } catch (\Exception $e) {
    // Fall back to hardcoded defaults on any DB error
  }
?>
<footer class="reg-footer">
  <div class="container">
    <div class="row">
      <div class="col-md-4 mb-3 mb-md-0">
        <h6 class="mb-2"><i class="fas fa-landmark me-1"></i> AtoM Registry</h6>
        <p class="small mb-0"><?php echo htmlspecialchars($_footerDesc, ENT_QUOTES, 'UTF-8'); ?></p>
      </div>
      <?php foreach ($_footerColumns as $_ci => $_col): ?>
        <div class="col-md-2<?php echo $_ci < count($_footerColumns) - 1 ? ' mb-3 mb-md-0' : ''; ?>">
          <h6 class="mb-2"><?php echo htmlspecialchars($_col['title'] ?? '', ENT_QUOTES, 'UTF-8'); ?></h6>
          <?php if (!empty($_col['links']) && is_array($_col['links'])): ?>
            <ul class="list-unstyled small">
              <?php foreach ($_col['links'] as $_link): ?>
                <?php
                  $_linkUrl = $_link['url'] ?? '#';
                  $_isExternal = (0 === strpos($_linkUrl, 'http://') || 0 === strpos($_linkUrl, 'https://'));
                ?>
                <li><a href="<?php echo htmlspecialchars($_linkUrl, ENT_QUOTES, 'UTF-8'); ?>"<?php echo $_isExternal ? ' target="_blank"' : ''; ?>><?php echo htmlspecialchars($_link['label'] ?? '', ENT_QUOTES, 'UTF-8'); ?></a></li>
              <?php endforeach; ?>
            </ul>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    </div>
    <hr class="border-secondary mt-3 mb-2">
    <p class="text-center small mb-0"><?php echo $_footerCopyright; ?></p>
  </div>
</footer>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" <?php echo $na; ?>></script>
<!-- Registry JS -->
<script src="/plugins/ahgRegistryPlugin/js/registry-discussions.js" <?php echo $na; ?>></script>
<?php include_slot('scripts'); ?>

</body>
</html>
