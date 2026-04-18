<?php
// file: database/migrations/2026_04_10_000400_add_audit_log_listing_indexes.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAuditLogListingIndexes extends Migration
{
    public function up()
    {
        if (! Schema::hasTable('audit_logs')) {
            return;
        }

        Schema::table('audit_logs', function (Blueprint $table) {
            $table->index('created_at', 'audit_logs_created_at_listing_index');
            $table->index('action', 'audit_logs_action_listing_index');
            $table->index('page_name', 'audit_logs_page_name_listing_index');
            $table->index('auditable_type', 'audit_logs_type_listing_index');
            $table->index('auditable_id', 'audit_logs_auditable_id_listing_index');
            $table->index('user_id', 'audit_logs_user_id_listing_index');
        });
    }

    public function down()
    {
        if (! Schema::hasTable('audit_logs')) {
            return;
        }

        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropIndex('audit_logs_created_at_listing_index');
            $table->dropIndex('audit_logs_action_listing_index');
            $table->dropIndex('audit_logs_page_name_listing_index');
            $table->dropIndex('audit_logs_type_listing_index');
            $table->dropIndex('audit_logs_auditable_id_listing_index');
            $table->dropIndex('audit_logs_user_id_listing_index');
        });
    }
}
