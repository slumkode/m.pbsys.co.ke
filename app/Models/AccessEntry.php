<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AccessEntry extends Model
{
    protected $table = 'user_roles';

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'access_name',
        'access_value',
    ];

    public function user()
    {
        return $this->belongsTo('App\User');
    }
}
