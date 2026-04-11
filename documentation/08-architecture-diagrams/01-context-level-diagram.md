# Context Level Diagram

## System Context

The Library Management System operates within the school environment, interacting with users and external systems:

```mermaid
graph TB
    Student["👤 Students"]
    Teacher["👨‍🏫 Teachers"]
    Admin["👨‍💼 Administrators<br/>(Staff)"]
    
    LibrarySys["📚 Library Management<br/>System"]
    
    EmailSys["📧 Email System"]
    SchoolDB["🗄️ School Database<br/>(External)"]
    ReportSys["📊 Report Generator<br/>(External)"]
    
    Student -->|Borrow/Return<br/>View Status| LibrarySys
    Teacher -->|Borrow/Return<br/>Manage Distribution| LibrarySys
    Admin -->|Manage Inventory<br/>Process Returns<br/>View Reports| LibrarySys
    
    LibrarySys -->|Send Notifications<br/>Due Reminders| EmailSys
    LibrarySys -->|Query Student/Teacher<br/>Data| SchoolDB
    LibrarySys -->|Export Reports<br/>Analytics| ReportSys
    
    EmailSys -->|Deliver| Student
    EmailSys -->|Deliver| Teacher
    EmailSys -->|Deliver| Admin
    
    style LibrarySys fill:#4A90E2,color:#fff,stroke:#2E5C8A,stroke-width:3px
    style Student fill:#90EE90,color:#000
    style Teacher fill:#90EE90,color:#000
    style Admin fill:#FFD700,color:#000
    style EmailSys fill:#FFA500,color:#fff
    style SchoolDB fill:#DDA0DD,color:#fff
    style ReportSys fill:#87CEEB,color:#fff
```

## System Boundaries

| Component | Type | Description |
|-----------|------|-------------|
| **Students** | External Actor | Borrow books, check due dates, view personal records |
| **Teachers** | External Actor | Manage class distributions, borrow books |
| **Administrators/Staff** | External Actor | Manage entire system: inventory, users, rentals, reports |
| **Email System** | External System | Notifications and due date reminders |
| **School Database** | External System | Student and teacher master data (read-only) |
| **Report Generator** | External System | Export reports and analytics |

## Key Interactions

1. **Student Portal**: Browse catalog, check availability, view borrow history
2. **Teacher Distribution**: Bulk book distribution, management interface
3. **Admin Console**: Full system management, reporting, configuration
4. **Notification Engine**: Automated due date reminders via email
5. **Data Integration**: Sync with school's master student/teacher database
