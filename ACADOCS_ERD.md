# ACADOCS - Entity Relationship Diagram (ERD)

## Database Schema Documentation
**System:** Academic Document Management System (ACADOCS)  
**Version:** 2.0  
**Last Updated:** April 8, 2026

---

## Entity Definitions

### 1. USER
**Description:** Core entity representing all system users with role-based access

| Attribute | Data Type | Constraints | Description |
|-----------|-----------|-------------|-------------|
| user_id | VARCHAR(50) | PRIMARY KEY | Unique user identifier |
| name | VARCHAR(100) | NOT NULL | Full name of user |
| email | VARCHAR(100) | NOT NULL, UNIQUE | Email address for login |
| password | VARCHAR(255) | NOT NULL | Encrypted password |
| role | ENUM | NOT NULL | User role: 'admin', 'teacher', 'secretary', 'canteen', 'disbursing', 'adas' |
| created_at | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Account creation date |
| last_login | TIMESTAMP | NULL | Last login timestamp |
| is_active | BOOLEAN | DEFAULT TRUE | Account status |

**Relationships:**
- One-to-Many with TEACHER (if role = 'teacher')
- One-to-Many with DOCUMENT_SUBMISSION
- One-to-Many with FEEDBACK
- One-to-Many with ANNOUNCEMENT

---

### 2. TEACHER
**Description:** Extended profile for users with teacher role

| Attribute | Data Type | Constraints | Description |
|-----------|-----------|-------------|-------------|
| teacher_id | VARCHAR(50) | PRIMARY KEY | Unique teacher identifier |
| user_id | VARCHAR(50) | FOREIGN KEY → USER(user_id) | Reference to user account |
| employee_id | VARCHAR(20) | UNIQUE, NOT NULL | Employee identification number |
| subjects | JSON/TEXT | NOT NULL | Array of subjects taught |
| grade_level | VARCHAR(20) | NOT NULL | Primary grade level assigned |
| submission_rate | DECIMAL(5,2) | DEFAULT 0.00 | Document submission compliance % |
| created_at | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Record creation date |

**Relationships:**
- One-to-Many with DOCUMENT_SUBMISSION
- One-to-Many with PERFORMANCE_SUBJECT
- One-to-Many with TIME_RECORD

---

### 3. ANNOUNCEMENT
**Description:** System-wide announcements and notifications

| Attribute | Data Type | Constraints | Description |
|-----------|-----------|-------------|-------------|
| announcement_id | VARCHAR(50) | PRIMARY KEY | Unique announcement identifier |
| type | ENUM | NOT NULL | Type: 'Announcement', 'Questionnaires', 'Forms' |
| title | VARCHAR(200) | NOT NULL | Announcement title |
| content | TEXT | NOT NULL | Full announcement content |
| created_by | VARCHAR(50) | FOREIGN KEY → USER(user_id) | User who created announcement |
| date_posted | DATE | NOT NULL | Posting date |
| status | ENUM | DEFAULT 'active' | Status: 'active', 'archived' |
| created_at | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Record creation timestamp |

**Relationships:**
- Many-to-One with USER (created_by)

---

### 4. GRADE_LEVEL
**Description:** Grade level master data

| Attribute | Data Type | Constraints | Description |
|-----------|-----------|-------------|-------------|
| level_id | VARCHAR(50) | PRIMARY KEY | Unique level identifier |
| level_name | VARCHAR(20) | NOT NULL, UNIQUE | Grade level name (e.g., 'Grade 7') |
| total_students | INT | DEFAULT 0 | Current enrollment count |
| total_sections | INT | DEFAULT 0 | Number of sections |
| academic_year | VARCHAR(20) | NOT NULL | Academic year (e.g., '2025-2026') |

**Relationships:**
- One-to-Many with ENROLLMENT
- One-to-Many with PERFORMANCE_LEVEL

---

### 5. ENROLLMENT
**Description:** Student enrollment data by grade level

| Attribute | Data Type | Constraints | Description |
|-----------|-----------|-------------|-------------|
| enrollment_id | VARCHAR(50) | PRIMARY KEY | Unique enrollment identifier |
| level_id | VARCHAR(50) | FOREIGN KEY → GRADE_LEVEL(level_id) | Grade level reference |
| student_count | INT | NOT NULL | Number of enrolled students |
| section_count | INT | NOT NULL | Number of sections |
| academic_year | VARCHAR(20) | NOT NULL | Academic year |
| enrollment_date | DATE | NOT NULL | Enrollment record date |

