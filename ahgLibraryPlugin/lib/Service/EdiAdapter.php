<?php

declare(strict_types=1);

/**
 * EdiAdapter
 *
 * EDI/EANCOM message dispatcher for library trading partners. Builds ILL
 * request payloads (EANCOM S93/S94, UN/EDIFACT, X12 850, CUSTOM/JSON) and
 * transmits them via SFTP / AS2 / HTTP(S) / EMAIL / MANUAL endpoints.
 *
 * Framework-agnostic Symfony port of the Heratio (Laravel)
 * AhgLibrary\Services\EdiAdapter — operates on plain stdClass rows from
 * library_trading_partner / library_ill_request (no Eloquent), uses Guzzle
 * (when present) for AS2/HTTP and phpseclib3 (when present) for SFTP, with
 * graceful degradation when an optional transport library is missing.
 *
 * @package    ahgLibraryPlugin
 * @subpackage Service
 */

use Illuminate\Database\Capsule\Manager as DB;

class EdiAdapter
{
    private const TABLE = 'library_trading_partner';

    /** @var object|null A library_trading_partner row (endpoint_config decoded). */
    protected ?object $partner = null;

    public function __construct(?object $partner = null)
    {
        $this->partner = $partner ? $this->normalisePartner($partner) : null;
    }

    /**
     * Decode endpoint_config JSON into an array on the partner row.
     */
    private function normalisePartner(object $tp): object
    {
        if (isset($tp->endpoint_config) && is_string($tp->endpoint_config)) {
            $tp->endpoint_config = json_decode($tp->endpoint_config, true) ?: [];
        } elseif (!isset($tp->endpoint_config) || !is_array($tp->endpoint_config)) {
            $tp->endpoint_config = [];
        }

        return $tp;
    }

    private function cfg(object $tp): array
    {
        return is_array($tp->endpoint_config ?? null) ? $tp->endpoint_config : [];
    }

    /** Random uppercase token of $n hex chars. */
    private function token(int $n): string
    {
        return strtoupper(substr(bin2hex(random_bytes((int) ceil($n / 2))), 0, $n));
    }

    /** Local sender host, derived from the site base URL when available. */
    private function senderHost(): string
    {
        $base = (string) (\sfConfig::get('app_siteBaseUrl', '') ?: \sfConfig::get('sf_root_dir', ''));
        $host = $base ? (parse_url($base, PHP_URL_HOST) ?: '') : '';

        return strtoupper($host ?: 'PSIS');
    }

    private function truncate(?string $text, int $max): string
    {
        if (!$text) {
            return '';
        }

        return mb_strlen($text) > $max ? mb_substr($text, 0, $max) : $text;
    }

    // ── Connection testing ──────────────────────────────────────────────

    /**
     * @return array{ok:bool,message:string,details:array}
     */
    public function testConnection(?object $partner = null): array
    {
        $tp = $partner ? $this->normalisePartner($partner) : $this->partner;
        if (!$tp) {
            return ['ok' => false, 'message' => 'No trading partner configured.', 'details' => []];
        }

        switch ($tp->endpoint_type ?? '') {
            case 'SFTP':       return $this->testSftp($tp);
            case 'AS2':        return $this->testAs2($tp);
            case 'HTTP_HTTPS': return $this->testHttp($tp);
            case 'EMAIL':      return $this->testEmail($tp);
            case 'MANUAL':     return ['ok' => true, 'message' => 'Manual mode: no connection test needed.', 'details' => []];
            default:           return ['ok' => false, 'message' => 'Unknown endpoint type: ' . ($tp->endpoint_type ?? ''), 'details' => []];
        }
    }

    protected function testSftp(object $tp): array
    {
        $cfg  = $this->cfg($tp);
        $host = $cfg['host'] ?? '';
        $port = (int) ($cfg['port'] ?? 22);

        if (empty($host)) {
            return ['ok' => false, 'message' => 'SFTP host not configured.', 'details' => []];
        }

        $fp = @fsockopen($host, $port, $errno, $errstr, 10);
        if ($fp) {
            fclose($fp);

            return ['ok' => true, 'message' => "TCP open {$host}:{$port}", 'details' => ['host' => $host, 'port' => $port]];
        }

        return ['ok' => false, 'message' => "{$errstr} ({$errno})", 'details' => ['host' => $host]];
    }

