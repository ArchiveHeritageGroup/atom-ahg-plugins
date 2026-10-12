<?php
/**
 * PSIS / AtoM-AHG - sign a C2PA manifest for one AI output.
 *
 * PSIS twin of Heratio's AiOutputProduced event + WriteC2paSidecar listener.
 * AtoM has no Laravel event bus, so ahgAIPlugin calls record() directly at the
 * point it has produced an output (NER, summary, translation, HTR, LLM
 * description). Builds + signs an ai-generated / ai-assisted manifest,
 * sidecars or embeds it when there is an artefact file, persists it to
 * ahg_c2pa_manifest and links its claim digest into the Article 12 inference
 * chain when ahgAiCompliancePlugin is present.
 *
 * Best effort by contract: record() never throws, so a C2PA fault can never
 * break the AI action that called it.
 *
 * @copyright Copyright (c) 2026, Plain Sailing Information Systems
 * @author    Johan Pieterse <johan@plainsailingisystems.co.za>
 * @license   AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace AhgC2pa\Services;

use Throwable;

final class AiOutputRecorder
{
    /**
     * @param string      $action       'ai-generated' or 'ai-assisted'
     * @param string|null $artefactPath file the output was written to, if any
     *
     * @return int|null ahg_c2pa_manifest row id, or null when nothing was stored
     */
    public static function record(
        int $informationObjectId,
        string $action,
        string $modelId,
        ?string $modelVersion,
        string $output,
        ?string $artefactPath = null,
    ): ?int {
        try {
            if ($informationObjectId <= 0 || $output === '') {
                return null;
            }

            \C2paBootstrap::load();
            $signer = \C2paBootstrap::loadSigner();
            if ($signer === null) {
                return null;
            }
            $c2pa = new C2paService($signer);

            $modelId = $modelId !== '' ? mb_substr($modelId, 0, 128) : 'unknown';
            $modelVersion = ($modelVersion === null || $modelVersion === '') ? null : mb_substr($modelVersion, 0, 64);

            $manifest = $c2pa->manifestForAiSuggestion(
                informationObjectId: $informationObjectId,
                action: $action,
                modelId: $modelId,
                modelVersion: $modelVersion,
                output: $output,
                assetPath: $artefactPath,
                frameworkVersion: ProvenanceRecordService::frameworkVersion(),
            );
            $signed = $c2pa->signManifest($manifest);

            $sidecarPath = null;
            if ($artefactPath !== null && is_readable($artefactPath)) {
                if (preg_match('/\.jpe?g$/i', $artefactPath)) {
                    $sidecarPath = $c2pa->embedInJpeg($artefactPath, $signed);
                } elseif (preg_match('/\.pdf$/i', $artefactPath)) {
                    $sidecarPath = $c2pa->embedInPdf($artefactPath, $signed) ?? $c2pa->sidecar($signed, $artefactPath);
                } else {
                    $sidecarPath = $c2pa->sidecar($signed, $artefactPath);
                }
            }

            $rowId = $c2pa->persist($signed, $informationObjectId, $action, $modelId, $modelVersion, $sidecarPath);

            self::logToInferenceChain($signer, $informationObjectId, $action, $modelId, $modelVersion, $output, $signed, $rowId);

            return $rowId;
        } catch (Throwable $e) {
            error_log('[c2pa] AI output manifest failed for IO ' . $informationObjectId . ': ' . $e->getMessage());

            return null;
        }
    }

    /**
     * Link the manifest's claim digest into the ai_inference_log receipt chain.
     *
     * @param array<string,mixed> $signed
     */
    private static function logToInferenceChain(
        \AhgInferenceReceipts\Signer $signer,
        int $informationObjectId,
        string $action,
        string $modelId,
        ?string $modelVersion,
        string $output,
        array $signed,
        ?int $rowId,
    ): void {
        if (!class_exists('InferenceLogger') || !class_exists('PropelChainStore')
            || !class_exists(\AhgInferenceReceipts\ReceiptChain::class)) {
            return;
        }

        try {
            $chain = new \AhgInferenceReceipts\ReceiptChain(new \PropelChainStore(), $signer, C2paService::publicKeyResolver());
            (new \InferenceLogger($chain))->log(
                service: 'c2pa',
                modelId: $modelId,
                modelVersion: $modelVersion,
                inputBody: (string) $informationObjectId,
                outputBody: $output,
                extra: [
                    'c2pa_action'       => $action,
                    'c2pa_claim_digest' => hash('sha256', \AhgInferenceReceipts\JcsEncoder::encode($signed['claim'] ?? [])),
                    'c2pa_kid'          => $signed['claim_signature']['kid'] ?? null,
                    'c2pa_manifest_id'  => $rowId,
                ],
            );
        } catch (Throwable $e) {
            error_log('[c2pa] inference-chain link skipped: ' . $e->getMessage());
        }
    }
}
