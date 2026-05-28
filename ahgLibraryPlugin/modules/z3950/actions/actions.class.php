<?php

declare(strict_types=1);

/**
 * z3950 actions (Z39.50 client + SRU server control panel).
 *
 * @package ahgLibraryPlugin
 * @subpackage z3950
 */

namespace AtomExtensions\Modules\Z3950;

use AtomFramework\Http\Controllers\AhgController;
use AtomExtensions\Services\Z3950Service;
use AtomExtensions\Services\SruService;
use Illuminate\Database\Capsule\Manager as DB;

class Z3950Actions extends AhgController
{
    protected Z3950Service $z3950;
    protected SruService $sru;

    public function preExecute(): void
    {
        $this->z3950 = Z3950Service::getInstance();
        $this->sru   = new SruService();

        $this->yazLoaded = $this->z3950->isYazLoaded();
        if (!$this->yazLoaded) {
            $this->getUser()->setFlash(
                'warning',
                'The YAZ PHP extension is not installed on this server. '
                . 'Z39.50 operations are disabled. SRU-over-HTTP (CQL searchRetrieve) '
                . 'is still functional. Install with: apt install php-yaz'
            );
        }
    }

    // ========================================================================
    // index — list targets
    // ========================================================================

    public function executeIndex($request)
    {
        $targets = $this->z3950->listTargets();

        // Ping each target to show live status
        foreach ($targets as &$t) {
            $t = (array) $t;
            if (!$this->yazLoaded) {
                $t['_status'] = 'yaz_missing';
                continue;
            }
            if (!$t['is_active']) {
                $t['_status'] = 'inactive';
                continue;
            }
            $ping = $this->z3950->pingTarget((object) $t);
            $t['_status']   = $ping['ok'] ? 'ok' : 'fail';
            $t['_ping_msg'] = $ping['message'];
            $t['_ping_ms']  = $ping['elapsed_ms'];
        }

        $this->targets = $targets;
    }

    // ========================================================================
    // test — AJAX ping + sample query test (called from edit form)
    // ========================================================================

    public function executeTest($request)
    {
        $this->setLayout(false);
        $this->setTemplate(false);
        header('Content-Type: application/json; charset=utf-8');

        $targetId = (int) $request->getParameter('id', 0);
        $host     = trim($request->getParameter('host', ''));
        $port     = (int) ($request->getParameter('port', 210));
        $database = trim($request->getParameter('database', ''));

        // Build a target object from GET params (unsaved target)
        $target = (object) [
            'id'           => $targetId,
            'host'         => $host,
            'port'         => $port,
            'database'     => $database,
            'syntax'       => 'marc21',
            'username'     => '',
            'password_hash' => '',
            'timeout'      => 15,
            'is_active'    => 1,
        ];

        $result = ['ok' => false, 'records' => [], 'error' => '', 'elapsed_ms' => 0];

        try {
            if (!$this->yazLoaded) {
                // SRU fallback test
                $url = "http://{$host}:{$port}/{$database}";
                $start = microtime(true);
                $ctx = stream_context_create(['http' => ['timeout' => 10]]);
                $content = @file_get_contents($url, false, $ctx);
                $elapsed = round((microtime(true) - $start) * 1000, 1);
                if ($content !== false) {
                    $result = ['ok' => true, 'records' => [], 'error' => 'SRU HTTP reachable', 'elapsed_ms' => $elapsed];
                } else {
                    $result['error'] = "Could not reach SRU endpoint at {$url}";
                }
            } else {
                $ping = $this->z3950->pingTarget($target);
                $result['elapsed_ms'] = $ping['elapsed_ms'];

                if ($ping['ok']) {
                    $search = $this->z3950->search($targetId > 0 ? $targetId : 0, 'ti=w', 3, 0);
                    if ($search->error) {
                        $result['error'] = $search->error;
                    } else {
                        $result['ok'] = true;
                        $result['records'] = count($search->records);
                        $result['error'] = $search->hitCount . ' hit(s)';
                    }
                } else {
                    $result['error'] = $ping['message'];
                }
            }
        } catch (\Throwable $e) {
            $result['error'] = $e->getMessage();
        }

        echo json_encode($result);
        return sfView::NONE;
    }

