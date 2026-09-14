# TypeError on /informationobject/inventory, and the first real CallHub ticket (14 September 2026)

atom-ahg-plugins v3.106.97, PSIS. A 500 on the inventory page, broken since the action
shipped rather than newly regressed, found only because someone typed the URL.

## The fault

    TypeError: Elastica\Query\Terms::__construct(): Argument #2 ($values)
    must be of type array, null given

GET https://psis.theahg.co.za/flower-2/informationobject/inventory, 13 September 21:27.
`ahg_error_log` #33536, signature `e36910bedf9de830d90bebe667b32fbb`.

## Root cause

`getLevels()` in `ahgCorePlugin/modules/informationobject/actions/inventoryAction.class.php`
read the `inventory_levels` setting and returned null - a bare `return;` - on three
separate paths: no setting row, a failed unserialize, and a value that was empty or not
an array.

`inventory_levels` has never been set on PSIS. So null reached `getResults()`, which
passes it straight into `Elastica\Query\Terms`, whose second argument is typed `array`.
On PHP 8 that is a TypeError, not an empty query.

## Why it stayed hidden, which is the more useful half

The action has two entry points and only one was guarded.

`showInventory()` tested the result with `empty()`, so it tolerated null and hid the
inventory tab - correct behaviour for an unconfigured instance. `execute()` had no such
test. The tab was therefore invisible in the interface while the URL still routed, so
the page was reachable only by typing it. Nobody had, until Sunday evening.

This is a general shape worth recognising: a nullable accessor with one tolerant caller
and one intolerant caller is a latent 500 that presents as a working feature. The
tolerant caller is what suppresses the evidence.

## The fix

`getLevels(): array` - returns an array on every path, so the null cannot exist rather
than being caught downstream. Root cause in the shared function, not a guard at the
call site.

Every caller checked first; there are two, both in the same file, and neither
distinguished null from empty in a way this breaks. `empty([])` is still true for
`showInventory()`, and a Terms query over an empty list matches nothing - the honest
answer when no levels are configured. The page now renders empty instead of 500ing.

Verified live: HTTP 200, and no new error row written by the request.

## First real ticket through the CallHub path

This error was the first to exercise the alerting built on 11-12 September, and it
behaved as designed on live data rather than in a test:

    22:07 13 Sep  raised psis:e36910bedf9d:2026-W37   1 raised
    23:07 13 Sep  0 raised, 1 already sent this period   <- suppression working
    00:07 14 Sep  raised psis:e36910bedf9d:2026-W38   1 raised
    01:07 onward  0 raised, 1 already sent this period

Thirty preceding ticks read `0 raised, 0 already sent, 0 failed` - the healthy empty
state, which is worth recognising as success rather than as nothing happening.

**A wrinkle in the period design.** The ISO week rolled at midnight, so the same fault
re-ticketed two hours after the first ticket. That is the specification working - a
recurrence in a new week is treated as new - but any fault arriving late on a Sunday
will produce two tickets in quick succession. A rolling seven days instead of a calendar
week would remove it. Not changed; flagged for Johan.

## The cron failure that preceded this, and its real cause

The hourly drain failed silently from 11 Sep 11:07 until 12 Sep 16:07. Every tick fired,
every tick produced output cron could not deliver (it mails to "www-data", which the
Graph shim rejects as an unresolvable recipient), and the log file was never created.

Cause: my own verification. Running the cron command once as `johanpiet` to check it
resolved left `/run/lock/psis-callhub-alerts.lock` owned `johanpiet:johanpiet`. This host
has `fs.protected_regular = 2`, which blocks `O_CREAT` opens of an existing file you do
not own in a world-writable sticky directory - and `/run/lock` is 1777. `flock` opens its
lock file with `O_CREAT`, so www-data was refused. Deleting the lock fixed it on the next
tick.

Two lessons, both general:

**A test can measure the wrong thing and then outrank the evidence.** I tested whether
flock needs write permission by flocking a file in `/var/log/atom`, which is 755 and NOT
sticky, so `protected_regular` never applied. The test passed, I retracted a correct
diagnosis on its authority, and the timeline had been right all along. When a test
contradicts a timeline, suspect the test.

**Never run a locked cron command as another user to "verify" it.** Whoever creates the
lock owns it. The verification is what breaks the job.

Fixed in the artefact: the redirect now sits OUTSIDE `flock -c`. With it inside, it covers
only the php command and flock's own errors escape to cron - which is exactly why "never
fired" could not be distinguished from "failed before the redirect".

Still open: the entry mails to `www-data` on any output, which is undeliverable. A job
whose only failure channel cannot be delivered fails invisibly. MAILTO is Johan's call.

## Error log

Eight open rows resolved: this TypeError plus seven `CSRF token missing (mode: enforce)`
warnings, latest 12 September. Those are CSRF enforcement working and are deliberately
never ticketed. `ahg_error_alert` left alone - its rows record what has already been sent,
and clearing them would let the same fault re-ticket.
