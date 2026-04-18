@extends('admin.includes.body')
@section('title', 'Keywords')
@section('subtitle','Keywords')
@section('content')
    <?php $matchStyles = ['contains' => 'Contains text', 'starts_with' => 'Starts with', 'ends_with' => 'Ends with', 'exact' => 'Exact match', 'regex' => 'Regex']; ?>

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap">
            <div>
                <h5 class="mb-1">Keywords</h5>
            </div>
            <div class="mt-2 mt-md-0">
                <button type="button" class="btn btn-primary" id="add-keyword-button">
                    Add Keyword
                </button>
            </div>
        </div>
        <div class="card-body">
            <div class="alert alert-dark border mb-3 p-3">
                <strong>Simple example:</strong> create keyword Jamoko, choose a default service, then match words like jamoko, jamok, jamo, jomoko. Any assigned user can view matching transactions, using the min/max limits you set for that user.
            </div>
            <div class="table-responsive">
                <table id="keywords-table" class="table table-striped table-hover custom-list-table">
                    <thead>
                    <tr>
                        <th>Keyword</th>
                        <th>Default Service</th>
                        <th>Match</th>
                        <th>Assigned Users</th>
                        <th>Action</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($keywords as $keyword): ?>
                        <?php
                            $keywordUserLimits = [];
                            foreach ($keyword->users as $assignedKeywordUser) {
                                $keywordUserLimits[(int) $assignedKeywordUser->id] = [
                                    'min' => $assignedKeywordUser->pivot->transaction_min_amount,
                                    'max' => $assignedKeywordUser->pivot->transaction_max_amount,
                                    'bypass_history' => isset($assignedKeywordUser->pivot->bypass_amount_limit_history) ? (int) $assignedKeywordUser->pivot->bypass_amount_limit_history : 0,
                                ];
                            }

                            $keywordMatchTypes = array_values(array_filter(array_map('trim', explode(',', (string) $keyword->match_type))));
                            $keywordPayload = base64_encode(json_encode([
                                'id' => $keyword->id,
                                'service_id' => $keyword->service_id,
                                'keyword_name' => $keyword->keyword_name,
                                'match_types' => $keywordMatchTypes,
                                'match_pattern' => $keyword->match_pattern,
                                'user_ids' => array_keys($keywordUserLimits),
                                'user_limits' => $keywordUserLimits,
                            ]));
                            $keywordMatchLabels = collect(explode(',', (string) $keyword->match_type))
                                ->map(function ($type) use ($matchStyles) {
                                    return $matchStyles[trim($type)] ?? trim($type);
                                })
                                ->filter()
                                ->implode(', ');
                        ?>
                        <tr>
                            <td>
                                <strong>{{ $keyword->keyword_name }}</strong>
                                <small class="text-muted d-block">Status: {{ $keyword->status ? 'Active' : 'Inactive' }}</small>
                            </td>
                            <td>
                                {{ optional($keyword->service)->service_name ?: 'Not linked' }}
                                <small class="text-muted d-block">Shortcode: {{ optional(optional($keyword->service)->shortcode)->shortcode ?: 'N/A' }}</small>
                            </td>
                            <td>
                                <span class="d-block">{{ $keywordMatchLabels }}</span>
                                <small class="text-muted d-block">{{ $keyword->match_pattern }}</small>
                            </td>
                            <td>
                                <?php if ($keyword->users->count()): ?>
                                    <?php foreach ($keyword->users as $user): ?>
                                    <span class="badge badge-dark border mr-1 mb-1">
                                        {{ $user->name }}
                                        <?php if ($user->pivot->transaction_min_amount !== null || $user->pivot->transaction_max_amount !== null): ?>
                                            ({{ $user->pivot->transaction_min_amount ?? '0' }} - {{ $user->pivot->transaction_max_amount ?? 'Any' }})
                                        <?php endif; ?>
                                    </span>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <span class="text-muted">No users assigned</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <button type="button" class="btn btn-default btn-sm edit-keyword" data-keyword="{{ $keywordPayload }}">Edit</button>
                                <button type="button" class="btn btn-danger btn-sm delete-keyword" data-id="{{ $keyword->id }}" data-name="{{ $keyword->keyword_name }}">Delete</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="modal" tabindex="-1" role="dialog" id="keywordModal">
        <div class="modal-dialog modal-xl" role="document" style="max-width: 1280px; width: 96%;">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="keyword-modal-title">Add Keyword</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form action="{{ route('keywords.save') }}" method="post" id="keyword-form">
                        @csrf
                        <input type="hidden" name="id" id="keyword-id">

                        <div class="form-row">
                            <div class="col-md-4 mb-3">
                                <label class="control-label" for="keyword-service-id">Default Service</label>
                                <select class="custom-select" name="service_id" id="keyword-service-id">
                                    <option value="">Choose default service</option>
                                    <?php foreach ($keywordDefaultServices as $service): ?>
                                        <option value="{{ $service->id }}">{{ $service->service_name }} - {{ optional($service->shortcode)->shortcode ?: 'No shortcode' }}</option>
                                    <?php endforeach; ?>
                                </select>
                                <small class="text-muted">Keywords are for default services where account text identifies the owner.</small>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="control-label" for="keyword-name">Keyword Name</label>
                                <input type="text" class="form-control" name="keyword_name" id="keyword-name" placeholder="Example: Jamoko">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="control-label d-block">Match Style</label>
                                <?php foreach ($matchStyles as $matchValue => $matchLabel): ?>
                                    <label class="custom-control custom-checkbox custom-control-inline mb-1">
                                        <input type="checkbox" class="custom-control-input keyword-match-style" name="match_types[]" value="{{ $matchValue }}" <?php if ($matchValue === 'contains'): ?> checked <?php endif; ?>>
                                        <span class="custom-control-label">{{ $matchLabel }}</span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                            <div class="col-12 mb-3">
                                <label class="control-label" for="keyword-match-pattern">Words to Match</label>
                                <textarea class="form-control" name="match_pattern" id="keyword-match-pattern" rows="3" placeholder="jamoko, jamok, jamo, jomoko"></textarea>
                                <small class="text-muted">For normal matching, separate words with commas or new lines. For Regex, enter the regex pattern here.</small>
                            </div>
                        </div>

                        <div class="line-divider"></div>
                        <h6 class="mb-1">Assign Users / Accounts</h6>
                        <small class="text-muted d-block mb-3">Tick each user who should access this keyword. Min/max limits apply per user and follow the same history behavior used by service limits.</small>

                        <div style="max-height: 420px; overflow-y: auto;">
                            <?php foreach ($keywordUsers as $user): ?>
                                <div class="border rounded px-3 py-2 mb-2 keyword-user-row" data-user-id="{{ $user->id }}">
                                    <label class="d-flex align-items-start mb-2">
                                        <span class="custom-control custom-checkbox mt-1">
                                            <input type="checkbox" class="custom-control-input keyword-user-checkbox" name="user_ids[]" id="keyword-user-{{ $user->id }}" value="{{ $user->id }}">
                                            <span class="custom-control-label" for="keyword-user-{{ $user->id }}"></span>
                                        </span>
                                        <span class="ml-3">
                                            <span class="font-weight-bold d-block">{{ $user->name }}</span>
                                            <small class="text-muted d-block">{{ $user->email }} | {{ $user->status ? 'Active' : 'Inactive' }}</small>
                                        </span>
                                    </label>
                                    <div class="form-row">
                                        <div class="col-md-4 mb-2">
                                            <label class="input-label mb-1" for="keyword-user-{{ $user->id }}-min">Min Amount</label>
                                            <input type="number" class="form-control form-control-sm keyword-user-limit" name="user_limits[{{ $user->id }}][min]" id="keyword-user-{{ $user->id }}-min" min="0" step="0.01" placeholder="No minimum">
                                        </div>
                                        <div class="col-md-4 mb-2">
                                            <label class="input-label mb-1" for="keyword-user-{{ $user->id }}-max">Max Amount</label>
                                            <input type="number" class="form-control form-control-sm keyword-user-limit" name="user_limits[{{ $user->id }}][max]" id="keyword-user-{{ $user->id }}-max" min="0" step="0.01" placeholder="No maximum">
                                        </div>
                                        <div class="col-md-4 mb-2 d-flex align-items-end">
                                            <input type="hidden" name="user_limits[{{ $user->id }}][bypass_history]" value="0">
                                            <label class="custom-control custom-checkbox mb-1">
                                                <input type="checkbox" class="custom-control-input keyword-user-limit keyword-user-bypass" name="user_limits[{{ $user->id }}][bypass_history]" value="1">
                                                <span class="custom-control-label">Apply current min/max to past transactions too</span>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <div class="form-group form-row mt-3 mb-0">
                            <div class="ml-auto">
                                <button type="submit" class="btn btn-primary">Save Keyword</button>
                                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
