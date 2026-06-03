<?php

namespace AhgRecordsManage\Services;

use Illuminate\Database\Capsule\Manager as DB;

/**
 * EmailCaptureService — capture business email as records (#118).
 *
 * PSIS-parity port of the Heratio AhgRecordsManage\Services\EmailCaptureService.
 * MVP: .eml file upload (one message per file). Each captured email lands in
 * rm_email_capture with the original .eml stored for forensic preservation; an
 * officer can then classify it to a file-plan node + disposal class and declare
 * it as an information_object record. Laravel UploadedFile/Str/Log/config and
 * the `now()` helper are replaced with framework-neutral equivalents.
 *
 * @package ahgRecordsManagePlugin
 */
class EmailCaptureService
{
    public function listQueue(array $filters = []): array
    {
        $q = DB::table('rm_email_capture as e')
            ->leftJoin('rm_fileplan_node as fp', 'fp.id', '=', 'e.fileplan_node_id')
            ->select('e.id', 'e.message_id', 'e.from_address', 'e.subject', 'e.sent_at',
                'e.attachment_count', 'e.information_object_id', 'e.fileplan_node_id',
                'e.disposal_class_id', 'e.capture_source', 'e.status', 'e.created_at',
                'fp.code as fileplan_code', 'fp.title as fileplan_title');

        if (!empty($filters['status'])) {
            $q->where('e.status', $filters['status']);
        }
        if (!empty($filters['q'])) {
            $term = '%' . $filters['q'] . '%';
            $q->where(function ($w) use ($term) {
                $w->where('e.subject', 'like', $term)->orWhere('e.from_address', 'like', $term);
            });
        }
        $total = (clone $q)->count();
        $rows = $q->orderByDesc('e.sent_at')->orderByDesc('e.id')
            ->limit($filters['limit'] ?? 100)->offset($filters['offset'] ?? 0)->get()->all();

        return ['rows' => $rows, 'total' => $total];
    }

    public function get(int $id): ?object
    {
        return DB::table('rm_email_capture as e')
            ->leftJoin('rm_fileplan_node as fp', 'fp.id', '=', 'e.fileplan_node_id')
            ->select('e.*', 'fp.code as fileplan_code', 'fp.title as fileplan_title')
            ->where('e.id', $id)->first();
    }

    public function counts(): array
    {
        $base = DB::table('rm_email_capture');

        return [
            'total' => (clone $base)->count(),
            'captured' => (clone $base)->where('status', 'captured')->count(),
            'classified' => (clone $base)->where('status', 'classified')->count(),
            'declared' => (clone $base)->where('status', 'declared')->count(),
        ];
    }

