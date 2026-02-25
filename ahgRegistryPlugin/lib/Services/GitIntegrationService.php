<?php

namespace AhgRegistry\Services;

use Illuminate\Database\Capsule\Manager as DB;

class GitIntegrationService
{
    protected string $culture;

    public function __construct(string $culture = 'en')
    {
        $this->culture = $culture;
    }

    // =========================================================================
    // Fetch Latest Release
    // =========================================================================

    /**
     * Fetch the latest release from a software's git repository.
     */
    public function fetchLatestRelease(int $softwareId): array
    {
        $software = DB::table('registry_software')->where('id', $softwareId)->first();
        if (!$software) {
            return ['success' => false, 'error' => 'Software not found'];
        }

        if ($software->git_provider === 'none' || empty($software->git_url)) {
            return ['success' => false, 'error' => 'No git repository configured for this software'];
        }

        $parsed = $this->parseGitUrl($software->git_url);
        if (!$parsed) {
            return ['success' => false, 'error' => 'Could not parse git URL: ' . $software->git_url];
        }

        $token = $software->git_api_token_encrypted ?? null;

        switch ($software->git_provider) {
            case 'github':
                $releaseData = $this->fetchGitHubRelease($parsed['owner'], $parsed['repo'], $token);
                break;

            case 'gitlab':
                $releaseData = $this->fetchGitLabRelease($parsed['path'], $token);
                break;

            default:
                return ['success' => false, 'error' => 'Unsupported git provider: ' . $software->git_provider];
        }

        if (!$releaseData['success']) {
            return $releaseData;
        }

        return [
            'success' => true,
            'software_id' => $softwareId,
            'release' => $releaseData['release'],
        ];
    }

    // =========================================================================
    // GitHub
    // =========================================================================

    /**
     * Fetch the latest release from GitHub API.
     */
    public function fetchGitHubRelease(string $owner, string $repo, ?string $token = null): array
    {
        $url = "https://api.github.com/repos/{$owner}/{$repo}/releases/latest";

        $headers = [
            'Accept: application/vnd.github.v3+json',
            'User-Agent: AtoM-Heratio-Registry/1.0',
        ];
        if ($token) {
            $headers[] = 'Authorization: token ' . $token;
        }

        $response = $this->httpGet($url, $headers);
        if (!$response['success']) {
            return ['success' => false, 'error' => 'GitHub API request failed: ' . ($response['error'] ?? 'Unknown error')];
        }

        $data = json_decode($response['body'], true);
        if (!$data || isset($data['message'])) {
            return ['success' => false, 'error' => 'GitHub API error: ' . ($data['message'] ?? 'Invalid response')];
        }

        return [
            'success' => true,
            'release' => $this->parseGitHubResponse($data),
        ];
    }

    /**
     * Parse GitHub release API response.
     */
    public function parseGitHubResponse(array $data): array
    {
        $assets = [];
        if (!empty($data['assets'])) {
            foreach ($data['assets'] as $asset) {
                $assets[] = [
                    'name' => $asset['name'],
                    'url' => $asset['browser_download_url'],
                    'size' => $asset['size'],
                    'content_type' => $asset['content_type'],
                    'download_count' => $asset['download_count'],
                ];
            }
        }

        return [
            'tag' => $data['tag_name'] ?? null,
            'name' => $data['name'] ?? null,
            'commit_sha' => $data['target_commitish'] ?? null,
            'release_notes' => $data['body'] ?? null,
            'html_url' => $data['html_url'] ?? null,
            'published_at' => $data['published_at'] ?? null,
            'prerelease' => $data['prerelease'] ?? false,
            'draft' => $data['draft'] ?? false,
            'assets' => $assets,
        ];
    }

    // =========================================================================
    // GitLab
    // =========================================================================

    /**
     * Fetch the latest release from GitLab API.
     */
    public function fetchGitLabRelease(string $projectPath, ?string $token = null): array
    {
        $encodedPath = urlencode($projectPath);
        $url = "https://gitlab.com/api/v4/projects/{$encodedPath}/releases";

        $headers = ['User-Agent: AtoM-Heratio-Registry/1.0'];
        if ($token) {
            $headers[] = 'PRIVATE-TOKEN: ' . $token;
        }

        $response = $this->httpGet($url, $headers);
        if (!$response['success']) {
            return ['success' => false, 'error' => 'GitLab API request failed: ' . ($response['error'] ?? 'Unknown error')];
        }

        $data = json_decode($response['body'], true);
        if (!$data || !is_array($data)) {
            return ['success' => false, 'error' => 'GitLab API error: Invalid response'];
        }

        if (empty($data)) {
            return ['success' => false, 'error' => 'No releases found for this project'];
        }

        // GitLab returns releases sorted newest first
        $latest = $data[0];

        return [
            'success' => true,
            'release' => $this->parseGitLabResponse($latest),
        ];
    }

    /**
     * Parse GitLab release API response.
     */
    public function parseGitLabResponse(array $data): array
    {
        $assets = [];
        if (!empty($data['assets']['links'])) {
            foreach ($data['assets']['links'] as $link) {
                $assets[] = [
                    'name' => $link['name'],
                    'url' => $link['direct_asset_url'] ?? $link['url'],
                    'size' => null,
                    'content_type' => null,
                    'download_count' => null,
                ];
            }
        }
        if (!empty($data['assets']['sources'])) {
            foreach ($data['assets']['sources'] as $source) {
                $assets[] = [
                    'name' => 'Source (' . $source['format'] . ')',
                    'url' => $source['url'],
                    'size' => null,
                    'content_type' => null,
                    'download_count' => null,
                ];
            }
        }

        return [
            'tag' => $data['tag_name'] ?? null,
            'name' => $data['name'] ?? null,
            'commit_sha' => $data['commit']['id'] ?? null,
            'release_notes' => $data['description'] ?? null,
            'html_url' => $data['_links']['self'] ?? null,
            'published_at' => $data['released_at'] ?? $data['created_at'] ?? null,
            'prerelease' => false,
            'draft' => false,
            'assets' => $assets,
        ];
    }

