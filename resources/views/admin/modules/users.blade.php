@extends('admin.includes.body')
@section('title', 'Users')
@section('subtitle','Users')
@section('content')
    @php($hasCustomPermissionsRoute = Route::has('users.custom-permissions.update'))
    @php($hasResourceAccessRoute = Route::has('users.resource-access.update'))
    @php($canManageCustomPermissionsModal = $canManageCustomPermissions && $hasCustomPermissionsRoute)
    @php($canManageResourceAccessModal = $canManageResourceAccess && $hasResourceAccessRoute)
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap">
            <div>
                <h5 class="mb-1">Users</h5>
                {{-- <small class="text-muted">Manage user accounts, assign a role, and add extra shortcode or service visibility when needed. Actual shortcode ownership is assigned from the Shortcode page by anyone with the <strong>Shortcode - Assign Owner</strong> permission.</small> --}}
            </div>
            <div class="mt-2 mt-md-0">
                @if($authUser->hasPermission('users.update') && Route::has('keywords.index'))
                    <a href="{{ route('keywords.index') }}" class="btn btn-default">
                        Keywords
                    </a>
                @endif
                @if($canManageRoles)
                    <a href="{{ route('roles.index') }}" class="btn btn-default">
                        <i class="align-middle" data-feather="shield"></i> Manage Roles
                    </a>
                @endif
                @if($authUser->hasPermission('users.create'))
                    <button class="btn btn-primary" data-toggle="modal" data-target="#addModal">
                        <i class="align-middle" data-feather="plus"></i> Add User
                    </button>
                @endif
            </div>
        </div>
        <div class="card-body">
            <table id="userstable" class="table table-striped table-hover custom-list-table">
                <thead>
                <tr>
                    <th>#</th>
                    <th>Name</th>
                    <th>Username</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th>Visibility Scope</th>
                    <th>Custom Overrides</th>
                    <th>Ownership</th>
                    <th>Action</th>
                </tr>
                </thead>
                <tfoot>
                <tr>
                    <th>#</th>
                    <th>Name</th>
                    <th>Username</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th>Visibility Scope</th>
                    <th>Custom Overrides</th>
                    <th>Ownership</th>
                    <th>Action</th>
                </tr>
                </tfoot>
            </table>
        </div>
    </div>

    @if($authUser->hasPermission('users.create'))
        <div class="modal" tabindex="-1" role="dialog" id="addModal">
            <div class="modal-dialog modal-xl" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Add User</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <form action="{{ route('users.store') }}" class="form form-horizontal user-form" data-modal="#addModal" method="post" id="add-user-form">
                            @csrf
                            <div class="form-group form-row">
                                <div class="col-md-4">
                                    <label for="add-name" class="control-label">Name</label>
                                    <input type="text" name="name" id="add-name" class="form-control">
                                </div>
                                <div class="col-md-4">
                                    <label for="add-username" class="control-label">Username</label>
                                    <input type="text" name="username" id="add-username" class="form-control">
                                </div>
                                <div class="col-md-4">
                                    <label for="add-email" class="control-label">Email</label>
                                    <input type="email" name="email" id="add-email" class="form-control">
                                </div>
                            </div>

                            <div class="form-group form-row">
                                <div class="col-md-6">
                                    <label for="add-password" class="control-label">Password</label>
                                    <input type="password" name="password" id="add-password" class="form-control">
                                </div>
                                <div class="col-md-6">
                                    <label for="add-password-confirmation" class="control-label">Confirm Password</label>
                                    <input type="password" name="password_confirmation" id="add-password-confirmation" class="form-control">
                                </div>
                            </div>

                            @if($canManageRoles)
                                <div class="line-divider"></div>
                                <div class="form-group">
                                    <label for="add-role-id" class="control-label">Role</label>
                                    <select name="role_id" id="add-role-id" class="custom-select">
                                        <option value="">No role</option>
                                        @foreach($roles as $role)
                                            @php($rolePermissionSlugs = $role->slug === 'superadmin' ? \App\User::permissionSlugs() : $role->permissions->pluck('slug')->all())
                                            @if($role->slug === 'customer-client')
                                                @php($rolePermissionSlugs = array_values(array_diff($rolePermissionSlugs, \App\User::clientScopedRestrictedPermissionSlugs())))
                                            @endif
                                            <option value="{{ $role->id }}" data-role-name="{{ $role->name }}" data-role-slug="{{ $role->slug }}" data-description="{{ $role->description }}" data-permissions="{{ implode(',', $rolePermissionSlugs) }}">{{ $role->name }}</option>
                                        @endforeach
                                    </select>
                                    <small class="text-muted">Use roles for broad page access. Ownership comes from shortcode and service assignment, while the specific access section below handles shared resources.</small>
                                </div>
                            @endif

                            @if($canManageResourceAccessModal)
                                <div class="line-divider"></div>
                                <div class="alert alert-light border mb-0">
                                    Specific resource access is managed after the user is created. Use the <strong>Resource Access</strong> action from the Users list when you want to grant shared shortcode visibility, service transaction access, or service amount limits.
                                </div>
                            @endif

                            @if($canManageCustomPermissions)
                                <div class="line-divider"></div>
                                @include('admin.modules.partials.permission-fields', [
                                    'formPrefix' => 'add-custom-permission',
                                    'fieldName' => 'custom_permissions[]',
                                    'selectedPermissions' => [],
                                    'permissions' => $permissions,
                                    'permissionMode' => 'custom',
                                ])
                            @endif

                            <div class="form-group form-row mt-3 mb-0">
                                <div class="ml-auto">
                                    <button type="submit" class="btn btn-primary">Create User</button>
                                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    @endif

    @if($authUser->hasPermission('users.update'))
        <div class="modal" tabindex="-1" role="dialog" id="editModal">
            <div class="modal-dialog modal-xl" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Edit User</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <form action="{{ route('users.update') }}" class="form form-horizontal user-form" data-modal="#editModal" method="post" id="edit-user-form">
                            @csrf
                            <input type="hidden" name="id" id="edit-id">

                            <div class="form-group form-row">
                                <div class="col-md-4">
                                    <label for="edit-name" class="control-label">Name</label>
                                    <input type="text" name="name" id="edit-name" class="form-control">
                                </div>
                                <div class="col-md-4">
                                    <label for="edit-username" class="control-label">Username</label>
                                    <input type="text" name="username" id="edit-username" class="form-control">
                                </div>
                                <div class="col-md-4">
                                    <label for="edit-email" class="control-label">Email</label>
                                    <input type="email" name="email" id="edit-email" class="form-control">
                                </div>
                            </div>

                            <div class="form-group form-row">
                                <div class="col-md-6">
                                    <label for="edit-password" class="control-label">New Password</label>
                                    <input type="password" name="password" id="edit-password" class="form-control">
                                    <small class="text-muted">Leave blank to keep the current password.</small>
                                </div>
                                <div class="col-md-6">
                                    <label for="edit-password-confirmation" class="control-label">Confirm New Password</label>
                                    <input type="password" name="password_confirmation" id="edit-password-confirmation" class="form-control">
                                </div>
                            </div>

                            @if($canManageRoles)
                                <div class="line-divider"></div>
                                <div class="form-group">
                                    <label for="edit-role-id" class="control-label">Role</label>
                                    <select name="role_id" id="edit-role-id" class="custom-select">
                                        <option value="">No role</option>
                                        @foreach($roles as $role)
                                            @php($rolePermissionSlugs = $role->slug === 'superadmin' ? \App\User::permissionSlugs() : $role->permissions->pluck('slug')->all())
                                            @if($role->slug === 'customer-client')
                                                @php($rolePermissionSlugs = array_values(array_diff($rolePermissionSlugs, \App\User::clientScopedRestrictedPermissionSlugs())))
                                            @endif
                                            <option value="{{ $role->id }}" data-role-name="{{ $role->name }}" data-role-slug="{{ $role->slug }}" data-description="{{ $role->description }}" data-permissions="{{ implode(',', $rolePermissionSlugs) }}">{{ $role->name }}</option>
                                        @endforeach
                                    </select>
                                    <small class="text-muted">Role permissions are view only from the Users page. Use the separate role or custom permission dialogs for permission details.</small>
                                </div>
                            @endif

                            @if($canManageResourceAccessModal)
                                <div class="line-divider"></div>
                                <div class="card border">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-center flex-wrap">
                                            <div>
                                                <h6 class="mb-1">Specific Resource Access</h6>
                                                <small class="text-muted">Manage shared resource visibility and per-service transaction amount limits from a separate modal so profile edits do not change access by mistake.</small>
                                            </div>
                                            <div class="mt-2 mt-md-0">
                                                <button type="button" class="btn btn-default btn-sm" id="open-resource-access-from-edit">
                                                    <i class="align-middle" data-feather="git-branch"></i> Resource Access
                                                </button>
                                            </div>
                                        </div>
                                        <div class="mt-3">
                                            <label class="input-label d-block">Current Specific Access</label>
                                            <div class="text-muted" id="edit-resource-access-summary">No specific resource access assigned.</div>
                                        </div>
                                    </div>
                                </div>
                            @endif

                            @if($canManageRoles || $canManageCustomPermissionsModal)
                                <div class="line-divider"></div>
                                <div class="card border">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-center flex-wrap">
                                            <div>
                                                <h6 class="mb-1">Permissions</h6>
                                                <small class="text-muted">Role permissions are view only here. Custom permissions are managed separately so admins only work with true overrides.</small>
                                            </div>
                                            <div class="mt-2 mt-md-0">
                                                @if($canManageRoles)
                                                    <button type="button" class="btn btn-default btn-sm" id="open-role-permissions-from-edit">
                                                        <i class="align-middle" data-feather="shield"></i> View Role Permissions
                                                    </button>
                                                @endif
                                                @if($canManageCustomPermissionsModal)
                                                    <button type="button" class="btn btn-primary btn-sm" id="open-custom-permissions-from-edit">
                                                        <i class="align-middle" data-feather="lock"></i> Custom Permissions
                                                    </button>
                                                @endif
                                            </div>
                                        </div>
                                        @if($canManageCustomPermissionsModal)
                                            <div class="mt-3">
                                                <label class="input-label d-block">Current Custom Overrides</label>
                                                <div class="text-muted" id="edit-custom-permission-summary">No custom permissions selected.</div>
                                                <small class="text-muted d-block mt-1">If you change the assigned role, save the user first before updating custom permissions so the override list reflects the latest role access.</small>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @endif

                            <div class="form-group form-row mt-3 mb-0">
                                <div class="ml-auto">
                                    <button type="submit" class="btn btn-primary">Save Changes</button>
                                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    @endif

    @if($canManageRoles)
        <div class="modal" tabindex="-1" role="dialog" id="rolePermissionsModal">
            <div class="modal-dialog modal-xl" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Role Permissions</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group form-row">
                            <div class="col-md-6">
                                <label class="input-label d-block">User</label>
                                <div id="role-permissions-user-name">N/A</div>
                            </div>
                            <div class="col-md-6">
                                <label class="input-label d-block">Assigned Role</label>
                                <div id="role-permissions-role-name">No role assigned</div>
                            </div>
                        </div>
                        <div class="form-group" id="role-permissions-description-wrapper">
                            <label class="input-label d-block">Role Description</label>
                            <div id="role-permissions-role-description" class="text-muted">No role description available.</div>
                        </div>
                        <div id="role-permissions-empty-state" class="alert alert-light border d-none mb-0">
                            No role permissions are available to show for this user right now.
                        </div>
                        <div id="role-permissions-content">
                            @include('admin.modules.partials.permission-fields', [
                                'formPrefix' => 'role-permissions-view',
                                'fieldName' => 'role_permissions_view[]',
                                'selectedPermissions' => [],
                                'permissions' => $permissions,
                                'readOnly' => true,
                            ])
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    @if($canManageCustomPermissionsModal)
        <div class="modal" tabindex="-1" role="dialog" id="customPermissionsModal">
            <div class="modal-dialog modal-xl" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Custom Permissions</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <form action="{{ route('users.custom-permissions.update') }}" class="form form-horizontal" method="post" id="custom-permissions-form">
                            @csrf
                            <input type="hidden" name="id" id="custom-permissions-user-id">
                            <div class="form-group form-row">
                                <div class="col-md-6">
                                    <label class="input-label d-block">User</label>
                                    <div id="custom-permissions-user-name">N/A</div>
                                </div>
                                <div class="col-md-6">
                                    <label class="input-label d-block">Assigned Role</label>
                                    <div id="custom-permissions-role-name">No role assigned</div>
                                </div>
                            </div>
                            <div class="mb-3 text-muted">
                                Only permissions not already provided by the assigned role are listed here. If a role later receives one of these permissions, the duplicate custom override is removed automatically.
                            </div>
                            @include('admin.modules.partials.permission-fields', [
                                'formPrefix' => 'standalone-custom-permission',
                                'fieldName' => 'custom_permissions[]',
                                'selectedPermissions' => [],
                                'permissions' => $permissions,
                                'permissionMode' => 'custom',
                            ])
                            <div class="form-group form-row mt-3 mb-0">
                                <div class="ml-auto">
                                    <button type="submit" class="btn btn-primary">Save Custom Permissions</button>
                                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    @endif

    @if($canManageResourceAccessModal)
        <div class="modal" tabindex="-1" role="dialog" id="resourceAccessModal">
            <div class="modal-dialog modal-xl" role="document" style="max-width: 1280px; width: 96%;">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Specific Resource Access</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <form action="{{ route('users.resource-access.update') }}" class="form form-horizontal" method="post" id="resource-access-form">
                            @csrf
                            <input type="hidden" name="id" id="resource-access-user-id">
                            <div class="form-group form-row">
                                <div class="col-md-6">
                                    <label class="input-label d-block">User</label>
                                    <div id="resource-access-user-name">N/A</div>
                                </div>
                                <div class="col-md-6">
                                    <label class="input-label d-block">Assigned Role</label>
                                    <div id="resource-access-role-name">No role assigned</div>
                                </div>
                            </div>
                            <div class="mb-3 text-muted">
                                Use this for shared shortcodes or selected services. If you change min/max values, the new limits apply only to transactions received after you save; older transactions keep the limits that were active at that time.
                            </div>
                            @include('admin.modules.partials.resource-access-fields', [
                                'formPrefix' => 'resource-access',
                                'shortcodeFieldName' => 'shared_shortcode_access[]',
                                'serviceFieldName' => 'service_access[]',
                                'selectedShortcodes' => [],
                                'selectedServices' => [],
                                'sharedShortcodes' => $sharedShortcodes,
                                'sharedServices' => $sharedServices,
                            ])
                            <div class="alert alert-dark border d-none p-3" id="resource-access-empty-state">
                                No extra shared shortcodes or services are available for this user right now.
                            </div>
                            <div class="form-group form-row mt-3 mb-0">
                                <div class="ml-auto">
                                    <button type="submit" class="btn btn-primary">Save Resource Access</button>
                                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    @endif
