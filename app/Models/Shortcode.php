<?php
// file: app/Models/Shortcode.php

namespace App\Models;

use App\Traits\ConditionalSoftDeletes;
use Illuminate\Database\Eloquent\Model;

class Shortcode extends Model
{
    use ConditionalSoftDeletes;

    protected $table = 'shortcodes';

    protected $casts = [
        'status' => 'boolean',
        'transaction_status_credential_encrypted' => 'boolean',
    ];

    public function settings()
    {
        return $this->hasMany('App\Models\Setting');
    }

    public function service()
    {
        return $this->hasMany('App\Models\Service');
    }

    public function user()
    {
        return $this->belongsTo('App\User');
    }

    public function viewers()
    {
        return $this->belongsToMany('App\User', 'shortcode_user_access')->withTimestamps();
    }

    public function isShared()
    {
        return ($this->sharing_mode ?? 'dedicated') === 'shared';
    }

    public function isDedicated()
    {
        return ! $this->isShared();
    }
}
