#!/usr/bin/env bash
set -euo pipefail

# Sync runtime inventories into docs/_inventory for documentation grounding.
#
# Usage:
#   bin/inventory-sync.sh --framework /usr/share/nginx/archive/atom-framework
#   bin/inventory-sync.sh --framework /usr/share/nginx/archive/atom-framework --apply
#
# Default is DRY RUN (prints what it would do). Use --apply to write files.

FRAMEWORK=""
APPLY=0

while [[ $# -gt 0 ]]; do
  case "$1" in
    --framework) FRAMEWORK="${2:-}"; shift 2 ;;
    --apply) APPLY=1; shift ;;
    *) echo "Unknown arg: $1"; exit 2 ;;
  esac
done

if [[ -z "$FRAMEWORK" ]]; then
  echo "ERROR: --framework <path-to-atom-framework> is required"
  exit 1
fi
if [[ ! -d "$FRAMEWORK" ]]; then
  echo "ERROR: framework path not found: $FRAMEWORK"
  exit 1
fi

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
OUT="$ROOT/docs/_inventory"
TS="$(date -u +"%Y%m%dT%H%M%SZ")"

mkdir -p "$OUT"/{routes,schedule,config,env,queues,runtime,db}

run_or_echo() {
  local cmd="$*"
  if [[ "$APPLY" -eq 1 ]]; then
    bash -lc "$cmd"
  else
    echo "[DRY] $cmd"
  fi
}

echo "Inventory sync:"
echo "  framework: $FRAMEWORK"
echo "  output:    $OUT"
echo "  mode:      $([[ "$APPLY" -eq 1 ]] && echo APPLY || echo DRY)"
echo "  timestamp: $TS"

# Ensure we run artisan from framework root
ART="cd '$FRAMEWORK' && php artisan"

# Basic runtime
run_or_echo "$ART about > '$OUT/runtime/about-$TS.txt' 2>&1 || true"

# Routes (JSON + text)
run_or_echo "$ART route:list --json > '$OUT/routes/route-list-$TS.json' 2>&1 || true"
run_or_echo "$ART route:list > '$OUT/routes/route-list-$TS.txt' 2>&1 || true"

# Schedule
run_or_echo "$ART schedule:list > '$OUT/schedule/schedule-list-$TS.txt' 2>&1 || true"
run_or_echo "crontab -l > '$OUT/schedule/crontab-$TS.txt' 2>&1 || true"
run_or_echo "systemctl list-timers --all > '$OUT/schedule/systemd-timers-$TS.txt' 2>&1 || true"

# Config snapshots (keys/values; be careful—this can include secrets depending on config)
# Recommendation: only capture non-sensitive configs. Adjust list as needed.
for cfg in app auth cache database queue logging filesystems; do
  run_or_echo "$ART config:show $cfg > '$OUT/config/$cfg-$TS.txt' 2>&1 || true"
done

# Env keys only (no values) - uses .env.example if present, else .env (strips values)
if [[ -f "$FRAMEWORK/.env.example" ]]; then
  run_or_echo "sed -E 's/=.*$/=/' '$FRAMEWORK/.env.example' > '$OUT/env/env-keys-$TS.txt'"
else
  run_or_echo "sed -E 's/=.*$/=/' '$FRAMEWORK/.env' > '$OUT/env/env-keys-$TS.txt' 2>/dev/null || true"
fi

# Queue worker discovery (best-effort)
run_or_echo "ps auxww | grep -E 'queue:work|horizon|supervisord' | grep -v grep > '$OUT/queues/workers-ps-$TS.txt' || true"
run_or_echo "systemctl list-units --type=service | grep -E 'queue|horizon|supervisor' > '$OUT/queues/services-$TS.txt' || true"

# DB anchors (migrations list)
if [[ -d "$FRAMEWORK/database/migrations" ]]; then
  run_or_echo "ls -la '$FRAMEWORK/database/migrations' > '$OUT/db/migrations-ls-$TS.txt'"
fi

# Convenience "latest" symlinks (optional)
if [[ "$APPLY" -eq 1 ]]; then
  ln -sf "route-list-$TS.json" "$OUT/routes/route-list-latest.json" || true
  ln -sf "route-list-$TS.txt" "$OUT/routes/route-list-latest.txt" || true
  ln -sf "schedule-list-$TS.txt" "$OUT/schedule/schedule-list-latest.txt" || true
  ln -sf "about-$TS.txt" "$OUT/runtime/about-latest.txt" || true
  echo "Created latest symlinks."
fi

echo "Done."
