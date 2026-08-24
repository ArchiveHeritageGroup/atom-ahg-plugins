# Reports dashboard: stop rendering cards with nothing in them

Date: 2026-08-24
Instance: archaeology.theahg.co.za
Release: atom-ahg-plugins v3.106.17

Two cards on /reports/ rendered a header above an empty list on a lean install:
Sector Dashboards (Library / Gallery / Museum / DAM / GRAP / Donor) and Export
(ahgExportPlugin, ahgSpectrumPlugin, ahgHeritageAccountingPlugin).

Not a bug in the gates - each entry is correctly hidden when its plugin is absent,
a fix made 2026-08-18 after ungated links 404'd. The defect is that the CARD still
renders. "Not installed" and "broken" then look identical, and the user read it as
broken, which is the correct reading of an empty box.

Each column is now wrapped in an OR of exactly the flags its own entries are gated
on:

    Sector Dashboards: $hasGrap || $hasDonor || $hasGallery || $hasDam || $hasMuseum
    Export:            $hasExport || $hasSpectrum || $hasGrap

Checked programmatically rather than by eye - the wrapper's flag set and the
entries' flag set are identical in both cards, so a card renders if and only if at
least one entry inside it renders. It cannot hide a populated card, which is the
only way this change could do harm.

archaeology after the change: Sector Dashboards gone (it is not a library, gallery,
museum or DAM), Export keeps its 2 entries, /reports/ 200, no empty cards left.

PSIS shares this template but runs opcache.validate_timestamps=0, so it picks the
change up at its next php-fpm reload. Nothing should change visually there - it has
the sector plugins, so those cards have entries.
