<?php
// file: database/migrations/2020_03_14_042315_transactions.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class Transactions extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('transactions',function(Blueprint $table){
            $table->id();
            $table->string("account");
            $table->string("transaction_code");
            $table->string("type");
            $table->integer('shortcode_id')->unsigned();
            $table->string('msisdn');
            $table->integer('has_notified')->default(0);
            $table->string('channel');
            $table->string('source');
            $table->timestamp('trans_time');
            $table->decimal('amount',8,2);
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
        Schema::dropIfExists('transactions');
    }
}
