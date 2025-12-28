<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>GLAM Browse - Print Preview</title>
  <style>
    body { font-family: Arial, sans-serif; font-size: 12px; line-height: 1.4; margin: 20px; }
    h1 { font-size: 18px; border-bottom: 2px solid #1d6a52; padding-bottom: 10px; color: #1d6a52; }
    .meta { color: #666; margin-bottom: 20px; }
    table { width: 100%; border-collapse: collapse; margin-top: 20px; }
    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; vertical-align: top; }
    th { background-color: #1d6a52; color: white; font-weight: bold; }
    tr:nth-child(even) { background-color: #f9f9f9; }
    .type-archive { color: #198754; }
    .type-museum { color: #ffc107; }
    .type-gallery { color: #0dcaf0; }
    .type-library { color: #0d6efd; }
    .type-dam { color: #dc3545; }
    .scope { font-size: 11px; color: #666; max-width: 300px; }
    @media print {
      body { margin: 0; }
      h1 { page-break-after: avoid; }
      tr { page-break-inside: avoid; }
      .no-print { display: none; }
    }
    .print-btn { background: #1d6a52; color: white; border: none; padding: 10px 20px; cursor: pointer; margin-right: 10px; margin-bottom: 20px; }
    .print-btn:hover { background: #155a42; }
  </style>
</head>
<body>
  <div class="no-print">
    <button class="print-btn" onclick="window.print()">Print this page</button>
    <button class="print-btn" onclick="window.close()">Close</button>
  </div>

  <h1>
    <?php if (isset($parent) && $parent): ?>
      <?php echo esc_entities($parent->title) ?> - Contents
    <?php else: ?>
      GLAM Browse Results
    <?php endif ?>
  </h1>
  
  <div class="meta">
    <strong>Total:</strong> <?php echo $total ?> records |
    <strong>Generated:</strong> <?php echo date('Y-m-d H:i:s') ?>
    <?php if ($typeFilter): ?> | <strong>Type:</strong> <?php echo ucfirst($typeFilter) ?><?php endif ?>
  </div>

  <table>
    <thead>
      <tr>
        <th style="width:120px">Identifier</th>
        <th>Title</th>
        <th style="width:100px">Level</th>
        <th style="width:80px">Type</th>
        <th style="width:250px">Scope and Content</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($objects as $obj): ?>
        <tr>
          <td><?php echo esc_entities($obj->identifier ?: '-') ?></td>
          <td><strong><?php echo esc_entities($obj->title ?: '[Untitled]') ?></strong></td>
          <td><?php echo esc_entities($obj->level_name ?: '-') ?></td>
          <td class="type-<?php echo $obj->object_type ?>"><?php echo ucfirst($obj->object_type ?: '-') ?></td>
          <td class="scope"><?php echo esc_entities(mb_substr($obj->scope_and_content ?? '', 0, 200)) ?><?php if (strlen($obj->scope_and_content ?? '') > 200): ?>...<?php endif ?></td>
        </tr>
      <?php endforeach ?>
    </tbody>
  </table>

  <div class="meta" style="margin-top: 20px;">
    <em>Printed from GLAM Display System</em>
  </div>
</body>
</html>