**Relationships:**
- Many-to-One with GRADE_LEVEL

---

### 6. PERFORMANCE_LEVEL
**Description:** Academic performance metrics by grade level

| Attribute | Data Type | Constraints | Description |
|-----------|-----------|-------------|-------------|
| performance_id | VARCHAR(50) | PRIMARY KEY | Unique performance record ID |
| level_id | VARCHAR(50) | FOREIGN KEY → GRADE_LEVEL(level_id) | Grade level reference |
| mps | DECIMAL(5,2) | NOT NULL | Mean Percentage Score |
| nds | DECIMAL(5,2) | NOT NULL | Numerical Descriptor Score |
| quarter | INT | NOT NULL | Academic quarter (1-4) |
| academic_year | VARCHAR(20) | NOT NULL | Academic year |
| recorded_date | DATE | NOT NULL | Date recorded |

**Relationships:**
- Many-to-One with GRADE_LEVEL

---

### 7. SUBJECT
**Description:** Subject/Learning area master data

| Attribute | Data Type | Constraints | Description |
|-----------|-----------|-------------|-------------|
| subject_id | VARCHAR(50) | PRIMARY KEY | Unique subject identifier |
| subject_name | VARCHAR(100) | NOT NULL | Subject name |
| department | VARCHAR(50) | NULL | Department category |
| is_active | BOOLEAN | DEFAULT TRUE | Subject active status |

**Relationships:**
- One-to-Many with PERFORMANCE_SUBJECT

---

### 8. PERFORMANCE_SUBJECT
**Description:** Performance metrics by subject and instructor

| Attribute | Data Type | Constraints | Description |
|-----------|-----------|-------------|-------------|
| performance_subject_id | VARCHAR(50) | PRIMARY KEY | Unique performance record ID |
| subject_id | VARCHAR(50) | FOREIGN KEY → SUBJECT(subject_id) | Subject reference |
| teacher_id | VARCHAR(50) | FOREIGN KEY → TEACHER(teacher_id) | Instructor reference |
| level_name | VARCHAR(20) | NOT NULL | Grade level |
| mps | DECIMAL(5,2) | NOT NULL | Mean Percentage Score |
| quarter | INT | NOT NULL | Academic quarter |
| academic_year | VARCHAR(20) | NOT NULL | Academic year |
| recorded_date | DATE | NOT NULL | Date recorded |

**Relationships:**
- Many-to-One with SUBJECT
- Many-to-One with TEACHER

---

### 9. DOCUMENT_TYPE
**Description:** Types of documents that can be submitted

| Attribute | Data Type | Constraints | Description |
|-----------|-----------|-------------|-------------|
| doc_type_id | VARCHAR(50) | PRIMARY KEY | Unique document type ID |
| type_name | VARCHAR(50) | NOT NULL, UNIQUE | Document type name (e.g., 'DLL', 'Lesson Plan') |
| description | TEXT | NULL | Type description |
| is_required | BOOLEAN | DEFAULT TRUE | Whether submission is required |

**Relationships:**
- One-to-Many with DOCUMENT_SUBMISSION

---

### 10. DOCUMENT_SUBMISSION
**Description:** Teacher document submissions

| Attribute | Data Type | Constraints | Description |
|-----------|-----------|-------------|-------------|
| document_id | VARCHAR(50) | PRIMARY KEY | Unique document identifier |
| teacher_id | VARCHAR(50) | FOREIGN KEY → TEACHER(teacher_id) | Teacher who submitted |
| doc_type_id | VARCHAR(50) | FOREIGN KEY → DOCUMENT_TYPE(doc_type_id) | Document type |
| subject_id | VARCHAR(50) | FOREIGN KEY → SUBJECT(subject_id) | Subject/learning area |
| grade_level | VARCHAR(20) | NOT NULL | Grade level |
| file_url | VARCHAR(500) | NULL | Document file location |
| status | ENUM | DEFAULT 'Submitted' | Status: 'Submitted', 'Reviewed', 'Rejected' |
| submission_date | TIMESTAMP | NOT NULL | Date/time submitted |
| reviewed_by | VARCHAR(50) | FOREIGN KEY → USER(user_id) | Admin who reviewed |
| review_date | TIMESTAMP | NULL | Date/time reviewed |