    // ========================================================================
    // edit — create or update target (GET=form, POST=save)
    // ========================================================================

    public function executeEdit($request)
    {
        $id = (int) $request->getParameter('id', 0);
        $this->targetId = $id;

        if ($request->getMethod() === 'POST') {
            $data = [
                'name'       => trim($request->getParameter('name', '')),
                'host'       => trim($request->getParameter('host', '')),
                'port'       => (int) $request->getParameter('port', 210),
                'database'   => trim($request->getParameter('database', '')),
                'syntax'     => $request->getParameter('syntax', 'marc21'),
                'username'   => trim($request->getParameter('username', '')),
                'password'   => $request->getParameter('password', ''),
                'timeout'    => (int) $request->getParameter('timeout', 15),
                'is_active'  => $request->getParameter('is_active', '1') === '1' ? 1 : 0,
            ];

            $errors = [];
            if ($data['name'] === '') {
                $errors[] = 'Name is required.';
            }
            if ($data['host'] === '') {
                $errors[] = 'Host is required.';
            }
            if ($data['port'] < 1 || $data['port'] > 65535) {
                $errors[] = 'Port must be between 1 and 65535.';
            }
            if ($data['database'] === '') {
                $errors[] = 'Database name is required.';
            }

            if (!empty($errors)) {
                foreach ($errors as $err) {
                    $this->getUser()->setFlash('error', $err);
                }
            } else {
                // Blank password = don't update (keep existing)
                if ($data['password'] === '' && $id > 0) {
                    unset($data['password']);
                }

                $savedId = $this->z3950->saveTarget($data, $id ?: null);
                $this->getUser()->setFlash(
                    'notice',
                    $id > 0 ? 'Target updated successfully.' : 'Target created successfully.'
                );
                $this->redirect(url_for(['module' => 'z3950', 'action' => 'index']));
            }
        }

        if ($id > 0) {
            $this->target = (array) $this->z3950->getTarget($id);
        } else {
            $this->target = [];
        }
    }

    // ========================================================================
    // delete — remove a target
    // ========================================================================

    public function executeDelete($request)
    {
        $id = (int) $request->getParameter('id', 0);
        if ($id > 0) {
            $this->z3950->deleteTarget($id);
            $this->getUser()->setFlash('notice', 'Target deleted successfully.');
        }
        $this->redirect(url_for(['module' => 'z3950', 'action' => 'index']));
    }

    // ========================================================================
    // sru — SRU HTTP endpoint handler
    //                            Route: ?module=z3950&action=sru&...
    //                            Also reachable at /api/sru via web server rewrite.
    // ========================================================================

