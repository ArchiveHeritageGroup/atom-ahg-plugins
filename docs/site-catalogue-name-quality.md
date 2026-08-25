# Site catalogue authority record names - what is actually wrong with them

Date: 2026-08-24
Source: the source site-catalogue spreadsheet,
4,708 data rows - the file the site records were imported from.

Prompted by a cataloguer reporting an authority-record autocomplete "full of
question marks". That turned out not to be a bug: the widget opens before you
type and shows page 1 of 2,623 records in alphabetical order, and the head of
that order is punctuation. The names really are `? Part 1` and `# 28 field camp`.

Chasing it surfaced a larger problem in the same field.

## The headline: a third of named sites cannot be told apart

| | rows | share of named |
|---|---:|---:|
| Named sites | 3,964 | - |
| Distinct names | 3,006 | - |
| **Rows whose name is shared with another site** | **1,243** | **31%** |

285 names are reused. The worst offenders:

```
78x  <name-1>        24x  <name-4>      19x  <name-5>
33x  <name-2>      20x  <name-6>     19x  <name-7>
25x  <name-3>                              19x  <name-8> 1 - 19
```

An authority record exists to identify an entity. Seventy-eight records called
`<name-1>`, each a different site, do not. This is a more serious problem than
the punctuation the cataloguer noticed, and it is invisible in an autocomplete - every
one of those 78 looks like a correct, plausible choice.

**Map No. + Site No. resolves it.** Tested against every combination in the file:

| Key | Distinct | Rows still ambiguous |
|---|---:|---:|
| Site No. alone | 2,309 | 3,023 |
| Name + Map No. | 3,176 | 1,835 |
| Farm + Site No. | 3,236 | 1,858 |
| **Map No. + Site No.** | **4,682** | **49** |
| Name + Map No. + Site No. | 4,705 | 5 |

That 4,682 is the same figure the original import produced, which confirms it
keyed on the same pair. Only 49 rows stay ambiguous, and 9 rows have no Site No.
at all - a hand-sized problem rather than a bulk one.

Note that Site No. alone is useless: 3,023 rows share one. Do not disambiguate
with it.

## The rest, by class

| Class | Rows | Example | Worth changing? |
|---|---:|---|---|
| Blank name | 744 | - | Already handled: titled from farm + site no at import |
| Duplicate name | 1,243 | `<name-1>` x78 | **Yes** - append map + site no |
| Starts with punctuation or digit | 120 | `? Part 1`, `# 14`, `(Site 43) <name-7> IX` | Yes - this is what surfaces first everywhere |
| Uncertainty marker `?` | 67 | `<Hill name>?`, `Field Hut?` | Yes - strip, keep the uncertainty in the text |
| Parenthetical | 161 | `<Farm> Kraal A(2)`, `T40 (height marker 1917), <country>` | **Partly** - see below |
| Slash | 74 | `<NameA>/ <NameB>`, `<NameC>/<NameD>` | Partly - some are alternate names |
| ALL CAPS | 152 | `TP 1`, `ATTEN 99` | Probably not - see below |

**Parentheticals are not one thing.** `<Farm> Kraal A(2)` is a name; the
bracket is part of how the site is designated. `T40 (height marker 1917),
Botswana` is a name plus a locational note. `(Site 43) <name-7> IX` is a name with
a cross-reference bolted to the front. Only the second and third want moving, and
telling them apart needs an archaeologist, not a regex.

**Slashes are often alternate names.** `<NameA>/ <NameB>` is one site with two
accepted names - which is what ISAAR's parallel form of name is for, not
something to discard.

**ALL CAPS are mostly not site names at all.** `TP 1`, `TP 2`, `ATTEN 99` read as
trench and test-pit labels. Renaming them would be inventing; they may need
re-modelling as something other than a site, which is a cataloguing decision.

## What to do, and what not to

The period conversion on this same catalogue set the precedent worth following
(`bin/import-holding-periods`): additive, reversible, and it refused to expand
abbreviations because renaming a term later is cheap and re-mapping records is
not. The same restraint applies here.

Safe to do in bulk:

1. **Disambiguate duplicates** by appending Map No. + Site No. to the 1,243
   shared-name rows, in the form the import already uses for unnamed sites.
   Leaves 49 rows for a person.
2. **Strip trailing `?`** from the 67 uncertain names, and put the uncertainty in
   a note rather than the identifier. Precedent: exactly what the period import did.
3. **Normalise whitespace** - 41 rows carry double spaces.

Needs a person, not a script:

4. The 19 names that are `?`, `? Part 1`, `? Part 2` and similar. These are not
   names; the row knows its map sheet and site number, so a title can be built,
   but somebody has to decide whether the site is genuinely unidentified.
5. The 66 `#`-prefixed names, which look like a numbering convention from a
   different register that leaked into the name field.
6. The parenthetical and slash cases above.

Do not:

- Do not rename from the phase sheets (Zhizo, K2, Khami, Mapungubwe, Leokwe).
  They key on Site No. alone, which 3,023 rows share, so a join silently attaches
  one site's name to another. This is already recorded as a trap and the numbers
  above confirm it.
- Do not expand abbreviations.
- Do not touch the 152 ALL CAPS labels until somebody decides what they are.

## Boundaries of this analysis

**This profiles the source spreadsheet, not the live instance.** The client
instance reports 2,623 authority records; the source has 4,708 rows and 3,964
named sites. Those numbers do not reconcile, so the per-class counts here will
not match that instance exactly. Confirming them needs a query against it, and
the client instance is unreachable from our network - 443, 80 and 22 all refused
today, from 112 and from .131.

**The data is no longer on the instance B instance either.** Memory records
4,682 sites imported to .131 on 2026-08-18, but that host today holds 1
`ahg_site_record`, 1 `archaeology_site` and 26 actors. Whatever happened since,
any cleanup targets the client instance.

Tracked as ArchiveHeritageGroup/atom-extensions-catalog#310 (filed 2026-08-25, de-identified - this internal copy keeps the institution and the real examples).