@endsection

@section('script')
    <script>
        $(document).ready(function(){
            var userTable = null;
            var clientScopedRestrictedPermissions = @json(\App\User::clientScopedRestrictedPermissionSlugs());
            var usersDatatableConfig = {
                processing: true,
                serverSide: true,
                responsive: true,
                autoWidth: false,
                ajax: {
                    url: "{{ route('users.datatable') }}",
                    dataType: "json",
                    type: "POST",
                    data: { _token: "{{ csrf_token() }}" },
                    error: function(xhr) {
                        console.error('Users datatable request failed.', xhr);
                        toastr.error(
                            (xhr.responseJSON && (xhr.responseJSON.message || xhr.responseJSON.error)) || 'Unable to load users right now.',
                            'Users',
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
                    { data: "id" },
                    { data: "name" },
                    { data: "username" },
                    { data: "email" },
                    { data: "role", orderable: false, searchable: false },
                    { data: "status", orderable: false, searchable: false },
                    { data: "visibility", orderable: false, searchable: false },
                    { data: "permissions", orderable: false, searchable: false },
                    { data: "ownership", orderable: false, searchable: false },
                    { data: "action", orderable: false, searchable: false }
                ],
                order: [[0, "desc"]]
            };

            function initializeUsersTable(remainingAttempts) {
                if (!$('#userstable').length) {
                    return null;
                }

                if ($.fn.DataTable && $.fn.DataTable.isDataTable('#userstable')) {
                    userTable = $('#userstable').DataTable();
                    return userTable;
                }

                if (!$.fn.DataTable) {
                    if (remainingAttempts > 0) {
                        setTimeout(function() {
                            initializeUsersTable(remainingAttempts - 1);
                        }, 250);
                    } else {
                        console.error('DataTables plugin is not available on the Users page.');
                        toastr.error('The Users table could not be initialized. Please refresh the page.', 'Users', {
                            timeOut: 2500,
                            closeButton: true,
                            progressBar: true,
                            newestOnTop: true
                        });
                    }

                    return null;
                }

                try {
                    userTable = $('#userstable').DataTable(usersDatatableConfig);
                    return userTable;
                } catch (error) {
                    console.error('Failed to initialize the Users datatable.', error);

                    if (remainingAttempts > 0) {
                        setTimeout(function() {
                            initializeUsersTable(remainingAttempts - 1);
                        }, 250);
                    } else {
                        toastr.error('The Users table could not be initialized. Please refresh the page.', 'Users', {
                            timeOut: 2500,
                            closeButton: true,
                            progressBar: true,
                            newestOnTop: true
                        });
                    }
                }

                return null;
            }

            initializeUsersTable(5);

            function decodePayload(encoded) {
                var binary = atob(encoded);
                var encodedString = '';

                for (var i = 0; i < binary.length; i++) {
                    encodedString += '%' + ('00' + binary.charCodeAt(i).toString(16)).slice(-2);
                }

                return JSON.parse(decodeURIComponent(encodedString));
            }

            function normalizePermissions(permissions) {
                var values = $.isArray(permissions) ? permissions : [];
                var normalized = [];

                $.each(values, function(index, permission){
                    permission = $.trim(String(permission || ''));

                    if (permission !== '' && normalized.indexOf(permission) === -1) {
                        normalized.push(permission);
                    }
                });

                return normalized;
            }

            function resetPermissionCheckboxes(containerSelector, selectedPermissions) {
                var normalizedPermissions = normalizePermissions(selectedPermissions);

                $(containerSelector + ' .permission-checkbox').prop('checked', false);

                $.each(normalizedPermissions, function(index, permission){
                    $(containerSelector + ' .permission-checkbox[value="' + permission + '"]').prop('checked', true);
                });
            }

            function selectedRolePermissions(formSelector) {
                var roleSelector = $(formSelector + ' select[name="role_id"]');

                if (!roleSelector.length) {
                    return [];
                }

                var rawPermissions = String(roleSelector.find('option:selected').data('permissions') || '');

                if (rawPermissions === '') {
                    return [];
                }

                return rawPermissions.split(',').filter(function(permission) {
                    return $.trim(permission) !== '';
                });
            }

            function selectedRoleMeta(formSelector) {
                var roleSelector = $(formSelector + ' select[name="role_id"]');
                var selectedOption = roleSelector.find('option:selected');

                return {
                    id: selectedOption.val() || '',
                    name: String(selectedOption.data('role-name') || selectedOption.text() || '').trim(),
                    slug: String(selectedOption.data('role-slug') || '').trim(),
                    description: String(selectedOption.data('description') || '').trim(),
                    permissions: selectedRolePermissions(formSelector)
                };
            }

            function syncCustomPermissionVisibility(containerSelector, inheritedPermissions) {
                var container = $(containerSelector);

                if (!container.length) {
                    return;
                }

                inheritedPermissions = normalizePermissions(
                    inheritedPermissions !== undefined ? inheritedPermissions : selectedRolePermissions(containerSelector)
                );
                var roleSelector = container.find('select[name="role_id"]');
                var selectedRoleSlug = String(roleSelector.find('option:selected').data('role-slug') || '').trim();
                var superAdminRoleSelected = selectedRoleSlug === 'superadmin';
                var scopedClientRoleSelected = selectedRoleSlug === 'customer-client';

                container.find('.permission-option').each(function(){
                    var option = $(this);
                    var slug = String(option.data('permission-slug') || '');
                    var checkbox = option.find('.permission-checkbox');
                    var isInherited = inheritedPermissions.indexOf(slug) !== -1;
                    var isRestrictedForClient = scopedClientRoleSelected && clientScopedRestrictedPermissions.indexOf(slug) !== -1;

                    if (superAdminRoleSelected || isInherited || isRestrictedForClient) {
                        checkbox.prop('checked', false).prop('disabled', true);
                        option.addClass('d-none');
                        return;
                    }

                    checkbox.prop('disabled', false);
                    option.removeClass('d-none');
                });

                container.find('.permission-page-card').each(function(){
                    var card = $(this);
                    var visibleOptions = card.find('.permission-option').not('.d-none').length;

                    card.toggleClass('d-none', visibleOptions === 0);
                });
            }

            function syncReadOnlyPermissionVisibility(containerSelector) {
                var container = $(containerSelector);

                container.find('.permission-option').each(function(){
                    var option = $(this);
                    var checkbox = option.find('.permission-checkbox');
                    option.toggleClass('d-none', !checkbox.is(':checked'));
                });

                container.find('.permission-page-card').each(function(){
                    var card = $(this);
                    var visibleOptions = card.find('.permission-option').not('.d-none').length;
                    card.toggleClass('d-none', visibleOptions === 0);
                });
            }

            function resetResourceCheckboxes(formSelector, selectedShortcodes, selectedServices, serviceAmountLimits) {
                $(formSelector + ' input[name="shared_shortcode_access[]"]').prop('checked', false);
                $(formSelector + ' input[name="service_access[]"]').prop('checked', false);
                $(formSelector + ' .service-amount-min, ' + formSelector + ' .service-amount-max').val('');
                $(formSelector + ' .service-bypass-history').prop('checked', false);
                serviceAmountLimits = serviceAmountLimits || {};

                $.each(selectedShortcodes || [], function(index, resourceId){
                    $(formSelector + ' input[name="shared_shortcode_access[]"][value="' + resourceId + '"]').prop('checked', true);
                });

                $.each(selectedServices || [], function(index, resourceId){
                    $(formSelector + ' input[name="service_access[]"][value="' + resourceId + '"]').prop('checked', true);
                });

                $.each(serviceAmountLimits, function(resourceId, limits){
                    limits = limits || {};
                    $(formSelector + ' .service-amount-min[data-service-id="' + resourceId + '"]').val(limits.min !== null && limits.min !== undefined ? limits.min : '');
                    $(formSelector + ' .service-amount-max[data-service-id="' + resourceId + '"]').val(limits.max !== null && limits.max !== undefined ? limits.max : '');
                    $(formSelector + ' .service-bypass-history[data-service-id="' + resourceId + '"]').prop('checked', parseInt(limits.bypass_history, 10) === 1 || limits.bypass_history === true);
                });
            }

            function syncServiceAmountFieldState(formSelector) {
                var form = $(formSelector);

                form.find('.resource-option-service').each(function(){
                    var option = $(this);
                    var checkbox = option.find('input[name="service_access[]"]');
                    var enabled = checkbox.is(':checked');

                    option.find('.service-amount-min, .service-amount-max, .service-bypass-history').prop('disabled', !enabled);
                });

            }

            function syncResourceAccessVisibility(formSelector, userId) {
                var normalizedUserId = parseInt(userId, 10) || 0;
                var form = $(formSelector);

                if (!form.length) {
                    return;
                }

                form.find('.resource-option').each(function(){
                    var option = $(this);
                    var ownerUserId = parseInt(option.data('owner-user-id'), 10) || 0;
                    var checkbox = option.find('.resource-checkbox');
                    var isOwned = normalizedUserId > 0 && ownerUserId === normalizedUserId;
                    var isService = option.hasClass('resource-option-service');
                    var sharingMode = String(option.data('sharing-mode') || 'shared');

                    option.find('.resource-owned-badge').remove();

                    if (isOwned) {
                        checkbox.prop('checked', true).prop('disabled', true);
                        option.removeClass('d-none');
                        option.find('.font-weight-bold').first().append(' <span class="badge badge-success ml-2 resource-owned-badge">Owned</span>');
                        syncServiceAmountFieldState(formSelector);
                        return;
                    }

                    if (isService && sharingMode !== 'shared') {
                        checkbox.prop('checked', false).prop('disabled', true);
                        option.addClass('d-none');
                        return;
                    }

                    checkbox.prop('disabled', false);
                    option.removeClass('d-none');
                });

                form.find('.card').each(function(){
                    var card = $(this);
                    var visibleOptions = card.find('.resource-option').not('.d-none').length;
                    card.closest('.col-lg-6, .col-12').toggleClass('d-none', visibleOptions === 0);
                });

                $('#resource-access-empty-state').toggleClass(
                    'd-none',
                    form.find('.resource-option').not('.d-none').length > 0
                );
                syncServiceAmountFieldState(formSelector);
            }

            function renderResourceAccessSummary(targetSelector, user) {
                var shortcodeCount = $.isArray(user.shared_shortcode_access) ? user.shared_shortcode_access.length : 0;
                var serviceCount = $.isArray(user.service_access) ? user.service_access.length : 0;
                var summary = [];
                var target = $(targetSelector);

                if (!target.length) {
                    return;
                }

                summary.push(shortcodeCount ? shortcodeCount + ' shared shortcode(s)' : 'No shared shortcodes');
                summary.push(serviceCount ? serviceCount + ' shared service(s)' : 'No shared services');
                target.text(summary.join(' | '));
            }

            function permissionLabel(permissionSlug) {
                var option = $('.permission-option[data-permission-slug="' + permissionSlug + '"]').first();
                var label = $.trim(option.find('.font-weight-bold').first().text());

                return label !== '' ? label : permissionSlug;
            }

            function renderPermissionSummary(targetSelector, permissions, emptyLabel) {
                var normalizedPermissions = normalizePermissions(permissions);
                var target = $(targetSelector);

                if (!target.length) {
                    return;
                }

                if (!normalizedPermissions.length) {
                    target.text(emptyLabel);
                    return;
                }

                var labels = $.map(normalizedPermissions, function(permission){
                    return permissionLabel(permission);
                });
                var visibleLabels = labels.slice(0, 3).join(', ');
                var remaining = labels.length - 3;

                target.text(remaining > 0 ? visibleLabels + ' +' + remaining + ' more' : visibleLabels);
            }

            function resetUserForm(formSelector) {
                if (!$(formSelector).length) {
                    return;
                }

                $(formSelector)[0].reset();

                if (formSelector === '#add-user-form') {
                    resetPermissionCheckboxes(formSelector, []);
                    syncCustomPermissionVisibility(formSelector);
                }

                if (formSelector === '#edit-user-form') {
                    $(formSelector).removeData('userPayload');
                    renderPermissionSummary('#edit-custom-permission-summary', [], 'No custom permissions selected.');
                    renderResourceAccessSummary('#edit-resource-access-summary', {});
                }
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

            function openRolePermissionsModal(user, roleMetaOverride) {
                var roleMeta = roleMetaOverride || {
                    id: user.role_id || '',
                    name: user.role_name || '',
                    description: user.role_description || '',
                    permissions: user.role_permissions || []
                };
                var hasRole = $.trim(String(roleMeta.id || '')) !== '' || $.trim(String(roleMeta.name || '')) !== '';

                $('#role-permissions-user-name').text(user.name || ('User #' + user.id));
                $('#role-permissions-role-name').text(hasRole ? roleMeta.name : 'No role assigned');
                $('#role-permissions-role-description').text($.trim(roleMeta.description || '') !== '' ? roleMeta.description : 'No role description available.');
                resetPermissionCheckboxes('#role-permissions-content', roleMeta.permissions || []);
                syncReadOnlyPermissionVisibility('#role-permissions-content');

                var visibleCards = $('#role-permissions-content .permission-page-card').not('.d-none').length;
                var showEmptyState = !hasRole || visibleCards === 0;

                $('#role-permissions-empty-state').toggleClass('d-none', !showEmptyState);
                $('#role-permissions-content').toggleClass('d-none', showEmptyState);
                $('#role-permissions-description-wrapper').toggleClass('d-none', !hasRole);
                $('#rolePermissionsModal').modal('show');
            }

            function openCustomPermissionsModal(user) {
                if (user.is_superadmin === true) {
                    toastr.info('Superadmin already has full access. Custom permissions are not used for this role.', 'Users', {
                        timeOut: 2000,
                        closeButton: true,
                        progressBar: true,
                        newestOnTop: true
                    });
                    return;
                }

                var inheritedPermissions = normalizePermissions(user.role_permissions || []);

                $('#custom-permissions-user-id').val(user.id);
                $('#custom-permissions-user-name').text(user.name || ('User #' + user.id));
                $('#custom-permissions-role-name').text(user.role_name || 'No role assigned');
                $('#custom-permissions-form').data('userPayload', user);
                $('#custom-permissions-form').data('inheritedPermissions', inheritedPermissions);
                resetPermissionCheckboxes('#custom-permissions-form', user.custom_permissions || []);
                syncCustomPermissionVisibility('#custom-permissions-form', inheritedPermissions);
                $('#customPermissionsModal').modal('show');
            }

            function openResourceAccessModal(user) {
                $('#resource-access-user-id').val(user.id);
                $('#resource-access-user-name').text(user.name || ('User #' + user.id));
                $('#resource-access-role-name').text(user.role_name || 'No role assigned');
                $('#resource-access-form').data('userPayload', user);
                resetResourceCheckboxes('#resource-access-form', user.shared_shortcode_access || [], user.service_access || [], user.service_amount_limits || {});
                syncResourceAccessVisibility('#resource-access-form', user.id);
                $('#resourceAccessModal').modal('show');
            }

            $(document).on('submit', '.user-form', function(e){
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
                            resetUserForm('#add-user-form');
                            resetUserForm('#edit-user-form');
                            toastr.success(message.msg, message.header, {
                                timeOut: 1500,
                                closeButton: true,
                                progressBar: true,
                                newestOnTop: true
                            });
                            if (userTable) {
                                userTable.ajax.reload(null, false);
                            } else {
                                initializeUsersTable(2);
                            }
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
                        handleFormErrors(response, 'Users');
                    }
                });
            });

            $(document).on('click', '.edit-user', function(){
                var user = decodePayload($(this).attr('data-user'));

                $('#edit-id').val(user.id);
                $('#edit-name').val(user.name);
                $('#edit-username').val(user.username);
                $('#edit-email').val(user.email);
                $('#edit-password').val('');
                $('#edit-password-confirmation').val('');
                $('#edit-role-id').val(user.role_id || '');
                $('#edit-user-form').data('userPayload', user);
                $('#open-custom-permissions-from-edit').toggleClass('d-none', user.is_superadmin === true);
                if (user.is_superadmin === true) {
                    $('#edit-custom-permission-summary').text('Superadmin has full access. Custom permissions are not used.');
                } else {
                    renderPermissionSummary('#edit-custom-permission-summary', user.custom_permissions || [], 'No custom permissions selected.');
                }
                renderResourceAccessSummary('#edit-resource-access-summary', user);
                $('#editModal').modal('show');
            });

            $('#add-role-id').on('change', function(){
                syncCustomPermissionVisibility('#add-user-form');
            });

            $('#edit-role-id').on('change', function(){
                var roleMeta = selectedRoleMeta('#edit-user-form');
                var isSuperAdminRole = roleMeta.slug === 'superadmin';

                $('#open-custom-permissions-from-edit').toggleClass('d-none', isSuperAdminRole);

                if (isSuperAdminRole) {
                    $('#edit-custom-permission-summary').text('Superadmin has full access. Custom permissions are not used.');
                } else {
                    var user = $('#edit-user-form').data('userPayload') || {};
                    renderPermissionSummary('#edit-custom-permission-summary', user.custom_permissions || [], 'No custom permissions selected.');
                }
            });

            $(document).on('click', '.view-role-permissions', function(){
                openRolePermissionsModal(decodePayload($(this).attr('data-user')));
            });

            $(document).on('click', '.manage-custom-permissions', function(){
                openCustomPermissionsModal(decodePayload($(this).attr('data-user')));
            });

            $(document).on('click', '.manage-resource-access', function(){
                openResourceAccessModal(decodePayload($(this).attr('data-user')));
            });

            $(document).on('change', '#resource-access-form input[name="service_access[]"]', function(){
                syncServiceAmountFieldState('#resource-access-form');
            });

            function openModalFromEdit(opener) {
                if (!$('#editModal').hasClass('show')) {
                    opener();
                    return;
                }

                $('#editModal').data('reopenAfterPermissionModal', true);
                $('#editModal').data('pendingPermissionModal', opener);
                $('#editModal').modal('hide');
            }

            $('#editModal').on('hidden.bs.modal', function(){
                var opener = $(this).data('pendingPermissionModal');

                if (!opener) {
                    return;
                }

                $(this).removeData('pendingPermissionModal');
                opener();
            });

            $('#rolePermissionsModal, #customPermissionsModal, #resourceAccessModal').on('hidden.bs.modal', function(){
                if ($('#editModal').data('reopenAfterPermissionModal')) {
                    $('#editModal').data('reopenAfterPermissionModal', false);
                    $('#editModal').modal('show');
                }
            });

            $('#open-role-permissions-from-edit').on('click', function(){
                openModalFromEdit(function(){
                    var user = $('#edit-user-form').data('userPayload') || {};
                    var roleMeta = selectedRoleMeta('#edit-user-form');

                    user.name = $('#edit-name').val() || user.name;
                    openRolePermissionsModal(user, roleMeta);
                });
            });

            $('#open-custom-permissions-from-edit').on('click', function(){
                var user = $('#edit-user-form').data('userPayload');

                if (!user) {
                    toastr.error('Open a user first before managing custom permissions.', 'Users', {
                        timeOut: 2000,
                        closeButton: true,
                        progressBar: true,
                        newestOnTop: true
                    });
                    return;
                }

                if (user.is_superadmin === true) {
                    toastr.info('Superadmin already has full access. Custom permissions are not used for this role.', 'Users', {
                        timeOut: 2000,
                        closeButton: true,
                        progressBar: true,
                        newestOnTop: true
                    });
                    return;
                }

                openModalFromEdit(function(){
                    openCustomPermissionsModal(user);
                });
            });

            $('#open-resource-access-from-edit').on('click', function(){
                var user = $('#edit-user-form').data('userPayload');

                if (!user) {
                    toastr.error('Open a user first before managing specific resource access.', 'Users', {
                        timeOut: 2000,
                        closeButton: true,
                        progressBar: true,
                        newestOnTop: true
                    });
                    return;
                }

                openModalFromEdit(function(){
                    openResourceAccessModal(user);
                });
            });

            $(document).on('submit', '#custom-permissions-form', function(e){
                e.preventDefault();

                var form = $(this);
                var user = form.data('userPayload') || {};

                $.ajax({
                    type: 'POST',
                    url: form.attr('action'),
                    headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                    data: form.serialize(),
                    success: function(message){
                        if (message.status) {
                            user.custom_permissions = normalizePermissions(
                                $.map(form.find('.permission-checkbox:checked:not(:disabled)'), function(input){
                                    return $(input).val();
                                })
                            );
                            form.data('userPayload', user);

                            if ($('#edit-user-form').data('userPayload') && ($('#edit-user-form').data('userPayload').id === user.id)) {
                                $('#edit-user-form').data('userPayload').custom_permissions = user.custom_permissions;
                                renderPermissionSummary('#edit-custom-permission-summary', user.custom_permissions, 'No custom permissions selected.');
                            }

                            $('#customPermissionsModal').modal('hide');
                            toastr.success(message.msg, message.header, {
                                timeOut: 1500,
                                closeButton: true,
                                progressBar: true,
                                newestOnTop: true
                            });
                            if (userTable) {
                                userTable.ajax.reload(null, false);
                            } else {
                                initializeUsersTable(2);
                            }
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
                        handleFormErrors(response, 'Users');
                    }
                });
            });

            $(document).on('submit', '#resource-access-form', function(e){
                e.preventDefault();

                var form = $(this);
                var user = form.data('userPayload') || {};

                $.ajax({
                    type: 'POST',
                    url: form.attr('action'),
                    headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                    data: form.serialize(),
                    success: function(message){
                        if (message.status) {
                            user.shared_shortcode_access = $.map(form.find('input[name="shared_shortcode_access[]"]:checked'), function(input){
                                return parseInt($(input).val(), 10);
                            });
                            user.service_access = $.map(form.find('input[name="service_access[]"]:checked'), function(input){
                                return parseInt($(input).val(), 10);
                            });
                            user.service_amount_limits = {};
                            form.find('.resource-option-service').each(function(){
                                var option = $(this);
                                var serviceId = parseInt(option.data('service-id'), 10) || 0;

                                if (!serviceId) {
                                    return;
                                }

                                user.service_amount_limits[serviceId] = {
                                    min: option.find('.service-amount-min').val() || null,
                                    max: option.find('.service-amount-max').val() || null,
                                    bypass_history: option.find('.service-bypass-history').is(':checked') ? 1 : 0
                                };
                            });
                            form.data('userPayload', user);

                            if ($('#edit-user-form').data('userPayload') && ($('#edit-user-form').data('userPayload').id === user.id)) {
                                $('#edit-user-form').data('userPayload').shared_shortcode_access = user.shared_shortcode_access;
                                $('#edit-user-form').data('userPayload').service_access = user.service_access;
                                $('#edit-user-form').data('userPayload').service_amount_limits = user.service_amount_limits;
                                renderResourceAccessSummary('#edit-resource-access-summary', user);
                            }

                            $('#resourceAccessModal').modal('hide');
                            toastr.success(message.msg, message.header, {
                                timeOut: 1500,
                                closeButton: true,
                                progressBar: true,
                                newestOnTop: true
                            });
                            if (userTable) {
                                userTable.ajax.reload(null, false);
                            } else {
                                initializeUsersTable(2);
                            }
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
                        handleFormErrors(response, 'Users');
                    }
                });
            });

            $(document).on('click', '.toggle-user-status', function(){
                $.ajax({
                    type: 'POST',
                    url: "{{ route('users.toggle-status') }}",
                    headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                    data: { id: $(this).data('id') },
                    success: function(message){
                        if (message.status) {
                            toastr.success(message.msg, message.header, {
                                timeOut: 1500,
                                closeButton: true,
                                progressBar: true,
                                newestOnTop: true
                            });
                            if (userTable) {
                                userTable.ajax.reload(null, false);
                            } else {
                                initializeUsersTable(2);
                            }
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
                        handleFormErrors(response, 'Users');
                    }
                });
            });

            $(document).on('click', '.delete-user', function(){
                var userId = $(this).data('id');
                var name = $(this).data('name');

                if (!confirm('Delete ' + name + '? Linked owned shortcodes and services will be soft-deleted and can be restored from Audit Logs.')) {
                    return;
                }

                $.ajax({
                    type: 'POST',
                    url: "{{ route('users.destroy') }}",
                    headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                    data: { id: userId },
                    success: function(message){
                        if (message.status) {
                            toastr.success(message.msg, message.header, {
                                timeOut: 1500,
                                closeButton: true,
                                progressBar: true,
                                newestOnTop: true
                            });
                            if (userTable) {
                                userTable.ajax.reload(null, false);
                            } else {
                                initializeUsersTable(2);
                            }
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
                        handleFormErrors(response, 'Users');
                    }
                });
            });

            $(document).on('change', '.permission-checkbox', function(){
                if ($(this).is(':disabled')) {
                    return;
                }

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

            syncCustomPermissionVisibility('#add-user-form');
            renderPermissionSummary('#edit-custom-permission-summary', [], 'No custom permissions selected.');
            renderResourceAccessSummary('#edit-resource-access-summary', {});
        });
    </script>
@endsection