    protected function testAs2(object $tp): array
    {
        $cfg = $this->cfg($tp);
        $url = $cfg['as2_url'] ?? '';

        if (empty($url)) {
            return ['ok' => false, 'message' => 'AS2 URL not configured.', 'details' => []];
        }
        if (!class_exists(\GuzzleHttp\Client::class)) {
            return ['ok' => false, 'message' => 'HTTP client (Guzzle) not available.', 'details' => ['url' => $url]];
        }

        try {
            $client = new \GuzzleHttp\Client(['timeout' => 15, 'verify' => true, 'allow_redirects' => false]);
            $resp = $client->request('HEAD', $url);

            return ['ok' => $resp->getStatusCode() < 500, 'message' => 'AS2 endpoint HTTP ' . $resp->getStatusCode(), 'details' => ['url' => $url]];
        } catch (\Throwable $e) {
            return ['ok' => false, 'message' => 'AS2 test failed: ' . $e->getMessage(), 'details' => ['url' => $url]];
        }
    }

    protected function testHttp(object $tp): array
    {
        $cfg = $this->cfg($tp);
        $url = $cfg['url'] ?? '';

        if (empty($url)) {
            return ['ok' => false, 'message' => 'HTTP/HTTPS URL not configured.', 'details' => []];
        }
        if (!class_exists(\GuzzleHttp\Client::class)) {
            return ['ok' => false, 'message' => 'HTTP client (Guzzle) not available.', 'details' => ['url' => $url]];
        }

        try {
            $client = new \GuzzleHttp\Client(['timeout' => 15, 'verify' => true]);
            $resp = $client->request('GET', $url);

            return ['ok' => $resp->getStatusCode() < 500, 'message' => 'HTTP ' . $resp->getStatusCode(), 'details' => ['url' => $url]];
        } catch (\Throwable $e) {
            return ['ok' => false, 'message' => 'HTTP test failed: ' . $e->getMessage(), 'details' => ['url' => $url]];
        }
    }

    protected function testEmail(object $tp): array
    {
        $cfg  = $this->cfg($tp);
        $host = $cfg['smtp_host'] ?? 'localhost';
        $port = (int) ($cfg['smtp_port'] ?? 587);

        return ['ok' => true, 'message' => "SMTP configured: {$host}:{$port}", 'details' => ['host' => $host, 'port' => $port]];
    }

    // ── Message building ────────────────────────────────────────────────

    /**
     * Build an EDI message payload for an ILL request row.
     *
     * @param object      $request   A library_ill_request row (stdClass).
     * @param object|null $requester Optional requesting-library row (code/name/address/contact_email).
     * @param object|null $responder Optional responding-library row.
     * @return array{raw:string,envelope:string,type:string,msg_ref:string}
     */
    public function buildIllRequestMessage(object $request, ?object $requester = null, ?object $responder = null): array
    {
        $type = $this->partner->edi_type ?? 'EANCOM';

        switch ($type) {
            case 'UN/EDIFACT': return $this->buildEdifact($request, $requester, $responder);
            case 'X12':        return $this->buildX12($request);
            case 'CUSTOM':     return $this->buildCustom($request);
            case 'EANCOM':
            default:           return $this->buildEancom($request, $requester, $responder);
        }
    }