    // =========================================================================
    // Update Software from Git
    // =========================================================================

    /**
     * Create a release record and update software.latest_version from fetched git data.
     */
    public function updateSoftwareFromGit(int $softwareId, array $releaseData): array
    {
        $software = DB::table('registry_software')->where('id', $softwareId)->first();
        if (!$software) {
            return ['success' => false, 'error' => 'Software not found'];
        }

        $version = $releaseData['tag'] ?? null;
        if (!$version) {
            return ['success' => false, 'error' => 'No version tag in release data'];
        }

        // Strip leading 'v' from version tag
        $cleanVersion = ltrim($version, 'vV');

        // Check if this version already exists
        $existing = DB::table('registry_software_release')
            ->where('software_id', $softwareId)
            ->where('version', $cleanVersion)
            ->first();

        if ($existing) {
            return ['success' => false, 'error' => 'Version ' . $cleanVersion . ' already exists'];
        }

        $isStable = empty($releaseData['prerelease']) && empty($releaseData['draft']);

        // Unset previous is_latest if this is stable
        if ($isStable) {
            DB::table('registry_software_release')
                ->where('software_id', $softwareId)
                ->where('is_latest', 1)
                ->update(['is_latest' => 0]);
        }

        $releaseId = DB::table('registry_software_release')->insertGetId([
            'software_id' => $softwareId,
            'version' => $cleanVersion,
            'release_type' => $this->detectReleaseType($cleanVersion),
            'release_notes' => $releaseData['release_notes'] ?? null,
            'git_tag' => $releaseData['tag'],
            'git_commit' => $releaseData['commit_sha'] ?? null,
            'git_compare_url' => $releaseData['html_url'] ?? null,
            'is_stable' => $isStable ? 1 : 0,
            'is_latest' => $isStable ? 1 : 0,
            'released_at' => $releaseData['published_at'] ? date('Y-m-d H:i:s', strtotime($releaseData['published_at'])) : date('Y-m-d H:i:s'),
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        // Update software record
        $softwareUpdate = [
            'git_latest_tag' => $releaseData['tag'],
            'git_latest_commit' => $releaseData['commit_sha'] ?? null,
            'updated_at' => date('Y-m-d H:i:s'),
        ];
        if ($isStable) {
            $softwareUpdate['latest_version'] = $cleanVersion;
        }

        DB::table('registry_software')->where('id', $softwareId)->update($softwareUpdate);

        return ['success' => true, 'release_id' => $releaseId, 'version' => $cleanVersion];
    }

    // =========================================================================
    // URL Parsing
    // =========================================================================

    /**
     * Parse a git URL to extract owner/repo (GitHub) or project path (GitLab).
     */
    public function parseGitUrl(string $url): ?array
    {
        // GitHub: https://github.com/owner/repo or https://github.com/owner/repo.git
        if (preg_match('#github\.com[/:]([^/]+)/([^/.]+)(?:\.git)?#i', $url, $m)) {
            return [
                'provider' => 'github',
                'owner' => $m[1],
                'repo' => $m[2],
                'path' => $m[1] . '/' . $m[2],
            ];
        }

        // GitLab: https://gitlab.com/owner/repo or https://gitlab.com/group/subgroup/repo
        if (preg_match('#gitlab\.com[/:](.+?)(?:\.git)?$#i', $url, $m)) {
            $path = $m[1];
            $parts = explode('/', $path);

            return [
                'provider' => 'gitlab',
                'owner' => $parts[0] ?? null,
                'repo' => end($parts),
                'path' => $path,
            ];
        }

        // Bitbucket: https://bitbucket.org/owner/repo
        if (preg_match('#bitbucket\.org[/:]([^/]+)/([^/.]+)(?:\.git)?#i', $url, $m)) {
            return [
                'provider' => 'bitbucket',
                'owner' => $m[1],
                'repo' => $m[2],
                'path' => $m[1] . '/' . $m[2],
            ];
        }

        return null;
    }

    // =========================================================================
    // Helpers
    // =========================================================================

    /**
     * Perform an HTTP GET request using cURL.
     */
    private function httpGet(string $url, array $headers = []): array
    {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        $body = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($body === false || !empty($error)) {
            return ['success' => false, 'error' => $error ?: 'cURL request failed'];
        }

        if ($httpCode >= 400) {
            return ['success' => false, 'error' => 'HTTP ' . $httpCode, 'body' => $body];
        }

        return ['success' => true, 'body' => $body, 'http_code' => $httpCode];
    }

    /**
     * Detect release type from version string.
     */
    private function detectReleaseType(string $version): string
    {
        $lower = strtolower($version);

        if (strpos($lower, 'alpha') !== false) {
            return 'alpha';
        }
        if (strpos($lower, 'beta') !== false) {
            return 'beta';
        }
        if (strpos($lower, 'rc') !== false) {
            return 'rc';
        }

        // Semantic versioning detection
        $parts = explode('.', preg_replace('/[^0-9.]/', '', $version));
        if (count($parts) >= 3) {
            // x.0.0 = major, x.y.0 = minor, x.y.z = patch
            if (isset($parts[2]) && $parts[2] !== '0') {
                return 'patch';
            }
            if (isset($parts[1]) && $parts[1] !== '0') {
                return 'minor';
            }

            return 'major';
        }

        return 'patch';
    }
}
