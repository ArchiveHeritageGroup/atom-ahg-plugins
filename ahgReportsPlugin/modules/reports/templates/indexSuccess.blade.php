@extends('layouts.page')

<?php
use Illuminate\Database\Capsule\Manager as DB;

// Check which sector plugins are enabled
function isPluginActive($pluginName) {
    static $plugins = null;
    if ($plugins === null) {
        try {
            $pluginNames = DB::table('atom_plugin')
                ->where('is_enabled', 1)
                ->pluck('name')
                ->toArray();
            $plugins = array_flip($pluginNames);
        } catch (Exception $e) {
            $plugins = [];
        }
    }
    return isset($plugins[$pluginName]);
}

$hasLibrary = isPluginActive('ahgLibraryPlugin');
$hasMuseum = isPluginActive('ahgMuseumPlugin');
$hasGallery = isPluginActive('ahgGalleryPlugin');
$hasDam = isPluginActive('arDAMPlugin') || isPluginActive('ahgDAMPlugin');
$hasSpectrum = isPluginActive('ahgSpectrumPlugin');
$hasGrap = isPluginActive('ahgHeritageAccountingPlugin');
$hasHeritage = isPluginActive('ahgHeritageAccountingPlugin');
$hasResearch = isPluginActive('ahgResearchPlugin');
$hasDonor = isPluginActive('ahgDonorAgreementPlugin');
$hasRights = isPluginActive('ahgExtendedRightsPlugin');
$hasCondition = isPluginActive('ahgConditionPlugin');
$hasPrivacy = isPluginActive('ahgPrivacyPlugin');
$hasSecurity = isPluginActive('ahgSecurityClearancePlugin');
$hasAudit = isPluginActive('ahgAuditTrailPlugin');
$hasVendor = isPluginActive('ahgVendorPlugin');
$has3D = isPluginActive('ahg3DModelPlugin');
$hasOais = isPluginActive('ahgPreservationPlugin');
$hasPreservation = isPluginActive('ahgPreservationPlugin');
$hasReportBuilder = isPluginActive('ahgReportBuilderPlugin');
$hasAccessRequest = isPluginActive('ahgAccessRequestPlugin');
$hasRic = isPluginActive('ahgRicExplorerPlugin');
$hasDataMigration = isPluginActive('ahgDataMigrationPlugin');
$hasBackup = isPluginActive('ahgBackupPlugin');
$hasDedupe = isPluginActive('ahgDedupePlugin');
$hasForms = isPluginActive('ahgFormsPlugin');
$hasDoi = isPluginActive('ahgDoiPlugin');
$hasHeritage2 = isPluginActive('ahgHeritagePlugin');
$hasWorkflow = isPluginActive('ahgWorkflowPlugin');
// Zimbabwe Compliance Plugins
$hasCDPA = isPluginActive('ahgCDPAPlugin');
$hasNAZ = isPluginActive('ahgNAZPlugin');
$hasIPSAS = isPluginActive('ahgIPSASPlugin');
$hasNMMZ = isPluginActive('ahgNMMZPlugin');
?>

