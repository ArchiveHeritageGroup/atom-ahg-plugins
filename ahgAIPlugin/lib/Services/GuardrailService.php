<?php

/**
 * GuardrailService - RAG / LLM dispatch guardrails (atom-ahg-plugins).
 *
 * Port of the Heratio AhgAiServices\Services\GuardrailService to the AtoM-AHG
 * side - issue #141. Same three guardrails, same setting keys/defaults, same
 * PII patterns and grounding heuristic, so a request behaves identically
 * whichever codebase serves it.
 *
 * Copyright (C) 2026 Johan Pieterse, Plain Sailing Information Systems
 * The Archive and Heritage Group (Pty) Ltd. Licensed GPL-3.0-or-later.
 */

use Illuminate\Database\Capsule\Manager as DB;

/**
 * Policy guardrails applied to every LLM / RAG dispatch (issue #141, AI
 * Governance & Sovereignty design doc Goal 4):
 *
 *   1. allowed_data_scopes - out-of-scope data to a cloud provider is blocked,
 *      and PII in cloud-bound prompts is masked.
 *   2. purpose limitation  - the declared purpose must be in the sanctioned set.
 *   3. grounding           - a RAG output is scored against its source bundle;
 *      poorly-grounded output is flagged as a possible hallucination.
 *
 * Operator mode: off | warn | mask | block (default warn - safe to deploy).
 * Pure and self-contained: construct with an explicit config array for tests,
 * or let it load from ahg_ai_settings (feature 'guardrails') in production.
 */
class GuardrailService
{
    const MODE_OFF   = 'off';
    const MODE_WARN  = 'warn';
    const MODE_MASK  = 'mask';
    const MODE_BLOCK = 'block';

    /** @var array */
    private $config;

    public function __construct(?array $config = null)
    {
        $this->config = $config !== null ? $config : self::loadConfig();
    }

    /** Production config from ahg_ai_settings (feature 'guardrails'); falls back to defaults. */
    public static function loadConfig(): array
    {
        $defaults = self::defaultConfig();
        try {
            $rows = DB::table('ahg_ai_settings')
                ->where('feature', 'guardrails')
                ->pluck('setting_value', 'setting_key');

            $raw = function ($key) use ($rows) {
                $v = $rows[$key] ?? null;

                return ($v !== null && $v !== '') ? (string) $v : null;
            };

            $mode = strtolower(trim((string) ($raw('rag_guardrail_mode') ?? $defaults['mode'])));
            if (!in_array($mode, [self::MODE_OFF, self::MODE_WARN, self::MODE_MASK, self::MODE_BLOCK], true)) {
                $mode = $defaults['mode'];
            }

            return [
                'mode'                 => $mode,
                'cloud_allowed_scopes' => self::csv($raw('rag_cloud_allowed_scopes'), $defaults['cloud_allowed_scopes']),
                'local_providers'      => self::csv($raw('rag_local_providers'), $defaults['local_providers']),
                'sanctioned_purposes'  => self::csv($raw('rag_sanctioned_purposes'), $defaults['sanctioned_purposes']),
                'grounding_threshold'  => $raw('rag_grounding_threshold') !== null
                    ? (float) $raw('rag_grounding_threshold') : $defaults['grounding_threshold'],
            ];
        } catch (\Throwable $e) {
            return $defaults;
        }
    }

    /** The shipped defaults - identical to the Heratio side. */
    public static function defaultConfig(): array
    {
        return [
            'mode'                 => self::MODE_WARN,
            'cloud_allowed_scopes' => ['public', 'internal'],
            'local_providers'      => ['ollama'],
            'sanctioned_purposes'  => [
                'description_generation', 'summarization', 'translation',
                'entity_extraction', 'spellcheck', 'research_assistance',
                'metadata_enrichment',
            ],
            'grounding_threshold'  => 0.45,
        ];
    }

    /** Parse a comma-separated string into a lowercased list, or use the fallback. */
    private static function csv(?string $value, array $fallback): array
    {
        if ($value === null || trim($value) === '') {
            return $fallback;
        }
        $items = array_map('trim', explode(',', strtolower($value)));
        $items = array_values(array_filter($items, static function ($v) {
            return $v !== '';
        }));

        return empty($items) ? $fallback : $items;
    }