**Relationships:**
- Many-to-One with TEACHER
- Many-to-One with DOCUMENT_TYPE
- Many-to-One with SUBJECT
- One-to-Many with FEEDBACK

---

### 11. FEEDBACK
**Description:** Feedback comments on document submissions

| Attribute | Data Type | Constraints | Description |
|-----------|-----------|-------------|-------------|
| feedback_id | VARCHAR(50) | PRIMARY KEY | Unique feedback identifier |
| document_id | VARCHAR(50) | FOREIGN KEY → DOCUMENT_SUBMISSION(document_id) | Document reference |
| author_id | VARCHAR(50) | FOREIGN KEY → USER(user_id) | User who gave feedback |
| comment | TEXT | NOT NULL | Feedback comment content |
| feedback_date | DATE | NOT NULL | Date feedback given |
| created_at | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Record creation timestamp |

**Relationships:**
- Many-to-One with DOCUMENT_SUBMISSION
- Many-to-One with USER (author_id)

---

### 12. PARENT_MEETING
**Description:** Parent-teacher meeting attendance records

| Attribute | Data Type | Constraints | Description |
|-----------|-----------|-------------|-------------|
| meeting_id | VARCHAR(50) | PRIMARY KEY | Unique meeting identifier |
| title | VARCHAR(200) | NOT NULL | Meeting title/purpose |
| meeting_date | DATE | NOT NULL | Scheduled meeting date |
| expected_parents | INT | NOT NULL | Expected number of parents |
| actual_attendance | INT | DEFAULT 0 | Actual attendees count |
| attendance_rate | DECIMAL(5,2) | GENERATED | Calculated percentage |
| quarter | INT | NULL | Academic quarter |
| academic_year | VARCHAR(20) | NOT NULL | Academic year |
| created_at | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Record creation timestamp |

**Relationships:**
- None (standalone entity)

---

### 13. DOCUMENT_LINK
**Description:** Shared document links and resources (managed by Secretary)

| Attribute | Data Type | Constraints | Description |
|-----------|-----------|-------------|-------------|
| link_id | VARCHAR(50) | PRIMARY KEY | Unique link identifier |
| category | ENUM | NOT NULL | Category: 'Forms', 'Questionnaires', 'Templates', 'Guidelines' |
| title | VARCHAR(200) | NOT NULL | Document title |
| description | TEXT | NULL | Document description |
| url | VARCHAR(500) | NOT NULL | Document URL/link |
| added_by | VARCHAR(50) | FOREIGN KEY → USER(user_id) | User who added link |
| date_added | DATE | NOT NULL | Date link was added |
| access_level | ENUM | DEFAULT 'All Users' | Access: 'All Users', 'Teachers', 'Admin Only' |
| is_active | BOOLEAN | DEFAULT TRUE | Link active status |

**Relationships:**
- Many-to-One with USER (added_by)

---

### 14. CANTEEN_RECORD
**Description:** Daily canteen financial transactions

| Attribute | Data Type | Constraints | Description |
|-----------|-----------|-------------|-------------|
| record_id | VARCHAR(50) | PRIMARY KEY | Unique record identifier |
| transaction_date | DATE | NOT NULL | Transaction date |
| description | VARCHAR(200) | NOT NULL | Transaction description |
| revenue | DECIMAL(10,2) | NOT NULL | Total revenue amount |
| expenses | DECIMAL(10,2) | NOT NULL | Total expenses amount |
| net_income | DECIMAL(10,2) | GENERATED | Revenue - Expenses |
| transaction_count | INT | DEFAULT 0 | Number of transactions |
| recorded_by | VARCHAR(50) | FOREIGN KEY → USER(user_id) | Canteen personnel who recorded |
| created_at | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Record creation timestamp |

**Relationships:**
- Many-to-One with USER (recorded_by)

---

### 15. SCHOOL_FUND
**Description:** School financial disbursements and expenditures

