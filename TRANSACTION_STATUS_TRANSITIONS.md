# Transaction Status Tracking - Implementation Guide

**Date:** April 2, 2026  
**Module:** All Transactions Reports (Reports & Analytics)  
**Feature:** Book Status Transitions (Damaged → Repaired, Lost → Found)

---

## 📋 Overview

The All Transactions Reports module has been enhanced to properly track and display book status transitions. The system now:

✅ Displays "Damaged → Repaired" transitions  
✅ Displays "Lost → Found" transitions  
✅ Maintains complete history (non-destructive logging)  
✅ Shows status changes with visual indicators  
✅ Filters and sorts with new statuses  

---

## 🏗️ Implementation Details

### 1. Database Schema (No Changes Required)

The solution leverages existing tables:

```
borrows
├── id (transaction record)
├── user_id (borrower)
├── book_id
├── book_copy_id
├── borrowed_at
├── due_date
├── returned_at
├── return_status (existing field used for tracking)
└── ...

lost_damaged_items
├── id
├── borrow_id (FK to borrows)
├── book_id
├── type (enum: 'lost', 'damaged')
├── status (enum: 'active', 'returned', 'replaced')
└── ...

lost_damaged_item_histories
├── id
├── lost_damaged_item_id (FK)
├── action (e.g., 'created', 'repaired', 'returned', ...)
├── remarks (optional details)
├── created_by (user ID)
└── ...
```

### 2. Status Constant Additions ([Borrow.php](app/Models/Borrow.php#L17-L18))

**New Constants:**
```php
public const STATUS_REPAIRED = 'repaired';
public const STATUS_FOUND = 'found';
```

**Updated Status List:**
```php
public static function getStatusOptions()
{
    return [
        self::STATUS_PENDING => 'Pending',
        self::STATUS_RETURNED_ON_TIME => 'Returned (On Time)',
        self::STATUS_LATE_RETURN => 'Late Return',
        self::STATUS_DAMAGED_FOR_REPAIR => 'Damaged / For Repair',
        self::STATUS_LOST_AND_FOUND => 'Lost and Found',
        self::STATUS_REPAIRED => 'Repaired',           // NEW
        self::STATUS_FOUND => 'Found',                 // NEW
    ];
}
```

**Updated Color Mapping:**
```php
self::STATUS_REPAIRED => 'info',      // Blue badge
self::STATUS_FOUND => 'success',      // Green badge
```

### 3. Transaction Status Determination ([Borrow.php](app/Models/Borrow.php#L106-L190))

**Key Method: `getTransactionStatus()`**

This method examines the lost/damaged item history to determine the current status:

```php
public function getTransactionStatus()
{
    // If no lost/damaged item, return the regular status
    if (!$this->lostDamagedItem) {
        return $this->return_status ?? self::STATUS_PENDING;
    }

    // Get the latest history entry
    $latestHistory = $this->lostDamagedItem->histories()
        ->latest('created_at')
        ->first();

    // Map history action to status
    return match($latestHistory->action) {
        'repaired' => self::STATUS_REPAIRED,
        'returned' => $this->lostDamagedItem->type === 'lost' 
                        ? self::STATUS_FOUND 
                        : self::STATUS_REPAIRED,
        'resolved', 'replaced' => self::STATUS_REPAIRED,
        default => $this->lostDamagedItem->type === 'damaged'
                    ? self::STATUS_DAMAGED_FOR_REPAIR
                    : self::STATUS_LOST_AND_FOUND,
    };
}
```

**Companion Methods:**
- `getTransactionStatusLabel()` - Returns human-readable label
- `isLostOrDamaged()` - Checks if transaction has any loss/damage
- `getLossType()` - Returns current loss type (damaged, repaired, lost, found)

### 4. Reports Controller Enhancement ([DashboardController.php](app/Http/Controllers/DashboardController.php#L173-L280))

**Enhanced Query with Eager Loading:**
```php
$transactionsQuery = Borrow::with([
    'book',
    'lostDamagedItem' => function ($query) {
        $query->with('histories')->latest('created_at');
    }
])
->select('borrows.*');
```

**Transaction Enrichment:**
```php
$transactions->getCollection()->transform(function ($transaction) {
    // ... existing enrichment ...
    
    // Add status information
    $transaction->transaction_status = $transaction->getTransactionStatus();
    $transaction->transaction_status_label = $transaction->getTransactionStatusLabel();
    $transaction->transaction_loss_type = $transaction->getLossType();
    $transaction->is_lost_or_damaged = $transaction->isLostOrDamaged();
    
    return $transaction;
});
```

### 5. Reports View Updates ([reports.blade.php](resources/views/reports.blade.php))

