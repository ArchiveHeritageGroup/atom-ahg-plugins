<?php
// BUG FIX #74: Determine correct module based on display_standard_id
$cancelModule = 'informationobject';
$displayStandardId = $resource->displayStandardId ?? null;
if ($displayStandardId) {
    switch ($displayStandardId) {
        case 10: $cancelModule = 'ahgLibrary'; break;
        case 11: $cancelModule = 'ahgMuseum'; break;
        case 12: $cancelModule = 'ahgGallery'; break;
        case 13: $cancelModule = 'ahgDAM'; break;
    }
}
?>
