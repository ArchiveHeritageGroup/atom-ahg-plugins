<?php
/**
 * Child Levels Partial
 *
 * @package    ahgMuseumPlugin
 * @subpackage templates
 */

use Illuminate\Database\Capsule\Manager as DB;

/**
 * Get levels of description terms
 */
function cco_get_levels_of_description(): array
{
    $culture = sfContext::getInstance()->getUser()->getCulture() ?? 'en';
    
    // Taxonomy ID for levels of description
    $taxonomyId = 34; // TAXONOMY_LEVEL_OF_DESCRIPTION_ID
    
    // Root term ID for levels of description (to exclude)
    $rootTermId = 117; // ROOT_LEVEL_OF_DESCRIPTION_ID
    
    return DB::table('term as t')
        ->leftJoin('term_i18n as ti', function ($join) use ($culture) {
            $join->on('t.id', '=', 'ti.id')
                ->where('ti.culture', '=', $culture);
        })
        ->leftJoin('term_i18n as ti_en', function ($join) {
            $join->on('t.id', '=', 'ti_en.id')
                ->where('ti_en.culture', '=', 'en');
        })
        ->where('t.taxonomy_id', $taxonomyId)
        ->where('t.id', '!=', $rootTermId)
        ->orderBy(DB::raw('COALESCE(ti.name, ti_en.name)'))
        ->select([
            't.id',
            DB::raw('COALESCE(ti.name, ti_en.name) as name'),
        ])
        ->get()
        ->toArray();
}

$levelsOfDescription = cco_get_levels_of_description();
?>

<div class="section">
  <h3><?php echo __('Add new child levels'); ?></h3>
  <table class="table table-bordered multiRow" id="childsTable">
    <thead>
      <tr>
        <th style="width: 15%">
          <?php echo __('Identifier'); ?>
        </th><th style="width: 15%">
          <?php echo __('Level'); ?>
        </th><th style="width: 50%">
          <?php echo __('Title'); ?>
        </th><th style="width: 20%">
          <?php echo __('Date'); ?>
        </th>
      </tr>
    </thead><tbody>
      <tr class="date">
        <td>
          <input type="text" id="updateChildLevels_0_identifier" name="updateChildLevels[0][identifier]"/>
        </td><td>
          <select name="updateChildLevels[0][levelOfDescription]" id="updateChildLevels_0_levelOfDescription">
            <option value="">&nbsp;</option>
            <?php foreach ($levelsOfDescription as $item) { ?>
              <option value="<?php echo $item->id; ?>"><?php echo htmlspecialchars($item->name ?? ''); ?></option>
            <?php } ?>
          </select>
        </td><td>
          <input type="text" id="updateChildLevels_0_title" name="updateChildLevels[0][title]"/>
        </td><td>
          <input type="text" id="updateChildLevels_0_date" name="updateChildLevels[0][date]"/>
          <input type="hidden" id="updateChildLevels_0_startDate" name="updateChildLevels[0][startDate]"/>
          <input type="hidden" id="updateChildLevels_0_endDate" name="updateChildLevels[0][endDate]"/>
        </td>
      </tr>
    </tbody>
    <tfoot>
      <tr>
        <td colspan="5"><a href="#" class="multiRowAddButton"><?php echo __('Add new'); ?></a></td>
      </tr>
    </tfoot>
  </table>
  <?php if (isset($help)) { ?>
    <div class="description">
      <?php echo $sf_data->getRaw('help'); ?>
    </div>
  <?php } ?>
</div>