| Attribute | Data Type | Constraints | Description |
|-----------|-----------|-------------|-------------|
| fund_id | VARCHAR(50) | PRIMARY KEY | Unique fund record identifier |
| transaction_date | DATE | NOT NULL | Transaction date |
| category | ENUM | NOT NULL | Category: 'MOOE', 'Capital Outlay', 'Maintenance' |
| description | VARCHAR(200) | NOT NULL | Transaction description |
| particulars | TEXT | NOT NULL | Detailed particulars |
| amount | DECIMAL(12,2) | NOT NULL | Transaction amount (negative for expenses) |
| balance | DECIMAL(12,2) | NOT NULL | Running balance after transaction |
| prepared_by | VARCHAR(50) | FOREIGN KEY → USER(user_id) | Disbursing officer |
| approved_by | VARCHAR(50) | FOREIGN KEY → USER(user_id) | Approving authority |
| fiscal_year | VARCHAR(20) | NOT NULL | Fiscal year |
| created_at | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Record creation timestamp |

**Relationships:**
- Many-to-One with USER (prepared_by)
- Many-to-One with USER (approved_by)

---

### 16. TIME_RECORD
**Description:** Daily time-in/time-out records for personnel

| Attribute | Data Type | Constraints | Description |
|-----------|-----------|-------------|-------------|
| record_id | VARCHAR(50) | PRIMARY KEY | Unique record identifier |
| employee_id | VARCHAR(50) | FOREIGN KEY → TEACHER(teacher_id) | Employee reference |
| record_date | DATE | NOT NULL | Attendance date |
| time_in | TIME | NULL | Time-in timestamp |
| time_out | TIME | NULL | Time-out timestamp |
| status | ENUM | NOT NULL | Status: 'Present', 'Late', 'Absent', 'Half-day' |
| remarks | TEXT | NULL | Additional remarks/notes |
| recorded_by | VARCHAR(50) | FOREIGN KEY → USER(user_id) | ADAS who recorded |
| created_at | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Record creation timestamp |

**Relationships:**
- Many-to-One with TEACHER (employee_id)
- Many-to-One with USER (recorded_by)

---

### 17. DEPED_DOCUMENT
**Description:** DepEd required documents and compliance tracking

| Attribute | Data Type | Constraints | Description |
|-----------|-----------|-------------|-------------|
| deped_doc_id | VARCHAR(50) | PRIMARY KEY | Unique document identifier |
| document_type | VARCHAR(100) | NOT NULL | Document type (e.g., 'SF1', 'SF2', 'LESF') |
| description | TEXT | NOT NULL | Document description |
| due_date | DATE | NOT NULL | Submission deadline |
| status | ENUM | DEFAULT 'Pending' | Status: 'Pending', 'In Progress', 'Completed' |
| completion_rate | INT | DEFAULT 0 | Completion percentage (0-100) |
| prepared_by | VARCHAR(50) | FOREIGN KEY → USER(user_id) | ADAS preparing document |
| last_updated | DATE | NOT NULL | Last update date |
| school_year | VARCHAR(20) | NOT NULL | School year |
| created_at | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Record creation timestamp |

**Relationships:**
- Many-to-One with USER (prepared_by)

---

### 18. ROOM
**Description:** School rooms and facilities

| Attribute | Data Type | Constraints | Description |
|-----------|-----------|-------------|-------------|
| room_id | VARCHAR(50) | PRIMARY KEY | Unique room identifier |
| room_number | VARCHAR(50) | NOT NULL | Room number/name |
| building_name | VARCHAR(100) | NOT NULL | Building location |
| room_type | VARCHAR(50) | NULL | Type (e.g., 'Classroom', 'Laboratory', 'Office') |
| capacity | INT | NULL | Maximum capacity |
| is_active | BOOLEAN | DEFAULT TRUE | Room active status |

**Relationships:**
- One-to-Many with PROPERTY_ITEM

---

### 19. PROPERTY_ITEM
**Description:** Property and equipment inventory by room

| Attribute | Data Type | Constraints | Description |
|-----------|-----------|-------------|-------------|
| property_id | VARCHAR(50) | PRIMARY KEY | Unique property identifier |
| room_id | VARCHAR(50) | FOREIGN KEY → ROOM(room_id) | Room location reference |
| item_name | VARCHAR(200) | NOT NULL | Item/equipment name |
| quantity | INT | NOT NULL | Number of items |
| condition | ENUM | NOT NULL | Condition: 'Excellent', 'Good', 'Fair', 'Poor', 'Needs Repair' |
| last_inspection_date | DATE | NOT NULL | Last inspection date |
| remarks | TEXT | NULL | Additional notes |
| inspected_by | VARCHAR(50) | FOREIGN KEY → USER(user_id) | ADAS who inspected |
| created_at | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Record creation timestamp |

**Relationships:**
- Many-to-One with ROOM
- Many-to-One with USER (inspected_by)

