<?php
/**
 * Quick Links Menu - the "i" dropdown in the header.
 *
 * Mimics AtoM 2.10 quick links without the menu table, but only offers the
 * static pages this instance actually has. Base AtoM ships home, about and
 * privacy; there is no contact page, so a hardcoded link to /contact was a
 * guaranteed 404 on any install where nobody had created one by hand - reported
 * from the Wits archaeology instance 2026-08-24 by Stefan du Toit, and
 * reproduced on archaeology, where /index.php/contact is 404 while PSIS, which
 * does have the page, is 200.
 *
 * Whole dropdown disappears when none of the pages exist, rather than offering
 * an empty menu.
 */

$quickLinks = [
    'about' => ['label' => __('About'), 'icon' => 'fa-info-circle'],
    'contact' => ['label' => __('Contact'), 'icon' => 'fa-envelope'],
    'privacy' => ['label' => __('Privacy'), 'icon' => 'fa-user-shield'],
];

// Which of them exist here. A static page is reachable by slug, so the slug row
// is what decides it - an orphaned static_page row with no slug 404s just the
// same. Fails open on a database error: showing a link that might 404 beats
// dropping the menu because of an unrelated fault.
try {
    $present = \Illuminate\Database\Capsule\Manager::table('static_page')
        ->join('slug', 'slug.object_id', '=', 'static_page.id')
        ->whereIn('slug.slug', array_keys($quickLinks))
        ->pluck('slug.slug')
        ->all();

    $quickLinks = array_intersect_key($quickLinks, array_flip($present));
} catch (Exception $e) {
    // Leave the full list in place.
}

if (empty($quickLinks)) {
    return;
}
?>
<li class="nav-item dropdown d-flex flex-column">
  <a class="nav-link dropdown-toggle d-flex align-items-center p-0" href="#" id="quick-links-menu" role="button" data-bs-toggle="dropdown" aria-expanded="false">
    <i class="fas fa-2x fa-fw fa-info-circle px-0 px-lg-2 py-2" data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-custom-class="d-none d-lg-block" title="<?php echo __('Quick links'); ?>" aria-hidden="true"></i>
    <span class="d-lg-none mx-1" aria-hidden="true"><?php echo __('Quick links'); ?></span>
    <span class="visually-hidden"><?php echo __('Quick links'); ?></span>
  </a>
  <ul class="dropdown-menu dropdown-menu-end mb-2" aria-labelledby="quick-links-menu">
    <li><h6 class="dropdown-header"><?php echo __('Quick links'); ?></h6></li>
    <?php foreach ($quickLinks as $slug => $link) { ?>
      <li><a class="dropdown-item" href="<?php echo url_for(['module' => 'staticpage', 'action' => 'index', 'slug' => $slug]); ?>"><i class="fas <?php echo $link['icon']; ?> fa-fw me-2"></i><?php echo $link['label']; ?></a></li>
    <?php } ?>
  </ul>
</li>
