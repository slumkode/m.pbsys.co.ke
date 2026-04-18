@extends('admin.includes.body')
@section('title', 'Shortcode')
@section('subtitle','Shortcode')
@section('content')
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap">
            <div>
                <h5 class="mb-1">Shortcodes</h5>
            </div>
            <div class="mt-2 mt-md-0">
                @if($authUser->hasPermission('shortcode.create'))
                    <button class="btn btn-default" data-toggle="modal" data-target="#addModal">
                        <i class="align-middle" data-feather="plus"></i> Add Shortcode
                    </button>
                @endif
            </div>
        </div>
        <div class="card-body">
            <table id="datatables-buttons" class="table table-striped table-hover custom-list-table">
                <thead>
                    <tr>
                        <th>Shortcode</th>
                        <th>Mode</th>
                        <th>Owner</th>
                        <th>Type</th>
                        <th>Group</th>
                        <th>Notifying</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($shortcode as $value)
                        @php($shortcodePayload = base64_encode(json_encode([
                            'id' => $value->id,
                            'shortcode' => $value->shortcode,
                            'group' => $value->group,
                            'shortcode_type' => $value->shortcode_type,
                            'sharing_mode' => $value->sharing_mode ?? 'dedicated',
                            'user_id' => $value->user_id,
                            'consumerkey' => $value->consumerkey,
                            'consumersecret' => $value->consumersecret,
                            'passkey' => $value->passkey,
                            'transaction_status_initiator' => $value->transaction_status_initiator ?? null,
                            'transaction_status_identifier' => $value->transaction_status_identifier ?? 'shortcode',
                            'transaction_status_credential_encrypted' => (int) ($value->transaction_status_credential_encrypted ?? 0),
                            'transaction_status_credential_configured' => ! empty($value->transaction_status_credential),
                        ])))
                        @php($notifyPayload = base64_encode(json_encode([
                            'id' => $value->id,
                            'shortcode' => $value->shortcode,
                            'consumerkey' => $value->consumerkey,
                            'consumersecret' => $value->consumersecret,
                        ])))
                    <tr>
                        <td>
                            @if($authUser->hasPermission('transaction.view'))
                                <a href="{{ url('/grouptrans/'.$value->shortcode) }}">{{ $value->shortcode }}</a>
                            @else
                                {{ $value->shortcode }}
                            @endif
                        </td>
                        <td>
                            <span class="badge {{ ($value->sharing_mode ?? 'dedicated') === 'shared' ? 'badge-primary' : 'badge-default' }}">
                                {{ ucfirst($value->sharing_mode ?? 'dedicated') }}
                            </span>
                        </td>
                        <td>{{ optional($value->user)->name ?: 'Unknown owner' }}</td>
                        <td>{{ $value->shortcode_type }}</td>
                        <td>{{ $value->group }}</td>
                        <td>
                            <div class="custom-control custom-switch">
                                <input type="checkbox" class="custom-control-input shortcode-notify" data-shortcode="{{ $notifyPayload }}" @if($value->status == 1) checked @endif @if($value->status == 1 || ! $authUser->hasPermission('shortcode.update')) disabled @endif value="Y" id="active-{{ $value->id }}">
                                <label class="custom-control-label" for="active-{{ $value->id }}"></label>
                            </div>
                        </td>
                        <td>
                            @if($authUser->hasPermission('shortcode.update'))
                                <a href="javascript:;" class="edit-shortcode" data-shortcode="{{ $shortcodePayload }}">
                                    <i class="align-middle" data-feather="edit-2"></i>
                                </a>
                            @else
                                <span class="text-muted">View only</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <th>Shortcode</th>
                        <th>Mode</th>
                        <th>Owner</th>
                        <th>Type</th>
                        <th>Group</th>
                        <th>Notifying</th>
                        <th>Action</th>
                    </tr>
                </tfoot>
            </table>
            <div class="d-flex justify-content-end w-100">
                {{ $shortcode->links() }}
            </div>
        </div>
    </div>

    @if($authUser->hasPermission('shortcode.create'))
        <div class="modal" tabindex="-1" role="dialog" id="addModal">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Add Shortcode</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                        <form action="{{ url('saveshortcode') }}" class="form create_form" method="post">
                    </div>
                    <div class="modal-body">
                        @csrf
                        <div class="form-group">
                            <label for="add-shortcode">Shortcode</label>
                            <input type="text" class="form-control" name="shortcode" id="add-shortcode">
                        </div>
                        <div class="form-group">
                            <label for="add-group">Group</label>
                            <input type="text" class="form-control" name="group" id="add-group">
                        </div>
                        <div class="form-group">
                            <label for="add-sharing-mode">Sharing Mode</label>
                            <select class="custom-select" name="sharing_mode" id="add-sharing-mode">
                                <option value="dedicated">Dedicated</option>
                                <option value="shared">Shared</option>
                            </select>
                            <small class="text-muted">Dedicated keeps the shortcode with its owner only. Shared allows admin to grant extra visibility to other users.</small>
                        </div>
                        @if($authUser->hasPermission('shortcode.assign_owner'))
                            <div class="form-group">
                                <label for="add-owner-user-id">Shortcode Owner</label>
                                <select class="custom-select" name="owner_user_id" id="add-owner-user-id">
                                    @foreach($shortcodeOwners as $owner)
                                        <option value="{{ $owner->id }}" @if($authUser->id === $owner->id) selected @endif>
                                            {{ $owner->name }}{{ $owner->email ? ' - '.$owner->email : '' }}
                                        </option>
                                    @endforeach
                                </select>
                                <small class="text-muted">Users with the <strong>Shortcode - Assign Owner</strong> permission can change ownership here. Dedicated services will follow this owner.</small>
                            </div>
                        @endif
                        <div class="form-group">
                            <label for="add-shortcode_type">Type</label>
                            <input type="text" class="form-control" name="type" id="add-shortcode_type">
                        </div>
                        <div class="form-group">
                            <label for="add-consumerkey">Consumer Key</label>
                            <input type="text" class="form-control" name="consumerkey" id="add-consumerkey">
                        </div>
                        <div class="form-group">
                            <label for="add-consumersecret">Consumer Secret</label>
                            <input type="text" class="form-control" name="consumersecret" id="add-consumersecret">
                        </div>
                        <div class="form-group">
                            <label for="add-passkey">Passkey</label>
                            <input type="text" class="form-control" name="passkey" id="add-passkey">
                        </div>
                        <div class="line-divider"></div>
                        <h6 class="mb-2">Transaction Status Lookup</h6>
                        <small class="text-muted d-block mb-3">Optional. Fill this only when this shortcode should query Safaricom for full payer details before saving C2B notifications. If left blank, the system saves the M-Pesa callback directly.</small>
                        <div class="form-group">
                            <label for="add-transaction-status-initiator">Initiator Name</label>
                            <input type="text" class="form-control" name="transaction_status_initiator" id="add-transaction-status-initiator">
                        </div>
                        <div class="form-group">
                            <label for="add-transaction-status-credential">Security Credential / Initiator Password</label>
                            <textarea class="form-control" name="transaction_status_credential" id="add-transaction-status-credential" rows="3"></textarea>
                            <small class="text-muted">If you paste the already-encrypted Safaricom SecurityCredential, tick the checkbox below. Otherwise paste the initiator password and the system will encrypt it.</small>
                        </div>
                        <div class="form-group">
                            <label class="custom-control custom-checkbox">
                                <input type="checkbox" class="custom-control-input" name="transaction_status_credential_encrypted" value="1">
                                <span class="custom-control-label">This credential is already encrypted</span>
                            </label>
                        </div>
                        <div class="form-group">
                            <label for="add-transaction-status-identifier">Identifier Type</label>
                            <select class="custom-select" name="transaction_status_identifier" id="add-transaction-status-identifier">
                                <option value="shortcode">Shortcode / Paybill</option>
                                <option value="tillnumber">Till Number</option>
                                <option value="msisdn">MSISDN</option>
                            </select>
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

    @if($authUser->hasPermission('shortcode.update'))
        <div class="modal" tabindex="-1" role="dialog" id="editModal">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Edit Shortcode</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                        <form action="{{ url('editshortcode') }}" class="form create_form" method="post">
                    </div>
                    <div class="modal-body" id="editSc">
                        @csrf
                        <input type="hidden" name="id" id="edit-id">
                        <div class="form-group">
                            <label for="edit-shortcode">Shortcode</label>
                            <input type="text" class="form-control" name="shortcode" id="edit-shortcode">
                        </div>
                        <div class="form-group">
                            <label for="edit-group">Group</label>
                            <input type="text" class="form-control" name="group" id="edit-group">
                        </div>
                        <div class="form-group">
                            <label for="edit-sharing-mode">Sharing Mode</label>
                            <select class="custom-select" name="sharing_mode" id="edit-sharing-mode">
                                <option value="dedicated">Dedicated</option>
                                <option value="shared">Shared</option>
                            </select>
                        </div>
                        @if($authUser->hasPermission('shortcode.assign_owner'))
                            <div class="form-group">
                                <label for="edit-owner-user-id">Shortcode Owner</label>
                                <select class="custom-select" name="owner_user_id" id="edit-owner-user-id">
                                    @foreach($shortcodeOwners as $owner)
                                        <option value="{{ $owner->id }}">
                                            {{ $owner->name }}{{ $owner->email ? ' - '.$owner->email : '' }}
                                        </option>
                                    @endforeach
                                </select>
                                <small class="text-muted">Changing the owner updates dedicated services to match the new owner automatically.</small>
                            </div>
                        @endif
                        <div class="form-group">
                            <label for="edit-shortcode_type">Type</label>
                            <input type="text" class="form-control" name="type" id="edit-shortcode_type">
                        </div>
                        <div class="form-group">
                            <label for="edit-consumerkey">Consumer Key</label>
                            <input type="text" class="form-control" name="consumerkey" id="edit-consumerkey">
                        </div>
                        <div class="form-group">
                            <label for="edit-consumersecret">Consumer Secret</label>
                            <input type="text" class="form-control" name="consumersecret" id="edit-consumersecret">
                        </div>
                        <div class="form-group">
                            <label for="edit-passkey">Passkey</label>
                            <input type="text" class="form-control" name="passkey" id="edit-passkey">
                        </div>
                        <div class="line-divider"></div>
                        <h6 class="mb-2">Transaction Status Lookup</h6>
                        <small class="text-muted d-block mb-3">Optional per shortcode. If these fields are incomplete, the callback bypasses transaction status lookup and saves the M-Pesa notification directly.</small>
                        <div class="form-group">
                            <label for="edit-transaction-status-initiator">Initiator Name</label>
                            <input type="text" class="form-control" name="transaction_status_initiator" id="edit-transaction-status-initiator">
                        </div>
                        <div class="form-group">
                            <label for="edit-transaction-status-credential">Security Credential / Initiator Password</label>
                            <textarea class="form-control" name="transaction_status_credential" id="edit-transaction-status-credential" rows="3" placeholder="Leave blank to keep the saved credential"></textarea>
                            <small class="text-muted" id="edit-transaction-status-credential-help">Leave blank to keep the saved credential.</small>
                        </div>
                        <div class="form-group">
                            <label class="custom-control custom-checkbox">
                                <input type="checkbox" class="custom-control-input" name="transaction_status_credential_encrypted" id="edit-transaction-status-credential-encrypted" value="1">
                                <span class="custom-control-label">This credential is already encrypted</span>
                            </label>
                        </div>
                        <div class="form-group">
                            <label class="custom-control custom-checkbox">
                                <input type="checkbox" class="custom-control-input" name="clear_transaction_status_credentials" id="edit-clear-transaction-status-credentials" value="1">
                                <span class="custom-control-label">Clear saved credential</span>
                            </label>
                        </div>
                        <div class="form-group">
                            <label for="edit-transaction-status-identifier">Identifier Type</label>
                            <select class="custom-select" name="transaction_status_identifier" id="edit-transaction-status-identifier">
                                <option value="shortcode">Shortcode / Paybill</option>
                                <option value="tillnumber">Till Number</option>
                                <option value="msisdn">MSISDN</option>
                            </select>
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
