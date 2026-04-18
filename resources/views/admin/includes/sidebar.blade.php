<nav class="sidebar sidebar-sticky">
    <div class="sidebar-content  js-simplebar">
        <a class="sidebar-brand px-4" href="{{ url('dashboard') }}">
            <img class="img-fluid" src="{{ asset('assets/img/taifa.jpg') }}" alt="mpesa">
        </a>

        @php($currentUser = Auth::user())
        @php($hasAuditLogsRoute = Route::has('audit-logs.index'))
        @php($hasDocumentationRoute = Route::has('documentation.index'))
        @php($hasRolesRoute = Route::has('roles.index'))
        @php($hasKeywordsRoute = Route::has('keywords.index'))
        @php($hasTransactionReportsRoute = Route::has('transaction-reports.index'))
        @php($canManageKeywords = $currentUser && $currentUser->hasPermission('users.update') && $hasKeywordsRoute)
        <ul class="sidebar-nav">
            @if($currentUser && ($currentUser->canAccessPage('dashboard') || $currentUser->canAccessPage('shortcode') || $currentUser->canAccessPage('services') || $canManageKeywords || $currentUser->canAccessPage('transaction') || ($currentUser->canAccessPage('transaction_reports') && $hasTransactionReportsRoute) || ($currentUser->canAccessPage('documentation') && $hasDocumentationRoute)))
                <li class="sidebar-header">
                    Main
                </li>
            @endif

            @if($currentUser && $currentUser->canAccessPage('dashboard'))
                <li class="sidebar-item {{ Request::is('dashboard') ? 'active' : '' }}">
                    <a class="sidebar-link font-weight-bold" href="{{ url('dashboard') }}">
                        <i class="align-middle" data-feather="home"></i> <span class="align-middle">Dashboard</span>
                    </a>
                </li>
            @endif

            @if($currentUser && $currentUser->canAccessPage('shortcode'))
                <li class="sidebar-item {{ Request::is('shortcode') ? 'active' : '' }}">
                    <a class="sidebar-link font-weight-bold" href="{{ url('shortcode') }}">
                        <i class="align-middle" data-feather="pocket"></i> <span class="align-middle">Shortcode</span>
                    </a>
                </li>
            @endif

            @if($currentUser && $currentUser->canAccessPage('services'))
                <li class="sidebar-item {{ Request::is('services') ? 'active' : '' }}">
                    <a class="sidebar-link font-weight-bold" href="{{ url('services') }}">
                        <i class="align-middle" data-feather="package"></i> <span class="align-middle">Services</span>
                    </a>
                </li>
            @endif

            @if($canManageKeywords)
                <li class="sidebar-item {{ Request::is('keywords') ? 'active' : '' }}">
                    <a class="sidebar-link font-weight-bold" href="{{ route('keywords.index') }}">
                        <i class="align-middle" data-feather="tag"></i> <span class="align-middle">Keywords</span>
                    </a>
                </li>
            @endif

            @if($currentUser && $currentUser->canAccessPage('transaction'))
                <li class="sidebar-item {{ Request::is('transaction') || Request::is('grouptrans*') ? 'active' : '' }}">
                    <a class="sidebar-link font-weight-bold" href="{{ url('transaction') }}">
                        <i class="align-middle" data-feather="briefcase"></i>
                        <span class="align-middle">Transactions</span>
                    </a>
                </li>
            @endif

            @if($currentUser && $currentUser->canAccessPage('transaction_reports') && $hasTransactionReportsRoute)
                <li class="sidebar-item {{ Request::is('transaction-reports') ? 'active' : '' }}">
                    <a class="sidebar-link font-weight-bold" href="{{ route('transaction-reports.index') }}">
                        <i class="align-middle" data-feather="bar-chart-2"></i>
                        <span class="align-middle">Transaction Reports</span>
                    </a>
                </li>
            @endif

            @if($currentUser && $currentUser->canAccessPage('documentation') && $hasDocumentationRoute)
                <li class="sidebar-item {{ Request::is('documentation') ? 'active' : '' }}">
                    <a class="sidebar-link font-weight-bold" href="{{ route('documentation.index') }}">
                        <i class="align-middle" data-feather="book-open"></i>
                        <span class="align-middle">API Documentation</span>
                    </a>
                </li>
            @endif
            {{--<li class="sidebar-item">
                <a href="#dashboards" data-toggle="collapse" class="font-weight-bold sidebar-link collapsed">
                    <i class="align-middle" data-feather="shield"></i>
                    <span class="align-middle">Financial Services</span>
                </a>
                <ul id="dashboards" class="sidebar-dropdown list-unstyled collapse ">
                    <li class="sidebar-item"><a class="sidebar-link" href="{{ url("bulk_dispersement") }}">Bulk Dispersment</a></li>
                    <li class="sidebar-item"><a class="sidebar-link" href="{{ url("refund") }}">Refund</a></li>
                    <li class="sidebar-item"><a class="sidebar-link" href="{{ url("account_balance") }}">Account Balance</a></li>
                    <li class="sidebar-item"><a class="sidebar-link" href="{{ url("b2b") }}">B2B</a></li>
                </ul>
            </li>--}}
            @if($currentUser && ($currentUser->canAccessPage('users') || ($currentUser->hasPermission('users.manage_roles') && $hasRolesRoute) || ($currentUser->canAccessPage('audit_logs') && $hasAuditLogsRoute)))
                <li class="sidebar-header">
                   Users
                </li>

                @if($currentUser->canAccessPage('users'))
                    <li class="sidebar-item {{ Request::is('users') ? 'active' : '' }}">
                        <a class="sidebar-link font-weight-bold" href="{{ url("users") }}">
                            <i class="align-middle" data-feather="clipboard"></i> <span class="align-middle">Users</span>
                        </a>
                    </li>
                @endif

                @if($currentUser->hasPermission('users.manage_roles') && $hasRolesRoute)
                    <li class="sidebar-item {{ Request::is('roles') ? 'active' : '' }}">
                        <a class="sidebar-link font-weight-bold" href="{{ route('roles.index') }}">
                            <i class="align-middle" data-feather="shield"></i> <span class="align-middle">Roles</span>
                        </a>
                    </li>
                @endif

                @if($currentUser->canAccessPage('audit_logs') && $hasAuditLogsRoute)
                    <li class="sidebar-item {{ Request::is('audit-logs') ? 'active' : '' }}">
                        <a class="sidebar-link font-weight-bold" href="{{ route('audit-logs.index') }}">
                            <i class="align-middle" data-feather="clock"></i> <span class="align-middle">Audit Logs</span>
                        </a>
                    </li>
                @endif
            @endif
{{--           <li class="sidebar-item">--}}
{{--                <a class="sidebar-link font-weight-bold" href="{{ url("options") }}">--}}
{{--                    <i class="align-middle" data-feather="book-open"></i> <span class="align-middle">Options</span>--}}
{{--                </a>--}}
{{--            </li>--}}
          {{--  <li class="sidebar-header">
                Settings
            </li>--}}
        </ul>
    </div>
</nav>