    public function executeSru($request)
    {
        $this->setLayout(false);
        $this->setTemplate(false);

        // Auth: require X-API-Key with scope "sru"
        $this->requireSruAuth();

        // Log SRU request
        $startTime = microtime(true);
        $remoteAddr = $_SERVER['REMOTE_ADDR'] ?? '';

        // Normalise parameters
        $params = [
            'version'         => $request->getParameter('version', '1.1'),
            'operation'       => $request->getParameter('operation', 'searchRetrieve'),
            'query'           => $request->getParameter('query', ''),
            'recordPacking'   => $request->getParameter('recordPacking', 'xml'),
            'maximumRecords'  => $request->getParameter('maximumRecords', 20),
            'startRecord'     => $request->getParameter('startRecord', 1),
            'sortKeys'        => $request->getParameter('sortKeys', ''),
        ];

        // Dispatch operation
        try {
            if ($params['operation'] === 'explain') {
                $xml = $this->sru->explain();
            } else {
                $xml = $this->sru->searchRetrieve($params);
            }

            $duration = round((microtime(true) - $startTime) * 1000, 1);

            // Extract result count from XML for logging
            $resultCount = 0;
            if (preg_match('/<srw:numberOfRecords>(\d+)<\/srw:numberOfRecords>/', $xml, $m)) {
                $resultCount = (int) $m[1];
            }

            $this->logSruRequest(
                $params['query'] ?? '',
                $xml,
                $resultCount,
                $duration,
                $remoteAddr,
                null
            );

            header('Content-Type: application/xml; charset=UTF-8');
            header('Cache-Control: no-cache');
            echo $xml;

        } catch (\Throwable $e) {
            $duration = round((microtime(true) - $startTime) * 1000, 1);
            $this->logSruRequest(
                $params['query'] ?? '',
                '',
                0,
                $duration,
                $remoteAddr,
                $e->getMessage()
            );

            header('Content-Type: application/xml; charset=UTF-8');
            echo $this->sru->xmlHeader('1.1')
                . $this->sru->xmlDiagnostics([[
                    'id'      => 10,
                    'uri'     => 'http://www.loc.gov/zing/srw/diagnostic/',
                    'message' => 'Internal error: ' . $e->getMessage(),
                ]])
                . $this->sru->xmlFooter();
        }

        return sfView::NONE;
    }

    // -------------------------------------------------------------------------
    // Internal helpers
    // -------------------------------------------------------------------------

    /**
     * Require a valid X-API-Key header with scope "sru".
     * Returns 403 and exits if missing or invalid.
     */
    protected function requireSruAuth(): void
    {
        $key = $_SERVER['HTTP_X_API_KEY'] ?? '';
        if ($key === '') {
            header('HTTP/1.1 403 Forbidden');
            header('Content-Type: application/xml; charset=UTF-8');
            echo '<?xml version="1.0" encoding="UTF-8"?>'
                . '<diag:diagnostic xmlns:diag="http://www.loc.gov/zing/srw/diagnostic/">'
                . '<diag:uri>http://www.loc.gov/zing/srw/diagnostic/</diag:uri>'
                . '<diag:message>X-API-Key header required</diag:message>'
                . '</diag:diagnostic>';
            exit;
        }

        // Validate against ahg_api_key table (scope must include 'sru')
        $hashedKey = hash('sha256', $key);
        $keyRow = DB::table('ahg_api_key')
            ->whereRaw("LEFT(key_hash, 8) = LEFT(?, 8)", [substr($hashedKey, 0, 8)])
            ->where('is_active', 1)
            ->first();

        if (!$keyRow || !in_array('sru', array_map('trim', explode(',', $keyRow->scopes ?? '')), true)) {
            header('HTTP/1.1 403 Forbidden');
            header('Content-Type: application/xml; charset=UTF-8');
            echo '<?xml version="1.0" encoding="UTF-8"?>'
                . '<diag:diagnostic xmlns:diag="http://www.loc.gov/zing/srw/diagnostic/">'
                . '<diag:uri>http://www.loc.gov/zing/srw/diagnostic/</diag:uri>'
                . '<diag:message>Invalid API key or insufficient scope (sru scope required)</diag:message>'
                . '</diag:diagnostic>';
            exit;
        }
    }

    /**
     * Write a row to library_sru_log.
     */
    protected function logSruRequest(
        string $query,
        string $xml,
        int $resultCount,
        float $durationMs,
        string $remoteAddr,
        ?string $error
    ): void {
        try {
            DB::table('library_sru_log')->insert([
                'query'         => $query,
                'cql_query'     => $query,  // already CQL at this point
                'result_count'  => $resultCount,
                'duration_ms'   => $durationMs,
                'error'         => $error,
                'remote_addr'   => $remoteAddr,
                'created_at'    => date('Y-m-d H:i:s'),
            ]);
        } catch (\Throwable $e) {
            // Logging failure must not break the SRU response
        }
    }
}