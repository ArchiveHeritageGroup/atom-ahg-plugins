<?php
$story = $sf_data->getRaw('story');
$records = $sf_data->getRaw('records') ?: [];
$sources = $sf_data->getRaw('sources') ?: [];
$e = fn ($v) => esc_entities((string) $v);
$typeLabel = ['note' => __('Notes'), 'url' => __('Web page'), 'upload' => __('Document'), 'record' => __('Record')];
?>
<div class="container py-4" style="max-width: 820px">
  <?php if ('published' !== $story->status): ?><div class="alert alert-secondary"><?php echo __('Draft: only staff can see this page.') ?></div><?php endif ?>
  <h1 class="mb-4"><?php echo $e($story->title) ?></h1>
  <div class="fs-5 lh-lg">
    <?php foreach (preg_split('/\n\s*\n/', trim($story->body)) as $para): ?><p><?php echo nl2br($e(trim($para))) ?></p><?php endforeach ?>
  </div>
  <?php if ($records): ?>
    <h2 class="h5 mt-5"><?php echo __('Records in this story') ?></h2>
    <ul>
      <?php foreach ($records as $r): ?><li><?php if ($r['slug']): ?><a href="<?php echo url_for(['module' => 'informationobject', 'slug' => $r['slug']]) ?>"><?php echo $e($r['title']) ?></a><?php else: ?><?php echo $e($r['title']) ?><?php endif ?></li><?php endforeach ?>
    </ul>
  <?php endif ?>
  <?php if ($sources): ?>
    <h2 class="h6 mt-4"><?php echo __('Sources') ?></h2>
    <ul class="small">
      <?php foreach ($sources as $s): ?><li><?php echo $e($typeLabel[$s['type']] ?? '') ?>: <?php if (!empty($s['url'])): ?><a href="<?php echo $e($s['url']) ?>" rel="noopener noreferrer nofollow"><?php echo $e($s['label']) ?></a><?php else: ?><?php echo $e($s['label']) ?><?php endif ?></li><?php endforeach ?>
    </ul>
  <?php endif ?>
  <p class="small text-muted mt-4"><?php echo __('Written with the help of AI from records in this collection, and checked by staff before publication.') ?></p>
  <?php if ($staff): ?><a class="btn btn-sm btn-outline-secondary" href="<?php echo url_for(['module' => 'story', 'action' => 'index']) ?>?id=<?php echo (int) $story->id ?>"><?php echo __('Edit') ?></a><?php endif ?>
</div>
