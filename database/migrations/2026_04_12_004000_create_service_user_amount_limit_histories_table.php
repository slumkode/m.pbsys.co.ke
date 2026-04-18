<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateServiceUserAmountLimitHistoriesTable extends Migration
{
    public function up()
    {
        if (! Schema::hasTable('service_user_access')) {
            return;
        }

        if (! Schema::hasTable('service_user_amount_limit_histories')) {
            Schema::create('service_user_amount_limit_histories', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('service_id');
                $table->unsignedBigInteger('user_id');
                $table->decimal('transaction_min_amount', 15, 2)->nullable();
                $table->decimal('transaction_max_amount', 15, 2)->nullable();
                $table->dateTime('effective_from')->nullable();
                $table->dateTime('effective_to')->nullable();
                $table->timestamps();
                $table->index(['user_id', 'service_id', 'effective_from'], 'sualh_user_service_from_index');
                $table->index(['user_id', 'service_id', 'effective_to'], 'sualh_user_service_to_index');
            });
        }

        if (! Schema::hasTable('service_user_amount_limit_histories')
            || DB::table('service_user_amount_limit_histories')->exists()) {
            return;
        }

        $now = now();
        $baselineStart = '2000-01-01 00:00:00';
        $rows = DB::table('service_user_access')
            ->select([
                'service_id',
                'user_id',
                'transaction_min_amount',
                'transaction_max_amount',
            ])
            ->get()
            ->map(function ($row) use ($now, $baselineStart) {
                return [
                    'service_id' => $row->service_id,
                    'user_id' => $row->user_id,
                    'transaction_min_amount' => $row->transaction_min_amount,
                    'transaction_max_amount' => $row->transaction_max_amount,
                    'effective_from' => $baselineStart,
                    'effective_to' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            })
            ->all();

        foreach (array_chunk($rows, 500) as $chunk) {
            DB::table('service_user_amount_limit_histories')->insert($chunk);
        }
    }

    public function down()
    {
        Schema::dropIfExists('service_user_amount_limit_histories');
    }
}
