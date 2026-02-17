<?php
/**
 * Object Comparison Template
 *
 * @package    ahgMuseumPlugin
 * @subpackage templates
 */

use Illuminate\Database\Capsule\Manager as DB;

// Constants
define('TERM_CREATION_ID', 111);
define('GROUP_ADMINISTRATOR', 100);
define('GROUP_EDITOR', 101);
define('GROUP_CONTRIBUTOR', 102);
define('GROUP_TRANSLATOR', 103);
define('STATUS_TYPE_PUBLICATION_ID', 158);
define('PUBLICATION_STATUS_PUBLISHED', 160);

/**
 * Get information object by ID using Laravel
 */
function compare_get_object(int $id): ?object
{
    $culture = sfContext::getInstance()->getUser()->getCulture() ?? 'en';

    return DB::table('information_object as io')
        ->leftJoin('slug', 'io.id', '=', 'slug.object_id')
        ->leftJoin('information_object_i18n as ioi', function ($join) use ($culture) {
            $join->on('io.id', '=', 'ioi.id')
                ->where('ioi.culture', '=', $culture);
        })
        ->leftJoin('information_object_i18n as ioi_en', function ($join) {
            $join->on('io.id', '=', 'ioi_en.id')
                ->where('ioi_en.culture', '=', \AtomExtensions\Helpers\CultureHelper::getCulture());
        })
        ->where('io.id', $id)
        ->select([
            'io.*',
            'slug.slug',
            DB::raw('COALESCE(ioi.title, ioi_en.title) as title'),
            DB::raw('COALESCE(ioi.extent_and_medium, ioi_en.extent_and_medium) as extent_and_medium'),
            DB::raw('COALESCE(ioi.scope_and_content, ioi_en.scope_and_content) as scope_and_content'),
        ])
        ->first();
}

/**
 * Check ACL read permission using Laravel
 */
function compare_check_read_acl(?object $object): bool
{
    if (!$object) {
        return false;
    }

    // Check if resource is published (public access)
    $publicationStatus = DB::table('status')
        ->where('object_id', $object->id)
        ->where('type_id', STATUS_TYPE_PUBLICATION_ID)
        ->value('status_id');

    if ($publicationStatus == PUBLICATION_STATUS_PUBLISHED) {
        return true;
    }

    // Otherwise must be authenticated
    $user = sfContext::getInstance()->getUser();
    if (!$user->isAuthenticated()) {
        return false;
    }

    $userId = $user->getAttribute('user_id');
    if (!$userId) {
        return false;
    }

    // Get user groups
    $userGroups = DB::table('acl_user_group')
        ->where('user_id', $userId)
        ->pluck('group_id')
        ->toArray();

    // Admins, editors, contributors, translators can read
    return in_array(GROUP_ADMINISTRATOR, $userGroups)
        || in_array(GROUP_EDITOR, $userGroups)
        || in_array(GROUP_CONTRIBUTOR, $userGroups)
        || in_array(GROUP_TRANSLATOR, $userGroups);
}

/**
 * Get level of description name
 */
function compare_get_level_name(?int $levelId): ?string
{
    if (!$levelId) {
        return null;
    }

    $culture = sfContext::getInstance()->getUser()->getCulture() ?? 'en';

    $name = DB::table('term_i18n')
        ->where('id', $levelId)
        ->where('culture', $culture)
        ->value('name');

    if ($name === null) {
        $name = DB::table('term_i18n')
            ->where('id', $levelId)
            ->where('culture', \AtomExtensions\Helpers\CultureHelper::getCulture())
            ->value('name');
    }

    return $name;
}

/**
 * Get creators for object
 */
function compare_get_creators(int $objectId): string
{
    $culture = sfContext::getInstance()->getUser()->getCulture() ?? 'en';

    $creators = DB::table('event as e')
        ->join('actor as a', 'e.actor_id', '=', 'a.id')
        ->leftJoin('actor_i18n as ai', function ($join) use ($culture) {
            $join->on('a.id', '=', 'ai.id')
                ->where('ai.culture', '=', $culture);
        })
        ->leftJoin('actor_i18n as ai_en', function ($join) {
            $join->on('a.id', '=', 'ai_en.id')
                ->where('ai_en.culture', '=', \AtomExtensions\Helpers\CultureHelper::getCulture());
        })
        ->where('e.object_id', $objectId)
        ->where('e.type_id', TERM_CREATION_ID)
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
 * Get field value for object
 */
function compare_get_field_value(?object $object, string $field): ?string
{
    if (!$object) {
        return null;
    }

    switch ($field) {
        case 'identifier':
            return $object->identifier;

        case 'title':
            return $object->title;

        case 'level_of_description':
            return compare_get_level_name($object->level_of_description_id);

        case 'creator':
            return compare_get_creators($object->id);

        case 'extent_and_medium':
            return $object->extent_and_medium;

        default:
            return null;
    }
}

/**
 * Build URL for object
 */
function compare_object_url(?object $object): string
{
    if (!$object) {
        return '#';
    }

    if ($object->slug) {
        return url_for(['module' => 'informationobject', 'action' => 'index', 'slug' => $object->slug]);
    }

    return url_for(['module' => 'informationobject', 'action' => 'index', 'id' => $object->id]);
}

/**
 * Get object title for display
 */
function compare_get_title(?object $object): string
{
    if (!$object) {
        return '';
    }

    return htmlspecialchars($object->title ?? $object->slug ?? $object->identifier ?? 'Untitled');
}
?>

<?php decorate_with('layout_1col.php'); ?>

<?php
// Load data directly since action variables not passing through
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

$objectIds = sfContext::getInstance()->getRequest()->getParameter('objects', '');
if (is_string($objectIds)) {
    $objectIds = array_filter(explode(',', $objectIds));
}

$objects = [];
foreach ($objectIds as $id) {
    $object = compare_get_object((int) $id);
    if ($object && compare_check_read_acl($object)) {
        $objects[] = $object;
    }
}
?>

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
            <a href="<?php echo compare_object_url($object); ?>"><?php echo compare_get_title($object); ?></a>
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
              <td><?php echo compare_get_field_value($object, $field) ?: '<em class="text-muted">-</em>'; ?></td>
            <?php endforeach; ?>
          </tr>
        <?php endforeach; ?>
      <?php endforeach; ?>
    </tbody>
  </table>
<?php endif; ?>

<?php end_slot(); ?>