    protected function buildEancom(object $request, ?object $requester, ?object $responder): array
    {
        $profile  = $this->partner->message_profile ?? 'EANCOM_S93';
        $sender   = $this->senderHost();
        $receiver = $this->partner->edi_partner_code ?? 'PARTNER';
        $msgRef   = 'MR' . $this->token(12);
        $grpRef   = 'GR' . $this->token(8);
        $nowFmt   = date('ymdHis');

        $unb = "UNB+UNOC:3+{$sender}:ZZZ+{$receiver}:ZZZ+{$nowFmt}+{$msgRef}'";
        $ung = "UNG+ILLIC+:ZZ+{$sender}:ZZZ+{$receiver}:ZZZ+{$nowFmt}+{$grpRef}'";

        $msgId = $profile === 'EANCOM_S94' ? '24' : '23';
        $unh   = "UNH+{$msgRef}+ILLIC:{$msgId}:3:UN'";

        $bgmRef = 'BGM' . $this->token(8);
        $bgm    = "BGM+24+{$bgmRef}+AC'";

        $nadReq = $requester
            ? 'NAD+LR+' . ($requester->code ?? '') . '++' . $this->truncate($requester->name ?? '', 35) . '+' . ($requester->address ?? '') . '++' . ($requester->contact_email ?? '') . "'"
            : "NAD+LR+::SYSTEM+{$sender}'";

        $nadResp = $responder
            ? 'NAD+LE+' . ($responder->code ?? '') . '++' . $this->truncate($responder->name ?? '', 35) . '+' . ($responder->address ?? '') . '++' . ($responder->contact_email ?? '') . "'"
            : '';

        $isbnSeg = !empty($request->isbn) ? "LIN+1+++ISBN:IB' " : '';
        $issnSeg = !empty($request->issn) ? "LIN+2+++ISSN:IB' " : '';
        $lin     = rtrim("LIN+1+{$isbnSeg}{$issnSeg}", ' ');

        $author = $this->truncate($request->author ?? '', 200);
        $imd    = "IMD+L+++:::EN+{$author}:AU'";

        $reqDate    = !empty($request->request_date) ? date('ymd', strtotime((string) $request->request_date)) : date('ymd');
        $neededDate = !empty($request->needed_by_date) ? 'DTM+369:' . date('ymd', strtotime((string) $request->needed_by_date)) . ":102' " : '';
        $dtm        = trim("DTM+137:{$reqDate}:102' {$neededDate}");

        $ftx = !empty($request->requester_note)
            ? "FTX+GEN+++" . $this->truncate($request->requester_note, 300) . "'"
            : '';

        $body         = $unb . $ung . $unh . $bgm . $nadReq . $nadResp . $lin . $imd . $dtm . $ftx;
        $segmentCount = substr_count($body, "'") + 1;
        $unt          = "UNT+{$segmentCount}+{$msgRef}'";
        $une          = "UNE+1+{$grpRef}'";
        $unz          = "UNZ+1+{$msgRef}'";

        $raw = implode("\n", array_filter([$unb, $ung, $unh, $bgm, $nadReq, $nadResp, $lin, $imd, $dtm, $ftx, $unt, $une, $unz]));

        return ['raw' => $raw, 'envelope' => $unb, 'type' => 'EANCOM', 'msg_ref' => $msgRef];
    }

    protected function buildEdifact(object $request, ?object $requester, ?object $responder): array
    {
        $result = $this->buildEancom($request, $requester, $responder);
        $result['type']     = 'UN/EDIFACT';
        $result['envelope'] = preg_replace('/\+UNOC:3\+/', '+UNB+', $result['envelope']) ?: $result['envelope'];
        $result['raw']      = preg_replace('/\+UNOC:3\+/', '+UNB+', $result['raw']) ?: $result['raw'];

        return $result;
    }

    protected function buildX12(object $request): array
    {
        $isaCtrl  = str_pad((string) random_int(1, 999999999), 9, '0', STR_PAD_LEFT);
        $gsCtrl   = str_pad((string) random_int(1, 999999), 4, '0', STR_PAD_LEFT);
        $stCtrl   = str_pad((string) random_int(1, 99999), 4, '0', STR_PAD_LEFT);
        $sender   = $this->senderHost();
        $receiver = $this->partner->edi_partner_code ?? 'PARTNER';
        $ymd      = date('ymd');
        $hi       = date('Hi');

        $senderPad   = str_pad($sender, 15);
        $receiverPad = str_pad($receiver, 15);

        $isa = "ISA*00*          *00*          *ZZ*{$senderPad}*ZZ*{$receiverPad}*{$ymd}*{$hi}*^*00501*{$isaCtrl}*0*P*:~";
        $gs  = "GS*IL*{$sender}*{$receiver}*{$ymd}*{$hi}*{$gsCtrl}*X*005010X22A~";
        $st  = "ST*850*{$stCtrl}~";
        $ref = 'REF*IV*' . ($request->ill_number ?? ('ILL-' . ($request->id ?? '0'))) . '~';
        $n1  = "N1*LB*{$sender}*92*~";
        $n2  = !empty($request->requester_note) ? 'N2*' . $this->truncate($request->requester_note, 60) . '~' : '';
        $neededYmd = !empty($request->needed_by_date) ? date('ymd', strtotime((string) $request->needed_by_date)) : $ymd;
        $po1 = 'PO1*1*1*EA*' . number_format((float) ($request->cost_amount ?? 0), 2, '.', '') . '*UK*'
            . ($request->isbn ?? ($request->issn ?? '')) . "**PF*ILL~\n"
            . 'PID*F****' . $this->truncate($request->title ?? '', 80) . "~\n"
            . 'PAT*06***' . $neededYmd . '~';
        $ctt = 'CTT*1~';
        $se  = 'SE*' . (substr_count($st . $ref . $n1 . $n2 . $po1 . $ctt, '~') + 1) . "*{$stCtrl}~";
        $ge  = "GE*1*{$gsCtrl}~";
        $iea = "IEA*1*{$isaCtrl}~";

        $raw = implode("\n", array_filter([$isa, $gs, $st, $ref, $n1, $n2, $po1, $ctt, $se, $ge, $iea]));

        return ['raw' => $raw, 'envelope' => $isa, 'type' => 'X12 850', 'msg_ref' => $stCtrl];
    }

