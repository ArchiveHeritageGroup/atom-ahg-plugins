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
        $hasResearch = checkPluginEnabled('ahgResearchPlugin');
        $hasAuditTrail = checkPluginEnabled('ahgAuditTrailPlugin');
        $hasRic = checkPluginEnabled('ahgRicExplorerPlugin');
        $hasAccessRequest = checkPluginEnabled('ahgAccessRequestPlugin');
        $hasCondition = checkPluginEnabled('ahgConditionPlugin');
        $hasSpectrum = checkPluginEnabled('ahgSpectrumPlugin');
        $hasIiif = checkPluginEnabled('IiifViewerFramework');
        
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
            'url' => 'ahgMediaSettings/index'
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
                'url' => 'ahgLibraryPlugin/isbnProviders'
            ];
        }
        
        $this->sections['plugins'] = [
            'label' => 'Plugin Management',
            'icon' => 'fa-puzzle-piece',
            'description' => 'Enable or disable plugins',
            'url' => 'admin/ahg-settings/plugins'
        ];
        
        // === OPTIONAL (Plugin-dependent) ===
        
        if ($hasAuditTrail) {
            $this->sections['audit'] = [
                'label' => 'Audit Trail',
                'icon' => 'fa-history',
                'description' => 'View change history and user activity logs',
                'url' => 'ahgAuditTrailPlugin/browse'
            ];
        }
        
        if ($hasIiif) {
            $this->sections['carousel'] = [
                'label' => 'Carousel Settings',
                'icon' => 'fa-images',
                'description' => 'Homepage carousel and slideshow configuration',
                'url' => 'ahgIiifViewerSettings/index'
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
        
        if ($hasAccessRequest) {
            $this->sections['data_protection'] = [
                'label' => 'Data Protection',
                'icon' => 'fa-shield-alt',
                'description' => 'POPIA/GDPR compliance settings',
                'url' => 'admin/ahg-settings/section?section=data_protection'
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
    }
}
