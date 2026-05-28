<?php
/**
 * PSIS / AtoM-AHG - public-key endpoint for the AI inference receipt chain.
 *
 * Responds at /.well-known/ai-inference-pubkey with the same JSON shape as
 * Heratio's PublicKeyController:
 *
 *   {
 *     "issuer":  "<base URL>",
 *     "purpose": "EU AI Act Article 12 record-keeping ...",
 *     "spec":    "https://packagist.org/packages/ahg/inference-receipts",
 *     "keys": [
 *       {
 *         "kid":        "...",
 *         "alg":        "ed25519",
 *         "active":     true,
 *         "public_key": {"hex": "...", "base64": "...", "base64url": "..."},
 *         "jwk":        {"kty":"OKP","crv":"Ed25519","kid":"...","x":"..."},
 *         "rotated_at": "...",
 *         "created_at": "..."
 *       }
 *     ]
 *   }
 *
 * Cross-verifiers (nobulex, other ahg/inference-receipts consumers) pull this
 * to authenticate receipts off-host.
 *
 * @copyright Copyright (c) 2026, Plain Sailing Information Systems
 * @author    Johan Pieterse <johan@plainsailingisystems.co.za>
 * @license   AGPL-3.0-or-later
 */

use Illuminate\Database\Capsule\Manager as DB;

class aiComplianceActions extends sfActions
{
    public function executeWellKnownPubkey(sfWebRequest $request)
    {
        $rows = DB::table('ai_inference_key')
            ->orderByDesc('created_at')
            ->get(['kid', 'public_key', 'alg', 'active', 'rotated_at', 'created_at']);

        $keys = [];
        foreach ($rows as $row) {
            $pubBytes = (string) $row->public_key;
            $b64      = base64_encode($pubBytes);
            $b64url   = rtrim(strtr($b64, '+/', '-_'), '=');

            $keys[] = [
                'kid'        => (string) $row->kid,
                'alg'        => (string) $row->alg,
                'active'     => (bool) $row->active,
                'public_key' => [
                    'hex'       => bin2hex($pubBytes),
                    'base64'    => $b64,
                    'base64url' => $b64url,
                ],
                'jwk' => [
                    'kty' => 'OKP',
                    'crv' => 'Ed25519',
                    'kid' => (string) $row->kid,
                    'x'   => $b64url,
                ],
                'rotated_at' => $row->rotated_at,
                'created_at' => $row->created_at,
            ];
        }

        $body = json_encode([
            'issuer'  => $request->getUriPrefix(),
            'purpose' => 'EU AI Act Article 12 record-keeping. Public keys for verifying tamper-evident inference receipts.',
            'spec'    => 'https://packagist.org/packages/ahg/inference-receipts',
            'keys'    => $keys,
        ], JSON_UNESCAPED_SLASHES);

        $response = $this->getResponse();
        $response->setHttpHeader('Content-Type', 'application/json; charset=utf-8');
        $response->setHttpHeader('Cache-Control', 'public, max-age=300');

        return $this->renderText($body);
    }
}
