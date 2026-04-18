<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddShortcodeAssignOwnerPermission extends Migration
{
    public function up()
    {
        if (! Schema::hasTable('permissions')) {
            return;
        }

        $now = now();

        DB::table('permissions')->updateOrInsert(
            ['slug' => 'shortcode.assign_owner'],
            [
                'name' => 'Assign Shortcode Owner',
                'page_name' => 'Shortcode',
                'action_name' => 'assign_owner',
                'description' => 'Choose or change the owner of a shortcode during create or update.',
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        if (! Schema::hasTable('roles') || ! Schema::hasTable('permission_role')) {
            return;
        }

        $superAdminRoleId = DB::table('roles')->where('slug', 'superadmin')->value('id');
        $permissionId = DB::table('permissions')->where('slug', 'shortcode.assign_owner')->value('id');

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

        $permissionId = DB::table('permissions')->where('slug', 'shortcode.assign_owner')->value('id');

        if ($permissionId && Schema::hasTable('permission_role')) {
            DB::table('permission_role')->where('permission_id', $permissionId)->delete();
        }

        DB::table('permissions')->where('slug', 'shortcode.assign_owner')->delete();
    }
}
