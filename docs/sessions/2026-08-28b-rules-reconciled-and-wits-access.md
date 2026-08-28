# Rules that contradicted the tooling, and an instance we can no longer reach

Date: 2026-08-28

## Compiling the rules found three that were wrong

Asked for every standing rule in one place, which meant reading the two instruction
files rather than recalling them. Three had drifted from practice, and one had
been contradicted by our own tooling for months.

**The base lock said one thing, the installer offered another.** The rule reads
"base files are permanently locked, zero tolerance, no exceptions". Meanwhile
`bin/install --with-base-patches` sat in our installer as a documented, supported
flag whose help text describes it as being for the servers we operate. It
overwrites 29 base files in one step.

Nobody decided to break the lock. Each time something urgent turned up in base, the
rule said stop and the flag offered a fix in seconds - nine times over seven months,
no single batch looking like 29 files. The lock was only ever as strong as the
easiest available path, and the easiest path was a supported feature.

That is now closed in the file itself, with the alternative written beside it:
where base is genuinely broken and unreachable to override, move the action into a
module base does not ship and repoint the route. Base untouched, bug fixed.

**A rule that had not been true for months.** "Never execute git commit or push
directly" describes a working pattern abandoned long ago; every release this week
went through a shown-command-then-Y gate. Rewritten as a gate rather than a
prohibition, with the two traps attached - the release script runs `git add -A`, and
the remote must be confirmed to have advanced rather than assumed.

**A contradiction between two true statements.** One rule named a single safe test
VM; a later instruction described a different instance as a test box. Both are now
recorded together, with the qualifier that matters: its records are expendable, its
495 GB of uploads are not, and writes are still asked for individually.

Both files were also normalised to plain hyphens - 74 long dashes between them. They
had been written using the punctuation their own rule forbids.

## The normalisation broke something, quietly

The first pass added a tidy-up regex to collapse doubled spaces around the replaced
character. It matched the *leading indentation* of every nested list item and
flattened all 21 of them.

Dash count: correct. Line count: unchanged. Table separators, code fences: intact.
Every check in the summary passed, and the file was wrong. Only asking specifically
about indentation found it.

Replace the character and nothing else. A cleanup pass that runs alongside a
substitution is a second edit wearing the first one's clothes.

## An instance that used to be reachable

One deployment target stopped answering: no ICMP, no ports, no DNS name. Its
neighbour on the same subnet answers on 443 from the same host, which rules out our
routing, our firewall and the gateway - the boundary is on their side.

It is confirmed up and serving from within its own network. So this is a reachability
change, not an outage, and no amount of tunnelling from our end substitutes for the
access that was withdrawn.

A second, separate blocker sits behind it: the account available there has no sudo
and both code trees are root-owned and not group-writable. Reachability and
authorisation are different problems, and solving the first would not have solved the
second. Worth separating explicitly when asking for access, or the request comes back
half-answered.

⚠️ The ask that actually works is three parts, not one: change the owner, make it
group-writable, AND add the account to that group. Changing the owner alone leaves
the account exactly as stuck, with different names on the files.
