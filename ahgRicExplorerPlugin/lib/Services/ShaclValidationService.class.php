<?php

/*
 * ShaclValidationService - SHACL validation of RiC-O data for AtoM (PSIS).
 *
 * Symfony 1.x port of the Heratio AhgRic\Services\ShaclValidationService.
 * Validates RiC-O entities / the generated RiC graph against RiC-O SHACL
 * shapes (tools/ric_shacl_shapes.ttl) using the bundled pyshacl wrapper
 * (tools/ric_shacl_validator.py), with graceful fallback when the SHACL
 * engine (python3 + pyshacl/rdflib) is unavailable.
 *
 * Conventions:
 *   - Laravel Query Builder via \Illuminate\Database\Capsule\Manager
 *   - Namespaced plugin class, loaded via require_once + new (not autoload)
 *   - No FOREIGN KEYs to core tables, no ENUM, no INSERT INTO atom_plugin
 *
 * @package    ahgRicExplorerPlugin
 * @author     The Archive and Heritage Group (Pty) Ltd
 */

namespace AhgRicExplorer\Services;

use Illuminate\Database\Capsule\Manager as DB;

class ShaclValidationService
{
    /** @var string Absolute path to the SHACL shapes TTL file. */
    private $shapesPath;

    /** @var string Absolute path to the pyshacl wrapper script. */
    private $validatorScript;

    /** @var string Fuseki SPARQL endpoint base (no trailing slash). */
    private $fusekiEndpoint;

    /** @var string|null Last raw output captured from the validator. */
    private $lastRawOutput = null;

    public function __construct()
    {
        $pluginDir = dirname(dirname(__DIR__)); // .../ahgRicExplorerPlugin
        $this->shapesPath = $pluginDir . '/tools/ric_shacl_shapes.ttl';
        $this->validatorScript = $pluginDir . '/tools/ric_shacl_validator.py';

        $endpoint = \sfConfig::get('app_ric_fuseki_endpoint', 'http://192.168.0.112:3030/ric');
        $this->fusekiEndpoint = rtrim((string) $endpoint, '/');
    }

    /**
     * Whether the external SHACL engine appears usable on this host.
     *
     * @return array{available:bool, reason:string}
     */
    public function engineStatus(): array
    {
        if (!file_exists($this->validatorScript)) {
            return ['available' => false, 'reason' => 'Validator script not found: ' . $this->validatorScript];
        }
        if (!file_exists($this->shapesPath)) {
            return ['available' => false, 'reason' => 'SHACL shapes file not found: ' . $this->shapesPath];
        }
        if (!function_exists('shell_exec')) {
            return ['available' => false, 'reason' => 'shell_exec() disabled on this host'];
        }

        $python = $this->pythonBinary();
        if (null === $python) {
            return ['available' => false, 'reason' => 'python3 not found on PATH'];
        }

        // Probe pyshacl/rdflib availability without running a full validation.
        $probe = sprintf(
            '%s -c %s 2>&1',
            escapeshellcmd($python),
            escapeshellarg('import pyshacl, rdflib')
        );
        $out = @shell_exec($probe);
        if (null !== $out && '' !== trim((string) $out)) {
            return ['available' => false, 'reason' => 'pyshacl/rdflib not installed (pip install pyshacl rdflib)'];
        }

        return ['available' => true, 'reason' => 'ok'];
    }

