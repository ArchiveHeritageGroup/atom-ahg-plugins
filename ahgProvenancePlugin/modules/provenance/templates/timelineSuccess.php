<?php use_helper('Text') ?>

<?php
// Do NOT write `$resource->title ?? $resource->slug` here. ?? calls __isset(),
// and Propel's generated Base classes THROW "Unknown record property" for a
// column the class does not have rather than returning false, which made these
// pages fatal with HTTP 500. See viewSuccess.php.
$resourceLabel = '';

try {
    if (method_exists($resource, 'getTitle')) {
        $resourceLabel = (string) $resource->getTitle(['cultureFallback' => true]);
    }
} catch (Throwable $e) {
    $resourceLabel = '';
}

if ('' === trim($resourceLabel)) {
    $resourceLabel = (string) $resource->slug;
}
?>

<div class="container-fluid py-3">
  <!-- Breadcrumb -->
  <nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb mb-0">
      <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'informationobject', 'slug' => $resource->slug]) ?>"><?php echo $resourceLabel ?></a></li>
      <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'provenance', 'action' => 'view', 'slug' => $resource->slug]) ?>">Provenance</a></li>
      <li class="breadcrumb-item active">Timeline</li>
    </ol>
  </nav>

  <!-- Header -->
  <div class="d-flex justify-content-between align-items-center mb-4">
    <div>
      <h4 class="mb-1"><i class="fas fa-chart-gantt me-2"></i>Provenance Timeline</h4>
      <p class="text-muted mb-0"><?php echo $resourceLabel ?></p>
    </div>
    <div>
      <a href="<?php echo url_for(['module' => 'provenance', 'action' => 'view', 'slug' => $resource->slug]) ?>" class="btn btn-outline-secondary me-2">
        <i class="fas fa-arrow-left me-1"></i>Back to Provenance
      </a>
      <?php if ($sf_user->isAuthenticated()): ?>
      <a href="<?php echo url_for(['module' => 'provenance', 'action' => 'edit', 'slug' => $resource->slug]) ?>" class="btn btn-primary">
        <i class="fas fa-pen me-1"></i> Edit Provenance
      </a>
      <?php endif ?>
    </div>
  </div>

  <?php
  // The visual timeline. Rendered as markup and CSS rather than drawn with d3:
  // the previous version loaded d3 from /plugins/ahgThemeB5Plugin/web/js/, a theme
  // path, so on any install without the AHG theme the script 404'd, `d3` was
  // undefined and the chart silently never appeared - the page looked empty with
  // nothing to say why.
  //
  // Nodes are spaced evenly in date order rather than proportionally to elapsed
  // time. Proportional spacing needs a per-node offset, which means an inline
  // style attribute, and style-src here carries no 'unsafe-inline' - the browser
  // drops those silently. Even spacing also reads better for provenance, where a
  // few events are separated by very uneven gaps.
  $events = json_decode($sf_data->getRaw('timelineData'), true) ?: [];

  // Font Awesome only. The stock arDominionB5Plugin bundle ships Font Awesome 6
  // and no bootstrap-icons font, so a bi-* class renders as an empty box.
  $categoryMeta = [
      'creation'    => ['icon' => 'fa-seedling',          'label' => __('Creation')],
      'sale'        => ['icon' => 'fa-money-bill-wave',   'label' => __('Sale / Purchase')],
      'gift'        => ['icon' => 'fa-gift',              'label' => __('Gift / Donation')],
      'inheritance' => ['icon' => 'fa-scroll',            'label' => __('Inheritance / Bequest')],
      'auction'     => ['icon' => 'fa-gavel',             'label' => __('Auction')],
      'transfer'    => ['icon' => 'fa-right-left',        'label' => __('Transfer')],
      'loan'        => ['icon' => 'fa-hand-holding',      'label' => __('Loan')],
      'theft'       => ['icon' => 'fa-triangle-exclamation', 'label' => __('Theft / Confiscation')],
      'recovery'    => ['icon' => 'fa-shield-halved',     'label' => __('Recovery / Restitution')],
      'event'       => ['icon' => 'fa-circle-dot',        'label' => __('Other event')],
  ];

  // Only the categories actually present, so the key describes this record rather
  // than listing ten things most records never use.
  $usedCategories = [];
  foreach ($events as $e) {
      $c = $e['category'] ?? 'event';
      $usedCategories[isset($categoryMeta[$c]) ? $c : 'event'] = true;
  }
  ?>

  <div class="card shadow-sm mb-4">
    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
      <h5 class="mb-0">
        <i class="fas fa-chart-gantt me-2" aria-hidden="true"></i>
        <?php echo __('Visual Timeline') ?>
      </h5>
      <?php if (count($events)): ?>
        <span class="badge bg-light text-dark"><?php echo __('%1% events', ['%1%' => count($events)]) ?></span>
      <?php endif ?>
    </div>
    <div class="card-body">
      <?php if (empty($events)): ?>
        <p class="text-muted mb-0">
          <i class="fas fa-circle-info me-1" aria-hidden="true"></i>
          <?php echo __('No dated events to plot yet.') ?>
        </p>
      <?php else: ?>
        <div class="pv-scroll" tabindex="0" role="group" aria-label="<?php echo esc_entities(__('Provenance timeline')) ?>">
          <ol class="pv-rail">
            <?php foreach ($events as $i => $event):
                $category = $event['category'] ?? 'event';
                if (!isset($categoryMeta[$category])) { $category = 'event'; }
                $meta = $categoryMeta[$category];

                $certainty = strtolower((string) ($event['certainty'] ?? 'unknown'));
                $uncertain = in_array($certainty, ['uncertain', 'possible', 'unknown', 'disputed'], true);

                // Year alone on the rail; the full date belongs in the card. A rail
                // crowded with 1923-04-17 stops reading as a sequence.
                $date = trim((string) ($event['startDate'] ?? ''));
                $year = ('' !== $date && preg_match('/^(\d{4})/', $date, $m)) ? $m[1] : null;
            ?>
              <li class="pv-node pv-<?php echo $category ?><?php echo $uncertain ? ' pv-uncertain' : '' ?>">
                <div class="pv-year"><?php echo $year ? esc_entities($year) : '<span class="pv-nodate">'.esc_entities(__('undated')).'</span>' ?></div>

                <div class="pv-marker" aria-hidden="true">
                  <i class="fas <?php echo $meta['icon'] ?>"></i>
                </div>

                <div class="pv-card">
                  <p class="pv-type"><?php echo esc_entities($event['type'] ?? $meta['label']) ?></p>

                  <?php if (!empty($event['from']) || !empty($event['to'])): ?>
                    <p class="pv-actors">
                      <?php if (!empty($event['from'])): ?>
                        <span class="pv-actor"><?php echo esc_entities($event['from']) ?></span>
                      <?php endif ?>
                      <?php if (!empty($event['from']) && !empty($event['to'])): ?>
                        <i class="fas fa-arrow-right pv-arrow" aria-hidden="true"></i>
                      <?php endif ?>
                      <?php if (!empty($event['to'])): ?>
                        <span class="pv-actor"><?php echo esc_entities($event['to']) ?></span>
                      <?php endif ?>
                    </p>
                  <?php endif ?>

                  <?php if ('' !== $date): ?>
                    <p class="pv-meta"><i class="fas fa-calendar-days" aria-hidden="true"></i> <?php echo esc_entities($date) ?></p>
                  <?php endif ?>

                  <?php if (!empty($event['location'])): ?>
                    <p class="pv-meta"><i class="fas fa-location-dot" aria-hidden="true"></i> <?php echo esc_entities($event['location']) ?></p>
                  <?php endif ?>

                  <?php if ($uncertain): ?>
                    <p class="pv-meta pv-flag">
                      <i class="fas fa-circle-question" aria-hidden="true"></i>
                      <?php echo esc_entities(ucfirst($certainty)) ?>
                    </p>
                  <?php endif ?>

                  <?php if (!empty($event['description'])): ?>
                    <p class="pv-desc"><?php echo esc_entities($event['description']) ?></p>
                  <?php endif ?>
                </div>
              </li>
            <?php endforeach ?>
          </ol>
        </div>

        <div class="pv-key mt-3">
          <?php foreach ($usedCategories as $category => $_): ?>
            <span class="pv-key-item pv-<?php echo $category ?>">
              <i class="fas <?php echo $categoryMeta[$category]['icon'] ?>" aria-hidden="true"></i>
              <?php echo esc_entities($categoryMeta[$category]['label']) ?>
            </span>
          <?php endforeach ?>
        </div>
      <?php endif ?>
    </div>
  </div>

  <!-- Events Table -->
  <?php 
  $rawTimeline = $provenance['timeline'];
  if ($rawTimeline instanceof sfOutputEscaperArrayDecorator) {
      $rawTimeline = $rawTimeline->getRawValue();
  }
  ?>
  <?php if (!empty($rawTimeline)): ?>
  <div class="card shadow-sm mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
      <h5 class="mb-0">
        <i class="fas fa-list-ul me-2"></i>
        <?php echo __('Provenance Events') ?>
      </h5>
      <span class="badge bg-secondary"><?php echo count($rawTimeline) ?></span>
    </div>
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-striped table-hover mb-0">
          <thead class="table-light">
            <tr>
              <th><?php echo __('Type') ?></th>
              <th><?php echo __('Date') ?></th>
              <th><?php echo __('From') ?></th>
              <th><?php echo __('To') ?></th>
              <th><?php echo __('Location') ?></th>
              <th><?php echo __('Certainty') ?></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($rawTimeline as $event): ?>
            <tr>
              <td><span class="badge bg-primary"><?php echo htmlspecialchars($event['type_label'] ?? '') ?></span></td>
              <td><?php echo htmlspecialchars($event['date_display'] ?? '') ?></td>
              <td><?php echo htmlspecialchars($event['from'] ?? '-') ?></td>
              <td><?php echo htmlspecialchars($event['to'] ?? '-') ?></td>
              <td><?php echo htmlspecialchars($event['location'] ?? '-') ?></td>
              <td>
                <?php 
                $cert = $event['certainty'] ?? 'unknown';
                $certClass = $cert === 'certain' ? 'success' : ($cert === 'uncertain' ? 'warning' : 'secondary');
                ?>
                <span class="badge bg-<?php echo $certClass ?>"><?php echo ucfirst($cert) ?></span>
              </td>
            </tr>
            <?php endforeach ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
  <?php else: ?>
  <div class="alert alert-info">
    <i class="fas fa-circle-info me-2"></i>
    No provenance events have been recorded.
    <?php if ($sf_user->isAuthenticated()): ?>
    <a href="<?php echo url_for(['module' => 'provenance', 'action' => 'edit', 'slug' => $resource->slug]) ?>" class="alert-link">Add events</a>
    <?php endif ?>
  </div>
  <?php endif ?>
