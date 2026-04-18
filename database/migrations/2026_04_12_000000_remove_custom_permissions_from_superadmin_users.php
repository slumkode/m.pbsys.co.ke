<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class RemoveCustomPermissionsFromSuperadminUsers extends Migration
{
    public function up()
    {
        if (
            ! Schema::hasTable('roles')
            || ! Schema::hasTable('role_user')
            || ! Schema::hasTable('permission_user')
        ) {
            return;
        }

        $superAdminRoleIds = DB::table('roles')
            ->where('slug', 'superadmin')
            ->pluck('id')
            ->all();

        if (empty($superAdminRoleIds)) {
            return;
        }

        $superAdminUserIds = DB::table('role_user')
            ->whereIn('role_id', $superAdminRoleIds)
            ->pluck('user_id')
            ->all();

        if (empty($superAdminUserIds)) {
            return;
        }

        DB::table('permission_user')
            ->whereIn('user_id', $superAdminUserIds)
            ->delete();
    }

    public function down()
    {
        // Custom overrides for Superadmin are intentionally not restored.
    }
}
