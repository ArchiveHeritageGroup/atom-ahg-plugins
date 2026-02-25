<?php
  // Unescape sfOutputEscaper wrapper and skip the Home breadcrumb
  $rawItems = !empty($items) ? (is_array($items) ? $items : sfOutputEscaper::unescape($items)) : [];
  $breadcrumbItems = [];
  foreach ($rawItems as $crumb) {
    $c = is_array($crumb) ? $crumb : sfOutputEscaper::unescape($crumb);
    if (($c['label'] ?? '') !== __('Home')) {
      $breadcrumbItems[] = $c;
    }
  }
?>
<?php if (!empty($breadcrumbItems)): ?>
<nav aria-label="breadcrumb" class="mb-3">
  <ol class="breadcrumb">
    <?php foreach ($breadcrumbItems as $i => $crumb): ?>
      <?php if ($i === count($breadcrumbItems) - 1): ?>
        <li class="breadcrumb-item active" aria-current="page"><?php echo $crumb['label']; ?></li>
      <?php else: ?>
        <li class="breadcrumb-item"><a href="<?php echo $crumb['url'] ?? '#'; ?>"><?php echo $crumb['label']; ?></a></li>
      <?php endif; ?>
    <?php endforeach; ?>
  </ol>
</nav>
<?php endif; ?>
