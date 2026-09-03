# Redaction that failed open, and a phone number filed as a national ID

**Date:** 3 September 2026
**Plugin:** ahgPrivacyPlugin

Eleven findings in the PII scanner and the field-level redaction chain, reviewed
and then fixed. Two of them mattered for different reasons: one was a defect on a
code path nobody has ever used, and one had already fired and left a false
compliance record in the database.

## Redaction was best-effort and did not say so

`RedactionContentFilter` removes a redacted value from a rendered page by string
substitution. The stored value is looked up, escaped two ways, and replaced. That
works only while the rendered form of a value equals the stored form, and it
frequently does not: the theme may truncate the text, apply `nl2br`, normalise
whitespace, or the field may contain markup of its own. When the needle missed,
the original text stayed on the page and the page was served anyway.

It now verifies. After substitution the filter checks that each redacted value is
genuinely absent from the output, and serves a withheld-record notice instead of
the page if any survived. Once a record is known to carry redaction rules, even an
unexpected exception withholds rather than serves. The action gate also changed
from an allow-list of six action names to a deny-list, so a view action added
later by AtoM or by a plugin is redacted by default rather than exempted.

Verification compares visible text only, and values under twelve characters are
substituted but not verified - a bare year would otherwise match unrelated page
text and withhold every record that mentioned it. Both limits are now written down
in the class rather than left to be discovered.

### The test found a bug in the fix

The leak probe originally used the first 80 characters of the value. For anything
shorter than 80 characters that means probing with the whole value, so a page that
had *truncated* the secret produced no match and reported no leak. That is exactly
the case the check exists to catch. One failing assertion out of ten caught it
twenty minutes after the code was written, when it still read as correct. The
probe is now a twelve-character leading slice.

## A risk band that scored zero

`CREDIT_CARD` is the only finding type rated critical. `critical_risk` was missing
from the summary initialiser, absent from two hardcoded aggregation lists, and
skipped by an `isset()` guard. A detected card incremented `total` and nothing
else: zero risk points, no high-risk flag, no data inventory entry. An unvalidated
ten-digit reference number scored 40.

Four places enumerated the bands and were free to disagree. They now derive from
one constant, and a unit test walks the risk-level map by reflection and asserts
every assigned level is a known band.

Making the band real then exposed the other half: both scan-result templates
headline `high_risk` alone, so a validated card would have counted correctly in
the model and displayed as zero on screen. Fixed in the Symfony and Blade variants.

## The live consequence

Forty-three PII scans have run on this instance. Two of the stored findings sit on
information object 1058:

| Stored type | Masked value | Risk | Status |
|---|---|---|---|
| NG_NIN | 0941*****63 | high | flagged |
| PHONE_SA | 0941***763 | medium | pending |

They are the same number. A South African phone number, matched a second time by
an ungated `\b\d{11}\b` as a Nigerian national ID and rated high on shape alone.
That high-risk count opened the data-inventory gate, and the single
`privacy_data_inventory` row on the instance read "PII scan detected 5 entities.
High-risk: 1 ... NG_NIN: 1, PHONE_SA: 1", classified `personal`, dated 20 January.

A declared category of personal data, on a real record, manufactured by a
misreading. The row and the offending finding were removed after review, with both
backed up first. The legitimate `PHONE_SA` finding was left in place.

## Sizing it: 179 versus 2

Re-running every stored finding through the fixed detector and diffing the
classification bounded the damage exactly. Of 181 stored findings, 179 came from
NER or curated access points and cannot be touched by pattern changes. Two were
regex-derived. One reclassified.

Scan volume measured how often the feature ran, not how much of its output the
change could affect. Sizing the repair from "43 scans, 181 findings" would have
predicted a clean-up job; the real exposure was one row.

## Detection changes

`NG_NIN` and `PASSPORT` now require identity-document context, since an
eleven-digit run and a letter-plus-digits run are the shape of most reference
codes in an archive. The financial context gate used `strpos`, so its keyword
`ref` matched inside `reference` and the gate meant to suppress false positives
was open in nearly every description; keywords are matched on word boundaries now.
`BANK_ACCOUNT` and `TAX_NUMBER` are in a subset relationship and emitted every
ten-digit number twice, both high; overlapping spans now resolve by authority,
which also prevents a broad detector relabelling a span a specific one should have
claimed. Card numbers get the Luhn check the comment always claimed. Confidence is
scored against the occurrence being examined rather than the first match of the
same value.

Anything that persists as a compliance assertion now requires a validated finding.
Unvalidated matches still reach the reviewer; they no longer speak for the
institution.

## Left alone deliberately

`ahg_ner_entity.original_value` stores the unmasked value beside the masked one,
so masking is presentational and the table is a second cleartext copy. It is not
dead weight: `ahgAIPlugin`'s NER training sync reads it, and dropping the write
would silently feed masked text into training. The column belongs to that plugin,
so the exposure is documented at the write site rather than resolved from here.

Jurisdiction selection was implemented and tested but not wired to the installed
registry. Applying it on a live instance could only narrow detection breadth: if
`popia` turned out not to be installed, South African ID and phone detection would
switch off silently. The selection logic treats an empty or unimplemented
jurisdiction as the full pattern set, never the empty one, and enabling it is a
deliberate decision rather than a side effect.

## Verification

Thirty-three scoring assertions and ten filter assertions, in a dependency-free
runner at `ahgPrivacyPlugin/tests/pii_scoring_test.php`. No database, no framework
bootstrap, no fixtures: the decisions were extracted as pure statics precisely so
they could be tested without a harness this plugin does not have. Both suites pass.

The live site was checked after the cache clear: browse and two record views
return 200, with no spurious withheld notices and nothing from the filter in the
PHP-FPM log.
