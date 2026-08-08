# An IIIF image server, if you want one

The IIIF plugin and both viewers work without one. Manifests are generated, the
viewer loads, annotations and OCR export behave. What an image server adds is deep
zoom: tiles cut on demand, so a reader can move around a 400 megapixel scan without
downloading it.

If you already run Cantaloupe, IIPImage, Loris, serverless IIIF or anything else
that speaks the Image API, keep it. Point it at your uploads directory, make sure it
answers on the path your manifests advertise, and read the authorisation section
below. Nothing in this repository needs to change.

The rest of this page is for sites starting from nothing. It uses Cantaloupe because
that is what we run and can therefore describe honestly.

## Requirements

    Java            11 or later, for Cantaloupe
    Disk            a cache directory, sized to your collection

The viewers themselves need nothing. OpenSeadragon and Mirador are vendored inside
their plugins, so there is no CDN to whitelist and no npm step.

Separate from tiling, some of the IIIF plugin's media features shell out to tools
you may or may not want:

    ffmpeg, ffprobe   audio and video handling, transcription
    exiftool          embedded metadata
    tesseract         OCR

They are checked and reported at Admin > Media Settings, and the features that use
them stay out of the way when they are absent. Install them if you want those
features.

## Cantaloupe

Download a release from https://cantaloupe-project.github.io and run it bound to the
loopback interface. It should not be reachable from outside the host: requests
arrive through nginx, which is where TLS terminates and where the hostname needed
for authorisation is set.

In `cantaloupe.properties`:

    source.static = FilesystemSource
    FilesystemSource.lookup_strategy = ScriptLookupStrategy
    delegate_script.enabled = true
    delegate_script.pathname = /path/to/delegates.rb
    http.host = 127.0.0.1
    http.port = 8182
    endpoint.api.enabled = false

`endpoint.api.enabled = false` matters more than it looks. The control API can purge
the cache and read configuration, and it is not something to leave reachable.

Then proxy `/iiif/` to it. That configuration, including why the `Host` header is
not optional, is in `nginx.md`.

## Authorisation, which is the part that matters

An image server reads files straight off disk. It has no idea AtoM exists, let alone
that a description is a draft, that a record is embargoed, or that a file is
classified. Left open, every master under `uploads/r/` is retrievable through the
IIIF endpoint by anyone who can form the path.

Those paths are not secret. They appear in every manifest the site publishes.

Nginx rules protecting `/uploads/r/` do not help here, because this is a different
route to the same bytes. The check has to happen in the image server.

For Cantaloupe, the hook that does it ships with the IIIF plugin at
`config/cantaloupe/delegates.rb`. Copy it beside Cantaloupe, point
`delegate_script.pathname` at it, and edit `INSTANCE_PATHS` at the top: the key is
each site's hostname, the value the directory `uploads/` sits inside, with a
trailing slash. It is a map rather than a single path because one image server
usually fronts several instances, and the same identifier means a different file on
each.

For any other server, write the equivalent. It should call:

    GET https://<your-site>/iiif/auth/cantaloupe-check?identifier=<url-encoded-path>

and refuse unless the response body contains `{"allowed": true}`.

## Proving it refuses

Configuration that looks right and does nothing is the normal failure here, so
verify rather than assume:

    curl -sk -H "Host: <your-site>" \
      "https://127.0.0.1/iiif/auth/cantaloupe-check?identifier=<url-encoded-path>"

A master must answer `{"allowed": false}` to an anonymous caller. Then request that
same master through `/iiif/2/<identifier>/full/max/0/default.jpg` with no session and
confirm a 403. Restart the image server first, because delegates cache their
verdicts and you will otherwise be reading yesterday's answer.

`config/cantaloupe/README.md` in the IIIF plugin describes three ways this check can
appear configured while being completely inert. Each of them failed silently and
independently in practice, and any one of them leaves every master exposed. Read it
before putting an image server in front of a live collection.
