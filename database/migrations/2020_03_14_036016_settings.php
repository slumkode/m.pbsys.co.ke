<?php
// file: database/migrations/2020_03_14_036016_settings.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class Settings extends Migration
    {
        /**
         * Run the migrations.
         *
         * @return void
         */
        public function up()
            {
                Schema::create('settings', function (Blueprint $table) {
                    $table->id();
                    $table->integer('shortcode_id')->unsigned();
                    $table->string('meta_name');
                    $table->string('meta_value');
                    $table->timestamps();
                });
            }

        /**
         * Reverse the migrations.
         *
         * @return void
         */
        public function down()
            {
                Schema::dropIfExists('settings');
            }
    }
