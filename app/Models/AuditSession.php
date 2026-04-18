<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AuditSession extends Model
{
    use HasFactory;

    public const STATUS_OPEN = 'OPEN';
    public const STATUS_FINALIZED = 'FINALIZED';

    protected $fillable = [
        'school_year',
        'started_at',
        'ended_at',
        'created_by',
        'status',
        'include_borrowed',
        'include_lost_damaged',
        'notes',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'include_borrowed' => 'boolean',
        'include_lost_damaged' => 'boolean',
    ];

    public function creator()
    {
        return $this->belongsTo(SystemUser::class, 'created_by');
    }

    public function logs()
    {
        return $this->hasMany(AuditLog::class, 'audit_session_id');
    }

    public function isOpen(): bool
    {
        return $this->status === self::STATUS_OPEN;
    }
}

