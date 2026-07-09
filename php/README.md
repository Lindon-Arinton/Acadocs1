# ACADOCS — School Management Information System

A full PHP/HTML/CSS/Bootstrap/JavaScript web application with MySQL database, mirroring the design and interactivity of the React-based ACADOCS system.

---

## Tech Stack

| Layer      | Technology                          |
|------------|-------------------------------------|
| Backend    | PHP 8.1+                            |
| Database   | MySQL 8.0+                          |
| Frontend   | Bootstrap 5.3, Bootstrap Icons 1.11 |
| Charts     | Chart.js 4.4                        |
| Fonts      | Google Fonts — Inter                |
| Server     | Apache with `mod_rewrite`           |

---

## Requirements

- PHP 8.1 or higher (with PDO and PDO_MySQL extensions)
- MySQL 8.0 or higher
- Apache with `mod_rewrite` enabled
- `AllowOverride All` set for the document root

---

## Setup

### 1. Clone / Copy files

Place the contents of this `php/` folder into your Apache document root (e.g. `/var/www/html/acadocs/`) or a virtual host directory.

### 2. Import the database

```bash
mysql -u root -p < schema.sql
```

Or import via phpMyAdmin: select the database and import `schema.sql`.

### 3. Configure the database

Edit `config.php` and update the constants at the top:

```php
define('DB_HOST',  'localhost');
define('DB_NAME',  'school_db');
define('DB_USER',  'root');
define('DB_PASS',  '');
define('BASE_URL', '/');        // change if hosted in a subfolder, e.g. '/acadocs/'
```

### 4. Enable Apache `mod_rewrite`

```bash
sudo a2enmod rewrite
sudo systemctl restart apache2
```

Make sure your Apache config has:

```apache
<Directory /var/www/html/acadocs>
    AllowOverride All
</Directory>
```

### 5. Open in browser

Navigate to `http://localhost/` (or your configured domain). You will be redirected to the login page.

---

## Login Credentials

| Role       | Email                          | Password      |
|------------|--------------------------------|---------------|
| Admin      | principal@school.edu           | admin123      |
| Teacher    | maria.santos@school.edu        | teacher123    |
| Teacher    | juan.delacruz@school.edu       | teacher123    |
| Secretary  | secretary@school.edu           | sec123        |
| Canteen    | canteen@school.edu             | canteen123    |
| Disbursing | disbursing@school.edu          | disb123       |
| ADAS       | adas@school.edu                | adas123       |

> Passwords are stored as bcrypt hashes in the `users` table.

---

## Project Structure

```
php/
├── .htaccess               — Apache URL routing (pages + API)
├── config.php              — Database connection, session helpers, utilities
├── schema.sql              — MySQL schema + seed data
├── README.md               — This file
│
├── assets/
│   └── css/
│       └── app.css         — Custom CSS (design tokens, sidebar, cards, badges)
│
├── auth/
│   ├── login.php           — Login form (POST handler + HTML)
│   └── logout.php          — Destroys session, redirects to /login
│
├── includes/
│   ├── header.php          — HTML head, sidebar, topbar (breadcrumb + notifications)
│   └── footer.php          — Bootstrap JS, Chart.js, sidebar toggle scripts
│
├── pages/
│   ├── dashboard.php       — Admin dashboard: KPIs, charts, performance table
│   ├── teacher_dashboard.php — Teacher view: submissions, announcements, quick links
│   ├── announcements.php   — Post/view/delete announcements (admin & secretary)
│   ├── documents.php       — Document review & principal feedback (admin)
│   ├── submit_documents.php — Teacher document submission & feedback view
│   ├── performance.php     — MPS/NDS charts + subject performance table
│   ├── enrollment_kpis.php — Enrollment breakdown chart + stats
│   ├── financial.php       — Canteen records & school fund disbursements (tabbed)
│   ├── time_records.php    — Daily time-in/time-out with status badges
│   ├── deped_documents.php — DepEd forms with progress tracking cards
│   ├── document_links.php  — Categorized external link management
│   ├── parent_meetings.php — PTA conference attendance records + chart
│   ├── properties.php      — Room/building asset inventory
│   └── users.php           — User creation, deletion, password reset (admin only)
│
└── api/                    — JSON REST endpoints (session-authenticated)
    ├── auth.php             — POST /api/auth/login, /logout, GET /me
    ├── announcements.php    — CRUD /api/announcements
    ├── teachers.php         — CRUD /api/teachers (with subjects)
    ├── documents.php        — CRUD /api/documents + feedback
    ├── performance.php      — GET /api/performance?school_year=
    ├── financial.php        — CRUD /api/financial?type=canteen|funds
    ├── time_records.php     — CRUD /api/time-records
    ├── deped_documents.php  — CRUD /api/deped-documents
    ├── properties.php       — CRUD /api/properties
    ├── document_links.php   — CRUD /api/document-links
    └── parent_meetings.php  — CRUD /api/parent-meetings
```