---

### 20. KPI_METRIC
**Description:** Key Performance Indicators tracking

| Attribute | Data Type | Constraints | Description |
|-----------|-----------|-------------|-------------|
| kpi_id | VARCHAR(50) | PRIMARY KEY | Unique KPI record identifier |
| metric_type | ENUM | NOT NULL | Type: 'enrollment', 'compliance', 'mps', 'survival_rate', 'parent_attendance' |
| metric_value | DECIMAL(10,2) | NOT NULL | KPI value |
| academic_year | VARCHAR(20) | NOT NULL | Academic year |
| quarter | INT | NULL | Academic quarter if applicable |
| computed_date | DATE | NOT NULL | Date computed |
| created_at | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Record creation timestamp |

**Relationships:**
- None (standalone metrics entity)

---

## Entity Relationship Diagram (Visual Representation)

```
┌─────────────────┐
│      USER       │
├─────────────────┤
│ user_id (PK)    │─────┐
│ name            │     │
│ email           │     │
│ password        │     │
│ role            │     │
└─────────────────┘     │
         │              │
         │ 1            │
         │              │
         │ *            │
┌─────────────────┐     │
│    TEACHER      │     │
├─────────────────┤     │
│ teacher_id (PK) │     │
│ user_id (FK)    │─────┘
│ employee_id     │
│ subjects        │
│ grade_level     │
│ submission_rate │
└─────────────────┘
         │
         │ 1
         │
         │ *
┌──────────────────────┐
│ DOCUMENT_SUBMISSION  │
├──────────────────────┤
│ document_id (PK)     │
│ teacher_id (FK)      │
│ doc_type_id (FK)     │
│ subject_id (FK)      │
│ grade_level          │
│ status               │
│ submission_date      │
└──────────────────────┘
         │
         │ 1
         │
         │ *
┌─────────────────┐
│    FEEDBACK     │
├─────────────────┤
│ feedback_id (PK)│
│ document_id (FK)│
│ author_id (FK)  │
│ comment         │
│ feedback_date   │
└─────────────────┘


┌─────────────────┐
│  GRADE_LEVEL    │
├─────────────────┤
│ level_id (PK)   │
│ level_name      │
│ total_students  │
│ total_sections  │
│ academic_year   │
└─────────────────┘
         │
         ├──────────┐
         │ 1        │ 1
         │          │
         │ *        │ *
┌─────────────────┐   ┌──────────────────┐
│   ENROLLMENT    │   │ PERFORMANCE_LEVEL│
├─────────────────┤   ├──────────────────┤
│enrollment_id(PK)│   │performance_id(PK)│
│ level_id (FK)   │   │ level_id (FK)    │
│ student_count   │   │ mps              │
│ section_count   │   │ nds              │
│ academic_year   │   │ quarter          │
└─────────────────┘   └──────────────────┘


┌─────────────────┐
│    SUBJECT      │
├─────────────────┤
│ subject_id (PK) │
│ subject_name    │
│ department      │
└─────────────────┘
         │
         │ 1
         │
         │ *
┌──────────────────────┐
│ PERFORMANCE_SUBJECT  │
├──────────────────────┤
│performance_subject_id│
│ subject_id (FK)      │
│ teacher_id (FK)      │
│ level_name           │
│ mps                  │
│ quarter              │
└──────────────────────┘


┌─────────────────┐        ┌──────────────────┐
│ DOCUMENT_TYPE   │        │  DOCUMENT_LINK   │
├─────────────────┤        ├──────────────────┤
│doc_type_id (PK) │        │ link_id (PK)     │
│ type_name       │        │ category         │
│ description     │        │ title            │
│ is_required     │        │ url              │
└─────────────────┘        │ added_by (FK)    │
                           │ access_level     │
                           └──────────────────┘


┌──────────────────┐        ┌──────────────────┐
│ PARENT_MEETING   │        │  ANNOUNCEMENT    │
├──────────────────┤        ├──────────────────┤
│ meeting_id (PK)  │        │announcement_id(PK│
│ title            │        │ type             │
│ meeting_date     │        │ title            │
│ expected_parents │        │ content          │
│ actual_attendance│        │ created_by (FK)  │
│ attendance_rate  │        │ status           │
└──────────────────┘        └──────────────────┘


┌──────────────────┐        ┌──────────────────┐
│ CANTEEN_RECORD   │        │  SCHOOL_FUND     │
├──────────────────┤        ├──────────────────┤
│ record_id (PK)   │        │ fund_id (PK)     │
│ transaction_date │        │ transaction_date │
│ revenue          │        │ category         │
│ expenses         │        │ description      │
│ net_income       │        │ amount           │
│ recorded_by (FK) │        │ balance          │
└──────────────────┘        │ prepared_by (FK) │
                            │ approved_by (FK) │
                            └──────────────────┘


┌──────────────────┐        ┌──────────────────┐
│  TIME_RECORD     │        │ DEPED_DOCUMENT   │
├──────────────────┤        ├──────────────────┤
│ record_id (PK)   │        │deped_doc_id (PK) │
│ employee_id (FK) │        │ document_type    │
│ record_date      │        │ description      │
│ time_in          │        │ due_date         │
│ time_out         │        │ status           │
│ status           │        │ completion_rate  │
│ recorded_by (FK) │        │ prepared_by (FK) │
└──────────────────┘        └──────────────────┘


┌─────────────────┐
│      ROOM       │
├─────────────────┤
│ room_id (PK)    │
│ room_number     │
│ building_name   │
│ room_type       │
│ capacity        │
└─────────────────┘
         │
         │ 1
         │
         │ *
┌──────────────────┐
│ PROPERTY_ITEM    │
├──────────────────┤
│ property_id (PK) │
│ room_id (FK)     │
│ item_name        │
│ quantity         │
│ condition        │
│ inspected_by (FK)│
└──────────────────┘


┌──────────────────┐
│   KPI_METRIC     │
├──────────────────┤
│ kpi_id (PK)      │
│ metric_type      │
│ metric_value     │
│ academic_year    │
│ quarter          │
│ computed_date    │
└──────────────────┘
```

