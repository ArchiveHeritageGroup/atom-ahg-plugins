<?php
/*
 * CAAIS 1.0 profile - accession view page (#199, #203). Port of Heratio's
 * partials/_caais-show.blade.php. Repository always; the CAAIS elements and
 * the mandatory-element check only when the profile is on.
 *
 * One addition: the export links are offered to administrators whether or
 * not the profile is switched on, because the CAAIS record is built mostly
 * from the core accession fields and is useful to send on either way.
 *
 * Expects: accessionId (int), slug (string), canEdit (bool).
 */
require_once dirname(__DIR__, 3).'/lib/Services/CaaisProfileService.php';

$cs = new \AhgAccessionManage\Services\CaaisProfileService();
if (!$cs->installed()) {
    return;
}

$caaisId = (int) $accessionId;
$caais = $cs->get($caaisId);
$caaisEnabled = $cs->enabled();
$caaisUser = sfContext::getInstance()->getUser();
// The route check covers the window after a deploy in which this partial is
// live but the plugin configuration that registers the route is not.
$caaisCanExport = $caaisUser->isAdministrator()
    && sfContext::getInstance()->getRouting()->hasRouteName('accession_caais_export');
// Values the last save could not keep (see saveCaaisProfile()).
$caaisErrors = (array) $caaisUser->getFlash('caais_errors', []);

if (!$caais['repository_id'] && !$caaisEnabled && !$caaisCanExport && !$caaisErrors) {
    return;
}

$h = function ($v) {
    return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
};
$multiline = function ($v) use ($h) {
    return nl2br($h($v));
};
$field = function (string $label, string $html) {
    return '<div class="field text-break row g-0">'
        .'<h3 class="h6 lh-base m-0 text-muted col-3 border-end text-end p-2">'.$label.'</h3>'
        .'<div class="col-9 p-2">'.$html.'</div></div>';
};
$exportUrl = function (string $format, bool $external) use ($caaisId) {
    return url_for('@accession_caais_export').'?'.http_build_query(array_filter(['id' => $caaisId, 'format' => $format, 'external' => $external ? 1 : null]));
};
$caaisTitle = $caaisEnabled ? __('Repository and CAAIS profile') : __('Repository');
?>
<div class="section border-bottom" id="caaisArea">

  <?php echo render_b5_section_heading(
      $caaisTitle,
      (bool) $canEdit,
      ['module' => 'accession', 'action' => 'edit', 'slug' => (string) $slug],
      ['anchor' => 'caais-collapse', 'class' => '', 'title' => '']
  ); ?>

  <?php if ($caaisErrors) { ?>
    <div class="alert alert-warning m-2" role="status">
      <strong><?php echo __('The accession was saved, but these CAAIS values were not:'); ?></strong>
      <ul class="mb-0"><?php foreach ($caaisErrors as $m) { ?><li><?php echo $h($m); ?></li><?php } ?></ul>
    </div>
  <?php } ?>

  <?php echo $field(__('Repository'), $h($caais['repository_name'] ?? '')); ?>

  <?php if ($caaisEnabled) { ?>
    <?php $missing = $cs->missingFor($caaisId); ?>
    <?php if ($missing) { ?>
      <div class="alert alert-warning m-2" role="status">
        <strong><?php echo __('CAAIS mandatory elements not yet recorded:'); ?></strong>
        <ul class="mb-0"><?php foreach ($missing as $m) { ?><li><?php echo $h($m); ?></li><?php } ?></ul>
      </div>
    <?php } ?>

    <?php
        $confidential = array_filter($caais['sources']);
        if ($confidential) {
            $items = '';
            foreach ($cs->sources($caaisId) as $d) {
                if (!empty($confidential[$d['id']])) {
                    $items .= '<li>'.$h($d['name']).': <span class="badge bg-warning text-dark">'.$h($cs->label('confidentiality', $confidential[$d['id']])).'</span></li>';
                }
            }
            echo $field(__('Source confidentiality'), '<ul class="m-0 ms-1 ps-3">'.$items.'</ul>');
        }

        $items = '';
        foreach ($caais['extents'] as $e) {
            $qty = trim(($e['is_estimate'] ? 'ca. ' : '').\AhgAccessionManage\Services\CaaisProfileService::formatQuantity($e['quantity']).' '.($cs->label('unit', $e['unit']) ?? ''));
            $types = implode(', ', array_filter([$cs->label('content_type', $e['content_type']), $cs->label('carrier_type', $e['carrier_type'])]));
            $items .= '<li><strong>'.$h($cs->label('extent_type', $e['extent_type'])).':</strong> '.$h($qty)
                .('' !== $types ? ' ('.$h($types).')' : '')
                .($e['digital_file_formats'] ? '<br><span class="text-muted">'.__('Formats').':</span> '.$h($e['digital_file_formats']) : '')
                .($e['note'] ? '<br><span class="text-muted">'.$multiline($e['note']).'</span>' : '')
                .'</li>';
        }
        echo $field(__('Extent statements'), $items ? '<ul class="m-0 ms-1 ps-3">'.$items.'</ul>' : '');

        $languages = array_filter(array_map(
            fn ($l) => implode(' - ', array_filter([$cs->label('language', $l['language']), $l['note']])),
            $caais['languages']
        ));
        echo $field(__('Language of material'), $h(implode('; ', $languages)));

        $items = '';
        foreach ($caais['preservation'] as $r) {
            $items .= '<li><strong>'.$h($cs->label('requirement_type', $r['requirement_type'])).':</strong> '.$multiline($r['requirement_value'])
                .($r['note'] ? '<br><span class="text-muted">'.$multiline($r['note']).'</span>' : '').'</li>';
        }
        echo $field(__('Preservation requirements'), $items ? '<ul class="m-0 ms-1 ps-3">'.$items.'</ul>' : '');

        $items = '';
        foreach ($caais['events'] as $e) {
            $items .= '<li><strong>'.$h($cs->label('event_type', $e['event_type'])).'</strong>'
                .($e['event_date'] ? ' - '.$h($e['event_date']) : '')
                .($e['agent'] ? ' - '.$h($e['agent']) : '')
                .($e['note'] ? '<br><span class="text-muted">'.$multiline($e['note']).'</span>' : '').'</li>';
        }
        echo $field(__('Transfer and accessioning events'), $items ? '<ul class="m-0 ms-1 ps-3">'.$items.'</ul>' : '');

        echo $field(__('Rules or conventions'), $h($caais['rules_or_conventions'] ?? ''));

        $items = '';
        foreach ($caais['revisions'] as $r) {
            $items .= '<li>'.$h($cs->label('revision_type', $r['revision_type'])).' - '.$h(date('j F Y H:i', strtotime((string) $r['revision_date'])))
                .($r['agent'] ? ' - '.$h($r['agent']) : '').'</li>';
        }
        echo $field(__('Creation and revisions'), $items ? '<ul class="m-0 ms-1 ps-3">'.$items.'</ul>' : '');
    ?>
  <?php } ?>

  <?php if ($caaisCanExport) { ?>
    <?php
        $links = function (bool $external) use ($exportUrl, $h) {
            $out = [];
            foreach (['json' => 'JSON', 'csv' => 'CSV', 'xml' => 'XML'] as $format => $label) {
                $out[] = '<a href="'.$h($exportUrl($format, $external)).'">'.$label.'</a>';
            }

            return implode(' | ', $out);
        };
        echo $field(
            __('CAAIS export'),
            __('Full record').': '.$links(false).'<br>'.__('For sharing - confidential sources withheld').': '.$links(true)
        );
    ?>
  <?php } ?>

</div> <!-- /.section#caaisArea -->
