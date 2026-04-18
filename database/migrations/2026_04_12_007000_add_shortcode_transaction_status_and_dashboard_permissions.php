<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddShortcodeTransactionStatusAndDashboardPermissions extends Migration
{
    protected $dashboardPermissions = [
        [
            'slug' => 'dashboard.search',
            'name' => 'Search Dashboard Dates',
            'page_name' => 'Dashboard',
            'action_name' => 'search',
            'description' => 'Change the dashboard date range. Without this, the dashboard stays on today.',
        ],
        [
            'slug' => 'dashboard.reports',
            'name' => 'View Dashboard Report Cards',
            'page_name' => 'Dashboard',
            'action_name' => 'reports',
            'description' => 'View service and keyword report cards below the dashboard summary.',
        ],
    ];

    public function up()
    {
        if (Schema::hasTable('shortcodes')) {
            Schema::table('shortcodes', function (Blueprint $table) {
                if (! Schema::hasColumn('shortcodes', 'transaction_status_initiator')) {
                    $table->string('transaction_status_initiator')->nullable()->after('passkey');
                }

                if (! Schema::hasColumn('shortcodes', 'transaction_status_credential')) {
                    $table->text('transaction_status_credential')->nullable()->after('transaction_status_initiator');
                }

                if (! Schema::hasColumn('shortcodes', 'transaction_status_credential_encrypted')) {
                    $table->boolean('transaction_status_credential_encrypted')->default(false)->after('transaction_status_credential');
                }

                if (! Schema::hasColumn('shortcodes', 'transaction_status_identifier')) {
                    $table->string('transaction_status_identifier', 30)->nullable()->default('shortcode')->after('transaction_status_credential_encrypted');
                }
            });
        }

        if (Schema::hasTable('service_account_keywords') && Schema::hasColumn('service_account_keywords', 'match_type')) {
            DB::statement("ALTER TABLE service_account_keywords MODIFY match_type VARCHAR(255) NOT NULL DEFAULT 'contains'");
        }

        if (! Schema::hasTable('permissions')) {
            return;
        }

        $now = now();

        foreach ($this->dashboardPermissions as $permission) {
            DB::table('permissions')->updateOrInsert(
                ['slug' => $permission['slug']],
                array_merge($permission, [
                    'created_at' => $now,
                    'updated_at' => $now,
                ])
            );
        }

        $this->grantDashboardPermissionsToExistingDashboardRoles($now);
        $this->removePermission('transaction.amount_range');
    }

    public function down()
    {
        if (Schema::hasTable('permissions')) {
            foreach ($this->dashboardPermissions as $permission) {
                $this->removePermission($permission['slug']);
            }
        }

        if (Schema::hasTable('shortcodes')) {
            Schema::table('shortcodes', function (Blueprint $table) {
                if (Schema::hasColumn('shortcodes', 'transaction_status_identifier')) {
                    $table->dropColumn('transaction_status_identifier');
                }

                if (Schema::hasColumn('shortcodes', 'transaction_status_credential_encrypted')) {
                    $table->dropColumn('transaction_status_credential_encrypted');
                }

                if (Schema::hasColumn('shortcodes', 'transaction_status_credential')) {
                    $table->dropColumn('transaction_status_credential');
                }

                if (Schema::hasColumn('shortcodes', 'transaction_status_initiator')) {
                    $table->dropColumn('transaction_status_initiator');
                }
            });
        }
    }

    protected function grantDashboardPermissionsToExistingDashboardRoles($now)
    {
        if (! Schema::hasTable('roles') || ! Schema::hasTable('permission_role')) {
            return;
        }

        $dashboardViewPermissionId = DB::table('permissions')->where('slug', 'dashboard.view')->value('id');

        if (! $dashboardViewPermissionId) {
            return;
        }

        $roleIds = DB::table('permission_role')
            ->where('permission_id', $dashboardViewPermissionId)
            ->pluck('role_id')
            ->all();
        $permissionIds = DB::table('permissions')
            ->whereIn('slug', array_column($this->dashboardPermissions, 'slug'))
            ->pluck('id')
            ->all();

        foreach ($roleIds as $roleId) {
            foreach ($permissionIds as $permissionId) {
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

    protected function removePermission($slug)
    {
        $permissionId = DB::table('permissions')->where('slug', $slug)->value('id');

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
}
