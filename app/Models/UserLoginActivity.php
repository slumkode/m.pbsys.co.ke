<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserLoginActivity extends Model
{
    protected $table = 'user_login_activities';

    protected $fillable = [
        'user_id',
        'session_id',
        'remembered',
        'login_at',
        'last_seen_at',
        'logout_at',
        'last_url',
        'previous_url',
        'last_route_name',
        'ip_address',
        'previous_ip_address',
        'ip_changed_at',
        'user_agent',
        'browser',
        'platform',
        'device_type',
        'latitude',
        'longitude',
        'location_accuracy',
        'location_captured_at',
        'previous_latitude',
        'previous_longitude',
        'previous_location_captured_at',
        'location_changed_at',
    ];

    protected $casts = [
        'remembered' => 'boolean',
        'login_at' => 'datetime',
        'last_seen_at' => 'datetime',
        'logout_at' => 'datetime',
        'ip_changed_at' => 'datetime',
        'location_captured_at' => 'datetime',
        'previous_location_captured_at' => 'datetime',
        'location_changed_at' => 'datetime',
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
        'location_accuracy' => 'decimal:2',
        'previous_latitude' => 'decimal:7',
        'previous_longitude' => 'decimal:7',
    ];

    public function user()
    {
        return $this->belongsTo('App\User');
    }

    public function auditLogs()
    {
        return $this->hasMany('App\Models\AuditLog', 'login_activity_id');
    }
}
