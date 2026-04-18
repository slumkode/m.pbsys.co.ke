<?php
// file: database/migrations/2020_03_14_036016_settings.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class Options extends Migration
    {
        /**
         * Run the migrations.
         *
         * @return void
         */
        public function up()
            {
                Schema::create('options',function (Blueprint $table){
                    $table->id();
                    $table->string('meta_name');
                    $table->text('meta_value')->nullable();
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
                Schema::dropIfExists('options');
            }
    }
