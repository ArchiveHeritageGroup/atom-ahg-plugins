---
 
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
```bash
# Install Blender via snap (recommended - latest version)
snap install blender --classic

# Verify installation
/snap/bin/blender --version
```

### Speech-to-Text Transcription

| Tool | Install Command | Purpose |
|------|-----------------|---------|
| **OpenAI Whisper** | `pip install openai-whisper` | Speech-to-text for 90+ languages |
```bash
# Install Whisper
pip install openai-whisper

# Verify installation
whisper --help

# Whisper models (downloaded automatically on first use)
# tiny   - Fastest, ~1GB VRAM
# base   - Fast, ~1GB VRAM
# small  - Balanced, ~2GB VRAM
# medium - Good accuracy, ~5GB VRAM (recommended)
# large-v3 - Best accuracy, ~10GB VRAM
```

---

## IIIF Image Server (Cantaloupe)

Cantaloupe provides deep zoom capability for high-resolution images.

### Installation
```bash
# 1. Install Java (required)
apt update
apt install -y openjdk-11-jre-headless

# 2. Create directory and download
mkdir -p /opt/cantaloupe
cd /opt/cantaloupe
wget https://github.com/cantaloupe-project/cantaloupe/releases/download/v5.0.6/cantaloupe-5.0.6.zip
unzip cantaloupe-5.0.6.zip
mv cantaloupe-5.0.6/* .
rm -rf cantaloupe-5.0.6 cantaloupe-5.0.6.zip

# 3. Create configuration
cp cantaloupe.properties.sample cantaloupe.properties
```

### Configuration (cantaloupe.properties)
```properties
# Key settings
http.port = 8182
slash_substitute = _SL_
max_pixels = 50000000

# Enable delegate script
delegate_script.enabled = true
delegate_script.pathname = /opt/cantaloupe/delegates.rb

# Source configuration
source.delegate = true
FilesystemSource.BasicLookupStrategy.path_prefix = /usr/share/nginx/atom/uploads/
```

### Delegate Script (delegates.rb)

Create `/opt/cantaloupe/delegates.rb`:
```ruby
require 'json'

class CustomDelegate
  attr_accessor :context
  
  # Map hostnames to AtoM installation paths
  INSTANCE_PATHS = {
    'your-domain.example.com' => '/usr/share/nginx/atom/',
    # Add additional instances as needed
  }.freeze
  
  DEFAULT_PATH = '/usr/share/nginx/atom/'.freeze

  def filesystemsource_pathname
    identifier = context['identifier'].to_s
    
    # Decode _SL_ to / for path separator
    decoded_identifier = identifier.gsub('_SL_', '/')
    
    headers = context['request_headers'] || {}
    host = (headers['X-Forwarded-Host'] || headers['Host'] || '').to_s.split(':').first.to_s.downcase
    base = INSTANCE_PATHS[host] || DEFAULT_PATH
    
    path = base + decoded_identifier
    STDERR.puts "[Cantaloupe] Identifier=#{identifier} Decoded=#{decoded_identifier} Path=#{path}"
    path
  end
  
  def pre_authorize(options = {}); true; end
  def authorize(options = {}); true; end
  def source(options = {}); 'FilesystemSource'; end
  def overlay(options = {}); nil; end
  def extra_iiif_information_response_keys(options = {}); {}; end
  def azurestoragesource_blob_key(options = {}); nil; end
  def httpsource_resource_info(options = {}); nil; end
  def jdbcsource_database_identifier(options = {}); nil; end
  def jdbcsource_media_type(options = {}); nil; end
  def jdbcsource_lookup_sql(options = {}); nil; end
  def s3source_object_info(options = {}); nil; end
  def redactions(options = {}); []; end
  def metadata(options = {}); nil; end
end
```

### Systemd Service

Create `/etc/systemd/system/cantaloupe.service`:
```ini
[Unit]
Description=Cantaloupe IIIF Image Server
After=network.target

[Service]
Type=simple
User=www-data
Group=www-data
ExecStart=/usr/bin/java -Xmx2g -jar /opt/cantaloupe/cantaloupe-5.0.6.jar
Restart=on-failure
RestartSec=10

[Install]
WantedBy=multi-user.target
```
```bash
# Enable and start
systemctl daemon-reload
systemctl enable cantaloupe
systemctl start cantaloupe
systemctl status cantaloupe

# Verify
curl -s http://localhost:8182/iiif/2 | head -5
```

