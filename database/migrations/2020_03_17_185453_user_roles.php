<?php
// file: database/migrations/2020_03_14_036016_settings.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UserRoles extends Migration
    {
        /**
         * Run the migrations.
         *
         * @return void
         */
        public function up()
            {
                Schema::create('user_roles',function(Blueprint $table){
                    $table->id();
                    $table->integer('user_id')->unsigned();
                    $table->string('access_name');
                    $table->text('access_value');
                });
            }

        /**
         * Reverse the migrations.
         *
         * @return void
         */
        public function down()
            {
                Schema::dropIfExists('user_roles');
            }
    }
