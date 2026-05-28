<?php
/**
 * PSIS / AtoM-AHG - thin wrapper around the ReceiptChain for AtoM AI services.
 *
 * Symfony 1.4 / PHP 8.3 port of Heratio's AhgAiCompliance\Services\InferenceLogger.
 * Call-site signature is intentionally identical to the Heratio one so the
 * service-level wire-up reads the same on both stacks.
 *
 *   $logger->log(
 *       service:       'ner',
 *       modelId:       'spacy/en_core_web_trf',
 *       modelVersion:  '3.7.0',
 *       inputBody:     $text,
 *       outputBody:    json_encode($entities),
 *       extra:         ['latency_ms' => 412, 'tokens_in' => 800],
 *   );
 *
 * @copyright Copyright (c) 2026, Plain Sailing Information Systems
 * @author    Johan Pieterse <johan@plainsailingisystems.co.za>
 * @license   AGPL-3.0-or-later
 */

declare(strict_types=1);

use AhgInferenceReceipts\Receipt;
use AhgInferenceReceipts\ReceiptChain;

final class InferenceLogger
{
    public function __construct(private ReceiptChain $chain)
    {
    }

    /**
     * Log one inference call. Failure to log MUST NOT break the inference call -
     * exceptions are swallowed + written to the AtoM error log so an operator
     * notices the chain stopped growing.
     *
     * @param array<string,mixed> $extra latency_ms / tokens_in / tokens_out and
     *                                    any other payload columns (see schema)
     */
    public function log(
        string $service,
        string $modelId,
        ?string $modelVersion,
        string $inputBody,
        string $outputBody,
        array $extra = [],
    ): ?Receipt {
        try {
            $payload = array_merge([
                'service'            => $service,
                'model_id'           => $modelId,
                'model_version'      => $modelVersion,
                'input_fingerprint'  => hash('sha256', $inputBody),
                'output_fingerprint' => hash('sha256', $outputBody),
                'request_id'         => $this->requestId(),
                'user_id'            => $this->userId(),
                'tenant_id'          => $this->tenantId(),
            ], $extra);

            return $this->chain->append($payload);
        } catch (\Throwable $e) {
            error_log(sprintf(
                'ahgAiCompliancePlugin: inference logger append failed (service=%s, model=%s): %s',
                $service,
                $modelId,
                $e->getMessage(),
            ));
            return null;
        }
    }

    private function requestId(): ?string
    {
        // Symfony 1.4 / sf request id middleware
        if (class_exists('sfContext') && sfContext::hasInstance()) {
            $request = sfContext::getInstance()->getRequest();
            if ($request !== null) {
                $rid = $request->getHttpHeader('X-Request-Id')
                    ?: $request->getAttribute('request_id');
                if (!empty($rid)) {
                    return (string) $rid;
                }
            }
        }
        return null;
    }

    private function userId(): ?int
    {
        if (class_exists('sfContext') && sfContext::hasInstance()) {
            try {
                $user = sfContext::getInstance()->getUser();
                if ($user !== null && method_exists($user, 'getUserID')) {
                    $uid = $user->getUserID();
                    return $uid === null ? null : (int) $uid;
                }
                if ($user !== null && method_exists($user, 'getAttribute')) {
                    $uid = $user->getAttribute('user_id');
                    return $uid === null ? null : (int) $uid;
                }
            } catch (\Throwable) {
                return null;
            }
        }
        return null;
    }

    private function tenantId(): ?int
    {
        // AtoM is single-tenant; if a future multi-tenant plugin sets this, honour it.
        if (class_exists('sfConfig')) {
            $tid = sfConfig::get('app_ahg_tenant_id');
            if ($tid !== null && $tid !== '') {
                return (int) $tid;
            }
        }
        return null;
    }
}
