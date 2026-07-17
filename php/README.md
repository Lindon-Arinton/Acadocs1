# ACADOCS — School Management Information System (CodeIgniter 4)

A CodeIgniter 4 MVC application — same functionality, pages, and REST API as the
original hand-rolled PHP version, restructured into CI4 controllers, models,
views, migrations, and seeders.

---

## Tech Stack

| Layer      | Technology                          |
|------------|-------------------------------------|
| Framework  | CodeIgniter 4.7                     |
| Backend    | PHP 8.1+                            |
| Database   | MySQL / MariaDB 10.4+               |
| Frontend   | Bootstrap 5.3, Bootstrap Icons 1.11 |
| Charts     | Chart.js 4.4                        |
| Fonts      | Google Fonts — Inter                |

---

## Requirements

- PHP 8.1+ with `intl`, `mbstring`, `mysqli` extensions
- Composer
- MySQL/MariaDB 10.4+

---

## Setup

### 1. Install dependencies

```bash
composer install
```

### 2. Configure the environment

Copy `env` to `.env` (already done in this repo) and edit the database block:

```ini
CI_ENVIRONMENT = development
app.baseURL = 'http://localhost:8080/'

database.default.hostname = 127.0.0.1
database.default.database = acadocs
database.default.username = root
database.default.password =
database.default.DBDriver = MySQLi
```

Create the database:

```sql
CREATE DATABASE acadocs CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### 3. Run migrations and seed demo data

```bash
php spark migrate --all
php spark db:seed DatabaseSeeder
```

### 4. Serve the app

```bash
php spark serve
```

Navigate to `http://localhost:8080/` — you'll be redirected to the login page.

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

> Passwords are stored as bcrypt hashes in the `users` table (carried over
> unchanged from the original app, so these credentials keep working as-is).

---

## Project Structure

```
app/
├── Config/
│   ├── Routes.php          — page + API route definitions
│   └── Filters.php         — registers the `authGuard` filter
├── Controllers/
│   ├── Auth.php, Dashboard.php, TeacherDashboard.php, ... (one per page)
│   └── Api/                — JSON REST controllers, one per resource
├── Models/                 — one Model per table (17 total)
├── Views/
│   ├── layout/header.php, layout/footer.php  — shared chrome (sidebar/topbar)
│   ├── auth/login.php
│   └── pages/*.php         — one view per page
├── Helpers/acadocs_helper.php — e(), currentUser(), hasRole()
├── Filters/AuthGuard.php   — login-required guard (session-based)
└── Database/
    ├── Migrations/         — one migration per table group
    └── Seeds/              — DatabaseSeeder + per-table seeders (demo data)

public/
├── index.php               — front controller
└── assets/css/app.css      — design tokens, sidebar, cards, badges
```

---

## Database Schema

| Table                     | Description                                      |
|----------------------------|--------------------------------------------------|
| `users`                   | Login accounts with bcrypt passwords + roles     |
| `teachers`                | Staff roster                                     |
| `teacher_subjects`        | Many-to-many: teachers ↔ subjects                |
| `announcements`           | School-wide announcements, forms, questionnaires |
| `documents`                | Teacher-submitted DLLs and lesson plans          |
| `document_feedback`        | Principal feedback on submitted documents        |
| `kpi_snapshots`            | School-wide KPI metrics per school year          |
| `enrollment_by_level`      | Student headcount per grade level                |
| `performance_by_level`     | MPS/NDS scores per grade level                   |
| `performance_by_subject`   | MPS per subject and instructor                   |
| `parent_meetings`          | PTA conference attendance records                |
| `document_links`           | Secretary-managed external resource links        |
| `canteen_records`          | Weekly canteen revenue/expense (generated `net_income`) |
| `school_funds`             | School fund disbursement ledger                  |
| `time_records`             | Daily employee time-in/time-out attendance       |
| `deped_documents`          | DepEd-required forms with completion tracking    |
| `room_properties`          | Room-by-room asset and equipment inventory       |

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

Enforced per-controller via the `hasRole()` helper, matching the original app's
inline checks. All page and API routes additionally require an authenticated
session via the `authGuard` route filter.

---

## URL Routes

Unchanged from the original app — see `app/Config/Routes.php`:

```
/login, /logout, /dashboard, /teacher-dashboard, /submit-documents,
/documents, /performance, /enrollment-kpis, /announcements,
/parent-meetings, /financial-reports, /time-records, /deped-documents,
/document-links, /property-management, /users
```

---

## API Endpoints

All endpoints require an active session (log in via `/login` or
`POST /api/auth/login` first). Responses are JSON — same contracts as the
original app.

```
POST   /api/auth/login              { email, password }
POST   /api/auth/logout
GET    /api/auth/me

GET    /api/announcements
POST   /api/announcements           { type, title, content, date }
PUT    /api/announcements?id=X
DELETE /api/announcements?id=X

GET    /api/teachers
POST   /api/teachers                { employee_id, name, email, grade_level, subjects[] }
PUT    /api/teachers?id=X
DELETE /api/teachers?id=X

GET    /api/documents[?teacher_id=X]
GET    /api/documents?id=X          (includes feedback)
POST   /api/documents               { teacher_id, type, subject, grade_level }
POST   /api/documents/feedback?id=X { author, comment }
PUT    /api/documents?id=X          { type, subject, grade_level, status }
DELETE /api/documents?id=X

GET    /api/performance?school_year=2025-2026

GET    /api/financial?type=canteen
GET    /api/financial?type=funds
POST   /api/financial?type=canteen  { date, description, revenue, expenses, transaction_count }
POST   /api/financial?type=funds    { date, category, description, amount }
DELETE /api/financial?type=canteen&id=X

GET    /api/time-records[?date=YYYY-MM-DD]
POST   /api/time-records            { date, employee_name, employee_id, time_in, time_out, status }
PUT    /api/time-records?id=X
DELETE /api/time-records?id=X

GET    /api/deped-documents
POST   /api/deped-documents
PUT    /api/deped-documents?id=X    { status, completion_rate }
DELETE /api/deped-documents?id=X

GET    /api/properties[?building=X&room=Y]
POST   /api/properties              { room_number, building_name, item_name, quantity, condition_status }
PUT    /api/properties?id=X
DELETE /api/properties?id=X

GET    /api/document-links[?category=X]
POST   /api/document-links          { category, title, description, url, access_level }
DELETE /api/document-links?id=X

GET    /api/parent-meetings
POST   /api/parent-meetings         { title, date, expected_parents, actual_attendance }
PUT    /api/parent-meetings?id=X
DELETE /api/parent-meetings?id=X
```

---

## Notes on the CI4 port

- **CSRF protection is left disabled**, matching the original app's forms
  (they carry no CSRF token field). Enabling `Config\Filters::$globals['before']
  = ['csrf']` is a reasonable follow-up but was out of scope for this port.
- **No file uploads**: documents are metadata rows only, same as the original.
