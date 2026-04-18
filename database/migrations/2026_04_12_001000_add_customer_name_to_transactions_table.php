<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCustomerNameToTransactionsTable extends Migration
{
    public function up()
    {
        if (Schema::hasTable('transactions') && ! Schema::hasColumn('transactions', 'customer_name')) {
            Schema::table('transactions', function (Blueprint $table) {
                $table->string('customer_name')->nullable()->after('source');
            });
        }
    }

    public function down()
    {
        if (Schema::hasTable('transactions') && Schema::hasColumn('transactions', 'customer_name')) {
            Schema::table('transactions', function (Blueprint $table) {
                $table->dropColumn('customer_name');
            });
        }
    }
}