    /**
     * Validate a single RiC-O entity (JSON-LD assoc array) before save.
     *
     * Mirrors the Heratio service: SHACL shape validation + mandatory-field
     * check + referential-integrity warnings.
     *
     * @return array{valid:bool, errors:array, warnings:array, engine:string}
     */
    public function validateBeforeSave(array $ricEntity, string $entityType): array
    {
        $result = [
            'valid' => true,
            'errors' => [],
            'warnings' => [],
            'engine' => 'none',
        ];

        $validation = $this->validateAgainstShapes($ricEntity);
        $result['engine'] = $validation['engine'];
        if (!$validation['valid']) {
            $result['valid'] = false;
            $result['errors'] = $validation['violations'];
        }

        $mandatory = $this->checkMandatoryFields($ricEntity, $entityType);
        if (!$mandatory['valid']) {
            $result['valid'] = false;
            $result['errors'] = array_merge($result['errors'], $mandatory['errors']);
        }

        $ref = $this->checkReferentialIntegrity($ricEntity);
        if (!$ref['valid']) {
            $result['warnings'] = array_merge($result['warnings'], $ref['warnings']);
        }

        return $result;
    }

    /**
     * Validate a JSON-LD entity (assoc array) against the RiC-O SHACL shapes.
     *
     * Falls back gracefully (valid=true, engine=fallback) when the SHACL
     * engine is unavailable so CRUD operations are never blocked by a missing
     * dependency.
     *
     * @return array{valid:bool, violations:array, raw_output:string, engine:string}
     */
    public function validateAgainstShapes(array $ricEntity): array
    {
        $status = $this->engineStatus();
        if (!$status['available']) {
            return [
                'valid' => true,
                'violations' => [],
                'raw_output' => 'SHACL engine unavailable: ' . $status['reason'],
                'engine' => 'fallback',
            ];
        }

        $ttl = $this->toTurtle($ricEntity);
        $tempFile = tempnam(sys_get_temp_dir(), 'ric_validation_') . '.ttl';
        file_put_contents($tempFile, $ttl);

        $command = sprintf(
            '%s %s --file %s --shapes %s --validate --verbose 2>&1',
            escapeshellcmd($this->pythonBinary()),
            escapeshellarg($this->validatorScript),
            escapeshellarg($tempFile),
            escapeshellarg($this->shapesPath)
        );

        $output = (string) @shell_exec($command);
        @unlink($tempFile);
        $this->lastRawOutput = $output;

        $violations = $this->parseTextViolations($output);

        return [
            'valid' => empty($violations) && false === strpos($output, 'DOES NOT CONFORM'),
            'violations' => $violations,
            'raw_output' => $output,
            'engine' => 'pyshacl',
        ];
    }

    /**
     * Validate the entire generated RiC graph (from Fuseki) and produce a
     * structured report, persisting it to ric_shacl_report.
     *
     * @param string|null $graphUri optional named graph to scope validation
     *
     * @return array structured report
     */
    public function validateGraph(?string $graphUri = null): array
    {
        $startedAt = date('Y-m-d H:i:s');
        $status = $this->engineStatus();

        if (!$status['available']) {
            $report = [
                'conforms' => null,
                'engine' => 'fallback',
                'reason' => $status['reason'],
                'data_triples' => 0,
                'statistics' => $this->emptyStats(),
                'violations' => [],
                'raw_output' => '',
                'graph_uri' => $graphUri,
                'started_at' => $startedAt,
                'finished_at' => date('Y-m-d H:i:s'),
            ];
            $report['report_id'] = $this->persistReport($report);

            return $report;
        }

        $jsonOut = tempnam(sys_get_temp_dir(), 'ric_shacl_report_') . '.json';

        $env = '';
        if (null !== $graphUri) {
            $env = sprintf('FUSEKI_ENDPOINT=%s ', escapeshellarg($this->fusekiEndpoint));
        } else {
            $env = sprintf('FUSEKI_ENDPOINT=%s ', escapeshellarg($this->fusekiEndpoint));
        }

        $command = sprintf(
            '%s%s %s --validate --json --shapes %s --output %s 2>&1',
            $env,
            escapeshellcmd($this->pythonBinary()),
            escapeshellarg($this->validatorScript),
            escapeshellarg($this->shapesPath),
            escapeshellarg($jsonOut)
        );

        $raw = (string) @shell_exec($command);
        $this->lastRawOutput = $raw;

        $parsed = null;
        if (file_exists($jsonOut)) {
            $parsed = json_decode((string) file_get_contents($jsonOut), true);
            @unlink($jsonOut);
        }

        if (!is_array($parsed)) {
            // The script produced no JSON (e.g. Fuseki down). Degrade cleanly.
            $report = [
                'conforms' => null,
                'engine' => 'pyshacl',
                'reason' => 'No report produced (Fuseki unreachable or validator error)',
                'data_triples' => 0,
                'statistics' => $this->emptyStats(),
                'violations' => [],
                'raw_output' => $raw,
                'graph_uri' => $graphUri,
                'started_at' => $startedAt,
                'finished_at' => date('Y-m-d H:i:s'),
            ];
            $report['report_id'] = $this->persistReport($report);

            return $report;
        }

        $stats = isset($parsed['statistics']) && is_array($parsed['statistics'])
            ? $parsed['statistics']
            : $this->emptyStats();
        $violations = isset($parsed['violations']) && is_array($parsed['violations'])
            ? $parsed['violations']
            : [];

        $conforms = (false !== strpos($raw, ' CONFORMS') && false === strpos($raw, 'DOES NOT CONFORM'))
            ? true
            : (empty($violations) ? true : false);

        $report = [
            'conforms' => $conforms,
            'engine' => 'pyshacl',
            'reason' => $conforms ? 'Data conforms to RiC-O shapes' : 'Validation found issues',
            'data_triples' => (int) ($parsed['data_triples'] ?? 0),
            'statistics' => $stats,
            'violations' => $violations,
            'raw_output' => $raw,
            'graph_uri' => $graphUri,
            'started_at' => $startedAt,
            'finished_at' => date('Y-m-d H:i:s'),
        ];
        $report['report_id'] = $this->persistReport($report);

        return $report;
    }

