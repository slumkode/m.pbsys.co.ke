<?php
// file: database/migrations/2026_04_10_000100_create_advanced_access_control_tables.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateAdvancedAccessControlTables extends Migration
{
    protected $permissions = [
        ['slug' => 'dashboard.view', 'name' => 'View Dashboard', 'page_name' => 'Dashboard', 'action_name' => 'view', 'description' => 'Open dashboard summaries and reports.'],
        ['slug' => 'shortcode.view', 'name' => 'View Shortcodes', 'page_name' => 'Shortcode', 'action_name' => 'view', 'description' => 'View shortcodes assigned to the user.'],
        ['slug' => 'shortcode.create', 'name' => 'Create Shortcodes', 'page_name' => 'Shortcode', 'action_name' => 'create', 'description' => 'Add new shortcodes.'],
        ['slug' => 'shortcode.update', 'name' => 'Update Shortcodes', 'page_name' => 'Shortcode', 'action_name' => 'update', 'description' => 'Edit shortcode details and notification status.'],
        ['slug' => 'shortcode.delete', 'name' => 'Delete Shortcodes', 'page_name' => 'Shortcode', 'action_name' => 'delete', 'description' => 'Delete shortcodes.'],
        ['slug' => 'services.view', 'name' => 'View Services', 'page_name' => 'Services', 'action_name' => 'view', 'description' => 'View services under assigned shortcodes.'],
        ['slug' => 'services.create', 'name' => 'Create Services', 'page_name' => 'Services', 'action_name' => 'create', 'description' => 'Add new services.'],
        ['slug' => 'services.update', 'name' => 'Update Services', 'page_name' => 'Services', 'action_name' => 'update', 'description' => 'Edit service details.'],
        ['slug' => 'services.delete', 'name' => 'Delete Services', 'page_name' => 'Services', 'action_name' => 'delete', 'description' => 'Delete services.'],
        ['slug' => 'transaction.view', 'name' => 'View Transactions', 'page_name' => 'Transactions', 'action_name' => 'view', 'description' => 'Open the transactions page.'],
        ['slug' => 'transaction.search', 'name' => 'Search Transactions', 'page_name' => 'Transactions', 'action_name' => 'search', 'description' => 'Use transaction search and filtering.'],
        ['slug' => 'transaction.view_msisdn', 'name' => 'View MSISDN', 'page_name' => 'Transactions', 'action_name' => 'view_msisdn', 'description' => 'View full customer phone numbers instead of masked values.'],
        ['slug' => 'users.view', 'name' => 'View Users', 'page_name' => 'User Management', 'action_name' => 'view', 'description' => 'Open the user management page.'],
        ['slug' => 'users.create', 'name' => 'Create Users', 'page_name' => 'User Management', 'action_name' => 'create', 'description' => 'Create new user accounts.'],
        ['slug' => 'users.update', 'name' => 'Update Users', 'page_name' => 'User Management', 'action_name' => 'update', 'description' => 'Edit user profiles, roles, and status.'],
        ['slug' => 'users.delete', 'name' => 'Delete Users', 'page_name' => 'User Management', 'action_name' => 'delete', 'description' => 'Delete users and soft-delete their linked shortcodes/services.'],
        ['slug' => 'users.manage_roles', 'name' => 'Manage Roles', 'page_name' => 'User Management', 'action_name' => 'manage_roles', 'description' => 'Create and edit named roles.'],
        ['slug' => 'users.manage_permissions', 'name' => 'Manage User Permissions', 'page_name' => 'User Management', 'action_name' => 'manage_permissions', 'description' => 'Assign custom per-user permission overrides.'],
        ['slug' => 'audit_logs.view', 'name' => 'View Audit Logs', 'page_name' => 'Audit Logs', 'action_name' => 'view', 'description' => 'View audit log entries.'],
        ['slug' => 'audit_logs.restore', 'name' => 'Restore Audit Records', 'page_name' => 'Audit Logs', 'action_name' => 'restore', 'description' => 'Restore soft-deleted records from eligible audit log entries.'],
    ];

    public function up()
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->boolean('is_system')->default(false);
            $table->timestamps();
        });

        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('page_name');
            $table->string('action_name');
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('permission_role', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('role_id');
            $table->unsignedBigInteger('permission_id');
            $table->timestamps();
            $table->unique(['role_id', 'permission_id']);
        });

        Schema::create('role_user', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('role_id');
            $table->unsignedBigInteger('user_id');
            $table->timestamps();
            $table->unique(['role_id', 'user_id']);
        });

        Schema::create('permission_user', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('permission_id');
            $table->unsignedBigInteger('user_id');
            $table->timestamps();
            $table->unique(['permission_id', 'user_id']);
        });

        $now = now();
        $permissionRows = [];

        foreach ($this->permissions as $permission) {
            $permissionRows[] = array_merge($permission, [
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        DB::table('permissions')->insert($permissionRows);

        $superAdminRoleId = DB::table('roles')->insertGetId([
            'name' => 'Superadmin',
            'slug' => 'superadmin',
            'description' => 'Full access to every page, permission, restore action, and management flow.',
            'is_system' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $customerRoleId = DB::table('roles')->insertGetId([
            'name' => 'Customer/Client',
            'slug' => 'customer-client',
            'description' => 'Starter role for client users. Additional page actions can be granted per role or per user.',
            'is_system' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $permissionMap = DB::table('permissions')->pluck('id', 'slug');

        $superAdminPivotRows = [];

        foreach ($permissionMap as $permissionId) {
            $superAdminPivotRows[] = [
                'role_id' => $superAdminRoleId,
                'permission_id' => $permissionId,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        DB::table('permission_role')->insert($superAdminPivotRows);

        $customerDefaultSlugs = [
            'dashboard.view',
            'shortcode.view',
            'services.view',
            'transaction.view',
            'transaction.search',
        ];

        foreach ($customerDefaultSlugs as $slug) {
            DB::table('permission_role')->insert([
                'role_id' => $customerRoleId,
                'permission_id' => $permissionMap[$slug],
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $legacyEntries = Schema::hasTable('user_roles')
            ? DB::table('user_roles')->get()->groupBy('user_id')
            : collect();

        $legacyMap = [
            'dashboard' => ['dashboard.view'],
            'shortcode' => ['shortcode.view', 'shortcode.create', 'shortcode.update', 'shortcode.delete'],
            'services' => ['services.view', 'services.create', 'services.update', 'services.delete'],
            'transaction' => ['transaction.view', 'transaction.search', 'transaction.view_msisdn'],
            'users' => ['users.view', 'users.create', 'users.update', 'users.delete', 'users.manage_roles', 'users.manage_permissions'],
            'audit_logs' => ['audit_logs.view', 'audit_logs.restore'],
        ];

        foreach (DB::table('users')->select('id')->get() as $user) {
            $entries = $legacyEntries->get($user->id, collect());
            $hasLegacySuperAdmin = $entries->contains(function ($entry) {
                return $entry->access_name === 'super_admin' && ! in_array(strtolower(trim((string) $entry->access_value)), ['', '0', 'false', 'off', 'no'], true);
            });

            $roleId = $hasLegacySuperAdmin || (int) $user->id === 1 ? $superAdminRoleId : $customerRoleId;

            DB::table('role_user')->insert([
                'role_id' => $roleId,
                'user_id' => $user->id,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $customPermissionIds = [];

            foreach ($entries as $entry) {
                if (! isset($legacyMap[$entry->access_name])) {
                    continue;
                }

                if (in_array(strtolower(trim((string) $entry->access_value)), ['', '0', 'false', 'off', 'no'], true)) {
                    continue;
                }

                foreach ($legacyMap[$entry->access_name] as $slug) {
                    $customPermissionIds[$permissionMap[$slug]] = $permissionMap[$slug];
                }
            }

            foreach ($customPermissionIds as $permissionId) {
                DB::table('permission_user')->updateOrInsert(
                    [
                        'permission_id' => $permissionId,
                        'user_id' => $user->id,
                    ],
                    [
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]
                );
            }
        }
    }

    public function down()
    {
        Schema::dropIfExists('permission_user');
        Schema::dropIfExists('role_user');
        Schema::dropIfExists('permission_role');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('roles');
    }
}
