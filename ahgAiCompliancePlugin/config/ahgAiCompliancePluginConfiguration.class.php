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
    public static $summary = 'EU AI Act compliance - Art. 12 inference receipts + governance registers (systems, models, risks, attestations)';
    public static $version = '0.2.0';
    public static $category = 'ai';

    public function initialize()
    {
        // Wire routing (well-known pubkey + governance UI) and enable the
        // plugin's modules. Mirrors the working ahgIntegrityPlugin / ahgAIPlugin
        // pattern (the previous build defined routingLoadConfiguration but never
        // connected it, so its route never registered).
        $this->dispatcher->connect('routing.load_configuration', [$this, 'routingLoadConfiguration']);

        $enabledModules = sfConfig::get('sf_enabled_modules', []);
        $enabledModules[] = 'aiCompliance';
        $enabledModules[] = 'aiActGovernance';
        sfConfig::set('sf_enabled_modules', array_values(array_unique($enabledModules)));
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

        // EU AI Act governance UI (separate aiActGovernance module).
        $gov = new \AtomFramework\Routing\RouteLoader('aiActGovernance');
        $gov->any('ai_act_index', '/admin/ai-act', 'index');
        $gov->any('ai_act_systems', '/admin/ai-act/systems', 'systems');
        $gov->any('ai_act_system_edit', '/admin/ai-act/system/edit', 'systemEdit');
        $gov->any('ai_act_models', '/admin/ai-act/models', 'models');
        $gov->any('ai_act_model_edit', '/admin/ai-act/model/edit', 'modelEdit');
        $gov->any('ai_act_risks', '/admin/ai-act/risks', 'risks');
        $gov->any('ai_act_risk_edit', '/admin/ai-act/risk/edit', 'riskEdit');
        $gov->any('ai_act_attestations', '/admin/ai-act/attestations', 'attestations');
        $gov->any('ai_act_attestation_edit', '/admin/ai-act/attestation/edit', 'attestationEdit');

        // Symfony 1 routing
        $subject = $event->getSubject();
        if ($subject instanceof \sfRouting) {
            $router->register($subject);
            $gov->register($subject);
        }
    }
}
