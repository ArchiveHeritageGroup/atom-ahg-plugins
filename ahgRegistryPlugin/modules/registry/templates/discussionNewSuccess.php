<?php decorate_with(sfConfig::get('sf_plugins_dir').'/ahgRegistryPlugin/modules/registry/templates/layout_registry'); ?>

<?php $detail = $group['group']; ?>

<?php slot('title'); ?><?php echo __('Start Discussion'); ?> - <?php echo htmlspecialchars($detail->name, ENT_QUOTES, 'UTF-8'); ?><?php end_slot(); ?>

<?php slot('content'); ?>

<?php include_partial('registry/breadcrumb', ['items' => [
  ['label' => __('Home'), 'url' => url_for('@homepage')],
  ['label' => __('Registry'), 'url' => url_for(['module' => 'registry', 'action' => 'index'])],
  ['label' => __('Community'), 'url' => url_for(['module' => 'registry', 'action' => 'community'])],
  ['label' => htmlspecialchars($detail->name, ENT_QUOTES, 'UTF-8'), 'url' => url_for(['module' => 'registry', 'action' => 'groupView', 'slug' => $detail->slug])],
  ['label' => __('New Discussion')],
]]); ?>

<div class="row justify-content-center">
  <div class="col-lg-8">

    <h1 class="h3 mb-4"><?php echo __('Start a New Discussion'); ?></h1>

    <?php if (isset($errors) && count($errors) > 0): ?>
      <div class="alert alert-danger">
        <ul class="mb-0">
          <?php foreach ($errors as $err): ?>
            <li><?php echo htmlspecialchars($err, ENT_QUOTES, 'UTF-8'); ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <?php $fd = isset($formData) ? $formData : []; ?>

    <form method="post" action="<?php echo url_for(['module' => 'registry', 'action' => 'discussionNew', 'slug' => $detail->slug]); ?>" enctype="multipart/form-data">

      <div class="card mb-4">
        <div class="card-body">
          <div class="row g-3">
            <div class="col-md-8">
              <label for="dn-title" class="form-label"><?php echo __('Title'); ?> <span class="text-danger">*</span></label>
              <input type="text" class="form-control" id="dn-title" name="title" value="<?php echo htmlspecialchars($fd['title'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required placeholder="<?php echo __('What would you like to discuss?'); ?>">
            </div>
            <div class="col-md-4">
              <label for="dn-type" class="form-label"><?php echo __('Topic Type'); ?></label>
              <select class="form-select" id="dn-type" name="topic_type">
                <?php
                  $topicTypes = [
                    'discussion' => __('Discussion'), 'question' => __('Question'),
                    'announcement' => __('Announcement'), 'event' => __('Event'),
                    'showcase' => __('Showcase'), 'help' => __('Help / Support'),
                  ];
                  $selTopic = $fd['topic_type'] ?? 'discussion';
                  foreach ($topicTypes as $val => $label): ?>
                    <option value="<?php echo $val; ?>"<?php echo $selTopic === $val ? ' selected' : ''; ?>><?php echo $label; ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-12">
              <label for="dn-content" class="form-label"><?php echo __('Content'); ?> <span class="text-danger">*</span></label>
              <textarea class="form-control" id="dn-content" name="content" rows="10" required placeholder="<?php echo __('Share your thoughts, questions, or announcements...'); ?>"><?php echo htmlspecialchars($fd['content'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
              <div class="form-text"><?php echo __('Plain text. Paragraphs separated by blank lines.'); ?></div>
            </div>
            <div class="col-md-8">
              <label for="dn-tags" class="form-label"><?php echo __('Tags'); ?></label>
              <input type="text" class="form-control" id="dn-tags" name="tags" value="<?php echo htmlspecialchars($fd['tags'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" placeholder="<?php echo __('Comma-separated: AtoM, migration, metadata...'); ?>">
            </div>
            <div class="col-md-4">
              <label for="dn-attachment" class="form-label"><?php echo __('Attachment'); ?></label>
              <input type="file" class="form-control" id="dn-attachment" name="attachment">
              <div class="form-text"><?php echo __('Optional. Max 10MB.'); ?></div>
            </div>
          </div>
        </div>
      </div>

      <div class="d-flex justify-content-between">
        <a href="<?php echo url_for(['module' => 'registry', 'action' => 'groupView', 'slug' => $detail->slug]); ?>" class="btn btn-outline-secondary"><?php echo __('Cancel'); ?></a>
        <button type="submit" class="btn btn-primary"><i class="fas fa-paper-plane me-1"></i> <?php echo __('Post Discussion'); ?></button>
      </div>

    </form>

  </div>
</div>

<?php end_slot(); ?>
