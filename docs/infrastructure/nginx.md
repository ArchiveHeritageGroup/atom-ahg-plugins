# nginx, for sites that want it

Nothing in this repository requires you to change your web server. The plugins
install into `plugins/`, are enabled from Admin > Plugins, and are served by
whatever already serves AtoM. If your nginx is working, leave it alone.

This page is for the two cases where a plugin has something to say about it: when
you put an IIIF image server behind the same hostname, and when AtoM lives
somewhere php-fpm cannot write.

## Proxying an IIIF image server

Only relevant if you run one. See `iiif-image-server.md` for whether you need to.

The image server listens on the loopback interface and nginx passes `/iiif/` to it,
so that everything arrives through the same hostname and the same TLS certificate as
the rest of the site. In the AtoM server block, before the main PHP handler:

    location /iiif/ {
        proxy_pass http://127.0.0.1:8182/iiif/;
        proxy_set_header Host              $host;
        proxy_set_header X-Real-IP         $remote_addr;
        proxy_set_header X-Forwarded-For   $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
        add_header Access-Control-Allow-Origin "*" always;
    }

The port is Cantaloupe's default. Change it to match whatever you run.

The `Host` header is the line worth reading twice. An authorisation delegate asks
AtoM whether a given file may be served, and it has to say which site is asking. One
image server commonly fronts several AtoM instances, where the same identifier means
a different file on each. Drop the header and the question lands on whichever vhost
answers by default, which knows nothing about the file and cheerfully allows it.

`Access-Control-Allow-Origin: *` is there because IIIF is designed to be consumed
across origins. If your manifests are only ever read by your own site, you can drop
it.

A worked example covering these routes plus the media endpoints ships in the runtime
at `config/nginx/extensions.conf`. Treat it as a reference, not a drop-in: it was
written for our own deployments and yours will differ.

## Serving derivatives directly

Optional, and only worth doing if image delivery is slow.

Derivatives, meaning the `_141` reference copies and `_142` thumbnails, are safe to
serve as static files. Masters are not. A rule that hands `/uploads/r/` to nginx
wholesale bypasses AtoM's access control entirely: drafts, embargoed material and
anything restricted by classification all become downloadable by anyone who can
guess the path.

If you do add such a rule, scope it to the derivative suffixes and let everything
else fall through to AtoM. One thing that catches people: repository logos live at
`/uploads/r/<slug>/conf/logo.png` and are not derivatives, so a suffix-based rule
must let them through or every institution's logo disappears.

## When AtoM lives under /usr/share/nginx

This one is not really nginx at all, but it looks like it, so it belongs here.

Debian and Ubuntu ship php-fpm with `ProtectSystem=full`, which mounts `/usr`
read-only for the worker process. An AtoM installed under `/usr/share/nginx/<site>`
therefore cannot write its own cache, logs, uploads or downloads from a web request.
The failure is confusing rather than obvious: pages return 500 with an empty body,
while the same code run from the command line works perfectly, because a cron job is
not started by that unit and is not subject to the restriction.

Grant the paths in a drop-in at
`/etc/systemd/system/php8.3-fpm.service.d/<site>-storage.conf`:

    [Service]
    ReadWritePaths=/usr/share/nginx/<site>/log
    ReadWritePaths=/usr/share/nginx/<site>/cache
    ReadWritePaths=/usr/share/nginx/<site>/uploads
    ReadWritePaths=/usr/share/nginx/<site>/downloads

Then `systemctl daemon-reload && systemctl restart php8.3-fpm`, and confirm with
`systemctl show php8.3-fpm | grep ReadWritePaths`.

An AtoM under `/var/www` never encounters this.

## Content Security Policy

AtoM sets its own policy from `config/app.yml` and generates a per-request nonce.
The plugins here comply with it: no inline style attributes, and every inline
`<style>` or `<script>` carries the nonce.

Worth knowing if you are adjusting the policy yourself. A nonce covers `<style>` and
`<script>` **elements** only, never a `style="..."` attribute, so no policy setting
will rescue one. And an instance sending `Content-Security-Policy-Report-Only`
reports violations while applying the styles anyway, which means a report-only
instance cannot tell you whether anything is actually compliant.