---

## Relationship Summary

### One-to-Many Relationships

1. **USER → TEACHER**
   - One user can be one teacher (if role = 'teacher')
   
2. **USER → DOCUMENT_LINK**
   - One user (secretary) can add many document links

3. **USER → ANNOUNCEMENT**
   - One user (admin) can create many announcements

4. **USER → CANTEEN_RECORD**
   - One user (canteen personnel) can record many transactions

5. **USER → SCHOOL_FUND**
   - One user (disbursing officer) can prepare many fund records

6. **USER → TIME_RECORD**
   - One user (ADAS) can record many time entries

7. **USER → DEPED_DOCUMENT**
   - One user (ADAS) can prepare many DepEd documents

8. **TEACHER → DOCUMENT_SUBMISSION**
   - One teacher can submit many documents

9. **TEACHER → PERFORMANCE_SUBJECT**
   - One teacher can have many performance records

10. **TEACHER → TIME_RECORD**
    - One teacher (employee) has many time records

11. **GRADE_LEVEL → ENROLLMENT**
    - One grade level has many enrollment records

12. **GRADE_LEVEL → PERFORMANCE_LEVEL**
    - One grade level has many performance records

13. **SUBJECT → PERFORMANCE_SUBJECT**
    - One subject has many performance records

14. **DOCUMENT_TYPE → DOCUMENT_SUBMISSION**
    - One document type can have many submissions

15. **DOCUMENT_SUBMISSION → FEEDBACK**
    - One document can have many feedback comments

16. **ROOM → PROPERTY_ITEM**
    - One room contains many property items

### Many-to-One Relationships (Inverse of above)

All foreign key relationships create many-to-one relationships from the child to parent entity.

---

## Indexes Recommendations

### Primary Indexes (Automatic on PRIMARY KEY)
- All entities have primary key indexes

### Foreign Key Indexes
```sql
-- USER relationships
CREATE INDEX idx_teacher_user_id ON TEACHER(user_id);
CREATE INDEX idx_document_link_added_by ON DOCUMENT_LINK(added_by);
CREATE INDEX idx_announcement_created_by ON ANNOUNCEMENT(created_by);

-- TEACHER relationships
CREATE INDEX idx_document_submission_teacher_id ON DOCUMENT_SUBMISSION(teacher_id);
CREATE INDEX idx_performance_subject_teacher_id ON PERFORMANCE_SUBJECT(teacher_id);
CREATE INDEX idx_time_record_employee_id ON TIME_RECORD(employee_id);

-- DOCUMENT relationships
CREATE INDEX idx_feedback_document_id ON FEEDBACK(document_id);
CREATE INDEX idx_document_submission_doc_type_id ON DOCUMENT_SUBMISSION(doc_type_id);
CREATE INDEX idx_document_submission_subject_id ON DOCUMENT_SUBMISSION(subject_id);

-- LEVEL relationships
CREATE INDEX idx_enrollment_level_id ON ENROLLMENT(level_id);
CREATE INDEX idx_performance_level_level_id ON PERFORMANCE_LEVEL(level_id);

-- ROOM relationships
CREATE INDEX idx_property_item_room_id ON PROPERTY_ITEM(room_id);
```

