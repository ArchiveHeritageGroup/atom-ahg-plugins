<?php
/**
 * Heritage Admin - Featured Stories.
 *
 * Replaces "Stories are managed via the database. Contact your administrator",
 * which made a routine editorial task into an IT request.
 */

decorate_with('layout_2col');

// Symfony wraps view variables in output escapers, and count()/foreach over a
// decorator does not behave like the array. Unwrap before use - same helper the
// hero slides template carries, for the same reason.
$unwrap = function ($value) use (&$unwrap) {
    if ($value instanceof sfOutputEscaperObjectDecorator) {
        $raw = $value->getRawValue();

        return is_object($raw) ? (array) $raw : $raw;
    }
    if ($value instanceof sfOutputEscaperArrayDecorator || $value instanceof Traversable) {
        $out = [];
        foreach ($value as $k => $v) {
            $out[$k] = $unwrap($v);
        }

        return $out;
    }
    if (is_array($value)) {
        return array_map($unwrap, $value);
    }

    return is_object($value) ? (array) $value : $value;
};

$stories = $unwrap($stories);
$editStory = (isset($editStory) && $editStory) ? $unwrap($editStory) : null;
?>

<?php slot('title'); ?>
<h1 class="h3"><i class="fas fa-book-open me-2"></i><?php echo __('Featured stories'); ?></h1>
<?php end_slot(); ?>

<?php slot('sidebar'); ?>
<?php include_partial('heritage/adminSidebar', ['active' => 'stories']); ?>
<?php end_slot(); ?>