    /**
     * Batch-validate multiple JSON-LD entities.
     *
     * @return array{total:int, valid:int, invalid:int, results:array}
     */
    public function validateBatch(array $entities, string $entityType): array
    {
        $results = [];
        foreach ($entities as $index => $entity) {
            $results[$index] = $this->validateBeforeSave((array) $entity, $entityType);
        }

        return [
            'total' => count($entities),
            'valid' => count(array_filter($results, function ($r) { return $r['valid']; })),
            'invalid' => count(array_filter($results, function ($r) { return !$r['valid']; })),
            'results' => $results,
        ];
    }

    /**
     * Fetch the most recent persisted reports (for the dashboard listing).
     */
    public function recentReports(int $limit = 25): array
    {
        try {
            return DB::table('ric_shacl_report')
                ->orderBy('id', 'desc')
                ->limit($limit)
                ->get()
                ->toArray();
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * Fetch a single persisted report with decoded violations.
     */
    public function getReport(int $reportId): ?array
    {
        try {
            $row = DB::table('ric_shacl_report')->where('id', $reportId)->first();
        } catch (\Throwable $e) {
            return null;
        }
        if (!$row) {
            return null;
        }
        $arr = (array) $row;
        $arr['statistics'] = json_decode((string) ($arr['statistics_json'] ?? '{}'), true) ?: $this->emptyStats();
        $arr['violations'] = json_decode((string) ($arr['violations_json'] ?? '[]'), true) ?: [];

        return $arr;
    }

    public function getLastRawOutput(): ?string
    {
        return $this->lastRawOutput;
    }

    // -------------------------------------------------------------------
    // Internal helpers
    // -------------------------------------------------------------------

    /**
     * Persist a report row. Best-effort; returns inserted id or 0.
     */
    private function persistReport(array $report): int
    {
        $stats = $report['statistics'] ?? $this->emptyStats();
        $bySeverity = $stats['by_severity'] ?? [];

        try {
            $id = DB::table('ric_shacl_report')->insertGetId([
                'graph_uri' => $report['graph_uri'] ?? null,
                'engine' => $report['engine'] ?? 'none',
                'conforms' => null === $report['conforms'] ? null : ($report['conforms'] ? 1 : 0),
                'data_triples' => (int) ($report['data_triples'] ?? 0),
                'total_violations' => (int) ($stats['total_violations'] ?? count($report['violations'] ?? [])),
                'violation_count' => (int) ($bySeverity['Violation'] ?? 0),
                'warning_count' => (int) ($bySeverity['Warning'] ?? 0),
                'info_count' => (int) ($bySeverity['Info'] ?? 0),
                'statistics_json' => json_encode($stats),
                'violations_json' => json_encode(array_slice($report['violations'] ?? [], 0, 500)),
                'reason' => isset($report['reason']) ? mb_substr((string) $report['reason'], 0, 1000) : null,
                'started_at' => $report['started_at'] ?? null,
                'finished_at' => $report['finished_at'] ?? null,
                'created_at' => date('Y-m-d H:i:s'),
            ]);

            return (int) $id;
        } catch (\Throwable $e) {
            return 0;
        }
    }

    private function emptyStats(): array
    {
        return [
            'total_violations' => 0,
            'by_severity' => ['Violation' => 0, 'Warning' => 0, 'Info' => 0],
            'by_shape' => [],
            'by_entity_type' => [],
        ];
    }

    /**
     * Parse human/verbose pyshacl output for violation lines.
     */
    private function parseTextViolations(string $output): array
    {
        $violations = [];
        if (false === strpos($output, 'DOES NOT CONFORM') && false === stripos($output, 'Constraint Violation')) {
            return $violations;
        }
        foreach (explode("\n", $output) as $line) {
            $line = trim($line);
            if ('' === $line) {
                continue;
            }
            if (false !== stripos($line, 'Violation')
                || false !== stripos($line, 'Message:')
                || false !== stripos($line, 'Result Path:')) {
                $violations[] = $line;
            }
        }

        return $violations;
    }

    private function checkMandatoryFields(array $entity, string $type): array
    {
        $errors = [];
        foreach ($this->getMandatoryFields($type) as $field) {
            if (!isset($entity[$field]) || empty($entity[$field])) {
                $errors[] = "Mandatory field '{$field}' is missing for {$type}";
            }
        }

        return ['valid' => empty($errors), 'errors' => $errors];
    }

    /**
     * Mandatory RiC-O fields per entity type (ISAAR/ISDF/ISAD/ISDIAH).
     */
    private function getMandatoryFields(string $type): array
    {
        switch ($type) {
            case 'Agent':
            case 'Person':
            case 'CorporateBody':
            case 'Family':
                return ['rico:name'];
            case 'Function':
                return ['rico:name'];
            case 'Record':
            case 'RecordSet':
                return ['rico:identifier'];
            case 'Repository':
                return ['rico:name'];
            default:
                return [];
        }
    }

    private function checkReferentialIntegrity(array $entity): array
    {
        $warnings = [];

        if (isset($entity['rico:hasCreator'])) {
            $creators = is_array($entity['rico:hasCreator']) && !isset($entity['rico:hasCreator']['@id'])
                ? $entity['rico:hasCreator']
                : [$entity['rico:hasCreator']];
            foreach ($creators as $creator) {
                if (is_array($creator) && isset($creator['@id']) && !$this->entityExistsInDatabase($creator['@id'])) {
                    $warnings[] = "Referenced creator does not exist: {$creator['@id']}";
                }
            }
        }

        if (isset($entity['rico:heldBy']) && is_array($entity['rico:heldBy']) && isset($entity['rico:heldBy']['@id'])) {
            if (!$this->entityExistsInDatabase($entity['rico:heldBy']['@id'])) {
                $warnings[] = "Referenced repository does not exist: {$entity['rico:heldBy']['@id']}";
            }
        }

        return ['valid' => empty($warnings), 'warnings' => $warnings];
    }

    private function entityExistsInDatabase(string $uri): bool
    {
        try {
            $slug = $this->extractSlug($uri);
            $objectId = (int) DB::table('slug')->where('slug', $slug)->value('object_id');
            if (!$objectId) {
                return false;
            }

            return DB::table('actor')->where('id', $objectId)->exists()
                || DB::table('information_object')->where('id', $objectId)->exists()
                || DB::table('repository')->where('id', $objectId)->exists();
        } catch (\Throwable $e) {
            // If we cannot verify, do not raise a false warning.
            return true;
        }
    }

    private function extractSlug(string $uri): string
    {
        $parts = explode('/', rtrim($uri, '/'));

        return (string) end($parts);
    }

    /**
     * Convert a JSON-LD entity (assoc array) to a minimal Turtle document for
     * single-entity SHACL validation.
     */
    private function toTurtle(array $entity): string
    {
        $ttl = "@prefix rico: <https://www.ica.org/standards/RiC/ontology#> .\n";
        $ttl .= "@prefix rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#> .\n";
        $ttl .= "@prefix rdfs: <http://www.w3.org/2000/01/rdf-schema#> .\n";
        $ttl .= "@prefix skos: <http://www.w3.org/2004/02/skos/core#> .\n";

        $id = $entity['@id'] ?? '_:b0';
        $type = str_replace('https://www.ica.org/standards/RiC/ontology#', 'rico:', $entity['@type'] ?? 'rico:Thing');
        if (false === strpos($type, ':')) {
            $type = 'rico:' . $type;
        }

        $subject = $this->subjectRef($id);
        $ttl .= "{$subject} rdf:type {$type} .\n";

        foreach ($entity as $key => $value) {
            if (in_array($key, ['@context', '@id', '@type'], true)) {
                continue;
            }

            $prop = str_replace('https://www.ica.org/standards/RiC/ontology#', 'rico:', (string) $key);
            $prop = str_replace('http://www.w3.org/2004/02/skos/core#', 'skos:', $prop);
            if (false === strpos($prop, ':') && 0 !== strpos($prop, '<')) {
                $prop = 'rico:' . $prop;
            }

            if (is_array($value) && !isset($value['@id']) && !isset($value['@value'])) {
                foreach ($value as $v) {
                    $ttl .= $this->tripleLine($subject, $prop, $v);
                }
            } else {
                $ttl .= $this->tripleLine($subject, $prop, $value);
            }
        }

        return $ttl;
    }

    private function tripleLine(string $subject, string $prop, $v): string
    {
        if (is_array($v) && isset($v['@id'])) {
            return "{$subject} {$prop} {$this->subjectRef($v['@id'])} .\n";
        }
        if (is_array($v) && isset($v['@value'])) {
            return "{$subject} {$prop} \"{$this->escapeString((string) $v['@value'])}\" .\n";
        }
        if (is_string($v)) {
            return "{$subject} {$prop} \"{$this->escapeString($v)}\" .\n";
        }
        if (is_bool($v)) {
            return "{$subject} {$prop} {$this->boolLit($v)} .\n";
        }
        if (is_int($v) || is_float($v)) {
            return "{$subject} {$prop} {$v} .\n";
        }

        return '';
    }

    private function subjectRef(string $id): string
    {
        if (0 === strpos($id, '_:')) {
            return $id; // blank node
        }

        return '<' . $id . '>';
    }

    private function boolLit(bool $v): string
    {
        return $v ? 'true' : 'false';
    }

    private function escapeString(string $str): string
    {
        return str_replace(
            ['\\', '"', "\n", "\r", "\t"],
            ['\\\\', '\\"', '\\n', '\\r', '\\t'],
            $str
        );
    }

    /**
     * Resolve the python3 binary, returning null if not found.
     */
    private function pythonBinary(): ?string
    {
        if (!function_exists('shell_exec')) {
            return null;
        }
        $which = @shell_exec('command -v python3 2>/dev/null');
        $which = is_string($which) ? trim($which) : '';
        if ('' !== $which) {
            return $which;
        }

        return null;
    }
}
