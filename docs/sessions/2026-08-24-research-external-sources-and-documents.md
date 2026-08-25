# A table that could hold external sources, and an interface that never let anyone enter one

Date: 2026-08-24
Releases: atom-framework v2.18.23, atom-ahg-plugins v3.106.32

## What was already there

`research_project_resource` has carried `external_url`, `link_type` and `link_metadata`
since the table was created. `resource_type` has listed `external_link` and `document`
among its permitted values the whole time. Two templates read `external_url` and render
it when present.

Nothing ever wrote either one. The display half was built; the entry half never was.

That is worth naming as a shape, because it is easy to misread in the other direction:
finding the columns and the rendering code makes the feature look present, and a quick
check for "does the schema support external links" answers yes. It supported them. No
route could produce one.

## What was built

Two entry paths, both landing in the same table and the same panel on the project page.

**External source** - URL, optional title, source type drawn from the values already in
the column comment (`academic`, `archive`, `database`, `government`, `website`,
`social_media`, `other`), optional tags. No new modelling; the existing columns were
the right shape.

**Document** - stored under `uploads/research/project-documents/<project_id>/`, with four
new columns: `file_path`, `file_name`, `file_size`, `mime_type`. The stored filename is
generated; the original is kept only for display and download.

## The size limit, and the failure it prevents

The ceiling is admin-settable (`research_document_max_mb`, default 20) because the right
value is a local policy question - disk, what researchers actually deposit, what the
server permits.

It is then clamped to `min(configured, upload_max_filesize, post_max_size)`, and that
clamp is the part worth carrying forward.

A form advertising 20 MB on a server whose `post_max_size` is 8 MB fails in the worst
available way. PHP discards the request body before any application code runs. The action
sees an empty `$_POST`, and the obvious response - "choose a file" - sends the researcher
looking at their file picker when the problem is server configuration they cannot see.
The upload action detects that signature (a POST that arrived carrying nothing) and
reports the size limit instead.

One computed figure drives the help text, the form's `MAX_FILE_SIZE`, and the server-side
check, so the three cannot drift apart.

## Decisions

- Validation goes through the framework's `FileValidationService` - extension allowlist,
  size, MIME from magic bytes rather than the browser's claim. Hand-rolling it would have
  made a sixth copy of that logic in this repository.
- URLs are restricted to `http` and `https`. `filter_var(..., FILTER_VALIDATE_URL)` alone
  accepts `javascript:` and `data:`, and these values are rendered as an `href`.
- Removal deletes the file before the row, confined to `uploads/research` by `realpath`.
  The other order leaves a row pointing at nothing; worse, a file with no row is invisible
  and accumulates.
- Resource ownership is re-checked against the project rather than trusted from the
  request, so a guessed id cannot reach another project's resource.
- Access control reuses `ProjectService::getProject($id, $researcherId)` - the same gate
  the project view uses, rather than a second opinion about who may edit what.

## Verified, and not

Routes resolve and reach the authentication gate. The control that makes this meaningful
is the fourth line - a deliberately nonexistent route under the same prefix, which returns
the 404 page rather than the login page:

```
/research/project/1/link                    200  user login
/research/project/1/document                200  user login
/research/project/1/resource/remove         200  user login
/research/project/1/definitely-not-a-route  404  admin error
```

Schema applied, upload directory created and owned by the web user, error log clean.

**The round trip is untested.** Upload writing a row with the right size and MIME, the
download link serving the file, removal deleting both row and file - all of that needs an
authenticated researcher session this work did not have. The oversize branch has been
reasoned about but not observed firing. Whoever picks this up should start there rather
than assuming the routes resolving means the feature works; those are different claims,
and this repository has a history of the first being mistaken for the second.

## Not deployed

Standing rule: the archaeology instance is touched only on explicit instruction. This is
released to git and applied to the local instance only.
