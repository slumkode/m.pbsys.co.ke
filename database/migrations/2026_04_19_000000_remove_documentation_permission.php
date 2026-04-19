<?php
// file: database/migrations/2026_04_19_000000_remove_documentation_permission.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class RemoveDocumentationPermission extends Migration
{
    public function up()
    {
        if (! Schema::hasTable('permissions')) {
            return;
        }

        $permissionId = DB::table('permissions')->where('slug', 'documentation.view')->value('id');

        if (! $permissionId) {
            return;
        }

        if (Schema::hasTable('permission_role')) {
            DB::table('permission_role')->where('permission_id', $permissionId)->delete();
        }

        if (Schema::hasTable('permission_user')) {
            DB::table('permission_user')->where('permission_id', $permissionId)->delete();
        }

        DB::table('permissions')->where('id', $permissionId)->delete();
    }

    public function down()
    {
        if (! Schema::hasTable('permissions')) {
            return;
        }

        $now = now();

        DB::table('permissions')->updateOrInsert(
            ['slug' => 'documentation.view'],
            [
                'name' => 'View Documentation',
                'page_name' => 'API Documentation',
                'action_name' => 'view',
                'description' => 'Open the MPesa API documentation and sandbox page.',
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        if (! Schema::hasTable('roles') || ! Schema::hasTable('permission_role')) {
            return;
        }

        $permissionId = DB::table('permissions')->where('slug', 'documentation.view')->value('id');

        if (! $permissionId) {
            return;
        }

        $roleIds = DB::table('roles')
            ->whereIn('slug', ['superadmin', 'customer-client', 'internal-user'])
            ->pluck('id')
            ->all();

        foreach ($roleIds as $roleId) {
            DB::table('permission_role')->updateOrInsert(
                [
                    'role_id' => $roleId,
                    'permission_id' => $permissionId,
                ],
                [
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }
    }
}
