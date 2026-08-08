<?php // Rules moved out of style attributes: a CSP nonce covers <style>
      // elements and never an attribute, so under an enforcing policy every one
      // of these was dropped silently.
      $cspNonce = sfConfig::get('csp_nonce', ''); ?>
<style <?php echo $cspNonce ? preg_replace('/^nonce=/', 'nonce="', $cspNonce).'"' : ''; ?>>
  .author-width-22-03d9 { width: 22%; }
  .author-width-28-599e { width: 28%; }
</style>
<?php
/**
 * Partial: one evidence dimension row inside a candidate card.
 *
 * Locals expected:
 *   $dimension  - string dimension key (e.g. 'temporal', 'geographic')
 *   $signal     - 'match' | 'conflict' | 'silent' | 'absent'
 *   $detail     - human-readable string explaining the signal (from evidence_data)
 *
 * Copyright (C) 2026 Johan Pieterse / Plain Sailing Information Systems
 * GPL-3.0-or-later.
 */

$signalConfig = [
  'match'    => ['cls' => 'success', 'icon' => 'fa-check',         'label' => 'Match'],
  'conflict' => ['cls' => 'danger',  'icon' => 'fa-times',         'label' => 'Conflict'],
  'silent'   => ['cls' => 'secondary','icon' => 'fa-minus',        'label' => 'Silent'],
  'absent'   => ['cls' => 'light',   'icon' => 'fa-circle-notch',  'label' => 'Absent'],
];
$cfg = $signalConfig[$signal] ?? $signalConfig['absent'];

/**
 * Humanize a bare snake_case evidence token into sentence-case text.
 * Only touches values that are pure lowercase snake_case (no spaces,
 * braces or quotes) — JSON blobs and already-readable text pass through
 * untouched. Display-only: stored evidence data is never modified.
 */
$detailDisplay = (string) ($detail ?? '');
if ($detailDisplay !== '' && preg_match('/^[a-z][a-z0-9_]*$/', $detailDisplay)) {
    $detailDisplay = ucfirst(str_replace('_', ' ', $detailDisplay));
}
?>
<tr>
  <td class="text-capitalize author-width-28-599e" >
    <small><?php echo htmlspecialchars(str_replace('_', ' ', $dimension)); ?></small>
  </td>
  <td class="author-width-22-03d9">
    <span class="badge bg-<?php echo $cfg['cls']; ?><?php echo $cfg['cls'] === 'light' ? ' text-dark border' : ''; ?>">
      <i class="fas <?php echo $cfg['icon']; ?> me-1"></i><?php echo $cfg['label']; ?>
    </span>
  </td>
  <td class="text-muted small">
    <?php echo htmlspecialchars($detailDisplay); ?>
  </td>
</tr>
