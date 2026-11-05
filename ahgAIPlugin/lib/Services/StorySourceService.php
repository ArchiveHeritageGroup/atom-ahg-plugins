<?php

/**
 * Extra grounding for the storytelling engine (#155, parity with heratio#1202):
 * text from a public web page or an uploaded PDF or text file.
 *
 * Page fetches are pinned to the address that was checked (CURLOPT_RESOLVE)
 * and redirects are followed by hand, each hop checked again, so a name that
 * resolves to a public address for the check cannot reach a private one for
 * the fetch.
 */
class StorySourceService
{
    public const MAX_CONTEXT = 8000;
    public const MAX_SOURCE = 6000;
    public const MAX_FETCH_BYTES = 600000;
    private const MAX_REDIRECTS = 3;

    /** @return array{ok: bool, text: string, title: string, url: string, error: string} */
    public function fetchUrlText(string $url): array
    {
        $out = ['ok' => false, 'text' => '', 'title' => '', 'url' => $url, 'error' => ''];
        for ($hop = 0; $hop <= self::MAX_REDIRECTS; ++$hop) {
            $p = parse_url($url);
            $scheme = strtolower($p['scheme'] ?? '');
            $host = $p['host'] ?? '';
            if (!in_array($scheme, ['http', 'https'], true) || '' === $host || isset($p['user']) || isset($p['pass'])) {
                return ['error' => 'Only plain http and https addresses can be fetched.'] + $out;
            }
            $port = (int) ($p['port'] ?? ('https' === $scheme ? 443 : 80));
            $ip = self::publicAddress($host);
            if (null === $ip) {
                return ['error' => 'That address is not on the public internet.'] + $out;
            }

            $body = '';
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RESOLVE => ["{$host}:{$port}:".(str_contains($ip, ':') ? "[{$ip}]" : $ip)],
                CURLOPT_FOLLOWLOCATION => false,
                CURLOPT_RETURNTRANSFER => false,
                CURLOPT_PROTOCOLS => CURLPROTO_HTTP | CURLPROTO_HTTPS,
                CURLOPT_CONNECTTIMEOUT => 8,
                CURLOPT_TIMEOUT => 20,
                CURLOPT_USERAGENT => 'AtoM storytelling source fetch',
                CURLOPT_WRITEFUNCTION => function ($c, $chunk) use (&$body) {
                    $body .= $chunk;

                    return strlen($body) > self::MAX_FETCH_BYTES ? 0 : strlen($chunk);
                },
            ]);
            curl_exec($ch);
            $code = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
            $type = (string) curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
            $next = (string) curl_getinfo($ch, CURLINFO_REDIRECT_URL);
            curl_close($ch);

            if ($code >= 300 && $code < 400 && '' !== $next) {
                $url = $next;
                continue;
            }
            if (200 !== $code || '' === $body) {
                return ['error' => "The page could not be fetched (HTTP {$code})."] + $out;
            }
            if (preg_match('#<title[^>]*>(.*?)</title>#is', $body, $m)) {
                $out['title'] = mb_substr(trim(html_entity_decode(strip_tags($m[1]), ENT_QUOTES)), 0, 200);
            }
            $text = str_contains($type, 'html') || '' === $type ? self::htmlToText($body) : (str_starts_with($type, 'text/') ? $body : '');
            $out['text'] = self::clip($text);
            $out['url'] = $url;
            $out['ok'] = '' !== $out['text'];
            $out['error'] = $out['ok'] ? '' : 'No readable text on that page.';

            return $out;
        }

        return ['error' => 'Too many redirects.'] + $out;
    }

    /** @return array{ok: bool, text: string, error: string} */
    public function extractUploadText(string $path, string $name): array
    {
        $out = ['ok' => false, 'text' => '', 'error' => ''];
        $mime = (string) (new \finfo(FILEINFO_MIME_TYPE))->file($path);
        if (str_starts_with($mime, 'text/')) {
            $out['text'] = self::clip((string) file_get_contents($path, false, null, 0, self::MAX_FETCH_BYTES));
        } elseif ('application/pdf' === $mime) {
            $txt = (string) shell_exec('pdftotext -l 30 -enc UTF-8 '.escapeshellarg($path).' - 2>/dev/null');
            $out['text'] = self::clip($txt);
            if ('' === $out['text']) {
                $out['error'] = "No selectable text in {$name} (it may be scanned images).";
            }
        } else {
            $out['error'] = "{$name}: only PDF and text files can be used.";
        }
        $out['ok'] = '' !== $out['text'];

        return $out;
    }

    /** Join labelled pieces into one background block, capped at MAX_CONTEXT. */
    public static function assembleContext(array $pieces): string
    {
        $parts = [];
        foreach ($pieces as $label => $text) {
            if ('' !== trim($text)) {
                $parts[] = "[{$label}]\n".trim($text);
            }
        }

        return mb_substr(implode("\n\n", $parts), 0, self::MAX_CONTEXT);
    }

    /** The first public address the host resolves to, or null if any is private or reserved. */
    private static function publicAddress(string $host): ?string
    {
        $host = trim($host, '[]');
        $ips = filter_var($host, FILTER_VALIDATE_IP) ? [$host] : array_values(array_filter(array_map(
            fn ($r) => $r['ip'] ?? ($r['ipv6'] ?? null),
            @dns_get_record($host, DNS_A | DNS_AAAA) ?: []
        )));
        if (!$ips) {
            return null;
        }
        foreach ($ips as $ip) {
            if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                return null;
            }
        }

        return $ips[0];
    }

    private static function htmlToText(string $html): string
    {
        $html = preg_replace('#<(script|style|noscript|nav|header|footer)[^>]*>.*?</\1>#is', ' ', $html);

        return trim(preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags((string) $html), ENT_QUOTES | ENT_HTML5)));
    }

    private static function clip(string $text): string
    {
        return mb_substr(trim(preg_replace('/[ \t]+/u', ' ', $text)), 0, self::MAX_SOURCE);
    }
}
