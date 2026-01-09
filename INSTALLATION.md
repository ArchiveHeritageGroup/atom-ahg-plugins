# Installation Guide

Complete guide for installing the AtoM Extension Framework and extensions.

---

## Prerequisites

| Component | Minimum | Recommended |
|-----------|---------|-------------|
| AtoM | 2.8.0 | 2.10.1+ |
| PHP | 8.1 | 8.3 |
| MySQL | 8.0 | 8.0+ |
| Composer | 2.x | Latest |

---

## Install Framework

The `atom-framework` is **required** for all extensions.

### Step 1: Clone Framework
```bash
cd /usr/share/nginx/atom
git clone https://github.com/ArchiveHeritageGroup/atom-framework.git
```

### Step 2: Install Dependencies
```bash
cd atom-framework
composer install --no-dev
```

### Step 3: Run Framework Installer
```bash
php bin/atom framework:install
```

This will:
- ✅ Create all required database tables
- ✅ Run pending migrations
- ✅ Configure default settings

### Step 4: Verify
```bash
php bin/atom extension:list
php bin/atom migrate status
```

---

## Install Extensions

### Discover Available Extensions
```bash
php bin/atom extension:discover
```

### Install from Local or GitHub
```bash
# Install by machine name
php bin/atom extension:install arAHGThemeB5Plugin

# Enable the extension
php bin/atom extension:enable arAHGThemeB5Plugin

# Clear Symfony cache
php symfony cc
```

### Install Multiple Extensions
```bash
# Theme + GLAM sectors
php bin/atom extension:install arAHGThemeB5Plugin
php bin/atom extension:install sfMuseumPlugin
php bin/atom extension:install ahgDAMPlugin

# Enable all
php bin/atom extension:enable arAHGThemeB5Plugin
php bin/atom extension:enable sfMuseumPlugin
php bin/atom extension:enable ahgDAMPlugin

php symfony cc
```

---

## Upgrading

When updating the framework:
```bash
cd /usr/share/nginx/atom/atom-framework
git pull
php bin/atom migrate run
```

This runs only new migrations - already-executed migrations are tracked and skipped.

### Check Migration Status
```bash
php bin/atom migrate status
```

---

## CLI Reference

| Command | Description |
|---------|-------------|
| `php bin/atom framework:install` | Install/upgrade framework |
| `php bin/atom migrate run` | Run pending migrations |
| `php bin/atom migrate status` | Show migration status |
| `php bin/atom extension:discover` | Find available extensions |
| `php bin/atom extension:list` | List installed extensions |
| `php bin/atom extension:install <name>` | Install extension |
| `php bin/atom extension:enable <name>` | Enable extension |
| `php bin/atom extension:disable <name>` | Disable extension |
| `php bin/atom extension:uninstall <name>` | Uninstall (30-day grace) |
| `php bin/atom extension:audit` | View audit log |


---

---

## NGINX 443 Sample
This example listens for connections on port 443 using https with encryption.

**##
# Access to Memory (AtoM) – GENERIC vhost WITH IIIF SUPPORT
# Updated: 2025-01-08 - Added bot protection
##

# PHP upstream (adjust socket if your server differs)
upstream atom {
    # Common options:
    # server unix:/run/php-fpm.atom.sock;
    # server unix:/run/php/php8.3-fpm.sock;
    server unix:/run/php/php8.3-fpm.sock;
}

# =========================================================
# HTTP (80) - default catch-all
# - Allow ACME challenges
# - Redirect everything else to HTTPS
# =========================================================
server {
    listen 80 default_server;
    listen [::]:80 default_server;

    server_name _;

    # Allow Let's Encrypt renewal over HTTP
    location ^~ /.well-known/acme-challenge/ {
        root /var/www/letsencrypt;
        try_files $uri =404;
    }

    return 301 https://$host$request_uri;
}

