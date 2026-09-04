# What a check reports when it fails

**Date:** 3-4 September 2026
**Releases:** plugins v3.106.69 - v3.106.85; framework v2.18.35, v2.18.36

A review of the PII and redaction criteria in `ahgPrivacyPlugin` turned into
nineteen releases, one production outage, and a recurring defect that turned up
eight times in two days. The first part of the session has its own log in
`2026-09-03-redaction-failed-open-and-a-misfiled-phone-number.md`; this covers the
whole arc.

Base AtoM was untouched throughout. No file under `apps/`, `lib/` or `vendor/` was
modified at any point.

## The review found eleven issues. The work found three it had missed.

The eleven were real: bare-shape patterns scored high with no checksum, a financial
context gate whose keyword `ref` matched inside `reference` so it was open in nearly
every description, two patterns in a subset relationship double-counting every
ten-digit number, a comment claiming a Luhn check that did not exist, confidence
scored against the wrong occurrence of a repeated value, and a whole risk band
silently discarded because `critical` was missing from four separate enumerations.

What reading the code did not reveal:

**Field-level redaction had never executed.** Not once since #130 shipped. The
event wrapper required two of the three service files it needed, nothing autoloads
that namespace in the Symfony context, and a bare `catch (\Throwable)` served the
original page. Tests passed, the symlink was right, the plugin was enabled, the
module routed. None of that answered whether the filter had ever been *called*. An
end-to-end test on live data did, and it ran only because the owner asked for it
after the work had been reported complete.

**A false compliance record had been in the database since January.** Two of 181
stored findings sat on one object and were the same eleven-digit number: a South
African phone number matched a second time as a Nigerian national ID, scored high on
shape, which opened the data-inventory gate. Re-running every stored finding through
the fixed detector bounded it exactly - only two findings were regex-derived and one
reclassified. Scan volume measured how often the feature ran, not how much of its
output the change could touch.

**Unmasked values were on an egress path, not merely at rest.**
`ahg_ner_entity.original_value` belongs to `ahgAIPlugin` and means "the value before
a human corrected it"; `NerTrainingSync` exports it to the AHG Central training
server. Masking was therefore presentational, and the unmasked copy was one reviewer
action from leaving the site.

## Jurisdictions

Pattern selection now follows the installed registry and fails open to the full set,
so reading configuration can never narrow detection. Identifier patterns were added
for seven jurisdictions plus IBAN, every mostly-numeric one either checksum
validated or context gated. Only checksums set the `validated` flag, so a
context-gated finding reaches a reviewer but can never declare a category of
personal data.

Checksums were verified against published test values rather than against the
implementation that would consume them. GDPR stays uncovered permanently - a
regulation across 27 member states has no identifier of its own - and that is
documented, asserted in a test, and surfaced in the UI rather than left to look like
a backlog item.

## The outage

PSIS returned 500 on every page, web and CLI together. Two independent causes from
one Plugin Manager session: a plugin enabled whose files are not on this instance,
which makes `sfProjectConfiguration` throw and takes down the application and the
recovery tooling at once; and the theme disabled, leaving none enabled.

Neither guard held. Nothing checked whether a plugin's files existed before enabling
it. The disable check refused core and locked plugins correctly, then returned
`can_disable => true` from its catch block, so any error waived both protections -
and it caught `Exception`, so an `Error` was not caught at all. Both are fixed, in
the framework and in the UI.

## The thread

Almost every defect this session was a check that could not distinguish **"I checked
and found nothing"** from **"I could not check"**.

| Check | What it could not tell you |
|---|---|
| `preg_match_all()` inside an `if` | false is falsy, so an unevaluable pattern looked like no matches |
| `preg_replace()` cast with `(string)` | null became empty string, so the leak detector searched nothing and served the record |
| plugin protection check | returned "yes you may disable this" from its catch block |
| `catch (\Throwable) { return $content; }` | swallowed a missing class for months |
| an empty debug log | read as "the listener never fired", when the log path was unreachable by the web user |
| `grep -r` over symlinks | skips them silently; produced a confident, wrong claim that two instances were unserved |
| `php -l` on a Blade file | validates nothing, because the directives are not PHP |
| a vhost comment | described an alias that the directive four lines below contradicts |

Every one of those resolves toward the reassuring reading by default. The question
worth asking of any check is not what it reports, but what it reports when it fails.

## Corrections

Three claims made during the session were wrong and were corrected in writing rather
than quietly amended: that the January inventory row concerned a real record (it was
demonstration data); that nothing outside `ahgSettingsPlugin` read the `dp_`
settings (three places did, though none called the accessor); and that neither
instance carrying a phantom plugin was served (one was). Each was found by checking,
not by being challenged.

## Housekeeping

`CLAUDE.md` was corrected in two sections. The ANC entry described a Symfony AtoM
instance at a path that has been a Laravel Heratio checkout for some time; the
TrueNAS entry listed two directories where the shared mount holds around
twenty-eight. A dead settings section and a dead admin-menu partial were removed -
the latter having absorbed a fix in July that consequently did nothing, since
nothing renders that file.

Four test suites now total 101 assertions: 68 PII scoring, 12 redaction filter, 9
coordinate parsing, 12 extension protection. The plugin had no PHP tests at the
start of the session.

## Open

Operator-editable PII patterns were scoped and recommended against for now. The one
uncovered jurisdiction cannot be covered by a pattern, and an operator-supplied
pattern is unvalidated by construction. `wdb210` carries a phantom plugin row that
is inert there, because that instance loads plugins from a hardcoded list rather
than from `atom_plugin`. The `rari` instances are proxied from another host and
cannot be assessed locally.
