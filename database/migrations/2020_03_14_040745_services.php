<?php
// file: database/migrations/2020_03_14_040745_services.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class Services extends Migration
    {
        /**
         * Run the migrations.
         *
         * @return void
         */
        public function up()
            {
                Schema::create('services',function(Blueprint $table){
                    $table->id();
                    $table->integer('shortcode_id')->unsigned();
                    $table->string('service_name');
                    $table->text('service_description')->nullable();
                    $table->string('prefix')->nullable();
                    $table->string('verification_url')->nullable();
                    $table->text('callback_url');
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
                Schema::dropIfExists('services');
            }
    }
