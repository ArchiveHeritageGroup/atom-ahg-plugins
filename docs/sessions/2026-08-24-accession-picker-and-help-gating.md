# Accession archival-description picker, and gating the help chatbot

Date: 2026-08-24
Instance: archaeology.theahg.co.za (VM 192.168.0.131)
Release: atom-ahg-plugins v3.106.13

## The picker showed one result at a time

`/accession/add` gained a TomSelect lookup for Archival description. It searched
correctly and returned results, but only one was ever visible - the list appeared
squished into a thin block.

Cause, measured rather than guessed. A geometry probe walked the ancestors of the
open dropdown and found the clipper:

    clippers: [ "DIV.accordion-item overflow=hidden/hidden maxH=none h=168" ]

Bootstrap's `.accordion-item` carries `overflow: hidden`. The dropdown is
absolutely positioned, so it is laid out fine - 1263px wide - and then clipped to
the accordion panel's own 168px box. Nothing about the dropdown's own CSS was
wrong: `max-height: none`, `overflow: visible`, `z-index: 1060` were all correct.

Two changes:

- `ahgThemeB5Plugin/web/js/io-tom-select.js` - `dropdownParent: 'body'`, so the
  results render outside the clipping ancestor entirely. This is the same fix the
  donor picker already carried for the analogous problem inside a scrollable
  modal. `io-tom-select.js` had been written deliberately WITHOUT it, on the
  reasoning that this field is not in a modal - which was true and still missed
  the accordion.
- `ahgThemeB5Plugin/modules/accession/templates/editSuccess.php` - the results
  list raised from TomSelect's default 200px (about four rows) to 360px.
  A nonce'd `<style>` ELEMENT; a nonce would not cover a `style=""` attribute.

Confirmed working in a real browser. Worth noting the headless probe reported
zero options even after the fix while the XHR returned 200 - the probe was not a
reliable oracle here, and the browser was.

## Help chatbot no longer active without an LLM

`HelpChatbotService` was reachable on instances with no LLM configured. Three
changes so it is off unless something can actually answer:

- `isAiAvailable()` now reads `sfProjectConfiguration::getActive()->getPlugins()`
  rather than assuming, and guards the `ahg_llm_config` table.
- Plugin configuration and the action both gate on it.
- `ahgHelpPlugin/modules/help/config/security.yml` added. A missing security.yml
  FAILS OPEN in AtoM, so the help module previously had no declared posture at
  all. Public reads are now explicit; `apiChat`, `systemMap` and `apiSystemMap`
  are secured.

## apiChat stays public - decided

v3.106.13 shipped `apiChat` as `is_secure: true`. That would have broken PSIS,
which runs a public help chatbot for anonymous visitors and only worked because
the missing security.yml failed open. Johan's call, 2026-08-24: keep it public,
now as a written decision rather than an accident. Set `is_secure: false`.

Verified before releasing the change:

- PSIS's compiled cache (`cache/qubit/prod/config/modules_help_config_security.yml.php`,
  stamped 09:16) still holds only APP-LEVEL rules, so the module file was not yet
  live and anonymous `POST /help/apiChat` still answered - `{"error":"Message is
  required"}`, the action itself, not a login page.
- All 11 help actions are explicitly declared, so nothing falls through to the
  `all: is_secure: true` fallback when that cache recompiles. No surprise lockout.

What the choice costs is written into the file: an unmetered LLM endpoint open to
the internet. The exposure is spend and abuse, not disclosure - it answers from
help content. Instances with no LLM are inert regardless, because
`isAiAvailable()` gates the call. Bound usage with an nginx rate limit if needed,
not by securing the action.

## Open, and deliberately not carried by this release

The archaeology user guide and technical manual (.md and .docx) are still
uncommitted in atom-extensions-catalog and need their own commit.

The security baseline can be ratcheted: 232 unguarded modules against a baseline
of 233, via `bin/audit-security-yml-baseline`.
