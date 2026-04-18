# Top Level System Diagram

## System Architecture Overview

The Library Management System is organized into integrated modules that work together to manage the complete book lifecycle and user interactions:

```mermaid
graph TB
    UI["🖥️ Web Interface<br/>(Blade Templates +<br/>JavaScript/Tailwind)"]
    
    subgraph "API & Routing Layer"
        APIRouter["Route Handler"]
        APIGateway["Request Validation<br/>& Authorization"]
    end
    
    subgraph "Core Application Layer"
        BookMod["📚 Books & Inventory<br/>Module"]
        BorrowMod["📖 Borrowing &<br/>Returns Module"]
        UserMod["👥 Users & Access<br/>Management"]
        ReportMod["📊 Reports &<br/>Analytics"]
    end
    
    subgraph "Data & Services Layer"
        Models["Eloquent Models<br/>(ORM)"]
        Services["Business Logic<br/>Services"]
        EventLog["Activity Log &<br/>Audit Trail"]
    end
    
    subgraph "Data Persistence Layer"
        MySQL["☁️ MySQL<br/>Database"]
        Cache["⚡ Cache<br/>(Redis/File)"]
        Storage["📁 File Storage<br/>(Uploads)"]
    end
    
    External["🔗 External Systems<br/>(Email, School DB,<br/>Reports)"]
    
    UI -->|HTTP Request| APIRouter
    APIRouter -->|Validate| APIGateway
    APIGateway -->|Route to| BookMod
    APIGateway -->|Route to| BorrowMod
    APIGateway -->|Route to| UserMod
    APIGateway -->|Route to| ReportMod
    
    BookMod -->|Read/Write| Models
    BorrowMod -->|Read/Write| Models
    UserMod -->|Read/Write| Models
    ReportMod -->|Query| Models
    
    Models -->|Execute Query| MySQL
    Models -->|Cache Result| Cache
    
    BookMod -->|Log Action| EventLog
    BorrowMod -->|Log Action| EventLog
    UserMod -->|Log Action| EventLog
    
    EventLog -->|Store| MySQL
    
    BookMod -->|Write| Storage
    ReportMod -->|Generate| Storage
    
    BorrowMod -->|Send| External
    UserMod -->|Query| External
    ReportMod -->|Export| External
    
    MySQL -->|Response| Models
    Cache -->|Response| Models
    
    style UI fill:#4A90E2,color:#fff,stroke:#2E5C8A,stroke-width:2px
    style BookMod fill:#90EE90,color:#000,stroke:#228B22,stroke-width:2px
    style BorrowMod fill:#87CEEB,color:#000,stroke:#4682B4,stroke-width:2px
    style UserMod fill:#FFD700,color:#000,stroke:#FF8C00,stroke-width:2px
    style ReportMod fill:#DDA0DD,color:#fff,stroke:#9932CC,stroke-width:2px
    style MySQL fill:#FF6B6B,color:#fff
    style Cache fill:#FFA500,color:#fff
    style Storage fill:#95E1D3,color:#000
    style External fill:#F7B731,color:#000
```

## Module Responsibilities

| Module | Primary Responsibilities | Key Features |
|--------|-------------------------|--------------|
| **Books & Inventory** | Book CRUD, copy management, catalog | Control number generation, available vs total counts, import/export |
| **Borrowing & Returns** | Borrow/return transactions, lost/damaged tracking | Copy-level tracking, status transitions, activity logging |
| **Users & Access** | Student/teacher/admin management, authentication | Role-based access, user records, permissions |
| **Reports & Analytics** | Dashboard, transaction reports, metrics | Transaction history, status tracking, system statistics |

## Data Flow Overview

```
User Input
    ↓
Web Interface (Blade)
    ↓
Route Handler (web.php)
    ↓
Controller (Business Logic)
    ↓
Models (ORM) → MySQL Database
    ↓
Cache Layer (Performance)
    ↓
Response to User
```

## Technology Stack

| Layer | Technology |
|-------|-----------|
| **Frontend** | Blade Templates, Tailwind CSS, Alpine.js, Vite |
| **Backend** | Laravel Framework, PHP 8+ |
| **ORM** | Eloquent |
| **Database** | MySQL |
| **Caching** | Redis / File Cache |
| **Task Queue** | Redis Queue / Database Driver |
| **Testing** | PHPUnit, Pest |
