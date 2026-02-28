#!/usr/bin/env bash
set -euo pipefail

# Inventory exporter for psis.theahg.co.za (AtoM Symfony + Heratio nginx routing)
# Writes to: atom-extensions-catalog/docs/_inventory/psis/<timestamp>/
#
# Usage:
#   bin/inventory-sync-psis.sh --archive-root /usr/share/nginx/archive --apply
# Optional DB plugin dump (NO secrets committed unless you choose):
#   bin/inventory-sync-psis.sh --archive-root /usr/share/nginx/archive --apply \
#     --mysql-defaults /root/.my.cnf --db archive
#
# Default is DRY mode.

ARCHIVE_ROOT=""
APPLY=0
MYSQL_DEFAULTS=""
DB_NAME=""

while [[ $# -gt 0 ]]; do
  case "$1" in
    --archive-root) ARCHIVE_ROOT="${2:-}"; shift 2 ;;
    --apply) APPLY=1; shift ;;
    --mysql-defaults) MYSQL_DEFAULTS="${2:-}"; shift 2 ;;
    --db) DB_NAME="${2:-}"; shift 2 ;;
    *) echo "Unknown arg: $1"; exit 2 ;;
  esac
done

if [[ -z "$ARCHIVE_ROOT" ]]; then
  echo "ERROR: --archive-root is required (e.g. /usr/share/nginx/archive)"
  exit 1
fi
if [[ ! -d "$ARCHIVE_ROOT" ]]; then
  echo "ERROR: archive root not found: $ARCHIVE_ROOT"
  exit 1
fi
if [[ ! -f "$ARCHIVE_ROOT/symfony" ]]; then
  echo "ERROR: Symfony CLI not found: $ARCHIVE_ROOT/symfony"
  exit 1
fi

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
BASE="$ROOT/docs/_inventory/psis"
TS="$(date -u +"%Y%m%dT%H%M%SZ")"
OUT="$BASE/$TS"

mkdir -p "$OUT"/{nginx,heratio,symfony,runtime,fs,db}

run_or_echo() {
  local cmd="$*"
  if [[ "$APPLY" -eq 1 ]]; then
    bash -lc "$cmd"
  else
    echo "[DRY] $cmd"
  fi
}

echo "psis inventory sync:"
echo "  archive-root: $ARCHIVE_ROOT"
echo "  output:       $OUT"
echo "  mode:         $([[ "$APPLY" -eq 1 ]] && echo APPLY || echo DRY)"
echo "  timestamp:    $TS"

# --- runtime ---
run_or_echo "php -v > '$OUT/runtime/php.txt' 2>&1 || true"
run_or_echo "uname -a > '$OUT/runtime/uname.txt' 2>&1 || true"
run_or_echo "lsb_release -a > '$OUT/runtime/os.txt' 2>&1 || true"

# --- nginx truth (extract only psis vhost + heratio include) ---
run_or_echo "sudo nginx -T > '$OUT/nginx/nginx-T.txt' 2>&1 || true"
run_or_echo "grep -nE '^# configuration file ' '$OUT/nginx/nginx-T.txt' > '$OUT/nginx/config-files.txt' || true"

# Extract the psis vhost file directly if present
PSIS_VHOST="/etc/nginx/sites-enabled/psis.theahg.co.za.conf"
if [[ -f "$PSIS_VHOST" ]]; then
  run_or_echo "sudo sed -n '1,260p' '$PSIS_VHOST' > '$OUT/nginx/psis.vhost.conf' 2>&1 || true"
else
  # fallback: extract from nginx -T dump by matching server_name
  run_or_echo "awk 'BEGIN{p=0} /server_name psis\\.theahg\\.co\\.za;/{p=1} p{print} /}\\s*$/&&p{exit}' '$OUT/nginx/nginx-T.txt' > '$OUT/nginx/psis.vhost.conf' 2>&1 || true"
fi

HERATIO_CONF=\"$ARCHIVE_ROOT/atom-framework/config/nginx/heratio.conf\"
if [[ -f "$ARCHIVE_ROOT/atom-framework/config/nginx/heratio.conf" ]]; then
  run_or_echo "sed -n '1,260p' '$ARCHIVE_ROOT/atom-framework/config/nginx/heratio.conf' > '$OUT/heratio/heratio.nginx.conf' 2>&1 || true"
fi
if [[ -f "$ARCHIVE_ROOT/atom-framework/config/nginx/extensions.conf" ]]; then
  run_or_echo "sed -n '1,260p' '$ARCHIVE_ROOT/atom-framework/config/nginx/extensions.conf' > '$OUT/heratio/extensions.nginx.conf' 2>&1 || true"
fi

# --- filesystem flags relevant to heratio/atom ---
run_or_echo "ls -la '$ARCHIVE_ROOT' > '$OUT/fs/archive-root.ls.txt' 2>&1 || true"
run_or_echo "find '$ARCHIVE_ROOT' -maxdepth 2 -type f -name '.heratio_enabled' -print -exec ls -la {} \\; > '$OUT/fs/heratio-enabled.txt' 2>&1 || true"
run_or_echo "find '$ARCHIVE_ROOT' -maxdepth 3 -type f -name 'settings.yml' -o -name 'databases.yml' -o -name 'app.yml' > '$OUT/fs/symfony-config-files.txt' 2>&1 || true"

# --- symfony app inventory (tasks + routes-like output) ---
SYM="cd '$ARCHIVE_ROOT' && php symfony"
run_or_echo "$SYM -V > '$OUT/symfony/symfony-version.txt' 2>&1 || true"
run_or_echo "$SYM list > '$OUT/symfony/task-list.txt' 2>&1 || true"
# Common tasks we care about (best-effort; some may not exist)
run_or_echo "$SYM cache:clear > '$OUT/symfony/cache-clear.txt' 2>&1 || true"
run_or_echo "$SYM search:status > '$OUT/symfony/search-status.txt' 2>&1 || true"
run_or_echo "$SYM search:populate --help > '$OUT/symfony/search-populate-help.txt' 2>&1 || true"

# --- Optional: DB plugin list (safe content, but requires mysql access) ---
if [[ -n "$MYSQL_DEFAULTS" && -n "$DB_NAME" ]]; then
  if [[ ! -f "$MYSQL_DEFAULTS" ]]; then
    echo "WARN: mysql defaults file not found: $MYSQL_DEFAULTS (skipping db dump)"
  else
    run_or_echo "mysql --defaults-extra-file='$MYSQL_DEFAULTS' '$DB_NAME' -e \
      \"SELECT id, name, class_name, enabled, created_at, updated_at FROM atom_plugin ORDER BY enabled DESC, name ASC;\" \
      > '$OUT/db/atom_plugin.txt' 2>&1 || true"
  fi
else
  if [[ "$APPLY" -eq 1 ]]; then
    echo "NOTE: DB plugin inventory skipped (provide --mysql-defaults and --db if desired)" \
      > "$OUT/db/README.txt"
  fi
fi

# --- latest pointer ---
if [[ "$APPLY" -eq 1 ]]; then
  ln -sfn "$TS" "$BASE/latest"
  echo "Updated: $BASE/latest -> $TS"
fi

echo "Done."
