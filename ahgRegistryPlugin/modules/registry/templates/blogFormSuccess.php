<?php decorate_with(sfConfig::get('sf_plugins_dir').'/ahgRegistryPlugin/modules/registry/templates/layout_registry'); ?>

<?php slot('title'); ?><?php echo $post ? __('Edit Blog Post') : __('Write New Post'); ?><?php end_slot(); ?>

<?php slot('content'); ?>

<?php $n = sfConfig::get('csp_nonce', ''); $na = $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>

<?php include_partial('registry/breadcrumb', ['items' => [
  ['label' => __('Home'), 'url' => url_for('@homepage')],
  ['label' => __('Registry'), 'url' => url_for(['module' => 'registry', 'action' => 'index'])],
  ['label' => __('My Blog'), 'url' => url_for(['module' => 'registry', 'action' => 'myBlog'])],
  ['label' => $post ? __('Edit') : __('New Post')],
]]); ?>

<div class="row justify-content-center">
  <div class="col-lg-9">

    <h1 class="h3 mb-4"><?php echo $post ? __('Edit Blog Post') : __('Write New Post'); ?></h1>

    <?php if (isset($errors) && count($errors) > 0): ?>
      <div class="alert alert-danger">
        <ul class="mb-0">
          <?php foreach ($errors as $err): ?>
            <li><?php echo htmlspecialchars($err, ENT_QUOTES, 'UTF-8'); ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <?php $p = sfOutputEscaper::unescape($post); ?>

    <?php
      $formAction = $p && !empty($p->id)
        ? url_for(['module' => 'registry', 'action' => 'blogEdit', 'id' => $p->id])
        : url_for(['module' => 'registry', 'action' => 'blogNew']);
    ?>

    <form method="post" action="<?php echo $formAction; ?>" enctype="multipart/form-data">

      <div class="card mb-4">
        <div class="card-body">
          <div class="row g-3">
            <div class="col-12">
              <label for="bf-title" class="form-label"><?php echo __('Title'); ?> <span class="text-danger">*</span></label>
              <input type="text" class="form-control form-control-lg" id="bf-title" name="title" value="<?php echo htmlspecialchars($p->title ?? '', ENT_QUOTES, 'UTF-8'); ?>" required placeholder="<?php echo __('Enter a compelling title...'); ?>">
            </div>
            <div class="col-12">
              <label for="bf-slug" class="form-label"><?php echo __('Slug'); ?></label>
              <div class="input-group">
                <span class="input-group-text">/blog/</span>
                <input type="text" class="form-control" id="bf-slug" name="slug" value="<?php echo htmlspecialchars($p->slug ?? '', ENT_QUOTES, 'UTF-8'); ?>" placeholder="<?php echo __('auto-generated-from-title'); ?>">
              </div>
              <div class="form-text"><?php echo __('Leave blank to auto-generate from title.'); ?></div>
            </div>
          </div>
        </div>
      </div>

      <div class="card mb-4">
        <div class="card-header fw-semibold"><?php echo __('Content'); ?></div>
        <div class="card-body">
          <div class="row g-3">
            <div class="col-12">
              <label for="bf-content" class="form-label"><?php echo __('Content'); ?> <span class="text-danger">*</span></label>
              <textarea class="form-control" id="bf-content" name="content" rows="15" required placeholder="<?php echo __('Write your blog post content here...'); ?>"><?php echo htmlspecialchars($p->content ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
              <div class="form-text"><?php echo __('Supports HTML markup. Use &lt;p&gt;, &lt;h3&gt;, &lt;ul&gt;, &lt;a&gt; and other tags for formatting.'); ?></div>
            </div>
            <div class="col-12">
              <label for="bf-excerpt" class="form-label"><?php echo __('Excerpt'); ?></label>
              <textarea class="form-control" id="bf-excerpt" name="excerpt" rows="3" placeholder="<?php echo __('Brief summary shown in listings and previews...'); ?>"><?php echo htmlspecialchars($p->excerpt ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
            </div>
          </div>
        </div>
      </div>

      <div class="card mb-4">
        <div class="card-header fw-semibold"><?php echo __('Metadata'); ?></div>
        <div class="card-body">
          <div class="row g-3">
            <div class="col-md-6">
              <label for="bf-author-type" class="form-label"><?php echo __('Author Type'); ?></label>
              <select class="form-select" id="bf-author-type" name="author_type">
                <?php
                  $authorTypes = ['admin' => __('Admin / Personal'), 'vendor' => __('Vendor'), 'institution' => __('Institution'), 'user_group' => __('User Group')];
                  $selAuthor = $p->author_type ?? 'admin';
                  foreach ($authorTypes as $val => $label): ?>
                    <option value="<?php echo $val; ?>"<?php echo $selAuthor === $val ? ' selected' : ''; ?>><?php echo $label; ?></option>
                <?php endforeach; ?>
              </select>
              <div class="form-text"><?php echo __('If Vendor or Institution, the post will be attributed to your registered entity.'); ?></div>
            </div>
            <div class="col-md-6">
              <label for="bf-category" class="form-label"><?php echo __('Category'); ?></label>
              <select class="form-select" id="bf-category" name="category">
                <?php
                  $cats = [
                    'news' => __('News'), 'announcement' => __('Announcement'),
                    'event' => __('Event'), 'tutorial' => __('Tutorial'),
                    'case_study' => __('Case Study'), 'release' => __('Release'),
                    'community' => __('Community'), 'other' => __('Other'),
                  ];
                  $selCat = $p->category ?? 'news';
                  foreach ($cats as $val => $label): ?>
                    <option value="<?php echo $val; ?>"<?php echo $selCat === $val ? ' selected' : ''; ?>><?php echo $label; ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-8">
              <label for="bf-tags" class="form-label"><?php echo __('Tags'); ?></label>
              <?php
                $tagsVal = '';
                if (!empty($p->tags)) {
                  $rawTags = sfOutputEscaper::unescape($p->tags);
                  $decoded = is_string($rawTags) ? json_decode($rawTags, true) : (array) $rawTags;
                  $tagsVal = is_array($decoded) ? implode(', ', $decoded) : ($p->tags ?? '');
                }
              ?>
              <input type="text" class="form-control" id="bf-tags" name="tags" value="<?php echo htmlspecialchars($tagsVal, ENT_QUOTES, 'UTF-8'); ?>" placeholder="<?php echo __('Comma-separated: AtoM, archives, GLAM, preservation...'); ?>">
            </div>
            <div class="col-md-4">
              <?php if ($p && !empty($p->status)): ?>
              <label class="form-label"><?php echo __('Status'); ?></label>
              <div class="mt-1">
                <?php
                  $statusColors = ['draft' => 'secondary', 'pending_review' => 'warning', 'published' => 'success', 'archived' => 'dark'];
                  $sColor = $statusColors[$p->status] ?? 'secondary';
                ?>
                <span class="badge bg-<?php echo $sColor; ?><?php echo 'warning' === $sColor ? ' text-dark' : ''; ?> fs-6">
                  <?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $p->status)), ENT_QUOTES, 'UTF-8'); ?>
                </span>
                <div class="form-text"><?php echo __('Status is managed by administrators.'); ?></div>
              </div>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>

      <!-- Featured image -->
      <div class="card mb-4">
        <div class="card-header fw-semibold"><i class="fas fa-image me-2 text-info"></i><?php echo __('Featured Image'); ?></div>
        <div class="card-body">
          <?php if (!empty($p->featured_image_path)): ?>
            <div class="mb-2">
              <img src="<?php echo htmlspecialchars($p->featured_image_path, ENT_QUOTES, 'UTF-8'); ?>" alt="" class="rounded border" style="max-height: 120px;">
              <small class="text-muted d-block mt-1"><?php echo __('Upload a new image to replace.'); ?></small>
            </div>
          <?php endif; ?>
          <div class="border rounded p-3 text-center position-relative" id="blog-img-drop" style="min-height: 80px; cursor: pointer;">
            <div id="blog-img-preview">
              <i class="fas fa-cloud-upload-alt fa-2x text-muted mb-1"></i>
              <p class="mb-0 small"><?php echo __('Drag and drop, or click to upload a featured image.'); ?></p>
              <small class="text-muted"><?php echo __('PNG, JPG. Recommended: 1200x630px.'); ?></small>
            </div>
            <input type="file" class="position-absolute top-0 start-0 w-100 h-100 opacity-0" id="blog-img" name="featured_image" accept="image/png,image/jpeg" style="cursor: pointer;">
          </div>
        </div>
      </div>

      <div class="d-flex justify-content-between">
        <a href="<?php echo url_for(['module' => 'registry', 'action' => 'myBlog']); ?>" class="btn btn-outline-secondary"><?php echo __('Cancel'); ?></a>
        <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> <?php echo $post ? __('Save Changes') : __('Save as Draft'); ?></button>
      </div>

    </form>

  </div>
