<?php
// file: database/migrations/2020_03_14_035943_shortcode.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class Shortcode extends Migration
    {
        /**
         * Run the migrations.
         *
         * @return void
         */
        public function up()
            {
                Schema::create('shortcodes',function (Blueprint $table){
                    $table->id();
                    $table->string('shortcode')->unique();
                    $table->boolean('status')->default(false);
                    $table->text('shortcode_type');
                    $table->string('consumerkey');
                    $table->string('consumersecret');
                    $table->string('passkey');
                    $table->integer('user_id')->unsigned();
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
                Schema::dropIfExists('shortcodes');
            }
    }
