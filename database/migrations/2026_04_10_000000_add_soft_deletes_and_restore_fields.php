<?php
// file: database/migrations/2026_04_10_000000_add_soft_deletes_and_restore_fields.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSoftDeletesAndRestoreFields extends Migration
{
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'deleted_at')) {
                $table->softDeletes();
            }
        });

        Schema::table('shortcodes', function (Blueprint $table) {
            if (! Schema::hasColumn('shortcodes', 'deleted_at')) {
                $table->softDeletes();
            }
        });

        Schema::table('services', function (Blueprint $table) {
            if (! Schema::hasColumn('services', 'deleted_at')) {
                $table->softDeletes();
            }
        });

        Schema::table('audit_logs', function (Blueprint $table) {
            if (! Schema::hasColumn('audit_logs', 'is_restorable')) {
                $table->boolean('is_restorable')->default(false)->after('ip_address');
            }

            if (! Schema::hasColumn('audit_logs', 'restore_payload')) {
                $table->json('restore_payload')->nullable()->after('is_restorable');
            }

            if (! Schema::hasColumn('audit_logs', 'restored_at')) {
                $table->timestamp('restored_at')->nullable()->after('restore_payload');
            }

            if (! Schema::hasColumn('audit_logs', 'restored_by')) {
                $table->unsignedBigInteger('restored_by')->nullable()->after('restored_at');
            }
        });
    }

    public function down()
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            $dropColumns = [];

            foreach (['is_restorable', 'restore_payload', 'restored_at', 'restored_by'] as $column) {
                if (Schema::hasColumn('audit_logs', $column)) {
                    $dropColumns[] = $column;
                }
            }

            if (! empty($dropColumns)) {
                $table->dropColumn($dropColumns);
            }
        });

        Schema::table('services', function (Blueprint $table) {
            if (Schema::hasColumn('services', 'deleted_at')) {
                $table->dropSoftDeletes();
            }
        });

        Schema::table('shortcodes', function (Blueprint $table) {
            if (Schema::hasColumn('shortcodes', 'deleted_at')) {
                $table->dropSoftDeletes();
            }
        });

        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'deleted_at')) {
                $table->dropSoftDeletes();
            }
        });
    }
}
