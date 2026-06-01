<?php

namespace AtomFramework\Console\Commands\Library;

use AtomFramework\Console\BaseCommand;
use Illuminate\Database\Capsule\Manager as DB;

/**
 * z3950:server — Start the Z39.50 bibliographic server daemon (ISO 23950).
 *
 * PSIS already exposes the catalogue via SRU-over-HTTP (/api/sru). This command
 * adds the raw binary Z39.50 *server* half: it answers INIT / SEARCH / PRESENT /
 * DELETE / CLOSE APDUs from Z39.50 clients (Koha, Evergreen, VTLS, EndNote)
 * directly against the AtoM library catalogue.
 *
 * Usage:
 *   php bin/atom z3950:server [--host=0.0.0.0] [--port=9210] [--timeout=30]
 *
 * Privileged port 210 (the IANA Z39.50 port) requires root; prefer a high port
 * (e.g. 9210) and NAT-forward 210 → 9210 with iptables. Run under systemd:
 *
 *   [Unit]
 *   Description=PSIS Z39.50 Server
 *   After=network.target mysql.service
 *
 *   [Service]
 *   Type=simple
 *   User=www-data
 *   WorkingDirectory=/usr/share/nginx/archive
 *   ExecStart=/usr/bin/php bin/atom z3950:server --port=9210
 *   Restart=on-failure
 *   RestartSec=10s
 *
 *   [Install]
 *   WantedBy=multi-user.target
 *
 * Dependency note: this daemon is pure PHP and needs the `sockets` extension
 * (ext-sockets). It does NOT require the YAZ extension (YAZ is only used by the
 * outbound Z39.50 *client*). If ext-sockets is absent the command exits cleanly
 * with installation guidance rather than fatally erroring.
 *
 * @package ahgLibraryPlugin
 */
class Z3950ServerCommand extends BaseCommand
{
    protected string $name = 'z3950:server';
    protected string $description = 'Start the Z39.50 bibliographic server daemon (ISO 23950)';
    protected string $detailedDescription = <<<'EOF'
    Answers raw binary Z39.50 client queries against the AtoM library catalogue.

    Examples:
      php bin/atom z3950:server
      php bin/atom z3950:server --port=9210
      php bin/atom z3950:server --host=127.0.0.1 --port=9210 --timeout=60

    Requires the PHP sockets extension (apt install php-cli; ext-sockets is
    bundled by default). Privileged port 210 needs root; prefer a high port.
    EOF;

    /** @var resource|null */
    private $serverSocket;

    private bool $running = true;

    protected function configure(): void
    {
        $this->addOption('host', null, 'Host/interface to bind to', '0.0.0.0');
        $this->addOption('port', null, 'TCP port to listen on', '9210');
        $this->addOption('timeout', null, 'Per-client idle timeout in seconds', '30');
        $this->addOption('max-result-set', null, 'Server-side cap on a result set', '1000');
    }

    protected function handle(): int
    {
        $host    = (string) $this->option('host');
        $port    = (int) $this->option('port');
        $timeout = (int) $this->option('timeout');
        $maxSet  = (int) $this->option('max-result-set');

        if ($port < 1 || $port > 65535) {
            $this->error("Port must be between 1 and 65535, got {$port}.");

            return 1;
        }

        if ($timeout < 1 || $timeout > 3600) {
            $this->error('Timeout must be between 1 and 3600 seconds.');

            return 1;
        }

        // Graceful dependency fallback: no ext-sockets → exit cleanly.
        if (!extension_loaded('sockets')) {
            $this->error('The PHP "sockets" extension is required for the Z39.50 server daemon.');
            $this->line('Install it with: apt-get install php-cli  (ext-sockets is bundled by default)');
            $this->line('SRU-over-HTTP (/api/sru) remains available without this daemon.');

            return 1;
        }

        if ($port < 1024 && function_exists('posix_geteuid') && posix_geteuid() !== 0) {
            $this->warning("Port {$port} is privileged; bind a high port (e.g. 9210) and NAT-forward instead.");
        }

        // Load the server service (Symfony 1.x does not autoload namespaced plugin classes).
        $serviceDir = $this->atomRoot . '/atom-ahg-plugins/ahgLibraryPlugin/lib/Service';
        require_once $serviceDir . '/BerEncoder.class.php';
        require_once $serviceDir . '/Z3950ServerService.class.php';

        $server = new \AtomExtensions\Services\Z3950ServerService();
        $server->setMaxResultSet(max(1, $maxSet));

        if (!$this->openSocket($host, $port)) {
            return 1;
        }

        // Best-effort signal handling for clean shutdown.
        if (function_exists('pcntl_signal') && function_exists('pcntl_async_signals')) {
            pcntl_async_signals(true);
            pcntl_signal(SIGINT, fn () => $this->running = false);
            pcntl_signal(SIGTERM, fn () => $this->running = false);
        }

        $this->info("Z39.50 server listening on {$host}:{$port} (timeout {$timeout}s)");
        $this->line('  Press Ctrl+C to stop.');

        $this->acceptLoop($server, $timeout);

        if (is_resource($this->serverSocket)) {
            socket_close($this->serverSocket);
        }

        $this->success('Z39.50 server stopped.');

        return 0;
    }

