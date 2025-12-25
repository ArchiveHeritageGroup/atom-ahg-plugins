<?php
if (empty($classification)) return;
$colors = ['PUBLIC'=>'success','INTERNAL'=>'info','CONFIDENTIAL'=>'primary','SECRET'=>'warning','TOP_SECRET'=>'danger'];
?>
<span class="badge bg-<?php echo $colors[$classification->code] ?? 'secondary'; ?>" style="font-size:0.7rem;">
    <?php echo esc_entities($classification->code); ?>
</span>