@section('sidebar')
<?php
$isAdmin = $sf_user->isAdministrator();
$isEditor = $sf_user->hasCredential('editor');
$canManage = $isAdmin || $isEditor;
?>
<div class="sidebar-content">
    <h4>{{ __('Quick Links') }}</h4>
    <ul class="list-unstyled">
        @if ($canManage && $hasReportBuilder)
        <li><a href="/admin/report-builder"><i class="fas fa-tools me-2"></i>{{ __('Report Builder') }}</a></li>
        @endif
        @if ($canManage)
        <li><a href="{{ url_for('export/index') }}"><i class="fas fa-download me-2"></i>{{ __('Export Data') }}</a></li>
        @endif
        @if ($hasPreservation && $canManage)
        <li><a href="{{ url_for(['module' => 'preservation', 'action' => 'index']) }}"><i class="fas fa-shield-alt me-2"></i>{{ __('Preservation') }}</a></li>
        @endif
    </ul>

    @if ($hasVendor && $canManage)
    <h4 class="mt-4">{{ __('Vendors') }}</h4>
    <ul class="list-unstyled">
        <li><a href="{{ url_for(['module' => 'vendor', 'action' => 'index']) }}"><i class="fas fa-building me-2"></i>{{ __('Vendor Dashboard') }}</a></li>
        <li><a href="{{ url_for(['module' => 'vendor', 'action' => 'transactions']) }}"><i class="fas fa-exchange-alt me-2"></i>{{ __('Transactions') }}</a></li>
    </ul>
    @endif

    @if (isPluginActive('ahgResearchPlugin') && $canManage)
    <h4 class="mt-4">{{ __('Research') }}</h4>
    <ul class="list-unstyled">
        <li><a href="{{ url_for(['module' => 'research', 'action' => 'dashboard']) }}"><i class="fas fa-graduation-cap me-2"></i>{{ __('Research Dashboard') }}</a></li>
        <li><a href="{{ url_for(['module' => 'research', 'action' => 'bookings']) }}"><i class="fas fa-calendar-alt me-2"></i>{{ __('Bookings') }}</a></li>
    </ul>
    @endif

    @if (isPluginActive('ahgAuditTrailPlugin') && $canManage)
    <h4 class="mt-4">{{ __('Audit') }}</h4>
    <ul class="list-unstyled">
        <li><a href="{{ url_for(['module' => 'auditTrail', 'action' => 'statistics']) }}"><i class="fas fa-chart-line me-2"></i>{{ __('Statistics') }}</a></li>
        <li><a href="{{ url_for(['module' => 'auditTrail', 'action' => 'browse']) }}"><i class="fas fa-clipboard-list me-2"></i>{{ __('Logs') }}</a></li>
    </ul>
    @endif

    @if ($isAdmin)
    <h4 class="mt-4">{{ __('Settings') }}</h4>
    <ul class="list-unstyled">
        <li><a href="{{ url_for(['module' => 'settings', 'action' => 'index']) }}"><i class="fas fa-cogs me-2"></i>{{ __('AHG Settings') }}</a></li>
        <li><a href="{{ url_for(['module' => 'ahgSettings', 'action' => 'levels']) }}"><i class="fas fa-layer-group me-2"></i>{{ __('Levels of Description') }}</a></li>
    </ul>

    <h4 class="mt-4">{{ __('Compliance') }}</h4>
    <ul class="list-unstyled">
        @if ($hasSecurity)
        <li><a href="/admin/security/compliance"><i class="fas fa-shield-alt me-2"></i>{{ __('Security') }}</a></li>
        @endif
        @if ($hasPrivacy)
        <li><a href="/privacyAdmin"><i class="fas fa-user-shield me-2"></i>{{ __('Privacy &amp; Compliance') }}</a></li>
        @endif
        @if ($hasCondition)
        <li><a href="/admin/condition"><i class="fas fa-heartbeat me-2"></i>{{ __('Condition') }}</a></li>
        @endif
        @if ($hasRights)
        <li><a href="{{ url_for(['module' => 'extendedRights', 'action' => 'dashboard']) }}"><i class="fas fa-gavel me-2"></i>{{ __('Rights') }}</a></li>
        @endif
    </ul>
    @endif

    @if (($hasCDPA || $hasNAZ || $hasIPSAS || $hasNMMZ) && $isAdmin)
    <h4 class="mt-4">{{ __('Zimbabwe Compliance') }}</h4>
    <ul class="list-unstyled">
        @if ($hasCDPA)
        <li><a href="{{ url_for(['module' => 'cdpa', 'action' => 'index']) }}"><i class="fas fa-shield-alt me-2"></i>{{ __('CDPA (Data Protection)') }}</a></li>
        @endif
        @if ($hasNAZ)
        <li><a href="{{ url_for(['module' => 'naz', 'action' => 'index']) }}"><i class="fas fa-landmark me-2"></i>{{ __('NAZ (Archives)') }}</a></li>
        @endif
        @if ($hasIPSAS)
        <li><a href="{{ url_for(['module' => 'ipsas', 'action' => 'index']) }}"><i class="fas fa-coins me-2"></i>{{ __('IPSAS (Assets)') }}</a></li>
        @endif
        @if ($hasNMMZ)
        <li><a href="{{ url_for(['module' => 'nmmz', 'action' => 'index']) }}"><i class="fas fa-monument me-2"></i>{{ __('NMMZ (Monuments)') }}</a></li>
        @endif
    </ul>
    @endif
</div>
@endsection

@section('title')
<h1><i class="fas fa-tachometer-alt"></i> {{ __('Central Dashboard') }}</h1>
@endsection

