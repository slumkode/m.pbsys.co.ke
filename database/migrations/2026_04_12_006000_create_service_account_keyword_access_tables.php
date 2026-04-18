<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateServiceAccountKeywordAccessTables extends Migration
{
    public function up()
    {
        if (! Schema::hasTable('services')) {
            return;
        }

        if (! Schema::hasTable('service_account_keywords')) {
            Schema::create('service_account_keywords', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('service_id');
                $table->string('keyword_name');
                $table->string('match_type', 255)->default('contains');
                $table->text('match_pattern');
                $table->boolean('status')->default(true);
                $table->timestamps();
                $table->index(['service_id', 'status'], 'sakw_service_status_index');
                $table->index('match_type', 'sakw_match_type_index');
            });
        }

        if (! Schema::hasTable('service_account_keyword_user_access')) {
            Schema::create('service_account_keyword_user_access', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('keyword_id');
                $table->unsignedBigInteger('user_id');
                $table->decimal('transaction_min_amount', 15, 2)->nullable();
                $table->decimal('transaction_max_amount', 15, 2)->nullable();
                $table->boolean('bypass_amount_limit_history')->default(false);
                $table->timestamps();
                $table->unique(['keyword_id', 'user_id'], 'sakwua_keyword_user_unique');
                $table->index(['user_id', 'keyword_id'], 'sakwua_user_keyword_index');
            });
        }

        if (! Schema::hasTable('service_account_keyword_limit_histories')) {
            Schema::create('service_account_keyword_limit_histories', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('keyword_id');
                $table->unsignedBigInteger('user_id');
                $table->decimal('transaction_min_amount', 15, 2)->nullable();
                $table->decimal('transaction_max_amount', 15, 2)->nullable();
                $table->dateTime('effective_from')->nullable();
                $table->dateTime('effective_to')->nullable();
                $table->timestamps();
                $table->index(['user_id', 'keyword_id', 'effective_from'], 'saklh_user_keyword_from_index');
                $table->index(['user_id', 'keyword_id', 'effective_to'], 'saklh_user_keyword_to_index');
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('service_account_keyword_limit_histories');
        Schema::dropIfExists('service_account_keyword_user_access');
        Schema::dropIfExists('service_account_keywords');
    }
}
