<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServiceAccountKeyword extends Model
{
    protected $table = 'service_account_keywords';

    protected $fillable = [
        'service_id',
        'keyword_name',
        'match_type',
        'match_pattern',
        'status',
    ];

    protected $casts = [
        'status' => 'boolean',
    ];

    public function service()
    {
        return $this->belongsTo('App\Models\Service');
    }

    public function users()
    {
        return $this->belongsToMany('App\User', 'service_account_keyword_user_access', 'keyword_id', 'user_id')
            ->withPivot('transaction_min_amount', 'transaction_max_amount', 'bypass_amount_limit_history')
            ->withTimestamps();
    }
}
