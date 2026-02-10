<?php require_once sfConfig::get('sf_plugins_dir') . '/ahgUiOverridesPlugin/lib/helper/AhgLaravelHelper.php'; ?>
<li class="nav-item dropdown">
    <a class="nav-link dropdown-toggle" href="#" id="reportsMenuDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
        <i class="fas fa-chart-bar"></i>
        <span class="d-none d-lg-inline ms-1">{{ __('Reports') }}</span>
    </a>
    <ul class="dropdown-menu dropdown-menu-end mega-menu" aria-labelledby="reportsMenuDropdown">
        <li class="mega-menu-content">
            <div class="row">
                {{-- Reports Column --}}
                <div class="col-md-4">
                    <h6 class="dropdown-header"><i class="fas fa-file-alt me-2"></i>{{ __('Reports') }}</h6>
                    <a class="dropdown-item" href="{{ url_for(['module' => 'reports', 'action' => 'descriptions']) }}">
                        <i class="fas fa-archive me-2"></i>{{ __('Archival Descriptions') }}
                    </a>
                    <a class="dropdown-item" href="{{ url_for(['module' => 'reports', 'action' => 'authorities']) }}">
                        <i class="fas fa-users me-2"></i>{{ __('Authority Records') }}
                    </a>
                    <a class="dropdown-item" href="{{ url_for(['module' => 'reports', 'action' => 'repositories']) }}">
                        <i class="fas fa-building me-2"></i>{{ __('Repositories') }}
                    </a>
                    <a class="dropdown-item" href="{{ url_for(['module' => 'reports', 'action' => 'accessions']) }}">
                        <i class="fas fa-inbox me-2"></i>{{ __('Accessions') }}
                    </a>
                    <a class="dropdown-item" href="{{ url_for(['module' => 'reports', 'action' => 'reportSpatialAnalysis']) }}">
                        <i class="fas fa-map-marker-alt me-2"></i>{{ __('Spatial Analysis Export') }}
                    </a>
                    <div class="dropdown-divider"></div>
                    <h6 class="dropdown-header"><i class="fas fa-clipboard-check me-2"></i>{{ __('Audit') }}</h6>
                    <a class="dropdown-item" href="{{ url_for(['module' => 'dashboard', 'action' => 'index']) }}">
                        <i class="fas fa-chart-line me-2"></i>{{ __('Data Quality') }}
                    </a>
                </div>

                {{-- Dashboards Column --}}
                <div class="col-md-4">
                    <h6 class="dropdown-header"><i class="fas fa-tachometer-alt me-2"></i>{{ __('Dashboards') }}</h6>
                    <a class="dropdown-item" href="/admin/dashboard">
                        <i class="fas fa-tachometer-alt me-2"></i>{{ __('Central Dashboard') }}
                    </a>
                    @if (ahg_is_plugin_enabled('ahgSpectrumPlugin'))
                    <a class="dropdown-item" href="{{ url_for(['module' => 'spectrum', 'action' => 'dashboard']) }}">
                        <i class="fas fa-layer-group me-2"></i>{{ __('Collections Management') }}
                    </a>
                    <a class="dropdown-item" href="{{ url_for(['module' => 'spectrum', 'action' => 'grapDashboard']) }}">
                        <i class="fas fa-balance-scale me-2"></i>{{ __('GRAP 103') }}
                    </a>
                    @endif
                    <a class="dropdown-item" href="{{ url_for(['module' => 'dashboard', 'action' => 'index']) }}">
                        <i class="fas fa-chart-bar me-2"></i>{{ __('Data Quality') }}
                    </a>
                </div>

                {{-- Export/Import Column --}}
                <div class="col-md-4">
                    <h6 class="dropdown-header"><i class="fas fa-download me-2"></i>{{ __('Export') }}</h6>
                    <a class="dropdown-item" href="{{ url_for(['module' => 'export', 'action' => 'archival']) }}">
                        <i class="fas fa-file-export me-2"></i>{{ __('Full Export') }}
                    </a>
                    <a class="dropdown-item" href="{{ url_for(['module' => 'export', 'action' => 'csv']) }}">
                        <i class="fas fa-file-csv me-2"></i>{{ __('CSV (ISAD-G)') }}
                    </a>
                    <a class="dropdown-item" href="{{ url_for(['module' => 'export', 'action' => 'ead']) }}">
                        <i class="fas fa-file-code me-2"></i>{{ __('EAD') }}
                    </a>
                    <a class="dropdown-item" href="{{ url_for(['module' => 'cidoc', 'action' => 'export']) }}">
                        <i class="fas fa-project-diagram me-2"></i>{{ __('CIDOC-CRM') }}
                    </a>
                    <div class="dropdown-divider"></div>
                    <h6 class="dropdown-header"><i class="fas fa-upload me-2"></i>{{ __('Import') }}</h6>
                    <a class="dropdown-item" href="{{ url_for(['module' => 'object', 'action' => 'importSelect']) }}">
                        <i class="fas fa-file-import me-2"></i>{{ __('Import Data') }}
                    </a>
                </div>
            </div>
        </li>
    </ul>
</li>
