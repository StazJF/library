<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    use HasFactory;

    public const EVENT_SCAN = 'SCAN';
    public const EVENT_STATUS_SET = 'STATUS_SET';
    public const EVENT_NOTE = 'NOTE';
    public const EVENT_FINALIZE = 'FINALIZE';

    public const RESULT_VERIFIED = 'VERIFIED';
    public const RESULT_MISSING = 'MISSING';
    public const RESULT_DAMAGED = 'DAMAGED';
    public const RESULT_MISPLACED = 'MISPLACED';
    public const RESULT_BORROWED = 'BORROWED';
    public const RESULT_REPLACED = 'REPLACED';

    protected $fillable = [
        'audit_session_id',
        'event_type',
        'control_number',
        'book_copy_id',
        'result_status',
        'location',
        'remarks',
        'created_by',
    ];

    public function session()
    {
        return $this->belongsTo(AuditSession::class, 'audit_session_id');
    }

    public function bookCopy()
    {
        return $this->belongsTo(BookCopy::class, 'book_copy_id')->withTrashed();
    }

    public function creator()
    {
        return $this->belongsTo(SystemUser::class, 'created_by');
    }
}
