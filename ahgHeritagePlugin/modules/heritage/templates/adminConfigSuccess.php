<?php
/**
 * Heritage Admin Configuration Page.
 *
 * Admin interface for configuring the landing page.
 */

decorate_with('layout_2col');

// Helper to unwrap Symfony escaper objects
$unwrap = function ($value) use (&$unwrap) {
    if ($value instanceof sfOutputEscaperObjectDecorator) {
        $raw = $value->getRawValue();
        return is_object($raw) ? (array) $raw : $raw;
    }
    if ($value instanceof sfOutputEscaperArrayDecorator || $value instanceof Traversable) {
        $result = [];
        foreach ($value as $k => $v) {
            $result[$k] = $unwrap($v);
        }
        return $result;
    }
    if (is_array($value)) {
        return array_map($unwrap, $value);
    }
    if (is_object($value)) {
        return (array) $value;
    }
    return $value;
};

// Unwrap heroImages if set
$heroImagesArray = isset($heroImages) ? $unwrap($heroImages) : [];

// Get config values with defaults
$heroTagline = $config->hero_tagline ?? 'Discover Our Heritage';
$heroSubtext = $config->hero_subtext ?? '';
$heroSearchPlaceholder = $config->hero_search_placeholder ?? 'What are you looking for?';
$suggestedSearches = $config->suggested_searches ?? [];
if (is_string($suggestedSearches)) {
    $suggestedSearches = json_decode($suggestedSearches, true) ?: [];
} elseif (!is_array($suggestedSearches) && $suggestedSearches instanceof Traversable) {
    $suggestedSearches = iterator_to_array($suggestedSearches);
}
$heroRotationSeconds = $config->hero_rotation_seconds ?? 8;
$heroEffect = $config->hero_effect ?? 'kenburns';
$showCuratedStories = $config->show_curated_stories ?? 1;
$showCommunityActivity = $config->show_community_activity ?? 1;
$showFilters = $config->show_filters ?? 1;
$showStats = $config->show_stats ?? 1;
$showRecentAdditions = $config->show_recent_additions ?? 1;
$primaryColor = $config->primary_color ?? '#0d6efd';
$secondaryColor = $config->secondary_color ?? '';
?>

<?php slot('title'); ?>
<h1 class="h3">
    <i class="fas fa-cog me-2"></i>Heritage Landing Configuration
</h1>
<?php end_slot(); ?>

<?php slot('sidebar'); ?>
<div class="list-group">
    <a href="#section-hero" class="list-group-item list-group-item-action">Hero Section</a>
    <a href="#section-sections" class="list-group-item list-group-item-action">Sections</a>
    <a href="#section-filters" class="list-group-item list-group-item-action">Filters</a>
    <a href="#section-stories" class="list-group-item list-group-item-action">Stories</a>
    <a href="#section-images" class="list-group-item list-group-item-action">Hero Images</a>
    <a href="#section-styling" class="list-group-item list-group-item-action">Styling</a>
</div>

<div class="mt-4">
    <a href="<?php echo url_for(['module' => 'heritage', 'action' => 'landing']); ?>" class="btn btn-outline-primary w-100" target="_blank">
        <i class="fas fa-eye me-2"></i>Preview Landing Page
    </a>
</div>
<?php end_slot(); ?>