### Composite Indexes (for common queries)
```sql
-- Performance tracking
CREATE INDEX idx_performance_subject_quarter_year ON PERFORMANCE_SUBJECT(quarter, academic_year);
CREATE INDEX idx_performance_level_quarter_year ON PERFORMANCE_LEVEL(quarter, academic_year);

-- Document management
CREATE INDEX idx_document_submission_status_date ON DOCUMENT_SUBMISSION(status, submission_date);
CREATE INDEX idx_document_submission_teacher_status ON DOCUMENT_SUBMISSION(teacher_id, status);

-- Time tracking
CREATE INDEX idx_time_record_date_status ON TIME_RECORD(record_date, status);

-- Financial tracking
CREATE INDEX idx_canteen_record_date ON CANTEEN_RECORD(transaction_date);
CREATE INDEX idx_school_fund_date_category ON SCHOOL_FUND(transaction_date, category);

-- DepEd compliance
CREATE INDEX idx_deped_document_status_due_date ON DEPED_DOCUMENT(status, due_date);
```

---

## Data Flow Summary

### 1. **Document Submission Flow**
```
TEACHER → DOCUMENT_SUBMISSION → FEEDBACK ← USER (Admin)
         ↓
    DOCUMENT_TYPE
         ↓
      SUBJECT
```

### 2. **Performance Monitoring Flow**
```
TEACHER → PERFORMANCE_SUBJECT ← SUBJECT
                ↓
         GRADE_LEVEL → PERFORMANCE_LEVEL
```

### 3. **Financial Reporting Flow**
```
USER (Canteen) → CANTEEN_RECORD
USER (Disbursing) → SCHOOL_FUND → USER (Admin approval)
```

### 4. **Administrative Compliance Flow**
```
USER (ADAS) → TIME_RECORD ← TEACHER
USER (ADAS) → DEPED_DOCUMENT
USER (ADAS) → PROPERTY_ITEM ← ROOM
```

### 5. **Communication Flow**
```
USER (Admin/Secretary) → ANNOUNCEMENT → All Users
USER (Secretary) → DOCUMENT_LINK → All Users/Teachers
```

---

## Notes

1. **Computed Fields:**
   - `net_income` in CANTEEN_RECORD (revenue - expenses)
   - `attendance_rate` in PARENT_MEETING (actual/expected * 100)
   - `submission_rate` in TEACHER (calculated from DOCUMENT_SUBMISSION)

2. **Enum Values:**
   - USER.role: 'admin', 'teacher', 'secretary', 'canteen', 'disbursing', 'adas'
   - DOCUMENT_SUBMISSION.status: 'Submitted', 'Reviewed', 'Rejected'
   - TIME_RECORD.status: 'Present', 'Late', 'Absent', 'Half-day'
   - PROPERTY_ITEM.condition: 'Excellent', 'Good', 'Fair', 'Poor', 'Needs Repair'
   - SCHOOL_FUND.category: 'MOOE', 'Capital Outlay', 'Maintenance'
   - DEPED_DOCUMENT.status: 'Pending', 'In Progress', 'Completed'

3. **Academic Year Format:** 'YYYY-YYYY' (e.g., '2025-2026')

4. **Access Control:**
   - Role-based access enforced at application layer
   - DOCUMENT_LINK has access_level field for granular control

---

## Database Normalization

The schema follows **Third Normal Form (3NF)**:
- ✓ First Normal Form: All attributes are atomic
- ✓ Second Normal Form: No partial dependencies
- ✓ Third Normal Form: No transitive dependencies

**Denormalized Fields (for performance):**
- `submission_rate` in TEACHER (cached calculation)
- `attendance_rate` in PARENT_MEETING (cached calculation)
- `balance` in SCHOOL_FUND (running balance)

---

**End of ERD Documentation**