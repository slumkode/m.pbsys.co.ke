<?php
// file: app/Models/Role.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    protected $table = 'roles';

    protected $fillable = [
        'name',
        'slug',
        'description',
        'is_system',
    ];

    protected $casts = [
        'is_system' => 'boolean',
    ];

    public function permissions()
    {
        return $this->belongsToMany('App\Models\Permission', 'permission_role')->withTimestamps();
    }

    public function users()
    {
        return $this->belongsToMany('App\User', 'role_user')->withTimestamps();
    }
}
