# Child Level Diagrams - Module Details

## 1. Books & Inventory Module Diagram

```mermaid
graph TB
    subgraph Input["📥 Inputs"]
        Create["Create Book"]
        Import["Import CSV"]
        AddCopy["Add Copies"]
        DeleteCopy["Delete Copy"]
    end
    
    subgraph Processing["⚙️ Processing"]
        Validate["Validate<br/>Data"]
        GenerateCtrl["Generate Control<br/>Numbers"]
        CreateRecords["Create Book<br/>& BookCopy Records"]
        UpdateAvail["Update Availability<br/>Counts"]
    end
    
    subgraph Output["📤 Outputs"]
        Success["✓ Success<br/>Response"]
        Error["✗ Error<br/>Response"]
        CatalogView["📚 Catalog<br/>Display"]
        InventoryReport["📋 Inventory<br/>Report"]
    end
    
    subgraph Storage["💾 Data Storage"]
        BooksTable["books table"]
        CopiesTable["book_copies table"]
        ActivityLog["activity_logs"]
    end
    
    Input -->|Validate| Processing
    Processing -->|Valid| Output
    Processing -->|Invalid| Error
    Processing -->|Store| Storage
    CatalogView -->|Query| Storage
    InventoryReport -->|Query| Storage
    
    style Input fill:#90EE90,stroke:#228B22,stroke-width:2px
    style Processing fill:#FFD700,stroke:#FF8C00,stroke-width:2px
    style Output fill:#87CEEB,stroke:#4682B4,stroke-width:2px
    style Storage fill:#FF6B6B,color:#fff,stroke:#8B0000,stroke-width:2px
```

## 2. Borrowing & Returns Module Diagram

```mermaid
graph TB
    subgraph Input["📥 Inputs"]
        StudentBorrow["Student Borrow"]
        TeacherBorrow["Teacher Borrow"]
        ProcessReturn["Process Return"]
        MarkLost["Mark Lost/<br/>Damaged"]
        MarkRepaired["Mark Repaired/<br/>Found"]
    end
    
    subgraph Processing["⚙️ Processing"]
        CheckAvail["Check<br/>Availability"]
        LinkCopy["Link to<br/>BookCopy"]
        UpdateStatus["Update Copy<br/>Status"]
        CreateHistory["Create History<br/>Record"]
        CalcPenalty["Calculate<br/>Penalty"]
    end
    
    subgraph Output["📤 Outputs"]
        Receipt["📄 Receipt"]
        StatusUpdate["Status<br/>Update"]
        Notification["📧 Notification"]
        Report["📊 Report"]
    end
    
    subgraph Storage["💾 Data Storage"]
        BorrowsTable["borrows table"]
        CopiesTable["book_copies table"]
        LostDamagedTable["lost_damaged_items"]
        HistoryTable["histories table"]
    end
    
    Input -->|Validate| Processing
    Processing -->|Execute| Output
    Processing -->|Store| Storage
    Output -->|Send| Notification
    Report -->|Query| Storage
    
    style Input fill:#90EE90,stroke:#228B22,stroke-width:2px
    style Processing fill:#FFD700,stroke:#FF8C00,stroke-width:2px
    style Output fill:#87CEEB,stroke:#4682B4,stroke-width:2px
    style Storage fill:#FF6B6B,color:#fff,stroke:#8B0000,stroke-width:2px
```

## 3. Users & Access Management Module Diagram

```mermaid
graph TB
    subgraph Input["📥 Inputs"]
        CreateUser["Create User"]
        EditUser["Edit User"]
        DeleteUser["Delete User"]
        Authenticate["Login<br/>(Auth)"]
    end
    
    subgraph Processing["⚙️ Processing"]
        ValidateEmail["Validate<br/>Email/Phone"]
        HashPassword["Hash<br/>Password"]
        AssignRole["Assign<br/>Role"]
        CheckPermission["Check<br/>Permissions"]
    end
    
    subgraph Output["📤 Outputs"]
        UserList["User<br/>List"]
        AuthToken["Auth<br/>Token"]
        AccessGrant["✓ Access<br/>Granted"]
        AccessDeny["✗ Access<br/>Denied"]
    end
    
    subgraph Storage["💾 Data Storage"]
        UsersTable["users table"]
        SystemUsersTable["system_users table"]
        TeachersTable["teachers table"]
        ActivityLog["activity_logs"]
    end
    
    Input -->|Validate| Processing
    Processing -->|Valid| Output
    Processing -->|Store| Storage
    UserList -->|Query| Storage
    Authenticate -->|Verify| Storage
    
    style Input fill:#90EE90,stroke:#228B22,stroke-width:2px
    style Processing fill:#FFD700,stroke:#FF8C00,stroke-width:2px
    style Output fill:#87CEEB,stroke:#4682B4,stroke-width:2px
    style Storage fill:#FF6B6B,color:#fff,stroke:#8B0000,stroke-width:2px
```

## 4. Reports & Analytics Module Diagram

```mermaid
graph TB
    subgraph Input["📥 Inputs"]
        ViewDashboard["View<br/>Dashboard"]
        ViewReports["View<br/>Reports"]
        FilterData["Filter by<br/>Date/Category"]
        ExportReport["Export<br/>Report"]
    end
    
    subgraph Processing["⚙️ Processing"]
        AggregateData["Aggregate<br/>Data"]
        CalculateMetrics["Calculate<br/>Metrics"]
        DetermineStatus["Determine<br/>Transaction<br/>Status"]
        FormatOutput["Format<br/>Output"]
    end
    
    subgraph Output["📤 Outputs"]
        Dashboard["📊 Dashboard<br/>View"]
        TransactionList["📋 Transaction<br/>List"]
        Analytics["📈 Analytics<br/>Graphs"]
        ExportFile["📁 Export<br/>File"]
    end
    
    subgraph Storage["💾 Data Storage"]
        BorrowsTable["borrows table"]
        BooksTable["books table"]
        LostDamagedtable["lost_damaged_items"]
        HistoryTable["histories table"]
    end
    
    Input -->|Query| Processing
    Processing -->|Retrieve| Storage
    Processing -->|Format| Output
    ExportReport -->|Generate| ExportFile
    
    style Input fill:#90EE90,stroke:#228B22,stroke-width:2px
    style Processing fill:#FFD700,stroke:#FF8C00,stroke-width:2px
    style Output fill:#87CEEB,stroke:#4682B4,stroke-width:2px
    style Storage fill:#FF6B6B,color:#fff,stroke:#8B0000,stroke-width:2px
```

---

## Summary of Child Modules

| Module | Primary Flow | Key Entities |
|--------|-------------|--------------|
| **Books & Inventory** | Input → Validate → Store → Display | Books, BookCopies, ControlNumbers |
| **Borrowing & Returns** | Input → Check Stock → Execute → Notify | Borrows, BookCopies, LostDamagedItems |
| **Users & Access** | Input → Validate → Authenticate → Authorize | Users, SystemUsers, Teachers, Roles |
| **Reports & Analytics** | Query → Aggregate → Calculate → Format | Transactions, Status History, Metrics |

Each module operates independently but shares common data and audit logging infrastructure.