@section('content')
<div class="reports-dashboard">
    {{-- Stats Row --}}
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card text-center bg-primary text-white">
                <div class="card-body">
                    <h2 class="mb-0">{{ number_format($stats['totalDescriptions'] ?? 0) }}</h2>
                    <p class="mb-0">{{ __('Archival Descriptions') }}</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center bg-success text-white">
                <div class="card-body">
                    <h2 class="mb-0">{{ number_format($stats['totalActors'] ?? 0) }}</h2>
                    <p class="mb-0">{{ __('Authority Records') }}</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center bg-info text-white">
                <div class="card-body">
                    <h2 class="mb-0">{{ number_format($stats['totalDigitalObjects'] ?? 0) }}</h2>
                    <p class="mb-0">{{ __('Digital Objects') }}</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center bg-warning text-dark">
                <div class="card-body">
                    <h2 class="mb-0">{{ number_format($stats['recentUpdates'] ?? 0) }}</h2>
                    <p class="mb-0">{{ __('Updated (7 days)') }}</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Main Content --}}
    <div class="row mb-4">
        {{-- Reports Column --}}
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-file-alt me-2"></i>{{ __('Reports') }}</h5>
                </div>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item"><a href="{{ url_for(['module' => 'reports', 'action' => 'descriptions']) }}"><i class="fas fa-archive me-2 text-muted"></i>{{ __('Archival Descriptions') }}</a></li>
                    <li class="list-group-item"><a href="{{ url_for(['module' => 'reports', 'action' => 'authorities']) }}"><i class="fas fa-users me-2 text-muted"></i>{{ __('Authority Records') }}</a></li>
                    <li class="list-group-item"><a href="{{ url_for(['module' => 'reports', 'action' => 'repositories']) }}"><i class="fas fa-building me-2 text-muted"></i>{{ __('Repositories') }}</a></li>
                    <li class="list-group-item"><a href="{{ url_for(['module' => 'reports', 'action' => 'accessions']) }}"><i class="fas fa-inbox me-2 text-muted"></i>{{ __('Accessions') }}</a></li>
                    @if ($hasDonor)
                    <li class="list-group-item"><a href="{{ url_for(['module' => 'donorAgreement', 'action' => 'browse']) }}"><i class="fas fa-handshake me-2 text-muted"></i>{{ __('Donor Agreements') }}</a></li>
                    @endif
                    <li class="list-group-item"><a href="{{ url_for(['module' => 'reports', 'action' => 'storage']) }}"><i class="fas fa-boxes me-2 text-muted"></i>{{ __('Physical Storage') }}</a></li>
                    <li class="list-group-item"><a href="{{ url_for(['module' => 'reports', 'action' => 'reportSpatialAnalysis']) }}"><i class="fas fa-map-marker-alt me-2 text-muted"></i>{{ __('Spatial Analysis Export') }}</a></li>
                    @if ($hasGallery || $hasLibrary || $hasDam || $hasMuseum || $has3D || $hasSpectrum)
                    <li class="list-group-item border-top mt-2 pt-2"><small class="text-muted fw-bold">{{ __("Sector Reports") }}</small></li>
                    @endif
                    @if ($hasGallery)
                    <li class="list-group-item"><a href="{{ url_for(['module' => 'galleryReports', 'action' => 'index']) }}"><i class="fas fa-palette me-2 text-muted"></i>{{ __("Gallery Reports") }}</a></li>
                    @endif
                    @if ($hasLibrary)
                    <li class="list-group-item"><a href="{{ url_for(['module' => 'libraryReports', 'action' => 'index']) }}"><i class="fas fa-book me-2 text-muted"></i>{{ __("Library Reports") }}</a></li>
                    @endif
                    @if ($hasDam)
                    <li class="list-group-item"><a href="{{ url_for(['module' => 'damReports', 'action' => 'index']) }}"><i class="fas fa-images me-2 text-muted"></i>{{ __("DAM Reports") }}</a></li>
                    @endif
                    @if ($hasMuseum)
                    <li class="list-group-item"><a href="{{ url_for(['module' => 'museumReports', 'action' => 'index']) }}"><i class="fas fa-landmark me-2 text-muted"></i>{{ __("Museum Reports") }}</a></li>
                    @endif
                    @if ($has3D)
                    <li class="list-group-item"><a href="{{ url_for(['module' => 'threeDReports', 'action' => 'index']) }}"><i class="fas fa-cube me-2 text-muted"></i>{{ __("3D Object Reports") }}</a></li>
                    @endif
                    @if ($hasSpectrum)
                    <li class="list-group-item"><a href="{{ url_for(['module' => 'spectrumReports', 'action' => 'index']) }}"><i class="fas fa-clipboard-list me-2 text-muted"></i>{{ __("Spectrum Reports") }}</a></li>
                    @endif
                </ul>
            </div>
        </div>

        {{-- Dashboards Column --}}
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0"><i class="fas fa-tachometer-alt me-2"></i>{{ __('Dashboards') }}</h5>
                </div>
                <ul class="list-group list-group-flush">
                    @if ($hasSpectrum)
                    <li class="list-group-item"><a href="{{ url_for(['module' => 'spectrum', 'action' => 'dashboard']) }}"><i class="fas fa-layer-group me-2 text-muted"></i>{{ __('Spectrum Workflow') }}</a></li>
                    @endif
                    @if ($hasWorkflow)
                    <li class="list-group-item"><a href="/workflow"><i class="fas fa-tasks me-2 text-muted"></i>{{ __('Approval Workflow') }}</a></li>
                    <li class="list-group-item"><a href="/workflow/my-tasks"><i class="fas fa-clipboard-check me-2 text-muted"></i>{{ __('My Workflow Tasks') }}</a></li>
                    <li class="list-group-item"><a href="/workflow/pool"><i class="fas fa-inbox me-2 text-muted"></i>{{ __('Task Pool') }}</a></li>
                    @endif
                    @if ($hasGrap)
                    <li class="list-group-item"><a href="{{ url_for(['module' => 'grapCompliance', 'action' => 'dashboard']) }}"><i class="fas fa-balance-scale me-2 text-muted"></i>{{ __('GRAP 103 Dashboard') }}</a></li>
                    @endif
                    @if ($hasHeritage)
                    <li class="list-group-item"><a href="{{ url_for(['module' => 'heritageAccounting', 'action' => 'dashboard']) }}"><i class="fas fa-landmark me-2 text-muted"></i>{{ __('Heritage Asset Accounting') }}</a></li>
                    @endif
                    @if ($hasCondition)
                    <li class="list-group-item"><a href="/admin/condition"><i class="fas fa-heartbeat me-2 text-muted"></i>{{ __('Condition Management') }}</a></li>
                    @endif
                    @if ($hasOais)
                    <li class="list-group-item"><a href="{{ url_for(['module' => 'oais', 'action' => 'dashboard']) }}"><i class="fas fa-archive me-2 text-muted"></i>{{ __('Digital Preservation (OAIS)') }}</a></li>
                    @endif
                    @if ($hasResearch)
                    <li class="list-group-item"><a href="{{ url_for(['module' => 'research', 'action' => 'dashboard']) }}"><i class="fas fa-graduation-cap me-2 text-muted"></i>{{ __('Research Services') }}</a></li>
                    @endif
                    @if ($hasDonor)
                    <li class="list-group-item"><a href="{{ url_for(['module' => 'donor', 'action' => 'dashboard']) }}"><i class="fas fa-handshake me-2 text-muted"></i>{{ __('Donor Management') }}</a></li>
                    @endif
                    @if ($hasGallery)
                    <li class="list-group-item"><a href="{{ url_for(['module' => 'gallery', 'action' => 'dashboard']) }}"><i class="fas fa-palette me-2 text-muted"></i>{{ __('Gallery Management') }}</a></li>
                    @endif
                    @if ($hasLibrary)
                    <li class="list-group-item"><a href="{{ url_for(['module' => 'library', 'action' => 'browse']) }}"><i class="fas fa-book me-2 text-muted"></i>{{ __('Library Management') }}</a></li>
                    @endif
                    @if ($hasDam)
                    <li class="list-group-item"><a href="{{ url_for(['module' => 'dam', 'action' => 'dashboard']) }}"><i class="fas fa-images me-2 text-muted"></i>{{ __('Digital Asset Management') }}</a></li>
                    @endif
                    @if ($hasMuseum)
                    <li class="list-group-item"><a href="{{ url_for(['module' => 'museum', 'action' => 'dashboard']) }}"><i class="fas fa-landmark me-2 text-muted"></i>{{ __("Museum Dashboard") }}</a></li>
                    <li class="list-group-item"><a href="{{ url_for(['module' => 'dashboard', 'action' => 'index']) }}"><i class="fas fa-chart-line me-2 text-muted"></i>{{ __("Data Quality Dashboard") }}</a></li>
                    <li class="list-group-item"><a href="{{ url_for(['module' => 'exhibition', 'action' => 'dashboard']) }}"><i class="fas fa-theater-masks me-2 text-muted"></i>{{ __("Exhibitions") }}</a></li>
                    @endif
                </ul>
            </div>
        </div>

        {{-- Export Column --}}
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="fas fa-download me-2"></i>{{ __('Export') }}</h5>
                </div>
                <ul class="list-group list-group-flush">
                    @if ($hasGrap)
                    <li class="list-group-item"><a href="{{ url_for(['module' => 'grapCompliance', 'action' => 'nationalTreasuryReport']) }}"><i class="fas fa-balance-scale me-2 text-muted"></i>{{ __('GRAP 103 National Treasury Report') }}</a></li>
                    @endif
                    @if ($hasSpectrum)
                    <li class="list-group-item"><a href="/spectrum/export"><i class="fas fa-history me-2 text-muted"></i>{{ __('Spectrum History Export') }}</a></li>
                    @endif
                    <li class="list-group-item"><a href="{{ url_for(['module' => 'export', 'action' => 'csv']) }}"><i class="fas fa-file-csv me-2 text-muted"></i>{{ __('CSV Export') }}</a></li>
                    <li class="list-group-item"><a href="{{ url_for(['module' => 'export', 'action' => 'ead']) }}"><i class="fas fa-file-code me-2 text-muted"></i>{{ __('EAD Export') }}</a></li>
                </ul>
            </div>
        </div>
    </div>

    {{-- Compliance Row --}}
    <div class="row mb-4">
        @if ($hasSecurity || $hasAudit)
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-header bg-danger text-white">
                    <h5 class="mb-0"><i class="fas fa-shield-alt me-2"></i>{{ __('Security & Compliance') }}</h5>
                </div>
                <ul class="list-group list-group-flush">
                    @if ($hasSecurity)
                    <li class="list-group-item">
                        <a href="/admin/security/compliance"><i class="fas fa-lock me-2 text-muted"></i>{{ __('Security Dashboard') }}</a>
                    </li>
                    <li class="list-group-item"><a href="{{ url_for(['module' => 'securityClearance', 'action' => 'report']) }}"><i class="fas fa-user-shield me-2 text-muted"></i>{{ __('Clearance Report') }}</a></li>
                    @endif
                    @if ($hasAudit)
                    <li class="list-group-item"><a href="{{ url_for(['module' => 'auditTrail', 'action' => 'browse']) }}"><i class="fas fa-clipboard-list me-2 text-muted"></i>{{ __('Audit Log') }}</a></li>
                    <li class="list-group-item"><a href="{{ url_for(['module' => 'auditTrail', 'action' => 'export']) }}"><i class="fas fa-download me-2 text-muted"></i>{{ __('Export Audit Log') }}</a></li>
                    @endif
                </ul>
            </div>
        </div>
        @endif

        @if ($hasPrivacy)
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0"><i class="fas fa-user-shield me-2"></i>{{ __('Privacy &amp; Data Protection') }}</h5>
                </div>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item">
                        <a href="/privacyAdmin"><i class="fas fa-tachometer-alt me-2 text-muted"></i>{{ __('Privacy Dashboard') }}</a>
                    </li>
                    <li class="list-group-item"><a href="/privacyAdmin/ropaList"><i class="fas fa-clipboard-list me-2 text-muted"></i>{{ __('ROPA') }}</a></li>
                    <li class="list-group-item"><a href="/privacyAdmin/dsarList"><i class="fas fa-user-clock me-2 text-muted"></i>{{ __('DSAR Requests') }}</a></li>
                    <li class="list-group-item"><a href="/privacyAdmin/breachList"><i class="fas fa-exclamation-circle me-2 text-muted"></i>{{ __('Breach Register') }}</a></li>
                    <li class="list-group-item"><a href="/privacyAdmin/paiaList"><i class="fas fa-file-contract me-2 text-muted"></i>{{ __('PAIA Requests') }}</a></li>
                    <li class="list-group-item"><a href="/privacyAdmin/officerList"><i class="fas fa-user-tie me-2 text-muted"></i>{{ __('Privacy Officers') }}</a></li>
                    <li class="list-group-item"><a href="/privacyAdmin/config"><i class="fas fa-file-alt me-2 text-muted"></i>{{ __('Template Library') }}</a></li>
                </ul>
            </div>
        </div>
        @endif

        @if ($hasCondition)
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-header bg-secondary text-white">
                    <h5 class="mb-0"><i class="fas fa-heartbeat me-2"></i>{{ __('Condition (Spectrum 5.0)') }}</h5>
                </div>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item">
                        <a href="/admin/condition"><i class="fas fa-clipboard-check me-2 text-muted"></i>{{ __('Condition Dashboard') }}</a>
                    </li>
                    <li class="list-group-item">
                        <a href="/admin/condition/risk"><i class="fas fa-exclamation-triangle me-2 text-muted"></i>{{ __('Risk Assessment') }}</a>
                    </li>
                    <li class="list-group-item">
                        <a href="/condition/templates"><i class="fas fa-clipboard me-2 text-muted"></i>{{ __('Condition Templates') }}</a>
                    </li>
                </ul>
            </div>
        </div>
        @endif
    </div>

    @if ($hasRights)
    {{-- Rights & Licensing Row --}}
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-header bg-purple text-white" style="background-color: #6f42c1 !important;">
                    <h5 class="mb-0"><i class="fas fa-gavel me-2"></i>{{ __('Rights & Licensing') }}</h5>
                </div>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item">
                        <a href="{{ url_for(['module' => 'extendedRights', 'action' => 'dashboard']) }}"><i class="fas fa-tachometer-alt me-2 text-muted"></i>{{ __('Rights Dashboard') }}</a>
                    </li>
                    <li class="list-group-item">
                        <a href="{{ url_for(['module' => 'extendedRights', 'action' => 'batch']) }}"><i class="fas fa-layer-group me-2 text-muted"></i>{{ __('Batch Rights Assignment') }}</a>
                    </li>
                    <li class="list-group-item">
                        <a href="{{ url_for(['module' => 'extendedRights', 'action' => 'index']) }}"><i class="fas fa-list me-2 text-muted"></i>{{ __('Browse Rights') }}</a>
                    </li>
                    <li class="list-group-item">
                        <a href="{{ url_for(['module' => 'extendedRights', 'action' => 'export']) }}"><i class="fas fa-download me-2 text-muted"></i>{{ __('Export Rights Report') }}</a>
                    </li>
                </ul>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-header text-white" style="background-color: #e83e8c !important;">
                    <h5 class="mb-0"><i class="fas fa-lock me-2"></i>{{ __('Embargo Management') }}</h5>
                </div>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item">
                        <a href="{{ url_for(['module' => 'extendedRights', 'action' => 'embargoStatus']) }}"><i class="fas fa-clock me-2 text-muted"></i>{{ __('Active Embargoes') }}</a>
                    </li>
                    <li class="list-group-item">
                        <a href="{{ url_for(['module' => 'extendedRights', 'action' => 'batch', 'action_type' => 'embargo']) }}"><i class="fas fa-plus-circle me-2 text-muted"></i>{{ __('Apply Embargo') }}</a>
                    </li>
                    <li class="list-group-item">
                        <a href="{{ url_for(['module' => 'extendedRights', 'action' => 'expiringEmbargoes']) }}"><i class="fas fa-hourglass-half me-2 text-muted"></i>{{ __('Expiring Soon') }}</a>
                    </li>
                </ul>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-header text-white" style="background-color: #20c997 !important;">
                    <h5 class="mb-0"><i class="fas fa-book-open me-2"></i>{{ __('Rights Vocabularies') }}</h5>
                </div>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item">
                        <a href="{{ url_for(['module' => 'extendedRights', 'action' => 'browse']) }}"><i class="fas fa-copyright me-2 text-muted"></i>{{ __('Rights Statements') }}</a>
                    </li>
                    <li class="list-group-item">
                        <a href="{{ url_for(['module' => 'extendedRights', 'action' => 'browse']) }}#creative-commons"><i class="fab fa-creative-commons me-2 text-muted"></i>{{ __('Creative Commons') }}</a>
                    </li>
                    <li class="list-group-item">
                        <a href="{{ url_for(['module' => 'extendedRights', 'action' => 'browse']) }}#tk-labels"><i class="fas fa-globe-africa me-2 text-muted"></i>{{ __('TK Labels') }}</a>
                    </li>
                    <li class="list-group-item">
                        <a href="{{ url_for(['module' => 'actor', 'action' => 'browse', 'type' => 'rightsHolder']) }}"><i class="fas fa-user-tie me-2 text-muted"></i>{{ __('Rights Holders') }}</a>
                    </li>
                </ul>
            </div>
        </div>
    </div>
    @endif

    @if ($hasVendor)
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-header text-white" style="background-color: #fd7e14 !important;">
                    <h5 class="mb-0"><i class="fas fa-building me-2"></i>{{ __('Vendor Management') }}</h5>
                </div>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item"><a href="{{ url_for(['module' => 'vendor', 'action' => 'index']) }}"><i class="fas fa-tachometer-alt me-2 text-muted"></i>{{ __('Vendor Dashboard') }}</a></li>
                    <li class="list-group-item"><a href="{{ url_for(['module' => 'vendor', 'action' => 'list']) }}"><i class="fas fa-list me-2 text-muted"></i>{{ __('Browse Vendors') }}</a></li>
                    <li class="list-group-item"><a href="{{ url_for(['module' => 'vendor', 'action' => 'add']) }}"><i class="fas fa-plus-circle me-2 text-muted"></i>{{ __('Add Vendor') }}</a></li>
                    <li class="list-group-item"><a href="{{ url_for(['module' => 'vendor', 'action' => 'transactions']) }}"><i class="fas fa-exchange-alt me-2 text-muted"></i>{{ __('Transactions') }}</a></li>
                    <li class="list-group-item"><a href="{{ url_for(['module' => 'vendor', 'action' => 'addTransaction']) }}"><i class="fas fa-file-invoice me-2 text-muted"></i>{{ __('New Transaction') }}</a></li>
                    <li class="list-group-item"><a href="{{ url_for(['module' => 'vendor', 'action' => 'serviceTypes']) }}"><i class="fas fa-tools me-2 text-muted"></i>{{ __('Service Types') }}</a></li>
                </ul>
            </div>
        </div>
    </div>
    @endif

    @if ($hasAccessRequest || $hasRic || $hasBackup)
    <div class="row mb-4">
        @if ($hasAccessRequest)
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-header text-white" style="background-color: #0d6efd !important;">
                    <h5 class="mb-0"><i class="fas fa-shield-alt me-2"></i>{{ __('Access Requests') }}</h5>
                </div>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item"><a href="{{ url_for(['module' => 'accessRequest', 'action' => 'pending']) }}"><i class="fas fa-clock me-2 text-muted"></i>{{ __('Pending Requests') }}</a></li>
                    <li class="list-group-item"><a href="{{ url_for(['module' => 'accessRequest', 'action' => 'approvers']) }}"><i class="fas fa-user-check me-2 text-muted"></i>{{ __('Approvers') }}</a></li>
                    <li class="list-group-item"><a href="{{ url_for(['module' => 'accessRequest', 'action' => 'history']) }}"><i class="fas fa-history me-2 text-muted"></i>{{ __('Request History') }}</a></li>
                </ul>
            </div>
        </div>
        @endif

        @if ($hasRic)
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-header text-white" style="background-color: #6f42c1 !important;">
                    <h5 class="mb-0"><i class="fas fa-project-diagram me-2"></i>{{ __('Records in Contexts (RiC)') }}</h5>
                </div>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item"><a href="{{ url_for(['module' => 'ricDashboard', 'action' => 'index']) }}"><i class="fas fa-tachometer-alt me-2 text-muted"></i>{{ __('RiC Dashboard') }}</a></li>
                    <li class="list-group-item"><a href="{{ url_for(['module' => 'ricDashboard', 'action' => 'explorer']) }}"><i class="fas fa-sitemap me-2 text-muted"></i>{{ __('RiC Explorer') }}</a></li>
                    <li class="list-group-item"><a href="{{ url_for(['module' => 'ricDashboard', 'action' => 'sync']) }}"><i class="fas fa-sync me-2 text-muted"></i>{{ __('Sync Status') }}</a></li>
                </ul>
            </div>
        </div>
        @endif

        @if ($hasBackup)
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-header bg-dark text-white">
                    <h5 class="mb-0"><i class="fas fa-database me-2"></i>{{ __('Backup & Maintenance') }}</h5>
                </div>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item"><a href="{{ url_for(['module' => 'backup', 'action' => 'index']) }}"><i class="fas fa-download me-2 text-muted"></i>{{ __('Backup Dashboard') }}</a></li>
                    <li class="list-group-item"><a href="{{ url_for(['module' => 'backup', 'action' => 'restore']) }}"><i class="fas fa-undo-alt me-2 text-muted"></i>{{ __('Restore') }}</a></li>
                    <li class="list-group-item"><a href="{{ url_for(['module' => 'jobs', 'action' => 'browse']) }}"><i class="fas fa-tasks me-2 text-muted"></i>{{ __('Background Jobs') }}</a></li>
                </ul>
            </div>
        </div>
        @endif
    </div>
    @endif

    @if ($hasDedupe || $hasForms || $hasDoi)
    <div class="row mb-4">
        @if ($hasDedupe)
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-header text-white" style="background-color: #dc3545 !important;">
                    <h5 class="mb-0"><i class="fas fa-clone me-2"></i>{{ __('Duplicate Detection') }}</h5>
                </div>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item"><a href="{{ url_for(['module' => 'dedupe', 'action' => 'index']) }}"><i class="fas fa-tachometer-alt me-2 text-muted"></i>{{ __('Dedupe Dashboard') }}</a></li>
                    <li class="list-group-item"><a href="{{ url_for(['module' => 'dedupe', 'action' => 'browse']) }}"><i class="fas fa-list me-2 text-muted"></i>{{ __('Browse Duplicates') }}</a></li>
                    <li class="list-group-item"><a href="{{ url_for(['module' => 'dedupe', 'action' => 'scan']) }}"><i class="fas fa-search me-2 text-muted"></i>{{ __('Run Scan') }}</a></li>
                    <li class="list-group-item"><a href="{{ url_for(['module' => 'dedupe', 'action' => 'rules']) }}"><i class="fas fa-cog me-2 text-muted"></i>{{ __('Detection Rules') }}</a></li>
                    <li class="list-group-item"><a href="{{ url_for(['module' => 'dedupe', 'action' => 'report']) }}"><i class="fas fa-chart-bar me-2 text-muted"></i>{{ __('Reports') }}</a></li>
                </ul>
            </div>
        </div>
        @endif

        @if ($hasForms)
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-header text-white" style="background-color: #198754 !important;">
                    <h5 class="mb-0"><i class="fas fa-edit me-2"></i>{{ __('Form Templates') }}</h5>
                </div>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item"><a href="{{ url_for(['module' => 'forms', 'action' => 'index']) }}"><i class="fas fa-tachometer-alt me-2 text-muted"></i>{{ __('Forms Dashboard') }}</a></li>
                    <li class="list-group-item"><a href="{{ url_for(['module' => 'forms', 'action' => 'browse']) }}"><i class="fas fa-list me-2 text-muted"></i>{{ __('Browse Templates') }}</a></li>
                    <li class="list-group-item"><a href="{{ url_for(['module' => 'forms', 'action' => 'create']) }}"><i class="fas fa-plus me-2 text-muted"></i>{{ __('Create Template') }}</a></li>
                    <li class="list-group-item"><a href="{{ url_for(['module' => 'forms', 'action' => 'assignments']) }}"><i class="fas fa-link me-2 text-muted"></i>{{ __('Assignments') }}</a></li>
                </ul>
            </div>
        </div>
        @endif

        @if ($hasDoi)
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-header text-white" style="background-color: #0dcaf0 !important;">
                    <h5 class="mb-0"><i class="fas fa-link me-2"></i>{{ __('DOI Management') }}</h5>
                </div>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item"><a href="{{ url_for(['module' => 'doi', 'action' => 'index']) }}"><i class="fas fa-tachometer-alt me-2 text-muted"></i>{{ __('DOI Dashboard') }}</a></li>
                    <li class="list-group-item"><a href="{{ url_for(['module' => 'doi', 'action' => 'browse']) }}"><i class="fas fa-list me-2 text-muted"></i>{{ __('Browse DOIs') }}</a></li>
                    <li class="list-group-item"><a href="{{ url_for(['module' => 'doi', 'action' => 'queue']) }}"><i class="fas fa-tasks me-2 text-muted"></i>{{ __('Minting Queue') }}</a></li>
                    <li class="list-group-item"><a href="{{ url_for(['module' => 'doi', 'action' => 'batchMint']) }}"><i class="fas fa-layer-group me-2 text-muted"></i>{{ __('Batch Mint') }}</a></li>
                    <li class="list-group-item"><a href="{{ url_for(['module' => 'doi', 'action' => 'config']) }}"><i class="fas fa-cog me-2 text-muted"></i>{{ __('DataCite Config') }}</a></li>
                </ul>
            </div>
        </div>
        @endif
    </div>
    @endif

    @if ($hasDataMigration || $hasHeritage2)
    <div class="row mb-4">
        @if ($hasDataMigration)
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-header text-white" style="background-color: #fd7e14 !important;">
                    <h5 class="mb-0"><i class="fas fa-exchange-alt me-2"></i>{{ __('Data Migration') }}</h5>
                </div>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item"><a href="{{ url_for(['module' => 'dataMigration', 'action' => 'index']) }}"><i class="fas fa-tachometer-alt me-2 text-muted"></i>{{ __('Migration Dashboard') }}</a></li>
                    <li class="list-group-item"><a href="{{ url_for(['module' => 'dataMigration', 'action' => 'import']) }}"><i class="fas fa-upload me-2 text-muted"></i>{{ __('Import Data') }}</a></li>
                    <li class="list-group-item"><a href="{{ url_for(['module' => 'dataMigration', 'action' => 'export']) }}"><i class="fas fa-download me-2 text-muted"></i>{{ __('Export Data') }}</a></li>
                    <li class="list-group-item"><a href="{{ url_for(['module' => 'dataMigration', 'action' => 'history']) }}"><i class="fas fa-history me-2 text-muted"></i>{{ __('Migration History') }}</a></li>
                </ul>
            </div>
        </div>
        @endif

        @if ($hasHeritage2)
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-header text-white" style="background-color: #6c757d !important;">
                    <h5 class="mb-0"><i class="fas fa-landmark me-2"></i>{{ __('Heritage Management') }}</h5>
                </div>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item"><a href="{{ url_for(['module' => 'heritage', 'action' => 'adminDashboard']) }}"><i class="fas fa-cogs me-2 text-muted"></i>{{ __('Admin Dashboard') }}</a></li>
                    <li class="list-group-item"><a href="{{ url_for(['module' => 'heritage', 'action' => 'analyticsDashboard']) }}"><i class="fas fa-chart-line me-2 text-muted"></i>{{ __('Analytics Dashboard') }}</a></li>
                    <li class="list-group-item"><a href="{{ url_for(['module' => 'heritage', 'action' => 'custodianDashboard']) }}"><i class="fas fa-user-shield me-2 text-muted"></i>{{ __('Custodian Dashboard') }}</a></li>
                </ul>
            </div>
        </div>
        @endif
    </div>
    @endif

    @if ($hasPreservation)
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-header text-white" style="background-color: #17a2b8 !important;">
                    <h5 class="mb-0"><i class="fas fa-shield-alt me-2"></i>{{ __('Digital Preservation') }}</h5>
                </div>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item"><a href="{{ url_for(['module' => 'preservation', 'action' => 'index']) }}"><i class="fas fa-tachometer-alt me-2 text-muted"></i>{{ __('Preservation Dashboard') }}</a></li>
                    <li class="list-group-item"><a href="{{ url_for(['module' => 'preservation', 'action' => 'fixityLog']) }}"><i class="fas fa-check-double me-2 text-muted"></i>{{ __('Fixity Verification') }}</a></li>
                    <li class="list-group-item"><a href="{{ url_for(['module' => 'preservation', 'action' => 'events']) }}"><i class="fas fa-history me-2 text-muted"></i>{{ __('PREMIS Events') }}</a></li>
                    <li class="list-group-item"><a href="{{ url_for(['module' => 'preservation', 'action' => 'reports']) }}"><i class="fas fa-chart-bar me-2 text-muted"></i>{{ __('Preservation Reports') }}</a></li>
                    <li class="list-group-item"><a href="{{ url_for(['module' => 'tiffpdfmerge', 'action' => 'browse']) }}"><i class="fas fa-layer-group me-2 text-muted"></i>{{ __('TIFF to PDF Merge Jobs') }}</a></li>
                </ul>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-header text-white" style="background-color: #6610f2 !important;">
                    <h5 class="mb-0"><i class="fas fa-file-alt me-2"></i>{{ __('Format Registry') }}</h5>
                </div>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item"><a href="{{ url_for(['module' => 'preservation', 'action' => 'formats']) }}"><i class="fas fa-list me-2 text-muted"></i>{{ __('Browse Formats') }}</a></li>
                    <li class="list-group-item"><a href="{{ url_for(['module' => 'preservation', 'action' => 'formats']) }}?risk=high"><i class="fas fa-exclamation-triangle me-2 text-muted"></i>{{ __('At-Risk Formats') }}</a></li>
                    <li class="list-group-item"><a href="{{ url_for(['module' => 'preservation', 'action' => 'policies']) }}"><i class="fas fa-cogs me-2 text-muted"></i>{{ __('Preservation Policies') }}</a></li>
                </ul>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-header text-white" style="background-color: #28a745 !important;">
                    <h5 class="mb-0"><i class="fas fa-fingerprint me-2"></i>{{ __('Checksums & Integrity') }}</h5>
                </div>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item"><a href="{{ url_for(['module' => 'preservation', 'action' => 'reports']) }}?type=missing"><i class="fas fa-exclamation-circle me-2 text-muted"></i>{{ __('Missing Checksums') }}</a></li>
                    <li class="list-group-item"><a href="{{ url_for(['module' => 'preservation', 'action' => 'reports']) }}?type=stale"><i class="fas fa-clock me-2 text-muted"></i>{{ __('Stale Verification') }}</a></li>
                    <li class="list-group-item"><a href="{{ url_for(['module' => 'preservation', 'action' => 'fixityLog']) }}?status=failed"><i class="fas fa-times-circle me-2 text-muted"></i>{{ __('Failed Checks') }}</a></li>
                </ul>
            </div>
        </div>
    </div>
    @endif

    @if ($hasCDPA || $hasNAZ || $hasIPSAS || $hasNMMZ)
    <div class="row mb-4">
        @if ($hasCDPA)
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-header text-white" style="background-color: #198754 !important;">
                    <h5 class="mb-0"><i class="fas fa-shield-alt me-2"></i>{{ __('CDPA Data Protection') }}</h5>
                </div>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item"><a href="{{ url_for(['module' => 'cdpa', 'action' => 'index']) }}"><i class="fas fa-tachometer-alt me-2 text-muted"></i>{{ __('CDPA Dashboard') }}</a></li>
                    <li class="list-group-item"><a href="{{ url_for(['module' => 'cdpa', 'action' => 'license']) }}"><i class="fas fa-id-card me-2 text-muted"></i>{{ __('POTRAZ License') }}</a></li>
                    <li class="list-group-item"><a href="{{ url_for(['module' => 'cdpa', 'action' => 'requests']) }}"><i class="fas fa-user-clock me-2 text-muted"></i>{{ __('Data Subject Requests') }}</a></li>
                    <li class="list-group-item"><a href="{{ url_for(['module' => 'cdpa', 'action' => 'breaches']) }}"><i class="fas fa-exclamation-triangle me-2 text-muted"></i>{{ __('Breach Register') }}</a></li>
                </ul>
            </div>
        </div>
        @endif

        @if ($hasNAZ)
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-header text-white" style="background-color: #0d6efd !important;">
                    <h5 class="mb-0"><i class="fas fa-landmark me-2"></i>{{ __('NAZ Archives') }}</h5>
                </div>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item"><a href="{{ url_for(['module' => 'naz', 'action' => 'index']) }}"><i class="fas fa-tachometer-alt me-2 text-muted"></i>{{ __('NAZ Dashboard') }}</a></li>
                    <li class="list-group-item"><a href="{{ url_for(['module' => 'naz', 'action' => 'closures']) }}"><i class="fas fa-lock me-2 text-muted"></i>{{ __('Closure Periods') }}</a></li>
                    <li class="list-group-item"><a href="{{ url_for(['module' => 'naz', 'action' => 'permits']) }}"><i class="fas fa-id-card me-2 text-muted"></i>{{ __('Research Permits') }}</a></li>
                    <li class="list-group-item"><a href="{{ url_for(['module' => 'naz', 'action' => 'transfers']) }}"><i class="fas fa-truck me-2 text-muted"></i>{{ __('Records Transfers') }}</a></li>
                </ul>
            </div>
        </div>
        @endif

        @if ($hasIPSAS)
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-header text-white" style="background-color: #ffc107 !important; color: #000 !important;">
                    <h5 class="mb-0" style="color: #000 !important;"><i class="fas fa-coins me-2"></i>{{ __('IPSAS Heritage Assets') }}</h5>
                </div>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item"><a href="{{ url_for(['module' => 'ipsas', 'action' => 'index']) }}"><i class="fas fa-tachometer-alt me-2 text-muted"></i>{{ __('IPSAS Dashboard') }}</a></li>
                    <li class="list-group-item"><a href="{{ url_for(['module' => 'ipsas', 'action' => 'assets']) }}"><i class="fas fa-archive me-2 text-muted"></i>{{ __('Asset Register') }}</a></li>
                    <li class="list-group-item"><a href="{{ url_for(['module' => 'ipsas', 'action' => 'valuations']) }}"><i class="fas fa-calculator me-2 text-muted"></i>{{ __('Valuations') }}</a></li>
                    <li class="list-group-item"><a href="{{ url_for(['module' => 'ipsas', 'action' => 'insurance']) }}"><i class="fas fa-shield-alt me-2 text-muted"></i>{{ __('Insurance') }}</a></li>
                </ul>
            </div>
        </div>
        @endif
    </div>

    @if ($hasNMMZ)
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-header text-white" style="background-color: #6c757d !important;">
                    <h5 class="mb-0"><i class="fas fa-monument me-2"></i>{{ __('NMMZ Monuments') }}</h5>
                </div>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item"><a href="{{ url_for(['module' => 'nmmz', 'action' => 'index']) }}"><i class="fas fa-tachometer-alt me-2 text-muted"></i>{{ __('NMMZ Dashboard') }}</a></li>
                    <li class="list-group-item"><a href="{{ url_for(['module' => 'nmmz', 'action' => 'monuments']) }}"><i class="fas fa-monument me-2 text-muted"></i>{{ __('National Monuments') }}</a></li>
                    <li class="list-group-item"><a href="{{ url_for(['module' => 'nmmz', 'action' => 'antiquities']) }}"><i class="fas fa-vase me-2 text-muted"></i>{{ __('Antiquities Register') }}</a></li>
                    <li class="list-group-item"><a href="{{ url_for(['module' => 'nmmz', 'action' => 'permits']) }}"><i class="fas fa-file-export me-2 text-muted"></i>{{ __('Export Permits') }}</a></li>
                    <li class="list-group-item"><a href="{{ url_for(['module' => 'nmmz', 'action' => 'sites']) }}"><i class="fas fa-map-marker-alt me-2 text-muted"></i>{{ __('Archaeological Sites') }}</a></li>
                </ul>
            </div>
        </div>
    </div>
    @endif
    @endif
</div>
@endsection
