@extends('admin.includes.body')
@section('title', 'Audit Logs')
@section('subtitle', 'Audit Logs')
@section('content')
    <style>
        #audit-logs-table_wrapper .dataTables_filter {
            display: none;
        }

        .audit-log-json {
            max-height: 320px;
            overflow: auto;
            margin: 0;
            padding: 16px 18px;
            white-space: pre-wrap;
            word-break: break-word;
            background: #f7f9fc;
            border: 1px solid #dbe4ef;
            border-radius: 0 0 6px 6px;
            color: #223247;
            font-size: 12px;
            line-height: 1.65;
            font-family: Consolas, "Courier New", monospace;
        }

        .audit-log-details-wrapper {
            background: #fbfcfe;
        }

        .audit-log-json-card {
            height: 100%;
        }

        .audit-log-json-header {
            padding: 10px 14px;
            border: 1px solid #dbe4ef;
            border-bottom: 0;
            border-radius: 6px 6px 0 0;
            background: #eef3f9;
            color: #4f6278;
            font-size: 12px;
            font-weight: 600;
            letter-spacing: 0.04em;
            text-transform: uppercase;
        }

        .audit-log-empty-state {
            padding: 16px 18px;
            border: 1px solid #dbe4ef;
            border-radius: 0 0 6px 6px;
            background: #f7f9fc;
            color: #6c7a89;
        }

        .audit-log-change-state {
            display: inline-flex;
            align-items: center;
            padding: 6px 10px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 600;
        }

        .audit-log-change-state--changed {
            background: #fff6e5;
            color: #8a5a00;
        }

        .audit-log-change-state--unchanged {
            background: #eef3f9;
            color: #5a6b7c;
        }

        .audit-log-change-hint {
            color: #6d7b8a;
            font-size: 12px;
        }

        .audit-log-diff {
            max-height: 360px;
            overflow: auto;
            border: 1px solid #dbe4ef;
            border-radius: 0 0 6px 6px;
            background: #f7f9fc;
        }

        .audit-log-diff-line {
            display: flex;
            align-items: stretch;
            min-height: 24px;
            border-bottom: 1px solid rgba(219, 228, 239, 0.6);
            font-family: Consolas, "Courier New", monospace;
            font-size: 12px;
            line-height: 1.65;
        }

        .audit-log-diff-line:last-child {
            border-bottom: 0;
        }

        .audit-log-diff-line--context {
            background: #f7f9fc;
            color: #223247;
        }

        .audit-log-diff-line--removed {
            background: #ffeef0;
            color: #86181d;
        }

        .audit-log-diff-line--added {
            background: #e6ffed;
            color: #1b6b2c;
        }

        .audit-log-diff-marker {
            flex: 0 0 28px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-right: 1px solid rgba(219, 228, 239, 0.9);
            color: #7a8794;
            user-select: none;
        }

        .audit-log-diff-line--removed .audit-log-diff-marker {
            color: #b31d28;
        }

        .audit-log-diff-line--added .audit-log-diff-marker {
            color: #22863a;
        }

        .audit-log-diff-code {
            flex: 1 1 auto;
            margin: 0;
            padding: 3px 12px;
            white-space: pre-wrap;
            word-break: break-word;
            background: transparent;
            color: inherit;
        }

        table.dataTable tbody td.audit-log-details-cell {
            white-space: nowrap;
        }

        .audit-log-filter-input[list]::-webkit-calendar-picker-indicator {
            opacity: 0;
        }
    </style>
    <div class="card">
        <div class="card-header">
            <div class="form-row">
                <div class="col-md-3 mb-3">
                    <label for="audit-user-search" class="control-label">Actioned By</label>
                    <input type="text" id="audit-user-search" class="form-control audit-log-filter-input" list="audit-user-options" placeholder="Select or search a user" autocomplete="off">
                    <input type="hidden" id="audit-user-id">
                    <datalist id="audit-user-options">
                        @foreach($auditUsers as $auditUser)
                            <option value="{{ $auditUser['label'] }}" data-user-id="{{ $auditUser['id'] }}"></option>
                        @endforeach
                    </datalist>
                </div>
                <div class="col-md-3 mb-3">
                    <label for="audit-object-search" class="control-label">Object</label>
                    <input type="text" id="audit-object-search" class="form-control" placeholder="Search by type, label, or ID">
                </div>
                <div class="col-md-3 mb-3">
                    <label for="audit-page-search" class="control-label">Page</label>
                    <input type="text" id="audit-page-search" class="form-control audit-log-filter-input" list="audit-page-options" placeholder="Select or search a page" autocomplete="off">
                    <datalist id="audit-page-options">
                        @foreach($auditPages as $auditPage)
                            <option value="{{ $auditPage }}"></option>
                        @endforeach
                    </datalist>
                </div>
                <div class="col-md-3 mb-3">
                    <label for="audit-action-search" class="control-label">Action</label>
                    <select id="audit-action-search" class="custom-select">
                        <option value="">All actions</option>
                        @foreach($actions as $action)
                            <option value="{{ $action }}">{{ ucwords(str_replace('_', ' ', $action)) }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="form-row">
                <div class="col-md-6 mb-3">
                    <label for="audit-date-range" class="control-label">When</label>
                    <input type="text" id="audit-date-range" class="form-control" placeholder="Select date and time range" autocomplete="off">
                    <input type="hidden" id="audit-date-from">
                    <input type="hidden" id="audit-date-to">
                </div>
                <div class="col-md-6 mb-3 d-flex align-items-end justify-content-md-end">
                    <button type="button" class="btn btn-default" id="reset-audit-filters">
                        <i class="align-middle" data-feather="x-circle"></i> Reset Search
                    </button>
                </div>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="audit-logs-table" class="table table-striped table-hover custom-list-table">
                    <thead>
                    <tr>
                        <th>When</th>
                        <th>Actioned By</th>
                        <th>Action</th>
                        <th>Object</th>
                        <th>Page</th>
                        <th>Details</th>
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
            var detailUrlTemplate = "{{ route('audit-logs.details', ['auditLog' => '__AUDIT_LOG_ID__']) }}";
            var reloadTimer = null;

            function resolveDatalistOption(listSelector, value) {
                var normalizedValue = $.trim(value || '');

                if (normalizedValue === '') {
                    return null;
                }

                var matchedOption = null;

                $(listSelector).find('option').each(function () {
                    if ($(this).val() === normalizedValue) {
                        matchedOption = $(this);
                        return false;
                    }
                });

                return matchedOption;
            }

            function syncAuditUserSelection() {
                var matchedOption = resolveDatalistOption('#audit-user-options', $('#audit-user-search').val());
                $('#audit-user-id').val(matchedOption ? matchedOption.data('user-id') : '');
            }

            var auditLogTable = $('#audit-logs-table').DataTable({
                processing: true,
                serverSide: true,
                deferRender: true,
                responsive: true,
                autoWidth: false,
                searchDelay: 350,
                pageLength: 25,
                lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
                dom: 'lrtip',
                ajax: {
                    url: "{{ route('audit-logs.datatable') }}",
                    dataType: 'json',
                    type: 'POST',
                    headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                    data: function (d) {
                        syncAuditUserSelection();
                        d.user_id = $('#audit-user-id').val();
                        d.user_name = $('#audit-user-search').val();
                        d.object = $('#audit-object-search').val();
                        d.page_name = $('#audit-page-search').val();
                        d.action = $('#audit-action-search').val();
                        d.date_from = $('#audit-date-from').val();
                        d.date_to = $('#audit-date-to').val();
                    },
                    error: function () {
                        toastr.error('Unable to load audit logs right now.', 'Audit Logs', {
                            timeOut: 2000,
                            closeButton: true,
                            progressBar: true,
                            newestOnTop: true
                        });
                    }
                },
                columns: [
                    { data: 'when', name: 'when' },
                    { data: 'user', name: 'user' },
                    { data: 'action', name: 'action' },
                    { data: 'object', name: 'object', orderable: false, searchable: false },
                    { data: 'page', name: 'page' },
                    { data: 'details', name: 'details', orderable: false, searchable: false, className: 'audit-log-details-cell' }
                ],
                order: [[0, 'desc']]
            });

            function reloadAuditLogs() {
                auditLogTable.ajax.reload(null, true);
            }

            function queueReload() {
                clearTimeout(reloadTimer);
                reloadTimer = setTimeout(reloadAuditLogs, 350);
            }

            function resetAuditFilters() {
                var datePicker = $('#audit-date-range').data('daterangepicker');
                $('#audit-user-search').val('');
                $('#audit-user-id').val('');
                $('#audit-object-search').val('');
                $('#audit-page-search').val('');
                $('#audit-action-search').val('');
                $('#audit-date-from').val('');
                $('#audit-date-to').val('');
                $('#audit-date-range').val('');

                if (datePicker) {
                    datePicker.setStartDate(moment().startOf('day'));
                    datePicker.setEndDate(moment().endOf('day'));
                }

                reloadAuditLogs();
            }

            $('#audit-date-range').daterangepicker({
                autoUpdateInput: false,
                timePicker: true,
                timePicker24Hour: true,
                timePickerIncrement: 5,
                opens: 'left',
                locale: {
                    cancelLabel: 'Clear',
                    format: 'DD/MM/YYYY HH:mm'
                }
            });

            $('#audit-date-range').on('apply.daterangepicker', function (ev, picker) {
                $('#audit-date-from').val(picker.startDate.format('YYYY-MM-DD HH:mm:ss'));
                $('#audit-date-to').val(picker.endDate.format('YYYY-MM-DD HH:mm:ss'));
                $(this).val(
                    picker.startDate.format('DD/MM/YYYY HH:mm') +
                    ' - ' +
                    picker.endDate.format('DD/MM/YYYY HH:mm')
                );
                reloadAuditLogs();
            });

            $('#audit-date-range').on('cancel.daterangepicker', function () {
                $('#audit-date-from').val('');
                $('#audit-date-to').val('');
                $(this).val('');
                reloadAuditLogs();
            });

            $('#audit-object-search').on('keyup change', queueReload);
            $('#audit-user-search, #audit-page-search').on('input', queueReload);
            $('#audit-user-search, #audit-page-search, #audit-action-search').on('change', reloadAuditLogs);
            $('#reset-audit-filters').on('click', resetAuditFilters);

            $(document).on('click', '.toggle-audit-details', function () {
                var button = $(this);
                var rowElement = button.closest('tr');
                var row = auditLogTable.row(rowElement);
                var auditLogId = button.data('id');

                if (row.child.isShown()) {
                    row.child.hide();
                    rowElement.removeClass('shown');
                    button.text('View Changes');
                    return;
                }

                if (button.data('detailsHtml')) {
                    row.child(button.data('detailsHtml')).show();
                    rowElement.addClass('shown');
                    button.text('Hide Changes');
                    return;
                }

                button.prop('disabled', true).text('Loading...');

                $.ajax({
                    type: 'GET',
                    url: detailUrlTemplate.replace('__AUDIT_LOG_ID__', auditLogId),
                    success: function (response) {
                        if (!response.status) {
                            toastr.error('Unable to load audit details.', 'Audit Logs', {
                                timeOut: 2000,
                                closeButton: true,
                                progressBar: true,
                                newestOnTop: true
                            });
                            return;
                        }

                        button.data('detailsHtml', response.html);
                        row.child(response.html).show();
                        rowElement.addClass('shown');
                        button.text('Hide Changes');
                    },
                    error: function () {
                        toastr.error('Unable to load audit details.', 'Audit Logs', {
                            timeOut: 2000,
                            closeButton: true,
                            progressBar: true,
                            newestOnTop: true
                        });
                    },
                    complete: function () {
                        button.prop('disabled', false);

                        if (button.text() === 'Loading...') {
                            button.text('View Changes');
                        }
                    }
                });
            });

            $(document).on('click', '.restore-audit-log', function () {
                var auditLogId = $(this).data('id');

                if (!confirm('Restore the deleted data recorded in this audit entry?')) {
                    return;
                }

                $.ajax({
                    type: 'POST',
                    url: "{{ url('audit-logs') }}/" + auditLogId + "/restore",
                    headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                    success: function (message) {
                        if (message.status) {
                            toastr.success(message.msg, message.header, {
                                timeOut: 1500,
                                closeButton: true,
                                progressBar: true,
                                newestOnTop: true,
                                onHidden: function () {
                                    auditLogTable.ajax.reload(null, false);
                                }
                            });
                            return;
                        }

                        toastr.error(message.msg, message.header, {
                            timeOut: 2000,
                            closeButton: true,
                            progressBar: true,
                            newestOnTop: true
                        });
                    },
                    error: function (xhr) {
                        toastr.error(
                            (xhr.responseJSON && (xhr.responseJSON.message || xhr.responseJSON.error)) || 'Unable to restore this audit entry.',
                            'Audit Logs',
                            {
                                timeOut: 2000,
                                closeButton: true,
                                progressBar: true,
                                newestOnTop: true
                            }
                        );
                    }
                });
            });
        });
    </script>
@endsection
