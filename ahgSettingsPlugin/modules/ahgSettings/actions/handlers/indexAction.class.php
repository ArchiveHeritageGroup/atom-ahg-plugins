<?php
use AtomExtensions\Services\AclService;

class AhgSettingsIndexAction extends sfAction
{
    public function execute($request)
    {
        if (!$this->context->user->isAdministrator()) {
            AclService::forwardUnauthorized();
        }


        // Check which plugins are enabled
        $hasResearch = in_array('ahgResearchPlugin', sfProjectConfiguration::getActive()->getPlugins());
        $hasAuditTrail = in_array('ahgAuditTrailPlugin', sfProjectConfiguration::getActive()->getPlugins());
        $hasRic = in_array('ahgRicExplorerPlugin', sfProjectConfiguration::getActive()->getPlugins());
        $hasAccessRequest = in_array('ahgAccessRequestPlugin', sfProjectConfiguration::getActive()->getPlugins());
        $hasCondition = in_array('ahgConditionPlugin', sfProjectConfiguration::getActive()->getPlugins());
        $hasSpectrum = in_array('ahgSpectrumPlugin', sfProjectConfiguration::getActive()->getPlugins());
        $hasIiif = in_array('IiifViewerFramework', sfProjectConfiguration::getActive()->getPlugins());
        $hasLibrary = in_array('ahgLibraryPlugin', sfProjectConfiguration::getActive()->getPlugins());
        
        // GLAM/DAM plugins
        $hasMuseum = in_array('ahgMuseumPlugin', sfProjectConfiguration::getActive()->getPlugins());
        $hasGallery = in_array('ahgGalleryPlugin', sfProjectConfiguration::getActive()->getPlugins());
        $hasDam = in_array('ahgDamPlugin', sfProjectConfiguration::getActive()->getPlugins());
        $hasDisplay = in_array('ahgDisplayPlugin', sfProjectConfiguration::getActive()->getPlugins());
        $hasGlamDam = $hasMuseum || $hasGallery || $hasLibrary || $hasDam || $hasDisplay || $hasSpectrum;
        $hasHeritage = in_array('ahgHeritageAccountingPlugin', sfProjectConfiguration::getActive()->getPlugins());
        $hasPrivacy = in_array('ahgPrivacyPlugin', sfProjectConfiguration::getActive()->getPlugins());

        $this->sections = [];

        // === BASE (Always Available) ===

        $this->sections['general'] = [
            'label' => 'Theme Configuration',
            'icon' => 'fa-palette',
            'description' => 'Customize AHG theme appearance and branding',
            'url' => 'admin/ahg-settings/section?section=general'
        ];

        $this->sections['email'] = [
            'label' => 'Email Settings',
            'icon' => 'fa-envelope',
            'description' => 'SMTP configuration and email templates',
            'url' => 'admin/ahg-settings/email'
        ];

        $this->sections['sectorNumbering'] = [
            'label' => 'Sector Numbering',
            'icon' => 'fa-hashtag',
            'description' => 'Configure unique numbering schemes per GLAM/DAM sector (Archive, Museum, Library, Gallery, DAM)',
            'url' => 'ahgSettings/sectorNumbering',
        ];

        // Researcher - conditional but part of default install
        if ($hasResearch) {
            $this->sections['research'] = [
                'label' => 'Reading Room',
                'icon' => 'fa-book-reader',
                'description' => 'Researcher registration and reading room settings',
                'url' => 'research/rooms'
            ];
        }

        $this->sections['metadata'] = [
            'label' => 'Metadata Extraction',
            'icon' => 'fa-tags',
            'description' => 'Auto-extract EXIF, IPTC, XMP from uploaded files',
            'url' => 'admin/ahg-settings/section?section=metadata'
        ];

        $this->sections['media'] = [
            'label' => 'Media Player',
            'icon' => 'fa-play-circle',
            'description' => 'Enhanced media player configuration',
            'url' => 'admin/ahg-settings/section?section=media'
        ];

        $this->sections['media_processing'] = [
            'label' => 'Media Processing',
            'icon' => 'fa-cogs',
            'description' => 'Transcription, thumbnails, waveforms & media derivatives',
            'url' => 'mediaSettings/index'
        ];

        $this->sections['tts'] = [
            'label' => 'Text-to-Speech',
            'icon' => 'fa-volume-up',
            'description' => 'Configure read-aloud accessibility feature for record pages',
            'url' => 'ahgSettings/tts'
        ];

        $this->sections['watermark'] = [
            'label' => 'Watermark Settings',
            'icon' => 'fa-stamp',
            'description' => 'Configure default watermarks for images and downloads',
            'url' => 'securityClearance/watermarkSettings'
        ];
        $this->sections['jobs'] = [
            'label' => 'Background Jobs',
            'icon' => 'fa-tasks',
            'description' => 'Job queue and scheduling settings',
            'url' => 'admin/ahg-settings/section?section=jobs'
        ];

        if ($hasLibrary) {
            $this->sections['library'] = [
                'label' => 'Library Settings',
                'icon' => 'fa-book',
                'description' => 'ISBN providers and library module configuration',
                'url' => 'library/isbn-providers'
            ];
        }

        // Levels of Description - show when any GLAM/DAM plugin enabled
        if ($hasGlamDam) {
            $this->sections['levels'] = [
                'label' => 'Levels of Description',
                'icon' => 'fa-layer-group',
                'description' => 'Assign levels to sectors (Archive, Museum, Library, Gallery, DAM)',
                'url' => 'ahgSettings/levels'
            ];
        }

        $this->sections['plugins'] = [
            'label' => 'Plugin Management',
            'icon' => 'fa-puzzle-piece',
            'description' => 'Enable or disable plugins',
            'url' => 'admin/ahg-settings/plugins'
        ];

        // Multi-Tenant - show when ahgMultiTenantPlugin is enabled
        $hasMultiTenant = in_array('ahgMultiTenantPlugin', sfProjectConfiguration::getActive()->getPlugins());
        if ($hasMultiTenant) {
            $this->sections['multi_tenant'] = [
                'label' => 'Multi-Tenancy',
                'icon' => 'fa-building',
                'description' => 'Repository-based multi-tenancy with user hierarchy (Admin > Super User > User)',
                'url' => 'admin/ahg-settings/section?section=multi_tenant'
            ];
        }

        // API Keys - show when ahgAPIPlugin is enabled
        $hasApi = in_array('ahgAPIPlugin', sfProjectConfiguration::getActive()->getPlugins());
        if ($hasApi) {
            $this->sections['api'] = [
                'label' => 'API Keys',
                'icon' => 'fa-key',
                'description' => 'Manage REST API keys for external integrations',
                'url' => 'admin/ahg-settings/api-keys'
            ];
        }


        // AI Services - show when ahgAIPlugin is enabled
        $hasAI = in_array('ahgAIPlugin', sfProjectConfiguration::getActive()->getPlugins());
        if ($hasAI) {
            $this->sections['ai'] = [
                'label' => 'AI Services',
                'icon' => 'fa-brain',
                'description' => 'NER, Summarization, Spell Check - processing mode and field mappings',
                'url' => 'admin/ahg-settings/ai-services'
            ];
        }

        // === OPTIONAL (Plugin-dependent) ===

        if ($hasAuditTrail) {
            $this->sections['audit'] = [
                'label' => 'Audit Trail',
                'icon' => 'fa-history',
                'description' => 'View change history and user activity logs',
                'url' => 'auditTrail/browse'
            ];
        }

        if ($hasIiif) {
            $this->sections['carousel'] = [
                'label' => 'Carousel Settings',
                'icon' => 'fa-images',
                'description' => 'Homepage carousel and slideshow configuration',
                'url' => 'admin/iiif-settings'
            ];

            $this->sections['iiif'] = [
                'label' => 'IIIF Viewer',
                'icon' => 'fa-photo-video',
                'description' => 'IIIF image viewer configuration',
                'url' => 'admin/ahg-settings/section?section=iiif'
            ];
        }

        if ($hasSpectrum) {
            $this->sections['spectrum'] = [
                'label' => 'Spectrum / Collections',
                'icon' => 'fa-archive',
                'description' => 'Museum collections management settings',
                'url' => 'admin/ahg-settings/section?section=spectrum'
            ];
        }


        if ($hasCondition) {
            $this->sections['photos'] = [
                'label' => 'Condition Photos',
                'icon' => 'fa-camera',
                'description' => 'Photo upload and thumbnail settings',
                'url' => 'admin/ahg-settings/section?section=photos'
            ];
        }

        if ($hasRic) {
            $this->sections['fuseki'] = [
                'label' => 'Fuseki / RIC Triplestore',
                'icon' => 'fa-project-diagram',
                'description' => 'Configure Apache Jena Fuseki connection for RIC ontology',
                'url' => 'admin/ahg-settings/section?section=fuseki'
            ];
        }

        if ($hasHeritage) {
            $this->sections['heritage'] = [
                'label' => 'Heritage Accounting',
                'icon' => 'fa-landmark',
                'description' => 'Multi-standard heritage asset accounting settings (GRAP, FRS, GASB, PSAS)',
                'url' => 'heritageAdmin/index'
            ];
        }

        if ($hasPrivacy) {
            $this->sections['privacy'] = [
                'label' => 'Privacy Compliance',
                'icon' => 'fa-user-shield',
                'description' => 'POPIA, NDPA, GDPR compliance - DSARs, Breaches, ROPA, PAIA',
                'url' => 'privacyAdmin'
            ];
        }

        // ICIP - show when ahgICIPPlugin is enabled
        $hasIcip = in_array('ahgICIPPlugin', sfProjectConfiguration::getActive()->getPlugins());
        if ($hasIcip) {
            $this->sections['icip'] = [
                'label' => 'ICIP Settings',
                'icon' => 'fa-shield-alt',
                'description' => 'Indigenous Cultural and Intellectual Property management settings',
                'url' => 'ahgSettings/icipSettings'
            ];
        }

        // Services Monitor - always available
        $this->sections['services'] = [
            'label' => 'Services Monitor',
            'icon' => 'fa-heartbeat',
            'description' => 'Monitor system services health and configure notifications',
            'url' => 'ahgSettings/services'
        ];

        // Cron Jobs Info - always available
        $this->sections['cron_jobs'] = [
            'label' => 'Cron Jobs & System Info',
            'icon' => 'fa-clock',
            'description' => 'View all available cron jobs, scheduling examples, and installed software versions',
            'url' => 'ahgSettings/cronJobs'
        ];

        // Semantic Search - always available (core feature)
        $this->sections['semantic_search'] = [
            'label' => 'Semantic Search',
            'icon' => 'fa-brain',
            'description' => 'Thesaurus, synonyms, query expansion and search enhancement settings',
            'url' => 'semanticSearchAdmin'
        ];

        // E-Commerce / Cart Plugin
        $hasCart = in_array('ahgCartPlugin', sfProjectConfiguration::getActive()->getPlugins());
        if ($hasCart) {
            $this->sections['ecommerce'] = [
                'label' => 'E-Commerce',
                'icon' => 'fa-store',
                'description' => 'Shopping cart, product pricing, payment gateway and order management',
                'url' => 'cart/adminSettings'
            ];
            $this->sections['orders'] = [
                'label' => 'Order Management',
                'icon' => 'fa-shopping-bag',
                'description' => 'View and manage customer orders',
                'url' => 'cart/adminOrders'
            ];
        }
    }
}
