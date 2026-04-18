<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddAuditLogViewAllPermission extends Migration
{
    public function up()
    {
        if (! Schema::hasTable('permissions')) {
            return;
        }

        $now = now();

        DB::table('permissions')->updateOrInsert(
            ['slug' => 'audit_logs.view_all'],
            [
                'name' => 'View All Audit Logs',
                'page_name' => 'Audit Logs',
                'action_name' => 'view_all',
                'description' => 'View every audit log in the system instead of only the actions performed by the logged-in user.',
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        if (! Schema::hasTable('roles') || ! Schema::hasTable('permission_role')) {
            return;
        }

        $superAdminRoleId = DB::table('roles')->where('slug', 'superadmin')->value('id');
        $permissionId = DB::table('permissions')->where('slug', 'audit_logs.view_all')->value('id');

        if ($superAdminRoleId && $permissionId) {
            DB::table('permission_role')->updateOrInsert(
                [
                    'role_id' => $superAdminRoleId,
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
        if (! Schema::hasTable('permissions')) {
            return;
        }

        $permissionId = DB::table('permissions')->where('slug', 'audit_logs.view_all')->value('id');

        if ($permissionId && Schema::hasTable('permission_role')) {
            DB::table('permission_role')->where('permission_id', $permissionId)->delete();
        }

        DB::table('permissions')->where('slug', 'audit_logs.view_all')->delete();
    }
}
