<?php
// file: app/Models/Permission.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Permission extends Model
{
    protected $table = 'permissions';

    protected $fillable = [
        'name',
        'slug',
        'page_name',
        'action_name',
        'description',
    ];

    public function roles()
    {
        return $this->belongsToMany('App\Models\Role', 'permission_role')->withTimestamps();
    }

    public function users()
    {
        return $this->belongsToMany('App\User', 'permission_user')->withTimestamps();
    }
}
