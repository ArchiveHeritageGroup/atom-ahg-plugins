<?php
/**
 * Search Result Adapter for arDisplayPlugin
 * 
 * Transforms Elasticsearch results into display-ready format
 * Avoids MySQL round-trips by using ES data directly
 */

class DisplaySearchResultAdapter
{
    protected $culture;
    
    public function __construct(?string $culture = null)
    {
        $this->culture = $culture ?? sfContext::getInstance()->getUser()->getCulture() ?? 'en';
    }
    
    /**
     * Transform ES hit to display data array
     * Compatible with DisplayService::prepareForDisplay() output
     */
    public function transformHit(array $hit): array
    {
        $objectType = $hit['object_type'] ?? 'archive';
        $profileCode = $hit['profile'] ?? $this->getDefaultProfileCode($objectType);
        
        // Get profile configuration
        $profile = $this->getProfileConfig($profileCode);
        
        // Build object-like structure from ES data
        $object = (object) [
            'id' => $hit['id'],
            'slug' => $hit['slug'],
            'identifier' => $hit['identifier'],
            'title' => $hit['title'],
            'level_name' => $hit['level'],
        ];
        
        // Build digital object if present
        $digitalObject = null;
        if ($hit['has_digital_object'] ?? false) {
            $digitalObject = (object) [
                'id' => null, // Not available from ES
                'path' => $hit['thumbnail_path'],
                'mime_type' => $hit['media_type'] ?? null,
            ];
        }
        
        // Build fields from ES data
        $fields = $this->buildFieldsFromHit($hit, $profile);
        
        return [
            'object' => $object,
            'object_type' => $objectType,
            'profile' => (object) [
                'code' => $profileCode,
                'layout_mode' => $profile['layout_mode'] ?? 'card',
                'thumbnail_size' => $profile['thumbnail_size'] ?? 'small',
                'thumbnail_position' => $profile['thumbnail_position'] ?? 'left',
            ],
            'digital_object' => $digitalObject,
            'layout' => $profile['layout_mode'] ?? 'card',
            'thumbnail_size' => $profile['thumbnail_size'] ?? 'small',
            'thumbnail_position' => $profile['thumbnail_position'] ?? 'left',
            'fields' => $fields,
            'actions' => $profile['actions'] ?? ['view'],
            'available_profiles' => [],
            'css_class' => '',
            'from_elasticsearch' => true,
        ];
    }
    
    /**
     * Transform array of ES hits
     */
    public function transformHits(array $hits): array
    {
        return array_map([$this, 'transformHit'], $hits);
    }
    
    /**
     * Build field groups from ES hit data
     */
    protected function buildFieldsFromHit(array $hit, array $profile): array
    {
        $fields = [
            'identity' => [],
            'description' => [],
            'context' => [],
            'access' => [],
            'technical' => [],
        ];
        
        // Identity fields
        if (!empty($hit['identifier'])) {
            $fields['identity']['identifier'] = [
                'code' => 'identifier',
                'label' => 'Reference Code',
                'value' => $hit['identifier'],
                'type' => 'text',
            ];
        }
        
        if (!empty($hit['title'])) {
            $fields['identity']['title'] = [
                'code' => 'title',
                'label' => 'Title',
                'value' => $hit['title'],
                'type' => 'text',
            ];
        }
        
        if (!empty($hit['level'])) {
            $fields['identity']['level'] = [
                'code' => 'level',
                'label' => 'Level',
                'value' => $hit['level'],
                'type' => 'term',
            ];
        }
        
        if (!empty($hit['creator'])) {
            $fields['identity']['creator'] = [
                'code' => 'creator',
                'label' => $this->getCreatorLabel($hit['object_type'] ?? 'archive'),
                'value' => $hit['creator'],
                'type' => 'actor',
            ];
        }
        
        if (!empty($hit['date'])) {
            $fields['identity']['date'] = [
                'code' => 'date',
                'label' => 'Date',
                'value' => $hit['date'],
                'type' => 'date',
            ];
        }
        
        // Description
        if (!empty($hit['description'])) {
            $fields['description']['description'] = [
                'code' => 'description',
                'label' => 'Description',
                'value' => $hit['description'],
                'type' => 'textarea',
            ];
        }
        
        // Subjects
        if (!empty($hit['subjects'])) {
            $fields['description']['subjects'] = [
                'code' => 'subjects',
                'label' => 'Subjects',
                'value' => $hit['subjects'],
                'type' => 'multiselect',
            ];
        }
        
        return $fields;
    }
    
    /**
     * Get creator label based on object type
     */
    protected function getCreatorLabel(string $objectType): string
    {
        return match($objectType) {
            'gallery' => 'Artist',
            'library' => 'Author',
            'dam' => 'Photographer',
            'museum' => 'Maker',
            default => 'Creator',
        };
    }
    
