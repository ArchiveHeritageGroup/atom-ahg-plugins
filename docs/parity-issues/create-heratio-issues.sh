#!/usr/bin/env bash
# File Heratio-side issues: PSIS/AtoM features that Heratio lacks (from 2026-05-31 two-way parity audit).
set -euo pipefail
cd "$(dirname "$0")"

gh issue create --repo ArchiveHeritageGroup/heratio \
  --title "Heratio parity: add 3 PSIS/AtoM-only feature(s) in core-ui" \
  --body-file "heratio-core-ui.md" \
  --label enhancement

gh issue create --repo ArchiveHeritageGroup/heratio \
  --title "Heratio parity: add 1 PSIS/AtoM-only feature(s) in descriptive-manage" \
  --body-file "heratio-descriptive-manage.md" \
  --label enhancement

gh issue create --repo ArchiveHeritageGroup/heratio \
  --title "Heratio parity: add 1 PSIS/AtoM-only feature(s) in dam-media" \
  --body-file "heratio-dam-media.md" \
  --label enhancement

gh issue create --repo ArchiveHeritageGroup/heratio \
  --title "Heratio parity: add 1 PSIS/AtoM-only feature(s) in iiif-3d" \
  --body-file "heratio-iiif-3d.md" \
  --label enhancement

gh issue create --repo ArchiveHeritageGroup/heratio \
  --title "Heratio parity: add 8 PSIS/AtoM-only feature(s) in search-discovery" \
  --body-file "heratio-search-discovery.md" \
  --label enhancement

gh issue create --repo ArchiveHeritageGroup/heratio \
  --title "Heratio parity: add 2 PSIS/AtoM-only feature(s) in rights-privacy-compliance" \
  --body-file "heratio-rights-privacy-compliance.md" \
  --label enhancement

gh issue create --repo ArchiveHeritageGroup/heratio \
  --title "Heratio parity: add 1 PSIS/AtoM-only feature(s) in ingest-preservation" \
  --body-file "heratio-ingest-preservation.md" \
  --label enhancement

gh issue create --repo ArchiveHeritageGroup/heratio \
  --title "Heratio parity: add 3 PSIS/AtoM-only feature(s) in ai" \
  --body-file "heratio-ai.md" \
  --label enhancement

gh issue create --repo ArchiveHeritageGroup/heratio \
  --title "Heratio parity: add 1 PSIS/AtoM-only feature(s) in research-public" \
  --body-file "heratio-research-public.md" \
  --label enhancement

gh issue create --repo ArchiveHeritageGroup/heratio \
  --title "Heratio parity: add 3 PSIS/AtoM-only feature(s) in api-integration" \
  --body-file "heratio-api-integration.md" \
  --label enhancement

gh issue create --repo ArchiveHeritageGroup/heratio \
  --title "Heratio parity: add 8 PSIS/AtoM-only feature(s) in sectors" \
  --body-file "heratio-sectors.md" \
  --label enhancement

gh issue create --repo ArchiveHeritageGroup/heratio \
  --title "Heratio parity: add 1 PSIS/AtoM-only feature(s) in workflow-reporting-misc" \
  --body-file "heratio-workflow-reporting-misc.md" \
  --label enhancement
