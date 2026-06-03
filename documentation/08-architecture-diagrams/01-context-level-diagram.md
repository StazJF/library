# Context Level Diagram

## System Context

The Library Management System operates within the school environment, interacting with users and external systems while coordinating internal processes for cataloging, loans, notifications, and reporting:

```mermaid
graph TB
    Student["👤 Students"]
    Teacher["👨‍🏫 Teachers"]
    Librarian["👩‍💼 Librarians<br/>(Staff)"]

    subgraph LibrarySys["📚 Library Management System"]
        direction TB
        Catalog["📖 Catalog Search & Availability"]
        LoanProc["🧾 Loan / Return Processing"]
        UserAuth["🔐 User Access & Roles"]
        Notification["✉️ Notification Engine"]
        Reporting["📈 Reporting & Export"]
        DataSync["🔄 Student / Teacher Data Sync"]
    end

    EmailSys["📧 Email System"]
    SchoolDB["🗄️ School Database<br/>(External)"]
    ReportSys["📊 Report Generator<br/>(External)"]

    Student -->|Search Catalog<br/>Borrow / Return| Catalog
    Student -->|Login / View Status| UserAuth
    Student -->|Receive Due Alerts / Notices| Notification

    Teacher -->|Search Catalog<br/>Borrow / Return| Catalog
    Teacher -->|Manage Class Loans| LoanProc
    Teacher -->|Login / Role Access| UserAuth
    Teacher -->|Receive Alerts / Report Summaries| Notification

    Librarian -->|Manage Inventory / Loans| LoanProc
    Librarian -->|Approve Memberships / Roles| UserAuth
    Librarian -->|Configure Rules / Generate Reports| Reporting
    Librarian -->|Review Notifications / Follow Up| Notification
    Librarian -->|Sync User Records| DataSync

    Catalog -->|Check Availability| LoanProc
    LoanProc -->|Update Inventory<br/>Record Transaction| Reporting
    LoanProc -->|Trigger Due Alerts| Notification
    Notification -->|Send Reminder / Alert| EmailSys

    UserAuth -->|Verify User| SchoolDB
    DataSync -->|Read Master Records| SchoolDB
    Reporting -->|Deliver Reports| ReportSys

    EmailSys -->|Deliver Messages| Student
    EmailSys -->|Deliver Messages| Teacher
    EmailSys -->|Deliver Messages| Librarian

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
| **Students** | External Actor | Borrow books, search catalog, view account and due status |
| **Teachers** | External Actor | Borrow books for classes, manage bulk distributions, and view records |
| **Librarians/Staff** | External Actor | Manage inventory, loans, memberships, notifications, and reporting |
| **Email System** | External System | Delivers reminders, alerts, and notifications to users |
| **School Database** | External System | Provides authoritative student and teacher master data for authentication and validation |
| **Report Generator** | External System | Receives exported analytics and reports for external consumption |

## Key System Processes

1. **Catalog Search**: Users search the library catalog and confirm availability before start of a loan.
2. **Loan / Return Processing**: The system records borrow requests, validates user eligibility, updates inventory, and handles returns.
3. **User Access & Roles**: Authentication and role-based access control determine whether a user is a student, teacher, or administrator.
4. **Notification Engine**: The system triggers due-date alerts, overdue reminders, and inventory notifications through email.
5. **External Data Sync**: The library syncs with the school database to validate user identities and refresh student/teacher records.
6. **Reporting & Export**: Transaction summaries, inventory reports, and analytics are exported to the reporting service.