    /**
     * Get default profile code for object type
     */
    protected function getDefaultProfileCode(string $objectType): string
    {
        return match($objectType) {
            'museum' => 'spectrum_card',
            'gallery' => 'gallery_wall',
            'library' => 'book_card',
            'dam' => 'photo_grid',
            default => 'search_result',
        };
    }
    
    /**
     * Get profile configuration (simplified version without DB lookup)
     */
    protected function getProfileConfig(string $profileCode): array
    {
        $profiles = [
            'search_result' => [
                'layout_mode' => 'card',
                'thumbnail_size' => 'small',
                'thumbnail_position' => 'left',
                'actions' => ['view'],
            ],
            'spectrum_card' => [
                'layout_mode' => 'card',
                'thumbnail_size' => 'medium',
                'thumbnail_position' => 'top',
                'actions' => ['view', 'add_to_exhibition'],
            ],
            'gallery_wall' => [
                'layout_mode' => 'gallery',
                'thumbnail_size' => 'full',
                'thumbnail_position' => 'background',
                'actions' => ['view', 'zoom', 'info'],
            ],
            'book_card' => [
                'layout_mode' => 'card',
                'thumbnail_size' => 'small',
                'thumbnail_position' => 'left',
                'actions' => ['view'],
            ],
            'photo_grid' => [
                'layout_mode' => 'grid',
                'thumbnail_size' => 'medium',
                'thumbnail_position' => 'top',
                'actions' => ['view', 'select', 'add_to_lightbox'],
            ],
            'isad_list' => [
                'layout_mode' => 'list',
                'thumbnail_size' => 'small',
                'thumbnail_position' => 'left',
                'actions' => ['view'],
            ],
        ];
        
        return $profiles[$profileCode] ?? $profiles['search_result'];
    }
    
    /**
     * Render search result item using display templates
     */
    public function renderHit(array $hit): string
    {
        $data = $this->transformHit($hit);
        
        ob_start();
        
        // Include the display object template
        $layoutTemplate = sfConfig::get('sf_plugins_dir') . '/arDisplayPlugin/templates/layouts/_' . $data['layout'] . '.php';
        
        // Set variables for template
        $object = $data['object'];
        $profile = $data['profile'];
        $digitalObject = $data['digital_object'];
        $objectType = $data['object_type'];
        $fields = $data['fields'];
        
        // Load helper
        require_once sfConfig::get('sf_plugins_dir') . '/arDisplayPlugin/lib/helper/DisplayHelper.php';
        
        if (file_exists($layoutTemplate)) {
            include $layoutTemplate;
        } else {
            include sfConfig::get('sf_plugins_dir') . '/arDisplayPlugin/templates/layouts/_card.php';
        }
        
        return ob_get_clean();
    }
    
    /**
     * Render search results grid/list
     */
    public function renderResults(array $searchResults, string $layout = 'grid'): string
    {
        $hits = $searchResults['hits'] ?? [];
        
        ob_start();
        
        echo '<div class="display-search-results layout-' . $layout . '">';
        
        if ($layout === 'grid') {
            echo '<div class="row g-3">';
            foreach ($hits as $hit) {
                echo '<div class="col-md-4 col-lg-3">';
                echo $this->renderHit($hit);
                echo '</div>';
            }
            echo '</div>';
        } elseif ($layout === 'list') {
            echo '<table class="table table-hover">';
            echo '<thead><tr><th></th><th>Title</th><th>Creator</th><th>Date</th><th>Type</th><th></th></tr></thead>';
            echo '<tbody>';
            foreach ($hits as $hit) {
                echo $this->renderHit($hit);
            }
            echo '</tbody></table>';
        } elseif ($layout === 'masonry') {
            echo '<div class="masonry-container" data-masonry=\'{"itemSelector": ".masonry-item", "columnWidth": ".masonry-sizer", "percentPosition": true}\'>';
            echo '<div class="masonry-sizer col-md-4"></div>';
            foreach ($hits as $hit) {
                echo '<div class="col-md-4 masonry-item">';
                echo $this->renderHit($hit);
                echo '</div>';
            }
            echo '</div>';
        } else {
            // Card layout (default)
            echo '<div class="row g-3">';
            foreach ($hits as $hit) {
                echo '<div class="col-md-6 col-lg-4">';
                echo $this->renderHit($hit);
                echo '</div>';
            }
            echo '</div>';
        }
        
        echo '</div>';
        
        return ob_get_clean();
    }
    