    protected function buildCustom(object $request): array
    {
        $raw = json_encode([
            'ill_number'         => $request->ill_number ?? null,
            'title'              => $request->title ?? null,
            'author'             => $request->author ?? null,
            'isbn'               => $request->isbn ?? null,
            'issn'               => $request->issn ?? null,
            'volume'             => $request->volume ?? null,
            'issue'              => $request->issue ?? null,
            'pages'              => $request->pages ?? null,
            'requester_note'     => $request->requester_note ?? null,
            'needed_by_date'     => $request->needed_by_date ?? null,
            'request_date'       => $request->request_date ?? null,
            'request_type'       => $request->request_type ?? 'BORROW',
            'borrowing_protocol' => $request->borrowing_protocol ?? 'AARC',
            'material_type'      => $request->material_type ?? 'BOOK',
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        return ['raw' => $raw, 'envelope' => 'CUSTOM/JSON', 'type' => 'CUSTOM', 'msg_ref' => 'CUST' . $this->token(10)];
    }

    // ── Transmission ───────────────────────────────────────────────────

    /**
     * Send an ILL request via the configured endpoint.
     *
     * @return array{ok:bool,message:string,edi_message_id:?string}
     */
    public function sendIllRequest(object $request, ?object $requester = null, ?object $responder = null): array
    {
        $tp = $this->partner;
        if (!$tp) {
            return ['ok' => false, 'message' => 'No trading partner set.', 'edi_message_id' => null];
        }
        if (empty($tp->is_active)) {
            return ['ok' => false, 'message' => 'Trading partner is inactive.', 'edi_message_id' => null];
        }

        if (!empty($tp->test_mode)) {
            return ['ok' => true, 'message' => 'TEST mode: message not transmitted.', 'edi_message_id' => 'TEST-' . $this->token(8)];
        }

        $msg = $this->buildIllRequestMessage($request, $requester, $responder);

        switch ($tp->endpoint_type ?? '') {
            case 'MANUAL':     return $this->prepareManual($msg, $tp);
            case 'SFTP':       return $this->sendViaSftp($msg, $tp);
            case 'AS2':        return $this->sendViaAs2($msg, $tp);
            case 'HTTP_HTTPS': return $this->sendViaHttp($msg, $tp);
            case 'EMAIL':      return $this->sendViaEmail($msg, $tp);
            default:           return ['ok' => false, 'message' => 'Unsupported endpoint: ' . ($tp->endpoint_type ?? ''), 'edi_message_id' => null];
        }
    }

    private function markOutbound(object $tp): void
    {
        DB::table(self::TABLE)->where('id', $tp->id)->update([
            'last_outbound_at'   => date('Y-m-d H:i:s'),
            'last_error_at'      => null,
            'last_error_message' => null,
        ]);
    }

    private function markError(object $tp, string $message): void
    {
        DB::table(self::TABLE)->where('id', $tp->id)->update([
            'last_error_at'      => date('Y-m-d H:i:s'),
            'last_error_message' => $message,
        ]);
    }

    protected function prepareManual(array $msg, object $tp): array
    {
        $root = rtrim((string) \sfConfig::get('sf_root_dir', getcwd()), '/');
        $dir  = $root . '/' . ltrim((string) ($tp->outbound_directory ?? 'outbox'), '/');
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        $filename = ($msg['msg_ref'] ?? 'msg') . '.edi';
        @file_put_contents(rtrim($dir, '/') . '/' . $filename, $msg['raw']);

        $this->markOutbound($tp);

        return ['ok' => true, 'message' => 'Message queued to ' . ($tp->outbound_directory ?? 'outbox') . '/' . $filename, 'edi_message_id' => $msg['msg_ref']];
    }

    protected function sendViaSftp(array $msg, object $tp): array
    {
        $cfg  = $this->cfg($tp);
        $host = $cfg['host'] ?? '';
        $port = (int) ($cfg['port'] ?? 22);
        $user = $cfg['username'] ?? '';
        $pass = $cfg['password'] ?? '';
        $dir  = $cfg['path'] ?? ($tp->outbound_directory ?? '/outbox/');

        if (!class_exists(\phpseclib3\Net\SFTP::class)) {
            return ['ok' => false, 'message' => 'SFTP transport (phpseclib3) not installed.', 'edi_message_id' => null];
        }

        try {
            $sftp = new \phpseclib3\Net\SFTP($host, $port, 30);
            if (!$sftp->login($user, $pass)) {
                throw new \RuntimeException('SFTP authentication failed.');
            }
            $remotePath = rtrim($dir, '/') . '/' . ($msg['msg_ref'] ?? 'msg') . '.edi';
            $sftp->put($remotePath, $msg['raw']);

            $this->markOutbound($tp);

            return ['ok' => true, 'message' => "SFTP upload ok: {$remotePath}", 'edi_message_id' => $msg['msg_ref']];
        } catch (\Throwable $e) {
            $this->markError($tp, $e->getMessage());

            return ['ok' => false, 'message' => 'SFTP error: ' . $e->getMessage(), 'edi_message_id' => null];
        }
    }

    protected function sendViaAs2(array $msg, object $tp): array
    {
        $cfg = $this->cfg($tp);
        $url = $cfg['as2_url'] ?? '';
        if (empty($url)) {
            return ['ok' => false, 'message' => 'AS2 URL not configured.', 'edi_message_id' => null];
        }
        if (!class_exists(\GuzzleHttp\Client::class)) {
            return ['ok' => false, 'message' => 'HTTP client (Guzzle) not available.', 'edi_message_id' => null];
        }

        try {
            $client = new \GuzzleHttp\Client(['timeout' => 60, 'verify' => true]);
            $client->request('POST', $url, [
                'headers' => [
                    'Content-Type' => 'application/edifact',
                    'AS2-Version'  => '1.2',
                    'AS2-From'     => $this->senderHost(),
                    'AS2-To'       => $tp->edi_partner_code ?? '',
                    'Message-ID'   => '<' . ($msg['msg_ref'] ?? $this->token(16)) . '@' . strtolower($this->senderHost()) . '>',
                ],
                'body' => $msg['raw'],
            ]);
            $this->markOutbound($tp);

            return ['ok' => true, 'message' => 'AS2 message sent.', 'edi_message_id' => $msg['msg_ref']];
        } catch (\Throwable $e) {
            $this->markError($tp, $e->getMessage());

            return ['ok' => false, 'message' => 'AS2 error: ' . $e->getMessage(), 'edi_message_id' => null];
        }
    }

    protected function sendViaHttp(array $msg, object $tp): array
    {
        $cfg = $this->cfg($tp);
        $url = $cfg['url'] ?? '';
        if (empty($url)) {
            return ['ok' => false, 'message' => 'HTTP/HTTPS URL not configured.', 'edi_message_id' => null];
        }
        if (!class_exists(\GuzzleHttp\Client::class)) {
            return ['ok' => false, 'message' => 'HTTP client (Guzzle) not available.', 'edi_message_id' => null];
        }

        try {
            $client = new \GuzzleHttp\Client(['timeout' => 60, 'verify' => true]);
            $resp = $client->request('POST', $url, [
                'headers' => ['Content-Type' => 'application/edifact'],
                'body'    => $msg['raw'],
            ]);
            $this->markOutbound($tp);

            return ['ok' => true, 'message' => 'HTTP POST ok: ' . $resp->getStatusCode(), 'edi_message_id' => $msg['msg_ref']];
        } catch (\Throwable $e) {
            $this->markError($tp, $e->getMessage());

            return ['ok' => false, 'message' => 'HTTP error: ' . $e->getMessage(), 'edi_message_id' => null];
        }
    }

    protected function sendViaEmail(array $msg, object $tp): array
    {
        $cfg = $this->cfg($tp);
        $to  = $cfg['smtp_to'] ?? ($cfg['contact_email'] ?? '');
        if (empty($to)) {
            return ['ok' => false, 'message' => 'No recipient email configured.', 'edi_message_id' => null];
        }

        try {
            $from    = $cfg['smtp_from'] ?? ('noreply@' . strtolower($this->senderHost()));
            $subject = 'EDI ILL Message ' . ($msg['msg_ref'] ?? '');
            $headers = 'From: ' . $from . "\r\nContent-Type: text/plain; charset=UTF-8";
            $sent    = @mail($to, $subject, $msg['raw'], $headers);

            if (!$sent) {
                throw new \RuntimeException('mail() returned false.');
            }
            $this->markOutbound($tp);

            return ['ok' => true, 'message' => "EDI message emailed to {$to}", 'edi_message_id' => $msg['msg_ref']];
        } catch (\Throwable $e) {
            $this->markError($tp, $e->getMessage());

            return ['ok' => false, 'message' => 'Email error: ' . $e->getMessage(), 'edi_message_id' => null];
        }
    }
}
