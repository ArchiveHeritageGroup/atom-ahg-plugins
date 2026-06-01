#!/usr/bin/env bash
# File the 12 PSIS-parity twin epics from the 2026-05-31 Heratio<->PSIS audit.
# Run from anywhere; bodies live beside this script.
set -euo pipefail
cd "$(dirname "$0")"

gh issue create --repo ArchiveHeritageGroup/atom-ahg-plugins \
  --title "PSIS descriptive-manage — close 7 high + 5 med gaps vs Heratio (35%)" \
  --body-file "descriptive-manage.md" \
  --label type:enhancement --label status:future --label priority:P2

gh issue create --repo ArchiveHeritageGroup/atom-ahg-plugins \
  --title "PSIS sectors — close 6 high + 4 med gaps vs Heratio (42%)" \
  --body-file "sectors.md" \
  --label type:enhancement --label status:future --label priority:P2

gh issue create --repo ArchiveHeritageGroup/atom-ahg-plugins \
  --title "PSIS dam-media — close 5 high + 6 med gaps vs Heratio (45%)" \
  --body-file "dam-media.md" \
  --label type:enhancement --label status:future --label priority:P2

gh issue create --repo ArchiveHeritageGroup/atom-ahg-plugins \
  --title "PSIS ai — close 5 high + 4 med gaps vs Heratio (55%)" \
  --body-file "ai.md" \
  --label type:enhancement --label status:future --label priority:P3

gh issue create --repo ArchiveHeritageGroup/atom-ahg-plugins \
  --title "PSIS iiif-3d — close 5 high + 8 med gaps vs Heratio (62%)" \
  --body-file "iiif-3d.md" \
  --label type:enhancement --label status:future --label priority:P3

gh issue create --repo ArchiveHeritageGroup/atom-ahg-plugins \
  --title "PSIS search-discovery — close 6 high + 6 med gaps vs Heratio (65%)" \
  --body-file "search-discovery.md" \
  --label type:enhancement --label status:future --label priority:P3

gh issue create --repo ArchiveHeritageGroup/atom-ahg-plugins \
  --title "PSIS ingest-preservation — close 2 high + 3 med gaps vs Heratio (65%)" \
  --body-file "ingest-preservation.md" \
  --label type:enhancement --label status:future --label priority:P3

gh issue create --repo ArchiveHeritageGroup/atom-ahg-plugins \
  --title "PSIS accounting-collection — close 3 high + 3 med gaps vs Heratio (68%)" \
  --body-file "accounting-collection.md" \
  --label type:enhancement --label status:future --label priority:P3

gh issue create --repo ArchiveHeritageGroup/atom-ahg-plugins \
  --title "PSIS rights-privacy-compliance — close 5 high + 5 med gaps vs Heratio (72%)" \
  --body-file "rights-privacy-compliance.md" \
  --label type:enhancement --label status:future --label priority:P3

gh issue create --repo ArchiveHeritageGroup/atom-ahg-plugins \
  --title "PSIS research-public — close 7 high + 13 med gaps vs Heratio (72%)" \
  --body-file "research-public.md" \
  --label type:enhancement --label status:future --label priority:P3

gh issue create --repo ArchiveHeritageGroup/atom-ahg-plugins \
  --title "PSIS workflow-reporting-misc — close 2 high + 4 med gaps vs Heratio (72%)" \
  --body-file "workflow-reporting-misc.md" \
  --label type:enhancement --label status:future --label priority:P3

gh issue create --repo ArchiveHeritageGroup/atom-ahg-plugins \
  --title "PSIS api-integration — close 3 high + 6 med gaps vs Heratio (75%)" \
  --body-file "api-integration.md" \
  --label type:enhancement --label status:future --label priority:P3
