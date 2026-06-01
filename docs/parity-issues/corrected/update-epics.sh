#!/usr/bin/env bash
# Update PSIS parity epics with CORRECTED (cross-plugin verified) counts.
set -euo pipefail
cd "$(dirname "$0")"

gh issue edit 118 --repo ArchiveHeritageGroup/atom-ahg-plugins --title "PSIS parity: descriptive-manage — 0 missing + 2 partial vs Heratio (?% audit)" --body-file "118.md"
gh issue edit 120 --repo ArchiveHeritageGroup/atom-ahg-plugins --title "PSIS parity: dam-media — 3 missing + 2 partial vs Heratio (?% audit)" --body-file "120.md"
gh issue edit 122 --repo ArchiveHeritageGroup/atom-ahg-plugins --title "PSIS parity: iiif-3d — 4 missing + 4 partial vs Heratio (?% audit)" --body-file "122.md"
gh issue edit 123 --repo ArchiveHeritageGroup/atom-ahg-plugins --title "PSIS parity: search-discovery — 3 missing + 2 partial vs Heratio (?% audit)" --body-file "123.md"
gh issue edit 126 --repo ArchiveHeritageGroup/atom-ahg-plugins --title "PSIS parity: rights-privacy-compliance — 0 missing + 5 partial vs Heratio (?% audit)" --body-file "126.md"
gh issue edit 125 --repo ArchiveHeritageGroup/atom-ahg-plugins --title "PSIS parity: accounting-collection — 0 missing + 0 partial vs Heratio (?% audit)" --body-file "125.md"
gh issue edit 124 --repo ArchiveHeritageGroup/atom-ahg-plugins --title "PSIS parity: ingest-preservation — 2 missing + 0 partial vs Heratio (?% audit)" --body-file "124.md"
gh issue edit 121 --repo ArchiveHeritageGroup/atom-ahg-plugins --title "PSIS parity: ai — 1 missing + 2 partial vs Heratio (?% audit)" --body-file "121.md"
gh issue edit 127 --repo ArchiveHeritageGroup/atom-ahg-plugins --title "PSIS parity: research-public — 0 missing + 0 partial vs Heratio (?% audit)" --body-file "127.md"
gh issue edit 129 --repo ArchiveHeritageGroup/atom-ahg-plugins --title "PSIS parity: api-integration — 3 missing + 2 partial vs Heratio (?% audit)" --body-file "129.md"
gh issue edit 119 --repo ArchiveHeritageGroup/atom-ahg-plugins --title "PSIS parity: sectors — 0 missing + 0 partial vs Heratio (?% audit)" --body-file "119.md"
gh issue edit 128 --repo ArchiveHeritageGroup/atom-ahg-plugins --title "PSIS parity: workflow-reporting-misc — 1 missing + 1 partial vs Heratio (?% audit)" --body-file "128.md"

# These domains had ZERO genuinely-missing gaps (all false positives) — close them:
# for n in 119 125 127; do gh issue close $n --repo ArchiveHeritageGroup/atom-ahg-plugins --reason 'not planned' --comment 'Closed: cross-plugin re-verification found 0 genuine gaps (all false positives).'; done