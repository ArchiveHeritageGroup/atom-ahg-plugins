<?php
$result = $sf_data->getRaw('result');
$story = $sf_data->getRaw('story');
$form = $sf_data->getRaw('form');
$picked = $sf_data->getRaw('picked') ?: [];
$saved = $sf_data->getRaw('saved') ?: [];
$problems = $sf_data->getRaw('problems') ?: [];
$e = fn ($v) => esc_entities((string) $v);
$url = url_for(['module' => 'story', 'action' => 'index']);
$typeLabel = ['note' => __('Notes'), 'url' => __('Web page'), 'upload' => __('Document'), 'record' => __('Record')];
?>
<div class="container-fluid py-3">
  <div class="d-flex justify-content-between align-items-center mb-2">
    <h1 class="h3 mb-0"><i class="fas fa-book-open me-2"></i><?php echo __('Stories of the collection') ?></h1>
  </div>
  <p class="text-muted"><?php echo __('Give a theme, pick records and add background; the AI gateway writes a short public story from published records only. Edit it, then save it as a draft or publish it.') ?></p>

  <div class="row g-3">
    <div class="col-lg-5">
      <form method="post" action="<?php echo $url ?>" enctype="multipart/form-data" class="card">
        <?php echo \AtomFramework\Services\CsrfService::renderHiddenField() ?>
        <div class="card-header"><h2 class="h5 mb-0"><?php echo __('1. Material') ?></h2></div>
        <div class="card-body">
          <div class="mb-3"><label class="form-label" for="st-theme"><?php echo __('Theme') ?></label>
            <input class="form-control" id="st-theme" name="theme" maxlength="200" value="<?php echo $e($form['theme']) ?>" placeholder="<?php echo __('e.g. Life on the river') ?>"></div>
          <div class="mb-3"><label class="form-label" for="st-find"><?php echo __('Records to include') ?></label>
            <input class="form-control" id="st-find" autocomplete="off" placeholder="<?php echo __('Type part of a title') ?>" aria-describedby="st-find-help">
            <div id="st-find-help" class="form-text"><?php echo __('Published records only.') ?></div>
            <div id="st-hits" class="list-group mt-1" role="listbox" aria-label="<?php echo __('Matching records') ?>"></div>
            <div id="st-picked" class="mt-2 d-flex flex-wrap gap-1">
              <?php foreach ($picked as $o): ?><span class="badge bg-secondary"><input type="hidden" name="record_ids[]" value="<?php echo (int) $o['id'] ?>"><?php echo $e($o['title']) ?> <button type="button" class="btn-close btn-close-white ms-1 st-remove" aria-label="<?php echo __('Remove') ?>"></button></span><?php endforeach ?>
            </div></div>
          <div class="mb-3"><label class="form-label" for="st-notes"><?php echo __('Background notes') ?></label>
            <textarea class="form-control" id="st-notes" name="notes" rows="3"><?php echo $e($form['notes']) ?></textarea></div>
          <div class="mb-3"><label class="form-label" for="st-urls"><?php echo __('Web pages to draw on (one address per line, up to 5)') ?></label>
            <textarea class="form-control" id="st-urls" name="urls" rows="2" placeholder="https://"><?php echo $e($form['urls']) ?></textarea></div>
          <div class="mb-0"><label class="form-label" for="st-files"><?php echo __('Documents (PDF or text)') ?></label>
            <input class="form-control" type="file" id="st-files" name="uploads[]" multiple accept=".pdf,.txt,.md,text/plain,application/pdf"></div>
        </div>
        <div class="card-footer d-flex justify-content-between flex-wrap gap-2">
          <button type="submit" name="form_action" value="on_this_day" class="btn btn-outline-secondary" formnovalidate><?php echo __('On this day') ?></button>
          <button type="submit" name="form_action" value="generate" class="btn btn-primary"><?php echo __('Write the story') ?></button>
        </div>
      </form>
    </div>

    <div class="col-lg-7">
      <?php foreach ($problems as $p): ?><div class="alert alert-warning py-2"><?php echo $e($p) ?></div><?php endforeach ?>
      <?php if ($result && !$result['ok']): ?>
        <div class="alert alert-warning"><?php echo $e($result['error']) ?></div>
      <?php elseif ($result): ?>
        <form method="post" action="<?php echo $url ?>" class="card">
          <?php echo \AtomFramework\Services\CsrfService::renderHiddenField() ?>
          <input type="hidden" name="form_action" value="save">
          <input type="hidden" name="id" value="<?php echo (int) ($story->id ?? 0) ?>">
          <input type="hidden" name="theme" value="<?php echo $e($result['theme']) ?>">
          <input type="hidden" name="sources_json" value="<?php echo $e(json_encode($result['sources'])) ?>">
          <div class="card-header d-flex justify-content-between align-items-center"><h2 class="h5 mb-0"><?php echo __('2. Story') ?></h2>
            <?php if ($story): ?><span class="badge <?php echo 'published' === $story->status ? 'bg-success' : 'bg-secondary' ?>"><?php echo 'published' === $story->status ? __('Published') : __('Draft') ?></span><?php endif ?></div>
          <div class="card-body">
            <div class="mb-3"><label class="form-label" for="st-title"><?php echo __('Title') ?></label>
              <input class="form-control" id="st-title" name="title" maxlength="200" required value="<?php echo $e($story->title ?? $result['theme']) ?>"></div>
            <div class="mb-3"><label class="form-label" for="st-body"><?php echo __('Text') ?></label>
              <textarea class="form-control" id="st-body" name="body" rows="12" required><?php echo $e($result['story']) ?></textarea></div>
            <fieldset class="mb-3"><legend class="form-label fs-6"><?php echo __('Records linked from the story') ?></legend>
              <?php if (!$result['objects']): ?><div class="text-muted small"><?php echo __('None.') ?></div><?php endif ?>
              <?php foreach ($result['objects'] as $i => $o): ?>
                <div class="form-check"><input class="form-check-input" type="checkbox" name="object_ids[]" value="<?php echo (int) $o['id'] ?>" id="st-o<?php echo $i ?>" checked>
                  <label class="form-check-label" for="st-o<?php echo $i ?>"><?php echo $e($o['title']) ?></label></div>
              <?php endforeach ?>
            </fieldset>
            <?php if ($result['sources']): ?>
              <div class="small"><strong><?php echo __('Sources') ?>:</strong> <?php echo implode('; ', array_map(fn ($s) => $e(($typeLabel[$s['type']] ?? '').': '.$s['label']), $result['sources'])) ?></div>
            <?php endif ?>
          </div>
          <div class="card-footer d-flex justify-content-between flex-wrap gap-2">
            <span class="small text-muted"><?php echo $story && 'published' === $story->status ? __('Public at') . ' <a href="' . url_for(['module' => 'story', 'action' => 'show', 'slug' => $story->slug]) . '">/stories/' . $e($story->slug) . '</a>' : __('Check the text against the records before publishing.') ?></span>
            <span>
              <button type="submit" name="status" value="draft" class="btn btn-outline-secondary"><?php echo __('Save draft') ?></button>
              <button type="submit" name="status" value="published" class="btn btn-success"><?php echo __('Publish') ?></button>
            </span>
          </div>
        </form>
      <?php else: ?>
        <div class="card"><div class="card-body text-muted"><?php echo __('The story appears here.') ?></div></div>
      <?php endif ?>
    </div>
  </div>

  <div class="card mt-4">
    <div class="card-header"><h2 class="h5 mb-0"><?php echo __('Saved stories') ?></h2></div>
    <div class="card-body p-0 table-responsive">
      <table class="table table-sm mb-0 align-middle">
        <thead class="table-light"><tr><th><?php echo __('Title') ?></th><th><?php echo __('Status') ?></th><th><?php echo __('Updated') ?></th><th></th></tr></thead>
        <tbody>
        <?php if (!$saved): ?><tr><td colspan="4" class="text-muted p-3"><?php echo __('No stories yet.') ?></td></tr><?php endif ?>
        <?php foreach ($saved as $s): ?>
          <tr><td><?php echo $e($s->title) ?></td>
            <td><span class="badge <?php echo 'published' === $s->status ? 'bg-success' : 'bg-secondary' ?>"><?php echo 'published' === $s->status ? __('Published') : __('Draft') ?></span></td>
            <td class="small text-muted"><?php echo $e($s->updated_at) ?></td>
            <td class="text-end text-nowrap"><a class="btn btn-sm btn-outline-primary" href="<?php echo $url ?>?id=<?php echo (int) $s->id ?>"><?php echo __('Edit') ?></a>
              <a class="btn btn-sm btn-outline-secondary" href="<?php echo url_for(['module' => 'story', 'action' => 'show', 'slug' => $s->slug]) ?>"><?php echo __('View') ?></a></td></tr>
        <?php endforeach ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<script <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
