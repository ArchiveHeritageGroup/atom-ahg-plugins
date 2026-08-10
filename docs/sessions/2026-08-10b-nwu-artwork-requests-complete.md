# NWU artwork placements: approver settings and pre-due reminders

**Date:** 2026-08-10
**Release:** plugins v3.96.0
**Plugin:** ahgArtworkRequestPlugin 0.2.0 -> 0.3.0
**Issues:** [#275](https://github.com/ArchiveHeritageGroup/atom-extensions-catalog/issues/275) closed, [#278](https://github.com/ArchiveHeritageGroup/atom-extensions-catalog/issues/278) completed

The two items left outstanding on the NWU Art Across Campus work.

## Approver settings screen

`/artworkRequest/approvers`, linked from the review queue. Until now the rows in
`artwork_request_approver` went in by hand, so a gallery could not change who
reviews requests without someone at a database prompt - and an approver who left
kept getting the mail until somebody noticed.

Three decisions worth keeping:

- **Disabling is the default, not deleting.** An approver who steps away usually
  comes back, and the row carries which department they covered.
- **The duplicate check is explicit** rather than leaning on the unique key. The
  key is `(user_id, department)` and NULL never equals NULL in MySQL, so the
  general queue would have taken the same person twice with the constraint
  sitting right there looking like it was doing something.
- **Inactive rows are listed, not hidden.** A settings screen that conceals what
  it disabled invites the same person being added again.

`security.yml` gets `approvers: credentials: [[administrator]]`, tighter than the
review queue itself. Every action in the module now has an entry, since absence
fails open.

## Reminder before the due date

`--before-days` on the existing task, default 7, `0` disables. One cron entry
still, doing both passes.

Logged as **`reminded_due_soon`**, deliberately a different event from
`reminded`. Sharing one event would have let a courtesy nudge on the Monday
suppress the overdue chase on the Friday through the `--every-days` throttle -
the reminder that actually matters would be the one silently skipped.

## Verification

Both driven through a browser on the 2.10 VM.

Approver screen, every control:

    start          admin  Humanities  ...  On   Active
    notif toggled  admin  Humanities  ...  Off  Active
    disabled       admin  Humanities  ...  Off  Disabled
    dup add        1 row - back to On / Active     <- re-enables, does not duplicate
    unknown user   "No user matches that username or email address."
    console/5xx    0

Reminders, against a real request created through the UI and approved
(AR-2026-0002, due in three days):

    dry-run       AR-2026-0002 - due 2026-08-13 (in 3 day(s))
    send          1 due-soon reminder(s) sent
    send again    0                                <- throttle holds
    --before-days=0   courtesy nudge skipped
    --every-days=0    AR-2026-0001 still found, overdue by 40 days

Log shows both events side by side rather than one masking the other:

    req=2  reminded_due_soon   Due in 3 day(s); courtesy reminder sent
    req=1  reminded            Overdue by 40 day(s); reminder sent

**Not verified: delivery.** The VM has no MTA, so the send path runs and logs but
no mail leaves the box. Worth one live check before NWU relies on it.

## Found, not fixed

Both pre-existing in the request form, outside the scope of these two items:

- The form discards everything typed when validation fails. Submitting without a
  work returns the form with the dates blank.
- Works can only be attached by script - `object_ids[]` inputs are built by
  JavaScript, so there is no non-JS path to add one.

## Caveat on "complete"

This closes every item on the four issues raised from the NWU email. It does not
prove the issues captured everything the email asked for: there is no NWU
requirements document in the repo, no memory file and nothing in the scratchpad,
so the client's original text is not recorded anywhere checkable. Worth fixing -
the requirements should live somewhere durable.
