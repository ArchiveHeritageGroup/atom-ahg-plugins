require 'json'
require 'net/http'
require 'openssl'
require 'uri'

class CustomDelegate
  attr_accessor :context

    # Where each site keeps its uploads, keyed by the hostname the request
    # arrives with. One image server usually fronts several AtoM instances, and
    # the same identifier means a different file on each, so this cannot be a
    # single path.
    #
    # Replace these with your own. The key is the site's hostname; the value is
    # the directory that uploads/ sits inside, with a trailing slash.
    INSTANCE_PATHS = {
      # 'archives.example.org' => '/usr/share/nginx/archives/',
      # 'museum.example.org'   => '/srv/atom-museum/',
    }.freeze

    # Used when the hostname is not listed above. Set this to your AtoM root, or
    # leave it and list every site explicitly.
    DEFAULT_PATH = '/usr/share/nginx/atom/'.freeze

  AUTH_CACHE_TTL = 60  # seconds

  def self.auth_cache
    @auth_cache ||= {}
  end

  def filesystemsource_pathname
    identifier = context['identifier'].to_s

    # Decode _SL_ to / for path separator
    decoded_identifier = identifier.gsub('_SL_', '/')

    headers = context['request_headers'] || {}
    host = (headers['X-Forwarded-Host'] || headers['Host'] || '').to_s.split(':').first.to_s.downcase
    base = INSTANCE_PATHS[host] || DEFAULT_PATH

    path = base + decoded_identifier

    # Dynamic fallback: if file not found at expected path, try absolute or other bases
    unless File.exist?(path)
      # Try as absolute path (identifier already includes full relative path from /)
      abs = '/' + decoded_identifier
      path = abs if File.exist?(abs)
    end
    STDERR.puts "[Cantaloupe] Identifier=#{identifier} Decoded=#{decoded_identifier} Path=#{path}"
    path
  end

  def pre_authorize(options = {}); true; end

  ##
  # IIIF Auth enforcement.
  # Calls AtoM's internal cantaloupe-check endpoint to validate access.
  # Returns true (allow), false (deny), or { max_scale: N } (degraded).
  #
  def authorize(options = {})
    identifier = context['identifier'].to_s
    headers = context['request_headers'] || {}

    # Extract auth credentials
    cookie_header = (headers['Cookie'] || '').to_s
    auth_cookie = nil
    if cookie_header =~ /iiif_auth_token=([^;]+)/
      auth_cookie = $1
    end

    # Master grants (iiif_mg_<record id>=<user>.<expiry>.<signature>), set by
    # AtoM when a member of staff with readMaster opens a manifest. Passed on
    # as they are; AtoM checks the signature and the record.
    grants = cookie_header.scan(/iiif_mg_\d+=([0-9]+\.[0-9]+\.[0-9a-f]{64})/).flatten.uniq.first(20)

    bearer = nil
    auth_header = (headers['Authorization'] || '').to_s
    if auth_header =~ /Bearer\s+(.+)$/i
      bearer = $1
    end

    # One Cantaloupe serves several AtoM instances, so the check has to reach the
    # one the image belongs to. Without this the call went to whatever vhost
    # answers 127.0.0.1 by default, which knows nothing about the file and
    # answered 'allow'.
    host = (headers['Host'] || headers['X-Forwarded-Host'] || '').to_s.split(',').first.to_s.strip

    # Build cache key (identifier + credentials + instance)
    cache_key = "#{host}:#{identifier}:#{auth_cookie}:#{bearer}:#{grants.join(',')}"

    # Check cache
    cached = self.class.auth_cache[cache_key]
    if cached && cached[:expires] > Time.now
      return cached[:result]
    end

    # Call AtoM's internal auth check endpoint
    begin
      # HTTPS, not HTTP. Certbot puts a server-level
      #   if ($host = <site>) { return 301 https://... }
      # in the port-80 block, which runs before location matching, so a plain HTTP
      # call is redirected no matter what and never returns 200 - and this method
      # refuses on any non-200, so the check would refuse every image.
      # Still to 127.0.0.1 so the request stays on the box; the certificate will not
      # match that address, hence VERIFY_NONE.
      uri = URI.parse("https://127.0.0.1/iiif/auth/cantaloupe-check")
      uri.query = URI.encode_www_form(
        identifier: identifier,
        cookie: auth_cookie || '',
        bearer: bearer || '',
        grants: grants.join(',')
      )

      http = Net::HTTP.new(uri.host, uri.port)
      http.use_ssl = true
      http.verify_mode = OpenSSL::SSL::VERIFY_NONE
      http.open_timeout = 2
      http.read_timeout = 2

      request = Net::HTTP::Get.new(uri)
      request['Host'] = host unless host.empty?
      response = http.request(request)

      if response.code.to_i == 200
        data = JSON.parse(response.body)
        result = if data['allowed']
                   true
                 elsif data['degraded']
                   # Return hash with max_scale for degraded access
                   { 'max_scale' => (data['max_scale'].to_f / 10000.0).clamp(0.01, 1.0) }
                 else
                   false
                 end

        # Cache the result
        self.class.auth_cache[cache_key] = { result: result, expires: Time.now + AUTH_CACHE_TTL }

        # Evict old cache entries periodically
        if self.class.auth_cache.size > 1000
          now = Time.now
          self.class.auth_cache.delete_if { |_, v| v[:expires] < now }
        end

        return result
      end
      STDERR.puts "[Cantaloupe] Auth check answered #{response.code} for #{identifier} (#{host}) - refusing"
    rescue => e
      STDERR.puts "[Cantaloupe] Auth check failed for #{identifier} (#{host}): #{e.message} - refusing"
    end

    # Fail closed. Any answer that is not a readable verdict (an error, a non-200,
    # a login page instead of JSON) refuses the image. Failing open here once
    # served masters to anyone when the check endpoint was locked behind sign-in
    # (found on RARI, 26 Oct 2026): a broken check must cost the zoom, never the
    # masters. Not cached, so a fixed check takes effect at once.
    false
  end

  def source(options = {}); 'FilesystemSource'; end

  def overlay(options = {})
    STDERR.puts "[Cantaloupe] overlay() called - RETURNING NIL (disabled)"
    nil
  end

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
