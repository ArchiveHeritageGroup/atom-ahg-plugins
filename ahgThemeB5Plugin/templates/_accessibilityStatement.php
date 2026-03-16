<?php decorate_with('layout_1col'); ?>

<?php slot('title'); ?>
  <h1><?php echo __('Accessibility Statement'); ?></h1>
<?php end_slot(); ?>

<?php slot('content'); ?>
<div class="container py-4">
  <div class="card">
    <div class="card-body">

      <h2><?php echo __('Our Commitment'); ?></h2>
      <p><?php echo __('This site is committed to ensuring digital accessibility for people with disabilities. We continually improve the user experience for everyone and apply the relevant accessibility standards.'); ?></p>

      <h2><?php echo __('Conformance Status'); ?></h2>
      <p><?php echo __('We aim to conform to the Web Content Accessibility Guidelines (WCAG) 2.1 at Level AA. These guidelines explain how to make web content more accessible to people with a wide range of disabilities.'); ?></p>

      <h2><?php echo __('Accessibility Features'); ?></h2>
      <ul>
        <li><?php echo __('Skip navigation link to bypass repetitive content'); ?></li>
        <li><?php echo __('ARIA landmarks for screen reader navigation (banner, main, navigation, complementary, contentinfo)'); ?></li>
        <li><?php echo __('Keyboard navigable — all interactive elements reachable via Tab'); ?></li>
        <li><?php echo __('Visible focus indicators on interactive elements'); ?></li>
        <li><?php echo __('ARIA live regions for dynamic content announcements'); ?></li>
        <li><?php echo __('Collapsible facets with aria-expanded state'); ?></li>
        <li><?php echo __('Form validation linked to error messages via aria-describedby'); ?></li>
        <li><?php echo __('Table headers with scope attributes'); ?></li>
        <li><?php echo __('Respects prefers-reduced-motion for users sensitive to animation'); ?></li>
        <li><?php echo __('High contrast mode support (forced-colors media query)'); ?></li>
        <li><?php echo __('Language and text direction set on the html element'); ?></li>
        <li><?php echo __('Alternative text on images'); ?></li>
        <li><?php echo __('Voice command support (optional)'); ?></li>
        <li><?php echo __('Text-to-speech support (optional)'); ?></li>
      </ul>

      <h2><?php echo __('Known Limitations'); ?></h2>
      <ul>
        <li><?php echo __('Some legacy pages rendered by the base system may not fully meet all AA criteria.'); ?></li>
        <li><?php echo __('Third-party embedded content (e.g. IIIF viewers) may have their own accessibility limitations.'); ?></li>
        <li><?php echo __('PDF documents may not be fully accessible; alternative formats are available on request.'); ?></li>
      </ul>

      <h2><?php echo __('Feedback'); ?></h2>
      <p><?php echo __('We welcome your feedback on the accessibility of this site. If you encounter accessibility barriers, please contact us:'); ?></p>
      <ul>
        <li><?php echo __('Email'); ?>: <a href="mailto:<?php echo sfConfig::get('app_site_admin_email', 'admin@example.com'); ?>"><?php echo sfConfig::get('app_site_admin_email', 'admin@example.com'); ?></a></li>
      </ul>

      <h2><?php echo __('Technical Specifications'); ?></h2>
      <p><?php echo __('Accessibility of this site relies on the following technologies: HTML, CSS, JavaScript, WAI-ARIA, Bootstrap 5.'); ?></p>

      <p class="text-muted small mt-4"><?php echo __('This statement was last updated on %date%.', ['%date%' => date('j F Y')]); ?></p>

    </div>
  </div>
</div>
<?php end_slot(); ?>
