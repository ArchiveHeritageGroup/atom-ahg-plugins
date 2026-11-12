#!/bin/bash
# Anonymous GLAM browse count cache (12 Nov 2026). Two identical anonymous browses
# of a fresh filter combination: the first must count (X-Display-Counts: stored),
# the second must reuse it (cached), with the same result total.
#
#   bash ahgDisplayPlugin/testing/anon-count-cache-check.sh [base-url] [host-header]
#   e.g. bash .../anon-count-cache-check.sh https://psis.theahg.co.za
#        bash .../anon-count-cache-check.sh https://localhost rari.wits.ac.za
BASE=${1:-https://psis.theahg.co.za}
HOST=${2:-}
H=(); [ -n "$HOST" ] && H=(-H "Host: $HOST")
# A start date no one has asked for yet, so the first request cannot be cached.
URL="$BASE/index.php/glam/browse?topLevel=0&startDate=1$(printf '%03d' $((RANDOM % 1000)))-0$((RANDOM % 9 + 1))-1$((RANDOM % 9))"
T=$(mktemp -d); trap 'rm -rf "$T"' EXIT
get() { curl -skL "${H[@]}" -D "$1.h" -o "$1" -w '%{http_code} %{time_total}' "$URL"; }
src() { grep -i '^x-display-counts:' "$1.h" | tail -1 | awk '{print $2}' | tr -d '\r'; }
total() { grep -oE '[0-9][0-9,]* results' "$1" | head -1; }
read -r c1 t1 < <(get "$T/1"); read -r c2 t2 < <(get "$T/2")
echo "first:  $c1 in ${t1}s  counts=$(src "$T/1")  ($(total "$T/1"))"
echo "second: $c2 in ${t2}s  counts=$(src "$T/2")  ($(total "$T/2"))"
fail=0
[ "$c1" = 200 ] && [ "$c2" = 200 ] || { echo "FAIL: not 200"; fail=1; }
[ "$(src "$T/1")" = stored ] || { echo "FAIL: first request should count and store (APCu off on this site?)"; fail=1; }
[ "$(src "$T/2")" = cached ] || { echo "FAIL: second request should use the cache"; fail=1; }
[ "$(total "$T/1")" = "$(total "$T/2")" ] || { echo "FAIL: totals differ"; fail=1; }
[ $fail = 0 ] && echo PASS
exit $fail