@section('script')
    <script>
        $(function(){
            $('#keywords-table').DataTable({
                pageLength: 10,
                order: [[0, 'asc']]
            });

            function decodePayload(payload) {
                try {
                    return JSON.parse(atob(payload));
                } catch (error) {
                    return {};
                }
            }

            function resetKeywordForm() {
                $('#keyword-form')[0].reset();
                $('#keyword-id').val('');
                $('#keyword-modal-title').text('Add Keyword');
                $('.keyword-match-style').prop('checked', false);
                $('.keyword-match-style[value="contains"]').prop('checked', true);
                $('.keyword-user-checkbox').prop('checked', false);
                $('.keyword-user-row input[type="number"].keyword-user-limit').val('');
                $('.keyword-user-bypass').prop('checked', false);
                syncKeywordUserFields();
            }

            function syncKeywordUserFields() {
                $('.keyword-user-row').each(function(){
                    var row = $(this);
                    var enabled = row.find('.keyword-user-checkbox').is(':checked');
                    row.find('.keyword-user-limit').prop('disabled', !enabled);
                });
            }

            $('#add-keyword-button').on('click', function(){
                resetKeywordForm();
                $('#keywordModal').modal('show');
            });

            $(document).on('click', '.edit-keyword', function(){
                var keyword = decodePayload($(this).attr('data-keyword'));
                resetKeywordForm();

                $('#keyword-modal-title').text('Edit Keyword');
                $('#keyword-id').val(keyword.id || '');
                $('#keyword-service-id').val(keyword.service_id || '');
                $('#keyword-name').val(keyword.keyword_name || '');
                $('#keyword-match-pattern').val(keyword.match_pattern || '');
                $('.keyword-match-style').prop('checked', false);

                $.each(keyword.match_types || ['contains'], function(index, matchType){
                    $('.keyword-match-style[value="' + matchType + '"]').prop('checked', true);
                });

                $.each(keyword.user_ids || [], function(index, userId){
                    $('.keyword-user-checkbox[value="' + userId + '"]').prop('checked', true);
                });

                $.each(keyword.user_limits || {}, function(userId, limits){
                    var row = $('.keyword-user-row[data-user-id="' + userId + '"]');
                    row.find('input[name="user_limits[' + userId + '][min]"]').val(limits && limits.min !== null ? limits.min : '');
                    row.find('input[name="user_limits[' + userId + '][max]"]').val(limits && limits.max !== null ? limits.max : '');
                    row.find('input[name="user_limits[' + userId + '][bypass_history]"][type="checkbox"]').prop('checked', parseInt(limits && limits.bypass_history ? limits.bypass_history : 0, 10) === 1);
                });

                syncKeywordUserFields();
                $('#keywordModal').modal('show');
            });

            $(document).on('change', '.keyword-user-checkbox', syncKeywordUserFields);

            $('#keyword-form').on('submit', function(e){
                e.preventDefault();

                $.ajax({
                    type: 'POST',
                    url: $(this).attr('action'),
                    headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                    data: $(this).serialize(),
                    success: function(message){
                        if (message.status) {
                            $('#keywordModal').modal('hide');
                            toastr.success(message.msg, message.header, {
                                timeOut: 1500,
                                closeButton: true,
                                progressBar: true,
                                newestOnTop: true
                            });
                            setTimeout(function(){
                                window.location.reload();
                            }, 650);
                            return;
                        }

                        toastr.error(message.msg, message.header || 'Keywords', {
                            timeOut: 2500,
                            closeButton: true,
                            progressBar: true,
                            newestOnTop: true
                        });
                    },
                    error: function(response) {
                        if (response.responseJSON && response.responseJSON.errors) {
                            $.each(response.responseJSON.errors, function(key, val){
                                toastr.error(val[0], 'Keywords', {
                                    timeOut: 2500,
                                    closeButton: true,
                                    progressBar: true,
                                    newestOnTop: true
                                });
                            });
                            return;
                        }

                        toastr.error((response.responseJSON && response.responseJSON.message) || 'Unable to save keyword.', 'Keywords', {
                            timeOut: 2500,
                            closeButton: true,
                            progressBar: true,
                            newestOnTop: true
                        });
                    }
                });
            });

            $(document).on('click', '.delete-keyword', function(){
                var keywordId = $(this).data('id');
                var keywordName = $(this).data('name');

                if (!confirm('Delete keyword "' + keywordName + '"? This removes it from all assigned users.')) {
                    return;
                }

                $.ajax({
                    type: 'POST',
                    url: "{{ route('keywords.delete') }}",
                    headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                    data: { id: keywordId },
                    success: function(message){
                        if (message.status) {
                            toastr.success(message.msg, message.header, {
                                timeOut: 1500,
                                closeButton: true,
                                progressBar: true,
                                newestOnTop: true
                            });
                            setTimeout(function(){
                                window.location.reload();
                            }, 650);
                            return;
                        }

                        toastr.error(message.msg, message.header || 'Keywords', {
                            timeOut: 2500,
                            closeButton: true,
                            progressBar: true,
                            newestOnTop: true
                        });
                    },
                    error: function(response) {
                        toastr.error((response.responseJSON && response.responseJSON.message) || 'Unable to delete keyword.', 'Keywords', {
                            timeOut: 2500,
                            closeButton: true,
                            progressBar: true,
                            newestOnTop: true
                        });
                    }
                });
            });

            syncKeywordUserFields();
        });
    </script>
@endsection
