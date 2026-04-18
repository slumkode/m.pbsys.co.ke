<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
    {
        protected $table = "transactions";

        protected $fillable = [
            'account',
            'transaction_code',
            'type',
            'shortcode_id',
            'msisdn',
            'has_notified',
            'channel',
            'source',
            'customer_name',
            'trans_time',
            'amount',
        ];

        public function service()
            {
                return $this->belongsTo('App\Models\Service');
            }
    }
