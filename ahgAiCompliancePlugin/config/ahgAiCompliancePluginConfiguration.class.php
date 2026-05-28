<?php
/**
 * PSIS / AtoM-AHG - ahgAiCompliancePlugin Configuration (Symfony 1.4 sfPluginConfiguration).
 *
 * EU AI Act Article 12 record-keeping (PSIS twin of heratio).
 *
 * Registers:
 *   - the /.well-known/ai-inference-pubkey route -> aiCompliance/wellKnownPubkey
 *
 * The plugin's lib/InferenceLogger, lib/PropelChainStore, lib/KeyResolver and
 * lib/SignerFactory wrap the framework-agnostic ahg/inference-receipts library
 * for use inside AtoM-AHG AI services.
 *
 * @copyright Copyright (c) 2026, Plain Sailing Information Systems
 * @author    Johan Pieterse <johan@plainsailingisystems.co.za>
 * @license   AGPL-3.0-or-later
 */
class ahgAiCompliancePluginConfiguration extends sfPluginConfiguration
{
    public static $summary = 'EU AI Act Article 12 - tamper-evident AI inference receipt chain';
    public static $version = '0.1.0';
    public static $category = 'ai';

    public function initialize()
    {
        // Routing is wired via routingLoadConfiguration() below.
    }

    /**
     * Register the /.well-known/ai-inference-pubkey endpoint.
     *
     * Uses the same \AtomFramework\Routing\RouteLoader pattern as ahgAIPlugin
     * so the routes show up in both Symfony sfRouting and Laravel routers.
     *
     * Note on the leading dot: Symfony's sfRoute treats the URL as a literal
     * pattern, so /.well-known/... is fine. The reachability of /.well-known/*
     * depends on the front-controller rewrites (apache/nginx). If your
     * deployment strips well-known paths before they hit Symfony, add a rule
     * to forward them - see ahgAiCompliancePlugin/README.md for the snippet.
     */
    public function routingLoadConfiguration(sfEvent $event)
    {
        if (!class_exists('\\AtomFramework\\Routing\\RouteLoader')) {
            return;
        }

        $router = new \AtomFramework\Routing\RouteLoader('aiCompliance');
        $router->get(
            'ahg_ai_compliance_well_known_pubkey',
            '/.well-known/ai-inference-pubkey',
            'wellKnownPubkey'
        );

        // Symfony 1 routing
        $subject = $event->getSubject();
        if ($subject instanceof \sfRouting) {
            $router->register($subject);
        }
    }
}