<form action="<?php echo url_for(['module' => 'heritage', 'action' => 'adminConfig']); ?>" method="post">

    <!-- Hero Section Settings -->
    <div class="card border-0 shadow-sm mb-4" id="section-hero">
        <div class="card-header bg-transparent">
            <h2 class="h5 mb-0">Hero Section</h2>
        </div>
        <div class="card-body">
            <div class="mb-3">
                <label for="hero_tagline" class="form-label">Tagline</label>
                <input type="text" class="form-control" id="hero_tagline" name="hero_tagline"
                       value="<?php echo esc_specialchars($heroTagline); ?>" maxlength="500">
                <div class="form-text">Main headline displayed in the hero section.</div>
            </div>

            <div class="mb-3">
                <label for="hero_subtext" class="form-label">Subtext</label>
                <textarea class="form-control" id="hero_subtext" name="hero_subtext" rows="2"><?php echo esc_specialchars($heroSubtext); ?></textarea>
                <div class="form-text">Supporting text below the tagline.</div>
            </div>

            <div class="mb-3">
                <label for="hero_search_placeholder" class="form-label">Search Placeholder</label>
                <input type="text" class="form-control" id="hero_search_placeholder" name="hero_search_placeholder"
                       value="<?php echo esc_specialchars($heroSearchPlaceholder); ?>">
            </div>

            <div class="mb-3">
                <label for="suggested_searches" class="form-label">Suggested Searches</label>
                <textarea class="form-control" id="suggested_searches" name="suggested_searches" rows="4"
                          placeholder="One search term per line"><?php echo esc_specialchars(implode("\n", $suggestedSearches)); ?></textarea>
                <div class="form-text">Enter one search suggestion per line. These appear as clickable links below the search box.</div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="hero_effect" class="form-label">Background Effect</label>
                    <select class="form-select" id="hero_effect" name="hero_effect">
                        <option value="kenburns" <?php echo $heroEffect === 'kenburns' ? 'selected' : ''; ?>>Ken Burns (zoom/pan)</option>
                        <option value="fade" <?php echo $heroEffect === 'fade' ? 'selected' : ''; ?>>Fade</option>
                        <option value="none" <?php echo $heroEffect === 'none' ? 'selected' : ''; ?>>None (static)</option>
                    </select>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="hero_rotation_seconds" class="form-label">Image Rotation (seconds)</label>
                    <input type="number" class="form-control" id="hero_rotation_seconds" name="hero_rotation_seconds"
                           value="<?php echo (int) $heroRotationSeconds; ?>" min="1" max="60">
                </div>
            </div>
        </div>
    </div>

    <!-- Sections Toggle -->
    <div class="card border-0 shadow-sm mb-4" id="section-sections">
        <div class="card-header bg-transparent">
            <h2 class="h5 mb-0">Sections</h2>
        </div>
        <div class="card-body">
            <p class="text-muted">Choose which sections to display on the landing page.</p>

            <div class="form-check form-switch mb-3">
                <input class="form-check-input" type="checkbox" id="show_filters" name="show_filters"
                       <?php echo $showFilters ? 'checked' : ''; ?>>
                <label class="form-check-label" for="show_filters">
                    <strong>Explore By</strong> - Filter categories with top values
                </label>
            </div>

            <div class="form-check form-switch mb-3">
                <input class="form-check-input" type="checkbox" id="show_curated_stories" name="show_curated_stories"
                       <?php echo $showCuratedStories ? 'checked' : ''; ?>>
                <label class="form-check-label" for="show_curated_stories">
                    <strong>Featured Stories</strong> - Curated collections and narratives
                </label>
            </div>

            <div class="form-check form-switch mb-3">
                <input class="form-check-input" type="checkbox" id="show_community_activity" name="show_community_activity"
                       <?php echo $showCommunityActivity ? 'checked' : ''; ?>>
                <label class="form-check-label" for="show_community_activity">
                    <strong>Community Activity</strong> - Recent contributions
                </label>
            </div>

            <div class="form-check form-switch mb-3">
                <input class="form-check-input" type="checkbox" id="show_recent_additions" name="show_recent_additions"
                       <?php echo $showRecentAdditions ? 'checked' : ''; ?>>
                <label class="form-check-label" for="show_recent_additions">
                    <strong>Recently Added</strong> - Latest items carousel
                </label>
            </div>

            <div class="form-check form-switch mb-3">
                <input class="form-check-input" type="checkbox" id="show_stats" name="show_stats"
                       <?php echo $showStats ? 'checked' : ''; ?>>
                <label class="form-check-label" for="show_stats">
                    <strong>Statistics</strong> - Collection counts
                </label>
            </div>
        </div>
    </div>

    <!-- Filters Overview -->
    <div class="card border-0 shadow-sm mb-4" id="section-filters">
        <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
            <h2 class="h5 mb-0">Filters</h2>
        </div>
        <div class="card-body">
            <p class="text-muted">Filters displayed in the "Explore By" section. Drag to reorder.</p>

            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th style="width: 30px;"></th>
                            <th>Filter</th>
                            <th>Source</th>
                            <th class="text-center">Landing</th>
                            <th class="text-center">Search</th>
                            <th class="text-center">Enabled</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($filters as $filter): ?>
                        <tr>
                            <td><i class="fas fa-grip-vertical text-muted"></i></td>
                            <td>
                                <i class="<?php echo esc_specialchars($filter['icon'] ?? 'fa-tag'); ?> me-2"></i>
                                <?php echo esc_specialchars($filter['label']); ?>
                            </td>
                            <td><small class="text-muted"><?php echo esc_specialchars($filter['source_type']); ?></small></td>
                            <td class="text-center">
                                <?php if ($filter['show_on_landing']): ?>
                                <i class="fas fa-check-circle text-success"></i>
                                <?php else: ?>
                                <i class="fas fa-minus-circle text-muted"></i>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <?php if ($filter['show_in_search']): ?>
                                <i class="fas fa-check-circle text-success"></i>
                                <?php else: ?>
                                <i class="fas fa-minus-circle text-muted"></i>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <div class="form-check form-switch d-inline-block">
                                    <input class="form-check-input" type="checkbox" disabled
                                           <?php echo ($filter['is_enabled'] ?? true) ? 'checked' : ''; ?>>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <p class="small text-muted mb-0">Filter configuration is managed via the database. Contact your administrator to modify filter settings.</p>
        </div>
    </div>

    <!-- Stories -->
    <div class="card border-0 shadow-sm mb-4" id="section-stories">
        <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
            <h2 class="h5 mb-0">Featured Stories</h2>
            <span class="badge bg-secondary"><?php echo count($stories); ?> stories</span>
        </div>
        <div class="card-body">
            <?php if (empty($stories)): ?>
            <p class="text-muted text-center py-4">No stories configured yet.</p>
            <?php else: ?>
            <div class="list-group">
                <?php foreach ($stories as $story): ?>
                <div class="list-group-item d-flex justify-content-between align-items-center">
                    <div>
                        <strong><?php echo esc_specialchars($story->title ?? $story['title'] ?? 'Untitled'); ?></strong>
                        <?php $storyType = $story->story_type ?? $story['story_type'] ?? 'collection'; ?>
                        <span class="badge bg-light text-dark ms-2"><?php echo esc_specialchars($storyType); ?></span>
                        <?php $isEnabled = $story->is_enabled ?? $story['is_enabled'] ?? 1; ?>
                        <?php if (!$isEnabled): ?>
                        <span class="badge bg-warning text-dark ms-2">Disabled</span>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
            <p class="small text-muted mt-3 mb-0">Stories are managed via the database. Contact your administrator to add or modify stories.</p>
        </div>
    </div>

    <!-- Hero Images -->
    <div class="card border-0 shadow-sm mb-4" id="section-images">
        <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
            <h2 class="h5 mb-0">Hero Images</h2>
            <a href="<?php echo url_for(['module' => 'heritage', 'action' => 'adminHeroSlides']); ?>" class="btn btn-primary btn-sm">
                <i class="fas fa-cog me-1"></i>Manage Hero Slides
            </a>
        </div>
        <div class="card-body">
            <?php if (empty($heroImagesArray)): ?>
            <p class="text-muted text-center py-4">No hero images configured. A gradient background will be used instead.</p>
            <?php else: ?>
            <div class="row g-3">
                <?php foreach ($heroImagesArray as $image): ?>
                <div class="col-md-4">
                    <div class="card h-100">
                        <img src="<?php echo esc_specialchars($image['image_path'] ?? ''); ?>"
                             class="card-img-top" style="height: 120px; object-fit: cover;"
                             alt="Hero image">
                        <div class="card-body p-2">
                            <small class="text-muted">
                                <?php echo esc_specialchars($image['title'] ?? $image['caption'] ?? 'No title'); ?>
                            </small>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
            <div class="mt-3">
                <a href="<?php echo url_for(['module' => 'heritage', 'action' => 'adminHeroSlides']); ?>" class="btn btn-outline-primary">
                    <i class="fas fa-images me-1"></i>Manage All Hero Slides
                </a>
            </div>
        </div>
    </div>

    <!-- Styling -->
    <div class="card border-0 shadow-sm mb-4" id="section-styling">
        <div class="card-header bg-transparent">
            <h2 class="h5 mb-0">Styling</h2>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="primary_color" class="form-label">Primary Color</label>
                    <div class="input-group">
                        <input type="color" class="form-control form-control-color" id="primary_color_picker"
                               value="<?php echo esc_specialchars($primaryColor); ?>"
                               onchange="document.getElementById('primary_color').value = this.value;">
                        <input type="text" class="form-control" id="primary_color" name="primary_color"
                               value="<?php echo esc_specialchars($primaryColor); ?>" pattern="#[0-9A-Fa-f]{6}">
                    </div>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="secondary_color" class="form-label">Secondary Color (optional)</label>
                    <div class="input-group">
                        <input type="color" class="form-control form-control-color" id="secondary_color_picker"
                               value="<?php echo esc_specialchars($secondaryColor ?: '#6c757d'); ?>"
                               onchange="document.getElementById('secondary_color').value = this.value;">
                        <input type="text" class="form-control" id="secondary_color" name="secondary_color"
                               value="<?php echo esc_specialchars($secondaryColor); ?>" pattern="#[0-9A-Fa-f]{6}">
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Submit -->
    <div class="d-flex justify-content-end gap-2 mb-4">
        <a href="<?php echo url_for(['module' => 'heritage', 'action' => 'landing']); ?>" class="btn btn-outline-secondary">
            Cancel
        </a>
        <button type="submit" class="btn btn-primary">
            <i class="fas fa-check me-2"></i>Save Configuration
        </button>
    </div>

</form>
