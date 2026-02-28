#!/usr/bin/env bash
set -euo pipefail

# Organise atom-extensions-catalog docs into a consistent IA under /docs
# Usage:
#   bin/docs-reorg.sh --dry-run
#   bin/docs-reorg.sh --apply
#
# What it does:
# - Creates docs/ structure (getting-started, install, user-manual, technical, developer, operations, reference, adr, _inventory)
# - Moves root MD files into docs/ buckets (keeps root README.md)
# - Moves docs/technical/* stays in docs/technical/
# - Generates docs/README.md + section READMEs
# - Writes redirect stubs in old locations (optional; enabled by default)

DRY_RUN=1
WRITE_REDIRECTS=1

while [[ $# -gt 0 ]]; do
  case "$1" in
    --apply) DRY_RUN=0; shift ;;
    --dry-run) DRY_RUN=1; shift ;;
    --no-redirects) WRITE_REDIRECTS=0; shift ;;
    *) echo "Unknown arg: $1"; exit 2 ;;
  esac
done

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT"

if [[ ! -d ".git" ]]; then
  echo "ERROR: must run inside repo root (missing .git)."
  exit 1
fi

mkdirp() {
  if [[ "$DRY_RUN" -eq 1 ]]; then
    echo "[DRY] mkdir -p $*"
  else
    mkdir -p "$@"
  fi
}

gitmv() {
  local src="$1"
  local dst="$2"
  if [[ "$src" == "$dst" ]]; then return 0; fi
  if [[ ! -e "$src" ]]; then return 0; fi
  mkdirp "$(dirname "$dst")"
  if [[ "$DRY_RUN" -eq 1 ]]; then
    echo "[DRY] git mv $src -> $dst"
  else
    git mv "$src" "$dst"
  fi
}

writefile() {
  local path="$1"
  shift
  local content="$*"
  mkdirp "$(dirname "$path")"
  if [[ "$DRY_RUN" -eq 1 ]]; then
    echo "[DRY] write $path"
  else
    printf "%s\n" "$content" > "$path"
  fi
}

# --- Target IA ---
mkdirp docs/_inventory \
       docs/getting-started \
       docs/install \
       docs/user-manual \
       docs/technical \
       docs/developer \
       docs/operations \
       docs/reference \
       docs/adr

# --- Move root-level MD files into docs/ buckets (keep root README.md) ---
# You currently have: ARCHITECTURE.md, INSTALLATION.md, MEETING-NOTES.md, ROADMAP.md at repo root.
# Keep root README.md as repo landing page; create docs/README.md for docs landing page.
gitmv "ARCHITECTURE.md"   "docs/technical/ARCHITECTURE.md"
gitmv "INSTALLATION.md"   "docs/install/INSTALLATION.md"
gitmv "MEETING-NOTES.md"  "docs/reference/MEETING-NOTES.md"
gitmv "ROADMAP.md"        "docs/reference/ROADMAP.md"

# If there are other root *.md files (excluding README.md), move them to reference by default
shopt -s nullglob
for f in *.md; do
  [[ "$f" == "README.md" ]] && continue
  [[ "$f" == "ARCHITECTURE.md" || "$f" == "INSTALLATION.md" || "$f" == "MEETING-NOTES.md" || "$f" == "ROADMAP.md" ]] && continue
  gitmv "$f" "docs/reference/$f"
done
shopt -u nullglob

# --- Optional redirect stubs (so old links still resolve in GitHub) ---
# Note: Redirect stubs only make sense if you keep the old path; since we moved files, we can optionally create new small stubs
# at the old location with same name pointing to docs path. But that reintroduces root files.
# Default: keep repo root clean, so redirects are OFF unless you want them.
if [[ "$WRITE_REDIRECTS" -eq 1 ]]; then
  # Only create redirects if the file no longer exists at root AND you want compatibility.
  # You can turn off with --no-redirects.
  if [[ ! -f "ARCHITECTURE.md" ]]; then
    writefile "ARCHITECTURE.md" \
"# ARCHITECTURE (moved)

This document moved to: [docs/technical/ARCHITECTURE.md](docs/technical/ARCHITECTURE.md)
"
    if [[ "$DRY_RUN" -eq 0 ]]; then git add "ARCHITECTURE.md"; fi
  fi
  if [[ ! -f "INSTALLATION.md" ]]; then
    writefile "INSTALLATION.md" \
