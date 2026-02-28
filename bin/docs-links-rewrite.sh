#!/usr/bin/env bash
set -euo pipefail

# Rewrite relative links after docs reorg.
# Usage:
#   bin/docs-links-rewrite.sh --dry-run
#   bin/docs-links-rewrite.sh --apply
#
# Updates occurrences like:
#   (ARCHITECTURE.md) -> (technical/ARCHITECTURE.md)
# inside docs/**/*.md only.
#
# No dependency on ripgrep; uses grep+perl.

DRY_RUN=1
while [[ $# -gt 0 ]]; do
  case "$1" in
    --apply) DRY_RUN=0; shift ;;
    --dry-run) DRY_RUN=1; shift ;;
    *) echo "Unknown arg: $1"; exit 2 ;;
  esac
done

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT"

if [[ ! -d docs ]]; then
  echo "ERROR: docs/ not found."
  exit 1
fi

declare -A MAP=(
  ["(ARCHITECTURE.md)"]="(technical/ARCHITECTURE.md)"
  ["(INSTALLATION.md)"]="(install/INSTALLATION.md)"
  ["(MEETING-NOTES.md)"]="(reference/MEETING-NOTES.md)"
  ["(ROADMAP.md)"]="(reference/ROADMAP.md)"
)

# gather docs markdown files
mapfile -d '' files < <(find docs -type f -name "*.md" -print0)

any_change=0
for f in "${files[@]}"; do
  changed=0
  for k in "${!MAP[@]}"; do
    v="${MAP[$k]}"
    if grep -qF -- "$k" "$f"; then
      changed=1
      any_change=1
      if [[ "$DRY_RUN" -eq 1 ]]; then
        echo "[DRY] $f: $k -> $v"
      else
        perl -pi -e "s/\Q$k\E/$v/g" "$f"
      fi
    fi
  done
  if [[ "$changed" -eq 1 && "$DRY_RUN" -eq 0 ]]; then
    git add "$f" >/dev/null 2>&1 || true
  fi
done

if [[ "$DRY_RUN" -eq 1 ]]; then
  echo "DRY RUN complete. Re-run with: bin/docs-links-rewrite.sh --apply"
else
  if [[ "$any_change" -eq 1 ]]; then
    echo "Apply complete (changes made)."
  else
    echo "Apply complete (no changes needed)."
  fi
fi