# =========================================================
# HTTPS (443) - default catch-all
# =========================================================
server {
    listen 443 ssl default_server;
    listen [::]:443 ssl default_server;

    server_name _;

    client_max_body_size 2G;

    root /usr/share/nginx/atom;
    index index.php index.html;

    ssl_certificate     /etc/letsencrypt/live/xxxxxxx/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/xxxxxxx/privkey.pem;
    ssl_trusted_certificate /etc/letsencrypt/live/xxxxxxx/chain.pem;

    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers 'EECDH+AESGCM:EDH+AESGCM:AES256+EECDH:AES256+EDH';
    ssl_session_cache shared:SSL:10m;
    ssl_session_timeout 10m;
    ssl_prefer_server_ciphers on;

    access_log /var/log/nginx/atom_access.log;
    error_log  /var/log/nginx/atom_error.log;

    # ======================================
    # SECURITY HEADERS
    # ======================================
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header Referrer-Policy "strict-origin-when-cross-origin" always;

    # ======================================
    # BOT PROTECTION (uses maps from conf.d)
    # ======================================
    if ($bad_bot) { return 444; }
    if ($blocked_ip) { return 403; }

    # ======================================
    # BLOCK COMMON PHP EXPLOIT SCANNERS
    # ======================================
    location ~* (eval-stdin\.php|phpunit|pearcmd|thinkphp|invokefunction|\.env|\.git|shell|cmd) {
        return 444;
    }

    # Block attempts to include remote files
    if ($query_string ~* "allow_url_include" ) { return 444; }
    if ($query_string ~* "auto_prepend_file" ) { return 444; }

    # ======================================
    # BLOCK PATH TRAVERSAL
    # ======================================
    if ($request_uri ~ "\.\./") { return 444; }
    if ($request_uri ~ "\.%2e%2e/") { return 444; }

    # ======================================
    # RATE-LIMITED BROWSE ENDPOINTS (bot targets)
    # ======================================

    location ~ ^/index\.php/glam/browse {
        limit_req zone=browse_limit burst=10 nodelay;
        limit_conn conn_limit 5;

        include fastcgi_params;
        fastcgi_split_path_info ^(.+?\.php)(/.*)$;
        fastcgi_pass atom;
        fastcgi_param PATH_INFO $fastcgi_path_info;
        fastcgi_param PATH_TRANSLATED $document_root$fastcgi_path_info;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        fastcgi_param SCRIPT_NAME $fastcgi_script_name;
        fastcgi_index index.php;
        fastcgi_read_timeout 300;
        fastcgi_buffer_size 128k;
        fastcgi_buffers 4 256k;
        fastcgi_busy_buffers_size 256k;
    }

    location ~ ^/index\.php/informationobject/browse {
        limit_req zone=browse_limit burst=10 nodelay;
        limit_conn conn_limit 5;

        include fastcgi_params;
        fastcgi_split_path_info ^(.+?\.php)(/.*)$;
        fastcgi_pass atom;
        fastcgi_param PATH_INFO $fastcgi_path_info;
        fastcgi_param PATH_TRANSLATED $document_root$fastcgi_path_info;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        fastcgi_param SCRIPT_NAME $fastcgi_script_name;
        fastcgi_index index.php;
        fastcgi_read_timeout 300;
        fastcgi_buffer_size 128k;
        fastcgi_buffers 4 256k;
        fastcgi_busy_buffers_size 256k;
    }

    location ~ ^/index\.php/.*/search {
        limit_req zone=search_limit burst=15 nodelay;
        limit_conn conn_limit 10;

        include fastcgi_params;
        fastcgi_split_path_info ^(.+?\.php)(/.*)$;
        fastcgi_pass atom;
        fastcgi_param PATH_INFO $fastcgi_path_info;
        fastcgi_param PATH_TRANSLATED $document_root$fastcgi_path_info;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        fastcgi_param SCRIPT_NAME $fastcgi_script_name;
        fastcgi_index index.php;
        fastcgi_read_timeout 300;
        fastcgi_buffer_size 128k;
        fastcgi_buffers 4 256k;
        fastcgi_busy_buffers_size 256k;
    }

    # ======================================
    # RiC EXPLORER & APIs
    # ======================================

    location ^~ /ric/ {
        alias /usr/share/nginx/atom/web/ric/;
        index index.html;
        try_files $uri $uri/ =404;
    }

    location ^~ /api/ric/ {
        proxy_pass http://127.0.0.1:5001/api/;
        proxy_http_version 1.1;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_connect_timeout 30;
        proxy_read_timeout 30;
    }

    location ^~ /api/provenance/ {
        proxy_pass http://127.0.0.1:5003/api/provenance/;
        proxy_http_version 1.1;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
    }

    location ^~ /api/editor/ {
        proxy_pass http://127.0.0.1:5002/api/editor/;
        proxy_http_version 1.1;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
    }

    location ^~ /sparql/ {
        proxy_pass http://192.168.0.112:3030/ric/;
        proxy_http_version 1.1;
        proxy_set_header Host $host;
        add_header Access-Control-Allow-Origin "*" always;
        add_header Access-Control-Allow-Methods "GET, POST, OPTIONS" always;
        add_header Access-Control-Allow-Headers "Content-Type" always;
    }

    location ^~ /ric-dashboard/ {
        alias /usr/share/nginx/atom/web/ric-dashboard/;
        index index.php index.html;

        location ~ \.php$ {
            fastcgi_pass atom;
            fastcgi_param SCRIPT_FILENAME $request_filename;
            include fastcgi_params;
        }
    }

    location ^~ /ric-provenance/ {
        alias /usr/share/nginx/atom/web/ric-provenance/;
        index index.html;
        try_files $uri $uri/ =404;
    }

    location ^~ /ric-editor/ {
        alias /usr/share/nginx/atom/web/ric-editor/;
        index index.html;
        try_files $uri $uri/ =404;
    }

    # ======================================
    # MEDIA API ROUTES
    # ======================================
    location ~ ^/media/ {
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME /usr/share/nginx/atom/atom-framework/src/Extensions/Media/public/routes.php;
        fastcgi_pass atom;
    }

    location ~ ^/media/(metadata|extract|waveform)/([0-9]+)$ {
        fastcgi_pass atom;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME /usr/share/nginx/atom/atom-framework/src/Extensions/IiifViewer/public/router.php;
        add_header Access-Control-Allow-Origin * always;
    }

    location ~ ^/media/(transcription|transcribe)/([0-9]+)(.*)$ {
        fastcgi_pass atom;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME /usr/share/nginx/atom/atom-framework/src/Extensions/IiifViewer/public/router.php;
        fastcgi_read_timeout 600;
        add_header Access-Control-Allow-Origin * always;
    }

    location ~ ^/media/search {
        fastcgi_pass atom;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME /usr/share/nginx/atom/atom-framework/src/Extensions/IiifViewer/public/router.php;
        add_header Access-Control-Allow-Origin * always;
    }

    # ======================================
    # PHP HANDLING
    # ======================================
    location ~ ^/(index|qubit_dev)\.php(/|$) {
        include fastcgi_params;
        fastcgi_split_path_info ^(.+?\.php)(/.*)$;
        fastcgi_pass atom;
        fastcgi_param PATH_INFO $fastcgi_path_info;
        fastcgi_param PATH_TRANSLATED $document_root$fastcgi_path_info;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        fastcgi_param SCRIPT_NAME $fastcgi_script_name;
        fastcgi_index index.php;
        fastcgi_read_timeout 3600;
        fastcgi_buffer_size 128k;
        fastcgi_buffers 4 256k;
        fastcgi_busy_buffers_size 256k;
    }

    # ======================================
    # DIGITAL OBJECT VIEWERS
    # ======================================

    location ~* /index\.php/.*/digitalobject/ {
        add_header Content-Security-Policy "default-src 'self' data: blob:;
                                            script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.jsdelivr.net;
                                            style-src 'self' 'unsafe-inline';
                                            img-src 'self' data: blob: https:;" always;
    }

    location /zoompan/ {
        add_header Content-Security-Policy "default-src 'self' data: blob:;
                                            script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.jsdelivr.net;
                                            style-src 'self' 'unsafe-inline';
                                            img-src 'self' data: blob: https:;" always;
        try_files $uri $uri/ /index.php$args;
    }

    location = /3D-image.php {
        root /usr/share/nginx/atom;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root/3D-image.php;
        fastcgi_pass atom;
        add_header Content-Security-Policy "default-src 'self' data: blob:;
                                            script-src 'self' 'unsafe-inline';
                                            img-src 'self' data: blob:;
                                            style-src 'self' 'unsafe-inline';" always;
    }

    location ^~ /atom/3d/ {
        alias /usr/share/nginx/atom/3d/;
        try_files $uri =404;
    }

    # ======================================
    # STANDALONE REPORT EXTENSION
    # ======================================
    location ^~ /ext/reports/ {
        alias /usr/share/nginx/atom/atom-extensions/extensions/reports/public/;
        index index.php index.html;

        location ~ \.php$ {
            try_files $uri =404;
            include fastcgi_params;
            fastcgi_param SCRIPT_FILENAME $request_filename;
            fastcgi_param DOCUMENT_ROOT /usr/share/nginx/atom/atom-extensions/extensions/reports/public;
            fastcgi_pass atom;
        }
    }

    # ======================================
    # IIIF (Cantaloupe)
    # ======================================

    location ~ ^/iiif/manifest/(.+)$ {
        fastcgi_pass atom;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME /usr/share/nginx/atom/atom-framework/src/Extensions/IiifViewer/public/router.php;
        add_header Access-Control-Allow-Origin * always;
    }

    location ~ ^/iiif/collection/(.+)$ {
        fastcgi_pass atom;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME /usr/share/nginx/atom/atom-framework/src/Extensions/IiifViewer/public/router.php;
        add_header Access-Control-Allow-Origin * always;
    }

    location ~ ^/iiif/viewer/(.+)$ {
        fastcgi_pass atom;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME /usr/share/nginx/atom/atom-framework/src/Extensions/IiifViewer/public/router.php;
    }

    location ~ ^/iiif/embed/(.+)$ {
        fastcgi_pass atom;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME /usr/share/nginx/atom/atom-framework/src/Extensions/IiifViewer/public/router.php;
    }

    location ~ ^/iiif/annotations {
        fastcgi_pass atom;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME /usr/share/nginx/atom/atom-framework/src/Extensions/IiifViewer/public/router.php;
        add_header Access-Control-Allow-Origin * always;
    }

    location ~ ^/iiif/ocr {
        fastcgi_pass atom;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME /usr/share/nginx/atom/atom-framework/src/Extensions/IiifViewer/public/router.php;
    }

    location ~ ^/iiif/text {
        fastcgi_pass atom;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME /usr/share/nginx/atom/atom-framework/src/Extensions/IiifViewer/public/router.php;
    }

    location ~ ^/iiif/search {
        fastcgi_pass atom;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME /usr/share/nginx/atom/atom-framework/src/Extensions/IiifViewer/public/router.php;
    }

    location /atom-framework/src/Extensions/IiifViewer/public/ {
        alias /usr/share/nginx/atom/atom-framework/src/Extensions/IiifViewer/public/;
        expires 7d;
    }

    location /iiif/ {
        proxy_pass http://127.0.0.1:8182/iiif/;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
        add_header Access-Control-Allow-Origin "*" always;
    }

    location ^~ /atom/iiif/ {
        rewrite ^/atom/iiif/(.*)$ /iiif/$1 break;
        proxy_pass http://127.0.0.1:8182/iiif/;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
        add_header Access-Control-Allow-Origin "*" always;
        add_header Access-Control-Allow-Methods "GET, OPTIONS" always;
        add_header Access-Control-Allow-Headers "Origin, X-Requested-With, Content-Type, Accept" always;
    }

    location = /iiif-manifest.php {
        fastcgi_pass atom;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        add_header Access-Control-Allow-Origin * always;
    }

    # ======================================
    # STATIC ASSETS
    # ======================================

    location ^~ /plugins/arDominionB5Plugin/js/dist/ {
        alias /usr/share/nginx/atom/dist/js/;
        try_files $uri $uri/ =404;
        expires 30d;
        add_header Cache-Control "public, immutable";
    }

    location ^~ /plugins/arDominionB5Plugin/css/dist/ {
        alias /usr/share/nginx/atom/dist/css/;
        try_files $uri $uri/ =404;
        expires 30d;
        add_header Cache-Control "public, immutable";
    }

    location ^~ /atom/dist/js/ {
        alias /usr/share/nginx/atom/dist/js/;
        try_files $uri $uri/ =404;
        expires 30d;
        add_header Cache-Control "public, immutable";
    }

    location ^~ /atom/dist/css/ {
        alias /usr/share/nginx/atom/dist/css/;
        try_files $uri $uri/ =404;
        expires 30d;
        add_header Cache-Control "public, immutable";
    }

    location ~* ^/(css|dist|js|images|plugins|vendor|arDominionB5Plugin)/.*\.(css|js|png|jpg|jpeg|gif|svg|ico|woff|woff2|ttf|eot|pdf)$ {
        root /usr/share/nginx/atom;
        try_files $uri $uri/ =404;
        expires 30d;
        add_header Cache-Control "public, immutable";
    }

    # ======================================
    # SECURITY - BLOCK DIRECT PHP ACCESS
    # ======================================
    location ~ \.php$ {
        deny all;
        return 404;
    }

    location ~ /\.(ht|git|svn) {
        deny all;
    }

    # ======================================
    # MAIN ROUTER (catch-all with rate limiting)
    # ======================================
    location / {
        limit_req zone=slow burst=40 nodelay;
        try_files $uri /index.php?$args;
    }
}

