<?php
/**
 * CCO Index/View Template
 *
 * @package    ahgMuseumPlugin
 * @subpackage templates
 */

use Illuminate\Database\Capsule\Manager as DB;

// Constants
define('ROOT_INFORMATION_OBJECT_ID', 1);

/**
 * Get resource i18n data with culture fallback
 */
function cco_get_resource_i18n($resourceId): ?object
{
    $culture = sfContext::getInstance()->getUser()->getCulture() ?? 'en';
    
    return DB::table('information_object_i18n as ioi')
        ->where('ioi.id', $resourceId)
        ->where(function ($query) use ($culture) {
            $query->where('ioi.culture', $culture)
                ->orWhere('ioi.culture', 'en');
        })
        ->orderByRaw("CASE WHEN ioi.culture = ? THEN 0 ELSE 1 END", [$culture])
        ->first();
}

/**
 * Get i18n field value with culture fallback
 */
function cco_get_i18n_field($resourceId, string $field): ?string
{
    $culture = sfContext::getInstance()->getUser()->getCulture() ?? 'en';
    
    $result = DB::table('information_object_i18n')
        ->where('id', $resourceId)
        ->where('culture', $culture)
        ->value($field);
    
    if ($result === null) {
        $result = DB::table('information_object_i18n')
            ->where('id', $resourceId)
            ->where('culture', 'en')
            ->value($field);
    }
    
    return $result;
}

/**
 * Get ancestors for breadcrumb
 */
function cco_get_ancestors($resource): array
{
    $culture = sfContext::getInstance()->getUser()->getCulture() ?? 'en';
    
    return DB::table('information_object as io')
        ->leftJoin('information_object_i18n as ioi', function ($join) use ($culture) {
            $join->on('io.id', '=', 'ioi.id')
                ->where('ioi.culture', '=', $culture);
        })
        ->leftJoin('information_object_i18n as ioi_en', function ($join) {
            $join->on('io.id', '=', 'ioi_en.id')
                ->where('ioi_en.culture', '=', 'en');
        })
        ->leftJoin('slug', 'io.id', '=', 'slug.object_id')
        ->where('io.lft', '<=', $resource->lft)
        ->where('io.rgt', '>=', $resource->rgt)
        ->where('io.id', '!=', ROOT_INFORMATION_OBJECT_ID)
        ->orderBy('io.lft')
        ->select([
            'io.id',
            'io.lft',
            'io.rgt',
            'slug.slug',
            DB::raw('COALESCE(ioi.title, ioi_en.title) as title'),
        ])
        ->get()
        ->toArray();
}

/**
 * Get digital objects for resource
 */
function cco_get_digital_objects($resourceId): array
{
    return DB::table('digital_object')
        ->where('object_id', $resourceId)
        ->select([
            'id',
            'name',
            'path',
            'mime_type as mimeType',
            'byte_size as byteSize',
            'object_id',
            'media_type_id as mediaTypeId',
        ])
        ->get()
        ->toArray();
}

/**
 * Get dates (events) for resource
 */
function cco_get_dates($resourceId): array
{
    $culture = sfContext::getInstance()->getUser()->getCulture() ?? 'en';
    
    return DB::table('event as e')
        ->leftJoin('event_i18n as ei', function ($join) use ($culture) {
            $join->on('e.id', '=', 'ei.id')
                ->where('ei.culture', '=', $culture);
        })
        ->leftJoin('event_i18n as ei_en', function ($join) {
            $join->on('e.id', '=', 'ei_en.id')
                ->where('ei_en.culture', '=', 'en');
        })
        ->leftJoin('term_i18n as ti', function ($join) use ($culture) {
            $join->on('e.type_id', '=', 'ti.id')
                ->where('ti.culture', '=', $culture);
        })
        ->leftJoin('term_i18n as ti_en', function ($join) {
            $join->on('e.type_id', '=', 'ti_en.id')
                ->where('ti_en.culture', '=', 'en');
        })
        ->where('e.object_id', $resourceId)
        ->whereNotNull('e.type_id')
        ->select([
            'e.id',
            'e.start_date',
            'e.end_date',
            'e.type_id',
            DB::raw('COALESCE(ei.date, ei_en.date) as date_display'),
            DB::raw('COALESCE(ti.name, ti_en.name) as type_name'),
        ])
        ->get()
        ->toArray();
}

/**
 * Render date range 
 */
function cco_render_date_range(?string $displayDate, ?string $startDate, ?string $endDate): string
{
    if ($displayDate) {
        return $displayDate;
    }
    
    if ($startDate && $endDate) {
        if ($startDate === $endDate) {
            return $startDate;
        }
        return $startDate . ' - ' . $endDate;
    }
    
    if ($startDate) {
        return $startDate . ' - ';
    }
    
    if ($endDate) {
        return ' - ' . $endDate;
    }
    
    return '';
}

/**
 * Get term name by ID
 */
function cco_get_term_name(?int $termId): ?string
{
    if (!$termId) {
        return null;
    }
    
    $culture = sfContext::getInstance()->getUser()->getCulture() ?? 'en';
    
    $term = DB::table('term_i18n')
        ->where('id', $termId)
        ->where('culture', $culture)
        ->value('name');
    
    if ($term === null) {
        $term = DB::table('term_i18n')
            ->where('id', $termId)
            ->where('culture', 'en')
            ->value('name');
    }
    
    return $term;
}

/**
 * Get repository for resource
 */
function cco_get_repository($resource): ?object
{
    $culture = sfContext::getInstance()->getUser()->getCulture() ?? 'en';
    
    $repositoryId = $resource->repository_id ?? null;
    
    // If no repository, try to inherit from parent
    if (!$repositoryId && isset($resource->lft) && isset($resource->rgt)) {
        $repositoryId = DB::table('information_object')
            ->where('lft', '<', $resource->lft)
            ->where('rgt', '>', $resource->rgt)
            ->whereNotNull('repository_id')
            ->orderBy('lft', 'desc')
            ->value('repository_id');
    }
    
    if (!$repositoryId) {
        return null;
    }
    
    return DB::table('repository as r')
        ->leftJoin('actor_i18n as ai', function ($join) use ($culture) {
            $join->on('r.id', '=', 'ai.id')
                ->where('ai.culture', '=', $culture);
        })
        ->leftJoin('actor_i18n as ai_en', function ($join) {
            $join->on('r.id', '=', 'ai_en.id')
                ->where('ai_en.culture', '=', 'en');
        })
        ->leftJoin('slug', 'r.id', '=', 'slug.object_id')
        ->where('r.id', $repositoryId)
        ->select([
            'r.id',
            'slug.slug',
            DB::raw('COALESCE(ai.authorized_form_of_name, ai_en.authorized_form_of_name) as name'),
        ])
        ->first();
}

