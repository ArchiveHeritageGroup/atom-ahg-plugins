# Inline event handler triage - plugins enabled on archaeology

Date: 2026-08-24
Scope: the 31 AHG plugins enabled on archaeology.theahg.co.za (enforcing CSP)
Related: #303, #298

## The count, and why earlier figures differed

**97 handler occurrences across 95 lines.** Two lines carry two handlers each.

An earlier figure of 110 in this thread was WRONG: that run filtered `.blade.php`
against the matched TEXT (`onclick=`) rather than the filename, so Blade files
were silently included. Corrected here.

For contrast, the same day's authenticated crawl exercised **2** violations. The
three figures answer different questions and should never be swapped:

    exercised by a crawl        2    what is visibly broken today
    present and live here      97    what this instance ships
    present in the repo       250    all rendered Symfony templates, all plugins

| Event | Count |
|-------|-------|
| onclick  | 54 |
| onerror  | 18 |
| onchange | 17 |
| onload   |  4 |
| oninput  |  4 |

By plugin: ahgHeritage 19, ahgReports 18, ahgSettings 14, ahgResearch 14,
ahgIiif 7, ahgDisplay 6, ahgRuntime 5, ahgSecurityClearance 4, ahgFavorites 4,
ahgUiOverrides 3, ahgCart 3.

Of 85 handlers whose value could be captured, **22 contain PHP interpolation**
(e.g. `updateTerm(<?php echo $term->id ?>, ...)`) and need a `data-*` attribute to
carry the value; the other 63 lift straight into a listener.

## Buckets, in the order I would do them

### B. onchange - navigation and field sync - 17 - DO FIRST
    onchange="location=this.value"
    onchange="window.location.href='?room_id=' + this.value"
    onchange="document.getElementById('primary_color').value = this.value; ..."

These fire on ordinary interaction on live admin pages, so this is where the
enforcing CSP actually breaks something a person is doing. Highest impact per
unit of work, and formulaic: `data-nav-target` / `data-sync-target` plus one
delegated `change` listener.

### C. onclick - single expression - 31 - STRAIGHTFORWARD
One call, no semicolon. `data-action` plus a delegated `click` listener, or a
named function per page. Mechanical.

### D. onclick - multi-statement - 20 - SAME WORK, MORE CARE
Contains at least one `;`. The body has to move into a named function rather than
be lifted verbatim, and several are the PHP-interpolated ones, so the value has to
move to a `data-*` attribute at the same time. Nothing hard, but not a sed.

### E. onload + oninput - 8 - SMALL
Handle with C.

### A. onerror - image fallbacks - 18 - DO LAST, ONE FIX FOR ALL
    onerror="this.style.display='none'"
    onerror="this.onerror=null; this.parentElement.innerHTML='<div class=...>'"

Every one is a broken-image fallback. They fire ONLY when an image fails to load,
so they are close to invisible in normal use - real, but the least urgent thing
on this list. Two notes:

- A single delegated listener replaces all 18:
  `document.addEventListener('error', handler, true)` - **capture phase is
  required**, because `error` does not bubble.
- What these handlers DO is write `this.style...`, which is a CSSOM write and is
  NOT governed by CSP at all. Only the handler itself violates. So converting
  them removes the violation without needing any style work.

## Sequencing

B (17) then C+E (39) then D (20) then A (18). B is where the user-visible
breakage is; A is 18 violations retired by one listener and can wait.

## What this does not cover

Handlers in plugins NOT enabled on archaeology (250 repo-wide, so ~153 elsewhere),
and anything in `.blade.php` (21 repo-wide, likely dead - #280). Triage those when
the plugin is enabled somewhere that enforces CSP, not before.