    /**
     * Capture an uploaded .eml. Stores the original file and inserts a parsed
     * rm_email_capture row. Idempotent by Message-ID.
     *
     * @return array{id:int, message_id:string, duplicate:bool}
     */
    public function captureFromEml(string $realPath, int $userId): array
    {
        $raw = is_readable($realPath) ? file_get_contents($realPath) : false;
        if ($raw === false || trim($raw) === '') {
            throw new \RuntimeException('EML file is empty or unreadable');
        }
        $parsed = $this->parseEml($raw);

        if ($parsed['message_id'] !== '') {
            $existing = DB::table('rm_email_capture')->where('message_id', $parsed['message_id'])->value('id');
            if ($existing) {
                return ['id' => (int) $existing, 'message_id' => $parsed['message_id'], 'duplicate' => true];
            }
        } else {
            $parsed['message_id'] = 'ahg-capture-' . $this->uuid();
        }

        $storagePath = $this->saveEmlBlob($parsed['message_id'], $raw);

        $id = DB::table('rm_email_capture')->insertGetId([
            'message_id' => $parsed['message_id'],
            'from_address' => $parsed['from'],
            'to_addresses' => $parsed['to'],
            'cc_addresses' => $parsed['cc'],
            'subject' => $parsed['subject'],
            'sent_at' => $parsed['sent_at'],
            'received_at' => $parsed['received_at'],
            'body_text' => $parsed['body_text'],
            'body_html' => $parsed['body_html'],
            'attachment_count' => $parsed['attachment_count'],
            'eml_storage_path' => $storagePath,
            'capture_source' => 'eml_upload',
            'status' => 'captured',
            'captured_by' => $userId,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        return ['id' => (int) $id, 'message_id' => $parsed['message_id'], 'duplicate' => false];
    }

    public function classify(int $id, int $fileplanNodeId, ?int $disposalClassId, int $userId): bool
    {
        $update = ['fileplan_node_id' => $fileplanNodeId, 'status' => 'classified'];
        if ($disposalClassId !== null) {
            $update['disposal_class_id'] = $disposalClassId;
        }

        return DB::table('rm_email_capture')->where('id', $id)->update($update) > 0;
    }

    /**
     * Declare a captured email as an information_object record (Qubit
     * class-table inheritance: object → information_object → i18n → slug),
     * appended as last child of the root. Transactional.
     *
     * @return int|null new information_object.id, or null on failure
     */
    public function declareAsRecord(int $id, int $userId): ?int
    {
        $email = $this->get($id);
        if (!$email) {
            return null;
        }
        if ($email->information_object_id) {
            return (int) $email->information_object_id;
        }

        try {
            return DB::connection()->transaction(function () use ($email, $id, $userId) {
                $title = $email->subject ?: '[No subject]';
                $now = date('Y-m-d H:i:s');
                $culture = 'en';

                $objectId = (int) DB::table('object')->insertGetId([
                    'class_name' => 'QubitInformationObject',
                    'created_at' => $now,
                    'updated_at' => $now,
                    'serial_number' => 0,
                ]);

                $root = DB::table('information_object')->where('id', 1)->select('rgt')->first();
                $newLft = $root ? (int) $root->rgt : 0;
                $newRgt = $newLft + 1;
                if ($root) {
                    DB::table('information_object')->where('rgt', '>=', $root->rgt)->increment('rgt', 2);
                    DB::table('information_object')->where('lft', '>', $root->rgt)->increment('lft', 2);
                }

                DB::table('information_object')->insert([
                    'id' => $objectId,
                    'identifier' => 'EMAIL-' . $id,
                    'parent_id' => 1,
                    'lft' => $newLft,
                    'rgt' => $newRgt,
                    'source_culture' => $culture,
                ]);

                DB::table('information_object_i18n')->insert([
                    'id' => $objectId,
                    'culture' => $culture,
                    'title' => $title,
                    'scope_and_content' => $this->summariseForScope($email),
                ]);

                $baseSlug = $this->slugify($title) ?: ('email-' . $id);
                $slug = $baseSlug;
                $counter = 1;
                while (DB::table('slug')->where('slug', $slug)->exists()) {
                    $slug = $baseSlug . '-' . $counter++;
                }
                DB::table('slug')->insert(['object_id' => $objectId, 'slug' => $slug]);

                if ($email->disposal_class_id && DB::schema()->hasTable('rm_record_disposal_class')) {
                    $startDate = substr((string) ($email->sent_at ?? $email->received_at ?? $now), 0, 10) ?: date('Y-m-d');
                    DB::table('rm_record_disposal_class')->insert([
                        'information_object_id' => $objectId,
                        'disposal_class_id' => $email->disposal_class_id,
                        'assigned_by' => $userId,
                        'retention_start_date' => $startDate,
                        'created_at' => $now,
                    ]);
                }

                DB::table('rm_email_capture')->where('id', $id)
                    ->update(['information_object_id' => $objectId, 'status' => 'declared']);

                return $objectId;
            });
        } catch (\Throwable $e) {
            error_log('rm: declareAsRecord failed for email ' . $id . ': ' . $e->getMessage());

            return null;
        }
    }

    // ── EML parsing (hand-rolled MVP; common cases only) ────────────────

    public function parseEml(string $raw): array
    {
        $raw = str_replace(["\r\n", "\r"], "\n", $raw);
        [$headerBlock, $body] = array_pad(explode("\n\n", $raw, 2), 2, '');
        $headers = $this->parseHeaders($headerBlock);

        $sentAt = $this->parseHeaderDate($headers['date'] ?? null);
        $receivedAt = null;
        if (!empty($headers['received'])) {
            $first = is_array($headers['received']) ? $headers['received'][0] : $headers['received'];
            if (preg_match('/;\s*(.+)$/', $first, $m)) {
                $receivedAt = $this->parseHeaderDate($m[1]);
            }
        }

        $contentType = strtolower($headers['content-type'] ?? 'text/plain');
        $bodyText = null;
        $bodyHtml = null;
        $attachmentCount = 0;

        if (str_starts_with($contentType, 'multipart/') && preg_match('/boundary="?([^";]+)"?/', $headers['content-type'] ?? '', $m)) {
            foreach ($this->splitMultipart($body, $m[1]) as $part) {
                [$ph, $pb] = array_pad(explode("\n\n", $part, 2), 2, '');
                $pHeaders = $this->parseHeaders($ph);
                $pType = strtolower($pHeaders['content-type'] ?? 'text/plain');
                if (str_contains(strtolower($pHeaders['content-disposition'] ?? ''), 'attachment')) {
                    ++$attachmentCount;
                    continue;
                }
                if (str_starts_with($pType, 'text/plain') && $bodyText === null) {
                    $bodyText = $this->decodePart($pb, $pHeaders);
                } elseif (str_starts_with($pType, 'text/html') && $bodyHtml === null) {
                    $bodyHtml = $this->decodePart($pb, $pHeaders);
                }
            }
        } elseif (str_starts_with($contentType, 'text/html')) {
            $bodyHtml = $this->decodePart($body, $headers);
        } else {
            $bodyText = $this->decodePart($body, $headers);
        }

        return [
            'message_id' => trim((string) ($headers['message-id'] ?? ''), " <>\t\r\n"),
            'from' => $this->cleanAddress($headers['from'] ?? ''),
            'to' => $this->cleanAddress($headers['to'] ?? ''),
            'cc' => $this->cleanAddress($headers['cc'] ?? ''),
            'subject' => $this->decodeHeader($headers['subject'] ?? ''),
            'sent_at' => $sentAt,
            'received_at' => $receivedAt,
            'body_text' => $bodyText,
            'body_html' => $bodyHtml,
            'attachment_count' => $attachmentCount,
        ];
    }

    private function parseHeaders(string $block): array
    {
        $headers = [];
        $current = '';
        foreach (explode("\n", $block) as $line) {
            if ($line === '') {
                continue;
            }
            if (preg_match('/^\s/', $line) && $current !== '') {
                $prev = is_array($headers[$current]) ? end($headers[$current]) : $headers[$current];
                $headers[$current] = $prev . ' ' . trim($line);
                continue;
            }
            if (!preg_match('/^([!-~]+):\s*(.*)$/', $line, $m)) {
                continue;
            }
            $key = strtolower($m[1]);
            if (isset($headers[$key])) {
                $headers[$key] = (array) $headers[$key];
                $headers[$key][] = $m[2];
            } else {
                $headers[$key] = $m[2];
            }
            $current = $key;
        }

        return $headers;
    }

    private function splitMultipart(string $body, string $boundary): array
    {
        $segments = explode('--' . $boundary, $body);
        array_shift($segments);
        if (!empty($segments) && trim((string) end($segments)) === '--') {
            array_pop($segments);
        }

        return array_map(fn ($s) => ltrim($s, "\n"), $segments);
    }

    private function decodePart(string $body, array $headers): string
    {
        $encoding = strtolower(trim($headers['content-transfer-encoding'] ?? ''));
        $decoded = match ($encoding) {
            'base64' => base64_decode(preg_replace('/\s+/', '', $body)) ?: $body,
            'quoted-printable' => quoted_printable_decode($body),
            default => $body,
        };
        if (!mb_check_encoding($decoded, 'UTF-8')) {
            $decoded = mb_convert_encoding($decoded, 'UTF-8',
                mb_detect_encoding($decoded, ['UTF-8', 'ISO-8859-1', 'Windows-1252'], true) ?: 'UTF-8');
        }

        return $decoded;
    }

    private function decodeHeader(string $value): string
    {
        if (function_exists('iconv_mime_decode')) {
            $decoded = @iconv_mime_decode($value, ICONV_MIME_DECODE_CONTINUE_ON_ERROR, 'UTF-8');
            if (is_string($decoded) && $decoded !== '') {
                return trim($decoded);
            }
        }

        return trim($value);
    }

    private function cleanAddress(string $value): string
    {
        return trim(preg_replace('/\s+/', ' ', $this->decodeHeader($value)));
    }

    private function parseHeaderDate(?string $value): ?string
    {
        if (!$value) {
            return null;
        }
        $ts = strtotime($value);

        return $ts ? date('Y-m-d H:i:s', $ts) : null;
    }

    private function saveEmlBlob(string $messageId, string $raw): string
    {
        $base = class_exists('\sfConfig')
            ? \sfConfig::get('sf_upload_dir', \sfConfig::get('sf_root_dir', '/usr/share/nginx/archive') . '/uploads')
            : '/usr/share/nginx/archive/uploads';
        $dir = rtrim($base, '/') . '/rm/email-capture/' . date('Y/m');
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        $safe = preg_replace('/[^A-Za-z0-9._-]/', '_', $messageId);
        $path = $dir . '/' . substr($safe, 0, 200) . '.eml';
        @file_put_contents($path, $raw);

        return $path;
    }

    private function summariseForScope(object $email): string
    {
        $parts = [];
        if (!empty($email->from_address)) {
            $parts[] = 'From: ' . $email->from_address;
        }
        if (!empty($email->to_addresses)) {
            $parts[] = 'To: ' . $email->to_addresses;
        }
        if (!empty($email->sent_at)) {
            $parts[] = 'Sent: ' . $email->sent_at;
        }
        $excerpt = trim((string) ($email->body_text ?? strip_tags((string) $email->body_html)));
        if ($excerpt !== '') {
            $parts[] = '--- Body excerpt ---';
            $parts[] = mb_strlen($excerpt) > 1500 ? mb_substr($excerpt, 0, 1500) . "\n…[truncated]" : $excerpt;
        }

        return implode("\n", $parts);
    }

    private function uuid(): string
    {
        $d = random_bytes(16);
        $d[6] = chr((ord($d[6]) & 0x0F) | 0x40);
        $d[8] = chr((ord($d[8]) & 0x3F) | 0x80);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($d), 4));
    }

    private function slugify(string $text): string
    {
        $text = strtolower(trim($text));
        $text = preg_replace('/[^a-z0-9]+/', '-', $text);

        return substr(trim($text, '-'), 0, 200);
    }
}
