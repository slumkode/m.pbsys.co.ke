<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserLoginActivitiesTable extends Migration
{
    public function up()
    {
        if (Schema::hasTable('user_login_activities')) {
            return;
        }

        Schema::create('user_login_activities', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('session_id', 120)->nullable();
            $table->boolean('remembered')->default(false);
            $table->timestamp('login_at')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamp('logout_at')->nullable();
            $table->text('last_url')->nullable();
            $table->text('previous_url')->nullable();
            $table->string('last_route_name')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('previous_ip_address', 45)->nullable();
            $table->timestamp('ip_changed_at')->nullable();
            $table->text('user_agent')->nullable();
            $table->string('browser')->nullable();
            $table->string('platform')->nullable();
            $table->string('device_type')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->decimal('location_accuracy', 10, 2)->nullable();
            $table->timestamp('location_captured_at')->nullable();
            $table->decimal('previous_latitude', 10, 7)->nullable();
            $table->decimal('previous_longitude', 10, 7)->nullable();
            $table->timestamp('previous_location_captured_at')->nullable();
            $table->timestamp('location_changed_at')->nullable();
            $table->timestamps();

            $table->index('user_id');
            $table->index('session_id');
            $table->index('login_at');
            $table->index('last_seen_at');
            $table->index('logout_at');
        });
    }

    public function down()
    {
        Schema::dropIfExists('user_login_activities');
    }
}