"# INSTALLATION (moved)

This document moved to: [docs/install/INSTALLATION.md](docs/install/INSTALLATION.md)
"
    if [[ "$DRY_RUN" -eq 0 ]]; then git add "INSTALLATION.md"; fi
  fi
  if [[ ! -f "MEETING-NOTES.md" ]]; then
    writefile "MEETING-NOTES.md" \
"# MEETING NOTES (moved)

This document moved to: [docs/reference/MEETING-NOTES.md](docs/reference/MEETING-NOTES.md)
"
    if [[ "$DRY_RUN" -eq 0 ]]; then git add "MEETING-NOTES.md"; fi
  fi
  if [[ ! -f "ROADMAP.md" ]]; then
    writefile "ROADMAP.md" \
"# ROADMAP (moved)

This document moved to: [docs/reference/ROADMAP.md](docs/reference/ROADMAP.md)
"
    if [[ "$DRY_RUN" -eq 0 ]]; then git add "ROADMAP.md"; fi
  fi
fi

# --- Generate docs landing README ---
writefile "docs/README.md" \
"# Documentation

This folder contains documentation for the **AHG Extensions Catalog** and related guidance.

## Sections

- [Getting Started](getting-started/README.md)
- [Installation](install/README.md)
- [User Manual](user-manual/README.md)
- [Technical](technical/README.md)
- [Developer](developer/README.md)
- [Operations](operations/README.md)
- [Reference](reference/README.md)
- [ADRs](adr/README.md)

## Inventory

- [_inventory](_inventory/) — committed runtime inventories (routes, schedules, config keys, etc.)
"

# --- Section READMEs (minimal but stable) ---
writefile "docs/getting-started/README.md" \
"# Getting Started

Start here for high-level orientation and quickstart material.
"
writefile "docs/install/README.md" \
"# Installation

Installation, setup, upgrades and deployment notes.
"
writefile "docs/user-manual/README.md" \
"# User Manual

Task-based guidance for end users (workflows, screens, roles).
"
writefile "docs/technical/README.md" \
"# Technical Manual

Architecture, internals and implementation notes.
"
writefile "docs/developer/README.md" \
"# Developer

Contribution, SDKs, extension development and integration notes.
"
writefile "docs/operations/README.md" \
"# Operations

Monitoring, backups, maintenance, cron/scheduler, and runbooks.
"
writefile "docs/reference/README.md" \
"# Reference

Static reference docs, meeting notes, roadmaps, compatibility matrices, etc.
"
writefile "docs/adr/README.md" \
"# Architecture Decision Records

Design decisions captured as ADRs.
"

# --- Index existing markdown in each section (append list) ---
# This builds a list of pages under each section.
index_section() {
  local sec="$1"
  local readme="docs/$sec/README.md"
  local tmp
  tmp="$(mktemp)"
  if [[ "$DRY_RUN" -eq 0 ]]; then
    cp "$readme" "$tmp"
  else
    # in dry-run, just show what we'd do
    tmp="/dev/null"
  fi

  local list=""
  while IFS= read -r -d '' f; do
    local rel="${f#docs/$sec/}"
    [[ "$rel" == "README.md" ]] && continue
    list+="- [${rel}](./${rel})"$'\n'
  done < <(find "docs/$sec" -maxdepth 2 -type f -name "*.md" -print0 2>/dev/null | sort -z)

  if [[ -n "$list" ]]; then
    if [[ "$DRY_RUN" -eq 1 ]]; then
      echo "[DRY] would append page index to $readme"
    else
      # remove any previous auto-index block
      perl -0777 -i -pe 's/\n## Pages\n.*?\n(?=\z)//s' "$readme" || true
      printf "\n## Pages\n\n%s\n" "$list" >> "$readme"
    fi
  fi
}

for s in getting-started install user-manual technical developer operations reference adr; do
  index_section "$s"
done

if [[ "$DRY_RUN" -eq 1 ]]; then
  echo "DRY RUN complete. Re-run with: bin/docs-reorg.sh --apply"
else
  echo "Apply complete. Review git status, then commit."
fi