<div class="heritage-admin-stories">
    <?php if ($sf_user->hasFlash('notice')): ?>
      <div class="alert alert-success"><?php echo esc_specialchars($sf_user->getFlash('notice')); ?></div>
    <?php endif; ?>
    <?php if ($sf_user->hasFlash('error')): ?>
      <div class="alert alert-danger"><?php echo esc_specialchars($sf_user->getFlash('error')); ?></div>
    <?php endif; ?>

    <div class="card border-0 shadow-sm mb-4">
      <div class="card-header bg-white">
        <h5 class="mb-0"><?php echo $editStory ? __('Edit story') : __('Add a story'); ?></h5>
      </div>
      <div class="card-body">
        <form method="post" action="<?php echo url_for(['module' => 'heritage', 'action' => 'adminStories']); ?>">
          <input type="hidden" name="story_action" value="<?php echo $editStory ? 'update' : 'create'; ?>">
          <?php if ($editStory): ?>
            <input type="hidden" name="story_id" value="<?php echo (int) $editStory['id']; ?>">
          <?php endif; ?>

          <div class="row g-3">
            <div class="col-md-8">
              <label class="form-label" for="title"><?php echo __('Title'); ?> <span class="text-danger">*</span></label>
              <input class="form-control" type="text" id="title" name="title" required
                     value="<?php echo esc_specialchars($editStory['title'] ?? ''); ?>">
            </div>
            <div class="col-md-4">
              <label class="form-label" for="display_order"><?php echo __('Order'); ?></label>
              <input class="form-control" type="number" id="display_order" name="display_order"
                     value="<?php echo (int) ($editStory['display_order'] ?? 100); ?>">
            </div>

            <div class="col-12">
              <label class="form-label" for="subtitle"><?php echo __('Subtitle'); ?></label>
              <input class="form-control" type="text" id="subtitle" name="subtitle"
                     value="<?php echo esc_specialchars($editStory['subtitle'] ?? ''); ?>">
            </div>

            <div class="col-12">
              <label class="form-label" for="description"><?php echo __('Description'); ?></label>
              <textarea class="form-control" id="description" name="description" rows="3"><?php echo esc_specialchars($editStory['description'] ?? ''); ?></textarea>
            </div>

            <div class="col-md-6">
              <label class="form-label" for="cover_image"><?php echo __('Cover image path'); ?></label>
              <input class="form-control" type="text" id="cover_image" name="cover_image"
                     placeholder="/uploads/heritage/stories/example.jpg"
                     value="<?php echo esc_specialchars($editStory['cover_image'] ?? ''); ?>">
            </div>

            <div class="col-md-3">
              <label class="form-label" for="link_type"><?php echo __('Link type'); ?></label>
              <?php $lt = $editStory['link_type'] ?? 'search'; ?>
              <select class="form-select" id="link_type" name="link_type">
                <?php foreach (['search' => 'Search', 'collection' => 'Collection', 'external' => 'External URL', 'page' => 'Page'] as $v => $l): ?>
                  <option value="<?php echo $v; ?>" <?php echo $lt === $v ? 'selected' : ''; ?>><?php echo __($l); ?></option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="col-md-3">
              <label class="form-label" for="item_count"><?php echo __('Item count'); ?></label>
              <input class="form-control" type="number" id="item_count" name="item_count"
                     value="<?php echo esc_specialchars((string) ($editStory['item_count'] ?? '')); ?>">
            </div>

            <div class="col-12">
              <label class="form-label" for="link_reference"><?php echo __('Link target'); ?></label>
              <input class="form-control" type="text" id="link_reference" name="link_reference"
                     value="<?php echo esc_specialchars($editStory['link_reference'] ?? ''); ?>">
            </div>

            <div class="col-md-3">
              <label class="form-label" for="start_date"><?php echo __('Show from'); ?></label>
              <input class="form-control" type="date" id="start_date" name="start_date"
                     value="<?php echo esc_specialchars($editStory['start_date'] ?? ''); ?>">
            </div>
            <div class="col-md-3">
              <label class="form-label" for="end_date"><?php echo __('Show until'); ?></label>
              <input class="form-control" type="date" id="end_date" name="end_date"
                     value="<?php echo esc_specialchars($editStory['end_date'] ?? ''); ?>">
            </div>

            <div class="col-md-6 d-flex align-items-end gap-4">
              <?php // Hidden 0 before each checkbox: an unchecked box submits nothing,
                    // so without this the field can never be turned off. ?>
              <div class="form-check">
                <input type="hidden" name="is_enabled" value="0">
                <input class="form-check-input" type="checkbox" id="is_enabled" name="is_enabled" value="1"
                       <?php echo (!$editStory || !empty($editStory['is_enabled'])) ? 'checked' : ''; ?>>
                <label class="form-check-label" for="is_enabled"><?php echo __('Enabled'); ?></label>
              </div>
              <div class="form-check">
                <input type="hidden" name="is_featured" value="0">
                <input class="form-check-input" type="checkbox" id="is_featured" name="is_featured" value="1"
                       <?php echo !empty($editStory['is_featured']) ? 'checked' : ''; ?>>
                <label class="form-check-label" for="is_featured"><?php echo __('Featured'); ?></label>
              </div>
            </div>
          </div>

          <div class="mt-3">
            <button class="btn btn-primary" type="submit"><?php echo $editStory ? __('Save changes') : __('Add story'); ?></button>
            <?php if ($editStory): ?>
              <a class="btn btn-outline-secondary" href="<?php echo url_for(['module' => 'heritage', 'action' => 'adminStories']); ?>"><?php echo __('Cancel'); ?></a>
            <?php endif; ?>
          </div>
        </form>
      </div>
    </div>

    <div class="card border-0 shadow-sm">
      <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><?php echo __('Stories'); ?></h5>
        <span class="text-muted small"><?php echo count($stories); ?> <?php echo __('total'); ?></span>
      </div>
      <div class="card-body p-0">
        <?php if (!count($stories)): ?>
          <p class="text-muted p-3 mb-0"><?php echo __('No stories yet. Add one above.'); ?></p>
        <?php else: ?>
        <div class="table-responsive">
          <table class="table mb-0 align-middle">
            <thead>
              <tr>
                <th><?php echo __('Title'); ?></th>
                <th><?php echo __('Link'); ?></th>
                <th><?php echo __('Order'); ?></th>
                <th><?php echo __('Featured'); ?></th>
                <th><?php echo __('Enabled'); ?></th>
                <th></th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($stories as $s): ?>
              <tr>
                <td>
                  <strong><?php echo esc_specialchars($s['title']); ?></strong>
                  <?php if (!empty($s['subtitle'])): ?>
                    <div class="small text-muted"><?php echo esc_specialchars($s['subtitle']); ?></div>
                  <?php endif; ?>
                </td>
                <td class="small text-muted">
                  <?php echo esc_specialchars($s['link_type'] ?? ''); ?>
                  <?php if (!empty($s['link_reference'])): ?>
                    <div><?php echo esc_specialchars(substr($s['link_reference'], 0, 40)); ?></div>
                  <?php endif; ?>
                </td>
                <td><?php echo (int) ($s['display_order'] ?? 0); ?></td>
                <td><?php echo !empty($s['is_featured']) ? '<span class="badge bg-info">'.__('yes').'</span>' : ''; ?></td>
                <td>
                  <?php if (!empty($s['is_enabled'])): ?>
                    <span class="badge bg-success"><?php echo __('enabled'); ?></span>
                  <?php else: ?>
                    <span class="badge bg-secondary"><?php echo __('disabled'); ?></span>
                  <?php endif; ?>
                </td>
                <td class="text-end">
                  <a class="btn btn-sm btn-outline-secondary"
                     href="<?php echo url_for(['module' => 'heritage', 'action' => 'adminStories', 'story_id' => $s['id']]); ?>"><?php echo __('Edit'); ?></a>
                  <?php // Destructive actions are POST, so a crawler following a link
                        // cannot delete a story. ?>
                  <form class="d-inline" method="post"
                        action="<?php echo url_for(['module' => 'heritage', 'action' => 'adminStories']); ?>">
                    <input type="hidden" name="story_action" value="toggle">
                    <input type="hidden" name="story_id" value="<?php echo (int) $s['id']; ?>">
                    <button class="btn btn-sm btn-outline-secondary" type="submit"><?php echo !empty($s['is_enabled']) ? __('Disable') : __('Enable'); ?></button>
                  </form>
                  <form class="d-inline" method="post"
                        action="<?php echo url_for(['module' => 'heritage', 'action' => 'adminStories']); ?>"
                        onsubmit="return confirm('<?php echo __('Delete this story?'); ?>');">
                    <input type="hidden" name="story_action" value="delete">
                    <input type="hidden" name="story_id" value="<?php echo (int) $s['id']; ?>">
                    <button class="btn btn-sm btn-outline-danger" type="submit"><?php echo __('Delete'); ?></button>
                  </form>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <?php endif; ?>
      </div>
    </div>
</div>
