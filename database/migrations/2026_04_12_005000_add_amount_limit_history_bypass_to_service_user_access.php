<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAmountLimitHistoryBypassToServiceUserAccess extends Migration
{
    public function up()
    {
        if (! Schema::hasTable('service_user_access')) {
            return;
        }

        Schema::table('service_user_access', function (Blueprint $table) {
            if (! Schema::hasColumn('service_user_access', 'bypass_amount_limit_history')) {
                $column = $table->boolean('bypass_amount_limit_history')->default(false);

                if (Schema::hasColumn('service_user_access', 'transaction_max_amount')) {
                    $column->after('transaction_max_amount');
                }
            }
        });
    }

    public function down()
    {
        if (! Schema::hasTable('service_user_access')) {
            return;
        }

        Schema::table('service_user_access', function (Blueprint $table) {
            if (Schema::hasColumn('service_user_access', 'bypass_amount_limit_history')) {
                $table->dropColumn('bypass_amount_limit_history');
            }
        });
    }
}
