<?php
// file: app/Models/Service.php

namespace App\Models;

use App\Traits\ConditionalSoftDeletes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class Service extends Model
{
    use ConditionalSoftDeletes;

    protected $table = 'services';

    public function shortcode()
    {
        return $this->belongsTo('App\Models\Shortcode');
    }

    public function user()
    {
        return $this->belongsTo('App\User');
    }

    public function viewers()
    {
        $relation = $this->belongsToMany('App\User', 'service_user_access')->withTimestamps();

        if (Schema::hasTable('service_user_access')
            && Schema::hasColumn('service_user_access', 'transaction_min_amount')
            && Schema::hasColumn('service_user_access', 'transaction_max_amount')) {
            $relation->withPivot('transaction_min_amount', 'transaction_max_amount');
        }

        if (Schema::hasTable('service_user_access')
            && Schema::hasColumn('service_user_access', 'bypass_amount_limit_history')) {
            $relation->withPivot('bypass_amount_limit_history');
        }

        return $relation;
    }
}
