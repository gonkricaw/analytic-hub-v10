# **Analytics Hub - Logical System Architecture**

## **1. System Overview**

### **1.1 System Context**
Analytics Hub is a secure, invitation-only web application that serves as a centralized platform for displaying analytical content. The system operates on a closed-loop architecture where all user access is controlled through administrator invitations.

### **1.2 Core Architecture Pattern**
```
┌─────────────────────────────────────────────────────────────┐
│                     Presentation Layer                       │
│  (Dark Theme UI, Responsive Design, Real-time Updates)      │
├─────────────────────────────────────────────────────────────┤
│                     Application Layer                        │
│  (Laravel Controllers, Middleware, Service Classes)          │
├─────────────────────────────────────────────────────────────┤
│                      Business Logic                          │
│  (RBAC, Content Security, Email Processing, Notifications)  │
├─────────────────────────────────────────────────────────────┤
│                        Data Layer                            │
│  (PostgreSQL, Eloquent ORM, Query Optimization, Caching)    │
└─────────────────────────────────────────────────────────────┘
```

---

## **2. Authentication & Authorization Flow**

### **2.1 Authentication State Machine**
```
States:
┌────────────┐     ┌─────────────┐     ┌──────────────┐
│   Guest    │────►│  Pending    │────►│ Authenticated│
│ (No Login) │     │(First Login)│     │   (Active)   │
└────────────┘     └─────────────┘     └──────────────┘
                          │                     │
                          ▼                     ▼
                   ┌─────────────┐     ┌──────────────┐
                   │  Suspended  │     │   Expired    │
                   │  (Blocked)  │     │ (Password)   │
                   └─────────────┘     └──────────────┘
```

### **2.2 Authorization Decision Flow**
```
User Request
    │
    ▼
Check Authentication ──No──► Redirect to Login
    │Yes
    ▼
Check User Status ─────Suspended──► Show Suspended Message
    │Active
    ▼
Check T&C Acceptance ──No──► Force T&C Modal
    │Yes
    ▼
Check Password Expiry ─Expired──► Force Password Change
    │Valid
    ▼
Check Role Permissions ─No──► 403 Forbidden
    │Yes
    ▼
Check Content Access ──No──► 404 Not Found
    │Yes
    ▼
Grant Access & Log Activity
```

---

## **3. Data Flow Architecture**

### **3.1 User Creation & Invitation Flow**
```
Administrator Action
    │
    ▼
Create User Record (idbi_users)
    │
    ▼
Generate Temporary Password (8 chars)
    │
    ▼
Create User-Role Mapping (idbi_user_roles)
    │
    ▼
Queue Email Job (idbi_email_queue)
    │
    ▼
Send Invitation Email
    │
    ▼
Log Activity (idbi_user_activities)
```

### **3.2 Content Access Flow**
```
User Clicks Menu Item
    │
    ▼
Check Menu Permissions (idbi_menu_roles)
    │Allowed
    ▼
Retrieve Content (idbi_contents)
    │
    ▼
Check Content Type
    ├─Custom HTML──► Render Direct HTML
    │
    └─Embedded─────► Decrypt URL (AES-256)
                         │
                         ▼
                    Generate Masked URL
                         │
                         ▼
                    Render in Secure iFrame
                         │
                         ▼
                    Log Content Visit
```

---

## **4. Security Architecture**

### **4.1 IP Security Management**
```
Login Attempt
    │
    ▼
Check IP Blacklist (idbi_blacklisted_ips)
    │Not Blacklisted
    ▼
Validate Credentials
    ├─Success──► Reset Failed Count
    │
    └─Failed───► Increment Failed Count
                     │
                     ▼
                Check Threshold (30)
                     │Exceeded
                     ▼
                Add to Blacklist
                     │
                     ▼
                Block Future Access
```

### **4.2 Session Security Flow**
```
User Login Success
    │
    ▼
Generate Session Token
    │
    ▼
Store Session Data:
├── User ID (UUID)
├── Role Permissions (Cached)
├── Login Timestamp
├── IP Address
└── User Agent
    │
    ▼
Set Session Timeout (30 min)
    │
    ▼
Monitor Activity ──Idle──► Expire Session
    │Active
    ▼
Refresh Timeout
```

### **4.3 Content Security Model**
```
Original URL (e.g., PowerBI)
    │
    ▼
AES-256 Encryption
    │
    ▼
Store Encrypted URL + UUID
    │
    ▼
User Requests Content
    │
    ▼
Validate Permissions
    │
    ▼
Decrypt URL in Memory
    │
    ▼
Generate One-Time Token
    │
    ▼
Render Secure iFrame
    │
    ▼
Prevent Browser Inspection
```

---

