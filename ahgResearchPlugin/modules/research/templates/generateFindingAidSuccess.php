<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo __('Finding Aid'); ?> - <?php echo htmlspecialchars($data['collection']->name); ?></title>
  <style <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
    body { font-family: 'Times New Roman', Times, serif; font-size: 12pt; line-height: 1.6; margin: 40px; color: #333; }
    h1 { font-size: 24pt; border-bottom: 2px solid #333; padding-bottom: 10px; margin-bottom: 20px; }
    h2 { font-size: 18pt; color: #444; border-bottom: 1px solid #ccc; padding-bottom: 5px; margin-top: 30px; }
    h3 { font-size: 14pt; color: #555; margin-top: 20px; }
    .header { text-align: center; margin-bottom: 40px; }
    .header h1 { border: none; }
    .meta { background: #f5f5f5; padding: 15px; margin-bottom: 30px; border-left: 4px solid #333; }
    .meta p { margin: 5px 0; }
    .item { margin-bottom: 25px; padding-bottom: 15px; border-bottom: 1px dotted #ccc; page-break-inside: avoid; }
    .item-title { font-weight: bold; font-size: 13pt; color: #222; }
    .item-id { color: #666; font-size: 10pt; }
    .item-level { display: inline-block; background: #e0e0e0; padding: 2px 8px; font-size: 9pt; border-radius: 3px; margin-left: 10px; }
    .item-field { margin: 8px 0; }
    .item-field label { font-weight: bold; color: #555; display: block; font-size: 10pt; text-transform: uppercase; }
    .item-field p { margin: 3px 0 0 0; }
    .researcher-note { background: #fff8dc; padding: 10px; border-left: 3px solid #daa520; margin-top: 10px; font-style: italic; }
    .toc { background: #fafafa; padding: 20px; margin-bottom: 30px; }
    .toc h2 { margin-top: 0; }
    .toc ul { list-style: none; padding-left: 0; }
    .toc li { padding: 3px 0; }
    .toc a { text-decoration: none; color: #333; }
    .footer { margin-top: 50px; padding-top: 20px; border-top: 1px solid #ccc; font-size: 10pt; color: #666; text-align: center; }
    .descendant { margin-left: 30px; border-left: 2px solid #ddd; padding-left: 15px; }
    .print-btn { position: fixed; top: 20px; right: 20px; background: #007bff; color: white; border: none; padding: 10px 20px; cursor: pointer; border-radius: 5px; font-size: 14px; }
    .print-btn:hover { background: #0056b3; }
    @media print {
      .print-btn { display: none; }
      body { margin: 20px; }
      .item { page-break-inside: avoid; }
    }
  </style>
</head>
<body>

<a href="javascript:history.back()" class="print-btn" style="right: 230px; background: #6c757d;"><i class="fas fa-arrow-left"></i> Back</a>
<button class="print-btn" onclick="window.print()"><i class="fas fa-print"></i> Print / Save PDF</button>

<div class="header">
  <h1><?php echo __('Finding Aid'); ?></h1>
  <p><strong><?php echo htmlspecialchars($data['collection']->name); ?></strong></p>
</div>

<div class="meta">
  <p><strong><?php echo __('Collection'); ?>:</strong> <?php echo htmlspecialchars($data['collection']->name); ?></p>
  <?php if (!empty($data['collection']->description)): ?>
    <p><strong><?php echo __('Description'); ?>:</strong> <?php echo htmlspecialchars($data['collection']->description); ?></p>
  <?php endif; ?>
  <p><strong><?php echo __('Researcher'); ?>:</strong> <?php echo htmlspecialchars($data['researcher']->first_name . ' ' . $data['researcher']->last_name); ?></p>
  <?php if (!empty($data['researcher']->institution)): ?>
    <p><strong><?php echo __('Institution'); ?>:</strong> <?php echo htmlspecialchars($data['researcher']->institution); ?></p>
  <?php endif; ?>
  <p><strong><?php echo __('Generated'); ?>:</strong> <?php echo date('F j, Y \a\t H:i', strtotime($data['generated_at'])); ?></p>
  <p><strong><?php echo __('Items in collection'); ?>:</strong> <?php echo $data['item_count']; ?></p>
</div>

<div class="toc">
  <h2><?php echo __('Table of Contents'); ?></h2>
  <ul>
    <?php foreach ($data['items'] as $idx => $item): ?>
      <?php if (empty($item->is_descendant)): ?>
        <li><a href="#item-<?php echo $item->id; ?>"><?php echo ($idx + 1) . '. ' . htmlspecialchars($item->title ?: 'Untitled'); ?></a></li>
      <?php endif; ?>
    <?php endforeach; ?>
  </ul>
</div>

<h2><?php echo __('Collection Contents'); ?></h2>

<?php foreach ($data['items'] as $idx => $item): ?>
  <div class="item <?php echo !empty($item->is_descendant) ? 'descendant' : ''; ?>" id="item-<?php echo $item->id; ?>">
    <div class="item-title">
      <?php if (empty($item->is_descendant)): ?><?php echo ($idx + 1) . '. '; ?><?php endif; ?>
      <?php echo htmlspecialchars($item->title ?: 'Untitled'); ?>
      <?php if (!empty($item->level_of_description)): ?>
        <span class="item-level"><?php echo htmlspecialchars($item->level_of_description); ?></span>
      <?php endif; ?>
    </div>
    
    <?php if (!empty($item->identifier)): ?>
      <div class="item-id"><?php echo __('Reference'); ?>: <?php echo htmlspecialchars($item->identifier); ?></div>
    <?php endif; ?>
    
    <?php if (!empty($item->repository_name)): ?>
      <div class="item-field">
        <label><?php echo __('Repository'); ?></label>
        <p><?php echo htmlspecialchars($item->repository_name); ?></p>
      </div>
    <?php endif; ?>
    
    <?php if (!empty($item->extent_and_medium)): ?>
      <div class="item-field">
        <label><?php echo __('Extent'); ?></label>
        <p><?php echo htmlspecialchars($item->extent_and_medium); ?></p>
      </div>
    <?php endif; ?>
    
    <?php if (!empty($item->scope_and_content)): ?>
      <div class="item-field">
        <label><?php echo __('Scope and Content'); ?></label>
        <p><?php echo nl2br(htmlspecialchars($item->scope_and_content)); ?></p>
      </div>
    <?php endif; ?>
    
    <?php if (!empty($item->arrangement)): ?>
      <div class="item-field">
        <label><?php echo __('Arrangement'); ?></label>
        <p><?php echo nl2br(htmlspecialchars($item->arrangement)); ?></p>
      </div>
    <?php endif; ?>
    
    <?php if (!empty($item->access_conditions)): ?>
      <div class="item-field">
        <label><?php echo __('Conditions of Access'); ?></label>
        <p><?php echo nl2br(htmlspecialchars($item->access_conditions)); ?></p>
      </div>
    <?php endif; ?>
    
    <?php if (!empty($item->reproduction_conditions)): ?>
      <div class="item-field">
        <label><?php echo __('Conditions of Reproduction'); ?></label>
        <p><?php echo nl2br(htmlspecialchars($item->reproduction_conditions)); ?></p>
      </div>
    <?php endif; ?>
    
    <?php if (!empty($item->researcher_notes)): ?>
      <div class="researcher-note">
        <strong><?php echo __('Researcher Notes'); ?>:</strong><br>
        <?php echo nl2br(htmlspecialchars($item->researcher_notes)); ?>
      </div>
    <?php endif; ?>
  </div>
<?php endforeach; ?>

<div class="footer">
  <p><?php echo __('This finding aid was generated from a personal research collection.'); ?></p>
  <p><?php echo __('Generated by'); ?> AtoM Research Module | <?php echo date('Y'); ?></p>
</div>

</body>
</html>
