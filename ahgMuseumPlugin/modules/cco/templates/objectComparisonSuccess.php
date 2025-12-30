<?php
/**
 * Object Comparison Template
 *
 * @package    ahgMuseumPlugin
 * @subpackage templates
 */

use Illuminate\Database\Capsule\Manager as DB;

/**
 * Check if user can read resource
 */
function comparison_check_read_access(int $objectId): bool
{
    $user = sfContext::getInstance()->getUser();
    
    // Check if object is published (public)
    $object = DB::table('information_object as io')
        ->leftJoin('status as s', function ($join) {
            $join->on('io.id', '=', 's.object_id')
                ->where('s.type_id', '=', 158); // STATUS_TYPE_PUBLICATION_ID
        })
        ->where('io.id', $objectId)
        ->select('s.status_id')
        ->first();
    
    // If published (status_id = 160), anyone can read
    if ($object && $object->status_id == 160) { // PUBLICATION_STATUS_PUBLISHED
        return true;
    }
    
    // Otherwise, must be authenticated
    if (!$user->isAuthenticated()) {
        return false;
    }
    
    $userId = $user->getAttribute('user_id');
    if (!$userId) {
        return false;
    }
    
    // Check user groups - admin/editor/contributor can read
    return DB::table('acl_user_group')
        ->where('user_id', $userId)
        ->whereIn('group_id', [100, 101, 102, 103]) // Admin, Editor, Contributor, Translator
        ->exists();
}

/**
 * Get information object with i18n data
 */
function comparison_get_object(int $id): ?object
{
    $culture = sfContext::getInstance()->getUser()->getCulture() ?? 'en';
    
    $object = DB::table('information_object as io')
        ->leftJoin('information_object_i18n as ioi', function ($join) use ($culture) {
            $join->on('io.id', '=', 'ioi.id')
                ->where('ioi.culture', '=', $culture);
        })
        ->leftJoin('information_object_i18n as ioi_en', function ($join) {
            $join->on('io.id', '=', 'ioi_en.id')
                ->where('ioi_en.culture', '=', 'en');
        })
        ->leftJoin('slug', 'io.id', '=', 'slug.object_id')
        ->where('io.id', $id)
        ->select([
            'io.id',
            'io.identifier',
            'io.level_of_description_id',
            'slug.slug',
            DB::raw('COALESCE(ioi.title, ioi_en.title) as title'),
            DB::raw('COALESCE(ioi.extent_and_medium, ioi_en.extent_and_medium) as extent_and_medium'),
        ])
        ->first();
    
    return $object;
}

/**
 * Get level of description name
 */
function comparison_get_level_name(?int $levelId): ?string
{
    if (!$levelId) {
        return null;
    }
    
    $culture = sfContext::getInstance()->getUser()->getCulture() ?? 'en';
    
    $term = DB::table('term as t')
        ->leftJoin('term_i18n as ti', function ($join) use ($culture) {
            $join->on('t.id', '=', 'ti.id')
                ->where('ti.culture', '=', $culture);
        })
        ->leftJoin('term_i18n as ti_en', function ($join) {
            $join->on('t.id', '=', 'ti_en.id')
                ->where('ti_en.culture', '=', 'en');
        })
        ->where('t.id', $levelId)
        ->select(DB::raw('COALESCE(ti.name, ti_en.name) as name'))
        ->first();
    
    return $term ? $term->name : null;
}

/**
 * Get creators for information object
 */
function comparison_get_creators(int $objectId): string
{
    $culture = sfContext::getInstance()->getUser()->getCulture() ?? 'en';
    
    // Event type ID for creation
    $creationTypeId = 111; // TERM_CREATION_ID
    
    $creators = DB::table('event as e')
        ->join('actor as a', 'e.actor_id', '=', 'a.id')
        ->leftJoin('actor_i18n as ai', function ($join) use ($culture) {
            $join->on('a.id', '=', 'ai.id')
                ->where('ai.culture', '=', $culture);
        })
        ->leftJoin('actor_i18n as ai_en', function ($join) {
            $join->on('a.id', '=', 'ai_en.id')
                ->where('ai_en.culture', '=', 'en');
        })
        ->where('e.object_id', $objectId)
        ->where('e.type_id', $creationTypeId)
        ->select(DB::raw('COALESCE(ai.authorized_form_of_name, ai_en.authorized_form_of_name) as name'))
        ->get();
    
    $names = [];
    foreach ($creators as $creator) {
        if ($creator->name) {
            $names[] = $creator->name;
        }
    }
    
    return implode('; ', $names);
}