    /**
     * Render aggregation facets
     */
    public function renderFacets(array $aggregations): string
    {
        ob_start();
        
        echo '<div class="display-facets">';
        
        // Object Types
        if (!empty($aggregations['object_types'])) {
            echo '<div class="facet-group mb-4">';
            echo '<h6 class="facet-title"><i class="fas fa-layer-group me-2"></i>Collection Type</h6>';
            echo '<div class="list-group list-group-flush">';
            foreach ($aggregations['object_types'] as $bucket) {
                $icon = match($bucket['key']) {
                    'archive' => 'fa-archive',
                    'museum' => 'fa-landmark',
                    'gallery' => 'fa-palette',
                    'library' => 'fa-book',
                    'dam' => 'fa-images',
                    default => 'fa-folder',
                };
                echo '<a href="?object_type=' . $bucket['key'] . '" class="list-group-item list-group-item-action d-flex justify-content-between">';
                echo '<span><i class="fas ' . $icon . ' me-2"></i>' . ucfirst($bucket['key']) . '</span>';
                echo '<span class="badge bg-secondary">' . $bucket['count'] . '</span>';
                echo '</a>';
            }
            echo '</div></div>';
        }
        
        // Media Types
        if (!empty($aggregations['media_types'])) {
            echo '<div class="facet-group mb-4">';
            echo '<h6 class="facet-title"><i class="fas fa-photo-video me-2"></i>Media Type</h6>';
            echo '<div class="list-group list-group-flush">';
            foreach ($aggregations['media_types'] as $bucket) {
                $icon = match($bucket['key']) {
                    'image' => 'fa-image',
                    'video' => 'fa-video',
                    'audio' => 'fa-music',
                    'document' => 'fa-file-pdf',
                    default => 'fa-file',
                };
                echo '<a href="?media_type=' . $bucket['key'] . '" class="list-group-item list-group-item-action d-flex justify-content-between">';
                echo '<span><i class="fas ' . $icon . ' me-2"></i>' . ucfirst($bucket['key']) . '</span>';
                echo '<span class="badge bg-secondary">' . $bucket['count'] . '</span>';
                echo '</a>';
            }
            echo '</div></div>';
        }
        
        // Subjects
        if (!empty($aggregations['subjects'])) {
            echo '<div class="facet-group mb-4">';
            echo '<h6 class="facet-title"><i class="fas fa-tags me-2"></i>Subjects</h6>';
            echo '<div class="list-group list-group-flush" style="max-height: 250px; overflow-y: auto;">';
            foreach (array_slice($aggregations['subjects'], 0, 15) as $bucket) {
                echo '<a href="?subject=' . urlencode($bucket['key']) . '" class="list-group-item list-group-item-action d-flex justify-content-between">';
                echo '<span>' . htmlspecialchars($bucket['key']) . '</span>';
                echo '<span class="badge bg-secondary">' . $bucket['count'] . '</span>';
                echo '</a>';
            }
            echo '</div></div>';
        }
        
        // Creators
        if (!empty($aggregations['creators'])) {
            echo '<div class="facet-group mb-4">';
            echo '<h6 class="facet-title"><i class="fas fa-user me-2"></i>Creators</h6>';
            echo '<div class="list-group list-group-flush" style="max-height: 250px; overflow-y: auto;">';
            foreach (array_slice($aggregations['creators'], 0, 15) as $bucket) {
                echo '<a href="?creator=' . urlencode($bucket['key']) . '" class="list-group-item list-group-item-action d-flex justify-content-between">';
                echo '<span>' . htmlspecialchars($bucket['key']) . '</span>';
                echo '<span class="badge bg-secondary">' . $bucket['count'] . '</span>';
                echo '</a>';
            }
            echo '</div></div>';
        }
        
        // Has Digital Object
        if (!empty($aggregations['has_digital'])) {
            echo '<div class="facet-group mb-4">';
            echo '<h6 class="facet-title"><i class="fas fa-file-image me-2"></i>Digital Content</h6>';
            echo '<div class="list-group list-group-flush">';
            foreach ($aggregations['has_digital'] as $bucket) {
                $label = $bucket['key'] ? 'With digital content' : 'No digital content';
                $icon = $bucket['key'] ? 'fa-check text-success' : 'fa-times text-muted';
                echo '<a href="?has_digital=' . ($bucket['key'] ? '1' : '0') . '" class="list-group-item list-group-item-action d-flex justify-content-between">';
                echo '<span><i class="fas ' . $icon . ' me-2"></i>' . $label . '</span>';
                echo '<span class="badge bg-secondary">' . $bucket['count'] . '</span>';
                echo '</a>';
            }
            echo '</div></div>';
        }
        
        echo '</div>';
        
        return ob_get_clean();
    }
}