</div>

<style <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
/* Horizontal provenance timeline.
   Everything lives here rather than in style attributes: style-src carries no
   'unsafe-inline', so an inline style attribute is dropped by the browser without
   any console error - which is how the old legend ended up rendering grey. */

.pv-scroll {
  overflow-x: auto;
  overflow-y: hidden;
  padding: 0.5rem 0 1rem;
}
.pv-scroll:focus-visible { outline: 2px solid #0d6efd; outline-offset: 2px; }

.pv-rail {
  display: flex;
  align-items: flex-start;
  list-style: none;
  margin: 0;
  padding: 0;
  min-width: min-content;
}

.pv-node {
  position: relative;
  flex: 0 0 15rem;
  max-width: 15rem;
  padding: 0 0.75rem;
  text-align: center;
}

/* The rail itself, drawn as two half-connectors per node so the line stops at the
   first and last marker instead of running off the ends. */
.pv-node::before,
.pv-node::after {
  content: "";
  position: absolute;
  top: 3.15rem;
  height: 2px;
  background: #d5d9de;
  width: 50%;
}
.pv-node::before { left: 0; }
.pv-node::after { right: 0; }
.pv-node:first-child::before,
.pv-node:last-child::after { display: none; }

.pv-year {
  font-weight: 700;
  font-size: 0.95rem;
  line-height: 1.6rem;
  color: #333;
  white-space: nowrap;
}
.pv-nodate { font-weight: 400; font-style: italic; color: #8a9099; }

.pv-marker {
  position: relative;
  z-index: 1;
  width: 3rem;
  height: 3rem;
  margin: 0.35rem auto 0.75rem;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #fff;
  font-size: 1.15rem;
  background: #9e9e9e;
  box-shadow: 0 0 0 4px #fff;
}

.pv-card {
  text-align: left;
  background: #fff;
  border: 1px solid #dee2e6;
  border-top: 3px solid #9e9e9e;
  border-radius: 0.25rem;
  padding: 0.6rem 0.7rem;
}
.pv-type {
  font-weight: 600;
  font-size: 0.9rem;
  margin: 0 0 0.25rem;
  color: #212529;
}
.pv-actors { margin: 0 0 0.35rem; font-size: 0.85rem; line-height: 1.35; }
.pv-actor { color: #212529; }
.pv-arrow { font-size: 0.7rem; color: #6c757d; margin: 0 0.2rem; }
.pv-meta {
  margin: 0 0 0.2rem;
  font-size: 0.78rem;
  color: #6c757d;
}
.pv-meta i { width: 0.9rem; }
.pv-flag { color: #b8860b; }
.pv-desc {
  margin: 0.4rem 0 0;
  font-size: 0.8rem;
  color: #495057;
  border-top: 1px dashed #e6e8eb;
  padding-top: 0.35rem;
}

/* An uncertain event should look uncertain at a glance, not only in its label. */
.pv-uncertain .pv-marker { box-shadow: 0 0 0 4px #fff, 0 0 0 6px #f0e2c0; }
.pv-uncertain .pv-card { border-style: dashed; }

.pv-key {
  display: flex;
  flex-wrap: wrap;
  gap: 0.4rem 0.9rem;
  font-size: 0.8rem;
  color: #495057;
}
.pv-key-item i { margin-right: 0.3rem; }

/* Category colours. Applied to the marker, the card's top edge and the key entry,
   so one class carries the whole visual identity of an event type. */
.pv-creation    .pv-marker { background: #2e7d32; }
.pv-creation    .pv-card   { border-top-color: #2e7d32; }
.pv-creation.pv-key-item i { color: #2e7d32; }

.pv-sale        .pv-marker { background: #1565c0; }
.pv-sale        .pv-card   { border-top-color: #1565c0; }
.pv-sale.pv-key-item i     { color: #1565c0; }

.pv-gift        .pv-marker { background: #7b1fa2; }
.pv-gift        .pv-card   { border-top-color: #7b1fa2; }
.pv-gift.pv-key-item i     { color: #7b1fa2; }

.pv-inheritance .pv-marker { background: #ef6c00; }
.pv-inheritance .pv-card   { border-top-color: #ef6c00; }
.pv-inheritance.pv-key-item i { color: #ef6c00; }

.pv-auction     .pv-marker { background: #c62828; }
.pv-auction     .pv-card   { border-top-color: #c62828; }
.pv-auction.pv-key-item i  { color: #c62828; }

.pv-transfer    .pv-marker { background: #455a64; }
.pv-transfer    .pv-card   { border-top-color: #455a64; }
.pv-transfer.pv-key-item i { color: #455a64; }

.pv-loan        .pv-marker { background: #6d4c41; }
.pv-loan        .pv-card   { border-top-color: #6d4c41; }
.pv-loan.pv-key-item i     { color: #6d4c41; }

.pv-theft       .pv-marker { background: #b71c1c; }
.pv-theft       .pv-card   { border-top-color: #b71c1c; }
.pv-theft.pv-key-item i    { color: #b71c1c; }

.pv-recovery    .pv-marker { background: #00695c; }
.pv-recovery    .pv-card   { border-top-color: #00695c; }
.pv-recovery.pv-key-item i { color: #00695c; }

.pv-event       .pv-marker { background: #757575; }
.pv-event       .pv-card   { border-top-color: #757575; }
.pv-event.pv-key-item i    { color: #757575; }

@media print {
  .pv-scroll { overflow: visible; }
  .pv-rail { flex-wrap: wrap; }
}
</style>