/**
 * Get field value for comparison
 */
function comparison_get_field_value(object $object, string $field): ?string
{
    switch ($field) {
        case 'identifier':
            return $object->identifier;
        case 'title':
            return $object->title;
        case 'level_of_description':
            return comparison_get_level_name($object->level_of_description_id);
        case 'creator':
            return comparison_get_creators($object->id);
        case 'extent_and_medium':
            return $object->extent_and_medium;
        default:
            return null;
    }
}

/**
 * Build URL for information object
 */
function comparison_object_url(object $object): string
{
    if ($object->slug) {
        return url_for(['module' => 'informationobject', 'action' => 'index', 'slug' => $object->slug]);
    }
    return url_for(['module' => 'informationobject', 'action' => 'index', 'id' => $object->id]);
}

// Field groups configuration
$fieldGroups = [
    'identification' => [
        'label' => 'Identification',
        'fields' => ['identifier', 'title', 'level_of_description'],
    ],
    'creation' => [
        'label' => 'Creation',
        'fields' => ['creator'],
    ],
    'physical' => [
        'label' => 'Physical Description',
        'fields' => ['extent_and_medium'],
    ],
];

// Get object IDs from request
$objectIds = sfContext::getInstance()->getRequest()->getParameter('objects', '');
if (is_string($objectIds)) {
    $objectIds = array_filter(explode(',', $objectIds));
}

// Load objects with access check
$objects = [];
foreach ($objectIds as $id) {
    $objectId = (int)$id;
    if ($objectId > 0 && comparison_check_read_access($objectId)) {
        $object = comparison_get_object($objectId);
        if ($object) {
            $objects[] = $object;
        }
    }
}
?>

<?php decorate_with('layout_1col.php'); ?>

<?php slot('title'); ?>
  <h1><?php echo __('Object Comparison'); ?></h1>
<?php end_slot(); ?>

<?php slot('content'); ?>

<?php if (count($objects) < 2): ?>
  <div class="alert alert-warning">
    <?php echo __('Select at least 2 objects to compare. Add object IDs to the URL: ?objects=123,456'); ?>
    <br><br>
    <?php echo __('Objects found: %1%', ['%1%' => count($objects)]); ?>
  </div>
<?php else: ?>
  <table class="table table-bordered">
    <thead>
      <tr>
        <th><?php echo __('Field'); ?></th>
        <?php foreach ($objects as $object): ?>
          <th>
            <a href="<?php echo comparison_object_url($object); ?>">
              <?php echo htmlspecialchars($object->title ?? $object->slug ?? $object->identifier ?? ''); ?>
            </a>
          </th>
        <?php endforeach; ?>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($fieldGroups as $groupKey => $group): ?>
        <tr class="table-secondary">
          <th colspan="<?php echo count($objects) + 1; ?>"><?php echo __($group['label']); ?></th>
        </tr>
        <?php foreach ($group['fields'] as $field): ?>
          <tr>
            <td><strong><?php echo __(ucwords(str_replace('_', ' ', $field))); ?></strong></td>
            <?php foreach ($objects as $object): ?>
              <td><?php 
                $value = comparison_get_field_value($object, $field);
                echo $value ? htmlspecialchars($value) : '<em class="text-muted">-</em>'; 
              ?></td>
            <?php endforeach; ?>
          </tr>
        <?php endforeach; ?>
      <?php endforeach; ?>
    </tbody>
  </table>
<?php endif; ?>

<?php end_slot(); ?>