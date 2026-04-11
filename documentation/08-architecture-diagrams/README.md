# Section 08: Architecture & Design Diagrams

This section provides comprehensive system architecture diagrams following formal design methodologies (Context Level, Top Level, Child Level, and HIPO diagrams).

## Contents

### 1. Context Level Diagram (`01-context-level-diagram.md`)
Shows the Library Management System and its interactions with external actors and systems:
- **Internal Actors**: Students, Teachers, Administrators (Staff)
- **External Systems**: Email System, School Database, Report Generator
- **Key Interactions**: User portals, notifications, data integration

### 2. Top Level System Diagram (`02-top-level-diagram.md`)
Depicts the complete system architecture including:
- **Web Interface Layer**: Blade templates with Tailwind CSS and JavaScript
- **API & Routing Layer**: Request validation and authorization
- **Core Application Modules**: Books, Borrowing, Users, Reports
- **Data & Services Layer**: Eloquent ORM, business logic, activity logging
- **Data Persistence Layer**: MySQL, Redis Cache, File Storage
- **External Integrations**: Email, School DB, Report generators

### 3. Child Level Diagrams (`03-child-level-diagrams.md`)
Detailed data flow for four main system modules:

#### Module 1: Books & Inventory
- Input: Create book, Import CSV, Add/Delete copies
- Processing: Validate, Generate control numbers, Create records, Update availability
- Output: Success/error responses, Catalog view, Inventory reports
- Storage: books, book_copies, activity_logs

#### Module 2: Borrowing & Returns
- Input: Student/teacher borrow, Process return, Mark lost/damaged/repaired
- Processing: Check availability, Link to copy, Update status, Create history, Calculate penalties
- Output: Receipt, Status update, Notifications, Reports
- Storage: borrows, book_copies, lost_damaged_items, histories

#### Module 3: Users & Access Management
- Input: Create/edit/delete user, Login/authentication
- Processing: Validate email/phone, Hash password, Assign role, Check permissions
- Output: User list, Auth token, Access granted/denied
- Storage: users, system_users, teachers, activity_logs

#### Module 4: Reports & Analytics
- Input: View dashboard/reports, Filter data, Export
- Processing: Aggregate data, Calculate metrics, Determine status, Format output
- Output: Dashboard view, Transaction list, Analytics graphs, Exported files
- Storage: borrows, books, lost_damaged_items, histories

---

## HIPO (Hierarchical Input Process Output) Diagrams

### Admin Management HIPO (`04-hipo-diagrams.md` - Part 1)

**Level 1: Admin Management Hierarchy**
- System Configuration (Penalty settings, Backups, Cache management)
- Data Management (Books, Users, Borrowing management)
- Operations (Monitoring, Reporting, Maintenance)

**Level 2 Detail: System Configuration**
```
Input: Config data, settings files, parameters
→ Process: Validate, Parse, Write to DB, Update cache, Log changes
→ Output: Config saved, Confirmation messages
```

**Level 2 Detail: Data Management**
```
Input: Book data, User records, Borrow requests, Returns, Status updates
→ Process: Validate (ISBN, Ctrl#, etc.), Gen control numbers, Create records, Update counts
→ Output: Records created, Users added, Status updated, Success/errors
```

**Level 2 Detail: Operations & Reporting**
```
Input: Date ranges, Filters, Report type, Export format
→ Process: Query data, Aggregate, Calculate metrics, Determine status, Format output
→ Output: HTML/CSV/PDF reports, Statistics, Insights, Charts, Graphs, Data exports
```

### Staff (Librarian) Management HIPO (`04-hipo-diagrams.md` - Part 2)

**Level 1: Staff Management Hierarchy**
- Daily Operations (Process borrows, Process returns, Mark lost/damaged)
- Inventory Operations (Add copies, Remove copies, Import books)
- Support & Reporting (Print receipts, View reports, Manage student records)

**Level 2 Detail: Daily Operations**
```
Input: Borrow requests (User ID, Book, Qty), Returns (Status, Condition, Notes)
→ Process: Identify user, Select book, Check availability, Create record, Update counts, Log activity
→ Output: Receipt, Confirmation, Status update, Penalty calculation (if needed)
Special Cases: Lost books, Damaged items, Late returns
```

**Level 2 Detail: Inventory Operations**
```
Input: Add (Book ID, Qty, Condition), Remove (Ctrl#, Reason), Import (CSV file)
→ Process: Gen control numbers, Create records, Validate data, Archive entries
→ Output: Copies added/removed, Warnings if any, Import results, Activity logged
```

**Level 2 Detail: Support & Reporting**
```
Input: Borrow ID (for receipt), Report type, Date range, Student ID
→ Process: Get records, Format data, Query database, Aggregate data, Cache results
→ Output: Printed receipts, Reports (PDF/CSV), Student records, Borrow history
```

---

## Key Takeaways

### Admin vs Staff Roles
| Aspect | Admin | Staff |
|--------|-------|-------|
| **Scope** | System-wide config & oversight | Daily operations & transactions |
| **Data Access** | All data, full configuration | Transaction data & inventory |
| **Primary Functions** | Monitor, Configure, Report | Process, Manage, Serve |
| **Frequency** | Periodic (daily/weekly reports) | Continuous (all-day operations) |
| **Risk Level** | High (system-wide impact) | Medium (operational) |
| **Audit Trail** | Complete configuration logging | Transaction & action logging |

### Data Flow Patterns
1. **User Input** → **Validation** → **Processing** → **Database** → **Cache** → **Response**
2. **All changes** logged to `activity_logs` with user, action, target, and details
3. **Copy-level tracking** ensures accurate inventory and borrowing history
4. **Non-destructive status updates** maintain complete audit trail via `lost_damaged_item_histories`

### System Interactions
- **Books Module** manages inventory and copy metadata
- **Borrowing Module** handles transactions and item tracking
- **Users Module** manages access control and person records
- **Reports Module** aggregates and visualizes system data
- **All modules** share common data persistence and logging infrastructure

---

## Related Documentation

- For detailed database schema: See `documentation/03-backend/database/schema.md`
- For API endpoints: See `documentation/05-api/api-map.md`
- For module implementation: See `documentation/03-backend/modules/`
- For system setup: See `documentation/02-setup/local-setup.md`