    public function mode(): string
    {
        return $this->config['mode'] ?? self::MODE_WARN;
    }

    /** True when the named provider sits outside the local trust domain. */
    public function isCloudProvider(string $provider): bool
    {
        $local = array_map('strtolower', (array) ($this->config['local_providers'] ?? ['ollama']));

        return !in_array(strtolower(trim($provider)), $local, true);
    }

    /**
     * Inspect a request before dispatch. Returns the decision plus
     * possibly-masked prompts. Never throws.
     *
     * @param array $req keys: provider, model, system_prompt, user_prompt, data_scope, purpose
     */
    public function inspect(array $req): array
    {
        $provider = (string) ($req['provider'] ?? '');
        $system   = (string) ($req['system_prompt'] ?? '');
        $user     = (string) ($req['user_prompt'] ?? '');
        $scope    = $this->normalise($req['data_scope'] ?? '', 'internal');
        $purpose  = $this->normalise($req['purpose'] ?? '', 'unspecified');
        $mode     = $this->mode();
        $isCloud  = $this->isCloudProvider($provider);

        $out = [
            'action'             => 'allow',
            'reason'             => null,
            'mode'               => $mode,
            'provider'           => $provider,
            'is_cloud'           => $isCloud,
            'data_scope'         => $scope,
            'purpose'            => $purpose,
            'purpose_sanctioned' => true,
            'pii_masked'         => 0,
            'system_prompt'      => $system,
            'user_prompt'        => $user,
            'flags'              => [],
        ];

        if ($mode === self::MODE_OFF) {
            return $out;
        }

        $enforce = ($mode === self::MODE_BLOCK);
        $mutate  = ($mode === self::MODE_MASK || $mode === self::MODE_BLOCK);

        // Guardrail 2 - purpose limitation.
        if ($purpose === 'unspecified') {
            $out['flags'][] = 'purpose_unspecified';
        } elseif (!in_array($purpose, (array) ($this->config['sanctioned_purposes'] ?? []), true)) {
            $out['purpose_sanctioned'] = false;
            $out['flags'][] = 'purpose_not_sanctioned';
            if ($enforce) {
                $out['action'] = 'block';
                $out['reason'] = "Purpose '{$purpose}' is not in the sanctioned set";

                return $out;
            }
        }

        // Guardrail 1 - data-scope enforcement (cloud providers only).
        if ($isCloud) {
            $allowed = (array) ($this->config['cloud_allowed_scopes'] ?? []);
            if (!in_array($scope, $allowed, true)) {
                $out['flags'][] = 'data_scope_out_of_policy';
                if ($enforce) {
                    $out['action'] = 'block';
                    $out['reason'] = "Data scope '{$scope}' may not be sent to cloud provider '{$provider}'";

                    return $out;
                }
            }
            // PII masking on cloud-bound prompts (mask + block modes).
            if ($mutate) {
                list($maskedSystem, $countSystem) = $this->maskPii($system);
                list($maskedUser,   $countUser)   = $this->maskPii($user);
                $masked = $countSystem + $countUser;
                if ($masked > 0) {
                    $out['system_prompt'] = $maskedSystem;
                    $out['user_prompt']   = $maskedUser;
                    $out['pii_masked']    = $masked;
                    $out['flags'][]       = 'pii_masked';
                    if ($out['action'] === 'allow') {
                        $out['action'] = 'mask';
                    }
                }
            }
        }

        return $out;
    }

