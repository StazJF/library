# Status Badge & Icon Reference Guide

## Transaction Status Display

### Badge Colors & Meaning

| Status | Badge Color | Icon | Meaning |
|--------|-------------|------|---------|
| **Pending** | Gray | N/A | Initial status, not started |
| **Active** | Yellow | N/A | Book currently borrowed |
| **Overdue** | Red | N/A | Due date has passed |
| **Returned (On Time)** | Green | N/A | Returned before due date |
| **Late Return** | Orange | 🕐 Clock | Returned after due date |
| **Damaged / For Repair** | Red | 🔧 Tools | Book is damaged, awaiting repair |
| **Repaired** | Blue | ✓ Check-circle | Damaged book has been repaired |
| **Lost and Found** | Blue | ⚠️ Exclamation | Book was marked as lost |
| **Found** | Green | 🔍 Search | Lost book has been found/recovered |

---

## Status Icons (Bootstrap Icons)

### Icons Used in Reports

```
🔧 bi-tools          → Damaged (red color)
✓ bi-check-circle    → Repaired (green color)  
⚠️ bi-exclamation-triangle → Lost (orange color)
🔍 bi-search         → Found (green color)
```

### How to Read the Status Column

Each transaction row shows:
1. **Badge** - The text label in a colored box (e.g., "Repaired")
2. **Icon** - A visual indicator (if applicable)
3. **Background** - Yellow highlight if damaged/lost item

**Example 1 (Repaired):**
```
[Repaired] ✓
  ↑         ↑
Badge    Icon (green)
```

**Example 2 (Found):**
```
[Found] 🔍
  ↑      ↑
Badge  Icon (green)
```

**Example 3 (Damaged):**
```
[Damaged / For Repair] 🔧
  ↑                    ↑
Badge (red)         Icon (red)
← Row has yellow background too
```

---

## Status Transition Examples

### Reading a Damaged → Repaired Transition

**Initial Mark:**
- Status shows: `[Damaged / For Repair] 🔧` (red)
- Row background: Yellow tint
- Meaning: Book needs repair

**After Marking Repaired:**
- Status shows: `[Repaired] ✓` (blue)
- Row background: Normal
- Icon color: Green
- Meaning: Book has been repaired and is back in inventory

---

### Reading a Lost → Found Transition

**Initial Mark:**
- Status shows: `[Lost and Found] ⚠️` (blue)
- Row background: Yellow tint
- Icon: Orange/yellow exclamation
- Meaning: Book is reported lost

**After Marking Found:**
- Status shows: `[Found] 🔍` (green)
- Row background: Normal
- Icon color: Green
- Meaning: Lost book recovered and back in inventory

---

## Visual Hierarchy

### By Importance (High to Low)

1. **Status Badge** - Primary indicator
2. **Icon** - Secondary visual cue
3. **Row Background Color** - Tertiary indicator for items needing attention
4. **Table Position** - Ordered by most recent first

### Quick Scan Guide

**Looking for items needing action?**
- Scan for **yellow highlighted rows** → These have active damage/loss
- Look for **red badges** → Immediate attention needed
- Check for **pending clock icon** → Late returns

**Looking for completed resolutions?**
- Scan for **green badges** → Resolved successfully
- Look for **green check-circle or search icons** → Repaired or Found
- Non-highlighted rows are generally resolved

---

## Color Coding System

### Bootstrap Color Classes Used

```css
bg-danger     → Red (Damaged/Urgent)
bg-warning    → Orange/Yellow (Late/Attention)
bg-info       → Blue (Lost/Found/Repaired)
bg-success    → Green (Returned/Found/Repaired)
bg-secondary  → Gray (Generic/Other)
bg-primary    → Blue (Active Borrow)
```

---

## Filtering Guide

Use the Status filter dropdown to find specific transaction types:

**To Find:** | **Filter:**
---|---
Books marked as damaged | Filter by Status: "Damaged / For Repair"
Books that have been repaired | Filter by Status: "Repaired"
Books marked as lost | Filter by Status: "Lost and Found"
Books that were found | Filter by Status: "Found"
Late returns | Filter by Status: "Late Return"
All completed transactions | Filter by Status: "Completed"

---

## Common Questions

**Q: Why does my repaired book still show an icon?**
A: The icon indicates the history of the transaction. Once repaired, the icon is a green checkmark, showing the resolution was successful.

**Q: Can I tell the difference between an old damaged vs. newly damaged item?**
A: Yes, check the transaction history. The most recent entry will show which status the item currently has. The full history shows the timeline.

**Q: Why is my row highlighted yellow?**
A: Yellow highlighting indicates the book has an active lost or damaged record. Once resolved (repaired or found), the highlighting disappears.

**Q: What does the clock icon mean?**
A: It indicates a late return - the book was returned after the due date.

**Q: Can a book be both repaired AND found?**
A: No, these are mutually exclusive. A book is either damaged (→ repaired) OR lost (→ found). A damaged book is repaired in inventory. A lost book is found and then returned.

---

## Accessibility Notes

- **Color blind friendly:** Icons and text labels provide redundancy
- **Screen readers:** Badge classes include descriptive titles
- **Tooltips:** Hover over icons to see full descriptions
- **Keyboard navigation:** All elements are keyboard accessible

---

## Summary Icons

| Icon | Status | Color | Action |
|------|--------|-------|--------|
| 🔧 | Damaged | Red | Awaiting repair |
| ✓ | Repaired | Green | Repair complete |
| ⚠️ | Lost | Orange | Book missing |
| 🔍 | Found | Green | Recovery complete |
| 🕐 | Late Return | Orange | Overdue return |

---

**Last Updated:** April 2, 2026
