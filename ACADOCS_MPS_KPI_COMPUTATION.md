# ACADOCS - MPS & KPI Computation Documentation

## System: Academic Document Management System (ACADOCS)
**Version:** 2.0  
**Last Updated:** April 8, 2026

---

## Table of Contents
1. [Additional Entities for MPS/KPI Computation](#additional-entities)
2. [MPS Computation Flow](#mps-computation-flow)
3. [KPI Computation Formulas](#kpi-computation-formulas)
4. [Automated Triggers](#automated-triggers)
5. [Stored Procedures](#stored-procedures)
6. [Business Rules](#business-rules)

---

## ADDITIONAL ENTITIES FOR MPS & KPI COMPUTATION

### 21. STUDENT
**Description:** Student master data for academic tracking

| Attribute | Data Type | Constraints | Description |
|-----------|-----------|-------------|-------------|
| student_id | VARCHAR(50) | PRIMARY KEY | Unique student identifier |
| lrn | VARCHAR(20) | UNIQUE, NOT NULL | Learner Reference Number |
| first_name | VARCHAR(100) | NOT NULL | Student first name |
| last_name | VARCHAR(100) | NOT NULL | Student last name |
| middle_name | VARCHAR(100) | NULL | Student middle name |
| level_id | VARCHAR(50) | FOREIGN KEY → GRADE_LEVEL(level_id) | Current grade level |
| section | VARCHAR(50) | NOT NULL | Section assignment |
| academic_year | VARCHAR(20) | NOT NULL | Current academic year |
| enrollment_status | ENUM | DEFAULT 'Active' | Status: 'Active', 'Dropped', 'Transferred', 'Graduated' |
| date_enrolled | DATE | NOT NULL | Date of enrollment |
| created_at | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Record creation timestamp |

**Relationships:**
- Many-to-One with GRADE_LEVEL
- One-to-Many with STUDENT_GRADE
- One-to-Many with STUDENT_ASSESSMENT

---

### 22. CLASS_RECORD
**Description:** Teacher class assignments and grading periods

| Attribute | Data Type | Constraints | Description |
|-----------|-----------|-------------|-------------|
| class_id | VARCHAR(50) | PRIMARY KEY | Unique class identifier |
| teacher_id | VARCHAR(50) | FOREIGN KEY → TEACHER(teacher_id) | Teacher assigned |
| subject_id | VARCHAR(50) | FOREIGN KEY → SUBJECT(subject_id) | Subject taught |
| level_id | VARCHAR(50) | FOREIGN KEY → GRADE_LEVEL(level_id) | Grade level |
| section | VARCHAR(50) | NOT NULL | Section name |
| quarter | INT | NOT NULL | Academic quarter (1-4) |
| academic_year | VARCHAR(20) | NOT NULL | Academic year |
| total_students | INT | DEFAULT 0 | Number of enrolled students |
| created_at | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Record creation timestamp |

**Relationships:**
- Many-to-One with TEACHER
- Many-to-One with SUBJECT
- Many-to-One with GRADE_LEVEL
- One-to-Many with STUDENT_GRADE
- One-to-Many with STUDENT_ASSESSMENT

---

### 23. ASSESSMENT_COMPONENT
**Description:** Grading components per subject (DepEd K-12 grading system)

| Attribute | Data Type | Constraints | Description |
|-----------|-----------|-------------|-------------|
| component_id | VARCHAR(50) | PRIMARY KEY | Unique component identifier |
| component_name | VARCHAR(100) | NOT NULL | Component name |
| component_type | ENUM | NOT NULL | Type: 'Written Work', 'Performance Task', 'Quarterly Assessment' |
| weight_percentage | DECIMAL(5,2) | NOT NULL | Percentage weight in final grade |
| subject_id | VARCHAR(50) | FOREIGN KEY → SUBJECT(subject_id) | Subject reference |
| is_active | BOOLEAN | DEFAULT TRUE | Component active status |

#### Weight Distribution (DepEd K-12 Grading System)

**Core Subjects** (Math, Science, English, Filipino):
- Written Work: 30%
- Performance Task: 50%
- Quarterly Assessment: 20%

**Non-Core Subjects** (MAPEH, TLE, EPP, etc.):
- Written Work: 20%
- Performance Task: 60%
- Quarterly Assessment: 20%

**Relationships:**
- Many-to-One with SUBJECT
- One-to-Many with STUDENT_ASSESSMENT

---

### 24. STUDENT_ASSESSMENT
**Description:** Individual student assessment scores per component

| Attribute | Data Type | Constraints | Description |
|-----------|-----------|-------------|-------------|
| assessment_id | VARCHAR(50) | PRIMARY KEY | Unique assessment identifier |
| student_id | VARCHAR(50) | FOREIGN KEY → STUDENT(student_id) | Student reference |
| class_id | VARCHAR(50) | FOREIGN KEY → CLASS_RECORD(class_id) | Class reference |
| component_id | VARCHAR(50) | FOREIGN KEY → ASSESSMENT_COMPONENT(component_id) | Component type |
| assessment_name | VARCHAR(200) | NOT NULL | Specific assessment name (e.g., "Quiz 1", "Project 1") |
| score_obtained | DECIMAL(6,2) | NOT NULL | Score obtained by student |
| highest_possible_score | DECIMAL(6,2) | NOT NULL | Maximum possible score |
| percentage_score | DECIMAL(5,2) | GENERATED | (score_obtained / highest_possible_score) * 100 |
| date_recorded | DATE | NOT NULL | Date score was recorded |
| recorded_by | VARCHAR(50) | FOREIGN KEY → TEACHER(teacher_id) | Teacher who recorded |
| created_at | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Record creation timestamp |

**Relationships:**
- Many-to-One with STUDENT
- Many-to-One with CLASS_RECORD
- Many-to-One with ASSESSMENT_COMPONENT
- Many-to-One with TEACHER (recorded_by)

---

### 25. STUDENT_GRADE
**Description:** Computed quarterly and final grades per student per subject

| Attribute | Data Type | Constraints | Description |
|-----------|-----------|-------------|-------------|
| grade_id | VARCHAR(50) | PRIMARY KEY | Unique grade identifier |
| student_id | VARCHAR(50) | FOREIGN KEY → STUDENT(student_id) | Student reference |
| class_id | VARCHAR(50) | FOREIGN KEY → CLASS_RECORD(class_id) | Class reference |
| quarter | INT | NOT NULL | Academic quarter (1-4) |
| written_work_ps | DECIMAL(5,2) | NOT NULL | Written Work Percentage Score |
| performance_task_ps | DECIMAL(5,2) | NOT NULL | Performance Task Percentage Score |
| quarterly_assessment_ps | DECIMAL(5,2) | NOT NULL | Quarterly Assessment Percentage Score |
| written_work_ws | DECIMAL(5,2) | GENERATED | Written Work Weighted Score |
| performance_task_ws | DECIMAL(5,2) | GENERATED | Performance Task Weighted Score |
| quarterly_assessment_ws | DECIMAL(5,2) | GENERATED | Quarterly Assessment Weighted Score |
| initial_grade | DECIMAL(5,2) | GENERATED | Sum of all weighted scores |
| quarterly_grade | DECIMAL(5,2) | GENERATED | Transmuted grade (based on DepEd table) |
| descriptor | VARCHAR(20) | GENERATED | Performance descriptor |
| is_passed | BOOLEAN | GENERATED | TRUE if grade >= 75 |
| academic_year | VARCHAR(20) | NOT NULL | Academic year |
| computed_date | DATE | NOT NULL | Date grade was computed |
| created_at | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Record creation timestamp |

**Relationships:**
- Many-to-One with STUDENT
- Many-to-One with CLASS_RECORD

---

### 26. MPS_COMPUTATION_LOG
**Description:** Audit trail for MPS (Mean Percentage Score) computations

| Attribute | Data Type | Constraints | Description |
|-----------|-----------|-------------|-------------|
| computation_id | VARCHAR(50) | PRIMARY KEY | Unique computation identifier |
| subject_id | VARCHAR(50) | FOREIGN KEY → SUBJECT(subject_id) | Subject reference |
| teacher_id | VARCHAR(50) | FOREIGN KEY → TEACHER(teacher_id) | Teacher reference |
| class_id | VARCHAR(50) | FOREIGN KEY → CLASS_RECORD(class_id) | Class reference |
| quarter | INT | NOT NULL | Academic quarter |
| academic_year | VARCHAR(20) | NOT NULL | Academic year |
| total_students | INT | NOT NULL | Number of students included |
| sum_of_grades | DECIMAL(10,2) | NOT NULL | Sum of all student initial grades |
| computed_mps | DECIMAL(5,2) | NOT NULL | Mean Percentage Score |
| computed_nds | DECIMAL(5,2) | NOT NULL | Numerical Descriptor Score (transmuted) |
| passing_count | INT | NOT NULL | Number of students who passed (grade >= 75) |
| failing_count | INT | NOT NULL | Number of students who failed |
| passing_rate | DECIMAL(5,2) | GENERATED | (passing_count / total_students) * 100 |
| computation_method | VARCHAR(100) | DEFAULT 'AUTO' | Computation method used |
| computed_by | VARCHAR(50) | FOREIGN KEY → USER(user_id) | User who triggered computation |
| computed_date | TIMESTAMP | NOT NULL | Date/time computed |
| created_at | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Record creation timestamp |

**Relationships:**
- Many-to-One with SUBJECT
- Many-to-One with TEACHER
- Many-to-One with CLASS_RECORD
- Many-to-One with USER (computed_by)

---

### 27. KPI_COMPUTATION_LOG
**Description:** Audit trail for KPI calculations

| Attribute | Data Type | Constraints | Description |
|-----------|-----------|-------------|-------------| 
| kpi_computation_id | VARCHAR(50) | PRIMARY KEY | Unique KPI computation identifier |
| kpi_type | ENUM | NOT NULL | Type: 'total_enrollment', 'submission_compliance', 'average_mps', 'survival_rate', 'parent_attendance' |
| computation_formula | TEXT | NOT NULL | Formula/method used |
| input_data | JSON | NULL | Input data used for computation |
| computed_value | DECIMAL(10,2) | NOT NULL | Resulting KPI value |
| quarter | INT | NULL | Quarter if applicable |
| academic_year | VARCHAR(20) | NOT NULL | Academic year |
| computed_by | VARCHAR(50) | FOREIGN KEY → USER(user_id) | User who triggered computation |
| computed_date | TIMESTAMP | NOT NULL | Date/time computed |
| is_automated | BOOLEAN | DEFAULT TRUE | Whether auto-computed or manual |
| created_at | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Record creation timestamp |

**Relationships:**
- Many-to-One with USER (computed_by)

---

### 28. TRANSMUTATION_TABLE
**Description:** DepEd transmutation table for converting initial grades to final grades

| Attribute | Data Type | Constraints | Description |
|-----------|-----------|-------------|-------------|
| transmutation_id | VARCHAR(50) | PRIMARY KEY | Unique transmutation identifier |
| initial_grade_from | DECIMAL(5,2) | NOT NULL | Starting range (e.g., 96.00) |
| initial_grade_to | DECIMAL(5,2) | NOT NULL | Ending range (e.g., 100.00) |
| transmuted_grade | DECIMAL(5,2) | NOT NULL | Final transmuted grade (e.g., 100) |
| descriptor | VARCHAR(50) | NOT NULL | Performance descriptor |
| grading_system | VARCHAR(20) | DEFAULT 'K-12' | Grading system type |
| is_active | BOOLEAN | DEFAULT TRUE | Record active status |

#### Sample DepEd Transmutation Table

| Initial Grade Range | Transmuted Grade | Descriptor |
|---------------------|------------------|------------|
| 98.40 - 100.00 | 100 | Outstanding |
| 95.20 - 98.39 | 99 | Outstanding |
| 92.00 - 95.19 | 98 | Outstanding |
| 88.80 - 91.99 | 97 | Very Satisfactory |
| 85.60 - 88.79 | 96 | Very Satisfactory |
| 82.40 - 85.59 | 95 | Very Satisfactory |
| 79.20 - 82.39 | 94 | Very Satisfactory |
| 76.00 - 79.19 | 93 | Satisfactory |
| 72.80 - 75.99 | 92 | Satisfactory |
| 69.60 - 72.79 | 91 | Satisfactory |
| 66.40 - 69.59 | 90 | Satisfactory |
| 63.20 - 66.39 | 89 | Satisfactory |
| 60.00 - 63.19 | 88 | Satisfactory |
| 56.00 - 59.99 | 87 | Satisfactory |
| 52.00 - 55.99 | 86 | Satisfactory |
| 48.00 - 51.99 | 85 | Satisfactory |
| 44.00 - 47.99 | 84 | Satisfactory |
| 40.00 - 43.99 | 83 | Satisfactory |
| 36.00 - 39.99 | 82 | Satisfactory |
| 32.00 - 35.99 | 81 | Satisfactory |
| 28.00 - 31.99 | 80 | Satisfactory |
| 24.00 - 27.99 | 79 | Satisfactory |
| 20.00 - 23.99 | 78 | Satisfactory |
| 16.00 - 19.99 | 77 | Fairly Satisfactory |
| 12.00 - 15.99 | 76 | Fairly Satisfactory |
| 8.00 - 11.99 | 75 | Fairly Satisfactory |
| 4.00 - 7.99 | 74 | Did Not Meet Expectations |
| 0.00 - 3.99 | 73 | Did Not Meet Expectations |

**Relationships:**
- None (lookup table)

---

### 29. SURVIVAL_RATE_TRACKER
**Description:** Tracks student progression between grade levels for survival rate computation

| Attribute | Data Type | Constraints | Description |
|-----------|-----------|-------------|-------------|
| tracker_id | VARCHAR(50) | PRIMARY KEY | Unique tracker identifier |
| cohort_year | VARCHAR(20) | NOT NULL | School year cohort started (e.g., '2023-2024') |
| from_level_id | VARCHAR(50) | FOREIGN KEY → GRADE_LEVEL(level_id) | Starting grade level |
| to_level_id | VARCHAR(50) | FOREIGN KEY → GRADE_LEVEL(level_id) | Next grade level |
| students_from | INT | NOT NULL | Number of students in starting level |
| students_to | INT | NOT NULL | Number who progressed to next level |
| students_dropped | INT | DEFAULT 0 | Number who dropped out |
| students_transferred | INT | DEFAULT 0 | Number who transferred out |
| students_retained | INT | DEFAULT 0 | Number who repeated the level |
| survival_rate | DECIMAL(5,2) | GENERATED | (students_to / students_from) * 100 |
| academic_year | VARCHAR(20) | NOT NULL | Academic year |
| computed_date | DATE | NOT NULL | Date computed |
| created_at | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Record creation timestamp |

**Relationships:**
- Many-to-One with GRADE_LEVEL (from_level_id)
- Many-to-One with GRADE_LEVEL (to_level_id)

---

## MPS COMPUTATION FLOW

### Complete MPS Calculation Process

```
┌─────────────────────────────────────────────────────┐
│                DATA COLLECTION PHASE                │
└─────────────────────────────────────────────────────┘
                         │
                         ▼
┌─────────────────────────────────────────────────────┐
│  Collect STUDENT_ASSESSMENT records for:            │
│  - Specific CLASS_RECORD (teacher + subject)        │
│  - Specific QUARTER (1, 2, 3, or 4)                │
│  - All enrolled STUDENTS in the class               │
│                                                      │
│  Example:                                            │
│  Class: Maria Santos - Math - Grade 7               │
│  Quarter: 3                                          │
│  Students: 45 students                               │
└─────────────────────────────────────────────────────┘
                         │
                         ▼
┌─────────────────────────────────────────────────────┐
│           COMPUTE PERCENTAGE SCORES (PS)            │
│                                                      │
│  For each STUDENT and each COMPONENT:               │
│                                                      │
│  STEP 1: Calculate individual assessment PS         │
│  PS = (score_obtained / highest_possible_score) × 100│
│                                                      │
│  STEP 2: Average all assessments per component      │
│  Written Work PS = AVG(all WW assessments)          │
│  Performance Task PS = AVG(all PT assessments)      │
│  Quarterly Assessment PS = QA score (only 1)        │
│                                                      │
│  Example for Student Juan:                          │
│  WW: Quiz 1 (80%), Quiz 2 (85%) → WW PS = 82.5%    │
│  PT: Project (90%), Performance (88%) → PT PS = 89% │
│  QA: Final Exam → QA PS = 85%                       │
└─────────────────────────────────────────────────────┘
                         │
                         ▼
┌─────────────────────────────────────────────────────┐
│          APPLY WEIGHTS to get WEIGHTED SCORES       │
│                                                      │
│  Get weight_percentage from ASSESSMENT_COMPONENT    │
│                                                      │
│  For CORE SUBJECTS (Math, Science, English):        │
│  WW WS = WW PS × 30% (0.30)                         │
│  PT WS = PT PS × 50% (0.50)                         │
│  QA WS = QA PS × 20% (0.20)                         │
│                                                      │
│  For NON-CORE SUBJECTS (MAPEH, TLE):                │
│  WW WS = WW PS × 20% (0.20)                         │
│  PT WS = PT PS × 60% (0.60)                         │
│  QA WS = QA PS × 20% (0.20)                         │
│                                                      │
│  Example (Juan - Math - Core Subject):              │
│  WW WS = 82.5 × 0.30 = 24.75                        │
│  PT WS = 89.0 × 0.50 = 44.50                        │
│  QA WS = 85.0 × 0.20 = 17.00                        │
└─────────────────────────────────────────────────────┘
                         │
                         ▼
┌─────────────────────────────────────────────────────┐
│         COMPUTE INITIAL GRADE per STUDENT           │
│                                                      │
│  Initial Grade = WW WS + PT WS + QA WS              │
│  (Stored in STUDENT_GRADE.initial_grade)            │
│                                                      │
│  Example (Juan):                                     │
│  Initial Grade = 24.75 + 44.50 + 17.00 = 86.25      │
└─────────────────────────────────────────────────────┘
                         │
                         ▼
┌─────────────────────────────────────────────────────┐
│    TRANSMUTE INITIAL GRADE to QUARTERLY GRADE       │
│                                                      │
│  1. Look up TRANSMUTATION_TABLE                     │
│  2. Find range where initial_grade falls            │
│  3. Apply corresponding transmuted_grade            │
│  4. Assign descriptor                               │
│  (Stored in STUDENT_GRADE.quarterly_grade)          │
│                                                      │
│  Example (Juan):                                     │
│  Initial Grade: 86.25                                │
│  Range: 85.60 - 88.79 → Transmuted Grade: 96        │
│  Descriptor: "Very Satisfactory"                     │
│  is_passed: TRUE (96 >= 75)                         │
└─────────────────────────────────────────────────────┘
                         │
                         ▼
┌─────────────────────────────────────────────────────┐
│            COMPUTE CLASS MPS (Mean)                 │
│                                                      │
│  MPS = SUM(all student initial_grades) /            │
│        COUNT(students in class)                      │
│                                                      │
│  Example (Maria Santos - Math - Grade 7):           │
│  Total of all student initial grades: 3,712.50      │
│  Number of students: 45                              │
│  MPS = 3,712.50 / 45 = 82.50                        │
└─────────────────────────────────────────────────────┘
                         │
                         ▼
┌─────────────────────────────────────────────────────┐
│          TRANSMUTE MPS to NDS (Numerical)           │
│                                                      │
│  Apply same TRANSMUTATION_TABLE to class MPS        │
│                                                      │
│  Example:                                            │
│  MPS: 82.50                                          │
│  Range: 82.40 - 85.59 → NDS: 95                     │
│  Descriptor: "Very Satisfactory"                     │
└─────────────────────────────────────────────────────┘
                         │
                         ▼
┌─────────────────────────────────────────────────────┐
│              STORE MPS COMPUTATION                  │
│                                                      │
│  1. Update PERFORMANCE_SUBJECT table:               │
│     - Set mps = 82.50                                │
│     - Set for correct teacher, subject, quarter     │
│                                                      │
│  2. Create MPS_COMPUTATION_LOG entry:               │
│     - computation_id: generated UUID                 │
│     - class_id, quarter, academic_year              │
│     - total_students: 45                             │
│     - computed_mps: 82.50                            │
│     - computed_nds: 95                               │
│     - passing_count: 42                              │
│     - failing_count: 3                               │
│     - passing_rate: 93.33%                           │
│     - computed_date: timestamp                       │
└─────────────────────────────────────────────────────┘
                         │
                         ▼
┌─────────────────────────────────────────────────────┐
│       AGGREGATE TO GRADE LEVEL PERFORMANCE          │
│                                                      │
│  1. Average all subject MPS for the grade level     │
│     (All Math, Science, English, etc. for Grade 7)  │
│                                                      │
│  2. Update PERFORMANCE_LEVEL table:                 │
│     - Calculate overall grade level MPS             │
│     - Calculate overall NDS                          │
│                                                      │
│  3. Trigger KPI recalculation:                      │
│     - School-wide Average MPS                        │
└─────────────────────────────────────────────────────┘
```

---

## KPI COMPUTATION FORMULAS

### 1. Total Enrollment KPI

**Definition:** Total number of students enrolled in all grade levels

**SQL Formula:**
```sql
SELECT SUM(student_count) as total_enrollment
FROM ENROLLMENT
WHERE academic_year = '2025-2026'
  AND enrollment_date = (
    SELECT MAX(enrollment_date) 
    FROM ENROLLMENT 
    WHERE academic_year = '2025-2026'
  );
```

**Computation Triggers:**
- New student enrollment
- Student status change (dropped/transferred/graduated)
- End of enrollment period
- Daily automated update

**Expected Output:** Integer (e.g., 1247)

**Storage:** KPI_METRIC table with metric_type = 'enrollment'

---

### 2. Submission Compliance Rate KPI

**Definition:** Percentage of required documents submitted by teachers

**SQL Formula:**
```sql
WITH required_docs AS (
  SELECT COUNT(*) * 
         (SELECT COUNT(*) FROM DOCUMENT_TYPE WHERE is_required = TRUE) 
         as total_required
  FROM TEACHER 
  WHERE is_active = TRUE
),
submitted_docs AS (
  SELECT COUNT(DISTINCT CONCAT(teacher_id, '-', doc_type_id)) as total_submitted
  FROM DOCUMENT_SUBMISSION
  WHERE status IN ('Submitted', 'Reviewed')
    AND submission_date >= DATE_SUB(CURRENT_DATE, INTERVAL 1 QUARTER)
)
SELECT 
  (submitted_docs.total_submitted * 100.0 / NULLIF(required_docs.total_required, 0)) 
  as compliance_rate
FROM required_docs, submitted_docs;
```

**Computation Triggers:**
- Document submission (any teacher)
- Document review/approval
- Start of new quarter
- Weekly automated update

**Expected Output:** Decimal (e.g., 87.5%)

**Storage:** KPI_METRIC table with metric_type = 'compliance'

**Alternative Formula (Teacher-specific):**
```sql
UPDATE TEACHER
SET submission_rate = (
  SELECT COUNT(*) * 100.0 / 
    (SELECT COUNT(*) FROM DOCUMENT_TYPE WHERE is_required = TRUE)
  FROM DOCUMENT_SUBMISSION
  WHERE teacher_id = TEACHER.teacher_id
    AND status IN ('Submitted', 'Reviewed')
    AND submission_date >= DATE_SUB(CURRENT_DATE, INTERVAL 1 QUARTER)
)
WHERE teacher_id = 'T001';
```

---

### 3. Average MPS (School-Wide) KPI

**Definition:** Mean of all class MPS across all subjects and teachers

**SQL Formula (from Performance Subject):**
```sql
SELECT AVG(mps) as average_mps
FROM PERFORMANCE_SUBJECT
WHERE quarter = 3
  AND academic_year = '2025-2026';
```

**SQL Formula (from Computation Logs):**
```sql
SELECT AVG(computed_mps) as average_mps
FROM MPS_COMPUTATION_LOG
WHERE quarter = 3
  AND academic_year = '2025-2026';
```

**SQL Formula (Level-specific):**
```sql
SELECT AVG(mps) as level_average_mps
FROM PERFORMANCE_SUBJECT
WHERE level_name = 'Grade 7'
  AND quarter = 3
  AND academic_year = '2025-2026';
```

**Computation Triggers:**
- MPS computation for any class
- End of quarter (automated rollup)
- Grade finalization
- Admin manual trigger

**Expected Output:** Decimal (e.g., 82.3%)

**Storage:** 
- KPI_METRIC table with metric_type = 'mps'
- PERFORMANCE_LEVEL table (aggregated by level)

---

### 4. Survival Rate KPI

**Definition:** Percentage of students who progress from one grade level to the next

**SQL Formula (Overall School):**
```sql
WITH cohort_tracking AS (
  SELECT 
    from_level_id,
    to_level_id,
    students_from,
    students_to,
    (students_to * 100.0 / NULLIF(students_from, 0)) as rate
  FROM SURVIVAL_RATE_TRACKER
  WHERE academic_year = '2025-2026'
)
SELECT AVG(rate) as overall_survival_rate
FROM cohort_tracking;
```

**SQL Formula (Level-to-Level):**
```sql
SELECT 
  (students_to * 100.0 / NULLIF(students_from, 0)) as survival_rate
FROM SURVIVAL_RATE_TRACKER
WHERE from_level_id = (SELECT level_id FROM GRADE_LEVEL WHERE level_name = 'Grade 7')
  AND to_level_id = (SELECT level_id FROM GRADE_LEVEL WHERE level_name = 'Grade 8')
  AND cohort_year = '2024-2025';
```

**Detailed Calculation:**
```sql
-- Step 1: Count students in Grade 7 (2024-2025)
DECLARE @grade7_count INT = 320;

-- Step 2: Count those same students now in Grade 8 (2025-2026)
DECLARE @progressed_to_grade8 INT = 308;

-- Step 3: Calculate survival rate
DECLARE @survival_rate DECIMAL(5,2) = (@progressed_to_grade8 * 100.0 / @grade7_count);
-- Result: (308 / 320) × 100 = 96.25%
```

**Computation Triggers:**
- Academic year end (June)
- Enrollment opening for new year
- Student status updates (dropped/transferred)
- Annual automated computation

**Expected Output:** Decimal (e.g., 94.8%)

**Storage:** 
- SURVIVAL_RATE_TRACKER table (detailed tracking)
- KPI_METRIC table with metric_type = 'survival_rate'

---

### 5. Parent Attendance Rate KPI

**Definition:** Average percentage of parent attendance at scheduled meetings

**SQL Formula (Overall Average):**
```sql
SELECT AVG(attendance_rate) as parent_attendance_kpi
FROM PARENT_MEETING
WHERE academic_year = '2025-2026';
```

**SQL Formula (Per Quarter):**
```sql
SELECT AVG(attendance_rate) as parent_attendance_kpi
FROM PARENT_MEETING
WHERE academic_year = '2025-2026'
  AND quarter = 3;
```

**SQL Formula (Latest Meeting):**
```sql
SELECT 
  (actual_attendance * 100.0 / NULLIF(expected_parents, 0)) as latest_attendance_rate
FROM PARENT_MEETING
WHERE academic_year = '2025-2026'
ORDER BY meeting_date DESC
LIMIT 1;
```

**Detailed Calculation Example:**
```
Meeting 1 (Q1):
- Expected: 450 parents
- Actual: 342 parents
- Rate: (342 / 450) × 100 = 76.0%

Meeting 2 (Q2):
- Expected: 450 parents
- Actual: 389 parents
- Rate: (389 / 450) × 100 = 86.4%

Overall Average: (76.0 + 86.4) / 2 = 81.2%
```

**Computation Triggers:**
- Parent meeting completion
- Attendance record update
- Quarter end
- Monthly automated update

**Expected Output:** Decimal (e.g., 76.2%)

**Storage:** 
- PARENT_MEETING table (per-meeting rates)
- KPI_METRIC table with metric_type = 'parent_attendance'

---

## AUTOMATED COMPUTATION TRIGGERS

### Database Trigger Specifications

#### 1. Auto-Compute MPS After Assessment Recording

```sql
DELIMITER $$
CREATE TRIGGER trg_compute_mps_after_assessment
AFTER INSERT OR UPDATE ON STUDENT_ASSESSMENT
FOR EACH ROW
BEGIN
  DECLARE v_total_assessments INT;
  DECLARE v_required_assessments INT;
  
  -- Check if all required assessments are complete for the class
  SELECT COUNT(DISTINCT sa.component_id) INTO v_total_assessments
  FROM STUDENT_ASSESSMENT sa
  WHERE sa.class_id = NEW.class_id;
  
  -- Get required number of components (should be 3: WW, PT, QA)
  SET v_required_assessments = 3;
  
  -- If all components have at least one assessment, trigger MPS computation
  IF v_total_assessments >= v_required_assessments THEN
    CALL sp_compute_class_mps(NEW.class_id, 
                               (SELECT quarter FROM CLASS_RECORD WHERE class_id = NEW.class_id));
  END IF;
END$$
DELIMITER ;
```

#### 2. Auto-Update Student Grade When Scores Change

```sql
DELIMITER $$
CREATE TRIGGER trg_update_student_grade
AFTER INSERT OR UPDATE ON STUDENT_ASSESSMENT
FOR EACH ROW
BEGIN
  -- Recalculate weighted scores and update STUDENT_GRADE table
  CALL sp_compute_student_grade(NEW.student_id, NEW.class_id);
END$$
DELIMITER ;
```

#### 3. Auto-Compute Submission Compliance

```sql
DELIMITER $$
CREATE TRIGGER trg_update_compliance_rate
AFTER INSERT OR UPDATE ON DOCUMENT_SUBMISSION
FOR EACH ROW
BEGIN
  -- Recalculate teacher-specific submission rate
  CALL sp_update_teacher_submission_rate(NEW.teacher_id);
  
  -- Recalculate school-wide compliance KPI
  CALL sp_compute_submission_compliance_kpi();
END$$
DELIMITER ;
```

#### 4. Auto-Update Performance Level Aggregates

```sql
DELIMITER $$
CREATE TRIGGER trg_update_performance_level
AFTER INSERT OR UPDATE ON PERFORMANCE_SUBJECT
FOR EACH ROW
BEGIN
  -- Aggregate MPS for the entire grade level
  CALL sp_aggregate_level_performance(NEW.level_name, NEW.quarter, NEW.academic_year);
  
  -- Trigger school-wide MPS KPI update
  CALL sp_compute_average_mps_kpi(NEW.quarter, NEW.academic_year);
END$$
DELIMITER ;
```

#### 5. Auto-Compute Survival Rate at Year End

```sql
DELIMITER $$
CREATE EVENT evt_compute_annual_survival_rate
ON SCHEDULE EVERY 1 YEAR
STARTS '2026-05-31 23:59:00'  -- End of school year
DO
BEGIN
  DECLARE v_current_year VARCHAR(20);
  SET v_current_year = CONCAT(YEAR(CURRENT_DATE), '-', YEAR(CURRENT_DATE) + 1);
  
  CALL sp_compute_survival_rates(v_current_year);
  CALL sp_compute_survival_rate_kpi(v_current_year);
END$$
DELIMITER ;
```

---

## STORED PROCEDURES

### 1. Compute Student Grade

```sql
DELIMITER $$
CREATE PROCEDURE sp_compute_student_grade(
  IN p_student_id VARCHAR(50),
  IN p_class_id VARCHAR(50)
)
BEGIN
  DECLARE v_ww_ps DECIMAL(5,2);
  DECLARE v_pt_ps DECIMAL(5,2);
  DECLARE v_qa_ps DECIMAL(5,2);
  DECLARE v_ww_weight DECIMAL(5,2);
  DECLARE v_pt_weight DECIMAL(5,2);
  DECLARE v_qa_weight DECIMAL(5,2);
  DECLARE v_ww_ws DECIMAL(5,2);
  DECLARE v_pt_ws DECIMAL(5,2);
  DECLARE v_qa_ws DECIMAL(5,2);
  DECLARE v_initial_grade DECIMAL(5,2);
  DECLARE v_quarterly_grade DECIMAL(5,2);
  DECLARE v_descriptor VARCHAR(50);
  DECLARE v_quarter INT;
  
  -- Get quarter from class record
  SELECT quarter INTO v_quarter
  FROM CLASS_RECORD
  WHERE class_id = p_class_id;
  
  -- Get component weights (depends on subject type)
  SELECT 
    MAX(CASE WHEN component_type = 'Written Work' THEN weight_percentage END),
    MAX(CASE WHEN component_type = 'Performance Task' THEN weight_percentage END),
    MAX(CASE WHEN component_type = 'Quarterly Assessment' THEN weight_percentage END)
  INTO v_ww_weight, v_pt_weight, v_qa_weight
  FROM ASSESSMENT_COMPONENT ac
  INNER JOIN CLASS_RECORD cr ON cr.subject_id = ac.subject_id
  WHERE cr.class_id = p_class_id;
  
  -- Calculate average percentage score for each component
  SELECT 
    AVG(CASE WHEN ac.component_type = 'Written Work' THEN sa.percentage_score END),
    AVG(CASE WHEN ac.component_type = 'Performance Task' THEN sa.percentage_score END),
    AVG(CASE WHEN ac.component_type = 'Quarterly Assessment' THEN sa.percentage_score END)
  INTO v_ww_ps, v_pt_ps, v_qa_ps
  FROM STUDENT_ASSESSMENT sa
  INNER JOIN ASSESSMENT_COMPONENT ac ON sa.component_id = ac.component_id
  WHERE sa.student_id = p_student_id
    AND sa.class_id = p_class_id;
  
  -- Calculate weighted scores
  SET v_ww_ws = v_ww_ps * (v_ww_weight / 100);
  SET v_pt_ws = v_pt_ps * (v_pt_weight / 100);
  SET v_qa_ws = v_qa_ps * (v_qa_weight / 100);
  
  -- Calculate initial grade
  SET v_initial_grade = v_ww_ws + v_pt_ws + v_qa_ws;
  
  -- Transmute to quarterly grade
  SELECT transmuted_grade, descriptor 
  INTO v_quarterly_grade, v_descriptor
  FROM TRANSMUTATION_TABLE
  WHERE v_initial_grade BETWEEN initial_grade_from AND initial_grade_to
  LIMIT 1;
  
  -- Insert or update STUDENT_GRADE table
  INSERT INTO STUDENT_GRADE (
    grade_id, student_id, class_id, quarter,
    written_work_ps, performance_task_ps, quarterly_assessment_ps,
    written_work_ws, performance_task_ws, quarterly_assessment_ws,
    initial_grade, quarterly_grade, descriptor,
    is_passed, academic_year, computed_date
  ) VALUES (
    UUID(), p_student_id, p_class_id, v_quarter,
    v_ww_ps, v_pt_ps, v_qa_ps,
    v_ww_ws, v_pt_ws, v_qa_ws,
    v_initial_grade, v_quarterly_grade, v_descriptor,
    (v_quarterly_grade >= 75),
    (SELECT academic_year FROM CLASS_RECORD WHERE class_id = p_class_id),
    CURRENT_DATE
  )
  ON DUPLICATE KEY UPDATE
    written_work_ps = v_ww_ps,
    performance_task_ps = v_pt_ps,
    quarterly_assessment_ps = v_qa_ps,
    written_work_ws = v_ww_ws,
    performance_task_ws = v_pt_ws,
    quarterly_assessment_ws = v_qa_ws,
    initial_grade = v_initial_grade,
    quarterly_grade = v_quarterly_grade,
    descriptor = v_descriptor,
    is_passed = (v_quarterly_grade >= 75),
    computed_date = CURRENT_DATE;
    
END$$
DELIMITER ;
```

### 2. Compute Class MPS

```sql
DELIMITER $$
CREATE PROCEDURE sp_compute_class_mps(
  IN p_class_id VARCHAR(50),
  IN p_quarter INT
)
BEGIN
  DECLARE v_mps DECIMAL(5,2);
  DECLARE v_nds DECIMAL(5,2);
  DECLARE v_student_count INT;
  DECLARE v_passing_count INT;
  DECLARE v_failing_count INT;
  DECLARE v_sum_of_grades DECIMAL(10,2);
  DECLARE v_subject_id VARCHAR(50);
  DECLARE v_teacher_id VARCHAR(50);
  DECLARE v_level_name VARCHAR(20);
  DECLARE v_academic_year VARCHAR(20);
  
  -- Get class details
  SELECT subject_id, teacher_id, 
         CONCAT('Grade ', SUBSTRING(section, 1, 1)), academic_year
  INTO v_subject_id, v_teacher_id, v_level_name, v_academic_year
  FROM CLASS_RECORD
  WHERE class_id = p_class_id;
  
  -- Calculate MPS from student grades
  SELECT 
    AVG(initial_grade),
    SUM(initial_grade),
    COUNT(*),
    SUM(CASE WHEN is_passed = TRUE THEN 1 ELSE 0 END),
    SUM(CASE WHEN is_passed = FALSE THEN 1 ELSE 0 END)
  INTO v_mps, v_sum_of_grades, v_student_count, v_passing_count, v_failing_count
  FROM STUDENT_GRADE
  WHERE class_id = p_class_id
    AND quarter = p_quarter;
  
  -- Transmute MPS to NDS
  SELECT transmuted_grade INTO v_nds
  FROM TRANSMUTATION_TABLE
  WHERE v_mps BETWEEN initial_grade_from AND initial_grade_to
  LIMIT 1;
  
  -- Update PERFORMANCE_SUBJECT table
  INSERT INTO PERFORMANCE_SUBJECT (
    performance_subject_id, subject_id, teacher_id, level_name,
    mps, quarter, academic_year, recorded_date
  ) VALUES (
    UUID(), v_subject_id, v_teacher_id, v_level_name,
    v_mps, p_quarter, v_academic_year, CURRENT_DATE
  )
  ON DUPLICATE KEY UPDATE
    mps = v_mps,
    recorded_date = CURRENT_DATE;
  
  -- Log computation
  INSERT INTO MPS_COMPUTATION_LOG (
    computation_id, subject_id, teacher_id, class_id, quarter, academic_year,
    total_students, sum_of_grades, computed_mps, computed_nds,
    passing_count, failing_count, computation_method,
    computed_by, computed_date
  ) VALUES (
    UUID(), v_subject_id, v_teacher_id, p_class_id, p_quarter, v_academic_year,
    v_student_count, v_sum_of_grades, v_mps, v_nds,
    v_passing_count, v_failing_count, 'AUTOMATED',
    (SELECT user_id FROM USER WHERE role = 'admin' LIMIT 1),
    CURRENT_TIMESTAMP
  );
  
END$$
DELIMITER ;
```

### 3. Compute All KPIs

```sql
DELIMITER $$
CREATE PROCEDURE sp_compute_all_kpis(
  IN p_academic_year VARCHAR(20),
  IN p_quarter INT
)
BEGIN
  DECLARE v_enrollment DECIMAL(10,2);
  DECLARE v_compliance DECIMAL(5,2);
  DECLARE v_avg_mps DECIMAL(5,2);
  DECLARE v_survival DECIMAL(5,2);
  DECLARE v_parent_attendance DECIMAL(5,2);
  DECLARE v_admin_user_id VARCHAR(50);
  
  -- Get admin user for logging
  SELECT user_id INTO v_admin_user_id
  FROM USER WHERE role = 'admin' LIMIT 1;
  
  -- 1. Total Enrollment
  SELECT SUM(student_count) INTO v_enrollment
  FROM ENROLLMENT 
  WHERE academic_year = p_academic_year;
  
  -- 2. Submission Compliance
  WITH required_docs AS (
    SELECT COUNT(*) * 
           (SELECT COUNT(*) FROM DOCUMENT_TYPE WHERE is_required = TRUE) 
           as total_required
    FROM TEACHER WHERE is_active = TRUE
  ),
  submitted_docs AS (
    SELECT COUNT(DISTINCT CONCAT(teacher_id, '-', doc_type_id)) as total_submitted
    FROM DOCUMENT_SUBMISSION
    WHERE status IN ('Submitted', 'Reviewed')
      AND submission_date >= DATE_SUB(CURRENT_DATE, INTERVAL 1 QUARTER)
  )
  SELECT 
    (submitted_docs.total_submitted * 100.0 / NULLIF(required_docs.total_required, 0))
  INTO v_compliance
  FROM required_docs, submitted_docs;
  
  -- 3. Average MPS
  SELECT AVG(mps) INTO v_avg_mps
  FROM PERFORMANCE_SUBJECT
  WHERE academic_year = p_academic_year AND quarter = p_quarter;
  
  -- 4. Survival Rate
  SELECT AVG(survival_rate) INTO v_survival
  FROM SURVIVAL_RATE_TRACKER
  WHERE academic_year = p_academic_year;
  
  -- 5. Parent Attendance
  SELECT AVG(attendance_rate) INTO v_parent_attendance
  FROM PARENT_MEETING
  WHERE academic_year = p_academic_year;
  
  -- Insert into KPI_METRIC table
  INSERT INTO KPI_METRIC (kpi_id, metric_type, metric_value, academic_year, quarter, computed_date)
  VALUES 
    (UUID(), 'enrollment', v_enrollment, p_academic_year, p_quarter, CURRENT_DATE),
    (UUID(), 'compliance', v_compliance, p_academic_year, p_quarter, CURRENT_DATE),
    (UUID(), 'mps', v_avg_mps, p_academic_year, p_quarter, CURRENT_DATE),
    (UUID(), 'survival_rate', v_survival, p_academic_year, NULL, CURRENT_DATE),
    (UUID(), 'parent_attendance', v_parent_attendance, p_academic_year, p_quarter, CURRENT_DATE);
  
  -- Log each KPI computation
  INSERT INTO KPI_COMPUTATION_LOG (
    kpi_computation_id, kpi_type, computation_formula, computed_value,
    quarter, academic_year, computed_by, computed_date, is_automated
  ) VALUES
    (UUID(), 'total_enrollment', 'SUM(student_count)', v_enrollment, p_quarter, p_academic_year, v_admin_user_id, CURRENT_TIMESTAMP, TRUE),
    (UUID(), 'submission_compliance', 'submitted/required * 100', v_compliance, p_quarter, p_academic_year, v_admin_user_id, CURRENT_TIMESTAMP, TRUE),
    (UUID(), 'average_mps', 'AVG(mps)', v_avg_mps, p_quarter, p_academic_year, v_admin_user_id, CURRENT_TIMESTAMP, TRUE),
    (UUID(), 'survival_rate', 'AVG(students_to/students_from * 100)', v_survival, NULL, p_academic_year, v_admin_user_id, CURRENT_TIMESTAMP, TRUE),
    (UUID(), 'parent_attendance', 'AVG(actual/expected * 100)', v_parent_attendance, p_quarter, p_academic_year, v_admin_user_id, CURRENT_TIMESTAMP, TRUE);
    
END$$
DELIMITER ;
```

---

## BUSINESS RULES FOR COMPUTATIONS

### MPS Computation Rules

#### 1. Minimum Assessments Required
- **Written Work:** At least 2 assessments per quarter
- **Performance Task:** At least 2 assessments per quarter
- **Quarterly Assessment:** Exactly 1 assessment (final exam)

If minimum not met: Grade = "INCOMPLETE" (INC)

#### 2. Incomplete Grade Handling
- INC must be completed within 2 weeks after quarter end
- If not completed: Default grade = 60 (Did Not Meet Expectations)
- System sends automatic notifications to teacher and admin

#### 3. Grade Rounding
- All computations use 2 decimal places during calculation
- Final transmuted grade is whole number (no decimals)
- Rounding: Standard mathematical rounding (0.5 and above rounds up)

#### 4. Passing Grade Threshold
- Minimum passing grade: 75
- Grade < 75 = "Did Not Meet Expectations"
- Grade >= 75 = Passed with appropriate descriptor

#### 5. Component Weight Validation
- Weights must total exactly 100%
- System validates before allowing computation
- Core vs Non-Core subject differentiation enforced

### KPI Computation Rules

#### 1. Data Freshness
- Dashboard displays: Cached KPI (max 1 hour old)
- On-demand computation: Real-time calculation
- Quarter end: Force recompute all KPIs
- Manual refresh: Available to administrators

#### 2. Null Value Handling
- NULL values excluded from averages
- Zero values included if explicitly recorded
- Missing data: Display "Insufficient Data" instead of 0

#### 3. Audit Trail Requirements
- All computations must be logged
- Input data snapshot stored in JSON format
- Computation method versioned
- Timestamp and user recorded for all calculations

#### 4. Computation Schedule
- **Daily:** Enrollment count, time records
- **Weekly:** Submission compliance, attendance
- **Per Quarter:** MPS, grades, level performance
- **Annual:** Survival rate, year-end KPIs

#### 5. Error Handling
- Division by zero: Return NULL instead of error
- Missing reference data: Log error, notify admin
- Data inconsistency: Flag for manual review
- Failed computation: Retry 3 times, then alert

---

## COMPUTATION SCHEDULE

### Daily Automated Tasks (12:00 AM)
```sql
-- Update enrollment count
CALL sp_update_enrollment_kpi();

-- Process time-in records
CALL sp_process_daily_attendance();

-- Update canteen records
CALL sp_update_canteen_summary();
```

### Weekly Automated Tasks (Sunday 11:00 PM)
```sql
-- Compute teacher submission rates
CALL sp_update_all_teacher_submission_rates();

-- Compute document compliance KPI
CALL sp_compute_submission_compliance_kpi();

-- Generate attendance summaries
CALL sp_generate_weekly_attendance_summary();
```

### Per Quarter Tasks (Last day of quarter)
```sql
-- Finalize all student grades
CALL sp_finalize_quarter_grades(current_quarter);

-- Compute all class MPS
CALL sp_compute_all_class_mps(current_quarter);

-- Aggregate performance by level
CALL sp_aggregate_all_level_performance(current_quarter);

-- Update all KPIs
CALL sp_compute_all_kpis(current_academic_year, current_quarter);

-- Generate quarter-end reports
CALL sp_generate_quarter_end_reports(current_quarter);
```

### Annual Tasks (End of school year - May 31)
```sql
-- Compute survival rates
CALL sp_compute_survival_rates(current_academic_year);

-- Generate annual KPI summary
CALL sp_compute_annual_kpis(current_academic_year);

-- Archive completed academic year data
CALL sp_archive_academic_year_data(current_academic_year);

-- Prepare for new academic year
CALL sp_initialize_new_academic_year(next_academic_year);
```

---

**End of MPS & KPI Computation Documentation**
