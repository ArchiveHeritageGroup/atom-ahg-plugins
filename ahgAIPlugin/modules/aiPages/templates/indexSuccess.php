<?php decorate_with('layout_1col.php'); ?>
<?php
// Per-page document type and privacy screening of a record's PDF, with the
// record-level roll-up (privacy is the maximum: one flagged page flags the
// record) and per-page review (catalogue#320, twin of heratio#1584).
$io = $sf_data->getRaw('io');
$segments = $sf_data->getRaw('segments');
$rollup = $sf_data->getRaw('rollup');
$types = $sf_data->getRaw('types');
$pdfUrl = $sf_data->getRaw('pdfUrl');
$settings = $sf_data->getRaw('settings');
$isAdmin = $sf_data->getRaw('isAdmin');
$e = fn ($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
$codes = array_keys($types);
$palette = ['#10373E', '#E1B34F', '#653332', '#5b7f86', '#a8842f', '#8c5b5a', '#2f5d64', '#c9a14a', '#7a4644', '#3d6e75', '#b8934a', '#4d2827'];
$colour = fn ($code) => null === $code ? '#C3CDCE' : $palette[(int) array_search($code, $codes, true) % count($palette)];
$label = fn ($code) => null === $code ? __('Not classified') : ($types[$code] ?? $code);
$range = fn ($s) => $s['from'] === $s['to'] ? (string) $s['from'] : $s['from'] . '-' . $s['to'];
$privacyLabels = ['flagged' => __('Personal information'), 'clear' => __('None found'), 'unread' => __('No text to read')];
$nonce = preg_replace('/^nonce=/', '', (string) sfConfig::get('csp_nonce', ''));
$pagesUrl = url_for('@ahg_ai_pages?slug=' . $io->slug);
?>
<?php slot('title'); ?>
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
  <div>
    <h1 class="h3 mb-1"><i class="fas fa-layer-group me-2" aria-hidden="true"></i><?php echo __('Pages: type and privacy'); ?></h1>
    <a href="<?php echo url_for(['module' => 'informationobject', 'slug' => $io->slug]); ?>"><?php echo $e($io->title ?: $io->slug); ?></a>
  </div>
  <form method="post" action="<?php echo $pagesUrl; ?>">
    <button type="submit" class="btn atom-btn-outline-success"><i class="fas fa-magic me-1" aria-hidden="true"></i><?php echo $segments ? __('Read the pages again') : __('Read the pages'); ?></button>
  </form>
</div>
<?php end_slot(); ?>

<style <?php echo $nonce ? 'nonce="' . $e($nonce) . '"' : ''; ?>>
  .pg-strip { height: 2.5rem; }
  .pg-strip a { border-right: 1px solid #fff; }
  .pg-strip a.pg-flag { box-shadow: inset 0 -6px 0 #dc3545; }
<?php foreach ($segments as $i => $s): ?>
  .pg-<?php echo $i; ?> { flex: <?php echo $s['to'] - $s['from'] + 1; ?> 1 0; background: <?php echo $colour($s['doctype']['label'] ?? null); ?>; }
<?php endforeach; ?>
<?php foreach ($codes as $c): ?>
  .pg-t-<?php echo preg_replace('/[^a-z0-9_]/', '', $c); ?> { background: <?php echo $colour($c); ?>; }
<?php endforeach; ?>
</style>

<?php if (!$segments): ?>
  <p class="text-muted"><?php echo __('Not read yet. Each page is screened for personal information and given a document type, so one label for the file does not hide what a mixed file contains.'); ?></p>
<?php else: ?>
  <?php if ('flagged' === $rollup['privacy']): ?>
    <div class="alert alert-danger"><i class="fas fa-user-shield me-1" aria-hidden="true"></i><?php echo $e(count($rollup['flagged']) === 1
        ? __('Personal information on 1 page (%1%): the record is flagged for privacy review.', ['%1%' => implode(', ', $rollup['flagged'])])
        : __('Personal information on %1% pages (%2%): the record is flagged for privacy review.', ['%1%' => count($rollup['flagged']), '%2%' => implode(', ', $rollup['flagged'])])); ?></div>
  <?php elseif ('incomplete' === $rollup['privacy']): ?>
    <div class="alert alert-warning"><i class="fas fa-question-circle me-1" aria-hidden="true"></i><?php echo $e(__('No personal information found, but these pages had no text to read: %1%. Check them by eye.', ['%1%' => implode(', ', $rollup['unread'])])); ?></div>
  <?php else: ?>
    <div class="alert alert-success"><i class="fas fa-check-circle me-1" aria-hidden="true"></i><?php echo $e(__('No personal information found on any of the %1% pages.', ['%1%' => $rollup['pages']])); ?></div>
  <?php endif; ?>

  <?php if ($rollup['spread']): ?>
    <p class="mb-2">
      <?php foreach ($rollup['spread'] as $code => $n): ?>
        <span class="badge me-1 pg-t-<?php echo preg_replace('/[^a-z0-9_]/', '', $code); ?>"><?php echo $e(__('%1% of %2% pages: %3%', ['%1%' => $n, '%2%' => $rollup['pages'], '%3%' => $label($code)])); ?></span>
      <?php endforeach; ?>
    </p>
  <?php endif; ?>

  <h2 class="h5 mt-3"><?php echo __('Page strip'); ?></h2>
  <div class="d-flex mb-1 border rounded overflow-hidden pg-strip" role="list" aria-label="<?php echo __('Pages of the file'); ?>">
    <?php foreach ($segments as $i => $s): ?>
      <?php $t = $s['doctype']['label'] ?? null; $p = $s['privacy']['label'] ?? null; ?>
      <a role="listitem" href="<?php echo $pdfUrl ? $e($pdfUrl . '#page=' . $s['from']) : '#seg-' . $s['from']; ?>" <?php echo $pdfUrl ? 'target="_blank" rel="noopener"' : ''; ?>
         class="d-flex align-items-center justify-content-center text-white text-decoration-none small pg-<?php echo $i; ?><?php echo 'flagged' === $p ? ' pg-flag' : ''; ?>"
         title="<?php echo $e(__('Page %1%', ['%1%' => $range($s)]) . ': ' . $label($t) . ('flagged' === $p ? ' - ' . __('Personal information') : '')); ?>">
        <?php echo $range($s); ?><?php if ('flagged' === $p): ?> <i class="fas fa-user-shield ms-1" aria-hidden="true"></i><span class="visually-hidden"><?php echo __('Personal information'); ?></span><?php endif; ?>
      </a>
    <?php endforeach; ?>
  </div>
  <p class="small text-muted mb-4"><?php echo __('Click a page to open it. A red bar marks a page with personal information.'); ?></p>

  <div class="table-responsive mb-3">
    <table class="table table-bordered table-sm align-middle">
      <thead><tr><th scope="col"><?php echo __('Pages'); ?></th><th scope="col"><?php echo __('Document type'); ?></th><th scope="col"><?php echo __('Privacy'); ?></th><th scope="col"><?php echo __('Review'); ?></th></tr></thead>
      <tbody>
      <?php foreach ($segments as $s): ?>
        <tr id="seg-<?php echo $s['from']; ?>">
          <td><?php echo $range($s); ?></td>
          <td>
            <?php echo $e($label($s['doctype']['label'] ?? null)); ?>
            <?php if ($s['doctype']['overridden'] ?? false): ?><span class="badge bg-secondary ms-1"><?php echo __('Reviewed'); ?></span><?php endif; ?>
            <?php if ($s['doctype'] ?? null): ?><div class="small text-muted"><?php echo $e($s['doctype']['model']); ?></div><?php endif; ?>
          </td>
          <td>
            <?php echo $e($privacyLabels[$s['privacy']['label'] ?? 'unread'] ?? ($s['privacy']['label'] ?? '')); ?>
            <?php if ($s['privacy']['types'] ?? []): ?><div class="small text-muted"><?php echo $e(implode(', ', $s['privacy']['types'])); ?></div><?php endif; ?>
            <?php if ($s['privacy']['overridden'] ?? false): ?><span class="badge bg-secondary"><?php echo __('Reviewed'); ?></span><?php endif; ?>
          </td>
          <td>
            <?php if ($s['doctype'] ?? null): ?>
              <form method="post" action="<?php echo url_for('@ahg_ai_pages_review?slug=' . $io->slug . '&inference=' . $s['doctype']['id']); ?>" class="d-flex gap-1 mb-1">
                <label class="visually-hidden" for="t-<?php echo $s['doctype']['id']; ?>"><?php echo __('Document type'); ?></label>
                <select id="t-<?php echo $s['doctype']['id']; ?>" name="value" class="form-select form-select-sm">
                  <?php foreach ($types as $code => $name): ?><option value="<?php echo $e($code); ?>"<?php echo $code === $s['doctype']['label'] ? ' selected' : ''; ?>><?php echo $e(__($name)); ?></option><?php endforeach; ?>
                </select>
                <button type="submit" class="btn btn-sm atom-btn-white"><?php echo __('Save'); ?></button>
              </form>
            <?php endif; ?>
            <?php if (($s['privacy'] ?? null) && 'unread' !== ($s['privacy']['original'] ?? '')): ?>
              <form method="post" action="<?php echo url_for('@ahg_ai_pages_review?slug=' . $io->slug . '&inference=' . $s['privacy']['id']); ?>" class="d-flex gap-1">
                <label class="visually-hidden" for="p-<?php echo $s['privacy']['id']; ?>"><?php echo __('Privacy'); ?></label>
                <select id="p-<?php echo $s['privacy']['id']; ?>" name="value" class="form-select form-select-sm">
                  <option value="flagged"<?php echo 'flagged' === ($s['privacy']['label'] ?? '') ? ' selected' : ''; ?>><?php echo __('Personal information'); ?></option>
                  <option value="clear"<?php echo 'clear' === ($s['privacy']['label'] ?? '') ? ' selected' : ''; ?>><?php echo __('None found'); ?></option>
                </select>
                <button type="submit" class="btn btn-sm atom-btn-white"><?php echo __('Save'); ?></button>
              </form>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <p class="small text-muted"><?php echo __('Each page result is recorded with its model and an input digest; a review is recorded as a correction, and the AI result is kept.'); ?></p>
<?php endif; ?>

<?php if ($isAdmin): ?>
<details class="card mt-4">
  <summary class="card-header"><?php echo __('Settings for every record (administrators)'); ?></summary>
  <form method="post" action="<?php echo url_for('@ahg_ai_pages_settings?slug=' . $io->slug); ?>" class="card-body">
    <div class="row g-3">
      <div class="col-md-3">
        <label class="form-label" for="pg-model"><?php echo __('Document type model'); ?></label>
        <input class="form-control" id="pg-model" name="model" value="<?php echo $e($settings['model']); ?>">
        <div class="form-text"><?php echo __('A model on the AHG AI gateway.'); ?></div>
      </div>
      <div class="col-md-2">
        <label class="form-label" for="pg-window"><?php echo __('Words per unit'); ?></label>
        <input class="form-control" type="number" min="0" max="20000" id="pg-window" name="window_words" value="<?php echo $e($settings['window']); ?>">
        <div class="form-text"><?php echo __('0 reads page by page.'); ?></div>
      </div>
      <div class="col-md-4">
        <label class="form-label" for="pg-cols"><?php echo __('Collections read on ingest'); ?></label>
        <input class="form-control" id="pg-cols" name="collections" value="<?php echo $e($settings['collections']); ?>" placeholder="913567">
        <div class="form-text"><?php echo __('Record ids, separated by commas. Empty: every collection.'); ?></div>
      </div>
      <div class="col-md-3 d-flex align-items-center">
        <div class="form-check"><input class="form-check-input" type="checkbox" id="pg-ingest" name="on_ingest" value="1"<?php echo '1' === $settings['on_ingest'] ? ' checked' : ''; ?>><label class="form-check-label" for="pg-ingest"><?php echo __('Read PDFs page by page after ingest'); ?></label></div>
      </div>
    </div>
    <button type="submit" class="btn atom-btn-outline-success mt-3"><?php echo __('Save'); ?></button>
  </form>
</details>
<?php endif; ?>
