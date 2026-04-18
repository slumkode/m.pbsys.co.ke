<?php
// file: app/Http/Controllers/UserManagementController.php
namespace App\Http\Controllers;

use App\Models\Permission;
use App\Models\Role;
use App\Models\Service;
use App\Models\ServiceAccountKeyword;
use App\Models\Shortcode;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

class UserManagementController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        $authUser = $this->requireActionPermission($request, 'users', 'view');

        return view('admin.modules.users', $this->baseViewData($request, $this->userManagementReferenceData($authUser)));
    }

    public function roles(Request $request)
    {
        $authUser = $this->requireActionPermission($request, 'users', 'manage_roles');

        return view('admin.modules.roles', $this->baseViewData($request, [
            'permissions' => User::permissionCatalog(),
        ]));
    }

    public function keywords(Request $request)
    {
        $authUser = $this->requireActionPermission($request, 'users', 'update');
        abort_if(! User::supportsAccountKeywordAccess(), 422, 'Keyword access is not available until the latest keyword access migrations are run.');

        return view('admin.modules.keywords', $this->baseViewData($request, $this->keywordManagementReferenceData($authUser)));
    }

    public function saveKeyword(Request $request)
    {
        $authUser = $this->requireActionPermission($request, 'users', 'update');
        abort_if(! User::supportsAccountKeywordAccess(), 422, 'Keyword access is not available until the latest keyword access migrations are run.');

        $validatedData = $request->validate($this->keywordValidationRules());
        $amountRangeError = $this->keywordAmountRangeError($request);

        if ($amountRangeError) {
            return [
                'status' => false,
                'msg' => $amountRangeError,
                'header' => 'Keywords',
            ];
        }

        $keyword = $request->filled('id')
            ? ServiceAccountKeyword::with(['service.shortcode', 'users'])->findOrFail((int) $request->input('id'))
            : new ServiceAccountKeyword();
        $oldValues = $keyword->exists ? $this->keywordAuditData($keyword) : [];
        $allowedServiceIds = $this->keywordDefaultServiceOptions($authUser)->pluck('id')->map(function ($id) {
            return (int) $id;
        })->all();
        $serviceId = (int) $validatedData['service_id'];

        abort_if(! in_array($serviceId, $allowedServiceIds, true), 403, 'You do not have permission to manage a keyword for this service.');

        if ($keyword->exists) {
            $allowedKeywordIds = $this->accountKeywordOptions($authUser)->pluck('id')->map(function ($id) {
                return (int) $id;
            })->all();

            abort_if(! in_array((int) $keyword->id, $allowedKeywordIds, true), 403, 'You do not have permission to update this keyword.');
        }

        $keyword->service_id = $serviceId;
        $keyword->keyword_name = trim($validatedData['keyword_name']);
        $keyword->match_type = $this->normalizedKeywordMatchTypes($request->input('match_types', []));
        $keyword->match_pattern = trim($validatedData['match_pattern']);
        $keyword->status = true;
        $keyword->save();

        $this->syncKeywordAssignments(
            $authUser,
            $keyword,
            $request->input('user_ids', []),
            $request->input('user_limits', [])
        );

        $keyword->load(['service.shortcode', 'users']);

        $this->recordAudit($request, $oldValues ? 'updated' : 'created', $keyword, $oldValues, $this->keywordAuditData($keyword), [
            'page_name' => 'Keywords',
            'auditable_type' => 'Keyword',
            'auditable_label' => $keyword->keyword_name,
        ]);

        return [
            'status' => true,
            'msg' => 'Keyword saved successfully.',
            'header' => 'Keywords',
        ];
    }

    public function deleteKeyword(Request $request)
    {
        $authUser = $this->requireActionPermission($request, 'users', 'update');
        abort_if(! User::supportsAccountKeywordAccess(), 422, 'Keyword access is not available until the latest keyword access migrations are run.');

        $validatedData = $request->validate([
            'id' => ['required', 'integer', 'exists:service_account_keywords,id'],
        ]);
        $keyword = ServiceAccountKeyword::with(['service.shortcode', 'users'])->findOrFail((int) $validatedData['id']);
        $allowedKeywordIds = $this->accountKeywordOptions($authUser)->pluck('id')->map(function ($id) {
            return (int) $id;
        })->all();

        abort_if(! in_array((int) $keyword->id, $allowedKeywordIds, true), 403, 'You do not have permission to delete this keyword.');

        $oldValues = $this->keywordAuditData($keyword);
        $this->deleteAccountKeywordRule($keyword);

        $this->recordAudit($request, 'deleted', $keyword, $oldValues, [], [
            'page_name' => 'Keywords',
            'auditable_type' => 'Keyword',
            'auditable_id' => $keyword->id,
            'auditable_label' => $oldValues['keyword_name'] ?? $keyword->keyword_name,
        ]);

        return [
            'status' => true,
            'msg' => 'Keyword deleted successfully.',
            'header' => 'Keywords',
        ];
    }

    public function datatable(Request $request)
    {
        $authUser = $this->requireActionPermission($request, 'users', 'view');
        $hasCustomPermissionsRoute = Route::has('users.custom-permissions.update');
        $hasResourceAccessRoute = Route::has('users.resource-access.update');
        $columns = [
            0 => 'id',
            1 => 'name',
            2 => 'username',
            3 => 'email',
            4 => 'id',
            5 => 'status',
        ];

        $baseQuery = User::query();
        $totalData = (clone $baseQuery)->count();
        $search = trim((string) $request->input('search.value'));
        $limit = (int) $request->input('length', 10);
        $start = (int) $request->input('start', 0);
        $order = $columns[$request->input('order.0.column', 0)] ?? 'id';
        $dir = strtolower((string) $request->input('order.0.dir', 'desc')) === 'asc' ? 'asc' : 'desc';

        $eagerLoads = ['shortcodes.service'];

        if (User::supportsServiceOwnership()) {
            $eagerLoads[] = 'services.shortcode.user';
        }

        if (User::supportsShortcodeVisibilityAssignments()) {
            $eagerLoads[] = 'sharedShortcodeAccess.user';
        }

        if (User::supportsServiceVisibilityAssignments()) {
            $eagerLoads[] = 'serviceAccess.shortcode.user';
        }

        $filteredQuery = User::with($eagerLoads);

        if (User::usesAdvancedAccessControl()) {
            $filteredQuery->with(['assignedRoles.permissions', 'customPermissions']);
        }

        if ($search !== '') {
            $filteredQuery->where(function ($query) use ($search) {
                $query->where('name', 'LIKE', "%{$search}%")
                    ->orWhere('username', 'LIKE', "%{$search}%")
                    ->orWhere('email', 'LIKE', "%{$search}%")
                    ->orWhere('status', 'LIKE', "%{$search}%");
            });
        }

        $totalFiltered = (clone $filteredQuery)->count();

        $users = $filteredQuery->offset($start)
            ->limit($limit)
            ->orderBy($order, $dir)
            ->get();

        $data = [];

        foreach ($users as $user) {
            $primaryRole = $user->primaryRole();
            $roleName = $user->primaryRoleName();
            $customPermissions = $this->customPermissionSummary($user);
            $visibility = $this->visibilitySummary($user);
            $ownership = $this->ownershipSummary($user);
            $isSuperAdminUser = $user->hasSuperAdminRole();
            $rolePermissionSlugs = $primaryRole ? $this->rolePermissionSlugs($primaryRole) : [];
            $userPayload = base64_encode(json_encode([
                'id' => $user->id,
                'name' => $user->name,
                'username' => $user->username,
                'email' => $user->email,
                'status' => (int) $user->status,
                'role_id' => optional($primaryRole)->id,
                'role_name' => optional($primaryRole)->name,
                'role_description' => optional($primaryRole)->description,
                'role_slug' => optional($primaryRole)->slug,
                'is_superadmin' => $isSuperAdminUser,
                'role_permissions' => $isSuperAdminUser ? User::permissionSlugs() : $rolePermissionSlugs,
                'custom_permissions' => $isSuperAdminUser ? [] : $user->customPermissionOverrideSlugs(),
                'effective_permissions' => $user->effectivePermissionSlugs(),
                'shared_shortcode_access' => $user->sharedShortcodeAccessIds(),
                'service_access' => $user->grantedServiceIds(),
                'service_amount_limits' => $user->serviceAmountLimitsByServiceId(),
            ]));

            $statusBadge = $user->status
                ? '<span class="badge badge-success">Active</span>'
                : '<span class="badge badge-default">Inactive</span>';

            $actions = '';

            if ($authUser->hasPermission('users.update')) {
                $actions .= '<a href="javascript:;" class="text-dark mr-3 edit-user" data-user="'.$userPayload.'" title="Edit User"><i class="fas fa-edit"></i></a>';
            }

            if ($authUser->hasPermission('users.manage_roles')) {
                $actions .= '<a href="javascript:;" class="text-info mr-3 view-role-permissions" data-user="'.$userPayload.'" title="View Role Permissions"><i class="fas fa-shield-alt"></i></a>';
            }

            if (! $isSuperAdminUser && $authUser->hasPermission('users.manage_permissions') && $hasCustomPermissionsRoute) {
                $actions .= '<a href="javascript:;" class="text-primary mr-3 manage-custom-permissions" data-user="'.$userPayload.'" title="Manage Custom Permissions"><i class="fas fa-key"></i></a>';
            }

            if ($authUser->hasPermission('users.update') && $hasResourceAccessRoute && $this->supportsUserResourceAccessManagement()) {
                $actions .= '<a href="javascript:;" class="text-secondary mr-3 manage-resource-access" data-user="'.$userPayload.'" title="Manage Resource Access"><i class="fas fa-project-diagram"></i></a>';
            }

            if ($authUser->hasPermission('users.update')) {
                $actions .= '<a href="javascript:;" class="text-dark mr-3 toggle-user-status" data-id="'.$user->id.'" data-status="'.$user->status.'"><i class="fas fa-power-off"></i></a>';
            }

            if ($authUser->hasPermission('users.delete') && $authUser->id !== $user->id) {
                $actions .= '<a href="javascript:;" class="text-danger delete-user" data-id="'.$user->id.'" data-name="'.e($user->name).'"><i class="fas fa-trash"></i></a>';
            }

            $data[] = [
                'id' => $user->id,
                'name' => e($user->name),
                'username' => e($user->username),
                'email' => e($user->email),
                'role' => e($roleName),
                'status' => $statusBadge,
                'visibility' => e($visibility),
                'permissions' => e($customPermissions),
                'ownership' => e($ownership),
                'action' => $actions ?: '<span class="text-muted">No actions</span>',
            ];
        }

        return response()->json([
            'draw' => intval($request->input('draw')),
            'recordsTotal' => intval($totalData),
            'recordsFiltered' => intval($totalFiltered),
            'data' => $data,
        ]);
    }

    public function rolesDatatable(Request $request)
    {
        $authUser = $this->requireActionPermission($request, 'users', 'manage_roles');
        $columns = [
            0 => 'name',
            1 => 'description',
            2 => 'name',
            3 => 'name',
        ];

        $baseQuery = Role::query();
        $totalData = (clone $baseQuery)->count();
        $search = trim((string) $request->input('search.value'));
        $limit = (int) $request->input('length', 10);
        $start = (int) $request->input('start', 0);
        $order = $columns[$request->input('order.0.column', 0)] ?? 'name';
        $dir = strtolower((string) $request->input('order.0.dir', 'asc')) === 'desc' ? 'desc' : 'asc';

        $filteredQuery = Role::with(['permissions', 'users']);

        if ($search !== '') {
            $filteredQuery->where(function ($query) use ($search) {
                $query->where('name', 'LIKE', "%{$search}%")
                    ->orWhere('description', 'LIKE', "%{$search}%")
                    ->orWhere('slug', 'LIKE', "%{$search}%");
            });
        }

        $totalFiltered = (clone $filteredQuery)->count();

        $roles = $filteredQuery->offset($start)
            ->limit($limit)
            ->orderBy($order, $dir)
            ->get();

        $data = [];

        foreach ($roles as $role) {
            $rolePayload = base64_encode(json_encode([
                'id' => $role->id,
                'name' => $role->name,
                'description' => $role->description,
                'is_system' => (bool) $role->is_system,
                'slug' => $role->slug,
                'permissions' => $role->slug === 'superadmin'
                    ? User::permissionSlugs()
                    : $this->rolePermissionSlugs($role),
            ]));

            $actions = $role->slug === 'superadmin'
                ? '<span class="text-muted">Not editable</span>'
                : '<a href="javascript:;" class="text-dark edit-role" data-role="'.$rolePayload.'" title="Edit Role"><i class="fas fa-edit"></i></a>';

            $data[] = [
                'name' => e($role->name),
                'description' => e($role->description ?: 'No description'),
                'permissions' => e($this->rolePermissionSummary($role)),
                'users' => (int) $role->users->count(),
                'action' => $actions,
            ];
        }

        return response()->json([
            'draw' => intval($request->input('draw')),
            'recordsTotal' => intval($totalData),
            'recordsFiltered' => intval($totalFiltered),
            'data' => $data,
        ]);
    }

    public function store(Request $request)
    {
        $authUser = $this->requireActionPermission($request, 'users', 'create');
        $validatedData = $request->validate($this->userValidationRules());

        $this->assertRoleAndPermissionManagementAccess($request, $authUser);
        $selectedRoleId = $authUser->hasPermission('users.manage_roles') && $request->filled('role_id')
            ? (int) $request->input('role_id')
            : null;
        $selectedCustomPermissions = $authUser->hasPermission('users.manage_permissions')
            ? $this->filterCustomPermissionsForRole($selectedRoleId, $request->input('custom_permissions', []))
            : [];

        $payload = [
            'name' => trim($validatedData['name']),
            'username' => trim($validatedData['username']),
            'email' => trim($validatedData['email']),
            'password' => Hash::make($validatedData['password']),
            'status' => 1,
        ];

        $user = User::create($payload);

        $user->syncPrimaryRole($selectedRoleId);
        $user->syncCustomPermissions($selectedCustomPermissions);
        $user->refresh();

        $this->recordAudit($request, 'created', $user, [], $this->userAuditData($user), [
            'page_name' => 'Users',
        ]);

        return [
            'status' => true,
            'msg' => 'User created successfully.',
            'header' => 'Users',
        ];
    }

    public function update(Request $request)
    {
        $authUser = $this->requireActionPermission($request, 'users', 'update');
        $user = User::findOrFail((int) $request->input('id'));
        $validatedData = $request->validate($this->userValidationRules($user->id, true));

        $this->assertRoleAndPermissionManagementAccess($request, $authUser);

        $oldValues = $this->userAuditData($user);
        $selectedRoleId = $authUser->hasPermission('users.manage_roles')
            ? ($request->filled('role_id') ? (int) $request->input('role_id') : null)
            : optional($user->primaryRole())->id;
        $selectedCustomPermissions = $authUser->hasPermission('users.manage_permissions')
            ? $this->filterCustomPermissionsForRole(
                $selectedRoleId,
                $request->has('custom_permissions')
                    ? $request->input('custom_permissions', [])
                    : $user->assignedCustomPermissionSlugs()
            )
            : $user->assignedCustomPermissionSlugs();

        if (
            $authUser->id === $user->id
            && ! $this->selectionIncludesPermission($selectedRoleId, $selectedCustomPermissions, 'users.view')
            && ! $this->selectionIncludesPermission($selectedRoleId, $selectedCustomPermissions, 'users.manage_roles')
        ) {
            return [
                'status' => false,
                'msg' => 'You cannot remove your own access to the user management page.',
                'header' => 'Users',
            ];
        }

        $user->name = trim($validatedData['name']);
        $user->username = trim($validatedData['username']);
        $user->email = trim($validatedData['email']);

        if (! empty($validatedData['password'])) {
            $user->password = Hash::make($validatedData['password']);
        }

        $user->save();

        if ($authUser->hasPermission('users.manage_roles')) {
            $user->syncPrimaryRole($selectedRoleId);
        }

        if ($authUser->hasPermission('users.manage_permissions')) {
            $user->syncCustomPermissions($selectedCustomPermissions);
        }

        $user->refresh();

        $this->recordAudit($request, 'updated', $user, $oldValues, $this->userAuditData($user), [
            'page_name' => 'Users',
        ]);

        return [
            'status' => true,
            'msg' => 'User updated successfully.',
            'header' => 'Users',
        ];
    }

    public function updateResourceAccess(Request $request)
    {
        $authUser = $this->requireActionPermission($request, 'users', 'update');
        abort_if(! $this->supportsUserResourceAccessManagement(), 422, 'Specific resource access is not available until the latest access-control migrations are run.');

        $validatedData = $request->validate($this->userResourceAccessValidationRules());
        $user = User::findOrFail((int) $validatedData['id']);
        $amountRangeError = $this->resourceAmountRangeError($request);

        if ($amountRangeError) {
            return [
                'status' => false,
                'msg' => $amountRangeError,
                'header' => 'Users',
            ];
        }

        $oldValues = $this->userAuditData($user);

        $this->syncUserResourceAccess(
            $authUser,
            $user,
            $request->input('shared_shortcode_access', []),
            $request->input('service_access', []),
            $request->input('service_amount_limits', [])
        );

        $user->refresh();

        $this->recordAudit($request, 'resource_access_updated', $user, $oldValues, $this->userAuditData($user), [
            'page_name' => 'Users',
        ]);

        return [
            'status' => true,
            'msg' => 'Specific resource access updated successfully.',
            'header' => 'Users',
        ];
    }

    public function updateCustomPermissions(Request $request)
    {
        $authUser = $this->requireActionPermission($request, 'users', 'manage_permissions');
        $validatedData = $request->validate([
            'id' => ['required', 'integer', 'exists:users,id'],
            'custom_permissions' => ['nullable', 'array'],
            'custom_permissions.*' => ['in:'.implode(',', User::permissionSlugs())],
        ]);

        $user = User::findOrFail((int) $validatedData['id']);
        $oldValues = $this->userAuditData($user);

        if ($user->hasSuperAdminRole()) {
            $user->syncCustomPermissions([]);

            return [
                'status' => false,
                'msg' => 'Superadmin already has full access. Custom permissions are not used for this role.',
                'header' => 'Users',
            ];
        }

        $selectedRoleId = optional($user->primaryRole())->id;
        $selectedCustomPermissions = $this->filterCustomPermissionsForRole(
            $selectedRoleId,
            $request->input('custom_permissions', [])
        );

        if (
            $authUser->id === $user->id
            && ! $this->selectionIncludesPermission($selectedRoleId, $selectedCustomPermissions, 'users.view')
            && ! $this->selectionIncludesPermission($selectedRoleId, $selectedCustomPermissions, 'users.manage_roles')
        ) {
            return [
                'status' => false,
                'msg' => 'You cannot remove your own access to the user management page.',
                'header' => 'Users',
            ];
        }

        $user->syncCustomPermissions($selectedCustomPermissions);
        $user->refresh();

        $this->recordAudit($request, 'custom_permissions_updated', $user, $oldValues, $this->userAuditData($user), [
            'page_name' => 'Users',
        ]);

        return [
            'status' => true,
            'msg' => 'Custom permissions updated successfully.',
            'header' => 'Users',
        ];
    }

    public function toggleStatus(Request $request)
    {
        $authUser = $this->requireActionPermission($request, 'users', 'update');
        $user = User::findOrFail((int) $request->input('id'));

        if ($authUser->id === $user->id) {
            return [
                'status' => false,
                'msg' => 'You cannot disable your own account.',
                'header' => 'Users',
            ];
        }

        $oldValues = $this->userAuditData($user);
        $user->status = ! $user->status;
        $user->save();

        $this->recordAudit($request, 'status_changed', $user, $oldValues, $this->userAuditData($user), [
            'page_name' => 'Users',
        ]);

        return [
            'status' => true,
            'msg' => 'User status updated successfully.',
            'header' => 'Users',
        ];
    }

    public function destroy(Request $request)
    {
        $authUser = $this->requireActionPermission($request, 'users', 'delete');
        abort_if(
            ! Schema::hasColumn('users', 'deleted_at')
            || ! Schema::hasColumn('shortcodes', 'deleted_at')
            || ! Schema::hasColumn('services', 'deleted_at'),
            422,
            'Run the latest migrations before deleting users so restore can work safely.'
        );

        $relations = ['shortcodes.service'];

        if (User::supportsServiceOwnership()) {
            $relations[] = 'services.shortcode';
        }

        $user = User::with($relations)->findOrFail((int) $request->input('id'));

        if ($authUser->id === $user->id) {
            return [
                'status' => false,
                'msg' => 'You cannot delete your own account.',
                'header' => 'Users',
            ];
        }

        $oldValues = $this->userAuditData($user, true);
        $restorePayload = $this->userRestorePayload($user);
        $shortcodeIds = $user->shortcodes->pluck('id')->all();
        $serviceIds = array_values(array_unique(array_merge(
            $user->shortcodes->pluck('service')->flatten()->pluck('id')->all(),
            User::supportsServiceOwnership() ? $user->services->pluck('id')->all() : []
        )));

        DB::transaction(function () use ($request, $user, $shortcodeIds, $serviceIds, $oldValues, $restorePayload) {
            if (! empty($serviceIds)) {
                Service::whereIn('id', $serviceIds)->delete();
            }

            if (! empty($shortcodeIds)) {
                Shortcode::whereIn('id', $shortcodeIds)->delete();
            }

            $user->delete();

            $this->recordAudit($request, 'deleted', $user, $oldValues, [], [
                'page_name' => 'Users',
                'auditable_id' => $user->id,
                'auditable_label' => $user->name,
                'is_restorable' => true,
                'restore_payload' => $restorePayload,
            ]);
        });

        return [
            'status' => true,
            'msg' => 'User deleted successfully. Linked owned shortcodes and services were soft-deleted and can be restored from Audit Logs.',
            'header' => 'Users',
        ];
    }

    public function storeRole(Request $request)
    {
        $this->requireActionPermission($request, 'users', 'manage_roles');
        abort_if(! User::usesAdvancedAccessControl(), 422, 'Advanced role management is not available until migrations are run.');

        $validatedData = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:roles,name'],
            'description' => ['nullable', 'string', 'max:1000'],
            'role_permissions' => ['nullable', 'array'],
            'role_permissions.*' => ['in:'.implode(',', User::permissionSlugs())],
        ]);

        $role = Role::create([
            'name' => trim($validatedData['name']),
            'slug' => $this->roleSlug($validatedData['name']),
            'description' => trim((string) $request->input('description')),
            'is_system' => false,
        ]);

        $this->syncRolePermissions($role, $request->input('role_permissions', []));
        $role->load('permissions');

        $this->recordAudit($request, 'created', $role, [], $this->roleAuditData($role), [
            'page_name' => 'Roles',
            'auditable_type' => 'Role',
        ]);

        return [
            'status' => true,
            'msg' => 'Role created successfully.',
            'header' => 'Roles',
        ];
    }

    public function updateRole(Request $request)
    {
        $this->requireActionPermission($request, 'users', 'manage_roles');
        abort_if(! User::usesAdvancedAccessControl(), 422, 'Advanced role management is not available until migrations are run.');

        $role = Role::with('permissions')->findOrFail((int) $request->input('id'));
        abort_if($role->slug === 'superadmin', 422, 'The Superadmin role is fixed and cannot be edited.');
        $validatedData = $request->validate([
            'id' => ['required', 'integer', 'exists:roles,id'],
            'name' => ['required', 'string', 'max:255', 'unique:roles,name,'.$role->id],
            'description' => ['nullable', 'string', 'max:1000'],
            'role_permissions' => ['nullable', 'array'],
            'role_permissions.*' => ['in:'.implode(',', User::permissionSlugs())],
        ]);

        $oldValues = $this->roleAuditData($role);

        if (! $role->is_system) {
            $role->name = trim($validatedData['name']);
            $role->slug = $this->roleSlug($validatedData['name'], $role->id);
        }

        $role->description = trim((string) $request->input('description'));
        $role->save();
        $this->syncRolePermissions($role, $request->input('role_permissions', []));
        $role->load('permissions');

        $this->recordAudit($request, 'updated', $role, $oldValues, $this->roleAuditData($role), [
            'page_name' => 'Roles',
            'auditable_type' => 'Role',
        ]);

        return [
            'status' => true,
            'msg' => 'Role updated successfully.',
            'header' => 'Roles',
        ];
    }

    protected function userValidationRules($userId = null, $updating = false)
    {
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255', 'unique:users,username'.($userId ? ','.$userId : '')],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'.($userId ? ','.$userId : '')],
            'role_id' => ['nullable'],
            'custom_permissions' => ['nullable', 'array'],
            'custom_permissions.*' => ['in:'.implode(',', User::permissionSlugs())],
        ];

        if ($updating) {
            $rules['id'] = ['required', 'integer', 'exists:users,id'];
            $rules['password'] = ['nullable', 'string', 'min:8', 'confirmed'];
        } else {
            $rules['password'] = ['required', 'string', 'min:8', 'confirmed'];
        }

        if (User::usesAdvancedAccessControl()) {
            $rules['role_id'][] = 'integer';
            $rules['role_id'][] = 'exists:roles,id';
        }

        return $rules;
    }

    protected function userResourceAccessValidationRules()
    {
        $rules = [
            'id' => ['required', 'integer', 'exists:users,id'],
            'shared_shortcode_access' => ['nullable', 'array'],
            'shared_shortcode_access.*' => ['integer', 'exists:shortcodes,id'],
            'service_access' => ['nullable', 'array'],
            'service_access.*' => ['integer', 'exists:services,id'],
        ];

        if (User::supportsTransactionAmountLimits()) {
            $rules['service_amount_limits'] = ['nullable', 'array'];
            $rules['service_amount_limits.*.min'] = ['nullable', 'numeric', 'min:0'];
            $rules['service_amount_limits.*.max'] = ['nullable', 'numeric', 'min:0'];

            if (User::supportsTransactionAmountLimitHistoryBypass()) {
                $rules['service_amount_limits.*.bypass_history'] = ['nullable', 'boolean'];
            }
        }

        return $rules;
    }

    protected function keywordValidationRules()
    {
        return [
            'id' => ['nullable', 'integer', 'exists:service_account_keywords,id'],
            'service_id' => ['required', 'integer', 'exists:services,id'],
            'keyword_name' => ['required', 'string', 'max:255'],
            'match_types' => ['required', 'array', 'min:1'],
            'match_types.*' => ['in:contains,starts_with,ends_with,exact,regex'],
            'match_pattern' => ['required', 'string', 'max:1000'],
            'user_ids' => ['nullable', 'array'],
            'user_ids.*' => ['integer', 'exists:users,id'],
            'user_limits' => ['nullable', 'array'],
            'user_limits.*.min' => ['nullable', 'numeric', 'min:0'],
            'user_limits.*.max' => ['nullable', 'numeric', 'min:0'],
            'user_limits.*.bypass_history' => ['nullable', 'boolean'],
        ];
    }

    protected function resourceAmountRangeError(Request $request)
    {
        if (! User::supportsTransactionAmountLimits()) {
            return null;
        }

        foreach ($request->input('service_amount_limits', []) as $serviceId => $limits) {
            $min = $limits['min'] ?? null;
            $max = $limits['max'] ?? null;

            if ($min === null || $min === '' || $max === null || $max === '') {
                continue;
            }

            if ((float) $min > (float) $max) {
                return 'Service #'.(int) $serviceId.' has a minimum transaction amount greater than its maximum amount.';
            }
        }

        return null;
    }

    protected function keywordAmountRangeError(Request $request)
    {
        foreach ($request->input('user_limits', []) as $userId => $limits) {
            $min = $limits['min'] ?? null;
            $max = $limits['max'] ?? null;

            if ($min === null || $min === '' || $max === null || $max === '') {
                continue;
            }

            if ((float) $min > (float) $max) {
                return 'User #'.(int) $userId.' has a minimum transaction amount greater than its maximum amount.';
            }
        }

        return null;
    }

    protected function assertRoleAndPermissionManagementAccess(Request $request, User $authUser)
    {
        if ($request->filled('role_id') && ! $authUser->hasPermission('users.manage_roles')) {
            abort(403, 'You do not have permission to assign roles.');
        }

        if (! empty($request->input('custom_permissions', [])) && ! $authUser->hasPermission('users.manage_permissions')) {
            abort(403, 'You do not have permission to assign custom user permissions.');
        }
    }

    protected function syncRolePermissions(Role $role, array $permissionSlugs = [])
    {
        $selectedSlugs = array_values(array_intersect(User::permissionSlugs(), $permissionSlugs));

        if ($role->slug === 'customer-client') {
            $selectedSlugs = array_values(array_diff($selectedSlugs, User::clientScopedRestrictedPermissionSlugs()));
        }

        $permissionIds = Permission::whereIn('slug', $selectedSlugs)->pluck('id')->all();
        $role->permissions()->sync($permissionIds);

        if (! empty($permissionIds) && Schema::hasTable('permission_user') && Schema::hasTable('role_user')) {
            $userIds = DB::table('role_user')
                ->where('role_id', $role->id)
                ->pluck('user_id')
                ->map(function ($userId) {
                    return (int) $userId;
                })
                ->values()
                ->all();

            if (! empty($userIds)) {
                DB::table('permission_user')
                    ->whereIn('user_id', $userIds)
                    ->whereIn('permission_id', $permissionIds)
                    ->delete();
            }
        }
    }

    protected function filterCustomPermissionsForRole($roleId, array $customPermissions = [])
    {
        $selectedSlugs = array_values(array_intersect(User::permissionSlugs(), $customPermissions));

        if (! User::usesAdvancedAccessControl() || ! $roleId) {
            return $selectedSlugs;
        }

        $role = Role::whereKey((int) $roleId)
            ->with('permissions:id,slug')
            ->first();

        if (! $role) {
            return $selectedSlugs;
        }

        if ($role->slug === 'superadmin') {
            return [];
        }

        if ($role->slug === 'customer-client') {
            $selectedSlugs = array_values(array_diff($selectedSlugs, User::clientScopedRestrictedPermissionSlugs()));
        }

        return array_values(array_diff($selectedSlugs, $role->permissions->pluck('slug')->all()));
    }

    protected function selectionIncludesPermission($roleId, array $customPermissions, $permissionSlug)
    {
        if (! User::usesAdvancedAccessControl()) {
            return true;
        }

        $rolePermissions = [];

        if ($roleId) {
            $role = Role::whereKey($roleId)
                ->with('permissions:id,slug')
                ->first();

            if ($role && $role->slug === 'superadmin') {
                return true;
            }

            $rolePermissions = $role
                ? $role->permissions->pluck('slug')->all()
                : [];
        }

        return in_array($permissionSlug, array_unique(array_merge($rolePermissions, $customPermissions)), true);
    }

    protected function roleSlug($name, $ignoreRoleId = null)
    {
        $baseSlug = trim(strtolower(preg_replace('/[^a-z0-9]+/i', '-', $name)), '-');
        $baseSlug = $baseSlug === '' ? 'role' : $baseSlug;
        $slug = $baseSlug;
        $counter = 1;

        while (Role::when($ignoreRoleId, function ($query) use ($ignoreRoleId) {
            $query->where('id', '!=', $ignoreRoleId);
        })->where('slug', $slug)->exists()) {
            $slug = $baseSlug.'-'.$counter;
            $counter++;
        }

        return $slug;
    }

    protected function userManagementReferenceData(User $authUser)
    {
        return [
            'permissions' => User::permissionCatalog(),
            'roles' => User::usesAdvancedAccessControl()
                ? Role::with('permissions')->orderBy('name')->get()
                : collect(),
            'canManageRoles' => $authUser->hasPermission('users.manage_roles'),
            'canManageCustomPermissions' => $authUser->hasPermission('users.manage_permissions'),
            'canManageResourceAccess' => $authUser->hasPermission('users.update') && $this->supportsUserResourceAccessManagement(),
            'sharedShortcodes' => $this->sharedShortcodeOptions($authUser),
            'sharedServices' => $this->sharedServiceOptions($authUser),
        ];
    }

    protected function keywordManagementReferenceData(User $authUser)
    {
        return [
            'keywords' => $this->accountKeywordOptions($authUser)->load(['service.shortcode', 'service.user', 'users']),
            'keywordDefaultServices' => $this->keywordDefaultServiceOptions($authUser),
            'keywordUsers' => $this->keywordAssignableUsers($authUser),
        ];
    }

    protected function supportsUserResourceAccessManagement()
    {
        return User::supportsShortcodeVisibilityAssignments()
            || User::supportsServiceVisibilityAssignments();
    }

    protected function keywordAssignableUsers(User $authUser)
    {
        return User::orderBy('name')
            ->get(['id', 'name', 'username', 'email', 'status']);
    }

    protected function sharedShortcodeOptions(User $authUser)
    {
        if (! User::supportsShortcodeSharing()) {
            return collect();
        }

        return Shortcode::with('user')
            ->where('sharing_mode', 'shared')
            ->when(! $authUser->canViewAllShortcodes(), function ($query) use ($authUser) {
                $query->whereIn('id', $authUser->manageableShortcodeIds());
            })
            ->orderBy('shortcode')
            ->get();
    }

    protected function sharedServiceOptions(User $authUser)
    {
        if (! User::supportsServiceVisibilityAssignments()) {
            return collect();
        }

        return Service::with(['shortcode.user', 'user'])
            ->when(! $authUser->canViewAllServices(), function ($query) use ($authUser) {
                $query->whereIn('id', $authUser->viewableServiceIds());
            })
            ->orderBy('service_name')
            ->get();
    }

    protected function accountKeywordOptions(User $authUser)
    {
        if (! User::supportsAccountKeywordAccess()) {
            return collect();
        }

        return ServiceAccountKeyword::with(['service.shortcode', 'service.user'])
            ->where('status', 1)
            ->whereHas('service', function ($query) use ($authUser) {
                $query->where(function ($defaultQuery) {
                    $defaultQuery->whereNull('prefix')
                        ->orWhere('prefix', '');
                });

                if (! $authUser->canViewAllServices()) {
                    $query->whereIn('id', $authUser->viewableServiceIds());
                }
            })
            ->orderBy('keyword_name')
            ->get();
    }

    protected function keywordDefaultServiceOptions(User $authUser)
    {
        if (! User::supportsAccountKeywordAccess()) {
            return collect();
        }

        return Service::with(['shortcode.user', 'user'])
            ->where(function ($query) {
                $query->whereNull('prefix')
                    ->orWhere('prefix', '');
            })
            ->when(! $authUser->canViewAllServices(), function ($query) use ($authUser) {
                $query->whereIn('id', $authUser->viewableServiceIds());
            })
            ->orderBy('service_name')
            ->get();
    }

    protected function syncUserResourceAccess(User $authUser, User $user, array $shortcodeIds = [], array $serviceIds = [], array $serviceAmountLimits = [])
    {
        if (User::supportsShortcodeVisibilityAssignments()) {
            $assignableShortcodeIds = $this->sharedShortcodeOptions($authUser)->pluck('id')->map(function ($id) {
                return (int) $id;
            })->all();

            $validShortcodeIds = Shortcode::query()
                ->whereIn('id', array_map('intval', $shortcodeIds))
                ->whereIn('id', $assignableShortcodeIds)
                ->when(User::supportsShortcodeSharing(), function ($query) {
                    $query->where('sharing_mode', 'shared');
                })
                ->where('user_id', '!=', $user->id)
                ->pluck('id')
                ->all();

            $user->sharedShortcodeAccess()->sync($validShortcodeIds);
        }

        if (User::supportsServiceVisibilityAssignments()) {
            $currentServiceIds = $user->serviceAccess()->pluck('services.id')->map(function ($id) {
                return (int) $id;
            })->all();
            $assignableServiceIds = $this->sharedServiceOptions($authUser)->pluck('id')->map(function ($id) {
                return (int) $id;
            })->all();

            $validServiceIds = Service::query()
                ->whereIn('id', array_map('intval', $serviceIds))
                ->whereIn('id', $assignableServiceIds)
                ->when(User::supportsShortcodeSharing(), function ($query) {
                    $query->whereHas('shortcode', function ($shortcodeQuery) {
                        $shortcodeQuery->where('sharing_mode', 'shared');
                    });
                })
                ->where(function ($query) use ($user) {
                    $query->whereNull('user_id')
                        ->orWhere('user_id', '!=', $user->id);
                })
                ->pluck('id')
                ->all();

            $ownedServiceIds = User::supportsServiceOwnership()
                ? $user->services()->pluck('id')->map(function ($id) {
                    return (int) $id;
                })->all()
                : [];
            $syncServiceIds = array_values(array_unique(array_merge(
                array_map('intval', $validServiceIds),
                array_map('intval', $ownedServiceIds)
            )));

            $existingLimits = User::supportsTransactionAmountLimits()
                ? DB::table('service_user_access')
                    ->where('user_id', $user->id)
                    ->whereIn('service_id', $syncServiceIds)
                    ->get()
                    ->keyBy('service_id')
                : collect();
            $syncPayload = [];

            foreach ($syncServiceIds as $serviceId) {
                if (! User::supportsTransactionAmountLimits()) {
                    $syncPayload[$serviceId] = [];
                    continue;
                }

                $submittedLimits = $serviceAmountLimits[$serviceId] ?? [];
                $existingLimit = $existingLimits->get($serviceId);
                $syncPayload[$serviceId] = [
                    'transaction_min_amount' => array_key_exists('min', $submittedLimits)
                        ? $this->nullableAmount($submittedLimits['min'])
                        : ($existingLimit ? $existingLimit->transaction_min_amount : null),
                    'transaction_max_amount' => array_key_exists('max', $submittedLimits)
                        ? $this->nullableAmount($submittedLimits['max'])
                        : ($existingLimit ? $existingLimit->transaction_max_amount : null),
                ];

                if (User::supportsTransactionAmountLimitHistoryBypass()) {
                    $syncPayload[$serviceId]['bypass_amount_limit_history'] = array_key_exists('bypass_history', $submittedLimits)
                        ? (int) $this->truthy($submittedLimits['bypass_history'])
                        : ($existingLimit ? (int) ($existingLimit->bypass_amount_limit_history ?? 0) : 0);
                }
            }

            $user->serviceAccess()->sync($syncPayload);
            $removedServiceIds = array_values(array_diff($currentServiceIds, $syncServiceIds));
            $this->syncServiceAmountLimitHistory($user->id, $syncPayload, $existingLimits, $removedServiceIds);
        }

    }

    protected function syncKeywordAssignments(User $authUser, ServiceAccountKeyword $keyword, array $userIds = [], array $userLimits = [])
    {
        $allowedUserIds = $this->keywordAssignableUsers($authUser)->pluck('id')->map(function ($id) {
            return (int) $id;
        })->all();
        $validUserIds = User::query()
            ->whereIn('id', array_values(array_unique(array_map('intval', $userIds))))
            ->whereIn('id', $allowedUserIds)
            ->pluck('id')
            ->map(function ($id) {
                return (int) $id;
            })
            ->all();
        $existingLimits = DB::table('service_account_keyword_user_access')
            ->where('keyword_id', $keyword->id)
            ->get()
            ->keyBy('user_id');
        $currentUserIds = $existingLimits->keys()
            ->map(function ($id) {
                return (int) $id;
            })
            ->all();
        $removedUserIds = array_values(array_diff($currentUserIds, $validUserIds));
        $now = now();

        if (! empty($removedUserIds)) {
            DB::table('service_account_keyword_user_access')
                ->where('keyword_id', $keyword->id)
                ->whereIn('user_id', $removedUserIds)
                ->delete();

            foreach ($removedUserIds as $removedUserId) {
                $this->syncAccountKeywordLimitHistory((int) $removedUserId, [], collect(), [(int) $keyword->id]);
            }
        }

        foreach ($validUserIds as $userId) {
            $submittedLimits = $userLimits[$userId] ?? [];
            $existingLimit = $existingLimits->get($userId);
            $payload = [
                'keyword_id' => (int) $keyword->id,
                'user_id' => (int) $userId,
                'transaction_min_amount' => array_key_exists('min', $submittedLimits)
                    ? $this->nullableAmount($submittedLimits['min'])
                    : ($existingLimit ? $existingLimit->transaction_min_amount : null),
                'transaction_max_amount' => array_key_exists('max', $submittedLimits)
                    ? $this->nullableAmount($submittedLimits['max'])
                    : ($existingLimit ? $existingLimit->transaction_max_amount : null),
                'bypass_amount_limit_history' => array_key_exists('bypass_history', $submittedLimits)
                    ? (int) $this->truthy($submittedLimits['bypass_history'])
                    : ($existingLimit ? (int) ($existingLimit->bypass_amount_limit_history ?? 0) : 0),
                'created_at' => $existingLimit ? $existingLimit->created_at : $now,
                'updated_at' => $now,
            ];

            DB::table('service_account_keyword_user_access')->updateOrInsert(
                [
                    'keyword_id' => (int) $keyword->id,
                    'user_id' => (int) $userId,
                ],
                [
                    'transaction_min_amount' => $payload['transaction_min_amount'],
                    'transaction_max_amount' => $payload['transaction_max_amount'],
                    'bypass_amount_limit_history' => $payload['bypass_amount_limit_history'],
                    'created_at' => $payload['created_at'],
                    'updated_at' => $payload['updated_at'],
                ]
            );

            $existingForHistory = $existingLimit
                ? collect([$existingLimit])->keyBy('keyword_id')
                : collect();

            $this->syncAccountKeywordLimitHistory((int) $userId, [(int) $keyword->id => $payload], $existingForHistory, []);
        }
    }

    protected function deleteAccountKeywordRule(ServiceAccountKeyword $keyword)
    {
        $now = now();

        $keyword->status = false;
        $keyword->save();

        DB::table('service_account_keyword_user_access')
            ->where('keyword_id', $keyword->id)
            ->delete();

        if (User::supportsAccountKeywordLimitHistory()) {
            DB::table('service_account_keyword_limit_histories')
                ->where('keyword_id', $keyword->id)
                ->whereNull('effective_to')
                ->update([
                    'effective_to' => $now,
                    'updated_at' => $now,
                ]);
        }
    }

    protected function normalizedKeywordMatchTypes($matchTypes)
    {
        $allowed = ['contains', 'starts_with', 'ends_with', 'exact', 'regex'];
        $values = is_array($matchTypes) ? $matchTypes : explode(',', (string) $matchTypes);
        $normalized = collect($values)
            ->map(function ($value) {
                return trim((string) $value);
            })
            ->filter(function ($value) use ($allowed) {
                return in_array($value, $allowed, true);
            })
            ->unique()
            ->values()
            ->all();

        return empty($normalized) ? 'contains' : implode(',', $normalized);
    }

    protected function syncAccountKeywordLimitHistory($userId, array $syncPayload, $existingLimits, array $removedKeywordIds = [])
    {
        if (! User::supportsAccountKeywordLimitHistory()) {
            return;
        }

        $now = now();

        if (! empty($removedKeywordIds)) {
            DB::table('service_account_keyword_limit_histories')
                ->where('user_id', $userId)
                ->whereIn('keyword_id', array_map('intval', $removedKeywordIds))
                ->whereNull('effective_to')
                ->update([
                    'effective_to' => $now,
                    'updated_at' => $now,
                ]);
        }

        foreach ($syncPayload as $keywordId => $limits) {
            $keywordId = (int) $keywordId;
            $existingLimit = $existingLimits->get($keywordId);
            $oldMin = $existingLimit ? $this->nullableAmount($existingLimit->transaction_min_amount) : null;
            $oldMax = $existingLimit ? $this->nullableAmount($existingLimit->transaction_max_amount) : null;
            $newMin = $this->nullableAmount($limits['transaction_min_amount'] ?? null);
            $newMax = $this->nullableAmount($limits['transaction_max_amount'] ?? null);
            $historyExists = DB::table('service_account_keyword_limit_histories')
                ->where('user_id', $userId)
                ->where('keyword_id', $keywordId)
                ->exists();
            $activeHistoryExists = DB::table('service_account_keyword_limit_histories')
                ->where('user_id', $userId)
                ->where('keyword_id', $keywordId)
                ->whereNull('effective_to')
                ->exists();
            $limitChanged = $existingLimit && ($oldMin !== $newMin || $oldMax !== $newMax);

            if (! $historyExists && $existingLimit && $limitChanged) {
                DB::table('service_account_keyword_limit_histories')->insert([
                    'keyword_id' => $keywordId,
                    'user_id' => $userId,
                    'transaction_min_amount' => $oldMin,
                    'transaction_max_amount' => $oldMax,
                    'effective_from' => '2000-01-01 00:00:00',
                    'effective_to' => $now,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            } elseif (! $historyExists && ! $limitChanged) {
                DB::table('service_account_keyword_limit_histories')->insert([
                    'keyword_id' => $keywordId,
                    'user_id' => $userId,
                    'transaction_min_amount' => $newMin,
                    'transaction_max_amount' => $newMax,
                    'effective_from' => '2000-01-01 00:00:00',
                    'effective_to' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                continue;
            }

            if ($historyExists && ! $activeHistoryExists && ! $limitChanged) {
                DB::table('service_account_keyword_limit_histories')->insert([
                    'keyword_id' => $keywordId,
                    'user_id' => $userId,
                    'transaction_min_amount' => $newMin,
                    'transaction_max_amount' => $newMax,
                    'effective_from' => $now,
                    'effective_to' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                continue;
            }

            if (! $limitChanged) {
                continue;
            }

            DB::table('service_account_keyword_limit_histories')
                ->where('user_id', $userId)
                ->where('keyword_id', $keywordId)
                ->whereNull('effective_to')
                ->update([
                    'effective_to' => $now,
                    'updated_at' => $now,
                ]);

            DB::table('service_account_keyword_limit_histories')->insert([
                'keyword_id' => $keywordId,
                'user_id' => $userId,
                'transaction_min_amount' => $newMin,
                'transaction_max_amount' => $newMax,
                'effective_from' => $now,
                'effective_to' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    protected function syncServiceAmountLimitHistory($userId, array $syncPayload, $existingLimits, array $removedServiceIds = [])
    {
        if (! User::supportsServiceAmountLimitHistory()) {
            return;
        }

        $now = now();

        if (! empty($removedServiceIds)) {
            DB::table('service_user_amount_limit_histories')
                ->where('user_id', $userId)
                ->whereIn('service_id', array_map('intval', $removedServiceIds))
                ->whereNull('effective_to')
                ->update([
                    'effective_to' => $now,
                    'updated_at' => $now,
                ]);
        }

        foreach ($syncPayload as $serviceId => $limits) {
            $serviceId = (int) $serviceId;
            $existingLimit = $existingLimits->get($serviceId);
            $oldMin = $existingLimit ? $this->nullableAmount($existingLimit->transaction_min_amount) : null;
            $oldMax = $existingLimit ? $this->nullableAmount($existingLimit->transaction_max_amount) : null;
            $newMin = $this->nullableAmount($limits['transaction_min_amount'] ?? null);
            $newMax = $this->nullableAmount($limits['transaction_max_amount'] ?? null);
            $historyExists = DB::table('service_user_amount_limit_histories')
                ->where('user_id', $userId)
                ->where('service_id', $serviceId)
                ->exists();
            $activeHistoryExists = DB::table('service_user_amount_limit_histories')
                ->where('user_id', $userId)
                ->where('service_id', $serviceId)
                ->whereNull('effective_to')
                ->exists();
            $limitChanged = $existingLimit && ($oldMin !== $newMin || $oldMax !== $newMax);

            if (! $historyExists && $existingLimit && $limitChanged) {
                DB::table('service_user_amount_limit_histories')->insert([
                    'service_id' => $serviceId,
                    'user_id' => $userId,
                    'transaction_min_amount' => $oldMin,
                    'transaction_max_amount' => $oldMax,
                    'effective_from' => '2000-01-01 00:00:00',
                    'effective_to' => $now,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            } elseif (! $historyExists && ! $limitChanged) {
                DB::table('service_user_amount_limit_histories')->insert([
                    'service_id' => $serviceId,
                    'user_id' => $userId,
                    'transaction_min_amount' => $newMin,
                    'transaction_max_amount' => $newMax,
                    'effective_from' => '2000-01-01 00:00:00',
                    'effective_to' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                continue;
            }

            if ($historyExists && ! $activeHistoryExists && ! $limitChanged) {
                DB::table('service_user_amount_limit_histories')->insert([
                    'service_id' => $serviceId,
                    'user_id' => $userId,
                    'transaction_min_amount' => $newMin,
                    'transaction_max_amount' => $newMax,
                    'effective_from' => $now,
                    'effective_to' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                continue;
            }

            if (! $limitChanged) {
                continue;
            }

            DB::table('service_user_amount_limit_histories')
                ->where('user_id', $userId)
                ->where('service_id', $serviceId)
                ->whereNull('effective_to')
                ->update([
                    'effective_to' => $now,
                    'updated_at' => $now,
                ]);

            DB::table('service_user_amount_limit_histories')->insert([
                'service_id' => $serviceId,
                'user_id' => $userId,
                'transaction_min_amount' => $newMin,
                'transaction_max_amount' => $newMax,
                'effective_from' => $now,
                'effective_to' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    protected function nullableAmount($value)
    {
        if ($value === null || trim((string) $value) === '') {
            return null;
        }

        return number_format((float) $value, 2, '.', '');
    }

    protected function truthy($value)
    {
        return in_array(strtolower(trim((string) $value)), ['1', 'true', 'on', 'yes'], true);
    }

    protected function ownershipSummary(User $user)
    {
        $ownedShortcodes = $user->relationLoaded('shortcodes')
            ? $user->shortcodes->count()
            : $user->shortcodes()->count();
        $ownedServices = User::supportsServiceOwnership()
            ? ($user->relationLoaded('services')
                ? $user->services->count()
                : $user->services()->count())
            : $user->shortcodes->pluck('service')->flatten()->count();

        return $ownedShortcodes.' owned shortcode(s), '.$ownedServices.' owned service(s)';
    }

    protected function visibilitySummary(User $user)
    {
        $parts = [];
        $sharedShortcodeCount = User::supportsShortcodeVisibilityAssignments() ? count($user->sharedShortcodeAccessIds()) : 0;
        $sharedServiceCount = User::supportsServiceVisibilityAssignments() ? count($user->grantedServiceIds()) : 0;
        $keywordCount = User::supportsAccountKeywordAccess() ? count($user->accountKeywordAccessIds()) : 0;

        $parts[] = ! $user->hasPermission('shortcode.view')
            ? 'No shortcode page'
            : ($user->canViewAllShortcodes() ? 'All shortcodes' : ($sharedShortcodeCount ? $sharedShortcodeCount.' shared shortcode(s)' : 'Own shortcodes only'));
        $parts[] = ! $user->hasPermission('services.view')
            ? 'No services page'
            : ($user->canViewAllServices() ? 'All services' : ($sharedServiceCount ? $sharedServiceCount.' shared service(s)' : 'Owned services only'));
        $transactionScope = $user->canViewAllTransactions() ? 'All transactions' : 'Scoped transactions';

        if ($user->hasTransactionAmountRangeLimit()) {
            $transactionScope .= ' with service amount limits';
        }

        if ($keywordCount) {
            $transactionScope .= ' and '.$keywordCount.' keyword rule(s)';
        }

        $parts[] = ! $user->hasPermission('transaction.view')
            ? 'No transaction page'
            : $transactionScope;

        return implode(' | ', $parts);
    }

    protected function customPermissionSummary(User $user)
    {
        return $this->formatPermissionLabels($user->customPermissionOverrideSlugs(), 'No custom overrides');
    }

    protected function rolePermissionSummary(Role $role)
    {
        if ($role->slug === 'superadmin') {
            return 'All permissions (system default)';
        }

        return $this->formatPermissionLabels($this->rolePermissionSlugs($role), 'No permissions assigned');
    }

    protected function rolePermissionSlugs(Role $role)
    {
        $slugs = $role->permissions->pluck('slug')->values()->all();

        if ($role->slug === 'customer-client') {
            $slugs = array_values(array_diff($slugs, User::clientScopedRestrictedPermissionSlugs()));
        }

        return $slugs;
    }

    protected function formatPermissionLabels(array $slugs, $emptyLabel)
    {
        $labels = collect($slugs)
            ->map(function ($slug) {
                return User::permissionDefinitions()[$slug]['label'] ?? $slug;
            })
            ->values();

        if ($labels->isEmpty()) {
            return $emptyLabel;
        }

        $visible = $labels->take(3)->implode(', ');
        $remaining = $labels->count() - 3;

        return $remaining > 0 ? $visible.' +'.$remaining.' more' : $visible;
    }

    protected function userAuditData(User $user, $includeBundle = false)
    {
        $relations = ['shortcodes.service', 'assignedRoles.permissions', 'customPermissions'];

        if (User::supportsServiceOwnership()) {
            $relations[] = 'services.shortcode';
        }

        if (User::supportsShortcodeVisibilityAssignments()) {
            $relations[] = 'sharedShortcodeAccess';
        }

        if (User::supportsServiceVisibilityAssignments()) {
            $relations[] = 'serviceAccess.shortcode';
        }

        if (User::supportsAccountKeywordAccess()) {
            $relations[] = 'accountKeywordAccess.service.shortcode';
        }

        $user->loadMissing($relations);
        $grantedShortcodeIds = User::supportsShortcodeVisibilityAssignments() ? $user->sharedShortcodeAccessIds() : [];
        $grantedServiceIds = User::supportsServiceVisibilityAssignments() ? $user->grantedServiceIds() : [];

        $data = [
            'id' => $user->id,
            'name' => $user->name,
            'username' => $user->username,
            'email' => $user->email,
            'status' => (bool) $user->status,
            'role' => $user->primaryRoleName(),
            'custom_permissions' => $user->customPermissionOverrideSlugs(),
            'effective_permissions' => $user->effectivePermissionSlugs(),
            'visibility' => $this->visibilitySummary($user),
            'service_transaction_amount_limits' => $this->serviceAmountLimitAuditData($user),
            'account_keyword_access' => $this->accountKeywordAccessAuditData($user),
            'shared_shortcode_access' => User::supportsShortcodeVisibilityAssignments() ? $user->sharedShortcodeAccess->whereIn('id', $grantedShortcodeIds)->map(function ($shortcode) {
                return [
                    'id' => $shortcode->id,
                    'shortcode' => $shortcode->shortcode,
                ];
            })->values()->all() : [],
            'shared_service_access' => User::supportsServiceVisibilityAssignments() ? $user->serviceAccess->whereIn('id', $grantedServiceIds)->map(function ($service) {
                return [
                    'id' => $service->id,
                    'service_name' => $service->service_name,
                    'shortcode_id' => $service->shortcode_id,
                ];
            })->values()->all() : [],
        ];

        if ($includeBundle) {
            $data['shortcodes'] = $user->shortcodes->map(function ($shortcode) {
                return [
                    'id' => $shortcode->id,
                    'shortcode' => $shortcode->shortcode,
                    'group' => $shortcode->group,
                    'sharing_mode' => $shortcode->sharing_mode ?? 'dedicated',
                    'services' => $shortcode->service->map(function ($service) {
                        return [
                            'id' => $service->id,
                            'service_name' => $service->service_name,
                            'prefix' => $service->prefix,
                            'user_id' => $service->user_id,
                        ];
                    })->values()->all(),
                ];
            })->values()->all();

            $data['owned_services'] = User::supportsServiceOwnership() ? $user->services->map(function ($service) {
                return [
                    'id' => $service->id,
                    'service_name' => $service->service_name,
                    'shortcode_id' => $service->shortcode_id,
                ];
            })->values()->all() : [];
        }

        return $data;
    }

    protected function userRestorePayload(User $user)
    {
        return [
            'type' => 'user_bundle',
            'user_id' => $user->id,
            'shortcode_ids' => $user->shortcodes->pluck('id')->values()->all(),
            'service_ids' => array_values(array_unique(array_merge(
                $user->shortcodes->pluck('service')->flatten()->pluck('id')->values()->all(),
                User::supportsServiceOwnership() ? $user->services->pluck('id')->values()->all() : []
            ))),
        ];
    }

    protected function roleAuditData(Role $role)
    {
        return [
            'id' => $role->id,
            'name' => $role->name,
            'slug' => $role->slug,
            'description' => $role->description,
            'is_system' => (bool) $role->is_system,
            'permissions' => $role->permissions->pluck('slug')->values()->all(),
        ];
    }

    protected function keywordAuditData(ServiceAccountKeyword $keyword)
    {
        $keyword->loadMissing(['service.shortcode', 'users']);

        return [
            'id' => $keyword->id,
            'keyword_name' => $keyword->keyword_name,
            'match_type' => $keyword->match_type,
            'match_pattern' => $keyword->match_pattern,
            'status' => (bool) $keyword->status,
            'service_id' => $keyword->service_id,
            'service_name' => optional($keyword->service)->service_name,
            'shortcode' => optional(optional($keyword->service)->shortcode)->shortcode,
            'users' => $keyword->users->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'min' => $user->pivot->transaction_min_amount,
                    'max' => $user->pivot->transaction_max_amount,
                    'bypass_history' => isset($user->pivot->bypass_amount_limit_history)
                        ? (bool) $user->pivot->bypass_amount_limit_history
                        : false,
                ];
            })->values()->all(),
        ];
    }

    protected function serviceAmountLimitAuditData(User $user)
    {
        if (! User::supportsTransactionAmountLimits()) {
            return [];
        }

        return $user->serviceAccess()
            ->with('shortcode')
            ->get()
            ->map(function ($service) {
                return [
                    'service_id' => $service->id,
                    'service_name' => $service->service_name,
                    'shortcode' => optional($service->shortcode)->shortcode,
                    'min' => $service->pivot->transaction_min_amount,
                    'max' => $service->pivot->transaction_max_amount,
                    'bypass_history' => isset($service->pivot->bypass_amount_limit_history)
                        ? (bool) $service->pivot->bypass_amount_limit_history
                        : false,
                ];
            })
            ->values()
            ->all();
    }

    protected function accountKeywordAccessAuditData(User $user)
    {
        if (! User::supportsAccountKeywordAccess()) {
            return [];
        }

        return $user->accountKeywordAccess()
            ->with('service.shortcode')
            ->get()
            ->map(function ($keyword) {
                return [
                    'keyword_id' => $keyword->id,
                    'keyword_name' => $keyword->keyword_name,
                    'match_type' => $keyword->match_type,
                    'match_pattern' => $keyword->match_pattern,
                    'service_name' => optional($keyword->service)->service_name,
                    'shortcode' => optional(optional($keyword->service)->shortcode)->shortcode,
                    'min' => $keyword->pivot->transaction_min_amount,
                    'max' => $keyword->pivot->transaction_max_amount,
                    'bypass_history' => isset($keyword->pivot->bypass_amount_limit_history)
                        ? (bool) $keyword->pivot->bypass_amount_limit_history
                        : false,
                ];
            })
            ->values()
            ->all();
    }
}
