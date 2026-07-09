-- ============================================================
-- School Management System — MySQL Schema + Seed Data
-- ============================================================

-- Use the same database name as the application (acadocs)
-- Drop and recreate the database itself so every run starts from a completely
-- clean state (this is what actually fixes errno 150 caused by stale/partial
-- foreign key metadata left over from a previous incomplete run).
DROP DATABASE IF EXISTS acadocs;
CREATE DATABASE acadocs CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE acadocs;

-- Ensure InnoDB and utf8mb4 are used so foreign keys work correctly
SET default_storage_engine=INNODB;
SET NAMES utf8mb4;

-- Make import idempotent: disable checks while building the schema
SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0;
SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;
SET @OLD_SQL_MODE=@@SQL_MODE;
SET SQL_MODE='NO_AUTO_VALUE_ON_ZERO';


-- ------------------------------------------------------------
-- USERS
-- ------------------------------------------------------------
CREATE TABLE users (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(100)  NOT NULL,
    email       VARCHAR(150)  NOT NULL UNIQUE,
    password    VARCHAR(255)  NOT NULL,          -- bcrypt hash
    role        ENUM('admin','teacher','secretary','canteen','disbursing','adas') NOT NULL,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO users (name, email, password, role) VALUES
('Principal',      'principal@school.edu',        '$2y$10$mZiXiBtaycSs2DmiVudkq.NT6PlyGsTBSLisKR0G6tH6HQzsVL2Au', 'admin'),
('Maria Santos',   'maria.santos@school.edu',     '$2y$10$ssBWXqjz0SdbD5AOnxxSwONXi5u.hzyvhTC/VPnB.33yGIH1zS0fG', 'teacher'),
('Juan dela Cruz', 'juan.delacruz@school.edu',    '$2y$10$ssBWXqjz0SdbD5AOnxxSwONXi5u.hzyvhTC/VPnB.33yGIH1zS0fG', 'teacher'),
('Carmen Lopez',   'secretary@school.edu',        '$2y$10$iVemOAuYsrWtDhAe3BmrJe2qYKXSUFiTNFDH851mKd31CMTTxmDYi', 'secretary'),
('Roberto Santos', 'canteen@school.edu',          '$2y$10$.PHXEGPC7nwrTiWmOaIRHetxLP7qLKBJ0Hhd.1CrwXf8T1SCgKyXy', 'canteen'),
('Linda Aquino',   'disbursing@school.edu',       '$2y$10$hziMkTHuEGpUYmsCvwjb8emAgc4IPNXX5AV3V5QR5zGFOQXFD.M9e', 'disbursing'),
('Jose Ramirez',   'adas@school.edu',             '$2y$10$EoWuuEchqWy.jPRi50J/8e1ucyvKvJFL6xcq3YJAshBThctyEzOz.', 'adas');

-- Plain-text passwords for reference (never store these):
-- principal@school.edu  → admin123
-- *@school.edu teachers → teacher123
-- secretary@school.edu  → sec123
-- canteen@school.edu    → canteen123
-- disbursing@school.edu → disb123
-- adas@school.edu       → adas123

-- ------------------------------------------------------------
-- TEACHERS (staff roster — links to users where applicable)
-- ------------------------------------------------------------
CREATE TABLE teachers (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    employee_id     VARCHAR(20)  NOT NULL UNIQUE,
    name            VARCHAR(100) NOT NULL,
    email           VARCHAR(150) NOT NULL UNIQUE,
    grade_level     VARCHAR(50)  NOT NULL,
    submission_rate DECIMAL(5,2) DEFAULT 0.00,
    user_id         INT UNSIGNED NULL,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE teacher_subjects (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    teacher_id  INT UNSIGNED NOT NULL,
    subject     VARCHAR(100) NOT NULL,
    FOREIGN KEY (teacher_id) REFERENCES teachers(id) ON DELETE CASCADE
);

INSERT INTO teachers (employee_id, name, email, grade_level, submission_rate, user_id) VALUES
('T-001','Maria Santos',       'maria.santos@school.edu',       'Grade 7',    95.00, 2),
('T-002','Juan dela Cruz',     'juan.delacruz@school.edu',      'Grade 7',    88.00, 3),
('T-003','Ana Reyes',          'ana.reyes@school.edu',          'Grade 8',    92.00, NULL),
('T-004','Pedro Garcia',       'pedro.garcia@school.edu',       'Grade 8',   100.00, NULL),
('T-005','Rosa Mendoza',       'rosa.mendoza@school.edu',       'Grade 9',    78.00, NULL),
('T-006','Carlos Bautista',    'carlos.bautista@school.edu',    'Grade 9',    85.00, NULL),
('T-007','Sofia Torres',       'sofia.torres@school.edu',       'Grade 10',   90.00, NULL),
('T-008','Miguel Ramos',       'miguel.ramos@school.edu',       'Grade 10',   97.00, NULL),
('T-009','Elena Cruz',         'elena.cruz@school.edu',         'Grade 7',    94.00, NULL),
('T-010','Roberto Lim',        'roberto.lim@school.edu',        'Grade 7',    89.00, NULL),
('T-011','Teresa Valdez',      'teresa.valdez@school.edu',      'Grade 7',    91.00, NULL),
('T-012','Ricardo Santos',     'ricardo.santos@school.edu',     'Grade 7',    96.00, NULL),
('T-013','Isabel Martinez',    'isabel.martinez@school.edu',    'Grade 8',    87.00, NULL),
('T-014','Fernando Diaz',      'fernando.diaz@school.edu',      'Grade 8',    93.00, NULL),
('T-015','Corazon Flores',     'corazon.flores@school.edu',     'Grade 8',    88.00, NULL),
('T-016','Antonio Rivera',     'antonio.rivera@school.edu',     'Grade 8',    99.00, NULL),
('T-017','Margarita Luna',     'margarita.luna@school.edu',     'Grade 9',    82.00, NULL),
('T-018','Benjamin Castro',    'benjamin.castro@school.edu',    'Grade 9',    86.00, NULL),
('T-019','Cristina Morales',   'cristina.morales@school.edu',   'Grade 9',    91.00, NULL),
('T-020','Francisco Gomez',    'francisco.gomez@school.edu',    'Grade 9',    95.00, NULL),
('T-021','Angelica Reyes',     'angelica.reyes@school.edu',     'Grade 10',   90.00, NULL),
('T-022','Gabriel Ramos',      'gabriel.ramos@school.edu',      'Grade 10',   88.00, NULL),
('T-023','Victoria Santos',    'victoria.santos@school.edu',    'Grade 10',   92.00, NULL),
('T-024','Domingo Cruz',       'domingo.cruz@school.edu',       'Grade 10',   97.00, NULL),
('T-025','Luz Fernandez',      'luz.fernandez@school.edu',      'Grade 8',    84.00, NULL),
('T-026','Emilio Torres',      'emilio.torres@school.edu',      'Grade 8',    89.00, NULL),
('T-027','Patricia Gutierrez', 'patricia.gutierrez@school.edu', 'Grade 10',   93.00, NULL),
('T-028','Alfredo Medina',     'alfredo.medina@school.edu',     'Grade 10',   91.00, NULL),
('T-029','Carmen Lopez',       'carmen.lopez@school.edu',       'Grade 7',    86.00, NULL),
('T-030','Jose Ramirez',       'jose.ramirez@school.edu',       'Grade 7',    94.00, NULL),
('T-031','Rosario Ortiz',      'rosario.ortiz@school.edu',      'Grade 9',    88.00, NULL),
('T-032','Luis Chavez',        'luis.chavez@school.edu',        'Grade 9',    90.00, NULL),
('T-033','Linda Aquino',       'linda.aquino@school.edu',       'All Levels', 85.00, NULL);

INSERT INTO teacher_subjects (teacher_id, subject) VALUES
(1,'Mathematics'),(2,'Science'),(3,'English'),(4,'Filipino'),
(5,'Mathematics'),(6,'Science'),(7,'English'),(8,'Filipino'),
(9,'Araling Panlipunan'),(10,'MAPEH'),(11,'TLE'),(12,'ESP'),
(13,'Araling Panlipunan'),(14,'MAPEH'),(15,'TLE'),(16,'ESP'),
(17,'Araling Panlipunan'),(18,'MAPEH'),(19,'TLE'),(20,'ESP'),
(21,'Araling Panlipunan'),(22,'MAPEH'),(23,'TLE'),(24,'ESP'),
(25,'Mathematics'),(26,'Science'),(27,'Mathematics'),(28,'Science'),
(29,'English'),(30,'Filipino'),(31,'English'),(32,'Filipino'),
(33,'Computer');

-- ------------------------------------------------------------
-- ANNOUNCEMENTS
-- ------------------------------------------------------------
CREATE TABLE announcements (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    type        ENUM('Announcement','Questionnaires','Forms') NOT NULL,
    title       VARCHAR(255) NOT NULL,
    content     TEXT         NOT NULL,
    date        DATE         NOT NULL,
    status      ENUM('active','inactive') DEFAULT 'active',
    created_by  INT UNSIGNED NULL,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
);

INSERT INTO announcements (type, title, content, date, status) VALUES
('Announcement','Faculty Meeting - March 31, 2026','All teachers are required to attend the faculty meeting in the conference room at 3:00 PM.','2026-03-28','active'),
('Questionnaires','Teacher Performance Survey','Please complete the term performance survey by April 5, 2026.','2026-03-25','active'),
('Forms','Document Submission Reminder','Reminder: All DLLs and Lesson Plans for March must be submitted by end of day.','2026-03-29','active');

-- ------------------------------------------------------------
-- DOCUMENTS (teacher submissions)
-- ------------------------------------------------------------
CREATE TABLE documents (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    teacher_id      INT UNSIGNED NOT NULL,
    type            ENUM('DLL','Lesson Plan','Assessment','Report') NOT NULL,
    subject         VARCHAR(100) NOT NULL,
    grade_level     VARCHAR(50)  NOT NULL,
    date_submitted  DATETIME     NOT NULL,
    status          ENUM('Submitted','Reviewed','Returned','Pending') DEFAULT 'Pending',
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (teacher_id) REFERENCES teachers(id) ON DELETE CASCADE
);

CREATE TABLE document_feedback (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    document_id INT UNSIGNED NOT NULL,
    author      VARCHAR(100) NOT NULL,
    comment     TEXT         NOT NULL,
    date        DATE         NOT NULL,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (document_id) REFERENCES documents(id) ON DELETE CASCADE
);

INSERT INTO documents (teacher_id, type, subject, grade_level, date_submitted, status) VALUES
(1, 'DLL',         'Mathematics', 'Grade 7', '2026-03-28 09:30:00', 'Submitted'),
(2, 'Lesson Plan', 'Science',     'Grade 7', '2026-03-27 14:15:00', 'Reviewed'),
(3, 'DLL',         'English',     'Grade 8', '2026-03-29 11:20:00', 'Submitted'),
(4, 'Lesson Plan', 'Filipino',    'Grade 8', '2026-03-26 16:45:00', 'Reviewed');

INSERT INTO document_feedback (document_id, author, comment, date) VALUES
(2, 'Principal', 'Well structured. Please add more assessment criteria.', '2026-03-28'),
(4, 'Principal', 'Excellent integration of cultural elements.',            '2026-03-27');

-- ------------------------------------------------------------
-- ENROLLMENT KPIs
-- ------------------------------------------------------------
CREATE TABLE kpi_snapshots (
    id                   INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    school_year          VARCHAR(20)   NOT NULL,
    total_enrollment     INT UNSIGNED  NOT NULL,
    submission_compliance DECIMAL(5,2) NOT NULL,
    average_mps          DECIMAL(5,2)  NOT NULL,
    dropout_count        INT UNSIGNED  NOT NULL,
    parent_attendance    DECIMAL(5,2)  NOT NULL,
    recorded_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO kpi_snapshots (school_year, total_enrollment, submission_compliance, average_mps, dropout_count, parent_attendance) VALUES
('2025-2026', 1247, 87.50, 82.30, 23, 76.20);

CREATE TABLE enrollment_by_level (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    school_year VARCHAR(20)  NOT NULL,
    grade_level VARCHAR(50)  NOT NULL,
    students    INT UNSIGNED NOT NULL,
    sections    INT UNSIGNED NOT NULL
);

INSERT INTO enrollment_by_level (school_year, grade_level, students, sections) VALUES
('2025-2026','Grade 7', 320, 8),
('2025-2026','Grade 8', 315, 8),
('2025-2026','Grade 9', 307, 8),
('2025-2026','Grade 10',305, 8);

-- ------------------------------------------------------------
-- PERFORMANCE
-- ------------------------------------------------------------
CREATE TABLE performance_by_level (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    school_year VARCHAR(20)  NOT NULL,
    grade_level VARCHAR(50)  NOT NULL,
    mps         DECIMAL(5,2) NOT NULL,
    nds         DECIMAL(5,2) NOT NULL
);

INSERT INTO performance_by_level (school_year, grade_level, mps, nds) VALUES
('2025-2026','Grade 7', 84.20, 78.50),
('2025-2026','Grade 8', 82.70, 76.30),
('2025-2026','Grade 9', 80.10, 74.80),
('2025-2026','Grade 10',83.50, 79.20);

CREATE TABLE performance_by_subject (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    school_year VARCHAR(20)  NOT NULL,
    subject     VARCHAR(100) NOT NULL,
    grade_level VARCHAR(50)  NOT NULL,
    instructor  VARCHAR(100) NOT NULL,
    mps         DECIMAL(5,2) NOT NULL
);

INSERT INTO performance_by_subject (school_year, subject, grade_level, instructor, mps) VALUES
('2025-2026','Mathematics','Grade 7', 'Maria Santos',  76.50),
('2025-2026','Science',    'Grade 7', 'Juan dela Cruz', 85.20),
('2025-2026','English',    'Grade 8', 'Ana Reyes',      83.70),
('2025-2026','Filipino',   'Grade 8', 'Pedro Garcia',   88.10),
('2025-2026','Mathematics','Grade 9', 'Rosa Mendoza',   74.30),
('2025-2026','Science',    'Grade 9', 'Carlos Bautista',81.90),
('2025-2026','English',    'Grade 10','Sofia Torres',   86.40),
('2025-2026','Filipino',   'Grade 10','Miguel Ramos',   89.20);

-- ------------------------------------------------------------
-- PARENT MEETINGS
-- ------------------------------------------------------------
CREATE TABLE parent_meetings (
    id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title            VARCHAR(255) NOT NULL,
    date             DATE         NOT NULL,
    expected_parents INT UNSIGNED NOT NULL,
    actual_attendance INT UNSIGNED NOT NULL,
    attendance_rate  DECIMAL(5,2) NOT NULL,
    created_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO parent_meetings (title, date, expected_parents, actual_attendance, attendance_rate) VALUES
('First Term Parent-Teacher Conference',  '2026-02-15', 450, 342, 76.00),
('Second Term Parent-Teacher Conference', '2026-03-15', 450, 389, 86.40);

-- ------------------------------------------------------------
-- DOCUMENT LINKS (Secretary)
-- ------------------------------------------------------------
CREATE TABLE document_links (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    category     ENUM('Forms','Questionnaires','Templates','Guidelines') NOT NULL,
    title        VARCHAR(255) NOT NULL,
    description  TEXT,
    url          VARCHAR(500) NOT NULL,
    added_by     VARCHAR(100) NOT NULL,
    date_added   DATE         NOT NULL,
    access_level ENUM('All Users','Teachers','Admin') DEFAULT 'All Users',
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO document_links (category, title, description, url, added_by, date_added, access_level) VALUES
('Forms',          'Teacher Performance Evaluation Form','Annual performance evaluation form for all teaching staff','https://docs.google.com/forms/d/sample-form-1',          'Carmen Lopez','2026-03-15','All Users'),
('Questionnaires', 'Student Satisfaction Survey',        'Term survey to assess student satisfaction and needs',     'https://docs.google.com/forms/d/sample-survey-1',        'Carmen Lopez','2026-03-20','All Users'),
('Templates',      'DLL Template 2026',                  'Official Daily Lesson Log template for SY 2025-2026',     'https://docs.google.com/spreadsheets/d/sample-dll-template','Carmen Lopez','2026-01-10','Teachers'),
('Guidelines',     'DepEd Memorandum 023-2026',          'Latest guidelines on curriculum implementation',          'https://drive.google.com/file/d/sample-memo-1',           'Carmen Lopez','2026-02-28','All Users'),
('Forms',          'Leave Application Form',             'Standard leave application form for all personnel',       'https://docs.google.com/forms/d/sample-leave-form',      'Carmen Lopez','2026-01-05','All Users');

-- ------------------------------------------------------------
-- CANTEEN FINANCIAL RECORDS
-- ------------------------------------------------------------
CREATE TABLE canteen_records (
    id                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    date              DATE           NOT NULL,
    description       VARCHAR(255)   NOT NULL,
    revenue           DECIMAL(12,2)  NOT NULL DEFAULT 0.00,
    expenses          DECIMAL(12,2)  NOT NULL DEFAULT 0.00,
    net_income        DECIMAL(12,2)  GENERATED ALWAYS AS (revenue - expenses) STORED,
    transaction_count INT UNSIGNED   NOT NULL DEFAULT 0,
    created_at        TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO canteen_records (date, description, revenue, expenses, transaction_count) VALUES
('2026-03-01','Daily Sales - Breakfast & Snacks', 8450.00, 3200.00, 145),
('2026-03-08','Daily Sales - Breakfast & Snacks', 9100.00, 3500.00, 162),
('2026-03-15','Daily Sales - Breakfast & Snacks', 8750.00, 3400.00, 158),
('2026-03-22','Daily Sales - Breakfast & Snacks', 9300.00, 3600.00, 171),
('2026-03-29','Daily Sales - Breakfast & Snacks', 8950.00, 3450.00, 165);

-- ------------------------------------------------------------
-- SCHOOL FUNDS (Disbursing Officer)
-- ------------------------------------------------------------
CREATE TABLE school_funds (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    date        DATE          NOT NULL,
    category    ENUM('MOOE','Capital Outlay','Maintenance','Other') NOT NULL,
    description VARCHAR(255)  NOT NULL,
    particulars TEXT,
    amount      DECIMAL(12,2) NOT NULL,    -- negative = disbursement
    balance     DECIMAL(12,2) NOT NULL,
    prepared_by VARCHAR(100)  NOT NULL,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO school_funds (date, category, description, particulars, amount, balance, prepared_by) VALUES
('2026-03-05','MOOE',           'Office Supplies Purchase', 'Bond paper, ink, pens, folders',          -15420.00, 284580.00,'Linda Aquino'),
('2026-03-12','Capital Outlay', 'Laboratory Equipment',     'Science lab microscopes (5 units)',        -85000.00, 199580.00,'Linda Aquino'),
('2026-03-15','MOOE',           'Utilities Payment',        'Electricity - March 2026',                 -42300.00, 157280.00,'Linda Aquino'),
('2026-03-20','Maintenance',    'Building Repairs',         'Roof repair - Building A',                 -28500.00, 128780.00,'Linda Aquino'),
('2026-03-25','MOOE',           'Cleaning Supplies',        'Janitorial and sanitation supplies',        -8750.00, 120030.00,'Linda Aquino');

-- ------------------------------------------------------------
-- TIME RECORDS (ADAS)
-- ------------------------------------------------------------
CREATE TABLE time_records (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    date          DATE        NOT NULL,
    employee_name VARCHAR(100) NOT NULL,
    employee_id   VARCHAR(20)  NOT NULL,
    time_in       TIME         NULL,
    time_out      TIME         NULL,
    status        ENUM('Present','Late','Absent','On Leave') NOT NULL,
    remarks       TEXT,
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO time_records (date, employee_name, employee_id, time_in, time_out, status, remarks) VALUES
('2026-03-31','Maria Santos',   'T-001','07:15:00','16:30:00','Present', ''),
('2026-03-31','Juan dela Cruz', 'T-002','07:20:00','16:35:00','Present', ''),
('2026-03-31','Ana Reyes',      'T-003','07:10:00','16:25:00','Present', ''),
('2026-03-31','Pedro Garcia',   'T-004','07:25:00','16:40:00','Present', ''),
('2026-03-31','Rosa Mendoza',   'T-005','08:45:00','16:30:00','Late',    'Traffic due to road construction'),
('2026-03-31','Carlos Bautista','T-006', NULL,      NULL,     'Absent',  'Sick Leave - Medical Certificate Submitted');

-- ------------------------------------------------------------
-- DEPED REQUIRED DOCUMENTS
-- ------------------------------------------------------------
CREATE TABLE deped_documents (
    id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    document_type    VARCHAR(100) NOT NULL,
    description      VARCHAR(255) NOT NULL,
    due_date         DATE         NOT NULL,
    status           ENUM('Pending','In Progress','Completed') DEFAULT 'Pending',
    completion_rate  TINYINT UNSIGNED NOT NULL DEFAULT 0,   -- 0-100
    prepared_by      VARCHAR(100) NOT NULL,
    last_updated     DATE         NOT NULL,
    created_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO deped_documents (document_type, description, due_date, status, completion_rate, prepared_by, last_updated) VALUES
('School Form 1 (SF1)',                    'School Register',                          '2026-04-15','In Progress', 75, 'Jose Ramirez','2026-03-30'),
('School Form 2 (SF2)',                    'Daily Attendance Report',                  '2026-04-05','Completed',  100, 'Jose Ramirez','2026-03-28'),
('School Form 3 (SF3)',                    'Individual Learner Monitoring',             '2026-04-20','In Progress', 60, 'Jose Ramirez','2026-03-29'),
('Learners Enrollment Survey Form (LESF)', 'Annual enrollment data submission',         '2026-05-01','Pending',     25, 'Jose Ramirez','2026-03-25'),
('Annual Implementation Plan (AIP)',       'School improvement plan for SY 2026-2027', '2026-05-15','Pending',     15, 'Jose Ramirez','2026-03-20');

-- ------------------------------------------------------------
-- PROPERTY MANAGEMENT
-- ------------------------------------------------------------
CREATE TABLE room_properties (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    room_number     VARCHAR(50)  NOT NULL,
    building_name   VARCHAR(100) NOT NULL,
    item_name       VARCHAR(150) NOT NULL,
    quantity        INT UNSIGNED NOT NULL DEFAULT 1,
    condition_status ENUM('Excellent','Good','Fair','Poor') NOT NULL,
    last_inspection DATE         NOT NULL,
    remarks         TEXT,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO room_properties (room_number, building_name, item_name, quantity, condition_status, last_inspection, remarks) VALUES
('Room 101',    'Building A',   'Student Chairs',    45, 'Good',      '2026-03-15', 'Recently painted'),
('Room 101',    'Building A',   'Teacher Desk',       1, 'Fair',      '2026-03-15', 'Minor scratches'),
('Room 102',    'Building A',   'Whiteboard',         1, 'Excellent', '2026-03-15', 'Newly installed'),
('Science Lab', 'Building B',   'Microscopes',       15, 'Excellent', '2026-03-20', 'Newly purchased'),
('Science Lab', 'Building B',   'Lab Tables',         8, 'Good',      '2026-03-20', 'Heat-resistant surface'),
('Computer Lab','Building C',   'Desktop Computers', 30, 'Fair',      '2026-03-10', '3 units need repair'),
('Library',     'Main Building','Bookshelves',       12, 'Good',      '2026-03-05', 'Sturdy metal frames'),
('Library',     'Main Building','Reading Tables',    10, 'Good',      '2026-03-05', 'Can seat 6 students each');

-- Restore SQL mode and re-enable checks
SET SQL_MODE=@OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS;
