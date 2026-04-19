<?php
// file: app/User.php

namespace App;

use App\Models\AccessEntry;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Service;
use App\Models\ServiceAccountKeyword;
use App\Models\Shortcode;
use App\Traits\ConditionalSoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class User extends Authenticatable
{
    use Notifiable, ConditionalSoftDeletes;

    const PAGE_PERMISSION_CATALOG = [
        'dashboard' => [
            'label' => 'Dashboard',
            'description' => 'Dashboard summaries and reports.',
            'actions' => [
                'view' => [
                    'label' => 'View',
                    'description' => 'Open dashboard reports. Amounts are limited to the services and shortcodes the user can access.',
                ],
                'search' => [
                    'label' => 'Search Dates',
                    'description' => 'Change the dashboard date range. Without this, the dashboard stays on today.',
                ],
                'reports' => [
                    'label' => 'Report Cards',
                    'description' => 'View service and keyword report cards below the dashboard summary.',
                ],
            ],
        ],
        'transaction_reports' => [
            'label' => 'Transaction Reports',
            'description' => 'Daily transaction report summaries.',
            'actions' => [
                'view' => [
                    'label' => 'View',
                    'description' => 'Open daily transaction reports using the user transaction scope.',
                ],
            ],
        ],
        'shortcode' => [
            'label' => 'Shortcode',
            'description' => 'Shortcode configuration and notification setup.',
            'actions' => [
                'view' => [
                    'label' => 'View',
                    'description' => 'View shortcodes assigned to the user.',
                ],
                'view_all' => [
                    'label' => 'View All',
                    'description' => 'View every shortcode in the system, not only the ones assigned to the user.',
                ],
                'create' => [
                    'label' => 'Create',
                    'description' => 'Add new shortcodes.',
                ],
                'update' => [
                    'label' => 'Update',
                    'description' => 'Edit shortcode details and start notifications.',
                ],
                'assign_owner' => [
                    'label' => 'Assign Owner',
                    'description' => 'Choose or change the owner of a shortcode during create or update.',
                ],
                'delete' => [
                    'label' => 'Delete',
                    'description' => 'Delete shortcodes.',
                ],
            ],
        ],
        'services' => [
            'label' => 'Services',
            'description' => 'Service setup under each shortcode.',
            'actions' => [
                'view' => [
                    'label' => 'View',
                    'description' => 'View services assigned to the user.',
                ],
                'view_all' => [
                    'label' => 'View All',
                    'description' => "View every service in the system, not only the ones under the user's shortcodes.",
                ],
                'create' => [
                    'label' => 'Create',
                    'description' => 'Add new services.',
                ],
                'update' => [
                    'label' => 'Update',
                    'description' => 'Edit service details.',
                ],
                'delete' => [
                    'label' => 'Delete',
                    'description' => 'Delete services.',
                ],
            ],
        ],
        'transaction' => [
            'label' => 'Transactions',
            'description' => 'Transaction listings and search.',
            'actions' => [
                'view' => [
                    'label' => 'View',
                    'description' => 'Open the transactions page for the services or shortcodes the user owns or has been granted.',
                ],
                'view_all' => [
                    'label' => 'View All',
                    'description' => 'View transactions for every shortcode and service in the system, not only assigned resources.',
                ],
                'search' => [
                    'label' => 'Search',
                    'description' => 'Search and filter transaction records.',
                ],
                'download' => [
                    'label' => 'Download',
                    'description' => 'Export transaction lists and transaction reports.',
                ],
                'view_msisdn' => [
                    'label' => 'View MSISDN',
                    'description' => 'See full phone numbers instead of masked values.',
                ],
            ],
        ],
        'users' => [
            'label' => 'User Management',
            'description' => 'Manage users, roles, and custom permissions.',
            'actions' => [
                'view' => [
                    'label' => 'View',
                    'description' => 'Open the user management page.',
                ],
                'create' => [
                    'label' => 'Create',
                    'description' => 'Create new users.',
                ],
                'update' => [
                    'label' => 'Update',
                    'description' => 'Edit users, account status, and grant specific resource access from resources the manager can access.',
                ],
                'delete' => [
                    'label' => 'Delete',
                    'description' => 'Delete users and soft-delete linked data.',
                ],
                'manage_roles' => [
                    'label' => 'Manage Roles',
                    'description' => 'Create and edit named roles.',
                ],
                'manage_permissions' => [
                    'label' => 'Custom Permissions',
                    'description' => 'Assign custom permission overrides to individual users.',
                ],
            ],
        ],
        'audit_logs' => [
            'label' => 'Audit Logs',
            'description' => 'Detailed change history and restore actions.',
            'actions' => [
                'view' => [
                    'label' => 'View',
                    'description' => 'Open the audit logs page.',
                ],
                'view_all' => [
                    'label' => 'View All',
                    'description' => 'View every audit log in the system, not only the actions performed by the logged-in user.',
                ],
                'restore' => [
                    'label' => 'Restore',
                    'description' => 'Restore eligible soft-deleted records from audit logs.',
                ],
            ],
        ],
    ];

    protected static $advancedAccessControlAvailable;
    protected static $shortcodeSharingAvailable;
    protected static $serviceOwnershipAvailable;
    protected static $shortcodeVisibilityAssignmentsAvailable;
    protected static $serviceVisibilityAssignmentsAvailable;
    protected static $transactionAmountLimitsAvailable;
    protected static $serviceAmountLimitHistoryAvailable;
    protected static $transactionAmountLimitHistoryBypassAvailable;
    protected static $accountKeywordAccessAvailable;
    protected static $accountKeywordLimitHistoryAvailable;

    protected $fillable = [
        'name', 'email', 'username', 'password', 'status', 'transaction_min_amount', 'transaction_max_amount',
    ];

    protected $hidden = [
        'password', 'remember_token', 'status',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'transaction_min_amount' => 'decimal:2',
        'transaction_max_amount' => 'decimal:2',
    ];

    protected $resolvedPermissionSlugs;

    public function shortcodes()
    {
        return $this->hasMany('App\Models\Shortcode');
    }

    public function services()
    {
        return $this->hasMany('App\Models\Service');
    }

    public function accessEntries()
    {
        return $this->hasMany('App\Models\AccessEntry');
    }

    public function loginActivities()
    {
        return $this->hasMany('App\Models\UserLoginActivity');
    }

    public function assignedRoles()
    {
        return $this->belongsToMany('App\Models\Role', 'role_user')->withTimestamps();
    }

    public function customPermissions()
    {
        return $this->belongsToMany('App\Models\Permission', 'permission_user')->withTimestamps();
    }

    public function sharedShortcodeAccess()
    {
        return $this->belongsToMany('App\Models\Shortcode', 'shortcode_user_access')->withTimestamps();
    }

    public function serviceAccess()
    {
        $relation = $this->belongsToMany('App\Models\Service', 'service_user_access')->withTimestamps();

        if (self::supportsTransactionAmountLimits()) {
            $relation->withPivot('transaction_min_amount', 'transaction_max_amount');
        }

        if (self::supportsTransactionAmountLimitHistoryBypass()) {
            $relation->withPivot('bypass_amount_limit_history');
        }

        return $relation;
    }

    public function accountKeywordAccess()
    {
        return $this->belongsToMany(
            'App\Models\ServiceAccountKeyword',
            'service_account_keyword_user_access',
            'user_id',
            'keyword_id'
        )->withPivot('transaction_min_amount', 'transaction_max_amount', 'bypass_amount_limit_history')
            ->withTimestamps();
    }

    public static function permissionCatalog()
    {
        return self::PAGE_PERMISSION_CATALOG;
    }

    public static function permissionDefinitions()
    {
        $definitions = [];

        foreach (self::PAGE_PERMISSION_CATALOG as $pageKey => $page) {
            foreach ($page['actions'] as $actionKey => $action) {
                $slug = $pageKey.'.'.$actionKey;
                $definitions[$slug] = [
                    'slug' => $slug,
                    'page_key' => $pageKey,
                    'page' => $page['label'],
                    'page_description' => $page['description'],
                    'action_key' => $actionKey,
                    'action' => $action['label'],
                    'label' => $page['label'].' - '.$action['label'],
                    'description' => $action['description'],
                ];
            }
        }

        return $definitions;
    }

    public static function permissionSlugs()
    {
        return array_keys(self::permissionDefinitions());
    }

    public static function clientScopedRestrictedPermissionSlugs()
    {
        return [
            'shortcode.view_all',
            'services.view_all',
            'transaction.view_all',
            'audit_logs.view_all',
        ];
    }

    public static function usesAdvancedAccessControl()
    {
        if (static::$advancedAccessControlAvailable === null) {
            static::$advancedAccessControlAvailable = Schema::hasTable('roles')
                && Schema::hasTable('permissions')
                && Schema::hasTable('role_user')
                && Schema::hasTable('permission_user')
                && Schema::hasTable('permission_role');
        }

        return static::$advancedAccessControlAvailable;
    }

    public static function supportsShortcodeSharing()
    {
        if (static::$shortcodeSharingAvailable === null) {
            static::$shortcodeSharingAvailable = Schema::hasTable('shortcodes')
                && Schema::hasColumn('shortcodes', 'sharing_mode');
        }

        return static::$shortcodeSharingAvailable;
    }

    public static function supportsServiceOwnership()
    {
        if (static::$serviceOwnershipAvailable === null) {
            static::$serviceOwnershipAvailable = Schema::hasTable('services')
                && Schema::hasColumn('services', 'user_id');
        }

        return static::$serviceOwnershipAvailable;
    }

    public static function supportsShortcodeVisibilityAssignments()
    {
        if (static::$shortcodeVisibilityAssignmentsAvailable === null) {
            static::$shortcodeVisibilityAssignmentsAvailable = Schema::hasTable('shortcode_user_access');
        }

        return static::$shortcodeVisibilityAssignmentsAvailable;
    }

    public static function supportsServiceVisibilityAssignments()
    {
        if (static::$serviceVisibilityAssignmentsAvailable === null) {
            static::$serviceVisibilityAssignmentsAvailable = Schema::hasTable('service_user_access');
        }

        return static::$serviceVisibilityAssignmentsAvailable;
    }

    public static function supportsTransactionAmountLimits()
    {
        if (static::$transactionAmountLimitsAvailable === null) {
            static::$transactionAmountLimitsAvailable = Schema::hasTable('service_user_access')
                && Schema::hasColumn('service_user_access', 'transaction_min_amount')
                && Schema::hasColumn('service_user_access', 'transaction_max_amount');
        }

        return static::$transactionAmountLimitsAvailable;
    }

    public static function supportsServiceAmountLimitHistory()
    {
        if (static::$serviceAmountLimitHistoryAvailable === null) {
            static::$serviceAmountLimitHistoryAvailable = Schema::hasTable('service_user_amount_limit_histories')
                && Schema::hasColumn('service_user_amount_limit_histories', 'transaction_min_amount')
                && Schema::hasColumn('service_user_amount_limit_histories', 'transaction_max_amount')
                && Schema::hasColumn('service_user_amount_limit_histories', 'effective_from')
                && Schema::hasColumn('service_user_amount_limit_histories', 'effective_to');
        }

        return static::$serviceAmountLimitHistoryAvailable;
    }

    public static function supportsTransactionAmountLimitHistoryBypass()
    {
        if (static::$transactionAmountLimitHistoryBypassAvailable === null) {
            static::$transactionAmountLimitHistoryBypassAvailable = Schema::hasTable('service_user_access')
                && Schema::hasColumn('service_user_access', 'bypass_amount_limit_history');
        }

        return static::$transactionAmountLimitHistoryBypassAvailable;
    }

    public static function supportsAccountKeywordAccess()
    {
        if (static::$accountKeywordAccessAvailable === null) {
            static::$accountKeywordAccessAvailable = Schema::hasTable('service_account_keywords')
                && Schema::hasTable('service_account_keyword_user_access')
                && Schema::hasColumn('service_account_keywords', 'service_id')
                && Schema::hasColumn('service_account_keywords', 'match_type')
                && Schema::hasColumn('service_account_keywords', 'match_pattern')
                && Schema::hasColumn('service_account_keyword_user_access', 'keyword_id')
                && Schema::hasColumn('service_account_keyword_user_access', 'user_id')
                && Schema::hasColumn('service_account_keyword_user_access', 'transaction_min_amount')
                && Schema::hasColumn('service_account_keyword_user_access', 'transaction_max_amount')
                && Schema::hasColumn('service_account_keyword_user_access', 'bypass_amount_limit_history');
        }

        return static::$accountKeywordAccessAvailable;
    }

    public static function supportsAccountKeywordLimitHistory()
    {
        if (static::$accountKeywordLimitHistoryAvailable === null) {
            static::$accountKeywordLimitHistoryAvailable = Schema::hasTable('service_account_keyword_limit_histories')
                && Schema::hasColumn('service_account_keyword_limit_histories', 'transaction_min_amount')
                && Schema::hasColumn('service_account_keyword_limit_histories', 'transaction_max_amount')
                && Schema::hasColumn('service_account_keyword_limit_histories', 'effective_from')
                && Schema::hasColumn('service_account_keyword_limit_histories', 'effective_to');
        }

        return static::$accountKeywordLimitHistoryAvailable;
    }

    public function hasSuperAdminRole()
    {
        if (! self::usesAdvancedAccessControl()) {
            return false;
        }

        if ($this->relationLoaded('assignedRoles')) {
            return $this->assignedRoles->contains('slug', 'superadmin');
        }

        return $this->assignedRoles()->where('slug', 'superadmin')->exists();
    }

    public function hasRoleSlug($roleSlug)
    {
        if (! self::usesAdvancedAccessControl()) {
            return false;
        }

        if ($this->relationLoaded('assignedRoles')) {
            return $this->assignedRoles->contains('slug', $roleSlug);
        }

        return $this->assignedRoles()->where('slug', $roleSlug)->exists();
    }

    public function hasInternalVisibilityRole()
    {
        return $this->hasSuperAdminRole() || $this->hasRoleSlug('internal-user');
    }

    public function hasScopedClientRole()
    {
        return $this->hasRoleSlug('customer-client') && ! $this->hasInternalVisibilityRole();
    }

    public function isSuperAdmin()
    {
        if ($this->hasSuperAdminRole()) {
            return true;
        }

        return (int) $this->id === 1 && $this->legacyAccessEnabled('users');
    }

    public function hasPermission($permission)
    {
        $slug = self::normalizePermissionSlug($permission);

        if ($slug === null) {
            return false;
        }

        if (strpos($slug, 'profile.') === 0) {
            return true;
        }

        if ($this->isSuperAdmin()) {
            return true;
        }

        if (! self::usesAdvancedAccessControl()) {
            return $this->legacyHasPermissionSlug($slug);
        }

        $effectivePermissionSlugs = $this->effectivePermissionSlugs();

        if (in_array($slug, $effectivePermissionSlugs, true)) {
            return true;
        }

        if (substr($slug, -5) === '.view') {
            $viewAllSlug = substr($slug, 0, -5).'.view_all';

            return in_array($viewAllSlug, self::permissionSlugs(), true)
                && in_array($viewAllSlug, $effectivePermissionSlugs, true);
        }

        return false;
    }

    public function canAccessPage($page)
    {
        if ($page === 'profile') {
            return true;
        }

        return $this->hasPermission($page.'.view');
    }

    public function permissionStates()
    {
        $states = [];

        foreach (self::permissionSlugs() as $permissionSlug) {
            $states[$permissionSlug] = $this->hasPermission($permissionSlug);
        }

        return $states;
    }

    public function effectivePermissionSlugs()
    {
        if ($this->resolvedPermissionSlugs !== null) {
            return $this->resolvedPermissionSlugs;
        }

        if (! self::usesAdvancedAccessControl()) {
            $this->resolvedPermissionSlugs = $this->legacyPermissionSlugs();
            return $this->resolvedPermissionSlugs;
        }

        $roles = $this->relationLoaded('assignedRoles')
            ? $this->assignedRoles
            : $this->assignedRoles()->with('permissions:id,slug')->get();

        $rolePermissionSlugs = $roles
            ->pluck('permissions')
            ->flatten()
            ->pluck('slug')
            ->all();

        $customPermissions = $this->relationLoaded('customPermissions')
            ? $this->customPermissions
            : $this->customPermissions()->get(['permissions.slug']);

        $customPermissionSlugs = $customPermissions
            ->pluck('slug')
            ->all();

        $this->resolvedPermissionSlugs = array_values(array_unique(array_merge($rolePermissionSlugs, $customPermissionSlugs)));

        if ($this->hasScopedClientRole()) {
            $this->resolvedPermissionSlugs = array_values(array_diff(
                $this->resolvedPermissionSlugs,
                self::clientScopedRestrictedPermissionSlugs()
            ));
        }

        return $this->resolvedPermissionSlugs;
    }

    public function assignedCustomPermissionSlugs()
    {
        if (! self::usesAdvancedAccessControl()) {
            return $this->legacyPermissionSlugs();
        }

        $customPermissions = $this->relationLoaded('customPermissions')
            ? $this->customPermissions
            : $this->customPermissions()->get(['permissions.slug']);

        $slugs = $customPermissions->pluck('slug')->values()->all();

        if ($this->hasScopedClientRole()) {
            $slugs = array_values(array_diff($slugs, self::clientScopedRestrictedPermissionSlugs()));
        }

        return $slugs;
    }

    public function customPermissionOverrideSlugs()
    {
        if (! self::usesAdvancedAccessControl()) {
            return $this->legacyPermissionSlugs();
        }

        if ($this->hasSuperAdminRole()) {
            return [];
        }

        $customPermissionSlugs = $this->assignedCustomPermissionSlugs();
        $roles = $this->relationLoaded('assignedRoles')
            ? $this->assignedRoles
            : $this->assignedRoles()->with('permissions:id,slug')->get();

        $rolePermissionSlugs = $roles
            ->pluck('permissions')
            ->flatten()
            ->pluck('slug')
            ->unique()
            ->values()
            ->all();

        return array_values(array_diff($customPermissionSlugs, $rolePermissionSlugs));
    }

    public function assignedRoleIds()
    {
        if (! self::usesAdvancedAccessControl()) {
            return [];
        }

        return $this->assignedRoles()->pluck('roles.id')->values()->all();
    }

    public function primaryRole()
    {
        if (! self::usesAdvancedAccessControl()) {
            return null;
        }

        if ($this->relationLoaded('assignedRoles')) {
            return $this->assignedRoles->sortBy('name')->first();
        }

        return $this->assignedRoles()->orderBy('roles.name')->first();
    }

    public function primaryRoleName()
    {
        $role = $this->primaryRole();

        return $role ? $role->name : 'No role';
    }

    public function roleSummary()
    {
        if (! self::usesAdvancedAccessControl()) {
            return $this->isSuperAdmin() ? 'Superadmin' : 'Legacy access';
        }

        $roles = $this->relationLoaded('assignedRoles')
            ? $this->assignedRoles
            : $this->assignedRoles()->get(['roles.name']);

        $names = $roles->pluck('name')->values()->all();

        return empty($names) ? 'No role' : implode(', ', $names);
    }

    public function syncPrimaryRole($roleId = null)
    {
        if (! self::usesAdvancedAccessControl()) {
            return;
        }

        $roleIds = [];
        $isSuperAdminRole = false;

        if ($roleId !== null && Role::whereKey($roleId)->exists()) {
            $roleIds[] = (int) $roleId;
            $isSuperAdminRole = Role::whereKey($roleId)->where('slug', 'superadmin')->exists();
        }

        $this->assignedRoles()->sync($roleIds);

        if ($isSuperAdminRole) {
            $this->customPermissions()->sync([]);
        }

        $this->resolvedPermissionSlugs = null;
    }

    public function syncCustomPermissions(array $permissionSlugs = [])
    {
        if (! self::usesAdvancedAccessControl()) {
            return;
        }

        if ($this->hasSuperAdminRole()) {
            $this->customPermissions()->sync([]);
            $this->resolvedPermissionSlugs = null;
            return;
        }

        $selectedSlugs = array_values(array_intersect(self::permissionSlugs(), $permissionSlugs));
        $permissionIds = Permission::whereIn('slug', $selectedSlugs)->pluck('id')->all();

        $this->customPermissions()->sync($permissionIds);
        $this->resolvedPermissionSlugs = null;
    }

    public function accessibleShortcodeIds()
    {
        return $this->viewableShortcodeIds();
    }

    public function ownedShortcodeIds()
    {
        return $this->shortcodes()->pluck('id')->all();
    }

    public function sharedShortcodeAccessIds()
    {
        if (! self::supportsShortcodeVisibilityAssignments()) {
            return [];
        }

        return $this->sharedShortcodeAccess()
            ->when(self::supportsShortcodeSharing(), function ($query) {
                $query->where('shortcodes.sharing_mode', 'shared');
            })
            ->where('shortcodes.user_id', '!=', $this->id)
            ->pluck('shortcodes.id')
            ->all();
    }

    public function ownedServiceIds()
    {
        if (self::supportsServiceOwnership()) {
            return $this->services()->pluck('id')->all();
        }

        return Service::query()
            ->whereIn('shortcode_id', $this->ownedShortcodeIds())
            ->pluck('id')
            ->all();
    }

    public function shortcodeServiceIds()
    {
        return Service::query()
            ->whereIn('shortcode_id', $this->ownedShortcodeIds())
            ->pluck('id')
            ->all();
    }

    public function grantedServiceIds()
    {
        if (! self::supportsServiceVisibilityAssignments()) {
            return [];
        }

        return $this->serviceAccess()
            ->when(self::supportsShortcodeSharing(), function ($query) {
                $query->whereHas('shortcode', function ($shortcodeQuery) {
                    $shortcodeQuery->where('sharing_mode', 'shared');
                });
            })
            ->where(function ($query) {
                $query->whereNull('services.user_id')
                    ->orWhere('services.user_id', '!=', $this->id);
            })
            ->pluck('services.id')
            ->all();
    }

    public function viewableShortcodeIds()
    {
        if ($this->canViewAllShortcodes()) {
            return Shortcode::pluck('id')->all();
        }

        $ids = array_merge($this->ownedShortcodeIds(), $this->sharedShortcodeAccessIds());

        return array_values(array_unique(array_map('intval', $ids)));
    }

    public function manageableShortcodeIds()
    {
        if ($this->canViewAllShortcodes()) {
            return Shortcode::pluck('id')->all();
        }

        return array_values(array_unique(array_map('intval', $this->ownedShortcodeIds())));
    }

    public function viewableServiceIds()
    {
        if ($this->canViewAllServices()) {
            return Service::pluck('id')->all();
        }

        $ids = array_merge(
            $this->ownedServiceIds(),
            $this->grantedServiceIds()
        );

        return array_values(array_unique(array_map('intval', $ids)));
    }

    public function manageableServiceIds()
    {
        if ($this->canViewAllServices() || $this->canViewAllShortcodes()) {
            return Service::pluck('id')->all();
        }

        $ids = array_merge(
            $this->ownedServiceIds(),
            $this->shortcodeServiceIds()
        );

        return array_values(array_unique(array_map('intval', $ids)));
    }

    public function serviceSelectableShortcodeIds()
    {
        if ($this->canViewAllServices() || $this->canViewAllShortcodes()) {
            return Shortcode::pluck('id')->all();
        }

        $ids = array_merge(
            $this->ownedShortcodeIds(),
            Service::query()
                ->whereIn('id', $this->manageableServiceIds())
                ->pluck('shortcode_id')
                ->all()
        );

        return array_values(array_unique(array_map('intval', $ids)));
    }

    public function transactionVisibleShortcodeIds()
    {
        if ($this->canViewAllTransactions()) {
            return Shortcode::pluck('id')->all();
        }

        $ids = $this->sharedShortcodeAccessIds();

        return array_values(array_unique(array_map('intval', $ids)));
    }

    public function transactionVisibleServiceIds()
    {
        if ($this->canViewAllTransactions()) {
            return Service::pluck('id')->all();
        }

        $ids = array_merge(
            $this->ownedServiceIds(),
            $this->grantedServiceIds()
        );

        return array_values(array_unique(array_map('intval', $ids)));
    }

    public function transactionVisibleShortcodeIdsFromServices()
    {
        if ($this->canViewAllTransactions()) {
            return Shortcode::pluck('id')->all();
        }

        return Service::query()
            ->whereIn('id', $this->transactionVisibleServiceIds())
            ->pluck('shortcode_id')
            ->map(function ($id) {
                return (int) $id;
            })
            ->unique()
            ->values()
            ->all();
    }

    public function transactionVisibleShortcodeIdsFromKeywords()
    {
        if ($this->canViewAllTransactions()) {
            return Shortcode::pluck('id')->all();
        }

        return collect($this->transactionVisibleKeywordRules())
            ->pluck('shortcode_id')
            ->map(function ($id) {
                return (int) $id;
            })
            ->unique()
            ->values()
            ->all();
    }

    public function transactionVisibleKeywordServiceIds()
    {
        if ($this->canViewAllTransactions()) {
            return Service::pluck('id')->all();
        }

        return collect($this->transactionVisibleKeywordRules())
            ->pluck('service_id')
            ->map(function ($id) {
                return (int) $id;
            })
            ->unique()
            ->values()
            ->all();
    }

    public function transactionVisibleServicePairs()
    {
        if ($this->canViewAllTransactions()) {
            return Service::query()
                ->select('shortcode_id', 'service_name')
                ->get()
                ->map(function ($service) {
                    return [
                        'shortcode_id' => (int) $service->shortcode_id,
                        'service_name' => (string) $service->service_name,
                    ];
                })
                ->all();
        }

        $userId = $this->id;
        $query = Service::query();

        if (self::supportsTransactionAmountLimits()) {
            $query->leftJoin('service_user_access', function ($join) use ($userId) {
                $join->on('service_user_access.service_id', '=', 'services.id')
                    ->where('service_user_access.user_id', '=', $userId);
            });
        }

        $services = $query
            ->whereIn('services.id', $this->transactionVisibleServiceIds())
            ->select($this->transactionVisibleServiceSelectColumns())
            ->get();
        $amountLimitWindows = $this->serviceAmountLimitWindowsByServiceId($services->pluck('id')->all());

        return $services
            ->map(function ($service) use ($amountLimitWindows) {
                return [
                    'service_id' => (int) $service->id,
                    'shortcode_id' => (int) $service->shortcode_id,
                    'service_name' => (string) $service->service_name,
                    'transaction_min_amount' => isset($service->transaction_min_amount) && $service->transaction_min_amount !== null ? (float) $service->transaction_min_amount : null,
                    'transaction_max_amount' => isset($service->transaction_max_amount) && $service->transaction_max_amount !== null ? (float) $service->transaction_max_amount : null,
                    'bypass_amount_limit_history' => ! empty($service->bypass_amount_limit_history),
                    'amount_limit_windows' => $amountLimitWindows[(int) $service->id] ?? [],
                ];
            })
            ->all();
    }

    public function transactionVisibleKeywordRules()
    {
        if ($this->canViewAllTransactions() || ! self::supportsAccountKeywordAccess()) {
            return [];
        }

        $rulesQuery = DB::table('service_account_keyword_user_access as access')
            ->join('service_account_keywords as keywords', 'keywords.id', '=', 'access.keyword_id')
            ->join('services', 'services.id', '=', 'keywords.service_id')
            ->where('access.user_id', $this->id)
            ->where('keywords.status', 1);

        if (Schema::hasColumn('services', 'deleted_at')) {
            $rulesQuery->whereNull('services.deleted_at');
        }

        $rules = $rulesQuery->select([
                'keywords.id as keyword_id',
                'keywords.keyword_name',
                'keywords.match_type',
                'keywords.match_pattern',
                'services.id as service_id',
                'services.shortcode_id',
                'services.service_name',
                'access.transaction_min_amount',
                'access.transaction_max_amount',
                'access.bypass_amount_limit_history',
            ])
            ->get();
        $amountLimitWindows = $this->accountKeywordLimitWindowsByKeywordId($rules->pluck('keyword_id')->all());

        return $rules
            ->map(function ($rule) use ($amountLimitWindows) {
                return [
                    'keyword_id' => (int) $rule->keyword_id,
                    'keyword_name' => (string) $rule->keyword_name,
                    'match_type' => (string) $rule->match_type,
                    'match_pattern' => (string) $rule->match_pattern,
                    'service_id' => (int) $rule->service_id,
                    'shortcode_id' => (int) $rule->shortcode_id,
                    'service_name' => (string) $rule->service_name,
                    'transaction_min_amount' => $rule->transaction_min_amount !== null ? (float) $rule->transaction_min_amount : null,
                    'transaction_max_amount' => $rule->transaction_max_amount !== null ? (float) $rule->transaction_max_amount : null,
                    'bypass_amount_limit_history' => ! empty($rule->bypass_amount_limit_history),
                    'amount_limit_windows' => $amountLimitWindows[(int) $rule->keyword_id] ?? [],
                ];
            })
            ->all();
    }

    protected function transactionVisibleServiceSelectColumns()
    {
        $columns = [
            'services.id',
            'services.shortcode_id',
            'services.service_name',
        ];

        if (self::supportsTransactionAmountLimits()) {
            $columns[] = 'service_user_access.transaction_min_amount';
            $columns[] = 'service_user_access.transaction_max_amount';
        }

        if (self::supportsTransactionAmountLimitHistoryBypass()) {
            $columns[] = 'service_user_access.bypass_amount_limit_history';
        }

        return $columns;
    }

    public function serviceAmountLimitWindowsByServiceId(array $serviceIds = [])
    {
        if (! self::supportsServiceAmountLimitHistory()) {
            return [];
        }

        $serviceIds = array_values(array_unique(array_map('intval', $serviceIds)));

        if (empty($serviceIds)) {
            return [];
        }

        return DB::table('service_user_amount_limit_histories')
            ->where('user_id', $this->id)
            ->whereIn('service_id', $serviceIds)
            ->orderBy('effective_from')
            ->get()
            ->groupBy('service_id')
            ->map(function ($windows) {
                return $windows->map(function ($window) {
                    return [
                        'transaction_min_amount' => $window->transaction_min_amount !== null ? (float) $window->transaction_min_amount : null,
                        'transaction_max_amount' => $window->transaction_max_amount !== null ? (float) $window->transaction_max_amount : null,
                        'effective_from' => $window->effective_from ? (string) $window->effective_from : null,
                        'effective_to' => $window->effective_to ? (string) $window->effective_to : null,
                    ];
                })->values()->all();
            })
            ->all();
    }

    public function accountKeywordAccessIds()
    {
        if (! self::supportsAccountKeywordAccess()) {
            return [];
        }

        return DB::table('service_account_keyword_user_access')
            ->where('user_id', $this->id)
            ->pluck('keyword_id')
            ->map(function ($id) {
                return (int) $id;
            })
            ->all();
    }

    public function accountKeywordAmountLimitsByKeywordId()
    {
        if (! self::supportsAccountKeywordAccess()) {
            return [];
        }

        return DB::table('service_account_keyword_user_access')
            ->where('user_id', $this->id)
            ->get()
            ->mapWithKeys(function ($keyword) {
                return [
                    (int) $keyword->keyword_id => [
                        'min' => $keyword->transaction_min_amount,
                        'max' => $keyword->transaction_max_amount,
                        'bypass_history' => isset($keyword->bypass_amount_limit_history)
                            ? (int) $keyword->bypass_amount_limit_history
                            : 0,
                    ],
                ];
            })
            ->all();
    }

    public function accountKeywordLimitWindowsByKeywordId(array $keywordIds = [])
    {
        if (! self::supportsAccountKeywordLimitHistory()) {
            return [];
        }

        $keywordIds = array_values(array_unique(array_map('intval', $keywordIds)));

        if (empty($keywordIds)) {
            return [];
        }

        return DB::table('service_account_keyword_limit_histories')
            ->where('user_id', $this->id)
            ->whereIn('keyword_id', $keywordIds)
            ->orderBy('effective_from')
            ->get()
            ->groupBy('keyword_id')
            ->map(function ($windows) {
                return $windows->map(function ($window) {
                    return [
                        'transaction_min_amount' => $window->transaction_min_amount !== null ? (float) $window->transaction_min_amount : null,
                        'transaction_max_amount' => $window->transaction_max_amount !== null ? (float) $window->transaction_max_amount : null,
                        'effective_from' => $window->effective_from ? (string) $window->effective_from : null,
                        'effective_to' => $window->effective_to ? (string) $window->effective_to : null,
                    ];
                })->values()->all();
            })
            ->all();
    }

    public function ownsShortcode($shortcodeId)
    {
        return $this->isSuperAdmin() || $this->shortcodes()->where('id', $shortcodeId)->exists();
    }

    public function canViewAllShortcodes()
    {
        return $this->isSuperAdmin()
            || (! $this->hasScopedClientRole() && $this->hasPermission('shortcode.view_all'));
    }

    public function canViewAllServices()
    {
        return $this->isSuperAdmin()
            || (! $this->hasScopedClientRole() && $this->hasPermission('services.view_all'));
    }

    public function canViewAllTransactions()
    {
        return $this->isSuperAdmin()
            || (! $this->hasScopedClientRole() && $this->hasPermission('transaction.view_all'));
    }

    public function hasTransactionAmountRangeLimit()
    {
        if ($this->isSuperAdmin()) {
            return false;
        }

        $hasServiceLimit = false;

        if (self::supportsTransactionAmountLimits()) {
            $hasServiceLimit = $this->serviceAccess()
                ->where(function ($query) {
                    $query->whereNotNull('service_user_access.transaction_min_amount')
                        ->orWhereNotNull('service_user_access.transaction_max_amount');
                })
                ->exists();
        }

        if ($hasServiceLimit || ! self::supportsAccountKeywordAccess()) {
            return $hasServiceLimit;
        }

        return DB::table('service_account_keyword_user_access')
            ->where('user_id', $this->id)
            ->where(function ($query) {
                $query->whereNotNull('transaction_min_amount')
                    ->orWhereNotNull('transaction_max_amount');
            })
            ->exists();
    }

    public function transactionAmountRange()
    {
        return [null, null];
    }

    public function serviceAmountLimitsByServiceId()
    {
        if (! self::supportsTransactionAmountLimits()) {
            return [];
        }

        return $this->serviceAccess()
            ->get(['services.id'])
            ->mapWithKeys(function ($service) {
                return [
                    (int) $service->id => [
                        'min' => $service->pivot->transaction_min_amount,
                        'max' => $service->pivot->transaction_max_amount,
                        'bypass_history' => isset($service->pivot->bypass_amount_limit_history)
                            ? (int) $service->pivot->bypass_amount_limit_history
                            : 0,
                    ],
                ];
            })
            ->all();
    }

    public function canViewAllAuditLogs()
    {
        return $this->isSuperAdmin()
            || (! $this->hasScopedClientRole() && $this->hasPermission('audit_logs.view_all'));
    }

    public static function normalizePermissionSlug($permission)
    {
        if ($permission === null) {
            return null;
        }

        $permission = trim((string) $permission);

        if ($permission === '') {
            return null;
        }

        if (in_array($permission, ['profile', 'profile.view', 'profile.update'], true)) {
            return strpos($permission, '.') === false ? 'profile.view' : $permission;
        }

        if (strpos($permission, '.') !== false) {
            $parts = explode('.', $permission, 2);
            $parts[0] = self::normalizePageKey($parts[0]);
            $slug = implode('.', $parts);

            return in_array($slug, self::permissionSlugs(), true) ? $slug : null;
        }

        $slug = self::normalizePageKey($permission).'.view';

        return in_array($slug, self::permissionSlugs(), true) ? $slug : null;
    }

    public static function normalizePageKey($page)
    {
        $page = trim((string) $page);

        if ($page === 'transactions') {
            return 'transaction';
        }

        return $page;
    }

    protected function legacyHasPermissionSlug($slug)
    {
        $legacyMap = [
            'dashboard.view' => 'dashboard',
            'dashboard.search' => 'dashboard',
            'dashboard.reports' => 'dashboard',
            'shortcode.view' => 'shortcode',
            'shortcode.view_all' => 'shortcode',
            'shortcode.create' => 'shortcode',
            'shortcode.update' => 'shortcode',
            'shortcode.assign_owner' => 'shortcode',
            'shortcode.delete' => 'shortcode',
            'services.view' => 'services',
            'services.view_all' => 'services',
            'services.create' => 'services',
            'services.update' => 'services',
            'services.delete' => 'services',
            'transaction.view' => 'transaction',
            'transaction.view_all' => 'transaction',
            'transaction.search' => 'transaction',
            'transaction.view_msisdn' => 'transaction',
            'users.view' => 'users',
            'users.create' => 'users',
            'users.update' => 'users',
            'users.delete' => 'users',
            'users.manage_roles' => 'users',
            'users.manage_permissions' => 'users',
            'audit_logs.view' => 'audit_logs',
            'audit_logs.view_all' => 'audit_logs',
            'audit_logs.restore' => 'audit_logs',
            'transaction_reports.view' => 'transaction',
            'transaction.download' => 'transaction',
        ];

        if (! isset($legacyMap[$slug])) {
            return false;
        }

        return $this->legacyAccessEnabled($legacyMap[$slug]);
    }

    protected function legacyPermissionSlugs()
    {
        $slugs = [];

        foreach (self::permissionSlugs() as $slug) {
            if ($this->legacyHasPermissionSlug($slug)) {
                $slugs[] = $slug;
            }
        }

        return $slugs;
    }

    protected function legacyAccessEnabled($accessName)
    {
        $entry = $this->accessEntries()->where('access_name', $accessName)->first();

        return $entry ? $this->permissionValueIsEnabled($entry->access_value) : false;
    }

    protected function permissionValueIsEnabled($value)
    {
        if ($value === null) {
            return false;
        }

        return ! in_array(strtolower(trim((string) $value)), ['', '0', 'false', 'off', 'no'], true);
    }
}
