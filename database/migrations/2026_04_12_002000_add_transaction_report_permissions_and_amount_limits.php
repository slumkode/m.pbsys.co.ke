<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddTransactionReportPermissionsAndAmountLimits extends Migration
{
    protected $permissions = [
        [
            'slug' => 'transaction_reports.view',
            'name' => 'View Transaction Reports',
            'page_name' => 'Transaction Reports',
            'action_name' => 'view',
            'description' => 'Open daily transaction report summaries.',
        ],
        [
            'slug' => 'transaction.download',
            'name' => 'Download Transactions',
            'page_name' => 'Transactions',
            'action_name' => 'download',
            'description' => 'Export transaction lists and transaction reports.',
        ],
        [
            'slug' => 'transaction.amount_range',
            'name' => 'Transaction Amount Range Limit',
            'page_name' => 'Transactions',
            'action_name' => 'amount_range',
            'description' => 'Limit visible transactions and reports to the min/max amount set on the user account.',
        ],
    ];

    public function up()
    {
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                if (! Schema::hasColumn('users', 'transaction_min_amount')) {
                    $table->decimal('transaction_min_amount', 15, 2)->nullable()->after('status');
                }

                if (! Schema::hasColumn('users', 'transaction_max_amount')) {
                    $table->decimal('transaction_max_amount', 15, 2)->nullable()->after('transaction_min_amount');
                }
            });
        }

        if (! Schema::hasTable('permissions')) {
            return;
        }

        $now = now();

        foreach ($this->permissions as $permission) {
            DB::table('permissions')->updateOrInsert(
                ['slug' => $permission['slug']],
                array_merge($permission, [
                    'created_at' => $now,
                    'updated_at' => $now,
                ])
            );
        }

        if (! Schema::hasTable('roles') || ! Schema::hasTable('permission_role')) {
            return;
        }

        $superAdminRoleId = DB::table('roles')->where('slug', 'superadmin')->value('id');
        $permissionIds = DB::table('permissions')
            ->whereIn('slug', array_column($this->permissions, 'slug'))
            ->pluck('id')
            ->all();

        if ($superAdminRoleId && ! empty($permissionIds)) {
            foreach ($permissionIds as $permissionId) {
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
    }

    public function down()
    {
        if (Schema::hasTable('permissions')) {
            $permissionIds = DB::table('permissions')
                ->whereIn('slug', array_column($this->permissions, 'slug'))
                ->pluck('id')
                ->all();

            if (! empty($permissionIds) && Schema::hasTable('permission_role')) {
                DB::table('permission_role')->whereIn('permission_id', $permissionIds)->delete();
            }

            if (! empty($permissionIds) && Schema::hasTable('permission_user')) {
                DB::table('permission_user')->whereIn('permission_id', $permissionIds)->delete();
            }

            DB::table('permissions')->whereIn('slug', array_column($this->permissions, 'slug'))->delete();
        }

        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                if (Schema::hasColumn('users', 'transaction_min_amount')) {
                    $table->dropColumn('transaction_min_amount');
                }

                if (Schema::hasColumn('users', 'transaction_max_amount')) {
                    $table->dropColumn('transaction_max_amount');
                }
            });
        }
    }
}
