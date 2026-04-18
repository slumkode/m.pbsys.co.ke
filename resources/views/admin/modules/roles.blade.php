@extends('admin.includes.body')
@section('title', 'Roles')
@section('subtitle','Roles')
@section('content')
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap">
            <div>
                <h5 class="mb-1">Roles</h5>
            </div>
            <div class="mt-2 mt-md-0">
                <a href="{{ route('users.index') }}" class="btn btn-default">
                    <i class="align-middle" data-feather="clipboard"></i> Manage Users
                </a>
                <button class="btn btn-primary" data-toggle="modal" data-target="#addRoleModal">
                    <i class="align-middle" data-feather="plus"></i> Add Role
                </button>
            </div>
        </div>
        <div class="card-body">
            <table id="rolestable" class="table table-striped table-hover custom-list-table">
                <thead>
                <tr>
                    <th>Role Name</th>
                    <th>Description</th>
                    <th>Permission Coverage</th>
                    <th>Users</th>
                    <th>Action</th>
                </tr>
                </thead>
                <tfoot>
                <tr>
                    <th>Role Name</th>
                    <th>Description</th>
                    <th>Permission Coverage</th>
                    <th>Users</th>
                    <th>Action</th>
                </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <div class="modal" tabindex="-1" role="dialog" id="addRoleModal">
        <div class="modal-dialog modal-xl" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add Role</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form action="{{ route('roles.store') }}" class="form form-horizontal role-form" data-modal="#addRoleModal" method="post" id="add-role-form">
                        @csrf
                        <div class="form-group">
                            <label for="role-name" class="control-label">Role Name</label>
                            <input type="text" name="name" id="role-name" class="form-control" placeholder="Customer/Client">
                        </div>
                        <div class="form-group">
                            <label for="role-description" class="control-label">Description</label>
                            <textarea name="description" id="role-description" class="form-control" rows="3" placeholder="Explain who should use this role and what they should be able to do."></textarea>
                        </div>
                        <div class="line-divider"></div>
                        @include('admin.modules.partials.permission-fields', [
                            'formPrefix' => 'add-role-permission',
                            'fieldName' => 'role_permissions[]',
                            'selectedPermissions' => [],
                            'permissions' => $permissions,
                        ])
                        <div class="form-group form-row mt-3 mb-0">
                            <div class="ml-auto">
                                <button type="submit" class="btn btn-primary">Create Role</button>
                                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="modal" tabindex="-1" role="dialog" id="editRoleModal">
        <div class="modal-dialog modal-xl" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Role</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form action="{{ route('roles.update') }}" class="form form-horizontal role-form" data-modal="#editRoleModal" method="post" id="edit-role-form">
                        @csrf
                        <input type="hidden" name="id" id="edit-role-id-hidden">
                        <div class="form-group">
                            <label for="edit-role-name" class="control-label">Role Name</label>
                            <input type="text" name="name" id="edit-role-name" class="form-control">
                            <small class="text-muted">System roles keep their original names, but you can still adjust the description and permissions if needed.</small>
                        </div>
                        <div class="form-group">
                            <label for="edit-role-description" class="control-label">Description</label>
                            <textarea name="description" id="edit-role-description" class="form-control" rows="3"></textarea>
                        </div>
                        <div class="line-divider"></div>
                        @include('admin.modules.partials.permission-fields', [
                            'formPrefix' => 'edit-role-permission',
                            'fieldName' => 'role_permissions[]',
                            'selectedPermissions' => [],
                            'permissions' => $permissions,
                        ])
                        <div class="form-group form-row mt-3 mb-0">
                            <div class="ml-auto">
                                <button type="submit" class="btn btn-primary">Save Role</button>
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
        $(document).ready(function(){
            var rolesTable = $('#rolestable').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: "{{ route('roles.datatable') }}",
                    dataType: "json",
                    type: "POST",
                    data: { _token: "{{ csrf_token() }}" }
                },
                columns: [
                    { data: "name" },
                    { data: "description" },
                    { data: "permissions", orderable: false, searchable: false },
                    { data: "users", searchable: false },
                    { data: "action", orderable: false, searchable: false }
                ],
                order: [[0, "asc"]]
            });

            function decodePayload(encoded) {
                var binary = atob(encoded);
                var encodedString = '';

                for (var i = 0; i < binary.length; i++) {
                    encodedString += '%' + ('00' + binary.charCodeAt(i).toString(16)).slice(-2);
                }

                return JSON.parse(decodeURIComponent(encodedString));
            }

            function resetPermissionCheckboxes(formSelector, selectedPermissions) {
                $(formSelector + ' .permission-checkbox').prop('checked', false);

                $.each(selectedPermissions || [], function(index, permission){
                    $(formSelector + ' .permission-checkbox[value="' + permission + '"]').prop('checked', true);
                });
            }

            function handleFormErrors(response, title) {
                if (!response.responseJSON || !response.responseJSON.errors) {
                    toastr.error('Something went wrong. Please try again.', title, {
                        timeOut: 2000,
                        closeButton: true,
                        progressBar: true,
                        newestOnTop: true
                    });
                    return;
                }

                $.each(response.responseJSON.errors, function(key, val){
                    toastr.error(val[0], title, {
                        timeOut: 2000,
                        closeButton: true,
                        progressBar: true,
                        newestOnTop: true
                    });
                });
            }

            $(document).on('submit', '.role-form', function(e){
                e.preventDefault();

                var form = $(this);

                $.ajax({
                    type: 'POST',
                    url: form.attr('action'),
                    headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                    data: form.serialize(),
                    success: function(message){
                        if (message.status) {
                            $(form.data('modal')).modal('hide');
                            form[0].reset();
                            resetPermissionCheckboxes('#add-role-form', []);
                            resetPermissionCheckboxes('#edit-role-form', []);
                            toastr.success(message.msg, message.header, {
                                timeOut: 1500,
                                closeButton: true,
                                progressBar: true,
                                newestOnTop: true
                            });
                            rolesTable.ajax.reload(null, false);
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
                        handleFormErrors(response, 'Roles');
                    }
                });
            });

            $(document).on('click', '.edit-role', function(){
                var role = decodePayload($(this).attr('data-role'));

                $('#edit-role-id-hidden').val(role.id);
                $('#edit-role-name').val(role.name);
                $('#edit-role-description').val(role.description || '');
                $('#edit-role-name').prop('readonly', role.is_system === true);
                resetPermissionCheckboxes('#edit-role-form', role.permissions);
                $('#editRoleModal').modal('show');
            });

            $(document).on('change', '.permission-checkbox', function(){
                var value = $(this).val();
                var parts = value.split('.');

                if (parts.length !== 2) {
                    return;
                }

                var page = parts[0];
                var action = parts[1];
                var form = $(this).closest('form');
                var viewCheckbox = form.find('.permission-checkbox[value="' + page + '.view"]');

                if ($(this).is(':checked') && action !== 'view') {
                    viewCheckbox.prop('checked', true);
                }

                if (!$(this).is(':checked') && action === 'view') {
                    form.find('.permission-checkbox').filter(function(){
                        return $(this).val().indexOf(page + '.') === 0 && $(this).val() !== page + '.view';
                    }).prop('checked', false);
                }
            });
        });
    </script>
@endsection
