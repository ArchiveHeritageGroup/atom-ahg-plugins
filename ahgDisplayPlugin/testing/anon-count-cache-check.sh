#!/bin/bash
# Anonymous GLAM browse count cache (12 Nov 2026): the second identical anonymous
# browse must be faster and show the same result total as the first.
#
#   bash ahgDisplayPlugin/testing/anon-count-cache-check.sh [base-url] [host-header]
#   e.g. bash .../anon-count-cache-check.sh https://psis.theahg.co.za
#        bash .../anon-count-cache-check.sh https://localhost rari.wits.ac.za
BASE=${1:-https://psis.theahg.co.za}
HOST=${2:-}
H=(); [ -n "$HOST" ] && H=(-H "Host: $HOST")
URL="$BASE/index.php/informationobject/browse?topLod=0&view=table&check=$RANDOM$RANDOM"
fail=0
get() { curl -skL "${H[@]}" -o "$1" -w '%{http_code} %{time_total}' "$URL"; }
total() { grep -oE '[0-9][0-9,]* (results|records|descriptions)' "$1" | head -1; }
T=$(mktemp -d); trap 'rm -rf "$T"' EXIT
read -r c1 t1 < <(get "$T/1"); read -r c2 t2 < <(get "$T/2")
echo "first:  $c1 in ${t1}s  ($(total "$T/1"))"
echo "second: $c2 in ${t2}s  ($(total "$T/2"))"
[ "$c1" = 200 ] && [ "$c2" = 200 ] || { echo "FAIL: not 200"; fail=1; }
[ "$(total "$T/1")" = "$(total "$T/2")" ] || { echo "FAIL: totals differ"; fail=1; }
awk -v a="$t1" -v b="$t2" 'BEGIN{exit !(b < a)}' || echo "WARN: second request not faster (cache may be off, or the first was already cached)"
[ $fail = 0 ] && echo PASS
exit $fail