---

## Database Schema

| Table                  | Description                                      |
|------------------------|--------------------------------------------------|
| `users`                | Login accounts with bcrypt passwords + roles     |
| `teachers`             | Staff roster                                     |
| `teacher_subjects`     | Many-to-many: teachers ↔ subjects                |
| `announcements`        | School-wide announcements, forms, questionnaires |
| `documents`            | Teacher-submitted DLLs and lesson plans          |
| `document_feedback`    | Principal feedback on submitted documents        |
| `kpi_snapshots`        | School-wide KPI metrics per school year          |
| `enrollment_by_level`  | Student headcount per grade level                |
| `performance_by_level` | MPS/NDS scores per grade level                   |
| `performance_by_subject` | MPS per subject and instructor                 |
| `parent_meetings`      | PTA conference attendance records                |
| `document_links`       | Secretary-managed external resource links        |
| `canteen_records`      | Weekly canteen revenue and expense records       |
| `school_funds`         | School fund disbursement ledger                  |
| `time_records`         | Daily employee time-in/time-out attendance       |
| `deped_documents`      | DepEd-required forms with completion tracking    |
| `room_properties`      | Room-by-room asset and equipment inventory       |

---

## Role-Based Access

| Feature                  | Admin | Teacher | Secretary | Canteen | Disbursing | ADAS |
|--------------------------|:-----:|:-------:|:---------:|:-------:|:----------:|:----:|
| Admin Dashboard          | ✓     |         |           |         |            |      |
| Teacher Dashboard        |       | ✓       |           |         |            |      |
| Submit Documents         | ✓     | ✓       |           |         |            |      |
| Manage Documents         | ✓     |         |           |         |            |      |
| Performance Analytics    | ✓     |         |           |         |            |      |
| Enrollment KPIs          | ✓     |         |           |         |            |      |
| Announcements            | ✓     | ✓       | ✓         |         |            |      |
| Parent Meetings          | ✓     |         | ✓         |         |            |      |
| Financial Reports        | ✓     |         |           | ✓       | ✓          |      |
| Time Records             | ✓     |         |           |         |            | ✓    |
| DepEd Documents          | ✓     |         |           |         |            | ✓    |
| Document Links           | ✓     | ✓       | ✓         |         |            |      |
| Property Management      | ✓     |         |           |         |            |      |
| User Management          | ✓     |         |           |         |            |      |

---

## URL Routes

| URL                      | Page                        |
|--------------------------|-----------------------------|
| `/login`                 | Login page                  |
| `/logout`                | Logout + redirect           |
| `/` or `/dashboard`      | Admin dashboard             |
| `/teacher-dashboard`     | Teacher dashboard           |
| `/submit-documents`      | Document submission         |
| `/documents`             | Document management         |
| `/performance`           | Performance analytics       |
| `/enrollment-kpis`       | Enrollment KPIs             |
| `/announcements`         | Announcements               |
| `/parent-meetings`       | Parent meetings             |
| `/financial-reports`     | Financial reports           |
| `/time-records`          | Time records                |
| `/deped-documents`       | DepEd documents             |
| `/document-links`        | Document links              |
| `/property-management`   | Property management         |
| `/users`                 | User management             |

---

## API Endpoints

All endpoints require an active PHP session (login first). Responses are JSON.

```
POST   /api/auth/login              { email, password }
POST   /api/auth/logout
GET    /api/auth/me

GET    /api/announcements
POST   /api/announcements           { type, title, content, date }
DELETE /api/announcements?id=X

GET    /api/teachers
POST   /api/teachers                { employee_id, name, email, grade_level, subjects[] }
PUT    /api/teachers?id=X
DELETE /api/teachers?id=X

GET    /api/documents[?teacher_id=X]
GET    /api/documents?id=X          (includes feedback)
POST   /api/documents               { teacher_id, type, subject, grade_level }
PUT    /api/documents?id=X          { type, subject, grade_level, status }

GET    /api/performance?school_year=2025-2026
GET    /api/financial?type=canteen
GET    /api/financial?type=funds
POST   /api/financial?type=canteen  { date, description, revenue, expenses, transaction_count }
POST   /api/financial?type=funds    { date, category, description, amount }

GET    /api/time-records[?date=YYYY-MM-DD]
POST   /api/time-records            { date, employee_name, employee_id, time_in, time_out, status }
PUT    /api/time-records?id=X       { time_in, time_out, status, remarks }

GET    /api/deped-documents
PUT    /api/deped-documents?id=X    { status, completion_rate }

GET    /api/properties[?building=X&room=Y]
POST   /api/properties              { room_number, building_name, item_name, quantity, condition_status }

GET    /api/document-links[?category=X]
POST   /api/document-links          { category, title, description, url, access_level }

GET    /api/parent-meetings
POST   /api/parent-meetings         { title, date, expected_parents, actual_attendance }
```