    /**
     * Grounding / hallucination check on a RAG output. Returns null when no
     * source bundle was supplied. Never throws.
     *
     * @param array $contextSources the RAG provenance bundle (source snippets)
     */
    public function checkGrounding(string $output, array $contextSources): ?array
    {
        if ($this->mode() === self::MODE_OFF || empty($contextSources)) {
            return null;
        }

        $context = strtolower(trim(implode(' ', array_map('strval', $contextSources))));
        if ($context === '') {
            return null;
        }

        $terms = $this->significantTerms($output);
        if (count($terms) < 5) {
            return ['grounding_score' => 1.0, 'grounded' => true, 'flag' => null, 'terms_checked' => count($terms)];
        }

        $supported = 0;
        foreach ($terms as $term) {
            if (strpos($context, $term) !== false) {
                $supported++;
            }
        }
        $score     = round($supported / count($terms), 4);
        $threshold = (float) ($this->config['grounding_threshold'] ?? 0.45);
        $grounded  = $score >= $threshold;

        return [
            'grounding_score' => $score,
            'grounded'        => $grounded,
            'flag'            => $grounded ? null : 'low_grounding',
            'terms_checked'   => count($terms),
        ];
    }

    /**
     * Fold an inspect() decision and an optional checkGrounding() result into
     * the compact `guardrail` array attached to an LLM result.
     */
    public function summarize(array $inspect, ?array $grounding): array
    {
        $guardrail = [
            'mode'               => $inspect['mode'] ?? $this->mode(),
            'action'             => $inspect['action'] ?? 'allow',
            'data_scope'         => $inspect['data_scope'] ?? null,
            'purpose'            => $inspect['purpose'] ?? null,
            'purpose_sanctioned' => $inspect['purpose_sanctioned'] ?? true,
            'pii_masked'         => (int) ($inspect['pii_masked'] ?? 0),
            'flags'              => array_values((array) ($inspect['flags'] ?? [])),
        ];
        if (!empty($inspect['reason'])) {
            $guardrail['reason'] = $inspect['reason'];
        }
        if ($grounding !== null) {
            $guardrail['grounding_score'] = $grounding['grounding_score'];
            $guardrail['grounded']        = $grounding['grounded'];
            if (!empty($grounding['flag'])) {
                $guardrail['flags'][] = $grounding['flag'];
            }
        }
        $guardrail['flags'] = array_values(array_unique($guardrail['flags']));

        return $guardrail;
    }

    /**
     * Mask personally-identifiable patterns: email addresses and digit
     * sequences carrying 9+ digits (phone / national-ID / account numbers).
     * Jurisdiction-neutral. Returns [maskedText, replacementCount].
     */
    public function maskPii(string $text): array
    {
        $count = 0;

        $masked = preg_replace_callback(
            '/[\w.+-]+@[\w-]+\.[\w.-]+/u',
            function () use (&$count) {
                $count++;

                return '[REDACTED:email]';
            },
            $text
        );
        $text = ($masked !== null) ? $masked : $text;

        $masked = preg_replace_callback(
            '/\+?\d[\d\s().-]{6,}\d/u',
            function ($m) use (&$count) {
                if (strlen(preg_replace('/\D/', '', $m[0])) >= 9) {
                    $count++;

                    return '[REDACTED:number]';
                }

                return $m[0];
            },
            $text
        );
        $text = ($masked !== null) ? $masked : $text;

        return [$text, $count];
    }

    /**
     * Distinct significant terms: lowercased runs of 5+ Latin letters, minus a
     * small stopword set. Used by the grounding check.
     */
    public function significantTerms(string $text): array
    {
        preg_match_all('/[a-z]{5,}/', strtolower($text), $matches);

        $stop = array_flip([
            'which', 'their', 'there', 'these', 'those', 'where', 'about',
            'would', 'could', 'should', 'other', 'through', 'being', 'because',
            'between', 'during', 'before', 'after', 'while', 'within', 'first',
            'three', 'among', 'under', 'above', 'below', 'using', 'based',
            'including', 'various', 'several', 'however', 'therefore',
        ]);

        $terms = [];
        foreach ($matches[0] as $word) {
            if (!isset($stop[$word])) {
                $terms[$word] = true;
            }
        }

        return array_keys($terms);
    }

    /** Lowercase + trim a scalar, substituting a default when empty. */
    private function normalise($value, string $default): string
    {
        $v = strtolower(trim((string) $value));

        return $v !== '' ? $v : $default;
    }
}
