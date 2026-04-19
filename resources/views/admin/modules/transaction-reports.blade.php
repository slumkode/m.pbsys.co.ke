@extends('admin.includes.body')
@section('title', 'Transaction Reports')
@section('subtitle','Transaction Reports')
@section('content')
    @php
        $canSearchTransactions = $authUser->hasPermission('transaction.search');
        $canDownloadTransactions = $authUser->hasPermission('transaction.download');
        $transactionExportButtons = [];
        if ($canDownloadTransactions) {
            $transactionExportButtons = ['copy', 'print', 'excel', 'csv', 'pdf', 'pageLength'];
        }
    @endphp
    <style>
        #daily-transaction-reports-table_wrapper .dataTables_filter {
            display: none;
        }
    </style>
    <div class="card">
        <div class="card-header">
            <div class="form-row">
                <div class="col-md-3 mb-3">
                    <label for="report-date-range" class="control-label">When</label>
                    <input type="text" id="report-date-range" class="form-control" autocomplete="off">
                    <input type="hidden" id="report-date-from" value="{{ $defaultDateFrom->format('Y-m-d H:i:s') }}">
                    <input type="hidden" id="report-date-to" value="{{ $defaultDateTo->format('Y-m-d H:i:s') }}">
                </div>
                <div class="col-md-3 mb-3">
                    <label for="report-shortcode-id" class="control-label">Shortcode</label>
                    <select id="report-shortcode-id" class="custom-select">
                        <option value="">All visible shortcodes</option>
                        @foreach($shortcodeOptions as $shortcode)
                            <option value="{{ $shortcode->id }}">{{ $shortcode->shortcode }}{{ $shortcode->group ? ' - '.$shortcode->group : '' }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 mb-3">
                    <label for="report-service-key" class="control-label">Service</label>
                    <select id="report-service-key" class="custom-select">
                        <option value="">All visible services</option>
                        @foreach($serviceOptions as $service)
                            <option value="{{ $service->shortcode_id }}|{{ $service->service_name }}">{{ $service->service_name }} - {{ optional($service->shortcode)->shortcode ?: 'No shortcode' }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 mb-3">
                    <label for="report-keyword-id" class="control-label">Keyword</label>
                    <select id="report-keyword-id" class="custom-select">
                        <option value="">All visible keywords</option>
                        @foreach($keywordOptions as $keyword)
                            <option value="{{ $keyword->id }}">{{ $keyword->keyword_name }} - {{ optional(optional($keyword->service)->shortcode)->shortcode ?: 'No shortcode' }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="form-row">
                @if($canSearchTransactions)
                    <div class="col-md-3 mb-3">
                        <label for="report-account-search" class="control-label">Account</label>
                        <input type="text" id="report-account-search" class="form-control" placeholder="Account number">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label for="report-code-search" class="control-label">Trans ID</label>
                        <input type="text" id="report-code-search" class="form-control" placeholder="Transaction code">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label for="report-customer-search" class="control-label">Customer / MSISDN</label>
                        <input type="text" id="report-customer-search" class="form-control" placeholder="Customer or phone">
                    </div>
                    <div class="col-md-3 mb-3 d-flex align-items-end justify-content-md-end">
                        <button type="button" class="btn btn-default" id="reset-report-filters">Reset Search</button>
                    </div>
                @else
                    <div class="col-md-12 mb-3 d-flex align-items-end justify-content-md-end">
                        <button type="button" class="btn btn-default" id="reset-report-filters">Reset Search</button>
                    </div>
                @endif
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="daily-transaction-reports-table" class="table table-striped table-hover custom-list-table">
                    <thead>
                    <tr>
                        <th>Day</th>
                        <th>Transactions</th>
                        <th>Total Amount</th>
                        <th>Shortcodes</th>
                        <th>Services</th>
                        <th>Action</th>
                    </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
@endsection
@section('script')
    <script>
        $(function () {
            var reloadTimer = null;
            var defaultStart = moment(@json($defaultDateFrom->format('Y-m-d H:i:s')), 'YYYY-MM-DD HH:mm:ss');
            var defaultEnd = moment(@json($defaultDateTo->format('Y-m-d H:i:s')), 'YYYY-MM-DD HH:mm:ss');

            $('#report-date-range').val(defaultStart.format('DD/MM/YYYY HH:mm') + ' - ' + defaultEnd.format('DD/MM/YYYY HH:mm'));

            var reportTable = $('#daily-transaction-reports-table').DataTable({
                processing: true,
                serverSide: true,
                deferRender: true,
                responsive: true,
                autoWidth: false,
                pageLength: 25,
                searchDelay: 350,
                lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
                dom: "{{ $canDownloadTransactions ? 'Brtip' : 'rtip' }}",
                buttons: {!! json_encode($transactionExportButtons) !!},
                ajax: {
                    url: "{{ route('transaction-reports.datatable') }}",
                    dataType: 'json',
                    type: 'POST',
                    headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                    data: function (d) {
                        d.date_from = $('#report-date-from').val();
                        d.date_to = $('#report-date-to').val();
                        d.shortcode_id = $('#report-shortcode-id').val();
                        d.service_key = $('#report-service-key').val();
                        d.keyword_id = $('#report-keyword-id').val();
                        d.account = $('#report-account-search').val();
                        d.transaction_code = $('#report-code-search').val();
                        d.customer = $('#report-customer-search').val();
                    },
                    error: function (xhr) {
                        toastr.error(
                            (xhr.responseJSON && (xhr.responseJSON.message || xhr.responseJSON.error)) || 'Unable to load transaction reports right now.',
                            'Transaction Reports',
                            {
                                timeOut: 2500,
                                closeButton: true,
                                progressBar: true,
                                newestOnTop: true
                            }
                        );
                    }
                },
                columns: [
                    { data: 'day', name: 'day' },
                    { data: 'transactions', name: 'transactions' },
                    { data: 'total_amount', name: 'total_amount' },
                    { data: 'shortcodes', name: 'shortcodes' },
                    { data: 'services', name: 'services' },
                    { data: 'action', name: 'action', orderable: false, searchable: false }
                ],
                order: [[0, 'desc']]
            });

            function reloadReports() {
                reportTable.ajax.reload(null, true);
            }

            function queueReload() {
                clearTimeout(reloadTimer);
                reloadTimer = setTimeout(reloadReports, 350);
            }

            $('#report-date-range').daterangepicker({
                autoUpdateInput: false,
                timePicker: true,
                timePicker24Hour: true,
                timePickerIncrement: 5,
                startDate: defaultStart,
                endDate: defaultEnd,
                opens: 'left',
                locale: {
                    cancelLabel: 'Clear',
                    format: 'DD/MM/YYYY HH:mm'
                }
            });

            $('#report-date-range').on('apply.daterangepicker', function (ev, picker) {
                $('#report-date-from').val(picker.startDate.format('YYYY-MM-DD HH:mm:ss'));
                $('#report-date-to').val(picker.endDate.format('YYYY-MM-DD HH:mm:ss'));
                $(this).val(picker.startDate.format('DD/MM/YYYY HH:mm') + ' - ' + picker.endDate.format('DD/MM/YYYY HH:mm'));
                reloadReports();
            });

            $('#report-date-range').on('cancel.daterangepicker', function () {
                $('#report-date-from').val('');
                $('#report-date-to').val('');
                $(this).val('');
                reloadReports();
            });

            $('#report-shortcode-id, #report-service-key, #report-keyword-id').on('change', reloadReports);
            $('#report-account-search, #report-code-search, #report-customer-search').on('keyup change', queueReload);

            $('#reset-report-filters').on('click', function () {
                $('#report-shortcode-id').val('');
                $('#report-service-key').val('');
                $('#report-keyword-id').val('');
                $('#report-account-search').val('');
                $('#report-code-search').val('');
                $('#report-customer-search').val('');
                $('#report-date-from').val(defaultStart.format('YYYY-MM-DD HH:mm:ss'));
                $('#report-date-to').val(defaultEnd.format('YYYY-MM-DD HH:mm:ss'));
                $('#report-date-range').val(defaultStart.format('DD/MM/YYYY HH:mm') + ' - ' + defaultEnd.format('DD/MM/YYYY HH:mm'));
                reloadReports();
            });
        });
    </script>
@endsection
