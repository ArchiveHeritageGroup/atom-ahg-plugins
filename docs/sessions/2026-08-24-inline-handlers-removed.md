# Removing the last inline event handlers on archaeology

Date: 2026-08-24
Instance: archaeology.theahg.co.za (enforcing CSP)

The CSP work earlier today fixed the 239 inline-STYLE violations with
`style-src-attr 'unsafe-inline'`. That left the script half: 2 inline event
handler violations, deliberately NOT fixed with `script-src-attr` because two
occurrences do not justify weakening the directive.

Locations came from the workbench session's authenticated crawl - both AHG plugin
pages, neither reachable anonymously, which is why a 7-page sample here found
none:

    /ahgSettings/tts     ahgSettingsPlugin  ttsSuccess.php     oninput=
    /glam/print          ahgDisplayPlugin   printSuccess.php   onclick= x2

Three handlers, not two: /glam/print carries two buttons. A violation fires when
the handler EXECUTES, so an unclicked button never reported one - the crawl count
was of violations, not of handlers.

Replaced with `addEventListener` in nonce'd `<script>` ELEMENTS. The nonce covers
an element; it can never cover an attribute, which is the whole reason these
failed.

Verified on .131 rather than assumed - removing a handler and replacing it with
nothing would be worse than the violation:

    /ahgSettings/tts   slider label 1 -> 1.7 on input, oninput attr gone, console clean
    /glam/print        window.print() fires on click, onclick attrs gone, console clean

archaeology now has no inline event handlers in these pages and no CSP violations
across the pages checked. `script-src` keeps its nonce with nothing added to it.

Still interim, unchanged: `style-src-attr 'unsafe-inline'` remains in place for
the 2,348 inline style attributes across 661 files (#298 / #303). That is the
real work and this does not touch it.