---

## RiC Triplestore (Apache Jena Fuseki)

Fuseki provides SPARQL queries for Records in Contexts (RiC) ontology support.

### Docker Installation (Recommended)
```bash
# Pull and run Fuseki
docker run -d \
  --name fuseki \
  -p 3030:3030 \
  -v /opt/fuseki-data:/fuseki \
  -e ADMIN_PASSWORD=your-secure-password \
  stain/jena-fuseki

# Verify
curl -s http://127.0.0.1:3030/$/ping && echo " - Fuseki responding"

# Create RIC dataset
curl -X POST http://127.0.0.1:3030/$/datasets \
  -u admin:your-secure-password \
  -d "dbName=ric&dbType=tdb2"
```

### Systemd Service (Alternative)

If not using Docker, download and install manually:
```bash
# Download Fuseki
wget https://dlcdn.apache.org/jena/binaries/apache-jena-fuseki-4.10.0.tar.gz
tar -xzf apache-jena-fuseki-4.10.0.tar.gz -C /opt/
ln -s /opt/apache-jena-fuseki-4.10.0 /opt/fuseki

# Create service
cat > /etc/systemd/system/fuseki.service << 'EOF'
[Unit]
Description=Apache Jena Fuseki
After=network.target

[Service]
Type=simple
User=www-data
ExecStart=/opt/fuseki/fuseki-server --loc=/opt/fuseki-data/ric /ric
Restart=on-failure

[Install]
WantedBy=multi-user.target
EOF

systemctl daemon-reload
systemctl enable fuseki
systemctl start fuseki
```

### Check Triple Count
```bash
curl -s "http://127.0.0.1:3030/ric/query" \
  -H "Content-Type: application/sparql-query" \
  -H "Accept: application/json" \
  -d "SELECT (COUNT(*) as ?count) WHERE { ?s ?p ?o }" | \
  python3 -c "import sys,json; d=json.load(sys.stdin); print(f\"Triples: {d['results']['bindings'][0]['count']['value']}\")"
```

---

## Rate Limiting Configuration

Add to `/etc/nginx/conf.d/rate-limits.conf`:
```nginx
# Rate limiting zones
limit_req_zone $binary_remote_addr zone=browse_limit:10m rate=5r/s;
limit_req_zone $binary_remote_addr zone=search_limit:10m rate=10r/s;
limit_req_zone $binary_remote_addr zone=slow:10m rate=20r/s;
limit_conn_zone $binary_remote_addr zone=conn_limit:10m;

# Bot blocking map
map $http_user_agent $bad_bot {
    default 0;
    ~*bot 0;          # Allow legitimate bots
    ~*crawl 0;
    ~*spider 0;
    ~*Googlebot 0;
    ~*Bingbot 0;
    ~*python-requests 1;
    ~*curl 1;
    ~*wget 1;
    ~*libwww 1;
    ~*Scrapy 1;
    ~*sqlmap 1;
    ~*nikto 1;
}

map $remote_addr $blocked_ip {
    default 0;
    # Add specific IPs to block:
    # "1.2.3.4" 1;
}
```

---

## Quick Dependency Check

Run this script to verify all dependencies:
```bash
#!/bin/bash
echo "=== AHG Framework Dependency Check ==="

# PHP
php -v | head -1

# MySQL
mysql --version

# FFmpeg
ffmpeg -version 2>/dev/null | head -1 || echo "❌ FFmpeg not installed"

# Blender
/snap/bin/blender --version 2>/dev/null | head -1 || blender --version 2>/dev/null | head -1 || echo "❌ Blender not installed"

# Whisper
whisper --help 2>/dev/null | head -1 || echo "❌ Whisper not installed"

# ImageMagick
convert --version 2>/dev/null | head -1 || echo "❌ ImageMagick not installed"

# exiftool
exiftool -ver 2>/dev/null || echo "❌ exiftool not installed"

# Cantaloupe
curl -s http://localhost:8182/iiif/2 >/dev/null && echo "✅ Cantaloupe running" || echo "❌ Cantaloupe not running"

# Fuseki
curl -s http://127.0.0.1:3030/$/ping >/dev/null && echo "✅ Fuseki running" || echo "❌ Fuseki not running"

echo "=== Check Complete ==="
```

---
