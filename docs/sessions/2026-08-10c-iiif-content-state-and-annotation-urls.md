# IIIF annotation identifiers, and a Content State API that had never worked

**Date:** 2026-08-10
**Releases:** plugins v3.95.4, v3.95.5
**Issues:** [#294](https://github.com/ArchiveHeritageGroup/atom-extensions-catalog/issues/294) opened, [#286](https://github.com/ArchiveHeritageGroup/atom-extensions-catalog/issues/286) closed

Started as one line: `IiifAnnotationService` fell back to a customer's domain. It
ended as six defects, five of which were only found because each fix was checked
by running the thing rather than reading it.

## The identifier bug (v3.95.4)

```php
$host = $_SERVER['HTTP_HOST'] ?? 'psis.theahg.co.za';
$this->baseUrl = "https://{$host}";
```

Wrong twice. The scheme was hardcoded, so an instance served over http minted
https identifiers, and a viewer on an http page then refuses to fetch them as
mixed content. And the fallback was one particular customer's domain, so any
request without HTTP_HOST - CLI, queued jobs, sync - minted identifiers pointing
at somebody else's site. An annotation `@id` is a durable identifier, so this
outlives the request that got it wrong.

Extracted to `IiifBaseUrl`, following the precedence `get_iiif_base_url()` in
IiifViewerHelper already used: `app_iiif_base_url` -> `app_siteBaseUrl` ->
derive from request -> `http://localhost`. Configuration first, because a site
that cares about durable identifiers should pin them rather than let them follow
whichever hostname a request arrived on. `localhost` is obviously local and
obviously wrong, which is the point - it cannot be mistaken for a real published
identifier the way another site's domain can.

Scheme detection honours `X-Forwarded-Proto` and port 443, since `HTTPS` is
unset in php-fpm when nginx terminates TLS without passing it.

## Then the endpoint that used it turned out to be dead (v3.95.5)

Fixing `ContentStateService` alone would have changed nothing: the action
constructing it passed an explicit base URL defaulting to the same customer
domain, so the service's own detection was unreachable code.

With that removed, `/iiif/content-state/encode` still answered HTTP 200 with a
zero-byte body. Four more defects underneath:

**1. A request method that does not exist.**

```php
$body = $request->getRawPostParameters();
```

`sfWebRequest` has no such method. Symfony does not fail on this: with no
listener for `request.method_not_found`, `sfRequest::__call()` treats the call as
a fluent setter and **returns a clone of the request**. So the body was an
`sfWebRequest`, `json_decode()` threw a TypeError on it, and the request finished
as 200 with nothing in it.

Worth remembering well beyond this endpoint: a mistyped request method in
Symfony 1.4 is not an error, it is an object that looks plausible until
something type-checks it.

**2.** `renderJson()` declared `: sfView` and returned `sfView::NONE`, which is
the string `'None'`. A second TypeError on the same path, latent behind the
first. It also never wrote a body - it assigned `$this->data` and returned NONE,
which tells Symfony to render nothing.

**3.** `getPdo()` opened its own connection from environment variables php-fpm
does not set, so it always fell through to `archive` / `root` / empty password -
one developer's local setup. Same defect class as the base URL: somebody's
environment baked in as everybody else's fallback. Now takes the framework's
configured credentials.

A separate handle is kept deliberately: this service reads rows as arrays while
the shared connection is FETCH_OBJ for everything else, so flipping the shared
default would have fixed this service and quietly broken every other caller.

**4.** `getPdo()` was `protected` and called from three places outside the class.

**5.** `iiif_saved_view` was queried in three places and **declared nowhere**.
The Content State API could not have worked on any install without someone
creating the table by hand.

## Verification

    encode, valid      200  {token, format: short, expiresAt, decodeUrl}
    decode             round-trips the state
    click_count        increments
    missing fields     400  "manifestId and canvasId are required"
    malformed JSON     400  "Invalid JSON body"
    annotations @id    http://<host>/...   (was https://)

Table created on the 2.10 VM only. No other database altered, so PSIS and
archaeology still lack `iiif_saved_view` until the installer runs there.

**Not proven:** the `X-Forwarded-Proto` branch, by direct invocation only. Sending
that header through the live endpoint did not change the result and the vhost
routes everything through index.php, so there was no way to see what PHP
received. Likely moot - `fastcgi_params` sets `HTTPS` on a real TLS vhost, and
that branch is verified.

## Annotations split out to #294

#286 closed. Investigating its last item found the endpoint is not missing at
all: `/iiif/annotations/object/:id` already returns a valid W3C AnnotationPage
with full CRUD behind it. What is missing is the `annotations` property on the
canvas, so no viewer is ever told it exists.

The real work is that there are **four annotation stores** across three plugins -
`iiif_annotation`, `ahg_web_annotation`, `research_annotation`,
`research_annotation_v2` - two of them serving independent W3C implementations at
different URL spaces. Which is authoritative is a decision, not a patch.

## Same pattern, still unfixed elsewhere

`psis.theahg.co.za` as a hardcoded fallback also appears in
`ahgAuthorityResolutionPlugin` (two writers), `ahgFederationPlugin` and
`ahgPrivacyPlugin`. Those mint provenance IRIs and federation URLs, so the same
durability argument applies.
