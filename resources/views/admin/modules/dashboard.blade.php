@extends('admin.includes.body')
@section('title', 'Dashboard')
@section('subtitle','Dashboard')
@section('content')
    @php($summary = $dashboardSummary ?? ['total_amount' => 0, 'transaction_count' => 0, 'service_count' => 0, 'shortcode_count' => 0])
    <div class="d-flex align-items-center justify-content-between flex-wrap w-100">
        <div class="mb-2">
            <h5 class="mb-1">Reports</h5>
        </div>
        @if($canSearchDashboard ?? true)
            <div class="ml-auto mb-2" style="width: 360px; max-width: 100%;">
                <label class="input-label mb-1 d-block">When</label>
                <input class="form-control" type="text" name="daterange" value="{{ \Carbon\Carbon::parse($reportStart ?? now()->startOfDay())->format('m/d/Y H:i') }} - {{ \Carbon\Carbon::parse($reportEnd ?? now())->format('m/d/Y H:i') }}" id="reportrange" />
            </div>
        @else
            <div class="ml-auto mb-2 text-muted">Showing today only</div>
        @endif
    </div>

    <div class="row mt-2">
        <div class="col-12 col-md-4 d-flex">
            <div class="card flex-fill">
                <div class="card-body py-4">
                    <div class="text-muted mb-1">Total Collected</div>
                    <h3 class="mb-0">Ksh {{ number_format($summary['total_amount'] ?? 0, 2) }}</h3>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-4 d-flex">
            <div class="card flex-fill">
                <div class="card-body py-4">
                    <div class="text-muted mb-1">Transactions</div>
                    <h3 class="mb-0">{{ number_format($summary['transaction_count'] ?? 0) }}</h3>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-4 d-flex">
            <div class="card flex-fill">
                <div class="card-body py-4">
                    <div class="text-muted mb-1">Services In Scope</div>
                    <h3 class="mb-0">{{ number_format($summary['service_count'] ?? 0) }}</h3>
                    <small class="text-muted">{{ number_format($summary['shortcode_count'] ?? 0) }} shortcode(s)</small>
                </div>
            </div>
        </div>
    </div>

    @if($canViewDashboardReports ?? true)
    <div class="row mt-3">
        @foreach($services as $val)
            @php($reportKey = $val->id)
            @php($serviceReport = $report[$reportKey] ?? ['amount' => 0, 'count' => 0, 'latest' => null])
            <div class="col-12 col-md-3 col-xl-3 d-flex">
                <div class="card flex-fill">
                    <div class="card-body py-4">
                        <div class="row">
                            <div class="col">
                                <h3 class="mb-2">
                                    @if($val->shortcode && $authUser->hasPermission('transaction.view'))
                                        <a href="{{ url('/grouptrans/'.$val->shortcode->shortcode.'/'.$val->service_name) }}?start={{ urlencode(\Carbon\Carbon::parse($reportStart)->format('Y-m-d H:i:s')) }}&end={{ urlencode(\Carbon\Carbon::parse($reportEnd)->format('Y-m-d H:i:s')) }}">{{ ucfirst($val->service_name) }}</a>
                                    @else
                                        {{ ucfirst($val->service_name) }}
                                    @endif
                                </h3>
                                <div class="mb-0">
                                    Ksh {{ number_format($serviceReport['amount'] ?? 0, 2) }}
                                </div>
                                <small class="text-muted d-block mt-2">{{ number_format($serviceReport['count'] ?? 0) }} transaction(s)</small>
                                <small class="text-muted d-block">Shortcode: {{ optional($val->shortcode)->shortcode ?: 'Not linked' }}</small>
                                <small class="text-muted d-block">Owner: {{ optional($val->user)->name ?: (optional(optional($val->shortcode)->user)->name ?: 'Default owner') }}</small>
                                @if(! empty($serviceReport['latest']))
                                    <small class="text-muted d-block">Latest: {{ \Carbon\Carbon::parse($serviceReport['latest'])->format('d M Y H:i') }}</small>
                                @endif
                            </div>

                        </div>
                    </div>
                </div>
            </div>
        @endforeach

        @if(! empty($keywordReport))
            <div class="col-12">
                <h6 class="mt-3 mb-2">Keyword Reports</h6>
            </div>
        @endif

        @foreach($keywordReport ?? [] as $keywordItem)
            <div class="col-12 col-md-3 col-xl-3 d-flex">
                <div class="card flex-fill border">
                    <div class="card-body py-4">
                        <h3 class="mb-2">
                            @if($authUser->hasPermission('transaction.view'))
                                <a href="{{ url('/transaction') }}?keyword={{ urlencode($keywordItem['keyword_id']) }}&start={{ urlencode(\Carbon\Carbon::parse($reportStart)->format('Y-m-d H:i:s')) }}&end={{ urlencode(\Carbon\Carbon::parse($reportEnd)->format('Y-m-d H:i:s')) }}">{{ ucfirst($keywordItem['keyword_name']) }}</a>
                            @else
                                {{ ucfirst($keywordItem['keyword_name']) }}
                            @endif
                        </h3>
                        <div class="mb-0">Ksh {{ number_format($keywordItem['amount'] ?? 0, 2) }}</div>
                        <small class="text-muted d-block mt-2">{{ number_format($keywordItem['count'] ?? 0) }} transaction(s)</small>
                        <small class="text-muted d-block">Keyword on {{ $keywordItem['service_name'] }}</small>
                        <small class="text-muted d-block">Assigned to: {{ $keywordItem['assigned_users'] ?: 'No user assigned' }}</small>
                        <small class="text-muted d-block">Service owner: {{ $keywordItem['service_owner'] ?: 'Default owner' }}</small>
                        @if(! empty($keywordItem['latest']))
                            <small class="text-muted d-block">Latest: {{ \Carbon\Carbon::parse($keywordItem['latest'])->format('d M Y H:i') }}</small>
                        @endif
                    </div>
                </div>
            </div>
        @endforeach

        @if($services->isEmpty() && empty($keywordReport))
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <span class="text-muted">No services are available for this account yet.</span>
                    </div>
                </div>
            </div>
        @endif
    </div>
    @else
        <div class="alert alert-light border mt-3 mb-0">
            Report cards are hidden for this account.
        </div>
    @endif
@endsection
