<?php
/**
 * Partial: one pre-filled form field with a provenance badge.
 *
 * Locals expected (passed via include_partial):
 *   $name   - form field name (also the merged_fields key)
 *   $label  - human-readable field label
 *   $value  - default value (string)
 *   $prov   - array|null with keys: source, uri, license, license_url, at
 *   $type   - 'text' | 'textarea' | 'number'  (default 'text')
 *   $rows   - rows attr for textarea (default 3)
 *   $help   - help text shown below the input (string|null)
 *
 * Copyright (C) 2026 Johan Pieterse / Plain Sailing Information Systems
 * GPL-3.0-or-later.
 */

$type = isset($type) && $type !== '' ? $type : 'text';
$rows = isset($rows) && (int) $rows > 0 ? (int) $rows : 3;
$value = isset($value) ? (string) $value : '';
$prov = isset($prov) && is_array($prov) ? $prov : null;
$help = isset($help) ? (string) $help : '';
?>
<div class="mb-3">
  <label class="form-label fw-bold" for="ar-cn-<?php echo htmlspecialchars($name); ?>">
    <?php echo htmlspecialchars($label); ?>
    <?php if ($prov !== null): ?>
      <span class="badge bg-info text-dark ms-1" title="<?php echo htmlspecialchars((string) ($prov['uri'] ?? '')); ?>">
        <i class="fas fa-link me-1"></i><?php echo htmlspecialchars((string) ($prov['source'] ?? 'unknown')); ?>
        <?php if (!empty($prov['license'])): ?>
          <span class="ms-1 opacity-75"><?php echo htmlspecialchars((string) $prov['license']); ?></span>
        <?php endif; ?>
        <?php if (!empty($prov['at'])): ?>
          <span class="ms-1 opacity-75"><?php echo htmlspecialchars((string) $prov['at']); ?></span>
        <?php endif; ?>
      </span>
    <?php endif; ?>
  </label>

  <?php if ($type === 'textarea'): ?>
    <textarea name="<?php echo htmlspecialchars($name); ?>"
              id="ar-cn-<?php echo htmlspecialchars($name); ?>"
              class="form-control" rows="<?php echo $rows; ?>"><?php echo htmlspecialchars($value); ?></textarea>
  <?php elseif ($type === 'number'): ?>
    <input type="number" name="<?php echo htmlspecialchars($name); ?>"
           id="ar-cn-<?php echo htmlspecialchars($name); ?>"
           value="<?php echo htmlspecialchars($value); ?>"
           class="form-control" step="any">
  <?php else: ?>
    <input type="text" name="<?php echo htmlspecialchars($name); ?>"
           id="ar-cn-<?php echo htmlspecialchars($name); ?>"
           value="<?php echo htmlspecialchars($value); ?>"
           class="form-control">
  <?php endif; ?>

  <?php if ($help !== ''): ?>
    <div class="form-text"><?php echo htmlspecialchars($help); ?></div>
  <?php endif; ?>

  <?php if ($prov !== null): ?>
    <?php foreach (['source', 'uri', 'license', 'license_url', 'at'] as $k): ?>
      <?php if (isset($prov[$k]) && $prov[$k] !== ''): ?>
        <input type="hidden"
               name="_provenance[<?php echo htmlspecialchars($name); ?>][<?php echo $k; ?>]"
               value="<?php echo htmlspecialchars((string) $prov[$k]); ?>">
      <?php endif; ?>
    <?php endforeach; ?>
  <?php endif; ?>
</div>