---

## NGINX

This example listens for connections on port 80 using basic http without encryption.
While this is ok for testing AtoM locally on a private network, any public implementation of AtoM should be secured using TLS/SSL certificates such that your content is served over HTTPS.
The Mozilla SSL Configuration Generator is useful for assisting with adding the appropriate blocks to your Nginx configuration file.

##
# Access to Memory (AtoM) – GENERIC HTTP (80 ONLY) WITH IIIF SUPPORT
# Includes: bot protection, rate limits, RiC, Media APIs, IIIF/Cantaloupe, viewers, reports
##

upstream atom {
   server unix:/run/php-fpm.atom.sock;
}

server {
   listen 80 default_server;
   listen [::]:80 default_server;

   server_name _;

   root /usr/share/nginx/atom;
   index index.php index.html;

   client_max_body_size 2G;

   access_log /var/log/nginx/atom_access.log;
   error_log  /var/log/nginx/atom_error.log;

   # ======================================
   # SECURITY HEADERS
   # ======================================
   add_header X-Frame-Options "SAMEORIGIN" always;
   add_header X-XSS-Protection "1; mode=block" always;
   add_header X-Content-Type-Options "nosniff" always;
   add_header Referrer-Policy "strict-origin-when-cross-origin" always;

   # ======================================
   # BOT PROTECTION (uses maps from conf.d)
   # ======================================
   if ($bad_bot) { return 444; }
   if ($blocked_ip) { return 403; }

   # ======================================
   # BLOCK COMMON PHP EXPLOIT SCANNERS
   # ======================================
   location ~* (eval-stdin\.php|phpunit|pearcmd|thinkphp|invokefunction|\.env|\.git|shell|cmd) {
      return 444;
   }

   # Block attempts to include remote files
   if ($query_string ~* "allow_url_include" ) { return 444; }
   if ($query_string ~* "auto_prepend_file" ) { return 444; }

   # ======================================
   # BLOCK PATH TRAVERSAL
   # ======================================
   if ($request_uri ~ "\.\./") { return 444; }
   if ($request_uri ~ "\.%2e%2e/") { return 444; }

   # ======================================
   # STATIC ASSETS (original install pattern + caching)
   # ======================================
   location ~* ^/(css|dist|js|images|plugins|vendor)/.*\.(css|png|jpg|js|svg|ico|gif|pdf|woff|woff2|otf|ttf)$ {
      try_files $uri $uri/ =404;
      expires 30d;
      add_header Cache-Control "public, immutable";
   }

   location ~* ^/(downloads)/.*\.(pdf|xml|html|csv|zip|rtf)$ {
      try_files $uri $uri/ =404;
      expires 7d;
      add_header Cache-Control "public";
   }

   location ~ ^/(ead\.dtd|favicon\.ico|robots\.txt|sitemap.*)$ {
      try_files $uri =404;
      expires 7d;
      add_header Cache-Control "public";
   }

   # ======================================
   # RATE-LIMITED BROWSE ENDPOINTS (bot targets)
   # ======================================
   location ~ ^/index\.php/glam/browse {
      limit_req zone=browse_limit burst=10 nodelay;
      limit_conn conn_limit 5;

      include /etc/nginx/fastcgi_params;
      fastcgi_split_path_info ^(.+?\.php)(/.*)$;
      fastcgi_pass atom;
      fastcgi_param PATH_INFO $fastcgi_path_info;
      fastcgi_param PATH_TRANSLATED $document_root$fastcgi_path_info;
      fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
      fastcgi_param SCRIPT_NAME $fastcgi_script_name;
      fastcgi_index index.php;
      fastcgi_read_timeout 300;
      fastcgi_buffer_size 128k;
      fastcgi_buffers 4 256k;
      fastcgi_busy_buffers_size 256k;
   }

   location ~ ^/index\.php/informationobject/browse {
      limit_req zone=browse_limit burst=10 nodelay;
      limit_conn conn_limit 5;

      include /etc/nginx/fastcgi_params;
      fastcgi_split_path_info ^(.+?\.php)(/.*)$;
      fastcgi_pass atom;
      fastcgi_param PATH_INFO $fastcgi_path_info;
      fastcgi_param PATH_TRANSLATED $document_root$fastcgi_path_info;
      fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
      fastcgi_param SCRIPT_NAME $fastcgi_script_name;
      fastcgi_index index.php;
      fastcgi_read_timeout 300;
      fastcgi_buffer_size 128k;
      fastcgi_buffers 4 256k;
      fastcgi_busy_buffers_size 256k;
   }

   location ~ ^/index\.php/.*/search {
      limit_req zone=search_limit burst=15 nodelay;
      limit_conn conn_limit 10;

      include /etc/nginx/fastcgi_params;
      fastcgi_split_path_info ^(.+?\.php)(/.*)$;
      fastcgi_pass atom;
      fastcgi_param PATH_INFO $fastcgi_path_info;
      fastcgi_param PATH_TRANSLATED $document_root$fastcgi_path_info;
      fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
      fastcgi_param SCRIPT_NAME $fastcgi_script_name;
      fastcgi_index index.php;
      fastcgi_read_timeout 300;
      fastcgi_buffer_size 128k;
      fastcgi_buffers 4 256k;
      fastcgi_busy_buffers_size 256k;
   }

   # ======================================
   # RiC EXPLORER & APIs
   # ======================================
   location ^~ /ric/ {
      alias /usr/share/nginx/atom/web/ric/;
      index index.html;
      try_files $uri $uri/ =404;
   }

   location ^~ /api/ric/ {
      proxy_pass http://127.0.0.1:5001/api/;
      proxy_http_version 1.1;
      proxy_set_header Host $host;
      proxy_set_header X-Real-IP $remote_addr;
      proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
      proxy_connect_timeout 30;
      proxy_read_timeout 30;
   }

   location ^~ /api/provenance/ {
      proxy_pass http://127.0.0.1:5003/api/provenance/;
      proxy_http_version 1.1;
      proxy_set_header Host $host;
      proxy_set_header X-Real-IP $remote_addr;
   }

   location ^~ /api/editor/ {
      proxy_pass http://127.0.0.1:5002/api/editor/;
      proxy_http_version 1.1;
      proxy_set_header Host $host;
      proxy_set_header X-Real-IP $remote_addr;
   }

   location ^~ /sparql/ {
      proxy_pass http://192.168.0.112:3030/ric/;
      proxy_http_version 1.1;
      proxy_set_header Host $host;
      add_header Access-Control-Allow-Origin "*" always;
      add_header Access-Control-Allow-Methods "GET, POST, OPTIONS" always;
      add_header Access-Control-Allow-Headers "Content-Type" always;
   }

   location ^~ /ric-dashboard/ {
      alias /usr/share/nginx/atom/web/ric-dashboard/;
      index index.php index.html;

      location ~ \.php$ {
         include /etc/nginx/fastcgi_params;
         fastcgi_pass atom;
         fastcgi_param SCRIPT_FILENAME $request_filename;
      }
   }

   location ^~ /ric-provenance/ {
      alias /usr/share/nginx/atom/web/ric-provenance/;
      index index.html;
      try_files $uri $uri/ =404;
   }

   location ^~ /ric-editor/ {
      alias /usr/share/nginx/atom/web/ric-editor/;
      index index.html;
      try_files $uri $uri/ =404;
   }

   # ======================================
   # MEDIA API ROUTES
   # ======================================
   location ~ ^/media/ {
      include /etc/nginx/fastcgi_params;
      fastcgi_param SCRIPT_FILENAME /usr/share/nginx/atom/atom-framework/src/Extensions/Media/public/routes.php;
      fastcgi_pass atom;
   }

   location ~ ^/media/(metadata|extract|waveform)/([0-9]+)$ {
      include /etc/nginx/fastcgi_params;
      fastcgi_param SCRIPT_FILENAME /usr/share/nginx/atom/atom-framework/src/Extensions/IiifViewer/public/router.php;
      fastcgi_pass atom;
      add_header Access-Control-Allow-Origin * always;
   }

   location ~ ^/media/(transcription|transcribe)/([0-9]+)(.*)$ {
      include /etc/nginx/fastcgi_params;
      fastcgi_param SCRIPT_FILENAME /usr/share/nginx/atom/atom-framework/src/Extensions/IiifViewer/public/router.php;
      fastcgi_pass atom;
      fastcgi_read_timeout 600;
      add_header Access-Control-Allow-Origin * always;
   }

   location ~ ^/media/search {
      include /etc/nginx/fastcgi_params;
      fastcgi_param SCRIPT_FILENAME /usr/share/nginx/atom/atom-framework/src/Extensions/IiifViewer/public/router.php;
      fastcgi_pass atom;
      add_header Access-Control-Allow-Origin * always;
   }

   # ======================================
   # UPLOADS / PRIVATE (original install logic)
   # ======================================
   location ~* /uploads/r/(.*)/conf/ { }

   location ~* ^/uploads/r/(.*)$ {
      include /etc/nginx/fastcgi_params;
      set $index /index.php;
      fastcgi_param SCRIPT_FILENAME $document_root$index;
      fastcgi_param SCRIPT_NAME $index;
      fastcgi_pass atom;
   }

   location ~ ^/private/(.*)$ {
      internal;
      alias /usr/share/nginx/atom/$1;
   }

   # ======================================
   # DIGITAL OBJECT VIEWERS
   # ======================================
   location ~* /index\.php/.*/digitalobject/ {
      add_header Content-Security-Policy "default-src 'self' data: blob:;
                                          script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.jsdelivr.net;
                                          style-src 'self' 'unsafe-inline';
                                          img-src 'self' data: blob: https:;" always;
   }

   location /zoompan/ {
      add_header Content-Security-Policy "default-src 'self' data: blob:;
                                          script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.jsdelivr.net;
                                          style-src 'self' 'unsafe-inline';
                                          img-src 'self' data: blob: https:;" always;
      try_files $uri $uri/ /index.php$args;
   }

   location = /3D-image.php {
      root /usr/share/nginx/atom;
      include /etc/nginx/fastcgi_params;
      fastcgi_param SCRIPT_FILENAME $document_root/3D-image.php;
      fastcgi_pass atom;
      add_header Content-Security-Policy "default-src 'self' data: blob:;
                                          script-src 'self' 'unsafe-inline';
                                          img-src 'self' data: blob:;
                                          style-src 'self' 'unsafe-inline';" always;
   }

   location ^~ /atom/3d/ {
      alias /usr/share/nginx/atom/3d/;
      try_files $uri =404;
   }

   # ======================================
   # STANDALONE REPORT EXTENSION
   # ======================================
   location ^~ /ext/reports/ {
      alias /usr/share/nginx/atom/atom-extensions/extensions/reports/public/;
      index index.php index.html;

      location ~ \.php$ {
         try_files $uri =404;
         include /etc/nginx/fastcgi_params;
         fastcgi_param SCRIPT_FILENAME $request_filename;
         fastcgi_param DOCUMENT_ROOT /usr/share/nginx/atom/atom-extensions/extensions/reports/public;
         fastcgi_pass atom;
      }
   }

   # ======================================
   # IIIF (Cantaloupe)
   # ======================================

   # IIIF Viewer Framework Routes (must be before generic /iiif/ proxy)
   location ~ ^/iiif/manifest/(.+)$ {
      include /etc/nginx/fastcgi_params;
      fastcgi_param SCRIPT_FILENAME /usr/share/nginx/atom/atom-framework/src/Extensions/IiifViewer/public/router.php;
      fastcgi_pass atom;
      add_header Access-Control-Allow-Origin * always;
   }

   location ~ ^/iiif/collection/(.+)$ {
      include /etc/nginx/fastcgi_params;
      fastcgi_param SCRIPT_FILENAME /usr/share/nginx/atom/atom-framework/src/Extensions/IiifViewer/public/router.php;
      fastcgi_pass atom;
      add_header Access-Control-Allow-Origin * always;
   }

   location ~ ^/iiif/viewer/(.+)$ {
      include /etc/nginx/fastcgi_params;
      fastcgi_param SCRIPT_FILENAME /usr/share/nginx/atom/atom-framework/src/Extensions/IiifViewer/public/router.php;
      fastcgi_pass atom;
   }

   location ~ ^/iiif/embed/(.+)$ {
      include /etc/nginx/fastcgi_params;
      fastcgi_param SCRIPT_FILENAME /usr/share/nginx/atom/atom-framework/src/Extensions/IiifViewer/public/router.php;
      fastcgi_pass atom;
   }

   location ~ ^/iiif/annotations {
      include /etc/nginx/fastcgi_params;
      fastcgi_param SCRIPT_FILENAME /usr/share/nginx/atom/atom-framework/src/Extensions/IiifViewer/public/router.php;
      fastcgi_pass atom;
      add_header Access-Control-Allow-Origin * always;
   }

   location ~ ^/iiif/ocr {
      include /etc/nginx/fastcgi_params;
      fastcgi_param SCRIPT_FILENAME /usr/share/nginx/atom/atom-framework/src/Extensions/IiifViewer/public/router.php;
      fastcgi_pass atom;
   }

   location ~ ^/iiif/text {
      include /etc/nginx/fastcgi_params;
      fastcgi_param SCRIPT_FILENAME /usr/share/nginx/atom/atom-framework/src/Extensions/IiifViewer/public/router.php;
      fastcgi_pass atom;
   }

   location ~ ^/iiif/search {
      include /etc/nginx/fastcgi_params;
      fastcgi_param SCRIPT_FILENAME /usr/share/nginx/atom/atom-framework/src/Extensions/IiifViewer/public/router.php;
      fastcgi_pass atom;
   }

   location /atom-framework/src/Extensions/IiifViewer/public/ {
      alias /usr/share/nginx/atom/atom-framework/src/Extensions/IiifViewer/public/;
      expires 7d;
   }

   # IIIF Cantaloupe proxy
   location /iiif/ {
      proxy_pass http://127.0.0.1:8182/iiif/;
      proxy_set_header Host $host;
      proxy_set_header X-Real-IP $remote_addr;
      proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
      proxy_set_header X-Forwarded-Proto $scheme;
      add_header Access-Control-Allow-Origin "*" always;
   }

   # Optional pretty route: /atom/iiif/... -> /iiif/...
   location ^~ /atom/iiif/ {
      rewrite ^/atom/iiif/(.*)$ /iiif/$1 break;
      proxy_pass http://127.0.0.1:8182/iiif/;
      proxy_set_header Host $host;
      proxy_set_header X-Real-IP $remote_addr;
      proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
      proxy_set_header X-Forwarded-Proto $scheme;
      add_header Access-Control-Allow-Origin "*" always;
      add_header Access-Control-Allow-Methods "GET, OPTIONS" always;
      add_header Access-Control-Allow-Headers "Origin, X-Requested-With, Content-Type, Accept" always;
   }

   location = /iiif-manifest.php {
      include /etc/nginx/fastcgi_params;
      fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
      fastcgi_pass atom;
      add_header Access-Control-Allow-Origin * always;
   }

   # ======================================
   # MAIN ROUTER (catch-all with rate limiting)
   # ======================================
   location / {
      limit_req zone=slow burst=40 nodelay;
      try_files $uri /index.php?$args;

      # original install behaviour: block direct access to real files
      if (-f $request_filename) {
         return 403;
      }
   }

   # ======================================
   # AtoM PHP entrypoints (original install block)
   # ======================================
   location ~ ^/(index|qubit_dev)\.php(/|$) {
      include /etc/nginx/fastcgi_params;
      fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
      fastcgi_split_path_info ^(.+\.php)(/.*)$;
      fastcgi_pass atom;
   }

   # ======================================
   # SECURITY - BLOCK DIRECT PHP ACCESS (keep last)
   # ======================================
   location ~ \.php$ {
      deny all;
      return 404;
   }
}

---

---

## Troubleshooting

| Issue | Solution |
|-------|----------|
| Extension not found | Run `php bin/atom extension:discover` |
| Database errors | Run `php bin/atom framework:install` |
| Migration failed | Check `php bin/atom migrate status` |
| Permission denied | Run `chown -R www-data:www-data plugins/` |
| Theme not loading | Set `is_enabled=0` in `atom_plugin` table |

### Reset Migrations (Development Only)
```bash
# View what's been run
mysql -u root -p archive -e "SELECT * FROM atom_framework_migrations;"

# To re-run a migration (use with caution!)
mysql -u root -p archive -e "DELETE FROM atom_framework_migrations WHERE migration='migration_name';"
php bin/atom migrate run
```

---

## Get Help

- [GitHub Issues](https://github.com/ArchiveHeritageGroup/atom-framework/issues)
- [AtoM Community](https://groups.google.com/g/ica-atom-users)
