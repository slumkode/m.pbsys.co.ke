<?php
// file: database/migrations/2020_05_22_125932_create_phone_numbers_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePhoneNumbersTable extends Migration
    {
        /**
         * Run the migrations.
         *
         * @return void
         */
        public function up()
            {
                Schema::create('phone_numbers', function (Blueprint $table) {
                        $table->id();
                        $table->string("customer_name");
                        $table->string('phone_number')->unique();
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
                Schema::dropIfExists('phone_numbers');
            }
    }
