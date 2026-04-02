<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Borrow extends Model
{
    use HasFactory;

    // Return status constants
    public const STATUS_PENDING = 'pending';
    public const STATUS_RETURNED_ON_TIME = 'returned_on_time';
    public const STATUS_LATE_RETURN = 'late_return';
    public const STATUS_DAMAGED_FOR_REPAIR = 'damaged_for_repair';
    public const STATUS_LOST_AND_FOUND = 'lost_and_found';
    public const STATUS_REPAIRED = 'repaired';
    public const STATUS_FOUND = 'found';

    // Available status values for validation
    public static function getStatusOptions()
    {
        return [
            self::STATUS_PENDING => 'Pending',
            self::STATUS_RETURNED_ON_TIME => 'Returned (On Time)',
            self::STATUS_LATE_RETURN => 'Late Return',
            self::STATUS_DAMAGED_FOR_REPAIR => 'Damaged / For Repair',
            self::STATUS_LOST_AND_FOUND => 'Lost and Found',
            self::STATUS_REPAIRED => 'Repaired',
            self::STATUS_FOUND => 'Found',
        ];
    }

    // Get status badge color
    public static function getStatusColor($status)
    {
        return match($status) {
            self::STATUS_RETURNED_ON_TIME => 'success',
            self::STATUS_LATE_RETURN => 'warning',
            self::STATUS_DAMAGED_FOR_REPAIR => 'danger',
            self::STATUS_LOST_AND_FOUND => 'info',
            self::STATUS_REPAIRED => 'info',
            self::STATUS_FOUND => 'success',
            self::STATUS_PENDING => 'secondary',
            default => 'light',
        };
    }

    // Get status display label
    public static function getStatusLabel($status)
    {
        return self::getStatusOptions()[$status] ?? 'Unknown';
    }

    protected $fillable = [
        'user_id',
        'book_id',
        'book_copy_id',
        'borrowed_at',
        'due_date',
        'returned_at',
        'return_status',
        'remark',
        'notes',
        'role',
        'origin',
        'copy_number',
        'status',
    ];

    protected $casts = [
        'borrowed_at' => 'date',
        'due_date' => 'date',
        'returned_at' => 'datetime',
    ];

    // Get the borrower (either User or Teacher based on the 'role' field)
    public function user()
    {
        if ($this->role === 'teacher') {
            return $this->belongsTo(Teacher::class, 'user_id');
        }
        return $this->belongsTo(User::class, 'user_id');
    }

    public function book()
    {
        return $this->belongsTo(Book::class, 'book_id');
    }

    public function bookCopy()
    {
        return $this->belongsTo(BookCopy::class, 'book_copy_id');
    }

    public function lostDamagedItem()
    {
        return $this->hasOne(LostDamagedItem::class, 'borrow_id');
    }

    // Accessor to get the borrower (User or Teacher) directly
    public function getBorrower()
    {
        if ($this->role === 'teacher') {
            return Teacher::find($this->user_id);
        }
        return User::find($this->user_id);
    }

    /**
     * Get the current transaction status considering lost/damaged/repaired/found history
     * This method checks if there's a related LostDamagedItem and returns the appropriate
     * status reflecting the latest state in the history.
     */
    public function getTransactionStatus()
    {
        // Check if there's a lost/damaged item associated with this borrow
        $lostDamagedItem = $this->lostDamagedItem;
        
        if (!$lostDamagedItem) {
            // No lost/damaged item, return the regular return status or pending
            return $this->return_status ?? self::STATUS_PENDING;
        }

        // Item exists, get its latest history to determine current state
        $latestHistory = $lostDamagedItem->histories()
            ->latest('created_at')
            ->first();

        if (!$latestHistory) {
            // No history, return the type-based status
            return $lostDamagedItem->type === 'damaged' 
                ? self::STATUS_DAMAGED_FOR_REPAIR 
                : self::STATUS_LOST_AND_FOUND;
        }

        // Map history action to transaction status
        return match($latestHistory->action) {
            'repaired' => self::STATUS_REPAIRED,
            'returned' => $lostDamagedItem->type === 'lost' ? self::STATUS_FOUND : self::STATUS_REPAIRED,
            'resolved' => self::STATUS_REPAIRED,
            'replaced' => self::STATUS_REPAIRED,
            default => $lostDamagedItem->type === 'damaged' ? self::STATUS_DAMAGED_FOR_REPAIR : self::STATUS_LOST_AND_FOUND,
        };
    }

    /**
     * Get a human-readable status label including lost/damaged transitions
     */
    public function getTransactionStatusLabel()
    {
        $status = $this->getTransactionStatus();
        return self::getStatusLabel($status);
    }

    /**
     * Check if this transaction involves a lost/damaged item
     */
    public function isLostOrDamaged()
    {
        return $this->lostDamagedItem !== null;
    }

    /**
     * Get the type of loss/damage if applicable (lost, damaged, repaired, found)
     */
    public function getLossType()
    {
        if (!$this->lostDamagedItem) {
            return null;
        }

        $latestHistory = $this->lostDamagedItem->histories()
            ->latest('created_at')
            ->first();

        if (!$latestHistory) {
            return $this->lostDamagedItem->type;
        }

        return match($latestHistory->action) {
            'repaired' => 'repaired',
            'returned' => $this->lostDamagedItem->type === 'lost' ? 'found' : 'repaired',
            'resolved' => 'repaired',
            'replaced' => 'repaired',
            default => $this->lostDamagedItem->type,
        };
    }
}
