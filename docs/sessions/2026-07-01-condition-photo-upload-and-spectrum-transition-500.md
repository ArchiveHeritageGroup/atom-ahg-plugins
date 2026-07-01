# 2026-07-01 — Condition photo upload UX/speed + Spectrum workflow transition 500 (v3.79.27)

## 1. Condition photos upload — "took time / no save button"
Not broken; the AJAX upload works but felt slow/unresponsive.
- **#1 loading state** (`ahgConditionPlugin/web/js/condition-photos.js`): the upload button
  now disables + shows a spinner ("Uploading…") during the POST and restores on error, so
  the (slow) upload+thumbnail step is obviously in progress. The button/form only render
  for authenticated admin/editor (`$canEdit`) — if it looked absent, the page was loading.
- **#2 faster thumbnail** (`ConditionAnnotationService::createThumbnail`): added an Imagick
  fast-path that decodes large JPEGs at reduced resolution via the `jpeg:size` hint
  (libjpeg shrink-on-load) — far faster and lower-memory than GD loading the full image.
  Falls back to the existing GD path on any error.
  ⚠️ **Imagick is NOT installed on this host** → the fast path is currently dormant (GD
  fallback). To activate the real speedup: `apt install php8.3-imagick && systemctl restart
  php8.3-fpm`.

## 2. Spectrum workflow transition — "Oops! An Error Occurred" (500)
`/spectrum/:slug/workflow/transition` (POST) 500'd on `submit_for_review` / `complete` /
`report` transitions when there was no explicit assignee. Root cause in
`sendTransitionEmailNotification` (spectrum actions): the admin-notify branch joined a
**non-existent `user_role_relation` table** (`->join('user_role_relation', …)->where(
'role_id',1)`), which fatalled the whole action. (GET to the URL just forward404s — the 500
is the POST path.)

Fix: administrators are users in the AtoM administrator ACL group (id **100**), mapped via
`acl_user_group`:
```php
$admins = DB::table('user')
    ->join('acl_user_group', 'user.id', '=', 'acl_user_group.user_id')
    ->where('acl_user_group.group_id', 100)
    ->where('user.id', '!=', $actingUserId)
    ->pluck('user.id')->toArray();
```
Verified: `user_role_relation` absent; `acl_user_group` present; admin user (448) is in
group 100. Only one occurrence of the bad table in the plugin.
`ahgSpectrumNotificationService::sendEmailNotification` already swallows its own errors, so
email/SMTP wasn't the fatal.

## Deploy
All lint clean; mirrored archive→archaeology (archive == PSIS); php-fpm restarted. Released
v3.79.27 (pushed origin/main + tag).
