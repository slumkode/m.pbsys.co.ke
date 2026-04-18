<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddLoginActivityIdToAuditLogsTable extends Migration
{
    public function up()
    {
        if (! Schema::hasTable('audit_logs') || Schema::hasColumn('audit_logs', 'login_activity_id')) {
            return;
        }

        Schema::table('audit_logs', function (Blueprint $table) {
            $table->unsignedBigInteger('login_activity_id')->nullable()->after('user_id');
            $table->index('login_activity_id');
        });
    }

    public function down()
    {
        if (! Schema::hasTable('audit_logs') || ! Schema::hasColumn('audit_logs', 'login_activity_id')) {
            return;
        }

        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropIndex(['login_activity_id']);
            $table->dropColumn('login_activity_id');
        });
    }
}