## **5. Menu System Logic**

### **5.1 Menu Hierarchy Processing**
```
Load User Roles
    │
    ▼
Query Menu Items (idbi_menus)
    │
    ▼
Apply Role Filters (idbi_menu_roles)
    │
    ▼
Build Menu Tree:
├── Level 1 (Root)
│   ├── Level 2 (Child)
│   │   └── Level 3 (Sub-child)
│   └── Level 2 (Child)
└── Level 1 (Root)
    │
    ▼
Cache Menu Structure (Per Role)
    │
    ▼
Render Navigation
```

### **5.2 Menu State Management**
```
Current URL Path
    │
    ▼
Match Against Menu Routes
    │
    ▼
Identify Active Trail:
├── Mark Parent as Active
├── Mark Current as Active
└── Expand Parent Menus
    │
    ▼
Apply Visual Indicators
```

---

## **6. Email System Architecture**

### **6.1 Email Template Processing**
```
Trigger Email Event
    │
    ▼
Load Template (idbi_email_templates)
    │
    ▼
Parse Variables:
├── {{user_name}}
├── {{temp_password}}
├── {{reset_link}}
└── Other Dynamic Vars
    │
    ▼
Replace with Actual Values
    │
    ▼
Queue Email Job
    │
    ▼
Process via Queue Worker
    │
    ├─Success──► Log Delivery
    │
    └─Failed───► Retry Logic (3x)
```

### **6.2 Email Queue Management**
```
Email Jobs Queue
    │
    ▼
Queue Worker (Laravel Queue)
    │
    ▼
Check SMTP Connection
    │Connected
    ▼
Send Email Batch (Max 50)
    │
    ▼
Update Queue Status:
├── Sent
├── Failed (Retry)
└── Failed (Permanent)
    │
    ▼
Clean Processed Jobs (After 7 days)
```

---

## **7. Notification System Logic**

### **7.1 Notification Creation Flow**
```
Event Occurs
    │
    ▼
Create Notification Record:
├── Type (System/Announcement)
├── Priority (High/Normal/Low)
├── Target (All/Role/User)
└── Content (HTML Allowed)
    │
    ▼
Determine Recipients
    │
    ▼
Create User-Notification Links
    │
    ▼
Broadcast Real-time Update
    │
    ▼
Update Unread Counters
```

### **7.2 Real-time Notification Delivery**
```
WebSocket Connection
    │
    ▼
User Authenticated Channel
    │
    ▼
Listen for Events:
├── New Notification
├── Mark as Read
└── Delete Notification
    │
    ▼
Update UI Components:
├── Bell Icon Counter
├── Notification Dropdown
└── Toast Messages
```

---

## **8. Dashboard Widget Logic**

### **8.1 Widget Data Flow**
```
Page Load
    │
    ▼
Initialize Widget Grid
    │
    ▼
Load Widget Components:
├── Static Widgets (Clock, Marquee)
├── Dynamic Widgets (Charts, Lists)
└── Real-time Widgets (Online Users)
    │
    ▼
Set Refresh Intervals:
├── Clock: 1 second
├── Online Users: 30 seconds
└── Others: 5 minutes
    │
    ▼
Fetch Data (Cached/Fresh)
    │
    ▼
Render with Loading States
```

### **8.2 Widget Data Aggregation**
```
Login Activity Widget:
    Query: Last 15 days login counts
    Cache: 5 minutes
    Format: Line chart data

Top Active Users Widget:
    Query: Monthly login frequency
    View: v_top_active_users
    Cache: 1 hour
    Format: Leaderboard

Popular Content Widget:
    Query: Content visit counts
    View: v_popular_content
    Cache: 30 minutes
    Format: Ranked list
```

---

## **9. Database Transaction Logic**

### **9.1 User Activity Logging**
```
Every User Action
    │
    ▼
Capture Context:
├── User ID
├── IP Address
├── Action Type
├── Resource ID
├── Timestamp
└── User Agent
    │
    ▼
Queue Log Write
    │
    ▼
Batch Insert (Every 10 seconds)
    │
    ▼
Archive Old Logs (> 1 year)
```

### **9.2 Data Consistency Rules**
```
User Deletion:
├── Soft Delete User Record
├── Preserve User Activities
├── Anonymize After 90 Days
└── Maintain Referential Integrity

Role Changes:
├── Clear Permission Cache
├── Refresh Menu Cache
├── Update Active Sessions
└── Log Permission Changes

Content Updates:
├── Version Control
├── Clear Content Cache
├── Update Last Modified
└── Notify Subscribers
```

---

## **10. Performance Optimization Logic**

