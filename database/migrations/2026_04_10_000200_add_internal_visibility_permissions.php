<?php
// file: database/migrations/2026_04_10_000200_add_internal_visibility_permissions.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddInternalVisibilityPermissions extends Migration
{
    public function up()
    {
        if (! Schema::hasTable('permissions') || ! Schema::hasTable('roles')) {
            return;
        }

        $now = now();
        $permissions = [
            [
                'slug' => 'shortcode.view_all',
                'name' => 'View All Shortcodes',
                'page_name' => 'Shortcode',
                'action_name' => 'view_all',
                'description' => 'View every shortcode in the system, not only assigned shortcodes.',
            ],
            [
                'slug' => 'services.view_all',
                'name' => 'View All Services',
                'page_name' => 'Services',
                'action_name' => 'view_all',
                'description' => 'View every service in the system, not only services under assigned shortcodes.',
            ],
            [
                'slug' => 'transaction.view_all',
                'name' => 'View All Transactions',
                'page_name' => 'Transactions',
                'action_name' => 'view_all',
                'description' => 'View transactions across every shortcode in the system.',
            ],
        ];

        foreach ($permissions as $permission) {
            DB::table('permissions')->updateOrInsert(
                ['slug' => $permission['slug']],
                array_merge($permission, [
                    'created_at' => $now,
                    'updated_at' => $now,
                ])
            );
        }

        $internalRoleId = DB::table('roles')->where('slug', 'internal-user')->value('id');

        if (! $internalRoleId) {
            $internalRoleId = DB::table('roles')->insertGetId([
                'name' => 'Internal User',
                'slug' => 'internal-user',
                'description' => 'Internal staff role with cross-account visibility for operational review.',
                'is_system' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $permissionIds = DB::table('permissions')
            ->whereIn('slug', [
                'dashboard.view',
                'documentation.view',
                'shortcode.view',
                'shortcode.view_all',
                'services.view',
                'services.view_all',
                'transaction.view',
                'transaction.view_all',
                'transaction.search',
                'transaction.view_msisdn',
            ])
            ->pluck('id')
            ->all();

        foreach ($permissionIds as $permissionId) {
            DB::table('permission_role')->updateOrInsert(
                [
                    'role_id' => $internalRoleId,
                    'permission_id' => $permissionId,
                ],
                [
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }
    }

    public function down()
    {
        if (! Schema::hasTable('permissions') || ! Schema::hasTable('roles')) {
            return;
        }

        $permissionSlugs = ['shortcode.view_all', 'services.view_all', 'transaction.view_all'];
        $permissionIds = DB::table('permissions')->whereIn('slug', $permissionSlugs)->pluck('id')->all();
        $internalRoleId = DB::table('roles')->where('slug', 'internal-user')->value('id');

        if ($internalRoleId && ! empty($permissionIds)) {
            DB::table('permission_role')
                ->where('role_id', $internalRoleId)
                ->whereIn('permission_id', $permissionIds)
                ->delete();
        }

        DB::table('permissions')->whereIn('slug', $permissionSlugs)->delete();
    }
}
