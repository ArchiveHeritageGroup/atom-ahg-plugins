@if($sf_user->isAdministrator())
<li class="dropdown ahg-admin-menu">
    <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">
        <i class="fas fa-archive"></i>
        {{ __('AHG Plugins') }}
        <span class="caret"></span>
    </a>
    <ul class="dropdown-menu">
        <!-- AHG Settings -->
        <li class="dropdown-header">{{ __('Settings') }}</li>
        <li>
            <a href="{{ url_for(['module' => 'settings', 'action' => 'ahgSettings']) }}">
                <i class="fas fa-cogs fa-fw"></i> {{ __('AHG Settings') }}
            </a>
        </li>

        <li role="separator" class="divider"></li>

        <!-- Spectrum Collections -->
        <li class="dropdown-header">{{ __('Collections Management') }}</li>
        <li>
            <a href="{{ url_for(['module' => 'settings', 'action' => 'ahgSettings', 'section' => 'spectrum']) }}">
                <i class="fas fa-archive fa-fw"></i> {{ __('Spectrum Settings') }}
            </a>
        </li>
        <li>
            <a href="{{ url_for(['module' => 'spectrum', 'action' => 'dashboard']) }}">
                <i class="fas fa-tachometer-alt fa-fw"></i> {{ __('Spectrum Dashboard') }}
            </a>
        </li>
        <li>
            <a href="{{ url_for(['module' => 'spectrum', 'action' => 'myTasks']) }}">
                <i class="fas fa-clipboard-list fa-fw"></i> {{ __('My Tasks') }}
                @php
                // Show count badge if user is authenticated (exclude completed tasks)
                if ($sf_user->isAuthenticated()) {
                    $userId = $sf_user->getAttribute('user_id');
                    if ($userId) {
                        $taskCount = \Illuminate\Database\Capsule\Manager::table('spectrum_workflow_state')
                            ->where('assigned_to', $userId)
                            ->where('current_state', '!=', 'completed')
                            ->count();
                        if ($taskCount > 0) {
                            echo '<span class="badge bg-primary ms-1">' . $taskCount . '</span>';
                        }
                    }
                }
                @endphp
            </a>
        </li>

        <li role="separator" class="divider"></li>

        <!-- Data Protection -->
        <li class="dropdown-header">{{ __('Data Protection') }}</li>
        <li>
            <a href="{{ url_for(['module' => 'settings', 'action' => 'ahgSettings', 'section' => 'data_protection']) }}">
                <i class="fas fa-shield-alt fa-fw"></i> {{ __('Compliance Settings') }}
            </a>
        </li>
        <li>
            <a href="{{ url_for(['module' => 'accessRequest', 'action' => 'index']) }}">
                <i class="fas fa-file-alt fa-fw"></i> {{ __('Access Requests') }}
            </a>
        </li>

        <li role="separator" class="divider"></li>

        <!-- Media & IIIF -->
        <li class="dropdown-header">{{ __('Media') }}</li>
        <li>
            <a href="{{ url_for(['module' => 'settings', 'action' => 'ahgSettings', 'section' => 'media']) }}">
                <i class="fas fa-play-circle fa-fw"></i> {{ __('Media Player') }}
            </a>
        </li>
        <li>
            <a href="{{ url_for(['module' => 'settings', 'action' => 'ahgSettings', 'section' => 'iiif']) }}">
                <i class="fas fa-images fa-fw"></i> {{ __('IIIF Viewer') }}
            </a>
        </li>
        <li>
            <a href="{{ url_for(['module' => 'settings', 'action' => 'ahgSettings', 'section' => 'photos']) }}">
                <i class="fas fa-camera fa-fw"></i> {{ __('Photo Settings') }}
            </a>
        </li>

        <li role="separator" class="divider"></li>

        <!-- Jobs & Maintenance -->
        <li class="dropdown-header">{{ __('Maintenance') }}</li>
        <li>
            <a href="{{ url_for(['module' => 'settings', 'action' => 'ahgSettings', 'section' => 'jobs']) }}">
                <i class="fas fa-tasks fa-fw"></i> {{ __('Background Jobs') }}
            </a>
        </li>
        <li>
            <a href="{{ url_for(['module' => 'jobs', 'action' => 'browse']) }}">
                <i class="fas fa-history fa-fw"></i> {{ __('Job History') }}
            </a>
        </li>
    </ul>
</li>
@endif

<style {!! $csp_nonce !!}>
.ahg-admin-menu .dropdown-header {
    font-weight: 600;
    color: #6c757d;
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    padding: 8px 15px 4px;
}

.ahg-admin-menu .dropdown-menu {
    min-width: 220px;
}

.ahg-admin-menu .dropdown-menu a {
    padding: 8px 15px;
}

.ahg-admin-menu .dropdown-menu a i {
    margin-right: 8px;
    color: #6c757d;
}

.ahg-admin-menu .dropdown-menu a:hover i {
    color: inherit;
}

.ahg-admin-menu .divider {
    margin: 5px 0;
}
</style>
