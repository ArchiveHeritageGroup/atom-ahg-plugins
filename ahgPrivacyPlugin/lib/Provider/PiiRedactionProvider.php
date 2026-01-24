<?php

declare(strict_types=1);

namespace ahgPrivacyPlugin\Provider;

use AtomFramework\Contracts\PiiRedactionProviderInterface;
use ahgPrivacyPlugin\Service\PdfRedactionService;

/**
 * PII Redaction Provider implementation.
 *
 * Adapts the PdfRedactionService to the framework's PiiRedactionProviderInterface.
 * This allows the framework to use privacy plugin's redaction capabilities
 * without hardcoded require_once statements.
 */
class PiiRedactionProvider implements PiiRedactionProviderInterface
{
    private ?PdfRedactionService $service = null;

    /**
     * Get the PDF redaction service (lazy loaded).
     */
    private function getService(): PdfRedactionService
    {
        if (null === $this->service) {
            $this->service = new PdfRedactionService();
        }
        return $this->service;
    }

    /**
     * {@inheritdoc}
     */
    public function getRedactedPdf(int $objectId, string $originalPath): array
    {
        return $this->getService()->getRedactedPdf($objectId, $originalPath);
    }

    /**
     * {@inheritdoc}
     */
    public function hasRedaction(int $objectId): bool
    {
        return $this->getService()->hasRedactions($objectId);
    }
}