    private function openSocket(string $host, int $port): bool
    {
        $sock = @socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        if ($sock === false) {
            $this->error('socket_create failed: ' . socket_strerror(socket_last_error()));

            return false;
        }

        @socket_set_option($sock, SOL_SOCKET, SO_REUSEADDR, 1);

        if (@socket_bind($sock, $host, $port) === false) {
            $this->error("socket_bind to {$host}:{$port} failed: " . socket_strerror(socket_last_error($sock)));
            socket_close($sock);

            return false;
        }

        if (@socket_listen($sock, 16) === false) {
            $this->error('socket_listen failed: ' . socket_strerror(socket_last_error($sock)));
            socket_close($sock);

            return false;
        }

        $this->serverSocket = $sock;

        return true;
    }

    private function acceptLoop(\AtomExtensions\Services\Z3950ServerService $server, int $timeout): void
    {
        while ($this->running) {
            $read = [$this->serverSocket];
            $write = null;
            $except = null;

            // 1s tick so signals get a chance to flip $this->running.
            $ready = @socket_select($read, $write, $except, 1);
            if ($ready === false) {
                // Interrupted by a signal; loop and re-check $this->running.
                continue;
            }
            if ($ready < 1) {
                continue;
            }

            $client = @socket_accept($this->serverSocket);
            if ($client === false) {
                continue;
            }

            $this->handleClient($server, $client, $timeout);
            $server->clearResultSets();
        }
    }

    private function handleClient(\AtomExtensions\Services\Z3950ServerService $server, $client, int $timeout): void
    {
        $clientAddr = '';
        @socket_getpeername($client, $clientAddr);

        @socket_set_option($client, SOL_SOCKET, SO_RCVTIMEO, ['sec' => $timeout, 'usec' => 0]);
        @socket_set_option($client, SOL_SOCKET, SO_SNDTIMEO, ['sec' => $timeout, 'usec' => 0]);

        while ($this->running) {
            $packet = $this->readPackage($client);
            if ($packet === '') {
                break;
            }

            $start = microtime(true);
            try {
                $result = $server->routePackage($packet);
            } catch (\Throwable $e) {
                $this->logRequest($clientAddr, 'error', strlen($packet), null, 0.0, $e->getMessage());
                break;
            }
            $elapsedMs = (int) round((microtime(true) - $start) * 1000);

            $this->logRequest(
                $clientAddr,
                $result['type'] ?? 'unknown',
                strlen($packet),
                $result['resultCount'] ?? null,
                $elapsedMs,
                $result['error'] ?? null
            );

            $response = $result['response'] ?? '';
            if ($response !== '') {
                $this->writeAll($client, $response);
            }

            if (($result['type'] ?? '') === 'close') {
                break;
            }
        }

        @socket_close($client);
    }

    /**
     * Read one Z39.50 package: 2-byte size prefix (payload+5) then the body.
     */
    private function readPackage($client): string
    {
        $header = $this->readExactly($client, 2);
        if (strlen($header) < 2) {
            return '';
        }

        $unpacked = unpack('nsize', $header);
        $size = $unpacked['size'] ?? 0;
        if ($size < 5 || $size > 16 * 1024 * 1024) {
            return '';
        }

        // We already consumed the 2 size bytes; read the remaining 3 header
        // bytes + payload (size = total package incl. 5-byte header).
        $remaining = $size - 2;
        $rest = $this->readExactly($client, $remaining);
        if (strlen($rest) < $remaining) {
            return '';
        }

        return $header . $rest;
    }

    private function readExactly($client, int $length): string
    {
        $buf = '';
        while (strlen($buf) < $length) {
            $chunk = @socket_read($client, $length - strlen($buf), PHP_BINARY_READ);
            if ($chunk === false || $chunk === '') {
                break;
            }
            $buf .= $chunk;
        }

        return $buf;
    }

    private function writeAll($client, string $data): void
    {
        $total = strlen($data);
        $sent = 0;
        while ($sent < $total) {
            $n = @socket_write($client, substr($data, $sent), $total - $sent);
            if ($n === false) {
                break;
            }
            $sent += $n;
        }
    }

    /**
     * Persist an incoming APDU to library_z3950_server_request (best-effort).
     */
    private function logRequest(
        string $clientAddr,
        string $apduType,
        int $bytesReceived,
        ?int $resultCount,
        float $elapsedMs,
        ?string $error
    ): void {
        try {
            DB::table('library_z3950_server_request')->insert([
                'client_addr'    => substr($clientAddr, 0, 45),
                'apdu_type'      => substr($apduType, 0, 32),
                'bytes_received' => $bytesReceived,
                'result_count'   => $resultCount,
                'elapsed_ms'     => (int) $elapsedMs,
                'error_detail'   => $error,
                'created_at'     => date('Y-m-d H:i:s'),
            ]);
        } catch (\Throwable $e) {
            // Logging must never break the daemon.
        }
    }
}
