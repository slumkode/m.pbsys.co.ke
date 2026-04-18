<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddServiceTransactionAmountLimits extends Migration
{
    public function up()
    {
        if (! Schema::hasTable('service_user_access')) {
            return;
        }

        Schema::table('service_user_access', function (Blueprint $table) {
            if (! Schema::hasColumn('service_user_access', 'transaction_min_amount')) {
                $table->decimal('transaction_min_amount', 15, 2)->nullable()->after('user_id');
            }

            if (! Schema::hasColumn('service_user_access', 'transaction_max_amount')) {
                $table->decimal('transaction_max_amount', 15, 2)->nullable()->after('transaction_min_amount');
            }
        });

        if (! Schema::hasTable('services') || ! Schema::hasColumn('services', 'user_id')) {
            return;
        }

        $now = now();
        $rows = DB::table('services')
            ->whereNotNull('user_id')
            ->select('id as service_id', 'user_id')
            ->get()
            ->map(function ($service) use ($now) {
                return [
                    'service_id' => $service->service_id,
                    'user_id' => $service->user_id,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            })
            ->all();

        foreach ($rows as $row) {
            DB::table('service_user_access')->updateOrInsert(
                [
                    'service_id' => $row['service_id'],
                    'user_id' => $row['user_id'],
                ],
                [
                    'updated_at' => $row['updated_at'],
                    'created_at' => $row['created_at'],
                ]
            );
        }
    }

    public function down()
    {
        if (! Schema::hasTable('service_user_access')) {
            return;
        }

        Schema::table('service_user_access', function (Blueprint $table) {
            if (Schema::hasColumn('service_user_access', 'transaction_min_amount')) {
                $table->dropColumn('transaction_min_amount');
            }

            if (Schema::hasColumn('service_user_access', 'transaction_max_amount')) {
                $table->dropColumn('transaction_max_amount');
            }
        });
    }
}