</div>

<script <?php echo $na; ?>>
document.addEventListener('DOMContentLoaded', function() {
  // Featured image preview
  var inp = document.getElementById('blog-img'), prev = document.getElementById('blog-img-preview'), drop = document.getElementById('blog-img-drop');
  if (inp) { inp.addEventListener('change', function(e) { if (e.target.files && e.target.files[0]) { var r = new FileReader(); r.onload = function(ev) { prev.innerHTML = '<img src="'+ev.target.result+'" alt="Preview" style="max-height:100px;" class="mb-1"><br><small class="text-muted">'+e.target.files[0].name+'</small>'; }; r.readAsDataURL(e.target.files[0]); } }); }
  if (drop) { ['dragenter','dragover'].forEach(function(ev){drop.addEventListener(ev,function(e){e.preventDefault();drop.classList.add('border-primary');});}); ['dragleave','drop'].forEach(function(ev){drop.addEventListener(ev,function(e){e.preventDefault();drop.classList.remove('border-primary');});}); drop.addEventListener('drop',function(e){if(e.dataTransfer.files.length){inp.files=e.dataTransfer.files;inp.dispatchEvent(new Event('change'));}}); }

  // Auto-generate slug from title
  var titleInput = document.getElementById('bf-title');
  var slugInput = document.getElementById('bf-slug');
  if (titleInput && slugInput && !slugInput.value) {
    titleInput.addEventListener('input', function() {
      slugInput.value = this.value.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '');
    });
  }
});
</script>

<?php end_slot(); ?>
