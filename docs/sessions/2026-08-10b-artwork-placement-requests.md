# Artwork placement requests: approver settings, pre-due reminders, and a form that keeps what you type

**Date:** 2026-08-10
**Releases:** plugins v3.96.0, v3.96.2
**Plugin:** ahgArtworkRequestPlugin 0.2.0 -> 0.3.1
**Issues:** [#275](https://github.com/ArchiveHeritageGroup/atom-extensions-catalog/issues/275), [#278](https://github.com/ArchiveHeritageGroup/atom-extensions-catalog/issues/278), [#295](https://github.com/ArchiveHeritageGroup/atom-extensions-catalog/issues/295) - all closed

General GLAM functionality: staff at an institution borrow works from its own
collection for offices, boardrooms and shared spaces. The gallery is notified,
records what was asked, and keeps a register of what is out. Not specific to any
one client.

## Why this is not ahgLoanPlugin

`ahg_loan` models a loan to another institution - `partner_institution` is NOT
NULL, with couriers, customs states and loan fees beside it. A colleague hanging
a painting in their office is none of those, and an untrue value in a NOT NULL
column on every internal placement is how a collection database stops being
evidence of anything.

`artwork_request.loan_id` is the handoff instead, for when a placement genuinely
becomes an institutional loan.

The same constraint exists in Heratio's `ahg-loan` package
(`'partner_institution' => 'required|string|max:500'`), so a port there faces the
same decision.

## Approver settings screen

`/artworkRequest/approvers`, linked from the review queue. Until now the rows in
`artwork_request_approver` went in by hand, so an institution could not change
who reviews requests without someone at a database prompt - and an approver who
left kept getting the mail until somebody noticed.

- **Disabling is the default, not deleting.** An approver who steps away usually
  comes back, and the row carries which department they covered.
- **The duplicate check is explicit** rather than leaning on the unique key. The
  key is `(user_id, department)` and NULL never equals NULL in MySQL, so the
  general queue would have taken the same person twice with the constraint
  sitting right there looking like it was doing something.
- **Inactive rows are listed, not hidden.** A settings screen that conceals what
  it disabled invites the same person being added again.

`security.yml` gets `approvers: credentials: [[administrator]]`, tighter than the
review queue itself. Every action in the module has an entry, since absence fails
open.

## Reminder before the due date

`--before-days` on the existing task, default 7, `0` disables. One cron entry
still, doing both passes.

Logged as **`reminded_due_soon`**, deliberately a different event from
`reminded`. Sharing one event would have let a courtesy nudge on the Monday
suppress the overdue chase on the Friday through the `--every-days` throttle -
the reminder that actually matters would be the one silently skipped.

## The request form kept nothing (#295)

Two defects, both found while testing the above rather than reported.

**It discarded everything typed on a validation error.** The message was accurate
and the form beneath it was blank. Somebody who missed one field lost their
dates, placement and justification - and people do not retype a justification
carefully. They retype it badly, or they email the gallery instead, which is the
behaviour this plugin exists to replace. Ten fields now bind to the submission,
including the select and the chosen works.

**Works could only be attached with JavaScript.** `object_ids[]` inputs were
built by script, so with scripting unavailable the form submitted, failed with
"Choose at least one work", and offered no way to satisfy it. The page looked
usable and could not be finished. A plain `object_ids_manual` field is merged
server-side, and the picker is now `hidden` in the markup and revealed by the
script that operates it - only offered when the thing that makes it work has run.

Unknown ids are now reported rather than dropped silently, since a work
disappearing without explanation reads as the form eating input.

**The subtle part:** the script adopts server-rendered rows instead of assuming
an empty table. Without that they sit inert - no remove button, no availability,
invisible to the duplicate check - so re-adding a work would give two rows for it.

## Verification

Everything below driven through a browser on a clean 2.10 instance.

Approver screen, every control:

    start          admin  Humanities  ...  On   Active
    notif toggled  admin  Humanities  ...  Off  Active
    disabled       admin  Humanities  ...  Off  Disabled
    dup add        1 row - back to On / Active     <- re-enables, does not duplicate
    unknown user   "No user matches that username or email address."

Reminders, against a real request created through the UI and approved:

    dry-run       AR-2026-0002 - due in 3 day(s)
    send          1 due-soon reminder(s) sent
    send again    0                                <- throttle holds
    --before-days=0   courtesy nudge skipped
    --every-days=0    AR-2026-0001 still found, overdue by 40 days

Log shows both events side by side rather than one masking the other:

    req=2  reminded_due_soon   Due in 3 day(s); courtesy reminder sent
    req=1  reminded            Overdue by 40 day(s); reminder sent

Form, with scripting on and off:

    preserved      from, to, dept, purpose, justification, room, 1 work
    unknown id     "There is no record with id 999999."
    no-JS          picker hidden, record-ids field present, AR-2026-0003 submitted
    re-add same    1 row - server row adopted, not ignored
    console errors 0

**Not verified: delivery.** The VM has no MTA, so the send path runs and logs but
no mail leaves the box. Worth one live check on an instance that can send.

## Heratio

No `ahg-artwork-request` package exists there. Requirements and capability
mapping recorded in ArchiveHeritageGroup/heratio#1459.
