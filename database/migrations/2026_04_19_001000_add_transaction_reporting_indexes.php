<?php
// file: database/migrations/2026_04_19_001000_add_transaction_reporting_indexes.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddTransactionReportingIndexes extends Migration
{
    protected $indexes = [
        'transactions_time_reporting_index' => [
            'mysql' => 'ALTER TABLE `transactions` ADD INDEX `transactions_time_reporting_index` (`trans_time`)',
            'columns' => ['trans_time'],
        ],
        'transactions_service_time_reporting_index' => [
            'mysql' => 'ALTER TABLE `transactions` ADD INDEX `transactions_service_time_reporting_index` (`shortcode_id`, `type`(191), `trans_time`)',
            'columns' => ['shortcode_id', 'type', 'trans_time'],
        ],
        'transactions_time_service_reporting_index' => [
            'mysql' => 'ALTER TABLE `transactions` ADD INDEX `transactions_time_service_reporting_index` (`trans_time`, `shortcode_id`, `type`(191))',
            'columns' => ['trans_time', 'shortcode_id', 'type'],
        ],
        'transactions_code_reporting_index' => [
            'mysql' => 'ALTER TABLE `transactions` ADD INDEX `transactions_code_reporting_index` (`transaction_code`(191))',
            'columns' => ['transaction_code'],
        ],
        'transactions_account_reporting_index' => [
            'mysql' => 'ALTER TABLE `transactions` ADD INDEX `transactions_account_reporting_index` (`account`(191))',
            'columns' => ['account'],
        ],
    ];

    public function up()
    {
        if (! Schema::hasTable('transactions')) {
            return;
        }

        foreach ($this->indexes as $name => $definition) {
            if ($this->indexExists($name)) {
                continue;
            }

            if ($this->usesMySql()) {
                DB::statement($definition['mysql']);
                continue;
            }

            Schema::table('transactions', function (Blueprint $table) use ($name, $definition) {
                $table->index($definition['columns'], $name);
            });
        }
    }

    public function down()
    {
        if (! Schema::hasTable('transactions')) {
            return;
        }

        foreach (array_keys($this->indexes) as $name) {
            if (! $this->indexExists($name)) {
                continue;
            }

            if ($this->usesMySql()) {
                DB::statement("ALTER TABLE `transactions` DROP INDEX `{$name}`");
                continue;
            }

            Schema::table('transactions', function (Blueprint $table) use ($name) {
                $table->dropIndex($name);
            });
        }
    }

    protected function indexExists($name)
    {
        if (DB::getDriverName() === 'sqlite') {
            $result = DB::selectOne(
                "SELECT COUNT(1) as aggregate
                 FROM sqlite_master
                 WHERE type = 'index'
                   AND name = ?",
                [$name]
            );

            return $result && (int) $result->aggregate > 0;
        }

        if (! $this->usesMySql()) {
            return false;
        }

        $result = DB::selectOne(
            "SELECT COUNT(1) as aggregate
             FROM information_schema.statistics
             WHERE table_schema = DATABASE()
               AND table_name = 'transactions'
               AND index_name = ?",
            [$name]
        );

        return $result && (int) $result->aggregate > 0;
    }

    protected function usesMySql()
    {
        return DB::getDriverName() === 'mysql';
    }
}