/**
 * Build URL for resource
 */
function cco_build_url($resource, string $module, string $action = 'index'): string
{
    $slug = is_object($resource) ? ($resource->slug ?? null) : null;
    if ($slug) {
        return url_for(['module' => $module, 'action' => $action, 'slug' => $slug]);
    }
    $id = is_object($resource) ? ($resource->id ?? null) : $resource;
    return url_for(['module' => $module, 'action' => $action, 'id' => $id]);
}

/**
 * Render repository with link
 */
function cco_render_repository($label, $resource): string
{
    $repository = cco_get_repository($resource);
    if (!$repository) {
        return '';
    }
    
    $url = cco_build_url($repository, 'repository');
    $name = htmlspecialchars($repository->name ?? '');
    
    return render_show($label, '<a href="' . $url . '">' . $name . '</a>');
}

/**
 * Get resource title with fallback
 */
function cco_get_title($resource): string
{
    if (is_object($resource)) {
        return $resource->title ?? $resource->slug ?? $resource->identifier ?? '';
    }
    return '';
}

// Initialize database if needed
if (\AtomExtensions\Database\DatabaseBootstrap::getCapsule() === null) {
    \AtomExtensions\Database\DatabaseBootstrap::initializeFromAtom();
}

$culture = $sf_user->getCulture();
$userId = sfContext::getInstance()->getUser()->getAttribute('user_id');

// Get i18n data for resource
$resourceI18n = cco_get_resource_i18n($resource->id);

// Get digital objects
$digitalObjects = cco_get_digital_objects($resource->id);

// Get dates
$dates = cco_get_dates($resource->id);

// Get ancestors for breadcrumb
$ancestors = cco_get_ancestors($resource);
?>

<?php decorate_with('layout_3col'); ?>

<?php slot('sidebar'); ?>
  <?php include_component('informationobject', 'contextMenu'); ?>
<?php end_slot(); ?>

<?php slot('title'); ?>
  <?php echo get_component('informationobject', 'descriptionHeader', ['resource' => $resource, 'title' => cco_get_title($resource)]); ?>
<?php include_partial('ahgSecurityClearance/securityBadge', ['resource' => $resource]); ?>  
  <?php if (ROOT_INFORMATION_OBJECT_ID != $resource->parent_id) { ?>
    <?php echo include_partial('default/breadcrumb', ['resource' => $resource, 'objects' => $ancestors]); ?>
  <?php } ?>
  
  <?php echo get_component('default', 'translationLinks', ['resource' => $resource]); ?>
<?php end_slot(); ?>

<?php slot('context-menu'); ?>
  <?php echo get_partial('informationobject/actionIcons', ['resource' => $resource]); ?>
  <?php echo get_partial('object/subjectAccessPoints', ['resource' => $resource, 'sidebar' => true]); ?>
  <?php echo get_partial('informationobject/nameAccessPoints', ['resource' => $resource, 'sidebar' => true]); ?>
  <?php echo get_partial('object/placeAccessPoints', ['resource' => $resource, 'sidebar' => true]); ?>
<?php end_slot(); ?>