### **10.1 Caching Strategy**
```
Cache Layers:
┌─────────────────────┐
│   Browser Cache     │ ← Static Assets (1 week)
├─────────────────────┤
│  Application Cache  │ ← Laravel Cache
│  ├── Menu Structure │ ← Per Role (1 hour)
│  ├── Permissions    │ ← Per User (30 min)
│  └── Widget Data    │ ← Varies (1-60 min)
├─────────────────────┤
│  Database Cache     │ ← Query Results
│  └── Views          │ ← Materialized Views
└─────────────────────┘
```

### **10.2 Query Optimization Logic**
```
Menu Query:
    Use: Eager Loading (with('roles', 'children'))
    Index: parent_id, order_index, status

Content Query:
    Use: Select specific columns
    Index: slug, status, published_at

Activity Query:
    Use: Database views
    Partition: By month
```

---

## **11. Error Handling Logic**

### **11.1 Error Classification**
```
User Errors (4xx):
├── 401: Authentication required
├── 403: Permission denied
├── 404: Resource not found
└── 422: Validation failed
    │
    ▼
Show User-Friendly Message

System Errors (5xx):
├── 500: Internal error
├── 502: Service unavailable
└── 503: Maintenance mode
    │
    ▼
Log Full Stack Trace
Show Generic Message
Alert Administrators
```

### **11.2 Recovery Procedures**
```
Database Connection Lost:
├── Retry Connection (3x)
├── Switch to Read Replica
├── Show Maintenance Page
└── Alert DevOps

Email Service Down:
├── Queue for Later
├── Try Backup SMTP
├── Log Failed Attempts
└── Notify via Dashboard

External Service Timeout:
├── Show Cached Content
├── Display Fallback UI
├── Log Service Status
└── Retry with Backoff
```

---

## **12. State Management**

### **12.1 Application States**
```
Global States:
├── User Session
│   ├── Authentication Status
│   ├── Role & Permissions
│   └── Preferences
├── UI State
│   ├── Active Menu
│   ├── Loading States
│   └── Modal States
└── Data State
    ├── Cached Data
    ├── Fresh Data
    └── Pending Updates
```

### **12.2 State Synchronization**
```
State Change Event
    │
    ▼
Update Local State
    │
    ▼
Persist to Storage:
├── Session (Server)
├── LocalStorage (Client)
└── Database (Permanent)
    │
    ▼
Broadcast Updates:
├── UI Components
├── Other Tabs
└── Real-time Clients
```

---

## **13. Integration Logic**

### **13.1 External Service Integration**
```
SMTP Integration:
├── Test Connection
├── Validate Settings
├── Handle Failures
├── Queue Management
└── Delivery Tracking
```

### **13.2 API Communication**
```
Internal API Calls:
├── Add CSRF Token
├── Include Auth Header
├── Validate Response
├── Handle Errors
└── Update UI State

External API Calls:
├── Add API Key
├── Set Timeout (30s)
├── Retry Failed Calls
├── Cache Responses
└── Log All Requests
```

---

## **14. Business Rules Engine**

### **14.1 Core Business Rules**
```
Password Policy:
IF password_age > 90 days THEN force_reset
IF password_history contains new_password THEN reject
IF password_strength < required THEN reject

Access Control:
IF user.status != 'active' THEN deny_access
IF !user.accepted_terms THEN show_terms
IF !user.roles.contains(required_role) THEN deny

Content Visibility:
IF content.status != 'published' THEN hide
IF !content.roles.intersect(user.roles) THEN deny
IF content.expire_date < now THEN archive
```

### **14.2 Workflow Rules**
```
User Invitation:
WHEN admin.creates_user
  THEN generate_temp_password
  AND create_user_record
  AND assign_roles
  AND queue_invitation_email
  AND log_activity

Failed Login:
WHEN login_attempt.fails
  THEN increment_fail_count
  IF fail_count >= 30
    THEN blacklist_ip
    AND notify_admin
  ELSE
    log_attempt
```

---

## **15. System Monitoring Logic**

### **15.1 Health Check System**
```
Every 5 Minutes:
├── Check Database Connection
├── Check Cache Service
├── Check Queue Workers
├── Check Disk Space
├── Check Memory Usage
└── Check External APIs
    │
    ▼
Update Health Dashboard
    │
    ▼
IF any_check_fails
  THEN alert_administrators
```

### **15.2 Performance Monitoring**
```
Request Lifecycle:
├── Start Timer
├── Log Request Details
├── Track Database Queries
├── Monitor Memory Usage
├── Calculate Response Time
└── Store Metrics
    │
    ▼
IF response_time > threshold
  THEN log_slow_request
  AND analyze_bottleneck
```

This logical system architecture provides the blueprint for implementing Analytics Hub with clear data flows, state management, and business logic that