(function () {
  var find = document.getElementById('st-find'), hits = document.getElementById('st-hits'), picked = document.getElementById('st-picked');
  var searchUrl = <?php echo json_encode(url_for(['module' => 'story', 'action' => 'search'])) ?>, timer;
  function chip(id, title) {
    if (picked.querySelector('input[value="' + id + '"]')) { return; }
    var s = document.createElement('span'); s.className = 'badge bg-secondary';
    var i = document.createElement('input'); i.type = 'hidden'; i.name = 'record_ids[]'; i.value = id; s.appendChild(i);
    s.appendChild(document.createTextNode(title + ' '));
    var b = document.createElement('button'); b.type = 'button'; b.className = 'btn-close btn-close-white ms-1 st-remove'; b.setAttribute('aria-label', <?php echo json_encode(__('Remove')) ?>); s.appendChild(b);
    picked.appendChild(s);
  }
  picked.addEventListener('click', function (ev) { if (ev.target.classList.contains('st-remove')) { ev.target.parentNode.remove(); } });
  find.addEventListener('input', function () {
    clearTimeout(timer);
    timer = setTimeout(function () {
      if (find.value.trim().length < 2) { hits.innerHTML = ''; return; }
      fetch(searchUrl + '?q=' + encodeURIComponent(find.value.trim()), { credentials: 'same-origin' }).then(function (r) { return r.json(); }).then(function (rows) {
        hits.innerHTML = '';
        rows.forEach(function (r) {
          var a = document.createElement('button'); a.type = 'button'; a.className = 'list-group-item list-group-item-action py-1'; a.textContent = r.title;
          a.addEventListener('click', function () { chip(r.id, r.title); hits.innerHTML = ''; find.value = ''; find.focus(); });
          hits.appendChild(a);
        });
      });
    }, 250);
  });
})();
</script>