<?php slot('before-content'); ?>

    <?php use_helper('informationobject', 'DigitalObjectViewer'); ?>

    <?php
    $multiDigitalSetting = DB::table('setting')
        ->join('setting_i18n', 'setting.id', '=', 'setting_i18n.id')
        ->where('setting.name', 'multi_digital_linked_display')
        ->where('setting_i18n.culture', $culture)
        ->value('setting_i18n.value');
        
    if (count($digitalObjects) > 0) { ?>
        <?php foreach ($digitalObjects as $obj) { ?>
            <?php echo render_digital_object_viewer($resource, $obj); ?>
        <?php } ?>
    <?php } ?>
    <!-- User Actions (compact with tooltips) -->
    <?php
    $favoriteId = null;
    $cartId = null;
    if ($userId) {
        $favoriteId = DB::table('favorites')->where('user_id', $userId)->where('archival_description_id', $resource->id)->value('id');
        $cartId = DB::table('cart')->where('user_id', $userId)->where('archival_description_id', $resource->id)->value('id');
    }
    $hasDigitalObject = DB::table('digital_object')->where('object_id', $resource->id)->exists();
    ?>
    <div class="d-flex flex-wrap gap-1 mb-3">
      <?php if (in_array('ahgFavoritesPlugin', sfProjectConfiguration::getActive()->getPlugins()) && $userId): ?>
        <?php if ($favoriteId): ?>
          <a href="<?php echo url_for(['module' => 'ahgFavorites', 'action' => 'remove', 'id' => $favoriteId]); ?>" class="btn btn-xs btn-outline-danger" title="<?php echo __('Remove from Favorites'); ?>" data-bs-toggle="tooltip"><i class="fas fa-heart-broken"></i></a>
        <?php else: ?>
          <a href="<?php echo url_for(['module' => 'ahgFavorites', 'action' => 'add', 'slug' => $resource->slug]); ?>" class="btn btn-xs btn-outline-danger" title="<?php echo __('Add to Favorites'); ?>" data-bs-toggle="tooltip"><i class="fas fa-heart"></i></a>
        <?php endif; ?>
      <?php endif; ?>
      <?php if (in_array('ahgFeedbackPlugin', sfProjectConfiguration::getActive()->getPlugins())): ?>
        <a href="<?php echo url_for(['module' => 'ahgFeedback', 'action' => 'submit', 'slug' => $resource->slug]); ?>" class="btn btn-xs btn-outline-secondary" title="<?php echo __('Item Feedback'); ?>" data-bs-toggle="tooltip"><i class="fas fa-comment"></i></a>
      <?php endif; ?>
      <?php if (in_array('ahgRequestToPublishPlugin', sfProjectConfiguration::getActive()->getPlugins()) && $hasDigitalObject): ?>
        <a href="<?php echo url_for(['module' => 'requestToPublish', 'action' => 'submit', 'slug' => $resource->slug]); ?>" class="btn btn-xs btn-outline-primary" title="<?php echo __('Request to Publish'); ?>" data-bs-toggle="tooltip"><i class="fas fa-paper-plane"></i></a>
      <?php endif; ?>
      <!-- DEBUG: hasDigitalObject=<?php echo $hasDigitalObject ? "true" : "false"; ?>, cartClass=<?php echo in_array("ahgCartPlugin", sfProjectConfiguration::getActive()->getPlugins()) ? "true" : "false"; ?> -->
      <?php if (in_array('ahgCartPlugin', sfProjectConfiguration::getActive()->getPlugins()) && $hasDigitalObject): ?>
        <?php if ($cartId): ?>
          <a href="<?php echo url_for(['module' => 'ahgCart', 'action' => 'browse']); ?>" class="btn btn-xs btn-outline-success" title="<?php echo __('Go to Cart'); ?>" data-bs-toggle="tooltip"><i class="fas fa-shopping-cart"></i></a>
        <?php else: ?>
          <a href="<?php echo url_for(['module' => 'ahgCart', 'action' => 'add', 'slug' => $resource->slug]); ?>" class="btn btn-xs btn-outline-success" title="<?php echo __('Add to Cart'); ?>" data-bs-toggle="tooltip"><i class="fas fa-cart-plus"></i></a>
        <?php endif; ?>
      <?php endif; ?>
    </div>

    <!-- Standard Identity Area -->
    <section id="identityArea">
    <div class="field">
      <h3><?php echo __('Identity area'); ?></h3>
  
      <?php echo render_show(__('Reference code'), $resource->identifier ?? ''); ?>
      <?php echo render_show(__('Title'), esc_entities($resourceI18n->title ?? $resource->slug ?? '')); ?>
      
      <?php if (count($dates) > 0) { ?>
        <div class="field">
          <h3><?php echo __('Date(s)'); ?></h3>
          <div>
            <ul>
              <?php foreach ($dates as $item) { ?>
                <li>
                  <?php echo render_value_inline(cco_render_date_range($item->date_display, $item->start_date, $item->end_date)); ?> 
                  (<?php echo htmlspecialchars($item->type_name ?? ''); ?>)
                </li>
              <?php } ?>
            </ul>
          </div>
        </div>
      <?php } ?>
          
      <?php echo render_show(__('Level of description'), cco_get_term_name($resource->level_of_description_id ?? null)); ?>
      <?php echo render_show(__('Extent and medium'), render_value($resourceI18n->extent_and_medium ?? '')); ?>
      </div>
    </section>

    <!-- Context Area -->
    <section id="contextArea">
        <div class="field">
          <h3><?php echo __('Context area'); ?></h3>
          
          <div class="creatorHistories">
            <?php echo get_component('informationobject', 'creatorDetail', [
                'resource' => $resource,
                'creatorHistoryLabels' => $creatorHistoryLabels ?? [], ]); ?>
          </div>
          
          <?php echo cco_render_repository(__('Repository'), $resource); ?>
          <?php echo render_show(__('Archival history'), render_value($resourceI18n->archival_history ?? '')); ?>
          <?php echo render_show(__('Immediate source of acquisition'), render_value($resourceI18n->acquisition ?? '')); ?>
        </div>
    </section>

    <!-- Museum Object Information -->
    <?php if (isset($museumData) && !empty($museumData)) { ?>
    <section id="museumObjectArea">
        <div class="field">
          <h3><?php echo __('Museum object information Area'); ?></h3>
      
          <?php if (!empty($museumData['work_type'])) { ?>
            <?php echo render_show(__('Work type'), isset($museumData['work_type_label']) ? $museumData['work_type_label'] : $museumData['work_type']); ?>
          <?php } ?>
          
          <?php if (!empty($museumData['object_type'])) { ?>
            <?php echo render_show(__('Object type'), $museumData['object_type']); ?>
          <?php } ?>
          
          <?php if (!empty($museumData['classification'])) { ?>
            <?php echo render_show(__('Classification'), $museumData['classification']); ?>
          <?php } ?>
          
          <?php if (!empty($museumData['creation_date_earliest']) || !empty($museumData['creation_date_latest'])) { ?>
            <?php
              $dateRange = '';
              if (!empty($museumData['creation_date_earliest']) && !empty($museumData['creation_date_latest'])) {
                $dateRange = $museumData['creation_date_earliest'].' - '.$museumData['creation_date_latest'];
              } elseif (!empty($museumData['creation_date_earliest'])) {
                $dateRange = 'After '.$museumData['creation_date_earliest'];
              } elseif (!empty($museumData['creation_date_latest'])) {
                $dateRange = 'Before '.$museumData['creation_date_latest'];
              }
              echo render_show(__('Creation date'), $dateRange);
            ?>
          <?php } ?>

          <!-- Materials -->
          <?php if (!empty($museumData['materials'])) { ?>
            <?php
              $materials = json_decode($museumData['materials'], true);
              if (is_array($materials)) {
                $materialLabels = [
                    'oil_paint' => 'Oil paint',
                    'canvas' => 'Canvas',
                    'paper' => 'Paper',
                    'wood' => 'Wood',
                    'metal' => 'Metal',
                    'stone' => 'Stone',
                    'textile' => 'Textile',
                    'ceramic' => 'Ceramic',
                    'glass' => 'Glass',
                    'plastic' => 'Plastic',
                ];
                $display = [];
                foreach ($materials as $material) {
                  $display[] = isset($materialLabels[$material]) ? $materialLabels[$material] : $material;
                }
                echo render_show(__('Materials'), implode(', ', $display));
              }
            ?>
          <?php } ?>

          <!-- Techniques -->
          <?php if (!empty($museumData['techniques'])) { ?>
            <?php
              $techniques = json_decode($museumData['techniques'], true);
              if (is_array($techniques)) {
                $techniqueLabels = [
                    'painted' => 'Painted',
                    'glazed' => 'Glazed',
                    'carved' => 'Carved',
                    'etched' => 'Etched',
                    'printed' => 'Printed',
                    'woven' => 'Woven',
                    'cast' => 'Cast',
                    'molded' => 'Molded',
                    'assembled' => 'Assembled',
                    'fired' => 'Fired',
                ];
                $display = [];
                foreach ($techniques as $technique) {
                  $display[] = isset($techniqueLabels[$technique]) ? $techniqueLabels[$technique] : $technique;
                }
                echo render_show(__('Techniques'), implode(', ', $display));
              }
            ?>
          <?php } ?>
          
          <?php if (!empty($museumData['measurements'])) { ?>
            <?php echo render_show(__('Measurements'), render_value($museumData['measurements'])); ?>
          <?php } ?>
          
          <?php if (!empty($museumData['dimensions'])) { ?>
            <?php echo render_show(__('Dimensions'), $museumData['dimensions']); ?>
          <?php } ?>
          
          <?php if (!empty($museumData['inscription'])) { ?>
            <?php echo render_show(__('Inscriptions and marks'), render_value($museumData['inscription'])); ?>
          <?php } ?>
          
          <?php if (!empty($museumData['condition_notes'])) { ?>
            <?php echo render_show(__('Condition notes'), render_value($museumData['condition_notes'])); ?>
          <?php } ?>
          
          <?php if (!empty($museumData['provenance'])) { ?>
            <?php echo render_show(__('Provenance'), render_value($museumData['provenance'])); ?>
          <?php } ?>
          
          <?php if (!empty($museumData['cultural_context'])) { ?>
            <?php echo render_show(__('Cultural context'), $museumData['cultural_context']); ?>
          <?php } ?>
          
          <?php if (!empty($museumData['style_period'])) { ?>
            <?php echo render_show(__('Style or period'), $museumData['style_period']); ?>
          <?php } ?>
          
          <?php if (!empty($museumData['current_location'])) { ?>
            <?php echo render_show(__('Current location'), render_value($museumData['current_location'])); ?>
          <?php } ?>
        </div>
    </section>      
      <?php if (!empty($museumData['creator_identity']) || !empty($museumData['creator_role']) || !empty($museumData['creator_attribution'])) { ?>
        <section id="ccoCreatorArea">
        <div class="field">
          <h3><?php echo __('Creator information Area'); ?></h3>
              <?php if (!empty($museumData['creator_identity'])) { ?>
                <?php echo render_show(__('Creator/Maker'), $museumData['creator_identity']); ?>
              <?php } ?>
              <?php if (!empty($museumData['creator_role'])) { ?>
                <?php echo render_show(__('Role'), isset($museumData['creator_role_label']) ? $museumData['creator_role_label'] : $museumData['creator_role']); ?>
              <?php } ?>
              <?php if (!empty($museumData['creator_extent'])) { ?>
                <?php echo render_show(__('Extent'), $museumData['creator_extent']); ?>
              <?php } ?>
              <?php if (!empty($museumData['creator_qualifier'])) { ?>
                <?php echo render_show(__('Qualifier'), isset($museumData['creator_qualifier_label']) ? $museumData['creator_qualifier_label'] : $museumData['creator_qualifier']); ?>
              <?php } ?>
              <?php if (!empty($museumData['creator_attribution'])) { ?>
                <?php echo render_show(__('Attribution'), render_value($museumData['creator_attribution'])); ?>
              <?php } ?>
        </div>
    </section>      
      <?php } ?>

        <?php if (!empty($museumData['creation_date_display']) || !empty($museumData['creation_date_qualifier'])) { ?>
            <section id="ccoCreationDateArea">
        <div class="field">
          <h3><?php echo __('Creation date Area'); ?></h3>
              <?php if (!empty($museumData['creation_date_display'])) { ?>
                <?php echo render_show(__('Display date'), $museumData['creation_date_display']); ?>
              <?php } ?>
              <?php if (!empty($museumData['creation_date_qualifier'])) { ?>
                <?php echo render_show(__('Date qualifier'), isset($museumData['creation_date_qualifier_label']) ? $museumData['creation_date_qualifier_label'] : $museumData['creation_date_qualifier']); ?>
              <?php } ?>
        </div>
    </section>      
        <?php } ?>

        <?php if (!empty($museumData['style']) || !empty($museumData['period']) || !empty($museumData['cultural_group']) || !empty($museumData['movement']) || !empty($museumData['school']) || !empty($museumData['dynasty'])) { ?>
        <section id="ccoStylePeriodArea">
        <div class="field">
          <h3><?php echo __('Styles, periods, groups, movements Area'); ?></h3>
              <?php if (!empty($museumData['style'])) { ?>
                <?php echo render_show(__('Style'), $museumData['style']); ?>
              <?php } ?>
              <?php if (!empty($museumData['period'])) { ?>
                <?php echo render_show(__('Period'), $museumData['period']); ?>
              <?php } ?>
              <?php if (!empty($museumData['cultural_group'])) { ?>
                <?php echo render_show(__('Culture/Group'), $museumData['cultural_group']); ?>
              <?php } ?>
              <?php if (!empty($museumData['movement'])) { ?>
                <?php echo render_show(__('Movement'), $museumData['movement']); ?>
              <?php } ?>
              <?php if (!empty($museumData['school'])) { ?>
                <?php echo render_show(__('School'), $museumData['school']); ?>
              <?php } ?>
              <?php if (!empty($museumData['dynasty'])) { ?>
                <?php echo render_show(__('Dynasty'), $museumData['dynasty']); ?>
              <?php } ?>
        </div>
    </section>    
        <?php } ?>
        
        <?php if (!empty($museumData['subject_display'])) { ?>
            <section id="ccoSubjectArea">
        <div class="field">
          <h3><?php echo __('Subject matter Area'); ?></h3>
              <?php if (!empty($museumData['subject_indexing_type'])) { ?>
                <?php echo render_show(__('Indexing type'), isset($museumData['subject_indexing_type_label']) ? $museumData['subject_indexing_type_label'] : $museumData['subject_indexing_type']); ?>
              <?php } ?>
              <?php echo render_show(__('Subject'), render_value($museumData['subject_display'])); ?>
              <?php if (!empty($museumData['subject_extent'])) { ?>
                <?php echo render_show(__('Extent'), $museumData['subject_extent']); ?>
              <?php } ?>
        </div>
    </section>    
        <?php } ?>

        <?php if (!empty($museumData['historical_context']) || !empty($museumData['architectural_context']) || !empty($museumData['archaeological_context'])) { ?>
            <section id="ccoContextArea">
        <div class="field">
          <h3><?php echo __('Context Area'); ?></h3>
              <?php if (!empty($museumData['historical_context'])) { ?>
                <?php echo render_show(__('Historical'), render_value($museumData['historical_context'])); ?>
              <?php } ?>
              <?php if (!empty($museumData['architectural_context'])) { ?>
                <?php echo render_show(__('Architectural'), $museumData['architectural_context']); ?>
              <?php } ?>
              <?php if (!empty($museumData['archaeological_context'])) { ?>
                <?php echo render_show(__('Archaeological'), $museumData['archaeological_context']); ?>
              <?php } ?>
        </div>
    </section>    
        <?php } ?>
                
        <?php if (!empty($museumData['object_class']) || !empty($museumData['object_category']) || !empty($museumData['object_sub_category'])) { ?>
            <section id="ccoClassArea">
        <div class="field">
          <h3><?php echo __('Classification Area'); ?></h3>
              <?php if (!empty($museumData['object_class'])) { ?>
                <?php echo render_show(__('Class'), $museumData['object_class']); ?>
              <?php } ?>
              <?php if (!empty($museumData['object_category'])) { ?>
                <?php echo render_show(__('Category'), $museumData['object_category']); ?>
              <?php } ?>
              <?php if (!empty($museumData['object_sub_category'])) { ?>
                <?php echo render_show(__('Sub-category'), $museumData['object_sub_category']); ?>
              <?php } ?>
        </div>
    </section>    
        <?php } ?>

        <?php if (!empty($museumData['edition_number']) || !empty($museumData['edition_description']) || !empty($museumData['state_description'])) { ?>
            <section id="ccoEditionArea">
        <div class="field">
          <h3><?php echo __('Edition/State Area'); ?></h3>
              <?php if (!empty($museumData['edition_number'])) { ?>
                <?php
                  $edition = $museumData['edition_number'];
                  if (!empty($museumData['edition_size'])) {
                      $edition .= ' of '.$museumData['edition_size'];
                  }
                  echo render_show(__('Edition'), $edition);
                ?>
              <?php } ?>
              <?php if (!empty($museumData['edition_description'])) { ?>
                <?php echo render_show(__('Description'), render_value($museumData['edition_description'])); ?>
              <?php } ?>
              <?php if (!empty($museumData['state_identification'])) { ?>
                <?php echo render_show(__('State'), $museumData['state_identification']); ?>
              <?php } ?>
              <?php if (!empty($museumData['state_description'])) { ?>
                <?php echo render_show(__('State description'), $museumData['state_description']); ?>
              <?php } ?>
        </div>
    </section>    
        <?php } ?>        

        <?php if (!empty($museumData['facture_description']) || !empty($museumData['technique_cco'])) { ?>
            <section id="ccoTechniqueArea">
        <div class="field">
          <h3><?php echo __('Facture/Technique Area'); ?></h3>
              <?php if (!empty($museumData['facture_description'])) { ?>
                <?php echo render_show(__('Facture'), render_value($museumData['facture_description'])); ?>
              <?php } ?>
              <?php if (!empty($museumData['technique_cco'])) { ?>
                <?php echo render_show(__('Technique'), $museumData['technique_cco']); ?>
              <?php } ?>
              <?php if (!empty($museumData['technique_qualifier'])) { ?>
                <?php echo render_show(__('Qualifier'), $museumData['technique_qualifier']); ?>
              <?php } ?>
        </div>
    </section>    
        <?php } ?>

        <?php if (!empty($museumData['physical_appearance']) || !empty($museumData['color']) || !empty($museumData['shape'])) { ?>
            <section id="ccoPhysicalArea">
        <div class="field">
          <h3><?php echo __('Physical description Area'); ?></h3>
              <?php if (!empty($museumData['physical_appearance'])) { ?>
                <?php echo render_show(__('Appearance'), render_value($museumData['physical_appearance'])); ?>
              <?php } ?>
              <?php if (!empty($museumData['color'])) { ?>
                <?php echo render_show(__('Color'), $museumData['color']); ?>
              <?php } ?>
              <?php if (!empty($museumData['shape'])) { ?>
                <?php echo render_show(__('Shape'), $museumData['shape']); ?>
              <?php } ?>
              <?php if (!empty($museumData['orientation'])) { ?>
                <?php echo render_show(__('Orientation'), $museumData['orientation']); ?>
              <?php } ?>
        </div>
    </section>    
        <?php } ?>

        <?php if (!empty($museumData['condition_term']) || !empty($museumData['condition_description'])) { ?>
            <section id="ccoConditionArea">
        <div class="field">
          <h3><?php echo __('Condition Area'); ?></h3>
              <?php if (!empty($museumData['condition_term'])) { ?>
                <?php echo render_show(__('Condition'), isset($museumData['condition_term_label']) ? $museumData['condition_term_label'] : $museumData['condition_term']); ?>
              <?php } ?>
              <?php if (!empty($museumData['condition_date'])) { ?>
                <?php echo render_show(__('Date examined'), $museumData['condition_date']); ?>
              <?php } ?>
              <?php if (!empty($museumData['condition_description'])) { ?>
                <?php echo render_show(__('Description'), render_value($museumData['condition_description'])); ?>
              <?php } ?>
              <?php if (!empty($museumData['condition_agent'])) { ?>
                <?php echo render_show(__('Examiner'), $museumData['condition_agent']); ?>
              <?php } ?>
        </div>
    </section>    
        <?php } ?>

        <?php if (!empty($museumData['treatment_type']) || !empty($museumData['treatment_description'])) { ?>
            <section id="ccoConservationArea">
        <div class="field">
          <h3><?php echo __('Conservation/Treatment Area'); ?></h3>
              <?php if (!empty($museumData['treatment_type'])) { ?>
                <?php echo render_show(__('Treatment type'), $museumData['treatment_type']); ?>
              <?php } ?>
              <?php if (!empty($museumData['treatment_date'])) { ?>
                <?php echo render_show(__('Date'), $museumData['treatment_date']); ?>
              <?php } ?>
              <?php if (!empty($museumData['treatment_agent'])) { ?>
                <?php echo render_show(__('Conservator'), $museumData['treatment_agent']); ?>
              <?php } ?>
              <?php if (!empty($museumData['treatment_description'])) { ?>
                <?php echo render_show(__('Description'), render_value($museumData['treatment_description'])); ?>
              <?php } ?>
        </div>
    </section>    
        <?php } ?>

        <?php if (!empty($museumData['inscription_transcription']) || !empty($museumData['mark_description'])) { ?>
            <section id="ccoInscriptionArea">
        <div class="field">
          <h3><?php echo __('Inscriptions/Marks Area'); ?></h3>
              <?php if (!empty($museumData['inscription_type'])) { ?>
                <?php echo render_show(__('Type'), isset($museumData['inscription_type_label']) ? $museumData['inscription_type_label'] : $museumData['inscription_type']); ?>
              <?php } ?>
              <?php if (!empty($museumData['inscription_transcription'])) { ?>
                <?php echo render_show(__('Transcription'), render_value($museumData['inscription_transcription'])); ?>
              <?php } ?>
              <?php if (!empty($museumData['inscription_location'])) { ?>
                <?php echo render_show(__('Location'), $museumData['inscription_location']); ?>
              <?php } ?>
              <?php if (!empty($museumData['inscription_language'])) { ?>
                <?php echo render_show(__('Language'), $museumData['inscription_language']); ?>
              <?php } ?>
              <?php if (!empty($museumData['inscription_translation'])) { ?>
                <?php echo render_show(__('Translation'), render_value($museumData['inscription_translation'])); ?>
              <?php } ?>
              <?php if (!empty($museumData['mark_type'])) { ?>
                <?php echo render_show(__('Mark type'), $museumData['mark_type']); ?>
              <?php } ?>
              <?php if (!empty($museumData['mark_description'])) { ?>
                <?php echo render_show(__('Mark description'), render_value($museumData['mark_description'])); ?>
              <?php } ?>
              <?php if (!empty($museumData['mark_location'])) { ?>
                <?php echo render_show(__('Mark location'), $museumData['mark_location']); ?>
              <?php } ?>
        </div>
    </section>    
        <?php } ?>

        <?php if (!empty($museumData['related_work_label'])) { ?>
            <section id="ccoRelatedWorksArea">
        <div class="field">
          <h3><?php echo __('Related works Area'); ?></h3>
              <?php if (!empty($museumData['related_work_type'])) { ?>
                <?php echo render_show(__('Relationship type'), isset($museumData['related_work_type_label']) ? $museumData['related_work_type_label'] : $museumData['related_work_type']); ?>
              <?php } ?>
              <?php if (!empty($museumData['related_work_relationship'])) { ?>
                <?php echo render_show(__('Relationship'), $museumData['related_work_relationship']); ?>
              <?php } ?>
              <?php echo render_show(__('Related work'), $museumData['related_work_label']); ?>
              <?php if (!empty($museumData['related_work_id'])) { ?>
                <?php echo render_show(__('Identifier'), $museumData['related_work_id']); ?>
              <?php } ?>
        </div>
    </section>    
        <?php } ?>

        <?php if (!empty($museumData['current_location_repository']) || !empty($museumData['creation_place']) || !empty($museumData['discovery_place'])) { ?>
            <section id="ccoLocationArea">
        <div class="field">
          <h3><?php echo __('Location Area'); ?></h3>
              <?php if (!empty($museumData['current_location_repository'])) { ?>
                <?php echo render_show(__('Current repository'), $museumData['current_location_repository']); ?>
              <?php } ?>
              <?php if (!empty($museumData['current_location_geography'])) { ?>
                <?php echo render_show(__('Geography'), $museumData['current_location_geography']); ?>
              <?php } ?>
              <?php if (!empty($museumData['current_location_coordinates'])) { ?>
                <?php echo render_show(__('Coordinates'), $museumData['current_location_coordinates']); ?>
              <?php } ?>
              <?php if (!empty($museumData['current_location_ref_number'])) { ?>
                <?php echo render_show(__('Reference number'), $museumData['current_location_ref_number']); ?>
              <?php } ?>
              <?php if (!empty($museumData['creation_place'])) { ?>
                <?php echo render_show(__('Place of creation'), $museumData['creation_place']); ?>
              <?php } ?>
              <?php if (!empty($museumData['discovery_place'])) { ?>
                <?php echo render_show(__('Place of discovery'), $museumData['discovery_place']); ?>
              <?php } ?>
        </div>
    </section>    
        <?php } ?>

        <?php if (!empty($museumData['provenance_text']) || !empty($museumData['ownership_history'])) { ?>
            <section id="ccoProvenanceArea">
        <div class="field">
          <h3><?php echo __('Provenance/Ownership Area'); ?></h3>
              <?php if (!empty($museumData['provenance_text'])) { ?>
                <?php echo render_show(__('Provenance'), render_value($museumData['provenance_text'])); ?>
              <?php } ?>
              <?php if (!empty($museumData['ownership_history'])) { ?>
                <?php echo render_show(__('Ownership history'), render_value($museumData['ownership_history'])); ?>
              <?php } ?>
              <?php if (!empty($museumData['legal_status'])) { ?>
                <?php echo render_show(__('Legal status'), $museumData['legal_status']); ?>
              <?php } ?>
        </div>
    </section>    
        <?php } ?>

        <?php if (!empty($museumData['rights_type']) || !empty($museumData['rights_holder'])) { ?>
            <section id="ccoRightsArea">
        <div class="field">
          <h3><?php echo __('Rights Area'); ?></h3>
              <?php if (!empty($museumData['rights_type'])) { ?>
                <?php echo render_show(__('Rights type'), isset($museumData['rights_type_label']) ? $museumData['rights_type_label'] : $museumData['rights_type']); ?>
              <?php } ?>
              <?php if (!empty($museumData['rights_holder'])) { ?>
                <?php echo render_show(__('Rights holder'), $museumData['rights_holder']); ?>
              <?php } ?>
              <?php if (!empty($museumData['rights_date'])) { ?>
                <?php echo render_show(__('Date'), $museumData['rights_date']); ?>
              <?php } ?>
              <?php if (!empty($museumData['rights_remarks'])) { ?>
                <?php echo render_show(__('Remarks'), render_value($museumData['rights_remarks'])); ?>
              <?php } ?>
        </div>
    </section>    
        <?php } ?>

        <?php if (!empty($museumData['cataloger_name']) || !empty($museumData['cataloging_institution'])) { ?>
            <section id="ccoCatalogingArea">
        <div class="field">
          <h3><?php echo __('Cataloging information Area'); ?></h3>
              <?php if (!empty($museumData['cataloger_name'])) { ?>
                <?php echo render_show(__('Cataloger'), $museumData['cataloger_name']); ?>
              <?php } ?>
              <?php if (!empty($museumData['cataloging_date'])) { ?>
                <?php echo render_show(__('Date cataloged'), $museumData['cataloging_date']); ?>
              <?php } ?>
              <?php if (!empty($museumData['cataloging_institution'])) { ?>
                <?php echo render_show(__('Institution'), $museumData['cataloging_institution']); ?>
              <?php } ?>
              <?php if (!empty($museumData['cataloging_remarks'])) { ?>
                <?php echo render_show(__('Remarks'), render_value($museumData['cataloging_remarks'])); ?>
              <?php } ?>
              <?php if (!empty($museumData['record_type'])) { ?>
                <?php echo render_show(__('Record type'), $museumData['record_type']); ?>
              <?php } ?>
              <?php if (!empty($museumData['record_level'])) { ?>
                <?php echo render_show(__('Record level'), $museumData['record_level']); ?>
              <?php } ?>
        </div>
    </section>    
        <?php } ?>

        <!-- GRAP Financial Compliance Section -->
        <?php if (isset($grapData) && !empty($grapData)) { ?>
        <section id="grapAccountingArea">
        <div class="field">
          <h3><?php echo __('GRAP Financial Compliance Area'); ?></h3>
          
          <!-- Recognition & Measurement -->
          <?php if (!empty($grapData['recognition_status']) || !empty($grapData['measurement_basis']) || !empty($grapData['asset_class'])) { ?>
            <h4><?php echo __('Recognition & Measurement'); ?></h4>
            
            <?php if (!empty($grapData['recognition_status'])) { ?>
              <?php 
                $statusLabels = [
                  'recognised' => 'Recognised',
                  'not_recognised' => 'Not Recognised'
                ];
                $statusLabel = isset($statusLabels[$grapData['recognition_status']]) ? $statusLabels[$grapData['recognition_status']] : $grapData['recognition_status'];
              ?>
              <?php echo render_show(__('Recognition status'), $statusLabel); ?>
            <?php } ?>
            
            <?php if (!empty($grapData['recognition_status_reason'])) { ?>
              <?php echo render_show(__('Reason (if not recognised)'), render_value($grapData['recognition_status_reason'])); ?>
            <?php } ?>
            
            <?php if (!empty($grapData['measurement_basis'])) { ?>
              <?php 
                $basisLabels = [
                  'cost_model' => 'Cost Model',
                  'revaluation_model' => 'Revaluation Model'
                ];
                $basisLabel = isset($basisLabels[$grapData['measurement_basis']]) ? $basisLabels[$grapData['measurement_basis']] : $grapData['measurement_basis'];
              ?>
              <?php echo render_show(__('Measurement basis'), $basisLabel); ?>
            <?php } ?>
            
            <?php if (!empty($grapData['initial_recognition_date'])) { ?>
              <?php echo render_show(__('Initial recognition date'), $grapData['initial_recognition_date']); ?>
            <?php } ?>
            
            <?php if (!empty($grapData['initial_recognition_value'])) { ?>
              <?php echo render_show(__('Initial recognition value'), 'R ' . number_format($grapData['initial_recognition_value'], 2)); ?>
            <?php } ?>
            
            <?php if (!empty($grapData['asset_class'])) { ?>
              <?php 
                $classLabels = [
                  'heritage_asset' => 'Heritage Asset',
                  'operational_asset' => 'Operational Asset',
                  'investment' => 'Investment'
                ];
                $classLabel = isset($classLabels[$grapData['asset_class']]) ? $classLabels[$grapData['asset_class']] : $grapData['asset_class'];
              ?>
              <?php echo render_show(__('Asset class'), $classLabel); ?>
            <?php } ?>
          <?php } ?>
          
          <!-- Acquisition -->
          <?php if (!empty($grapData['acquisition_method_grap']) || !empty($grapData['cost_of_acquisition']) || !empty($grapData['fair_value_at_acquisition'])) { ?>
            <h4><?php echo __('Acquisition'); ?></h4>
            
            <?php if (!empty($grapData['acquisition_method_grap'])) { ?>
              <?php 
                $methodLabels = [
                  'purchase' => 'Purchase',
                  'donation' => 'Donation',
                  'transfer' => 'Transfer',
                  'exchange' => 'Exchange',
                  'other' => 'Other'
                ];
                $methodLabel = isset($methodLabels[$grapData['acquisition_method_grap']]) ? $methodLabels[$grapData['acquisition_method_grap']] : $grapData['acquisition_method_grap'];
              ?>
              <?php echo render_show(__('Acquisition method'), $methodLabel); ?>
            <?php } ?>
            
            <?php if (!empty($grapData['cost_of_acquisition'])) { ?>
              <?php echo render_show(__('Cost of acquisition'), 'R ' . number_format($grapData['cost_of_acquisition'], 2)); ?>
            <?php } ?>
            
            <?php if (!empty($grapData['fair_value_at_acquisition'])) { ?>
              <?php echo render_show(__('Fair value at acquisition'), 'R ' . number_format($grapData['fair_value_at_acquisition'], 2)); ?>
            <?php } ?>
          <?php } ?>
          
          <!-- Financial Classification -->
          <?php if (!empty($grapData['gl_account_code']) || !empty($grapData['cost_center']) || !empty($grapData['fund_source'])) { ?>
            <h4><?php echo __('Financial Classification'); ?></h4>
            
            <?php if (!empty($grapData['gl_account_code'])) { ?>
              <?php echo render_show(__('GL account code'), $grapData['gl_account_code']); ?>
            <?php } ?>
            
            <?php if (!empty($grapData['cost_center'])) { ?>
              <?php echo render_show(__('Cost center'), $grapData['cost_center']); ?>
            <?php } ?>
            
            <?php if (!empty($grapData['fund_source'])) { ?>
              <?php echo render_show(__('Fund source'), $grapData['fund_source']); ?>
            <?php } ?>
          <?php } ?>
          
          <!-- Depreciation -->
          <?php if (!empty($grapData['depreciation_policy']) || !empty($grapData['depreciation_method'])) { ?>
            <h4><?php echo __('Depreciation'); ?></h4>
            
            <?php if (!empty($grapData['depreciation_policy'])) { ?>
              <?php 
                $policyLabels = [
                  'not_depreciated' => 'Not Depreciated',
                  'depreciated' => 'Depreciated'
                ];
                $policyLabel = isset($policyLabels[$grapData['depreciation_policy']]) ? $policyLabels[$grapData['depreciation_policy']] : $grapData['depreciation_policy'];
              ?>
              <?php echo render_show(__('Depreciation policy'), $policyLabel); ?>
            <?php } ?>
            
            <?php if (!empty($grapData['depreciation_method'])) { ?>
              <?php 
                $depMethodLabels = [
                  'straight_line' => 'Straight Line',
                  'reducing_balance' => 'Reducing Balance'
                ];
                $depMethodLabel = isset($depMethodLabels[$grapData['depreciation_method']]) ? $depMethodLabels[$grapData['depreciation_method']] : $grapData['depreciation_method'];
              ?>
              <?php echo render_show(__('Depreciation method'), $depMethodLabel); ?>
            <?php } ?>
            
            <?php if (!empty($grapData['useful_life_years'])) { ?>
              <?php echo render_show(__('Useful life'), $grapData['useful_life_years'] . ' years'); ?>
            <?php } ?>
            
            <?php if (!empty($grapData['residual_value'])) { ?>
              <?php echo render_show(__('Residual value'), 'R ' . number_format($grapData['residual_value'], 2)); ?>
            <?php } ?>
            
            <?php if (!empty($grapData['accumulated_depreciation'])) { ?>
              <?php echo render_show(__('Accumulated depreciation'), 'R ' . number_format($grapData['accumulated_depreciation'], 2)); ?>
            <?php } ?>
          <?php } ?>
          
          <!-- Revaluation -->
          <?php if (!empty($grapData['last_revaluation_date']) || !empty($grapData['revaluation_amount'])) { ?>
            <h4><?php echo __('Revaluation'); ?></h4>
            
            <?php if (!empty($grapData['last_revaluation_date'])) { ?>
              <?php echo render_show(__('Last revaluation date'), $grapData['last_revaluation_date']); ?>
            <?php } ?>
            
            <?php if (!empty($grapData['revaluation_amount'])) { ?>
              <?php echo render_show(__('Revaluation amount'), 'R ' . number_format($grapData['revaluation_amount'], 2)); ?>
            <?php } ?>
            
            <?php if (!empty($grapData['valuation_method'])) { ?>
              <?php 
                $valMethodLabels = [
                  'market_approach' => 'Market Approach',
                  'cost_approach' => 'Cost Approach',
                  'income_approach' => 'Income Approach'
                ];
                $valMethodLabel = isset($valMethodLabels[$grapData['valuation_method']]) ? $valMethodLabels[$grapData['valuation_method']] : $grapData['valuation_method'];
              ?>
              <?php echo render_show(__('Valuation method'), $valMethodLabel); ?>
            <?php } ?>
            
            <?php if (!empty($grapData['valuer_credentials'])) { ?>
              <?php echo render_show(__('Valuer credentials'), $grapData['valuer_credentials']); ?>
            <?php } ?>
          <?php } ?>
          
          <!-- GRAP 103 Disclosure -->
          <?php if (!empty($grapData['heritage_significance_rating']) || !empty($grapData['restrictions_use_disposal']) || !empty($grapData['conservation_commitments'])) { ?>
            <h4><?php echo __('GRAP 103 Disclosure'); ?></h4>
            
            <?php if (!empty($grapData['heritage_significance_rating'])) { ?>
              <?php echo render_show(__('Heritage significance rating'), $grapData['heritage_significance_rating']); ?>
            <?php } ?>
            
            <?php if (!empty($grapData['restrictions_use_disposal'])) { ?>
              <?php echo render_show(__('Restrictions on use/disposal'), render_value($grapData['restrictions_use_disposal'])); ?>
            <?php } ?>
            
            <?php if (!empty($grapData['conservation_commitments'])) { ?>
              <?php echo render_show(__('Conservation commitments'), render_value($grapData['conservation_commitments'])); ?>
            <?php } ?>
            
            <?php if (!empty($grapData['insurance_coverage_required'])) { ?>
              <?php echo render_show(__('Insurance coverage required'), 'R ' . number_format($grapData['insurance_coverage_required'], 2)); ?>
            <?php } ?>
            
            <?php if (!empty($grapData['insurance_coverage_actual'])) { ?>
              <?php echo render_show(__('Insurance coverage actual'), 'R ' . number_format($grapData['insurance_coverage_actual'], 2)); ?>
            <?php } ?>
          <?php } ?>
          
        </div>
    </section>    
        <?php } ?>

    </section>
    <?php } ?>


    <!-- Content and Structure Area -->
    <section id="contentAndStructureArea">
      <h2><?php echo __('Content and structure area'); ?></h2>
      
      <?php echo render_show(__('Scope and content'), render_value($resourceI18n->scope_and_content ?? '')); ?>
      <?php echo render_show(__('Appraisal, destruction and scheduling'), render_value($resourceI18n->appraisal ?? '')); ?>
      <?php echo render_show(__('Accruals'), render_value($resourceI18n->accruals ?? '')); ?>
      <?php echo render_show(__('System of arrangement'), render_value($resourceI18n->arrangement ?? '')); ?>
    </section>

    <!-- Conditions of Access and Use Area -->
    <section id="conditionsOfAccessAndUseArea">
      <h2><?php echo __('Conditions of access and use area'); ?></h2>
      
      <?php echo render_show(__('Conditions governing access'), render_value($resourceI18n->access_conditions ?? '')); ?>
      <?php echo render_show(__('Conditions governing reproduction'), render_value($resourceI18n->reproduction_conditions ?? '')); ?>
      <?php echo render_show(__('Physical characteristics'), render_value($resourceI18n->physical_characteristics ?? '')); ?>
      <?php echo render_show(__('Finding aids'), render_value($resourceI18n->finding_aids ?? '')); ?>
    </section>

    <!-- Access Points -->
    <section id="accessPointsArea">
      <h2><?php echo __('Access points'); ?></h2>
      
      <?php echo get_partial('object/subjectAccessPoints', ['resource' => $resource]); ?>
      <?php echo get_partial('object/placeAccessPoints', ['resource' => $resource]); ?>
      <?php echo get_partial('informationobject/nameAccessPoints', ['resource' => $resource]); ?>
    </section>

    <!-- Rights Area -->
    <?php if ($sf_user->isAuthenticated()) { ?>

      <div class="section border-bottom" id="rightsArea">

        <?php echo render_b5_section_heading(__('Rights area')); ?>

        <div class="relatedRights">
          <?php echo get_component('right', 'relatedRights', ['resource' => $resource]); ?>
        </div>

      </div> <!-- /section#rightsArea -->

    <?php } ?>
    
      <?php if (count($digitalObjects) > 0) { ?>
      <div class="digitalObjectMetadata">
        <?php echo get_component('digitalobject', 'metadata', ['resource' => $digitalObjects[0], 'object' => $resource]); ?>
      </div>
      <?php } ?>

    <section id="accessionArea" class="border-bottom">

      <?php echo render_b5_section_heading(__('Accession area')); ?>

      <div class="accessions">
        <?php echo get_component('informationobject', 'accessions', ['resource' => $resource]); ?>
      </div>

    </section> <!-- /section#accessionArea -->

<?php end_slot(); ?>

<?php slot('after-content'); ?>
  <?php echo get_partial('cco/actions', ['resource' => $resource]); ?>
<?php end_slot(); ?>

<?php echo get_component('object', 'gaInstitutionsDimension', ['resource' => $resource]); ?>