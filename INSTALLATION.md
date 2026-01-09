## Optional Software Dependencies

The AHG Framework provides enhanced functionality when these tools are installed:

### Media Processing

| Tool | Install Command | Purpose |
|------|-----------------|---------|
| **FFmpeg** | `apt install ffmpeg` | Audio/video transcoding, streaming legacy formats |
| **FFprobe** | (included with FFmpeg) | Media metadata extraction (duration, bitrate, codec) |
| **ImageMagick** | `apt install imagemagick` | Image processing, TIFF conversion, placeholder generation |
| **Ghostscript** | `apt install ghostscript` | PDF/A generation for archival compliance |
| **exiftool** | `apt install libimage-exiftool-perl` | EXIF/IPTC/XMP metadata extraction from images |
| **pdfinfo** | `apt install poppler-utils` | PDF metadata extraction (title, author, pages) |

### 3D Thumbnail Generation

| Tool | Install Command | Purpose |
|------|-----------------|---------|
| **Blender** | `snap install blender --classic` | 3D model rendering for GLB/OBJ/STL/FBX/PLY/DAE |

snap install blender --classic

/snap/bin/blender --version

### Speech-to-Text Transcription

| Tool | Install Command | Purpose |
|------|-----------------|---------|
| **OpenAI Whisper** | `pip install openai-whisper` | Speech-to-text for 90+ languages |

pip install openai-whisper

whisper --help

Whisper models (downloaded automatically on first use):
- tiny - Fastest, ~1GB VRAM
- base - Fast, ~1GB VRAM  
- small - Balanced, ~2GB VRAM
- medium - Good accuracy, ~5GB VRAM (recommended)
- large-v3 - Best accuracy, ~10GB VRAM

---

## IIIF Image Server (Cantaloupe)

Cantaloupe provides deep zoom capability for high-resolution images.

### Installation

1. Install Java: `apt install -y openjdk-11-jre-headless`
2. Download Cantaloupe 5.0.6 from GitHub releases
3. Extract to `/opt/cantaloupe`
4. Copy `cantaloupe.properties.sample` to `cantaloupe.properties`

systemctl daemon-reload
systemctl enable cantaloupe
systemctl start cantaloupe

curl -s http://localhost:8182/iiif/2

### Key Configuration (cantaloupe.properties)

| Setting | Value | Purpose |
|---------|-------|---------|
| `http.port` | 8182 | Server port |
| `slash_substitute` | `_SL_` | Path separator encoding |
| `max_pixels` | 50000000 | Maximum image size |
| `delegate_script.enabled` | true | Enable Ruby delegate |
| `delegate_script.pathname` | /opt/cantaloupe/delegates.rb | Delegate location |

### Delegate Script (delegates.rb)

The delegate script maps IIIF identifiers to file paths. Key method:

| Method | Purpose |
|--------|---------|
| `filesystemsource_pathname` | Decodes `_SL_` to `/` and builds full file path |
| `pre_authorize` | Returns true (allow all) |
| `authorize` | Returns true (allow all) |
| `source` | Returns 'FilesystemSource' |

### Systemd Service

Create `/etc/systemd/system/cantaloupe.service` with:
- Type: simple
- User: www-data
- ExecStart: java -Xmx2g -jar /opt/cantaloupe/cantaloupe-5.0.6.jar
- Restart: on-failure

Commands: `systemctl enable cantaloupe` and `systemctl start cantaloupe`

---

## RiC Triplestore (Apache Jena Fuseki)

Fuseki provides SPARQL queries for Records in Contexts (RiC) ontology support.

### Docker Installation (Recommended)

| Parameter | Value |
|-----------|-------|
| Container name | fuseki |
| Port | 3030 |
| Data volume | /opt/fuseki-data:/fuseki |
| Dataset name | ric |
| Dataset type | tdb2 |

### Verification Commands

| Command | Purpose |
|---------|---------|
| `curl http://127.0.0.1:3030/$/ping` | Check if Fuseki is responding |
| `docker logs fuseki --tail 30` | View container logs |
| `docker restart fuseki` | Restart if unresponsive |

### SPARQL Endpoint

- Query endpoint: `http://127.0.0.1:3030/ric/query`
- Update endpoint: `http://127.0.0.1:3030/ric/update`

---

## Rate Limiting Configuration

Add to `/etc/nginx/conf.d/rate-limits.conf`:

### Rate Limit Zones

| Zone | Rate | Purpose |
|------|------|---------|
| browse_limit | 5r/s | GLAM browse pages |
| search_limit | 10r/s | Search endpoints |
| slow | 20r/s | General requests |
| conn_limit | - | Connection limiting |

### Bot Protection

Blocked user agents: python-requests, curl, wget, libwww, Scrapy, sqlmap, nikto

Allowed bots: Googlebot, Bingbot, legitimate crawlers

---

## Quick Dependency Check Script

Run to verify all dependencies are installed:

| Check | Success | Failure |
|-------|---------|---------|
| PHP 8.3 | Version displayed | Error |
| MySQL 8.0 | Version displayed | Error |
| FFmpeg | Version displayed | "not installed" |
| Blender | Version displayed | "not installed" |
| Whisper | Help displayed | "not installed" |
| ImageMagick | Version displayed | "not installed" |
| exiftool | Version displayed | "not installed" |
| Cantaloupe | "running" | "not running" |
| Fuseki | "running" | "not running" |
