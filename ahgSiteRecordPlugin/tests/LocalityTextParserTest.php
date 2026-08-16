<?php

require dirname(__DIR__).'/lib/Services/LocalityTextParser.php';

use AhgSiteRecordPlugin\Services\LocalityTextParser as P;

$pass = 0;
$fail = 0;

function check(string $label, $got, $want)
{
    global $pass, $fail;
    $ok = $got === $want;
    $ok ? ++$pass : ++$fail;
    printf("  %s %-52s got=%s want=%s\n", $ok ? 'ok  ' : 'FAIL', $label,
        var_export($got, true), var_export($want, true));
}

// Real values taken from RARI's actor_i18n.internal_structures.
$rari = 'E?.8.2.G<br>pl location th i: x100<br> Map sheet: 3027AC<br> Map sheet: 3027AC_1965_ED1_GEO<br> Map sheet: 3027AC_2009_ED3_GEO';
$noSheet = 'E?.05.E.E<br>pl location th i: x788';

echo "--- normalise ---\n";
check('br tags become spaces, not nothing', P::normalise('a<br>b'), 'a b');
check('self-closing br', P::normalise('a<br/>b'), 'a b');
check('collapses whitespace', P::normalise("a  \n\t b"), 'a b');
check('decodes entities', P::normalise('a &amp; b'), 'a & b');
check('null is empty', P::normalise(null), '');
check('keeps the code and index', P::normalise($noSheet), 'E?.05.E.E pl location th i: x788');

echo "\n--- map sheet ---\n";
check('reads the plain sheet', P::mapSheet(P::normalise($rari)), '3027AC');
check('no sheet returns null', P::mapSheet(P::normalise($noSheet)), null);
check('null returns null', P::mapSheet(null), null);

echo "\n--- editions must not become separate localities ---\n";
check('one sheet, not four', P::allMapSheets(P::normalise($rari)), ['3027AC']);
check('edition suffix alone is not a sheet', P::mapSheet('Map sheet: 3027AC_1965_ED1_GEO'), null);
check('two genuinely different sheets', P::allMapSheets('Map sheet: 3027AC Map sheet: 2929CC'), ['3027AC', '2929CC']);

echo "\n--- shape rules ---\n";
check('needs two letters', P::mapSheet('3027A'), null);
check('needs four digits', P::mapSheet('302AC'), null);
check('lowercase is not a sheet', P::mapSheet('3027ac'), null);
check('not a substring of a longer token', P::mapSheet('X3027ACX'), null);

printf("\n  %d passed, %d failed\n", $pass, $fail);
exit($fail > 0 ? 1 : 0);