**Status Display with Indicators:**
```blade
<td>
    <span class="badge bg-{{ $statusClass }}">{{ $statusLabel }}</span>
    @if($isLostOrDamaged)
        @if($lossType === 'damaged')
            <i class="bi bi-tools status-indicator damaged" title="Damaged - For Repair"></i>
        @elseif($lossType === 'repaired')
            <i class="bi bi-check-circle status-indicator repaired" title="Repaired"></i>
        @elseif($lossType === 'lost')
            <i class="bi bi-exclamation-triangle status-indicator lost" title="Lost"></i>
        @elseif($lossType === 'found')
            <i class="bi bi-search status-indicator found" title="Found"></i>
        @endif
    @endif
</td>
```

**Visual Styling:**
- Tools icon 🔧 for "Damaged" (red color)
- Check-circle icon ✓ for "Repaired" (green color)
- Exclamation-triangle icon ⚠️ for "Lost" (orange color)
- Search icon 🔍 for "Found" (green color)
- Yellow background highlight for rows with damaged/lost items

---

## 🔄 Status Transition Flows

### Flow 1: Damaged → Repaired

```
┌─────────────────────────────────┐
│  Borrow Record Created          │
│  Status: Pending/Active         │
└────────────┬────────────────────┘
             │
        Mark as Damaged
             ↓
┌─────────────────────────────────┐
│  LostDamagedItem Created        │
│  • type = 'damaged'             │
│  • status = 'active'            │
│  History: action='created'      │
└────────────┬────────────────────┘
             │
        Report Display
             ↓
┌─────────────────────────────────┐
│  Status: "Damaged / For Repair" │
│  Icon: Tools 🔧                 │
│  Color: Red Badge              │
└─────────────────────────────────┘
             │
        Mark as Repaired (lostDamagedRepaired)
             ↓
┌─────────────────────────────────┐
│  NEW History: action='repaired' │
│  BookCopy: status='available'   │
│  (Previous records preserved)   │
└────────────┬────────────────────┘
             │
        Report Display
             ↓
┌─────────────────────────────────┐
│  Status: "Repaired"             │
│  Icon: Check-circle ✓           │
│  Color: Blue Badge             │
└─────────────────────────────────┘
```

### Flow 2: Lost → Found

```
┌─────────────────────────────────┐
│  Borrow Record Created          │
│  Status: Pending/Active         │
└────────────┬────────────────────┘
             │
        Mark as Lost
             ↓
┌─────────────────────────────────┐
│  LostDamagedItem Created        │
│  • type = 'lost'                │
│  • status = 'active'            │
│  History: action='created'      │
└────────────┬────────────────────┘
             │
        Report Display
             ↓
┌─────────────────────────────────┐
│  Status: "Lost and Found"       │
│  Icon: Exclamation ⚠️            │
│  Color: Info Badge             │
└─────────────────────────────────┘
             │
        Mark as Found (lostDamagedReturn)
             ↓
┌─────────────────────────────────┐
│  NEW History: action='returned' │
│  LostDamagedItem: status='ret'  │
│  BookCopy: status='available'   │
└────────────┬────────────────────┘
             │
        Report Display
             ↓
┌─────────────────────────────────┐
│  Status: "Found"                │
│  Icon: Search 🔍                │
│  Color: Green Badge            │
└─────────────────────────────────┘
```

---

## 🛠️ How to Use

### For End Users (in Reports/Analytics)

1. **Navigate to Reports & Analytics** → "All Transactions" table
2. **View Status Column** to see:
   - Regular transaction statuses (Pending, Active, Overdue, etc.)
   - New transition statuses: "Repaired", "Found"
   - Visual indicators (icons) for lost/damaged/repaired/found items
3. **Filter by Status** using the dropdown to see specific transitions
4. **Check History** by clicking on transaction details to see full state change timeline

### For Developers (in Code)

**Get Transaction Status:**
```php
$borrow = Borrow::with('lostDamagedItem.histories')->find($id);
$status = $borrow->getTransactionStatus();
$label = $borrow->getTransactionStatusLabel();
```

**Check if Item is Lost/Damaged:**
```php
if ($borrow->isLostOrDamaged()) {
    $type = $borrow->getLossType(); // Returns: damaged, repaired, lost, or found
}
```

**In Views:**
```blade
@if($transaction->is_lost_or_damaged)
    <span>Loss Type: {{ ucfirst($transaction->transaction_loss_type) }}</span>
@endif
```

---

## 📊 Data Entry Points

The system currently marks items as lost/damaged through:

1. **[BookController.lostDamage()](app/Http/Controllers/BookController.php#L1110)**
   - Creates the initial LostDamagedItem record
   - Sets type ('lost' or 'damaged') and status ('active')

2. **[BookController.lostDamagedRepaired()](app/Http/Controllers/BookController.php#L1358)**
   - Marks damaged items as repaired
   - Creates history entry: action='repaired'
   - Restores book to available status

3. **[BookController.lostDamagedReturn()](app/Http/Controllers/BookController.php#L1308)**
   - Marks lost items as found
   - Creates history entry: action='returned'
   - Restores book to available status
   - Also handles damaged item returns

---

## ✅ Non-Destructive Logging

**Key Principle:** Previous records are NEVER overwritten or deleted.

**How It Works:**
- Each status change creates a NEW history entry
- Original lost/damaged item record remains unchanged
- All history entries are preserved and queryable
- Complete audit trail is maintained

**Example Timeline:**
```
2026-04-02 10:00 → Book marked as DAMAGED
  LostDamagedItem record created
  History: action='created'

2026-04-02 11:30 → Book marked as REPAIRED
  NEW History: action='repaired'
  Previous history still visible

2026-04-02 12:00 → Status queries return LATEST history action
  Status displayed as "Repaired"
  But full history available for auditing
```

---

## 🔄 Database Consistency

**BookCopy Synchronization:**
When a lost/damaged item is repaired or found:
```php
// In lostDamagedReturn() and lostDamagedRepaired()
$bookCopy = $book->getCopyByControlNumber($controlNumber);
if ($bookCopy) {
    $bookCopy->markAsAvailable();  // Updates BookCopy.status = 'available'
}
```

This ensures:
- BookCopy table reflects current copy status
- available_copies count is accurate
- Inventory reports show correct totals

---

## 🧪 Testing Scenarios

### Scenario 1: Damaged → Repaired
```
1. Borrow a book with ID 5
2. Go to Lost/Damaged Items section
3. Mark copy as damaged
4. Check Reports - should show "Damaged / For Repair" with tools icon
5. Mark as repaired
6. Check Reports - should show "Repaired" with check-circle icon
7. Verify both status lines visible in transaction history
```

### Scenario 2: Lost → Found
```
1. Borrow a book with ID 7
2. Mark copy as lost
3. Check Reports - should show "Lost and Found" with warning icon
4. Mark as found
5. Check Reports - should show "Found" with search icon
6. Verify history contains both entries
```

### Scenario 3: Pagination & Filtering
```
1. Filter by Status: "Repaired"
2. Verify only repaired transactions appear
3. Filter by Status: "Found"
4. Verify only found transactions appear
5. Navigate through pages
6. Verify status indicators remain consistent
```

### Scenario 4: Column Sorting
```
1. Sort by Date Borrowed
2. Sort by Due Date
3. Sort by Return Date
4. Verify transactions with new statuses sort correctly
```

---

## 🎯 Key Features

| Feature | Before | After |
|---------|--------|-------|
| **Track Damaged Items** | ✓ | ✓ (Enhanced) |
| **Track Lost Items** | ✓ | ✓ (Enhanced) |
| **Show Repaired Status** | ✗ | ✅ NEW |
| **Show Found Status** | ✗ | ✅ NEW |
| **Visual Indicators** | Basic | 🎨 Icon-based |
| **History Preserved** | ✓ | ✓ (Non-destructive) |
| **Status Transitions** | Manual query | 📊 Automatic detection |
| **Reports Display** | Generic | 💬 Context-aware |

---

## 📝 Files Modified

1. **[app/Models/Borrow.php](app/Models/Borrow.php)**
   - Added STATUS_REPAIRED and STATUS_FOUND constants
   - Updated getStatusOptions() and getStatusColor()
   - Added 4 new methods for status tracking

2. **[app/Http/Controllers/DashboardController.php](app/Http/Controllers/DashboardController.php#L173-L280)**
   - Enhanced reports() method with eager loading
   - Added transaction enrichment logic

3. **[resources/views/reports.blade.php](resources/views/reports.blade.php)**
   - Added CSS styling for new statuses
   - Updated status column with icons and indicators
   - Enhanced visual presentation

---

## 🚀 Deployment

No database migrations needed. The solution uses existing schema:
- ✅ borrows table (unchanged)
- ✅ lost_damaged_items table (unchanged)
- ✅ lost_damaged_item_histories table (unchanged)

Simply deploy the modified PHP and Blade files.

---

## 📞 Support & Questions

**How do I view the status history for an item?**
- Click on a transaction row for detailed history
- All history entries (damaged, repaired, lost, found) will appear

**Can I undo a status change?**
- Status changes are logged non-destructively
- Cannot undo directly, but can create a compensating entry
- Full history is preserved for audit purposes

**Why does a book show as "Found" instead of "Damaged"?**
- The system displays the LATEST status from history
- Previous statuses remain in history but don't override the current display
- Status reflects the most recent action taken

**Will old data be affected?**
- No, fully backward compatible
- Existing records continue to work as before
- New status detection only for records with histories

---

## 📚 Related Documentation

- [Lost/Damaged Item Tracking](COPY_COUNT_IMPLEMENTATION.md)
- [Transaction Status Implementation](IMPLEMENTATION_TRANSACTION_STATUS.md)
- [Book Copy Normalization](lost-damage-fix.md)

---

**Last Updated:** April 2, 2026  
**Status:** ✅ Complete and Ready for Testing
