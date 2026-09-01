
Found 2026-09-01 chasing "Invalid export options", reported by Stefan against
archaeology. Reproducible on PSIS too, so it is not instance-specific.

## The measurement

Posted six fields, logged what arrived:

```
sent:     zzprobe, type, Type, xtype, typex, format
arrived:  zzprobe,       Type, xtype, typex, format
```

**Only the exact lowercase `type` is dropped.** `Type`, `xtype` and `typex` all
survive. As a GET parameter it works perfectly - `?type=actor` selects Actor.
The loss is POST-specific.

Symfony core is innocent: `sfWebRequest::initialize()` sets
`postParameters = $_POST` and unsets only `sf_method`. Not ModSecurity (absent),
not a PHP input filter (`filter.default = unsafe_raw`), and not any app code I
could find. **Mechanism still unidentified** - the behaviour is solid and
reproducible, the cause is not established.

This is the same family as the documented `name="action"` bug in CLAUDE.md
("Symfony reserves `action` parameter for routing"), and the house workaround is
the same: rename the posted field.

## What it broke

`clipboard/export` binds `$request->getPostParameters()` to a form whose `type`
validator is `required`. The field can never arrive, so **every clipboard export
failed for every user, always**, with a bare "Invalid export options."

A second consequence, unfixed: line 28 does
`$this->objectType = trim(strtolower($request->getParameter('type')))`, and the
switch falls through to `default: 'informationObject'`. So the Type dropdown has
never worked - an Actor or Repository export would silently export descriptions.
⚠️ That also means the log line `type=informationObject` proves nothing; it is
the default, not the submitted value.

## Shipped in two parts

### v3.106.58 - the diagnostic

Only the diagnostic: the failing field is now named in the JSON response and
written to `ahg_error_log` with the posted field NAMES beside the errors. That
pairing is what solves it - sfForm rejects a bound field with no validator as
"unexpected extra field", so a name in one list and absent from the other is the
whole answer.

### v3.106.59 - the fix

The posted field is renamed `type` -> `exportType` in the form definition, the
action's `$NAMES`, `processField`, and `render_field($form->exportType)` in the
theme template. GET `?type=` is still read as a fallback, because links across
the site use it and it was never affected.

Proven by the one signal that discriminates: `exportType=accession` returns 403
"You are not allowed to export this entity type" for an anonymous user, while
`informationObject` and `actor` return 200. That branch is only reachable if the
posted value actually arrived - before the fix every value fell through to the
default and accession would have returned 200.

⚠️ The empty-clipboard check sits BEFORE the accession credential check, so the
test only discriminates when slugs are supplied. Without them everything returns
"clipboard is empty" and the fix looks unproven.

## Reusable lesson

When a form rejects everything, log the posted field names, not just the
validation errors. Two lists that should match, side by side, beat any amount of
reasoning about which validator failed.
