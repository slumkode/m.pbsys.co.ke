@extends('admin.includes.body')
@section('title', 'Services')
@section('subtitle','Services')
@section('content')

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap">
            <div>
                <h5 class="mb-1">Services</h5>
            </div>
            <div class="mt-2 mt-md-0">
                @if($authUser->hasPermission('services.create'))
                    <button class="btn btn-default" data-toggle="modal" data-target="#addModal">
                        <i class="align-middle" data-feather="plus"></i> Add Service
                    </button>
                @endif
            </div>
        </div>
        <div class="card-body">
            <table id="datatables-buttons" class="table table-striped table-hover custom-list-table">
                <thead>
                <tr>
                    <th>Service Name</th>
                    <th>Assigned To</th>
                    <th>Shortcode</th>
                    <th>Shortcode Mode</th>
                    <th>Prefix</th>
                    <th>Callback Url</th>
                    <th>Verification Url</th>
                    <th>Action</th>
                </tr>
                </thead>
                <tbody>
                    @foreach($services as $value)
                        @php($canEditService = $authUser->hasPermission('services.update'))
                        @php($canDeleteService = $authUser->hasPermission('services.delete'))
                        @php($servicePayload = base64_encode(json_encode([
                            'id' => $value->id,
                            'shortcode_id' => $value->shortcode_id,
                            'user_id' => $value->user_id,
                            'prefix' => $value->prefix,
                            'service_name' => $value->service_name,
                            'service_description' => $value->service_description,
                            'verification_url' => $value->verification_url,
                            'callback_url' => $value->callback_url,
                        ])))
                    <tr>
                        <td>
                            @if($authUser->hasPermission('transaction.view') && $value->shortcode)
                                <a href="{{ url('/grouptrans/'.$value->shortcode->shortcode.'/'.$value->service_name) }}">{{ $value->service_name }}</a>
                            @else
                                {{ $value->service_name }}
                            @endif
                        </td>
                        <td>{{ optional($value->user)->name ?: optional(optional($value->shortcode)->user)->name ?: 'Default owner' }}</td>
                        <td>{{ optional($value->shortcode)->shortcode ?: 'Unknown shortcode' }}</td>
                        <td>
                            <span class="badge {{ (optional($value->shortcode)->sharing_mode ?? 'dedicated') === 'shared' ? 'badge-primary' : 'badge-default' }}">
                                {{ ucfirst(optional($value->shortcode)->sharing_mode ?? 'dedicated') }}
                            </span>
                        </td>
                        <td>{{ $value->prefix ?: 'Not set' }}</td>
                        <td>{{ $value->callback_url ?: 'Not set' }}</td>
                        <td>{{ $value->verification_url ?: 'Not set' }}</td>
                        <td>
                            @if($canEditService || $canDeleteService)
                                @if($canEditService)
                                <a href="javascript:;" class="edit-service" data-service="{{ $servicePayload }}">
                                    <i class="align-middle" data-feather="edit-2"></i>
                                </a>
                                @endif
                                @if($canDeleteService)
                                    <a href="javascript:;" class="text-danger ml-3 delete-service" data-id="{{ $value->id }}" data-name="{{ $value->service_name }}" title="Delete service" aria-label="Delete service {{ $value->service_name }}">
                                        <i class="align-middle" data-feather="trash"></i>
                                    </a>
                                @endif
                            @else
                                <span class="text-muted">View only</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                <tr>
                    <th>Service Name</th>
                    <th>Assigned To</th>
                    <th>Shortcode</th>
                    <th>Shortcode Mode</th>
                    <th>Prefix</th>
                    <th>Callback Url</th>
                    <th>Verification Url</th>
                    <th>Action</th>
                </tr>
                </tfoot>
            </table>
            <div class="d-flex justify-content-end w-100">
                {{ $services->links() }}
            </div>

        </div>
    </div>
    @if($authUser->hasPermission('services.create'))
        <div class="modal" tabindex="-1" role="dialog" id="addModal">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content shadow">
                    <div class="modal-header">
                        <h5 class="modal-title">Add Service</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                        <form action="{{ url('addservice') }}" method="post" class="form form-horizontal create_form">
                    </div>
                    <div class="modal-body">
                        @csrf
                        <div class="form-group form-row">
                            <div class="col">
                                <label for="add-shortcode" class="control-label">Shortcode</label>
                                <select name="shortcode" id="add-shortcode" class="custom-select service-shortcode-selector">
                                    @foreach($shortcodes as $value)
                                    <option
                                        value="{{ $value->id }}"
                                        data-shortcode="{{ $value->shortcode }}"
                                        data-sharing-mode="{{ $value->sharing_mode ?? 'dedicated' }}"
                                        data-has-default-service="{{ in_array((int) $value->id, $defaultServiceShortcodeIds ?? [], true) ? '1' : '0' }}"
                                        data-owner-id="{{ $value->user_id }}"
                                        data-owner-name="{{ optional($value->user)->name }}"
                                    >
                                        {{ $value->shortcode }} - {{ ucfirst($value->sharing_mode ?? 'dedicated') }}
                                    </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col">
                                <label for="add-assigned-user-id" class="control-label">Service Owner</label>
                                <select name="assigned_user_id" id="add-assigned-user-id" class="custom-select service-owner-selector">
                                    <option value="">Default shortcode owner</option>
                                    @foreach($serviceAssignees as $assignee)
                                        <option value="{{ $assignee->id }}">{{ $assignee->name }}{{ $assignee->email ? ' - '.$assignee->email : '' }}</option>
                                    @endforeach
                                </select>
                                <small class="text-muted service-owner-help">Dedicated shortcodes keep the service with the shortcode owner.</small>
                            </div>
                        </div>
                        <div class="form-group form-row">
                            <div class="col">
                                <label for="add-code-prefix" class="control-label">Code Prefix</label>
                                <input type="text" name="prefix" id="add-code-prefix" class="form-control service-prefix-input" placeholder="Leave blank for default service">
                                <small class="text-muted d-block mt-1 service-prefix-help">Example: if the prefix is <strong>tml</strong> and the customer enters <strong>tml001</strong> as the MPesa account number, that transaction is routed to this service on the selected shortcode.</small>
                            </div>
                            <div class="col">
                                <label for="add-service-name" class="control-label">Service Name</label>
                                <input type="text" name="service_name" id="add-service-name" class="form-control service-name-input">
                                <small class="text-muted d-block mt-1 service-name-help">Use a clear internal name. For a fallback/default service, the form will suggest something like <strong>default-225558</strong> and the prefix should stay blank.</small>
                            </div>
                        </div>
                         <div class="form-group">
                             <label for="add-description" class="control-label">Service Description <span class="text-muted">(Optional)</span></label>
                             <input type="text" name="description" id="add-description" class="summernote">
                         </div>
                        <div class="form-group">
                            <label for="add-verification-callback" class="control-label">Verification Callback</label>
                            <input type="text" name="verification_callback" id="add-verification-callback" class="form-control">
                        </div>
                        <div class="form-group">
                            <label for="add-response-callback" class="control-label">Response Callback <span class="text-muted">(Optional)</span></label>
                            <input type="text" name="response_callback" id="add-response-callback" class="form-control">
                            <small class="text-muted">Leave blank if this service should not forward payment notifications to another system.</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary">Save changes</button>
                        </form>
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    @if($authUser->hasPermission('services.update'))
        <div class="modal" tabindex="-1" role="dialog" id="editModal">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Edit Service</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                        <form action="{{ url('editservice') }}" method="post" class="form form-horizontal create_form">
                    </div>
                    <div class="modal-body">
                        @csrf
                        <input type="hidden" name="id" id="edit-id">
                        <div class="form-group form-row">
                            <div class="col">
                                <label for="edit-shortcode" class="control-label">Shortcode</label>
                                <select name="shortcode" id="edit-shortcode" class="custom-select service-shortcode-selector">
                                    @foreach($shortcodes as $value)
                                        <option
                                            value="{{ $value->id }}"
                                            data-shortcode="{{ $value->shortcode }}"
                                            data-sharing-mode="{{ $value->sharing_mode ?? 'dedicated' }}"
                                            data-has-default-service="{{ in_array((int) $value->id, $defaultServiceShortcodeIds ?? [], true) ? '1' : '0' }}"
                                            data-owner-id="{{ $value->user_id }}"
                                            data-owner-name="{{ optional($value->user)->name }}"
                                        >
                                            {{ $value->shortcode }} - {{ ucfirst($value->sharing_mode ?? 'dedicated') }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col">
                                <label for="edit-assigned-user-id" class="control-label">Service Owner</label>
                                <select name="assigned_user_id" id="edit-assigned-user-id" class="custom-select service-owner-selector">
                                    <option value="">Default shortcode owner</option>
                                    @foreach($serviceAssignees as $assignee)
                                        <option value="{{ $assignee->id }}">{{ $assignee->name }}{{ $assignee->email ? ' - '.$assignee->email : '' }}</option>
                                    @endforeach
                                </select>
                                <small class="text-muted service-owner-help">Dedicated shortcodes keep the service with the shortcode owner.</small>
                            </div>
                        </div>
                        <div class="form-group form-row">
                            <div class="col">
                                <label for="edit-code-prefix" class="control-label">Code Prefix</label>
                                <input type="text" name="prefix" id="edit-code-prefix" class="form-control service-prefix-input" placeholder="Leave blank for default service">
                                <small class="text-muted d-block mt-1 service-prefix-help">Use a short alphabetic prefix to route account numbers like <strong>tml001</strong> to this service. Leave it blank if this should be the default service for the shortcode.</small>
                            </div>
                            <div class="col">
                                <label for="edit-service-name" class="control-label">Service Name</label>
                                <input type="text" name="service_name" id="edit-service-name" class="form-control service-name-input">
                                <small class="text-muted d-block mt-1 service-name-help">Example: <strong>default-225558</strong> is a simple way to label the fallback/default service for shortcode 225558.</small>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="edit-description" class="control-label">Service Description <span class="text-muted">(Optional)</span></label>
                            <input type="text" name="description" id="edit-description" class="summernote">
                        </div>
                        <div class="form-group">
                            <label for="edit-verification-callback" class="control-label">Verification Callback</label>
                            <input type="text" name="verification_callback" id="edit-verification-callback" class="form-control">
                        </div>
                        <div class="form-group">
                            <label for="edit-response-callback" class="control-label">Response Callback <span class="text-muted">(Optional)</span></label>
                            <input type="text" name="response_callback" id="edit-response-callback" class="form-control">
                            <small class="text-muted">Leave blank if this service should not forward payment notifications to another system.</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary">Save changes</button>
                        </form>
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>
    @endif
@endsection

@section('script')
    <script>
        $(document).ready(function(){
            $(document).on('click', '.delete-service', function(e){
                e.preventDefault();

                var serviceId = $(this).data('id');
                var serviceName = $(this).data('name');

                if (!confirm('Delete service ' + serviceName + '? The record will be soft-deleted and can be restored from Audit Logs.')) {
                    return;
                }

                $.ajax({
                    type: 'POST',
                    url: "{{ route('services.destroy') }}",
                    headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                    data: { id: serviceId },
                    success: function(message){
                        if (message.status) {
                            toastr.success(message.msg, message.header, {
                                timeOut: 1500,
                                closeButton: true,
                                progressBar: true,
                                newestOnTop: true,
                                onHidden: function () {
                                    window.location.reload();
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
                    error: function(response) {
                        if (response.responseJSON && response.responseJSON.errors) {
                            $.each(response.responseJSON.errors, function(key, val){
                                toastr.error(val[0], 'Services', {
                                    timeOut: 2000,
                                    closeButton: true,
                                    progressBar: true,
                                    newestOnTop: true
                                });
                            });
                            return;
                        }

                        toastr.error(
                            response.responseJSON && response.responseJSON.message ? response.responseJSON.message : 'Delete failed. Please try again.',
                            'Services',
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
