<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddShortcodeSharingAndVisibilityAssignments extends Migration
{
    public function up()
    {
        if (Schema::hasTable('shortcodes') && ! Schema::hasColumn('shortcodes', 'sharing_mode')) {
            Schema::table('shortcodes', function (Blueprint $table) {
                $table->string('sharing_mode')->default('dedicated')->after('group');
                $table->index('sharing_mode', 'shortcodes_sharing_mode_index');
            });
        }

        if (Schema::hasTable('services') && ! Schema::hasColumn('services', 'user_id')) {
            Schema::table('services', function (Blueprint $table) {
                $table->unsignedBigInteger('user_id')->nullable()->after('shortcode_id');
                $table->index('user_id', 'services_user_id_index');
            });
        }

        if (Schema::hasTable('services') && Schema::hasColumn('services', 'user_id') && Schema::hasTable('shortcodes')) {
            DB::statement('
                UPDATE services
                INNER JOIN shortcodes ON shortcodes.id = services.shortcode_id
                SET services.user_id = shortcodes.user_id
                WHERE services.user_id IS NULL
            ');
        }

        if (! Schema::hasTable('shortcode_user_access')) {
            Schema::create('shortcode_user_access', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('shortcode_id');
                $table->unsignedBigInteger('user_id');
                $table->timestamps();
                $table->unique(['shortcode_id', 'user_id'], 'shortcode_user_access_unique');
            });
        }

        if (! Schema::hasTable('service_user_access')) {
            Schema::create('service_user_access', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('service_id');
                $table->unsignedBigInteger('user_id');
                $table->timestamps();
                $table->unique(['service_id', 'user_id'], 'service_user_access_unique');
            });
        }

        if (Schema::hasTable('services') && Schema::hasColumn('services', 'user_id') && Schema::hasTable('service_user_access')) {
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

            if (! empty($rows)) {
                DB::table('service_user_access')->upsert(
                    $rows,
                    ['service_id', 'user_id'],
                    ['updated_at']
                );
            }
        }
    }

    public function down()
    {
        if (Schema::hasTable('service_user_access')) {
            Schema::dropIfExists('service_user_access');
        }

        if (Schema::hasTable('shortcode_user_access')) {
            Schema::dropIfExists('shortcode_user_access');
        }

        if (Schema::hasTable('services') && Schema::hasColumn('services', 'user_id')) {
            Schema::table('services', function (Blueprint $table) {
                $table->dropIndex('services_user_id_index');
                $table->dropColumn('user_id');
            });
        }

        if (Schema::hasTable('shortcodes') && Schema::hasColumn('shortcodes', 'sharing_mode')) {
            Schema::table('shortcodes', function (Blueprint $table) {
                $table->dropIndex('shortcodes_sharing_mode_index');
                $table->dropColumn('sharing_mode');
            });
        }
    }
}
