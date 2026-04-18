<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    protected $table = 'audit_logs';

    protected $fillable = [
        'user_id',
        'user_name',
        'action',
        'auditable_type',
        'auditable_id',
        'auditable_label',
        'page_name',
        'old_values',
        'new_values',
        'url',
        'ip_address',
        'is_restorable',
        'restore_payload',
        'restored_at',
        'restored_by',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'restore_payload' => 'array',
        'is_restorable' => 'boolean',
        'restored_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo('App\User');
    }

    public function restoredBy()
    {
        return $this->belongsTo('App\User', 'restored_by');
    }

    public function canBeRestored()
    {
        return $this->is_restorable && $this->restored_at === null && ! empty($this->restore_payload);
    }
}
