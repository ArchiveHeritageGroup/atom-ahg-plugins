# 2026-07-21 - South African language i18n catalogues

**Releases:** atom-framework v2.13.36 (Afrikaans restore) + v2.13.37 (9 SA catalogues)
**Deployed:** PSIS (`/usr/share/nginx/archive`), Wits Archaeology (`/usr/share/nginx/archeology`), atom210 base VM (`/usr/share/nginx/atom`)

## Part 1 - Afrikaans was silently broken on PSIS

Symptom: selecting Afrikaans in the language switcher rendered 100% English.

Cause: upstream AtoM ships `apps/qubit/i18n/af/messages.xml` with **every `<target>` blank**
- Afrikaans is not an Artefactual-translated language. The 2.10 upgrade therefore
replaced PSIS's locally-translated catalogue with that empty stub, and nothing
flagged it because an empty target legitimately falls back to English.

Archaeology escaped it only because it was cloned from the RARI lineage rather
than upgraded; its catalogue was byte-identical to rari29's.

**This was NOT a broad regression.** fr/es/nl/pt were checked and are intact
(85%, 76%, 22%, 88%) and identical across instances. Dutch at 22% is poor
*upstream*, not damage. 21 other languages showed a ~30-string delta against
2.6-era copies, which is ordinary upgrade drift in Artefactual's own
translations - deliberately NOT back-ported, since the correct source for those
is current upstream, not our old files.

### The fix

`/usr/share/nginx/rari29` carried a 99%-complete Afrikaans translation (1427/1430)
from the AtoM 2.6/2.7 era. Merged onto the 2.10 string set keyed on **source text**,
so genuinely-new 2.10 strings stay untranslated rather than invented.

atom210's template (1267 units) turned out not to be a superset of archive's
(1263) - 2 strings each way - so the shipped file is a **union**: 1269 units,
1147 translated (90%), covering both installs. Extra units simply never match.

Verified live: `/informationobject/browse?sf_culture=af` returns Soek, Taal,
Blaai, Titel, Datum, Sorteer, Resultate, Bewaarplek, Vlak.

## Part 2 - the other nine official languages

Only `af` and `tn` had catalogues at all; `tn` was 0-translated. Built all nine.

| Code | Language | Drafted | Status |
|---|---|---|---|
| zu | isiZulu | 56 | drafted, needs review |
| xh | isiXhosa | 45 | drafted, needs review |
| st | Sesotho | 34 | drafted, needs review |
| tn | Setswana | 31 | drafted, needs review |
| nso | Sepedi | 29 | drafted, needs review |
| nr | isiNdebele | 0 | scaffold only |
| ss | siSwati | 0 | scaffold only |
| ve | Tshivenda | 0 | scaffold only |
| ts | Xitsonga | 0 | scaffold only |

Each carries the full 1269-string set with correct ids, so a translator receives a
complete file rather than a stub.

### Deliberate limits

- **Only generic UI vocabulary** was drafted - Yes/No, Name, Date, Search, Save,
  Delete, Close, Print, User, Description, Back, Add, City, Country, Place,
  Results, Send, Level, List, Error. All **archival/domain terms were left empty**
  (Accession, Fonds, Repository, Taxonomy, Provenance, Slug, Level of description):
  those need a domain speaker, and a plausible-but-wrong archival term is harder to
  catch than an untranslated one.
- Individual words were dropped where the language makes an isolated gloss unsafe:
  isiZulu "Remove"/"Delete" both give *Susa* and "Place"/"Location" both *Indawo*;
  Setswana "All" is noun-class dependent (*botlhe* vs *tsotlhe*).
- **isiNdebele, siSwati, Tshivenda and Xitsonga were deliberately left empty.**
  These are low-resource languages where the drafts would have been plausible-looking
  output nobody on the team could audit.

### Provenance marking

Every drafted string is written as `<target state="needs-review-translation">`.
It is a real XLIFF attribute, Symfony ignores it at runtime, and it makes the
provisional work machine-findable - listable, auditable, and bulk-revertible
without touching anything a human later confirms.

```bash
# what still needs review, per language
grep -c 'needs-review-translation' apps/qubit/i18n/<code>/messages.xml
```

## Safety property worth remembering

An empty `<target>` is **not** a broken state - AtoM falls back to English, which
is correct and usable. So the risk here is asymmetric: a wrong translation replaces
a safe English string with a wrong foreign one that nobody on the team can audit.
That is the whole reason the drafts are small and four languages are empty.

## Near-miss

The first catalogue-builder run included `af` and regenerated it as an empty
scaffold, which would have destroyed the 1147 translations restored an hour
earlier. It only wrote to scratch, so nothing was lost. `af` is now hard-excluded
from the builder with a comment explaining why. Separately, a libvirt
argument-size limit (`MAX_ARG_STRLEN`, 128 KiB per argv entry) truncated the first
push to atom210 and left its `af/messages.xml` **empty**; restored from the backup
taken beforehand and retried with 32 KiB chunks, md5-verified.

## OPEN - needs Johan

1. **`bin/install` only reapplies `af`.** The nine new catalogues are in
   `patches/apps/qubit/i18n/` and deployed live, but Step 11 has a single
   hardcoded `af` block, so a reinstall would not restore them. Needs
   generalising to a loop over `patches/apps/qubit/i18n/*`. `bin/install` is an
   approval-gated file, so this was not changed.
2. **No SA language is enabled in the switcher.** At 2-4% coverage a user would
   see an almost entirely English page. Enable per language once coverage clears
   an agreed threshold.
3. **`zu` is enabled on PSIS but was pointing at nothing** - it now has a
   catalogue (56 strings), so it is no longer a dead option, but it is still far
   below a sensible threshold.
4. Review sheets were **not** generated (Johan declined). The drafted terms remain
   discoverable via the `needs-review-translation` state.
